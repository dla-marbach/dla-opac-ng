<?php

namespace Dla\DlaOpacNg\ViewHelpers\Data;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ImplodeViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('array', 'array', 'The array to be joined', true, array());
        $this->registerArgument('glue', 'string', '', true, '');
        $this->registerArgument('as', 'string', 'name of the result', true, NULL);
    }

    /**
     * @return string
     */
    public function render()
    {
        $array = $this->arguments['array'];
        if ($array) {
            $array = array_values($array);
            $glue = $this->arguments['glue'];

            $result = implode($glue,$array);

            $variableName = $this->arguments['as'];
            if ($variableName !== null) {
                if ($this->templateVariableContainer->exists($variableName)) {
                    $this->templateVariableContainer->remove($variableName);
                }
                $this->templateVariableContainer->add($variableName, $result);
                $result = $this->renderChildren();
            }
            return $result;
        }

    }
}
?>
