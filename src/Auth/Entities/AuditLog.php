<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Entities;

use Doctrine\ORM\Mapping as ORM;
use Technoholics\ServiceRegistry\Auth\Repositories\AuditLogRepository;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: AuditLogFields::TABLE)]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(name: AuditLogFields::TENANT_ID, type: 'guid')]
    private string $tenant_id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $event_type;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $actor_service = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $resource_type = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $resource_id = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $details = [];

    #[ORM\Column(type: 'datetime')]
    private \DateTime $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    public function setTenantId(string $tenantId): self
    {
        $this->tenant_id = $tenantId;

        return $this;
    }

    public function setEventType(string $eventType): self
    {
        $this->event_type = $eventType;

        return $this;
    }

    public function setActorService(?string $actorService): self
    {
        $this->actor_service = $actorService;

        return $this;
    }

    public function setResourceType(?string $resourceType): self
    {
        $this->resource_type = $resourceType;

        return $this;
    }

    public function setResourceId(?string $resourceId): self
    {
        $this->resource_id = $resourceId;

        return $this;
    }

    /**
     * @param array<string, mixed> $details
     */
    public function setDetails(array $details): self
    {
        $this->details = $details;

        return $this;
    }
}
