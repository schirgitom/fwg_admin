<?php

require __DIR__ . '/vendor/autoload.php';
use App\Settings;

// Gemeinsame Config
$appConfig = require __DIR__ . '/config/config.php';
Settings::init($appConfig);
$apiBase = Settings::get("CentralAPI");

// FWG CentralAPI Clients
$config = (new FWGCentralAPI\Configuration())->setHost($apiBase);
$guzzle = new \GuzzleHttp\Client([ 'base_uri' => rtrim($apiBase, '/') . '/' ]);

$heatApi     = new \FWGCentralAPI\Api\HeatDeviceApi($guzzle, $config);
$regionApi   = new \FWGCentralAPI\Api\RegionApi($guzzle, $config);
$customerApi = new \FWGCentralAPI\Api\CustomerApi($guzzle, $config);

// Hilfsfunktion: sicheres Array
function toArray($val) {
    if ($val instanceof JsonSerializable) $val = $val->jsonSerialize();
    return json_decode(json_encode($val), true);
}

// ---- Parameter prüfen ----
$deviceId = $_GET['id'] ?? null;
if (!$deviceId) {
    http_response_code(400);
    echo "Fehlender Parameter: id";
    exit;
}

$errorMsg = null;
$successMsg = null;

// ---- POST-SAVE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payload = [
            'deviceID'      => $_POST['deviceID'],
            'active'        => ($_POST['active'] ?? 'false') === 'true',
            'deviceVendor'  => $_POST['deviceVendor'] ?? '',
            'fK_Region'     => isset($_POST['fK_Region']) ? (int)$_POST['fK_Region'] : null,
            'fK_Customer'   => isset($_POST['fK_Customer']) ? (int)$_POST['fK_Customer'] : null
    ];

    try {
        $resp = $guzzle->request('PATCH', "api/HeatDevice/{$deviceId}", [
                'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                ],
                'json' => $payload,
                'timeout' => 10,
        ]);

        $successMsg = "Erfolgreich aktualisiert";

    } catch (\Throwable $e) {
        $errorMsg = "Speichern fehlgeschlagen: " . $e->getMessage();
    }
}


// ---- GET-Daten laden ----
try {
    $device   = toArray($heatApi->apiHeatDeviceIdGet($deviceId));
    //($device);

   // var_dump($device);

    $customerId =
            $device['fK_Customer']
            ?? $device['f_k_customer']
            ?? $device['fkCustomer']
            ?? null;

    if (!$customerId) {
        throw new Exception("CustomerId fehlt im Gerätedatensatz.");
    }
    $customer = toArray($customerApi->apiCustomerIdGet($customerId));
    $regions  = array_map('toArray', $regionApi->apiRegionAllGet());

    // Dictionaries
    $regionById = [];
    foreach ($regions as $r) {
        $regionById[(string)$r['id']] = $r;
    }

    //var_dump($device);
    $currentRegionId = $device['fK_Region'] ?? null;
    $currentRegion   = $currentRegionId ? ($regionById[(string)$currentRegionId] ?? null) : null;

    //var_dump($regions);
    $customerName = ($customer["customerNumber"] ?? '') . ' - ' . ($customer['name'] ?? '');

} catch (\Throwable $e) {
    http_response_code(500);
    echo "Fehler beim Laden: " . htmlspecialchars($e->getMessage());
    exit;
}

$jsRegions        = json_encode($regions, JSON_UNESCAPED_UNICODE);
$jsCurrRegionId   = json_encode($currentRegionId);

include 'header.php';
include 'customerDialog.php';

?>

