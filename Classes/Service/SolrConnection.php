<?php

namespace Dla\DlaOpacNg\Service;

use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Zentrale Solr-Verbindung für Such-Plugin, ViewHelper, Ajax-Endpunkte und Services.
 *
 * Einzige Konfigurationsquelle sind die Umgebungsvariablen:
 * - SOLR_HOST: Basis-URL des Solr-Servers inkl. Kontext, z.B. "http://host.docker.internal:8983/solr/"
 * - SOLR_CORE: Name des Cores, z.B. "internformat"
 * - SOLR_TIMEOUT: optional, Timeout in Sekunden für Solarium-Anfragen (Standard: 10)
 */
class SolrConnection
{
    private const DEFAULT_TIMEOUT = 10;

    /**
     * Timeout in Sekunden für die schnellen Rohanfragen der Ajax-Endpunkte.
     */
    private const REQUEST_TIMEOUT = 1.0;

    /**
     * Wiederverwendbare Solarium-Clients je Konfiguration.
     *
     * @var array<string,Client>
     */
    private static array $clients = [];

    /**
     * Liefert die Basis-URL des Cores mit abschließendem Slash, z.B. "http://host:8983/solr/internformat/".
     */
    public function getCoreUrl(): string
    {
        $host = trim((string)getenv('SOLR_HOST'));
        $core = trim((string)getenv('SOLR_CORE'), " \n\r\t\v\0/");
        if ($host === '' || $core === '') {
            throw new \RuntimeException('Solr-Verbindung nicht konfiguriert: Umgebungsvariablen SOLR_HOST und SOLR_CORE setzen.', 1759800000);
        }

        return rtrim($host, '/') . '/' . $core . '/';
    }

    /**
     * Solarium-Client für den konfigurierten Core.
     */
    public function getClient(): Client
    {
        $coreUrl = $this->getCoreUrl();
        $timeout = (int)getenv('SOLR_TIMEOUT') ?: self::DEFAULT_TIMEOUT;
        $cacheKey = $coreUrl . '|' . $timeout;

        if (!isset(self::$clients[$cacheKey])) {
            $adapter = new Curl();
            $adapter->setTimeout($timeout);
            self::$clients[$cacheKey] = new Client($adapter, new EventDispatcher(), [
                'endpoint' => [
                    'default' => $this->endpointOptions($coreUrl),
                ],
            ]);
        }

        return self::$clients[$cacheKey];
    }

    /**
     * Schickt eine GET-Anfrage an einen Request-Handler des Cores (z.B. "select", "suggest") und liefert
     * die dekodierte JSON-Antwort oder null bei fehlender Konfiguration, Verbindungs- oder Parse-Fehlern.
     *
     * @param array<string,string|int|list<string>> $params Mehrfach vorkommende Parameter als Liste übergeben.
     */
    public function request(string $handler, array $params): ?array
    {
        try {
            $url = $this->getCoreUrl() . $handler;
        } catch (\RuntimeException) {
            return null;
        }

        $query = [];
        foreach ($params as $key => $values) {
            foreach ((array)$values as $value) {
                $query[] = rawurlencode((string)$key) . '=' . rawurlencode((string)$value);
            }
        }

        $response = @file_get_contents(
            $url . '?' . implode('&', $query),
            false,
            stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'follow_location' => 0,
                    'timeout' => self::REQUEST_TIMEOUT,
                ],
            ])
        );
        if ($response === false) {
            return null;
        }

        $json = json_decode($response, true);

        return is_array($json) ? $json : null;
    }

    /**
     * Zerlegt die Core-URL in Solarium-Endpoint-Optionen (Solarium setzt die URL
     * als scheme://host:port{path}/{context}/{core}/ zusammen).
     */
    private function endpointOptions(string $coreUrl): array
    {
        $url = parse_url($coreUrl);
        $segments = array_values(array_filter(explode('/', $url['path'] ?? ''), static fn(string $s): bool => $s !== ''));
        $core = array_pop($segments);
        $context = array_pop($segments) ?? 'solr';
        $scheme = $url['scheme'] ?? 'http';

        return [
            'scheme' => $scheme,
            'host' => $url['host'] ?? 'localhost',
            'port' => $url['port'] ?? ($scheme === 'https' ? 443 : 80),
            'path' => '/' . implode('/', $segments),
            'context' => $context,
            'core' => $core,
        ];
    }
}
