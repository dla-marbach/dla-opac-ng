<?php
namespace Dla\DlaOpacNg\Controller;

use \TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class CollectionController extends ActionController
{

    /**
     * Index Action.
     * @param string $record
     * @param string $uid
     */
    public function indexAction(string $record = '', string $uid = '')
    {
        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager */

        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        $configurationManager = $objectManager->get('TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface');
        $settings = $configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT,
            'find',
            'find'
        );

        $extSettings = $settings['plugin.']['tx_find.']['settings.'];

        $this->view->assign('settings', $extSettings);
        $this->view->assign('record', $record);
        $this->view->assign('uid', $uid);
    }
}