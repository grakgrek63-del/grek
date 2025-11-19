  </main>

    <!-- Footer -->
    <footer class="bg-light text-center py-3 mt-5 border-top">
        <div class="container">
            <small class="text-muted">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Version <?php echo APP_VERSION; ?>
                <br>
                <i class="fas fa-code me-1"></i>Dikembangkan untuk pengelolaan majelis dzikir
            </small>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet Draw JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <!-- Custom JS -->
    <script src="js/map.js"></script>
    <script src="js/crud.js"></script>
    <script src="js/assignments.js"></script>
    <script src="js/assignments-engine.js"></script>
    <script src="js/missing_functions.js"></script>

    <script>
        // Global variables
        const APP_URL = '<?php echo APP_URL; ?>';
        const USER_ROLE = '<?php echo isset($user['role']) ? $user['role'] : ''; ?>';
        const USER_WILAYAH = <?php echo isset($user['wilayah_id']) ? $user['wilayah_id'] : 'null'; ?>;
        const DEFAULT_LAT = <?php echo DEFAULT_LAT; ?>;
        const DEFAULT_LNG = <?php echo DEFAULT_LNG; ?>;
        const DEFAULT_ZOOM = <?php echo DEFAULT_ZOOM; ?>;

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });

        // Loading indicator
        function showLoading() {
            if ($('#loadingModal').length === 0) {
                $('body').append(`
                    <div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                        <div class="modal-dialog modal-sm modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-body text-center py-4">
                                    <div class="spinner-border text-primary mb-3" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mb-0">Memuat data...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }
            var modal = new bootstrap.Modal(document.getElementById('loadingModal'));
            modal.show();
        }

        function hideLoading() {
            var modal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
            if (modal) {
                modal.hide();
            }
        }

        // Global AJAX error handler
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            hideLoading();

            let message = 'Terjadi kesalahan. Silakan coba lagi.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            alert('Error: ' + message);
        });

        // Open modals (will be defined in respective pages)
        function openWilayahModal(id = null) {
            console.log('openWilayahModal called with id:', id);
        }

        function openMajelisModal(id = null) {
            console.log('openMajelisModal called with id:', id);
        }

        function openPetugasModal(id = null) {
            console.log('openPetugasModal called with id:', id);
        }

        function openUserModal() {
            console.log('openUserModal called');
        }

        function openChangePasswordModal() {
            console.log('openChangePasswordModal called');
        }
    </script>
</body>
</html>