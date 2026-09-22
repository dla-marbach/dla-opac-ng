<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service;

use Dla\DlaOpacNg\Service\M3uPlaylistParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class M3uPlaylistParserTest extends UnitTestCase
{
    /**
     * @test
     */
    public function parsesExtendedM3uWithTitleAndDuration(): void
    {
        $content = <<<M3U
        #EXTM3U
        #EXTINF:123,Artist - Title One
        https://example.org/media/track1.mp3
        #EXTINF:-1,Title Two
        https://example.org/media/track2.mp3
        M3U;

        $tracks = (new M3uPlaylistParser())->parse($content);

        self::assertCount(2, $tracks);
        self::assertSame([
            'url' => 'https://example.org/media/track1.mp3',
            'title' => 'Artist - Title One',
            'duration' => 123.0,
        ], $tracks[0]);
        self::assertSame([
            'url' => 'https://example.org/media/track2.mp3',
            'title' => 'Title Two',
            'duration' => null,
        ], $tracks[1]);
    }

    /**
     * @test
     */
    public function parsesPlainM3uWithoutMetadata(): void
    {
        $content = "https://example.org/media/track1.mp3\nhttps://example.org/media/track2.mp3\n";

        $tracks = (new M3uPlaylistParser())->parse($content);

        self::assertCount(2, $tracks);
        self::assertSame('track1', $tracks[0]['title']);
        self::assertNull($tracks[0]['duration']);
        self::assertSame('https://example.org/media/track2.mp3', $tracks[1]['url']);
    }

    /**
     * @test
     */
    public function ignoresBlankLinesAndUnknownDirectives(): void
    {
        $content = <<<M3U
        #EXTM3U

        #EXT-X-VERSION:3
        #EXTINF:42,Only Track
        https://example.org/media/only.mp3

        M3U;

        $tracks = (new M3uPlaylistParser())->parse($content);

        self::assertCount(1, $tracks);
        self::assertSame('Only Track', $tracks[0]['title']);
        self::assertSame(42.0, $tracks[0]['duration']);
    }

    /**
     * @test
     */
    public function returnsEmptyArrayForEmptyContent(): void
    {
        self::assertSame([], (new M3uPlaylistParser())->parse(''));
    }
}
