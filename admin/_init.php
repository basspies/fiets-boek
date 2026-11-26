<?php
declare(strict_types=1);

// Wordt bovenaan elke admin-pagina geladen: sessie, login-check, CSRF-beveiliging.

require dirname(__DIR__) . '/includes/bootstrap.php';

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('bfg_admin');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => BASE . '/admin',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

const SESSION_TIMEOUT = 2 * 60 * 60; // 2 uur niets gedaan = opnieuw inloggen

function admin_url(string $path = ''): string
{
    return url('admin/' . ltrim($path, '/'));
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    if (time() - ($_SESSION['last_active'] ?? 0) > SESSION_TIMEOUT) {
        $_SESSION = [];
        return null;
    }
    $_SESSION['last_active'] = time();
    return db_one('SELECT id, email FROM users WHERE id = ?', [$_SESSION['user_id']]);
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect(admin_url('login.php'));
    }
    return $user;
}

// ---------- CSRF: elk formulier krijgt een geheime code die bij het opslaan wordt gecontroleerd ----------

function csrf_token(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = (string) ($_POST['csrf'] ?? '');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Ongeldig formulier. Ga terug, ververs de pagina en probeer het opnieuw.');
    }
}

// ---------- Meldingen ("Opgeslagen") die na een redirect één keer getoond worden ----------

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// Waarde uit een POST-formulier, ingekort tot een maximale lengte.
function post(string $key, int $max = 10000): string
{
    return mb_substr(trim((string) ($_POST[$key] ?? '')), 0, $max);
}

// ---------- Foto's uploaden ----------

const UPLOAD_MAX_BYTES = 5 * 1024 * 1024;
const UPLOAD_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

// Slaat een geüploade foto op in /uploads en geeft de nieuwe bestandsnaam.
// Geeft null als er geen bestand is gekozen. Gooit een foutmelding als het bestand niet mag.
function save_upload(string $field): ?string
{
    $file = $_FILES[$field] ?? null;
    if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Het uploaden is mislukt. Probeer het opnieuw.');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('De foto is te groot. Maximaal 5 MB.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(UPLOAD_TYPES[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Alleen JPG-, PNG- of WebP-foto\'s zijn toegestaan.');
    }

    $name = bin2hex(random_bytes(12)) . '.' . UPLOAD_TYPES[$mime];
    if (!move_uploaded_file($file['tmp_name'], ROOT . '/uploads/' . $name)) {
        throw new RuntimeException('De foto kon niet worden opgeslagen.');
    }
    return $name;
}

function delete_upload(?string $name): void
{
    if ($name === null || $name === '') {
        return;
    }
    $path = ROOT . '/uploads/' . basename($name);
    if (is_file($path)) {
        unlink($path);
    }
}

// Lijsten die in het CMS beheerd worden (diensten en prijzen).
const ADMIN_LISTS = [
    'services' => ['table' => 'services', 'title' => 'Diensten', 'singular' => 'dienst', 'has_price' => false],
    'prices'   => ['table' => 'prices',   'title' => 'Prijzen',  'singular' => 'prijs',  'has_price' => true],
];

const PAGE_NAMES = [
    'home'     => 'Home',
    'diensten' => 'Diensten',
    'prijzen'  => 'Prijzen',
    'merken'   => 'Merken',
    'service'  => 'Haal- en brengservice',
    'over-ons' => 'Over ons',
    'contact'  => 'Contact',
];
