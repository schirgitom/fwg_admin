<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Settings;

header('Content-Type: application/json; charset=utf-8');

// Config
$appConfig = require __DIR__ . '/config/config.php';
Settings::init($appConfig);
$apiBase = Settings::get("CentralAPI");

// Parameter lesen
$deviceId   = $_GET['deviceId']   ?? null;
$customerId = $_GET['customerId'] ?? null; // leer/null = entfernen

if (!$deviceId) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => 'deviceId fehlt'
    ]);
    exit;
}

// API vorbereiten
$config = (new \FWGCentralAPI\Configuration())->setHost($apiBase);
$http = new \GuzzleHttp\Client([
    'base_uri' => rtrim($apiBase, '/') . '/',
    'timeout'  => 10,
]);


$heatApiInstance    = new \FWGCentralAPI\Api\HeatDeviceApi($http, $config);

// Payload: customerId bewusst null → entfernen
$payload = [
    'customerId' => $customerId !== null && $customerId !== ''
        ? (int)$customerId
        : null
];

try {

    $heat     = $heatApiInstance->apiHeatDeviceAssignToCustomerDeviceIDPatch($deviceId, $customerId);

    echo json_encode(['success' => $heat]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
