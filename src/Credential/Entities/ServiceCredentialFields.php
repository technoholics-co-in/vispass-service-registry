<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Credential\Entities;

/**
 * Field constants for service_credentials table.
 */
final class ServiceCredentialFields
{
    public const TABLE = 'service_credentials';

    public const ID = 'id';

    public const SERVICE_ID = 'service_id';

    public const AUTH_TYPE = 'auth_type';

    public const SECRET_HASH = 'secret_hash';

    public const ROTATION_VERSION = 'rotation_version';

    public const ACTIVE = 'active';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public const AUTH_TYPE_SHARED_SECRET = 'shared_secret';
}
