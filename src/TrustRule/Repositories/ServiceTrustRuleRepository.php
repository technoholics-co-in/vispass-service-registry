<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\TrustRule\Repositories;

use Technoholics\Core\SharedContracts\Repositories\AbstractRepository;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredServiceFields;
use Technoholics\ServiceRegistry\TrustRule\Entities\ServiceTrustRule;
use Technoholics\ServiceRegistry\TrustRule\Entities\ServiceTrustRuleFields;
use Technoholics\ServiceRegistry\TrustRule\Exceptions\ServiceTrustRuleNotFoundException;

/**
 * @extends AbstractRepository<ServiceTrustRule>
 */
class ServiceTrustRuleRepository extends AbstractRepository
{
    private const PARAM_ID = 'id';

    private const PARAM_CALLER_ID = 'callerId';

    private const PARAM_TARGET_ID = 'targetId';

    private const PARAM_TENANT_ID = 'tenantId';

    private const TABLE_ALIAS = ServiceTrustRuleFields::TABLE_ALIAS;

    private const CALLER_ALIAS = 'callerSvc';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): object
    {
        $entity = new ServiceTrustRule();
        $entity->setCallerService($data[ServiceTrustRuleFields::CALLER_SERVICE_ID]);
        $entity->setTargetService($data[ServiceTrustRuleFields::TARGET_SERVICE_ID]);
        $entity->setAllowedScopes($data[ServiceTrustRuleFields::ALLOWED_SCOPES] ?? []);
        $entity->setMaxTtl((int) ($data[ServiceTrustRuleFields::MAX_TTL] ?? 900));

        $this->_em->persist($entity);
        $this->_em->flush();

        return $entity;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): object
    {
        throw new \BadMethodCallException('Trust rule updates are not supported.');
    }

    public function findById(int $id, string $tenantId): ?ServiceTrustRule
    {
        $qb = $this->createQueryBuilder(self::TABLE_ALIAS);
        $alias = self::TABLE_ALIAS;

        $qb->andWhere($qb->expr()->eq($alias . '.' . ServiceTrustRuleFields::ID, ':' . self::PARAM_ID))
            ->setParameter(self::PARAM_ID, $id);

        $this->applyTenantFilter($qb, $alias, $tenantId);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result instanceof ServiceTrustRule ? $result : null;
    }

    public function findByCallerAndTarget(string $callerId, string $targetId, string $tenantId): ?ServiceTrustRule
    {
        $qb = $this->createQueryBuilder(self::TABLE_ALIAS);
        $alias = self::TABLE_ALIAS;

        $qb->andWhere($qb->expr()->eq($alias . '.callerService', ':' . self::PARAM_CALLER_ID))
            ->andWhere($qb->expr()->eq($alias . '.targetService', ':' . self::PARAM_TARGET_ID))
            ->setParameter(self::PARAM_CALLER_ID, $callerId)
            ->setParameter(self::PARAM_TARGET_ID, $targetId);

        $this->applyTenantFilter($qb, $alias, $tenantId);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result instanceof ServiceTrustRule ? $result : null;
    }

    /**
     * @return list<ServiceTrustRule>
     */
    public function findAllOrdered(string $tenantId): array
    {
        $qb = $this->createQueryBuilder(self::TABLE_ALIAS);
        $alias = self::TABLE_ALIAS;

        $this->applyTenantFilter($qb, $alias, $tenantId);

        $qb->orderBy($alias . '.' . ServiceTrustRuleFields::ID, 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function removeById(int $id, string $tenantId): void
    {
        $entity = $this->findById($id, $tenantId);
        if ($entity === null) {
            throw new ServiceTrustRuleNotFoundException($id);
        }

        $this->_em->remove($entity);
        $this->_em->flush();
    }

    private function applyTenantFilter(
        \Doctrine\ORM\QueryBuilder $qb,
        string $alias,
        string $tenantId
    ): void {
        $callerAlias = self::CALLER_ALIAS;
        $qb->innerJoin($alias . '.callerService', $callerAlias)
            ->andWhere(
                $qb->expr()->eq($callerAlias . '.' . RegisteredServiceFields::TENANT_ID, ':' . self::PARAM_TENANT_ID)
            )
            ->setParameter(self::PARAM_TENANT_ID, $tenantId);
    }
}
