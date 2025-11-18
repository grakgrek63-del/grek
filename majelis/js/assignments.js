/**
 * Assignments JavaScript for Majelis Dzikir Management System
 */

$(document).ready(function() {
    loadAssignments();

    // Auto refresh assignments every 30 seconds
    setInterval(loadAssignments, 30000);
});

function loadAssignments() {
    const wilayahId = $('#wilayahFilter').val();
    const tanggal = $('#tanggalFilter').val();

    let url = 'api/penugasan.php?';
    const params = [];

    if (wilayahId) {
        params.push('wilayah_id=' + wilayahId);
    }

    if (tanggal) {
        params.push('tanggal=' + tanggal);
    }

    if (params.length > 0) {
        url += params.join('&');
    }

    $.ajax({
        url: url,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderAssignmentsTable(response.data);
            } else {
                $('#assignmentsTableBody').html(`
                    <tr>
                        <td colspan="9" class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${response.message}
                        </td>
                    </tr>
                `);
            }
        },
        error: function(xhr, status, error) {
            $('#assignmentsTableBody').html(`
                <tr>
                    <td colspan="9" class="text-center text-danger py-4">
                        <i class="fas fa-wifi me-2"></i>
                        Gagal memuat data. Silakan coba lagi.
                    </td>
                </tr>
            `);
        }
    });
}

