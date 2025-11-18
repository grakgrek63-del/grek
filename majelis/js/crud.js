/**
 * CRUD Operations JavaScript for Majelis Dzikir Management System
 */

// Global CRUD functions
let currentEditData = {};
let currentEditType = null;

// Wilayah CRUD
function openWilayahModal(id = null) {
    currentEditType = 'wilayah';
    currentEditId = id;

    const modalHtml = `
        <div class="modal fade" id="wilayahCrudModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-map-marked-alt me-2"></i>
                            ${id ? 'Edit' : 'Tambah'} Wilayah
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="wilayahCrudForm">
                        <div class="modal-body">
                            <input type="hidden" id="wilayahCrudId" name="id">
                            <div class="mb-3">
                                <label for="wilayahCrudName" class="form-label">Nama Wilayah <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="wilayahCrudName" name="nama_wilayah" required>
                            </div>
                            <div class="mb-3">
                                <label for="wilayahCrudPolygon" class="form-label">Polygon (JSON)</label>
                                <textarea class="form-control" id="wilayahCrudPolygon" name="polygon" rows="3" readonly></textarea>
                                <small class="form-text text-muted">Polygon akan diisi dari gambar di peta</small>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Untuk mengedit polygon, gunakan tools gambar di peta
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Batal
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if any
    $('#wilayahCrudModal').remove();
    $('body').append(modalHtml);

    const modal = new bootstrap.Modal(document.getElementById('wilayahCrudModal'));

    if (id) {
        loadWilayahForEdit(id);
    } else {
        resetWilayahForm();
        // For new wilayah, enable drawing mode
        enableDrawMode();
    }

    modal.show();
}

function loadWilayahForEdit(id) {
    showLoading();

    $.ajax({
        url: 'api/wilayah.php?id=' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                $('#wilayahCrudId').val(data.id);
                $('#wilayahCrudName').val(data.nama_wilayah);
                $('#wilayahCrudPolygon').val(data.polygon);
            }
            hideLoading();
        }
    });
}

function resetWilayahForm() {
    $('#wilayahCrudForm')[0].reset();
    $('#wilayahCrudId').val('');
    $('#wilayahCrudPolygon').val('');
}

function enableDrawMode() {
    showNotification('Gambar polygon wilayah di peta', 'info');
    // Focus on map and enable drawing controls
    if (map && window.drawControl) {
        map.invalidateSize();
    }
}

$('#wilayahCrudForm').on('submit', function(e) {
    e.preventDefault();

    const formData = {
        nama_wilayah: $('#wilayahCrudName').val(),
        polygon: $('#wilayahCrudPolygon').val() || '[]'
    };

    const id = $('#wilayahCrudId').val();
    const url = id ? 'api/wilayah.php?id=' + id : 'api/wilayah.php';
    const method = id ? 'PUT' : 'POST';

    showLoading();

    $.ajax({
        url: url,
        method: method,
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            hideLoading();

            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('wilayahCrudModal')).hide();
                showNotification(response.message, 'success');
                refreshMap();
                updateStatistics();
            } else {
                showNotification(response.message, 'error');
            }
        }
    });
});

// Majelis CRUD
function openMajelisModal(id = null) {
    currentEditType = 'majelis';
    currentEditId = id;

    const modalHtml = `
        <div class="modal fade" id="majelisCrudModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-mosque me-2"></i>
                            ${id ? 'Edit' : 'Tambah'} Majelis Dzikir
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="majelisCrudForm">
                        <div class="modal-body">
                            <input type="hidden" id="majelisCrudId" name="id">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="majelisCrudWilayah" class="form-label">Wilayah <span class="text-danger">*</span></label>
                                    <select class="form-select" id="majelisCrudWilayah" name="wilayah_id" required>
                                        <option value="">Pilih Wilayah</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="majelisCrudName" class="form-label">Nama Majelis <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="majelisCrudName" name="nama_majelis" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="majelisCrudAlamat" class="form-label">Alamat</label>
                                <textarea class="form-control" id="majelisCrudAlamat" name="alamat" rows="2"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="majelisCrudLat" class="form-label">Latitude <span class="text-danger">*</span></label>
                                    <input type="number" step="0.00000001" class="form-control" id="majelisCrudLat" name="latitude" required>
                                    <small class="form-text text-muted">Klik di peta untuk mengisi koordinat</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="majelisCrudLng" class="form-label">Longitude <span class="text-danger">*</span></label>
                                    <input type="number" step="0.00000001" class="form-control" id="majelisCrudLng" name="longitude" required>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                Klik di peta untuk menentukan lokasi majelis
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Batal
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    $('#majelisCrudModal').remove();
    $('body').append(modalHtml);

    const modal = new bootstrap.Modal(document.getElementById('majelisCrudModal'));

    loadWilayahOptionsForMajelis();

    if (id) {
        loadMajelisForEdit(id);
    } else {
        resetMajelisForm();
        enableMajelisMapClick();
    }

    modal.show();
}

