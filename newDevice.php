<?php
require __DIR__ . '/vendor/autoload.php';
use App\Settings;

// === Config ===
$appConfig = require __DIR__ . '/config/config.php';
Settings::init($appConfig);
$apiBase = Settings::get("CentralAPI");

$config = (new FWGCentralAPI\Configuration())->setHost($apiBase);
$http   = new \GuzzleHttp\Client(['base_uri' => rtrim($apiBase, '/') . '/']);

$blockApi    = new \FWGCentralAPI\Api\BlockApi($http, $config);
$lineApi     = new \FWGCentralAPI\Api\LineApi($http, $config);

// Daten für Dropdowns
try {
    $blocks = array_map(fn($x)=>$x->jsonSerialize(), $blockApi->apiBlockAllGet());
    $lines  = array_map(fn($x)=>$x->jsonSerialize(), $lineApi->apiLineAllGet());
} catch (Throwable $e) {
    http_response_code(500);
    echo "Fehler beim Laden von Line/Block: " . htmlspecialchars($e->getMessage());
    exit;
}

$errorMsg = null; $successMsg = null;

// === POST: Neues Gerät anlegen ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deviceID     = trim((string)($_POST['deviceID'] ?? ''));
    $deviceVendor = $_POST['deviceVendor'] ?? '';
    $deviceName   = $_POST['deviceName']   ?? '';
    $active       = (($_POST['active'] ?? 'false') === 'true');
    $fkBlock      = isset($_POST['fK_Block']) ? (int)$_POST['fK_Block'] : null;
    $fkCustomer   = $_POST['fK_Customer'] ?? null;

    if ($deviceID === '' || !$fkBlock || !$fkCustomer) {
        $errorMsg = "Bitte Device EUI, Kunde und Block ausfüllen.";
    } else {
        $payload = [
                'deviceID'     => $deviceID,
                'deviceVendor' => $deviceVendor,
                'deviceName'   => $deviceName,
                'deviceType'   => 'HeatDevice',
                'active'       => $active,
                'fK_Block'     => $fkBlock,
                'fK_Customer'  => $fkCustomer,
        ];
        try {
            $resp = $http->post('api/HeatDevice', [
                    'headers' => ['Accept'=>'application/json','Content-Type'=>'application/json'],
                    'json'    => $payload,
            ]);
            // Erfolgreich → direkt zur Edit-Seite der Device EUI
            header('Location: editDevice.php?id=' . urlencode($deviceID));
            exit;
        } catch (Throwable $e) {
            $errorMsg = "Anlegen fehlgeschlagen: " . $e->getMessage();
        }
    }
}

$jsBlocks = json_encode($blocks, JSON_UNESCAPED_UNICODE);
$jsLines  = json_encode($lines,  JSON_UNESCAPED_UNICODE);

