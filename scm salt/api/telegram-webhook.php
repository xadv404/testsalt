<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/services/runtime.php';
require_once dirname(__DIR__) . '/app/services/ip-blocklist.php';

salt_runtime_init();
require_once dirname(__DIR__) . '/app/services/telegram.php';

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$update = json_decode($raw ?: '', true);

if (!is_array($update)) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$config = telegram_config();
if ($config === null || empty($config['enabled'])) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'telegram_disabled']);
    exit;
}

$secret = trim((string) ($config['webhook_secret'] ?? ''));
if ($secret !== '') {
    $header = trim((string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''));
    if (!hash_equals($secret, $header)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }
}

if (!isset($update['callback_query']) || !is_array($update['callback_query'])) {
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

$cq = $update['callback_query'];
$data = (string) ($cq['data'] ?? '');
$queryId = (string) ($cq['id'] ?? '');

if ($queryId === '' || !str_starts_with($data, 'ban:')) {
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

$ip = substr($data, 4);
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    telegram_answer_callback($queryId, 'IP invalide.', true);
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => 'invalid_ip']);
    exit;
}

$chatId = $cq['message']['chat']['id'] ?? '';
if (!telegram_is_allowed_callback_chat($config, $chatId)) {
    telegram_answer_callback($queryId, 'Non autorisé pour ce canal.', true);
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => 'chat_not_allowed']);
    exit;
}

ip_blocklist_add($ip);
telegram_answer_callback(
    $queryId,
    "IP BLOQUÉE\n\n" . $ip,
    true
);

http_response_code(200);
echo json_encode(['ok' => true, 'blocked' => $ip]);
