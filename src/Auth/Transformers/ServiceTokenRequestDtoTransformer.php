<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Auth\Transformers;

use Technoholics\Core\SharedContracts\DTO\RequestDtoTransformer;
use Technoholics\ServiceRegistry\Auth\DTO\ServiceTokenRequestDTO;

final class ServiceTokenRequestDtoTransformer extends RequestDtoTransformer
{
    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): ServiceTokenRequestDTO
    {
        return self::toDto($data, ServiceTokenRequestDTO::class);
    }
}
