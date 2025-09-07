<?php
require __DIR__ . '/vendor/autoload.php';
include 'header.php';

use App\Settings;

?>
<main>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0">Geräte</h1>
            <div>
                <button id="btnRefresh" class="btn btn-outline-primary btn-sm">Neu laden</button>
            </div>
        </div>

        <!-- Filterleiste -->
        <div class="row g-3 align-items-end controls mb-3">
            <div class="col-12 col-md-4">
                <label for="globalSearch" class="form-label">Suchen</label>
                <input id="globalSearch" type="search" class="form-control" placeholder="z. B. DeviceID, Name, Kunde, Block, Line, DB…" />
            </div>
            <div class="col-6 col-md-3">
                <label for="filterCustomer" class="form-label">Kunde</label>
                <select id="filterCustomer" class="form-select">
                    <option value="">Alle</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label for="filterBlock" class="form-label">Block</label>
                <select id="filterBlock" class="form-select">
                    <option value="">Alle</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="filterLine" class="form-label">Line</label>
                <select id="filterLine" class="form-select">
                    <option value="">Alle</option>
                </select>
            </div>
        </div>

        <!-- Tabelle -->
        <div class="card">
            <div class="card-body">
                <div id="loading" class="d-flex align-items-center justify-content-center spinner-wrap">
                    <div class="text-center">
                        <div class="spinner-border" role="status" aria-hidden="true"></div>
                        <div class="mt-2 text-muted">Lade Daten…</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="devicesTable" class="table table-striped table-hover align-middle w-100 d-none">
                        <thead>
                        <tr>
                            <th>DeviceID</th>
                            <th>Kunde</th>
                            <th>Kunde Nr.</th>
                            <th>Block</th>
                            <th>Line</th>
                            <th>DB</th>
                            <th>Heidi</th>
                            <th style="width:1%"></th>
                        </tr>
                        </thead>
                        <tbody><!-- JS --></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
            <div id="toast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body" id="toastMsg">Fehler</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap JS + Popper -->
<!-- JS -->
<script src="/node_modules/jquery/dist/jquery.min.js"></script>
<script src="/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="/node_modules/datatables.net/js/dataTables.js"></script>
<script src="/node_modules/datatables.net-bs5/js/dataTables.bootstrap5.js"></script>



