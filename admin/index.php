<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
require_login();

$pages = [];
foreach (db_all('SELECT page_key, title_nl, title_en, updated_at FROM pages') as $row) {
    $pages[$row['page_key']] = $row;
}

$adminTitle = 'Pagina\'s';
require __DIR__ . '/_header.php';
?>
<h1>Pagina's</h1>
<p class="muted">Kies een pagina om de teksten en de foto aan te passen.</p>

<table class="table">
    <thead>
        <tr>
            <th>Pagina</th>
            <th>Titel (NL)</th>
            <th>Titel (EN)</th>
            <th>Laatst gewijzigd</th>
            <th><span class="sr-only">Acties</span></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach (PAGE_NAMES as $key => $name): ?>
            <?php $row = $pages[$key] ?? null; ?>
            <tr>
                <td><strong><?= e($name) ?></strong></td>
                <td><?= e($row['title_nl'] ?? '') ?></td>
                <td><?= $row && trim($row['title_en']) !== '' ? e($row['title_en']) : '<span class="badge">Nog niet vertaald</span>' ?></td>
                <td><?= $row ? e(date('d-m-Y H:i', strtotime($row['updated_at']))) : '' ?></td>
                <td class="actions">
                    <a class="button button-small" href="<?= e(admin_url('page.php?key=' . urlencode($key))) ?>">Bewerken</a>
                    <a href="<?= e(page_url($key, 'nl')) ?>" target="_blank" rel="noopener">Bekijken</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/_footer.php'; ?>
