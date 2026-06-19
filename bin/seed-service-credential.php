#!/usr/bin/env php
<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\Dotenv\Dotenv;
use Technoholics\ServiceRegistry\Credential\Entities\ServiceCredentialFields;
use Technoholics\ServiceRegistry\Credential\Repositories\ServiceCredentialRepository;
use Technoholics\ServiceRegistry\Service\Repositories\RegisteredServiceRepository;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    (new Dotenv())->bootEnv(__DIR__ . '/../.env');
}

$serviceName = null;
$secret = null;
$tenantId = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--tenant-id=')) {
        $tenantId = substr($arg, strlen('--tenant-id='));
        continue;
    }
    if ($serviceName === null) {
        $serviceName = $arg;
        continue;
    }
    if ($secret === null) {
        $secret = $arg;
    }
}

if ($serviceName === null || $secret === null || $secret === '' || $tenantId === null || $tenantId === '') {
    fwrite(STDERR, "Usage: php bin/seed-service-credential.php --tenant-id=<uuid> <service-name> <secret>\n");
    exit(1);
}

$settings = require __DIR__ . '/../config/settings.php';
$entityPaths = [
    __DIR__ . '/../src/Service/Entities',
    __DIR__ . '/../src/Credential/Entities',
];
$config = Setup::createAttributeMetadataConfiguration(
    $entityPaths,
    true,
    $settings['settings']['doctrine']['meta']['proxy_dir']
);

$em = EntityManager::create($settings['settings']['doctrine']['connection'], $config);

/** @var RegisteredServiceRepository $serviceRepository */
$serviceRepository = $em->getRepository(\Technoholics\ServiceRegistry\Service\Entities\RegisteredService::class);
/** @var ServiceCredentialRepository $credentialRepository */
$credentialRepository = $em->getRepository(\Technoholics\ServiceRegistry\Credential\Entities\ServiceCredential::class);

$service = $serviceRepository->findByName($serviceName, $tenantId);
if ($service === null) {
    fwrite(STDERR, sprintf(
        "Service '%s' not found for tenant '%s'. Register it first via POST /services.\n",
        $serviceName,
        $tenantId
    ));
    exit(1);
}

$nextVersion = $credentialRepository->getLatestVersionForService($service->getId()) + 1;

$credentialRepository->create([
    ServiceCredentialFields::SERVICE_ID => $service,
    ServiceCredentialFields::SECRET_HASH => password_hash($secret, PASSWORD_BCRYPT),
    ServiceCredentialFields::ROTATION_VERSION => $nextVersion,
    ServiceCredentialFields::ACTIVE => true,
]);

fwrite(STDOUT, sprintf(
    "Credential seeded for '%s' (tenant=%s, rotation_version=%d).\n",
    $serviceName,
    $tenantId,
    $nextVersion
));
