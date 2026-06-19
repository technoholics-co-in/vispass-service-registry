<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Shared\Redis;

use Predis\ClientInterface;

/**
 * Thin wrapper for Redis PING health checks (testable).
 */
class RedisPingProbe
{
    public function __construct(
        private ClientInterface $redis
    ) {
    }

    public function ping(): mixed
    {
        return $this->redis->ping();
    }
}
