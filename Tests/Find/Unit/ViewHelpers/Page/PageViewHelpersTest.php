<?php

namespace Dla\Find\Tests\Unit\ViewHelpers\Page;

use Dla\Find\Tests\Unit\ViewHelpers\ViewHelperTestTrait;
use Dla\Find\ViewHelpers\Page\LinkCSSViewHelper;
use Dla\Find\ViewHelpers\Page\ScriptViewHelper;
use Dla\Find\ViewHelpers\Page\TitleViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Die ViewHelper übergeben Dateien unverändert an den PageRenderer (der »EXT:«-Pfade selbst auflöst)
 * und kommen ohne den in TYPO3 v14 entfernten TypoScriptFrontendController aus.
 */
class PageViewHelpersTest extends UnitTestCase
{
    use ViewHelperTestTrait;

    protected bool $resetSingletonInstances = true;

    private PageRenderer&MockObject $pageRenderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pageRenderer = $this->createMock(PageRenderer::class);
        GeneralUtility::setSingletonInstance(PageRenderer::class, $this->pageRenderer);
    }

    private function render(AbstractViewHelper $viewHelper, array $arguments, mixed $children = null): mixed
    {
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setRenderChildrenClosure(static fn () => $children);
        $this->setArgumentsWithDefaults($viewHelper, $arguments);

        return $viewHelper->initializeArgumentsAndRender();
    }

    #[Test]
    public function linkCssAddsFileToPageRenderer(): void
    {
        $this->pageRenderer->expects(self::once())->method('addCssFile')
            ->with('EXT:dla_opac_ng/Resources/Public/CSS/find.css');

        $this->render(new LinkCSSViewHelper(), ['file' => 'EXT:dla_opac_ng/Resources/Public/CSS/find.css']);
    }

    #[Test]
    public function linkCssIgnoresMissingFile(): void
    {
        $this->pageRenderer->expects(self::never())->method('addCssFile');

        $this->render(new LinkCSSViewHelper(), []);
    }

    #[Test]
    public function scriptAddsFileAsFooterLibrary(): void
    {
        $this->pageRenderer->expects(self::once())->method('addJsFooterLibrary')
            ->with('findJs', 'EXT:dla_opac_ng/Resources/Public/JavaScript/find.js');
        $this->pageRenderer->expects(self::never())->method('addJsFooterInlineCode');

        self::assertSame('', $this->render(new ScriptViewHelper(), ['name' => 'findJs', 'file' => 'EXT:dla_opac_ng/Resources/Public/JavaScript/find.js']));
    }

    #[Test]
    public function scriptWithoutFileAddsChildrenAsInlineCode(): void
    {
        $this->pageRenderer->expects(self::never())->method('addJsFooterLibrary');
        $this->pageRenderer->expects(self::once())->method('addJsFooterInlineCode')->with('inline', 'var a = 1;');

        self::assertSame('', $this->render(new ScriptViewHelper(), ['name' => 'inline'], 'var a = 1;'));
    }

    #[Test]
    public function titleDoesNothingWithoutTypoScriptFrontendController(): void
    {
        unset($GLOBALS['TSFE']);

        self::assertNull($this->render(new TitleViewHelper(), ['title' => 'Treffer – Find']));
    }

    #[Test]
    public function titleReplacesPageTitleInRenderedContent(): void
    {
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->content = '<html><head><title>Katalog: Find</title></head></html>';
        $GLOBALS['TSFE']->page = ['title' => 'Find'];

        $this->render(new TitleViewHelper(), [], 'PE00001005 – Find');

        self::assertSame('<html><head><title>Katalog: PE00001005 – Find</title></head></html>', $GLOBALS['TSFE']->content);
        unset($GLOBALS['TSFE']);
    }
}
