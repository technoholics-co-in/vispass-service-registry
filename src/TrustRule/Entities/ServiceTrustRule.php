<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\TrustRule\Entities;

use Doctrine\ORM\Mapping as ORM;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredService;
use Technoholics\ServiceRegistry\TrustRule\Repositories\ServiceTrustRuleRepository;

#[ORM\Entity(repositoryClass: ServiceTrustRuleRepository::class)]
#[ORM\Table(name: ServiceTrustRuleFields::TABLE)]
#[ORM\UniqueConstraint(
    name: 'uq_service_trust_rules_caller_target',
    columns: ['caller_service_id', 'target_service_id']
)]
#[ORM\HasLifecycleCallbacks]
class ServiceTrustRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: RegisteredService::class)]
    #[ORM\JoinColumn(name: 'caller_service_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RegisteredService $callerService;

    #[ORM\ManyToOne(targetEntity: RegisteredService::class)]
    #[ORM\JoinColumn(name: 'target_service_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RegisteredService $targetService;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $allowed_scopes = [];

    #[ORM\Column(type: 'integer')]
    private int $max_ttl = 900;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $created_at;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updated_at;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCallerService(): RegisteredService
    {
        return $this->callerService;
    }

    public function setCallerService(RegisteredService $callerService): self
    {
        $this->callerService = $callerService;

        return $this;
    }

    public function getTargetService(): RegisteredService
    {
        return $this->targetService;
    }

    public function setTargetService(RegisteredService $targetService): self
    {
        $this->targetService = $targetService;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getAllowedScopes(): array
    {
        return $this->allowed_scopes;
    }

    /**
     * @param list<string> $allowedScopes
     */
    public function setAllowedScopes(array $allowedScopes): self
    {
        $this->allowed_scopes = $allowedScopes;

        return $this;
    }

    public function getMaxTtl(): int
    {
        return $this->max_ttl;
    }

    public function setMaxTtl(int $maxTtl): self
    {
        $this->max_ttl = $maxTtl;

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

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated_at = new \DateTime();
    }
}
