<?php

namespace Dla\DlaOpacNg\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\JsonResponse;

class Autocomplete implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (!isset($request->getQueryParams()['q'], $request->getQueryParams()['autocomplete'])) {
            return $response;
        }

        include_once 'EidSettings.php';

        // Configuration options
        $solr_suggest_url = $host . $core . '/suggest';
        $solr_suggest_dictionary = 'mySuggester';

        $solr_suggest_text = 'text';

        // Array of suggestions
        $suggests = [];

        // Get query string
        $query = $request->getQueryParams()['q'];

        // Get Solr suggestions
        $response = file_get_contents(
            $solr_suggest_url . '?suggest=true&suggest.dictionary=' . $solr_suggest_dictionary . '&suggest.dictionary=' . $solr_suggest_text . '&suggest=true&suggest.q=' . urlencode($query),
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
        if ($response !== FALSE) {
            $json = json_decode($response, TRUE);

            foreach ($json['suggest'][$solr_suggest_text][$query]['suggestions'] as $suggestion) {
                list ($id, $normalized) = explode('␝', $suggestion['payload']);
                $suggests[] = [
                    'id' => htmlspecialchars($suggestion['term']),
                    'term' => htmlspecialchars($suggestion['term']),
                    'normalized' => htmlspecialchars($suggestion['term']),
                    'autocomplete' => '1'
                ];
            }

            $suggests[] = [
                'id' => 'br'
            ];

            $idDeduping = [];

            foreach ($json['suggest'][$solr_suggest_dictionary][$query]['suggestions'] as $suggestion) {
                list ($id, $normalized) = explode('␝', $suggestion['payload']);
                if (!in_array($id, $idDeduping)) {
                    $suggests[] = [
                        'id' => '(entity_ids:' . $id . ' OR entity_ids_from:' . $id . ' OR entity_ids_to:' . $id . ')',
                        'term' => htmlspecialchars($suggestion['term']),
                        'normalized' => htmlspecialchars($normalized),
                        'autocomplete' => '0'
                    ];

                    $idDeduping[] = $id;
                }
            }
        }

        // Return results
        if (!empty($suggests)) {
            // Return result
            return new JsonResponse($suggests);
        }
    }
}