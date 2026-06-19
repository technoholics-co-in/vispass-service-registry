<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Shared\Exceptions;

use Technoholics\Exception\ApiException;
use Technoholics\Log\LogLevel;

/**
 * 404 base for service-registry (avoids broken NotFoundException default parameter on PHP 8.2).
 */
abstract class RegistryNotFoundException extends ApiException
{
    /**
     * @param array<string, mixed> $details
     * @param array<string, mixed> $context
     */
    protected function __construct(
        string $message,
        string $businessCode,
        array $details = [],
        ?string $messageKey = null,
        ?string $docUrl = null,
        array $context = []
    ) {
        parent::__construct(
            message: $message,
            status: 404,
            error: 'resource_not_found',
            businessCode: $businessCode,
            details: $details,
            logLevel: LogLevel::NOTICE,
            messageKey: $messageKey,
            docUrl: $docUrl,
            context: $context
        );
    }
}
