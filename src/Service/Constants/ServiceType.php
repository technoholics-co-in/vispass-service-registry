<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\Constants;

/**
 * Allowed registered service types.
 */
final class ServiceType
{
    public const API = 'api';

    public const WORKER = 'worker';

    public const CRON = 'cron';

    public const BFF = 'bff';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::API,
            self::WORKER,
            self::CRON,
            self::BFF,
        ];
    }
}
