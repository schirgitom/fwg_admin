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
            'adresse'        => $_POST['adresse'] ?: '',
            'sendToHeidi'    => ($_POST['sendToHeidi'] === 'true' || $_POST['sendToHeidi'] === '1'),
    ];



    try {
        $customer = $customerApi->apiCustomerIdPatch($customerId, $payload);

        // robust in Array
        $customer = json_decode(json_encode($customer), true);

        // API meldet Fehler
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

        header("Location: editCustomer.php?id={$customerId}&saved=1");
        exit;

    } catch (Throwable $e) {
        // ⛔ KEIN Redirect
        $errorMsg = $e->getMessage();
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

<main class="container py-4">


    <?php if (!empty($errorMsg)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                showToast(
                        <?= json_encode($errorMsg) ?>,
                    "error"
                );
            });
        </script>
    <?php endif; ?>

    <?php if (!empty($_GET['created'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                showToast("Kunde erfolgreich angelegt", "success");
            });
        </script>
    <?php endif; ?>


    <?php if (!empty($_GET['saved'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                showToast("Kunde gespeichert", "success");
            });
        </script>
    <?php endif; ?>

    <!-- ================== KUNDE BEARBEITEN ================== -->
    <div class="mb-5">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">
                Kunde bearbeiten – <?= htmlspecialchars($customer['customerNumber']) ?>
            </h1>
            <a href="customers.php" class="btn btn-outline-secondary btn-sm">Zurück</a>
        </div>

        <form method="post" class="card">
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-1">
                        <label class="form-label">ID</label>
                        <input class="form-control" value="<?= $customer['id'] ?>" disabled>
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
                        <label class="form-label">Heidi</label>
                        <select name="sendToHeidi" class="form-select">
                            <option value="false" <?= !$customer['sendToHeidi'] ? 'selected' : '' ?>>Nein</option>
                            <option value="true"  <?=  $customer['sendToHeidi'] ? 'selected' : '' ?>>Ja</option>
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

    
    <!-- ================== DEVICES ================== -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Geräte des Kunden</h2>
        <button class="btn btn-outline-primary btn-sm" id="btnAddDevice">
            + Gerät hinzufügen
        </button>
    </div>

    <div class="card">
        <div class="card-body">

            <div id="loadingDevices" class="text-center py-4">
                <div class="spinner-border text-primary"></div>
            </div>

            <div id="devicesTableWrap" class="table-responsive d-none">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Device ID</th>
                        <th>Name</th>
                        <th>Typ</th>
                        <th>Region</th>
                        <th>Datenbank</th>
                        <th>Aktiv</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody id="customerDevicesBody"></tbody>
                </table>
            </div>

        </div>
    </div>

</main>

<!-- ================== MODAL ================== -->
<div class="modal fade" id="assignDeviceModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Gerät zuweisen</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <!-- STEP INDICATOR -->
                <ul class="nav nav-pills nav-fill small mb-4">
                    <li class="nav-item"><span class="nav-link active" id="step1Tab">1. Gerät</span></li>
                    <li class="nav-item"><span class="nav-link disabled" id="step2Tab">2. Region</span></li>
                    <li class="nav-item"><span class="nav-link disabled" id="step3Tab">3. Prüfung</span></li>
                </ul>

                <!-- STEP 1 -->
                <div id="step1">
                    <table id="wizardDeviceTable" class="table table-hover w-100">
                        <thead>
                        <tr>
                            <th>Device ID</th>
                            <th>Name</th>
                            <th>Typ</th>
                            <th></th>
                        </tr>
                        </thead>
                    </table>
                </div>

                <!-- STEP 2 -->
                <div id="step2" class="d-none">
                    <label class="form-label">Region auswählen</label>
                    <select id="regionSelect" class="form-select"></select>
                </div>

                <!-- STEP 3 -->
                <div id="step3" class="d-none"></div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" id="btnBack" disabled>Zurück</button>
                <button class="btn btn-primary" id="btnNext">Weiter</button>
            </div>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
    /* ================== STATE ================== */
    let wizardStep = 1;
    let selectedDevice = null;
    let selectedRegion = null;
    let selectedRegionName = null;
    let deviceTable = null;

    /* ================== CUSTOMER DEVICES ================== */
    async function loadCustomerDevices() {
        const body = document.getElementById("customerDevicesBody");
        const wrap = document.getElementById("devicesTableWrap");
        const loading = document.getElementById("loadingDevices");

        const res = await fetch(`getHeatDevicesForCustomer.php?id=<?= $customerId ?>`);
        const data = await res.json();

        body.innerHTML = "";
        data.forEach(d => {
            body.innerHTML += `
            <tr>
                <td>${d.deviceID}</td>
                <td>${d.deviceName ?? ""}</td>
                <td>${d.deviceVendor}</td>
                <td>${d.regionName}</td>
                <td>${d.databaseName}</td>
                <td><span class="badge ${d.active?'bg-success':'bg-secondary'}">
                    ${d.active?'Ja':'Nein'}
                </span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="removeDevice(${d.id})">
                        Entfernen
                    </button>
                </td>
            </tr>`;
        });

        loading.classList.add("d-none");
        wrap.classList.remove("d-none");
    }

    loadCustomerDevices();

    /* ================== REMOVE ================== */
    async function removeDevice(id) {
        const ok = await confirmModal({
            title: "Gerät entfernen",
            message: "Gerät wirklich entfernen?",
            okClass: "btn-danger"
        });
        if (!ok) return;

        await fetch("setDeviceCustomer.php", {
            method: "POST",
            headers: { "Content-Type":"application/json" },
            body: JSON.stringify({
                deviceId: id,
                customerId: null,
                regionId: null
            })
        });
        showToast("Gerät entfernt", "success");
        loadCustomerDevices();
    }

    /* ================== WIZARD ================== */
    document.getElementById("btnAddDevice").onclick = () => {
        resetWizard();
        new bootstrap.Modal("#assignDeviceModal").show();
    };

    function resetWizard() {
        wizardStep = 1;
        selectedDevice = null;
        selectedRegion = null;
        showStep(1);
    }

    function showStep(step) {

        // Content umschalten
        ["step1","step2","step3"].forEach(s =>
            document.getElementById(s).classList.add("d-none")
        );
        document.getElementById("step"+step).classList.remove("d-none");

        // Tabs oben aktualisieren
        ["step1Tab","step2Tab","step3Tab"].forEach((id, idx) => {
            const tab = document.getElementById(id);
            tab.classList.remove("active");
            tab.classList.add("disabled");
            if (idx + 1 === step) {
                tab.classList.add("active");
                tab.classList.remove("disabled");
            }
        });

        // Buttons
        document.getElementById("btnBack").disabled = step === 1;
        document.getElementById("btnNext").textContent =
            step === 3 ? "Zuweisen" : "Weiter";

        wizardStep = step;
    }


    /* ================== MODAL INIT ================== */
    document.getElementById("assignDeviceModal")
        .addEventListener("shown.bs.modal", () => {

            if (!deviceTable) {
                deviceTable = new DataTable("#wizardDeviceTable", {
                    ajax: {
                        url: "getUnassignedHeatDevices.php",
                        dataSrc: ""   // 👈 DAS ist der Fix
                    },
                    columns: [
                        { data: "deviceID" },
                        { data: "deviceName" },
                        { data: "deviceVendor" },
                        {
                            data: null,
                            orderable: false,
                            render: d => `
                <button class="btn btn-sm btn-primary"
                        data-pick='${JSON.stringify(d)}'>
                    Auswählen
                </button>`
                        }
                    ]
                });
            }

            deviceTable.columns.adjust().draw(false);
        });

    /* ================== PICK DEVICE ================== */
    document.addEventListener("click", async e => {
        const btn = e.target.closest("[data-pick]");
        if (!btn) return;

        selectedDevice = JSON.parse(btn.dataset.pick);
        showToast(`Gerät ${selectedDevice.deviceID} gewählt`, "success");
        await loadRegions();
        showStep(2);
    });

    /* ================== REGIONS ================== */
    async function loadRegions() {
        const res = await fetch("getRegions.php");
        const regions = await res.json();

        const sel = document.getElementById("regionSelect");
        sel.innerHTML = `<option value="">Bitte wählen</option>`;
        regions.forEach(r =>
            sel.innerHTML += `<option value="${r.id}">${r.regionName}</option>`
        );
    }

    document.getElementById("regionSelect").onchange = e => {
        selectedRegion = e.target.value;
        selectedRegionName =
            e.target.options[e.target.selectedIndex].text;
    };

    /* ================== NAV ================== */
    document.getElementById("btnBack").onclick = () =>
        showStep(wizardStep - 1);

    document.getElementById("btnNext").onclick = async () => {

        if (wizardStep === 2) {
            if (!selectedRegion) return;
            document.getElementById("step3").innerHTML = `
            <ul class="list-group">
                <li class="list-group-item"><b>Gerät:</b> ${selectedDevice.deviceID}</li>
                <li class="list-group-item"><b>Region:</b> ${selectedRegionName}</li>
            </ul>`;
            showStep(3);
            return;
        }

        if (wizardStep === 3) {
            await fetch("setDeviceCustomer.php", {
                method: "POST",
                headers: { "Content-Type":"application/json" },
                body: JSON.stringify({
                    deviceId: selectedDevice.id,
                    customerId: <?= $customerId ?>,
                    regionId: selectedRegion
                })
            });

            showToast("Gerät zugewiesen", "success");
            bootstrap.Modal.getInstance(
                document.getElementById("assignDeviceModal")
            ).hide();
            loadCustomerDevices();
        }
    };
</script>
