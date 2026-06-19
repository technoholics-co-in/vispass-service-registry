<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Entities;

use Doctrine\ORM\Mapping as ORM;
use Technoholics\ServiceRegistry\Auth\Repositories\SigningKeyRepository;

#[ORM\Entity(repositoryClass: SigningKeyRepository::class)]
#[ORM\Table(name: SigningKeyFields::TABLE)]
#[ORM\UniqueConstraint(
    name: 'uq_signing_keys_tenant_kid',
    columns: [SigningKeyFields::TENANT_ID, SigningKeyFields::KID]
)]
class SigningKey
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(name: SigningKeyFields::TENANT_ID, type: 'guid')]
    private string $tenant_id;

    #[ORM\Column(type: 'string', length: 64)]
    private string $kid;

    #[ORM\Column(type: 'string', length: 10)]
    private string $algorithm = SigningKeyFields::ALGORITHM_RS256;

    #[ORM\Column(type: 'text')]
    private string $public_key;

    #[ORM\Column(type: 'text')]
    private string $private_key;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $created_at;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $rotated_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    public function getId(): int
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

    public function getKid(): string
    {
        return $this->kid;
    }

    public function setKid(string $kid): self
    {
        $this->kid = $kid;

        return $this;
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function setAlgorithm(string $algorithm): self
    {
        $this->algorithm = $algorithm;

        return $this;
    }

    public function getPublicKey(): string
    {
        return $this->public_key;
    }

    public function setPublicKey(string $publicKey): self
    {
        $this->public_key = $publicKey;

        return $this;
    }

    public function getPrivateKey(): string
    {
        return $this->private_key;
    }

    public function setPrivateKey(string $privateKey): self
    {
        $this->private_key = $privateKey;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getRotatedAt(): ?\DateTime
    {
        return $this->rotated_at;
    }

    public function setRotatedAt(?\DateTime $rotatedAt): self
    {
        $this->rotated_at = $rotatedAt;

        return $this;
    }
}
