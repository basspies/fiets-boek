<?php
// Kopieer dit bestand naar config.php en vul je eigen gegevens in.
// config.php staat in .gitignore en komt dus niet in Git.

return [
    'db' => [
        // 'sqlite' = lokaal ontwikkelen, geen databaseserver nodig.
        // 'mysql'  = op de hosting.
        'driver' => 'sqlite',
        'path'   => __DIR__ . '/database/bakfietsgarage.sqlite',

        // Alleen nodig bij driver 'mysql':
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'bakfietsgarage',
        'user' => 'root',
        'pass' => '',
    ],

    // null = automatisch bepalen (werkt in XAMPP en met php -S).
    // Op de hosting eventueel vast invullen, bijvoorbeeld ''.
    'base_url' => null,

    // Op de live server op false zetten.
    'debug' => true,
];
