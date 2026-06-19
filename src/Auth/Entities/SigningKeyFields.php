<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Entities;

final class SigningKeyFields
{
    public const TABLE = 'signing_keys';

    public const TABLE_ALIAS = 'sk';

    public const ID = 'id';

    public const TENANT_ID = 'tenant_id';

    public const KID = 'kid';

    public const ALGORITHM = 'algorithm';

    public const PUBLIC_KEY = 'public_key';

    public const PRIVATE_KEY = 'private_key';

    public const ACTIVE = 'active';

    public const CREATED_AT = 'created_at';

    public const ROTATED_AT = 'rotated_at';

    public const ALGORITHM_RS256 = 'RS256';
}
