<?php
namespace Dla\DlaOpacNg\Controller;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class StartController extends \Subugoe\Find\Controller\SearchController
{

    public function initializeAction()
    {
        $settings = $this->configurationManager->getConfiguration(
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
        return $this->htmlResponse();
    }
}