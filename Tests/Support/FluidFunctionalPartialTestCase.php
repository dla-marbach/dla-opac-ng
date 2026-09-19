<?php

namespace Dla\DlaOpacNg\Tests\Support;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request as ExtbaseRequest;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Prototyp-Basisklasse für Rendering-Tests einzelner Fluid-Partials mit
 * TYPO3-Functional-Bootstrap (echter DI-Container statt manueller Unit-Hacks).
 */
abstract class FluidFunctionalPartialTestCase extends FunctionalTestCase
{
    private const ROOT_PAGE_UID = 1;
    private const REQUEST_PAGE_UID = 2;

    protected function setUp(): void
    {
        parent::setUp();
        // Aus .devfiles/init.sql übernommener Minimal-Auszug (pages uid=1/2),
        // damit Site-Konfiguration und Page-UIDs konsistent zur Dev-Umgebung bleiben.
        $this->importCSVDataSet(dirname(__DIR__) . '/Functional/Fixtures/pages.from-initsql.csv');
        $this->writeSiteConfigurationFromDevEnvironment();
    }

    protected function renderPartial(string $partial, array $variables): string
    {
        $extensionPath = dirname(__DIR__, 2) . '/Resources/Private/';

        $renderingContext = GeneralUtility::makeInstance(RenderingContextFactory::class)->create([
            'partialRootPaths' => [$extensionPath . 'Partials/'],
            'layoutRootPaths' => [$extensionPath . 'Layouts/'],
        ]);

        $extbaseParameters = new ExtbaseRequestParameters();
        $extbaseParameters->setControllerExtensionName('Find');
        $extbaseParameters->setPluginName('Find');
        $extbaseParameters->setControllerName('Search');
        $extbaseParameters->setControllerActionName('index');

        $site = $this->get(SiteFinder::class)->getSiteByPageId(self::REQUEST_PAGE_UID);
        $pageArguments = new PageArguments(self::REQUEST_PAGE_UID, '0', []);

        $request = (new ServerRequest('https://example.invalid/katalog?id=' . self::REQUEST_PAGE_UID))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('site', $site)
            ->withAttribute('language', $site->getDefaultLanguage())
            ->withAttribute('routing', $pageArguments)
            ->withAttribute('extbase', $extbaseParameters)
            ->withQueryParams($variables['arguments'] ?? []);
        $renderingContext->setRequest(new ExtbaseRequest($request));

        $view = new StandaloneView($renderingContext);
        $view->setTemplateSource(
            '{namespace s=Dla\Find\ViewHelpers}'
            . '{namespace dla=Dla\DlaOpacNg\ViewHelpers}'
            . '{namespace v=FluidTYPO3\Vhs\ViewHelpers}'
            . '<f:render partial="' . $partial . '" arguments="{_all}"/>'
        );
        $view->assignMultiple($variables);

        return $view->render();
    }

    private function writeSiteConfigurationFromDevEnvironment(): void
    {
        $siteConfiguration = Yaml::parseFile(dirname(__DIR__, 2) . '/.devfiles/siteconfig.yaml');
        $siteConfiguration['rootPageId'] = self::ROOT_PAGE_UID;
        $siteIdentifier = 'dla-opac-' . substr(md5(static::class), 0, 8);
        $siteWriter = $this->get(SiteWriter::class);

        GeneralUtility::rmdir(Environment::getConfigPath() . '/sites/' . $siteIdentifier, true);
        $siteWriter->write($siteIdentifier, $siteConfiguration);
    }
}
