<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class HistogramValueViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('array', 'array', 'List of fields', true, array());
        $this->registerArgument('firstValueAs', 'string', 'name of the value result variable', true, array());
        $this->registerArgument('dateFormat', 'string', 'returns date in the given format default is Y', false, "Y");
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $array = $this->arguments['array'];
        $resultValue = date($this->arguments['dateFormat'], strtotime(min(array_keys($array))));

        $valueName = $this->arguments['firstValueAs'];
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
