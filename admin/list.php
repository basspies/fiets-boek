<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
require_login();

// Overzicht van diensten of prijzen: list.php?type=services of list.php?type=prices
$type = (string) ($_GET['type'] ?? '');
if (!isset(ADMIN_LISTS[$type])) {
    http_response_code(404);
    exit('Onbekende lijst.');
}
$list = ADMIN_LISTS[$type];
$table = $list['table']; // komt uit de vaste lijst hierboven, nooit uit de URL

// Verwijderen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['delete_id'] ?? 0);
    if ($id > 0) {
        db_run("DELETE FROM {$table} WHERE id = ?", [$id]);
        flash(ucfirst($list['singular']) . ' verwijderd.');
    }
    redirect(admin_url('list.php?type=' . $type));
}

$items = db_all("SELECT * FROM {$table} ORDER BY sort_order, id");

$adminTitle = $list['title'];
require __DIR__ . '/_header.php';
?>
<div class="title-row">
    <h1><?= e($list['title']) ?></h1>
    <a class="button" href="<?= e(admin_url('item.php?type=' . $type)) ?>">+ Nieuwe <?= e($list['singular']) ?></a>
</div>
<p class="muted">Volgorde: lager nummer staat hoger op de website.</p>

<table class="table">
    <thead>
        <tr>
            <th>Volgorde</th>
            <th>Titel (NL)</th>
            <?php if ($list['has_price']): ?><th>Prijs</th><?php endif; ?>
            <th>Zichtbaar</th>
            <th><span class="sr-only">Acties</span></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= (int) $item['sort_order'] ?></td>
                <td>
                    <strong><?= e($item['title_nl']) ?></strong>
                    <?php if (trim((string) $item['title_en']) === ''): ?><span class="badge">Nog niet vertaald</span><?php endif; ?>
                </td>
                <?php if ($list['has_price']): ?>
                    <td><?= trim($item['price']) !== '' ? e($item['price']) : '<span class="badge">Op aanvraag</span>' ?></td>
                <?php endif; ?>
                <td><?= $item['active'] ? 'Ja' : 'Nee' ?></td>
                <td class="actions">
                    <a class="button button-small" href="<?= e(admin_url('item.php?type=' . $type . '&id=' . (int) $item['id'])) ?>">Bewerken</a>
                    <form method="post" onsubmit="return confirm('Weet je zeker dat je dit wilt verwijderen?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_id" value="<?= (int) $item['id'] ?>">
                        <button type="submit" class="link-button danger">Verwijderen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($items === []): ?>
            <tr><td colspan="5" class="muted">Nog niets toegevoegd.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php require __DIR__ . '/_footer.php'; ?>
