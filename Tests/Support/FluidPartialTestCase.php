<?php

namespace Dla\DlaOpacNg\Tests\Support;

use Dla\DlaOpacNg\ViewHelpers\CollectionViewHelper;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request as ExtbaseRequest;
use TYPO3\CMS\Fluid\Core\Cache\FluidTemplateCache;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolverFactoryInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;
use TYPO3Fluid\Fluid\View\TemplateView;

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
    // CacheManager und PackageManager werden als Singleton hinterlegt (siehe getRenderingContextFactory()).
    protected bool $resetSingletonInstances = true;

    private static ?RenderingContextFactory $renderingContextFactory = null;

    protected function setUp(): void
    {
        parent::setUp();
        FakeCollectionService::$parents = [];
        @unlink(self::missingRequestsLog());
    }

    private static function missingRequestsLog(): string
    {
        return dirname(__DIR__) . '/Fixtures/Solr/missing-requests.log';
    }

    private function getRenderingContextFactory(): RenderingContextFactory
    {
        if (self::$renderingContextFactory === null) {
            // Test-Override zuletzt anhängen: ViewHelperResolver probiert Namespace-Klassen
            // in umgekehrter Reihenfolge (array_pop), unser TranslateViewHelper hat also Vorrang
            // vor TYPO3\CMS\Fluid\ViewHelpers\TranslateViewHelper, alle anderen f:*-ViewHelper
            // bleiben unverändert (Klasse existiert dort nicht, resolver fällt zurück).
            // Der Namespace "f" wird vollständig angegeben, weil er ab TYPO3 v14 nicht mehr in
            // $GLOBALS['TYPO3_CONF_VARS'] steht, sondern über den DI-Container registriert wird.
            $namespaces = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces'] ?? [];
            $namespaces['f'] = [
                'TYPO3Fluid\\Fluid\\ViewHelpers',
                'TYPO3\\CMS\\Fluid\\ViewHelpers',
                'Dla\\DlaOpacNg\\Tests\\Support\\FluidViewHelperOverrides',
            ];
            // EXT:vhs registriert seinen Namespace global (ext_localconf.php), viele Partials verlassen sich darauf.
            $namespaces['v'] = ['FluidTYPO3\\Vhs\\ViewHelpers'];

            // Über die globale CacheManager-Singleton-Instanz laufen (statt "new CacheManager()"),
            // damit auch Core-Code, der später GeneralUtility::makeInstance(CacheManager::class)
            // aufruft, dieselbe, hier konfigurierte Instanz erhält.
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            $cacheManager->setCacheConfigurations([
                'fluid_template' => [
                    'frontend' => FluidTemplateCache::class,
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

            // Eigene Factory statt TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolverFactory, deren
            // Konstruktor sich zwischen den TYPO3-Versionen unterscheidet. Der Resolver erzeugt die
            // ViewHelper über createInstance() statt über den (hier leeren) DI-Container.
            $container = new FailsafeContainer();
            $viewHelperResolverFactory = new class ($container, $namespaces) implements ViewHelperResolverFactoryInterface {
                public function __construct(
                    private readonly ContainerInterface $container,
                    private readonly array $namespaces
                ) {
                }

                public function create(): ViewHelperResolver
                {
                    return new class ($this->container, $this->namespaces) extends ViewHelperResolver {
                        public function createViewHelperInstanceFromClassName($viewHelperClassName): ViewHelperInterface
                        {
                            return FluidPartialTestCase::createInstance($viewHelperClassName);
                        }
                    };
                }
            };

            $arguments = [$container, $cacheManager, $viewHelperResolverFactory];
            if ((new \ReflectionMethod(RenderingContextFactory::class, '__construct'))->getNumberOfParameters() > 3) {
                // Ab TYPO3 v14 (Fluid 5) prüft Fluid die Typen der ViewHelper-Argumente strikt.
                $arguments[] = new \TYPO3Fluid\Fluid\Core\ViewHelper\StrictArgumentProcessor();
            }
            self::$renderingContextFactory = new RenderingContextFactory(...$arguments);
        }

        return self::$renderingContextFactory;
    }

    /**
     * Ersatz für den DI-Container: berücksichtigt XCLASS-Einträge (siehe renderPartial()) und löst
     * Konstruktor-Abhängigkeiten rekursiv auf (ab TYPO3 v14 haben einige ViewHelper des Core welche,
     * z.B. f:format.crop).
     *
     * @internal nur für den ViewHelperResolver in getRenderingContextFactory()
     */
    public static function createInstance(string $className): object
    {
        $className = $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$className]['className'] ?? $className;
        $arguments = [];
        foreach ((new \ReflectionClass($className))->getConstructor()?->getParameters() ?? [] as $parameter) {
            if ($parameter->isOptional()) {
                break;
            }
            $type = $parameter->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : '';
            $arguments[] = $typeName === LoggerInterface::class ? new NullLogger() : self::createInstance($typeName);
        }

        return new $className(...$arguments);
    }

    protected function renderPartial(string $partial, array $variables): string
    {
        // <dla:collection> braucht CollectionService (Datenbank) per Konstruktor-Injektion, siehe FakeCollectionService.
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][CollectionViewHelper::class]['className'] = CollectionViewHelperWithFakeService::class;

        $extensionPath = dirname(__DIR__, 2) . '/Resources/Private/';

        $renderingContext = $this->getRenderingContextFactory()->create([
            'partialRootPaths' => [$extensionPath . 'Partials/'],
            'layoutRootPaths' => [$extensionPath . 'Layouts/'],
        ]);

        // Extbase-Request für ViewHelper des Core, die den Request aus dem RenderingContext lesen.
        $extbaseParameters = new ExtbaseRequestParameters();
        $extbaseParameters->setControllerExtensionName('DlaOpacNg');
        $request = (new ServerRequest())->withAttribute('extbase', $extbaseParameters);
        if ((new Typo3Version())->getMajorVersion() < 13) {
            $renderingContext->setRequest(new ExtbaseRequest($request));
        } else {
            $renderingContext->setAttribute(ServerRequestInterface::class, new ExtbaseRequest($request));
        }

        // TemplateView von Fluid statt StandaloneView (in TYPO3 v14 entfernt).
        $view = new TemplateView($renderingContext);
        // Alle Partials werden in Produktion innerhalb eines Top-Level-Templates gerendert, das diese
        // Namespaces einmal deklariert (siehe z.B. Resources/Private/Templates/Search/Index.html) -
        // Fluid merkt sich deklarierte Namespaces für die gesamte Rendering-Context-Lebensdauer, auch
        // über f:render-Partial-Grenzen hinweg. Einzelne Partials deklarieren "dla"/"v" daher oft
        // nicht erneut selbst und würden hier isoliert sonst mit "Unknown Namespace" scheitern.
        $renderingContext->getTemplatePaths()->setTemplateSource(
            '{namespace s=Dla\Find\ViewHelpers}'
            . '{namespace dla=Dla\DlaOpacNg\ViewHelpers}'
            . '{namespace v=FluidTYPO3\Vhs\ViewHelpers}'
            . '<f:render partial="' . $partial . '" arguments="{_all}"/>'
        );
        $view->assignMultiple($variables);

        $html = $view->render();

        // dla:countFromSolr fängt HTTP-Fehler ab und zeigt dann still "0" an - fehlende Cassetten
        // (z.B. nach Änderung einer queryFields-Definition) würden sonst unbemerkt bleiben.
        $log = self::missingRequestsLog();
        self::assertFileDoesNotExist(
            $log,
            "Solr-Anfragen ohne Cassette (Aufnahme siehe README, SOLR_RECORD_BASE):\n" . (string)@file_get_contents($log)
        );

        return $html;
    }

    /**
     * Rendert ein Detail-Partial mit dem Dokument und dem Trefferset, wie es das Detail-Template
     * übergibt (siehe SolrFixture::detailVariables()). Unterabfragen laufen gegen die Cassetten.
     */
    protected function renderDetailPartial(string $partial, string $documentId): string
    {
        return $this->renderPartial($partial, SolrFixture::detailVariables($documentId));
    }

    /** Sichtbarer Text des gerenderten HTML: ohne Tags/Kommentare/Skripte, Entities dekodiert, Whitespace zusammengefasst. */
    protected static function visibleText(string $html): string
    {
        $html = (string)preg_replace(['/<!--.*?-->/s', '/<(script|style)\b.*?<\/\1>/is'], '', $html);
        $withoutTags = preg_replace('/<[^>]*>/', ' ', $html);

        return trim((string)preg_replace('/\s+/u', ' ', html_entity_decode((string)$withoutTags, ENT_QUOTES | ENT_HTML5)));
    }
}
