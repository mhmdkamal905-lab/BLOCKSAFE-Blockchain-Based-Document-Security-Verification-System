<?php
$pageTitle = $pageTitle ?? APP_NAME;
$notice = get_flash();

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>assets/style.css" rel="stylesheet">

    <meta name="theme-color" content="#101827">
    <meta name="description" content="BlockSafe Ethereum - Sistem penyimpanan dan verifikasi dokumen digital berbasis blockchain.">
</head>

<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
    <div class="container">

        <!-- BRAND -->
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold"
           href="<?= BASE_URL ?>/index.php">

            <span class="d-flex align-items-center justify-content-center rounded-3 bg-primary"
                  style="width:40px;height:40px;">
                <i class="bi bi-shield-lock-fill text-white fs-5"></i>
            </span>

            <span>
                BlockSafe
                <small class="d-block text-secondary fw-normal"
                       style="font-size:10px; letter-spacing:1.5px;">
                    ETHEREUM SECURITY
                </small>
            </span>
        </a>

        <!-- MOBILE BUTTON -->
        <button class="navbar-toggler border-0 shadow-none"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavbar"
                aria-controls="mainNavbar"
                aria-expanded="false"
                aria-label="Buka navigasi">

            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- NAVIGATION -->
        <div class="collapse navbar-collapse" id="mainNavbar">

            <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2 py-3 py-lg-0">

                <?php if (is_logged_in()): ?>

                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/index.php">
                        <i class="bi bi-grid me-1"></i>
                        Dashboard
                    </a>

                    <a class="nav-link <?= $currentPage === 'upload.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/upload.php">
                        <i class="bi bi-cloud-upload me-1"></i>
                        Simpan Dokumen
                    </a>

                    <a class="nav-link <?= $currentPage === 'verify.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/verify.php">
                        <i class="bi bi-patch-check me-1"></i>
                        Verifikasi
                    </a>

                    <!-- USER PROFILE -->
                    <div class="dropdown ms-lg-3">

                        <button class="btn btn-outline-light btn-sm dropdown-toggle rounded-pill px-3"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">

                            <i class="bi bi-person-circle me-1"></i>
                            Akun Saya
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">

                            <li>
                                <h6 class="dropdown-header">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Akun BlockSafe
                                </h6>
                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item text-danger"
                                   href="<?= BASE_URL ?>/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Keluar
                                </a>
                            </li>

                        </ul>
                    </div>

                <?php else: ?>

                    <a class="nav-link <?= $currentPage === 'login.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Login
                    </a>

                    <a class="btn btn-primary btn-sm rounded-pill px-4 ms-lg-2"
                       href="<?= BASE_URL ?>/register.php">
                        <i class="bi bi-person-plus me-1"></i>
                        Daftar Sekarang
                    </a>

                <?php endif; ?>

            </div>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<main class="container py-4">

    <!-- FLASH NOTIFICATION -->
    <?php if ($notice): ?>

        <?php
        $allowedTypes = ['success', 'danger', 'warning', 'info'];
        $noticeType = in_array($notice['type'], $allowedTypes, true)
            ? $notice['type']
            : 'info';
        ?>

        <div class="alert alert-<?= e($noticeType) ?> alert-dismissible fade show shadow-sm border-0 rounded-3"
             role="alert">

            <div class="d-flex align-items-center gap-2">

                <i class="bi bi-info-circle-fill fs-5"></i>

                <div>
                    <?= e($notice['message']) ?>
                </div>

            </div>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Tutup">
            </button>

        </div>

    <?php endif; ?>