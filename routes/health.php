<?php

declare(strict_types=1);

use Technoholics\ServiceRegistry\Health\Controllers\HealthController;

return function (\Slim\App $app): void {
    $app->get('/health', [HealthController::class, 'ping']);
};
