<?php
declare(strict_types=1);

include_once __DIR__ . '/customersApi.php';
include_once __DIR__ . '/Api.php';
include_once __DIR__ . '/Customer.php';
include_once __DIR__ . '/Database.php';
include_once __DIR__ . '/User.php';

try {
    $api = new flannan\YABS\customersApi();
    echo $api->run();
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
