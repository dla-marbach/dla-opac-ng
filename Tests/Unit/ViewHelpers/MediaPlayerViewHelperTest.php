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
    /** @var array<string, string|false> */
    private array $previousEnv = [];

    /** @var array<string, mixed> */
    private array $previousServer = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['campusRanges', 'sandboxRanges', 'staffRanges'] as $envName) {
            $this->previousEnv[$envName] = getenv($envName);
        }
        foreach (['REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR'] as $serverKey) {
            $this->previousServer[$serverKey] = $_SERVER[$serverKey] ?? null;
        }
        $this->setEnvironmentVariable('campusRanges', '10.0.0.0/8');
        $this->setEnvironmentVariable('sandboxRanges', '192.168.0.0/16');
        $this->setEnvironmentVariable('staffRanges', '172.16.0.0/12');
        $_SERVER['REMOTE_ADDR'] = '203.0.113.1';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnv as $envName => $value) {
            $this->restoreEnvironmentVariable($envName, $value);
        }
        foreach ($this->previousServer as $serverKey => $value) {
            if ($value === null) {
                unset($_SERVER[$serverKey]);
                continue;
            }
            $_SERVER[$serverKey] = $value;
        }

        parent::tearDown();
    }

    private function restoreEnvironmentVariable(string $envName, string|false $value): void
    {
        if ($value === false) {
            putenv($envName);
            unset($_ENV[$envName], $_SERVER[$envName]);
            return;
        }

        putenv($envName . '=' . $value);
        $_ENV[$envName] = $value;
        $_SERVER[$envName] = $value;
    }

    private function setEnvironmentVariable(string $envName, string $value): void
    {
        putenv($envName . '=' . $value);
        $_ENV[$envName] = $value;
        $_SERVER[$envName] = $value;
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
    public function forbiddenVideoFileStaysInLinksAndIsNotExposedAsMediaplayerEntry(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/movie.mp4'],
            ['mp4'],
            ['campus'],
            ['Ein Film'],
            []
        );

        self::assertCount(0, $result['mediaplayer']);
        self::assertCount(1, $result['links']);
        self::assertSame(1, $result['links'][0]['forbidden']);
    }

    /**
     * @test
     */
    public function singleForwardedForAddressMatchingCampusRangeAllowsCampusMedia(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.23.45.67';
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/movie.mp4'],
            ['mp4'],
            ['campus'],
            ['Ein Film'],
            []
        );

        self::assertCount(1, $result['mediaplayer']);
        self::assertSame(0, $result['mediaplayer'][0]['forbidden']);
    }

    /**
     * @test
     */
    public function sandboxClientMayAccessSandboxAndCampusMedia(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.55.201';
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/sandbox.pdf', 'https://example.org/campus.pdf'],
            ['pdf', 'pdf'],
            ['sandbox', 'campus'],
            ['Sandbox', 'Campus'],
            []
        );

        self::assertCount(2, $result['links']);
        self::assertSame(0, $result['links'][0]['forbidden']);
        self::assertSame(0, $result['links'][1]['forbidden']);
    }

    /**
     * @test
     */
    public function campusClientMayNotAccessSandboxMedia(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.23.45.67';
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/sandbox.pdf'],
            ['pdf'],
            ['sandbox'],
            ['Sandbox'],
            []
        );

        self::assertSame(1, $result['links'][0]['forbidden']);
    }

    /**
     * @test
     */
    public function rangesWithQuotesAndWhitespaceAreRecognized(): void
    {
        $this->setEnvironmentVariable('sandboxRanges', '"192.168.55.200/29, 192.168.55.208/28"');
        $_SERVER['REMOTE_ADDR'] = '192.168.55.210';
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/sandbox.pdf'],
            ['pdf'],
            ['sandbox'],
            ['Sandbox'],
            []
        );

        self::assertSame(0, $result['links'][0]['forbidden']);
    }

    /**
     * @test
     */
    public function firstForwardedForAddressIsUsedWhenMultipleAddressesArePresent(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.23.45.67, 172.16.1.5';
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/movie.mp4'],
            ['mp4'],
            ['campus'],
            ['Ein Film'],
            []
        );

        self::assertCount(1, $result['mediaplayer']);
        self::assertSame(0, $result['mediaplayer'][0]['forbidden']);
    }

    /**
     * @test
     */
    public function remoteAddrIsUsedWhenForwardedForHeaderIsEmpty(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.23.45.67';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '   ';
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/movie.mp4'],
            ['mp4'],
            ['campus'],
            ['Ein Film'],
            []
        );

        self::assertCount(1, $result['mediaplayer']);
        self::assertSame(0, $result['mediaplayer'][0]['forbidden']);
    }

    /**
     * @test
     */
    public function remoteAddrIsUsedWhenForwardedForHeaderIsAbsent(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.23.45.67';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/movie.mp4'],
            ['mp4'],
            ['campus'],
            ['Ein Film'],
            []
        );

        self::assertCount(1, $result['mediaplayer']);
        self::assertSame(0, $result['mediaplayer'][0]['forbidden']);
    }

    /**
     * @test
     */
    public function forwardedForHeaderIsIgnoredForDirectPublicRequests(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.23.45.67';
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/movie.mp4'],
            ['mp4'],
            ['campus'],
            ['Ein Film'],
            []
        );

        self::assertCount(0, $result['mediaplayer']);
        self::assertCount(1, $result['links']);
        self::assertSame(1, $result['links'][0]['forbidden']);
    }

    /**
     * @test
     */
    public function invalidForwardedForEntryFallsBackToRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'unknown, 10.23.45.68';
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/movie.mp4'],
            ['mp4'],
            ['campus'],
            ['Ein Film'],
            []
        );

        self::assertCount(0, $result['mediaplayer']);
        self::assertCount(1, $result['links']);
        self::assertSame(1, $result['links'][0]['forbidden']);
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
        // öffentliche IP-Adresse statt Hostname: isSafeRemoteUrl() braucht dann keine DNS-Abfrage
        $viewHelper->playlistContentByUrl['https://93.184.215.14/media/playlist.m3u'] = implode("\n", [
            '#EXTM3U',
            '#EXTINF:10,Track A',
            'https://example.org/media/a.mp3',
            '#EXTINF:20,Track B',
            'tracks/b.mp3',
        ]);

        $result = $viewHelper->buildMediaData(
            ['https://93.184.215.14/media/playlist.m3u'],
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
        self::assertSame('https://93.184.215.14/media/tracks/b.mp3', $mediaItem['tracks'][1]['url']);
    }

    /**
     * @test
     */
    public function playlistTracksAreNumberedAndDurationsFormatted(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();
        $viewHelper->playlistContentByUrl['https://93.184.215.14/media/numbered.m3u'] = implode("\n", [
            '#EXTM3U',
            'https://example.org/media/ohne-metadaten.mp3',
            '#EXTINF:185,Interpret - Titel',
            'https://example.org/media/b.mp3',
            '#EXTINF:4860,Lesung',
            'https://example.org/media/c.mp3',
        ]);

        $result = $viewHelper->buildMediaData(
            ['https://93.184.215.14/media/numbered.m3u'],
            ['m3u'],
            ['public'],
            ['Playlist'],
            []
        );

        $mediaItem = $result['mediaplayer'][0];
        self::assertSame(['01', '02', '03'], array_column($mediaItem['tracks'], 'number'));
        self::assertSame([null, '3:05', '1:21:00'], array_column($mediaItem['tracks'], 'durationFormatted'));
        self::assertSame('Interpret', $mediaItem['tracks'][1]['artist']);
        self::assertTrue($mediaItem['hasArtists']);
        self::assertTrue($mediaItem['hasDurations']);
    }

    /**
     * @test
     */
    public function playlistWithoutMetadataHasNoArtistAndDurationColumns(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();
        $viewHelper->playlistContentByUrl['https://93.184.215.14/media/plain.m3u'] = "https://example.org/media/a.mp3\nhttps://example.org/media/b.mp3";

        $result = $viewHelper->buildMediaData(
            ['https://93.184.215.14/media/plain.m3u'],
            ['m3u'],
            ['public'],
            ['Playlist'],
            []
        );

        self::assertFalse($result['mediaplayer'][0]['hasArtists']);
        self::assertFalse($result['mediaplayer'][0]['hasDurations']);
    }

    /**
     * @test
     */
    public function videoItemCarriesMatchingThumbnailAsPoster(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/movie.mp4', '␣'],
            ['mp4', '␣'],
            ['public', '␣'],
            ['Videodatei: Zugang', 'movie.mp4'],
            [],
            ['https://example.org/media/movie-thumbnail.png', '␣']
        );

        self::assertSame('https://example.org/media/movie-thumbnail.png', $result['mediaplayer'][0]['thumbnail']);
    }

    /**
     * @test
     */
    public function teaserIsFirstAccessibleThumbnailWhenFirstObjectIsForbidden(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/a.jpg', 'https://example.org/media/b.jpg', 'https://example.org/media/c.jpg'],
            ['jpg', 'jpg', 'jpg'],
            ['campus', 'public', 'public'],
            ['Bild A', 'Bild B', 'Bild C'],
            [],
            ['https://example.org/media/a_thumb.jpg', 'https://example.org/media/b_thumb.jpg', 'https://example.org/media/c_thumb.jpg']
        );

        self::assertSame('https://example.org/media/b_thumb.jpg', $result['teaser']['thumbnail']);
        self::assertTrue($result['teaser']['jump']);
        self::assertFalse($result['accessDenied']);
    }

    /**
     * @test
     */
    public function teaserOfCampusObjectIsShownInsideCampusRange(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.1.2.3';
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/a.mp3', 'https://example.org/media/b.mp3'],
            ['mp3', 'mp3'],
            ['campus', 'public'],
            ['Vorderseite', 'Rückseite'],
            [],
            ['https://example.org/media/a_thumb.png', 'https://example.org/media/b_thumb.png']
        );

        self::assertSame('https://example.org/media/a_thumb.png', $result['teaser']['thumbnail']);
    }

    /**
     * @test
     */
    public function fileWithThumbnailIsExposedAsImagesEntryAndTeaserJumps(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/book.epub'],
            ['epub'],
            ['public'],
            ['Elektronische Ressource: Zugang'],
            [],
            ['https://example.org/media/book-thumbnail.png']
        );

        self::assertCount(0, $result['links']);
        self::assertCount(1, $result['images']);
        self::assertSame('file', $result['images'][0]['kind']);
        self::assertSame('https://example.org/media/book.epub', $result['images'][0]['url']);
        self::assertSame('https://example.org/media/book-thumbnail.png', $result['images'][0]['thumbnail']);
        self::assertSame('https://example.org/media/book.epub', $result['teaser']['url']);
        self::assertTrue($result['teaser']['jump']);
        self::assertSame([['type' => 'link', 'count' => 1]], $result['summary']);
    }

    /**
     * @test
     */
    public function viewerLinkWithoutExtensionButWithThumbnailIsExposedAsViewerImage(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        // wie bei Handschriften: Digitalisat im Viewer, ohne Dateiendung
        $result = $viewHelper->buildMediaData(
            ['https://digital.example.org/viewer/fullscreen/HS00242238/?media_access=public'],
            [],
            ['public'],
            ['HS00242238'],
            [],
            ['https://digital.example.org/viewer/api/v1/records/HS00242238/files/images/HS_00242238_0001.tif/full/!480,480/0/default.jpg']
        );

        self::assertCount(0, $result['links']);
        self::assertCount(1, $result['images']);
        self::assertSame('viewer', $result['images'][0]['kind']);
        self::assertSame('HS00242238', $result['images'][0]['display']);
        self::assertTrue($result['teaser']['jump']);
        self::assertSame([['type' => 'digitalisat', 'count' => 1]], $result['summary']);
    }

    /**
     * @test
     */
    public function objectWithoutThumbnailAndImageExtensionStaysInLinks(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://digital.example.org/viewer/image/AU01067549/', 'https://example.org/media/document.pdf'],
            ['␣', 'pdf'],
            ['public', 'public'],
            ['Digitalisat: Zugang', 'Inhaltsverzeichnis'],
            [],
            ['␣', '␣']
        );

        self::assertCount(0, $result['images']);
        self::assertCount(2, $result['links']);
        self::assertNull($result['teaser']);
    }

    /**
     * @test
     */
    public function forbiddenViewerLinkWithThumbnailStaysInLinks(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://digital.example.org/viewer/fullscreen/HS00242238/?media_access=campus'],
            [],
            ['campus'],
            ['HS00242238'],
            [],
            ['https://digital.example.org/viewer/api/v1/records/HS00242238/files/images/HS_00242238_0001.tif/full/!480,480/0/default.jpg']
        );

        self::assertCount(0, $result['images']);
        self::assertCount(1, $result['links']);
        self::assertSame(1, $result['links'][0]['forbidden']);
        self::assertNull($result['teaser']);
        self::assertTrue($result['accessDenied']);
    }

    /**
     * @test
     */
    public function imagesAndViewerLinksShareOneSlideshowInOriginalOrder(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            ['https://example.org/media/a.jpg', 'https://digital.example.org/viewer/fullscreen/HS01/', 'https://example.org/media/b.jpg'],
            ['jpg', '␣', 'jpg'],
            ['public', 'public', 'public'],
            ['Bild A', 'HS01', 'Bild B'],
            [],
            ['https://example.org/media/a_thumb.jpg', 'https://digital.example.org/viewer/api/HS01.jpg', 'https://example.org/media/b_thumb.jpg']
        );

        self::assertCount(0, $result['links']);
        self::assertSame(['image', 'viewer', 'image'], array_column($result['images'], 'kind'));
        self::assertSame([
            ['type' => 'image', 'count' => 2],
            ['type' => 'digitalisat', 'count' => 1],
        ], $result['summary']);
    }

    /**
     * @test
     */
    public function accessDeniedOnlyIfNoDigitalObjectIsAccessible(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $allForbidden = $viewHelper->buildMediaData(
            ['https://example.org/media/a.mp3', '␣'],
            ['mp3', '␣'],
            ['campus', '␣'],
            ['Audiodatei', 'a.wav'],
            [],
            ['https://example.org/media/a_thumb.png', '␣']
        );
        $partlyForbidden = $viewHelper->buildMediaData(
            ['https://example.org/media/a.mp3', 'https://example.org/media/b.pdf'],
            ['mp3', 'pdf'],
            ['campus', 'public'],
            ['Audiodatei', 'Begleitheft'],
            [],
            []
        );

        self::assertTrue($allForbidden['accessDenied']);
        self::assertNull($allForbidden['teaser']);
        self::assertFalse($partlyForbidden['accessDenied']);
        self::assertNull($partlyForbidden['teaser']);
    }

    /**
     * @test
     */
    public function forbiddenM3uPlaylistIsNotFetchedAndStaysInLinks(): void
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

        self::assertCount(0, $result['mediaplayer']);
        self::assertCount(1, $result['links']);
        self::assertSame(1, $result['links'][0]['forbidden']);
        self::assertArrayNotHasKey('tracks', $result['links'][0]);
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
        self::assertSame('image', $imageItem['kind']);
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

    /**
     * @test
     */
    public function summaryCountsAccessibleObjectsPerKindInSectionOrder(): void
    {
        $viewHelper = new TestableMediaPlayerViewHelper();

        $result = $viewHelper->buildMediaData(
            [
                'https://example.org/media/a.jpg',
                'https://example.org/media/heft.pdf',
                'https://example.org/media/b.jpg',
                'https://example.org/media/a.mp3',
                'https://example.org/media/b.mp3',
                'https://example.org/media/film.mp4',
                'https://example.org/media/gesperrt.mp3',
                'https://example.org/media/gesperrt.pdf',
                '␣',
            ],
            ['jpg', 'pdf', 'jpg', 'mp3', 'mp3', 'mp4', 'mp3', 'pdf', '␣'],
            ['public', 'public', 'public', 'public', 'public', 'public', 'campus', 'campus', '␣'],
            [],
            []
        );

        self::assertSame([
            ['type' => 'video', 'count' => 1],
            ['type' => 'audio', 'count' => 2],
            ['type' => 'image', 'count' => 2],
            ['type' => 'link', 'count' => 1],
        ], $result['summary']);
    }
}
