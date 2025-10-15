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
$successMsg = null;

// POST: speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $payload = [
    'deviceID'    => $_POST['deviceID'],
    'active'      => isset($_POST['active']) ? ($_POST['active'] === 'true' || $_POST['active'] === '1') : false,
    'deviceVendor'=> $_POST['deviceVendor']?? '',
    'fK_Block'    => isset($_POST['fK_Block']) ? (int)$_POST['fK_Block'] : null,
      'FK_Customer' => $_POST['fK_Customer']
  ];

  try {
    // PUT /api/HeatDevice/{id}
    $resp = $guzzle->request('PATCH', "api/HeatDevice/{$deviceId}", [
      'headers' => [ 'Accept' => 'application/json', 'Content-Type' => 'application/json' ],
      'json'    => $payload,
      'timeout' => 10,
    ]);
    // Erfolg → zurück zur Liste
    //header('Location: index.php?updated=1');
      $successMsg = "Erfolgreich aktualisiert";
   // exit;
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

/*
var_dump($device);
    echo "------------";
    var_dump($customer);*/
include 'header.php';

include 'customerDialog.php'

?>
<!-- Kunden-Modal -->


<main>
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">Gerät bearbeiten - <?php echo $device['device_id'] ?> - (<?php echo $customerName ?>)</h1>
    <div>
      <a href="index.php" class="btn btn-outline-secondary btn-sm">Zur Übersicht</a>
    </div>
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
        <div class="col-md-2">
          <label class="form-label">ID</label>
          <input name="ID" value="<?= htmlspecialchars($device['id'] ?? $deviceId) ?>" class="form-control" readonly>
        </div>

        <div class="col-md-6">
          <label class="form-label">Device EUI</label>
          <input name="deviceID" value="<?= htmlspecialchars($device['device_id'] ?? '') ?>" class="form-control" required>
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
                     value="<?= htmlspecialchars($device['fK_Customer'] ?? $device['f_k_customer'] ?? '') ?>">
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
      <a href="index.php" class="btn btn-outline-secondary">Abbrechen</a>
        <button type="button" id="btnMigrate" class="btn btn-outline-warning ms-auto">
            Visualisierung aktualisieren
        </button>
    </div>
  </form>
</div>

    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
        <div id="actionToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div id="toastBody" class="toast-body text-white">...</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Schließen"></button>
            </div>
        </div>
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

  function showToast(message, type = 'success') {
      const toastEl = document.getElementById('actionToast');
      const toastBody = document.getElementById('toastBody');

      // Farbe je nach Typ setzen
      let bgClass = 'bg-success';
      if (type === 'error') bgClass = 'bg-danger';
      if (type === 'warning') bgClass = 'bg-warning text-dark';
      if (type === 'info') bgClass = 'bg-info text-dark';

      toastBody.className = `toast-body text-white ${bgClass.includes('text-dark') ? 'text-dark' : 'text-white'}`;
      toastEl.className = `toast align-items-center border-0 ${bgClass}`;

      toastBody.textContent = message;

      const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
      toast.show();
  }

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

  document.getElementById('btnMigrate').addEventListener('click', async () => {
      const deviceEUI = document.getElementById('editDeviceId')?.value
          || document.querySelector('input[name="deviceID"]')?.value;

      if (!deviceEUI) {
          alert('DeviceEUI nicht gefunden.');
          return;
      }

      try {
          const res = await fetch(`MigrateHeatDevice.php?id=${encodeURIComponent(deviceEUI)}`);

          if (!res.ok) throw new Error(`HTTP ${res.status}`);
          const data = await res.json().catch(() => ({}));

          showToast('Migration erfolgreich ✅', 'success');
          console.log('Migrate Response:', data);

      } catch (err) {
          showToast('Fehler bei Migration: ' + err.message, 'error');
      }
  });

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






    </script>


</main>
</body>
</html>