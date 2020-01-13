<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class ExplodeViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
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
