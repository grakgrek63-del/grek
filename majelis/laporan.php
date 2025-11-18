<?php
$page_title = 'Laporan';
require_once 'includes/header.php';

// Check if export requested
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    generateExcelReport();
    exit;
}
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-excel me-2 text-primary"></i>
                        Laporan Penugasan
                    </h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                        <a href="?export=excel" class="btn btn-success">
                            <i class="fas fa-download me-1"></i>Export Excel
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form id="reportFilterForm" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="reportWilayah" class="form-label">Wilayah</label>
                        <select class="form-select" id="reportWilayah" name="wilayah_id">
                            <option value="">Semua Wilayah</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="startDate" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="startDate" name="start_date" required>
                    </div>
                    <div class="col-md-3">
                        <label for="endDate" class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="endDate" name="end_date" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <div class="btn-group w-100" role="group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetReportFilters()">
                                <i class="fas fa-redo me-1"></i>Reset
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Report Summary -->
                <div class="row mb-4" id="reportSummary">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0" id="totalDaysReport">0</h4>
                                        <small>Hari</small>
                                    </div>
                                    <i class="fas fa-calendar fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0" id="totalAssignmentsReport">0</h4>
                                        <small>Penugasan</small>
                                    </div>
                                    <i class="fas fa-user-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0" id="totalPetugasReport">0</h4>
                                        <small>Petugas</small>
                                    </div>
                                    <i class="fas fa-user-tie fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0" id="totalMajelisReport">0</h4>
                                        <small>Majelis</small>
                                    </div>
                                    <i class="fas fa-mosque fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading indicator -->
                <div id="reportLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data laporan...</p>
                </div>

                <!-- Report Content -->
                <div id="reportContent" class="d-none">
                    <!-- Report Matrix Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="reportMatrixTable">
                            <thead class="table-dark">
                                <tr id="reportHeaderRow">
                                    <th>No</th>
                                    <th>Hari</th>
                                    <th>Tanggal</th>
                                    <!-- Majelis columns will be added dynamically -->
                                </tr>
                            </thead>
                            <tbody id="reportTableBody">
                                <!-- Report rows will be added dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div id="reportEmptyState" class="text-center py-5 d-none">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada data penugasan</h5>
                        <p class="text-muted">Pilih rentang tanggal yang berbeda untuk melihat laporan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style media="print">
    .no-print {
        display: none !important;
    }

    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }

    .table {
        page-break-inside: avoid;
    }

    .btn-group {
        display: none !important;
    }

    body {
        font-size: 12px;
    }

    .card-body {
        padding: 0.5rem;
    }
</style>

<?php require_once 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Set default date range (this week)
    const today = new Date();
    const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
    const endOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 6));

    $('#startDate').val(startOfWeek.toISOString().split('T')[0]);
    $('#endDate').val(endOfWeek.toISOString().split('T')[0]);

    // Load wilayah options
    loadWilayahOptionsForReport();

    // Load initial report
    loadReport();
});

function loadWilayahOptionsForReport() {
    $.ajax({
        url: 'api/wilayah.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#reportWilayah');
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

function loadReport() {
    const wilayahId = $('#reportWilayah').val();
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();

    if (!startDate || !endDate) {
        showNotification('Pilih rentang tanggal terlebih dahulu', 'warning');
        return;
    }

    if (new Date(startDate) > new Date(endDate)) {
        showNotification('Tanggal mulai tidak boleh lebih besar dari tanggal selesai', 'warning');
        return;
    }

    // Show loading
    $('#reportContent').addClass('d-none');
    $('#reportLoading').removeClass('d-none');

    const params = new URLSearchParams({
        start_date: startDate,
        end_date: endDate
    });

    if (wilayahId) {
        params.append('wilayah_id', wilayahId);
    }

    $.ajax({
        url: 'api/penugasan.php?' + params.toString(),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderReport(response.data);
                updateReportSummary(response.data);
            } else {
                showReportError(response.message);
            }
        },
        error: function(xhr, status, error) {
            showReportError('Gagal memuat data laporan');
        },
        complete: function() {
            $('#reportLoading').addClass('d-none');
        }
    });
}

