<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/app/services/telegram.php';
require_once dirname(__DIR__, 2) . '/app/services/panel-auth.php';

$authenticated = panel_is_authenticated();

echo json_encode([
    'ok' => true,
    'authenticated' => $authenticated,
    'configured' => panel_password_configured(),
    'expires_at' => $authenticated ? panel_session_expires_at() : null,
]);
