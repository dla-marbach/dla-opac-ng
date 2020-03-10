<?php
namespace Dla\DlaOpacNg\Controller;

class StartController extends \Subugoe\Find\Controller\SearchController
{

    public function initializeAction()
    {

        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager */

        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        $configurationManager = $objectManager->get('TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface');
        $settings = $configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT,
            'find',
            'find'
        );

        $this->settings = $settings['plugin.']['tx_find.']['settings.'];

    }

    /**
     * Start Action.
     */
    public function startAction()
    {

    }
}