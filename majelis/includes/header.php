<?php
require_once 'includes/auth.php';
require_auth();

$user = get_current_user();
// Ensure user is an array to prevent warnings
if (!is_array($user)) {
    $user = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : APP_NAME; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet Draw CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/custom.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mosque me-2"></i>
                <?php echo APP_NAME; ?>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                           href="index.php">
                            <i class="fas fa-map me-1"></i>Dashboard
                        </a>
                    </li>
                    <?php if (is_admin()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="manageDropdown" role="button"
                               data-bs-toggle="dropdown">
                                <i class="fas fa-cogs me-1"></i>Manajemen Data
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="openWilayahModal()">
                                    <i class="fas fa-map-marked-alt me-2"></i>Wilayah
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="openMajelisModal()">
                                    <i class="fas fa-mosque me-2"></i>Majelis Dzikir
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="openPetugasModal()">
                                    <i class="fas fa-user-tie me-2"></i>Petugas Pentawajuh
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="openUserModal()">
                                    <i class="fas fa-users me-2"></i>Manajemen User
                                </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>"
                           href="laporan.php">
                            <i class="fas fa-file-excel me-1"></i>Laporan
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                           data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($user['username'] ?? 'User'); ?>
                            <span class="badge bg-<?php echo ($user['role'] ?? '') == 'admin' ? 'danger' : 'info'; ?> ms-1">
                                <?php echo ucfirst($user['role'] ?? 'user'); ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (!empty($user['nama_wilayah'])): ?>
                                <li><span class="dropdown-item-text text-muted">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?php echo htmlspecialchars($user['nama_wilayah']); ?>
                                </span></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="#" onclick="openChangePasswordModal()">
                                <i class="fas fa-key me-2"></i>Ganti Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container-fluid py-3">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>