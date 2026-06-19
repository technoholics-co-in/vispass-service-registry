<?php

declare(strict_types=1);

namespace Technoholics\ServiceRegistry\Shared\Cache;

use Predis\ClientInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * PSR-16 cache adapter backed by Redis (Predis).
 */
final class RedisSimpleCache implements CacheInterface
{
    public function __construct(private ClientInterface $predis)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->predis->get($key);

        return $value === null ? $default : unserialize($value);
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $serialized = serialize($value);
        if ($ttl === null) {
            return $this->predis->set($key, $serialized) === 'OK';
        }

        $seconds = is_int($ttl) ? $ttl : $this->dateIntervalToSeconds($ttl);

        return $this->predis->setex($key, $seconds, $serialized) === 'OK';
    }

    public function delete(string $key): bool
    {
        return $this->predis->del([$key]) > 0;
    }

    public function clear(): bool
    {
        return $this->predis->flushdb() === 'OK';
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keysArray = is_array($keys) ? $keys : iterator_to_array($keys);
        $result = [];
        foreach ($keysArray as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return $this->predis->exists($key) > 0;
    }

    private function dateIntervalToSeconds(\DateInterval $interval): int
    {
        $reference = new \DateTimeImmutable();
        $endTime = $reference->add($interval);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }
}
