<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ExplodeViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('string', 'mixed', 'List of fields', false, '');
        $this->registerArgument('delimiter', 'string', 'List of fields', true);
    }

    /**
     * @return boolean
     */
    public function render()
    {
        return explode((string)$this->arguments['delimiter'], (string)$this->arguments['string']);
    }
}
?>
