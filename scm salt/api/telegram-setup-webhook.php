<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/services/runtime.php';
require_once dirname(__DIR__) . '/app/services/telegram.php';

salt_runtime_init();

$config = telegram_config();
if ($config === null || empty($config['enabled'])) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'telegram_disabled']);
    exit;
}

$expected = trim((string) ($config['panel_password'] ?? ''));
$key = trim((string) ($_GET['key'] ?? $_POST['key'] ?? ''));

if ($expected === '' || !hash_equals($expected, $key)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$registered = telegram_register_webhook();
$info = telegram_get_webhook_info();

echo json_encode([
    'ok' => $registered,
    'webhook_url' => telegram_webhook_url(),
    'telegram' => $info,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
