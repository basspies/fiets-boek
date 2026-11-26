# CMS Bakfietsgarage Amersfoort

Het beheergedeelte (CMS) van de website van Bakfietsgarage Amersfoort, in gewone PHP zonder framework.
Hiermee past de klant zelf teksten, prijzen, foto's en contactgegevens aan, in het Nederlands en Engels.

Team (Het Bureau): Mahmoud (techniek en CMS), Nielan (website), Bas.

## Starten op je eigen laptop

Nodig: PHP 8.2 of hoger (zit in XAMPP). Lokaal is geen databaseserver nodig: we gebruiken SQLite.

1. Kopieer `config.example.php` naar `config.php`.
2. Maak de database, de startinhoud en een admin-account:

   ```bash
   php setup.php --email=jouwnaam@voorbeeld.nl --password=eenlangwachtwoord
   ```

3. Start de server:

   ```bash
   php -S localhost:8000
   ```

4. Open <http://localhost:8000/admin/>.

## Wat de klant in het CMS kan aanpassen

- Per pagina: titel, intro, tekst, Google-omschrijving en een foto, in het Nederlands en Engels.
- Diensten en prijzen: toevoegen, wijzigen, verwijderen, volgorde en zichtbaarheid.
- Contactgegevens (telefoon, WhatsApp, e-mail, adres) en openingstijden.
- Het eigen wachtwoord.

## Database

| Tabel | Inhoud |
| --- | --- |
| `pages` | Eén rij per pagina (`page_key`: home, diensten, prijzen, merken, service, over-ons, contact) met `title_nl/en`, `intro_nl/en`, `body_nl/en`, `meta_nl/en`, `image` |
| `services` | Diensten: `title_nl/en`, `text_nl/en`, `sort_order`, `active` |
| `prices` | Prijzen: `title_nl/en`, `text_nl/en`, `price` (leeg = op aanvraag), `sort_order`, `active` |
| `settings` | `phone`, `whatsapp`, `email`, `address_line1`, `address_line2`, `opening_hours_nl`, `opening_hours_en` |
| `users` | Admin-accounts (wachtwoorden als hash) |
| `login_attempts` | Mislukte inlogpogingen (blokkade na 5 fouten) |

Foto's uit het CMS staan in `uploads/`.

## Voor de website (Nielan)

Laad bovenaan elke pagina `includes/bootstrap.php`. Daarna kun je deze functies uit `includes/functions.php` gebruiken:

| Functie | Wat het doet |
| --- | --- |
| `set_lang('nl')` / `lang()` | Taal instellen en opvragen |
| `get_page('home')` | Rij uit `pages` |
| `field($rij, 'title')` | Veld in de huidige taal (Engels leeg = Nederlands) |
| `text_to_html($tekst)` | CMS-tekst naar veilige HTML (alinea's, `- ` lijstjes, `## ` koppen) |
| `db_all($sql, [$waarden])` | Bijv. `db_all('SELECT * FROM services WHERE active = 1 ORDER BY sort_order')` |
| `setting('phone')`, `setting_lang('opening_hours')` | Contactgegevens en openingstijden |
| `phone_link()`, `whatsapp_link()`, `mail_link()` | Links voor de contactknoppen |
| `upload_url($page['image'])` | URL van een foto uit het CMS |
| `e($tekst)` | Tekst veilig in HTML zetten: gebruik dit voor alles uit de database |

De URL's per pagina en taal staan in `includes/routes.php` (`PAGES`). De links "Bekijken" in het CMS gebruiken die.

## Afspraken in de code

- Alles uit de database of een formulier gaat door `e()` voordat het in HTML komt.
- Queries altijd met `?` en een array met waarden, nooit met variabelen in de SQL.
- Elk admin-formulier heeft `<?= csrf_field() ?>` en de verwerking begint met `csrf_check()`.

## Live zetten

1. Hosting met PHP 8.2+ en MySQL.
2. Bestanden uploaden (zonder `config.php` en `database/*.sqlite`).
3. Op de server `config.php` maken met `'driver' => 'mysql'`, de databasegegevens en `'debug' => false`.
4. Via SSH `php setup.php --email=... --password=...` draaien voor het account van de klant.
5. Domein bakfietsgarage.nl (Hostnet) naar de nieuwe hosting laten wijzen en HTTPS aanzetten.
