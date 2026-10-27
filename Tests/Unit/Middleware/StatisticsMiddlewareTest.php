<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Tests\Unit\Middleware;

use Dla\DlaOpacNg\Middleware\StatisticsMiddleware;
use Dla\DlaOpacNg\Statistics\RawLogWriter;
use Dla\DlaOpacNg\Statistics\StatisticsCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StatisticsMiddlewareTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private const BROWSER = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    private string $directory;

    /** @var array<string, string|false> */
    private array $previousEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/dla-opac-stats-mw-' . bin2hex(random_bytes(4));
        foreach (['opacStatsEnabled', 'campusRanges', 'sandboxRanges', 'staffRanges'] as $name) {
            $this->previousEnv[$name] = getenv($name);
        }
        putenv('opacStatsEnabled=1');
        putenv('campusRanges=10.0.0.0/8');
        putenv('sandboxRanges');
        putenv('staffRanges');
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnv as $name => $value) {
            putenv($value === false ? $name : $name . '=' . $value);
        }
        foreach (glob($this->directory . '/raw/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->directory . '/raw');
        @rmdir($this->directory);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function recordedAreaIsWrittenWithoutIpAddressOrUserAgent(): void
    {
        $request = $this->createRequest('203.0.113.7', self::BROWSER)
            ->withHeader('Referer', 'https://www.example.org/find/');

        $this->process($request, 200, static function (): void {
            GeneralUtility::makeInstance(StatisticsCollector::class)->recordDetail('DE-1', true, true, false);
        });

        $lines = $this->readLines();
        self::assertSame([[
            'a' => 'detail',
            'r' => 'int',
            'in' => 0,
            'bot' => 0,
            'd' => ['id' => 'DE-1', 'u' => 1, 'pdf' => 0],
        ]], $lines);
        $raw = implode('', array_map('file_get_contents', glob($this->directory . '/raw/*')));
        self::assertStringNotContainsString('203.0.113.7', $raw);
        self::assertStringNotContainsString('Mozilla', $raw);
    }

    /**
     * @test
     */
    public function internalNetworksAndBotsAreFlagged(): void
    {
        $this->process($this->createRequest('10.1.2.3', self::BROWSER), 200, $this->recordStart());
        $this->process($this->createRequest('203.0.113.7', 'curl/8.5.0'), 200, $this->recordStart());
        $this->process($this->createRequest('203.0.113.7', 'Mozilla/5.0 (compatible; Googlebot/2.1)'), 200, $this->recordStart());

        $lines = $this->readLines();
        self::assertSame([1, 0, 0], array_column($lines, 'in'));
        self::assertSame([0, 1, 1], array_column($lines, 'bot'));
        self::assertSame(['none', 'none', 'none'], array_column($lines, 'r'));
    }

    /**
     * @test
     */
    public function nothingIsWrittenForErrorsPrefetchHeadOrWhenDisabled(): void
    {
        $this->process($this->createRequest('203.0.113.7', self::BROWSER), 404, $this->recordStart());
        $this->process($this->createRequest('203.0.113.7', self::BROWSER)->withHeader('Sec-Purpose', 'prefetch'), 200, $this->recordStart());
        $this->process($this->createRequest('203.0.113.7', self::BROWSER, 'HEAD'), 200, $this->recordStart());
        $this->process($this->createRequest('203.0.113.7', self::BROWSER), 200, static function (): void {
        });
        putenv('opacStatsEnabled=0');
        $this->process($this->createRequest('203.0.113.7', self::BROWSER), 200, $this->recordStart());

        self::assertSame([], $this->readLines());
        self::assertFalse(GeneralUtility::makeInstance(StatisticsCollector::class)->hasEntries());
    }

    /**
     * @test
     */
    public function ajaxEndpointsAreCountedWithoutInput(): void
    {
        $request = $this->createRequest('203.0.113.7', self::BROWSER)
            ->withQueryParams(['q' => 'geheime eingabe', 'autocomplete' => '1']);
        $this->process($request, 200, static function (): void {
        });
        $request = $this->createRequest('203.0.113.7', self::BROWSER)
            ->withQueryParams(['type' => 'classification', 'action' => 'getNodes', 'collection' => '1', 'search' => 'x']);
        $this->process($request, 200, static function (): void {
        });

        $lines = $this->readLines();
        self::assertSame(['ajax:autocomplete', 'ajax:classification'], array_column($lines, 'a'));
        self::assertSame(['m' => 'getNodes'], $lines[1]['d']);
        self::assertArrayNotHasKey('d', $lines[0]);
    }

    private function recordStart(): \Closure
    {
        return static function (): void {
            GeneralUtility::makeInstance(StatisticsCollector::class)->record(StatisticsCollector::AREA_START);
        };
    }

    private function createRequest(string $ip, string $userAgent, string $method = 'GET'): ServerRequestInterface
    {
        return (new ServerRequest('https://www.example.org/find/', $method, 'php://input', [], ['REMOTE_ADDR' => $ip]))
            ->withHeader('User-Agent', $userAgent);
    }

    private function process(ServerRequestInterface $request, int $status, \Closure $plugin): ResponseInterface
    {
        $handler = new class ($status, $plugin) implements RequestHandlerInterface {
            public function __construct(private readonly int $status, private readonly \Closure $plugin)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                ($this->plugin)();
                return new Response('php://temp', $this->status);
            }
        };

        return (new StatisticsMiddleware(new RawLogWriter($this->directory)))->process($request, $handler);
    }

    private function readLines(): array
    {
        $lines = [];
        foreach (glob($this->directory . '/raw/*.ndjson') ?: [] as $file) {
            foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
                $lines[] = json_decode($line, true);
            }
        }

        return $lines;
    }
}
