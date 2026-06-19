<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Tests\TrustRule;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Technoholics\ServiceRegistry\Tests\Support\TestFileLoggerFactory;
use Technoholics\ServiceRegistry\Tests\Support\TestTenantContext;
use Technoholics\ServiceRegistry\Auth\Services\AuditLogService;
use Technoholics\ServiceRegistry\TrustRule\Entities\ServiceTrustRule;
use Technoholics\ServiceRegistry\Service\Constants\ServiceStatus;
use Technoholics\ServiceRegistry\Service\Constants\ServiceType;
use Technoholics\ServiceRegistry\Service\Entities\RegisteredService;
use Technoholics\ServiceRegistry\Service\Repositories\RegisteredServiceRepository;
use Technoholics\ServiceRegistry\TrustRule\DTO\CreateTrustRuleRequestDTO;
use Technoholics\ServiceRegistry\TrustRule\Exceptions\ServiceTrustRuleAlreadyExistsException;
use Technoholics\ServiceRegistry\TrustRule\Repositories\ServiceTrustRuleRepository;
use Technoholics\ServiceRegistry\TrustRule\Services\ServiceTrustRuleService;

final class ServiceTrustRuleServiceTest extends TestCase
{
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

    public function testCreateThrowsWhenRuleExists(): void
    {
        $caller = $this->makeService('document-service');
        $target = $this->makeService('storage-service');

        $serviceRepository = $this->createMock(RegisteredServiceRepository::class);
        $serviceRepository->method('requireByName')
            ->willReturnCallback(static fn (string $name) => $name === 'document-service' ? $caller : $target);

        $trustRuleRepository = $this->createMock(ServiceTrustRuleRepository::class);
        $trustRuleRepository->method('findByCallerAndTarget')
            ->willReturn($this->createMock(ServiceTrustRule::class));

        $service = new ServiceTrustRuleService(
            $this->createMock(EntityManagerInterface::class),
            $serviceRepository,
            $trustRuleRepository,
            $this->createMock(CacheInterface::class),
            TestFileLoggerFactory::create(),
            $this->createMock(AuditLogService::class),
            'service-registry:',
            300
        );

        $dto = CreateTrustRuleRequestDTO::fromRequest([
            'callerService' => 'document-service',
            'targetService' => 'storage-service',
            'allowedScopes' => ['storage.upload'],
        ]);

        $this->expectException(ServiceTrustRuleAlreadyExistsException::class);
        $service->create($dto);
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
