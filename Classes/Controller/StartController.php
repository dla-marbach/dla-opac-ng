<?php
namespace Dla\DlaOpacNg\Controller;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class StartController extends \Dla\Find\Controller\SearchController
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
        $queryParams = $this->request->getQueryParams();
        $findParams = $queryParams['tx_find_find'] ?? [];
        $formFacets = is_array($findParams) ? ($findParams['facet'] ?? []) : [];

        $this->view->assign(
            'formFacets',
            is_array($formFacets) ? array_filter($formFacets, 'is_array') : []
        );

        return $this->htmlResponse();
    }
}