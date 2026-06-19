<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\TrustRule\Exceptions;

use Technoholics\Exception\ConflictException;

class ServiceTrustRuleAlreadyExistsException extends ConflictException
{
    public function __construct(
        string $callerService,
        string $targetService,
        array $details = [],
        ?string $messageKey = null,
        ?string $docUrl = null,
        array $context = []
    ) {
        parent::__construct(
            message: sprintf(
                'Trust rule already exists for %s -> %s',
                $callerService,
                $targetService
            ),
            details: array_merge($details, [
                'callerService' => $callerService,
                'targetService' => $targetService,
            ]),
            businessCode: 'SERVICE_TRUST_RULE_ALREADY_EXISTS',
            messageKey: $messageKey,
            docUrl: $docUrl,
            context: $context
        );
    }
}
