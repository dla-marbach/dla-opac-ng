<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Proof-of-Work middleware to protect search result list pages from bot crawling.
 *
 * When a request contains the tx_find_find parameter,
 * the middleware checks for a valid PoW cookie. If absent, it serves a
 * challenge page whose embedded JavaScript solves a SHA-256 PoW puzzle.
 * Once solved, a signed cookie is set allowing 24 hours of access.
 *
 */
class ProofOfWorkMiddleware implements MiddlewareInterface
{
    /**
     * Cookie name for the PoW verification token.
     */
    private const COOKIE_NAME = 'dla_pow';

    /**
     * Number of leading hex zeros required in the SHA-256 hash.
     * 4 hex zeros ≈ 65 536 average hash operations ≈ 50-200 ms in browsers.
     */
    private const DIFFICULTY = 4;

    /**
     * PoW cookie lifetime in seconds (24 hours).
     */
    private const COOKIE_LIFETIME = 86400;

    /**
     * Maximum time allowed to solve a challenge (seconds).
     */
    private const CHALLENGE_LIFETIME = 300;

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (!$this->isSearchResultRequest($request)) {
            return $handler->handle($request);
        }

        if (!$this->isProofOfWorkEnabled()) {
            return $handler->handle($request);
        }

        if ($this->isWhitelistedIp($request)) {
            return $handler->handle($request);
        }

        if ($this->hasValidCookie($request)) {
            return $handler->handle($request);
        }

        // Handle PoW solution submitted via POST
        if ($request->getMethod() === 'POST') {
            $body = $request->getParsedBody();
            if (is_array($body) && $this->verifyProofOfWork($body)) {
                return $this->createSuccessResponse($request);
            }
        }

