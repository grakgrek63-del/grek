<!-- Penugasan Modal -->
<div class="modal fade" id="penugasanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="penugasanModalLabel">
                    <i class="fas fa-user-clock me-2"></i>
                    <span id="penugasanTitle">Tambah Penugasan</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="penugasanForm">
                <div class="modal-body">
                    <input type="hidden" id="penugasanId" name="id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tanggalPenugasan" class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggalPenugasan" name="tanggal" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tipePenugasan" class="form-label">Tipe Penugasan</label>
                            <select class="form-select" id="tipePenugasan" name="tugas">
                                <option value="manual">Manual</option>
                                <option value="otomatis">Otomatis</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="wilayahPenugasan" class="form-label">Wilayah <span class="text-danger">*</span></label>
                        <select class="form-select" id="wilayahPenugasan" name="wilayah_id" required>
                            <option value="">Pilih Wilayah</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="petugasPenugasan" class="form-label">Petugas Pentawajuh <span class="text-danger">*</span></label>
                            <select class="form-select" id="petugasPenugasan" name="id_petugas" required>
                                <option value="">Pilih Petugas</option>
                            </select>
                            <small class="form-text text-muted">Hanya petugas yang tersedia pada tanggal terpilih</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="majelisPenugasan" class="form-label">Majelis Dzikir <span class="text-danger">*</span></label>
                            <select class="form-select" id="majelisPenugasan" name="id_majelis" required>
                                <option value="">Pilih Majelis</option>
                            </select>
                        </div>
                    </div>

                    <!-- Distance Display -->
                    <div class="alert alert-info d-none" id="distanceInfo">
                        <i class="fas fa-route me-2"></i>
                        <strong>Jarak:</strong> <span id="distanceValue">-</span> km
                    </div>

                    <!-- Petugas Info -->
                    <div class="card bg-light d-none" id="petugasInfo">
                        <div class="card-body py-2">
                            <h6 class="mb-2"><i class="fas fa-user-tie me-2"></i>Informasi Petugas</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <small><strong>Nama:</strong> <span id="petugasNama">-</span></small>
                                </div>
                                <div class="col-md-6">
                                    <small><strong>Telepon:</strong> <span id="petugasTelepon">-</span></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Majelis Info -->
                    <div class="card bg-light d-none" id="majelisInfo">
                        <div class="card-body py-2">
                            <h6 class="mb-2"><i class="fas fa-mosque me-2"></i>Informasi Majelis</h6>
                            <div class="row">
                                <div class="col-md-12">
                                    <small><strong>Alamat:</strong> <span id="majelisAlamat">-</span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary" id="savePenugasan">
                        <i class="fas fa-save me-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePenugasanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus penugasan ini?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    Tindakan ini tidak dapat dibatalkan.
                </div>
                <div id="deletePenugasanInfo"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeletePenugasan">
                    <i class="fas fa-trash me-2"></i>Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Auto Assignment Modal -->
<div class="modal fade" id="autoAssignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-magic me-2"></i>Generate Penugasan Otomatis
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="autoAssignmentForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="autoWilayah" class="form-label">Wilayah <span class="text-danger">*</span></label>
                        <select class="form-select" id="autoWilayah" name="wilayah_id" required>
                            <option value="">Pilih Wilayah</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="startDate" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="startDate" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="endDate" class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="endDate" name="end_date" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="assignmentType" class="form-label">Jenis Penugasan</label>
                        <select class="form-select" id="assignmentType" name="assignment_type">
                            <option value="daily">Setiap Hari</option>
                            <option value="weekly">Hari Tertentu (Mingguan)</option>
                            <option value="custom">Kustom</option>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="weeklyDaysContainer">
                        <label class="form-label">Pilih Hari</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="day1" name="days[]">
                                    <label class="form-check-label" for="day1">Senin</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="2" id="day2" name="days[]">
                                    <label class="form-check-label" for="day2">Selasa</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="3" id="day3" name="days[]">
                                    <label class="form-check-label" for="day3">Rabu</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="4" id="day4" name="days[]">
                                    <label class="form-check-label" for="day4">Kamis</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="5" id="day5" name="days[]">
                                    <label class="form-check-label" for="day5">Jumat</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="6" id="day6" name="days[]">
                                    <label class="form-check-label" for="day6">Sabtu</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="0" id="day0" name="days[]">
                                    <label class="form-check-label" for="day0">Ahad</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Sistem akan otomatis menugaskan petugas ke majelis terdekat dalam wilayah yang sama dengan memperhatikan constraint bahwa satu petugas hanya bisa ditugaskan ke satu majelis per hari.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-magic me-2"></i>Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentEditId = null;
let penugasanData = {};

