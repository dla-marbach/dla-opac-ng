<?php

namespace Dla\DlaOpacNg\Tests\Support;

/**
 * Startet/stoppt den PHP-eingebauten Webserver mit Tests/Fixtures/Solr/MockSolrServer.php
 * als Solr-Ersatz für Tests, die ohne Netzwerkzugriff auf den echten Solr laufen sollen.
 */
class MockSolrServerProcess
{
    private static ?self $instance = null;

    /** @var resource */
    private $process;

    private string $baseUrl;

    private function __construct($process, string $baseUrl)
    {
        $this->process = $process;
        $this->baseUrl = $baseUrl;
    }

    public static function start(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $port = self::findFreePort();
        $docroot = dirname(__DIR__) . '/Fixtures/Solr';
        $router = $docroot . '/MockSolrServer.php';

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            ['php', '-S', '127.0.0.1:' . $port, '-t', $docroot, $router],
            $descriptorSpec,
            $pipes
        );

        if ($process === false) {
            throw new \RuntimeException('Mock-Solr-Server konnte nicht gestartet werden.');
        }

        foreach ($pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }

        self::waitUntilReady($port, $process);

        $baseUrl = 'http://127.0.0.1:' . $port . '/solr/';
        self::$instance = new self($process, $baseUrl);
        register_shutdown_function([self::$instance, 'stop']);

        return self::$instance;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function stop(): void
    {
        if (!is_resource($this->process)) {
            return;
        }
        proc_terminate($this->process);
        proc_close($this->process);
        self::$instance = null;
    }

    private static function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            throw new \RuntimeException("Kein freier Port gefunden: {$errstr}");
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        return (int)substr($name, strrpos($name, ':') + 1);
    }

    /** @param resource $process */
    private static function waitUntilReady(int $port, $process): void
    {
        $deadline = microtime(true) + 5.0;
        while (microtime(true) < $deadline) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                throw new \RuntimeException('Mock-Solr-Server ist unerwartet beendet worden.');
            }
            $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if ($socket !== false) {
                fclose($socket);
                return;
            }
            usleep(20_000);
        }
        throw new \RuntimeException('Mock-Solr-Server wurde nicht rechtzeitig bereit.');
    }
}
