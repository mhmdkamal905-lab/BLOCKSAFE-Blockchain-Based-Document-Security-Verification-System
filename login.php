<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

if (!empty($_SESSION['user']['id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Sesi formulir tidak valid. Silakan muat ulang halaman.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id, full_name, username, email,
                   password_hash, wallet_address
            FROM users
            WHERE username = :username
               OR email = :email
            LIMIT 1
        ");

        $stmt->execute([
            'username' => $username,
            'email' => $username
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'full_name' => $user['full_name'],
                'username' => $user['username'],
                'email' => $user['email'],
                'wallet_address' => $user['wallet_address']
            ];

            header('Location: index.php');
            exit;
        }

        $error = 'Username/email atau password tidak sesuai.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | BLOCKSAFE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    
</head>
<body>

<div class="login-card">
    <div class="brand">BLOCKSAFE</div>
    <p class="subtitle">Secure Blockchain Data Storage System</p>

    <?php if ($error): ?>
        <div class="alert alert-danger small">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">

        <div class="mb-3">
            <label class="form-label small fw-bold">Username atau Email</label>
            <input type="text" name="username"
                   class="form-control"
                   placeholder="Masukkan username atau email"
                   autocomplete="username"
                   required>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-bold">Password</label>
            <input type="password" name="password"
                   class="form-control"
                   placeholder="Masukkan password"
                   autocomplete="current-password"
                   required>
        </div>

        <button type="submit" class="btn-login">
            Masuk ke Dashboard
        </button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
        BLOCKSAFE © 2026
    </p>
</div>

<div class="text-center mt-4">
    <small class="text-muted">
        Belum memiliki akun?
        <a href="register.php"
           class="fw-bold text-success text-decoration-none">
            Buat Akun Baru
        </a>
    </small>
</div>

</body>
</html>