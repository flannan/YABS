<?php
declare(strict_types=1);

include_once __DIR__ . '/Customer.php';
include_once __DIR__ . '/Database.php';
include_once __DIR__ . '/User.php';
include_once __DIR__ . '/Api.php';
include_once __DIR__ . '/CustomersApi.php';
include_once __DIR__ . '/SettingsApi.php';
include_once __DIR__ . '/ManagementApi.php';
include_once __DIR__ . '/UsersApi.php';
include_once __DIR__ . '/Rules.php';

try {
    $uri=explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    if ($uri[1]==='customers') {
        $api = new flannan\YABS\CustomersApi();
    } elseif ($uri[1]==='settings') {
        $api = new flannan\YABS\SettingsApi();
    } elseif ($uri[1]==='users') {
        $api = new flannan\YABS\UsersApi();
    } elseif ($uri[1]==='management') {
        $api = new flannan\YABS\ManagementApi();
    }

    echo $api->run();
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
