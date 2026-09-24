<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Tests\Unit\Middleware;

use Dla\DlaOpacNg\Middleware\ProofOfWorkMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ProofOfWorkMiddlewareTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('campusRanges=10.0.0.0/8');
        putenv('sandboxRanges=192.168.0.0/16');
        putenv('staffRanges=172.16.0.0/12');
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
        $request = $this->createRequestWithServerParams([
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7, 10.23.45.67',
            'REMOTE_ADDR' => '192.168.1.10',
        ]);

        self::assertFalse($this->invokeIsWhitelistedIp($request));
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

    private function createRequestWithServerParams(array $serverParams): ServerRequestInterface&MockObject
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn($serverParams);

        return $request;
    }

    private function invokeIsWhitelistedIp(ServerRequestInterface $request): bool
    {
        $middleware = new ProofOfWorkMiddleware();
        $method = new \ReflectionMethod($middleware, 'isWhitelistedIp');
        $method->setAccessible(true);

        return $method->invoke($middleware, $request);
    }
}
