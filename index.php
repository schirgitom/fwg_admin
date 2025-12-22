<?php
include 'header.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    .tile {
        transition: transform .15s ease, box-shadow .15s ease;
        cursor: pointer;
    }
    .tile:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }
    .tile-icon {
        font-size: 2.5rem;
        opacity: .75;
    }
</style>

<main class="container py-5">

    <h1 class="mb-4 text-center">FWG Central Admin</h1>

    <div class="row g-4">

        <!-- Geräte -->
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="devices.php" class="text-decoration-none text-dark">
                <div class="card tile p-4 h-100">
                    <div class="tile-icon text-primary mb-2">
                        <i class="bi bi-thermometer-half"></i>
                    </div>
                    <h4>Geräteverwaltung</h4>
                    <p class="text-muted mb-0">Alle Geräte anzeigen, filtern und bearbeiten.</p>
                </div>
            </a>
        </div>

        <!-- Neues Gerät anlegen -->
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="createDevice.php" class="text-decoration-none text-dark">
                <div class="card tile p-4 h-100">
                    <div class="tile-icon text-success mb-2">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <h4>Gerät anlegen</h4>
                    <p class="text-muted mb-0">Ein neues HeatDevice erstellen.</p>
                </div>
            </a>
        </div>

        <!-- Kundenverwaltung -->
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="customers.php" class="text-decoration-none text-dark">
                <div class="card tile p-4 h-100">
                    <div class="tile-icon text-info mb-2">
                        <i class="bi bi-people"></i>
                    </div>
                    <h4>Kundenverwaltung</h4>
                    <p class="text-muted mb-0">Kunden anzeigen, anlegen und bearbeiten.</p>
                </div>
            </a>
        </div>

   <!--
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="regions.php" class="text-decoration-none text-dark">
                <div class="card tile p-4 h-100">
                    <div class="tile-icon text-warning mb-2">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <h4>Regionen</h4>
                    <p class="text-muted mb-0">Regionen & Datenbankverbindungen verwalten.</p>
                </div>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <a href="logs.php" class="text-decoration-none text-dark">
                <div class="card tile p-4 h-100">
                    <div class="tile-icon text-danger mb-2">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h4>Systemlogs</h4>
                    <p class="text-muted mb-0">Logs & Ereignisse einsehen.</p>
                </div>
            </a>
        </div>


        <div class="col-12 col-sm-6 col-lg-4">
            <a href="settings.php" class="text-decoration-none text-dark">
                <div class="card tile p-4 h-100">
                    <div class="tile-icon text-secondary mb-2">
                        <i class="bi bi-gear"></i>
                    </div>
                    <h4>Einstellungen</h4>
                    <p class="text-muted mb-0">System- & API-Konfiguration anpassen.</p>
                </div>
            </a>
        </div>
-->


        <hr class="my-5">

        <h3 class="mb-3 text-center">Service-Status</h3>

        <div class="row justify-content-center">
            <div class="col-lg-6">

                <ul class="list-group" id="healthList">
                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        data-url="http://10.150.20.104:7214/health">
                        Zentrale Datenhaltung
                        <span class="badge bg-secondary">prüfe …</span>
                    </li>

                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        data-url="http://10.150.20.107:7215/health">
                        Decoder
                        <span class="badge bg-secondary">prüfe …</span>
                    </li>

                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        data-url="http://10.150.20.108:7216/health">
                        Datenspeicherung
                        <span class="badge bg-secondary">prüfe …</span>
                    </li>

                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        data-url="http://10.150.20.109:7217/health">
                        Heidi Interface
                        <span class="badge bg-secondary">prüfe …</span>
                    </li>
                </ul>

                <p class="text-muted mt-2 text-center" style="font-size: 0.9rem">
                    Automatische Prüfung alle 30 Sekunden
                </p>

            </div>
        </div>
    </div>
</main>


<script>
    async function checkHealth() {
        const items = document.querySelectorAll('#healthList li');

        for (const item of items) {
            const url = item.dataset.url;
            const badge = item.querySelector('.badge');

            badge.className = 'badge bg-secondary';
            badge.textContent = 'prüfe …';

            try {
                const res = await fetch('healthcheck.php?url=' + encodeURIComponent(url));
                const data = await res.json();

                if (data.ok) {
                    badge.className = 'badge bg-success';
                    badge.innerHTML = '● OK';
                } else {
                    badge.className = 'badge bg-danger';
                    badge.innerHTML = '● Nicht erreichbar';
                }
            } catch (e) {
                badge.className = 'badge bg-danger';
                badge.innerHTML = '● Nicht erreichbar';
            }
        }
    }

    // sofort prüfen
    checkHealth();

    // alle 30 Sekunden erneut
    setInterval(checkHealth, 30000);
</script>


<?php
include 'footer.php';
?>


