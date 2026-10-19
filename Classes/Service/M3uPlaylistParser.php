<?php

namespace Dla\DlaOpacNg\Service;

/**
 * Parses (Extended) M3U/M3U8 playlist files into a plain array structure that
 * can be handed over to the Video.js-based player templates.
 *
 * Supported format (see https://en.wikipedia.org/wiki/M3U):
 *
 *   #EXTM3U
 *   #EXTINF:123,Artist - Title
 *   https://example.org/track1.mp3
 *   #EXTINF:-1,Another Title
 *   https://example.org/track2.mp3
 *
 * The "#EXTINF" title is split into "artist" and "title" at the first " - "
 * (format "Artist - Title"); without separator "artist" is null.
 *
 * Plain M3U files without "#EXTINF" metadata (just a list of URLs, one per
 * line) are supported as well; in that case "title" falls back to the file
 * name and "artist" and "duration" are null.
 *
 * A leading UTF-8 byte order mark (BOM), as written by some text editors when
 * saving as "UTF-8 mit BOM", is ignored.
 */
class M3uPlaylistParser
{
    /**
     * @return array<int, array{url: string, artist: ?string, title: string, duration: ?float}>
     */
    public function parse(string $content, ?string $playlistUrl = null): array
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $lines = preg_split('/\R/', $content) ?: [];
        $tracks = [];
        $pendingTitle = null;
        $pendingDuration = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if ($line === '#EXTM3U') {
                continue;
            }

            if (str_starts_with($line, '#EXTINF:')) {
                [$duration, $title] = $this->parseExtinf(substr($line, strlen('#EXTINF:')));
                $pendingDuration = $duration;
                $pendingTitle = $title;
                continue;
            }

            if (str_starts_with($line, '#')) {
                // unknown/unsupported directive, ignore
                continue;
            }

            [$artist, $title] = $this->splitArtistAndTitle($pendingTitle);
            $tracks[] = [
                'url' => $playlistUrl === null ? $line : $this->resolveUrl($line, $playlistUrl),
                'artist' => $artist,
                'title' => $title !== null ? $title : $this->titleFromUrl($line),
                'duration' => $pendingDuration,
            ];

            $pendingTitle = null;
            $pendingDuration = null;
        }

        return $tracks;
    }

    /**
     * @return array{0: ?float, 1: ?string}
     */
    private function parseExtinf(string $info): array
    {
        $parts = explode(',', $info, 2);
        $durationValue = trim($parts[0]);
        $title = isset($parts[1]) ? trim($parts[1]) : null;

        $duration = is_numeric($durationValue) ? (float)$durationValue : null;
        if ($duration !== null && $duration < 0) {
            // "-1" is used by the spec to signal "duration unknown"
            $duration = null;
        }

        return [$duration, $title];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function splitArtistAndTitle(?string $extinfTitle): array
    {
        if ($extinfTitle === null || $extinfTitle === '') {
            return [null, null];
        }

        $parts = explode(' - ', $extinfTitle, 2);
        if (count($parts) === 2 && trim($parts[0]) !== '' && trim($parts[1]) !== '') {
            return [trim($parts[0]), trim($parts[1])];
        }

        return [null, $extinfTitle];
    }

    private function titleFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        return pathinfo($path, PATHINFO_FILENAME);
    }

    private function resolveUrl(string $url, string $playlistUrl): string
    {
        if (parse_url($url, PHP_URL_SCHEME) !== null) {
            return $url;
        }

        $playlistParts = parse_url($playlistUrl);
        if (!is_array($playlistParts) || empty($playlistParts['scheme']) || empty($playlistParts['host'])) {
            return $url;
        }

        $origin = $playlistParts['scheme'] . '://' . $playlistParts['host'];
        if (isset($playlistParts['port'])) {
            $origin .= ':' . $playlistParts['port'];
        }

        if (str_starts_with($url, '//')) {
            return $playlistParts['scheme'] . ':' . $url;
        }
        if (str_starts_with($url, '/')) {
            return $origin . $url;
        }

        $path = $playlistParts['path'] ?? '/';
        $directory = rtrim(str_replace('\\', '/', dirname($path)), '/');

        return $origin . ($directory === '' ? '' : $directory) . '/' . $url;
    }
}
