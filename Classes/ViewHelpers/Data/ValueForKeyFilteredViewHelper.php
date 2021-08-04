<?php

namespace Dla\DlaOpacNg\ViewHelpers\Data;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ValueForKeyFilteredViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('key', 'string', 'field name', true, array());
        $this->registerArgument('filterKey', 'string', 'name of the corrosponding field which is used for the filtering', true, array());
        $this->registerArgument('filterValues', 'array', 'values to be filtered', true, array());
        $this->registerArgument('mustMatch', 'boolean', 'Specifies whether the filterValue must match or only needs to be included', false, true);
        $this->registerArgument('negateFilter', 'boolean', 'Specifies whether the values not matching the filterValue are returned', false, false);
        $this->registerArgument('fields', 'array', 'The field array', true, array());
        $this->registerArgument('as', 'string', 'name of the result array', true, array());
    }

    /**
     * @return string
     */
    public function render()
    {
        $values = $this->getValuesByKey($this->arguments['key'], $this->arguments['fields']);
        $parallelValues = $this->getValuesByKey($this->arguments['filterKey'], $this->arguments['fields']);

        $mustMatch = $this->arguments['mustMatch'];
        $negateFilter = $this->arguments['negateFilter'];

        $result = $this->arrayFilterByParallelArray($values, $parallelValues, $this->arguments['filterValues'], $negateFilter, $mustMatch);

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

    protected function getValuesByKey($key, $fields) {
        $values = array();
        if ($fields) {
            if (array_key_exists($key, $fields)) {
                $values = $fields[$key];
                if (!empty($values)) {
                    if (!is_array($values)) {
                        $values = array($values);
                    }
                }
            }
        }
        return $values;
    }

    protected function arrayFilterByParallelArray($array, $parallelArray, $filterValues, $negateFilter = FALSE, $mustMatch = TRUE) {

        $arraySize = max(sizeof($array),sizeof($parallelArray));

        $array = array_values($array);
        $array = array_pad($array, $arraySize , NULL);
        $filterValues = array_values($filterValues);

        $parallelArray = array_values($parallelArray);
        $parallelArray = array_pad($parallelArray, $arraySize , NULL);

        $result = array();
        for($i=0; $i<$arraySize; $i++) {
            if ($mustMatch) {
                foreach ($filterValues as $filterValue) {
                    if (($parallelArray[$i] == $filterValue) && $array[$i]) {
                        $result[$i] = $array[$i];
                        break;
                    }
                }
            } else {
                foreach ($filterValues as $filterValue) {
                    if (strpos($parallelArray[$i],$filterValue) !== FALSE) {
                        $result[$i] = $array[$i];
                        break;
                    }
                }
            }
        }

        if ($negateFilter) {
            $negatedResult = $array;
            foreach (array_keys($result) as $index) {
                unset($negatedResult[$index]);
            }
            return array_values($negatedResult);
        }

        return array_values($result);
    }
}
?>
