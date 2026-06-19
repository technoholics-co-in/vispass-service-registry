<?php

declare(strict_types=1);

use Technoholics\ServiceRegistry\Service\Controllers\RegisteredServiceController;

return function (\Slim\App $app): void {
    $app->post('/services', [RegisteredServiceController::class, 'register']);
    $app->get('/services/{name}', [RegisteredServiceController::class, 'getByName']);
};
