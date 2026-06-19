<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Scope\Entities;

/**
 * Field constants for service_scopes table and API payloads.
 */
final class ServiceScopeFields
{
    public const TABLE = 'service_scopes';

    public const TABLE_ALIAS = 'ss';

    public const ID = 'id';

    public const SERVICE_ID = 'service_id';

    public const SCOPE = 'scope';

    public const CREATED_AT = 'created_at';

    public const REQUEST_SCOPE = 'scope';
}
