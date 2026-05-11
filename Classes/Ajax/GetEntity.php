<?php

namespace Dla\DlaOpacNg\Ajax;


use Dla\DlaOpacNg\Service\EntityService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\JsonResponse;

class GetEntity implements MiddlewareInterface
{
    private EntityService $entityService;

    public function __construct(EntityService $entityService)
    {
        $this->entityService = $entityService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!isset($request->getQueryParams()['q'], $request->getQueryParams()['getEntity'])) {
            return $handler->handle($request);
        }

        $entity = $this->entityService->getEntity($request->getQueryParams()['q']);

        // Return result
        return new JsonResponse($entity);
    }

}