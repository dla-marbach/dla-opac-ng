<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ExplodeViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('string', 'string', 'List of fields', true, '');
        $this->registerArgument('delimiter', 'string', 'List of fields', true, '');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        return explode($this->arguments['delimiter'], $this->arguments['string']);
    }
}
?>
