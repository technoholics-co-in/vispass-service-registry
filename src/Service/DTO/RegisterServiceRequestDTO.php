<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;
use Technoholics\Core\SharedContracts\DTO\BaseRequestDTO;
use Technoholics\Core\SharedContracts\Validation\ValidationGroup;
use Technoholics\Psr15Middleware\Http\Attributes\Sanitize;
use Technoholics\Psr15Middleware\Http\Attributes\SanitizerType;
use Technoholics\ServiceRegistry\Service\Constants\ServiceType;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredServiceFields;
use Technoholics\ServiceRegistry\Service\Transformers\RegisterServiceRequestDtoTransformer;

#[OA\Schema(
    schema: 'RegisterServiceRequestDTO',
    title: 'Register Service Request',
    description: 'Payload for registering an internal microservice'
)]
class RegisterServiceRequestDTO extends BaseRequestDTO
{
    #[OA\Property(property: 'name', type: 'string', example: 'document-service')]
    #[Sanitize(types: [SanitizerType::TRIM, SanitizerType::LOWERCASE])]
    #[Assert\NotBlank(message: "Field 'name' is required.", groups: [ValidationGroup::CREATE])]
    #[Assert\Length(max: 100, groups: [ValidationGroup::CREATE])]
    #[Assert\Regex(
        pattern: '/^[a-z][a-z0-9-]*$/',
        message: "Field 'name' must be a lowercase slug (e.g. document-service).",
        groups: [ValidationGroup::CREATE]
    )]
    public ?string $name = null;

    #[OA\Property(property: 'type', type: 'string', example: 'api')]
    #[Sanitize(types: [SanitizerType::TRIM, SanitizerType::LOWERCASE])]
    #[Assert\NotBlank(message: "Field 'type' is required.", groups: [ValidationGroup::CREATE])]
    #[Assert\Choice(
        choices: [ServiceType::API, ServiceType::WORKER, ServiceType::CRON, ServiceType::BFF],
        groups: [ValidationGroup::CREATE]
    )]
    public ?string $type = null;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRequest(array $data): static
    {
        return RegisterServiceRequestDtoTransformer::fromArray($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            RegisteredServiceFields::REQUEST_NAME => $this->name,
            RegisteredServiceFields::REQUEST_TYPE => $this->type,
        ];
    }
}
