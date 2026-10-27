<?php
namespace Dla\DlaOpacNg\Controller;

use Dla\DlaOpacNg\Statistics\StatisticsCollector;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class CollectionController extends ActionController
{

    /**
     * Index Action. Parameter tx_dlaopacng_dlacollection
     * @param string $record
     * @param string $uid
     * @param string $search
     */
    public function indexAction(string $record = '', string $uid = '', string $search = '')
    {
        GeneralUtility::makeInstance(StatisticsCollector::class)
            ->recordTree(StatisticsCollector::AREA_COLLECTION, $record, $uid, $search);

        $settings = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT,
            'find',
            'find'
        );


        $extSettings = $settings['plugin.']['tx_find.']['settings.'];

        $this->view->assign('settings', $extSettings);
        $this->view->assign('record', $record);
        $this->view->assign('uid', $uid);
        $this->view->assign('search', $search);

        return $this->htmlResponse();
    }
}