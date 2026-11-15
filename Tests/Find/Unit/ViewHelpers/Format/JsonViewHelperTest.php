<?php

namespace Dla\Find\Tests\Unit\ViewHelpers\Format;

use Dla\Find\Tests\Unit\ViewHelpers\ViewHelperTestTrait;
use Dla\Find\ViewHelpers\Format\JsonViewHelper;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class JsonViewHelperTest extends UnitTestCase
{
    use ViewHelperTestTrait;

    private function render(array $arguments, mixed $children = null): string
    {
        $viewHelper = new JsonViewHelper();
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setRenderChildrenClosure(static fn () => $children);
        $this->setArgumentsWithDefaults($viewHelper, $arguments);

        // E_USER_DEPRECATED des ViewHelpers ("Please use f:format.json instead") ist hier nicht von Belang
        return (string)@$viewHelper->initializeArgumentsAndRender();
    }

    #[Test]
    public function dataArgumentIsEncodedWithEscapedTags(): void
    {
        $json = $this->render(['data' => ['Goethe', '<Schiller>']]);

        self::assertSame(['Goethe', '<Schiller>'], json_decode($json, true));
        self::assertStringNotContainsString('<', $json);
    }

    #[Test]
    public function childrenAreEncodedWithoutDataArgument(): void
    {
        self::assertSame('{"a":1}', $this->render([], ['a' => 1]));
    }
}
