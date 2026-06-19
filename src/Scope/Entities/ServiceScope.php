<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Scope\Entities;

use Doctrine\ORM\Mapping as ORM;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredService;
use Technoholics\ServiceRegistry\Scope\Repositories\ServiceScopeRepository;

#[ORM\Entity(repositoryClass: ServiceScopeRepository::class)]
#[ORM\Table(name: ServiceScopeFields::TABLE)]
#[ORM\UniqueConstraint(name: 'uq_service_scopes_service_scope', columns: ['service_id', 'scope'])]
class ServiceScope
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: RegisteredService::class)]
    #[ORM\JoinColumn(name: 'service_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RegisteredService $service;

    #[ORM\Column(type: 'string', length: 100)]
    private string $scope;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }
}