function openPenugasanModal(id = null) {
    currentEditId = id;
    const modal = new bootstrap.Modal(document.getElementById('penugasanModal'));

    if (id) {
        // Edit mode
        $('#penugasanModalLabel').text('Edit Penugasan');
        loadPenugasanData(id);
    } else {
        // Add mode
        $('#penugasanModalLabel').text('Tambah Penugasan');
        resetPenugasanForm();
        $('#tanggalPenugasan').val($('#tanggalFilter').val() || new Date().toISOString().split('T')[0]);
    }

    modal.show();
}

function loadPenugasanData(id) {
    showLoading();

    $.ajax({
        url: 'api/penugasan.php?id=' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                penugasanData = data;

                $('#penugasanId').val(data.id);
                $('#tanggalPenugasan').val(data.tanggal);
                $('#tipePenugasan').val(data.tugas);

                // Load wilayah options and set selected
                loadWilayahOptions(function() {
                    $('#wilayahPenugasan').val(data.wilayah_id).trigger('change');

                    // Load petugas and majelis options
                    setTimeout(() => {
                        loadPetugasOptions(data.wilayah_id, data.tanggal);
                        loadMajelisOptions(data.wilayah_id);

                        setTimeout(() => {
                            $('#petugasPenugasan').val(data.id_petugas).trigger('change');
                            $('#majelisPenugasan').val(data.id_majelis).trigger('change');
                        }, 500);
                    }, 500);
                });
            }
            hideLoading();
        }
    });
}

function resetPenugasanForm() {
    $('#penugasanForm')[0].reset();
    $('#penugasanId').val('');
    $('#distanceInfo').addClass('d-none');
    $('#petugasInfo').addClass('d-none');
    $('#majelisInfo').addClass('d-none');
    penugasanData = {};
}

function loadWilayahOptions(callback = null) {
    $.ajax({
        url: 'api/wilayah.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#wilayahPenugasan');
                select.find('option:not(:first)').remove();

                response.data.forEach(function(wilayah) {
                    select.append(`<option value="${wilayah.id}">${wilayah.nama_wilayah}</option>`);
                });

                if (callback) callback();
            }
        }
    });
}

function loadPetugasOptions(wilayahId, tanggal = null) {
    const date = tanggal || $('#tanggalPenugasan').val();

    if (!date) {
        $('#petugasPenugasan').html('<option value="">Pilih tanggal terlebih dahulu</option>');
        return;
    }

    $.ajax({
        url: 'api/penugasan.php?action=available&tanggal=' + date + '&wilayah_id=' + wilayahId,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#petugasPenugasan');
                select.find('option:not(:first)').remove();

                response.data.forEach(function(petugas) {
                    select.append(`<option value="${petugas.id}">${petugas.nama}</option>`);
                });
            }
        }
    });
}

function loadMajelisOptions(wilayahId) {
    $.ajax({
        url: 'api/majelis.php?wilayah_id=' + wilayahId,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#majelisPenugasan');
                select.find('option:not(:first)').remove();

                response.data.forEach(function(majelis) {
                    select.append(`<option value="${majelis.id}">${majelis.nama_majelis}</option>`);
                });
            }
        }
    });
}

// Event handlers
$('#wilayahPenugasan').on('change', function() {
    const wilayahId = $(this).val();
    const tanggal = $('#tanggalPenugasan').val();

    if (wilayahId) {
        loadPetugasOptions(wilayahId, tanggal);
        loadMajelisOptions(wilayahId);
    } else {
        $('#petugasPenugasan').html('<option value="">Pilih wilayah terlebih dahulu</option>');
        $('#majelisPenugasan').html('<option value="">Pilih wilayah terlebih dahulu</option>');
    }
});

$('#tanggalPenugasan').on('change', function() {
    const wilayahId = $('#wilayahPenugasan').val();
    if (wilayahId) {
        loadPetugasOptions(wilayahId, $(this).val());
    }
});

$('#petugasPenugasan').on('change', function() {
    const petugasId = $(this).val();
    if (petugasId) {
        // Load petugas details
        $.ajax({
            url: 'api/petugas.php?id=' + petugasId,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#petugasNama').text(data.nama);
                    $('#petugasTelepon').text(data.telepon || '-');
                    $('#petugasInfo').removeClass('d-none');

                    // Calculate distance if majelis is selected
                    calculateDistance();
                }
            }
        });
    } else {
        $('#petugasInfo').addClass('d-none');
        $('#distanceInfo').addClass('d-none');
    }
});

$('#majelisPenugasan').on('change', function() {
    const majelisId = $(this).val();
    if (majelisId) {
        // Load majelis details
        $.ajax({
            url: 'api/majelis.php?id=' + majelisId,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#majelisAlamat').text(data.alamat || '-');
                    $('#majelisInfo').removeClass('d-none');

                    // Calculate distance if petugas is selected
                    calculateDistance();
                }
            }
        });
    } else {
        $('#majelisInfo').addClass('d-none');
        $('#distanceInfo').addClass('d-none');
    }
});

