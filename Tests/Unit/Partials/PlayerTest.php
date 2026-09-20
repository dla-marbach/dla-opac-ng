<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

/**
 * Rendert Resources/Private/Partials/MediaAccess/Player.html für Audio-, Video- und
 * Playlist-Objekte, wie sie von MediaPlayerViewHelper::buildMediaData() erzeugt werden.
 */
class PlayerTest extends FluidPartialTestCase
{
    /**
     * @test
     */
    public function rendersVideoElementWithSourceAndChapters(): void
    {
        $html = $this->renderPartial('MediaAccess/Player', [
            'id' => 'player-1',
            'item' => [
                'url' => 'https://example.org/media/talk.mp4',
                'type' => 'video',
                'chapters' => 'https://example.org/media/talk.vtt',
                'allowDownload' => true,
            ],
        ]);

        self::assertStringContainsString('<video', $html);
        self::assertStringContainsString('id="player-1"', $html);
        self::assertStringContainsString('video-js', $html);
        self::assertStringContainsString('src="https://example.org/media/talk.mp4"', $html);
        self::assertStringContainsString('kind="chapters"', $html);
        self::assertStringContainsString('src="https://example.org/media/talk.vtt"', $html);
        self::assertStringContainsString('dla-mediaplayer-download', $html);
    }

    /**
     * @test
     */
    public function rendersAudioElementWithoutDownloadLinkWhenDisabled(): void
    {
        $html = $this->renderPartial('MediaAccess/Player', [
            'id' => 'player-2',
            'item' => [
                'url' => 'https://example.org/media/track.mp3',
                'type' => 'audio',
                'chapters' => null,
                'allowDownload' => false,
            ],
        ]);

        self::assertStringContainsString('<audio', $html);
        self::assertStringNotContainsString('dla-mediaplayer-download', $html);
        self::assertStringNotContainsString('kind="chapters"', $html);
    }

    /**
     * @test
     */
    public function rendersPlaylistWithTrackList(): void
    {
        $html = $this->renderPartial('MediaAccess/Player', [
            'id' => 'player-3',
            'item' => [
                'url' => 'https://example.org/media/playlist.m3u',
                'type' => 'playlist',
                'chapters' => null,
                'allowDownload' => true,
                'tracks' => [
                    ['url' => 'https://example.org/media/a.mp3', 'title' => 'Track A', 'duration' => 10.0],
                    ['url' => 'https://example.org/media/b.mp3', 'title' => 'Track B', 'duration' => 20.0],
                ],
            ],
        ]);

        self::assertStringContainsString('dla-mediaplayer-playlist', $html);
        self::assertStringContainsString('Track A', $html);
        self::assertStringContainsString('Track B', $html);
        self::assertSame(2, substr_count($html, 'dla-mediaplayer-playlist-item'));
    }
}
