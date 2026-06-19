<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Exceptions;

use Technoholics\Exception\ForbiddenException;

class TrustRuleDeniedException extends ForbiddenException
{
    /**
     * @param list<string> $requestedScopes
     */
    public function __construct(
        string $callerService,
        string $targetService,
        array $requestedScopes,
        array $details = [],
        ?string $messageKey = null,
        ?string $docUrl = null,
        array $context = []
    ) {
        parent::__construct(
            message: sprintf(
                'Trust rule denies %s -> %s for requested scopes',
                $callerService,
                $targetService
            ),
            details: array_merge($details, [
                'callerService' => $callerService,
                'targetService' => $targetService,
                'requestedScopes' => $requestedScopes,
            ]),
            businessCode: 'TRUST_RULE_DENIED',
            messageKey: $messageKey,
            docUrl: $docUrl,
            context: $context
        );
    }
}
