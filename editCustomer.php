<?php
require __DIR__ . '/vendor/autoload.php';
use App\Settings;

// Config laden
$appConfig = require __DIR__ . '/config/config.php';
Settings::init($appConfig);
$apiBase = Settings::get("CentralAPI");

// API vorbereiten
$config = (new \FWGCentralAPI\Configuration())->setHost($apiBase);
$http   = new \GuzzleHttp\Client([
    'base_uri' => rtrim($apiBase, '/') . '/',
    'timeout'  => 10,
]);

$customerApi = new \FWGCentralAPI\Api\CustomerApi($http, $config);

// Parameter prüfen
$customerId = $_GET['id'] ?? null;
if (!$customerId) {
    http_response_code(400);
    echo "Fehlender Parameter: id";
    exit;
}

$errorMsg = null;
$successMsg = null;

// POST → speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payload = [
        'customerNumber' => $_POST['customerNumber'],
        'name'           => $_POST['name'],
        'adresse'        => $_POST['adresse'] ?: null,
        'sendToHeidi'    => ($_POST['sendToHeidi'] === 'true' || $_POST['sendToHeidi'] === '1'),
    ];

    try {
        $http->request('PATCH', "api/Customer/{$customerId}", [
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'json'    => $payload,
        ]);

        $successMsg = "Kunde erfolgreich gespeichert.";

    } catch (Throwable $e) {
        $errorMsg = "Fehler beim Speichern: " . $e->getMessage();
    }
}

// Kunde laden
try {
    $customer = $customerApi->apiCustomerIdGet($customerId);

    // robust konvertieren
    $customer = json_decode(json_encode($customer), true);

} catch (Throwable $e) {
    http_response_code(500);
    echo "Fehler beim Laden: " . $e->getMessage();
    exit;
}

include 'header.php';
?>

<main>
    <div class="container py-4">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0">Kunde bearbeiten – <?= htmlspecialchars($customer['customerNumber']) ?></h1>
            <a href="customers.php" class="btn btn-outline-secondary btn-sm">Zurück</a>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <form method="post" class="card">
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-1">
                        <label class="form-label">ID</label>
                        <input class="form-control" value="<?= htmlspecialchars($customer['id']) ?>" readonly disabled>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Kundennummer</label>
                        <input name="customerNumber" class="form-control"
                               value="<?= htmlspecialchars($customer['customerNumber']) ?>" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Name</label>
                        <input name="name" class="form-control"
                               value="<?= htmlspecialchars($customer['name']) ?>" required>
                    </div>

                    <div class="col-md-11">
                        <label class="form-label">Zusatzinformation</label>
                        <input name="adresse" class="form-control"
                               value="<?= htmlspecialchars($customer['adresse'] ?? '') ?>">
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">SendToHeidi</label>
                        <select name="sendToHeidi" class="form-select">
                            <option value="false" <?= !$customer['sendToHeidi'] ? 'selected' : '' ?>>Nein</option>
                            <option value="true"  <?= $customer['sendToHeidi'] ? 'selected' : '' ?>>Ja</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="customers.php" class="btn btn-outline-secondary">Abbrechen</a>
            </div>
        </form>

    </div>
    <div class="container py-4">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0">  Geräte des Kunden</h1>

        </div>

        <div class="card">
            <div class="card-body">

                <!-- Loader -->
                <div id="loading" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Lade Geräte…</p>
                </div>

                <!-- Tabelle -->
                <div class="table-responsive d-none" id="tableWrap">
                    <table id="customerDevicesTable" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Device ID</th>
                            <th>Name</th>
                            <th>Typ</th>
                            <th>Region</th>
                            <th>Datenbank</th>
                            <th>Aktiv</th>
                            <th style="width:1%;"></th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>


</main>

<?php include 'footer.php'; ?>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const customerId = urlParams.get("id");

    async function loadCustomerDevices() {
        const loading = document.getElementById("loading");
        const tableWrap = document.getElementById("tableWrap");
        const tbody = document.querySelector("#customerDevicesTable tbody");

        try {
            const res = await fetch("getHeatDevicesForCustomer.php?id=" + customerId);
            if (!res.ok) throw new Error("HTTP " + res.status);

            const devices = await res.json();

            tbody.innerHTML = "";

            devices.forEach(d => {
                const tr = document.createElement("tr");

                tr.innerHTML = `
                    <td>${d.deviceID}</td>
                    <td>${d.deviceName ?? ""}</td>
                    <td>${d.deviceVendor ?? ""}</td>
                    <td>${d.fK_Region ?? ""}</td>
                    <td>${d.databaseName ?? ""}</td>
                    <td>
                        ${d.active
                    ? '<span class="badge bg-success">Ja</span>'
                    : '<span class="badge bg-secondary">Nein</span>'}
                    </td>
                    <td class="text-end">
                        <a href="editDevice.php?id=${d.id}" class="btn btn-sm btn-outline-primary">
                            Bearbeiten
                        </a>
                    </td>
                `;

                tbody.appendChild(tr);
            });

            loading.classList.add("d-none");
            tableWrap.classList.remove("d-none");

        } catch (err) {
            loading.innerHTML = `<p class="text-danger">Fehler: ${err.message}</p>`;
        }
    }

    loadCustomerDevices();
</script>
