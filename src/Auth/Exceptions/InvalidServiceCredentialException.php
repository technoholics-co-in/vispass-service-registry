<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Exceptions;

use Technoholics\Exception\UnauthorizedException;

class InvalidServiceCredentialException extends UnauthorizedException
{
    public function __construct(
        string $serviceName,
        array $details = [],
        ?string $messageKey = null,
        ?string $docUrl = null,
        array $context = []
    ) {
        parent::__construct(
            message: sprintf('Invalid credentials for service %s', $serviceName),
            details: array_merge($details, ['service' => $serviceName]),
            businessCode: 'INVALID_SERVICE_CREDENTIAL',
            messageKey: $messageKey,
            docUrl: $docUrl,
            context: $context
        );
    }
}
