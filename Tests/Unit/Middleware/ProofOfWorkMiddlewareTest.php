<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Tests\Unit\Middleware;

use Dla\DlaOpacNg\Middleware\ProofOfWorkMiddleware;
use Dla\DlaOpacNg\Statistics\RawLogWriter;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ProofOfWorkMiddlewareTest extends UnitTestCase
{
    /** @var array<string, string|false> */
    private array $previousEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['campusRanges', 'sandboxRanges', 'staffRanges', 'opacPowEnabled', 'opacStatsEnabled'] as $envName) {
            $this->previousEnv[$envName] = getenv($envName);
        }
        $this->setEnvironmentVariable('campusRanges', '10.0.0.0/8');
        $this->setEnvironmentVariable('sandboxRanges', '192.168.0.0/16');
        $this->setEnvironmentVariable('staffRanges', '172.16.0.0/12');
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnv as $envName => $value) {
            $this->restoreEnvironmentVariable($envName, $value);
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function singleForwardedForAddressMatchingCampusRangeIsWhitelisted(): void
    {
        $request = $this->createRequestWithServerParams([
            'HTTP_X_FORWARDED_FOR' => '10.23.45.67',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        self::assertTrue($this->invokeIsWhitelistedIp($request));
    }

    /**
     * @test
     */
    public function firstForwardedForAddressIsUsedWhenMultipleAddressesArePresent(): void
    {
        $this->setEnvironmentVariable('sandboxRanges', '');
        $this->setEnvironmentVariable('staffRanges', '');
        $request = $this->createRequestWithServerParams([
            'HTTP_X_FORWARDED_FOR' => '10.23.45.67, 172.16.1.5',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        self::assertTrue($this->invokeIsWhitelistedIp($request));
    }

    /**
     * @test
     */
    public function remoteAddrIsUsedWhenForwardedForHeaderIsAbsent(): void
    {
        $request = $this->createRequestWithServerParams([
            'REMOTE_ADDR' => '10.23.45.67',
        ]);

        self::assertTrue($this->invokeIsWhitelistedIp($request));
    }

    /**
     * @test
     */
    public function remoteAddrIsUsedWhenForwardedForHeaderIsEmpty(): void
    {
        $request = $this->createRequestWithServerParams([
            'HTTP_X_FORWARDED_FOR' => '   ',
            'REMOTE_ADDR' => '10.23.45.67',
        ]);

        self::assertTrue($this->invokeIsWhitelistedIp($request));
    }

    /**
     * @test
     */
    public function forwardedForHeaderIsIgnoredForDirectPublicRequests(): void
    {
        $request = $this->createRequestWithServerParams([
            'HTTP_X_FORWARDED_FOR' => '10.23.45.67',
            'REMOTE_ADDR' => '203.0.113.7',
        ]);

        self::assertFalse($this->invokeIsWhitelistedIp($request));
    }

    /**
     * @test
     */
    public function invalidForwardedForEntryFallsBackToRemoteAddr(): void
    {
        $request = $this->createRequestWithServerParams([
            'HTTP_X_FORWARDED_FOR' => 'unknown, 10.23.45.68',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        self::assertFalse($this->invokeIsWhitelistedIp($request));
    }

    /**
     * @test
     */
    public function challengeIsCountedOnlyAsPowEventAndNotAsAccess(): void
    {
        $this->setEnvironmentVariable('opacPowEnabled', '1');
        $this->setEnvironmentVariable('opacStatsEnabled', '1');
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'test-key';

        $package = $this->createMock(PackageInterface::class);
        $package->method('getPackagePath')->willReturn(dirname(__DIR__, 3) . '/');
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('isPackageActive')->willReturn(true);
        $packageManager->method('getPackage')->willReturn($package);
        ExtensionManagementUtility::setPackageManager($packageManager);

        $directory = sys_get_temp_dir() . '/dla-opac-stats-pow-' . bin2hex(random_bytes(4));
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');
        $request = new ServerRequest('https://www.example.org/find/?tx_find_find[q][default]=x', 'GET', 'php://input', [], [
            'REMOTE_ADDR' => '203.0.113.7',
        ]);
        $request = $request->withQueryParams(['tx_find_find' => ['q' => ['default' => 'x']]]);

        $response = (new ProofOfWorkMiddleware(new RawLogWriter($directory)))->process($request, $handler);

        $files = glob($directory . '/raw/*.ndjson') ?: [];
        $lines = array_merge(...array_map(static fn(string $file): array => file($file, FILE_IGNORE_NEW_LINES), $files));
        array_map('unlink', $files);
        @rmdir($directory . '/raw');
        @rmdir($directory);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['{"a":"pow:issued"}'], $lines);
    }

    private function createRequestWithServerParams(array $serverParams): ServerRequestInterface&MockObject
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn($serverParams);

        return $request;
    }

    private function restoreEnvironmentVariable(string $envName, string|false $value): void
    {
        if ($value === false) {
            putenv($envName);
            unset($_ENV[$envName], $_SERVER[$envName]);
            return;
        }

        putenv($envName . '=' . $value);
        $_ENV[$envName] = $value;
        $_SERVER[$envName] = $value;
    }

    private function setEnvironmentVariable(string $envName, string $value): void
    {
        putenv($envName . '=' . $value);
        $_ENV[$envName] = $value;
        $_SERVER[$envName] = $value;
    }

    private function invokeIsWhitelistedIp(ServerRequestInterface $request): bool
    {
        $middleware = new ProofOfWorkMiddleware();
        $method = new \ReflectionMethod($middleware, 'isWhitelistedIp');
        $method->setAccessible(true);

        return $method->invoke($middleware, $request);
    }
}
