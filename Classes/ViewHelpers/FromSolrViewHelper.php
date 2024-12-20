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
use Symfony\Component\EventDispatcher\EventDispatcher;
use Solarium\Core\Client\Adapter\Curl;

/**
 * FromSolrViewHelper
 *
 * Gets a field value from a Solr record
 *
 */
class FromSolrViewHelper extends AbstractViewHelper {

    /**
     * @var \Solarium\Client
     */
    protected $solr;

    public function initialize() {
        $configuration = array(
            'endpoint' => array(
                'localhost' => array(
                    'host' => $this->templateVariableContainer->get('settings')['connection']['host'],
                    'port' => intval($this->templateVariableContainer->get('settings')['connection']['port']),
                    'path' => $this->templateVariableContainer->get('settings')['connection']['path'],
                    'timeout' => $this->templateVariableContainer->get('settings')['connection']['timeout'],
                    'scheme' => $this->templateVariableContainer->get('settings')['connection']['scheme'],
                    'core' => $this->templateVariableContainer->get('settings')['connection']['core'],
                )
            )
        );

        $adapter = new Curl();

        $this->solr = new \Solarium\Client($adapter, new EventDispatcher, $configuration);
    }

    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments() {
        parent::initializeArguments();
        $this->registerArgument('query', 'string|array', 'Solr querystring or array of query fields and their query values.', TRUE);
        $this->registerArgument('operator', 'string', 'Solr query operator.', FALSE, 'AND');
        $this->registerArgument('sortField', 'string', 'Sort field.', FALSE);
        $this->registerArgument('sortOrder', 'string', 'Sort order ("asc" or "desc").', FALSE, 'asc');
        $this->registerArgument('rows', 'integer', 'Number of rows to be returned.', FALSE);
        $this->registerArgument('fields', 'string', 'Fields to be returned, comma seperated if more than one field.', FALSE);
    }

    /**
     */
    public function render() {

        switch(gettype($this->arguments['query'])) {
            case 'string':
                $query = $this->createQuery($this->arguments['query']);
                break;
            case 'array':
                $query = $this->createQuery(implode(' ' . $this->arguments['operator'] . ' ', array_map( function($k,$v) { return $k . ':' . $v; }, array_keys($this->arguments['query']), array_values($this->arguments['query']))));
                break;
            default:
                $query = $this->createQuery('*:*');
        }

        if(!is_null($this->arguments['sortField'])) {
            $query->addSort($this->arguments['sortField'], $this->arguments['sortOrder']);
        }

        if(!is_null($this->arguments['rows'])) {
            $query->setRows($this->arguments['rows']);
        }

        if(!is_null($this->arguments['fields'])) {
            $query->clearFields();
            $query->addFields($this->arguments['fields']);
        }

        /** @var Result $resultSet */
        $resultSet = $this->solr->select($query);

        /** @var DocumentInterface $result */
        $results = $resultSet->getDocuments();

        if ($this->templateVariableContainer->exists("solr")) {
            $this->templateVariableContainer->remove("solr");
        }
        $this->templateVariableContainer->add("solr", $results);


    }

    /**
     * Check configuration for shards and when found create Distributed Search
     *
     * @param \Solarium\QueryType\Select\Query\Query $query
     */
    private function createQueryComponents(&$query) {

        // Shards
        if (!empty($this->templateVariableContainer->get('settings')['shards'])) {
            if (is_array($this->templateVariableContainer->get('settings')['shards']) || is_object($this->templateVariableContainer->get('settings')['shards'])) {
                if (count($this->templateVariableContainer->get('settings')['shards'])) {
                    $distributedSearch = $query->getDistributedSearch();
                    foreach ($this->templateVariableContainer->get('settings')['shards'] as $name => $shard) {
                        $distributedSearch->addShard($name, $shard);
                    }
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

        $this->configuration['solarium'] = $queryObject;

        return $this->configuration['solarium'];
    }
}