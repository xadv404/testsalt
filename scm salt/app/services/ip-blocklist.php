<?php

declare(strict_types=1);

function ip_blocklist_path(): string
{
    return dirname(__DIR__, 2) . '/data/blocked-ips.json';
}

function ip_blocklist_load(): array
{
    $path = ip_blocklist_path();
    if (!is_file($path)) {
        return [];
    }

    $data = json_decode((string) file_get_contents($path), true);

    return is_array($data) ? $data : [];
}

function ip_blocklist_save(array $list): void
{
    $dir = dirname(ip_blocklist_path());
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    file_put_contents(ip_blocklist_path(), json_encode($list, JSON_PRETTY_PRINT), LOCK_EX);
}

function ip_blocklist_add(string $ip): bool
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }

    $list = ip_blocklist_load();
    $list[$ip] = [
        'at' => time(),
        'source' => 'telegram',
    ];
    ip_blocklist_save($list);

    return true;
}

function ip_blocklist_is_blocked(string $ip): bool
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }

    $list = ip_blocklist_load();

    return isset($list[$ip]);
}
