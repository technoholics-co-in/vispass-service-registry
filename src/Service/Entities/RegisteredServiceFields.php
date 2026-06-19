<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\Entities;

/**
 * Field constants for services table and API payloads.
 */
final class RegisteredServiceFields
{
    public const TABLE = 'services';

    public const TABLE_ALIAS = 's';

    public const ID = 'id';

    public const TENANT_ID = 'tenant_id';

    public const NAME = 'name';

    public const TYPE = 'type';

    public const STATUS = 'status';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public const REQUEST_NAME = 'name';

    public const REQUEST_TYPE = 'type';
}
