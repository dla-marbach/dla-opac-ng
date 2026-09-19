<?php

namespace Dla\DlaOpacNg\Tests\Support\FluidViewHelperOverrides\Link;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Test-only Ersatz für TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper (final, daher nicht
 * erweiterbar). Echtes Routing (UriBuilder) braucht einen vollständigen Frontend-Request
 * (Site/TSFE/Routing), das ist für reine Partial-Rendering-Tests bewusst außer Scope
 * (siehe Tests/Support/FluidPartialTestCase.php) - hier wird nur ein Platzhalter-Link
 * gerendert, damit der umgebende Inhalt (Linktext, umschließende Partials) testbar bleibt.
 */
class ActionViewHelper extends AbstractTagBasedViewHelper
{
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('controller', 'string', 'Target controller');
        $this->registerArgument('extensionName', 'string', 'Target extension name');
        $this->registerArgument('pluginName', 'string', 'Target plugin');
        $this->registerArgument('pageUid', 'int', 'Target page');
        $this->registerArgument('pageType', 'int', 'Type of the target page');
        $this->registerArgument('noCache', 'bool', 'unused in tests');
        $this->registerArgument('language', 'string', 'unused in tests');
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI');
        $this->registerArgument('format', 'string', 'unused in tests');
        $this->registerArgument('linkAccessRestrictedPages', 'bool', 'unused in tests');
        $this->registerArgument('additionalParams', 'array', 'unused in tests');
        $this->registerArgument('absolute', 'bool', 'unused in tests');
        $this->registerArgument('addQueryString', 'string', 'unused in tests', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'unused in tests');
        $this->registerArgument('arguments', 'array', 'Arguments for the controller action');
    }

    public function render(): string
    {
        $this->tag->addAttribute('href', '#test-link');
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
