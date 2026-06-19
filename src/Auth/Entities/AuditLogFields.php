<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Entities;

final class AuditLogFields
{
    public const TABLE = 'audit_logs';

    public const TABLE_ALIAS = 'al';

    public const ID = 'id';

    public const TENANT_ID = 'tenant_id';

    public const EVENT_TYPE = 'event_type';

    public const ACTOR_SERVICE = 'actor_service';

    public const RESOURCE_TYPE = 'resource_type';

    public const RESOURCE_ID = 'resource_id';

    public const DETAILS = 'details';

    public const CREATED_AT = 'created_at';
}
