<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Settings;

$errorMsg = null;

// ================== CONFIG ==================
$appConfig = require __DIR__ . '/config/config.php';
Settings::init($appConfig);
$apiBase = Settings::get("CentralAPI");

$config = (new \FWGCentralAPI\Configuration())->setHost($apiBase);
$http = new \GuzzleHttp\Client([
    'base_uri' => rtrim($apiBase, '/') . '/',
    'timeout'  => 10,
]);

$customerApi = new \FWGCentralAPI\Api\CustomerApi($http, $config);

// ================== POST → CREATE ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payload = [
        'customerNumber' => $_POST['customerNumber'],
        'name'           => $_POST['name'],
        'adresse'        => $_POST['adresse'] ?: '',
        'sendToHeidi'    => !empty($_POST['sendToHeidi']),
    ];

    try {
        $customer = $customerApi->apiCustomerPost($payload);

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

        // Erfolg
        $id = $customer['data']['id'];

        header("Location: editCustomer.php?id={$id}&created=1");
        exit;

    } catch (Throwable $e) {
        // ⛔ KEIN Redirect
        $errorMsg = $e->getMessage();
    }
}



include 'header.php';
?>

<main class="container py-4">

    <h1 class="h3 mb-4">Neuen Kunden anlegen</h1>



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


    <form method="post" class="card">
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">Kundennummer *</label>
                    <input name="customerNumber" class="form-control" value="<?= htmlspecialchars($_POST['customerNumber'] ?? '') ?>" required>
                </div>

                <div class="col-md-9">
                    <label class="form-label">Name *</label>
                    <input name="name" class="form-control"    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>

                <div class="col-md-11">
                    <label class="form-label">Zusatzinformation</label>
                    <input name="adresse" class="form-control" value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
                </div>

                <div class="col-md-1">
                    <label class="form-label">Heidi</label>
                    <select name="sendToHeidi" class="form-select">
                        <option value="0">Nein</option>
                        <option value="1">Ja</option>
                    </select>
                </div>

            </div>
        </div>

        <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary">Anlegen</button>
            <a href="customers.php" class="btn btn-outline-secondary">Abbrechen</a>
        </div>
    </form>

</main>

<?php include 'footer.php'; ?>
