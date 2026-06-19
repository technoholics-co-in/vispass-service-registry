<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Tests\Scope;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Technoholics\ServiceRegistry\Tests\Support\TestFileLoggerFactory;
use Technoholics\ServiceRegistry\Tests\Support\TestTenantContext;
use Technoholics\ServiceRegistry\Auth\Services\AuditLogService;
use Technoholics\ServiceRegistry\Scope\DTO\AddServiceScopeRequestDTO;
use Technoholics\ServiceRegistry\Scope\Exceptions\InvalidScopeException;
use Technoholics\ServiceRegistry\Scope\Exceptions\ServiceScopeAlreadyExistsException;
use Technoholics\ServiceRegistry\Scope\Repositories\ServiceScopeRepository;
use Technoholics\ServiceRegistry\Scope\Services\ServiceScopeService;
use Technoholics\ServiceRegistry\Service\Constants\ServiceStatus;
use Technoholics\ServiceRegistry\Service\Constants\ServiceType;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredService;
use Technoholics\ServiceRegistry\Service\Helpers\ServiceCacheKeyHelper;
use Technoholics\ServiceRegistry\Service\Repositories\RegisteredServiceRepository;

final class ServiceScopeServiceTest extends TestCase
{
    private const CACHE_PREFIX = 'service-registry:';

    protected function setUp(): void
    {
        parent::setUp();
        TestTenantContext::activate();
    }

    protected function tearDown(): void
    {
        TestTenantContext::clear();
        parent::tearDown();
    }

    public function testAddScopeRejectsWildcard(): void
    {
        $service = $this->createServiceScopeService(
            $this->createMock(RegisteredServiceRepository::class),
            $this->createMock(ServiceScopeRepository::class)
        );

        $dto = AddServiceScopeRequestDTO::fromRequest(['scope' => 'storage.*']);

        $this->expectException(InvalidScopeException::class);
        $service->addScope('document-service', $dto);
    }

    public function testListScopesUsesCache(): void
    {
        $serviceEntity = $this->makeService('document-service');

        $serviceRepository = $this->createMock(RegisteredServiceRepository::class);
        $serviceRepository->method('requireByName')->willReturn($serviceEntity);

        $scopeRepository = $this->createMock(ServiceScopeRepository::class);
        $scopeRepository->expects($this->never())->method('findAllByServiceId');

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with(ServiceCacheKeyHelper::forScopes(
                self::CACHE_PREFIX,
                TestTenantContext::TENANT_ID,
                'document-service'
            ))
            ->willReturn(['storage.read']);

        $service = $this->createServiceScopeService($serviceRepository, $scopeRepository, $cache);

        $this->assertSame(['storage.read'], $service->listScopes('document-service'));
    }

    public function testAddScopeThrowsWhenDuplicate(): void
    {
        $serviceEntity = $this->makeService('document-service');

        $serviceRepository = $this->createMock(RegisteredServiceRepository::class);
        $serviceRepository->method('requireByName')->willReturn($serviceEntity);

        $scopeRepository = $this->createMock(ServiceScopeRepository::class);
        $scopeRepository->method('existsByServiceIdAndScope')->willReturn(true);

        $service = $this->createServiceScopeService($serviceRepository, $scopeRepository);
        $dto = AddServiceScopeRequestDTO::fromRequest(['scope' => 'storage.upload']);

        $this->expectException(ServiceScopeAlreadyExistsException::class);
        $service->addScope('document-service', $dto);
    }

    private function createServiceScopeService(
        RegisteredServiceRepository $serviceRepository,
        ServiceScopeRepository $scopeRepository,
        ?CacheInterface $cache = null
    ): ServiceScopeService {
        $em = $this->createMock(EntityManagerInterface::class);

        return new ServiceScopeService(
            $em,
            $serviceRepository,
            $scopeRepository,
            $cache ?? $this->createMock(CacheInterface::class),
            TestFileLoggerFactory::create(),
            $this->createMock(AuditLogService::class),
            self::CACHE_PREFIX,
            300
        );
    }

    private function makeService(string $name): RegisteredService
    {
        $service = new RegisteredService();
        $service->setTenantId(TestTenantContext::TENANT_ID);
        $service->setName($name);
        $service->setType(ServiceType::API);
        $service->setStatus(ServiceStatus::ACTIVE);

        return $service;
    }
}
