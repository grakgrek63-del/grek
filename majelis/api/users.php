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
        case 'POST':
            if (isset($input['action']) && $input['action'] === 'change_password') {
                handleChangePassword($db, $input);
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
    require_admin();

    $query = "SELECT u.*, w.nama_wilayah
              FROM user u
              LEFT JOIN wilayah w ON u.wilayah_id = w.id
              ORDER BY u.username";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response(['success' => true, 'data' => $users]);
}

function handlePost($db, $input) {
    require_admin();

    if (!isset($input['username']) || !isset($input['password']) || !isset($input['role'])) {
        json_response(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    // Validate input
    if (strlen($input['username']) < 3) {
        json_response(['success' => false, 'message' => 'Username must be at least 3 characters'], 400);
    }

    if (strlen($input['password']) < PASSWORD_MIN_LENGTH) {
        json_response(['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'], 400);
    }

    if (!in_array($input['role'], ['admin', 'regional'])) {
        json_response(['success' => false, 'message' => 'Invalid role'], 400);
    }

    // Check if username already exists
    $checkQuery = "SELECT id FROM user WHERE username = :username";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':username', $input['username']);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        json_response(['success' => false, 'message' => 'Username already exists'], 400);
    }

    // Validate wilayah_id if role is regional
    $wilayahId = null;
    if ($input['role'] === 'regional') {
        if (!isset($input['wilayah_id']) || empty($input['wilayah_id'])) {
            json_response(['success' => false, 'message' => 'Wilayah is required for regional users'], 400);
        }

        // Check if wilayah exists
        $wilayahQuery = "SELECT id FROM wilayah WHERE id = :wilayah_id";
        $wilayahStmt = $db->prepare($wilayahQuery);
        $wilayahStmt->bindParam(':wilayah_id', $input['wilayah_id']);
        $wilayahStmt->execute();

        if ($wilayahStmt->rowCount() === 0) {
            json_response(['success' => false, 'message' => 'Invalid wilayah'], 400);
        }

        $wilayahId = (int)$input['wilayah_id'];
    }

    // Hash password
    $hashedPassword = hash_password($input['password']);

    // Insert user
    $query = "INSERT INTO user (username, password, role, wilayah_id, created_at)
              VALUES (:username, :password, :role, :wilayah_id, NOW())";
    $stmt = $db->prepare($query);

    $stmt->bindParam(':username', $input['username']);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':role', $input['role']);
    $stmt->bindParam(':wilayah_id', $wilayahId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $id = $db->lastInsertId();
        json_response([
            'success' => true,
            'message' => 'User created successfully',
            'data' => ['id' => $id]
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Failed to create user'], 500);
    }
}

function handlePut($db, $input) {
    require_admin();

    if (!isset($_GET['id'])) {
        json_response(['success' => false, 'message' => 'Missing user ID'], 400);
    }

    $id = (int)$_GET['id'];

    // Check if user exists
    $checkQuery = "SELECT * FROM user WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'User not found'], 404);
    }

    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

    // Prevent editing admin users (safety measure)
    if ($user['role'] === 'admin' && $user['id'] != $_SESSION['user_id']) {
        json_response(['success' => false, 'message' => 'Cannot edit other admin users'], 403);
    }

    $query = "UPDATE user SET updated_at = NOW()";
    $params = [];

    // Update password if provided
    if (isset($input['password']) && !empty($input['password'])) {
        if (strlen($input['password']) < PASSWORD_MIN_LENGTH) {
            json_response(['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'], 400);
        }
        $query .= ", password = :password";
        $params[':password'] = hash_password($input['password']);
    }

    // Update role if provided
    if (isset($input['role'])) {
        if (!in_array($input['role'], ['admin', 'regional'])) {
            json_response(['success' => false, 'message' => 'Invalid role'], 400);
        }
        $query .= ", role = :role";
        $params[':role'] = $input['role'];

        // Update wilayah_id for regional users
        if ($input['role'] === 'regional') {
            if (!isset($input['wilayah_id']) || empty($input['wilayah_id'])) {
                json_response(['success' => false, 'message' => 'Wilayah is required for regional users'], 400);
            }
            $query .= ", wilayah_id = :wilayah_id";
            $params[':wilayah_id'] = (int)$input['wilayah_id'];
        } else {
            $query .= ", wilayah_id = NULL";
        }
    }

    $query .= " WHERE id = :id";
    $params[':id'] = $id;

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'User updated successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to update user'], 500);
    }
}

function handleDelete($db) {
    require_admin();

    if (!isset($_GET['id'])) {
        json_response(['success' => false, 'message' => 'Missing user ID'], 400);
    }

    $id = (int)$_GET['id'];

    // Prevent self-deletion
    if ($id == $_SESSION['user_id']) {
        json_response(['success' => false, 'message' => 'Cannot delete your own account'], 400);
    }

    // Check if user exists and is not admin
    $checkQuery = "SELECT * FROM user WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'User not found'], 404);
    }

    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

    // Prevent deletion of admin users
    if ($user['role'] === 'admin') {
        json_response(['success' => false, 'message' => 'Cannot delete admin users'], 403);
    }

    // Delete user
    $deleteQuery = "DELETE FROM user WHERE id = :id";
    $stmt = $db->prepare($deleteQuery);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to delete user'], 500);
    }
}
?>