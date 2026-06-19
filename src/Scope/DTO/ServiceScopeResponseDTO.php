<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Scope\DTO;

use Technoholics\ServiceRegistry\Scope\Entities\ServiceScope;
use Technoholics\ServiceRegistry\Scope\Entities\ServiceScopeFields;

class ServiceScopeResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $serviceName,
        public readonly string $scope
    ) {
    }

    public static function fromEntity(ServiceScope $entity): self
    {
        return new self(
            id: $entity->getId(),
            serviceName: $entity->getService()->getName(),
            scope: $entity->getScope()
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            ServiceScopeFields::ID => $this->id,
            'serviceName' => $this->serviceName,
            ServiceScopeFields::SCOPE => $this->scope,
        ];
    }
}
