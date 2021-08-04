<?php

namespace Dla\DlaOpacNg\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\JsonResponse;

class Decisiontree implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (!isset($request->getQueryParams()['q'], $request->getQueryParams()['p'], $request->getQueryParams()['decisiontree'])) {
            return $response;
        }

        include_once 'EidSettings.php';

        // Configuration options
        $solr_select_url = $host . $core . '/select';

        // Array of entity facts
        $entity = [];

        // Get query string
        $query = $request->getQueryParams()['q'];

        $prefix = $request->getQueryParams()['p'];

        $activeFacets = $request->getQueryParams()['activeFacets'];

        if ($activeFacets) {
            $query = $query . ' AND ' . $activeFacets;
        }

        // Get relations
        $responseRelations = file_get_contents(
            $solr_select_url . '?facet.field=facet_names_relations&facet.mincount=1&facet=on&facet.prefix=' . urlencode($prefix) . '&fq=NOT%20filter_hidden%3Atrue&fq=NOT%20source%3A(AU%20OR%20MM)&q=' . urlencode($query) . '&rows=0',
            FALSE,
            stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'follow_location' => 0,
                    'timeout' => 1.0
                ]
            ])
        );

        $responseRoles = file_get_contents(
            $solr_select_url . '?facet.field=facet_names_roles&facet=on&facet.mincount=1&facet.prefix=' . urlencode($prefix) . '&fq=NOT%20filter_hidden%3Atrue&fq=NOT%20source%3A(AU%20OR%20MM)&q=' . urlencode($query) . '&rows=0',
            FALSE,
            stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'follow_location' => 0,
                    'timeout' => 1.0
                ]
            ])
        );

        // Parse JSON response
        if ($responseRelations !== FALSE && $responseRoles !== FALSE) {
            $jsonRelations = json_decode($responseRelations, TRUE);
            $jsonRoles = json_decode($responseRoles, TRUE);

            $relations = array($jsonRelations['facet_counts']['facet_fields']['facet_names_relations']);
            $roles = array($jsonRoles['facet_counts']['facet_fields']['facet_names_roles']);

            //    $roles = $jsonRoles['facet_counts']['facet_fields']['facet_names_roles'];

            $output = array_merge($relations, $roles);

        }

        // Return result
        return new JsonResponse($output);
    }
}