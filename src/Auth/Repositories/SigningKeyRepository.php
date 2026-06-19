<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Repositories;

use Doctrine\ORM\QueryBuilder;
use Technoholics\Core\SharedContracts\Repositories\AbstractRepository;
use Technoholics\ServiceRegistry\Auth\Entities\SigningKey;
use Technoholics\ServiceRegistry\Auth\Entities\SigningKeyFields;

/**
 * @extends AbstractRepository<SigningKey>
 */
class SigningKeyRepository extends AbstractRepository
{
    private const PARAM_TENANT_ID = 'tenantId';

    private const TABLE_ALIAS = SigningKeyFields::TABLE_ALIAS;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): object
    {
        $entity = new SigningKey();
        $entity->setTenantId((string) $data[SigningKeyFields::TENANT_ID]);
        $entity->setKid((string) $data[SigningKeyFields::KID]);
        $entity->setAlgorithm((string) ($data[SigningKeyFields::ALGORITHM] ?? SigningKeyFields::ALGORITHM_RS256));
        $entity->setPublicKey((string) $data[SigningKeyFields::PUBLIC_KEY]);
        $entity->setPrivateKey((string) $data[SigningKeyFields::PRIVATE_KEY]);
        $entity->setActive((bool) ($data[SigningKeyFields::ACTIVE] ?? true));

        $this->_em->persist($entity);
        $this->_em->flush();

        return $entity;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): object
    {
        $entity = $this->find((int) $id);
        if (!$entity instanceof SigningKey) {
            throw new \RuntimeException('Signing key not found');
        }

        if (isset($data[SigningKeyFields::ACTIVE])) {
            $entity->setActive((bool) $data[SigningKeyFields::ACTIVE]);
        }
        if (isset($data[SigningKeyFields::ROTATED_AT])) {
            $entity->setRotatedAt($data[SigningKeyFields::ROTATED_AT]);
        }

        $this->_em->flush();

        return $entity;
    }

    public function findActiveKey(string $tenantId): ?SigningKey
    {
        $qb = $this->createActiveKeysQueryBuilder($tenantId);
        $qb->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result instanceof SigningKey ? $result : null;
    }

    /**
     * @return list<SigningKey>
     */
    public function findAllActiveKeys(string $tenantId): array
    {
        return $this->createActiveKeysQueryBuilder($tenantId)->getQuery()->getResult();
    }

    public function deactivateAll(string $tenantId): void
    {
        $keys = $this->findAllActiveKeys($tenantId);
        foreach ($keys as $key) {
            $key->setActive(false);
            $key->setRotatedAt(new \DateTime());
        }
        $this->_em->flush();
    }

    private function createActiveKeysQueryBuilder(string $tenantId): QueryBuilder
    {
        $qb = $this->createQueryBuilder(self::TABLE_ALIAS);
        $alias = self::TABLE_ALIAS;

        $qb->andWhere($qb->expr()->eq($alias . '.' . SigningKeyFields::ACTIVE, ':active'))
            ->andWhere(
                $qb->expr()->eq($alias . '.' . SigningKeyFields::TENANT_ID, ':' . self::PARAM_TENANT_ID)
            )
            ->setParameter('active', true)
            ->setParameter(self::PARAM_TENANT_ID, $tenantId)
            ->orderBy($alias . '.' . SigningKeyFields::ID, 'DESC');

        return $qb;
    }
}
