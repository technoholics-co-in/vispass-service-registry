<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\TrustRule\Transformers;

use Technoholics\Core\SharedContracts\DTO\RequestDtoTransformer;
use Technoholics\ServiceRegistry\TrustRule\DTO\CreateTrustRuleRequestDTO;

final class CreateTrustRuleRequestDtoTransformer extends RequestDtoTransformer
{
    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): CreateTrustRuleRequestDTO
    {
        return self::toDto($data, CreateTrustRuleRequestDTO::class);
    }
}
