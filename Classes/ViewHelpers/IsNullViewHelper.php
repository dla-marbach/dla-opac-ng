<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class IsNullViewHelper extends AbstractViewHelper
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
