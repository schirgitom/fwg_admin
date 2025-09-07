<?php
// CreateCustomer.php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Settings;

header('Content-Type: application/json; charset=utf-8');

try {
    // Body einlesen & prüfen
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'Ungültiger JSON-Body.']);
        exit;
    }

    // Minimalvalidierung
    $customerNumber = trim((string)($input['customerNumber'] ?? ''));
    $name           = trim((string)($input['name'] ?? ''));
    $adresse        = isset($input['adresse']) ? (string)$input['adresse'] : '';
    $sendToHeidi    = (bool)($input['sendToHeidi'] ?? false);

    if ($customerNumber === '' || $name === '') {
        http_response_code(422);
        echo json_encode(['error' => true, 'message' => 'Pflichtfelder: customerNumber, name.']);
        exit;
    }

    // App-Config laden
    $appConfig = require __DIR__ . '/config/config.php';
    Settings::init($appConfig);

    // API-Basis
    $apiBase = Settings::get("CentralAPI");

    $http = new \GuzzleHttp\Client([
        'base_uri' => rtrim($apiBase, '/') . '/',
        'timeout'  => 10,
        // 'headers' => ['Authorization' => 'Bearer ...'] // falls nötig
    ]);

    // Payload an CentralAPI (Pfad ggf. anpassen)
    $resp = $http->post('api/Customer', [
        'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
        'json'    => [
            'customerNumber' => $customerNumber,
            'name'           => $name,
            'adresse'        => $adresse,
            'sendToHeidi'    => $sendToHeidi,
        ],
    ]);

    // Antwort 1:1 durchreichen
    http_response_code($resp->getStatusCode());
    echo (string)$resp->getBody();
} catch (\GuzzleHttp\Exception\ClientException $e) {
    // 4xx vom Backend weiterreichen
    $res = $e->getResponse();
    http_response_code($res ? $res->getStatusCode() : 400);
    echo $res ? (string)$res->getBody() : json_encode(['error' => true, 'message' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => true,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}