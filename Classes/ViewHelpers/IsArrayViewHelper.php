<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class IsArrayViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('array', 'mixed', 'List of fields', true, array());
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
