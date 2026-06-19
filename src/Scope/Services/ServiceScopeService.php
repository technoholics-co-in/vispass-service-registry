<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Scope\Services;

use Doctrine\ORM\EntityManagerInterface;
use Psr\SimpleCache\CacheInterface;
use Technoholics\Logger\FileLogger;
use Technoholics\ServiceRegistry\Scope\DTO\AddServiceScopeRequestDTO;
use Technoholics\ServiceRegistry\Scope\DTO\ServiceScopeResponseDTO;
use Technoholics\ServiceRegistry\Scope\Entities\ServiceScopeFields;
use Technoholics\ServiceRegistry\Scope\Exceptions\ServiceScopeAlreadyExistsException;
use Technoholics\ServiceRegistry\Scope\Exceptions\ServiceScopeNotFoundException;
use Technoholics\ServiceRegistry\Scope\Repositories\ServiceScopeRepository;
use Technoholics\ServiceRegistry\Auth\Constants\AuditEventType;
use Technoholics\ServiceRegistry\Auth\Services\AuditLogService;
use Technoholics\ServiceRegistry\Service\Helpers\ServiceCacheKeyHelper;
use Technoholics\ServiceRegistry\Service\Repositories\RegisteredServiceRepository;
use Technoholics\ServiceRegistry\Shared\Tenant\TenantContextResolver;
use Technoholics\ServiceRegistry\Shared\Validation\ScopeFormatValidator;

class ServiceScopeService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RegisteredServiceRepository $serviceRepository,
        private ServiceScopeRepository $scopeRepository,
        private CacheInterface $cache,
        private FileLogger $logger,
        private AuditLogService $auditLogService,
        private string $cachePrefix,
        private int $cacheTtl
    ) {
    }

    /**
     * @return array<string, int|string>
     */
    public function addScope(string $serviceName, AddServiceScopeRequestDTO $dto): array
    {
        $tenantId = TenantContextResolver::requireTenantId();
        $scope = (string) $dto->toArray()[ServiceScopeFields::REQUEST_SCOPE];
        ScopeFormatValidator::assertValid($scope);

        $service = $this->serviceRepository->requireByName($serviceName, $tenantId);

        if ($this->scopeRepository->existsByServiceIdAndScope($service->getId(), $scope)) {
            throw new ServiceScopeAlreadyExistsException($serviceName, $scope);
        }

        $result = $this->em->transactional(function () use ($service, $scope, $serviceName, $tenantId): array {
            $entity = $this->scopeRepository->create([
                ServiceScopeFields::SERVICE_ID => $service,
                ServiceScopeFields::SCOPE => $scope,
            ]);

            $this->invalidateScopesCache($serviceName, $tenantId);
            $this->logger->info('Service scope added', ['service' => $serviceName, 'scope' => $scope]);
            $this->auditLogService->log(
                AuditEventType::SCOPE_ASSIGNED,
                $serviceName,
                'service_scope',
                $scope
            );

            return ServiceScopeResponseDTO::fromEntity($entity)->toArray();
        });

        $this->em->clear();

        return $result;
    }

    /**
     * @return list<string>
     */
    public function listScopes(string $serviceName): array
    {
        $tenantId = TenantContextResolver::requireTenantId();
        $cacheKey = ServiceCacheKeyHelper::forScopes($this->cachePrefix, $tenantId, $serviceName);
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $service = $this->serviceRepository->requireByName($serviceName, $tenantId);
        $entities = $this->scopeRepository->findAllByServiceId($service->getId());

        $scopes = array_map(
            static fn ($entity) => $entity->getScope(),
            $entities
        );

        $this->cache->set($cacheKey, $scopes, $this->cacheTtl);
        $this->em->clear();

        return $scopes;
    }

    public function removeScope(string $serviceName, string $scope): void
    {
        $tenantId = TenantContextResolver::requireTenantId();
        ScopeFormatValidator::assertValid($scope);

        $service = $this->serviceRepository->requireByName($serviceName, $tenantId);

        if (!$this->scopeRepository->existsByServiceIdAndScope($service->getId(), $scope)) {
            throw new ServiceScopeNotFoundException($serviceName, $scope);
        }

        $this->em->transactional(function () use ($service, $scope, $serviceName, $tenantId): void {
            $this->scopeRepository->deleteByServiceIdAndScope($service->getId(), $scope);
            $this->invalidateScopesCache($serviceName, $tenantId);
            $this->logger->info('Service scope removed', ['service' => $serviceName, 'scope' => $scope]);
            $this->auditLogService->log(
                AuditEventType::SCOPE_REMOVED,
                $serviceName,
                'service_scope',
                $scope
            );
        });

        $this->em->clear();
    }

    private function invalidateScopesCache(string $serviceName, string $tenantId): void
    {
        $this->cache->delete(ServiceCacheKeyHelper::forScopes($this->cachePrefix, $tenantId, $serviceName));
    }
}