function calculateDistance() {
    const petugasId = $('#petugasPenugasan').val();
    const majelisId = $('#majelisPenugasan').val();

    if (petugasId && majelisId) {
        // Get coordinates and calculate distance
        $.when(
            $.getJSON('api/petugas.php?id=' + petugasId),
            $.getJSON('api/majelis.php?id=' + majelisId)
        ).then(function(petugasResponse, majelisResponse) {
            if (petugasResponse[0].success && majelisResponse[0].success) {
                const petugas = petugasResponse[0].data;
                const majelis = majelisResponse[0].data;

                const distance = calculate_distance(
                    petugas.latitude, petugas.longitude,
                    majelis.latitude, majelis.longitude
                );

                $('#distanceValue').text(distance.toFixed(2));
                $('#distanceInfo').removeClass('d-none');
            }
        });
    }
}

// Form submission
$('#penugasanForm').on('submit', function(e) {
    e.preventDefault();

    const formData = {
        id_petugas: $('#petugasPenugasan').val(),
        id_majelis: $('#majelisPenugasan').val(),
        tanggal: $('#tanggalPenugasan').val(),
        tugas: $('#tipePenugasan').val()
    };

    const url = currentEditId ? 'api/penugasan.php?id=' + currentEditId : 'api/penugasan.php';
    const method = currentEditId ? 'PUT' : 'POST';

    showLoading();

    $.ajax({
        url: url,
        method: method,
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            hideLoading();

            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('penugasanModal')).hide();
                showNotification(response.message, 'success');
                loadAssignments();
                updateStatistics();
            } else {
                showNotification(response.message, 'error');
            }
        }
    });
});

// Auto assignment functionality
function generateAutoAssignments() {
    const modal = new bootstrap.Modal(document.getElementById('autoAssignmentModal'));

    // Set default dates
    const today = new Date();
    const nextWeek = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);

    $('#startDate').val(today.toISOString().split('T')[0]);
    $('#endDate').val(nextWeek.toISOString().split('T')[0]);

    // Load wilayah options
    loadWilayahOptionsForAuto();

    modal.show();
}

function loadWilayahOptionsForAuto() {
    $.ajax({
        url: 'api/wilayah.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#autoWilayah');
                select.find('option:not(:first)').remove();

                response.data.forEach(function(wilayah) {
                    select.append(`<option value="${wilayah.id}">${wilayah.nama_wilayah}</option>`);
                });
            }
        }
    });
}

$('#assignmentType').on('change', function() {
    const value = $(this).val();
    if (value === 'weekly') {
        $('#weeklyDaysContainer').removeClass('d-none');
    } else {
        $('#weeklyDaysContainer').addClass('d-none');
    }
});

$('#autoAssignmentForm').on('submit', function(e) {
    e.preventDefault();

    const formData = {
        action: 'auto_assign',
        wilayah_id: $('#autoWilayah').val(),
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val(),
        assignment_type: $('#assignmentType').val()
    };

    if (formData.assignment_type === 'weekly') {
        const selectedDays = $('input[name="days[]"]:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedDays.length === 0) {
            showNotification('Pilih setidaknya satu hari untuk penugasan mingguan', 'error');
            return;
        }

        formData.days = selectedDays;
    }

    showLoading();

    $.ajax({
        url: 'api/penugasan.php',
        method: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            hideLoading();

            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('autoAssignmentModal')).hide();
                showNotification(response.message, 'success');
                loadAssignments();
                updateStatistics();
            } else {
                showNotification(response.message, 'error');
            }
        }
    });
});

// Delete functionality
function deletePenugasan(id) {
    currentDeleteId = id;

    $.ajax({
        url: 'api/penugasan.php?id=' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                $('#deletePenugasanInfo').html(`
                    <strong>Tanggal:</strong> ${formatDate(data.tanggal)}<br>
                    <strong>Petugas:</strong> ${data.nama_petugas}<br>
                    <strong>Majelis:</strong> ${data.nama_majelis}
                `);

                const modal = new bootstrap.Modal(document.getElementById('deletePenugasanModal'));
                modal.show();
            }
        }
    });
}

$('#confirmDeletePenugasan').on('click', function() {
    if (currentDeleteId) {
        showLoading();

        $.ajax({
            url: 'api/penugasan.php?id=' + currentDeleteId,
            method: 'DELETE',
            success: function(response) {
                hideLoading();

                if (response.success) {
                    bootstrap.Modal.getInstance(document.getElementById('deletePenugasanModal')).hide();
                    showNotification(response.message, 'success');
                    loadAssignments();
                    updateStatistics();
                } else {
                    showNotification(response.message, 'error');
                }

                currentDeleteId = null;
            }
        });
    }
});

// Helper functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}
</script>