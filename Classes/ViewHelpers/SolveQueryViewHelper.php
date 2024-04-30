<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class SolveQueryViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('array', 'array', 'Array', true, null);
        $this->registerArgument('settings', 'array', 'Array', true, null);
        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $settings = $this->arguments['settings'];
        $array = $this->arguments['array'];

        foreach ($settings['queryFields'] as $queryField) {
            if (!empty($array[$queryField['id']])) {
                if (is_array($array[$queryField['id']])) {
                    $resultValue = $queryField['query'];
                    for ($i=0;$i < sizeof($array[$queryField['id']]);$i++) {
                        $resultValue = str_replace('%' . ($i + 1) . '$s', $array[$queryField['id']][$i], $resultValue);

                    }
                } else {
                    $resultValue = str_replace('%1$s', $array[$queryField['id']], $queryField['query']);
                }
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
