<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class FormatDateViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('date', 'string', 'Datum', true, '');
        $this->registerArgument('regexDay', 'string', 'Regex for input', false, '/\d{4}\d{2}(\d{2})/');
        $this->registerArgument('regexMonth', 'string', 'Regex for input', false, '/\d{4}(\d{2})\d{2}/');
        $this->registerArgument('regexYear', 'string', 'Regex for input', false, '/(\d{4})\d{2}\d{2}/');
        $this->registerArgument('format', 'string', 'Output format', false, '');
        $this->registerArgument('as', 'string', 'name of the label result variable', true, array());
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $date = $this->arguments['date'];
        $year = preg_match($this->arguments['regexYear'], $date,$matchYear);
        $month = preg_match($this->arguments['regexMonth'], $date,$matchMonth);
        $day = preg_match($this->arguments['regexDay'], $date,$matchDay);

        $yearOnly = false;

        if ($matchDay[1] == "00" || !$matchDay) {
            $matchDay[1] = 01;
            $yearOnly = true;
        }

        if ($matchMonth[1] == "00" || !$matchMonth) {
            $matchMonth[1] = 01;
            $yearOnly = true;
        }

        if (!$matchYear && $yearOnly) {
            if (strlen($date) == 4) {
                $matchYear[1] = $date;
                $matchMonth[1] = 01;
                $matchDay[1] = 01;
            }
        }

        $resultValue = new \DateTime();
        $resultValue->setDate($matchYear[1], $matchMonth[1], $matchDay[1]);

        if ($yearOnly) {
            $resultValue = date_format($resultValue, 'Y');
        } else {
            $resultValue = date_format($resultValue, 'd.m.Y');
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
