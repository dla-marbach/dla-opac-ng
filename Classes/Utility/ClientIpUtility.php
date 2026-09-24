<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Utility;

use Symfony\Component\HttpFoundation\IpUtils;

class ClientIpUtility
{
    /**
     * @param string[] $trustedProxyRanges
     * @param string[] $forwardedIpRanges
     */
    public static function resolveClientIp(
        array $serverParams,
        array $trustedProxyRanges = [],
        array $forwardedIpRanges = [],
        string $defaultRemoteAddr = '',
        bool $requireForwardedIpRangeMatch = false
    ): string
    {
        $remoteAddr = (string)($serverParams['REMOTE_ADDR'] ?? $defaultRemoteAddr);
        $forwardedFor = trim((string)($serverParams['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwardedFor === '' || !self::isTrustedProxyAddress($remoteAddr, $trustedProxyRanges)) {
            return $remoteAddr;
        }

        $forwardedClientIp = trim(explode(',', $forwardedFor)[0]);
        return self::isAllowedForwardedClientIp($forwardedClientIp, $forwardedIpRanges, $requireForwardedIpRangeMatch)
            ? $forwardedClientIp
            : $remoteAddr;
    }

    /**
     * @param string[] $trustedProxyRanges
     */
    public static function isTrustedProxyAddress(string $ipAddress, array $trustedProxyRanges): bool
    {
        if ($ipAddress === '') {
            return false;
        }

        foreach ($trustedProxyRanges as $trustedProxyRange) {
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
     * Parse a comma-separated IP/CIDR range list from an environment variable.
     *
     * @return string[]
     */
    public static function getRangesFromEnvironmentVariable(string $envName): array
    {
        $rawValue = getenv($envName);
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
    private static function isAllowedForwardedClientIp(
        string $ipAddress,
        array $forwardedIpRanges,
        bool $requireForwardedIpRangeMatch
    ): bool
    {
        // Only forwarded IPs that actually fall into the configured access CIDRs
        // are relevant for these access checks; everything else can safely fall
        // back to the proxy's REMOTE_ADDR without changing the allow/deny outcome.
        if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if (!$requireForwardedIpRangeMatch) {
            return true;
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
