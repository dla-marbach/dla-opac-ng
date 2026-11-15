<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class HistogramValueViewHelper extends AbstractViewHelper
{

    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('array', 'array', 'List of fields', false, array());
        $this->registerArgument('firstValueAs', 'string', 'name of the value result variable', false, "firstValue");
        $this->registerArgument('lastValueAs', 'string', 'name of the value result variable', false, "lastValue");
        $this->registerArgument('dateFormat', 'string', 'returns date in the given format default is Y', false, "Y");
    }

    /**
     * @return array
     */
    public function render()
    {

        $array = $this->arguments['array'];
        $minTimeValue = date($this->arguments['dateFormat'], strtotime(min(array_keys($array))));

        foreach ($array as $key => $value) {
            $timeArray[] = $key;
        }
        $maxTimeValue = date($this->arguments['dateFormat'], strtotime(max($timeArray)));

        return [
            'lastDate' => $maxTimeValue,
            'firstDate' => $minTimeValue,
        ];
    }

}
?>
