<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Utility;

use Symfony\Component\HttpFoundation\IpUtils;

class ClientIpUtility
{
    /**
     * @param string[] $forwardedIpRanges
     */
    public static function resolveClientIp(array $serverParams, array $forwardedIpRanges = [], string $defaultRemoteAddr = ''): string
    {
        $remoteAddr = (string)($serverParams['REMOTE_ADDR'] ?? $defaultRemoteAddr);
        $forwardedFor = trim((string)($serverParams['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwardedFor === '' || !self::isTrustedProxyAddress($remoteAddr)) {
            return $remoteAddr;
        }

        $forwardedClientIp = trim(explode(',', $forwardedFor)[0]);
        return self::isAllowedForwardedClientIp($forwardedClientIp, $forwardedIpRanges)
            ? $forwardedClientIp
            : $remoteAddr;
    }

    public static function isTrustedProxyAddress(string $ipAddress): bool
    {
        if ($ipAddress === '') {
            return false;
        }

        foreach (self::getTrustedProxyRanges() as $trustedProxyRange) {
            try {
                if (IpUtils::checkIp($ipAddress, $trustedProxyRange)) {
                    return true;
                }
            } catch (\InvalidArgumentException $exception) {
                continue;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private static function getTrustedProxyRanges(): array
    {
        // `trustedProxyRanges` is a comma-separated list of IPs/CIDR ranges for
        // proxy hops whose `X-Forwarded-For` header may be trusted.
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

    /**
     * @param string[] $forwardedIpRanges
     */
    private static function isAllowedForwardedClientIp(string $ipAddress, array $forwardedIpRanges): bool
    {
        // Only forwarded IPs that actually fall into the configured access CIDRs
        // are relevant for these access checks; everything else can safely fall
        // back to the proxy's REMOTE_ADDR without changing the allow/deny outcome.
        if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        foreach ($forwardedIpRanges as $forwardedIpRange) {
            try {
                if (IpUtils::checkIp($ipAddress, $forwardedIpRange)) {
                    return true;
                }
            } catch (\InvalidArgumentException $exception) {
                continue;
            }
        }

        return false;
    }
}
