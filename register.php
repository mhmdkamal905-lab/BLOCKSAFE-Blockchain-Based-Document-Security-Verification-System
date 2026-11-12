<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

// Jika sudah login, langsung ke dashboard.
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

$fullName = '';
$username = '';
$email = '';
$walletAddress = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $walletAddress = trim($_POST['wallet_address'] ?? '');

    // Validasi CSRF
    if (!verify_csrf()) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman.';
    } elseif (
        $fullName === '' ||
        $username === '' ||
        $email === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {
        $error = 'Semua kolom wajib diisi kecuali alamat wallet.';
    } elseif (mb_strlen($fullName) > 180) {
        $error = 'Nama lengkap maksimal 180 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]{4,30}$/', $username)) {
        $error = 'Username harus 4–30 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Konfirmasi password tidak sama.';
    } elseif (
        $walletAddress !== '' &&
        !preg_match('/^0x[a-fA-F0-9]{40}$/', $walletAddress)
    ) {
        $error = 'Alamat wallet Ethereum tidak valid.';
    } else {
        try {
            // Cek username atau email yang sudah digunakan.
            $check = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = :username
                   OR email = :email
                LIMIT 1
            ");

            $check->execute([
                'username' => $username,
                'email' => $email
            ]);

            if ($check->fetch()) {
                $error = 'Username atau email sudah terdaftar.';
            } else {
                // Hash password sebelum disimpan.
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $insert = $pdo->prepare("
                    INSERT INTO users (
                        full_name,
                        username,
                        email,
                        password_hash,
                        wallet_address,
                        created_at,
                        updated_at
                    ) VALUES (
                        :full_name,
                        :username,
                        :email,
                        :password_hash,
                        :wallet_address,
                        CURRENT_TIMESTAMP,
                        CURRENT_TIMESTAMP
                    )
                ");

                $insert->execute([
                    'full_name' => $fullName,
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'wallet_address' => $walletAddress !== ''
                        ? $walletAddress
                        : null
                ]);

                $success = 'Akun berhasil dibuat. Silakan login.';

                // Kosongkan input setelah berhasil.
                $fullName = '';
                $username = '';
                $email = '';
                $walletAddress = '';

                // Perbarui token CSRF.
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Username atau email sudah digunakan.';
            } else {
                error_log('BLOCKSAFE registration error: ' . $e->getMessage());
                $error = 'Pendaftaran gagal. Silakan coba kembali.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Register | BLOCKSAFE</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 30px 15px;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at top left, #d8f3e9, transparent 40%),
                #f1f6f5;
            font-family: Arial, sans-serif;
        }

        .register-card {
            width: min(490px, 100%);
            padding: 35px;
            background: white;
            border: 1px solid #e8eeee;
            border-radius: 18px;
            box-shadow: 0 18px 55px rgba(0, 0, 0, .07);
        }

        .brand {
            color: #0f766e;
            font-size: 27px;
            font-weight: 800;
            text-align: center;
            letter-spacing: -1px;
        }

        .subtitle {
            color: #83918e;
            text-align: center;
            font-size: 12px;
            margin: 8px 0 28px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #354a46;
        }

        .form-control {
            padding: 11px 13px;
            border-radius: 8px;
            font-size: 13px;
        }

        .form-control:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 .2rem rgba(15, 118, 110, .12);
        }

        .btn-register {
            width: 100%;
            padding: 12px;
            border: 0;
            border-radius: 8px;
            background: #0f766e;
            color: white;
            font-size: 13px;
            font-weight: 700;
        }

        .btn-register:hover {
            background: #115e59;
        }

        .login-link {
            color: #0f766e;
            font-weight: 700;
            text-decoration: none;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        .security-note {
            padding: 11px;
            border-radius: 8px;
            background: #eff9f5;
            color: #387565;
            font-size: 11px;
            line-height: 1.6;
        }

        .optional {
            color: #99a5a2;
            font-weight: 400;
        }
    </style>
</head>

<body>

<div class="register-card">

    <div class="brand">BLOCKSAFE</div>

    <p class="subtitle">
        Create Your Secure Storage Account
    </p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger small">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success small">
            <?= e($success) ?>
            <a href="login.php" class="alert-link">Login sekarang</a>.
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">

        <input type="hidden"
               name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">

        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text"
                   name="full_name"
                   class="form-control"
                   placeholder="Masukkan nama lengkap"
                   maxlength="180"
                   value="<?= e($fullName) ?>"
                   autocomplete="name"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text"
                   name="username"
                   class="form-control"
                   placeholder="Contoh: kamal2026"
                   minlength="4"
                   maxlength="30"
                   value="<?= e($username) ?>"
                   autocomplete="username"
                   required>
            <small class="text-muted">
                4–30 karakter, tanpa spasi.
            </small>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email"
                   name="email"
                   class="form-control"
                   placeholder="nama@email.com"
                   maxlength="254"
                   value="<?= e($email) ?>"
                   autocomplete="email"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Alamat Wallet Ethereum
                <span class="optional">(Opsional)</span>
            </label>
            <input type="text"
                   name="wallet_address"
                   class="form-control"
                   placeholder="0x..."
                   maxlength="42"
                   value="<?= e($walletAddress) ?>">
            <small class="text-muted">
                Dapat ditambahkan nanti ketika menghubungkan wallet.
            </small>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password"
                   name="password"
                   class="form-control"
                   placeholder="Minimal 8 karakter"
                   minlength="8"
                   autocomplete="new-password"
                   required>
        </div>

        <div class="mb-4">
            <label class="form-label">Konfirmasi Password</label>
            <input type="password"
                   name="confirm_password"
                   class="form-control"
                   placeholder="Ulangi password"
                   minlength="8"
                   autocomplete="new-password"
                   required>
        </div>

        <div class="security-note mb-4">
            <strong>Keamanan BLOCKSAFE</strong><br>
            Password Anda disimpan dalam bentuk hash menggunakan
            algoritma password_hash PHP, bukan sebagai teks biasa.
        </div>

        <button type="submit" class="btn-register">
            Buat Akun BLOCKSAFE
        </button>

    </form>

    <div class="text-center mt-4">
        <small class="text-muted">
            Sudah memiliki akun?
            <a href="login.php" class="login-link">Login di sini</a>
        </small>
    </div>

    <p class="text-center text-muted small mt-4 mb-0">
        BLOCKSAFE © 2026
    </p>

</div>

</body>
</html>