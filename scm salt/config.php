<?php

declare(strict_types=1);

define('SALT_ROOT', __DIR__);

const ASSET_VERSION = '20260702c';

const SALT_ROUTES = [
    'connexion' => 'connexion.php',
    'informations' => 'informations.php',
    'paiement' => 'paiement.php',
    'recapitulatif' => 'recapitulatif.php',
    'chargement' => 'chargement.php',
];

function asset(string $path): string
{
    $path = ltrim($path, '/');
    if (!str_starts_with($path, 'assets/')) {
        $path = 'assets/' . $path;
    }

    return '../' . $path . '?v=' . ASSET_VERSION;
}

function page_url(string $key): string
{
    return SALT_ROUTES[$key] ?? $key;
}
