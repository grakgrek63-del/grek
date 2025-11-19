<?php
/**
 * Authentication Functions
 * Majelis Dzikir Management System
 */

require_once 'config/config.php';

/**
 * Check if user is logged in
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

/**
 * Check session timeout
 */
if (!function_exists('check_session_timeout')) {
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
}

/**
 * Get current user info
 */
if (!function_exists('get_current_user')) {
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
}

/**
 * Check if current user is admin
 */
if (!function_exists('is_admin')) {
    function is_admin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

/**
 * Get current user's wilayah (for regional users)
 */
if (!function_exists('get_user_wilayah')) {
    function get_user_wilayah() {
        if (is_admin()) {
            return null; // Admin can access all regions
        }
        return isset($_SESSION['wilayah_id']) ? $_SESSION['wilayah_id'] : null;
    }
}

/**
 * Hash password
 */
if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_ARGON2ID, ['cost' => HASH_COST]);
    }
}

/**
 * Verify password
 */
if (!function_exists('verify_password')) {
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

/**
 * Validate user access to region
 */
if (!function_exists('can_access_wilayah')) {
    function can_access_wilayah($wilayah_id) {
        $user = get_current_user();

        // Admin can access all regions
        if ($user['role'] === 'admin') {
            return true;
        }

        // Regional users can only access their region
        return $user['wilayah_id'] == $wilayah_id;
    }
}

/**
 * Build WHERE clause for regional access
 */
if (!function_exists('build_wilayah_filter')) {
    function build_wilayah_filter($alias = 'wilayah_id') {
        $user = get_current_user();

        if ($user['role'] === 'admin') {
            return ''; // Admin can see all
        }

        return " AND $alias = " . $user['wilayah_id'];
    }
}

/**
 * Require authentication
 */
if (!function_exists('require_auth')) {
    function require_auth() {
        if (!is_logged_in()) {
            redirect('login.php');
        }

        check_session_timeout();
    }
}

/**
 * Require admin role
 */
if (!function_exists('require_admin')) {
    function require_admin() {
        require_auth();

        if ($_SESSION['user_role'] !== 'admin') {
            // Show error page for unauthorized access
            http_response_code(403);
            die('Access Denied: Admin privileges required');
        }
    }
}

/**
 * Login attempt logger
 */
if (!function_exists('log_login_attempt')) {
    function log_login_attempt($username, $success, $ip = null) {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $timestamp = date('Y-m-d H:i:s');
        $status = $success ? 'SUCCESS' : 'FAILED';

        $log_entry = "[$timestamp] $status: $username from $ip\n";
        file_put_contents('logs/auth.log', $log_entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Change user password
 */
if (!function_exists('change_password')) {
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
}

/**
 * Get user by ID (for assignment modal functionality)
 */
if (!function_exists('get_user_by_id')) {
    function get_user_by_id($user_id) {
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT u.*, w.nama_wilayah
                  FROM user u
                  LEFT JOIN wilayah w ON u.wilayah_id = w.id
                  WHERE u.id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

/**
 * Check if a date is valid for assignment (not in past, etc.)
 */
if (!function_exists('is_valid_assignment_date')) {
    function is_valid_assignment_date($date) {
        $today = date('Y-m-d');
        $assignment_date = date('Y-m-d', strtotime($date));

        // Don't allow assignments in the past
        if ($assignment_date < $today) {
            return false;
        }

        // Don't allow assignments more than 1 year in advance
        $max_date = date('Y-m-d', strtotime('+1 year'));
        if ($assignment_date > $max_date) {
            return false;
        }

        return true;
    }
}
?>