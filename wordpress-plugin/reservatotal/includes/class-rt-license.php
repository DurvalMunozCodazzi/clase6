<?php
/**
 * Clase de validación de licencia anual por dominio
 * Comunicación con servidor: licencias.reservatotal.ar
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class RT_License {

    const OPTION_KEY     = 'rt_license_key';
    const OPTION_STATUS  = 'rt_license_status';
    const OPTION_DATA    = 'rt_license_data';
    const OPTION_EXPIRES = 'rt_license_expires';

    // Valida la licencia contra el servidor remoto
    public function validate_remote( $key = null ) {
        $key = $key ?: get_option( self::OPTION_KEY, '' );
        if ( empty( $key ) ) {
            update_option( self::OPTION_STATUS, 'invalid' );
            return false;
        }

        $domain   = $this->get_clean_domain();
        $url1     = add_query_arg( [], str_replace( '/rtl/v1', '/rtl/v1/validate', RT_LICENSE_SERVER ) );
        $response = wp_remote_post( $url1, [
            'timeout'     => 15,
            'redirection' => 5,
            'body'        => [
                'license_key' => sanitize_text_field( $key ),
                'domain'      => $domain,
                'plugin_ver'  => RT_VERSION,
            ],
            'headers' => [
                'X-RT-Request' => 'license-check',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            // Si falla la conexión, usamos el estado cacheado (no bloqueamos el sitio)
            return get_option( self::OPTION_STATUS ) === 'active';
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 && isset( $body['status'] ) && $body['status'] === 'active' ) {
            update_option( self::OPTION_STATUS,  'active' );
            update_option( self::OPTION_EXPIRES, sanitize_text_field( $body['expires_at'] ?? '' ) );
            update_option( self::OPTION_DATA,    $body );
            return true;
        }

        // Licencia inválida, vencida o dominio no coincide
        $status = $body['status'] ?? 'invalid';
        update_option( self::OPTION_STATUS, $status );
        update_option( self::OPTION_DATA, $body );
        return false;
    }

    // Activa una licencia nueva en este dominio
    public function activate( $key ) {
        $key    = sanitize_text_field( $key );
        $domain = $this->get_clean_domain();

        $url2     = add_query_arg( [], str_replace( '/rtl/v1', '/rtl/v1/activate', RT_LICENSE_SERVER ) );
        $response = wp_remote_post( $url2, [
            'timeout' => 20,
            'body'    => [
                'license_key' => $key,
                'domain'      => $domain,
                'plugin_ver'  => RT_VERSION,
                'site_name'   => get_bloginfo( 'name' ),
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return [ 'success' => false, 'message' => 'No se pudo conectar con el servidor de licencias.' ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 && isset( $body['status'] ) && $body['status'] === 'active' ) {
            update_option( self::OPTION_KEY,     $key );
            update_option( self::OPTION_STATUS,  'active' );
            update_option( self::OPTION_EXPIRES, $body['expires_at'] ?? '' );
            update_option( self::OPTION_DATA,    $body );
            update_option( 'rt_license_last_check', time() );
            return [ 'success' => true, 'message' => '✓ Licencia activada correctamente.', 'data' => $body ];
        }

        $msg = $body['message'] ?? 'Licencia inválida o ya registrada en otro dominio.';
        return [ 'success' => false, 'message' => $msg ];
    }

    // Desactiva la licencia en este dominio (para transferirla)
    public function deactivate() {
        $key    = get_option( self::OPTION_KEY, '' );
        $domain = $this->get_clean_domain();
        if ( empty( $key ) ) return [ 'success' => false, 'message' => 'No hay licencia activa.' ];

        $url3     = add_query_arg( [], str_replace( '/rtl/v1', '/rtl/v1/deactivate', RT_LICENSE_SERVER ) );
        $response = wp_remote_post( $url3, [
            'timeout' => 15,
            'body'    => [
                'license_key' => $key,
                'domain'      => $domain,
            ],
        ] );

        delete_option( self::OPTION_STATUS );
        delete_option( self::OPTION_EXPIRES );
        delete_option( self::OPTION_DATA );
        delete_option( self::OPTION_KEY );
        delete_option( 'rt_license_last_check' );

        return [ 'success' => true, 'message' => 'Licencia desactivada en este dominio.' ];
    }

    // Estado actual de la licencia (sin consultar servidor)
    public function get_status() {
        return get_option( self::OPTION_STATUS, 'inactive' );
    }

    public function is_active() {
        // DEV_MODE: retorna siempre true hasta que el servidor de licencias esté configurado
        // Cambiá RT_DEV_MODE a false cuando tengas el servidor activo
        if ( defined('RT_DEV_MODE') && RT_DEV_MODE ) return true;
        return $this->get_status() === 'active';
    }

    public function get_expiry() {
        return get_option( self::OPTION_EXPIRES, '' );
    }

    public function get_key_masked() {
        $key = get_option( self::OPTION_KEY, '' );
        if ( strlen( $key ) < 8 ) return $key;
        return substr( $key, 0, 4 ) . str_repeat( '*', strlen( $key ) - 8 ) . substr( $key, -4 );
    }

    // Días hasta el vencimiento
    public function days_until_expiry() {
        $expiry = $this->get_expiry();
        if ( empty( $expiry ) ) return null;
        $diff = strtotime( $expiry ) - time();
        return max( 0, (int) ceil( $diff / DAY_IN_SECONDS ) );
    }

    // Devuelve el dominio limpio (sin www ni protocolo)
    public function get_clean_domain() {
        $site_url = get_site_url();
        $host     = parse_url( $site_url, PHP_URL_HOST );
        $host     = strtolower( preg_replace( '/^www\./', '', $host ) );
        return $host;
    }

    // Muestra aviso admin si la licencia está por vencer o inactiva
    public function admin_notices() {
        $status = $this->get_status();
        $days   = $this->days_until_expiry();

        if ( $status !== 'active' ) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>ReservaTotal:</strong> La licencia no está activa. ';
            echo '<a href="' . esc_url( admin_url( 'admin.php?page=reservatotal-licencia' ) ) . '">Activar licencia →</a>';
            echo '</p></div>';
            return;
        }

        if ( $days !== null && $days <= 30 ) {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            printf(
                '<strong>ReservaTotal:</strong> Tu licencia vence en <strong>%d días</strong>. <a href="%s" target="_blank">Renovar ahora →</a>',
                (int) $days,
                'https://reservatotal.com.ar/renovar'
            );
            echo '</p></div>';
        }
    }

    public static function on_activate() {
        // Al activar, marcamos estado como pendiente de activación
        if ( ! get_option( self::OPTION_STATUS ) ) {
            update_option( self::OPTION_STATUS, 'inactive' );
        }
    }
}
