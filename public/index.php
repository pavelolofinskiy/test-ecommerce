<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Src\Controllers\ApiController;

$controller = new ApiController();
$controller->handleRequest();