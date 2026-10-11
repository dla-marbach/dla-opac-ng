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
     * @test
     */
    public function rendersNoticeForInterpretedQuestion(): void
    {
        $html = $this->renderPartial('Form/NaturalLanguageSearch', [
            'arguments' => ['nlq' => 'Briefe von Hesse', 'nlstatus' => 'ok'],
        ]);

        self::assertStringContainsString('dla-nl-notice-ok', $html);
        self::assertStringContainsString('Your question »Briefe von Hesse« was automatically translated', $html);
    }

    /**
     * @test
     */
    public function rendersNoticeWithDroppedParts(): void
    {
        $html = $this->renderPartial('Form/NaturalLanguageSearch', [
            'arguments' => ['nlq' => 'Briefe über Exil', 'nlstatus' => 'relaxed', 'nldropped' => 'Exil, 1930–1939'],
        ]);

        self::assertStringContainsString('dla-nl-notice-relaxed', $html);
        self::assertStringContainsString('otherwise be no results: Exil, 1930–1939.', $html);
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
