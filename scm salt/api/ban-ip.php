<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/services/runtime.php';
require_once dirname(__DIR__) . '/app/services/ip-blocklist.php';

salt_runtime_init();
require_once dirname(__DIR__) . '/app/services/telegram.php';

header('Content-Type: text/html; charset=utf-8');

$ip = trim((string) ($_GET['ip'] ?? ''));
$token = trim((string) ($_GET['t'] ?? ''));

function ban_ip_render(string $title, string $message, bool $success): void
{
    $color = $success ? '#0a7a3f' : '#b42318';
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
    echo '<style>body{font-family:system-ui,sans-serif;margin:0;padding:2rem;background:#f6f7f9;color:#111}';
    echo '.card{max-width:28rem;margin:0 auto;background:#fff;border-radius:12px;padding:1.5rem;box-shadow:0 8px 24px rgba(0,0,0,.08)}';
    echo 'h1{font-size:1.25rem;margin:0 0 .75rem;color:' . $color . '}p{margin:0;line-height:1.5}</style></head><body>';
    echo '<div class="card"><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
    echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
}

$config = telegram_config();
if ($config === null || empty($config['enabled'])) {
    http_response_code(503);
    ban_ip_render('Service indisponible', 'Telegram n’est pas configuré.', false);
    exit;
}

if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    http_response_code(400);
    ban_ip_render('IP invalide', 'L’adresse IP fournie n’est pas valide.', false);
    exit;
}

if ($token === '' || !telegram_verify_ban_ip_token($ip, $token)) {
    http_response_code(403);
    ban_ip_render('Accès refusé', 'Lien de bannissement invalide ou expiré.', false);
    exit;
}

if (ip_blocklist_is_blocked($ip)) {
    ban_ip_render('Déjà bloquée', "L’IP {$ip} est déjà bannie.", true);
    exit;
}

ip_blocklist_add($ip);
ban_ip_render('IP bannie', "L’IP {$ip} a été ajoutée à la blocklist.", true);
