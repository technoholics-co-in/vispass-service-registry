<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Tests\Support;

use Technoholics\Logger\FileLogger;
use Technoholics\Logger\FileLoggerConfigProvider;

/**
 * Creates FileLogger instances for unit tests (FileLogger is final).
 */
final class TestFileLoggerFactory
{
    public static function create(): FileLogger
    {
        return new FileLogger(
            'service_registry_test',
            FileLoggerConfigProvider::get([
                'log_file_path' => sys_get_temp_dir() . '/service-registry-test.log',
                'log_level' => \Monolog\Logger::ERROR,
            ])
        );
    }
}
