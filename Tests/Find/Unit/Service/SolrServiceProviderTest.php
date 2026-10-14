<?php

namespace Dla\Find\Tests\Unit\Service;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
 *      Goettingen State Library
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Dla\Find\Service\SolrServiceProvider;
use Solarium\QueryType\Select\Query\Query;

/**
 * Solr ServiceProvider Test.
 */
class SolrServiceProviderTest extends UnitTestCase
{
    /**
     * @var SolrServiceProvider
     */
    protected $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture = $this->getMockBuilder(SolrServiceProvider::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @test
     */
    public function setConfigurationAddsTheValueToConfigurationArray()
    {
        $key = 'foo';
        $value = 'bar';

        $this->fixture->setConfigurationValue($key, $value);
        self::assertArrayHasKey($key, $this->fixture->getConfiguration());
    }

    /**
     * @test
     */
    public function setConfigurationAddsAKeyValuePairToAnExistingConfiguration()
    {
        $key = 'foo';
        $value = 'bar';

        $key1 = 'bar';
        $value1 = 'baz';

        $this->fixture->setConfigurationValue($key1, $value1);
        $this->fixture->setConfigurationValue($key, $value);
        self::assertArrayHasKey($key, $this->fixture->getConfiguration());
        self::assertArrayHasKey($key1, $this->fixture->getConfiguration());
    }

    /**
     * The request parameter »data-fields« must no longer influence the Solr field
     * list (»fl«). Otherwise a caller could expose internal fields or inject
     * expensive Solr pseudo-fields such as [explain] or [docid].
     *
     * @test
     */
    public function setFieldsIgnoresDataFieldsRequestParameter()
    {
        $query = $this->createMock(Query::class);
        // With no configured dataFields the attacker-supplied field list must be
        // dropped completely, so setFields() must never be called on the query.
        $query->expects(self::never())->method('setFields');

        $this->injectProperty($this->fixture, 'query', $query);
        $this->injectProperty($this->fixture, 'settings', ['dataFields' => ['default' => []]]);
        $this->injectProperty($this->fixture, 'action', 'index');

        $this->invokeProtected($this->fixture, 'setFields', [
            ['data-fields' => '*,[explain],[docid]'],
        ]);
    }

    /**
     * The configured default field list is still applied.
     *
     * @test
     */
    public function setFieldsUsesConfiguredDefaultFields()
    {
        $query = $this->createMock(Query::class);
        $query->expects(self::once())
            ->method('setFields')
            ->with(['id', 'title']);

        $this->injectProperty($this->fixture, 'query', $query);
        $this->injectProperty($this->fixture, 'settings', [
            'dataFields' => ['default' => ['default' => ['id', 'title']]],
        ]);
        $this->injectProperty($this->fixture, 'action', 'index');

        // Even if »data-fields« is supplied, only the configured defaults are used.
        $this->invokeProtected($this->fixture, 'setFields', [
            ['data-fields' => 'secret_internal_field'],
        ]);
    }

    public static function dateRangeFacetBoundsProvider(): array
    {
        return [
            'weniger als 50 Jahre: Abstand 1 Jahr' => ['2000-06-15', '2026-03-01', 'NOW/YEAR-27YEARS', 1],
            'mehr als 50 Jahre: Abstand gerundet' => ['1900-01-01', '2026-10-14', 'NOW/YEAR-129YEARS', 3],
            'Fallback Jahr 0' => ['0000-01-01', '2026-10-14', 'NOW/YEAR-2050YEARS', 41],
            'Solr-Zeitstempel als Minimum' => ['1910-06-15T00:00:00Z', '2026-10-14', 'NOW/YEAR-118YEARS', 2],
        ];
    }

    /**
     * @test
     * @dataProvider dateRangeFacetBoundsProvider
     */
    public function getDateRangeFacetBoundsUsesSolrDateMathRelativeToNow(string $minValue, string $now, string $expectedStart, int $expectedGap)
    {
        [$start, $end, $gap] = $this->invokeProtected($this->fixture, 'getDateRangeFacetBounds', [
            $minValue,
            new \DateTimeImmutable($now),
        ]);

        self::assertSame($expectedStart, $start);
        self::assertSame('NOW/DAY+1YEAR', $end);
        self::assertSame($expectedGap, $gap);
    }

    /**
     * @test
     */
    public function getDateRangeFacetBoundsOnlyChangeAtNewYear()
    {
        $start = fn (string $now) => $this->invokeProtected($this->fixture, 'getDateRangeFacetBounds', [
            '2000-06-15',
            new \DateTimeImmutable($now),
        ])[0];

        // Unverändert über das ganze Jahr, auch am Jahrestag des ältesten Datums
        self::assertSame('NOW/YEAR-27YEARS', $start('2026-01-01T00:00:00Z'));
        self::assertSame('NOW/YEAR-27YEARS', $start('2026-06-15T12:00:00Z'));
        self::assertSame('NOW/YEAR-27YEARS', $start('2026-12-31T23:59:59Z'));
        // Änderung erst zum Jahreswechsel
        self::assertSame('NOW/YEAR-28YEARS', $start('2027-01-01T00:00:00Z'));
    }

    private function injectProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }

    private function invokeProtected(object $object, string $method, array $arguments)
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }
}
