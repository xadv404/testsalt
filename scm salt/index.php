<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/services/runtime.php';
require_once __DIR__ . '/app/services/antibot.php';

salt_runtime_init();

antibot_init();
antibot_check_page();

header('Location: pages/connexion.php', true, 302);
exit;
