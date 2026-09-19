<?php

namespace Dla\DlaOpacNg\Tests\Support;

use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request as ExtbaseRequest;
use TYPO3\CMS\Fluid\Core\Cache\FluidTemplateCache;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolverFactory;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Basisklasse für Rendering-Tests einzelner Fluid-Partials dieser Extension,
 * ohne DB/Functional-Bootstrap. Rendert ein Partial mit den übergebenen
 * Variablen und liefert das erzeugte HTML zur Prüfung zurück.
 *
 * Baut die Fluid-RenderingContext-Infrastruktur manuell auf (statt über den
 * regulären Symfony-DI-Container, der im Unit-Test-Bootstrap nicht existiert):
 * ein "FailsafeContainer" reicht aus, weil unsere ViewHelper keine
 * Konstruktor-Injektion benötigen (siehe TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolver).
 */
abstract class FluidPartialTestCase extends UnitTestCase
{
    // LocalizationUtility::translate() registriert intern u.a. einen LogManager als Singleton.
    protected bool $resetSingletonInstances = true;

    private static ?RenderingContextFactory $renderingContextFactory = null;

    private function getRenderingContextFactory(): RenderingContextFactory
    {
        if (self::$renderingContextFactory === null) {
            // Test-Override zuletzt anhängen: ViewHelperResolver probiert Namespace-Klassen
            // in umgekehrter Reihenfolge (array_pop), unser TranslateViewHelper hat also Vorrang
            // vor TYPO3\CMS\Fluid\ViewHelpers\TranslateViewHelper, alle anderen f:*-ViewHelper
            // bleiben unverändert (Klasse existiert dort nicht, resolver fällt zurück).
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['f'][] = 'Dla\\DlaOpacNg\\Tests\\Support\\FluidViewHelperOverrides';

            // Über die globale CacheManager-Singleton-Instanz laufen (statt "new CacheManager()"),
            // damit auch Core-Code, der später GeneralUtility::makeInstance(CacheManager::class)
            // aufruft (z.B. LocalizationUtility::translate() fürs "runtime"-Cache), dieselbe,
            // hier konfigurierte Instanz erhält.
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            $cacheManager->setCacheConfigurations([
                'fluid_template' => [
                    'frontend' => FluidTemplateCache::class,
                    'backend' => NullBackend::class,
                    'options' => [],
                    'groups' => ['all'],
                ],
                'l10n' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => NullBackend::class,
                    'options' => [],
                    'groups' => ['all'],
                ],
                'runtime' => [
                    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class,
                    'options' => [],
                    'groups' => ['all'],
                ],
            ]);

            // LocalizationUtility::translate() baut sich über GeneralUtility::makeInstance()
            // eine LanguageServiceFactory, deren Konstruktor volle DI-Injektion erwartet
            // (nicht per Reflection auflösbar im Unit-Test-Bootstrap). Deshalb hier manuell
            // bauen und als Singleton hinterlegen, damit der Core diese Instanz wiederverwendet.
            //
            // Der Unit-Test-Bootstrap (UnitTestsBootstrap.php) registriert am Ende
            // GeneralUtility::purgeInstances(), was auch die dort gesetzte PackageManager-Singleton
            // wieder entfernt - deshalb hier erneut aufbauen. Wichtig: die reguläre PackageManager-Klasse
            // verwenden (nicht UnitTestPackageManager - deren scanAvailablePackages() ist im
            // Composer-Modus ein No-Op), damit über den Composer-PackageArtifact-Cache tatsächlich
            // alle installierten Extensions (inkl. dla_opac_ng) als aktiv erkannt werden.
            $coreCache = new \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend('core', new NullBackend('production', []));
            $packageManager = \TYPO3\CMS\Core\Core\Bootstrap::createPackageManager(
                PackageManager::class,
                \TYPO3\CMS\Core\Core\Bootstrap::createPackageCache($coreCache)
            );
            GeneralUtility::setSingletonInstance(PackageManager::class, $packageManager);
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::setPackageManager($packageManager);

            $languageStore = new LanguageStore($packageManager);
            $languageServiceFactory = new LanguageServiceFactory(
                GeneralUtility::makeInstance(Locales::class),
                new LocalizationFactory($languageStore, $cacheManager),
                $cacheManager->getCache('runtime')
            );
            // LanguageServiceFactory implementiert kein SingletonInterface, daher funktioniert
            // GeneralUtility::setSingletonInstance() hier nicht (strikte Typprüfung) - per Reflection
            // direkt in die interne Singleton-Registry eintragen.
            $singletonInstancesProperty = new \ReflectionProperty(GeneralUtility::class, 'singletonInstances');
            $singletonInstances = $singletonInstancesProperty->getValue();
            $singletonInstances[LanguageServiceFactory::class] = $languageServiceFactory;
            $singletonInstancesProperty->setValue(null, $singletonInstances);

            $container = new FailsafeContainer();
            self::$renderingContextFactory = new RenderingContextFactory(
                $container,
                $cacheManager,
                new ViewHelperResolverFactory($container)
            );
        }

        return self::$renderingContextFactory;
    }

    protected function renderPartial(string $partial, array $variables): string
    {
        $extensionPath = dirname(__DIR__, 2) . '/Resources/Private/';

        $renderingContext = $this->getRenderingContextFactory()->create([
            'partialRootPaths' => [$extensionPath . 'Partials/'],
            'layoutRootPaths' => [$extensionPath . 'Layouts/'],
        ]);

        // f:translate löst kurze Keys (ohne "LLL:EXT:...") nur über einen Extbase-Request auf,
        // der die zu verwendende Extension kennt (siehe TranslateViewHelper::renderStatic()).
        $extbaseParameters = new ExtbaseRequestParameters();
        $extbaseParameters->setControllerExtensionName('DlaOpacNg');
        $request = (new ServerRequest())->withAttribute('extbase', $extbaseParameters);
        $renderingContext->setRequest(new ExtbaseRequest($request));

        $view = new StandaloneView($renderingContext);
        // Alle Partials werden in Produktion innerhalb eines Top-Level-Templates gerendert, das diese
        // Namespaces einmal deklariert (siehe z.B. Resources/Private/Templates/Search/Index.html) -
        // Fluid merkt sich deklarierte Namespaces für die gesamte Rendering-Context-Lebensdauer, auch
        // über f:render-Partial-Grenzen hinweg. Einzelne Partials deklarieren "dla"/"v" daher oft
        // nicht erneut selbst und würden hier isoliert sonst mit "Unknown Namespace" scheitern.
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
