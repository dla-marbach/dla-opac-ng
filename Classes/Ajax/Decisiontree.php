<?php

namespace Dla\DlaOpacNg\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Dla\DlaOpacNg\Service\SolrConnection;
use TYPO3\CMS\Core\Http\JsonResponse;

class Decisiontree implements MiddlewareInterface
{
    public function __construct(
        private readonly SolrConnection $solrConnection = new SolrConnection()
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!isset($request->getQueryParams()['q'], $request->getQueryParams()['p'], $request->getQueryParams()['decisiontree'])) {
            return $handler->handle($request);
        }

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

        if ($activeFacets) {
            $query = $query . ' AND ' . $activeFacets;
        }

        $output = [];

        // Get relations
        foreach (array_filter([$relationField1, $relationField2], static fn(string $field): bool => $field !== '') as $relationField) {
            $json = $this->solrConnection->request('select', [
                'facet.field' => $relationField,
                'facet' => 'on',
                'facet.mincount' => 1,
                'facet.prefix' => $prefix,
                'fq' => 'NOT source:(AU OR MM)',
                'q' => $query,
                'rows' => 0,
            ]);
            if ($json !== null) {
                $output[] = $json['facet_counts']['facet_fields'][$relationField] ?? [];
            }
        }

        // Return result
        return new JsonResponse($output);
    }
}