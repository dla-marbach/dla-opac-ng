<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

class NaturalLanguageSearchTest extends FluidPartialTestCase
{
    protected function tearDown(): void
    {
        putenv('LLM_BASE_URL');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function hidesCheckboxWhenNotConfigured(): void
    {
        putenv('LLM_BASE_URL');

        $html = $this->renderPartial('Form/NaturalLanguageSearch', ['arguments' => []]);

        self::assertStringNotContainsString('tx_find_find[nl]', $html);
        self::assertStringNotContainsString('dla-nl-notice', $html);
    }

    /**
     * @test
     */
    public function rendersCheckboxWhenConfigured(): void
    {
        putenv('LLM_BASE_URL=http://localhost:11434/v1');

        $html = $this->renderPartial('Form/NaturalLanguageSearch', ['arguments' => []]);

        self::assertStringContainsString('name="tx_find_find[nl]" value="1"', $html);
        self::assertStringContainsString('Understand as a question', $html);
    }

    /**
     * Argumente nach der Weiterleitung, dazu ein selbst gewählter Filter (Sprache)
     */
    private const INTERPRETED = [
        'q' => ['default' => 'steppenwolf (exil OR emigration)'],
        'facet' => [
            'Sprache' => ['Deutsch' => '1'],
            'filterAuthorityRelation_mv' => ['Hesse, Hermann (1877-1962)␝Von' => '1'],
            'Medientypen' => ['Handschriften' => '1'],
            'Zeit' => ['RANGE 1930 TO 1939' => '1'],
        ],
        'nlq' => 'Briefe von Hesse aus den 1930ern über Exil',
        'nlstatus' => 'ok',
        'nlf' => [
            ['f' => 'filterAuthorityRelation_mv', 't' => 'Hesse, Hermann (1877-1962)␝Von'],
            ['f' => 'Medientypen', 't' => 'Handschriften'],
            ['f' => 'Zeit', 't' => 'RANGE 1930 TO 1939'],
        ],
        'nlt' => [
            ['k' => 'text', 'q' => 'steppenwolf', 'l' => 'Steppenwolf'],
            ['k' => 'topic', 'q' => '(exil OR emigration)', 'l' => 'Exil'],
        ],
    ];

    private function renderInterpretation(array $arguments): string
    {
        return $this->renderPartial('Form/NaturalLanguageSearch', [
            'arguments' => $arguments,
            'config' => ['activeFacets' => []],
            'settings' => ['jumpToID' => ''],
        ]);
    }

    /**
     * @test
     */
    public function rendersInterpretationLineWithRemovableParts(): void
    {
        $html = $this->renderInterpretation(self::INTERPRETED);

        self::assertStringContainsString('dla-nl-notice-ok', $html);
        self::assertStringContainsString('Your question »Briefe von Hesse aus den 1930ern über Exil« was understood as:', $html);
        self::assertStringContainsString('dla-nl-part-mediaType', $html);
        self::assertMatchesRegularExpression('#dla-nl-part-relationVon">\s*<a[^>]*>\s*<span class="icon bel-ende01"></span>\s*by Hesse, Hermann \(1877-1962\)#', $html);
        self::assertStringContainsString('1930–1939', $html);
        self::assertStringContainsString('search terms: Steppenwolf', $html);
        self::assertStringContainsString('topic: Exil', $html);
        // Selbst gewählte Filter gehören nicht zur Interpretation
        self::assertStringNotContainsString('Deutsch', $html);
        // Reihenfolge: Medientyp, Person, Zeitraum, Suchbegriffe, Thema
        self::assertMatchesRegularExpression('#mediaType.*relationVon.*date.*part-text.*part-topic#s', $html);
    }

    /**
     * @test
     */
    public function removedPartsAreNotShownAnymore(): void
    {
        $arguments = self::INTERPRETED;
        unset($arguments['facet']['Medientypen']);
        $arguments['q']['default'] = 'steppenwolf';

        $html = $this->renderInterpretation($arguments);

        self::assertStringNotContainsString('dla-nl-part-mediaType', $html);
        self::assertStringNotContainsString('topic: Exil', $html);
        self::assertStringContainsString('by Hesse, Hermann (1877-1962)', $html);
    }

    /**
     * @test
     */
    public function saysSoWhenAllPartsWereRemoved(): void
    {
        $html = $this->renderInterpretation(['nlq' => 'Briefe von Hesse', 'nlstatus' => 'ok', 'nlf' => [['f' => 'Medientypen', 't' => 'Handschriften']]]);

        self::assertStringNotContainsString('dla-nl-parts"', $html);
        self::assertStringContainsString('All parts of the interpretation have been removed.', $html);
    }

    /**
     * @test
     */
    public function suggestsRemovingPartsWhenThereAreNoResults(): void
    {
        $render = fn(int $numFound): string => $this->renderPartial('Form/NaturalLanguageSearch', [
            'arguments' => self::INTERPRETED,
            'config' => ['activeFacets' => []],
            'settings' => ['jumpToID' => ''],
            'results' => ['numfound' => $numFound],
        ]);

        self::assertStringContainsString('No results. Remove individual parts of the interpretation above', $render(0));
        self::assertStringNotContainsString('No results.', $render(12));
    }

    /**
     * @test
     */
    public function rendersNoticeForUnrecognizedQuestion(): void
    {
        $html = $this->renderPartial('Form/NaturalLanguageSearch', [
            'arguments' => ['nlq' => 'Wie spät ist es?', 'nlstatus' => 'unrecognized'],
        ]);

        self::assertStringContainsString('dla-nl-notice-unrecognized', $html);
        self::assertStringContainsString('No search terms or filters could be derived from your question »Wie spät ist es?«.', $html);
    }

    /**
     * @test
     */
    public function rendersFallbackNotice(): void
    {
        $html = $this->renderPartial('Form/NaturalLanguageSearch', [
            'arguments' => ['nlq' => 'Briefe von Hesse', 'nlstatus' => 'fallback'],
        ]);

        self::assertStringContainsString('dla-nl-notice-fallback', $html);
        self::assertStringContainsString('A normal search was performed for »Briefe von Hesse«.', $html);
    }
}
