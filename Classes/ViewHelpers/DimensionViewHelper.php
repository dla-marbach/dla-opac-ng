<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class DimensionViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('height', 'array', 'Array', false, null);
        $this->registerArgument('width', 'array', 'Array', false, null);
        $this->registerArgument('depth', 'array', 'Array', false, null);
        $this->registerArgument('diameter', 'array', 'Array', false, null);
        $this->registerArgument('description', 'array', 'Array', false, null);

        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $heights = $this->arguments['height'];
        $widths = $this->arguments['width'];
        $depths = $this->arguments['depth'];
        $diameters = $this->arguments['diameter'];
        $descriptions = $this->arguments['description'];

        $countAll[] = count($heights);
        $countAll[] = count($widths);
        $countAll[] = count($depths);
        $countAll[] = count($diameters);
        $countAll[] = count($descriptions);

        $maxValue = max($countAll);
        $resultValue = array();

        for($i=0; $i < $maxValue; $i++) {
            $result = "";
            $nextNoX = false;
            if($descriptions[$i] && $descriptions[$i] != '#NV') {
                $nextNoX = true;
                $result = $descriptions[$i];
            }
            if($diameters[$i] && $diameters[$i] != '#NV') {
                if($nextNoX) {
                    $result = $diameters[$i] . " " . $result;
                    $nextNoX = false;
                } else {
                    $result = $diameters[$i] . " x " . $result;
                }
            }
            if($depths[$i] && $depths[$i] != '#NV') {
                if($nextNoX) {
                    $result = $depths[$i] . " " . $result;
                    $nextNoX = false;
                } else {
                    $result = $depths[$i] . " x " . $result;
                }
            }
            if($widths[$i] && $widths[$i] != '#NV') {
                if($nextNoX) {
                    $result = $widths[$i] . " " . $result;
                    $nextNoX = false;
                } else {
                    $result = $widths[$i] . " x " . $result;
                }
            }
            if($heights[$i] && $heights[$i] != '#NV') {
                if($nextNoX) {
                    $result = $heights[$i] . " " . $result;
                    $nextNoX = false;
                } else {
                    $result = $heights[$i] . " x " . $result;
                }
            }

            $resultValue[] = $result;
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
