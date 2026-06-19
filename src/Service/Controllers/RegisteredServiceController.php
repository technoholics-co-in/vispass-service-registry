<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\Controllers;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Technoholics\Core\SharedContracts\Response\ResponseHelper;
use Technoholics\Core\SharedContracts\Validation\ValidationGroup;
use Technoholics\Exception\ValidationException;
use Technoholics\Logger\FileLogger;
use Technoholics\ServiceRegistry\Service\DTO\RegisterServiceRequestDTO;
use Technoholics\ServiceRegistry\Service\Services\RegisteredServiceService;
use Technoholics\ServiceRegistry\Shared\Tenant\TenantContextResolver;

#[OA\Tag(name: 'Services', description: 'Internal service registration and discovery')]
class RegisteredServiceController
{
    public function __construct(
        private RegisteredServiceService $service,
        private ValidatorInterface $validator,
        private FileLogger $logger
    ) {
    }

    #[OA\Post(
        path: '/services',
        summary: 'Register an internal service',
        tags: ['Services'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(ref: '#/components/schemas/RegisterServiceRequestDTO')
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 409, description: 'Service already exists'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(Request $request, Response $response): Response
    {
        $this->validateTenant();
        $data = (array) ($request->getParsedBody() ?: []);
        $dto = RegisterServiceRequestDTO::fromRequest($data);

        $violations = $this->validator->validate($dto, null, [ValidationGroup::CREATE]);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            throw new ValidationException('Validation failed', $errors);
        }

        $result = $this->service->register($dto);

        return ResponseHelper::success(
            $response,
            [
                'id' => $result['id'],
                'name' => $result['name'],
            ],
            'Service registered successfully',
            null,
            [],
            201
        );
    }

    #[OA\Get(
        path: '/services/{name}',
        summary: 'Get service metadata by name',
        tags: ['Services'],
        parameters: [
            new OA\Parameter(
                name: 'name',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'document-service')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function getByName(Request $request, Response $response, array $args): Response
    {
        $this->validateTenant();
        $name = (string) ($args['name'] ?? '');
        $result = $this->service->getByName($name);

        return ResponseHelper::success($response, $result, 'Service retrieved successfully');
    }

    private function validateTenant(): void
    {
        TenantContextResolver::requireTenantId();
    }
}
