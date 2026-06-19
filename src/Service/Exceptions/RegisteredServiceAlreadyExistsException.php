<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\Exceptions;

use Technoholics\Exception\ConflictException;

/**
 * Registered service already exists.
 */
class RegisteredServiceAlreadyExistsException extends ConflictException
{
    public function __construct(
        string $name,
        array $details = [],
        ?string $messageKey = null,
        ?string $docUrl = null,
        array $context = []
    ) {
        $message = sprintf('Service with name %s already exists', $name);

        parent::__construct(
            message: $message,
            details: array_merge($details, ['name' => $name]),
            businessCode: 'SERVICE_ALREADY_EXISTS',
            messageKey: $messageKey,
            docUrl: $docUrl,
            context: $context
        );
    }
}
