<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Controllers;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Technoholics\Core\SharedContracts\Response\ResponseHelper;
use Technoholics\Core\SharedContracts\Validation\ValidationGroup;
use Technoholics\Exception\ValidationException;
use Technoholics\Logger\FileLogger;
use Technoholics\ServiceRegistry\Auth\DTO\ServiceTokenRequestDTO;
use Technoholics\ServiceRegistry\Auth\Exceptions\InvalidServiceCredentialException;
use Technoholics\ServiceRegistry\Auth\Services\ServiceTokenService;
use Technoholics\ServiceRegistry\Shared\Tenant\TenantContextResolver;

#[OA\Tag(name: 'Auth', description: 'Internal service authentication')]
class ServiceTokenController
{
    public function __construct(
        private ServiceTokenService $tokenService,
        private ValidatorInterface $validator,
        private FileLogger $logger
    ) {
    }

    #[OA\Post(
        path: '/auth/token',
        summary: 'Issue a short-lived service JWT',
        tags: ['Auth'],
        security: [['serviceSecret' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(ref: '#/components/schemas/ServiceTokenRequestDTO')
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token issued'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 403, description: 'Scope or trust denied'),
        ]
    )]
    public function issueToken(Request $request, Response $response): Response
    {
        $this->validateTenant();
        $secret = $this->extractBearerSecret($request);
        $dto = ServiceTokenRequestDTO::fromRequest((array) ($request->getParsedBody() ?: []));

        $violations = $this->validator->validate($dto, null, [ValidationGroup::CREATE]);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            throw new ValidationException('Validation failed', $errors);
        }

        $result = $this->tokenService->issueToken($dto, $secret);

        $this->logger->info('Service token issued', [
            'service' => $dto->service,
            'targetService' => $dto->targetService,
        ]);

        return ResponseHelper::success($response, [
            'token' => $result['token'],
            'expiresIn' => $result['expiresIn'],
        ], 'Token issued successfully');
    }

    private function extractBearerSecret(Request $request): string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (preg_match('/^Bearer\s+(\S+)$/i', $authHeader, $matches) !== 1) {
            throw new InvalidServiceCredentialException('unknown');
        }

        return $matches[1];
    }

    private function validateTenant(): void
    {
        TenantContextResolver::requireTenantId();
    }
}
