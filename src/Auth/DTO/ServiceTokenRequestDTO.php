<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;
use Technoholics\Core\SharedContracts\DTO\BaseRequestDTO;
use Technoholics\Core\SharedContracts\Validation\ValidationGroup;
use Technoholics\Psr15Middleware\Http\Attributes\Sanitize;
use Technoholics\Psr15Middleware\Http\Attributes\SanitizerType;
use Technoholics\ServiceRegistry\Auth\Transformers\ServiceTokenRequestDtoTransformer;

#[OA\Schema(
    schema: 'ServiceTokenRequestDTO',
    title: 'Service Token Request',
    description: 'Request a short-lived JWT for internal service calls'
)]
class ServiceTokenRequestDTO extends BaseRequestDTO
{
    #[OA\Property(property: 'service', type: 'string', example: 'document-service')]
    #[Sanitize(types: [SanitizerType::TRIM, SanitizerType::LOWERCASE])]
    #[Assert\NotBlank(message: "Field 'service' is required.", groups: [ValidationGroup::CREATE])]
    #[Assert\Length(max: 100, groups: [ValidationGroup::CREATE])]
    public ?string $service = null;

    #[OA\Property(property: 'targetService', type: 'string', example: 'storage-service')]
    #[Sanitize(types: [SanitizerType::TRIM, SanitizerType::LOWERCASE])]
    #[Assert\NotBlank(message: "Field 'targetService' is required.", groups: [ValidationGroup::CREATE])]
    #[Assert\Length(max: 100, groups: [ValidationGroup::CREATE])]
    public ?string $targetService = null;

    /** @var list<string>|null */
    #[OA\Property(
        property: 'requestedScopes',
        type: 'array',
        items: new OA\Items(type: 'string'),
        example: ['storage.upload']
    )]
    #[Assert\NotBlank(message: "Field 'requestedScopes' is required.", groups: [ValidationGroup::CREATE])]
    #[Assert\Count(min: 1, groups: [ValidationGroup::CREATE])]
    public ?array $requestedScopes = null;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRequest(array $data): static
    {
        return ServiceTokenRequestDtoTransformer::fromArray($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'service' => $this->service,
            'targetService' => $this->targetService,
            'requestedScopes' => $this->requestedScopes ?? [],
        ];
    }
}
