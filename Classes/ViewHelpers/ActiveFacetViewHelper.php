<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ActiveFacetViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('text', 'string', 'Text', false, null);
        $this->registerArgument('function', 'string', 'Text', false, null);
        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $resultValue = null;
        $function = (string)($this->arguments['function'] ?? '');
        $text = (string)($this->arguments['text'] ?? '');
        if (empty($function)) {


            if ($text) {
                $resultValue = $text;

                $rangeRegex = "/RANGE.([0-9]*).TO.([0-9]*)/";

                preg_match($rangeRegex, $resultValue, $matches);

                if (isset($matches[1], $matches[2]) && $matches[1] !== '') {
                    $resultValue = $matches[1] . ' bis ' . $matches[2];
                }

            }
        } else if ($function == "decisionTree") {
            $delimiter = '␝';
            $personFunctionArray = array('Über','Von','An','Unter');

            $explodedTerm = explode($delimiter, $text, 2);
            $person = $explodedTerm[0] ?? '';
            $personFunction = $explodedTerm[1] ?? '';

            if ($personFunction !== '' && in_array($personFunction, $personFunctionArray, true)) {
                $resultValue = $person . ' in Relation ' . $personFunction;
            } else {
                $resultValue = $personFunction !== ''
                    ? $person . ' in Funktion ' . $personFunction
                    : $person;
            }

        }

        $valueName = $this->arguments['as'];
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
