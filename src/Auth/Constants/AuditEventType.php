<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Constants;

/**
 * Audit event types for service registry operations.
 */
final class AuditEventType
{
    public const SERVICE_REGISTERED = 'SERVICE_REGISTERED';

    public const SCOPE_ASSIGNED = 'SCOPE_ASSIGNED';

    public const SCOPE_REMOVED = 'SCOPE_REMOVED';

    public const TRUST_RULE_CREATED = 'TRUST_RULE_CREATED';

    public const TRUST_RULE_DELETED = 'TRUST_RULE_DELETED';

    public const TOKEN_ISSUED = 'TOKEN_ISSUED';

    public const SIGNING_KEY_ROTATED = 'SIGNING_KEY_ROTATED';
}
