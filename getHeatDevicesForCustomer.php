<?php
// GetCustomers.php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Settings;

$deviceId = $_GET['id'] ?? $_GET['customerid'] ?? null;

header('Content-Type: application/json; charset=utf-8');

if (!$deviceId) {
    http_response_code(400);
    echo json_encode([
        'error'   => true,
        'message' => 'Missing GET parameter: id'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    // App-Config laden
    $appConfig = require __DIR__ . '/config/config.php';
    Settings::init($appConfig);

    // API-Basis
    $apiBase = Settings::get("CentralAPI"); // z.B. https://centralapi.example.com

    // OpenAPI-Client vorbereiten
    $config = (new \FWGCentralAPI\Configuration())->setHost($apiBase);
    $http   = new \GuzzleHttp\Client([
        'base_uri' => rtrim($apiBase, '/') . '/',
        'timeout'  => 10,
    ]);

    $customerApi = new \FWGCentralAPI\Api\HeatDeviceApi($http, $config);

    // Abrufen
    $list = $customerApi->apiHeatDeviceGetForCustomerCustomerIDGet($deviceId);
    $regionApi = new \FWGCentralAPI\Api\RegionApi($http, $config);
    $regions   = $regionApi->apiRegionAllGet();
//

    // Robust in Arrays wandeln
    $toArray = function ($v) {
        if (is_array($v)) return $v;
        if ($v instanceof \JsonSerializable) $v = $v->jsonSerialize();
        return json_decode(json_encode($v), true);
    };

    $regionsArr = array_map($toArray, $regions);
    $regionMap = [];
    foreach ($regionsArr as $r) {
        $regionMap[$r['id']] = $r['regionName'];
    }

    $result = array_map($toArray, $list);

    foreach ($result as &$device) {
        $rid = $device['fK_Region'] ?? null;
        $device['regionName'] = $rid && isset($regionMap[$rid])
            ? $regionMap[$rid]
            : null;
    }
    unset($device);

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => true,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}