<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Health\Services;

use Doctrine\ORM\EntityManagerInterface;
use Technoholics\ServiceRegistry\Shared\Redis\RedisPingProbe;

/**
 * Aggregates database and Redis connectivity checks.
 */
class HealthCheckService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RedisPingProbe $redis
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function check(): array
    {
        $database = $this->checkDatabase();
        $redis = $this->checkRedis();
        $allUp = $database === 'UP' && $redis === 'UP';

        return [
            'status' => $allUp ? 'UP' : 'DOWN',
            'database' => $database,
            'redis' => $redis,
        ];
    }

    public function isHealthy(): bool
    {
        $result = $this->check();

        return $result['status'] === 'UP';
    }

    private function checkDatabase(): string
    {
        try {
            $this->em->getConnection()->executeQuery('SELECT 1');

            return 'UP';
        } catch (\Throwable) {
            return 'DOWN';
        }
    }

    private function checkRedis(): string
    {
        try {
            $response = $this->redis->ping();

            return $response === 'PONG' || $response === true ? 'UP' : 'DOWN';
        } catch (\Throwable) {
            return 'DOWN';
        }
    }
}
