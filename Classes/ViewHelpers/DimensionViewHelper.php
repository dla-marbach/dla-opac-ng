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
                $description = $descriptions[$i];
            }
            if($diameters[$i] && $diameters[$i] != '#NV') {
                if($nextNoX) {
                    $result = $diameters[$i] . " " . $result;
                    $nextNoX = false;
                } else {
                    $result = $diameters[$i] . " x " . $result;
                }
            } else {
                $nextNoX = true;
            }
            if($depths[$i] && $depths[$i] != '#NV') {
                if($nextNoX) {
                    $result = $depths[$i] . " (Tiefe) " . $result;
                    $nextNoX = false;
                } else {
                    $result = $depths[$i] . " (Tiefe) x " . $result;
                }
            } else {
                $nextNoX = true;
            }
            if($widths[$i] && $widths[$i] != '#NV') {
                if($nextNoX) {
                    $result = $widths[$i] . " (Breite) " . $result;
                    $nextNoX = false;
                } else {
                    $result = $widths[$i] . " (Breite) x " . $result;
                }
            } else {
                $nextNoX = true;
            }
            if($heights[$i] && $heights[$i] != '#NV') {
                if($nextNoX) {
                    $result = $heights[$i] . " (Höhe) " . $result;
                    $nextNoX = false;
                } else {
                    $result = $heights[$i] . " (Höhe) x " . $result;
                }
            }
            $result = str_replace(".", ",", $result);
            $result = $result . ' ' . $description;
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
