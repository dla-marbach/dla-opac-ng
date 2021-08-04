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
        $this->registerArgument('array2', 'array', 'Array', true, null);

        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
        $this->registerArgument('as2', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $array = $this->arguments['array'];
        $array2 = $this->arguments['array2'];

        $newArray = array();
        $newArray2 = array();

        $i = 0;
        if ($array) {
            foreach ($array as $key => $value) {
                if (!($value == '␞' || $value == '#NV') && !($array2[$i] == '␞' || $array2[$i] == '#NV' )) {
                    $newArray[$i] = $value;
                    $newArray2[$i] = $array2[$i];
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
