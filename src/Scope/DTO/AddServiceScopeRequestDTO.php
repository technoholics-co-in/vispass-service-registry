<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Scope\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;
use Technoholics\Core\SharedContracts\DTO\BaseRequestDTO;
use Technoholics\Core\SharedContracts\Validation\ValidationGroup;
use Technoholics\Psr15Middleware\Http\Attributes\Sanitize;
use Technoholics\Psr15Middleware\Http\Attributes\SanitizerType;
use Technoholics\ServiceRegistry\Scope\Entities\ServiceScopeFields;
use Technoholics\ServiceRegistry\Scope\Transformers\AddServiceScopeRequestDtoTransformer;

#[OA\Schema(
    schema: 'AddServiceScopeRequestDTO',
    title: 'Add Service Scope Request',
    description: 'Assign an explicit scope to a registered service'
)]
class AddServiceScopeRequestDTO extends BaseRequestDTO
{
    #[OA\Property(property: 'scope', type: 'string', example: 'storage.upload')]
    #[Sanitize(types: [SanitizerType::TRIM, SanitizerType::LOWERCASE])]
    #[Assert\NotBlank(message: "Field 'scope' is required.", groups: [ValidationGroup::CREATE])]
    #[Assert\Length(max: 100, groups: [ValidationGroup::CREATE])]
    public ?string $scope = null;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRequest(array $data): static
    {
        return AddServiceScopeRequestDtoTransformer::fromArray($data);
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            ServiceScopeFields::REQUEST_SCOPE => $this->scope,
        ];
    }
}
