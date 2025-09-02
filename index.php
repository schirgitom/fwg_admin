<?php
require __DIR__ . '/vendor/autoload.php';
include 'header.php';

use App\Settings;

?>
<div class="container">
    <h1 class="text-center">Hallo mit Bodddotstrap!</h1>
    <button class="btn btn-primary">Klick mich</button>
</div>

<?php
$appConfig = require __DIR__ . '/config/config.php';

// 2) Settings initialisieren
Settings::init($appConfig);

// 3) Werte holen (JSON wird im Hintergrund aus Consul geholt/parsed/gecached)
$dbHost   = Settings::all();

var_dump($dbHost);

//echo "Wert: " . $dbHost . PHP_EOL;
?>

<!-- Bootstrap JS + Popper -->
<script src="node_modules/bootstrap/dist/js/bootstrap.js"></script>
</body>
</html>