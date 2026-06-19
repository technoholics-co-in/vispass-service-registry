<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Exceptions;

use Technoholics\Exception\ForbiddenException;

class ServiceScopeDeniedException extends ForbiddenException
{
    /**
     * @param list<string> $requestedScopes
     * @param list<string> $missingScopes
     */
    public function __construct(
        string $serviceName,
        array $requestedScopes,
        array $missingScopes = [],
        array $details = [],
        ?string $messageKey = null,
        ?string $docUrl = null,
        array $context = []
    ) {
        parent::__construct(
            message: sprintf('Service %s is not granted requested scope(s)', $serviceName),
            details: array_merge($details, [
                'service' => $serviceName,
                'requestedScopes' => $requestedScopes,
                'missingScopes' => $missingScopes,
            ]),
            businessCode: 'SERVICE_SCOPE_DENIED',
            messageKey: $messageKey,
            docUrl: $docUrl,
            context: $context
        );
    }
}
