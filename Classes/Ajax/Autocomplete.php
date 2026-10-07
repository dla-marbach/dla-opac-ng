<?php

namespace Dla\DlaOpacNg\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Dla\DlaOpacNg\Service\SolrConnection;
use TYPO3\CMS\Core\Http\JsonResponse;

class Autocomplete implements MiddlewareInterface
{
    public function __construct(
        private readonly SolrConnection $solrConnection = new SolrConnection()
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        if (!isset($queryParams['q'], $queryParams['autocomplete'])) {
            return $handler->handle($request);
        }

        $solr_suggest_dictionary = 'mySuggester';
        $solr_suggest_text = 'text';

        $suggests = [];
        $query = trim((string)$queryParams['q']);

        // Leere/Whitespace-Query: gültige leere JSON-Response zurückgeben
        if ($query === '') {
            return new JsonResponse($suggests);
        }

        $json = $this->solrConnection->request('suggest', [
            'suggest' => 'true',
            'suggest.dictionary' => [$solr_suggest_dictionary, $solr_suggest_text],
            'suggest.q' => $query,
        ]);

        if ($json !== null) {
            if (isset($json['suggest'][$solr_suggest_text][$query]['suggestions']) && is_array($json['suggest'][$solr_suggest_text][$query]['suggestions'])) {
                foreach ($json['suggest'][$solr_suggest_text][$query]['suggestions'] as $suggestion) {
                    $suggests[] = [
                        'id' => htmlspecialchars((string)$suggestion['term']),
                        'term' => htmlspecialchars((string)$suggestion['term']),
                        'normalized' => htmlspecialchars((string)$suggestion['term']),
                        'autocomplete' => '1',
                    ];
                }
            }

            if (!empty($suggests)) {
                $suggests[] = ['id' => 'br'];
            }

            $idDeduping = [];

            if (isset($json['suggest'][$solr_suggest_dictionary][$query]['suggestions']) && is_array($json['suggest'][$solr_suggest_dictionary][$query]['suggestions'])) {
                foreach ($json['suggest'][$solr_suggest_dictionary][$query]['suggestions'] as $suggestion) {
                    if (empty($suggestion['payload'])) {
                        continue;
                    }

                    [$id, $normalized] = array_pad(explode('␝', (string)$suggestion['payload'], 2), 2, '');

                    if ($id !== '' && !in_array($id, $idDeduping, true)) {
                        $suggests[] = [
                            'id' => 'searchEntity_id_mv:' . $id,
                            'term' => htmlspecialchars((string)$suggestion['term']),
                            'normalized' => htmlspecialchars((string)$normalized),
                            'autocomplete' => '0',
                        ];

                        $idDeduping[] = $id;
                    }
                }
            }
        }

        // IMMER ResponseInterface zurückgeben
        return new JsonResponse($suggests);
    }
}
