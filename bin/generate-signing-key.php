#!/usr/bin/env php
<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Dotenv\Dotenv;
use Technoholics\Psr15Middleware\Http\Context\Context;
use Technoholics\ServiceRegistry\Auth\Repositories\AuditLogRepository;
use Technoholics\ServiceRegistry\Auth\Repositories\SigningKeyRepository;
use Technoholics\ServiceRegistry\Auth\Security\SigningKeyService;
use Technoholics\ServiceRegistry\Auth\Services\AuditLogService;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

$tenantId = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--tenant-id=')) {
        $tenantId = substr($arg, strlen('--tenant-id='));
    }
}

if ($tenantId === null || $tenantId === '') {
    fwrite(STDERR, "Usage: php bin/generate-signing-key.php --tenant-id=<uuid>\n");
    exit(1);
}

Context::setTenantId($tenantId);

$settings = require __DIR__ . '/../config/settings.php';
$entityPaths = [
    __DIR__ . '/../src/Auth/Entities',
];
$config = Setup::createAttributeMetadataConfiguration(
    $entityPaths,
    true,
    $settings['settings']['doctrine']['meta']['proxy_dir']
);

$em = EntityManager::create($settings['settings']['doctrine']['connection'], $config);

/** @var SigningKeyRepository $signingKeyRepository */
$signingKeyRepository = $em->getRepository(\Technoholics\ServiceRegistry\Auth\Entities\SigningKey::class);
/** @var AuditLogRepository $auditLogRepository */
$auditLogRepository = $em->getRepository(\Technoholics\ServiceRegistry\Auth\Entities\AuditLog::class);

$auditLogService = new AuditLogService($auditLogRepository);
$signingKeyService = new SigningKeyService($signingKeyRepository, $auditLogService);

$key = $signingKeyService->getActiveSigningKey($tenantId);

fwrite(STDOUT, sprintf(
    "Signing key ready (kid=%s, algorithm=%s, tenantId=%s)\n",
    $key->getKid(),
    $key->getAlgorithm(),
    $tenantId
));
