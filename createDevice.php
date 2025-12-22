<?php

require __DIR__ . '/vendor/autoload.php';
use App\Settings;

// ---------------- Config ----------------
$appConfig = require __DIR__ . '/config/config.php';
Settings::init($appConfig);
$apiBase = Settings::get("CentralAPI");

$config = (new FWGCentralAPI\Configuration())->setHost($apiBase);
$guzzle = new \GuzzleHttp\Client([
    'base_uri' => rtrim($apiBase, '/') . '/',
    'timeout'  => 10,
]);

$heatApi     = new \FWGCentralAPI\Api\HeatDeviceApi($guzzle, $config);
$regionApi   = new \FWGCentralAPI\Api\RegionApi($guzzle, $config);
$customerApi = new \FWGCentralAPI\Api\CustomerApi($guzzle, $config);

function toArray($val) {
    if ($val instanceof JsonSerializable) $val = $val->jsonSerialize();
    return json_decode(json_encode($val), true);
}

$errorMsg = null;
$successMsg = null;

// ---------------- Defaults ----------------
$device = [
    'deviceID'     => '',
    'deviceVendor' => 'Kamstrup',
    'active'       => true,
    'fK_Region'    => null,
    'fK_Customer'  => null,
];

// ---------------- POST → CREATE ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payload = [
        'deviceID'     => trim($_POST['deviceID']),
        'deviceVendor' => $_POST['deviceVendor'] ?? '',
        'active'       => ($_POST['active'] ?? 'false') === 'true',
        'fK_Region'    => (int)$_POST['fK_Region'],
        'fK_Customer'  => (int)$_POST['fK_Customer'],
    ];

    try {
        //$heatApi->apiHeatDevicePost($payload);
      //  $successMsg = "Gerät erfolgreich angelegt.";

        $customer = $heatApi->apiHeatDevicePost($payload);

        // robust in Array
        $customer = json_decode(json_encode($customer), true);

        // Optional: zurück zur Übersicht
       // header("Location: editDevice.php?created=1");

        if (!empty($customer['hasError'])) {

            $messages = $customer['errorMessages'] ?? ['Unbekannter Fehler'];

            // defensive: sicherstellen, dass es ein Array ist
            if (!is_array($messages)) {
                $messages = [$messages];
            }

            throw new RuntimeException(
                    implode("\n", $messages)   // Zeilenumbruch für Toast
            );
        }

        // Erfolg
        $id = $customer['data']['id'];

        header("Location: editDevice.php?id={$id}&created=1");

        exit;

    } catch (\Throwable $e) {
        $errorMsg = "Anlegen fehlgeschlagen: " . $e->getMessage();
        $device = array_merge($device, $_POST);
    }
}

// ---------------- Stammdaten ----------------
try {
    $regions = array_map('toArray', $regionApi->apiRegionAllGet());

    // optionaler Kunde aus URL
    if (!empty($_GET['customerId'])) {
        $customerId = (int)$_GET['customerId'];
        $customer = toArray($customerApi->apiCustomerIdGet($customerId));
        $device['fK_Customer'] = $customerId;
        $customerName = ($customer['customerNumber'] ?? '') . ' - ' . ($customer['name'] ?? '');
    } else {
        $customerName = '';
    }

} catch (\Throwable $e) {
    http_response_code(500);
    echo "Fehler beim Laden: " . htmlspecialchars($e->getMessage());
    exit;
}

include 'header.php';
include 'customerDialog.php';
?>

<main>
    <div class="container py-4">

        <!-- Titel -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0">Gerät anlegen</h1>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Zur Übersicht</a>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <!-- Formular -->
        <form method="post" class="card">
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Device EUI</label>
                        <input name="deviceID" class="form-control" required
                               value="<?= htmlspecialchars($device['deviceID']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Hersteller</label>
                        <select name="deviceVendor" class="form-select">
                            <?php foreach (['Kamstrup','Siemens','Sharky'] as $v): ?>
                                <option value="<?= $v ?>" <?= $device['deviceVendor']===$v?'selected':'' ?>>
                                    <?= $v ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Aktiv</label>
                        <select name="active" class="form-select">
                            <option value="true" <?= $device['active']?'selected':'' ?>>Ja</option>
                            <option value="false" <?= !$device['active']?'selected':'' ?>>Nein</option>
                        </select>
                    </div>

                    <!-- Kunde -->
                    <div class="col-md-8">
                        <label class="form-label">Kunde</label>
                        <div class="input-group">
                            <input id="customerDisplay" class="form-control"
                                   value="<?= htmlspecialchars($customerName) ?>" readonly>
                            <button type="button" class="btn btn-outline-secondary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#customerModal">
                                Kunde wählen
                            </button>
                        </div>
                        <input type="hidden" name="fK_Customer" id="customerIdHidden"
                               value="<?= htmlspecialchars($device['fK_Customer']) ?>">
                    </div>

                    <!-- Region -->
                    <div class="col-md-4">
                        <label class="form-label">Region</label>
                        <select name="fK_Region" class="form-select" required>
                            <option value="" knowing disabled selected>Bitte wählen…</option>
                            <?php foreach ($regions as $r): ?>
                                <option value="<?= $r['id'] ?>"
                                    <?= (string)$device['fK_Region']===(string)$r['id']?'selected':'' ?>>
                                    <?= htmlspecialchars($r['regionName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
            </div>

            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Anlegen</button>
                <a href="index.php" class="btn btn-outline-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</main>

<?php include 'footer.php'; ?>
