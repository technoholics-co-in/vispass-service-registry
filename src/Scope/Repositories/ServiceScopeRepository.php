<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Scope\Repositories;

use Technoholics\Core\SharedContracts\Repositories\AbstractRepository;
use Technoholics\ServiceRegistry\Scope\Entities\ServiceScope;
use Technoholics\ServiceRegistry\Scope\Entities\ServiceScopeFields;
use Technoholics\ServiceRegistry\Scope\Exceptions\ServiceScopeNotFoundException;

/**
 * @extends AbstractRepository<ServiceScope>
 */
class ServiceScopeRepository extends AbstractRepository
{
    private const PARAM_SERVICE_ID = 'serviceId';

    private const PARAM_SCOPE = 'scope';

    private const TABLE_ALIAS = ServiceScopeFields::TABLE_ALIAS;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): object
    {
        $entity = new ServiceScope();
        $entity->setService($data[ServiceScopeFields::SERVICE_ID]);
        $entity->setScope((string) $data[ServiceScopeFields::SCOPE]);

        $this->_em->persist($entity);
        $this->_em->flush();

        return $entity;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): object
    {
        throw new \BadMethodCallException('Service scopes are immutable; delete and recreate instead.');
    }

    public function findByServiceIdAndScope(string $serviceId, string $scope): ?ServiceScope
    {
        $qb = $this->createQueryBuilder(self::TABLE_ALIAS);
        $alias = self::TABLE_ALIAS;

        $qb->andWhere($qb->expr()->eq($alias . '.service', ':' . self::PARAM_SERVICE_ID))
            ->andWhere($qb->expr()->eq($alias . '.' . ServiceScopeFields::SCOPE, ':' . self::PARAM_SCOPE))
            ->setParameter(self::PARAM_SERVICE_ID, $serviceId)
            ->setParameter(self::PARAM_SCOPE, $scope);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result instanceof ServiceScope ? $result : null;
    }

    /**
     * @return list<ServiceScope>
     */
    public function findAllByServiceId(string $serviceId): array
    {
        $qb = $this->createQueryBuilder(self::TABLE_ALIAS);
        $alias = self::TABLE_ALIAS;

        $qb->andWhere($qb->expr()->eq($alias . '.service', ':' . self::PARAM_SERVICE_ID))
            ->setParameter(self::PARAM_SERVICE_ID, $serviceId)
            ->orderBy($alias . '.' . ServiceScopeFields::SCOPE, 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function deleteByServiceIdAndScope(string $serviceId, string $scope): void
    {
        $entity = $this->findByServiceIdAndScope($serviceId, $scope);
        if ($entity === null) {
            throw new ServiceScopeNotFoundException('', $scope);
        }

        $this->_em->remove($entity);
        $this->_em->flush();
    }

    public function existsByServiceIdAndScope(string $serviceId, string $scope): bool
    {
        return $this->findByServiceIdAndScope($serviceId, $scope) !== null;
    }
}
