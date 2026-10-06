<?php

namespace Dla\Find\Tests\Unit\ViewHelpers\LinkedData;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
 *      Goettingen State Library
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
use Dla\Find\ViewHelpers\LinkedData\ItemViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;

/**
 * Tests for the item viewhelper.
 */
class ItemViewHelperTest extends UnitTestCase
{
    use ViewHelperTestTrait;

    /**
     * @var ItemViewHelper
     */
    protected $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture = $this->getMockBuilder(ItemViewHelper::class)
            ->onlyMethods([])
            ->getMock();
    }

    /**
     * @test
     */
    public function itemsAreAddedToContainer(): void
    {
        $variableProvider = new StandardVariableProvider(['linkedDataContainer' => []]);
        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $renderingContext->method('getVariableProvider')->willReturn($variableProvider);
        $this->fixture->setRenderingContext($renderingContext);

        $this->setArgumentsWithDefaults($this->fixture, [
            'subject' => 'hrdr',
            'predicate' => 'is',
            'object' => 'thirsty',
        ]);
        $this->fixture->initializeArgumentsAndRender();

        self::assertSame(['hrdr' => ['is' => ['thirsty' => null]]], $variableProvider->get('linkedDataContainer'));
    }
}
