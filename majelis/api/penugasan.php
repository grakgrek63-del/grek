<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';
require_auth();

$database = new Database();
$db = $database->getConnection();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = isset($_GET['action']) ? $_GET['action'] : null;
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            if ($action === 'available') {
                handleGetAvailable($db);
            } else {
                handleGet($db);
            }
            break;
        case 'POST':
            if ($action === 'auto_assign') {
                handleAutoAssign($db, $input);
            } else {
                handlePost($db, $input);
            }
            break;
        case 'PUT':
            handlePut($db, $input);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            json_response(['message' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    json_response(['message' => 'Server error: ' . $e->getMessage()], 500);
}

function handleGet($db) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $wilayahId = isset($_GET['wilayah_id']) ? (int)$_GET['wilayah_id'] : null;
    $petugasId = isset($_GET['petugas_id']) ? (int)$_GET['petugas_id'] : null;
    $tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : null;
    $user = get_current_user();

    if ($id) {
        // Get single penugasan
        $query = "SELECT p.*, pt.nama as nama_petugas, pt.telepon as telepon_petugas,
                         m.nama_majelis, m.alamat as alamat_majelis, w.nama_wilayah
                  FROM penugasan p
                  JOIN petugas_pentawajuh pt ON p.id_petugas = pt.id
                  JOIN majelis_dzikir m ON p.id_majelis = m.id
                  JOIN wilayah w ON m.wilayah_id = w.id
                  WHERE p.id = :id";

        // Check regional access
        if ($user['role'] !== 'admin') {
            $query .= " AND m.wilayah_id = :wilayah_id";
        }

        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($user['role'] !== 'admin') {
            $stmt->bindParam(':wilayah_id', $user['wilayah_id']);
        }

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $penugasan = $stmt->fetch(PDO::FETCH_ASSOC);
            json_response(['success' => true, 'data' => $penugasan]);
        } else {
            json_response(['success' => false, 'message' => 'Penugasan not found'], 404);
        }
    } else {
        // Get penugasan list with filters
        $query = "SELECT p.*, pt.nama as nama_petugas, pt.telepon as telepon_petugas,
                         m.nama_majelis, m.alamat as alamat_majelis, w.nama_wilayah
                  FROM penugasan p
                  JOIN petugas_pentawajuh pt ON p.id_petugas = pt.id
                  JOIN majelis_dzikir m ON p.id_majelis = m.id
                  JOIN wilayah w ON m.wilayah_id = w.id";

        $params = [];
        $where_clauses = [];

        // Apply regional access filter
        if ($user['role'] !== 'admin') {
            $where_clauses[] = "m.wilayah_id = :user_wilayah_id";
            $params[':user_wilayah_id'] = $user['wilayah_id'];
        }

        // Apply other filters
        if ($wilayahId) {
            $where_clauses[] = "m.wilayah_id = :wilayah_id";
            $params[':wilayah_id'] = $wilayahId;
        }

        if ($petugasId) {
            $where_clauses[] = "p.id_petugas = :petugas_id";
            $params[':petugas_id'] = $petugasId;
        }

        if ($tanggal) {
            $where_clauses[] = "p.tanggal = :tanggal";
            $params[':tanggal'] = $tanggal;
        } else {
            // Default to today if no date specified
            $where_clauses[] = "p.tanggal = CURDATE()";
        }

        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $query .= " ORDER BY p.tanggal, w.nama_wilayah, m.nama_majelis";

        $stmt = $db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $penugasan = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success' => true, 'data' => $penugasan]);
    }
}

