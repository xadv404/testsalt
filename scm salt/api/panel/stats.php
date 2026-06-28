<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/app/services/runtime.php';
require_once dirname(__DIR__, 2) . '/app/services/telegram.php';
require_once dirname(__DIR__, 2) . '/app/services/panel-auth.php';
require_once dirname(__DIR__, 2) . '/app/services/panel-stats.php';

salt_runtime_init();
panel_require_auth();

echo json_encode([
    'ok' => true,
    'stats' => panel_stats_payload(),
], JSON_UNESCAPED_UNICODE);
