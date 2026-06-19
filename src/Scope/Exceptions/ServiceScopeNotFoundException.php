<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Scope\Exceptions;

use Technoholics\Core\SharedContracts\Constants\ResourceName;
use Technoholics\ServiceRegistry\Shared\Exceptions\RegistryNotFoundException;

/**
 * Scope not found for service.
 */
class ServiceScopeNotFoundException extends RegistryNotFoundException
{
    public function __construct(
        string $serviceName,
        string $scope,
        array $details = [],
        ?string $messageKey = null,
        ?string $docUrl = null,
        array $context = []
    ) {
        parent::__construct(
            message: sprintf("Scope '%s' not found for service '%s'", $scope, $serviceName),
            businessCode: 'SERVICE_SCOPE_NOT_FOUND',
            details: array_merge($details, [
                'service' => $serviceName,
                'scope' => $scope,
                'resource' => ResourceName::SERVICE,
            ]),
            messageKey: $messageKey,
            docUrl: $docUrl,
            context: $context
        );
    }
}
