<?php

declare(strict_types=1);

use Technoholics\ServiceRegistry\Auth\Controllers\JwksController;

return function (\Slim\App $app): void {
    $app->get('/.well-known/jwks.json', [JwksController::class, 'jwks']);
};
