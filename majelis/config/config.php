<?php
/**
 * Application Configuration
 * Majelis Dzikir Management System
 */

// Start session
session_start();

// Database configuration
require_once 'database.php';

// Application settings
define('APP_NAME', 'Sistem Manajemen Majelis Dzikir');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/majelis');

// Security settings
define('SESSION_LIFETIME', 86400); // 24 hours
define('PASSWORD_MIN_LENGTH', 6);
define('HASH_COST', 12);

// Map settings
define('DEFAULT_LAT', -2.548926);
define('DEFAULT_LNG', 118.014863);
define('DEFAULT_ZOOM', 5);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);

// File upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('UPLOAD_PATH', 'uploads/');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Helper functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function calculate_distance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // Earth's radius in kilometers

    $lat1_rad = deg2rad($lat1);
    $lon1_rad = deg2rad($lon1);
    $lat2_rad = deg2rad($lat2);
    $lon2_rad = deg2rad($lon2);

    $lat_diff = $lat2_rad - $lat1_rad;
    $lon_diff = $lon2_rad - $lon1_rad;

    $a = sin($lat_diff/2) * sin($lat_diff/2) +
         cos($lat1_rad) * cos($lat2_rad) *
         sin($lon_diff/2) * sin($lon_diff/2);

    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $earth_radius * $c;
}

function format_date($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

function get_day_name($date) {
    $days = ['Ahad', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $days[date('w', strtotime($date))];
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function get_user_wilayah() {
    if (is_admin()) {
        return null; // Admin can access all regions
    }
    return isset($_SESSION['wilayah_id']) ? $_SESSION['wilayah_id'] : null;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

function json_response($data, $status_code = 200) {
    header('Content-Type: application/json');
    http_response_code($status_code);
    echo json_encode($data);
    exit();
}
?>