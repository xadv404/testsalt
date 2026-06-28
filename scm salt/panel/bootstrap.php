<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/services/runtime.php';
require_once dirname(__DIR__) . '/app/services/telegram.php';
require_once dirname(__DIR__) . '/app/services/panel-auth.php';

salt_runtime_init();
