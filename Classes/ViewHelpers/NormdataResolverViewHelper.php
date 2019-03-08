<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class NormdataResolverViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('id', 'string', 'Normdata ID', true, "");
        $this->registerArgument('valueAs', 'string', 'name of the value result variable', true, "output");

    }

    /**
     * @return boolean
     */
    public function render()
    {
        $id = $this->arguments['id'];

        $url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/index.php?eID=getEntity&q=" . $id;
        $jsonObject = json_decode(file_get_contents($url));

        $resultValue = $jsonObject;

        $valueName = $this->arguments['valueAs'];
        if ($valueName !== null) {
            if ($this->templateVariableContainer->exists($valueName)) {
                $this->templateVariableContainer->remove($valueName);
            }
            $this->templateVariableContainer->add($valueName, $resultValue);
        }

    }
}
?>
