<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RT_MercadoPago {

    private $access_token;
    private $public_key;
    private $sandbox;
    private $api_base = 'https://api.mercadopago.com';

    public function __construct() {
        $this->sandbox      = get_option( 'rt_mp_sandbox', '1' ) === '1';
        $this->access_token = $this->sandbox
            ? get_option( 'rt_mp_access_token_test', '' )
            : get_option( 'rt_mp_access_token_prod', '' );
        $this->public_key   = $this->sandbox
            ? get_option( 'rt_mp_public_key_test', '' )
            : get_option( 'rt_mp_public_key_prod', '' );

        // Endpoint para webhook de MP
        add_action( 'rest_api_init', [ $this, 'register_webhook_endpoint' ] );
    }

    // Crear preferencia de pago en MP
    public function create_preference( $booking_id ) {
        $booking = RT()->booking->get( $booking_id );
        if ( ! $booking ) return null;

        $back_url_base = get_site_url() . '/?rt_booking_return=1&booking_id=' . $booking_id;

        $body = [
            'items' => [
                [
                    'id'          => 'reserva-' . $booking->booking_code,
                    'title'       => sprintf(
                        'Reserva %s — %s (%d noches)',
                        $booking->booking_code,
                        $booking->room_name,
                        $booking->nights
                    ),
                    'description' => sprintf(
                        'Check-in: %s · Check-out: %s',
                        date( 'd/m/Y', strtotime( $booking->checkin ) ),
                        date( 'd/m/Y', strtotime( $booking->checkout ) )
                    ),
                    'quantity'    => 1,
                    'unit_price'  => (float) $booking->total_price,
                    'currency_id' => $booking->currency,
                ]
            ],
            'payer' => [
                'name'  => $booking->guest_name,
                'email' => $booking->guest_email,
            ],
            'back_urls' => [
                'success' => $back_url_base . '&status=success',
                'failure' => $back_url_base . '&status=failure',
                'pending' => $back_url_base . '&status=pending',
            ],
            'auto_return'         => 'approved',
            'external_reference'  => (string) $booking_id,
            'notification_url'    => get_rest_url( null, 'reservatotal/v1/mp-webhook' ),
            'statement_descriptor'=> 'RESERVATOTAL',
            'expires'             => true,
            'expiration_date_to'  => date( 'Y-m-d\TH:i:s.000-04:00', strtotime( '+24 hours' ) ),
        ];

        $response = $this->api_request( 'POST', '/checkout/preferences', $body );

        if ( isset( $response['id'] ) ) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'rt_bookings',
                [ 'mp_preference_id' => $response['id'] ],
                [ 'id' => $booking_id ]
            );

            return [
                'preference_id'  => $response['id'],
                'init_point'     => $this->sandbox ? $response['sandbox_init_point'] : $response['init_point'],
                'sandbox_mode'   => $this->sandbox,
            ];
        }

        return null;
    }

    // Obtener datos de un pago
    public function get_payment( $payment_id ) {
        return $this->api_request( 'GET', '/v1/payments/' . absint( $payment_id ) );
    }

    // Registrar endpoint REST para webhook
    public function register_webhook_endpoint() {
        register_rest_route( 'reservatotal/v1', '/mp-webhook', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_webhook' ],
            'permission_callback' => '__return_true',
        ] );
    }

    // Procesar notificación IPN/webhook de MercadoPago
    public function handle_webhook( WP_REST_Request $request ) {
        $params = $request->get_params();
        $body   = $request->get_json_params();

        // MP puede enviar tipo 'payment' vía IPN
        $topic     = $params['topic'] ?? $body['type'] ?? '';
        $mp_id     = $params['id'] ?? $body['data']['id'] ?? '';

        if ( $topic !== 'payment' || empty( $mp_id ) ) {
            return new WP_REST_Response( [ 'status' => 'ignored' ], 200 );
        }

        // Consultar el pago en la API de MP
        $payment = $this->get_payment( $mp_id );
        if ( ! $payment || ! isset( $payment['external_reference'] ) ) {
            return new WP_REST_Response( [ 'status' => 'not_found' ], 200 );
        }

        $booking_id = absint( $payment['external_reference'] );
        $mp_status  = sanitize_text_field( $payment['status'] );
        $amount     = floatval( $payment['transaction_amount'] ?? 0 );

        // Guardar raw data para auditoría
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'rt_payments', [
            'booking_id'    => $booking_id,
            'mp_payment_id' => (string) $mp_id,
            'mp_status'     => $mp_status,
            'amount'        => $amount,
            'currency'      => 'ARS',
            'raw_data'      => wp_json_encode( $payment ),
        ] );

        RT()->booking->update_payment( $booking_id, $mp_id, $mp_status, $amount );

        return new WP_REST_Response( [ 'status' => 'ok' ], 200 );
    }

    // Petición autenticada a la API de MP
    private function api_request( $method, $endpoint, $body = null ) {
        if ( empty( $this->access_token ) ) return null;

        $args = [
            'method'  => $method,
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type'  => 'application/json',
                'X-Idempotency-Key' => wp_generate_uuid4(),
            ],
        ];

        if ( $body ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( $this->api_base . $endpoint, $args );

        if ( is_wp_error( $response ) ) return null;

        $decoded = json_decode( wp_remote_retrieve_body( $response ), true );
        return $decoded ?: null;
    }

    public function get_public_key() {
        return $this->public_key;
    }

    public function is_sandbox() {
        return $this->sandbox;
    }

    public function is_configured() {
        return ! empty( $this->access_token ) && ! empty( $this->public_key );
    }
}
