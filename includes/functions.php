<?php
declare(strict_types=1);

// ---------- Algemeen ----------

// Tekst veilig in HTML zetten. Gebruik dit voor ALLES wat uit de database of het formulier komt.
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Bepaalt de map van de site t.o.v. de webroot, bijv. '/Web/fiets' in XAMPP of '' met php -S.
function detect_base(): string
{
    if (PHP_SAPI === 'cli') {
        return '';
    }
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $root = realpath(ROOT) ?: ROOT;
    if ($docRoot !== '' && stripos($root, $docRoot) === 0) {
        return rtrim(str_replace('\\', '/', substr($root, strlen($docRoot))), '/');
    }
    return '';
}

function url(string $path = ''): string
{
    return BASE . '/' . ltrim($path, '/');
}

// CSS/JS/afbeeldingen, met versienummer zodat de browser nieuwe versies laadt.
function asset(string $path): string
{
    $file = ROOT . '/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $version;
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

// ---------- Database ----------

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $c = $GLOBALS['config']['db'];
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (($c['driver'] ?? 'mysql') === 'sqlite') {
        $pdo = new PDO('sqlite:' . $c['path'], null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
    }
    return $pdo;
}

// Korte helpers voor queries. Altijd met ? of :naam en een array met waarden (prepared statements).
function db_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function db_run(string $sql, array $params = []): void
{
    db()->prepare($sql)->execute($params);
}

// ---------- Taal ----------

function set_lang(string $lang): void
{
    $GLOBALS['lang'] = in_array($lang, LANGS, true) ? $lang : 'nl';
}

function lang(): string
{
    return $GLOBALS['lang'] ?? 'nl';
}

// Vaste teksten van de website (menu, knoppen) uit lang/nl.php of lang/en.php.
function t(string $key): string
{
    static $strings = [];
    $lang = lang();
    $strings[$lang] ??= require ROOT . "/lang/{$lang}.php";
    return $strings[$lang][$key] ?? $key;
}

// Haalt een veld in de huidige taal uit een databaserij, bijv. field($page, 'title') -> title_nl of title_en.
// Is de Engelse tekst leeg, dan wordt de Nederlandse getoond.
function field(array $row, string $name): string
{
    $value = (string) ($row[$name . '_' . lang()] ?? '');
    if (trim($value) === '' && lang() !== 'nl') {
        $value = (string) ($row[$name . '_nl'] ?? '');
    }
    return $value;
}

// Zet tekst uit het CMS om naar veilige HTML.
// Lege regel = nieuwe alinea, "- " aan het begin = opsommingsteken, "## " = tussenkop.
function text_to_html(string $text): string
{
    $html = '';
    $blocks = preg_split('/\R\s*\R/', trim($text)) ?: [];

    foreach ($blocks as $block) {
        $lines = preg_split('/\R/', trim($block)) ?: [];
        if ($lines === [] || $lines === ['']) {
            continue;
        }

        if (count($lines) === 1 && str_starts_with($lines[0], '## ')) {
            $html .= '<h2>' . e(substr($lines[0], 3)) . "</h2>\n";
            continue;
        }

        $isList = array_reduce($lines, fn ($all, $line) => $all && str_starts_with(ltrim($line), '- '), true);
        if ($isList) {
            $items = array_map(fn ($line) => '<li>' . e(substr(ltrim($line), 2)) . '</li>', $lines);
            $html .= '<ul>' . implode('', $items) . "</ul>\n";
        } else {
            $html .= '<p>' . implode('<br>', array_map('e', $lines)) . "</p>\n";
        }
    }
    return $html;
}

// ---------- Instellingen en contact ----------

function setting(string $key, string $default = ''): string
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        foreach (db_all('SELECT setting_key, value FROM settings') as $row) {
            $settings[$row['setting_key']] = (string) $row['value'];
        }
    }
    return $settings[$key] ?? $default;
}

// Instelling in de huidige taal, bijv. opening_hours_nl / opening_hours_en.
function setting_lang(string $key): string
{
    $value = setting($key . '_' . lang());
    return trim($value) === '' ? setting($key . '_nl') : $value;
}

// 033-7505833 -> 31337505833 (voor tel: en WhatsApp-links).
function intl_number(string $number): string
{
    $digits = preg_replace('/\D+/', '', $number) ?? '';
    if (str_starts_with($digits, '00')) {
        return substr($digits, 2);
    }
    if (str_starts_with($digits, '0')) {
        return '31' . substr($digits, 1);
    }
    return $digits;
}

function phone_link(): string
{
    return 'tel:+' . intl_number(setting('phone'));
}

function whatsapp_link(): string
{
    $text = lang() === 'en'
        ? 'Hello, I would like to make an appointment for my cargo bike.'
        : 'Hallo, ik wil graag een afspraak maken voor mijn bakfiets.';
    return 'https://wa.me/' . intl_number(setting('whatsapp')) . '?text=' . rawurlencode($text);
}

function mail_link(): string
{
    return 'mailto:' . setting('email');
}

// ---------- Pagina's ----------

function get_page(string $key): ?array
{
    return db_one('SELECT * FROM pages WHERE page_key = ?', [$key]);
}

function upload_url(?string $file): ?string
{
    return $file ? url('uploads/' . rawurlencode($file)) : null;
}
