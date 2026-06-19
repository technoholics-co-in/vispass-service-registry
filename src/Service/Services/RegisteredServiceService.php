<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\Services;

use Doctrine\ORM\EntityManagerInterface;
use Psr\SimpleCache\CacheInterface;
use Technoholics\Logger\FileLogger;
use Technoholics\ServiceRegistry\Service\Constants\ServiceStatus;
use Technoholics\ServiceRegistry\Service\DTO\RegisterServiceRequestDTO;
use Technoholics\ServiceRegistry\Service\DTO\RegisteredServiceResponseDTO;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredServiceFields;
use Technoholics\ServiceRegistry\Service\Exceptions\RegisteredServiceAlreadyExistsException;
use Technoholics\ServiceRegistry\Service\Exceptions\RegisteredServiceNotFoundException;
use Technoholics\ServiceRegistry\Auth\Services\AuditLogService;
use Technoholics\ServiceRegistry\Service\Helpers\ServiceCacheKeyHelper;
use Technoholics\ServiceRegistry\Service\Repositories\RegisteredServiceRepository;
use Technoholics\ServiceRegistry\Shared\Tenant\TenantContextResolver;

/**
 * Business logic for service registration and discovery.
 */
class RegisteredServiceService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RegisteredServiceRepository $repository,
        private CacheInterface $cache,
        private FileLogger $logger,
        private AuditLogService $auditLogService,
        private string $cachePrefix,
        private int $cacheTtl
    ) {
    }

    /**
     * Register a new internal service.
     *
     * @return array<string, string>
     */
    public function register(RegisterServiceRequestDTO $dto): array
    {
        $tenantId = TenantContextResolver::requireTenantId();
        $payload = $dto->toArray();
        $name = (string) $payload['name'];

        if ($this->repository->existsByName($name, $tenantId)) {
            throw new RegisteredServiceAlreadyExistsException($name);
        }

        $data = array_merge($payload, [
            RegisteredServiceFields::TENANT_ID => $tenantId,
            RegisteredServiceFields::STATUS => ServiceStatus::ACTIVE,
        ]);

        $result = $this->em->transactional(function () use ($data, $name, $tenantId): array {
            $entity = $this->repository->create($data);
            $response = RegisteredServiceResponseDTO::fromEntity($entity);
            $array = $response->toArray();

            $this->cache->set(
                ServiceCacheKeyHelper::forService($this->cachePrefix, $tenantId, $name),
                $response->toDiscoveryArray(),
                $this->cacheTtl
            );

            $this->logger->info('Service registered', ['name' => $name, 'tenantId' => $tenantId]);
            $this->auditLogService->logServiceRegistered($name);

            return $array;
        });

        $this->em->clear();

        return $result;
    }

    /**
     * Retrieve service metadata by name (cache-aside).
     *
     * @return array<string, string>
     */
    public function getByName(string $name): array
    {
        $tenantId = TenantContextResolver::requireTenantId();
        $cacheKey = ServiceCacheKeyHelper::forService($this->cachePrefix, $tenantId, $name);
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $entity = $this->repository->findByName($name, $tenantId);
        if ($entity === null) {
            throw new RegisteredServiceNotFoundException($name);
        }

        $response = RegisteredServiceResponseDTO::fromEntity($entity);
        $data = $response->toDiscoveryArray();

        $this->cache->set($cacheKey, $data, $this->cacheTtl);
        $this->em->clear();

        return $data;
    }

    /**
     * Invalidate cached service metadata.
     */
    public function invalidateCache(string $name): void
    {
        $tenantId = TenantContextResolver::requireTenantId();
        $this->cache->delete(ServiceCacheKeyHelper::forService($this->cachePrefix, $tenantId, $name));
    }
}
