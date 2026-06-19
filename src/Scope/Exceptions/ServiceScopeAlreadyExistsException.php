<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Scope\Exceptions;

use Technoholics\Core\SharedContracts\Constants\ResourceName;
use Technoholics\Exception\ConflictException;

/**
 * Scope already assigned to service.
 */
class ServiceScopeAlreadyExistsException extends ConflictException
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
            message: sprintf("Scope '%s' already exists for service '%s'", $scope, $serviceName),
            details: array_merge($details, ['service' => $serviceName, 'scope' => $scope]),
            businessCode: 'SERVICE_SCOPE_ALREADY_EXISTS',
            messageKey: $messageKey,
            docUrl: $docUrl,
            context: $context
        );
    }
}
