<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class SubstringBeforeViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('text', 'string', 'Input text', false, '');
        $this->registerArgument('delimiter', 'string', 'Delimiter to cut before', false, '');
    }

    public function render()
    {
        $text = (string)$this->arguments['text'];
        $delimiter = (string)$this->arguments['delimiter'];

        if ($text === '' || $delimiter === '') {
            return $text;
        }

        $position = strpos($text, $delimiter);
        if ($position === false) {
            return $text;
        }

        return substr($text, 0, $position);
    }
}
