<?php

namespace Dla\DlaOpacNg\ViewHelpers\Data;


class ValueForKeyFilteredViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
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
        $this->registerArgument('filterValue', 'string', 'the value to be filtered', true, array());
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

        $result = $this->arrayFilterByParallelArray($values, $parallelValues, $this->arguments['filterValue']);

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

    protected function arrayFilterByParallelArray($array, $parallelArray, $filterValue = NULL) {

        $arraySize = max(sizeof($array),sizeof($parallelArray));

        $array = array_values($array);
        $array = array_pad($array, $arraySize , NULL);

        $parallelArray = array_values($parallelArray);
        $parallelArray = array_pad($parallelArray, $arraySize , NULL);

        $result = array();
        for($i=0; $i<$arraySize; $i++) {
            if (($parallelArray[$i] === $filterValue) && $array[$i]) {
                $result[] = $array[$i];
            }
        }

        return $result;
    }
}
?>
