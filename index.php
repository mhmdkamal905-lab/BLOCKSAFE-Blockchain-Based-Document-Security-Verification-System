<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
| Pengguna wajib login sebelum membuka dashboard.
*/
require_login();

$userId = (int) $_SESSION['user']['id'];

$userName = $_SESSION['user']['full_name'] ?? 'Pengguna';

$walletAddress = $_SESSION['user']['wallet_address'] ?? null;


/*
|--------------------------------------------------------------------------
| STATISTIK DOKUMEN
|--------------------------------------------------------------------------
*/

// Total dokumen milik pengguna yang sedang login
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM documents
    WHERE user_id = :user_id
");
$stmt->execute(['user_id' => $userId]);
$totalDocuments = (int) $stmt->fetchColumn();

// Total verifikasi valid
// Jika tabel verification_logs belum tersedia, nilai sementara 0.
$totalVerified = 0;

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM verification_logs vl
        INNER JOIN documents d ON d.id = vl.document_id
        WHERE d.user_id = :user_id
        AND vl.verification_status = 'VALID'
    ");
    $stmt->execute(['user_id' => $userId]);
    $totalVerified = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    $totalVerified = 0;
}

// Total dokumen yang sudah tercatat di blockchain
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM documents
    WHERE user_id = :user_id
    AND blockchain_status = 'CONFIRMED'
");
$stmt->execute(['user_id' => $userId]);
$totalBlockchain = (int) $stmt->fetchColumn();

// Total dokumen pending
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM documents
    WHERE user_id = :user_id
    AND blockchain_status = 'PENDING'
");
$stmt->execute(['user_id' => $userId]);
$totalPending = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| DAFTAR DOKUMEN
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        id,
        document_name,
        stored_name,
        mime_type,
        file_size,
        file_hash,
        blockchain_status,
        transaction_hash,
        created_at
    FROM documents
    WHERE user_id = :user_id
    ORDER BY id DESC
");
$stmt->execute(['user_id' => $userId]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/
function formatFileSize(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }

    return $bytes . ' B';
}

