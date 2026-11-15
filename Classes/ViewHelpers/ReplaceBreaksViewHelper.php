<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ReplaceBreaksViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('text', 'mixed', 'Text', false, '');
        $this->registerArgument('as', 'string', 'name of the label result variable', true);
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $resultValue = str_replace("\n", "<br/>", $this->arguments['text']);

        if (is_array($resultValue)) {
            $resultValue = $resultValue[0];
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
