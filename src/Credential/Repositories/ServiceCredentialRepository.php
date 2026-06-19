<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Credential\Repositories;

use Technoholics\Core\SharedContracts\Repositories\AbstractRepository;
use Technoholics\ServiceRegistry\Credential\Entities\ServiceCredential;
use Technoholics\ServiceRegistry\Credential\Entities\ServiceCredentialFields;

/**
 * @extends AbstractRepository<ServiceCredential>
 */
class ServiceCredentialRepository extends AbstractRepository
{
    private const PARAM_SERVICE_ID = 'serviceId';

    private const TABLE_ALIAS = 'sc';

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): object
    {
        $entity = new ServiceCredential();
        $entity->setService($data[ServiceCredentialFields::SERVICE_ID]);
        $entity->setAuthType(
            (string) ($data[ServiceCredentialFields::AUTH_TYPE] ?? ServiceCredentialFields::AUTH_TYPE_SHARED_SECRET)
        );
        $entity->setSecretHash((string) $data[ServiceCredentialFields::SECRET_HASH]);
        $entity->setRotationVersion((int) ($data[ServiceCredentialFields::ROTATION_VERSION] ?? 1));
        $entity->setActive((bool) ($data[ServiceCredentialFields::ACTIVE] ?? true));

        $this->_em->persist($entity);
        $this->_em->flush();

        return $entity;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): object
    {
        throw new \BadMethodCallException('Use credential rotation workflow in Phase 3.');
    }

    public function getLatestVersionForService(string $serviceId): int
    {
        $qb = $this->createQueryBuilder(self::TABLE_ALIAS);
        $alias = self::TABLE_ALIAS;

        $qb->select('MAX(' . $alias . '.' . ServiceCredentialFields::ROTATION_VERSION . ')')
            ->andWhere($qb->expr()->eq($alias . '.service', ':' . self::PARAM_SERVICE_ID))
            ->setParameter(self::PARAM_SERVICE_ID, $serviceId);

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result !== null ? (int) $result : 0;
    }

    /**
     * @return list<ServiceCredential>
     */
    public function findActiveByServiceId(string $serviceId): array
    {
        $qb = $this->createQueryBuilder(self::TABLE_ALIAS);
        $alias = self::TABLE_ALIAS;

        $qb->andWhere($qb->expr()->eq($alias . '.service', ':' . self::PARAM_SERVICE_ID))
            ->andWhere($qb->expr()->eq($alias . '.' . ServiceCredentialFields::ACTIVE, ':active'))
            ->setParameter(self::PARAM_SERVICE_ID, $serviceId)
            ->setParameter('active', true)
            ->orderBy($alias . '.' . ServiceCredentialFields::ROTATION_VERSION, 'DESC');

        return $qb->getQuery()->getResult();
    }
}
