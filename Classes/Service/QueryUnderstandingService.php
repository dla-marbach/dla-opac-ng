<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service;

use Dla\DlaOpacNg\Service\QueryUnderstanding\FacetVocabulary;
use Dla\DlaOpacNg\Service\QueryUnderstanding\LlmClient;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryInterpretation;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryPrompt;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryStructure;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use Dla\DlaOpacNg\Service\QueryUnderstanding\SearchArgumentsBuilder;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Natürlichsprachige Suche: zerlegt eine Frage mit einem lokalen LLM in Suchbegriffe und Filter.
 *
 * 1. Zerlegung durch das LLM (JSON nach festem Schema mit dem Vokabular des Index, siehe QueryPrompt),
 *    zwischengespeichert je Frage
 * 2. Prüfung und Bereinigung (QueryStructure)
 * 3. Abgleich mit dem Index und Abbildung auf Facetten/Suchbegriffe (SearchArgumentsBuilder)
 *
 * Das LLM erzeugt nie Solr-Syntax; die Abfrage wird ausschließlich aus geprüften Werten zusammengesetzt.
 */
class QueryUnderstandingService
{
    public const CACHE_IDENTIFIER = 'dla_opac_ng_query_understanding';

    private const CACHE_LIFETIME = 2592000;

    private const MAX_QUESTION_LENGTH = 300;

    public function __construct(
        private readonly QueryUnderstandingConfiguration $configuration,
        private readonly LlmClient $llmClient,
        private readonly QueryPrompt $prompt,
        private readonly SearchArgumentsBuilder $builder,
        private readonly FacetVocabulary $vocabulary,
        private readonly FrontendInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->configuration->isEnabled();
    }

    public static function normalizeQuestion(string $question): string
    {
        return mb_substr(trim((string)preg_replace('/\s+/u', ' ', $question)), 0, self::MAX_QUESTION_LENGTH);
    }

    /**
     * Ist das Ergebnis für diese Frage bereits zwischengespeichert (dann ohne LLM-Aufruf)?
     */
    public function isCached(string $question): bool
    {
        return $this->cache->has($this->cacheIdentifier(self::normalizeQuestion($question)));
    }

    /**
     * Zerlegt die Frage. Liefert null, wenn das LLM nicht verfügbar ist, und eine leere QueryStructure
     * (isEmpty()), wenn es nichts erkannt hat. Auch leere Ergebnisse werden zwischengespeichert.
     */
    public function analyze(string $question): ?QueryStructure
    {
        $question = self::normalizeQuestion($question);
        if ($question === '' || !$this->isEnabled()) {
            return null;
        }

        $vocabulary = $this->vocabulary->getAll();
        $cacheIdentifier = $this->cacheIdentifier($question);
        $cached = $this->cache->get($cacheIdentifier);
        if (is_array($cached)) {
            return QueryStructure::fromArray($cached, $vocabulary);
        }

        $result = $this->llmClient->completeJson(
            $this->prompt->buildMessages($question, $vocabulary),
            QueryPrompt::getJsonSchema($vocabulary),
            'catalog_query'
        );
        if ($result === null) {
            return null;
        }

        $structure = QueryStructure::fromArray($result, $vocabulary, $question);
        $this->cache->set($cacheIdentifier, $structure->toArray(), [], self::CACHE_LIFETIME);
        $this->logger->info('Frage zerlegt', ['question' => $question, 'structure' => $structure->toArray()]);

        return $structure;
    }

    /**
     * Gleicht die Zerlegung mit dem Index ab und bildet sie auf Facetten und Suchbegriffe ab.
     */
    public function interpret(QueryStructure $structure): QueryInterpretation
    {
        return $this->builder->build($structure);
    }

    private function cacheIdentifier(string $question): string
    {
        $version = $this->prompt->getVersion($this->vocabulary->getAll());

        return 'q_' . sha1($this->configuration->model . '|' . $version . '|' . mb_strtolower($question));
    }
}
