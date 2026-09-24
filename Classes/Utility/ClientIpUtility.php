<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Utility;

class ClientIpUtility
{
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
}
