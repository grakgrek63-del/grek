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
    $user = get_current_user();

    if ($id) {
        // Get single wilayah
        $query = "SELECT * FROM wilayah WHERE id = :id";

        // Check regional access
        if ($user['role'] !== 'admin') {
            $query .= " AND id = :wilayah_id";
        }

        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($user['role'] !== 'admin') {
            $stmt->bindParam(':wilayah_id', $user['wilayah_id']);
        }

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $wilayah = $stmt->fetch(PDO::FETCH_ASSOC);
            json_response(['success' => true, 'data' => $wilayah]);
        } else {
            json_response(['success' => false, 'message' => 'Wilayah not found'], 404);
        }
    } else {
        // Get all wilayah
        $query = "SELECT * FROM wilayah ORDER BY nama_wilayah";

        // Regional users only see their wilayah
        if ($user['role'] !== 'admin') {
            $query .= " LIMIT 1";
        }

        $stmt = $db->prepare($query);

        if ($user['role'] !== 'admin') {
            $stmt->bindParam(':wilayah_id', $user['wilayah_id']);
        }

        $stmt->execute();
        $wilayah = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $wilayah]);
    }
}

function handlePost($db, $input) {
    require_admin();

    if (!isset($input['nama_wilayah']) || !isset($input['polygon'])) {
        json_response(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    // Validate polygon JSON
    $polygon = json_decode($input['polygon']);
    if (!$polygon) {
        json_response(['success' => false, 'message' => 'Invalid polygon format'], 400);
    }

    $query = "INSERT INTO wilayah (nama_wilayah, polygon) VALUES (:nama_wilayah, :polygon)";
    $stmt = $db->prepare($query);

    $stmt->bindParam(':nama_wilayah', $input['nama_wilayah']);
    $stmt->bindParam(':polygon', $input['polygon']);

    if ($stmt->execute()) {
        $id = $db->lastInsertId();
        json_response([
            'success' => true,
            'message' => 'Wilayah created successfully',
            'data' => ['id' => $id]
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Failed to create wilayah'], 500);
    }
}

function handlePut($db, $input) {
    require_admin();

    if (!isset($_GET['id']) || !isset($input['nama_wilayah'])) {
        json_response(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    $id = (int)$_GET['id'];

    // Check if wilayah exists
    $checkQuery = "SELECT id FROM wilayah WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Wilayah not found'], 404);
    }

    $query = "UPDATE wilayah SET nama_wilayah = :nama_wilayah";

    if (isset($input['polygon'])) {
        // Validate polygon JSON
        $polygon = json_decode($input['polygon']);
        if (!$polygon) {
            json_response(['success' => false, 'message' => 'Invalid polygon format'], 400);
        }
        $query .= ", polygon = :polygon";
    }

    $query .= " WHERE id = :id";
    $stmt = $db->prepare($query);

    $stmt->bindParam(':nama_wilayah', $input['nama_wilayah']);
    $stmt->bindParam(':id', $id);

    if (isset($input['polygon'])) {
        $stmt->bindParam(':polygon', $input['polygon']);
    }

    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'Wilayah updated successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to update wilayah'], 500);
    }
}

function handleDelete($db) {
    require_admin();

    if (!isset($_GET['id'])) {
        json_response(['success' => false, 'message' => 'Missing wilayah ID'], 400);
    }

    $id = (int)$_GET['id'];

    // Check for dependent records
    $queries = [
        "SELECT COUNT(*) as count FROM majelis_dzikir WHERE wilayah_id = :id",
        "SELECT COUNT(*) as count FROM petugas_pentawajuh WHERE id_wilayah = :id",
        "SELECT COUNT(*) as count FROM user WHERE wilayah_id = :id AND role != 'admin'"
    ];

    foreach ($queries as $query) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            json_response([
                'success' => false,
                'message' => 'Cannot delete wilayah. It has dependent records.'
            ], 400);
        }
    }

    // Delete wilayah
    $deleteQuery = "DELETE FROM wilayah WHERE id = :id";
    $stmt = $db->prepare($deleteQuery);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'Wilayah deleted successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to delete wilayah'], 500);
    }
}
?>