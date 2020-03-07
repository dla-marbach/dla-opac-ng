<?php

namespace Dla\DlaOpacNg\ViewHelpers\Data;


class JoinFieldsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('fieldNames', 'array', 'Comma separated field name list', true, array());
        $this->registerArgument('fieldWraps', 'array', 'Comma separated list of wraps for the fields', true, array());
        $this->registerArgument('fields', 'array', 'The field array', true, array());
        $this->registerArgument('as', 'string', 'name of the result array', true, array());
    }

    /**
     * @return string
     */
    public function render()
    {

        $fieldNames = array_values($this->arguments['fieldNames']);
        $fieldWraps = array_values($this->arguments['fieldWraps']);
        $fields = $this->arguments['fields'];

        $fieldValues = array();
        $index = 0;
        foreach ($fieldNames as $fieldName) {

            $fieldWrap = "";
            if (array_key_exists($index, $fieldWraps)) {
                $fieldWrap = $fieldWraps[$index];
            }
            $fieldWrap = htmlspecialchars((empty($fieldWrap)) ? "%s" : $fieldWrap);

            if ($fields) {
                // Wrap the field values
                if (array_key_exists($fieldName, $fields)) {
                    $value = $fields[$fieldName];
                    if (!empty($value)) {
                        if (is_array($value)) {
                            array_walk($value, function (&$item, $key, $fieldWrap) {
                                $item = sprintf($fieldWrap, $item);
                            },$fieldWrap);
                            $fieldValues[$index] = $value;
                        } else {
                            $fieldValues[$index] = array(sprintf($fieldWrap, $value));
                        }
                    }
                }
            }
            ++$index;
        }

        $maxCountValues = 0;
        foreach ($fieldValues as $col) {
            $count = sizeof($col);
            $maxCountValues = ($count > $maxCountValues) ? $count : $maxCountValues;
        }

        $result = array();
        for ($i = 0; $i < $maxCountValues; $i++) {
            $line = array();
            foreach ($fieldValues as $col) {
                if (array_key_exists($i, $col)) {
                    $line[] = $col[$i];
                } else {
                    $line[] = "";
                }
            }
            $result[] = implode(" ", $line);
        }


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
}
?>
