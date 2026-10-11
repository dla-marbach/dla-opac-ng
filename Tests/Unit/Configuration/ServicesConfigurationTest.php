<?php

namespace Dla\DlaOpacNg\Tests\Unit\Configuration;

use Dla\DlaOpacNg\Middleware\NaturalLanguageSearchMiddleware;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Kompiliert Configuration/Services.yaml mit Symfony DI, wie TYPO3 es beim Aufbau des Containers tut.
 *
 * Geprüft werden nur die Klassen der natürlichsprachigen Suche; die übrigen Services benötigen
 * Core-Services, die es im Unit-Test-Bootstrap nicht gibt.
 */
class ServicesConfigurationTest extends UnitTestCase
{
    private function buildContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/Configuration')))->load('Services.yaml');

        foreach (array_keys($container->getDefinitions()) as $id) {
            if ($id !== 'service_container' && !preg_match('/QueryUnderstanding|NaturalLanguage/', $id)) {
                $container->removeDefinition($id);
            }
        }

        return $container;
    }

    /**
     * @test
     */
    public function naturalLanguageSearchClassesCanBeWired(): void
    {
        $container = $this->buildContainer();
        $container->compile();

        self::assertTrue($container->hasDefinition(NaturalLanguageSearchMiddleware::class));
    }

    /**
     * @test
     */
    public function middlewareCreatesItsOwnServiceWithCache(): void
    {
        $container = $this->buildContainer();
        $container->compile();

        // Ein per Autowiring injizierter QueryUnderstandingService hätte keinen Zwischenspeicher.
        self::assertSame([], $container->getDefinition(NaturalLanguageSearchMiddleware::class)->getArguments());
    }
}
