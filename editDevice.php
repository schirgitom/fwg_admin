<?php

require __DIR__ . '/vendor/autoload.php';
use App\Settings;

// Gemeinsame Config
$appConfig = require __DIR__ . '/config/config.php';
Settings::init($appConfig);
$apiBase = Settings::get("CentralAPI"); // z. B. https://centralapi.example.com

// FWG CentralAPI Clients
$config = (new FWGCentralAPI\Configuration())->setHost($apiBase);
$guzzle = new \GuzzleHttp\Client([ 'base_uri' => rtrim($apiBase, '/') . '/' ]);

$heatApi     = new \FWGCentralAPI\Api\HeatDeviceApi($guzzle, $config);
$blockApi    = new \FWGCentralAPI\Api\BlockApi($guzzle, $config);
$lineApi     = new \FWGCentralAPI\Api\LineApi($guzzle, $config);
$customerApi = new \FWGCentralAPI\Api\CustomerApi($guzzle, $config);

// Hilfsfunktionen
function toArray($value) {
  if (is_array($value)) return $value;
  if ($value instanceof \JsonSerializable) $value = $value->jsonSerialize();
  return json_decode(json_encode($value), true);
}
function findByDeviceId(array $devices, $id) {
  foreach ($devices as $d) {
    $a = toArray($d);
    $did = $a['deviceID'] ?? $a['deviceId'] ?? $a['id'] ?? null;
    if ((string)$did === (string)$id) return $a;
  }
  return null;
}

// Request-Parameter
$deviceId = $_GET['id'] ?? null;
if (!$deviceId) {
  http_response_code(400);
  echo "Fehlender Parameter: id";
  exit;
}

$errorMsg = null;

// POST: speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $payload = [
    'deviceID'    => $_POST['deviceID']    ?? $deviceId,
    'deviceName'  => $_POST['deviceName']  ?? '',
    'active'      => isset($_POST['active']) ? ($_POST['active'] === 'true' || $_POST['active'] === '1') : false,
    'deviceVendor'=> $_POST['deviceVendor']?? '',
    'fK_Block'    => isset($_POST['fK_Block']) ? (int)$_POST['fK_Block'] : null,
  ];

  try {
    // PUT /api/HeatDevice/{id}
    $resp = $guzzle->request('PUT', "api/HeatDevice/{$deviceId}", [
      'headers' => [ 'Accept' => 'application/json', 'Content-Type' => 'application/json' ],
      'json'    => $payload,
      'timeout' => 10,
    ]);
    // Erfolg → zurück zur Liste
    header('Location: heat-devices.php?updated=1');
    exit;
  } catch (\Throwable $e) {
    $errorMsg = "Speichern fehlgeschlagen: " . $e->getMessage();
  }
}

// GET: Daten laden
try {
  // HeatDevice laden (einfachheitshalber All + Filter; falls es einen /{id}-Endpoint gibt, kannst du den verwenden)


  $device = $heatApi->apiHeatDeviceIdGet($deviceId);




  if (!$device) {
    http_response_code(404);
    echo "Gerät nicht gefunden: " . htmlspecialchars($deviceId);
    exit;
  }


  $customer = $customerApi->apiCustomerIdGet($device['f_k_customer']);

  // Blöcke & Lines laden
  $blocks = array_map('toArray', $blockApi->apiBlockAllGet());
  $lines  = array_map('toArray', $lineApi->apiLineAllGet());

  // Hilfs-Maps
  $blockById = [];
  foreach ($blocks as $b) { if (isset($b['id'])) $blockById[(string)$b['id']] = $b; }
  $lineById  = [];
  foreach ($lines as $l)  { if (isset($l['id'])) $lineById[(string)$l['id']]  = $l; }

  // aktuelle Auswahl
  $currentBlockId = $device['f_k_block'] ?? $device['fkBlock'] ?? null;
  $currentBlock   = $currentBlockId !== null ? ($blockById[(string)$currentBlockId] ?? null) : null;
  $currentLineId  = $currentBlock['fK_Line'] ?? $currentBlock['fkLine'] ?? null;
  $currentLine    = $currentLineId !== null ? ($lineById[(string)$currentLineId] ?? null) : null;

  $customerName   = $customer["customer_number"] . ' - ' . $customer['name'];
} catch (\Throwable $e) {
  http_response_code(500);
  echo "Fehler beim Laden: " . htmlspecialchars($e->getMessage());
  exit;
}

