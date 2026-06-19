<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Shared\Exceptions;

use Technoholics\Exception\BadRequestException;

/**
 * Thrown when x-tenant-id is missing from the request context.
 */
final class TenantContextMissingException extends BadRequestException
{
    public const BUSINESS_CODE = 'TENANT_CONTEXT_MISSING';

    public function __construct()
    {
        parent::__construct(
            message: 'Tenant ID must be present in request context',
            businessCode: self::BUSINESS_CODE
        );
    }
}
