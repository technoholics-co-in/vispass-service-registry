<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Credential\Entities;

use Doctrine\ORM\Mapping as ORM;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredService;
use Technoholics\ServiceRegistry\Credential\Repositories\ServiceCredentialRepository;

#[ORM\Entity(repositoryClass: ServiceCredentialRepository::class)]
#[ORM\Table(name: ServiceCredentialFields::TABLE)]
#[ORM\UniqueConstraint(
    name: 'uq_service_credentials_service_version',
    columns: ['service_id', 'rotation_version']
)]
#[ORM\HasLifecycleCallbacks]
class ServiceCredential
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: RegisteredService::class)]
    #[ORM\JoinColumn(name: 'service_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RegisteredService $service;

    #[ORM\Column(type: 'string', length: 50)]
    private string $auth_type = ServiceCredentialFields::AUTH_TYPE_SHARED_SECRET;

    #[ORM\Column(type: 'string', length: 255)]
    private string $secret_hash;

    #[ORM\Column(type: 'integer')]
    private int $rotation_version = 1;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $created_at;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updated_at;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
    }

    public function getService(): RegisteredService
    {
        return $this->service;
    }

    public function setService(RegisteredService $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function setAuthType(string $authType): self
    {
        $this->auth_type = $authType;

        return $this;
    }

    public function setSecretHash(string $secretHash): self
    {
        $this->secret_hash = $secretHash;

        return $this;
    }

    public function setRotationVersion(int $rotationVersion): self
    {
        $this->rotation_version = $rotationVersion;

        return $this;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getSecretHash(): string
    {
        return $this->secret_hash;
    }

    public function getRotationVersion(): int
    {
        return $this->rotation_version;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated_at = new \DateTime();
    }
}
