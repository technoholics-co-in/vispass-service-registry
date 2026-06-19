<?php

declare(strict_types=1);

use Technoholics\ServiceRegistry\TrustRule\Controllers\ServiceTrustRuleController;

return function (\Slim\App $app): void {
    $app->post('/trust-rules', [ServiceTrustRuleController::class, 'create']);
    $app->get('/trust-rules', [ServiceTrustRuleController::class, 'list']);
    $app->delete('/trust-rules/{id}', [ServiceTrustRuleController::class, 'delete']);
};
