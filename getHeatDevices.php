<?php
require 'vendor/autoload.php';
use App\Settings;

$appConfig = require __DIR__ . '/config/config.php';

// 2) Settings initialisieren
Settings::init($appConfig);

$dbHost   = Settings::get("CentralAPI");

$config = (new FWGCentralAPI\Configuration())->setHost($dbHost);

$client = new \GuzzleHttp\Client();

$heatApiInstance = new \FWGCentralAPI\Api\HeatDeviceApi($client, $config);

$customerApiInstance = new FWGCentralAPI\Api\CustomerApi($client, $config);

$blockApiInstance = new FWGCentralAPI\Api\BlockApi($client, $config);

$linesApiInstance = new FWGCentralAPI\Api\LineApi($client, $config);

function toArray($value) {
    if (is_array($value)) return $value;
    if ($value instanceof \JsonSerializable) $value = $value->jsonSerialize();
    // json_encode + json_decode(true) macht aus stdClass ein assoc-Array
    return json_decode(json_encode($value), true);
}

/** Baut ein Dictionary: key = $idKey, value = komplettes Objekt (als Array) */
function buildDictionary($list, $idKey = 'id') {
    $dict = [];
    foreach ($list as $item) {
        $arr = toArray($item);
        if (isset($arr[$idKey])) {
            // Key konsistent als String, um 28 vs "28" Missmatches zu vermeiden
            $dict[(string)$arr[$idKey]] = $arr;
        }
    }
    return $dict;
}

try {
    $heat =  $heatApiInstance->apiHeatDeviceAllGet();
    $blocks =  $blockApiInstance->apiBlockAllGet();
    $customers = $customerApiInstance->apiCustomerAllGet();
    $lines = $linesApiInstance->apiLineAllGet();


    $customerDict = buildDictionary($customers, 'id');
    $blockDict    = buildDictionary($blocks,    'id');
    $lineDict     = buildDictionary($lines,     'id');

    $enriched = array_map(function($device) use ($customerDict, $blockDict, $lineDict) {
        $arr = toArray($device);

        // FKs am Device
        $fkCustomer = $arr['fK_Customer'] ?? $arr['fkCustomer'] ?? $arr['customerId'] ?? null;
        $fkBlock    = $arr['fK_Block']    ?? $arr['fkBlock']    ?? $arr['blockId']    ?? null;

        // Customer & Block nachladen
        $arr['customer'] = $fkCustomer !== null ? ($customerDict[(string)$fkCustomer] ?? null) : null;
        $arr['block']    = $fkBlock    !== null ? ($blockDict[(string)$fkBlock]       ?? null) : null;

        // Line-ID bevorzugt am Device, sonst aus dem Block ziehen
        $fkLineFromDevice = $arr['fK_Line'] ?? $arr['fkLine'] ?? $arr['lineId'] ?? null;
        $blockArray       = toArray($arr['block'] ?? []);
        $fkLineFromBlock  = $blockArray['fK_Line'] ?? $blockArray['fkLine'] ?? $blockArray['lineId'] ?? null;

        $lineId = $fkLineFromDevice ?? $fkLineFromBlock;

        // Line nachladen (falls vorhanden)
       /* $arr['line'] = $lineId !== null ? ($lineDict[(string)$lineId] ?? null) : null;

        // Sprechende Namen optional setzen
        $arr['customerName'] = $arr['customer']['customerName'] ?? $arr['customer']['name'] ?? null;
        $arr['blockName']    = $arr['block']['blockName']       ?? $arr['block']['name']    ?? null;
        $arr['lineName']     = $arr['line']['lineName']         ?? $arr['line']['name']     ?? null;
*/
        // Optional: Line auch innerhalb des Blocks mitgeben
        if ($arr['block']) {
            $arr['block']['line'] = $lineId !== null ? ($lineDict[(string)$lineId] ?? null) : null;
        }

        return $arr;
    }, $heat);


    header('Content-Type: application/json');
    echo json_encode($enriched, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500); // optionaler HTTP-Status
    header('Content-Type: application/json');
    echo json_encode([
        'error'   => true,
        'message' => $e->getMessage(),
    ], JSON_PRETTY_PRINT);
}