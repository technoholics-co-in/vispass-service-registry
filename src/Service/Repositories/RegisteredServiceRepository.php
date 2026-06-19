<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\Repositories;

use Doctrine\ORM\QueryBuilder;
use Technoholics\Core\SharedContracts\Repositories\AbstractRepository;
use Technoholics\ServiceRegistry\Service\Constants\ServiceStatus;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredService;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredServiceFields;
use Technoholics\ServiceRegistry\Service\Exceptions\RegisteredServiceNotFoundException;

/**
 * @extends AbstractRepository<RegisteredService>
 */
class RegisteredServiceRepository extends AbstractRepository
{
    private const PARAM_ID = 'id';

    private const PARAM_NAME = 'name';

    private const PARAM_TENANT_ID = 'tenantId';

    private const TABLE_ALIAS = RegisteredServiceFields::TABLE_ALIAS;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): object
    {
        $entity = new RegisteredService();

        if (isset($data[RegisteredServiceFields::TENANT_ID])) {
            $entity->setTenantId((string) $data[RegisteredServiceFields::TENANT_ID]);
        }
        if (isset($data[RegisteredServiceFields::REQUEST_NAME])) {
            $entity->setName((string) $data[RegisteredServiceFields::REQUEST_NAME]);
        }
        if (isset($data[RegisteredServiceFields::REQUEST_TYPE])) {
            $entity->setType((string) $data[RegisteredServiceFields::REQUEST_TYPE]);
        }
        if (isset($data[RegisteredServiceFields::STATUS])) {
            $entity->setStatus((string) $data[RegisteredServiceFields::STATUS]);
        }

        $this->_em->persist($entity);
        $this->_em->flush();

        return $entity;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): object
    {
        $entity = $this->findById((string) $id, (string) $data[RegisteredServiceFields::TENANT_ID]);
        if (!$entity) {
            throw new RegisteredServiceNotFoundException();
        }

        if (isset($data[RegisteredServiceFields::REQUEST_TYPE])) {
            $entity->setType((string) $data[RegisteredServiceFields::REQUEST_TYPE]);
        }
        if (isset($data[RegisteredServiceFields::STATUS])) {
            $entity->setStatus((string) $data[RegisteredServiceFields::STATUS]);
        }

        $entity->setUpdatedAt(new \DateTime());
        $this->_em->flush();

        return $entity;
    }

    public function findById(int|string $id, string $tenantId): ?RegisteredService
    {
        $qb = $this->createQueryBuilder(self::TABLE_ALIAS);
        $alias = self::TABLE_ALIAS;

        $qb->andWhere($qb->expr()->eq($alias . '.' . RegisteredServiceFields::ID, ':' . self::PARAM_ID))
            ->setParameter(self::PARAM_ID, (string) $id);

        $this->applyTenantFilter($qb, $alias, $tenantId);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result instanceof RegisteredService ? $result : null;
    }

    public function findByName(string $name, string $tenantId): ?RegisteredService
    {
        $qb = $this->createQueryBuilder(self::TABLE_ALIAS);
        $alias = self::TABLE_ALIAS;

        $qb->andWhere($qb->expr()->eq($alias . '.' . RegisteredServiceFields::NAME, ':' . self::PARAM_NAME))
            ->setParameter(self::PARAM_NAME, $name);

        $this->applyTenantFilter($qb, $alias, $tenantId);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result instanceof RegisteredService ? $result : null;
    }

    public function existsByName(string $name, string $tenantId): bool
    {
        return $this->findByName($name, $tenantId) !== null;
    }

    public function requireByName(string $name, string $tenantId): RegisteredService
    {
        $entity = $this->findByName($name, $tenantId);
        if ($entity === null) {
            throw new RegisteredServiceNotFoundException($name);
        }

        return $entity;
    }

    protected function getActiveFieldName(): ?string
    {
        return RegisteredServiceFields::STATUS;
    }

    private function applyTenantFilter(QueryBuilder $qb, string $alias, string $tenantId): void
    {
        $qb->andWhere(
            $qb->expr()->eq($alias . '.' . RegisteredServiceFields::TENANT_ID, ':' . self::PARAM_TENANT_ID)
        )->setParameter(self::PARAM_TENANT_ID, $tenantId);
    }
}
