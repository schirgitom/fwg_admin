<?php
// GetCustomers.php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Settings;



header('Content-Type: application/json; charset=utf-8');



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

    $customerApi = new \FWGCentralAPI\Api\RegionApi($http, $config);

    // Abrufen
    $list = $customerApi->apiRegionAllGet();

    // Robust in Arrays wandeln
    $toArray = function ($v) {
        if (is_array($v)) return $v;
        if ($v instanceof \JsonSerializable) $v = $v->jsonSerialize();
        return json_decode(json_encode($v), true);
    };

    $result = array_map($toArray, $list);

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => true,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}