function handleGetAvailable($db) {
    $tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
    $wilayahId = isset($_GET['wilayah_id']) ? (int)$_GET['wilayah_id'] : null;
    $user = get_current_user();

    if ($user['role'] !== 'admin' && !$wilayahId) {
        $wilayahId = $user['wilayah_id'];
    }

    $query = "SELECT p.*, w.nama_wilayah
              FROM petugas_pentawajuh p
              JOIN wilayah w ON p.id_wilayah = w.id
              WHERE p.id NOT IN (
                  SELECT id_petugas FROM penugasan WHERE tanggal = :tanggal
              )";

    $params = [':tanggal' => $tanggal];

    if ($wilayahId) {
        $query .= " AND p.id_wilayah = :wilayah_id";
        $params[':wilayah_id'] = $wilayahId;
    }

    if ($user['role'] !== 'admin') {
        $query .= " AND p.id_wilayah = :user_wilayah_id";
        $params[':user_wilayah_id'] = $user['wilayah_id'];
    }

    $query .= " ORDER BY w.nama_wilayah, p.nama";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $petugas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json_response(['success' => true, 'data' => $petugas]);
}

function handlePost($db, $input) {
    if (!isset($input['id_petugas']) || !isset($input['id_majelis']) || !isset($input['tanggal'])) {
        json_response(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    $user = get_current_user();

    // Validate date
    if (!validate_date($input['tanggal'])) {
        json_response(['success' => false, 'message' => 'Invalid date format'], 400);
    }

    // Check if petugas exists and get wilayah
    $petugasQuery = "SELECT p.*, w.nama_wilayah
                     FROM petugas_pentawajuh p
                     JOIN wilayah w ON p.id_wilayah = w.id
                     WHERE p.id = :id_petugas";
    $petugasStmt = $db->prepare($petugasQuery);
    $petugasStmt->bindParam(':id_petugas', $input['id_petugas']);
    $petugasStmt->execute();

    if ($petugasStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Petugas not found'], 404);
    }

    $petugas = $petugasStmt->fetch(PDO::FETCH_ASSOC);

    // Check wilayah access
    if ($user['role'] !== 'admin' && $user['wilayah_id'] != $petugas['id_wilayah']) {
        json_response(['success' => false, 'message' => 'Access denied'], 403);
    }

    // Check if majelis exists and validate wilayah
    $majelisQuery = "SELECT * FROM majelis_dzikir WHERE id = :id_majelis";
    $majelisStmt = $db->prepare($majelisQuery);
    $majelisStmt->bindParam(':id_majelis', $input['id_majelis']);
    $majelisStmt->execute();

    if ($majelisStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Majelis not found'], 404);
    }

    $majelis = $majelisStmt->fetch(PDO::FETCH_ASSOC);

    // Check if majelis is in same wilayah
    if ($petugas['id_wilayah'] != $majelis['wilayah_id']) {
        json_response(['success' => false, 'message' => 'Petugas and majelis must be in same wilayah'], 400);
    }

    // Check for existing assignment on same date
    $checkQuery = "SELECT id FROM penugasan WHERE id_petugas = :id_petugas AND tanggal = :tanggal";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id_petugas', $input['id_petugas']);
    $checkStmt->bindParam(':tanggal', $input['tanggal']);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        json_response(['success' => false, 'message' => 'Petugas already assigned on this date'], 400);
    }

    $tugas = isset($input['tugas']) ? $input['tugas'] : 'manual';

    $query = "INSERT INTO penugasan (id_petugas, id_majelis, tanggal, tugas)
              VALUES (:id_petugas, :id_majelis, :tanggal, :tugas)";

    $stmt = $db->prepare($query);

    $stmt->bindParam(':id_petugas', $input['id_petugas']);
    $stmt->bindParam(':id_majelis', $input['id_majelis']);
    $stmt->bindParam(':tanggal', $input['tanggal']);
    $stmt->bindParam(':tugas', $tugas);

    if ($stmt->execute()) {
        $id = $db->lastInsertId();
        json_response([
            'success' => true,
            'message' => 'Penugasan created successfully',
            'data' => ['id' => $id]
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Failed to create penugasan'], 500);
    }
}

function handlePut($db, $input) {
    if (!isset($_GET['id'])) {
        json_response(['success' => false, 'message' => 'Missing penugasan ID'], 400);
    }

    $id = (int)$_GET['id'];
    $user = get_current_user();

    // Get current penugasan and check access
    $currentQuery = "SELECT p.*, pt.id_wilayah as petugas_wilayah, m.wilayah_id as majelis_wilayah
                     FROM penugasan p
                     JOIN petugas_pentawajuh pt ON p.id_petugas = pt.id
                     JOIN majelis_dzikir m ON p.id_majelis = m.id
                     WHERE p.id = :id";
    $currentStmt = $db->prepare($currentQuery);
    $currentStmt->bindParam(':id', $id);
    $currentStmt->execute();

    if ($currentStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Penugasan not found'], 404);
    }

    $current = $currentStmt->fetch(PDO::FETCH_ASSOC);

    // Check access
    if ($user['role'] !== 'admin' && $user['wilayah_id'] != $current['petugas_wilayah']) {
        json_response(['success' => false, 'message' => 'Access denied'], 403);
    }

    // Validate new assignments
    if (isset($input['id_petugas'])) {
        // Check petugas access and wilayah
        $petugasQuery = "SELECT * FROM petugas_pentawajuh WHERE id = :id_petugas";
        $petugasStmt = $db->prepare($petugasQuery);
        $petugasStmt->bindParam(':id_petugas', $input['id_petugas']);
        $petugasStmt->execute();

        if ($petugasStmt->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Petugas not found'], 404);
        }

        $newPetugas = $petugasStmt->fetch(PDO::FETCH_ASSOC);

        if ($user['role'] !== 'admin' && $user['wilayah_id'] != $newPetugas['id_wilayah']) {
            json_response(['success' => false, 'message' => 'Access denied'], 403);
        }
    }

    if (isset($input['id_majelis'])) {
        // Check majelis
        $majelisQuery = "SELECT * FROM majelis_dzikir WHERE id = :id_majelis";
        $majelisStmt = $db->prepare($majelisQuery);
        $majelisStmt->bindParam(':id_majelis', $input['id_majelis']);
        $majelisStmt->execute();

        if ($majelisStmt->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Majelis not found'], 404);
        }
    }

    $query = "UPDATE penugasan SET";

    $updates = [];
    $params = [':id' => $id];

    if (isset($input['id_petugas'])) {
        $updates[] = " id_petugas = :id_petugas";
        $params[':id_petugas'] = $input['id_petugas'];
    }
    if (isset($input['id_majelis'])) {
        $updates[] = " id_majelis = :id_majelis";
        $params[':id_majelis'] = $input['id_majelis'];
    }
    if (isset($input['tanggal'])) {
        if (!validate_date($input['tanggal'])) {
            json_response(['success' => false, 'message' => 'Invalid date format'], 400);
        }
        $updates[] = " tanggal = :tanggal";
        $params[':tanggal'] = $input['tanggal'];
    }
    if (isset($input['tugas'])) {
        $updates[] = " tugas = :tugas";
        $params[':tugas'] = $input['tugas'];
    }

    if (empty($updates)) {
        json_response(['success' => false, 'message' => 'No fields to update'], 400);
    }

    $query .= implode(',', $updates) . " WHERE id = :id";
    $stmt = $db->prepare($query);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'Penugasan updated successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to update penugasan'], 500);
    }
}

