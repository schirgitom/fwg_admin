<?php
header('Content-Type: application/json');

$url = $_GET['url'] ?? null;
if (!$url) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 3,
    CURLOPT_FOLLOWLOCATION => true
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo json_encode([
    'ok' => $httpCode >= 200 && $httpCode < 300
]);
