<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Health\Controllers;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Technoholics\Core\SharedContracts\Response\ResponseHelper;
use Technoholics\Logger\FileLogger;
use Technoholics\ServiceRegistry\Health\Services\HealthCheckService;

#[OA\Tag(name: 'Health', description: 'Service health')]
final class HealthController
{
    public function __construct(
        private HealthCheckService $healthCheckService,
        private FileLogger $logger
    ) {
    }

    #[OA\Get(path: '/health', summary: 'Health check', tags: ['Health'])]
    #[OA\Response(response: 200, description: 'All dependencies healthy')]
    #[OA\Response(response: 503, description: 'One or more dependencies unhealthy')]
    public function ping(Request $request, Response $response): Response
    {
        $result = $this->healthCheckService->check();
        $statusCode = $this->healthCheckService->isHealthy() ? 200 : 503;

        return ResponseHelper::success($response, $result, 'Health check', null, [], $statusCode);
    }
}
