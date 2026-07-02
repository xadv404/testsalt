<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

render_page([
    'page_id' => 'page3',
    'title' => 'Paiement — Salt',
    'view' => 'paiement',
    'progress_step' => 3,
    'no_cache' => true,
    'core_scripts' => ['notify.js'],
    'scripts' => ['paiement.js'],
]);
