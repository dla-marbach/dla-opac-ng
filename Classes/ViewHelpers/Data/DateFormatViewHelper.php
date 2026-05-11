<?php

namespace Dla\DlaOpacNg\ViewHelpers\Data;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class DateFormatViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('dateValue', 'integer', 'DateValue', true, array());
        $this->registerArgument('desiredFormat', 'string', 'format', false, 'd.m.Y');
        $this->registerArgument('as', 'string', 'name of the result array', true, array());
    }

    /**
     * @return string
     */
    public function render()
    {
        $dateValue = $this->arguments['dateValue'][0];
        $desiredFormat = $this->arguments['desiredFormat'];

        $dateOutput = '';
        $date = null;

        // Prüfe auf vollständiges Datum (YYYY-MM-DD)
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateValue, $matches)) {
            $date = \DateTime::createFromFormat('Y-m-d', $dateValue);
            if ($date) {
                $dateOutput = $date->format($desiredFormat);
            }
        }
        // Prüfe auf Jahr und Monat (YYYY-MM)
        elseif (preg_match('/^(\d{4})-(\d{2})$/', $dateValue, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            // Erzeuge ein Datum mit Tag = 01
            $date = \DateTime::createFromFormat('Y-m-d', $year . '-' . $month . '-01');
            if ($date) {
                // Formatierung: nur Monat und Jahr anzeigen
                $dateOutput = $date->format('m.Y');
            } else {
                $dateOutput = $month . '.' . $year;
            }
        }
        // Prüfe auf Jahr (YYYY)
        elseif (preg_match('/^(\d{4})$/', $dateValue, $matches)) {
            $dateOutput = $matches[1];
        }

        $variableName = $this->arguments['as'];
        if ($variableName !== null) {
            if ($this->templateVariableContainer->exists($variableName)) {
                $this->templateVariableContainer->remove($variableName);
            }
            $this->templateVariableContainer->add($variableName, $dateOutput);
            $dateOutput = $this->renderChildren();
        }

        return $dateOutput;

    }
}
?>
