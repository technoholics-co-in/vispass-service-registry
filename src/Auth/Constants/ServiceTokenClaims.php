<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Constants;

/**
 * JWT claim names for internal service tokens.
 */
final class ServiceTokenClaims
{
    public const ISSUER = 'service-registry';

    public const SUBJECT = 'sub';

    public const AUDIENCE = 'aud';

    public const SCOPES = 'scopes';

    public const TOKEN_TYPE = 'typ';

    public const TOKEN_TYPE_SERVICE = 'service';

    public const CREDENTIAL_VERSION = 'cred_ver';

    public const TENANT_ID = 'tenant_id';

    public const DEFAULT_TTL = 900;

    public const MIN_TTL = 300;

    public const MAX_TTL = 3600;
}
