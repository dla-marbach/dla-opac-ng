<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Middleware;

use Dla\DlaOpacNg\Statistics\RawLogWriter;
use Dla\DlaOpacNg\Statistics\StatisticsCollector;
use Dla\DlaOpacNg\Statistics\StatisticsConfiguration;
use Dla\DlaOpacNg\Utility\ClientIpUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Datensparsame Nutzungsstatistik des Katalogs.
 *
 * Liegt innerhalb der ProofOfWorkMiddleware (siehe RequestMiddlewares.php): Requests, die dort mit der
 * Challenge-Seite beantwortet werden, erreichen diese Middleware nicht und werden nicht gezählt.
 *
 * Nach der Antwort wird pro Request höchstens eine Zeile je Bereich in das Rohlog geschrieben, das der Befehl
 * dla_opac_ng:stats:matomo-push an Matomo sendet. Die IP-Adresse und der User-Agent werden nur zur Laufzeit
 * ausgewertet (intern/extern, Bot) und weder gespeichert noch an Matomo übertragen.
 */
class StatisticsMiddleware implements MiddlewareInterface
{
    /**
     * Maximale Länge der erfassten URL (Matomo speichert bis zu 255 Zeichen pro Merkmal).
     */
    private const MAX_URL_LENGTH = 250;

    /**
     * User-Agents, die sich selbst als Bot, Crawler oder Skript zu erkennen geben.
     */
    private const BOT_PATTERN = '/bot\b|crawl|spider|slurp|scrap|fetch|curl|wget|python|java\/|go-http|libwww|httpclient|okhttp|axios|node-fetch|headless|phantom|lighthouse|monitor|preview|archiver|facebookexternalhit|^$/i';

    public function __construct(
        private readonly RawLogWriter $rawLogWriter = new RawLogWriter()
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $collector = GeneralUtility::makeInstance(StatisticsCollector::class);

        $ajaxArea = $this->detectAjaxArea($request->getQueryParams());
        if ($ajaxArea !== null) {
            $collector->record($ajaxArea[0], $ajaxArea[1]);
        }

        $response = $handler->handle($request);

        $entries = $collector->pull();
        if ($entries === [] || !$this->isCountable($request, $response) || !StatisticsConfiguration::isEnabled()) {
            return $response;
        }

        $flags = $this->describeRequest($request);
        foreach ($entries as $area => $data) {
            $record = ['a' => $area] + $flags;
            if ($data !== []) {
                $record['d'] = $data;
            }
            $this->rawLogWriter->append($record);
        }

        return $response;
    }

    /**
     * Nur erfolgreich ausgelieferte Seiten (inkl. Weiterleitungen, z.B. Suche mit genau einem Treffer)
     * und keine Prefetch-Anfragen des Browsers.
     */
    private function isCountable(ServerRequestInterface $request, ResponseInterface $response): bool
    {
        if (!in_array($request->getMethod(), ['GET', 'POST'], true)) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        $purpose = strtolower($request->getHeaderLine('Sec-Purpose') . ' ' . $request->getHeaderLine('Purpose')
            . ' ' . $request->getHeaderLine('X-Moz'));

        return !str_contains($purpose, 'prefetch');
    }

    /**
     * Merkmale, die für alle Bereiche erfasst werden: URL ohne Query-Parameter, Sprache, Herkunft
     * (Referrer-Kategorie, bei anderen Websites nur deren Hostname), intern/extern und Bot.
     * Keine IP-Adresse, kein User-Agent, keine Query-Parameter.
     */
    private function describeRequest(ServerRequestInterface $request): array
    {
        $flags = [];

        $uri = $request->getUri();
        $flags['u'] = StatisticsCollector::truncate($uri->getScheme() . '://' . $uri->getHost() . $uri->getPath(), self::MAX_URL_LENGTH);

        $language = $request->getAttribute('language');
        if ($language instanceof SiteLanguage) {
            $flags['l'] = strtolower($language->getLocale()->getLanguageCode());
        }

        $referrerHost = $this->getReferrerHost($request);
        if ($referrerHost === null) {
            $flags['r'] = 'none';
        } elseif ($referrerHost === strtolower($uri->getHost())) {
            $flags['r'] = 'int';
        } else {
            $flags['r'] = 'ext';
            if ($referrerHost !== '') {
                $flags['rh'] = $referrerHost;
            }
        }

        $flags['in'] = ClientIpUtility::isWhitelisted($request->getServerParams()) ? 1 : 0;
        $flags['bot'] = preg_match(self::BOT_PATTERN, $request->getHeaderLine('User-Agent')) === 1 ? 1 : 0;

        return $flags;
    }

    /**
     * Nur der Hostname des Referrers (null ohne Referrer, '' wenn er sich nicht bestimmen lässt).
     */
    private function getReferrerHost(ServerRequestInterface $request): ?string
    {
        $referrer = $request->getHeaderLine('Referer');
        if ($referrer === '') {
            return null;
        }

        $host = strtolower((string)parse_url($referrer, PHP_URL_HOST));

        return preg_match('/^[a-z0-9.-]{1,253}$/', $host) === 1 ? $host : '';
    }

    /**
     * Erkennt die AJAX-Endpunkte des Katalogs anhand derselben Parameter wie die jeweiligen Middlewares
     * in Classes/Ajax/. Gezählt wird nur der Endpunkt (bei der Baumansicht zusätzlich Typ und Aktion),
     * keine Eingaben.
     *
     * @return array{0: string, 1: array}|null
     */
    private function detectAjaxArea(array $queryParams): ?array
    {
        if (isset($queryParams['q'], $queryParams['autocomplete'])) {
            return ['ajax:autocomplete', []];
        }
        if (isset($queryParams['q'], $queryParams['p'], $queryParams['decisiontree'])) {
            return ['ajax:decisiontree', []];
        }
        if (isset($queryParams['q'], $queryParams['getEntity'])) {
            return ['ajax:entity', []];
        }
        if (isset($queryParams['q'], $queryParams['getEntities'])) {
            return ['ajax:entities', []];
        }
        if (!empty($queryParams['watchlistCsv'])) {
            return ['ajax:export', []];
        }
        if (isset($queryParams['type'], $queryParams['action'], $queryParams['collection'])) {
            $type = $queryParams['type'] === 'classification' ? 'classification' : 'collection';
            $action = is_scalar($queryParams['action']) ? StatisticsCollector::truncate((string)$queryParams['action']) : '';

            return ['ajax:' . $type, ['m' => $action]];
        }

        return null;
    }
}
