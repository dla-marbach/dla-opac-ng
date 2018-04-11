<?php

namespace Dla\DlaOpacNg\ViewHelpers\Data;


class JoinFieldArraysViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('arrays', 'array', 'The arrays to be joined as columns', true, array());
        $this->registerArgument('fieldWraps', 'array', 'Comma separated list of wraps for the fields', true, array());
        $this->registerArgument('as', 'string', 'name of the result array', true, array());
    }

    /**
     * @return string
     */
    public function render()
    {
        $fieldArrays = array_values($this->arguments['arrays']);
        $fieldWraps = array_values($this->arguments['fieldWraps']);

        $result = $this->joinArrays($fieldArrays,$fieldWraps);

        $variableName = $this->arguments['as'];
        if ($variableName !== null) {
            if ($this->templateVariableContainer->exists($variableName)) {
                $this->templateVariableContainer->remove($variableName);
            }
            $this->templateVariableContainer->add($variableName, $result);
            $result = $this->renderChildren();
        }
        return $result;

    }

    protected function joinArrays($arrays, $wraps) {

        if (!is_array($arrays)) {
            $arrays = array();
        }

        if (!is_array($wraps)) {
            $wraps = array();
        }
        $wraps = array_values($wraps);
        $wraps = array_pad($wraps, sizeof($arrays) , '%s');

        $arraySize = 0;

        for ($i=0; $i<sizeof($arrays); $i++) {
            if (!is_array($arrays[$i])) {
                $arrays[$i] = array();
            } else {
                $arrays[$i] = array_values($arrays[$i]);
            }
            $arraySize = (sizeof($arrays[$i]) > $arraySize)? sizeof($arrays[$i]) : $arraySize;
        }

        for ($i=0; $i<sizeof($arrays); $i++) {
            $arrays[$i] = array_pad($arrays[$i], $arraySize , NULL);
        }


        for ($j=0; $j<$arraySize; $j++) {
            $row = array();
            for ($i=0; $i<sizeof($arrays); $i++) {
                if ($arrays[$i][$j]) {
                    if ($arrays[$i][$j] != "␞") {
                        $row[] = sprintf($wraps[$i], $arrays[$i][$j]);
                    }
                }
            }
            $result[] = implode(" ",$row);
        }

        return $result;

    }
}
?>
