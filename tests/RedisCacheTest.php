<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Src\Cache\RedisCache;

class RedisCacheTest extends TestCase
{
    private Redis $redisMock;
    private RedisCache $cache;

    protected function setUp(): void
    {
        $this->redisMock = $this->createMock(Redis::class);
    }

    public function testSetCallsRedisSetWithSerializedValue(): void
    {
        $key = 'test_key';
        $value = ['foo' => 'bar'];
        $ttl = 3600;

        $this->redisMock->expects($this->once())
            ->method('connect')
            ->with('127.127.126.64', 6379)
            ->willReturn(true);

        $this->redisMock->expects($this->once())
            ->method('set')
            ->with(
                $this->equalTo($key),
                $this->equalTo(serialize($value)),
                $this->equalTo($ttl)
            )
            ->willReturn(true);

        $cache = new RedisCache($this->redisMock);
        $cache->set($key, $value, $ttl);
    }

    public function testGetReturnsUnserializedValue(): void
    {
        $key = 'test_key';
        $serializedValue = serialize(['foo' => 'bar']);

        $this->redisMock->expects($this->once())
            ->method('connect')
            ->with('127.127.126.64', 6379)
            ->willReturn(true);

        $this->redisMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo($key))
            ->willReturn($serializedValue);

        $cache = new RedisCache($this->redisMock);
        $result = $cache->get($key);

        $this->assertEquals(['foo' => 'bar'], $result);
    }

    public function testGetReturnsNullIfNoData(): void
    {
        $key = 'missing_key';

        $this->redisMock->expects($this->once())
            ->method('connect')
            ->with('127.127.126.64', 6379)
            ->willReturn(true);

        $this->redisMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo($key))
            ->willReturn(false);

        $cache = new RedisCache($this->redisMock);
        $result = $cache->get($key);

        $this->assertNull($result);
    }

    public function testDeleteCallsRedisDel(): void
    {
        $key = 'delete_key';

        $this->redisMock->expects($this->once())
            ->method('connect')
            ->with('127.127.126.64', 6379)
            ->willReturn(true);

        $this->redisMock->expects($this->once())
            ->method('del')
            ->with($this->equalTo($key))
            ->willReturn(1);

        $cache = new RedisCache($this->redisMock);
        $cache->delete($key);
    }
}