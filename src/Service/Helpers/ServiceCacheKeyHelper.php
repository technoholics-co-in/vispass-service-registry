<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\Helpers;

/**
 * Redis cache key builder for registered services.
 */
final class ServiceCacheKeyHelper
{
    public static function forService(string $prefix, string $tenantId, string $serviceName): string
    {
        return rtrim($prefix, ':') . ':' . $tenantId . ':service:' . $serviceName;
    }

    public static function forScopes(string $prefix, string $tenantId, string $serviceName): string
    {
        return rtrim($prefix, ':') . ':' . $tenantId . ':scopes:' . $serviceName;
    }

    public static function forTrustRules(string $prefix, string $tenantId): string
    {
        return rtrim($prefix, ':') . ':' . $tenantId . ':trust-rules';
    }
}
