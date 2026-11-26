<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
require_login();

// Toevoegen of bewerken van één dienst of prijs.
$type = (string) ($_GET['type'] ?? '');
if (!isset(ADMIN_LISTS[$type])) {
    http_response_code(404);
    exit('Onbekende lijst.');
}
$list = ADMIN_LISTS[$type];
$table = $list['table'];
$id = (int) ($_GET['id'] ?? 0);

$item = ['title_nl' => '', 'title_en' => '', 'text_nl' => '', 'text_en' => '', 'price' => '', 'sort_order' => 100, 'active' => 1];
if ($id > 0) {
    $item = db_one("SELECT * FROM {$table} WHERE id = ?", [$id]);
    if ($item === null) {
        http_response_code(404);
        exit('Niet gevonden.');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $item = [
        'title_nl'   => post('title_nl', 200),
        'title_en'   => post('title_en', 200),
        'text_nl'    => post('text_nl', 2000),
        'text_en'    => post('text_en', 2000),
        'price'      => post('price', 50),
        'sort_order' => (int) post('sort_order', 6),
        'active'     => isset($_POST['active']) ? 1 : 0,
    ];
    if ($item['title_nl'] === '') {
        $errors[] = 'De Nederlandse titel is verplicht.';
    }

    if ($errors === []) {
        $columns = ['title_nl', 'title_en', 'text_nl', 'text_en', 'sort_order', 'active'];
        if ($list['has_price']) {
            $columns[] = 'price';
        }
        $values = array_map(fn ($column) => $item[$column], $columns);

        if ($id > 0) {
            $set = implode(', ', array_map(fn ($column) => "$column = ?", $columns));
            db_run("UPDATE {$table} SET {$set} WHERE id = ?", [...$values, $id]);
        } else {
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            db_run("INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})", $values);
        }
        flash(ucfirst($list['singular']) . ' opgeslagen.');
        redirect(admin_url('list.php?type=' . $type));
    }
}

$adminTitle = ($id > 0 ? 'Bewerk ' : 'Nieuwe ') . $list['singular'];
require __DIR__ . '/_header.php';
?>
<p><a href="<?= e(admin_url('list.php?type=' . $type)) ?>">← Terug naar <?= e(strtolower($list['title'])) ?></a></p>
<h1><?= e(ucfirst($adminTitle)) ?></h1>

<?php foreach ($errors as $error): ?>
    <p class="flash flash-error" role="alert"><?= e($error) ?></p>
<?php endforeach; ?>

<form method="post" class="form">
    <?= csrf_field() ?>
    <div class="lang-columns">
        <?php foreach (['nl' => 'Nederlands', 'en' => 'Engels'] as $l => $label): ?>
            <fieldset>
                <legend><?= e($label) ?></legend>
                <label>
                    Titel<?= $l === 'nl' ? ' *' : '' ?>
                    <input type="text" name="title_<?= $l ?>" value="<?= e($item["title_$l"]) ?>" maxlength="200"<?= $l === 'nl' ? ' required' : '' ?>>
                </label>
                <label>
                    Omschrijving
                    <textarea name="text_<?= $l ?>" rows="4" maxlength="2000"><?= e($item["text_$l"]) ?></textarea>
                </label>
            </fieldset>
        <?php endforeach; ?>
    </div>

    <fieldset>
        <legend>Overig</legend>
        <?php if ($list['has_price']): ?>
            <label>
                Prijs (bijv. "€ 89" of "vanaf € 59"). Leeg = "Op aanvraag".
                <input type="text" name="price" value="<?= e($item['price']) ?>" maxlength="50">
            </label>
        <?php endif; ?>
        <label>
            Volgorde (lager = hoger op de pagina)
            <input type="number" name="sort_order" value="<?= (int) $item['sort_order'] ?>" min="0" max="9999">
        </label>
        <label class="checkbox">
            <input type="checkbox" name="active" value="1"<?= $item['active'] ? ' checked' : '' ?>> Zichtbaar op de website
        </label>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="button">Opslaan</button>
    </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
