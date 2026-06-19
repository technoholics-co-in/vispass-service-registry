<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Controllers;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Technoholics\Core\SharedContracts\Response\ResponseHelper;
use Technoholics\Logger\FileLogger;
use Technoholics\ServiceRegistry\Auth\Security\SigningKeyService;
use Technoholics\ServiceRegistry\Shared\Tenant\TenantContextResolver;

#[OA\Tag(name: 'Auth', description: 'Internal service authentication')]
final class JwksController
{
    public function __construct(
        private SigningKeyService $signingKeyService,
        private FileLogger $logger
    ) {
    }

    #[OA\Get(
        path: '/.well-known/jwks.json',
        summary: 'JSON Web Key Set for service token verification',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'JWKS document'),
        ]
    )]
    public function jwks(Request $request, Response $response): Response
    {
        $this->validateTenant();

        return ResponseHelper::json($response, [
            'keys' => $this->signingKeyService->getJwks(),
        ]);
    }

    private function validateTenant(): void
    {
        TenantContextResolver::requireTenantId();
    }
}
