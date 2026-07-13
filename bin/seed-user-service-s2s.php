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
use Technoholics\ServiceRegistry\Credential\Entities\ServiceCredentialFields;
use Technoholics\ServiceRegistry\Credential\Repositories\ServiceCredentialRepository;
use Technoholics\ServiceRegistry\Scope\Entities\ServiceScopeFields;
use Technoholics\ServiceRegistry\Scope\Repositories\ServiceScopeRepository;
use Technoholics\ServiceRegistry\Service\Constants\ServiceStatus;
use Technoholics\ServiceRegistry\Service\Constants\ServiceType;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredService;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredServiceFields;
use Technoholics\ServiceRegistry\Service\Repositories\RegisteredServiceRepository;
use Technoholics\ServiceRegistry\TrustRule\Entities\ServiceTrustRuleFields;
use Technoholics\ServiceRegistry\TrustRule\Repositories\ServiceTrustRuleRepository;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

/**
 * Known callers and minimum scopes for user-service internal APIs.
 *
 * @var array<string, array{type: string, scopes: list<string>}>
 */
const DEFAULT_CALLERS = [
    'bff-services' => [
        'type' => ServiceType::BFF,
        'scopes' => ['user.read', 'user.context.read'],
    ],
    'lookup-service' => [
        'type' => ServiceType::API,
        'scopes' => ['user.read'],
    ],
    'vac-services' => [
        'type' => ServiceType::API,
        'scopes' => ['user.read', 'user.write'],
    ],
];

const TARGET_SERVICE = 'user-service';
const ALL_SCOPES = ['user.read', 'user.context.read', 'user.write'];

$tenantId = null;
$callerNames = null;
$grantAllScopes = false;
$skipCredentials = false;
$rotateCredentials = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--tenant-id=')) {
        $tenantId = substr($arg, strlen('--tenant-id='));
        continue;
    }
    if (str_starts_with($arg, '--callers=')) {
        $callerNames = array_values(array_filter(array_map(
            static fn (string $name): string => strtolower(trim($name)),
            explode(',', substr($arg, strlen('--callers=')))
        )));
        continue;
    }
    if ($arg === '--all-scopes') {
        $grantAllScopes = true;
        continue;
    }
    if ($arg === '--skip-credentials') {
        $skipCredentials = true;
        continue;
    }
    if ($arg === '--rotate-credentials') {
        $rotateCredentials = true;
    }
}

if ($tenantId === null || $tenantId === '') {
    fwrite(STDERR, "Usage: php bin/seed-user-service-s2s.php --tenant-id=<uuid> [--callers=bff-services,lookup-service] [--all-scopes] [--skip-credentials] [--rotate-credentials]\n");
    exit(1);
}

if ($callerNames === null) {
    $callerNames = array_keys(DEFAULT_CALLERS);
}

Context::setTenantId($tenantId);

$settings = require __DIR__ . '/../config/settings.php';
$config = Setup::createAttributeMetadataConfiguration(
    $settings['settings']['doctrine']['meta']['entity_path'],
    true,
    $settings['settings']['doctrine']['meta']['proxy_dir']
);

$em = EntityManager::create($settings['settings']['doctrine']['connection'], $config);

/** @var RegisteredServiceRepository $serviceRepository */
$serviceRepository = $em->getRepository(RegisteredService::class);
/** @var ServiceScopeRepository $scopeRepository */
$scopeRepository = $em->getRepository(\Technoholics\ServiceRegistry\Scope\Entities\ServiceScope::class);
/** @var ServiceTrustRuleRepository $trustRuleRepository */
$trustRuleRepository = $em->getRepository(\Technoholics\ServiceRegistry\TrustRule\Entities\ServiceTrustRule::class);
/** @var ServiceCredentialRepository $credentialRepository */
$credentialRepository = $em->getRepository(\Technoholics\ServiceRegistry\Credential\Entities\ServiceCredential::class);

/** @var SigningKeyRepository $signingKeyRepository */
$signingKeyRepository = $em->getRepository(\Technoholics\ServiceRegistry\Auth\Entities\SigningKey::class);
/** @var AuditLogRepository $auditLogRepository */
$auditLogRepository = $em->getRepository(\Technoholics\ServiceRegistry\Auth\Entities\AuditLog::class);

$signingKeyService = new SigningKeyService(
    $signingKeyRepository,
    new AuditLogService($auditLogRepository)
);

$signingKey = $signingKeyService->getActiveSigningKey($tenantId);
fwrite(STDOUT, sprintf(
    "Signing key ready (kid=%s, tenant=%s).\n",
    $signingKey->getKid(),
    $tenantId
));

$target = ensureService($serviceRepository, $tenantId, TARGET_SERVICE, ServiceType::API);
fwrite(STDOUT, sprintf("Target service '%s' is registered.\n", TARGET_SERVICE));

