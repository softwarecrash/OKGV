<?php

$password = env('OKGV_DEMO_PASSWORD');
$explicitMode = env('OKGV_DEMO_MODE');

if ($explicitMode === '') {
    $explicitMode = null;
}

return [
    'enabled' => $explicitMode === null
        ? is_string($password) && trim($password) !== ''
        : (bool) $explicitMode,
    'password' => $password,
    'board_email' => env('OKGV_DEMO_BOARD_EMAIL', 'vorstand.demo@okgv.test'),
    'tenant_email' => env('OKGV_DEMO_TENANT_EMAIL', 'paechter1.demo@okgv.test'),
    'accounts' => [
        [
            'label' => 'Administrator',
            'description' => 'Vollzugriff auf die Demo-Konfiguration.',
            'email' => env('OKGV_DEMO_ADMIN_EMAIL', env('OKGV_ADMIN_EMAIL')),
            'password' => env('OKGV_DEMO_ADMIN_PASSWORD', env('OKGV_ADMIN_PASSWORD', $password)),
        ],
        [
            'label' => 'Vorstand',
            'description' => 'Verwaltung, Freigaben, Zählerstände und Abrechnung testen.',
            'email' => env('OKGV_DEMO_BOARD_EMAIL', 'vorstand.demo@okgv.test'),
            'password' => env('OKGV_DEMO_BOARD_PASSWORD', $password),
        ],
        [
            'label' => 'Pächter',
            'description' => 'Pächterportal, eigene Daten und Meldungen ausprobieren.',
            'email' => env('OKGV_DEMO_TENANT_EMAIL', 'paechter1.demo@okgv.test'),
            'password' => env('OKGV_DEMO_TENANT_PASSWORD', $password),
        ],
    ],
];
