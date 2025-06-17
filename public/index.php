<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Src\Controllers\ApiController;

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$api = new ApiController();

if (strpos($uri, '/api/catalog/products') === 0 && $method === 'GET') {
    $api->getProducts();
} elseif (strpos($uri, '/api/catalog/filters') === 0 && $method === 'GET') {
    $api->getFilters();
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
}