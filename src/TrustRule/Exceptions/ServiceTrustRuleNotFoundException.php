<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\TrustRule\Exceptions;

use Technoholics\Core\SharedContracts\Constants\ResourceName;
use Technoholics\ServiceRegistry\Shared\Exceptions\RegistryNotFoundException;

class ServiceTrustRuleNotFoundException extends RegistryNotFoundException
{
    public function __construct(
        ?int $id = null,
        array $details = [],
        ?string $messageKey = null,
        ?string $docUrl = null,
        array $context = []
    ) {
        $message = $id !== null
            ? sprintf('Trust rule with ID %d not found', $id)
            : 'Trust rule not found';

        parent::__construct(
            message: $message,
            businessCode: 'SERVICE_TRUST_RULE_NOT_FOUND',
            details: array_merge($details, ['resource' => ResourceName::SERVICE]),
            messageKey: $messageKey,
            docUrl: $docUrl,
            context: array_merge($context, $id !== null ? ['id' => $id] : [])
        );
    }
}
