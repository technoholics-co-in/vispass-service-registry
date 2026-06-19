<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Scope\Transformers;

use Technoholics\Core\SharedContracts\DTO\RequestDtoTransformer;
use Technoholics\ServiceRegistry\Scope\DTO\AddServiceScopeRequestDTO;

final class AddServiceScopeRequestDtoTransformer extends RequestDtoTransformer
{
    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): AddServiceScopeRequestDTO
    {
        return self::toDto($data, AddServiceScopeRequestDTO::class);
    }
}
