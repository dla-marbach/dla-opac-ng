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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
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

    /**
     * Abhängigkeiten per Dependency Injection, siehe Configuration/Services.yaml
     * (Konfiguration aus Umgebungsvariablen und Zwischenspeicher über Factories).
     */
    public function __construct(
        private readonly QueryUnderstandingConfiguration $configuration,
        private readonly LlmClient $llmClient,
        private readonly QueryPrompt $prompt,
        private readonly SearchArgumentsBuilder $builder,
        private readonly FacetVocabulary $vocabulary,
        private readonly FrontendInterface $cache,
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * Gibt den Logger auch an den LLM-Client weiter, damit dessen Warnungen (Timeout, HTTP-Fehler) im Log landen.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        $this->llmClient->setLogger($logger);
    }

    public function isEnabled(): bool
    {
        return $this->configuration->isEnabled();
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
        return $this->cache->has($this->cacheIdentifier(self::normalizeQuestion($question)));
    }

    /**
     * Zerlegt die Frage. Liefert null, wenn das LLM nicht verfügbar ist, und eine leere QueryStructure
     * (isEmpty()), wenn es nichts erkannt hat. Beides wird unterschiedlich angezeigt; auch leere Ergebnisse
     * werden zwischengespeichert, damit dieselbe Frage nicht erneut ans LLM geht.
     */
    public function analyze(string $question): ?QueryStructure
    {
        $question = self::normalizeQuestion($question);
        if ($question === '' || !$this->isEnabled()) {
            return null;
        }

        $vocabulary = $this->vocabulary->getAll();
        $cacheIdentifier = $this->cacheIdentifier($question, $vocabulary);
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

        $structure = QueryStructure::fromArray($result, $vocabulary);
        $this->cache->set($cacheIdentifier, $structure->toArray(), [], self::CACHE_LIFETIME);
        $this->logger->info(
            $structure->isEmpty() ? 'LLM hat in der Frage nichts erkannt' : 'Frage zerlegt',
            ['question' => $question, 'structure' => $structure->toArray()]
        );

        return $structure;
    }

    /**
     * Gleicht die Zerlegung mit dem Index ab und lässt weiche Bestandteile weg, solange es keine Treffer gibt.
     *
     * @param list<string>|null $additionalFilterQueries Filter, die in der Suche zusätzlich gelten (z.B. aus der
     *        Anfrage übernommene Facetten). null: unbekannte Filter, dann wird nichts weggelassen, weil sich die
     *        Trefferzahl nicht zuverlässig ermitteln lässt.
     */
    public function interpret(QueryStructure $structure, ?array $additionalFilterQueries = []): QueryInterpretation
    {
        $interpretation = $this->builder->build($structure);
        if ($additionalFilterQueries === null) {
            return $interpretation;
        }

        foreach (QueryInterpretation::RELAXATION_ORDER as $kind) {
            if (!$interpretation->hasPart($kind) || $this->countResults($interpretation, $additionalFilterQueries) !== 0) {
                continue;
            }
            $interpretation = $interpretation->without($kind);
        }

        return $interpretation;
    }

    /**
     * Trefferzahl einer Interpretation; null, wenn Solr nicht erreichbar ist (dann wird nichts weggelassen).
     *
     * @param list<string> $additionalFilterQueries
     */
    private function countResults(QueryInterpretation $interpretation, array $additionalFilterQueries): ?int
    {
        $resolver = $this->builder->getResolver();
        $queryString = $interpretation->getQueryString();

        return $resolver->count(
            $queryString !== '' ? $queryString : '*:*',
            [...$additionalFilterQueries, ...$interpretation->getFilterQueries()]
        );
    }

    private function cacheIdentifier(string $question, ?array $vocabulary = null): string
    {
        $vocabulary ??= $this->vocabulary->getAll();

        return 'q_' . sha1($this->configuration->model . '|' . $this->prompt->getVersion($vocabulary) . '|' . mb_strtolower($question));
    }
}
