<?php
/**
 * Authentication Functions
 * Majelis Dzikir Management System
 */

require_once 'config/config.php';

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check session timeout
 */
function check_session_timeout() {
    if (isset($_SESSION['login_time'])) {
        $elapsed = time() - $_SESSION['login_time'];
        if ($elapsed > SESSION_LIFETIME) {
            // Session expired, logout user
            session_unset();
            session_destroy();
            redirect('login.php?expired=1');
        }
    }
}

/**
 * Get current user info
 */
function get_current_user() {
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['user_role'],
        'wilayah_id' => $_SESSION['wilayah_id'] ?? null,
        'nama_wilayah' => $_SESSION['nama_wilayah'] ?? null
    ];
}

/**
 * Check if current user is admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current user's wilayah (for regional users)
 */
function get_user_wilayah() {
    if (is_admin()) {
        return null; // Admin can access all regions
    }
    return isset($_SESSION['wilayah_id']) ? $_SESSION['wilayah_id'] : null;
}

/**
 * Hash password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_ARGON2ID, ['cost' => HASH_COST]);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validate user access to region
 */
function can_access_wilayah($wilayah_id) {
    $user = get_current_user();

    // Admin can access all regions
    if ($user['role'] === 'admin') {
        return true;
    }

    // Regional users can only access their region
    return $user['wilayah_id'] == $wilayah_id;
}

/**
 * Build WHERE clause for regional access
 */
function build_wilayah_filter($alias = 'wilayah_id') {
    $user = get_current_user();

    if ($user['role'] === 'admin') {
        return ''; // Admin can see all
    }

    return " AND $alias = " . $user['wilayah_id'];
}

/**
 * Require authentication
 */
function require_auth() {
    if (!is_logged_in()) {
        redirect('login.php');
    }

    check_session_timeout();
}

/**
 * Require admin role
 */
function require_admin() {
    require_auth();

    if ($_SESSION['user_role'] !== 'admin') {
        // Show error page for unauthorized access
        http_response_code(403);
        die('Access Denied: Admin privileges required');
    }
}

/**
 * Login attempt logger
 */
function log_login_attempt($username, $success, $ip = null) {
    $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FAILED';

    $log_entry = "[$timestamp] $status: $username from $ip\n";
    file_put_contents('logs/auth.log', $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Change user password
 */
function change_password($user_id, $current_password, $new_password) {
    $database = new Database();
    $db = $database->getConnection();

    // Get current password hash
    $query = "SELECT password FROM user WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        return false; // User not found
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        return false; // Current password incorrect
    }

    // Hash new password
    $new_hash = hash_password($new_password);

    // Update password
    $update_query = "UPDATE user SET password = :new_password, updated_at = NOW() WHERE id = :user_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':new_password', $new_hash);
    $update_stmt->bindParam(':user_id', $user_id);

    return $update_stmt->execute();
}
?>