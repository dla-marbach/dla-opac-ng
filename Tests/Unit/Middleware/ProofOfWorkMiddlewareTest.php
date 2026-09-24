<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Tests\Unit\Middleware;

use Dla\DlaOpacNg\Middleware\ProofOfWorkMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ProofOfWorkMiddlewareTest extends UnitTestCase
{
    /** @var array<string, string|false> */
    private array $previousEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['campusRanges', 'sandboxRanges', 'staffRanges', 'trustedProxyRanges'] as $envName) {
            $this->previousEnv[$envName] = getenv($envName);
        }
        $this->setEnvironmentVariable('campusRanges', '10.0.0.0/8');
        $this->setEnvironmentVariable('sandboxRanges', '192.168.0.0/16');
        $this->setEnvironmentVariable('staffRanges', '172.16.0.0/12');
        $this->setEnvironmentVariable('trustedProxyRanges', '192.168.1.10/32');
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
            'REMOTE_ADDR' => '192.168.1.10',
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
            'REMOTE_ADDR' => '192.168.1.10',
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
        $this->setEnvironmentVariable('trustedProxyRanges', '');
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
        $this->setEnvironmentVariable('trustedProxyRanges', '10.23.45.67/32');
        $request = $this->createRequestWithServerParams([
            'HTTP_X_FORWARDED_FOR' => 'unknown, 10.23.45.68',
            'REMOTE_ADDR' => '10.23.45.67',
        ]);

        self::assertTrue($this->invokeIsWhitelistedIp($request));
    }

    /**
     * @test
     */
    public function invalidTrustedProxyRangeDoesNotBreakWhitelistCheck(): void
    {
        $this->setEnvironmentVariable('trustedProxyRanges', 'not-a-cidr');
        $request = $this->createRequestWithServerParams([
            'HTTP_X_FORWARDED_FOR' => '10.23.45.67',
            'REMOTE_ADDR' => '203.0.113.7',
        ]);

        self::assertFalse($this->invokeIsWhitelistedIp($request));
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