function renderAssignmentsTable(assignments) {
    const tbody = $('#assignmentsTableBody');

    if (assignments.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="9" class="text-center text-muted py-4">
                    <i class="fas fa-inbox me-2"></i>
                    Tidak ada penugasan untuk tanggal terpilih
                </td>
            </tr>
        `);
        return;
    }

    let html = '';
    let currentWilayah = '';
    let wilayahCount = 0;

    assignments.forEach(function(assignment, index) {
        // Add wilayah separator
        if (assignment.nama_wilayah !== currentWilayah) {
            currentWilayah = assignment.nama_wilayah;
            wilayahCount++;

            if (wilayahCount > 1) {
                html += `<tr><td colspan="9" class="table-secondary text-center fw-bold py-2">${currentWilayah}</td></tr>`;
            } else {
                html += `<tr><td colspan="9" class="table-secondary text-center fw-bold py-2">${currentWilayah}</td></tr>`;
            }
        }

        const badgeClass = assignment.tugas === 'otomatis' ? 'bg-success' : 'bg-primary';
        const dayName = getDayName(assignment.tanggal);
        const formattedDate = formatDate(assignment.tanggal);

        html += `
            <tr>
                <td>${index + 1}</td>
                <td>
                    <div class="d-flex flex-column">
                        <strong>${assignment.tanggal}</strong>
                        <small class="text-muted">${dayName}</small>
                    </div>
                </td>
                <td>${dayName}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-tie text-danger me-2"></i>
                        <div>
                            <strong>${assignment.nama_petugas}</strong>
                            ${assignment.telepon_petugas ? `<br><small class="text-muted">${assignment.telepon_petugas}</small>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-mosque text-primary me-2"></i>
                        <div>
                            <strong>${assignment.nama_majelis}</strong>
                            ${assignment.alamat_majelis ? `<br><small class="text-muted">${assignment.alamat_majelis}</small>` : ''}
                        </div>
                    </div>
                </td>
                <td>${assignment.nama_wilayah}</td>
                <td>
                    <span class="badge bg-info">
                        <i class="fas fa-route me-1"></i>
                        ${(parseFloat(assignment.jarak_km) || 0).toFixed(2)} km
                    </span>
                </td>
                <td>
                    <span class="badge ${badgeClass}">
                        <i class="fas fa-${assignment.tugas === 'otomatis' ? 'magic' : 'hand-pointer'} me-1"></i>
                        ${assignment.tugas === 'otomatis' ? 'Otomatis' : 'Manual'}
                    </span>
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary"
                                onclick="editPenugasan(${assignment.id})"
                                data-bs-toggle="tooltip" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger"
                                onclick="deletePenugasan(${assignment.id})"
                                data-bs-toggle="tooltip" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button type="button" class="btn btn-outline-info"
                                onclick="viewOnMap(${assignment.latitude}, ${assignment.longitude})"
                                data-bs-toggle="tooltip" title="Lihat di Peta">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.html(html);

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function refreshAssignments() {
    loadAssignments();
    showNotification('Data penugasan diperbarui', 'info');
}

function exportCurrentAssignments() {
    const wilayahId = $('#wilayahFilter').val();
    const tanggal = $('#tanggalFilter').val();

    let url = 'laporan.php?export=excel&';
    const params = [];

    if (wilayahId) {
        params.push('wilayah_id=' + wilayahId);
    }

    if (tanggal) {
        params.push('start_date=' + tanggal + '&end_date=' + tanggal);
    } else {
        // Default to today
        const today = new Date().toISOString().split('T')[0];
        params.push('start_date=' + today + '&end_date=' + today);
    }

    url += params.join('&');

    // Open in new window for download
    window.open(url, '_blank');
}

function viewOnMap(lat, lng) {
    if (!lat || !lng) {
        showNotification('Koordinat tidak tersedia', 'warning');
        return;
    }

    // Pan map to coordinates
    map.setView([lat, lng], 15);

    // Add temporary marker
    const tempMarker = L.marker([lat, lng]).addTo(map);
    tempMarker.bindPopup('<strong>Lokasi Penugasan</strong>').openPopup();

    // Remove marker after 5 seconds
    setTimeout(() => {
        map.removeLayer(tempMarker);
    }, 5000);

    // Scroll map into view
    document.getElementById('map').scrollIntoView({ behavior: 'smooth' });
}

function editPenugasan(id) {
    // This function is already defined in penugasan_modal.php
    if (typeof openPenugasanModal === 'function') {
        openPenugasanModal(id);
    }
}

function deletePenugasan(id) {
    // This function is already defined in penugasan_modal.php
    if (typeof window.deletePenugasan === 'function') {
        window.deletePenugasan(id);
    }
}

// Filter form submission
$('#filterForm').on('submit', function(e) {
    e.preventDefault();
    loadAssignments();
});

// Update date filter to today's date on page load
$(document).ready(function() {
    const today = new Date().toISOString().split('T')[0];
    $('#tanggalFilter').val(today);
});

// Keyboard shortcuts
$(document).on('keydown', function(e) {
    // Ctrl+N for new assignment
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        if (typeof openPenugasanModal === 'function') {
            openPenugasanModal();
        }
    }

    // Ctrl+R for refresh
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        refreshAssignments();
    }

    // Ctrl+E for export
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        exportCurrentAssignments();
    }
});

// Helper functions
function getDayName(dateString) {
    const days = ['Ahad', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const date = new Date(dateString);
    return days[date.getDay()];
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
}

// Search functionality
function searchAssignments() {
    const searchTerm = $('#searchInput').val().toLowerCase();
    const rows = $('#assignmentsTableBody tr');

    rows.each(function() {
        const text = $(this).text().toLowerCase();
        const shouldShow = text.includes(searchTerm);
        $(this).toggle(shouldShow);
    });
}

// Add search input if not exists
if ($('#searchInput').length === 0) {
    $('.card-header').first().find('div').prepend(`
        <div class="input-group" style="max-width: 300px;">
            <span class="input-group-text">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" class="form-control" id="searchInput"
                   placeholder="Cari penugasan..." onkeyup="searchAssignments()">
        </div>
    `);
}

// Print functionality
function printAssignments() {
    const printContent = $('#assignmentsTable').clone();

    // Remove action buttons for print
    printContent.find('td:last-child').remove();
    printContent.find('th:last-child').remove();

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Laporan Penugasan</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    .text-center { text-align: center; }
                    .text-right { text-align: right; }
                </style>
            </head>
            <body>
                <h1>Laporan Penugasan</h1>
                <p>Tanggal Cetak: ${new Date().toLocaleDateString('id-ID')}</p>
                ${printContent.prop('outerHTML')}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Add print button if not exists
if ($('#printButton').length === 0) {
    $('.card-header').first().find('div').append(`
        <button type="button" class="btn btn-outline-secondary btn-sm" id="printButton"
                onclick="printAssignments()" data-bs-toggle="tooltip" title="Print">
            <i class="fas fa-print me-1"></i>Print
        </button>
    `);
}

// Bulk operations
function selectAllAssignments() {
    const selectAll = $('#selectAllAssignments').prop('checked');
    $('input[name="assignment_ids"]').prop('checked', selectAll);
}

function bulkDeleteAssignments() {
    const selectedIds = $('input[name="assignment_ids"]:checked').map(function() {
        return $(this).val();
    }).get();

    if (selectedIds.length === 0) {
        showNotification('Pilih penugasan yang akan dihapus', 'warning');
        return;
    }

    if (confirm(`Apakah Anda yakin ingin menghapus ${selectedIds.length} penugasan?`)) {
        // Implement bulk delete
        showNotification('Bulk delete not implemented yet', 'info');
    }
}

// Add bulk delete button if admin
if (USER_ROLE === 'admin' && $('#bulkDeleteButton').length === 0) {
    $('.card-header').first().find('div').append(`
        <button type="button" class="btn btn-outline-danger btn-sm d-none" id="bulkDeleteButton"
                onclick="bulkDeleteAssignments()" data-bs-toggle="tooltip" title="Hapus yang Dipilih">
            <i class="fas fa-trash me-1"></i>Hapus
        </button>
    `);
}