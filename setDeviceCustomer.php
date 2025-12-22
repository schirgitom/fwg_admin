<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Settings;

header('Content-Type: application/json; charset=utf-8');

// ================== CONFIG ==================
$appConfig = require __DIR__ . '/config/config.php';
Settings::init($appConfig);
$apiBase = Settings::get("CentralAPI");

// ================== INPUT (JSON BODY) ==================
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$deviceId   = $data['deviceId']   ?? null;
$customerId = $data['customerId'] ?? null; // null = entfernen
$regionId   = $data['regionId']   ?? null;

if (!$deviceId) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => 'Device ID fehlt'
    ]);
    exit;
}

// ================== API CLIENT ==================
$config = (new \FWGCentralAPI\Configuration())->setHost($apiBase);
$http = new \GuzzleHttp\Client([
    'base_uri' => rtrim($apiBase, '/') . '/',
    'timeout'  => 10,
]);

$heatApi = new \FWGCentralAPI\Api\HeatDeviceApi($http, $config);

// ================== PAYLOAD ==================
$payload = [
    'customer' => $customerId !== null ? (int)$customerId : null,
    'region'   => $regionId   !== null ? (int)$regionId   : null,
];

try {

    $result = $heatApi
        ->apiHeatDeviceAssignToCustomerDeviceIDPatch(
            $deviceId,
            $payload   // 👈 JSON BODY
        );

    echo json_encode([
        'success' => true,
        'data'    => $result
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => true,
        'message' => $e->getMessage()
    ]);
}
