<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class RemoveFromArrayViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('array', 'array', 'Array', true, null);
        $this->registerArgument('array2', 'array', 'Array', false, null);

        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
        $this->registerArgument('as2', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $array = is_array($this->arguments['array']) ? $this->arguments['array'] : [];
        $array2 = is_array($this->arguments['array2']) ? $this->arguments['array2'] : [];

        $newArray = array();
        $newArray2 = array();

        $i = 0;
        if ($array) {
            foreach ($array as $key => $value) {
                $array2Value = $array2[$i] ?? null;
                if ($value != '␣' && $array2Value !== null && $array2Value != '␣') {
                    $newArray[$i] = $value;
                    $newArray2[$i] = $array2Value;
                }
                $i++;
            }


            $valueName = $this->arguments['as'];
            if ($valueName !== null) {
                if ($this->templateVariableContainer->exists($valueName)) {
                    $this->templateVariableContainer->remove($valueName);
                }
                $this->templateVariableContainer->add($valueName, $newArray);
            }

            $valueName = $this->arguments['as2'];
            if ($valueName !== null) {
                if ($this->templateVariableContainer->exists($valueName)) {
                    $this->templateVariableContainer->remove($valueName);
                }
                $this->templateVariableContainer->add($valueName, $newArray2);
            }
        }

    }
}
?>
