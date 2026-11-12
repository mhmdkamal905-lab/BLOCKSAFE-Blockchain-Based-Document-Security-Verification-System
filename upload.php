<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode request tidak diizinkan.');
}

// =====================================
// VALIDASI CSRF
// =====================================

$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    exit('Token keamanan tidak valid.');
}

// =====================================
// USER ID
// =====================================

// Sementara gunakan ID user 1 untuk pengujian.
// Setelah login dibuat, gunakan ID dari session login.

$userId = $_SESSION['user_id'] ?? 1;

// Pastikan user tersedia jika tabel users sudah digunakan.
$userCheck = $pdo->prepare("
    SELECT id FROM users WHERE id = :id LIMIT 1
");

$userCheck->execute([':id' => $userId]);

if (!$userCheck->fetch()) {
    exit('User tidak ditemukan. Periksa tabel users.');
}

// =====================================
// VALIDASI FILE
// =====================================

if (
    !isset($_FILES['document']) ||
    $_FILES['document']['error'] !== UPLOAD_ERR_OK
) {
    exit('File gagal diunggah. Kode: ' .
        ($_FILES['document']['error'] ?? 'Tidak ditemukan'));
}

$file = $_FILES['document'];

$maxSize = 10 * 1024 * 1024;

if ($file['size'] <= 0 || $file['size'] > $maxSize) {
    exit('Ukuran file maksimal 10 MB.');
}

// =====================================
// VALIDASI MIME TYPE
// =====================================

$allowedTypes = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'text/plain' => 'txt',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'
];

$finfo = new finfo(FILEINFO_MIME_TYPE);

$mime = $finfo->file($file['tmp_name']);

if (!isset($allowedTypes[$mime])) {
    exit('Format file tidak diperbolehkan.');
}

// =====================================
// GENERATE NAMA FILE
// =====================================

$originalName = basename($file['name']);

$extension = $allowedTypes[$mime];

$storedName = bin2hex(random_bytes(32));

$documentName = pathinfo(
    $originalName,
    PATHINFO_FILENAME
);

if (strlen($documentName) > 180) {
    $documentName = substr($documentName, 0, 180);
}

// =====================================
// HITUNG SHA-256
// =====================================

$fileHash = hash_file(
    'sha256',
    $file['tmp_name']
);

if ($fileHash === false) {
    exit('Gagal membuat SHA-256.');
}

// =====================================
// PERSIAPAN STORAGE
// =====================================

$storage = STORAGE_PATH;

if (!is_dir($storage)) {

    if (!mkdir($storage, 0750, true)) {
        exit('Folder penyimpanan gagal dibuat.');
    }

}

$destination = $storage . '/' . $storedName;

$moved = false;

try {

    // =================================
    // SIMPAN FILE
    // =================================

    if (!move_uploaded_file(
        $file['tmp_name'],
        $destination
    )) {
        throw new RuntimeException(
            'Gagal memindahkan file ke storage.'
        );
    }

    $moved = true;

    // =================================
    // SIMPAN KE DATABASE
    // =================================

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO documents (
            user_id,
            document_name,
            stored_name,
            mime_type,
            file_size,
            file_hash,
            blockchain_status
        ) VALUES (
            :user_id,
            :document_name,
            :stored_name,
            :mime_type,
            :file_size,
            :file_hash,
            'PENDING'
        )
    ");

    $stmt->execute([

        ':user_id' => $userId,

        ':document_name' => $documentName,

        ':stored_name' => $storedName,

        ':mime_type' => $mime,

        ':file_size' => $file['size'],

        ':file_hash' => $fileHash

    ]);

    $pdo->commit();

    header('Location: index.php?success=1');

    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($moved && is_file($destination)) {
        unlink($destination);
    }

    error_log($e->getMessage());

    http_response_code(500);

    exit(
        'Penyimpanan gagal: ' .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );

}