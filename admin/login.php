<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';

if (current_user()) {
    redirect(admin_url());
}

const MAX_ATTEMPTS = 5;
const LOCK_MINUTES = 15;

$ip = $_SERVER['REMOTE_ADDR'] ?? 'onbekend';
$since = date('Y-m-d H:i:s', time() - LOCK_MINUTES * 60);
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = post('email', 190);
    $password = (string) ($_POST['password'] ?? '');

    $attempts = (int) (db_one(
        'SELECT COUNT(*) AS n FROM login_attempts WHERE ip = ? AND attempted_at > ?',
        [$ip, $since]
    )['n'] ?? 0);

    if ($attempts >= MAX_ATTEMPTS) {
        $error = 'Te vaak een verkeerd wachtwoord. Probeer het over ' . LOCK_MINUTES . ' minuten opnieuw.';
    } else {
        $user = db_one('SELECT id, password_hash FROM users WHERE email = ?', [$email]);

        if ($user && password_verify($password, $user['password_hash'])) {
            db_run('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['last_active'] = time();
            redirect(admin_url());
        }

        db_run('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, ?)', [$ip, date('Y-m-d H:i:s')]);
        sleep(1);
        $error = 'E-mailadres of wachtwoord klopt niet.';
    }
}

$adminTitle = 'Inloggen';
require __DIR__ . '/_header.php';
?>
<div class="login-box">
    <h1>Beheer Bakfietsgarage</h1>
    <?php if ($error): ?>
        <p class="flash flash-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <label>
            E-mailadres
            <input type="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="username">
        </label>
        <label>
            Wachtwoord
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit" class="button">Inloggen</button>
    </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
