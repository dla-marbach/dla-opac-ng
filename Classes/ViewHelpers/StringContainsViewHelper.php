<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use Symfony\Component\HttpFoundation\IpUtils;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class StringContainsViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('input', 'string || array', 'string or array with texts', true);

        $this->registerArgument('search', 'string', 'search', true);
        $this->registerArgument('as', 'string', 'name of the label result variable', false, null);
    }

    /**
     * @return boolean
     */
    public function render(): bool|null
    {
        $resultValue = false;

        $input = $this->arguments['input'];
        $search = (string)$this->arguments['search'];

        if (is_array($input)) {
            foreach ($input as $value) {
                if ($value !== null && str_contains((string)$value, $search)) {
                    $resultValue = true;
                    break;
                }
            }
        } else {
            $resultValue = $input !== null && str_contains((string)$input, $search);
        }

        $valueName = $this->arguments['as'];
        if ($valueName !== null) {
            if ($this->templateVariableContainer->exists($valueName)) {
                $this->templateVariableContainer->remove($valueName);
            }
            $this->templateVariableContainer->add($valueName, $resultValue);
//            $result = $this->renderChildren();
            return null;
        }

        return $resultValue;

    }
}
?>
