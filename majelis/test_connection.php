<?php
// Simple test script to verify database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn) {
        echo "<p style='color: green;'>✓ Database connection successful!</p>";

        // Test basic query
        $stmt = $conn->query("SELECT COUNT(*) as total FROM wilayah");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>✓ Found {$result['total']} wilayah records</p>";

        // Test majelis table
        $stmt = $conn->query("SELECT COUNT(*) as total FROM majelis_dzikir");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>✓ Found {$result['total']} majelis records</p>";

        // Test petugas table
        $stmt = $conn->query("SELECT COUNT(*) as total FROM petugas_pentawajuh");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>✓ Found {$result['total']} petugas records</p>";

    } else {
        echo "<p style='color: red;'>✗ Database connection failed!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test config constants
echo "<h2>Configuration Test</h2>";
require_once 'config/config.php';

echo "<p>APP_NAME: " . APP_NAME . "</p>";
echo "<p>DEFAULT_LAT: " . DEFAULT_LAT . "</p>";
echo "<p>DEFAULT_LNG: " . DEFAULT_LNG . "</p>";
echo "<p>DEFAULT_ZOOM: " . DEFAULT_ZOOM . "</p>";

echo "<hr>";

// Test session
echo "<h2>Session Test</h2>";
session_start();
echo "<p>Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";

if (isset($_SESSION['user_id'])) {
    echo "<p>Logged in user ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Username: " . ($_SESSION['username'] ?? 'Not set') . "</p>";
    echo "<p>Role: " . ($_SESSION['user_role'] ?? 'Not set') . "</p>";
} else {
    echo "<p>No user logged in</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Application</a></p>";
?>