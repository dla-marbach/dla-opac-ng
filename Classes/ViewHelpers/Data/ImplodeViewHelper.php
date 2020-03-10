<?php

namespace Dla\DlaOpacNg\ViewHelpers\Data;


class ImplodeViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
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
        $array = array_values($this->arguments['array']);
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
?>
