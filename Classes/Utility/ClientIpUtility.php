<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Utility;

use Symfony\Component\HttpFoundation\IpUtils;

class ClientIpUtility
{
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

        $trustedProxyRanges = self::getTrustedProxyRanges();
        return $trustedProxyRanges !== [] && IpUtils::checkIp($ipAddress, $trustedProxyRanges);
    }

    /**
     * @return string[]
     */
    private static function getTrustedProxyRanges(): array
    {
        $rawValue = getenv('trustedProxyRanges');
        if ($rawValue === false || $rawValue === '') {
            return [];
        }

        $ranges = [];
        foreach (explode(',', $rawValue) as $range) {
            $normalized = trim($range, " \t\n\r\0\x0B\"");
            if ($normalized !== '') {
                $ranges[] = $normalized;
            }
        }

        return array_values(array_unique($ranges));
    }
}
