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
        $this->registerArgument('firstValueAs', 'string', 'name of the value result variable', true, "firstValue");
        $this->registerArgument('lastValueAs', 'string', 'name of the value result variable', true, "lastValue");
        $this->registerArgument('dateFormat', 'string', 'returns date in the given format default is Y', false, "Y");
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $array = $this->arguments['array'];
        $resultValue = date($this->arguments['dateFormat'], strtotime(min(array_keys($array))));

        foreach ($array as $key => $value) {
            if ($value > 0) {
                $timeArray[] = $key;
            }
        }

        $valueName = $this->arguments['firstValueAs'];
        if ($valueName !== null) {
            if ($this->templateVariableContainer->exists($valueName)) {
                $this->templateVariableContainer->remove($valueName);
            }
            $this->templateVariableContainer->add($valueName, $resultValue);
        }

        $resultValue = date($this->arguments['dateFormat'], strtotime(max($timeArray)));

        $valueName = $this->arguments['lastValueAs'];
        if ($valueName !== null) {
            if ($this->templateVariableContainer->exists($valueName)) {
                $this->templateVariableContainer->remove($valueName);
            }
            $this->templateVariableContainer->add($valueName, $resultValue);
        }
    }

}
?>