// Für JS bereitstellen
$jsBlocks = json_encode($blocks, JSON_UNESCAPED_UNICODE);
$jsLines  = json_encode($lines,  JSON_UNESCAPED_UNICODE);
$jsCurrLineId = json_encode($currentLineId);

var_dump($device);
    echo "------------";
    var_dump($customer);
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
                                type="button" role="tab" tabindex="-1" aria-disabled="true">
                            Bearbeiten
                        </button>
                    </li>
                </ul>

                <div class="tab-content pt-3">
                    <!-- TAB: AUSWÄHLEN -->
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

                    <!-- TAB: NEU ANLEGEN -->
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
    <h1 class="h3 mb-0">Gerät bearbeiten - <?php echo $device['device_id'] ?> - (<?php echo $customerName ?>)</h1>
    <div>
      <a href="heat-devices.php" class="btn btn-outline-secondary btn-sm">Zur Übersicht</a>
    </div>
  </div>

  <?php if ($errorMsg): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>

  <form method="post" class="card">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label">DeviceID</label>
          <input name="deviceID" value="<?= htmlspecialchars($device['id'] ?? $deviceId) ?>" class="form-control" readonly>
        </div>

        <div class="col-md-6">
          <label class="form-label">Device EUI</label>
          <input name="deviceName" value="<?= htmlspecialchars($device['device_id'] ?? '') ?>" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Hersteller</label>
            <select name="deviceVendor" class="form-select">
                <?php
                $vendor = $device['device_vendor'];
                echo $vendor;
                ?>
                <option value="Kamstrup"  <?= $vendor == 'Kamstrup' ? 'selected' : '' ?>>Kamstrup</option>
                <option value="Siemens" <?=  $vendor == 'Siemens' ? 'selected' : '' ?>>Siemens</option>
                <option value="Sharky" <?=  $vendor == 'Sharky' ? 'selected' : '' ?>>Sharky</option>
            </select>
        </div>

        <div class="col-md-1">
          <label class="form-label">Aktiv</label>
          <select name="active" class="form-select">
            <?php
              $isActive = ($device['active'] === true || $device['active'] === 1 || $device['active'] === 'true');
            ?>
            <option value="true"  <?= $isActive ? 'selected' : '' ?>>Ja</option>
            <option value="false" <?= !$isActive ? 'selected' : '' ?>>Nein</option>
          </select>
        </div>



          <div class="col-md-8">
              <label class="form-label">Kunde</label>
              <div class="input-group">
                  <input id="customerDisplay" class="form-control"
                         value="<?= htmlspecialchars($customerName) ?>" placeholder="Kein Kunde gewählt" readonly>
                  <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#customerModal">
                      Kunde wählen
                  </button>
              </div>
              <!-- hier speichern wir die gewählte Kunden-ID fürs Submit -->
              <input type="hidden" name="fK_Customer" id="customerIdHidden"
                     value="<?= htmlspecialchars($device['fK_Customer'] ?? $device['fkCustomer'] ?? '') ?>">
          </div>

        <div class="col-md-3">
          <label class="form-label">Datenbank</label>
          <input value="<?= htmlspecialchars($device['database_name'] ?? '') ?>" class="form-control" disabled>
        </div>

          <!-- Line zuerst -->
          <div class="col-md-6">
              <label class="form-label">Linie</label>
              <select id="lineSelect" class="form-select" required>
                  <option value="" disabled selected>Bitte wählen…</option>
                  <?php foreach ($lines as $l): ?>
                      <option value="<?= htmlspecialchars($l['id']) ?>"
                              <?= ((string)($currentLine['id'] ?? '') === (string)$l['id']) ? 'selected' : '' ?>>
                          <?= htmlspecialchars($l['lineName'] ?? $l['name'] ?? ('Line '.$l['id'])) ?>
                      </option>
                  <?php endforeach; ?>
              </select>
              <div class="form-text">Wähle zunächst die Linie.</div>
          </div>

          <!-- Danach Block, gefiltert nach gewählter Line -->
          <div class="col-md-6">
              <label class="form-label">Block</label>
              <select name="fK_Block" id="blockSelect" class="form-select" required>
                  <option value="" disabled>Bitte zuerst Line wählen…</option>
                  <?php if (!empty($currentLineId)): ?>
                      <?php foreach ($blocks as $b): if ((string)($b['fK_Line'] ?? $b['fkLine'] ?? '') !== (string)$currentLineId) continue; ?>
                          <option value="<?= htmlspecialchars($b['id']) ?>"
                                  <?= ((string)$b['id'] === (string)($currentBlockId ?? '')) ? 'selected' : '' ?>>
                              <?= htmlspecialchars($b['name'] ?? $b['blockName'] ?? ('Block '.$b['id'])) ?>
                          </option>
                      <?php endforeach; ?>
                  <?php endif; ?>
              </select>
              <div class="form-text">Es werden nur Blöcke der gewählten Line angezeigt.</div>
          </div>
      </div>
    </div>

    <div class="card-footer d-flex gap-2">
      <button type="submit" class="btn btn-primary">Speichern</button>
      <a href="heat-devices.php" class="btn btn-outline-secondary">Abbrechen</a>
    </div>
  </form>
