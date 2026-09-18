<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service;

use Dla\DlaOpacNg\Service\EntityService;
use Dla\DlaOpacNg\Tests\Support\MockSolrServerProcess;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests EntityService against a local Solr mock server replaying recorded
 * real-world responses (see Tests/Fixtures/Solr/cassettes/entity-*.json).
 */
class EntityServiceTest extends UnitTestCase
{
    private static ?MockSolrServerProcess $mockSolr = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$mockSolr = MockSolrServerProcess::start();
    }

    protected function setUp(): void
    {
        parent::setUp();
        putenv('SOLR_HOST=' . self::$mockSolr->getBaseUrl());
        putenv('SOLR_CORE=internformat');
    }

    /**
     * @test
     */
    public function getEntityReturnsEscapedFieldsForKnownId(): void
    {
        $entity = (new EntityService())->getEntity('PE00000863');

        self::assertSame('PE00000863', $entity['id']);
        self::assertSame('Goethe, Johann Wolfgang von (1749-1832)', $entity['display']);
        self::assertSame('', $entity['displayName']);
        self::assertSame('Schriftsteller, Politiker, Naturwissenschaftler', $entity['displayAddition1']);
    }

    /**
     * @test
     */
    public function getEntityReturnsEmptyishResultForUnknownId(): void
    {
        // The production code does not guard against an empty Solr result set (docs[0] missing);
        // this documents the current (legacy) behaviour instead of failing on the resulting PHP warnings.
        set_error_handler(static fn (): bool => true);
        try {
            $entity = (new EntityService())->getEntity('DOES-NOT-EXIST-XYZ');
        } finally {
            restore_error_handler();
        }

        self::assertSame('', $entity['id']);
    }

    /**
     * @test
     */
    public function getEntitiesReturnsAllRequestedEntitiesWithComposedTitle(): void
    {
        $entities = (new EntityService())->getEntities('PE00000863,BF00025111');

        self::assertCount(2, $entities);

        $byId = [];
        foreach ($entities as $entity) {
            $byId[$entity['id']] = $entity;
        }

        self::assertArrayHasKey('PE00000863', $byId);
        self::assertArrayHasKey('BF00025111', $byId);
        self::assertSame('Personen', $byId['PE00000863']['filterSource']);
        self::assertSame(
            'Goethe, Johann Wolfgang von (1749-1832) / Schriftsteller, Politiker, Naturwissenschaftler',
            $byId['PE00000863']['title']
        );
        self::assertSame('Nachlässe und Spezialsammlungen', $byId['BF00025111']['filterSource']);
        self::assertStringContainsString('Cotta', $byId['BF00025111']['title']);
    }

    /**
     * @test
     */
    public function getEntitiesReturnsEmptyArrayForBlankQuery(): void
    {
        self::assertSame([], (new EntityService())->getEntities('   '));
    }
}