function renderReport(assignments) {
    $('#reportContent').removeClass('d-none');

    if (assignments.length === 0) {
        $('#reportEmptyState').removeClass('d-none');
        $('#reportMatrixTable').addClass('d-none');
        return;
    }

    $('#reportEmptyState').addClass('d-none');
    $('#reportMatrixTable').removeClass('d-none');

    // Get unique majelis
    const majelisMap = {};
    assignments.forEach(function(assignment) {
        if (!majelisMap[assignment.id_majelis]) {
            majelisMap[assignment.id_majelis] = assignment.nama_majelis;
        }
    });

    const majelisList = Object.values(majelisMap).sort();

    // Build table header
    const headerRow = $('#reportHeaderRow');
    headerRow.find('th:gt(2)').remove(); // Keep first 3 columns

    majelisList.forEach(function(majelisName) {
        headerRow.append(`<th>${majelisName}</th>`);
    });

    // Group assignments by date
    const assignmentsByDate = {};
    assignments.forEach(function(assignment) {
        if (!assignmentsByDate[assignment.tanggal]) {
            assignmentsByDate[assignment.tanggal] = [];
        }
        assignmentsByDate[assignment.tanggal].push(assignment);
    });

    // Get date range
    const startDate = new Date($('#startDate').val());
    const endDate = new Date($('#endDate').val());
    const dateRange = [];

    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        dateRange.push(new Date(d).toISOString().split('T')[0]);
    }

    // Render table body
    const tbody = $('#reportTableBody');
    tbody.empty();

    let rowNumber = 1;
    dateRange.forEach(function(date) {
        const dayAssignments = assignmentsByDate[date] || [];
        const dayName = getDayName(date);
        const formattedDate = formatDate(date);

        const row = $('<tr></tr>');
        row.append(`<td>${rowNumber}</td>`);
        row.append(`<td>${dayName}</td>`);
        row.append(`<td>${formattedDate}</td>`);

        // Add columns for each majelis
        majelisList.forEach(function(majelisName) {
            const assignment = dayAssignments.find(function(a) {
                return a.nama_majelis === majelisName;
            });

            if (assignment) {
                const distance = parseFloat(assignment.jarak_km) || 0;
                const badgeClass = assignment.tugas === 'otomatis' ? 'bg-success' : 'bg-primary';

                row.append(`
                    <td>
                        <div class="assignment-cell">
                            <strong>${assignment.nama_petugas}</strong><br>
                            <small class="text-muted">${distance.toFixed(2)} km</small><br>
                            <span class="badge ${badgeClass}">${assignment.tugas}</span>
                        </div>
                    </td>
                `);
            } else {
                row.append('<td class="text-muted">-</td>');
            }
        });

        tbody.append(row);
        rowNumber++;
    });
}

function updateReportSummary(assignments) {
    const uniqueDates = [...new Set(assignments.map(a => a.tanggal))];
    const uniquePetugas = [...new Set(assignments.map(a => a.nama_petugas))];
    const uniqueMajelis = [...new Set(assignments.map(a => a.nama_majelis))];

    $('#totalDaysReport').text(uniqueDates.length);
    $('#totalAssignmentsReport').text(assignments.length);
    $('#totalPetugasReport').text(uniquePetugas.length);
    $('#totalMajelisReport').text(uniqueMajelis.length);
}

function showReportError(message) {
    $('#reportContent').addClass('d-none');
    $('#reportEmptyState').removeClass('d-none');
    $('#reportEmptyState').html(`
        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
        <h5 class="text-warning">Terjadi Kesalahan</h5>
        <p class="text-muted">${message}</p>
    `);
}

function resetReportFilters() {
    const today = new Date();
    const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
    const endOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 6));

    $('#startDate').val(startOfWeek.toISOString().split('T')[0]);
    $('#endDate').val(endOfWeek.toISOString().split('T')[0]);

    if (!USER_WILAYAH) {
        $('#reportWilayah').val('');
    }

    loadReport();
}

