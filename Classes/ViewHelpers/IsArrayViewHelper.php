<?php

namespace Dla\DlaOpacNg\ViewHelpers\Data;


class isArrayViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('array', 'array', 'List of fields', true, array());
    }

    /**
     * @return boolean
     */
    public function render()
    {

        return is_array($this->arguments['array']);

    }
}
?>
