<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Shared\Tenant;

use Technoholics\Psr15Middleware\Http\Context\Context;
use Technoholics\ServiceRegistry\Shared\Exceptions\TenantContextMissingException;

/**
 * Resolves tenant ID from request context (x-tenant-id header).
 */
final class TenantContextResolver
{
    public static function requireTenantId(): string
    {
        $tenantId = self::getTenantId();
        if ($tenantId === null) {
            throw new TenantContextMissingException();
        }

        return $tenantId;
    }

    public static function getTenantId(): ?string
    {
        $tenantId = Context::getTenantId();

        if ($tenantId === null || $tenantId === '') {
            return null;
        }

        return $tenantId;
    }
}
