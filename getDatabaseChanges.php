<?php
require __DIR__ . '/vendor/autoload.php';

use App\Settings;

// ==== Config laden ====
$appConfig = require __DIR__ . '/config/config.php';
Settings::init($appConfig);

$apiBase = Settings::get("CentralAPI");

// FWG CentralAPI Clients
$config = (new FWGCentralAPI\Configuration())->setHost($apiBase);
$guzzle = new \GuzzleHttp\Client([
    'base_uri' => rtrim($apiBase, '/') . '/'
]);

$changeApi  = new FWGCentralAPI\Api\DatabaseChangeApi($guzzle, $config);
$regionApi  = new FWGCentralAPI\Api\RegionApi($guzzle, $config);
$heatApi    = new FWGCentralAPI\Api\HeatDeviceApi($guzzle, $config);

// ==== Hilfsfunktionen ====
function toArray($value) {
    if (is_array($value)) return $value;
    if ($value instanceof \JsonSerializable) {
        $value = $value->jsonSerialize();
    }
    return json_decode(json_encode($value), true);
}

/**
 * Baut ein Dictionary: key = $idKey, value = komplettes Objekt als Array
 */
function buildDictionary(array $list, string $idKey = 'id'): array {
    $dict = [];
    foreach ($list as $item) {
        $arr = toArray($item);
        if (isset($arr[$idKey])) {
            $dict[(string)$arr[$idKey]] = $arr;
        }
    }
    return $dict;
}

// ==== Request-Parameter ====
$deviceId = $_GET['id'] ?? $_GET['deviceID'] ?? null;

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
    // 1) Changes für Device laden
    $changes = $changeApi->apiDatabaseChangeGetForDeviceDeviceIDGet($deviceId);

    // 2) Alle Regionen & HeatDevices laden (für Lookup)
    $regions    = $regionApi->apiRegionAllGet();
    $heatDevs   = $heatApi->apiHeatDeviceAllGet();

    $regionDict   = buildDictionary($regions,  'id');
    $heatDict     = buildDictionary($heatDevs, 'id');

    // 3) Changes anreichern
    $enriched = array_map(function($change) use ($regionDict, $heatDict) {
        $arr = toArray($change);

        // IDs können je nach Serialisierung fK_... oder fK_... heißen
        $fkHeat      = $arr['fK_HeatDevice'] ?? $arr['f_k_heat_device'] ?? null;
        $fkOldRegion = $arr['fK_OldRegion']  ?? $arr['f_k_old_region']  ?? null;
        $fkNewRegion = $arr['fK_NewRegion']  ?? $arr['f_k_new_region']  ?? null;

        // Lookups
        $arr['heatDevice'] = ($fkHeat !== null && isset($heatDict[(string)$fkHeat]))
            ? $heatDict[(string)$fkHeat]
            : null;

        $arr['oldRegion'] = ($fkOldRegion !== null && isset($regionDict[(string)$fkOldRegion]))
            ? $regionDict[(string)$fkOldRegion]
            : null;

        $arr['newRegion'] = ($fkNewRegion !== null && isset($regionDict[(string)$fkNewRegion]))
            ? $regionDict[(string)$fkNewRegion]
            : null;

        return $arr;
    }, $changes);

    echo json_encode($enriched, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\FWGCentralAPI\ApiException $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error'   => true,
        'message' => $e->getMessage(),
        'body'    => $e->getResponseBody()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => true,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