/** @var array<string, string> $callerSecrets */
$callerSecrets = [];

foreach ($callerNames as $callerName) {
    $profile = resolveCallerProfile($callerName, $grantAllScopes);
    $caller = ensureService($serviceRepository, $tenantId, $callerName, $profile['type']);

    foreach ($profile['scopes'] as $scope) {
        if ($scopeRepository->findByServiceIdAndScope($caller->getId(), $scope) === null) {
            $scopeRepository->create([
                ServiceScopeFields::SERVICE_ID => $caller,
                ServiceScopeFields::SCOPE => $scope,
            ]);
            fwrite(STDOUT, sprintf("Assigned scope '%s' to '%s'.\n", $scope, $callerName));
        }
    }

    if ($trustRuleRepository->findByCallerAndTarget($caller->getId(), $target->getId(), $tenantId) === null) {
        $trustRuleRepository->create([
            ServiceTrustRuleFields::CALLER_SERVICE_ID => $caller,
            ServiceTrustRuleFields::TARGET_SERVICE_ID => $target,
            ServiceTrustRuleFields::ALLOWED_SCOPES => $profile['scopes'],
            ServiceTrustRuleFields::MAX_TTL => 900,
        ]);
        fwrite(STDOUT, sprintf(
            "Created trust rule '%s' -> '%s'.\n",
            $callerName,
            TARGET_SERVICE
        ));
    }

    if ($skipCredentials) {
        continue;
    }

    $activeCredentials = $credentialRepository->findActiveByServiceId($caller->getId());
    if ($activeCredentials !== [] && !$rotateCredentials) {
        fwrite(STDOUT, sprintf("Skipping credential for '%s' (active credential exists).\n", $callerName));
        continue;
    }

    $secret = bin2hex(random_bytes(32));
    $nextVersion = $credentialRepository->getLatestVersionForService($caller->getId()) + 1;
    $credentialRepository->create([
        ServiceCredentialFields::SERVICE_ID => $caller,
        ServiceCredentialFields::SECRET_HASH => password_hash($secret, PASSWORD_BCRYPT),
        ServiceCredentialFields::ROTATION_VERSION => $nextVersion,
        ServiceCredentialFields::ACTIVE => true,
    ]);
    $callerSecrets[$callerName] = $secret;
    fwrite(STDOUT, sprintf(
        "Credential seeded for '%s' (rotation_version=%d).\n",
        $callerName,
        $nextVersion
    ));
}

fwrite(STDOUT, "\n--- user-service (php-app) ---\n");
fwrite(STDOUT, "SERVICE_REGISTRY_URL=http://service-registry:80\n");
fwrite(STDOUT, "USER_SERVICE_REGISTRY_NAME=user-service\n");
fwrite(STDOUT, "SERVICE_REGISTRY_ISSUER=service-registry\n");
fwrite(STDOUT, "# SERVICE_REGISTRY_SECRET is optional (only for outbound S2S from user-service)\n");

if ($callerSecrets !== []) {
    fwrite(STDOUT, "\n--- caller services (set SERVICE_REGISTRY_SECRET) ---\n");
    foreach ($callerSecrets as $callerName => $secret) {
        fwrite(STDOUT, sprintf("# %s\nSERVICE_NAME=%s\nSERVICE_REGISTRY_SECRET=%s\n\n", $callerName, $callerName, $secret));
    }
}

/**
 * @return array{type: string, scopes: list<string>}
 */
function resolveCallerProfile(string $callerName, bool $grantAllScopes): array
{
    if ($grantAllScopes) {
        return [
            'type' => $callerName === 'bff-services' ? ServiceType::BFF : ServiceType::API,
            'scopes' => ALL_SCOPES,
        ];
    }

    if (isset(DEFAULT_CALLERS[$callerName])) {
        return DEFAULT_CALLERS[$callerName];
    }

    return [
        'type' => str_ends_with($callerName, '-bff') || $callerName === 'bff-services'
            ? ServiceType::BFF
            : ServiceType::API,
        'scopes' => ['user.read'],
    ];
}

function ensureService(
    RegisteredServiceRepository $repository,
    string $tenantId,
    string $name,
    string $type
): RegisteredService {
    $existing = $repository->findByName($name, $tenantId);
    if ($existing instanceof RegisteredService) {
        return $existing;
    }

    $created = $repository->create([
        RegisteredServiceFields::TENANT_ID => $tenantId,
        RegisteredServiceFields::REQUEST_NAME => $name,
        RegisteredServiceFields::REQUEST_TYPE => $type,
        RegisteredServiceFields::STATUS => ServiceStatus::ACTIVE,
    ]);
    fwrite(STDOUT, sprintf("Registered service '%s' (type=%s).\n", $name, $type));

    if (!$created instanceof RegisteredService) {
        throw new \RuntimeException(sprintf('Failed to register service %s', $name));
    }

    return $created;
}
