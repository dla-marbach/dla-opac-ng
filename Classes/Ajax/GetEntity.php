<?php

namespace Dla\DlaOpacNg\Ajax;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\JsonResponse;

class GetEntity implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (!isset($request->getQueryParams()['q'], $request->getQueryParams()['getEntity'])) {
            return $response;
        }

        include_once 'EidSettings.php';

        // Configuration options
        $solr_select_url = $host . $core . '/select';

        // Array of entity facts
        $entity = [];

        // Get query string
        $query = $request->getQueryParams()['q'];

        // Get Solr record
        $response = file_get_contents(
            $solr_select_url . '?q=' . urlencode('id:(' . $query . ')') . '&rows=1',
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
            $entity = [
                'id' => htmlspecialchars($json['response']['docs'][0]['id']),
                'listview_title' => !empty($json['response']['docs'][0]['listview_title']) ? htmlspecialchars($json['response']['docs'][0]['listview_title']) : '',
                'listview_associate' => !empty($json['response']['docs'][0]['listview_associate']) ? array_map('htmlspecialchars', $json['response']['docs'][0]['listview_associate']) : '',
                'listview_additional1' => !empty($json['response']['docs'][0]['listview_additional1']) ? array_map('htmlspecialchars', $json['response']['docs'][0]['listview_additional1']) : '',
                'listview_additional2' => !empty($json['response']['docs'][0]['listview_additional2']) ? array_map('htmlspecialchars', $json['response']['docs'][0]['listview_additional2']) : '',
                'picture_midi' => !empty($json['response']['docs'][0]['picture_midi']) ? $json['response']['docs'][0]['picture_midi'] : '',
            ];
        }

        // Return result
        return new JsonResponse($entity);
    }

}