include 'header.php';
?>
<!-- Kunden-Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kunde auswählen / neu anlegen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="customerTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-pick" data-bs-toggle="tab" data-bs-target="#pane-pick" type="button" role="tab">Auswählen</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-create" data-bs-toggle="tab" data-bs-target="#pane-create" type="button" role="tab">Neu anlegen</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link disabled" id="tab-edit"
                                data-bs-toggle="tab" data-bs-target="#pane-edit"
                                type="button" role="tab" tabindex="-1" aria-disabled="true">Bearbeiten</button>
                    </li>
                </ul>

                <div class="tab-content pt-3">
                    <!-- AUSWÄHLEN -->
                    <div class="tab-pane fade show active" id="pane-pick" role="tabpanel">
                        <div class="row g-3 align-items-end mb-2">
                            <div class="col-md-10">
                                <label class="form-label">Suche</label>
                                <input id="customerSearch" type="search" class="form-control" placeholder="Name, Kundennummer, Adresse…" />
                            </div>
                            <div class="col-md-2 text-md-end">
                                <button id="btnReloadCustomers" type="button" class="btn btn-outline-primary">Neu laden</button>
                            </div>
                        </div>

                        <div id="custLoading" class="d-flex align-items-center justify-content-center" style="min-height:120px;">
                            <div class="text-center text-muted">
                                <div class="spinner-border" role="status" aria-hidden="true"></div>
                                <div class="mt-2">Lade Kunden…</div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="customerTable" class="table table-sm table-hover align-middle w-100 d-none">
                                <thead class="table-light">
                                <tr>
                                    <th style="width:12rem;">Kundennummer</th>
                                    <th>Name</th>
                                    <th>Adresse</th>
                                    <th>Heidi</th>
                                    <th style="width:1%"></th>
                                </tr>
                                </thead>
                                <tbody><!-- JS --></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- NEU ANLEGEN -->
                    <div class="tab-pane fade" id="pane-create" role="tabpanel">
                        <form id="createCustomerForm" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Kundennummer</label>
                                <input name="customerNumber" class="form-control" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Name</label>
                                <input name="name" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adresse</label>
                                <input name="adresse" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SendToHeidi</label>
                                <select name="sendToHeidi" class="form-select">
                                    <option value="false">Nein</option>
                                    <option value="true">Ja</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button id="btnCreateCustomer" type="submit" class="btn btn-primary">Kunden anlegen</button>
                                <span id="createStatus" class="ms-3 text-muted"></span>
                            </div>
                        </form>
                    </div>

                    <!-- BEARBEITEN -->
                    <div class="tab-pane fade" id="pane-edit" role="tabpanel">
                        <form id="editCustomerForm" class="row g-3">
                            <input type="hidden" id="ecId">
                            <div class="col-md-4">
                                <label class="form-label">Kundennummer</label>
                                <input id="ecNumber" class="form-control" required readonly>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Name</label>
                                <input id="ecName" class="form-control" required disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adresse</label>
                                <input id="ecAdresse" class="form-control" disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SendToHeidi</label>
                                <select id="ecSendToHeidi" class="form-select" disabled>
                                    <option value="false">Nein</option>
                                    <option value="true">Ja</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" id="btnUpdateCustomer" class="btn btn-primary" disabled>Speichern</button>
                                <span id="editStatus" class="ms-3 text-muted"></span>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<main>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0">Neues Gerät anlegen</h1>
            <div><a href="index.php" class="btn btn-outline-secondary btn-sm">Zur Übersicht</a></div>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <form method="post" class="card">
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Device EUI</label>
                        <input name="deviceID" class="form-control" required placeholder="z. B. 94193A0101024FFA">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Gerätename</label>
                        <input name="deviceName" class="form-control" placeholder="Anzeige-Name">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Hersteller</label>
                        <select name="deviceVendor" class="form-select">
                            <option value="">– bitte wählen –</option>
                            <option value="Kamstrup">Kamstrup</option>
                            <option value="Siemens">Siemens</option>
                            <option value="Sharky">Sharky</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Aktiv</label>
                        <select name="active" class="form-select">
                            <option value="true">Ja</option>
                            <option value="false" selected>Nein</option>
                        </select>
                    </div>

                    <!-- Kunde -->
                    <div class="col-md-6">
                        <label class="form-label">Kunde</label>
                        <div class="input-group">
                            <input id="customerDisplay" class="form-control" placeholder="Kein Kunde gewählt" readonly>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#customerModal">Kunde wählen</button>
                        </div>
                        <input type="hidden" name="fK_Customer" id="customerIdHidden">
                    </div>

                    <!-- Linie zuerst -->
                    <div class="col-md-6">
                        <label class="form-label">Linie</label>
                        <select id="lineSelect" class="form-select" required>
                            <option value="" disabled selected>Bitte wählen…</option>
                            <?php foreach ($lines as $l): ?>
                                <option value="<?= htmlspecialchars($l['id']) ?>">
                                    <?= htmlspecialchars($l['lineName'] ?? $l['name'] ?? ('Line '.$l['id'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Block abhängig von Linie -->
                    <div class="col-md-6">
                        <label class="form-label">Block</label>
                        <select name="fK_Block" id="blockSelect" class="form-select" required disabled>
                            <option value="" disabled selected>Bitte zuerst Linie wählen…</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-success">Anlegen</button>
                <a href="index.php" class="btn btn-outline-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</main>

<script src="/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- Line → Block ---
    const BLOCKS = <?= $jsBlocks ?>;
    const lineSelect  = document.getElementById('lineSelect');
    const blockSelect = document.getElementById('blockSelect');

    function populateBlocksForLine(lineId) {
        const list = BLOCKS.filter(b => String(b.fK_Line ?? b.fkLine ?? '') === String(lineId));
        blockSelect.innerHTML = '';
        if (!list.length) {
            blockSelect.innerHTML = '<option disabled selected>Keine Blöcke verfügbar</option>';
            blockSelect.disabled = true; return;
        }
        const ph = document.createElement('option');
        ph.disabled = true; ph.selected = true; ph.textContent = 'Bitte wählen…';
        blockSelect.appendChild(ph);
        list.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = b.name ?? b.blockName ?? `Block ${b.id}`;
            blockSelect.appendChild(opt);
        });
        blockSelect.disabled = false;
    }
    lineSelect.addEventListener('change', e => populateBlocksForLine(e.target.value));

    // ===== Kunden-Modal (ähnlich Edit) =====
    const CUSTOMERS_ENDPOINT = 'getCustomers.php';
    const CREATE_CUSTOMER_ENDPOINT = 'addCustomer.php';
    const UPDATE_CUSTOMER_ENDPOINT = 'setCustomer.php';

    let allCustomers = [];
    const $ = (id) => document.getElementById(id);
    const toggle = (el, show) => el.classList.toggle('d-none', !show);

    async function loadCustomers() {
        const loader = $('custLoading');
        const table  = $('customerTable');
        const tbody  = table.querySelector('tbody');

        toggle(loader, true); toggle(table, false);
        try {
            const res = await fetch(CUSTOMERS_ENDPOINT, { headers: { 'Accept': 'application/json' }});
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!Array.isArray(json)) throw new Error('Unerwartetes Format');

            allCustomers = json.map(c => ({
                id: c.id ?? c.customerId ?? c.customerID ?? null,
                customerNumber: c.customerNumber ?? '',
                name: c.name ?? c.customerName ?? '',
                adresse: c.adresse ?? c.address ?? '',
                sendToHeidi: (c.sendToHeidi === true || c.sendToHeidi === 1 || c.sendToHeidi === 'true') ? 'ja' : 'nein'
            }));

            // Sortierung nach Kundennummer
            allCustomers.sort((a,b) => {
                const na = parseInt(a.customerNumber,10), nb = parseInt(b.customerNumber,10);
                if (!isNaN(na) && !isNaN(nb)) return na - nb;
                return (a.customerNumber||'').localeCompare(b.customerNumber||'', 'de', {numeric:true});
            });

            renderCustomerRows(allCustomers);
            filterCustomers();

            toggle(loader, false); toggle(table, true);
        } catch (e) {
            $('custLoading').innerHTML = `<div class="text-danger text-center w-100">
        <div class="mb-2">Fehler beim Laden: ${escapeHtml(e.message)}</div>
        <button type="button" id="btnRetryCustomers" class="btn btn-sm btn-outline-primary">Erneut versuchen</button>
      </div>`;
        }
    }

    function renderCustomerRows(rows) {
        const tb = $('customerTable').querySelector('tbody');
        tb.innerHTML = rows.map(c => `
      <tr>
        <td class="text-nowrap">${escapeHtml(c.customerNumber)}</td>
        <td>${escapeHtml(c.name)}</td>
        <td>${escapeHtml(c.adresse ?? '')}</td>
        <td>${escapeHtml(c.sendToHeidi)}</td>
        <td class="text-end">
          <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-id="${c.id}" data-action="pick">Auswählen</button>
            <button type="button" class="btn btn-sm btn-outline-primary"   data-id="${c.id}" data-action="edit">Bearbeiten</button>
          </div>
        </td>
      </tr>
    `).join('');
    }

    function filterCustomers() {
        const q = ($('customerSearch').value || '').toLowerCase().trim();
        if (!q) { renderCustomerRows(allCustomers); return; }
        const filtered = allCustomers.filter(c =>
            (c.customerNumber||'').toLowerCase().includes(q) ||
            (c.name||'').toLowerCase().includes(q) ||
            (c.adresse||'').toLowerCase().includes(q)
        );
        renderCustomerRows(filtered);
    }

    function selectCustomerById(id) {
        const c = allCustomers.find(x => String(x.id) === String(id));
        if (!c) return;
        $('customerIdHidden').value = c.id;
        $('customerDisplay').value = `${c.customerNumber} - ${c.name}`;
        bootstrap.Modal.getInstance(document.getElementById('customerModal'))?.hide();
    }

    // Delegierte Clicks (Auswählen/Bearbeiten)
    document.addEventListener('click', (ev) => {
        const btn = ev.target.closest('#customerTable button[data-id][data-action],#btnRetryCustomers');
        if (!btn) return;
        if (btn.id === 'btnRetryCustomers') { loadCustomers(); return; }

        ev.preventDefault(); ev.stopPropagation();
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        if (action === 'pick') selectCustomerById(id);
        if (action === 'edit') openEditCustomer(id);
    });

    // Suche & Reload
    $('customerSearch').addEventListener('input', filterCustomers);
    $('btnReloadCustomers').addEventListener('click', loadCustomers);
    document.getElementById('customerModal').addEventListener('shown.bs.modal', () => {
        if (allCustomers.length === 0) loadCustomers();
        $('customerSearch').focus();
    });

    // Edit-Tab Öffnen/Befüllen
    function openEditCustomer(id) {
        const c = allCustomers.find(x => String(x.id) === String(id));
        if (!c) return;

        // Tab aktivieren
        const editTabBtn = document.getElementById('tab-edit');
        editTabBtn.classList.remove('disabled');
        editTabBtn.removeAttribute('aria-disabled');
        editTabBtn.removeAttribute('tabindex');
        new bootstrap.Tab(editTabBtn).show();

        // Felder füllen + entsperren
        $('ecId').value = c.id;
        $('ecNumber').value = c.customerNumber ?? '';
        $('ecName').value = c.name ?? '';
        $('ecAdresse').value = c.adresse ?? '';
        $('ecSendToHeidi').value = (c.sendToHeidi === 'ja') ? 'true' : 'false';
        ['ecName','ecAdresse','ecSendToHeidi','btnUpdateCustomer'].forEach(id => $(id).disabled = false);
        $('editStatus').textContent = '';
    }

    // Edit speichern
    document.getElementById('editCustomerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = {
            id: $('ecId').value,
            customerNumber: $('ecNumber').value.trim(),
            name: $('ecName').value.trim(),
            adresse: $('ecAdresse').value.trim(),
            sendToHeidi: $('ecSendToHeidi').value === 'true'
        };
        const status = $('editStatus'); const btn = $('btnUpdateCustomer');
        btn.disabled = true; status.textContent = 'Wird gespeichert…';
        try {
            const res = await fetch(UPDATE_CUSTOMER_ENDPOINT, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json','Accept':'application/json' },
                body: JSON.stringify(payload)
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const updated = await res.json();

            // local aktualisieren
            const idx = allCustomers.findIndex(x => String(x.id) === String(payload.id));
            if (idx >= 0) {
                allCustomers[idx] = {
                    id: payload.id,
                    customerNumber: updated.customerNumber ?? payload.customerNumber,
                    name: updated.name ?? updated.customerName ?? payload.name,
                    adresse: updated.adresse ?? updated.address ?? payload.adresse,
                    sendToHeidi: (updated.sendToHeidi ?? payload.sendToHeidi) ? 'ja' : 'nein'
                };
                renderCustomerRows(allCustomers);
            }

            status.textContent = 'Gespeichert.';
            btn.disabled = false;

            // Falls aktueller Kunde ausgewählt → Anzeige syncen
            if ($('customerIdHidden').value == payload.id) {
                const c = allCustomers[idx];
                $('customerDisplay').value = `${c.customerNumber} - ${c.name}`;
            }

            // Tab wieder sperren & zurück
            const editTabBtn = document.getElementById('tab-edit');
            editTabBtn.classList.add('disabled');
            editTabBtn.setAttribute('aria-disabled','true');
            editTabBtn.setAttribute('tabindex','-1');
            new bootstrap.Tab(document.querySelector('#tab-pick')).show();
        } catch (err) {
            status.textContent = 'Fehler: ' + err.message;
            btn.disabled = false;
        }
    });

    // Create Customer
    function formToJson(form){ const fd=new FormData(form); const obj={}; for(const [k,v] of fd.entries()) obj[k]=v; obj.sendToHeidi=(obj.sendToHeidi==='true'); return obj; }
    function escapeHtml(s){ return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }

    $('createCustomerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = formToJson(e.currentTarget);
        $('btnCreateCustomer').disabled = true;
        $('createStatus').textContent = 'Wird gespeichert…';
        try {
            const res = await fetch(CREATE_CUSTOMER_ENDPOINT, {
                method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
                body: JSON.stringify(data)
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const created = await res.json();

            const newC = {
                id: created.data?.id ?? created.id ?? null,
                customerNumber: created.data?.customerNumber ?? created.customerNumber ?? data.customerNumber,
                name: created.name ?? created.customerName ?? data.name,
                adresse: created.adresse ?? created.address ?? data.adresse,
                sendToHeidi: (created.sendToHeidi ?? data.sendToHeidi) ? 'ja' : 'nein'
            };
            allCustomers.unshift(newC);
            renderCustomerRows(allCustomers);
            $('customerSearch').value = '';
            $('createStatus').textContent = 'Angelegt.';
            $('btnCreateCustomer').disabled = false;

            // Sofort auswählen & schließen
            selectCustomerById(newC.id);
        } catch(err) {
            $('createStatus').textContent = 'Fehler: ' + err.message;
            $('btnCreateCustomer').disabled = false;
        }
    });
</script>
</body>
</html>