function loadWilayahOptionsForMajelis() {
    $.ajax({
        url: 'api/wilayah.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#majelisCrudWilayah');
                select.find('option:not(:first)').remove();

                response.data.forEach(function(wilayah) {
                    select.append(`<option value="${wilayah.id}">${wilayah.nama_wilayah}</option>`);
                });

                // Set selected wilayah for regional users
                if (USER_WILAYAH) {
                    select.val(USER_WILAYAH).prop('disabled', true);
                }
            }
        }
    });
}

function loadMajelisForEdit(id) {
    showLoading();

    $.ajax({
        url: 'api/majelis.php?id=' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                $('#majelisCrudId').val(data.id);
                $('#majelisCrudWilayah').val(data.wilayah_id);
                $('#majelisCrudName').val(data.nama_majelis);
                $('#majelisCrudAlamat').val(data.alamat);
                $('#majelisCrudLat').val(data.latitude);
                $('#majelisCrudLng').val(data.longitude);
            }
            hideLoading();
        }
    });
}

function resetMajelisForm() {
    $('#majelisCrudForm')[0].reset();
    $('#majelisCrudId').val('');
}

function enableMajelisMapClick() {
    if (map) {
        // Add temporary click handler
        map.once('click', function(e) {
            const lat = e.latlng.lat.toFixed(8);
            const lng = e.latlng.lng.toFixed(8);

            $('#majelisCrudLat').val(lat);
            $('#majelisCrudLng').val(lng);

            // Add temporary marker
            const tempMarker = L.marker([lat, lng]).addTo(map);
            tempMarker.bindPopup('Lokasi Majelis').openPopup();

            // Remove marker after 3 seconds
            setTimeout(() => {
                map.removeLayer(tempMarker);
            }, 3000);

            showNotification('Koordinat lokasi berhasil disimpan', 'success');
        });
    }
}

$('#majelisCrudForm').on('submit', function(e) {
    e.preventDefault();

    const formData = {
        wilayah_id: $('#majelisCrudWilayah').val(),
        nama_majelis: $('#majelisCrudName').val(),
        alamat: $('#majelisCrudAlamat').val(),
        latitude: parseFloat($('#majelisCrudLat').val()),
        longitude: parseFloat($('#majelisCrudLng').val())
    };

    const id = $('#majelisCrudId').val();
    const url = id ? 'api/majelis.php?id=' + id : 'api/majelis.php';
    const method = id ? 'PUT' : 'POST';

    showLoading();

    $.ajax({
        url: url,
        method: method,
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            hideLoading();

            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('majelisCrudModal')).hide();
                showNotification(response.message, 'success');
                refreshMap();
                updateStatistics();
                loadAssignments();
            } else {
                showNotification(response.message, 'error');
            }
        }
    });
});

