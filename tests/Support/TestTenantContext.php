<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Tests\Support;

use Technoholics\Psr15Middleware\Http\Context\Context;

/**
 * Sets tenant context for unit tests.
 */
final class TestTenantContext
{
    public const TENANT_ID = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    public static function activate(): void
    {
        Context::setTenantId(self::TENANT_ID);
    }

    public static function clear(): void
    {
        Context::clear();
    }
}
