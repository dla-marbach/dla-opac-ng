<?php

namespace Dla\DlaOpacNg\Ajax;

use Dla\DlaOpacNg\Service\EntityService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\JsonResponse;

class GetEntities implements MiddlewareInterface
{
    private EntityService $entityService;

    public function __construct(EntityService $entityService)
    {
        $this->entityService = $entityService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!isset($request->getQueryParams()['q'], $request->getQueryParams()['getEntities'])) {
            return $handler->handle($request);
        }

        $entities = $this->entityService->getEntities($request->getQueryParams()['q']);

        // Return result
        return new JsonResponse($entities);
    }
}
