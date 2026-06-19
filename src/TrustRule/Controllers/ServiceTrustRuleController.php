<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\TrustRule\Controllers;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Technoholics\Core\SharedContracts\Response\ResponseHelper;
use Technoholics\Core\SharedContracts\Validation\ValidationGroup;
use Technoholics\Exception\ValidationException;
use Technoholics\Logger\FileLogger;
use Technoholics\ServiceRegistry\TrustRule\DTO\CreateTrustRuleRequestDTO;
use Technoholics\ServiceRegistry\TrustRule\Services\ServiceTrustRuleService;
use Technoholics\ServiceRegistry\Shared\Tenant\TenantContextResolver;

#[OA\Tag(name: 'TrustRules', description: 'Service trust relationship management')]
class ServiceTrustRuleController
{
    public function __construct(
        private ServiceTrustRuleService $service,
        private ValidatorInterface $validator,
        private FileLogger $logger
    ) {
    }

    #[OA\Post(
        path: '/trust-rules',
        summary: 'Create a trust rule',
        tags: ['TrustRules'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(ref: '#/components/schemas/CreateTrustRuleRequestDTO')
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 409, description: 'Rule already exists'),
        ]
    )]
    public function create(Request $request, Response $response): Response
    {
        $this->validateTenant();
        $dto = CreateTrustRuleRequestDTO::fromRequest((array) ($request->getParsedBody() ?: []));

        $violations = $this->validator->validate($dto, null, [ValidationGroup::CREATE]);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            throw new ValidationException('Validation failed', $errors);
        }

        $result = $this->service->create($dto);

        return ResponseHelper::success($response, $result, 'Trust rule created successfully', null, [], 201);
    }

    #[OA\Get(
        path: '/trust-rules',
        summary: 'List trust rules',
        tags: ['TrustRules'],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
        ]
    )]
    public function list(Request $request, Response $response): Response
    {
        $this->validateTenant();
        $rules = $this->service->listAll();

        return ResponseHelper::success($response, ['rules' => $rules], 'Trust rules retrieved successfully');
    }

    #[OA\Delete(
        path: '/trust-rules/{id}',
        summary: 'Delete a trust rule',
        tags: ['TrustRules'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function delete(Request $request, Response $response, array $args): Response
    {
        $this->validateTenant();
        $id = (int) ($args['id'] ?? 0);
        $this->service->delete($id);

        return ResponseHelper::success($response, null, 'Trust rule deleted successfully');
    }

    private function validateTenant(): void
    {
        TenantContextResolver::requireTenantId();
    }
}
