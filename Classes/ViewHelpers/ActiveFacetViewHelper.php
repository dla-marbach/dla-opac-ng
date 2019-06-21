<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class ActiveFacetViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('text', 'string', 'Text', false, null);
        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        if ($this->arguments['text']) {
            $resultValue = $this->arguments['text'];

            $rangeRegex = "/RANGE.([0-9]*).TO.([0-9]*)/";

            preg_match($rangeRegex, $resultValue, $matches);

            if ($matches[1]) {
                $resultValue = $matches[1] . ' bis ' . $matches[2];
            }

        }

        $valueName = $this->arguments['as'];
        if ($valueName !== null) {
            if ($this->templateVariableContainer->exists($valueName)) {
                $this->templateVariableContainer->remove($valueName);
            }
            $this->templateVariableContainer->add($valueName, $resultValue);
//            $result = $this->renderChildren();
        }

    }
}
?>
