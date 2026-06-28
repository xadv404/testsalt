<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/app/services/runtime.php';
require_once dirname(__DIR__, 2) . '/app/services/telegram.php';
require_once dirname(__DIR__, 2) . '/app/services/panel-auth.php';

salt_runtime_init();

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
$password = trim((string) (is_array($payload) ? ($payload['password'] ?? '') : ''));

if ($password === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_password']);
    exit;
}

if (!panel_password_configured()) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'panel_not_configured']);
    exit;
}

if (!panel_password_valid($password)) {
    if (!panel_login_alert_rate_limited()) {
        notify_panel_login_failed();
    }
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'invalid_password']);
    exit;
}

panel_set_authenticated();
echo json_encode(['ok' => true]);
