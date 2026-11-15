<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ArrayRemoveNullViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('array', 'array', 'List of fields', false, array());
        $this->registerArgument('as', 'string', 'name of the label result variable', true);
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $resultValue = [];

        $array = $this->arguments['array'];
        foreach ($array as $key => $value) {
            if ($value !== NULL) {
                $resultValue[] = $value;
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
