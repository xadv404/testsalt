<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

render_page([
    'page_id' => 'page4',
    'title' => 'Récapitulatif — Salt',
    'view' => 'recapitulatif',
    'progress_step' => 4,
    'no_cache' => true,
    'scripts' => ['recapitulatif.js'],
]);