<script>
    // ====== Konfiguration ======
    const ENDPOINT = 'GetHeatDevices.php'; // liefert Array von Devices

    // ====== Utils ======
    const el = (id) => document.getElementById(id);
    const showToast = (msg, type='danger') => {
        const t = el('toast');
        t.classList.remove('text-bg-danger','text-bg-success','text-bg-info');
        t.classList.add('text-bg-' + type);
        el('toastMsg').textContent = msg;
        new bootstrap.Toast(t).show();
    };
    const setLoading = (on) => {
        el('loading').classList.toggle('d-none', !on);
        el('devicesTable').classList.toggle('d-none', on);
    };

    // Flacht den API-Datensatz für die Tabelle ab
    function flatten(dev) {
        const customerName = dev.customer?.customerName ?? dev.customer?.name ?? '';
        const customerNo= dev.customer?.customerNumber ?? dev.customer?.customerNumber ?? '';
        const blockName    = dev.block?.blockName ?? dev.block?.name ?? '';
        const line         = dev.block?.line ?? dev.line ?? null;
        const lineName     = line?.lineName ?? line?.name ?? '';

        // SendToHeidi kommt laut Beispiel aus dev.customer.sendToHeidi (boolean)
        const sendToHeidi  = dev.customer?.sendToHeidi === true || dev.customer?.sendToHeidi === 1 || dev.customer?.sendToHeidi === 'true';

        return {
            deviceID: dev.deviceID ?? dev.deviceId ?? dev.id ?? '',
            deviceName: dev.deviceName ?? '',
            id: dev.id,
            customerName,
            customerNo,
            blockName,
            lineName,
            databaseName: dev.databaseName ?? '',
            sendToHeidi: sendToHeidi ? 'true' : 'false',
            _raw: dev
        };
    }

    function populateFilters(rows) {
        const uniqueSorted = key => Array.from(new Set(rows.map(r => r[key]).filter(Boolean))).sort((a,b)=>a.localeCompare(b));

        const customers = uniqueSorted('customerName');
        const blocks    = uniqueSorted('blockName');
        const lines     = uniqueSorted('lineName');

        const customerSel = el('filterCustomer');
        const blockSel    = el('filterBlock');
        const lineSel     = el('filterLine');

        customerSel.length = 1;
        blockSel.length    = 1;
        lineSel.length     = 1;

        customers.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v; opt.textContent = v;
            customerSel.appendChild(opt);
        });
        blocks.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v; opt.textContent = v;
            blockSel.appendChild(opt);
        });
        lines.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v; opt.textContent = v;
            lineSel.appendChild(opt);
        });
    }

    // ====== DataTable ======
    let dt = null;

    async function loadData() {
        setLoading(true);
        try {
            const res = await fetch(ENDPOINT, { headers: { 'Accept': 'application/json' }});
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!Array.isArray(json)) throw new Error('Unerwartetes Format (kein Array).');

            const rows = json.map(flatten);
            populateFilters(rows);
            buildTable(rows);

            // Fallback falls Event nicht feuert
            setTimeout(() => setLoading(false), 0);
        } catch (e) {
            setLoading(false);
            showToast('Fehler beim Laden: ' + e.message);
        }
    }

    function buildTable(rows) {
        if (dt) { dt.clear().rows.add(rows).draw(); return; }

        dt = new DataTable('#devicesTable', {
            data: rows,
            responsive: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'asc']],
            columns: [
                { data: 'deviceID', title: 'DeviceID' },
                { data: 'customerNo', title: 'Kunde Nr.' },
                { data: 'customerName', title: 'Kunde' },
                { data: 'blockName',   title: 'Block' },
                { data: 'lineName',    title: 'Line' },
                { data: 'databaseName', title: 'DB' },
                { data: 'sendToHeidi', title: 'SendToHeidi', render: d => d === 'true'
                        ? '<span class="badge text-bg-success">Ja</span>'
                        : '<span class="badge text-bg-secondary">Nein</span>' },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-end',
                    render: (_d, _t, row) =>
                        `<a href="editDevice.php?id=${encodeURIComponent(row.id)}"
                class="btn btn-sm btn-outline-secondary">Bearbeiten</a>`
                }
            ],
            language: { url: 'https://cdn.datatables.net/plug-ins/2.0.7/i18n/de-DE.json' }
        });

        // Globale Suche
        el('globalSearch').addEventListener('input', e => {
            dt.search(e.target.value).draw();
        });

        // Dropdown-Filter
        const applyFilters = () => {
            const c = el('filterCustomer').value;
            const b = el('filterBlock').value;
            const l = el('filterLine').value;

            dt.columns().search('');
            if (c) dt.column(2).search('^' + escapeRegex(c) + '$', true, false);
            if (b) dt.column(3).search('^' + escapeRegex(b) + '$', true, false);
            if (l) dt.column(4).search('^' + escapeRegex(l) + '$', true, false);
            dt.draw();
        };
        el('filterCustomer').addEventListener('change', applyFilters);
        el('filterBlock').addEventListener('change', applyFilters);
        el('filterLine').addEventListener('change', applyFilters);

        // Refresh
        el('btnRefresh').addEventListener('click', () => loadData());

        document.querySelector('#devicesTable').addEventListener('init.dt', () => setLoading(false), { once: true });
        document.querySelector('#devicesTable').addEventListener('draw.dt', () => setLoading(false), { once: true });
    }

    function escapeRegex(text) {
        return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Init
    loadData();
</script>

</body>
</html>