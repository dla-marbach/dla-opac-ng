<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class ChartValueViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('array', 'array', 'List of fields', true, array());
        $this->registerArgument('valueAs', 'string', 'name of the value result variable', true, array());
        $this->registerArgument('labelAs', 'string', 'name of the label result variable', true, array());
        $this->registerArgument('countAs', 'string', 'name of the label result variable', true, array());
        $this->registerArgument('itemCount', 'integer', 'number of facet item', true, array());
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $array = $this->arguments['array'];
        $i = 0;
        $resultValue = '';
        $resultLabel = '';
        $countValues = 0;
        foreach ($array as $key => $value) {
            if ($i < $this->arguments['itemCount']) {
                if ($i == 0) {
                    $resultValue .= $value;
                    $resultLabel .= '"'.$key.'"';
                } else {
                    $resultValue .= ', ' . $value;
                    $resultLabel .= ', "' . $key . '"';
                }
            }

            $countValues += $value;

            $i++;
        }

        $valueName = $this->arguments['valueAs'];
        if ($valueName !== null) {
            if ($this->templateVariableContainer->exists($valueName)) {
                $this->templateVariableContainer->remove($valueName);
            }
            $this->templateVariableContainer->add($valueName, $resultValue);
//            $result = $this->renderChildren();
        }
        $labelName = $this->arguments['labelAs'];
        if ($labelName !== null) {
            if ($this->templateVariableContainer->exists($labelName)) {
                $this->templateVariableContainer->remove($labelName);
            }
            $this->templateVariableContainer->add($labelName, $resultLabel);
//            $result = $this->renderChildren();
        }
        $countName = $this->arguments['countAs'];
        if ($countName !== null) {
            if ($this->templateVariableContainer->exists($countName)) {
                $this->templateVariableContainer->remove($countName);
            }
            $this->templateVariableContainer->add($countName, $countValues);
//            $result = $this->renderChildren();
        }
//        return $resultValue;

    }
}
?>
