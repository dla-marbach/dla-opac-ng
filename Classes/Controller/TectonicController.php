<?php
namespace Dla\DlaOpacNg\Controller;

use \TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class TectonicController extends ActionController
{

    /**
     * Index Action.
     * @param string $record
     */
    public function indexAction(string $record)
    {
        $this->view->assign('record', $record);
    }
}