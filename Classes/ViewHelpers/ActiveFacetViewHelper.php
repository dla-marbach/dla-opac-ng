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
        $function = $this->arguments['function'];
        $text = $this->arguments['text'];
        if (empty($function)) {


            if ($text) {
                $resultValue = $this->arguments['text'];

                $rangeRegex = "/RANGE.([0-9]*).TO.([0-9]*)/";

                preg_match($rangeRegex, $resultValue, $matches);

                if ($matches[1]) {
                    $resultValue = $matches[1] . ' bis ' . $matches[2];
                }

            }
        } else if ($function == "decisionTree") {
            $delimiter = '␝';
            $personFunctionArray = array('Über','Von','An','Unter');

            $explodedTerm = explode($delimiter, $text);
            $person = $explodedTerm[0];
            $personFunction = $explodedTerm[1];

            if(in_array($personFunction, $personFunctionArray)) {
                $resultValue = $person . ' in Relation ' . $personFunction;
            } else {
                $resultValue = $person . ' in Funktion ' . $personFunction;
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
