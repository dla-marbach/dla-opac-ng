<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Utility;

use Symfony\Component\HttpFoundation\IpUtils;

class ClientIpUtility
{
    /**
     * Umgebungsvariablen mit vertrauenswürdigen Netzbereichen (CIDR, kommagetrennt).
     */
    private const WHITELIST_ENV_VARS = ['campusRanges', 'sandboxRanges', 'staffRanges'];

    public static function resolveClientIp(array $serverParams, string $defaultRemoteAddr = ''): string
    {
        $remoteAddr = (string)($serverParams['REMOTE_ADDR'] ?? $defaultRemoteAddr);
        $forwardedFor = trim((string)($serverParams['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwardedFor === '' || $remoteAddr !== '127.0.0.1') {
            return $remoteAddr;
        }

        $forwardedClientIp = trim(explode(',', $forwardedFor)[0]);
        return filter_var($forwardedClientIp, FILTER_VALIDATE_IP) !== false
            ? $forwardedClientIp
            : $remoteAddr;
    }

    /**
     * Liegt der Client in einem der vertrauenswürdigen Netzbereiche (campusRanges, sandboxRanges, staffRanges)?
     */
    public static function isWhitelisted(array $serverParams): bool
    {
        $ranges = self::getWhitelistedIpRanges();
        if ($ranges === []) {
            return false;
        }

        $clientIp = self::resolveClientIp($serverParams);
        if ($clientIp === '') {
            return false;
        }

        return IpUtils::checkIp($clientIp, $ranges);
    }

    /**
     * Netzbereiche aus den Umgebungsvariablen lesen.
     *
     * @return string[]
     */
    public static function getWhitelistedIpRanges(): array
    {
        $ranges = [];

        foreach (self::WHITELIST_ENV_VARS as $rangeVar) {
            $rawValue = getenv($rangeVar);
            if ($rawValue === false || $rawValue === '') {
                continue;
            }

            foreach (explode(',', $rawValue) as $range) {
                $normalized = trim($range, " \t\n\r\0\x0B\"");
                if ($normalized !== '') {
                    $ranges[] = $normalized;
                }
            }
        }

        return array_values(array_unique($ranges));
    }
}
