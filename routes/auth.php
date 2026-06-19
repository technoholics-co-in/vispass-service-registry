<?php

declare(strict_types=1);

return function (\Slim\App $app): void {
    $app->post('/auth/token', [\Technoholics\ServiceRegistry\Auth\Controllers\ServiceTokenController::class, 'issueToken']);
};
