<?php
declare(strict_types=1);

// Alle pagina's van de website, met de URL per taal.
// De sleutel (bijv. 'diensten') is ook de page_key in de database
// en de naam van het bestand in templates/pages/.
const PAGES = [
    'home'      => ['nl' => '',                     'en' => ''],
    'diensten'  => ['nl' => 'diensten',             'en' => 'services'],
    'prijzen'   => ['nl' => 'prijzen',              'en' => 'prices'],
    'merken'    => ['nl' => 'merken',               'en' => 'brands'],
    'service'   => ['nl' => 'haal-en-brengservice', 'en' => 'pickup-and-delivery'],
    'over-ons'  => ['nl' => 'over-ons',             'en' => 'about-us'],
    'contact'   => ['nl' => 'contact',              'en' => 'contact'],
];

const LANGS = ['nl', 'en'];

// Zoekt bij een taal en URL-deel de juiste pagina. Geeft null als die niet bestaat.
function find_page_key(string $lang, string $slug): ?string
{
    foreach (PAGES as $key => $slugs) {
        if ($slugs[$lang] === $slug) {
            return $key;
        }
    }
    return null;
}

function page_url(string $key, ?string $lang = null): string
{
    $lang ??= lang();
    $slug = PAGES[$key][$lang];
    return url($lang . '/' . ($slug === '' ? '' : $slug));
}
