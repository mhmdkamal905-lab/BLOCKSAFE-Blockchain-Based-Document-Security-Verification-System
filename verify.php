<?php
declare(strict_types=1);

/**
 * =========================================================
 * BLOCKSAFE ETHEREUM
 * DOCUMENT VERIFICATION
 * SHA-256 HASH VERIFICATION
 * =========================================================
 */

require_once __DIR__ . '/app/bootstrap.php';

// Pastikan PDO tersedia
if (!isset($pdo) || !$pdo instanceof PDO) {
    exit('Koneksi database tidak tersedia.');
}

// 1. VALIDASI ID DOKUMEN
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    exit('ID dokumen tidak valid.');
}

try {

    // 2. AMBIL DATA DOKUMEN
    $stmt = $pdo->prepare("
        SELECT *
        FROM documents
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        exit('Dokumen tidak ditemukan.');
    }

    // 3. AMBIL NAMA DOKUMEN SECARA AMAN
    $documentName = $document['original_name']
        ?? $document['document_name']
        ?? $document['file_name']
        ?? 'Dokumen tanpa nama';

    // 4. VALIDASI NAMA FILE
    $storedName = (string) ($document['stored_name'] ?? '');

    if (
        $storedName === '' ||
        basename($storedName) !== $storedName ||
        str_contains($storedName, "\0")
    ) {
        exit('Nama file tidak valid.');
    }

    // 5. AMBIL HASH ASLI
    $originalHash = strtolower(
        trim((string) ($document['file_hash'] ?? ''))
    );

    if (!preg_match('/^[a-f0-9]{64}$/', $originalHash)) {
        exit('Hash dokumen tidak valid atau belum tersedia.');
    }

    // 6. LOKASI FILE
    $filePath = rtrim(STORAGE_PATH, '/\\')
        . DIRECTORY_SEPARATOR
        . $storedName;

    // 7. PROSES VERIFIKASI SHA-256
    $status = 'MISSING';
    $currentHash = str_repeat('0', 64);

    if (is_file($filePath) && is_readable($filePath)) {

        $result = hash_file('sha256', $filePath);

        if ($result === false) {
            exit('Gagal membaca file dokumen.');
        }

        $currentHash = strtolower($result);

        $status = hash_equals(
            $originalHash,
            $currentHash
        ) ? 'VALID' : 'INVALID';
    }

    // 8. SIMPAN RIWAYAT VERIFIKASI
    $log = $pdo->prepare("
        INSERT INTO verification_logs (
            document_id,
            original_hash,
            checked_hash,
            verification_status
        ) VALUES (
            :document_id,
            :original_hash,
            :checked_hash,
            :status
        )
    ");

    $log->execute([
        ':document_id' => $id,
        ':original_hash' => $originalHash,
        ':checked_hash' => $currentHash,
        ':status' => $status
    ]);

} catch (PDOException $e) {

    error_log('BLOCKSAFE Verification Error: ' . $e->getMessage());

    exit('Terjadi kesalahan database saat verifikasi dokumen.');
}

// 9. STATUS DAN PESAN
$colors = [
    'VALID'   => '#16a34a',
    'INVALID' => '#dc2626',
    'MISSING' => '#d97706'
];

$messages = [
    'VALID' =>
        'Dokumen masih asli. Hash SHA-256 cocok dengan data awal.',

    'INVALID' =>
        'Hash berbeda. Dokumen kemungkinan telah dimodifikasi.',

    'MISSING' =>
        'File tidak ditemukan atau tidak dapat diakses di penyimpanan.'
];

$color = $colors[$status];
$message = $messages[$status];

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Verifikasi Dokumen | BLOCKSAFE</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>

        body {
            background: #f1f5f9;
            font-family: Arial, sans-serif;
        }

        .verification-card {
            max-width: 700px;
            border: none;
            border-radius: 18px;
            overflow: hidden;
        }

        .brand {
            font-weight: 800;
            letter-spacing: 1px;
            color: #0f172a;
        }

        .status-box {
            background: <?= json_encode($color) ?>15;
            border-left: 5px solid <?= json_encode($color) ?>;
            border-radius: 10px;
            padding: 20px;
        }

        .status-title {
            color: <?= json_encode($color) ?>;
            font-weight: 800;
        }

        .hash-box {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 15px;
            word-break: break-all;
            font-size: 13px;
        }

        .hash-box code {
            color: #334155;
        }

        .footer-text {
            font-size: 12px;
            color: #94a3b8;
        }

    </style>

</head>

<body>

<div class="container py-5">

    <div class="card shadow verification-card mx-auto">

        <div class="card-body p-4 p-md-5">

            <div class="mb-4">

                <h4 class="brand">
                    BLOCKSAFE
                </h4>

                <p class="text-muted mb-0">
                    Blockchain Document Security System
                </p>

            </div>

            <hr>

            <h3 class="fw-bold mb-2">
                Verifikasi Dokumen
            </h3>

            <p class="text-muted">
                Hasil pemeriksaan integritas dokumen
                menggunakan algoritma SHA-256.
            </p>

            <div class="mb-4">

                <small class="text-muted">
                    Nama Dokumen
                </small>

                <div class="fw-semibold">
                    <?= e((string) $documentName) ?>
                </div>

            </div>

            <!-- STATUS VERIFIKASI -->

            <div class="status-box mb-4">

                <h4 class="status-title mb-2">
                    <?= e($status) ?>
                </h4>

                <p class="mb-0">
                    <?= e($message) ?>
                </p>

            </div>

            <!-- HASH ASLI -->

            <h6 class="fw-bold">
                Hash Asli (SHA-256)
            </h6>

            <div class="hash-box mb-4">

                <code><?= e($originalHash) ?></code>

            </div>

            <!-- HASH TERKINI -->

            <h6 class="fw-bold">
                Hash Saat Ini (SHA-256)
            </h6>

            <div class="hash-box mb-4">

                <code><?= e($currentHash) ?></code>

            </div>

            <!-- INFORMASI -->

            <div class="alert alert-light border">

                <small class="text-muted">
                    ID Dokumen
                </small>

                <div class="fw-bold">
                    #<?= e((string) $id) ?>
                </div>

                <hr>

                <small class="text-muted">
                    Metode Verifikasi
                </small>

                <div class="fw-semibold">
                    SHA-256 Hash Comparison
                </div>

            </div>

            <div class="d-flex flex-wrap gap-2">

                <a href="index.php"
                   class="btn btn-dark px-4">
                    Kembali ke Dashboard
                </a>

                <button onclick="window.print()"
                        class="btn btn-outline-secondary">
                    Cetak Hasil
                </button>

            </div>

            <hr class="mt-4">

            <div class="text-center footer-text">
                BLOCKSAFE ETHEREUM
                <br>
                Document Integrity Verification System
            </div>

        </div>

    </div>

</div>

</body>
</html>