<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Middleware;

use Dla\DlaOpacNg\Service\QueryUnderstanding\ConcurrencyLimiter;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryInterpretation;
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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * - Begrenzung je Client-IP und Minute (LLM_RATE_LIMIT) und gleichzeitiger Anfragen (LLM_MAX_CONCURRENT)
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

    public const STATUS_FALLBACK = 'fallback';

    private readonly ?FrontendInterface $cache;

    private readonly QueryUnderstandingService $service;

    private readonly ConcurrencyLimiter $concurrencyLimiter;

    public function __construct(
        ?QueryUnderstandingService $service = null,
        ?FrontendInterface $cache = null,
        ?ConcurrencyLimiter $concurrencyLimiter = null,
    ) {
        $this->cache = $cache ?? self::getDefaultCache();
        $this->service = $service ?? new QueryUnderstandingService(cache: $this->cache);
        $this->concurrencyLimiter = $concurrencyLimiter ?? new ConcurrencyLimiter(
            Environment::getVarPath() . '/dla_opac_ng/llm-locks',
            $this->service->getConfiguration()->maxConcurrent
        );
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

        // Facetten der Anfrage (z.B. gewählter Medientyp-Reiter) bleiben in der Weiterleitung erhalten und
        // müssen deshalb auch beim Zählen für das schrittweise Lockern berücksichtigt werden.
        $retainedFacets = $findArguments['facet'] ?? [];
        $retainedFilterQueries = is_array($retainedFacets) ? SearchArgumentsBuilder::filterQueriesForFacets($retainedFacets) : [];

        $interpretation = $question !== '' ? $this->interpret($question, $request, $retainedFilterQueries) : null;

        return $this->createRedirect($request, $question, $interpretation);
    }

    /**
     * @param list<string>|null $retainedFilterQueries null, wenn beibehaltene Facetten nicht übersetzbar sind
     */
    private function interpret(string $question, ServerRequestInterface $request, ?array $retainedFilterQueries): ?QueryInterpretation
    {
        if (!$this->service->isEnabled()) {
            return null;
        }
        $this->service->setLogger($this->logger);

        // Zwischengespeicherte Fragen kosten keinen LLM-Aufruf und unterliegen daher keiner Begrenzung.
        if (!$this->service->isCached($question)) {
            if (!$this->isWithinRateLimit($request)) {
                $this->logger->notice('Natürlichsprachige Suche: Rate Limit erreicht');
                return null;
            }
            if (!$this->concurrencyLimiter->acquire()) {
                $this->logger->notice('Natürlichsprachige Suche: alle LLM-Plätze belegt');
                return null;
            }
        }

        try {
            $structure = $this->service->analyze($question);
        } finally {
            $this->concurrencyLimiter->release();
        }

        return $structure === null ? null : $this->service->interpret($structure, $retainedFilterQueries);
    }

    private function createRedirect(
        ServerRequestInterface $request,
        string $question,
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
                $findArguments[self::ARGUMENT_STATUS] = self::STATUS_FALLBACK;
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
                $findArguments[self::ARGUMENT_STATUS] = $interpretation->dropped === [] ? self::STATUS_OK : self::STATUS_RELAXED;
                if ($interpretation->dropped !== []) {
                    $findArguments[self::ARGUMENT_DROPPED] = implode(', ', $interpretation->dropped);
                }
            }
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

    /**
     * Einfacher Zähler je Client-IP und Minute im Zwischenspeicher (für einen Proof of Concept ausreichend genau).
     */
    private function isWithinRateLimit(ServerRequestInterface $request): bool
    {
        $limit = $this->service->getConfiguration()->rateLimitPerMinute;
        if ($limit === 0 || $this->cache === null) {
            return true;
        }

        $clientIp = ClientIpUtility::resolveClientIp($request->getServerParams());
        $identifier = 'rl_' . sha1($clientIp . '|' . intdiv(time(), 60));
        $count = (int)($this->cache->get($identifier) ?: 0);
        if ($count >= $limit) {
            return false;
        }
        $this->cache->set($identifier, $count + 1, [], 120);

        return true;
    }

    private static function getDefaultCache(): ?FrontendInterface
    {
        try {
            return GeneralUtility::makeInstance(CacheManager::class)->getCache(QueryUnderstandingService::CACHE_IDENTIFIER);
        } catch (\Throwable) {
            return null;
        }
    }
}