// Form submission
$('#reportFilterForm').on('submit', function(e) {
    e.preventDefault();
    loadReport();
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

// Add custom styles for assignment cells
$('<style>').text(`
    .assignment-cell {
        min-width: 120px;
        font-size: 0.85rem;
    }

    .assignment-cell strong {
        display: block;
        margin-bottom: 2px;
    }

    .assignment-cell .badge {
        font-size: 0.7rem;
    }

    #reportMatrixTable th {
        white-space: nowrap;
        vertical-align: middle;
        font-size: 0.85rem;
    }

    #reportMatrixTable td {
        vertical-align: top;
        padding: 8px;
    }
`).appendTo('head');
</script>

<?php
function generateExcelReport() {
    require_once 'config/config.php';
    require_auth();

    $database = new Database();
    $db = $database->getConnection();

    $wilayahId = isset($_GET['wilayah_id']) ? (int)$_GET['wilayah_id'] : null;
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    $user = get_current_user();

    // Validate parameters
    if (!validate_date($startDate) || !validate_date($endDate)) {
        die('Invalid date format');
    }

    // Build query
    $query = "SELECT p.*, pt.nama as nama_petugas, m.nama_majelis, w.nama_wilayah
              FROM penugasan p
              JOIN petugas_pentawajuh pt ON p.id_petugas = pt.id
              JOIN majelis_dzikir m ON p.id_majelis = m.id
              JOIN wilayah w ON m.wilayah_id = w.id
              WHERE p.tanggal BETWEEN :start_date AND :end_date";

    $params = [
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ];

    // Apply wilayah filter
    if ($wilayahId) {
        $query .= " AND w.id = :wilayah_id";
        $params[':wilayah_id'] = $wilayahId;
    } elseif ($user['role'] !== 'admin') {
        $query .= " AND w.id = :user_wilayah_id";
        $params[':user_wilayah_id'] = $user['wilayah_id'];
    }

    $query .= " ORDER BY p.tanggal, w.nama_wilayah, m.nama_majelis";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($assignments)) {
        die('Tidak ada data penugasan untuk periode yang dipilih');
    }

    try {
        // Load PhpSpreadsheet
        require_once __DIR__ . '/vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator(APP_NAME)
            ->setTitle('Laporan Penugasan')
            ->setSubject('Laporan Penugasan Majelis Dzikir')
            ->setDescription('Laporan penugasan petugas pentawajuh ke majelis dzikir');

        // Get unique majelis for columns
        $majelisMap = [];
        foreach ($assignments as $assignment) {
            $majelisMap[$assignment['id_majelis']] = $assignment['nama_majelis'];
        }
        $majelisList = array_values($majelisMap);

        // Create date range
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $dateRange = [];
        for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
            $dateRange[] = $date->format('Y-m-d');
        }

        // Group assignments by date
        $assignmentsByDate = [];
        foreach ($assignments as $assignment) {
            $assignmentsByDate[$assignment['tanggal']][] = $assignment;
        }

        // Set column headers
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Hari');
        $sheet->setCellValue('C1', 'Tanggal');

        $col = 'D';
        foreach ($majelisList as $majelis) {
            $sheet->setCellValue($col . '1', $majelis);
            $col++;
        }

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '007bff']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];

        $sheet->getStyle('A1:' . chr(ord($col) - 1) . '1')->applyFromArray($headerStyle);

        // Fill data
        $row = 2;
        $rowNumber = 1;

        foreach ($dateRange as $date) {
            $dayName = date('w', strtotime($date));
            $days = ['Ahad', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $dayNameText = $days[$dayName];

            $sheet->setCellValue('A' . $row, $rowNumber);
            $sheet->setCellValue('B' . $row, $dayNameText);
            $sheet->setCellValue('C' . $row, date('d/m/Y', strtotime($date)));

            $dayAssignments = $assignmentsByDate[$date] ?? [];
            $col = 'D';

            foreach ($majelisList as $majelisId => $majelisName) {
                $assignment = null;
                foreach ($dayAssignments as $dayAssignment) {
                    if ($dayAssignment['nama_majelis'] === $majelisName) {
                        $assignment = $dayAssignment;
                        break;
                    }
                }

                if ($assignment) {
                    $cellValue = $assignment['nama_petugas'];
                    $sheet->setCellValue($col . $row, $cellValue);

                    // Add comment with distance and assignment type
                    $distance = number_format($assignment['jarak_km'] ?? 0, 2);
                    $comment = "Jarak: {$distance} km\nTipe: " . ucfirst($assignment['tugas']);
                    $sheet->getComment($col . $row)->getText()->createTextRun($comment);
                } else {
                    $sheet->setCellValue($col . $row, '-');
                }

                $col++;
            }

            $row++;
            $rowNumber++;
        }

        // Auto-size columns
        foreach (range('A', $col) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Add summary at the bottom
        $summaryRow = $row + 2;
        $uniqueDates = count($dateRange);
        $totalAssignments = count($assignments);
        $uniquePetugas = count(array_unique(array_column($assignments, 'nama_petugas')));
        $uniqueMajelis = count($majelisList);

        $sheet->setCellValue('A' . $summaryRow, 'RINGKASAN');
        $sheet->setCellValue('A' . ($summaryRow + 1), "Total Hari: {$uniqueDates}");
        $sheet->setCellValue('A' . ($summaryRow + 2), "Total Penugasan: {$totalAssignments}");
        $sheet->setCellValue('A' . ($summaryRow + 3), "Total Petugas: {$uniquePetugas}");
        $sheet->setCellValue('A' . ($summaryRow + 4), "Total Majelis: {$uniqueMajelis}");

        // Style summary
        $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $summaryRow . ':A' . ($summaryRow + 4))->getFont()->setSize(10);

        // Set filename
        $filename = 'Laporan_Penugasan_' . str_replace('-', '', $startDate) . '_s_d_' . str_replace('-', '', $endDate) . '.xlsx';

        // Create writer
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');

        $writer->save('php://output');
        exit;

    } catch (Exception $e) {
        die('Error generating Excel file: ' . $e->getMessage());
    }
}
?>