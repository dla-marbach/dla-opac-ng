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
        if (!isset($request->getQueryParams()['q'], $request->getQueryParams()['p'], $request->getQueryParams()['decisiontree'])) {
            return $handler->handle($request);
        }

        include_once 'EidSettings.php';

        // Configuration options
        $solr_select_url = $host . $core . '/select';

        // Array of entity facts
        $entity = [];

        // Get query string
        $queryParams = $request->getQueryParams();
        $query = $queryParams['q'];

        $prefix = $queryParams['p'];

        $activeFacets = $queryParams['activeFacets'] ?? '';

        // add parameter for "filterAuthorityRelation_mv" and "filterAuthorityRole_mv"
        $relationField1 = trim((string)($queryParams['relation1'] ?? ''));
        $relationField2 = trim((string)($queryParams['relation2'] ?? ''));

        if ($relationField1 === '') {
            return new JsonResponse([]);
        }

        $fq = 'fq=NOT%20source%3A(AU%20OR%20MM)';

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

        $output = [];

        // Parse JSON response
        if ($responseField1 !== FALSE) {
            $jsonField1 = json_decode($responseField1, TRUE);
            $arrayField1 = $jsonField1['facet_counts']['facet_fields'][$relationField1] ?? [];
            $output[] = $arrayField1;
        }

        if ($relationField2 !== '') {
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

            if ($responseField2 !== FALSE) {
                $jsonField2 = json_decode($responseField2, TRUE);
                $arrayField2 = $jsonField2['facet_counts']['facet_fields'][$relationField2] ?? [];
                $output[] = $arrayField2;
            }
        }

        // Return result
        return new JsonResponse($output);
    }
}