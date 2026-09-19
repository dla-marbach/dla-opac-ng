<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

class ResultCountTest extends FluidPartialTestCase
{
    /**
     * @test
     */
    public function rendersSingleResultCount(): void
    {
        $html = $this->renderPartial('Components/ResultCount', [
            'results' => ['numfound' => 1],
            'error' => false,
            'config' => ['counterStart' => 1, 'counterEnd' => 1],
        ]);

        self::assertStringContainsString('1 result', $html);
    }
}
