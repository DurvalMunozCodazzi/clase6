<?php
defined('ABSPATH') || exit;

class LLS_Api {

    public function register_routes(): void {
        register_rest_route('luna-licenses/v1', '/verify', [
            'methods'             => 'POST',
            'callback'            => [$this, 'verify'],
            'permission_callback' => '__return_true',
            'args'                => [
                'license_key' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                'domain'      => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);
    }

    public function verify(WP_REST_Request $request): WP_REST_Response {
        // Rate limit: max 10 requests per IP per minute
        $ip       = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $rate_key = 'lls_rate_' . md5($ip);
        $hits     = (int) get_transient($rate_key);
        if ($hits >= 10) {
            return new WP_REST_Response(['valid' => false, 'reason' => 'rate_limit', 'message' => 'Demasiadas verificaciones. Esperá 1 minuto.'], 429);
        }
        set_transient($rate_key, $hits + 1, MINUTE_IN_SECONDS);

        $key    = $request->get_param('license_key');
        $domain = $request->get_param('domain');

        if (empty($key) || empty($domain)) {
            return new WP_REST_Response(['valid' => false, 'reason' => 'missing_params', 'message' => 'Faltan parámetros requeridos.'], 400);
        }

        $result = LLS_License::verify($key, $domain);
        // Always return 200 — callers check the 'valid' field in the JSON body.
        // Returning 4xx causes security layers (Wordfence, mod_security) to intercept
        // the response and replace our JSON with their own error page.
        return new WP_REST_Response($result, 200);
    }
}
