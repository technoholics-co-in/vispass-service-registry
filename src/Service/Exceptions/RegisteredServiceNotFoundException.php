<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\Exceptions;

use Technoholics\Core\SharedContracts\Constants\ResourceName;
use Technoholics\ServiceRegistry\Shared\Exceptions\RegistryNotFoundException;

/**
 * Registered service not found.
 */
class RegisteredServiceNotFoundException extends RegistryNotFoundException
{
    public function __construct(
        ?string $name = null,
        array $details = [],
        ?string $messageKey = null,
        ?string $docUrl = null,
        array $context = []
    ) {
        $message = $name
            ? sprintf('Service with name %s not found', $name)
            : 'Service not found';

        parent::__construct(
            message: $message,
            businessCode: 'SERVICE_NOT_FOUND',
            details: array_merge($details, ['resource' => ResourceName::SERVICE]),
            messageKey: $messageKey,
            docUrl: $docUrl,
            context: array_merge($context, $name !== null ? ['name' => $name] : [])
        );
    }
}
