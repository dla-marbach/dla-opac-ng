<?php

namespace Dla\DlaOpacNg\Ajax;

use Dla\DlaOpacNg\Service\CollectionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\JsonResponse;

class Collection implements MiddlewareInterface
{
    private CollectionService $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!isset($request->getQueryParams()['type'], $request->getQueryParams()['action'], $request->getQueryParams()['collection'])) {
            return $handler->handle($request);
        }

        $action = $request->getQueryParams()['action'] ?? '';
        $nodeId = $request->getQueryParams()['nodeid'] ?? '';
        $search = $request->getQueryParams()['search'] ?? '';
        $type = $request->getQueryParams()['type'] ?? '';

        $filter = $request->getParsedBody()['filterIds'] ?? '';

        // set field select
        if ($type == 'classification') {
            $this->collectionService->useClassification();
        } else {
            $this->collectionService->useCollection();
        }

        // Prepare JSON for jTree
        $jTree = [];

        if ($action == 'getNodes') {
            $jTree = $this->collectionService->getNodes($filter, $nodeId);
        } else if ($action == 'searchNodes') {
            $jTree = $this->collectionService->searchNodes($search);
        } else if ($action == 'getAllParents') {
            $jTree = $this->collectionService->getAllParents($nodeId);
        } else if ($action == 'getStructure') {
            $jTree = $this->collectionService->getStructure($nodeId);
        }

        // Return result
        return new JsonResponse($jTree);
    }
}
