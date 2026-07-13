#!/usr/bin/env php
<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Dotenv\Dotenv;
use Technoholics\ServiceRegistry\Credential\Entities\ServiceCredentialFields;
use Technoholics\ServiceRegistry\Credential\Repositories\ServiceCredentialRepository;
use Technoholics\ServiceRegistry\Scope\Entities\ServiceScopeFields;
use Technoholics\ServiceRegistry\Scope\Repositories\ServiceScopeRepository;
use Technoholics\ServiceRegistry\Service\Constants\ServiceStatus;
use Technoholics\ServiceRegistry\Service\Constants\ServiceType;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredServiceFields;
use Technoholics\ServiceRegistry\Service\Repositories\RegisteredServiceRepository;
use Technoholics\ServiceRegistry\TrustRule\Entities\ServiceTrustRuleFields;
use Technoholics\ServiceRegistry\TrustRule\Repositories\ServiceTrustRuleRepository;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

$tenantId = null;
$secret = null;
$callerService = 'bff-services';
$targetService = 'user-service';

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--tenant-id=')) {
        $tenantId = substr($arg, strlen('--tenant-id='));
        continue;
    }
    if (str_starts_with($arg, '--secret=')) {
        $secret = substr($arg, strlen('--secret='));
        continue;
    }
    if (str_starts_with($arg, '--caller=')) {
        $callerService = substr($arg, strlen('--caller='));
        continue;
    }
    if (str_starts_with($arg, '--target=')) {
        $targetService = substr($arg, strlen('--target='));
    }
}

if ($tenantId === null || $tenantId === '') {
    fwrite(STDERR, "Usage: php bin/seed-bff-s2s.php --tenant-id=<uuid> [--secret=<secret>] [--caller=bff-services] [--target=user-service]\n");
    exit(1);
}

if ($secret === null || $secret === '') {
    $secret = bin2hex(random_bytes(32));
    $generatedSecret = true;
} else {
    $generatedSecret = false;
}

$scopes = ['user.read', 'user.context.read'];

$settings = require __DIR__ . '/../config/settings.php';
$config = Setup::createAttributeMetadataConfiguration(
    $settings['settings']['doctrine']['meta']['entity_path'],
    true,
    $settings['settings']['doctrine']['meta']['proxy_dir']
);

$em = EntityManager::create($settings['settings']['doctrine']['connection'], $config);

/** @var RegisteredServiceRepository $serviceRepository */
$serviceRepository = $em->getRepository(\Technoholics\ServiceRegistry\Service\Entities\RegisteredService::class);
/** @var ServiceScopeRepository $scopeRepository */
$scopeRepository = $em->getRepository(\Technoholics\ServiceRegistry\Scope\Entities\ServiceScope::class);
/** @var ServiceTrustRuleRepository $trustRuleRepository */
$trustRuleRepository = $em->getRepository(\Technoholics\ServiceRegistry\TrustRule\Entities\ServiceTrustRule::class);
/** @var ServiceCredentialRepository $credentialRepository */
$credentialRepository = $em->getRepository(\Technoholics\ServiceRegistry\Credential\Entities\ServiceCredential::class);

$caller = $serviceRepository->findByName($callerService, $tenantId);
if ($caller === null) {
    $caller = $serviceRepository->create([
        RegisteredServiceFields::TENANT_ID => $tenantId,
        RegisteredServiceFields::REQUEST_NAME => $callerService,
        RegisteredServiceFields::REQUEST_TYPE => ServiceType::BFF,
        RegisteredServiceFields::STATUS => ServiceStatus::ACTIVE,
    ]);
    fwrite(STDOUT, sprintf("Registered service '%s'.\n", $callerService));
}

$target = $serviceRepository->findByName($targetService, $tenantId);
if ($target === null) {
    $target = $serviceRepository->create([
        RegisteredServiceFields::TENANT_ID => $tenantId,
        RegisteredServiceFields::REQUEST_NAME => $targetService,
        RegisteredServiceFields::REQUEST_TYPE => ServiceType::API,
        RegisteredServiceFields::STATUS => ServiceStatus::ACTIVE,
    ]);
    fwrite(STDOUT, sprintf("Registered service '%s'.\n", $targetService));
}

foreach ($scopes as $scope) {
    if ($scopeRepository->findByServiceIdAndScope($caller->getId(), $scope) === null) {
        $scopeRepository->create([
            ServiceScopeFields::SERVICE_ID => $caller,
            ServiceScopeFields::SCOPE => $scope,
        ]);
        fwrite(STDOUT, sprintf("Assigned scope '%s' to '%s'.\n", $scope, $callerService));
    }
}

$trustRule = $trustRuleRepository->findByCallerAndTarget($caller->getId(), $target->getId(), $tenantId);
if ($trustRule === null) {
    $trustRuleRepository->create([
        ServiceTrustRuleFields::CALLER_SERVICE_ID => $caller,
        ServiceTrustRuleFields::TARGET_SERVICE_ID => $target,
        ServiceTrustRuleFields::ALLOWED_SCOPES => $scopes,
        ServiceTrustRuleFields::MAX_TTL => 900,
    ]);
    fwrite(STDOUT, sprintf(
        "Created trust rule '%s' -> '%s'.\n",
        $callerService,
        $targetService
    ));
}

$nextVersion = $credentialRepository->getLatestVersionForService($caller->getId()) + 1;
$credentialRepository->create([
    ServiceCredentialFields::SERVICE_ID => $caller,
    ServiceCredentialFields::SECRET_HASH => password_hash($secret, PASSWORD_BCRYPT),
    ServiceCredentialFields::ROTATION_VERSION => $nextVersion,
    ServiceCredentialFields::ACTIVE => true,
]);

fwrite(STDOUT, sprintf(
    "Credential seeded for '%s' (tenant=%s, rotation_version=%d).\n",
    $callerService,
    $tenantId,
    $nextVersion
));

if ($generatedSecret) {
    fwrite(STDOUT, "\nSet this in bff-services (and docker-compose BFF_SERVICE_REGISTRY_SECRET):\n");
    fwrite(STDOUT, "SERVICE_REGISTRY_SECRET={$secret}\n");
}

fwrite(STDOUT, "\nEnsure a signing key exists: php bin/generate-signing-key.php --tenant-id={$tenantId}\n");
