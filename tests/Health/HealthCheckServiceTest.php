<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Tests\Health;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Technoholics\ServiceRegistry\Health\Services\HealthCheckService;
use Technoholics\ServiceRegistry\Shared\Redis\RedisPingProbe;

final class HealthCheckServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testCheckReturnsUpWhenDependenciesHealthy(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->with('SELECT 1');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        $redis = $this->createMock(RedisPingProbe::class);
        $redis->method('ping')->willReturn('PONG');

        $service = new HealthCheckService($em, $redis);
        $result = $service->check();

        $this->assertSame('UP', $result['status']);
        $this->assertSame('UP', $result['database']);
        $this->assertSame('UP', $result['redis']);
        $this->assertTrue($service->isHealthy());
    }

    public function testCheckReturnsDownWhenDatabaseFails(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willThrowException(new \RuntimeException('db down'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        $redis = $this->createMock(RedisPingProbe::class);
        $redis->method('ping')->willReturn('PONG');

        $service = new HealthCheckService($em, $redis);
        $result = $service->check();

        $this->assertSame('DOWN', $result['status']);
        $this->assertSame('DOWN', $result['database']);
        $this->assertFalse($service->isHealthy());
    }
}
