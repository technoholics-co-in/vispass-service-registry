<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Shared\Exceptions;

use Technoholics\Exception\BadRequestException;

/**
 * Thrown when an operation spans multiple tenants.
 */
final class CrossTenantOperationException extends BadRequestException
{
    public const BUSINESS_CODE = 'CROSS_TENANT_OPERATION_DENIED';

    public function __construct(string $message = 'Operation is not allowed across tenants')
    {
        parent::__construct(
            message: $message,
            businessCode: self::BUSINESS_CODE
        );
    }
}
