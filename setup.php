<?php
declare(strict_types=1);

// Maakt de database-tabellen, vult de startinhoud en maakt een admin-account.
// Gebruik (in de terminal, in de projectmap):
//   php setup.php --email=naam@voorbeeld.nl --password=geheim123
// Je kunt het script opnieuw draaien: bestaande inhoud blijft staan,
// alleen het wachtwoord van het opgegeven account wordt bijgewerkt.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/includes/bootstrap.php';

$args = getopt('', ['email:', 'password:']);
$email = trim($args['email'] ?? '');
$password = (string) ($args['password'] ?? '');

if ($email === '' || $password === '') {
    fwrite(STDERR, "Gebruik: php setup.php --email=naam@voorbeeld.nl --password=wachtwoord\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
    fwrite(STDERR, "Geef een geldig e-mailadres en een wachtwoord van minimaal 10 tekens.\n");
    exit(1);
}

$driver = $GLOBALS['config']['db']['driver'] ?? 'mysql';
$id = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
$end = $driver === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id $id,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL
    )$end",

    "CREATE TABLE IF NOT EXISTS pages (
        id $id,
        page_key VARCHAR(50) NOT NULL UNIQUE,
        title_nl VARCHAR(200) NOT NULL DEFAULT '',
        title_en VARCHAR(200) NOT NULL DEFAULT '',
        intro_nl TEXT,
        intro_en TEXT,
        body_nl TEXT,
        body_en TEXT,
        meta_nl VARCHAR(300) NOT NULL DEFAULT '',
        meta_en VARCHAR(300) NOT NULL DEFAULT '',
        image VARCHAR(255) NULL,
        updated_at DATETIME NOT NULL
    )$end",

    "CREATE TABLE IF NOT EXISTS services (
        id $id,
        title_nl VARCHAR(200) NOT NULL DEFAULT '',
        title_en VARCHAR(200) NOT NULL DEFAULT '',
        text_nl TEXT,
        text_en TEXT,
        sort_order INT NOT NULL DEFAULT 0,
        active TINYINT NOT NULL DEFAULT 1
    )$end",

    "CREATE TABLE IF NOT EXISTS prices (
        id $id,
        title_nl VARCHAR(200) NOT NULL DEFAULT '',
        title_en VARCHAR(200) NOT NULL DEFAULT '',
        text_nl TEXT,
        text_en TEXT,
        price VARCHAR(50) NOT NULL DEFAULT '',
        sort_order INT NOT NULL DEFAULT 0,
        active TINYINT NOT NULL DEFAULT 1
    )$end",

    "CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(50) NOT NULL PRIMARY KEY,
        value TEXT
    )$end",

    // Mislukte inlogpogingen, om raden van wachtwoorden te blokkeren.
    "CREATE TABLE IF NOT EXISTS login_attempts (
        id $id,
        ip VARCHAR(45) NOT NULL,
        attempted_at DATETIME NOT NULL
    )$end",
];

foreach ($tables as $sql) {
    db()->exec($sql);
}
echo "Tabellen klaar.\n";

// ---------- Startinhoud (alleen als de database nog leeg is) ----------

$now = date('Y-m-d H:i:s');

if ((int) db()->query('SELECT COUNT(*) FROM pages')->fetchColumn() === 0) {
    $pages = require __DIR__ . '/database/seed.php';

    foreach ($pages['pages'] as $key => $p) {
        db_run(
            'INSERT INTO pages (page_key, title_nl, title_en, intro_nl, intro_en, body_nl, body_en, meta_nl, meta_en, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$key, $p['title_nl'], $p['title_en'], $p['intro_nl'], $p['intro_en'], $p['body_nl'], $p['body_en'], $p['meta_nl'], $p['meta_en'], $now]
        );
    }
    foreach ($pages['services'] as $i => $s) {
        db_run(
            'INSERT INTO services (title_nl, title_en, text_nl, text_en, sort_order) VALUES (?, ?, ?, ?, ?)',
            [$s[0], $s[1], $s[2], $s[3], ($i + 1) * 10]
        );
    }
    foreach ($pages['prices'] as $i => $p) {
        db_run(
            'INSERT INTO prices (title_nl, title_en, text_nl, text_en, price, sort_order) VALUES (?, ?, ?, ?, ?, ?)',
            [$p[0], $p[1], $p[2], $p[3], '', ($i + 1) * 10]
        );
    }
    foreach ($pages['settings'] as $key => $value) {
        db_run('INSERT INTO settings (setting_key, value) VALUES (?, ?)', [$key, $value]);
    }
    echo "Startinhoud toegevoegd.\n";
} else {
    echo "Er staat al inhoud in de database, startinhoud overgeslagen.\n";
}

// ---------- Admin-account ----------

$hash = password_hash($password, PASSWORD_DEFAULT);
$existing = db_one('SELECT id FROM users WHERE email = ?', [$email]);
if ($existing) {
    db_run('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $existing['id']]);
    echo "Wachtwoord bijgewerkt voor $email.\n";
} else {
    db_run('INSERT INTO users (email, password_hash, created_at) VALUES (?, ?, ?)', [$email, $hash, $now]);
    echo "Admin-account aangemaakt: $email\n";
}

echo "Klaar.\n";
