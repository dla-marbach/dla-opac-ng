<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Middleware;

use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use Dla\DlaOpacNg\Service\QueryUnderstandingService;
use Dla\DlaOpacNg\Utility\ClientIpUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
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
 * Die Weiterleitung enthält zusätzlich die Frage (nlq), den Status (nlstatus) und für die Interpretationszeile
 * die Facetten (nlf) und Suchbegriffe (nlt) aus der Frage, siehe InterpretationParts.
 *
 * Schutz vor Last:
 * - läuft nach der ProofOfWorkMiddleware (die Anfrage enthält tx_find_find und ist damit geschützt)
 * - Zwischenspeicher je Frage (wiederholte Fragen ohne LLM-Aufruf und ohne Begrenzung)
 * - Rate Limit je Client-IP (LLM_RATE_LIMIT) mit symfony/rate-limiter
 * - gleichzeitige Anfragen begrenzt der LLM-Server selbst (bei Ollama OLLAMA_NUM_PARALLEL und OLLAMA_MAX_QUEUE)
 * - bei Überlast, Zeitüberschreitung oder Fehlern Rückfall auf die normale Suche mit der Frage als Suchbegriff
 */
class NaturalLanguageSearchMiddleware implements MiddlewareInterface
{
    public const STATUS_OK = 'ok';

    /**
     * Das LLM hat in der Frage nichts erkannt; es wurde normal gesucht.
     */
    public const STATUS_UNRECOGNIZED = 'unrecognized';

    /**
     * Das LLM war nicht verfügbar (Fehler, Zeitüberschreitung, Überlast, Rate Limit); es wurde normal gesucht.
     */
    public const STATUS_FALLBACK = 'fallback';

    /**
     * Parameter der natürlichsprachigen Suche, die bei jeder neuen Frage ersetzt werden.
     */
    private const OWN_ARGUMENTS = ['nl', 'nlq', 'nlstatus', 'nlf', 'nlt'];

    private readonly ?RateLimiterFactory $rateLimiterFactory;

    public function __construct(
        private readonly QueryUnderstandingService $service,
        QueryUnderstandingConfiguration $configuration,
        StorageInterface $rateLimiterStorage,
        private readonly LoggerInterface $logger,
    ) {
        $this->rateLimiterFactory = $configuration->rateLimitPerMinute > 0
            ? new RateLimiterFactory([
                'id' => 'dla-opac-ng-natural-language-search',
                'policy' => 'sliding_window',
                'limit' => $configuration->rateLimitPerMinute,
                'interval' => '1 minute',
            ], $rateLimiterStorage)
            : null;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $findArguments = $queryParams['tx_find_find'] ?? null;
        if (!is_array($findArguments) || (string)($findArguments['nl'] ?? '') !== '1') {
            return $handler->handle($request);
        }

        $question = QueryUnderstandingService::normalizeQuestion(is_string($findArguments['q']['default'] ?? null) ? $findArguments['q']['default'] : '');

        // Eigene Parameter einer früheren Frage, Blättern und manipulierte (nicht-Array-)Parameter verwerfen.
        foreach ([...self::OWN_ARGUMENTS, 'page', 'start'] as $key) {
            unset($findArguments[$key]);
        }
        foreach (['q', 'facet'] as $key) {
            if (!is_array($findArguments[$key] ?? [])) {
                unset($findArguments[$key]);
            }
        }
        unset($findArguments['q']['default']);

        if ($question !== '') {
            [$status, $arguments] = $this->interpret($question, $request);
            $findArguments = array_replace_recursive($findArguments, $arguments, ['nlq' => $question, 'nlstatus' => $status]);
        }
        if (($findArguments['q'] ?? null) === []) {
            unset($findArguments['q']);
        }

        $queryParams['tx_find_find'] = $findArguments;
        unset($queryParams['cHash']);

        return new RedirectResponse(
            $request->getUri()->withQuery(http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986)),
            303,
            ['Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex, nofollow']
        );
    }

    /**
     * @return array{0: string, 1: array} Status und Such-Argumente (q, facet, nlf, nlt)
     */
    private function interpret(string $question, ServerRequestInterface $request): array
    {
        $fallback = [self::STATUS_FALLBACK, ['q' => ['default' => $question]]];
        if (!$this->service->isEnabled()) {
            return $fallback;
        }

        // Zwischengespeicherte Fragen kosten keinen LLM-Aufruf und unterliegen daher keiner Begrenzung.
        if (!$this->service->isCached($question) && !$this->isWithinRateLimit($request)) {
            $this->logger->notice('Natürlichsprachige Suche: Rate Limit erreicht');
            return $fallback;
        }

        $structure = $this->service->analyze($question);
        if ($structure === null) {
            return $fallback;
        }
        if ($structure->isEmpty()) {
            return [self::STATUS_UNRECOGNIZED, ['q' => ['default' => $question]]];
        }

        $interpretation = $this->service->interpret($structure);

        return [self::STATUS_OK, array_filter($interpretation->toFindArguments() + $interpretation->toUrlParts())];
    }

    private function isWithinRateLimit(ServerRequestInterface $request): bool
    {
        $clientIp = ClientIpUtility::resolveClientIp($request->getServerParams());

        return $this->rateLimiterFactory?->create($clientIp)->consume()->isAccepted() ?? true;
    }
}
