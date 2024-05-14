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

        if (!$date)
            return false;

        $year = preg_match($this->arguments['regexYear'], $date,$matchYear);
        $month = preg_match($this->arguments['regexMonth'], $date,$matchMonth);
        $day = preg_match($this->arguments['regexDay'], $date,$matchDay);

        $yearOnly = false;
        $yearMonth = false;

        $year = null;
        $month = null;
        $day = null;

        // check if year only
        if (strlen($date) == 4) {
            $year = $date;
            $month = 1;
            $day = 1;
        } else {
            $year = array_key_exists(1, $matchYear) ? $matchYear[1] : 0;
            $month = array_key_exists(1, $matchMonth) ? $matchMonth[1] : 1;
            $day = array_key_exists(1, $matchDay) ? $matchDay[1] : 1;

            if ($day === '00') {
                $day = 1;
                $yearMonth = true;
            }

            if ($month === '00') {
                $month = 1;
                $yearOnly = true;
            }
        }

        $resultValue = new \DateTime();
        $resultValue->setDate(
            $year,
            $month,
            $day
        );

        if ($yearOnly) {
            $resultValue = date_format($resultValue, 'Y');
        } else if ($yearMonth) {
            $resultValue = date_format($resultValue, 'm.Y');
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
