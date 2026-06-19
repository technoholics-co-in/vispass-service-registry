<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Scope\Controllers;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Technoholics\Core\SharedContracts\Response\ResponseHelper;
use Technoholics\Core\SharedContracts\Validation\ValidationGroup;
use Technoholics\Exception\ValidationException;
use Technoholics\Logger\FileLogger;
use Technoholics\ServiceRegistry\Scope\DTO\AddServiceScopeRequestDTO;
use Technoholics\ServiceRegistry\Scope\Services\ServiceScopeService;
use Technoholics\ServiceRegistry\Shared\Tenant\TenantContextResolver;

#[OA\Tag(name: 'ServiceScopes', description: 'Service scope management')]
class ServiceScopeController
{
    public function __construct(
        private ServiceScopeService $service,
        private ValidatorInterface $validator,
        private FileLogger $logger
    ) {
    }

    #[OA\Post(
        path: '/services/{service}/scopes',
        summary: 'Assign a scope to a service',
        tags: ['ServiceScopes'],
        parameters: [
            new OA\Parameter(
                name: 'service',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'document-service')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(ref: '#/components/schemas/AddServiceScopeRequestDTO')
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 409, description: 'Scope already exists'),
        ]
    )]
    public function add(Request $request, Response $response, array $args): Response
    {
        $this->validateTenant();
        $serviceName = (string) ($args['service'] ?? '');
        $dto = AddServiceScopeRequestDTO::fromRequest((array) ($request->getParsedBody() ?: []));

        $violations = $this->validator->validate($dto, null, [ValidationGroup::CREATE]);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            throw new ValidationException('Validation failed', $errors);
        }

        $result = $this->service->addScope($serviceName, $dto);

        return ResponseHelper::success($response, $result, 'Scope assigned successfully', null, [], 201);
    }

    #[OA\Get(
        path: '/services/{service}/scopes',
        summary: 'List scopes for a service',
        tags: ['ServiceScopes'],
        parameters: [
            new OA\Parameter(
                name: 'service',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'document-service')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
        ]
    )]
    public function list(Request $request, Response $response, array $args): Response
    {
        $this->validateTenant();
        $serviceName = (string) ($args['service'] ?? '');
        $scopes = $this->service->listScopes($serviceName);

        return ResponseHelper::success($response, ['scopes' => $scopes], 'Scopes retrieved successfully');
    }

    #[OA\Delete(
        path: '/services/{service}/scopes/{scope}',
        summary: 'Remove a scope from a service',
        tags: ['ServiceScopes'],
        parameters: [
            new OA\Parameter(name: 'service', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'scope', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function delete(Request $request, Response $response, array $args): Response
    {
        $this->validateTenant();
        $serviceName = (string) ($args['service'] ?? '');
        $scope = urldecode((string) ($args['scope'] ?? ''));

        $this->service->removeScope($serviceName, $scope);

        return ResponseHelper::success($response, null, 'Scope removed successfully');
    }

    private function validateTenant(): void
    {
        TenantContextResolver::requireTenantId();
    }
}
