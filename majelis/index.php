<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';
?>

<div class="row">
    <!-- Map Section -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-map me-2 text-primary"></i>Peta Interaktif
                    </h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshMap()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleFullscreen('map')">
                            <i class="fas fa-expand me-1"></i>Fullscreen
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Map Container -->
                <div id="map" style="height: 500px; width: 100%;"></div>

                <!-- Map Legend -->
                <div class="legend bg-white p-3 rounded shadow-sm" style="position: absolute; top: 10px; right: 10px; z-index: 1000;">
                    <h6 class="fw-bold mb-2">Legenda</h6>
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-mosque text-primary me-2"></i>
                        <span>Majelis Dzikir</span>
                    </div>
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-user-tie text-danger me-2"></i>
                        <span>Petugas Pentawajuh</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-map-marked-alt text-success me-2"></i>
                        <span>Wilayah</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Control Panel -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0">
                    <i class="fas fa-filter me-2 text-primary"></i>Filter Data
                </h6>
            </div>
            <div class="card-body">
                <form id="filterForm">
                    <div class="mb-3">
                        <label for="wilayahFilter" class="form-label">Wilayah</label>
                        <select class="form-select" id="wilayahFilter" name="wilayah_id">
                            <option value="">Semua Wilayah</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="tanggalFilter" class="form-label">Tanggal</label>
                        <input type="date" class="form-control" id="tanggalFilter" name="tanggal"
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Terapkan Filter
                    </button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar me-2 text-primary"></i>Statistik
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="stat-item">
                            <h3 class="text-primary mb-0" id="totalMajelis">0</h3>
                            <small class="text-muted">Majelis</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-item">
                            <h3 class="text-danger mb-0" id="totalPetugas">0</h3>
                            <small class="text-muted">Petugas</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-item">
                            <h3 class="text-success mb-0" id="totalPenugasan">0</h3>
                            <small class="text-muted">Penugasan</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0">
                    <i class="fas fa-tools me-2 text-primary"></i>Menu Cepat
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="openPenugasanModal()">
                        <i class="fas fa-user-clock me-2"></i>Buat Penugasan Manual
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="generateAutoAssignments()">
                        <i class="fas fa-magic me-2"></i>Generate Penugasan Otomatis
                    </button>
                    <button type="button" class="btn btn-outline-info" href="laporan.php">
                        <i class="fas fa-file-excel me-2"></i>Export Laporan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assignments Table -->
<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-table me-2 text-primary"></i>Penugasan Hari Ini
            </h5>
            <div>
                <button type="button" class="btn btn-outline-primary btn-sm me-2" onclick="refreshAssignments()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="exportCurrentAssignments()">
                    <i class="fas fa-download me-1"></i>Export Excel
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="assignmentsTable">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Petugas</th>
                        <th>Majelis Dzikir</th>
                        <th>Wilayah</th>
                        <th>Jarak (km)</th>
                        <th>Tipe</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="assignmentsTableBody">
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin me-2"></i>Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modals will be included here -->
<?php include 'modals/penugasan_modal.php'; ?>

<?php require_once 'includes/footer.php'; ?>