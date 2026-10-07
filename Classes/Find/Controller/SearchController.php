<?php

namespace Dla\Find\Controller;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2013
 *      Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
 *      Sven-S. Porst
 *      Göttingen State and University Library
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

use Psr\Http\Message\ResponseInterface;
use Dla\Find\Service\ServiceProviderInterface;
use Dla\Find\Utility\ArrayUtility;
use Dla\Find\Utility\FrontendUtility;
use Dla\Find\Utility\SearchLimitUtility;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Utility\ArrayUtility as CoreArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class SearchController extends ActionController
{
    protected array $requestArguments = [];

    protected ?object $searchProvider = null;

    private \Psr\Log\LoggerInterface $logger;

    public function __construct(LogManagerInterface $logManager)
    {
        $this->logger = $logManager->getLogger('find');
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function detailAction(string $id): ResponseInterface
    {
        $arguments = $this->searchProvider->getRequestArguments();
        $detail = $this->searchProvider->getDocumentById($id);

        if (isset($this->requestArguments['underlyingQuery']) && is_array($this->requestArguments['underlyingQuery'])) {
            $underlyingQueryInfo = $this->requestArguments['underlyingQuery'];
            $assetCollector = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\AssetCollector::class);
            $assetCollector->addInlineJavaScript(
                'my_identifier',
                FrontendUtility::addQueryInformationAsJavaScript(
                    $underlyingQueryInfo['q'] ?? [],
                    $this->settings,
                    (int) $underlyingQueryInfo['position'],
                    $arguments
                ),
                [],
                ['priority' => true]
            );
//            $this->response->addAdditionalHeaderData(
//                FrontendUtility::addQueryInformationAsJavaScript(
//                    $underlyingQueryInfo['q'],
//                    $this->settings,
//                    (int) $underlyingQueryInfo['position'],
//                    $arguments
//                )
//            );
        }

        $this->addStandardAssignments();

        $this->view->assignMultiple($detail);
        $this->view->assignMultiple([
            'arguments' => $arguments,
            'config' => $this->searchProvider->getConfiguration(),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Index Action.
     */
    public function indexAction(): ResponseInterface
    {
        if (array_key_exists('id', $this->requestArguments)) {
            return $this->redirect('detail', NULL, NULL, $this->requestArguments);
        } else {
            $this->searchProvider->setCounter();

            if ([] !== ($this->requestArguments['facet'] ?? [])
                || SearchLimitUtility::getOffset($this->requestArguments, $this->settings) > 0
            ) {
                // Gefilterte Trefferlisten und Folgeseiten nicht indexieren und deren Links nicht folgen.
                GeneralUtility::makeInstance(MetaTagManagerRegistry::class)
                    ->getManagerForProperty('robots')
                    ->addProperty('robots', 'noindex, nofollow', [], true);
            }

            $assetCollector = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\AssetCollector::class);
            $assetCollector->addInlineJavaScript(
                'my_identifier',
                FrontendUtility::addQueryInformationAsJavaScript(
                    $this->searchProvider->getRequestArguments()['q'] ?? [],
                    $this->settings,
                    null,
                    $this->searchProvider->getRequestArguments()
                ),
                [],
                ['priority' => true]
            );

            $this->addStandardAssignments();
            $defaultQuery = $this->searchProvider->getDefaultQuery();
            $defaultResultSet = $defaultQuery['results'] ?? null;

            // redirect to detail if only one item found and search is configured to redirect
            if ($defaultResultSet !== null && $defaultResultSet->getNumFound() === 1) {
                $firstDocId = $defaultResultSet->getData()['response']['docs'][0]['id'] ?? null;
                $redirectQueries = [];
                if ($this->settings['redirectAllOneHitToDetail']) {
                    if ($firstDocId !== null) {
                        return $this->redirect('detail', NULL, NULL, ['id' => $firstDocId]);
                    }
                } else {
                    foreach ($this->settings['queryFields'] as $querySettings) {
                        if ($querySettings['redirectToDetail']) {
                            $redirectQueries[$querySettings['id']] = 1;
                        }
                    }

                    foreach (($this->requestArguments['q'] ?? []) as $queryId => $queryTerm) {
                        if ($firstDocId !== null && array_key_exists($queryId, $redirectQueries)) {
                            return $this->redirect('detail', NULL, NULL, ['id' => $firstDocId]);
                        }
                    }
                }
            }

            $viewValues = [
                'arguments' => $this->searchProvider->getRequestArguments(),
                'config' => $this->searchProvider->getConfiguration(),
            ];

            CoreArrayUtility::mergeRecursiveWithOverrule($viewValues, $defaultQuery);
            $this->view->assignMultiple($viewValues);
        }
        return $this->htmlResponse();
    }

    /**
     * Initialisation and setup.
     */
    protected function initializeAction()
    {
        ksort($this->settings['queryFields']);

        $this->requestArguments = $this->request->getArguments();
        $this->requestArguments = ArrayUtility::cleanArgumentsArray($this->requestArguments);

        // Limits prüfen, bevor eine Verbindung zu Solr aufgebaut wird: zu tiefes Blättern und zu viele
        // Filter werden ohne Solr-Anfrage abgewiesen.
        $this->enforceSearchLimits();

        $this->initializeConnection($this->settings['activeConnection']);

        $this->searchProvider->setRequestArguments($this->requestArguments);
        $this->searchProvider->setAction($this->request->getControllerActionName());
        $this->searchProvider->setControllerExtensionKey($this->request->getControllerExtensionKey());
    }

    /**
     * Weist Trefferlisten-Anfragen jenseits der Limits (siehe SearchLimitUtility) mit HTTP 400 ab.
     * Auf Detailseiten wird eine zu tiefe »underlyingQuery« (Blättern von Treffer zu Treffer) verworfen,
     * die Detailseite selbst aber normal angezeigt.
     *
     * @throws PropagateResponseException
     */
    protected function enforceSearchLimits(): void
    {
        $action = $this->request->getControllerActionName();

        if ('detail' === $action) {
            $underlyingQuery = $this->requestArguments['underlyingQuery'] ?? null;
            if (null !== $underlyingQuery
                && (!is_array($underlyingQuery)
                    || null !== SearchLimitUtility::getUnderlyingQueryViolation($underlyingQuery, $this->settings))
            ) {
                unset($this->requestArguments['underlyingQuery']);
            }

            return;
        }

        if ('index' !== $action || array_key_exists('id', $this->requestArguments)) {
            return;
        }

        $violation = SearchLimitUtility::getViolation($this->requestArguments, $this->settings);
        if (null === $violation) {
            return;
        }

        $this->logger->info('Search request rejected: search limit exceeded.', [
            'violation' => $violation,
            'offset' => SearchLimitUtility::getOffset($this->requestArguments, $this->settings),
            'count' => SearchLimitUtility::getCount($this->requestArguments, $this->settings),
            'activeFilters' => SearchLimitUtility::countActiveFilters($this->requestArguments),
        ]);

        throw new PropagateResponseException($this->buildSearchLimitResponse($violation), 1791331200);
    }

    /**
     * Schlanke Fehlerantwort (HTTP 400) für überschrittene Limits; JSON für den Datenpfad (format=data).
     *
     * HTTP 400 statt 404: Die Suchseite existiert, nur die Parameter (Seite/Offset, Anzahl Filter) liegen
     * außerhalb des erlaubten Bereichs. 404 würde fälschlich »Ressource nicht vorhanden« signalisieren.
     */
    protected function buildSearchLimitResponse(string $violation): \Psr\Http\Message\ResponseInterface
    {
        $status = 400;
        $headers = ['X-Robots-Tag' => 'noindex, nofollow'];

        if (SearchLimitUtility::VIOLATION_ACTIVE_FILTERS === $violation) {
            $limit = SearchLimitUtility::getMaxActiveFilters($this->settings);
            $messageKey = 'searchLimit.activeFilters';
        } else {
            $limit = SearchLimitUtility::getMaxResultWindow($this->requestArguments, $this->settings);
            $messageKey = SearchLimitUtility::isEmptyQuery($this->requestArguments, $this->settings)
                ? 'searchLimit.resultWindowEmptyQuery'
                : 'searchLimit.resultWindow';
        }

        $title = $this->translate('searchLimit.title');
        $message = $this->translate($messageKey, [$limit]);

        if ('data' === $this->request->getFormat()) {
            return new JsonResponse([
                'error' => 'searchLimitExceeded',
                'reason' => $violation,
                'limit' => $limit,
                'message' => $message,
            ], $status, $headers);
        }

        $html = '<!DOCTYPE html>' . "\n"
            . '<html><head><meta charset="utf-8">'
            . '<meta name="robots" content="noindex, nofollow">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . htmlspecialchars($title) . '</title></head>'
            . '<body><main>'
            . '<h1>' . htmlspecialchars($title) . '</h1>'
            . '<p>' . htmlspecialchars($message) . '</p>'
            . '</main></body></html>';

        return new HtmlResponse($html, $status, $headers);
    }

    protected function translate(string $key, ?array $arguments = null): string
    {
        return LocalizationUtility::translate($key, 'dla_opac_ng', $arguments) ?? $key;
    }

    /**
     * Suggest/Autocomplete action.
     */
    public function suggestAction()
    {
        $results = $this->searchProvider->suggestQuery($this->searchProvider->getRequestArguments());
        $this->view->assign('suggestions', $results);
    }

    /**
     * Assigns standard variables to the view.
     */
    protected function addStandardAssignments()
    {
        $this->searchProvider->setConfigurationValue('extendedSearch', $this->searchProvider->isExtendedSearch());
        $this->searchProvider->setConfigurationValue(
            'uid',
            $this->configurationManager->getContentObject()->data['uid']
        );
        $this->searchProvider->setConfigurationValue('prefixID', 'tx_find_find');
        $this->searchProvider->setConfigurationValue('pageTitle', $GLOBALS['TSFE']->page['title']);
    }

    /**
     * @param string $activeConnection
     */
    protected function initializeConnection($activeConnection)
    {
        $connectionConfiguration = $this->settings['connections'][$activeConnection];

        /* @var ServiceProviderInterface $searchProvider */
        $this->searchProvider = GeneralUtility::makeInstance($connectionConfiguration['provider'], $activeConnection,
            $this->settings);
        $this->searchProvider->connect();
    }
}
