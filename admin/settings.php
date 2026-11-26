<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$user = require_login();

$fields = [
    'phone'            => ['Telefoonnummer', 30],
    'whatsapp'         => ['WhatsApp-nummer', 30],
    'email'            => ['E-mailadres', 190],
    'address_line1'    => ['Adres (straat en huisnummer)', 100],
    'address_line2'    => ['Postcode en plaats', 100],
    'opening_hours_nl' => ['Openingstijden (Nederlands)', 1000],
    'opening_hours_en' => ['Openingstijden (Engels)', 1000],
];
$errors = [];
$values = [];
foreach ($fields as $key => $_) {
    $values[$key] = setting($key);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form = $_POST['form'] ?? '';

    if ($form === 'settings') {
        foreach ($fields as $key => [$label, $max]) {
            $values[$key] = post($key, $max);
        }
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Vul een geldig e-mailadres in.';
        }
        foreach (['phone', 'whatsapp'] as $key) {
            if (strlen(intl_number($values[$key])) < 10) {
                $errors[] = $fields[$key][0] . ' lijkt niet te kloppen.';
            }
        }
        if ($errors === []) {
            foreach ($values as $key => $value) {
                // REPLACE werkt in MySQL en SQLite: bestaat de instelling al, dan wordt hij overschreven.
                db_run('REPLACE INTO settings (setting_key, value) VALUES (?, ?)', [$key, $value]);
            }
            flash('Instellingen opgeslagen.');
            redirect(admin_url('settings.php'));
        }
    }

    if ($form === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $repeat = (string) ($_POST['repeat_password'] ?? '');
        $hash = db_one('SELECT password_hash FROM users WHERE id = ?', [$user['id']])['password_hash'] ?? '';

        if (!password_verify($current, $hash)) {
            $errors[] = 'Het huidige wachtwoord klopt niet.';
        } elseif (strlen($new) < 10) {
            $errors[] = 'Het nieuwe wachtwoord moet minimaal 10 tekens hebben.';
        } elseif ($new !== $repeat) {
            $errors[] = 'De twee nieuwe wachtwoorden zijn niet hetzelfde.';
        } else {
            db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            flash('Wachtwoord gewijzigd.');
            redirect(admin_url('settings.php'));
        }
    }
}

$adminTitle = 'Instellingen';
require __DIR__ . '/_header.php';
?>
<h1>Instellingen</h1>

<?php foreach ($errors as $error): ?>
    <p class="flash flash-error" role="alert"><?= e($error) ?></p>
<?php endforeach; ?>

<form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="form" value="settings">
    <fieldset>
        <legend>Contactgegevens</legend>
        <p class="muted">Deze gegevens staan op elke pagina en in de knoppen Bellen, WhatsApp en Mailen.</p>
        <?php foreach ($fields as $key => [$label, $max]): ?>
            <label>
                <?= e($label) ?>
                <?php if (str_starts_with($key, 'opening_hours')): ?>
                    <textarea name="<?= e($key) ?>" rows="7" maxlength="<?= $max ?>"><?= e($values[$key]) ?></textarea>
                <?php else: ?>
                    <input type="<?= $key === 'email' ? 'email' : 'text' ?>" name="<?= e($key) ?>" value="<?= e($values[$key]) ?>" maxlength="<?= $max ?>" required>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
    </fieldset>
    <div class="form-actions">
        <button type="submit" class="button">Opslaan</button>
    </div>
</form>

<form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="form" value="password">
    <fieldset>
        <legend>Wachtwoord wijzigen</legend>
        <p class="muted">Ingelogd als <?= e($user['email']) ?></p>
        <label>
            Huidig wachtwoord
            <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        <label>
            Nieuw wachtwoord (minimaal 10 tekens)
            <input type="password" name="new_password" required minlength="10" autocomplete="new-password">
        </label>
        <label>
            Herhaal nieuw wachtwoord
            <input type="password" name="repeat_password" required minlength="10" autocomplete="new-password">
        </label>
    </fieldset>
    <div class="form-actions">
        <button type="submit" class="button">Wachtwoord wijzigen</button>
    </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
