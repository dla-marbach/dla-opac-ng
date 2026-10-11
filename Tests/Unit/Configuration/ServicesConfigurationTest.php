<?php

namespace Dla\DlaOpacNg\Tests\Unit\Configuration;

use Dla\DlaOpacNg\Middleware\NaturalLanguageSearchMiddleware;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use Dla\DlaOpacNg\Service\QueryUnderstandingService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\RateLimiter\Storage\CachingFrameworkStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Baut Configuration/Services.yaml mit Symfony DI auf, wie TYPO3 es beim Aufbau des Containers tut.
 *
 * Geprüft werden die Services der natürlichsprachigen Suche; Core-Services (CacheManager, Speicher des
 * Rate Limiters) werden als synthetische Services bereitgestellt, weil es im Unit-Test-Bootstrap keinen
 * TYPO3-Container gibt.
 */
class ServicesConfigurationTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private function buildContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/Configuration')))->load('Services.yaml');

        foreach (array_keys($container->getDefinitions()) as $id) {
            if ($id !== 'service_container' && !preg_match('/QueryUnderstanding|NaturalLanguage|SolrConnection|cache\.dla_opac_ng/', $id)) {
                $container->removeDefinition($id);
            }
        }
        foreach ([CacheManager::class, CachingFrameworkStorage::class] as $id) {
            $container->setDefinition($id, (new Definition($id))->setSynthetic(true)->setPublic(true));
        }

        $container->compile();

        return $container;
    }

    private function createCacheManager(): CacheManager
    {
        $cacheManager = new CacheManager();
        foreach (['dla_opac_ng_query_understanding', 'ratelimiter'] as $identifier) {
            $cacheManager->registerCache(new VariableFrontend($identifier, new TransientMemoryBackend('Testing')));
        }

        return $cacheManager;
    }

    /**
     * @test
     */
    public function middlewareAndServicesAreWiredByDependencyInjection(): void
    {
        $container = $this->buildContainer();
        $cacheManager = $this->createCacheManager();
        $container->set(CacheManager::class, $cacheManager);
        $container->set(CachingFrameworkStorage::class, new CachingFrameworkStorage($cacheManager));

        self::assertInstanceOf(NaturalLanguageSearchMiddleware::class, $container->get(NaturalLanguageSearchMiddleware::class));
        self::assertInstanceOf(QueryUnderstandingService::class, $container->get(QueryUnderstandingService::class));
    }

    /**
     * @test
     */
    public function configurationIsBuiltFromEnvironment(): void
    {
        $container = $this->buildContainer();

        self::assertSame(
            [QueryUnderstandingConfiguration::class, 'fromEnvironment'],
            $container->getDefinition(QueryUnderstandingConfiguration::class)->getFactory()
        );
    }
}
