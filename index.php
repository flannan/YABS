<?php
declare(strict_types=1);

include_once __DIR__ . 'customersApi.php';

try {
    $api = new flannan\YABS\customersApi();
    echo $api->run();
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}