function handleDelete($db) {
    if (!isset($_GET['id'])) {
        json_response(['success' => false, 'message' => 'Missing penugasan ID'], 400);
    }

    $id = (int)$_GET['id'];
    $user = get_current_user();

    // Get current penugasan and check access
    $currentQuery = "SELECT p.*, pt.id_wilayah as petugas_wilayah
                     FROM penugasan p
                     JOIN petugas_pentawajuh pt ON p.id_petugas = pt.id
                     WHERE p.id = :id";
    $currentStmt = $db->prepare($currentQuery);
    $currentStmt->bindParam(':id', $id);
    $currentStmt->execute();

    if ($currentStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Penugasan not found'], 404);
    }

    $current = $currentStmt->fetch(PDO::FETCH_ASSOC);

    // Check access
    if ($user['role'] !== 'admin' && $user['wilayah_id'] != $current['petugas_wilayah']) {
        json_response(['success' => false, 'message' => 'Access denied'], 403);
    }

    // Delete penugasan
    $deleteQuery = "DELETE FROM penugasan WHERE id = :id";
    $stmt = $db->prepare($deleteQuery);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'Penugasan deleted successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to delete penugasan'], 500);
    }
}

function handleAutoAssign($db, $input) {
    if (!isset($input['wilayah_id']) || !isset($input['start_date']) || !isset($input['end_date'])) {
        json_response(['success' => false, 'message' => 'Missing required parameters'], 400);
    }

    $user = get_current_user();

    // Check wilayah access
    if ($user['role'] !== 'admin' && $user['wilayah_id'] != $input['wilayah_id']) {
        json_response(['success' => false, 'message' => 'Access denied'], 403);
    }

    $wilayahId = $input['wilayah_id'];
    $startDate = $input['start_date'];
    $endDate = $input['end_date'];
    $assignmentType = $input['assignment_type'] ?? 'daily';
    $days = $input['days'] ?? [];

    // Validate dates
    if (!validate_date($startDate) || !validate_date($endDate)) {
        json_response(['success' => false, 'message' => 'Invalid date format'], 400);
    }

    if (strtotime($startDate) > strtotime($endDate)) {
        json_response(['success' => false, 'message' => 'Start date cannot be after end date'], 400);
    }

    // Get all petugas in wilayah
    $petugasQuery = "SELECT * FROM petugas_pentawajuh WHERE id_wilayah = :wilayah_id";
    $petugasStmt = $db->prepare($petugasQuery);
    $petugasStmt->bindParam(':wilayah_id', $wilayahId);
    $petugasStmt->execute();
    $petugasList = $petugasStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($petugasList)) {
        json_response(['success' => false, 'message' => 'No petugas found in this wilayah'], 404);
    }

    // Get all majelis in wilayah
    $majelisQuery = "SELECT * FROM majelis_dzikir WHERE wilayah_id = :wilayah_id";
    $majelisStmt = $db->prepare($majelisQuery);
    $majelisStmt->bindParam(':wilayah_id', $wilayahId);
    $majelisStmt->execute();
    $majelisList = $majelisStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($majelisList)) {
        json_response(['success' => false, 'message' => 'No majelis found in this wilayah'], 404);
    }

    // Generate date range
    $dates = generateDateRange($startDate, $endDate, $assignmentType, $days);

    if (empty($dates)) {
        json_response(['success' => false, 'message' => 'No valid dates in range'], 400);
    }

    // Delete existing auto assignments for the date range
    $deleteQuery = "DELETE FROM penugasan
                    WHERE tanggal BETWEEN :start_date AND :end_date
                    AND tugas = 'otomatis'
                    AND id_majelis IN (SELECT id FROM majelis_dzikir WHERE wilayah_id = :wilayah_id)";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':start_date', $startDate);
    $deleteStmt->bindParam(':end_date', $endDate);
    $deleteStmt->bindParam(':wilayah_id', $wilayahId);
    $deleteStmt->execute();

    // Generate assignments for each date
    $assignmentsCreated = 0;
    $errors = [];

    foreach ($dates as $date) {
        try {
            $result = generateDailyAssignments($db, $wilayahId, $date, $petugasList, $majelisList);
            $assignmentsCreated += $result['count'];

            if (!empty($result['errors'])) {
                $errors = array_merge($errors, $result['errors']);
            }
        } catch (Exception $e) {
            $errors[] = "Date $date: " . $e->getMessage();
        }
    }

    $message = "Generated $assignmentsCreated assignments successfully";
    if (!empty($errors)) {
        $message .= ". Some issues occurred: " . implode('; ', array_slice($errors, 0, 3));
        if (count($errors) > 3) {
            $message .= " ... and " . (count($errors) - 3) . " more";
        }
    }

    json_response([
        'success' => true,
        'message' => $message,
        'data' => [
            'assignments_created' => $assignmentsCreated,
            'dates_processed' => count($dates),
            'errors' => $errors
        ]
    ]);
}