<main>
    <div class="container py-4">

        <!-- Titel -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0">Gerät bearbeiten – <?= htmlspecialchars($device['deviceID']) ?> (<?= htmlspecialchars($customerName) ?>)</h1>
            <div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">Zur Übersicht</a>
            </div>
        </div>

        <!-- Fehlermeldungen -->
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

                    <div class="col-md-2">
                        <label class="form-label">ID</label>
                        <input name="ID" class="form-control" value="<?= htmlspecialchars($device['id']) ?>" disabled>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Device EUI</label>
                        <input name="deviceID" class="form-control" required
                               value="<?= htmlspecialchars($device['deviceID']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Hersteller</label>
                        <select name="deviceVendor" class="form-select">
                            <?php $vendor = $device['deviceVendor'] ?? ''; ?>
                            <option value="Kamstrup" <?= $vendor === 'Kamstrup' ? 'selected' : '' ?>>Kamstrup</option>
                            <option value="Siemens"  <?= $vendor === 'Siemens'  ? 'selected' : '' ?>>Siemens</option>
                            <option value="Sharky"   <?= $vendor === 'Sharky'   ? 'selected' : '' ?>>Sharky</option>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">Aktiv</label>
                        <?php $isActive = $device['active'] == true; ?>
                        <select name="active" class="form-select">
                            <option value="true"  <?= $isActive ? 'selected' : '' ?>>Ja</option>
                            <option value="false" <?= !$isActive ? 'selected' : '' ?>>Nein</option>
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
                               value="<?= htmlspecialchars($customerId) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Datenbank</label>
                        <input class="form-control" disabled
                               value="<?= htmlspecialchars($device['databaseName'] ?? '') ?>">
                    </div>

                    <!-- REGION DROPDOWN -->
                    <div class="col-md-6">
                        <label class="form-label">Region</label>
                        <select name="fK_Region" id="regionSelect" class="form-select" required>
                            <option value="" disabled selected>Bitte wählen…</option>
                            <?php foreach ($regions as $r): ?>
                                <option value="<?= htmlspecialchars($r['id']) ?>"
                                        <?= (string)$currentRegionId === (string)$r['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['regionName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
            </div>

            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="index.php" class="btn btn-outline-secondary">Abbrechen</a>

                <button type="button" id="btnMigrate" class="btn btn-warning ms-auto">
                    Visualisierung aktualisieren
                </button>
            </div>
        </form>
    </div>

    <!-- Database Changes -->
    <div class="container mt-5">
        <h3 class="mb-3">Änderungsverlauf</h3>

        <div id="changesLoading" class="text-center py-4">
            <div class="spinner-border" role="status"></div>
            <div class="mt-2 text-muted">Lade Änderungen…</div>
        </div>

        <div class="table-responsive d-none" id="changesTableWrap">
            <table id="changesTable" class="table table-striped table-hover align-middle">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Start</th>
                    <th>Ende</th>
                    <th>Von Region</th>
                    <th>Nach Region</th>
                    <th>Alte DB</th>
                    <th>Neue DB</th>
                    <th>Rows</th>
                </tr>
                </thead>
                <tbody><!-- JS füllt hier ein --></tbody>
            </table>
        </div>
    </div>


    <!-- TOAST -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100;">
        <div id="actionToast" class="toast border-0" role="alert">
            <div class="d-flex">
                <div id="toastBody" class="toast-body text-white">...</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>



    <script>
        function showToast(message, type='success') {
            const toastEl = document.getElementById('actionToast');
            const body = document.getElementById('toastBody');

            let bg = 'bg-success';
            if (type === 'error') bg = 'bg-danger';
            if (type === 'warning') bg = 'bg-warning text-dark';
            if (type === 'info') bg = 'bg-info text-dark';

            body.className = `toast-body ${bg.includes('text-dark') ? 'text-dark' : 'text-white'} ${bg}`;
            toastEl.className = `toast border-0 ${bg}`;
            body.textContent = message;

            new bootstrap.Toast(toastEl).show();
        }

        document.getElementById('btnMigrate').addEventListener('click', async () => {
            const deviceEUI = document.querySelector('input[name="deviceID"]').value;

            try {
                const res = await fetch(`MigrateHeatDevice.php?id=${encodeURIComponent(deviceEUI)}`);
                if (!res.ok) throw new Error('HTTP ' + res.status);

                showToast('Migration erfolgreich durchgeführt!');
            } catch (err) {
                showToast('Fehler: ' + err.message, 'error');
            }
        });


        document.addEventListener("DOMContentLoaded", () => {

            const heatDeviceId = "<?php echo htmlspecialchars($deviceId); ?>";
            loadDbChanges(heatDeviceId);

        });

        async function loadDbChanges(deviceId) {
            const loader = document.getElementById("changesLoading");
            const tableWrap = document.getElementById("changesTableWrap");
            const tbody = document.querySelector("#changesTable tbody");

            loader.classList.remove("d-none");

            try {
                const res = await fetch(`getDatabaseChanges.php?id=${encodeURIComponent(deviceId)}`);
                if (!res.ok) throw new Error("HTTP " + res.status);

                const list = await res.json();

                tbody.innerHTML = "";

                if (!Array.isArray(list) || list.length === 0) {
                    tbody.innerHTML = `
                <tr><td colspan="8" class="text-center text-muted py-3">
                    Keine Änderungen vorhanden.
                </td></tr>`;
                } else {
                    list.forEach(ch => {
                        const tr = document.createElement("tr");

                        // Formatierung
                        const start = ch.migrationStart ? new Date(ch.migrationStart).toLocaleString("de-DE") : "-";
                        const end   = ch.migrationEnd   ? new Date(ch.migrationEnd).toLocaleString("de-DE") : "-";

                        const oldRegion = ch.oldRegion?.regionName ?? ("ID " + ch.fK_OldRegion);
                        const newRegion = ch.newRegion?.regionName ?? ("ID " + ch.fK_NewRegion);

                        tr.innerHTML = `
                    <td>${ch.id}</td>
                    <td>${start}</td>
                    <td>${end}</td>
                    <td>${oldRegion}</td>
                    <td>${newRegion}</td>
                    <td>${ch.oldDatabaseName ?? "-"}</td>
                    <td>${ch.newDatabaseName ?? "-"}</td>
                    <td>${ch.rowsAffected ?? "-"}</td>
                `;

                        tbody.appendChild(tr);
                    });
                }

                loader.classList.add("d-none");
                tableWrap.classList.remove("d-none");

            } catch (err) {
                loader.innerHTML = `
            <div class="text-danger">
                Fehler beim Laden: ${err.message}
            </div>`;
            }
        }
    </script>

</main>
<?php include 'footer.php'; ?>
