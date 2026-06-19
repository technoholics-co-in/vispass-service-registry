<?php

declare(strict_types=1);

use Technoholics\ServiceRegistry\Scope\Controllers\ServiceScopeController;

return function (\Slim\App $app): void {
    $app->post('/services/{service}/scopes', [ServiceScopeController::class, 'add']);
    $app->get('/services/{service}/scopes', [ServiceScopeController::class, 'list']);
    $app->delete('/services/{service}/scopes/{scope}', [ServiceScopeController::class, 'delete']);
};
