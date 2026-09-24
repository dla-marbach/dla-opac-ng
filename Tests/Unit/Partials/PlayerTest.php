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
            ],
        ]);

        self::assertStringContainsString('<video', $html);
        self::assertStringContainsString('id="player-1"', $html);
        self::assertStringContainsString('video-js', $html);
        self::assertStringContainsString('controlslist="nodownload"', $html);
        self::assertStringContainsString('src="https://example.org/media/talk.mp4"', $html);
        self::assertStringContainsString('kind="chapters"', $html);
        self::assertStringContainsString('src="https://example.org/media/talk.vtt"', $html);
        self::assertStringNotContainsString('{id}', $html);
        self::assertStringContainsString("document.currentScript.parentNode.querySelector('video, audio')", $html);
    }

    /**
     * @test
     */
    public function rendersAudioElementWithoutChapters(): void
    {
        $html = $this->renderPartial('MediaAccess/Player', [
            'id' => 'player-2',
            'item' => [
                'url' => 'https://example.org/media/track.mp3',
                'type' => 'audio',
                'chapters' => null,
            ],
        ]);

        self::assertStringContainsString('<audio', $html);
        self::assertStringContainsString('controlslist="nodownload"', $html);
        self::assertStringNotContainsString('kind="chapters"', $html);
        self::assertStringContainsString('class="video-js vjs-default-skin vjs-big-play-centered dla-mediaplayer dla-mediaplayer-audio"', $html);
        self::assertStringContainsString('videojs(el, {audioOnlyMode: true});', $html);
        self::assertStringNotContainsString('{id}', $html);
        self::assertStringContainsString("document.currentScript.parentNode.querySelector('video, audio')", $html);
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
                'tracks' => [
                    ['url' => 'https://example.org/media/a.mp3', 'title' => 'Track A', 'duration' => 10.0],
                    ['url' => 'https://example.org/media/b.mp3', 'title' => 'Track B', 'duration' => 20.0],
                ],
            ],
        ]);

        self::assertStringContainsString('dla-mediaplayer-playlist', $html);
        self::assertStringContainsString('Track A', $html);
        self::assertStringContainsString('Track B', $html);
        self::assertStringContainsString('class="video-js vjs-default-skin vjs-big-play-centered dla-mediaplayer dla-mediaplayer-audio"', $html);
        self::assertSame(2, substr_count($html, '<li class="dla-mediaplayer-playlist-item'));
        self::assertStringNotContainsString('{id}', $html);
        self::assertStringContainsString("var container = document.currentScript.parentNode;", $html);
        self::assertStringContainsString("var el = container.querySelector('video');", $html);
    }
}
