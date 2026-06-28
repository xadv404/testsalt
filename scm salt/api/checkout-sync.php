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

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

checkout_pending_save($payload);
echo json_encode(['ok' => true]);
