<?php

namespace Dla\DlaOpacNg\Tests\Support;

/**
 * Startet/stoppt den PHP-eingebauten Webserver mit Tests/Fixtures/Llm/MockLlmServer.php als Ersatz für
 * eine OpenAI-kompatible LLM-API, damit die Tests ohne laufendes Modell auskommen.
 */
class MockLlmServerProcess
{
    private static ?self $instance = null;

    /** @var resource */
    private $process;

    private function __construct($process, private readonly string $baseUrl, private readonly string $requestLog)
    {
        $this->process = $process;
    }

    public static function start(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            throw new \RuntimeException("Kein freier Port gefunden: {$errstr}");
        }
        $name = (string)stream_socket_get_name($socket, false);
        fclose($socket);
        $port = (int)substr($name, strrpos($name, ':') + 1);

        $docroot = dirname(__DIR__) . '/Fixtures/Llm';
        $requestLog = (string)tempnam(sys_get_temp_dir(), 'mock-llm-');
        $process = proc_open(
            ['php', '-S', '127.0.0.1:' . $port, '-t', $docroot, $docroot . '/MockLlmServer.php'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            array_merge(getenv(), ['MOCK_LLM_REQUEST_LOG' => $requestLog])
        );
        if ($process === false) {
            throw new \RuntimeException('Mock-LLM-Server konnte nicht gestartet werden.');
        }
        foreach ($pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }

        $deadline = microtime(true) + 5.0;
        while (true) {
            if (!proc_get_status($process)['running']) {
                throw new \RuntimeException('Mock-LLM-Server ist unerwartet beendet worden.');
            }
            $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if ($connection !== false) {
                fclose($connection);
                break;
            }
            if (microtime(true) > $deadline) {
                throw new \RuntimeException('Mock-LLM-Server wurde nicht rechtzeitig bereit.');
            }
            usleep(20_000);
        }

        self::$instance = new self($process, 'http://127.0.0.1:' . $port . '/v1', $requestLog);
        register_shutdown_function([self::$instance, 'stop']);

        return self::$instance;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Zuletzt vom Mock-Server empfangene Anfrage: ['authorization' => ?string, 'body' => array].
     */
    public function getLastRequest(): array
    {
        return json_decode((string)@file_get_contents($this->requestLog), true) ?: [];
    }

    public function clearLastRequest(): void
    {
        file_put_contents($this->requestLog, '');
    }

    public function stop(): void
    {
        if (!is_resource($this->process)) {
            return;
        }
        proc_terminate($this->process);
        proc_close($this->process);
        @unlink($this->requestLog);
        self::$instance = null;
    }
}