function generateDateRange($startDate, $endDate, $type, $days = []) {
    $dates = [];
    $current = strtotime($startDate);
    $end = strtotime($endDate);

    while ($current <= $end) {
        $date = date('Y-m-d', $current);
        $dayOfWeek = date('w', $current);

        if ($type === 'daily' || in_array($dayOfWeek, $days)) {
            $dates[] = $date;
        }

        $current = strtotime('+1 day', $current);
    }

    return $dates;
}

function generateDailyAssignments($db, $wilayahId, $date, $petugasList, $majelisList) {
    $assignmentsCreated = 0;
    $errors = [];

    // Get existing assignments for this date
    $existingQuery = "SELECT id_petugas, id_majelis FROM penugasan WHERE tanggal = :tanggal";
    $existingStmt = $db->prepare($existingQuery);
    $existingStmt->bindParam(':tanggal', $date);
    $existingStmt->execute();
    $existingAssignments = $existingStmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter out unavailable petugas and majelis
    $availablePetugas = array_filter($petugasList, function($petugas) use ($existingAssignments) {
        return !in_array($petugas['id'], array_column($existingAssignments, 'id_petugas'));
    });

    $availableMajelis = array_filter($majelisList, function($majelis) use ($existingAssignments) {
        return !in_array($majelis['id'], array_column($existingAssignments, 'id_majelis'));
    });

    // If no available petugas or majelis, skip
    if (empty($availablePetugas) || empty($availableMajelis)) {
        return ['count' => 0, 'errors' => []];
    }

    // Calculate distance matrix
    $distanceMatrix = calculateDistanceMatrix($availablePetugas, $availableMajelis);

    // Use Hungarian algorithm or greedy approach for assignment
    $assignments = assignPetugasToMajelis($availablePetugas, $availableMajelis, $distanceMatrix);

    // Insert assignments
    $insertQuery = "INSERT INTO penugasan (id_petugas, id_majelis, tanggal, tugas)
                    VALUES (:id_petugas, :id_majelis, :tanggal, 'otomatis')";
    $insertStmt = $db->prepare($insertQuery);

    foreach ($assignments as $assignment) {
        try {
            $insertStmt->execute([
                ':id_petugas' => $assignment['petugas_id'],
                ':id_majelis' => $assignment['majelis_id'],
                ':tanggal' => $date
            ]);
            $assignmentsCreated++;
        } catch (Exception $e) {
            $errors[] = "Failed to assign petugas {$assignment['petugas_id']} to majelis {$assignment['majelis_id']}: " . $e->getMessage();
        }
    }

    return ['count' => $assignmentsCreated, 'errors' => $errors];
}

