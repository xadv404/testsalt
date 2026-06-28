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
require_once dirname(__DIR__, 2) . '/app/services/panel-auth.php';
require_once dirname(__DIR__, 2) . '/app/services/panel-stats.php';

salt_runtime_init();
panel_require_auth();
panel_stats_reset_all();

echo json_encode(['ok' => true]);
