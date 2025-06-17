<?php
namespace Src\Controllers;

use Src\Database\DB;
use Src\Cache\RedisCache;
use PDO;

class ApiController

{
    function getFiltersWithCounts(PDO $pdo, RedisCache $cache): array
    {
        $cacheKey = 'filters_with_counts';

        $filters = $cache->get($cacheKey);
        if ($filters !== null) {
            return $filters;
        }

        // Если кеша нет — считаем из базы
        $filters = [];

        // Получаем все параметры
        $stmtParams = $pdo->query("SELECT id, name, slug FROM parameters");
        $parameters = $stmtParams->fetchAll();

        foreach ($parameters as $param) {
            $filters[$param['slug']] = [
                'name' => $param['name'],
                'values' => []
            ];

            $filters[$param['slug']]['slug'] = $param['slug'];

            // Для каждого параметра получаем значения и количество товаров
            $stmtValues = $pdo->prepare("
                SELECT pv.value, COUNT(pp.product_id) AS product_count
                FROM parameter_values pv
                LEFT JOIN product_parameters pp ON pp.parameter_value_id = pv.id
                WHERE pv.parameter_id = ?
                GROUP BY pv.id
                ORDER BY pv.value
            ");
            $stmtValues->execute([$param['id']]);
            $values = $stmtValues->fetchAll();

            foreach ($values as $val) {
                $filters[$param['slug']]['values'][] = [
                    'value' => $val['value'],
                    'count' => (int)$val['product_count']
                ];
            }
        }

        $cache->set($cacheKey, $filters, 600); // кеш 10 минут

        return $filters;
    }


    public function getProducts(): void
    {
        header('Content-Type: application/json');
        $pdo = DB::connect();

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $sort = $_GET['sort_by'] ?? null;
        $orderBy = 'p.id ASC';

        if ($sort === 'price_asc') $orderBy = 'p.price ASC';
        if ($sort === 'price_desc') $orderBy = 'p.price DESC';

        // Фильтры: filter[brand]=HP или filter[color][]=Black
        $filters = $_GET['filter'] ?? [];
        $whereParts = [];
        $joinCount = 0;
        $params = [];

        foreach ($filters as $slug => $values) {
            $joinCount++;
            $alias = "pp{$joinCount}";

            if (!is_array($values)) {
                $values = [$values];
            }

            $placeholders = implode(',', array_fill(0, count($values), '?'));

            // ✅ Сначала slug, потом значения
            $params[] = $slug;
            $params = array_merge($params, $values);

            $whereParts[] = [
                'join' => "
                    JOIN product_parameters {$alias} ON p.id = {$alias}.product_id
                    JOIN parameter_values pv{$joinCount} ON pv{$joinCount}.id = {$alias}.parameter_value_id
                    JOIN parameters pa{$joinCount} ON pa{$joinCount}.id = pv{$joinCount}.parameter_id AND pa{$joinCount}.slug = ?
                ",
                'condition' => "pv{$joinCount}.value IN ($placeholders)"
            ];
        }

        $joins = '';
        $wheres = [];

        foreach ($whereParts as $i => $part) {
            $joins .= $part['join'] . "\n";
            $wheres[] = $part['condition'];
        }

        $whereSql = count($wheres) ? 'WHERE ' . implode(' AND ', $wheres) : '';

        // Подсчёт общего количества
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.id)
            FROM products p
            $joins
            $whereSql
        ");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Получение товаров
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.id, p.name, p.price, p.description
            FROM products p
            $joins
            $whereSql
            ORDER BY $orderBy
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'data' => $products,
            'meta' => [
                'current_page' => $page,
                'last_page' => ceil($total / $limit),
                'per_page' => $limit,
                'total' => (int)$total
            ]
        ]);
    }

    public function getFilters(): void
    {
        header('Content-Type: application/json');
        $pdo = DB::connect();
        $redis = new RedisCache();

        $filters = $this->getFiltersWithCounts($pdo, $redis); 

        echo json_encode(array_values($filters));
    }
}