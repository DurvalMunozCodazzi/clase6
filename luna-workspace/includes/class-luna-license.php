<?php
defined('ABSPATH') || exit;

class Luna_License {

    // Endpoint REST del plugin Luna License Server instalado en websobreruedas.com
    const SERVER      = 'https://websobreruedas.com/wp-json/luna-licenses/v1/verify';
    const HMAC_SECRET = '6f9751ba0e4d31a5516cd44894b432f2538cca00360d92f9668638a4679f5ebd';

    const PLANS = [
        'free'         => ['label' => 'Gratis',        'max_workspaces' => 1,   'max_sites' => 1],
        'starter'      => ['label' => 'Básico',        'max_workspaces' => 1,   'max_sites' => 1],
        'professional' => ['label' => 'Profesional',   'max_workspaces' => 3,   'max_sites' => 3],
        'unlimited'    => ['label' => 'Corporativo',   'max_workspaces' => 999, 'max_sites' => 999],
    ];

    public static function verify(string $key, string $domain): array {
        if (empty($key)) {
            return ['valid' => false, 'reason' => 'no_key', 'message' => 'No se ingresó una clave de licencia'];
        }

        $server    = get_option('luna_license_server_url', self::SERVER);
        $cache_key = 'luna_license_' . md5($key . $domain);
        $cached    = get_transient($cache_key);
        if ($cached !== false) return $cached;

        $response = wp_remote_post($server, [
            'timeout'    => 15,
            'user-agent' => 'Luna-Workspace/' . (defined('LUNA_VERSION') ? LUNA_VERSION : '2.0'),
            'headers'    => ['Content-Type' => 'application/json'],
            'body'       => wp_json_encode(['license_key' => $key, 'domain' => $domain]),
        ]);

        if (is_wp_error($response)) {
            // Modo offline: usar solo si existe cache previa válida para esta clave exacta
            $offline = get_option('luna_license_offline_cache_' . md5($key), null);
            if ($offline && is_array($offline) && !empty($offline['valid'])) {
                $offline['offline'] = true;
                return $offline;
            }
            // Sin cache previa — no conceder acceso a claves no verificadas antes
            return [
                'valid'   => false,
                'reason'  => 'server_unreachable',
                'message' => 'No se pudo contactar el servidor de licencias ('
                             . $response->get_error_message()
                             . '). Verificá tu conexión o contactá al vendedor.',
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code === 429) {
            return ['valid' => false, 'reason' => 'rate_limit',
                    'message' => 'Demasiadas verificaciones. Esperá 1 minuto.'];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) {
            return ['valid' => false, 'reason' => 'bad_response',
                    'message' => 'Respuesta inválida del servidor de licencias (HTTP ' . $code . ')'];
        }

        // Verificar firma HMAC — protege contra servidores falsos
        if (!empty($data['hmac']) && !empty($data['issued_at'])) {
            $payload  = implode('|', [
                $key,
                $data['domain']     ?? $domain,
                !empty($data['valid']) ? 'true' : 'false',
                $data['plan']       ?? '',
                $data['expires_at'] ?? '',
                $data['issued_at'],
            ]);
            $expected = hash_hmac('sha256', $payload, self::HMAC_SECRET);
            if (!hash_equals($expected, $data['hmac'])) {
                return ['valid' => false, 'reason' => 'invalid_signature',
                        'message' => 'La firma de la respuesta del servidor es inválida.'];
            }
        }

        // Guardar cache persistente para modo offline (solo si la licencia es válida)
        if (!empty($data['valid'])) {
            update_option('luna_license_offline_cache_' . md5($key), $data, false);
        }

        $ttl = !empty($data['valid']) ? DAY_IN_SECONDS : HOUR_IN_SECONDS;
        set_transient($cache_key, $data, $ttl);
        return $data;
    }

    public static function clear_cache(string $key): void {
        $domain = parse_url(get_site_url(), PHP_URL_HOST) ?? '';
        delete_transient('luna_license_' . md5($key . $domain));
        delete_transient('luna_license_' . md5($key));
        delete_option('luna_license_offline_cache_' . md5($key));
    }

    public static function plan_label(string $plan): string {
        return self::PLANS[$plan]['label'] ?? ucfirst($plan);
    }
}
