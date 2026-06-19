<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Service\Transformers;

use Technoholics\ServiceRegistry\Service\Entities\RegisteredService;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredServiceFields;

/**
 * Transforms RegisteredService entities to arrays.
 */
class RegisteredServiceTransformer
{
    /**
     * @return array<string, string>
     */
    public static function toArray(RegisteredService $entity): array
    {
        return [
            RegisteredServiceFields::ID => $entity->getId(),
            RegisteredServiceFields::NAME => $entity->getName(),
            RegisteredServiceFields::TYPE => $entity->getType(),
            RegisteredServiceFields::STATUS => $entity->getStatus(),
        ];
    }
}
