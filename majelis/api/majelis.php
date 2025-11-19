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
        // Get single majelis
        $query = "SELECT m.*, w.nama_wilayah
                  FROM majelis_dzikir m
                  JOIN wilayah w ON m.wilayah_id = w.id
                  WHERE m.id = :id";

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
            $majelis = $stmt->fetch(PDO::FETCH_ASSOC);
            json_response(['success' => true, 'data' => $majelis]);
        } else {
            json_response(['success' => false, 'message' => 'Majelis not found'], 404);
        }
    } else {
        // Get all majelis with optional wilayah filter
        $query = "SELECT m.*, w.nama_wilayah
                  FROM majelis_dzikir m
                  JOIN wilayah w ON m.wilayah_id = w.id";

        $params = [];

        // Apply regional access filter
        if ($user['role'] !== 'admin') {
            $query .= " WHERE m.wilayah_id = :wilayah_id";
            $params[':wilayah_id'] = $user['wilayah_id'];
        }

        // Apply wilayah filter if specified
        if ($wilayahId) {
            if ($user['role'] !== 'admin') {
                $query .= " AND m.wilayah_id = :filter_wilayah_id";
            } else {
                $query .= " WHERE m.wilayah_id = :filter_wilayah_id";
            }
            $params[':filter_wilayah_id'] = $wilayahId;
        }

        $query .= " ORDER BY w.nama_wilayah, m.nama_majelis";

        $stmt = $db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $majelis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success' => true, 'data' => $majelis]);
    }
}

function handlePost($db, $input) {
    if (!isset($input['nama_majelis']) || !isset($input['wilayah_id']) ||
        !isset($input['latitude']) || !isset($input['longitude'])) {
        json_response(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    $user = get_current_user();

    // Check wilayah access
    if ($user['role'] !== 'admin' && $user['wilayah_id'] != $input['wilayah_id']) {
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
    $wilayahStmt->bindParam(':wilayah_id', $input['wilayah_id']);
    $wilayahStmt->execute();

    if ($wilayahStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Wilayah not found'], 404);
    }

    $query = "INSERT INTO majelis_dzikir (wilayah_id, nama_majelis, alamat, latitude, longitude)
              VALUES (:wilayah_id, :nama_majelis, :alamat, :latitude, :longitude)";

    $stmt = $db->prepare($query);

    $stmt->bindParam(':wilayah_id', $input['wilayah_id']);
    $stmt->bindParam(':nama_majelis', $input['nama_majelis']);
    $stmt->bindParam(':alamat', $input['alamat']);
    $stmt->bindParam(':latitude', $input['latitude']);
    $stmt->bindParam(':longitude', $input['longitude']);

    if ($stmt->execute()) {
        $id = $db->lastInsertId();
        json_response([
            'success' => true,
            'message' => 'Majelis created successfully',
            'data' => ['id' => $id]
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Failed to create majelis'], 500);
    }
}

function handlePut($db, $input) {
    if (!isset($_GET['id']) || !isset($input['nama_majelis'])) {
        json_response(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    $id = (int)$_GET['id'];
    $user = get_current_user();

    // Get current majelis and check access
    $currentQuery = "SELECT * FROM majelis_dzikir WHERE id = :id";
    $currentStmt = $db->prepare($currentQuery);
    $currentStmt->bindParam(':id', $id);
    $currentStmt->execute();

    if ($currentStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Majelis not found'], 404);
    }

    $current = $currentStmt->fetch(PDO::FETCH_ASSOC);

    // Check access
    if ($user['role'] !== 'admin' && $user['wilayah_id'] != $current['wilayah_id']) {
        json_response(['success' => false, 'message' => 'Access denied'], 403);
    }

    // Validate wilayah_id if provided
    if (isset($input['wilayah_id'])) {
        if ($user['role'] !== 'admin' && $user['wilayah_id'] != $input['wilayah_id']) {
            json_response(['success' => false, 'message' => 'Access denied'], 403);
        }

        $wilayahQuery = "SELECT id FROM wilayah WHERE id = :wilayah_id";
        $wilayahStmt = $db->prepare($wilayahQuery);
        $wilayahStmt->bindParam(':wilayah_id', $input['wilayah_id']);
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

    $query = "UPDATE majelis_dzikir SET nama_majelis = :nama_majelis";

    if (isset($input['wilayah_id'])) {
        $query .= ", wilayah_id = :wilayah_id";
    }
    if (isset($input['alamat'])) {
        $query .= ", alamat = :alamat";
    }
    if (isset($input['latitude'])) {
        $query .= ", latitude = :latitude";
    }
    if (isset($input['longitude'])) {
        $query .= ", longitude = :longitude";
    }

    $query .= " WHERE id = :id";
    $stmt = $db->prepare($query);

    $stmt->bindParam(':nama_majelis', $input['nama_majelis']);
    $stmt->bindParam(':id', $id);

    if (isset($input['wilayah_id'])) {
        $stmt->bindParam(':wilayah_id', $input['wilayah_id']);
    }
    if (isset($input['alamat'])) {
        $stmt->bindParam(':alamat', $input['alamat']);
    }
    if (isset($input['latitude'])) {
        $stmt->bindParam(':latitude', $input['latitude']);
    }
    if (isset($input['longitude'])) {
        $stmt->bindParam(':longitude', $input['longitude']);
    }

    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'Majelis updated successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to update majelis'], 500);
    }
}

function handleDelete($db) {
    if (!isset($_GET['id'])) {
        json_response(['success' => false, 'message' => 'Missing majelis ID'], 400);
    }

    $id = (int)$_GET['id'];
    $user = get_current_user();

    // Get current majelis and check access
    $currentQuery = "SELECT * FROM majelis_dzikir WHERE id = :id";
    $currentStmt = $db->prepare($currentQuery);
    $currentStmt->bindParam(':id', $id);
    $currentStmt->execute();

    if ($currentStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Majelis not found'], 404);
    }

    $current = $currentStmt->fetch(PDO::FETCH_ASSOC);

    // Check access
    if ($user['role'] !== 'admin' && $user['wilayah_id'] != $current['wilayah_id']) {
        json_response(['success' => false, 'message' => 'Access denied'], 403);
    }

    // Check for dependent records (assignments)
    $assignmentQuery = "SELECT COUNT(*) as count FROM penugasan WHERE id_majelis = :id";
    $assignmentStmt = $db->prepare($assignmentQuery);
    $assignmentStmt->bindParam(':id', $id);
    $assignmentStmt->execute();
    $assignmentResult = $assignmentStmt->fetch(PDO::FETCH_ASSOC);

    if ($assignmentResult['count'] > 0) {
        json_response([
            'success' => false,
            'message' => 'Cannot delete majelis. It has existing assignments.'
        ], 400);
    }

    // Delete majelis
    $deleteQuery = "DELETE FROM majelis_dzikir WHERE id = :id";
    $stmt = $db->prepare($deleteQuery);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'Majelis deleted successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to delete majelis'], 500);
    }
}
?>