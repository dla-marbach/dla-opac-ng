<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class ReplaceNullValueViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('text', 'string', 'Text', false, null);
        $this->registerArgument('array', 'array', 'Array', false, null);
        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        if ($this->arguments['text']) {
            $resultValue = str_replace("(#NV)", "", $this->arguments['text']);
            $resultValue = str_replace("#NV : #NV", "", $resultValue);
            $resultValue = str_replace("#NV :", "", $resultValue);
            $resultValue = str_replace("#NV", "", $resultValue);
            $resultValue = str_replace("␞", "", $resultValue);

            if (is_array($resultValue)) {
                $resultValue = $resultValue[0];
            }
        } else if (is_array($this->arguments['array'])) {
            foreach ($this->arguments['array'] as $key => $value) {

                $value = str_replace("(#NV)", "", $value);
                $value = str_replace("␞", "", $value);

                preg_match('/\#NV/', $value, $matches);
                if (empty($matches)) {
                    if (!empty($value)) {
                        $resultValue[] = $value;
                    }

                } else {
                    $value = str_replace("#NV", "", $value);
                    if (!empty($value)) {
                        $resultValue[] = $value;
                    }
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
