<?php
use PHPUnit\Framework\TestCase;
use Src\Cache\RedisCache;


class RedisCacheTest extends TestCase
{
    private $redisMock;
    private RedisCache $cache;

    protected function setUp(): void
    {
        // Создаём мок Redis
        $this->redisMock = $this->createMock(Redis::class);

        // Чтобы протестировать конструктор, нам нужно "вставить" мок в класс
        // В твоём коде Redis создаётся внутри конструктора,
        // поэтому для теста потребуется небольшая доработка класса
        // (например, внедрение зависимости Redis через конструктор)
    }

    public function testSetCallsRedisSetWithSerializedValue()
    {
        $key = 'test_key';
        $value = ['foo' => 'bar'];
        $ttl = 3600;

        // Мокаем connect, чтобы он успешно "подключался"
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

    public function testGetReturnsUnserializedValue()
    {
        $key = 'test_key';
        $serializedValue = serialize(['foo' => 'bar']);

        // Мокаем connect
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

    public function testGetReturnsNullIfNoData()
    {
        $key = 'missing_key';

        // Мокаем connect
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

    public function testDeleteCallsRedisDel()
    {
        $key = 'delete_key';

        // Мокаем connect
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