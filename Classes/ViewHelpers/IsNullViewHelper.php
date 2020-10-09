<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class IsNullViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('variable', 'mixed', 'Text', false, null);
    }

    /**
     * @return boolean
     */
    public function render()
    {
        return ($this->arguments['variable'] === NULL);
    }
}
?>
