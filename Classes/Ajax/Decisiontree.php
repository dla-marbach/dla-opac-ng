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

        // add parameter for "facet_names_relations" and "facet_names_roles"
        $relationField1 = $request->getQueryParams()['relation1'];
        $relationField2 = $request->getQueryParams()['relation2'];

        $fq = 'fq=NOT%20filter_hidden%3Atrue&fq=NOT%20source%3A(AU%20OR%20MM)';

        if ($activeFacets) {
            $query = $query . ' AND ' . $activeFacets;
        }



        // Get relations
        $responseField1 = file_get_contents(
            $solr_select_url . '?facet.field=' . $relationField1 . '&facet=on&facet.mincount=1&facet.prefix=' . urlencode($prefix) . '&' . $fq . '&q=' . urlencode($query) . '&rows=0',
            FALSE,
            stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'follow_location' => 0,
                    'timeout' => 1.0
                ]
            ])
        );

        $responseField2 = file_get_contents(
            $solr_select_url . '?facet.field=' . $relationField2 . '&facet=on&facet.mincount=1&facet.prefix=' . urlencode($prefix) . '&' . $fq . '&q=' . urlencode($query) . '&rows=0',
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
        if ($responseField1 !== FALSE && $responseField2 !== FALSE) {
            $jsonField1 = json_decode($responseField1, TRUE);
            $jsonField2 = json_decode($responseField2, TRUE);

            $arrayField1 = array($jsonField1['facet_counts']['facet_fields'][$relationField1]);
            $arrayField2 = array($jsonField2['facet_counts']['facet_fields'][$relationField2]);

            //    $roles = $jsonRoles['facet_counts']['facet_fields']['facet_names_roles'];

            $output = array_merge($arrayField1, $arrayField2);

        }

        // Return result
        return new JsonResponse($output);
    }
}