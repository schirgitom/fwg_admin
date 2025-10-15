<?php
require __DIR__ . '/vendor/autoload.php';
use App\Settings;

header('Content-Type: application/json; charset=utf-8');

$deviceEUI = $_GET['id'] ?? null;
if (!$deviceEUI) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Parameter id fehlt']);
    exit;
}

$appConfig = require __DIR__ . '/config/config.php';
Settings::init($appConfig);
$apiBase = Settings::get("CentralAPI");

$http = new \GuzzleHttp\Client(['base_uri' => rtrim($apiBase, '/') . '/']);
try {
    $resp = $http->get("api/HeatDevice/Migrate/" . urlencode($deviceEUI), [
        'headers' => ['Accept' => 'application/json']
    ]);
    http_response_code($resp->getStatusCode());
    echo $resp->getBody();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
