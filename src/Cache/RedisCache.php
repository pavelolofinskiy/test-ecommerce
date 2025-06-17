<?php

namespace Src\Cache;

use Redis;
use Exception;

/**
 * Класс для работы с кешем Redis.
 */
class RedisCache
{
    private Redis $redis;

    /**
     * Конструктор, подключается к Redis.
     *
     * @throws Exception Если не удается подключиться к Redis.
     */
    public function __construct()
    {
        $this->redis = new Redis();

        $connected = $this->redis->connect('127.0.0.1', 6379);

        if (!$connected) {
            throw new Exception('Cannot connect to Redis');
        }
    }

    /**
     * Сохраняет значение в кеш с TTL.
     *
     * @param string $key Ключ кеша.
     * @param mixed $value Значение для сохранения.
     * @param int $ttl Время жизни в секундах.
     */
    public function set(string $key, $value, int $ttl = 3600): void
    {
        $this->redis->set($key, serialize($value), $ttl);
    }

    /**
     * Получает значение из кеша по ключу.
     *
     * @param string $key Ключ кеша.
     * @return mixed|null Распарсенное значение или null, если отсутствует.
     */
    public function get(string $key)
    {
        $data = $this->redis->get($key);

        return $data === false ? null : unserialize($data);
    }

    /**
     * Удаляет значение из кеша по ключу.
     *
     * @param string $key Ключ кеша.
     */
    public function delete(string $key): void
    {
        $this->redis->del($key);
    }
}