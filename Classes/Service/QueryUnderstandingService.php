<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service;

use Dla\DlaOpacNg\Service\QueryUnderstanding\LlmClient;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryInterpretation;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryPrompt;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryStructure;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use Dla\DlaOpacNg\Service\QueryUnderstanding\SearchArgumentsBuilder;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Natürlichsprachige Suche: zerlegt eine Frage mit einem lokalen LLM in Suchbegriffe und Filter.
 *
 * Ablauf:
 * 1. Zerlegung durch das LLM (JSON nach festem Schema, siehe QueryPrompt), zwischengespeichert je Frage
 * 2. Prüfung und Bereinigung (QueryStructure)
 * 3. Abgleich mit dem Index und Abbildung auf Facetten/Suchbegriffe (SearchArgumentsBuilder)
 * 4. Bei null Treffern schrittweises Weglassen weicher Bestandteile (QueryInterpretation::RELAXATION_ORDER)
 *
 * Das LLM erzeugt nie Solr-Syntax; die Abfrage wird ausschließlich aus geprüften Werten zusammengesetzt.
 */
class QueryUnderstandingService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const CACHE_IDENTIFIER = 'dla_opac_ng_query_understanding';

    private const CACHE_LIFETIME = 2592000;

    private const MAX_QUESTION_LENGTH = 300;

    private readonly QueryUnderstandingConfiguration $configuration;

    private readonly LlmClient $llmClient;

    private readonly QueryPrompt $prompt;

    private readonly SearchArgumentsBuilder $builder;

    public function __construct(
        ?QueryUnderstandingConfiguration $configuration = null,
        ?LlmClient $llmClient = null,
        ?QueryPrompt $prompt = null,
        ?SearchArgumentsBuilder $builder = null,
        private readonly ?FrontendInterface $cache = null,
    ) {
        $this->configuration = $configuration ?? QueryUnderstandingConfiguration::fromEnvironment();
        $this->llmClient = $llmClient ?? new LlmClient($this->configuration);
        $this->prompt = $prompt ?? new QueryPrompt();
        $this->builder = $builder ?? new SearchArgumentsBuilder();
        $this->logger = new NullLogger();
    }

    public function isEnabled(): bool
    {
        return $this->configuration->isEnabled();
    }

    public function getConfiguration(): QueryUnderstandingConfiguration
    {
        return $this->configuration;
    }

    public static function normalizeQuestion(string $question): string
    {
        $question = trim((string)preg_replace('/\s+/u', ' ', $question));

        return mb_substr($question, 0, self::MAX_QUESTION_LENGTH);
    }

    /**
     * Prüft, ob das Ergebnis für diese Frage bereits zwischengespeichert ist (dann ohne LLM-Aufruf).
     */
    public function isCached(string $question): bool
    {
        return $this->cache !== null && $this->cache->has($this->cacheIdentifier(self::normalizeQuestion($question)));
    }

    /**
     * Zerlegt die Frage. Liefert null, wenn das LLM nicht verfügbar ist oder nichts erkannt hat.
     */
    public function analyze(string $question): ?QueryStructure
    {
        $question = self::normalizeQuestion($question);
        if ($question === '' || !$this->isEnabled()) {
            return null;
        }

        $cacheIdentifier = $this->cacheIdentifier($question);
        $cached = $this->cache?->get($cacheIdentifier);
        if (is_array($cached)) {
            return QueryStructure::fromArray($cached);
        }

        $result = $this->llmClient->completeJson(
            $this->prompt->buildMessages($question),
            QueryPrompt::getJsonSchema(),
            'catalog_query'
        );
        if ($result === null) {
            return null;
        }

        $structure = QueryStructure::fromArray($result);
        if ($structure->isEmpty()) {
            $this->logger->info('LLM hat in der Frage nichts erkannt', ['question' => $question]);
            return null;
        }

        $this->cache?->set($cacheIdentifier, $structure->toArray(), [], self::CACHE_LIFETIME);
        $this->logger->info('Frage zerlegt', ['question' => $question, 'structure' => $structure->toArray()]);

        return $structure;
    }

    /**
     * Gleicht die Zerlegung mit dem Index ab und lässt weiche Bestandteile weg, solange es keine Treffer gibt.
     */
    public function interpret(QueryStructure $structure): QueryInterpretation
    {
        $interpretation = $this->builder->build($structure);

        foreach (QueryInterpretation::RELAXATION_ORDER as $kind) {
            if (!$interpretation->hasPart($kind) || $this->countResults($interpretation) !== 0) {
                continue;
            }
            $interpretation = $interpretation->without($kind);
        }

        return $interpretation;
    }

    /**
     * Trefferzahl einer Interpretation; null, wenn Solr nicht erreichbar ist (dann wird nichts weggelassen).
     */
    private function countResults(QueryInterpretation $interpretation): ?int
    {
        $resolver = $this->builder->getResolver();
        $queryString = $interpretation->getQueryString();

        return $resolver->count($queryString !== '' ? $queryString : '*:*', $interpretation->getFilterQueries());
    }

    private function cacheIdentifier(string $question): string
    {
        return 'q_' . sha1($this->configuration->model . '|' . $this->prompt->getVersion() . '|' . mb_strtolower($question));
    }
}
