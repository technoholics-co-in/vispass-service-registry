<?php

declare(strict_types=1);

use Technoholics\Core\SharedContracts\Env\SecretEnv;

return [
    'settings' => [
        'displayErrorDetails' => ($_ENV['APP_ENV'] ?? 'dev') !== 'prod',
        'determineRouteBeforeAppMiddleware' => true,

        'doctrine' => [
            'meta' => [
                'entity_path' => [
                    __DIR__ . '/../src/Service/Entities',
                    __DIR__ . '/../src/Scope/Entities',
                    __DIR__ . '/../src/TrustRule/Entities',
                    __DIR__ . '/../src/Credential/Entities',
                    __DIR__ . '/../src/Auth/Entities',
                ],
                'auto_generate_proxies' => true,
                'proxy_dir' => __DIR__ . '/../cache/proxies',
                'cache' => null,
            ],
            'connection' => [
                'driver' => 'pdo_pgsql',
                'host' => $_ENV['DB_HOST'] ?? 'postgres',
                'port' => $_ENV['DB_PORT'] ?? '5432',
                'dbname' => $_ENV['DB_NAME'] ?? 'service_registry',
                'user' => $_ENV['DB_USER'] ?? 'kong',
                'password' => SecretEnv::value('DB_PASSWORD', 'kongpassword'),
                'charset' => 'UTF8',
            ],
        ],

        'logger' => [
            'service_name' => 'service_registry',
            'log_file_path' => __DIR__ . '/../logs/app.log',
            'component' => 'service_registry',
            'log_level' => \Monolog\Logger::DEBUG,
            'log_format' => 'text',
        ],

        'cache' => [
            'prefix' => $_ENV['REDIS_PREFIX'] ?? 'service-registry:',
            'ttl' => (int) ($_ENV['SERVICE_CACHE_TTL'] ?? 300),
        ],
    ],
];
