<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
require_login();

$key = (string) ($_GET['key'] ?? '');
if (!isset(PAGE_NAMES[$key])) {
    http_response_code(404);
    exit('Pagina niet gevonden.');
}

$page = get_page($key);
if ($page === null) {
    http_response_code(404);
    exit('Deze pagina staat nog niet in de database. Draai eerst setup.php.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $data = [];
    foreach (LANGS as $l) {
        $data["title_$l"] = post("title_$l", 200);
        $data["intro_$l"] = post("intro_$l", 1000);
        $data["body_$l"]  = post("body_$l", 20000);
        $data["meta_$l"]  = post("meta_$l", 300);
    }
    if ($data['title_nl'] === '') {
        $errors[] = 'De Nederlandse titel is verplicht.';
    }

    $image = $page['image'];
    if ($errors === []) {
        try {
            $newImage = save_upload('image');
            if ($newImage !== null) {
                delete_upload($image);
                $image = $newImage;
            } elseif (!empty($_POST['remove_image'])) {
                delete_upload($image);
                $image = null;
            }
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if ($errors === []) {
        db_run(
            'UPDATE pages SET title_nl = ?, title_en = ?, intro_nl = ?, intro_en = ?, body_nl = ?, body_en = ?,
             meta_nl = ?, meta_en = ?, image = ?, updated_at = ? WHERE page_key = ?',
            [
                $data['title_nl'], $data['title_en'], $data['intro_nl'], $data['intro_en'],
                $data['body_nl'], $data['body_en'], $data['meta_nl'], $data['meta_en'],
                $image, date('Y-m-d H:i:s'), $key,
            ]
        );
        flash('Pagina "' . PAGE_NAMES[$key] . '" is opgeslagen.');
        redirect(admin_url('page.php?key=' . urlencode($key)));
    }

    // Bij een fout: ingevulde waarden laten staan.
    $page = array_merge($page, $data);
}

$adminTitle = PAGE_NAMES[$key];
require __DIR__ . '/_header.php';
?>
<p><a href="<?= e(admin_url()) ?>">← Alle pagina's</a></p>
<h1>Pagina: <?= e(PAGE_NAMES[$key]) ?></h1>

<?php foreach ($errors as $error): ?>
    <p class="flash flash-error" role="alert"><?= e($error) ?></p>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="form">
    <?= csrf_field() ?>

    <div class="lang-columns">
        <?php foreach (['nl' => 'Nederlands', 'en' => 'Engels'] as $l => $label): ?>
            <fieldset>
                <legend><?= e($label) ?></legend>
                <label>
                    Titel<?= $l === 'nl' ? ' *' : '' ?>
                    <input type="text" name="title_<?= $l ?>" value="<?= e($page["title_$l"]) ?>" maxlength="200"<?= $l === 'nl' ? ' required' : '' ?>>
                </label>
                <label>
                    Korte intro (onder de titel)
                    <textarea name="intro_<?= $l ?>" rows="3" maxlength="1000"><?= e($page["intro_$l"]) ?></textarea>
                </label>
                <label>
                    Tekst
                    <textarea name="body_<?= $l ?>" rows="12"><?= e($page["body_$l"]) ?></textarea>
                </label>
                <label>
                    Omschrijving voor Google (max. 300 tekens)
                    <textarea name="meta_<?= $l ?>" rows="3" maxlength="300"><?= e($page["meta_$l"]) ?></textarea>
                </label>
                <?php if ($l === 'en'): ?>
                    <p class="muted">Laat je een Engels veld leeg, dan toont de Engelse website de Nederlandse tekst.</p>
                <?php endif; ?>
            </fieldset>
        <?php endforeach; ?>
    </div>

    <details class="help">
        <summary>Hoe maak ik een opsomming of tussenkop?</summary>
        <ul>
            <li>Een lege regel begint een nieuwe alinea.</li>
            <li>Begin een regel met <code>- </code> voor een opsommingsteken.</li>
            <li>Begin een regel met <code>## </code> voor een tussenkop.</li>
        </ul>
    </details>

    <fieldset>
        <legend>Foto bovenaan de pagina</legend>
        <?php if ($page['image']): ?>
            <img class="preview" src="<?= e(upload_url($page['image'])) ?>" alt="Huidige foto">
            <label class="checkbox">
                <input type="checkbox" name="remove_image" value="1"> Foto verwijderen
            </label>
        <?php endif; ?>
        <label>
            Nieuwe foto kiezen (JPG, PNG of WebP, max. 5 MB)
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        </label>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="button">Opslaan</button>
        <a href="<?= e(page_url($key, 'nl')) ?>" target="_blank" rel="noopener">Bekijk pagina</a>
    </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
