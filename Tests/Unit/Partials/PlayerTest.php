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
    public function rendersVideoElementWithSourceChaptersAndPoster(): void
    {
        $html = $this->renderPartial('MediaAccess/Player', [
            'id' => 'player-1',
            'item' => [
                'url' => 'https://example.org/media/talk.mp4',
                'type' => 'video',
                'chapters' => 'https://example.org/media/talk.vtt',
                'thumbnail' => 'https://example.org/media/talk-thumbnail.png',
            ],
        ]);

        self::assertStringContainsString('<video', $html);
        self::assertStringContainsString('id="player-1"', $html);
        self::assertStringContainsString('video-js', $html);
        self::assertStringContainsString('controlslist="nodownload"', $html);
        self::assertStringContainsString('src="https://example.org/media/talk.mp4"', $html);
        self::assertStringContainsString('kind="chapters"', $html);
        self::assertStringContainsString('src="https://example.org/media/talk.vtt"', $html);
        self::assertStringContainsString('poster="https://example.org/media/talk-thumbnail.png"', $html);
        self::assertStringContainsString('data-mediaplayer-type="video"', $html);
        self::assertStringNotContainsString('{id}', $html);
        // Initialisierung erfolgt durch Resources/Public/JavaScript/dla-mediaplayer.js
        self::assertStringNotContainsString('<script', $html);
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
        self::assertStringContainsString('data-mediaplayer-type="audio"', $html);
        self::assertStringNotContainsString('dla-mediaplayer-caption', $html);
        self::assertStringNotContainsString('{id}', $html);
    }

    /**
     * @test
     */
    public function rendersCaptionWithDisplayLabelAndFileExtensionForAudio(): void
    {
        $html = $this->renderPartial('MediaAccess/Player', [
            'id' => 'player-4',
            'item' => [
                'url' => 'https://example.org/media/track.mp3',
                'type' => 'audio',
                'display' => 'Audiodatei (Rückseite): Zugang',
                'ext' => 'mp3',
            ],
        ]);

        self::assertStringContainsString('<span class="dla-mediaplayer-caption-title">Audiodatei (Rückseite): Zugang</span>', $html);
        self::assertStringContainsString('<span class="dla-mediaplayer-caption-ext">(mp3)</span>', $html);
    }

    /**
     * @test
     */
    public function omitsCaptionForPlaceholderDisplayLabel(): void
    {
        $html = $this->renderPartial('MediaAccess/Player', [
            'id' => 'player-5',
            'item' => [
                'url' => 'https://example.org/media/track.mp3',
                'type' => 'audio',
                'display' => '␣',
                'ext' => 'mp3',
            ],
        ]);

        self::assertStringNotContainsString('dla-mediaplayer-caption', $html);
    }

    /**
     * @test
     */
    public function rendersPlaylistAsNumberedTracklistWithHeading(): void
    {
        $html = $this->renderPartial('MediaAccess/Player', [
            'id' => 'player-3',
            'item' => [
                'url' => 'https://example.org/media/playlist.m3u',
                'type' => 'playlist',
                'display' => 'Lesungen 2013',
                'ext' => 'm3u',
                'chapters' => null,
                'hasArtists' => true,
                'hasDurations' => true,
                'tracks' => [
                    ['url' => 'https://example.org/media/a.mp3', 'artist' => 'Interpret A', 'title' => 'Track A', 'duration' => 10.0, 'number' => '01', 'durationFormatted' => '0:10'],
                    ['url' => 'https://example.org/media/b.mp3', 'artist' => null, 'title' => 'Track B', 'duration' => null, 'number' => '02', 'durationFormatted' => null],
                ],
            ],
        ]);

        self::assertStringContainsString('<span class="dla-mediaplayer-caption-title">Lesungen 2013</span>', $html);
        self::assertStringNotContainsString('(m3u)', $html);
        self::assertStringContainsString('class="video-js vjs-default-skin vjs-big-play-centered dla-mediaplayer dla-mediaplayer-audio"', $html);
        self::assertStringContainsString('<table class="dla-mediaplayer-tracklist">', $html);
        self::assertSame(2, substr_count($html, '<tr class="dla-mediaplayer-track'));
        self::assertStringContainsString('<tr class="dla-mediaplayer-track active" data-src="https://example.org/media/a.mp3">', $html);
        self::assertStringContainsString('<td class="dla-mediaplayer-track-number">01</td>', $html);
        self::assertStringContainsString('<td class="dla-mediaplayer-track-number">02</td>', $html);
        self::assertStringContainsString('<td class="dla-mediaplayer-track-artist">Interpret A</td>', $html);
        self::assertStringContainsString('<td class="dla-mediaplayer-track-duration">0:10</td>', $html);
        self::assertStringContainsString('aria-current="true">Track A</button>', $html);
        self::assertStringContainsString('aria-current="false">Track B</button>', $html);
        self::assertStringNotContainsString('{id}', $html);
    }

    /**
     * @test
     */
    public function omitsArtistAndDurationColumnsWithoutPlaylistMetadata(): void
    {
        $html = $this->renderPartial('MediaAccess/Player', [
            'id' => 'player-6',
            'item' => [
                'url' => 'https://example.org/media/playlist.m3u',
                'type' => 'playlist',
                'hasArtists' => false,
                'hasDurations' => false,
                'tracks' => [
                    ['url' => 'https://example.org/media/a.mp3', 'artist' => null, 'title' => 'a', 'duration' => null, 'number' => '01', 'durationFormatted' => null],
                ],
            ],
        ]);

        self::assertStringContainsString('dla-mediaplayer-track-title', $html);
        self::assertStringNotContainsString('dla-mediaplayer-track-artist', $html);
        self::assertStringNotContainsString('dla-mediaplayer-track-duration', $html);
    }
}
