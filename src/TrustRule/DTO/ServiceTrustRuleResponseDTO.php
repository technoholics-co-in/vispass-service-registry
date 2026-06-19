<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\TrustRule\DTO;

use Technoholics\ServiceRegistry\TrustRule\Entities\ServiceTrustRule;
use Technoholics\ServiceRegistry\TrustRule\Entities\ServiceTrustRuleFields;

class ServiceTrustRuleResponseDTO
{
    /**
     * @param list<string> $allowedScopes
     */
    public function __construct(
        public readonly int $id,
        public readonly string $callerService,
        public readonly string $targetService,
        public readonly array $allowedScopes,
        public readonly int $maxTtl
    ) {
    }

    public static function fromEntity(ServiceTrustRule $entity): self
    {
        return new self(
            id: $entity->getId(),
            callerService: $entity->getCallerService()->getName(),
            targetService: $entity->getTargetService()->getName(),
            allowedScopes: $entity->getAllowedScopes(),
            maxTtl: $entity->getMaxTtl()
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ServiceTrustRuleFields::ID => $this->id,
            ServiceTrustRuleFields::REQUEST_CALLER_SERVICE => $this->callerService,
            ServiceTrustRuleFields::REQUEST_TARGET_SERVICE => $this->targetService,
            ServiceTrustRuleFields::REQUEST_ALLOWED_SCOPES => $this->allowedScopes,
            ServiceTrustRuleFields::REQUEST_MAX_TTL => $this->maxTtl,
        ];
    }
}
