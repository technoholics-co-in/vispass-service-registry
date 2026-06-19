<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Tests\Service;

use PHPUnit\Framework\TestCase;
use Technoholics\ServiceRegistry\Service\Helpers\ServiceCacheKeyHelper;
use Technoholics\ServiceRegistry\Tests\Support\TestTenantContext;

final class ServiceModuleTest extends TestCase
{
    public function testCacheKeyIncludesTenantAndServicePrefix(): void
    {
        $key = ServiceCacheKeyHelper::forService(
            'service-registry:',
            TestTenantContext::TENANT_ID,
            'document-service'
        );
        $this->assertSame(
            'service-registry:' . TestTenantContext::TENANT_ID . ':service:document-service',
            $key
        );
    }
}
