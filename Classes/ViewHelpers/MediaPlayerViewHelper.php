<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use Dla\DlaOpacNg\Service\M3uPlaylistParser;
use Symfony\Component\HttpFoundation\IpUtils;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Core\Environment;

class MediaPlayerViewHelper extends AbstractViewHelper
{
    protected const PLAYLIST_CACHE_TTL = 300;
    protected const PLAYLIST_CACHE_LIMIT = 100;
    protected static array $playlistCache = [];
    /**
     * Datei-Endungen, die im Video.js-Player als Audio bzw. Video abgespielt werden können.
     */
    protected const AUDIO_EXTENSIONS = ['wav', 'mp3', 'mp2', 'm4a', 'ogg', 'oga', 'flac'];
    protected const VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogv', 'mov', 'm4v'];

    /**
     * Datei-Endungen, die als (Extended) M3U-Playlisten interpretiert werden.
     */
    protected const PLAYLIST_EXTENSIONS = ['m3u', 'm3u8'];

    /**
     * Datei-Endungen, die (sofern nicht gesperrt) als Bild in der Slideshow
     * (Resources/Private/Partials/MediaAccess/Slideshow.html) statt als einfacher
     * Link angezeigt werden.
     */
    protected const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'tif', 'tiff', 'bmp', 'webp'];

    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('urls', 'mixed', 'urls', true, []);
        $this->registerArgument('ext', 'mixed', 'ext', false, []);
        $this->registerArgument('access', 'mixed', 'access', true, []);
        $this->registerArgument('display', 'mixed', 'display labels', false, []);
        $this->registerArgument('chapters', 'mixed', 'WebVTT-Kapitelmarken-URLs je Objekt', false, []);
        $this->registerArgument('thumbnails', 'mixed', 'Vorschaubild-URLs je Objekt (für Bilder in der Slideshow)', false, []);
        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * Normalize Fluid input to an array. Item fields can come in as strings
     * and may contain the special record separator used by Solr exports.
     */
    protected function normalizeToArray($value): array
    {
        $normalized = [];
        $values = is_array($value) ? $value : [$value];

        foreach ($values as $entry) {
            if ($entry === null || $entry === '') {
                continue;
            }

            if (is_string($entry) && preg_match('/[␝\x1D]/u', $entry)) {
                $parts = preg_split('/[␝\x1D]/u', $entry);
                if (is_array($parts)) {
                    foreach ($parts as $part) {
                        $normalized[] = is_string($part) ? trim($part) : $part;
                    }
                }
                continue;
            }

            $normalized[] = is_string($entry) ? trim($entry) : $entry;
        }

        return $normalized;
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $urls = $this->normalizeToArray($this->arguments['urls'] ?? null);
        $ext = $this->normalizeToArray($this->arguments['ext'] ?? null);
        $access = $this->normalizeToArray($this->arguments['access'] ?? null);
        $display = $this->normalizeToArray($this->arguments['display'] ?? null);
        $chapters = $this->normalizeToArray($this->arguments['chapters'] ?? null);
        $thumbnails = $this->normalizeToArray($this->arguments['thumbnails'] ?? null);
        $resultValue = $this->buildMediaData($urls, $ext, $access, $display, $chapters, $thumbnails);

        $valueName = $this->arguments['as'];
        if ($valueName !== null) {
            if ($this->templateVariableContainer->exists($valueName)) {
                $this->templateVariableContainer->remove($valueName);
            }
            $this->templateVariableContainer->add($valueName, $resultValue);
//            $result = $this->renderChildren();
        }

    }

    /**
     * Builds the "links" (all digital objects not otherwise categorized, incl. forbidden
     * ones), "mediaplayer" (playable audio/video/playlist objects only, forbidden ones
     * excluded from playback) and "images" (viewable, non-forbidden images only - forbidden
     * images stay in "links" so the existing restricted-access notice keeps covering them,
     * see Resources/Private/Partials/MediaAccess/RenderLinks.html) result structures from
     * already normalized, index-aligned input arrays. Pure/side-effect-free besides the
     * (best-effort) playlist HTTP fetch, kept separate from Fluid-specific plumbing
     * so it can be unit tested directly.
     *
     * @param array $urls
     * @param array $ext
     * @param array $access
     * @param array $display
     * @param array $chapters
     * @param array $thumbnails
     * @return array{links: array, mediaplayer: array, images: array}
     */
    public function buildMediaData(array $urls, array $ext, array $access, array $display, array $chapters, array $thumbnails = []): array
    {
        $campusRanges = explode(',', getenv('campusRanges'));
        $sandboxRanges = explode(',', getenv('sandboxRanges'));
        $staffRanges = explode(',', getenv('staffRanges'));

        // ip-adress ranges
        $ipRanges = [
            'staff' => $staffRanges,
            'sandbox' => $sandboxRanges,
            'campus' => $campusRanges
        ];

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $currentGroup = [];
        foreach ($ipRanges as $group => $range) {
            if (IpUtils::checkIp($clientIp, $range)) {
                $currentGroup[$group] = 1;
            }
        }

//        $restrictionGroups = ['op_admin', 'op_campus', 'op_sandbox', 'op_staff'];
//        $userRestrictionGroup = 'public';

        $resultValue = ['links' => [], 'mediaplayer' => [], 'images' => []];

//        $context = GeneralUtility::makeInstance(Context::class);
//        $userAspect = $context->getAspect('frontend.user');
//
//        if ($userAspect->isLoggedIn()) {
//            $userId = $userAspect->get('id');
//            $userGroupIds = $userAspect->get('groupIds');
//        }

        // frontend user restriction
//        $countGroupDataFE = $GLOBALS['TSFE']->fe_user->fetchGroupData();
//        if ($countGroupDataFE > 0) {
//            foreach ($GLOBALS['TSFE']->fe_user->groupData['title'] as $groupId => $groupTitle) {
//                if(in_array($groupTitle, $restrictionGroups)) {
//                    $userRestrictionGroup = $groupTitle;
//                }
//            }
//        }

        $i = 0;
        foreach ($access as $value) {
            // Show file data with information if file is forbidden
            $currentExt = $ext[$i] ?? null;
            $normalizedExt = is_string($currentExt) ? strtolower(ltrim(trim($currentExt), '.')) : null;

            $urlAccess = [
                'url' => $urls[$i] ?? null,
                'ext' => $currentExt,
                'display' => $display[$i] ?? null,
                'access' => $value,
                'forbidden' => 1,
            ];
            if ((is_string($value) && array_key_exists($value, $currentGroup)) || $value === 'public') {
                $urlAccess['forbidden'] = 0;
            }

            $mediaType = $this->mediaTypeForExtension($normalizedExt);
            if ($mediaType !== null && !empty($urlAccess['url'])) {
                $mediaItem = $urlAccess;
                $mediaItem['type'] = $mediaType;
                $mediaItem['chapters'] = $chapters[$i] ?? null;
                if ($mediaType === 'playlist' && $urlAccess['forbidden'] === 0) {
                    $mediaItem['tracks'] = $this->fetchPlaylistTracks($urlAccess['url']);
                }

                $resultValue['mediaplayer'][] = $mediaItem;
            } elseif ($normalizedExt !== null && in_array($normalizedExt, static::IMAGE_EXTENSIONS, true) && !empty($urlAccess['url']) && $urlAccess['forbidden'] === 0) {
                // Gesperrte Bilder bewusst NICHT hier einsortieren: sie bleiben in "links",
                // damit sie weiterhin nur über den bestehenden Rechtehinweis sichtbar werden
                // (keine Platzhalter-Kacheln in der Slideshow).
                $imageItem = $urlAccess;
                $imageItem['thumbnail'] = $thumbnails[$i] ?? null;
                $resultValue['images'][] = $imageItem;
            } else {
                $resultValue['links'][] = $urlAccess;
            }

            $i++;
        }

        return $resultValue;
    }

    /**
     * Determine which kind of Video.js-Player entry (if any) a given file extension maps to.
     *
     * @return string|null "audio", "video", "playlist" or null if the extension is not playable.
     */
    protected function mediaTypeForExtension(?string $normalizedExt): ?string
    {
        if ($normalizedExt === null || $normalizedExt === '') {
            return null;
        }

        if (in_array($normalizedExt, static::PLAYLIST_EXTENSIONS, true)) {
            return 'playlist';
        }

        if (in_array($normalizedExt, static::AUDIO_EXTENSIONS, true)) {
            return 'audio';
        }

        if (in_array($normalizedExt, static::VIDEO_EXTENSIONS, true)) {
            return 'video';
        }

        return null;
    }

    /**
     * Loads an M3U/M3U8 playlist file and parses it into a plain track list.
     * Returns an empty array if the playlist cannot be retrieved.
     *
     * @return array<int, array{url: string, title: string, duration: ?float}>
     */
    protected function fetchPlaylistTracks(string $url): array
    {
        if (!$this->isSafeRemoteUrl($url)) {
            return [];
        }

        $cacheKey = hash('sha256', $url);
        if (isset(self::$playlistCache[$cacheKey]) && self::$playlistCache[$cacheKey]['expires'] > time()) {
            return self::$playlistCache[$cacheKey]['tracks'];
        }

        $content = $this->fetchUrlContent($url);
        if ($content === false || $content === '') {
            return [];
        }

        $tracks = GeneralUtility::makeInstance(M3uPlaylistParser::class)->parse($content, $url);
        self::$playlistCache[$cacheKey] = [
            'expires' => time() + self::PLAYLIST_CACHE_TTL,
            'tracks' => $tracks,
        ];
        if (count(self::$playlistCache) > self::PLAYLIST_CACHE_LIMIT) {
            array_shift(self::$playlistCache);
        }

        return $tracks;
    }

    /**
     * Wrapper around GeneralUtility::getUrl(), extracted for testability.
     *
     * @return string|false
     */
    protected function fetchUrlContent(string $url)
    {
        try {
            $response = GeneralUtility::makeInstance(RequestFactory::class)->request($url, 'GET', [
                'timeout' => 5,
                'allow_redirects' => false,
            ]);
        } catch (\Throwable $exception) {
            return false;
        }

        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300
            ? $response->getBody()->getContents()
            : false;
    }

    protected function isSafeRemoteUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return false;
        }

        $host = $parts['host'] ?? '';
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            $addresses = gethostbynamel($host);
            if ($addresses === false || $addresses === []) {
                return false;
            }
            foreach ($addresses as $address) {
                if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    return false;
                }
            }
        }

        return true;
    }
}
