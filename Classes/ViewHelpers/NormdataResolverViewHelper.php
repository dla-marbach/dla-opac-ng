<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use Dla\DlaOpacNg\Service\EntityService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class NormdataResolverViewHelper extends AbstractViewHelper
{
    private EntityService $entityService;

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

    public function __construct(EntityService $entityService)
    {
        $this->entityService = $entityService;
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $id = $this->arguments['id'];

        $resultValue = $this->entityService->getEntity($id);

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
