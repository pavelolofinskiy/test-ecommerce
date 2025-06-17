<?php
namespace Src\Cache;

class RedisCache
{
    private \Redis $redis;

    public function __construct()
    {
        $this->redis = new \Redis();
        $connected = $this->redis->connect('127.0.0.1', 6379);
        if (!$connected) {
            throw new \Exception('Cannot connect to Redis');
        }
    }

    public function set(string $key, $value, int $ttl = 3600): void
    {
        $this->redis->set($key, serialize($value), $ttl);
    }

    public function get(string $key)
    {
        $data = $this->redis->get($key);
        return $data === false ? null : unserialize($data);
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }
}