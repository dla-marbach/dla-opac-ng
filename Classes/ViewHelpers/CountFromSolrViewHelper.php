<?php
namespace Dla\DlaOpacNg\ViewHelpers;

/***************************************************************
 *
 *  Copyright notice
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
 ***************************************************************/

use Solarium\QueryType\Select\Result\Result;
use Solarium\QueryType\Update\Query\Document\DocumentInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Dla\DlaOpacNg\Service\SolrConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Solarium\Exception\HttpException;

/**
 * FromSolrViewHelper
 *
 * Gets a field value from a Solr record
 *
 */
class CountFromSolrViewHelper extends AbstractViewHelper {

    /**
     * @var \Solarium\Client
     */
    protected $solr;

    public function initialize() {
        $this->solr = GeneralUtility::makeInstance(SolrConnection::class)->getClient();
    }

    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments() {
        parent::initializeArguments();
        $this->registerArgument('query', 'string|array', 'Solr querystring or array of query fields and their query values.', TRUE);
        $this->registerArgument('activeFacets',  'array', 'Array with active facets', FALSE);
    }

    /**
     */
    public function render() {

        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $findParameter = $request?->getParsedBody()['tx_find_find'] ?? $request?->getQueryParams()['tx_find_find'] ?? null;

        $activeFacets = $this->arguments['activeFacets'];
        $queryConcat = $this->arguments['queryConcat'] ?? '';

        $newQuery = $this->arguments['query'];

        if (!empty($findParameter['q']['default'])) {
            $newQuery = $newQuery . ' AND ' . $findParameter['q']['default'];
        }

        if ($activeFacets) {
            foreach ($activeFacets as $facetInfo) {
                foreach ($facetInfo as $facet) {
                    $newQuery = $newQuery . " AND " . $facet['query'];
                }
            }
        }

        if ($queryConcat) {
            $newQuery .= " AND " . $queryConcat;
        }

        if ($this->isInvalidQuery($newQuery)) {
            $this->setSolrCount(0);
            return;
        }

        $query = $this->createQuery($newQuery);

        $query->setRows(0);


        try {
            /** @var Result $resultSet */
            $resultSet = $this->solr->select($query);
            $this->setSolrCount((int)$resultSet->getNumFound());
        } catch (HttpException $exception) {
            $this->setSolrCount(0);
        }

    }

    private function isInvalidQuery($query) {
        if (!is_string($query) || trim($query) === '') {
            return true;
        }

        return preg_match('/\(\s*\)/', $query) === 1;
    }

    private function setSolrCount($resultValue) {
        if ($this->templateVariableContainer->exists("solrcount")) {
            $this->templateVariableContainer->remove("solrcount");
        }
        $this->templateVariableContainer->add("solrcount", $resultValue);
    }

    /**
     * Check configuration for shards and when found create Distributed Search
     *
     * @param \Solarium\QueryType\Select\Query\Query $query
     */
    private function createQueryComponents(&$query) {

        // Shards
        if (!empty($this->templateVariableContainer->get('settings')['shards'])) {
            if(count($this->templateVariableContainer->get('settings')['shards'])) {
                $distributedSearch = $query->getDistributedSearch();
                foreach($this->templateVariableContainer->get('settings')['shards'] as $name => $shard) {
                    $distributedSearch->addShard($name, $shard);
                }
            }
        }
    }

    /**
     * Adds filter queries configured in TypoScript to $query.
     *
     * @param \Solarium\QueryType\Select\Query\Query $query
     */
    private function addTypoScriptFilters ($query) {
        if (!empty($this->templateVariableContainer->get('settings')['additionalFilters'])) {
            foreach($this->templateVariableContainer->get('settings')['additionalFilters'] as $key => $filterQuery) {
                $query->createFilterQuery('additionalFilter-' . $key)
                    ->setQuery($filterQuery);
            }
        }
    }

    /**
     * Creates a query for a document
     *
     * @param string $id the document id
     * @param string $idfield the document id field
     * @return \Solarium\QueryType\Select\Query\Query
     */
    private function createQuery ($query) {

        $queryObject = $this->solr->createSelect();
        $this->addTypoScriptFilters($queryObject);

        $queryObject->setQuery($query);

        $this->createQueryComponents($queryObject);

        return $queryObject;
    }
}