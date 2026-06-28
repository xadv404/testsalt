<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

render_page([
    'page_id' => 'page2',
    'title' => 'Informations personnelles — Salt',
    'view' => 'informations',
    'progress_step' => 2,
    'no_cache' => true,
    'scripts' => ['informations.js'],
]);
