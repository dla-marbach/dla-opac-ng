<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ArrayMergeViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('array1', 'array', 'List of fields', true, array());
        $this->registerArgument('array2', 'array', 'List of fields', true, array());
        $this->registerArgument('array3', 'array', 'List of fields', false, array());
        $this->registerArgument('as', 'string', 'name of the label result variable', true, array());
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $resultValue = array_merge_recursive($this->arguments['array1'], $this->arguments['array2'], $this->arguments['array3']);

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
