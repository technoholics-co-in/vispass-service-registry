<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Technoholics\ServiceRegistry\Tests\Support\TestFileLoggerFactory;
use Technoholics\ServiceRegistry\Tests\Support\TestTenantContext;
use Technoholics\ServiceRegistry\Service\Constants\ServiceStatus;
use Technoholics\ServiceRegistry\Service\Constants\ServiceType;
use Technoholics\ServiceRegistry\Service\DTO\RegisterServiceRequestDTO;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredService;
use Technoholics\ServiceRegistry\Service\Exceptions\RegisteredServiceAlreadyExistsException;
use Technoholics\ServiceRegistry\Service\Exceptions\RegisteredServiceNotFoundException;
use Technoholics\ServiceRegistry\Auth\Services\AuditLogService;
use Technoholics\ServiceRegistry\Service\Helpers\ServiceCacheKeyHelper;
use Technoholics\ServiceRegistry\Service\Repositories\RegisteredServiceRepository;
use Technoholics\ServiceRegistry\Service\Services\RegisteredServiceService;

final class RegisteredServiceServiceTest extends TestCase
{
    private const CACHE_PREFIX = 'service-registry:';

    private const CACHE_TTL = 300;

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

    public function testRegisterCreatesServiceAndCachesDiscoveryPayload(): void
    {
        $dto = RegisterServiceRequestDTO::fromRequest([
            'name' => 'document-service',
            'type' => ServiceType::API,
        ]);

        $entity = new RegisteredService();
        $entity->setTenantId(TestTenantContext::TENANT_ID);
        $entity->setName('document-service');
        $entity->setType(ServiceType::API);
        $entity->setStatus(ServiceStatus::ACTIVE);

        $repository = $this->createMock(RegisteredServiceRepository::class);
        $repository->expects($this->once())
            ->method('existsByName')
            ->with('document-service', TestTenantContext::TENANT_ID)
            ->willReturn(false);
        $repository->expects($this->once())
            ->method('create')
            ->willReturn($entity);

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('set')
            ->with(
                ServiceCacheKeyHelper::forService(
                    self::CACHE_PREFIX,
                    TestTenantContext::TENANT_ID,
                    'document-service'
                ),
                [
                    'name' => 'document-service',
                    'type' => ServiceType::API,
                    'status' => ServiceStatus::ACTIVE,
                ],
                self::CACHE_TTL
            );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(static fn (callable $callback) => $callback());
        $em->expects($this->once())->method('clear');

        $logger = TestFileLoggerFactory::create();

        $service = new RegisteredServiceService(
            $em,
            $repository,
            $cache,
            $logger,
            $this->createMock(AuditLogService::class),
            self::CACHE_PREFIX,
            self::CACHE_TTL
        );

        $result = $service->register($dto);

        $this->assertSame('document-service', $result['name']);
        $this->assertSame(ServiceType::API, $result['type']);
        $this->assertSame(ServiceStatus::ACTIVE, $result['status']);
        $this->assertNotEmpty($result['id']);
    }

    public function testRegisterThrowsWhenServiceAlreadyExists(): void
    {
        $dto = RegisterServiceRequestDTO::fromRequest([
            'name' => 'document-service',
            'type' => ServiceType::API,
        ]);

        $repository = $this->createMock(RegisteredServiceRepository::class);
        $repository->expects($this->once())
            ->method('existsByName')
            ->with('document-service', TestTenantContext::TENANT_ID)
            ->willReturn(true);

        $service = new RegisteredServiceService(
            $this->createMock(EntityManagerInterface::class),
            $repository,
            $this->createMock(CacheInterface::class),
            TestFileLoggerFactory::create(),
            $this->createMock(AuditLogService::class),
            self::CACHE_PREFIX,
            self::CACHE_TTL
        );

        $this->expectException(RegisteredServiceAlreadyExistsException::class);
        $service->register($dto);
    }

    public function testGetByNameReturnsCachedValue(): void
    {
        $cached = [
            'name' => 'document-service',
            'type' => ServiceType::API,
            'status' => ServiceStatus::ACTIVE,
        ];

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with(ServiceCacheKeyHelper::forService(
                self::CACHE_PREFIX,
                TestTenantContext::TENANT_ID,
                'document-service'
            ))
            ->willReturn($cached);

        $repository = $this->createMock(RegisteredServiceRepository::class);
        $repository->expects($this->never())->method('findByName');

        $service = new RegisteredServiceService(
            $this->createMock(EntityManagerInterface::class),
            $repository,
            $cache,
            TestFileLoggerFactory::create(),
            $this->createMock(AuditLogService::class),
            self::CACHE_PREFIX,
            self::CACHE_TTL
        );

        $this->assertSame($cached, $service->getByName('document-service'));
    }

    public function testGetByNameLoadsFromRepositoryOnCacheMiss(): void
    {
        $entity = new RegisteredService();
        $entity->setTenantId(TestTenantContext::TENANT_ID);
        $entity->setName('document-service');
        $entity->setType(ServiceType::API);
        $entity->setStatus(ServiceStatus::ACTIVE);

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('get')->willReturn(null);
        $cache->expects($this->once())->method('set');

        $repository = $this->createMock(RegisteredServiceRepository::class);
        $repository->expects($this->once())
            ->method('findByName')
            ->with('document-service', TestTenantContext::TENANT_ID)
            ->willReturn($entity);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('clear');

        $service = new RegisteredServiceService(
            $em,
            $repository,
            $cache,
            TestFileLoggerFactory::create(),
            $this->createMock(AuditLogService::class),
            self::CACHE_PREFIX,
            self::CACHE_TTL
        );

        $result = $service->getByName('document-service');
        $this->assertSame('document-service', $result['name']);
    }

    public function testGetByNameThrowsWhenServiceMissing(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(null);

        $repository = $this->createMock(RegisteredServiceRepository::class);
        $repository->method('findByName')->willReturn(null);

        $service = new RegisteredServiceService(
            $this->createMock(EntityManagerInterface::class),
            $repository,
            $cache,
            TestFileLoggerFactory::create(),
            $this->createMock(AuditLogService::class),
            self::CACHE_PREFIX,
            self::CACHE_TTL
        );

        $this->expectException(RegisteredServiceNotFoundException::class);
        $service->getByName('missing-service');
    }
}
