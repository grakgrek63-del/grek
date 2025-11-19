<?php
/**
 * Debug API Script
 * Test all API endpoints to identify issues
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

echo "<h2>API Endpoint Debug</h2>";

// Test authentication first
session_start();

echo "<h3>Session Status</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>User logged in: " . (isset($_SESSION['user_id']) ? 'Yes' : 'No') . "</p>";

if (isset($_SESSION['user_id'])) {
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Username: " . ($_SESSION['username'] ?? 'Not set') . "</p>";
    echo "<p>Role: " . ($_SESSION['user_role'] ?? 'Not set') . "</p>";
    echo "<p>Wilayah ID: " . ($_SESSION['wilayah_id'] ?? 'Not set') . "</p>";
}

// Test database connection
echo "<h3>Database Connection</h3>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connected successfully</p>";

    // Test basic queries
    $tables = ['wilayah', 'majelis_dzikir', 'petugas_pentawajuh', 'penugasan', 'user'];
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>✓ Table '$table': {$result['count']} records</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test API endpoints
echo "<h3>API Endpoint Tests</h3>";

$endpoints = [
    'api/wilayah.php' => 'Wilayah API',
    'api/majelis.php' => 'Majelis API',
    'api/petugas.php' => 'Petugas API',
    'api/penugasan.php' => 'Penugasan API',
    'api/statistics.php' => 'Statistics API'
];

foreach ($endpoints as $endpoint => $name) {
    echo "<h4>$name</h4>";

    $url = $endpoint;
    if (strpos($endpoint, '?') === false) {
        $url .= '?debug=1';
    } else {
        $url .= '&debug=1';
    }

    echo "<p>Testing: <a href='$url' target='_blank'>$url</a></p>";

    // Use curl to test
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    // Add session cookie if available
    if (session_id()) {
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        echo "<p style='color: red;'>✗ CURL Error: $curl_error</p>";
    } else {
        echo "<p>HTTP Status: $http_code</p>";

        if ($http_code === 200) {
            echo "<p style='color: green;'>✓ Success</p>";
            // Try to decode JSON
            $data = json_decode($response, true);
            if ($data) {
                echo "<p>✓ Valid JSON response</p>";
                if (isset($data['success'])) {
                    echo "<p>Success: " . ($data['success'] ? 'Yes' : 'No') . "</p>";
                    if (!$data['success'] && isset($data['message'])) {
                        echo "<p style='color: orange;'>Message: " . htmlspecialchars($data['message']) . "</p>";
                    }
                }
                if (isset($data['data']) && is_array($data['data'])) {
                    echo "<p>Data records: " . count($data['data']) . "</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠ Invalid JSON response</p>";
                echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "...</pre>";
            }
        } else {
            echo "<p style='color: red;'>✗ HTTP Error $http_code</p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "...</pre>";
        }
    }
    echo "<hr>";
}

echo "<h3>Next Steps</h3>";
echo "<p>If you see any errors above, here are some things to check:</p>";
echo "<ul>";
echo "<li>Make sure you are logged in to the system</li>";
echo "<li>Check database credentials in config/database.php</li>";
echo "<li>Verify the database exists and has the required tables</li>";
echo "<li>Check file permissions for the API files</li>";
echo "</ul>";

echo "<p><a href='index.php'>Back to Application</a></p>";
?>