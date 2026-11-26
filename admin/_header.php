<?php
/** @var string $adminTitle */
$user = current_user();
$nav = [
    'index.php'              => 'Pagina\'s',
    'list.php?type=services' => 'Diensten',
    'list.php?type=prices'   => 'Prijzen',
    'settings.php'           => 'Instellingen',
];
$currentFile = basename($_SERVER['SCRIPT_NAME']) . (isset($_GET['type']) ? '?type=' . $_GET['type'] : '');
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($adminTitle) ?> | Beheer Bakfietsgarage</title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body>
<?php if ($user): ?>
<header class="admin-header">
    <a class="admin-brand" href="<?= e(admin_url()) ?>">Beheer Bakfietsgarage</a>
    <nav class="admin-nav">
        <?php foreach ($nav as $href => $label): ?>
            <a href="<?= e(admin_url($href)) ?>"<?= $currentFile === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="admin-user">
        <a href="<?= e(url('nl/')) ?>" target="_blank" rel="noopener">Bekijk website</a>
        <form method="post" action="<?= e(admin_url('logout.php')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="link-button">Uitloggen</button>
        </form>
    </div>
</header>
<?php endif; ?>
<main class="admin-main">
    <?php foreach (take_flashes() as $flash): ?>
        <p class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
    <?php endforeach; ?>
