<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Utility;

use Symfony\Component\HttpFoundation\IpUtils;

class ClientIpUtility
{
    private const TRUSTED_PROXY_RANGES = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8',
        'fc00::/7',
        'fe80::/10',
        '::1/128',
    ];

    public static function resolveClientIp(array $serverParams, string $defaultRemoteAddr = ''): string
    {
        $remoteAddr = (string)($serverParams['REMOTE_ADDR'] ?? $defaultRemoteAddr);
        $forwardedFor = trim((string)($serverParams['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwardedFor === '' || !self::isTrustedProxyAddress($remoteAddr)) {
            return $remoteAddr;
        }

        $forwardedClientIp = trim(explode(',', $forwardedFor)[0]);
        return filter_var($forwardedClientIp, FILTER_VALIDATE_IP) !== false
            ? $forwardedClientIp
            : $remoteAddr;
    }

    public static function isTrustedProxyAddress(string $ipAddress): bool
    {
        if ($ipAddress === '') {
            return false;
        }

        return IpUtils::checkIp($ipAddress, self::TRUSTED_PROXY_RANGES);
    }
}
