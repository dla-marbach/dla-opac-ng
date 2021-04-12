<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class CheckArrayValueExistsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('arrayValues', 'array', 'Values to check array with', false, null);
        $this->registerArgument('array', 'array', 'Array', false, null);
        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
        $this->registerArgument('isTrue', 'string', 'name of the label result variable', true, 'string2');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $arrayCheckValue = $this->arguments['arrayValues'];
        $array = $this->arguments['array'];
        $resultBoolArray = array();

        $flag = false;

        if ($array) {
            foreach ($array as $key => $value) {
                foreach ($arrayCheckValue as $checkKey => $checkValue) {
                    $pos = strpos($value, $checkValue);

                    if ($pos !== false) {
                        $flag = true;
                        $resultBoolArray[] = true;
                    } else {
                        $resultBoolArray[] = false;
                    }
                }

            }

            $valueName = $this->arguments['as'];
            if ($valueName !== null) {
                if ($this->templateVariableContainer->exists($valueName)) {
                    $this->templateVariableContainer->remove($valueName);
                }
                $this->templateVariableContainer->add($valueName, $resultBoolArray);
    //            $result = $this->renderChildren();
            }

            $valueName = $this->arguments['isTrue'];
            if ($valueName !== null) {
                if ($this->templateVariableContainer->exists($valueName)) {
                    $this->templateVariableContainer->remove($valueName);
                }
                $this->templateVariableContainer->add($valueName, $flag);
    //            $result = $this->renderChildren();
            }
        }

    }
}
?>
