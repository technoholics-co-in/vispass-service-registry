<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\Entities;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Technoholics\ServiceRegistry\Service\Constants\ServiceStatus;
use Technoholics\ServiceRegistry\Service\Repositories\RegisteredServiceRepository;

#[ORM\Entity(repositoryClass: RegisteredServiceRepository::class)]
#[ORM\Table(name: RegisteredServiceFields::TABLE)]
#[ORM\UniqueConstraint(
    name: 'uq_services_tenant_name',
    columns: [RegisteredServiceFields::TENANT_ID, RegisteredServiceFields::NAME]
)]
#[ORM\HasLifecycleCallbacks]
class RegisteredService
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: RegisteredServiceFields::TENANT_ID, type: 'guid')]
    private string $tenant_id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = ServiceStatus::ACTIVE;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $created_at;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updated_at;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function setTenantId(string $tenantId): self
    {
        $this->tenant_id = $tenantId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated_at = new \DateTime();
    }

    public function isActive(): bool
    {
        return $this->status === ServiceStatus::ACTIVE;
    }
}
