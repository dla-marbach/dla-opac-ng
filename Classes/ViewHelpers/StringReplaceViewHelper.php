<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class StringReplaceViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
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
        $this->registerArgument('replace', 'string', 'Text', false, null);
        $this->registerArgument('search', 'string', 'Text', false, null);
        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        if ($this->arguments['text']) {
            $resultValue = str_replace($this->arguments['search'], $this->arguments['replace'], $this->arguments['text']);

            if (is_array($resultValue)) {
                $resultValue = $resultValue[0];
            }
            var_dump($resultValue);
        } else if (is_array($this->arguments['array'])) {
            foreach ($this->arguments['array'] as $key => $value) {

                $value = str_replace($this->arguments['search'], $this->arguments['replace'], $value);

                $resultValue[] = $value;
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
