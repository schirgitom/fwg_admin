</main> <!-- Hauptinhalt Ende -->

<!-- ===== Footer ===== -->
<footer class="mt-auto py-3 border-top text-center text-muted small">
    © 2025 FWG Admin Portal – Alle Rechte vorbehalten.
    <div>
        <a href="index.php" class="text-decoration-none me-3">Startseite</a>
        <a href="settings.php" class="text-decoration-none me-3">Einstellungen</a>
        <a href="https://www.weiz.at" target="_blank" class="text-decoration-none">FWG Website</a>
    </div>
</footer>


<!-- ===== Confirm Modal (global) ===== -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalTitle">Bestätigung</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="confirmModalBody">
                Sind Sie sicher?
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">
                    Abbrechen
                </button>
                <button type="button" class="btn btn-danger"
                        id="confirmModalOk">
                    Bestätigen
                </button>
            </div>

        </div>
    </div>
</div>


<!-- ===== Global Toast Container ===== -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
    <div id="appToast" class="toast align-items-center border-0" role="alert"
         aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div id="appToastBody" class="toast-body">
                Nachricht
            </div>
            <button type="button"
                    class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"
                    aria-label="Schließen">
            </button>
        </div>
    </div>
</div>



<!-- Bootstrap JS -->

<!-- Bootstrap JS + Popper -->
<!-- JS -->
<script src="/node_modules/jquery/dist/jquery.min.js"></script>
<script src="/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="/node_modules/datatables.net/js/dataTables.js"></script>
<script src="/node_modules/datatables.net-bs5/js/dataTables.bootstrap5.js"></script>


<script>

        /**
        * Öffnet einen generischen Confirm-Dialog
        * @returns {Promise<boolean>}
        */
        function confirmModal({
        title = "Bestätigung",
        message = "Sind Sie sicher?",
        okText = "Bestätigen",
        okClass = "btn-danger"
    } = {}) {

        return new Promise((resolve) => {

        const modalEl = document.getElementById("confirmModal");
        const modal = new bootstrap.Modal(modalEl);

        // Texte setzen
        document.getElementById("confirmModalTitle").textContent = title;
        document.getElementById("confirmModalBody").innerHTML = message;

        const okBtn = document.getElementById("confirmModalOk");
        okBtn.textContent = okText;

        // Button-Klassen resetten
        okBtn.className = "btn " + okClass;

        const cleanup = (result) => {
        okBtn.onclick = null;
        modalEl.removeEventListener('hidden.bs.modal', onHidden);
        resolve(result);
    };

        okBtn.onclick = () => {
        modal.hide();
        cleanup(true);
    };

        const onHidden = () => cleanup(false);
        modalEl.addEventListener('hidden.bs.modal', onHidden, { once: true });

        modal.show();
    });
    }



            /**
            * Zeigt einen globalen Toast
            */
            function showToast(message, type = "success", delay = 4000) {

            const toastEl   = document.getElementById("appToast");
            const toastBody = document.getElementById("appToastBody");

            // Bootstrap Farben
            let bgClass = "bg-success text-white";
            if (type === "error")   bgClass = "bg-danger text-white";
            if (type === "warning") bgClass = "bg-warning text-dark";
            if (type === "info")    bgClass = "bg-info text-dark";

            // Reset Klassen
            toastEl.className = `toast align-items-center border-0 ${bgClass}`;
            toastBody.className = "toast-body";

            toastBody.innerHTML = message;

            const toast = new bootstrap.Toast(toastEl, { delay });
            toast.show();
        }
</script>

</body>
</html>
