<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Middleware;

use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryInterpretation;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use Dla\DlaOpacNg\Service\QueryUnderstanding\SearchArgumentsBuilder;
use Dla\DlaOpacNg\Service\QueryUnderstandingService;
use Dla\DlaOpacNg\Utility\ClientIpUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\StorageInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;

/**
 * Natürlichsprachige Suche (Proof of Concept).
 *
 * Greift bei Suchanfragen mit tx_find_find[nl]=1 ("Als Frage verstehen"): Die Frage aus dem Standardsuchfeld
 * wird vom QueryUnderstandingService in Suchbegriffe und Facetten zerlegt; anschließend wird per 303 auf
 * eine normale, strukturierte Such-URL weitergeleitet (Post/Redirect/Get). Blättern, Facettenklicks und
 * Links lösen dadurch nie einen weiteren LLM-Aufruf aus.
 *
 * Schutz vor Last:
 * - läuft nach der ProofOfWorkMiddleware (die Anfrage enthält tx_find_find und ist damit geschützt)
 * - Zwischenspeicher je Frage (wiederholte Fragen ohne LLM-Aufruf)
 * - Rate Limit je Client-IP (LLM_RATE_LIMIT) mit symfony/rate-limiter
 * - gleichzeitige Anfragen begrenzt der LLM-Server selbst (bei Ollama OLLAMA_NUM_PARALLEL und OLLAMA_MAX_QUEUE;
 *   bei Überlast antwortet er mit HTTP 503)
 * - bei Überlast, Zeitüberschreitung oder Fehlern Rückfall auf die normale Suche mit der Frage als Suchbegriff
 */
class NaturalLanguageSearchMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const ARGUMENT_FLAG = 'nl';

    public const ARGUMENT_QUESTION = 'nlq';

    public const ARGUMENT_STATUS = 'nlstatus';

    public const ARGUMENT_DROPPED = 'nldropped';

    public const STATUS_OK = 'ok';

    public const STATUS_RELAXED = 'relaxed';

    /**
     * Das LLM hat in der Frage nichts erkannt; es wurde normal gesucht.
     */
    public const STATUS_UNRECOGNIZED = 'unrecognized';

    /**
     * Das LLM war nicht verfügbar (Fehler, Zeitüberschreitung, Überlast, Rate Limit); es wurde normal gesucht.
     */
    public const STATUS_FALLBACK = 'fallback';

    private const RATE_LIMITER_ID = 'dla-opac-ng-natural-language-search';

    private readonly ?RateLimiterFactory $rateLimiterFactory;

    public function __construct(
        private readonly QueryUnderstandingService $service,
        QueryUnderstandingConfiguration $configuration,
        StorageInterface $rateLimiterStorage,
    ) {
        $this->rateLimiterFactory = $configuration->rateLimitPerMinute > 0
            ? new RateLimiterFactory([
                'id' => self::RATE_LIMITER_ID,
                'policy' => 'sliding_window',
                'limit' => $configuration->rateLimitPerMinute,
                'interval' => '1 minute',
            ], $rateLimiterStorage)
            : null;
        $this->logger = new NullLogger();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $findArguments = $queryParams['tx_find_find'] ?? null;
        if (!is_array($findArguments) || (string)($findArguments[self::ARGUMENT_FLAG] ?? '') !== '1') {
            return $handler->handle($request);
        }

        $rawQuestion = $findArguments['q']['default'] ?? '';
        $question = QueryUnderstandingService::normalizeQuestion(is_string($rawQuestion) ? $rawQuestion : '');
        if ($question === '') {
            return $this->createRedirect($request, '', self::STATUS_FALLBACK, null);
        }

        // Facetten der Anfrage (z.B. gewählter Medientyp-Reiter) bleiben in der Weiterleitung erhalten und
        // müssen deshalb auch beim Zählen für das schrittweise Lockern berücksichtigt werden.
        $retainedFacets = $findArguments['facet'] ?? [];
        $retainedFilterQueries = is_array($retainedFacets) ? SearchArgumentsBuilder::filterQueriesForFacets($retainedFacets) : [];

        [$status, $interpretation] = $this->interpret($question, $request, $retainedFilterQueries);

        return $this->createRedirect($request, $question, $status, $interpretation);
    }

    /**
     * @param list<string>|null $retainedFilterQueries null, wenn beibehaltene Facetten nicht übersetzbar sind
     * @return array{0: string, 1: QueryInterpretation|null} Status und Interpretation
     */
    private function interpret(string $question, ServerRequestInterface $request, ?array $retainedFilterQueries): array
    {
        if (!$this->service->isEnabled()) {
            return [self::STATUS_FALLBACK, null];
        }

        // Zwischengespeicherte Fragen kosten keinen LLM-Aufruf und unterliegen daher keiner Begrenzung.
        if (!$this->service->isCached($question) && !$this->isWithinRateLimit($request)) {
            $this->logger->notice('Natürlichsprachige Suche: Rate Limit erreicht');
            return [self::STATUS_FALLBACK, null];
        }

        $structure = $this->service->analyze($question);
        if ($structure === null) {
            return [self::STATUS_FALLBACK, null];
        }
        if ($structure->isEmpty()) {
            return [self::STATUS_UNRECOGNIZED, null];
        }

        $interpretation = $this->service->interpret($structure, $retainedFilterQueries);

        return [$interpretation->dropped === [] ? self::STATUS_OK : self::STATUS_RELAXED, $interpretation];
    }

    private function createRedirect(
        ServerRequestInterface $request,
        string $question,
        string $status,
        ?QueryInterpretation $interpretation
    ): ResponseInterface {
        $queryParams = $request->getQueryParams();
        $findArguments = $queryParams['tx_find_find'];
        unset(
            $findArguments[self::ARGUMENT_FLAG],
            $findArguments[self::ARGUMENT_QUESTION],
            $findArguments[self::ARGUMENT_STATUS],
            $findArguments[self::ARGUMENT_DROPPED],
            $findArguments['page'],
            $findArguments['start'],
        );
        // Manipulierte Parameter (z.B. tx_find_find[q]=x statt eines Arrays) nicht übernehmen.
        foreach (['q', 'facet'] as $key) {
            if (isset($findArguments[$key]) && !is_array($findArguments[$key])) {
                unset($findArguments[$key]);
            }
        }
        unset($findArguments['q']['default']);

        if ($question !== '') {
            $findArguments[self::ARGUMENT_QUESTION] = $question;
            if ($interpretation === null) {
                $findArguments['q']['default'] = $question;
            } else {
                $arguments = $interpretation->toFindArguments();
                if (isset($arguments['q']['default'])) {
                    $findArguments['q']['default'] = $arguments['q']['default'];
                }
                foreach ($arguments['facet'] ?? [] as $facetId => $terms) {
                    if (!isset($findArguments['facet'][$facetId]) || !is_array($findArguments['facet'][$facetId])) {
                        $findArguments['facet'][$facetId] = [];
                    }
                    foreach ($terms as $term => $value) {
                        $findArguments['facet'][$facetId][$term] = $value;
                    }
                }
                if ($interpretation->dropped !== []) {
                    $findArguments[self::ARGUMENT_DROPPED] = implode(', ', $interpretation->dropped);
                }
            }
            $findArguments[self::ARGUMENT_STATUS] = $status;
        }

        if (isset($findArguments['q']) && $findArguments['q'] === []) {
            unset($findArguments['q']);
        }

        $queryParams['tx_find_find'] = $findArguments;
        unset($queryParams['cHash']);

        $uri = $request->getUri()->withQuery(http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986));

        return new RedirectResponse($uri, 303, [
            'Cache-Control' => 'no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function isWithinRateLimit(ServerRequestInterface $request): bool
    {
        if ($this->rateLimiterFactory === null) {
            return true;
        }

        $clientIp = ClientIpUtility::resolveClientIp($request->getServerParams());

        return $this->rateLimiterFactory->create($clientIp)->consume()->isAccepted();
    }
}
