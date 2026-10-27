<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Statistics;

use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Sendet Requests über die Bulk-Tracking-API an Matomo (POST auf matomo.php, token_auth im JSON-Body).
 */
class MatomoClient
{
    private const TIMEOUT = 60;

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly string $url,
        #[\SensitiveParameter]
        private readonly string $token,
    ) {
    }

    /**
     * @param list<array<string, string|int>> $requests
     * @return int Anzahl der Requests, die Matomo als ungültig verworfen hat
     * @throws \RuntimeException wenn Matomo das Paket nicht angenommen hat
     */
    public function send(array $requests): int
    {
        $body = json_encode(
            ['requests' => $requests, 'token_auth' => $this->token],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        );

        $response = $this->requestFactory->request($this->url . '/matomo.php', 'POST', [
            'body' => $body,
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => self::TIMEOUT,
            'http_errors' => false,
        ]);

        $content = (string)$response->getBody();
        $data = json_decode($content, true);
        if ($response->getStatusCode() !== 200 || !is_array($data) || ($data['status'] ?? '') !== 'success') {
            throw new \RuntimeException(sprintf(
                'Matomo rejected the bulk request (HTTP %d): %s',
                $response->getStatusCode(),
                mb_substr(trim(strip_tags($content)), 0, 300)
            ));
        }

        return (int)($data['invalid'] ?? 0);
    }
}
