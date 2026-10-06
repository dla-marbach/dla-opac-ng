<?php

namespace Dla\Find\Tests\Unit\ViewHelpers\Find;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Dla\Find\Tests\Unit\ViewHelpers\ViewHelperTestTrait;
use Dla\Find\ViewHelpers\Find\HighlightFieldViewHelper;
use Solarium\Component\Result\Highlighting\Highlighting;
use Solarium\Component\Result\Highlighting\Result as HighlightingResult;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result;

/**
 * Test for HighlightField ViewHelper.
 */
class HighlightFieldViewHelperTest extends UnitTestCase
{
    use ViewHelperTestTrait;
    /**
     * @var HighlightFieldViewHelper
     */
    public $fixture;

    protected $solariumClient;

    protected $solariumResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = $this->getAccessibleMock(HighlightFieldViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->fixture);
    }

    /**
     * @test
     */
    public function fieldWithoutHighlightingIsEscaped()
    {
        $this->setArgumentsWithDefaults($this->fixture, [
            'results' => $this->createMock(Result::class),
            'document' => new Document(['id' => '1', 'title' => 'a & b']),
            'field' => 'title',
        ]);

        self::assertSame('a &amp; b', $this->fixture->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function fieldIsCorrectlyHighlighted()
    {
        $results = $this->createMock(Result::class);
        $results->method('getHighlighting')->willReturn(new Highlighting([
            '1' => new HighlightingResult(['title' => ['Hello \ueeeeworld\ueeef']]),
        ]));
        $this->setArgumentsWithDefaults($this->fixture, [
            'results' => $results,
            'document' => new Document(['id' => '1', 'title' => 'Hello world']),
            'field' => 'title',
        ]);

        self::assertSame('Hello <em class="highlight">world</em>', $this->fixture->initializeArgumentsAndRender());
    }
}