</div>

<script src="/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Blöcke & Lines aus PHP
  const BLOCKS = <?= $jsBlocks ?>;
  const LINES  = <?= $jsLines ?>;
  const initialLineId = <?= $jsCurrLineId ?>;

  // Hilfs-Maps
  const lineSelect  = document.getElementById('lineSelect');
  const blockSelect = document.getElementById('blockSelect');

  /** Füllt den Block-Select anhand der aktuell gewählten Line */
  function populateBlocksForLine(selectedLineId, preselectBlockId = null) {
      // Alle Blocks der Line
      const blocksForLine = BLOCKS.filter(b => String(b?.fK_Line ?? b?.fkLine ?? '') === String(selectedLineId));

      // Optionen neu aufbauen
      blockSelect.innerHTML = '';
      if (!selectedLineId || blocksForLine.length === 0) {
          blockSelect.innerHTML = '<option value="" disabled selected>Keine Blöcke verfügbar</option>';
          blockSelect.disabled = true;
          return;
      }

      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.disabled = true;
      placeholder.selected = !preselectBlockId;
      placeholder.textContent = 'Bitte wählen…';
      blockSelect.appendChild(placeholder);

      blocksForLine.forEach(b => {
          const opt = document.createElement('option');
          opt.value = String(b.id);
          opt.textContent = b.name ?? b.blockName ?? `Block ${b.id}`;
          if (preselectBlockId && String(preselectBlockId) === String(b.id)) opt.selected = true;
          blockSelect.appendChild(opt);
      });

      blockSelect.disabled = false;
  }

  /** Wenn die Line geändert wird, Blocks nachziehen */
  lineSelect.addEventListener('change', () => {
      const selectedLineId = lineSelect.value;
      populateBlocksForLine(selectedLineId, null);
  });

  // --- Initiale Vorbelegung (aus PHP) ---
  (function initLineBlock() {
      const initialLineId  = <?= json_encode($currentLineId) ?>;   // aus PHP
      const initialBlockId = <?= json_encode($currentBlockId) ?>;  // aus PHP

      // Falls Line in HTML bereits vorausgewählt ist, Dropdown befüllen
      const selectedLineId = lineSelect.value || initialLineId || '';
      if (selectedLineId) {
          lineSelect.value = String(selectedLineId);
          populateBlocksForLine(selectedLineId, initialBlockId || null);
      } else {
          // Ohne Vorauswahl: Block-Select gesperrt lassen
          blockSelect.disabled = true;
      }
  })();
        // ===== Endpoints (ggf. anpassen) =====
        const CUSTOMERS_ENDPOINT = 'getCustomers.php';     // GET: liefert Array von Kunden
        const CREATE_CUSTOMER_ENDPOINT = 'addCustomer.php'; // POST: erzeugt neuen Kunden
        const UPDATE_CUSTOMER_ENDPOINT = 'setCustomer.php'; // <— NEU
        // ===== Cache / State =====
        let allCustomers = [];

        const toggle = (el, show) => el.classList.toggle('d-none', !show);

        // ===== DOM helpers =====
        const $ = (id) => document.getElementById(id);
        const show = (el, on) => el.style.display = on ? '' : 'none';



        // ===== Kunden laden =====
        async function loadCustomers() {
            const loader = $('custLoading');
            const table  = $('customerTable');
            const tbody  = table.querySelector('tbody');

            // Start: Loader an, Tabelle aus
            toggle(loader, true);
            toggle(table,  false);

            try {
                const res = await fetch('GetCustomers.php', { headers: { 'Accept': 'application/json' }});
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const json = await res.json();
                if (!Array.isArray(json)) throw new Error('Unerwartetes Format (kein Array)');

                console.log(json);

                allCustomers = json.map(c => ({
                    id: c.id ?? c.customerId ?? c.customerID ?? null,
                    customerNumber: c.customerNumber ?? '',
                    name: c.name ?? c.customerName ?? '',
                    adresse: c.adresse ?? c.address ?? '',
                    sendToHeidi: (c.sendToHeidi === true || c.sendToHeidi === 1 || c.sendToHeidi === 'true') ? 'ja' : 'nein'
                }));

                allCustomers.sort((a, b) => {
                    // Wenn es echte Zahlen sind:
                    const na = parseInt(a.customerNumber, 10);
                    const nb = parseInt(b.customerNumber, 10);
                    if (!isNaN(na) && !isNaN(nb)) return na - nb;

                    // Fallback: Stringvergleich
                    return (a.customerNumber || '').localeCompare(b.customerNumber || '', 'de', {numeric:true});
                });

                // Render
                tbody.innerHTML = allCustomers.map(c => `
                      <tr>
                        <td class="text-nowrap">${escapeHtml(c.customerNumber)}</td>
                        <td>${escapeHtml(c.name)}</td>
                        <td>${escapeHtml(c.adresse)}</td>
                        <td>${escapeHtml(c.sendToHeidi)}</td>
                        <td class="text-end">
                          <div class="btn-group">
                              <button type="button" class="btn btn-sm btn-outline-secondary"
              data-id="${c.id}" data-action="pick">Auswählen</button>
      <button type="button" class="btn btn-sm btn-outline-primary"
              data-id="${c.id}" data-action="edit">Bearbeiten</button>
                          </div>
                        </td>
                      </tr>
                    `).join('');

                // Erfolg: Loader aus, Tabelle an
                toggle(loader, false);
                toggle(table,  true);

            } catch (e) {
                // Fehlertext im Loader zeigen
                $('custLoading').innerHTML = `
      <div class="text-danger text-center w-100">
        <div class="mb-2">Fehler beim Laden: ${escapeHtml(e.message)}</div>
        <button type="button" id="btnRetryCustomers" class="btn btn-sm btn-outline-primary">Erneut versuchen</button>
      </div>`;
            }


        }

        document.getElementById('editCustomerForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const payload = {
                id: document.getElementById('ecId').value,
                customerNumber: document.getElementById('ecNumber').value.trim(),
                name: document.getElementById('ecName').value.trim(),
                adresse: document.getElementById('ecAdresse').value.trim(),
                sendToHeidi: document.getElementById('ecSendToHeidi').value === 'true'
            };

            const status = document.getElementById('editStatus');
            const btn = document.getElementById('btnUpdateCustomer');
            btn.disabled = true;
            status.textContent = 'Wird gespeichert…';

            try {
                const res = await fetch(UPDATE_CUSTOMER_ENDPOINT, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const updated = await res.json();

                // Liste aktualisieren (in-memory)
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

                // Optional: gewählten Kunden im Formular aktualisieren, falls derselbe
                if (document.getElementById('customerIdHidden').value == payload.id) {
                    document.getElementById('customerDisplay').value = allCustomers[idx].name || allCustomers[idx].customerNumber;
                }

                const editTabBtn = document.getElementById('tab-edit');
                editTabBtn.classList.add('disabled');
                editTabBtn.setAttribute('aria-disabled', 'true');
                editTabBtn.setAttribute('tabindex', '-1');

                // Zurück zum „Auswählen“-Tab
                new bootstrap.Tab(document.querySelector('#tab-pick')).show();

            } catch (err) {
                status.textContent = 'Fehler: ' + err.message;
                btn.disabled = false;
            }
        });



        function renderCustomerRows(rows) {
            const tb = $('customerTable').querySelector('tbody');
            tb.innerHTML = '';
            for (const c of rows) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
        <td class="text-nowrap">${escapeHtml(c.customerNumber)}</td>
        <td>${escapeHtml(c.name)}</td>
        <td>${escapeHtml(c.adresse ?? '')}</td>
   <td>${escapeHtml(c.sendToHeidi ?? '')}</td>
         <td class="text-end">
           <div class="btn-group">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-id="${c.id}">Auswählen</button>
            <button type="button" class="btn btn-sm btn-outline-primary"   data-id="${c.id}" data-action="edit">Bearbeiten</button>
            </div>
        </td>
      `;
                tb.appendChild(tr);
            }
        }

        function filterCustomers() {
            const q = ($('customerSearch').value || '').toLowerCase().trim();
            if (!q) { renderCustomerRows(allCustomers); return; }
            const filtered = allCustomers.filter(c =>
                (c.customerNumber || '').toLowerCase().includes(q) ||
                (c.name || '').toLowerCase().includes(q) ||
                (c.adresse || '').toLowerCase().includes(q)
            );
            renderCustomerRows(filtered);
        }

        function selectCustomerById(id) {
            const c = allCustomers.find(x => String(x.id) === String(id));
            if (!c) return;
            $('customerIdHidden').value = c.id;
            $('customerDisplay').value = c.customerNumber + " - " + c.name;
            // Modal schließen
            bootstrap.Modal.getInstance(document.getElementById('customerModal'))?.hide();
        }

        // Delegiertes Click-Handling für "Auswählen"-Buttons
        document.addEventListener('click', (ev) => {
            const btn = ev.target.closest('#customerTable button[data-id][data-action]');
            if (!btn) return;

            // verhindert, dass irgendetwas das Modal schließt
            ev.preventDefault();
            ev.stopPropagation();

            const id = btn.getAttribute('data-id');
            const action = btn.getAttribute('data-action');

            if (action === 'pick') {
                selectCustomerById(id); // schließt Modal bewusst
            } else if (action === 'edit') {
                openEditCustomer(id);   // Modal bleibt offen
            }
        });


        $('customerSearch').addEventListener('input', filterCustomers);
        $('btnReloadCustomers').addEventListener('click', loadCustomers);

        // Wenn Modal geöffnet wird → Kunden laden (nur beim ersten Mal)
        document.getElementById('customerModal').addEventListener('shown.bs.modal', () => {
            if (allCustomers.length === 0) loadCustomers();
            $('customerSearch').focus();
        });

        document.addEventListener('click', (ev) => {
            const btn = ev.target.closest('#customerTable button[data-id]');
            if (!btn) return;
            const id = btn.getAttribute('data-id');
            const action = btn.getAttribute('data-action');

            if (action === 'pick') {
                selectCustomerById(id);
            } else if (action === 'edit') {
                openEditCustomer(id);
            }
        });


        function openEditCustomer(id) {
            const c = allCustomers.find(x => String(x.id) === String(id));
            if (!c) return;

            // Edit-Tab freischalten
            const editTabBtn = document.getElementById('tab-edit');
            editTabBtn.classList.remove('disabled');
            editTabBtn.removeAttribute('aria-disabled');
            editTabBtn.removeAttribute('tabindex');

            // auf Edit-Tab wechseln
            new bootstrap.Tab(editTabBtn).show();

            // Felder befüllen
            document.getElementById('ecId').value = c.id;
            document.getElementById('ecNumber').value = c.customerNumber ?? '';
            document.getElementById('ecName').value = c.name ?? '';
            document.getElementById('ecAdresse').value = c.adresse ?? '';
            document.getElementById('ecSendToHeidi').value = (c.sendToHeidi ? 'true' : 'false');

            // Felder + Speichern-Button entsperren
            ['ecNumber','ecName','ecAdresse','ecSendToHeidi','btnUpdateCustomer'].forEach(id =>
                document.getElementById(id).disabled = false
            );

            // Status leeren
            document.getElementById('editStatus').textContent = '';
        }



        // ===== Kunden anlegen =====
        function formToJson(form) {
            const fd = new FormData(form);
            const obj = {};
            for (const [k, v] of fd.entries()) obj[k] = v;
            // bools
            obj.sendToHeidi = (obj.sendToHeidi === 'true');
            return obj;
        }

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }

        $('createCustomerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = formToJson(e.currentTarget);
            $('btnCreateCustomer').disabled = true;
            $('createStatus').textContent = 'Wird gespeichert…';

            try {
                const res = await fetch(CREATE_CUSTOMER_ENDPOINT, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(data)
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const created = await res.json();

                // In Liste übernehmen
                const newCustomer = {
                    id: created.id ?? created.customerId ?? created.customerID,
                    customerNumber: created.customerNumber ?? data.customerNumber,
                    name: created.name ?? created.customerName ?? data.name,
                    adresse: created.adresse ?? created.address ?? data.adresse,
                    sendToHeidi: created.sendToHeidi === true || data.sendToHeidi === true
                };
                allCustomers.unshift(newCustomer);
                renderCustomerRows(allCustomers);
                $('customerSearch').value = '';
                $('createStatus').textContent = 'Angelegt.';
                $('btnCreateCustomer').disabled = false;

                // Direkt auswählen & Modal schließen
                selectCustomerById(newCustomer.id);
            } catch (err) {
                $('createStatus').textContent = 'Fehler: ' + err.message;
                $('btnCreateCustomer').disabled = false;
            }
        });
    </script>


</main>
</body>
</html>