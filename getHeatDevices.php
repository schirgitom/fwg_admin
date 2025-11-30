<?php
require 'vendor/autoload.php';
use App\Settings;

$appConfig = require __DIR__ . '/config/config.php';
Settings::init($appConfig);

$dbHost   = Settings::get("CentralAPI");

$config = (new FWGCentralAPI\Configuration())->setHost($dbHost);
$client = new \GuzzleHttp\Client();

$heatApiInstance    = new \FWGCentralAPI\Api\HeatDeviceApi($client, $config);
$customerApiInstance= new \FWGCentralAPI\Api\CustomerApi($client, $config);
$regionApiInstance  = new \FWGCentralAPI\Api\RegionApi($client, $config);

function toArray($value) {
    if (is_array($value)) return $value;
    if ($value instanceof \JsonSerializable) $value = $value->jsonSerialize();
    return json_decode(json_encode($value), true); // stdClass → Array
}

function buildDictionary($list, $idKey = 'id') {
    $dict = [];
    foreach ($list as $item) {
        $arr = toArray($item);
        if (isset($arr[$idKey])) {
            $dict[(string)$arr[$idKey]] = $arr;
        }
    }
    return $dict;
}

try {
    $heat     = $heatApiInstance->apiHeatDeviceAllGet();
    $customers= $customerApiInstance->apiCustomerAllGet();
    $regions  = $regionApiInstance->apiRegionAllGet();

    $customerDict = buildDictionary($customers, 'id');
    $regionDict   = buildDictionary($regions,   'id');

    $enriched = array_map(function($device) use ($customerDict, $regionDict) {

        // WICHTIG: jetzt wird WIRKLICH alles zu Array
        $arr = toArray($device);

        // robust: Kunden- & Regions-FK holen
        $fkCustomer =
            $arr['fK_Customer']
            ?? $arr['f_k_customer']
            ?? null;

        $fkRegion =
            $arr['fK_Region']
            ?? $arr['f_k_region']
            ?? null;

        // anreichern
        $arr['customer'] = $fkCustomer && isset($customerDict[$fkCustomer])
            ? $customerDict[$fkCustomer]
            : null;

        $arr['region'] = $fkRegion && isset($regionDict[$fkRegion])
            ? $regionDict[$fkRegion]
            : null;

        // sprechende Namen
        $arr['customerName'] = $arr['customer']['name']        ?? null;
        $arr['regionName']   = $arr['region']['region_name']   ?? null;

        return $arr;

    }, $heat);

    header('Content-Type: application/json');
    echo json_encode($enriched, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error'   => true,
        'message' => $e->getMessage(),
    ], JSON_PRETTY_PRINT);
}
