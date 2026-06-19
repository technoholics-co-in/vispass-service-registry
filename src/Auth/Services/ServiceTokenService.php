<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Services;

use Technoholics\ServiceRegistry\Auth\Constants\ServiceTokenClaims;
use Technoholics\ServiceRegistry\Auth\DTO\ServiceTokenRequestDTO;
use Technoholics\ServiceRegistry\Auth\Exceptions\InvalidServiceCredentialException;
use Technoholics\ServiceRegistry\Auth\Exceptions\ServiceScopeDeniedException;
use Technoholics\ServiceRegistry\Auth\Exceptions\TrustRuleDeniedException;
use Technoholics\ServiceRegistry\Auth\Security\ServiceJwtIssuer;
use Technoholics\ServiceRegistry\Credential\Repositories\ServiceCredentialRepository;
use Technoholics\ServiceRegistry\Scope\Repositories\ServiceScopeRepository;
use Technoholics\ServiceRegistry\Service\Repositories\RegisteredServiceRepository;
use Technoholics\ServiceRegistry\Shared\Exceptions\CrossTenantOperationException;
use Technoholics\ServiceRegistry\Shared\Tenant\TenantContextResolver;
use Technoholics\ServiceRegistry\Shared\Validation\ScopeFormatValidator;
use Technoholics\ServiceRegistry\TrustRule\Repositories\ServiceTrustRuleRepository;

/**
 * Validates credentials, scopes, and trust rules; issues service JWTs.
 */
class ServiceTokenService
{
    public function __construct(
        private RegisteredServiceRepository $serviceRepository,
        private ServiceCredentialRepository $credentialRepository,
        private ServiceScopeRepository $scopeRepository,
        private ServiceTrustRuleRepository $trustRuleRepository,
        private ServiceJwtIssuer $jwtIssuer,
        private AuditLogService $auditLogService
    ) {
    }

    /**
     * @return array{token: string, expiresIn: int}
     */
    public function issueToken(ServiceTokenRequestDTO $dto, string $providedSecret): array
    {
        $tenantId = TenantContextResolver::requireTenantId();
        $payload = $dto->toArray();
        $serviceName = (string) $payload['service'];
        $targetService = (string) $payload['targetService'];
        /** @var list<string> $requestedScopes */
        $requestedScopes = array_values($payload['requestedScopes']);

        foreach ($requestedScopes as $scope) {
            ScopeFormatValidator::assertValid((string) $scope);
        }

        $caller = $this->serviceRepository->requireByName($serviceName, $tenantId);
        $target = $this->serviceRepository->requireByName($targetService, $tenantId);

        if ($caller->getTenantId() !== $target->getTenantId()) {
            throw new CrossTenantOperationException(
                'Caller and target services must belong to the same tenant'
            );
        }

        $credential = $this->verifyCredentials($caller->getId(), $serviceName, $providedSecret);

        $assignedScopes = array_map(
            static fn ($entity) => $entity->getScope(),
            $this->scopeRepository->findAllByServiceId($caller->getId())
        );

        $missing = array_values(array_diff($requestedScopes, $assignedScopes));
        if ($missing !== []) {
            throw new ServiceScopeDeniedException($serviceName, $requestedScopes, $missing);
        }

        $trustRule = $this->trustRuleRepository->findByCallerAndTarget(
            $caller->getId(),
            $target->getId(),
            $tenantId
        );

        if ($trustRule === null) {
            throw new TrustRuleDeniedException($serviceName, $targetService, $requestedScopes);
        }

        $allowedScopes = $trustRule->getAllowedScopes();
        $denied = array_values(array_diff($requestedScopes, $allowedScopes));
        if ($denied !== []) {
            throw new TrustRuleDeniedException($serviceName, $targetService, $requestedScopes);
        }

        $ttl = min($trustRule->getMaxTtl(), ServiceTokenClaims::DEFAULT_TTL);

        $result = $this->jwtIssuer->issue(
            $serviceName,
            $targetService,
            $requestedScopes,
            $ttl,
            $credential->getRotationVersion(),
            $tenantId
        );

        $this->auditLogService->logTokenIssued($serviceName, $targetService, $requestedScopes, $result['expiresIn']);

        return $result;
    }

    private function verifyCredentials(
        string $serviceId,
        string $serviceName,
        string $providedSecret
    ): \Technoholics\ServiceRegistry\Credential\Entities\ServiceCredential {
        $credentials = $this->credentialRepository->findActiveByServiceId($serviceId);
        foreach ($credentials as $credential) {
            if (password_verify($providedSecret, $credential->getSecretHash())) {
                return $credential;
            }
        }

        throw new InvalidServiceCredentialException($serviceName);
    }
}
