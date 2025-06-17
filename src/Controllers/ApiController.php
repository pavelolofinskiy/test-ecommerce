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

        $filters = [];

        $stmtParams = $pdo->query("SELECT id, name, slug FROM parameters");
        $parameters = $stmtParams->fetchAll();

        foreach ($parameters as $param) {
            $filters[$param['slug']] = [
                'name' => $param['name'],
                'values' => [],
                'slug' => $param['slug']
            ];

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

        $cache->set($cacheKey, $filters, 600); // 10 минут кеша

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
        $orderBy = 'p_outer.id ASC';

        if ($sort === 'price_asc') $orderBy = 'p_outer.price ASC';
        if ($sort === 'price_desc') $orderBy = 'p_outer.price DESC';

        $filters = $_GET['filter'] ?? [];
        $whereParts = [];
        $params = [];

        foreach ($filters as $slug => $values) {
            if (!is_array($values)) {
                $values = [$values];
            }

            $placeholders = implode(',', array_fill(0, count($values), '?'));
            $params[] = $slug;
            $params = array_merge($params, $values);

            $whereParts[] = "
                EXISTS (
                    SELECT 1 FROM product_parameters pp
                    JOIN parameter_values pv ON pp.parameter_value_id = pv.id
                    JOIN parameters pa ON pv.parameter_id = pa.id
                    WHERE pp.product_id = p_outer.id
                    AND pa.slug = ?
                    AND pv.value IN ($placeholders)
                )
            ";
        }

        $whereSql = count($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM products p_outer
            $whereSql
        ");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT p_outer.id, p_outer.name, p_outer.price, p_outer.description
            FROM products p_outer
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