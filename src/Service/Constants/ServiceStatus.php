<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\Constants;

/**
 * Registered service lifecycle status values.
 */
final class ServiceStatus
{
    public const ACTIVE = 'active';

    public const INACTIVE = 'inactive';

    public const DEPRECATED = 'deprecated';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
            self::DEPRECATED,
        ];
    }
}
