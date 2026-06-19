<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\TrustRule\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;
use Technoholics\Core\SharedContracts\DTO\BaseRequestDTO;
use Technoholics\Core\SharedContracts\Validation\ValidationGroup;
use Technoholics\Psr15Middleware\Http\Attributes\Sanitize;
use Technoholics\Psr15Middleware\Http\Attributes\SanitizerType;
use Technoholics\ServiceRegistry\TrustRule\Entities\ServiceTrustRuleFields;
use Technoholics\ServiceRegistry\TrustRule\Transformers\CreateTrustRuleRequestDtoTransformer;

#[OA\Schema(
    schema: 'CreateTrustRuleRequestDTO',
    title: 'Create Trust Rule Request',
    description: 'Define allowed communication between internal services'
)]
class CreateTrustRuleRequestDTO extends BaseRequestDTO
{
    #[OA\Property(property: 'callerService', type: 'string', example: 'document-service')]
    #[Sanitize(types: [SanitizerType::TRIM, SanitizerType::LOWERCASE])]
    #[Assert\NotBlank(message: "Field 'callerService' is required.", groups: [ValidationGroup::CREATE])]
    #[Assert\Length(max: 100, groups: [ValidationGroup::CREATE])]
    public ?string $callerService = null;

    #[OA\Property(property: 'targetService', type: 'string', example: 'storage-service')]
    #[Sanitize(types: [SanitizerType::TRIM, SanitizerType::LOWERCASE])]
    #[Assert\NotBlank(message: "Field 'targetService' is required.", groups: [ValidationGroup::CREATE])]
    #[Assert\Length(max: 100, groups: [ValidationGroup::CREATE])]
    public ?string $targetService = null;

    /** @var list<string>|null */
    #[OA\Property(
        property: 'allowedScopes',
        type: 'array',
        items: new OA\Items(type: 'string'),
        example: ['storage.upload', 'storage.read']
    )]
    #[Assert\NotBlank(message: "Field 'allowedScopes' is required.", groups: [ValidationGroup::CREATE])]
    #[Assert\Count(min: 1, groups: [ValidationGroup::CREATE])]
    public ?array $allowedScopes = null;

    #[OA\Property(property: 'maxTtl', type: 'integer', example: 900)]
    #[Assert\Positive(groups: [ValidationGroup::CREATE])]
    #[Assert\LessThanOrEqual(value: 3600, groups: [ValidationGroup::CREATE])]
    public ?int $maxTtl = 900;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRequest(array $data): static
    {
        return CreateTrustRuleRequestDtoTransformer::fromArray($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ServiceTrustRuleFields::REQUEST_CALLER_SERVICE => $this->callerService,
            ServiceTrustRuleFields::REQUEST_TARGET_SERVICE => $this->targetService,
            ServiceTrustRuleFields::REQUEST_ALLOWED_SCOPES => $this->allowedScopes ?? [],
            ServiceTrustRuleFields::REQUEST_MAX_TTL => $this->maxTtl ?? 900,
        ];
    }
}
