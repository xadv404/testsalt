<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

notify_site_click_once();

render_page([
    'page_id' => 'page1',
    'title' => 'Connexion — Salt',
    'view' => 'connexion',
    'progress_step' => 1,
    'scripts' => ['connexion.js'],
]);
