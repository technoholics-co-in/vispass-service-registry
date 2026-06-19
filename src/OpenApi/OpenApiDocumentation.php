<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\OpenApi;

use OpenApi\Attributes as OA;

/**
 * OpenAPI root metadata for swagger-php scan.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'Service Registry API',
    description: 'Centralized service identity and authorization registry for internal microservices.'
)]
#[OA\Server(url: '/', description: 'Service root')]
class OpenApiDocumentation
{
}
