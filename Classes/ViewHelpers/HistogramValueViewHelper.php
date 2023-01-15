<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class HistogramValueViewHelper extends AbstractViewHelper
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
     * @return array
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {

        $array = $arguments['array'];
        $minTimeValue = date($arguments['dateFormat'], strtotime(min(array_keys($array))));

        foreach ($array as $key => $value) {
            $timeArray[] = $key;
        }
        $maxTimeValue = date($arguments['dateFormat'], strtotime(max($timeArray)));

        return [
            $arguments['lastValueAs'] => $maxTimeValue,
            $arguments['firstValueAs'] => $minTimeValue,
        ];
    }

}
?>