// Petugas CRUD
function openPetugasModal(id = null) {
    currentEditType = 'petugas';
    currentEditId = id;

    const modalHtml = `
        <div class="modal fade" id="petugasCrudModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-tie me-2"></i>
                            ${id ? 'Edit' : 'Tambah'} Petugas Pentawajuh
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="petugasCrudForm">
                        <div class="modal-body">
                            <input type="hidden" id="petugasCrudId" name="id">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="petugasCrudWilayah" class="form-label">Wilayah <span class="text-danger">*</span></label>
                                    <select class="form-select" id="petugasCrudWilayah" name="id_wilayah" required>
                                        <option value="">Pilih Wilayah</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="petugasCrudName" class="form-label">Nama Petugas <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="petugasCrudName" name="nama" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="petugasCrudPhone" class="form-label">Telepon</label>
                                <input type="text" class="form-control" id="petugasCrudPhone" name="telepon">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="petugasCrudLat" class="form-label">Latitude <span class="text-danger">*</span></label>
                                    <input type="number" step="0.00000001" class="form-control" id="petugasCrudLat" name="latitude" required>
                                    <small class="form-text text-muted">Klik di peta untuk mengisi koordinat</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="petugasCrudLng" class="form-label">Longitude <span class="text-danger">*</span></label>
                                    <input type="number" step="0.00000001" class="form-control" id="petugasCrudLng" name="longitude" required>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                Klik di peta untuk menentukan lokasi petugas
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Batal
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    $('#petugasCrudModal').remove();
    $('body').append(modalHtml);

    const modal = new bootstrap.Modal(document.getElementById('petugasCrudModal'));

    loadWilayahOptionsForPetugas();

    if (id) {
        loadPetugasForEdit(id);
    } else {
        resetPetugasForm();
        enablePetugasMapClick();
    }

    modal.show();
}

function loadWilayahOptionsForPetugas() {
    $.ajax({
        url: 'api/wilayah.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#petugasCrudWilayah');
                select.find('option:not(:first)').remove();

                response.data.forEach(function(wilayah) {
                    select.append(`<option value="${wilayah.id}">${wilayah.nama_wilayah}</option>`);
                });

                // Set selected wilayah for regional users
                if (USER_WILAYAH) {
                    select.val(USER_WILAYAH).prop('disabled', true);
                }
            }
        }
    });
}

function loadPetugasForEdit(id) {
    showLoading();

    $.ajax({
        url: 'api/petugas.php?id=' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                $('#petugasCrudId').val(data.id);
                $('#petugasCrudWilayah').val(data.id_wilayah);
                $('#petugasCrudName').val(data.nama);
                $('#petugasCrudPhone').val(data.telepon);
                $('#petugasCrudLat').val(data.latitude);
                $('#petugasCrudLng').val(data.longitude);
            }
            hideLoading();
        }
    });
}

function resetPetugasForm() {
    $('#petugasCrudForm')[0].reset();
    $('#petugasCrudId').val('');
}

function enablePetugasMapClick() {
    if (map) {
        map.once('click', function(e) {
            const lat = e.latlng.lat.toFixed(8);
            const lng = e.latlng.lng.toFixed(8);

            $('#petugasCrudLat').val(lat);
            $('#petugasCrudLng').val(lng);

            // Add temporary marker
            const tempMarker = L.marker([lat, lng]).addTo(map);
            tempMarker.bindPopup('Lokasi Petugas').openPopup();

            // Remove marker after 3 seconds
            setTimeout(() => {
                map.removeLayer(tempMarker);
            }, 3000);

            showNotification('Koordinat lokasi berhasil disimpan', 'success');
        });
    }
}

$('#petugasCrudForm').on('submit', function(e) {
    e.preventDefault();

    const formData = {
        id_wilayah: $('#petugasCrudWilayah').val(),
        nama: $('#petugasCrudName').val(),
        telepon: $('#petugasCrudPhone').val(),
        latitude: parseFloat($('#petugasCrudLat').val()),
        longitude: parseFloat($('#petugasCrudLng').val())
    };

    const id = $('#petugasCrudId').val();
    const url = id ? 'api/petugas.php?id=' + id : 'api/petugas.php';
    const method = id ? 'PUT' : 'POST';

    showLoading();

    $.ajax({
        url: url,
        method: method,
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            hideLoading();

            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('petugasCrudModal')).hide();
                showNotification(response.message, 'success');
                refreshMap();
                updateStatistics();
                loadAssignments();
            } else {
                showNotification(response.message, 'error');
            }
        }
    });
});

// User CRUD (Admin only)
function openUserModal() {
    if (USER_ROLE !== 'admin') {
        showNotification('Akses ditolak', 'error');
        return;
    }

    const modalHtml = `
        <div class="modal fade" id="userCrudModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-users me-2"></i>
                            Manajemen User
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#userListTab">
                                    <i class="fas fa-list me-2"></i>Daftar User
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#addUserTab">
                                    <i class="fas fa-user-plus me-2"></i>Tambah User
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content mt-3">
                            <div class="tab-pane fade show active" id="userListTab">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="usersTable">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Role</th>
                                                <th>Wilayah</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="usersTableBody">
                                            <tr>
                                                <td colspan="4" class="text-center">
                                                    <i class="fas fa-spinner fa-spin me-2"></i>Memuat...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="addUserTab">
                                <form id="addUserForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="newUsername" class="form-label">Username <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="newUsername" name="username" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="newUserRole" class="form-label">Role <span class="text-danger">*</span></label>
                                            <select class="form-select" id="newUserRole" name="role" required>
                                                <option value="">Pilih Role</option>
                                                <option value="regional">Regional</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="newUserWilayah" class="form-label">Wilayah</label>
                                        <select class="form-select" id="newUserWilayah" name="wilayah_id">
                                            <option value="">Pilih Wilayah</option>
                                        </select>
                                        <small class="form-text text-muted">Kosongkan untuk admin (jika applicable)</small>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="newPassword" class="form-label">Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="newPassword" name="password" required minlength="6">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="confirmPassword" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="confirmPassword" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Simpan User
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#userCrudModal').remove();
    $('body').append(modalHtml);

    const modal = new bootstrap.Modal(document.getElementById('userCrudModal'));
    loadUsersList();
    loadWilayahOptionsForUser();

    modal.show();
}

