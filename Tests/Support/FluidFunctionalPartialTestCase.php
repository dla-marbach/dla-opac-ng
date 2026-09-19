<?php

namespace Dla\DlaOpacNg\Tests\Support;

use TYPO3\CMS\Core\Http\ServerRequest;
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

        $request = (new ServerRequest('https://example.invalid/?id=1'))
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
}
