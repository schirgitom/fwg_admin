<?php
include 'header.php';
?>

<main>
    <div class="container py-4">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0">Kunden</h1>
            <button id="btnReload" class="btn btn-outline-primary btn-sm">Neu laden</button>
        </div>

        <!-- Filter -->
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-6">
                <label class="form-label">Suchen</label>
                <input id="searchBox" type="search" class="form-control"
                       placeholder="z. B. Name, Kundennummer, Adresse …">
            </div>
        </div>

        <!-- Tabelle -->
        <div class="card">
            <div class="card-body">

                <div id="loading" class="d-flex align-items-center justify-content-center" style="min-height:120px;">
                    <div class="text-center">
                        <div class="spinner-border"></div>
                        <div class="mt-2 text-muted">Lade Kunden…</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="customerTable"
                           class="table table-hover align-middle w-100 d-none">
                        <thead class="table-light">
                        <tr>
                            <th style="width:10rem;">Kundennummer</th>
                            <th>Name</th>
                            <th>Adresse</th>
                            <th>Heidi</th>
                            <th style="width:1%;"></th>
                        </tr>
                        </thead>
                        <tbody><!-- JS --></tbody>
                    </table>
                </div>

            </div>
        </div>

        <!-- Toast -->
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080;">
            <div id="toast" class="toast text-bg-danger border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body" id="toastMsg">Fehler</div>
                    <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>

    </div>
</main>

<?php include 'footer.php'; ?>

<script>
    const ENDPOINT = "getCustomers.php";

    const el = id => document.getElementById(id);
    const showToast = msg => {
        el("toastMsg").textContent = msg;
        new bootstrap.Toast(el("toast")).show();
    };

    function setLoading(on) {
        el("loading").classList.toggle("d-none", !on);
        el("customerTable").classList.toggle("d-none", on);
    }

    let table = null;

    function flatten(customer) {
        return {
            id: customer.id,
            number: customer.customerNumber ?? "",
            name: customer.name ?? "",
            adresse: customer.adresse ?? "",
            heidi: customer.sendToHeidi ? "Ja" : "Nein"
        };
    }

    async function loadData() {
        setLoading(true);
        try {
            const res = await fetch(ENDPOINT, {headers: {"Accept": "application/json"}});
            if (!res.ok) throw new Error("HTTP " + res.status);

            const json = await res.json();
            if (!Array.isArray(json)) throw new Error("Ungültiges Format");

            const rows = json.map(flatten);
            buildTable(rows);
            setLoading(false);

        } catch (err) {
            setLoading(false);
            showToast("Fehler beim Laden: " + err.message);
        }
    }

    function buildTable(rows) {
        const tbody = document.querySelector("#customerTable tbody");
        tbody.innerHTML = "";

        for (const c of rows) {
            const tr = document.createElement("tr");
            tr.innerHTML = `
            <td class="text-nowrap">${c.number}</td>
            <td>${c.name}</td>
            <td>${c.adresse}</td>
            <td>${c.heidi}</td>
            <td class="text-end">
                <a href="editCustomer.php?id=${c.id}" class="btn btn-sm btn-outline-primary">
                    Bearbeiten
                </a>
            </td>
        `;
            tbody.appendChild(tr);
        }

        // Live-Suche
        el("searchBox").addEventListener("input", () => applySearch(rows));

        applySearch(rows);
    }

    function applySearch(rows) {
        const q = el("searchBox").value.toLowerCase();
        const tbody = document.querySelector("#customerTable tbody");

        tbody.querySelectorAll("tr").forEach(tr => tr.remove());

        rows
            .filter(r =>
                r.number.toLowerCase().includes(q) ||
                r.name.toLowerCase().includes(q) ||
                r.adresse.toLowerCase().includes(q)
            )
            .forEach(c => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                <td class="text-nowrap">${c.number}</td>
                <td>${c.name}</td>
                <td>${c.adresse}</td>
                <td>${c.heidi}</td>
                <td class="text-end">
                    <a href="editCustomer.php?id=${c.id}"
                       class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                </td>
            `;
                tbody.appendChild(tr);
            });
    }

    el("btnReload").addEventListener("click", loadData);

    // Initial
    loadData();
</script>
