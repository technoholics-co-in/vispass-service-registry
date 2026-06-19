<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Repositories;

use Technoholics\Core\SharedContracts\Repositories\AbstractRepository;
use Technoholics\ServiceRegistry\Auth\Entities\AuditLog;
use Technoholics\ServiceRegistry\Auth\Entities\AuditLogFields;

/**
 * @extends AbstractRepository<AuditLog>
 */
class AuditLogRepository extends AbstractRepository
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): object
    {
        $entity = new AuditLog();
        $entity->setTenantId((string) $data[AuditLogFields::TENANT_ID]);
        $entity->setEventType((string) $data[AuditLogFields::EVENT_TYPE]);
        $entity->setActorService($data[AuditLogFields::ACTOR_SERVICE] ?? null);
        $entity->setResourceType($data[AuditLogFields::RESOURCE_TYPE] ?? null);
        $entity->setResourceId($data[AuditLogFields::RESOURCE_ID] ?? null);
        $entity->setDetails($data[AuditLogFields::DETAILS] ?? []);

        $this->_em->persist($entity);
        $this->_em->flush();

        return $entity;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): object
    {
        throw new \BadMethodCallException('Audit logs are append-only.');
    }
}