function statusBadge(string $status): string
{
    return match ($status) {
        'CONFIRMED' => '<span class="status status-confirmed">Confirmed</span>',
        'FAILED' => '<span class="status status-failed">Failed</span>',
        default => '<span class="status status-pending">Pending</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard | BLOCKSAFE</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="app-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <a href="index.php" class="brand">
            <span class="brand-icon">B</span>
            <span>BLOCK<span class="brand-accent">SAFE</span></span>
        </a>

        <div class="sidebar-label">MENU UTAMA</div>

        <nav class="sidebar-menu">
            <a href="index.php" class="menu-item active">
                <span class="menu-icon">▦</span>
                Dashboard
            </a>

            <a href="#upload" class="menu-item">
                <span class="menu-icon">↑</span>
                Upload Dokumen
            </a>

            <a href="#documents" class="menu-item">
                <span class="menu-icon">▤</span>
                Data Dokumen
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="security-card">
                <div class="security-icon">✓</div>
                <strong>Secure Storage</strong>
                <small>Perlindungan integritas dokumen dengan SHA-256.</small>
            </div>

            <a href="logout.php" class="logout-link"
               onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                <span class="menu-icon">↪</span>
                Keluar Sistem
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">
            <div>
                <div class="breadcrumb-text">Workspace / Dashboard</div>
                <h1>Dashboard</h1>
            </div>

            <div class="profile-area">
                <div class="profile-avatar">
                    <?= e(strtoupper(substr($userName, 0, 1))) ?>
                </div>
                <div class="profile-info">
                    <strong><?= e($userName) ?></strong>
                    <small>Pengguna BLOCKSAFE</small>
                </div>
            </div>
        </header>

        <section class="content-area">

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success border-0 shadow-sm">
                    Dokumen berhasil disimpan dan hash SHA-256 berhasil dibuat.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['verified'])): ?>
                <div class="alert alert-info border-0 shadow-sm">
                    Proses verifikasi dokumen telah selesai.
                </div>
            <?php endif; ?>

            <!-- INTRO -->
            <div class="welcome-section">
                <div>
                    <h2>Selamat datang, <?= e($userName) ?>!</h2>
                    <p>
                        Pantau penyimpanan, verifikasi integritas, dan
                        status pencatatan dokumen pada blockchain.
                    </p>
                </div>

                <div class="system-status">
                    <span class="online-dot"></span>
                    Sistem Aktif
                </div>
            </div>

            <!-- STATISTICS -->
            <div class="row g-3 statistics">

                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-top">
                            <span>Total Dokumen</span>
                            <div class="stat-icon icon-blue">▤</div>
                        </div>
                        <h2><?= number_format($totalDocuments) ?></h2>
                        <small>Dokumen tersimpan</small>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-top">
                            <span>Verifikasi Valid</span>
                            <div class="stat-icon icon-green">✓</div>
                        </div>
                        <h2><?= number_format($totalVerified) ?></h2>
                        <small>Hash berhasil dicocokkan</small>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-top">
                            <span>Blockchain Confirmed</span>
                            <div class="stat-icon icon-purple">◈</div>
                        </div>
                        <h2><?= number_format($totalBlockchain) ?></h2>
                        <small>Dokumen terkonfirmasi</small>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-top">
                            <span>Menunggu Konfirmasi</span>
                            <div class="stat-icon icon-orange">◷</div>
                        </div>
                        <h2><?= number_format($totalPending) ?></h2>
                        <small>Dokumen berstatus pending</small>
                    </div>
                </div>

            </div>

            <!-- UPLOAD -->
            <section class="panel upload-panel" id="upload">
                <div class="panel-heading">
                    <div>
                        <h3>Upload Dokumen Baru</h3>
                        <p>Simpan dokumen dan buat sidik digital SHA-256.</p>
                    </div>
                    <div class="upload-heading-icon">↑</div>
                </div>

                <form action="upload.php"
                      method="POST"
                      enctype="multipart/form-data">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= e($_SESSION['csrf_token']) ?>">

                    <div class="upload-area">
                        <div class="upload-symbol">↑</div>

                        <h5>Pilih dokumen untuk diunggah</h5>
                        <p>PDF, JPG, PNG, TXT, DOCX, XLSX · Maksimal 10 MB</p>

                        <input type="file"
                               name="document"
                               class="form-control"
                               accept=".pdf,.jpg,.jpeg,.png,.txt,.docx,.xlsx"
                               required>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn-upload">
                            Upload dan Buat Hash
                            <span>→</span>
                        </button>
                    </div>
                </form>
            </section>

            <!-- DOCUMENT TABLE -->
            <section class="panel documents-panel" id="documents">

                <div class="panel-heading document-heading">
                    <div>
                        <h3>Data Penyimpanan Dokumen</h3>
                        <p>Daftar dokumen yang tersimpan pada akun Anda.</p>
                    </div>

                    <span class="document-count">
                        <?= count($documents) ?> Dokumen
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table document-table align-middle">
                        <thead>
                            <tr>
                                <th>Dokumen</th>
                                <th>Ukuran</th>
                                <th>SHA-256</th>
                                <th>Blockchain</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (!empty($documents)): ?>

                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td>
                                        <div class="document-name">
                                            <div class="file-icon">PDF</div>
                                            <div>
                                                <strong>
                                                    <?= e($doc['document_name']) ?>
                                                </strong>
                                                <small>
                                                    ID: DOC-<?= str_pad(
                                                        (string) $doc['id'],
                                                        6,
                                                        '0',
                                                        STR_PAD_LEFT
                                                    ) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <?= e(formatFileSize((int) $doc['file_size'])) ?>
                                    </td>

                                    <td>
                                        <code class="hash-code">
                                            <?= e(substr($doc['file_hash'], 0, 18)) ?>...
                                        </code>
                                    </td>

                                    <td>
                                        <?= statusBadge($doc['blockchain_status']) ?>
                                    </td>

                                    <td>
                                        <span class="date-text">
                                            <?= e(date(
                                                'd M Y H:i',
                                                strtotime($doc['created_at'])
                                            )) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a href="verify.php?id=<?= (int) $doc['id'] ?>"
                                           class="btn-verify">
                                            Verifikasi
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div>▤</div>
                                        <strong>Belum ada dokumen</strong>
                                        <p>Upload dokumen pertama Anda untuk memulai.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="footer">
                <strong>BLOCKSAFE</strong>
                <span>Blockchain-Based Secure Data Storage System © 2026</span>
            </footer>

        </section>
    </main>
</div>

</body>
</html>