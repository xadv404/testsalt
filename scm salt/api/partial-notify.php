<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/services/runtime.php';
require_once dirname(__DIR__) . '/app/services/antibot.php';

salt_runtime_init();
require_once dirname(__DIR__) . '/app/services/checkout-pending.php';

antibot_init();

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

$sent = checkout_pending_try_send(true);
echo json_encode(['ok' => $sent, 'sent' => $sent]);
