<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\Transformers;

use Technoholics\Core\SharedContracts\DTO\RequestDtoTransformer;
use Technoholics\ServiceRegistry\Service\DTO\RegisterServiceRequestDTO;

final class RegisterServiceRequestDtoTransformer extends RequestDtoTransformer
{
    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): RegisterServiceRequestDTO
    {
        return self::toDto($data, RegisterServiceRequestDTO::class);
    }
}
