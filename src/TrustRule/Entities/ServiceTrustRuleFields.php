<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\TrustRule\Entities;

/**
 * Field constants for service_trust_rules table and API payloads.
 */
final class ServiceTrustRuleFields
{
    public const TABLE = 'service_trust_rules';

    public const TABLE_ALIAS = 'str';

    public const ID = 'id';

    public const CALLER_SERVICE_ID = 'caller_service_id';

    public const TARGET_SERVICE_ID = 'target_service_id';

    public const ALLOWED_SCOPES = 'allowed_scopes';

    public const MAX_TTL = 'max_ttl';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public const REQUEST_CALLER_SERVICE = 'callerService';

    public const REQUEST_TARGET_SERVICE = 'targetService';

    public const REQUEST_ALLOWED_SCOPES = 'allowedScopes';

    public const REQUEST_MAX_TTL = 'maxTtl';
}