function loadUsersList() {
    $.ajax({
        url: 'api/users.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderUsersTable(response.data);
            }
        }
    });
}

function renderUsersTable(users) {
    const tbody = $('#usersTableBody');

    if (users.length === 0) {
        tbody.html('<tr><td colspan="4" class="text-center">Tidak ada user</td></tr>');
        return;
    }

    let html = '';
    users.forEach(function(user) {
        html += `
            <tr>
                <td>${user.username}</td>
                <td><span class="badge bg-${user.role === 'admin' ? 'danger' : 'info'}">${user.role}</span></td>
                <td>${user.nama_wilayah || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id})" ${user.role === 'admin' ? 'disabled' : ''}>
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.html(html);
}

function loadWilayahOptionsForUser() {
    $.ajax({
        url: 'api/wilayah.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#newUserWilayah');
                select.find('option:not(:first)').remove();

                response.data.forEach(function(wilayah) {
                    select.append(`<option value="${wilayah.id}">${wilayah.nama_wilayah}</option>`);
                });
            }
        }
    });
}

$('#addUserForm').on('submit', function(e) {
    e.preventDefault();

    const password = $('#newPassword').val();
    const confirmPassword = $('#confirmPassword').val();

    if (password !== confirmPassword) {
        showNotification('Password tidak cocok', 'error');
        return;
    }

    const formData = {
        username: $('#newUsername').val(),
        password: password,
        role: $('#newUserRole').val(),
        wilayah_id: $('#newUserWilayah').val() || null
    };

    showLoading();

    $.ajax({
        url: 'api/users.php',
        method: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            hideLoading();

            if (response.success) {
                $('#addUserForm')[0].reset();
                showNotification(response.message, 'success');
                loadUsersList();
                $('a[href="#userListTab"]').tab('show');
            } else {
                showNotification(response.message, 'error');
            }
        }
    });
});

function deleteUser(userId) {
    if (!confirm('Apakah Anda yakin ingin menghapus user ini?')) {
        return;
    }

    showLoading();

    $.ajax({
        url: 'api/users.php?id=' + userId,
        method: 'DELETE',
        success: function(response) {
            hideLoading();

            if (response.success) {
                showNotification(response.message, 'success');
                loadUsersList();
            } else {
                showNotification(response.message, 'error');
            }
        }
    });
}

// Change Password Modal
function openChangePasswordModal() {
    const modalHtml = `
        <div class="modal fade" id="changePasswordModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-key me-2"></i>
                            Ganti Password
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="changePasswordForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="currentPassword" class="form-label">Password Saat Ini <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">Password Baru <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label for="confirmNewPassword" class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirmNewPassword" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Batal
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Ganti Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    $('#changePasswordModal').remove();
    $('body').append(modalHtml);

    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
}

$('#changePasswordForm').on('submit', function(e) {
    e.preventDefault();

    const currentPassword = $('#currentPassword').val();
    const newPassword = $('#newPassword').val();
    const confirmNewPassword = $('#confirmNewPassword').val();

    if (newPassword !== confirmNewPassword) {
        showNotification('Password baru tidak cocok', 'error');
        return;
    }

    if (newPassword.length < 6) {
        showNotification('Password minimal 6 karakter', 'error');
        return;
    }

    const user = get_current_user();

    showLoading();

    $.ajax({
        url: 'api/users.php?action=change_password',
        method: 'POST',
        data: JSON.stringify({
            user_id: user.id,
            current_password: currentPassword,
            new_password: newPassword
        }),
        contentType: 'application/json',
        success: function(response) {
            hideLoading();

            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
                showNotification('Password berhasil diubah', 'success');
                $('#changePasswordForm')[0].reset();
            } else {
                showNotification(response.message, 'error');
            }
        }
    });
});

// Utility functions
function showNotification(message, type = 'info') {
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
    } else {
        alert(message);
    }
}

function showLoading() {
    if (typeof window.showLoading === 'function') {
        window.showLoading();
    }
}

function hideLoading() {
    if (typeof window.hideLoading === 'function') {
        window.hideLoading();
    }
}