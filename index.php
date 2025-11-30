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
            <a href="newDevice.php" class="text-decoration-none text-dark">
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

        <!-- Regionen verwalten -->
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

        <!-- Logs -->
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

        <!-- Einstellungen -->
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

    </div>
</main>

<?php
include 'footer.php';
?>


