<?php

declare(strict_types=1);

$localVendor = __DIR__ . '/../vendor/autoload.php';
$lookupVendor = __DIR__ . '/../../lookup-service/vendor/autoload.php';

if (file_exists($localVendor)) {
    require $localVendor;
} elseif (file_exists($lookupVendor)) {
    require $lookupVendor;
    spl_autoload_register(static function (string $class): void {
        $prefix = 'Technoholics\\ServiceRegistry\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($path)) {
            require $path;
        }
    });
} else {
    throw new RuntimeException('Run composer install in service-registry or lookup-service first.');
}
