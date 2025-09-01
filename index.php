<?php
require __DIR__ . '/vendor/autoload.php';
include 'header.php';


?>
<div class="container">
    <h1 class="text-center">Hallo mit Bodddotstrap!</h1>
    <button class="btn btn-primary">Klick mich</button>
</div>

<?php
$consul = new ConsulKV("127.0.0.1:8500");
$value = $consul->get("test/key1");
echo "Wert: " . $value . PHP_EOL;
?>

<!-- Bootstrap JS + Popper -->
<script src="node_modules/bootstrap/dist/js/bootstrap.js"></script>
</body>
</html>