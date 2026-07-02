<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/app/services/panel-auth.php';

panel_clear_authenticated();
echo json_encode(['ok' => true]);
