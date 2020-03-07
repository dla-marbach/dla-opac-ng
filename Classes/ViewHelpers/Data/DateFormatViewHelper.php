<?php

namespace Dla\DlaOpacNg\ViewHelpers\Data;


class DateFormatViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('dateValue', 'integer', 'DateValue', true, array());
        $this->registerArgument('as', 'string', 'name of the result array', true, array());
    }

    /**
     * @return string
     */
    public function render()
    {
        $dateValue = $this->arguments['dateValue'][0];

        $year = substr($dateValue, 0, 4);
        $month = substr($dateValue, 4, 2);
        $day = substr($dateValue, 6, 2);

        $date = new \DateTime();

        if ($month == '00') {
            $month = '01';
        }
        if ($day == '00') {
            $day = '01';
        }

        $date->setDate($year, $month, $day);

        $variableName = $this->arguments['as'];
        if ($variableName !== null) {
            if ($this->templateVariableContainer->exists($variableName)) {
                $this->templateVariableContainer->remove($variableName);
            }
            $this->templateVariableContainer->add($variableName, $date);
            $date = $this->renderChildren();
        }

        return $date;

    }
}
?>
