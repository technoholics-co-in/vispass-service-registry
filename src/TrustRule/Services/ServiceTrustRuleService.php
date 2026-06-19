<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\TrustRule\Services;

use Doctrine\ORM\EntityManagerInterface;
use Psr\SimpleCache\CacheInterface;
use Technoholics\Logger\FileLogger;
use Technoholics\ServiceRegistry\Auth\Constants\AuditEventType;
use Technoholics\ServiceRegistry\Auth\Services\AuditLogService;
use Technoholics\ServiceRegistry\Service\Helpers\ServiceCacheKeyHelper;
use Technoholics\ServiceRegistry\Service\Repositories\RegisteredServiceRepository;
use Technoholics\ServiceRegistry\Shared\Exceptions\CrossTenantOperationException;
use Technoholics\ServiceRegistry\Shared\Tenant\TenantContextResolver;
use Technoholics\ServiceRegistry\Shared\Validation\ScopeFormatValidator;
use Technoholics\ServiceRegistry\TrustRule\DTO\CreateTrustRuleRequestDTO;
use Technoholics\ServiceRegistry\TrustRule\DTO\ServiceTrustRuleResponseDTO;
use Technoholics\ServiceRegistry\TrustRule\Entities\ServiceTrustRuleFields;
use Technoholics\ServiceRegistry\TrustRule\Exceptions\ServiceTrustRuleAlreadyExistsException;
use Technoholics\ServiceRegistry\TrustRule\Exceptions\ServiceTrustRuleNotFoundException;
use Technoholics\ServiceRegistry\TrustRule\Repositories\ServiceTrustRuleRepository;

class ServiceTrustRuleService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RegisteredServiceRepository $serviceRepository,
        private ServiceTrustRuleRepository $trustRuleRepository,
        private CacheInterface $cache,
        private FileLogger $logger,
        private AuditLogService $auditLogService,
        private string $cachePrefix,
        private int $cacheTtl
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function create(CreateTrustRuleRequestDTO $dto): array
    {
        $tenantId = TenantContextResolver::requireTenantId();
        $payload = $dto->toArray();
        $callerName = (string) $payload[ServiceTrustRuleFields::REQUEST_CALLER_SERVICE];
        $targetName = (string) $payload[ServiceTrustRuleFields::REQUEST_TARGET_SERVICE];
        /** @var list<string> $allowedScopes */
        $allowedScopes = $payload[ServiceTrustRuleFields::REQUEST_ALLOWED_SCOPES];

        foreach ($allowedScopes as $scope) {
            ScopeFormatValidator::assertValid((string) $scope);
        }

        $caller = $this->serviceRepository->requireByName($callerName, $tenantId);
        $target = $this->serviceRepository->requireByName($targetName, $tenantId);

        if ($caller->getTenantId() !== $target->getTenantId()) {
            throw new CrossTenantOperationException(
                'Caller and target services must belong to the same tenant'
            );
        }

        if ($this->trustRuleRepository->findByCallerAndTarget($caller->getId(), $target->getId(), $tenantId) !== null) {
            throw new ServiceTrustRuleAlreadyExistsException($callerName, $targetName);
        }

        $result = $this->em->transactional(function () use ($caller, $target, $allowedScopes, $payload, $tenantId): array {
            $entity = $this->trustRuleRepository->create([
                ServiceTrustRuleFields::CALLER_SERVICE_ID => $caller,
                ServiceTrustRuleFields::TARGET_SERVICE_ID => $target,
                ServiceTrustRuleFields::ALLOWED_SCOPES => $allowedScopes,
                ServiceTrustRuleFields::MAX_TTL => (int) $payload[ServiceTrustRuleFields::REQUEST_MAX_TTL],
            ]);

            $this->invalidateTrustRulesCache($tenantId);
            $this->logger->info('Trust rule created', [
                'caller' => $caller->getName(),
                'target' => $target->getName(),
            ]);
            $this->auditLogService->log(
                AuditEventType::TRUST_RULE_CREATED,
                $caller->getName(),
                'trust_rule',
                (string) $entity->getId(),
                ['targetService' => $target->getName()]
            );

            return ServiceTrustRuleResponseDTO::fromEntity($entity)->toArray();
        });

        $this->em->clear();

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        $tenantId = TenantContextResolver::requireTenantId();
        $cacheKey = ServiceCacheKeyHelper::forTrustRules($this->cachePrefix, $tenantId);
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $entities = $this->trustRuleRepository->findAllOrdered($tenantId);
        $rules = array_map(
            static fn ($entity) => ServiceTrustRuleResponseDTO::fromEntity($entity)->toArray(),
            $entities
        );

        $this->cache->set($cacheKey, $rules, $this->cacheTtl);
        $this->em->clear();

        return $rules;
    }

    public function delete(int $id): void
    {
        $tenantId = TenantContextResolver::requireTenantId();
        $entity = $this->trustRuleRepository->findById($id, $tenantId);
        if ($entity === null) {
            throw new ServiceTrustRuleNotFoundException($id);
        }

        $this->em->transactional(function () use ($id, $tenantId): void {
            $this->trustRuleRepository->removeById($id, $tenantId);
            $this->invalidateTrustRulesCache($tenantId);
            $this->logger->info('Trust rule deleted', ['id' => $id]);
            $this->auditLogService->log(
                AuditEventType::TRUST_RULE_DELETED,
                null,
                'trust_rule',
                (string) $id
            );
        });

        $this->em->clear();
    }

    private function invalidateTrustRulesCache(string $tenantId): void
    {
        $this->cache->delete(ServiceCacheKeyHelper::forTrustRules($this->cachePrefix, $tenantId));
    }
}
