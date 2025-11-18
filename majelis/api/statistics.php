<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/config.php';
require_auth();

$database = new Database();
$db = $database->getConnection();

try {
    $user = get_current_user();
    $wilayahId = isset($_GET['wilayah_id']) ? (int)$_GET['wilayah_id'] : null;

    // Base WHERE conditions for regional access
    $wilayahCondition = "";
    $params = [];

    if ($user['role'] !== 'admin') {
        $wilayahCondition = " WHERE wilayah_id = :user_wilayah_id";
        $params[':user_wilayah_id'] = $user['wilayah_id'];
    } elseif ($wilayahId) {
        $wilayahCondition = " WHERE wilayah_id = :wilayah_id";
        $params[':wilayah_id'] = $wilayahId;
    }

    // Get total majelis
    $majelisQuery = "SELECT COUNT(*) as total FROM majelis_dzikir" . $wilayahCondition;
    $majelisStmt = $db->prepare($majelisQuery);
    foreach ($params as $key => $value) {
        $majelisStmt->bindValue($key, $value);
    }
    $majelisStmt->execute();
    $totalMajelis = $majelisStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get total petugas
    $petugasQuery = "SELECT COUNT(*) as total FROM petugas_pentawajuh";
    $petugasWhereCondition = str_replace('wilayah_id', 'id_wilayah', $wilayahCondition);
    $petugasQuery .= $petugasWhereCondition;
    $petugasStmt = $db->prepare($petugasQuery);
    foreach ($params as $key => $value) {
        $newKey = str_replace('wilayah_id', 'id_wilayah', $key);
        $petugasStmt->bindValue($newKey, $value);
    }
    $petugasStmt->execute();
    $totalPetugas = $petugasStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get total penugasan
    $penugasanQuery = "SELECT COUNT(*) as total FROM penugasan p
                       JOIN majelis_dzikir m ON p.id_majelis = m.id" .
                       str_replace('WHERE', ' WHERE', $wilayahCondition);
    $penugasanStmt = $db->prepare($penugasanQuery);
    foreach ($params as $key => $value) {
        $penugasanStmt->bindValue($key, $value);
    }
    $penugasanStmt->execute();
    $totalPenugasan = $penugasanStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get today's assignments
    $todayQuery = "SELECT COUNT(*) as total FROM penugasan p
                   JOIN majelis_dzikir m ON p.id_majelis = m.id
                   WHERE p.tanggal = CURDATE()";

    if ($user['role'] !== 'admin') {
        $todayQuery .= " AND m.wilayah_id = :user_wilayah_id";
        $todayParams = [':user_wilayah_id' => $user['wilayah_id']];
    } elseif ($wilayahId) {
        $todayQuery .= " AND m.wilayah_id = :wilayah_id";
        $todayParams = [':wilayah_id' => $wilayahId];
    }

    $todayStmt = $db->prepare($todayQuery);
    if (isset($todayParams)) {
        foreach ($todayParams as $key => $value) {
            $todayStmt->bindValue($key, $value);
        }
    }
    $todayStmt->execute();
    $todayAssignments = $todayStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get assignments this week
    $weekQuery = "SELECT COUNT(*) as total FROM penugasan p
                  JOIN majelis_dzikir m ON p.id_majelis = m.id
                  WHERE p.tanggal BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";

    if ($user['role'] !== 'admin') {
        $weekQuery .= " AND m.wilayah_id = :user_wilayah_id";
        $weekParams = [':user_wilayah_id' => $user['wilayah_id']];
    } elseif ($wilayahId) {
        $weekQuery .= " AND m.wilayah_id = :wilayah_id";
        $weekParams = [':wilayah_id' => $wilayahId];
    }

    $weekStmt = $db->prepare($weekQuery);
    if (isset($weekParams)) {
        foreach ($weekParams as $key => $value) {
            $weekStmt->bindValue($key, $value);
        }
    }
    $weekStmt->execute();
    $weekAssignments = $weekStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get assignments this month
    $monthQuery = "SELECT COUNT(*) as total FROM penugasan p
                   JOIN majelis_dzikir m ON p.id_majelis = m.id
                   WHERE YEAR(p.tanggal) = YEAR(CURDATE()) AND MONTH(p.tanggal) = MONTH(CURDATE())";

    if ($user['role'] !== 'admin') {
        $monthQuery .= " AND m.wilayah_id = :user_wilayah_id";
        $monthParams = [':user_wilayah_id' => $user['wilayah_id']];
    } elseif ($wilayahId) {
        $monthQuery .= " AND m.wilayah_id = :wilayah_id";
        $monthParams = [':wilayah_id' => $wilayahId];
    }

    $monthStmt = $db->prepare($monthQuery);
    if (isset($monthParams)) {
        foreach ($monthParams as $key => $value) {
            $monthStmt->bindValue($key, $value);
        }
    }
    $monthStmt->execute();
    $monthAssignments = $monthStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get top 5 most assigned petugas
    $topPetugasQuery = "SELECT pt.nama, COUNT(p.id) as assignment_count
                        FROM penugasan p
                        JOIN petugas_pentawajuh pt ON p.id_petugas = pt.id
                        JOIN majelis_dzikir m ON p.id_majelis = m.id
                        WHERE YEAR(p.tanggal) = YEAR(CURDATE()) AND MONTH(p.tanggal) = MONTH(CURDATE())";

    if ($user['role'] !== 'admin') {
        $topPetugasQuery .= " AND m.wilayah_id = :user_wilayah_id";
        $topPetugasParams = [':user_wilayah_id' => $user['wilayah_id']];
    } elseif ($wilayahId) {
        $topPetugasQuery .= " AND m.wilayah_id = :wilayah_id";
        $topPetugasParams = [':wilayah_id' => $wilayahId];
    }

    $topPetugasQuery .= " GROUP BY pt.id ORDER BY assignment_count DESC LIMIT 5";

    $topPetugasStmt = $db->prepare($topPetugasQuery);
    if (isset($topPetugasParams)) {
        foreach ($topPetugasParams as $key => $value) {
            $topPetugasStmt->bindValue($key, $value);
        }
    }
    $topPetugasStmt->execute();
    $topPetugas = $topPetugasStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent assignments
    $recentQuery = "SELECT p.*, pt.nama as nama_petugas, m.nama_majelis, w.nama_wilayah
                    FROM penugasan p
                    JOIN petugas_pentawajuh pt ON p.id_petugas = pt.id
                    JOIN majelis_dzikir m ON p.id_majelis = m.id
                    JOIN wilayah w ON m.wilayah_id = w.id
                    ORDER BY p.created_at DESC LIMIT 10";

    if ($user['role'] !== 'admin') {
        $recentQuery = "SELECT p.*, pt.nama as nama_petugas, m.nama_majelis, w.nama_wilayah
                       FROM penugasan p
                       JOIN petugas_pentawajuh pt ON p.id_petugas = pt.id
                       JOIN majelis_dzikir m ON p.id_majelis = m.id
                       JOIN wilayah w ON m.wilayah_id = w.id
                       WHERE m.wilayah_id = :user_wilayah_id
                       ORDER BY p.created_at DESC LIMIT 10";
        $recentParams = [':user_wilayah_id' => $user['wilayah_id']];
    } elseif ($wilayahId) {
        $recentQuery = "SELECT p.*, pt.nama as nama_petugas, m.nama_majelis, w.nama_wilayah
                       FROM penugasan p
                       JOIN petugas_pentawajuh pt ON p.id_petugas = pt.id
                       JOIN majelis_dzikir m ON p.id_majelis = m.id
                       JOIN wilayah w ON m.wilayah_id = w.id
                       WHERE m.wilayah_id = :wilayah_id
                       ORDER BY p.created_at DESC LIMIT 10";
        $recentParams = [':wilayah_id' => $wilayahId];
    }

    $recentStmt = $db->prepare($recentQuery);
    if (isset($recentParams)) {
        foreach ($recentParams as $key => $value) {
            $recentStmt->bindValue($key, $value);
        }
    }
    $recentStmt->execute();
    $recentAssignments = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    json_response([
        'success' => true,
        'data' => [
            'total_majelis' => (int)$totalMajelis,
            'total_petugas' => (int)$totalPetugas,
            'total_penugasan' => (int)$totalPenugasan,
            'today_assignments' => (int)$todayAssignments,
            'week_assignments' => (int)$weekAssignments,
            'month_assignments' => (int)$monthAssignments,
            'top_petugas' => $topPetugas,
            'recent_assignments' => $recentAssignments
        ]
    ]);

} catch (Exception $e) {
    json_response(['message' => 'Server error: ' . $e->getMessage()], 500);
}
?>