function calculateDistanceMatrix($petugasList, $majelisList) {
    $matrix = [];

    foreach ($petugasList as $petugas) {
        foreach ($majelisList as $majelis) {
            $distance = calculate_distance(
                $petugas['latitude'], $petugas['longitude'],
                $majelis['latitude'], $majelis['longitude']
            );

            $matrix[$petugas['id']][$majelis['id']] = $distance;
        }
    }

    return $matrix;
}

function assignPetugasToMajelis($petugasList, $majelisList, $distanceMatrix) {
    $assignments = [];
    $usedPetugas = [];
    $usedMajelis = [];

    // Sort all possible assignments by distance
    $allAssignments = [];
    foreach ($petugasList as $petugas) {
        foreach ($majelisList as $majelis) {
            $allAssignments[] = [
                'petugas_id' => $petugas['id'],
                'majelis_id' => $majelis['id'],
                'distance' => $distanceMatrix[$petugas['id']][$majelis['id']],
                'petugas' => $petugas,
                'majelis' => $majelis
            ];
        }
    }

    usort($allAssignments, function($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });

    // Greedy assignment: pick the closest available petugas-majelis pairs
    foreach ($allAssignments as $assignment) {
        if (!in_array($assignment['petugas_id'], $usedPetugas) &&
            !in_array($assignment['majelis_id'], $usedMajelis)) {
            $assignments[] = [
                'petugas_id' => $assignment['petugas_id'],
                'majelis_id' => $assignment['majelis_id']
            ];

            $usedPetugas[] = $assignment['petugas_id'];
            $usedMajelis[] = $assignment['majelis_id'];
        }
    }

    return $assignments;
}
?>