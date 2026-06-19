<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Scope\Exceptions;

use Technoholics\Core\SharedContracts\Constants\ResourceName;
use Technoholics\Exception\BadRequestException;

/**
 * Invalid scope format or wildcard scope rejected.
 */
class InvalidScopeException extends BadRequestException
{
    public function __construct(
        string $scope,
        array $details = [],
        ?string $messageKey = null,
        ?string $docUrl = null,
        array $context = []
    ) {
        parent::__construct(
            message: sprintf(
                "Scope '%s' is invalid. Use explicit scopes like storage.upload (wildcards are not allowed).",
                $scope
            ),
            details: array_merge($details, ['scope' => $scope, 'resource' => ResourceName::SERVICE]),
            businessCode: 'INVALID_SCOPE',
            messageKey: $messageKey,
            docUrl: $docUrl,
            context: $context
        );
    }
}