        return $this->createChallengePage($request);
    }

    /**
     * Determine whether the request contains tx_find_find parameters.
     */
    private function isSearchResultRequest(ServerRequestInterface $request): bool
    {
        $params = $request->getQueryParams();
        return isset($params['tx_find_find']);
    }

    /**
     * Enable PoW only when explicitly configured via environment.
     * Enabled only when opacPowEnabled is exactly set to "1".
     */
    private function isProofOfWorkEnabled(): bool
    {
        $value = getenv('opacPowEnabled');
        if ($value === false || $value === '') {
            return false;
        }

        $normalized = trim((string)$value, " \t\n\r\0\x0B\"");
        return $normalized === '1';
    }

    /**
     * Skip PoW checks for trusted network ranges configured via environment.
     */
    private function isWhitelistedIp(ServerRequestInterface $request): bool
    {
        $clientIp = (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');
        if ($clientIp === '') {
            return false;
        }

        $ranges = $this->getWhitelistedIpRanges();
        if ($ranges === []) {
            return false;
        }

        return IpUtils::checkIp($clientIp, $ranges);
    }

    /**
     * Read whitelist CIDR ranges from environment variables.
     * Expected variables: campusRanges, sandboxRanges, staffRanges
     *
     * @return string[]
     */
    private function getWhitelistedIpRanges(): array
    {
        $rangeVars = ['campusRanges', 'sandboxRanges', 'staffRanges'];
        $ranges = [];

        foreach ($rangeVars as $rangeVar) {
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

    /**
     * Check whether the request carries a valid, non-expired PoW cookie.
     */
    private function hasValidCookie(ServerRequestInterface $request): bool
    {
        $cookies = $request->getCookieParams();
        if (!isset($cookies[self::COOKIE_NAME])) {
            return false;
        }
        $parts = explode('.', $cookies[self::COOKIE_NAME], 2);
        if (count($parts) !== 2) {
            return false;
        }
        [$payload, $mac] = $parts;
        if (!hash_equals(hash_hmac('sha256', $payload, $this->getSecretKey()), $mac)) {
            return false;
        }
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            return false;
        }
        return (int)$decoded > time();
    }

    /**
     * Verify the submitted PoW nonce against the signed challenge.
     */
    private function verifyProofOfWork(array $body): bool
    {
        $challenge = $body['pow_challenge'] ?? '';
        $mac       = $body['pow_signature'] ?? '';
        $nonce     = $body['pow_nonce'] ?? '';

        if ($challenge === '' || $mac === '' || $nonce === '') {
            return false;
        }

        // Challenge must have been issued by this server
        if (!hash_equals(hash_hmac('sha256', $challenge, $this->getSecretKey()), $mac)) {
            return false;
        }

        // Challenge must not be expired
        $decoded = base64_decode($challenge, true);
        if ($decoded === false) {
            return false;
        }
        $parts = explode(':', $decoded, 2);
        if (count($parts) !== 2) {
            return false;
        }
        if ((time() - (int)$parts[0]) > self::CHALLENGE_LIFETIME) {
            return false;
        }

        // Hash must have the required number of leading hex zeros
        $hash = hash('sha256', $challenge . ':' . $nonce);
        return str_starts_with($hash, str_repeat('0', self::DIFFICULTY));
    }

    /**
     * Set the PoW cookie and redirect back to the original URL.
     */
    private function createSuccessResponse(ServerRequestInterface $request): ResponseInterface
    {
        $expiry  = time() + self::COOKIE_LIFETIME;
        $payload = base64_encode((string)$expiry);
        $mac     = hash_hmac('sha256', $payload, $this->getSecretKey());

        $uri    = $request->getUri();
        $target = $uri->getPath();
        $query  = $uri->getQuery();
        if ($query !== '') {
            $target .= '?' . $query;
        }

        $secure = $uri->getScheme() === 'https';
        $cookie = sprintf(
            '%s=%s; Path=/; HttpOnly; SameSite=Lax; Max-Age=%d%s',
            self::COOKIE_NAME,
            $payload . '.' . $mac,
            self::COOKIE_LIFETIME,
            $secure ? '; Secure' : ''
        );

        return new RedirectResponse($target, 302, [
            'Set-Cookie'    => $cookie,
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Generate a fresh challenge and return the challenge HTML page.
     */
    private function createChallengePage(ServerRequestInterface $request): ResponseInterface
    {
        $challenge = base64_encode(time() . ':' . bin2hex(random_bytes(16)));
        $mac       = hash_hmac('sha256', $challenge, $this->getSecretKey());

        $html = $this->renderChallengeHtml(
            // htmlspecialchars is safe here: challenge is Base64 and mac is hex,
            // neither charset contains characters that need JS string escaping.
            htmlspecialchars($challenge, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($mac, ENT_QUOTES, 'UTF-8'),
            self::DIFFICULTY,
            $this->resolveTemplateLanguage($request)
        );

        return new HtmlResponse($html, 200, [
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Build the self-contained HTML page that solves the PoW challenge in the browser.
     */
    private function renderChallengeHtml(string $challenge, string $signature, int $difficulty, string $language): string
    {
        return str_replace(
            ['__POW_CHALLENGE__', '__POW_SIGNATURE__', '__POW_DIFFICULTY__'],
            [$challenge, $signature, (string)$difficulty],
            $this->loadChallengeTemplate($language)
        );
    }

    /**
     * Load PoW challenge HTML from template file.
     *
     * @throws \RuntimeException if no template file can be loaded
     */
    private function loadChallengeTemplate(string $language): string
    {
        $basePath = ExtensionManagementUtility::extPath('dla_opac_ng')
            . 'Resources/Private/Templates/Middleware/';

        $templateCandidates = array_values(array_unique([
            $basePath . 'ProofOfWorkChallenge.' . $language . '.html',
            $basePath . 'ProofOfWorkChallenge.de.html',
            $basePath . 'ProofOfWorkChallenge.en.html',
        ]));

        foreach ($templateCandidates as $templatePath) {
            if (!is_file($templatePath) || !is_readable($templatePath)) {
                continue;
            }

            $template = file_get_contents($templatePath);
            if ($template !== false && $template !== '') {
                return $template;
            }
        }

        throw new \RuntimeException(
            'PoW challenge template not found or empty. Checked: ' . implode(', ', $templateCandidates),
            1711600001
        );
    }

    /**
     * Resolve template language from TYPO3 site language, fallback to Accept-Language.
     */
    private function resolveTemplateLanguage(ServerRequestInterface $request): string
    {
        $language = $request->getAttribute('language');
        if ($language instanceof SiteLanguage) {
            $languageCode = strtolower($language->getLocale()->getLanguageCode());
            if ($languageCode === 'de' || $languageCode === 'en') {
                return $languageCode;
            }
        }

        $acceptLanguage = $request->getHeaderLine('Accept-Language');
        if (preg_match('/(?:^|,)\s*de\b/i', $acceptLanguage)) {
            return 'de';
        }

        return 'en';
    }

    private function getSecretKey(): string
    {
        $key = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? '';
        if ($key === '') {
            throw new \RuntimeException(
                'TYPO3 encryptionKey is not configured. '
                . 'ProofOfWorkMiddleware requires $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'encryptionKey\'] to be set.',
                1711600000
            );
        }
        return $key;
    }
}
