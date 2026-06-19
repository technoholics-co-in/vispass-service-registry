<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\DTO;

use Technoholics\ServiceRegistry\Service\Entities\RegisteredService;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredServiceFields;

/**
 * Response DTO for registered service metadata.
 */
class RegisteredServiceResponseDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $type,
        public readonly string $status
    ) {
    }

    public static function fromEntity(RegisteredService $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            type: $entity->getType(),
            status: $entity->getStatus()
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            RegisteredServiceFields::ID => $this->id,
            RegisteredServiceFields::NAME => $this->name,
            RegisteredServiceFields::TYPE => $this->type,
            RegisteredServiceFields::STATUS => $this->status,
        ];
    }

    /**
     * Discovery response omits internal id.
     *
     * @return array<string, string>
     */
    public function toDiscoveryArray(): array
    {
        return [
            RegisteredServiceFields::NAME => $this->name,
            RegisteredServiceFields::TYPE => $this->type,
            RegisteredServiceFields::STATUS => $this->status,
        ];
    }
}
