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
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handlePost($db, $input);
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
    $user = get_current_user();

    if ($id) {
        // Get single petugas
        $query = "SELECT p.*, w.nama_wilayah
                  FROM petugas_pentawajuh p
                  JOIN wilayah w ON p.id_wilayah = w.id
                  WHERE p.id = :id";

        // Check regional access
        if ($user['role'] !== 'admin') {
            $query .= " AND p.id_wilayah = :wilayah_id";
        }

        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($user['role'] !== 'admin') {
            $stmt->bindParam(':wilayah_id', $user['wilayah_id']);
        }

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $petugas = $stmt->fetch(PDO::FETCH_ASSOC);
            json_response(['success' => true, 'data' => $petugas]);
        } else {
            json_response(['success' => false, 'message' => 'Petugas not found'], 404);
        }
    } else {
        // Get all petugas with optional wilayah filter
        $query = "SELECT p.*, w.nama_wilayah
                  FROM petugas_pentawajuh p
                  JOIN wilayah w ON p.id_wilayah = w.id";

        $params = [];

        // Apply regional access filter
        if ($user['role'] !== 'admin') {
            $query .= " WHERE p.id_wilayah = :wilayah_id";
            $params[':wilayah_id'] = $user['wilayah_id'];
        }

        // Apply wilayah filter if specified
        if ($wilayahId) {
            if ($user['role'] !== 'admin') {
                $query .= " AND p.id_wilayah = :filter_wilayah_id";
            } else {
                $query .= " WHERE p.id_wilayah = :filter_wilayah_id";
            }
            $params[':filter_wilayah_id'] = $wilayahId;
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
}

function handlePost($db, $input) {
    if (!isset($input['nama']) || !isset($input['id_wilayah']) ||
        !isset($input['latitude']) || !isset($input['longitude'])) {
        json_response(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    $user = get_current_user();

    // Check wilayah access
    if ($user['role'] !== 'admin' && $user['wilayah_id'] != $input['id_wilayah']) {
        json_response(['success' => false, 'message' => 'Access denied'], 403);
    }

    // Validate coordinates
    if (!is_numeric($input['latitude']) || !is_numeric($input['longitude']) ||
        abs($input['latitude']) > 90 || abs($input['longitude']) > 180) {
        json_response(['success' => false, 'message' => 'Invalid coordinates'], 400);
    }

    // Check if wilayah exists
    $wilayahQuery = "SELECT id FROM wilayah WHERE id = :wilayah_id";
    $wilayahStmt = $db->prepare($wilayahQuery);
    $wilayahStmt->bindParam(':wilayah_id', $input['id_wilayah']);
    $wilayahStmt->execute();

    if ($wilayahStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Wilayah not found'], 404);
    }

    $query = "INSERT INTO petugas_pentawajuh (nama, telepon, latitude, longitude, id_wilayah)
              VALUES (:nama, :telepon, :latitude, :longitude, :id_wilayah)";

    $stmt = $db->prepare($query);

    $stmt->bindParam(':nama', $input['nama']);
    $stmt->bindParam(':telepon', $input['telepon']);
    $stmt->bindParam(':latitude', $input['latitude']);
    $stmt->bindParam(':longitude', $input['longitude']);
    $stmt->bindParam(':id_wilayah', $input['id_wilayah']);

    if ($stmt->execute()) {
        $id = $db->lastInsertId();
        json_response([
            'success' => true,
            'message' => 'Petugas created successfully',
            'data' => ['id' => $id]
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Failed to create petugas'], 500);
    }
}

function handlePut($db, $input) {
    if (!isset($_GET['id']) || !isset($input['nama'])) {
        json_response(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    $id = (int)$_GET['id'];
    $user = get_current_user();

    // Get current petugas and check access
    $currentQuery = "SELECT * FROM petugas_pentawajuh WHERE id = :id";
    $currentStmt = $db->prepare($currentQuery);
    $currentStmt->bindParam(':id', $id);
    $currentStmt->execute();

    if ($currentStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Petugas not found'], 404);
    }

    $current = $currentStmt->fetch(PDO::FETCH_ASSOC);

    // Check access
    if ($user['role'] !== 'admin' && $user['wilayah_id'] != $current['id_wilayah']) {
        json_response(['success' => false, 'message' => 'Access denied'], 403);
    }

    // Validate id_wilayah if provided
    if (isset($input['id_wilayah'])) {
        if ($user['role'] !== 'admin' && $user['wilayah_id'] != $input['id_wilayah']) {
            json_response(['success' => false, 'message' => 'Access denied'], 403);
        }

        $wilayahQuery = "SELECT id FROM wilayah WHERE id = :wilayah_id";
        $wilayahStmt = $db->prepare($wilayahQuery);
        $wilayahStmt->bindParam(':wilayah_id', $input['id_wilayah']);
        $wilayahStmt->execute();

        if ($wilayahStmt->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Wilayah not found'], 404);
        }
    }

    // Validate coordinates if provided
    if (isset($input['latitude']) && isset($input['longitude'])) {
        if (!is_numeric($input['latitude']) || !is_numeric($input['longitude']) ||
            abs($input['latitude']) > 90 || abs($input['longitude']) > 180) {
            json_response(['success' => false, 'message' => 'Invalid coordinates'], 400);
        }
    }

    $query = "UPDATE petugas_pentawajuh SET nama = :nama";

    if (isset($input['telepon'])) {
        $query .= ", telepon = :telepon";
    }
    if (isset($input['id_wilayah'])) {
        $query .= ", id_wilayah = :id_wilayah";
    }
    if (isset($input['latitude'])) {
        $query .= ", latitude = :latitude";
    }
    if (isset($input['longitude'])) {
        $query .= ", longitude = :longitude";
    }

    $query .= " WHERE id = :id";
    $stmt = $db->prepare($query);

    $stmt->bindParam(':nama', $input['nama']);
    $stmt->bindParam(':id', $id);

    if (isset($input['telepon'])) {
        $stmt->bindParam(':telepon', $input['telepon']);
    }
    if (isset($input['id_wilayah'])) {
        $stmt->bindParam(':id_wilayah', $input['id_wilayah']);
    }
    if (isset($input['latitude'])) {
        $stmt->bindParam(':latitude', $input['latitude']);
    }
    if (isset($input['longitude'])) {
        $stmt->bindParam(':longitude', $input['longitude']);
    }

    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'Petugas updated successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to update petugas'], 500);
    }
}

function handleDelete($db) {
    if (!isset($_GET['id'])) {
        json_response(['success' => false, 'message' => 'Missing petugas ID'], 400);
    }

    $id = (int)$_GET['id'];
    $user = get_current_user();

    // Get current petugas and check access
    $currentQuery = "SELECT * FROM petugas_pentawajuh WHERE id = :id";
    $currentStmt = $db->prepare($currentQuery);
    $currentStmt->bindParam(':id', $id);
    $currentStmt->execute();

    if ($currentStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Petugas not found'], 404);
    }

    $current = $currentStmt->fetch(PDO::FETCH_ASSOC);

    // Check access
    if ($user['role'] !== 'admin' && $user['wilayah_id'] != $current['id_wilayah']) {
        json_response(['success' => false, 'message' => 'Access denied'], 403);
    }

    // Check for future assignments
    $assignmentQuery = "SELECT COUNT(*) as count FROM penugasan WHERE id_petugas = :id AND tanggal >= CURDATE()";
    $assignmentStmt = $db->prepare($assignmentQuery);
    $assignmentStmt->bindParam(':id', $id);
    $assignmentStmt->execute();
    $assignmentResult = $assignmentStmt->fetch(PDO::FETCH_ASSOC);

    if ($assignmentResult['count'] > 0) {
        json_response([
            'success' => false,
            'message' => 'Cannot delete petugas. Has future assignments.'
        ], 400);
    }

    // Delete petugas
    $deleteQuery = "DELETE FROM petugas_pentawajuh WHERE id = :id";
    $stmt = $db->prepare($deleteQuery);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'Petugas deleted successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to delete petugas'], 500);
    }
}
?>