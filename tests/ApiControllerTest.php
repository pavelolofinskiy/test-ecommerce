<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Src\Controllers\ApiController;
use Src\Cache\RedisCache;

class ApiControllerTest extends TestCase
{
    public function testGetFiltersWithCountsReturnsCachedData(): void
    {
        $pdo = $this->createMock(PDO::class);
        $redisCache = $this->createMock(RedisCache::class);

        $cachedFilters = [
            'color' => [
                'name' => 'Color',
                'values' => [],
                'slug' => 'color',
            ],
        ];

        $redisCache->expects($this->once())
            ->method('get')
            ->with('filters_with_counts')
            ->willReturn($cachedFilters);

        // Метод query у PDO не должен вызываться, т.к. данные берутся из кеша
        $pdo->expects($this->never())
            ->method('query');

        $controller = new ApiController();
        $result = $controller->getFiltersWithCounts($pdo, $redisCache);

        $this->assertSame($cachedFilters, $result);
    }

    public function testGetFiltersWithCountsFetchesFromDbAndCaches(): void
    {
        $pdo = $this->createMock(PDO::class);
        $redisCache = $this->createMock(RedisCache::class);

        $redisCache->expects($this->once())
            ->method('get')
            ->with('filters_with_counts')
            ->willReturn(null);

        $stmtParams = $this->createMock(PDOStatement::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, slug FROM parameters')
            ->willReturn($stmtParams);

        $parameters = [
            [
                'id' => 1,
                'name' => 'Color',
                'slug' => 'color',
            ],
        ];

        $stmtParams->expects($this->once())
            ->method('fetchAll')
            ->willReturn($parameters);

        $stmtValues = $this->createMock(PDOStatement::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT pv.value, COUNT(pp.product_id)'))
            ->willReturn($stmtValues);

        $stmtValues->expects($this->once())
            ->method('execute')
            ->with([1]);

        $values = [
            [
                'value' => 'Red',
                'product_count' => 10,
            ],
            [
                'value' => 'Blue',
                'product_count' => 5,
            ],
        ];

        $stmtValues->expects($this->once())
            ->method('fetchAll')
            ->willReturn($values);

        $redisCache->expects($this->once())
            ->method('set')
            ->with(
                'filters_with_counts',
                $this->callback(function (array $filters): bool {
                    return isset($filters['color'])
                        && $filters['color']['name'] === 'Color'
                        && count($filters['color']['values']) === 2;
                }),
                600
            );

        $controller = new ApiController();
        $result = $controller->getFiltersWithCounts($pdo, $redisCache);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('color', $result);
        $this->assertCount(2, $result['color']['values']);
    }
}