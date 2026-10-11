<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\FacetVocabulary;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryPrompt;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class QueryPromptTest extends UnitTestCase
{
    private const VOCABULARY = [
        'mediaTypes' => ['Gedrucktes', 'Handschriften'],
        'formContent' => ['Brief', 'Gedicht'],
        'languages' => [],
    ];

    /**
     * @test
     */
    public function schemaRestrictsSmallFacetsToIndexValues(): void
    {
        $properties = QueryPrompt::getJsonSchema(self::VOCABULARY)['properties'];

        self::assertSame(['Gedrucktes', 'Handschriften'], $properties['mediaTypes']['items']['enum']);
        self::assertSame(['Brief', 'Gedicht'], $properties['formContent']['items']['enum']);
        self::assertSame(['Brief', 'Gedicht'], $properties['exclude']['properties']['formContent']['items']['enum']);
        // Ohne Werte (z.B. Solr nicht erreichbar) bleibt das Feld leer
        self::assertSame(0, $properties['languages']['maxItems']);
        self::assertArrayNotHasKey('enum', $properties['languages']['items']);
    }

    /**
     * @test
     */
    public function schemaWithoutVocabularyAllowsFreeTextExceptMediaTypes(): void
    {
        $properties = QueryPrompt::getJsonSchema()['properties'];

        self::assertSame(FacetVocabulary::FALLBACK_MEDIA_TYPES, $properties['mediaTypes']['items']['enum']);
        self::assertSame(['type' => 'string'], $properties['formContent']['items']);
    }

    /**
     * @test
     */
    public function systemPromptListsAllowedValues(): void
    {
        $prompt = (new QueryPrompt())->getSystemPrompt(self::VOCABULARY);

        self::assertStringContainsString('- "formContent": "Brief", "Gedicht"', $prompt);
        self::assertStringContainsString('- "languages": (keine, Feld leer lassen)', $prompt);
        self::assertStringNotContainsString('andere Werte sind nicht möglich', (new QueryPrompt())->getSystemPrompt());
    }

    /**
     * @test
     */
    public function systemPromptContainsCurrentYear(): void
    {
        self::assertStringContainsString('Aktuelles Jahr: ' . date('Y'), (new QueryPrompt())->getSystemPrompt(self::VOCABULARY));
    }

    /**
     * @test
     */
    public function examplesMatchTheSchema(): void
    {
        $schema = QueryPrompt::getJsonSchema();
        foreach ((new QueryPrompt())->getExamples() as $example) {
            self::assertSame($schema['required'], array_keys($example['answer']), $example['question']);
            self::assertSame(
                $schema['properties']['exclude']['required'],
                array_keys($example['answer']['exclude']),
                $example['question']
            );
            foreach ($example['answer']['persons'] as $person) {
                self::assertSame($schema['properties']['persons']['items']['required'], array_keys($person), $example['question']);
            }
        }
    }

    /**
     * @test
     */
    public function versionChangesWithVocabulary(): void
    {
        $prompt = new QueryPrompt();

        self::assertSame($prompt->getVersion(self::VOCABULARY), $prompt->getVersion(self::VOCABULARY));
        self::assertNotSame(
            $prompt->getVersion(self::VOCABULARY),
            $prompt->getVersion(['formContent' => ['Brief']] + self::VOCABULARY)
        );
    }
}
