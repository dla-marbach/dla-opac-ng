<?php

namespace Dla\DlaOpacNg\Tests\Unit\ViewHelpers;

use Dla\DlaOpacNg\ViewHelpers\MediaPlayerViewHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test-Double, das den echten HTTP-Aufruf einer Playlist-Datei durch einen
 * fest hinterlegten Inhalt ersetzt, damit die Tests ohne Netzwerkzugriff laufen.
 */
class TestableMediaPlayerViewHelper extends MediaPlayerViewHelper
{
    /** @var array<string, string|false> */
    public array $playlistContentByUrl = [];

    protected function fetchUrlContent(string $url)
    {
        return $this->playlistContentByUrl[$url] ?? false;
    }
}

class MediaPlayerViewHelperTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('campusRanges=10.0.0.0/8');
        putenv('sandboxRanges=192.168.0.0/16');
        putenv('staffRanges=172.16.0.0/12');
        $_SERVER['REMOTE_ADDR'] = '203.0.113.1';
    }

    /**
     * @test
     */
    public function publicAudioFileIsExposedAsMediaplayerEntry(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/track.mp3'],
            ['mp3'],
            ['public'],
            ['Ein Hörspiel'],
            []
        );

        self::assertCount(0, $result['links']);

        self::assertCount(1, $result['mediaplayer']);
        $mediaItem = $result['mediaplayer'][0];
        self::assertSame('audio', $mediaItem['type']);
        self::assertSame('https://example.org/media/track.mp3', $mediaItem['url']);
        self::assertSame(0, $mediaItem['forbidden']);
    }

    /**
     * @test
     */
    public function forbiddenVideoFileIsListedButNotPlayable(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/movie.mp4'],
            ['mp4'],
            ['campus'],
            ['Ein Film'],
            []
        );

        self::assertCount(0, $result['links']);
        self::assertCount(1, $result['mediaplayer']);
        self::assertSame(1, $result['mediaplayer'][0]['forbidden']);
        self::assertArrayNotHasKey('tracks', $result['mediaplayer'][0]);
    }

    /**
     * @test
     */
    public function nonMediaExtensionIsOnlyExposedAsLink(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/document.pdf'],
            ['pdf'],
            ['public'],
            ['Ein Dokument'],
            []
        );

        self::assertCount(1, $result['links']);
        self::assertCount(0, $result['mediaplayer']);
    }

    /**
     * @test
     */
    public function chaptersUrlIsAttachedToMatchingMediaItem(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/talk.mp4'],
            ['mp4'],
            ['public'],
            ['Ein Vortrag'],
            ['https://example.org/media/talk.vtt']
        );

        self::assertSame('https://example.org/media/talk.vtt', $result['mediaplayer'][0]['chapters']);
    }

    /**
     * @test
     */
    public function publicM3uPlaylistIsResolvedIntoTracks(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();
        $viewHelper->playlistContentByUrl['https://example.org/media/playlist.m3u'] = implode("\n", [
            '#EXTM3U',
            '#EXTINF:10,Track A',
            'https://example.org/media/a.mp3',
            '#EXTINF:20,Track B',
            'tracks/b.mp3',
        ]);

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/playlist.m3u'],
            ['m3u'],
            ['public'],
            ['Playlist'],
            []
        );

        self::assertCount(1, $result['mediaplayer']);
        $mediaItem = $result['mediaplayer'][0];
        self::assertSame('playlist', $mediaItem['type']);
        self::assertCount(2, $mediaItem['tracks']);
        self::assertSame('Track A', $mediaItem['tracks'][0]['title']);
        self::assertSame(10.0, $mediaItem['tracks'][0]['duration']);
        self::assertSame('https://example.org/media/tracks/b.mp3', $mediaItem['tracks'][1]['url']);
    }

    /**
     * @test
     */
    public function forbiddenM3uPlaylistIsNotFetched(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();
        // no entry in playlistContentByUrl -> fetchUrlContent() would return false anyway,
        // but forbidden entries must not even attempt to resolve tracks.

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/playlist.m3u'],
            ['m3u'],
            ['campus'],
            ['Playlist'],
            []
        );

        self::assertSame(1, $result['mediaplayer'][0]['forbidden']);
        self::assertArrayNotHasKey('tracks', $result['mediaplayer'][0]);
    }

    /**
     * @test
     */
    public function publicImageIsExposedAsImagesEntryWithThumbnail(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/picture.jpg'],
            ['jpg'],
            ['public'],
            ['Ein Bild'],
            [],
            ['https://example.org/media/picture_thumb.jpg']
        );

        self::assertCount(0, $result['links']);
        self::assertCount(0, $result['mediaplayer']);
        self::assertCount(1, $result['images']);
        $imageItem = $result['images'][0];
        self::assertSame('https://example.org/media/picture.jpg', $imageItem['url']);
        self::assertSame('https://example.org/media/picture_thumb.jpg', $imageItem['thumbnail']);
        self::assertSame(0, $imageItem['forbidden']);
    }

    /**
     * @test
     */
    public function forbiddenImageStaysInLinksAndIsNotExposedAsImagesEntry(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/picture.jpg'],
            ['jpg'],
            ['campus'],
            ['Ein Bild'],
            [],
            ['https://example.org/media/picture_thumb.jpg']
        );

        self::assertCount(0, $result['images']);
        self::assertCount(0, $result['mediaplayer']);
        self::assertCount(1, $result['links']);
        self::assertSame(1, $result['links'][0]['forbidden']);
    }

    /**
     * @test
     */
    public function multipleImagesArePairedWithMatchingThumbnailsByIndex(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/a.jpg', 'https://example.org/media/b.jpg'],
            ['jpg', 'jpg'],
            ['public', 'public'],
            ['Bild A', 'Bild B'],
            [],
            ['https://example.org/media/a_thumb.jpg', 'https://example.org/media/b_thumb.jpg']
        );

        self::assertCount(2, $result['images']);
        self::assertSame('https://example.org/media/a_thumb.jpg', $result['images'][0]['thumbnail']);
        self::assertSame('https://example.org/media/b_thumb.jpg', $result['images'][1]['thumbnail']);
    }
}
