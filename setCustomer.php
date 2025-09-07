<?php
// UpdateCustomer.php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Settings;

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        // Erlaube optional POST (falls dein Frontend kein PUT sendet)
        http_response_code(405);
        echo json_encode(['error' => true, 'message' => 'Method not allowed (use PUT).']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'Ungültiger JSON-Body.']);
        exit;
    }

    $id             = $input['id'] ?? $input['customerId'] ?? $input['customerID'] ?? null;
    $customerNumber = $input['customerNumber'] ?? null;
    $name           = $input['name'] ?? $input['customerName'] ?? null;
    $adresse        = $input['adresse'] ?? $input['address'] ?? null;
    $sendToHeidi    = isset($input['sendToHeidi']) ? (bool)$input['sendToHeidi'] : null;

    if ($id === null) {
        http_response_code(422);
        echo json_encode(['error' => true, 'message' => 'Pflichtfeld fehlt: id']);
        exit;
    }

    // App-Config laden
    $appConfig = require __DIR__ . '/config/config.php';
    Settings::init($appConfig);

    $apiBase = Settings::get("CentralAPI");
    $http = new \GuzzleHttp\Client([
        'base_uri' => rtrim($apiBase, '/') . '/',
        'timeout'  => 10,
    ]);

    // Payload für deine CentralAPI zusammenstellen
    // Passe Feldnamen an, falls deine API andere erwartet.
    $payload = array_filter([
        'customerNumber' => $customerNumber,
        'name'           => $name,
        'adresse'        => $adresse,
        'sendToHeidi'    => $sendToHeidi,
    ], function($v){ return $v !== null; });

    // PUT auf /api/Customer/{id} (Pfad ggf. anpassen)
    $resp = $http->request('PATCH', 'api/Customer/' . urlencode((string)$id), [
        'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
        'json'    => $payload,
    ]);

    http_response_code($resp->getStatusCode());
    echo (string)$resp->getBody();

} catch (\GuzzleHttp\Exception\ClientException $e) {
    $res = $e->getResponse();
    http_response_code($res ? $res->getStatusCode() : 400);
    echo $res ? (string)$res->getBody() : json_encode(['error'=>true,'message'=>$e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
