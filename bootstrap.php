<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BLOCKSAFE APPLICATION BOOTSTRAP
|--------------------------------------------------------------------------
*/

// SESSION SECURITY
if (session_status() !== PHP_SESSION_ACTIVE) {

    session_name('BLOCKSAFE_SESSION');

    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/'
    ]);

    session_start();
}

// PATH CONFIGURATION
define('BASE_PATH', dirname(__DIR__));

define(
    'STORAGE_PATH',
    BASE_PATH . '/storage/documents'
);

// DATABASE
require_once BASE_PATH . '/config/database.php';

// CSRF TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

// HTML ESCAPE
if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

// CSRF VALIDATION
function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';

    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// REQUIRE LOGIN
function require_login(): void
{
    if (empty($_SESSION['user']['id'])) {
        header('Location: login.php');
        exit;
    }
}

// CHECK LOGIN STATUS
function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id']);
}

// GET USER ID
function current_user_id(): int
{
    return (int) ($_SESSION['user']['id'] ?? 0);
}

// GET USER NAME
function current_user_name(): string
{
    return (string) ($_SESSION['user']['full_name'] ?? '');
}

// GET CURRENT USER
function current_user(): array
{
    return $_SESSION['user'] ?? [];
}

// REGENERATE SESSION
function regenerate_session(): void
{
    session_regenerate_id(true);
}

// LOGOUT
function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}