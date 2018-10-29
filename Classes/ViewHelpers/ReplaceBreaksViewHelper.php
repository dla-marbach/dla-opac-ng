<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class ReplaceBreaksViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('text', 'string', 'Text', true, 'string');
        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $resultValue = str_replace("/SP/", "<br/>", $this->arguments['text']);
        $resultValue = str_replace("/BR/", "<br/>", $resultValue);
        $resultValue = str_replace("\u{0085}", "<br/>", $resultValue);

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
