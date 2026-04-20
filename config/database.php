<?php

$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    $parsed = parse_url($databaseUrl);
    return [
        'driver'   => 'pgsql',
        'host'     => $parsed['host'],
        'port'     => $parsed['port'] ?? 5432,
        'database' => ltrim($parsed['path'], '/'),
        'username' => $parsed['user'],
        'password' => $parsed['pass'],
        'options'  => [
            PDO::PGSQL_ATTR_DISABLE_PREPARES => true,
        ],
    ];
}

// Fallback per sviluppo locale (Herd)
return [
    'driver'   => 'pgsql',
    'host'     => 'localhost',
    'port'     => 5432,
    'database' => 'blitzball',
    'username' => 'postgres',
    'password' => 'admin',
    'options'  => [],
];