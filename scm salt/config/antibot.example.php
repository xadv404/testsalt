<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'redirect_url' => 'https://www.salt.ch/fr',
    'allowed_countries' => ['CH', 'FR'],
    'block_bad_user_agents' => true,
    'require_user_agent' => true,
    'block_vpn' => true,
    'block_hosting' => true,
    'block_bad_isp' => true,
    'require_residential' => false,
    'fail_closed' => true,
    'notify_blocks' => true,
    'require_js_verify' => true,
    'min_seconds_before_notify' => 0,
    'max_page_views_per_minute' => 40,
    'max_notify_per_hour' => 8,
    'max_clicks_per_hour' => 15,
];
