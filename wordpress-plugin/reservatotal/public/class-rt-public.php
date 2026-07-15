<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RT_Public {

    public function __construct() {
        add_action( 'wp_enqueue_scripts',         [ $this, 'enqueue_assets' ] );
        add_shortcode( 'reservatotal_form',        [ $this, 'shortcode_booking_form' ] );
        add_shortcode( 'reservatotal_disponibilidad', [ $this, 'shortcode_availability' ] );
        add_action( 'wp_ajax_rt_public_action',        [ $this, 'handle_ajax' ] );
        add_action( 'wp_ajax_nopriv_rt_public_action', [ $this, 'handle_ajax' ] );
        add_action( 'template_redirect',          [ $this, 'handle_payment_return' ] );
    }

    public function enqueue_assets() {
        $page_id = (int) get_option( 'rt_booking_page_id', 0 );
        if ( ! is_page( $page_id ) && ! has_shortcode( get_post()->post_content ?? '', 'reservatotal_form' ) ) {
            // Solo cargar en páginas con el shortcode
            if ( ! is_singular() ) return;
            global $post;
            if ( ! $post || ( ! has_shortcode( $post->post_content, 'reservatotal_form' ) && ! has_shortcode( $post->post_content, 'reservatotal_disponibilidad' ) ) ) return;
        }

        wp_enqueue_style( 'rt-public-css', RT_PLUGIN_URL . 'public/css/public.css', [], RT_VERSION );
        wp_enqueue_script( 'rt-public-js',  RT_PLUGIN_URL . 'public/js/public.js', [ 'jquery' ], RT_VERSION, true );
        wp_localize_script( 'rt-public-js', 'RT_Public', [
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'rt_public_nonce' ),
            'public_key' => RT()->mercadopago->get_public_key(),
            'sandbox'    => RT()->mercadopago->is_sandbox(),
            'currency'   => get_option( 'rt_currency', 'ARS' ),
            'checkin_time'  => get_option( 'rt_checkin_time',  '14:00' ),
            'checkout_time' => get_option( 'rt_checkout_time', '10:00' ),
        ] );
    }

    // Shortcode principal: formulario de reserva completo
    public function shortcode_booking_form( $atts ) {
        if ( ! RT()->license->is_active() ) return '';

        $atts = shortcode_atts( [
            'room_id' => 0,
            'title'   => 'Reservar',
        ], $atts, 'reservatotal_form' );

        $rooms = RT()->room->get_all();
        if ( empty( $rooms ) ) {
            return '<p>No hay habitaciones disponibles por el momento.</p>';
        }

        $selected_room = absint( $atts['room_id'] );

        ob_start();
        include RT_PLUGIN_DIR . 'templates/booking-form.php';
        return ob_get_clean();
    }

    // Shortcode de disponibilidad simple (solo calendario)
    public function shortcode_availability( $atts ) {
        if ( ! RT()->license->is_active() ) return '';

        $atts = shortcode_atts( [ 'room_id' => 0 ], $atts );
        $room_id = absint( $atts['room_id'] );
        if ( ! $room_id ) return '<p>Especificá un room_id. Ej: [reservatotal_disponibilidad room_id="1"]</p>';

        $occupied = RT()->room->get_occupied_dates( $room_id );
        ob_start();
        include RT_PLUGIN_DIR . 'templates/availability-calendar.php';
        return ob_get_clean();
    }

    // AJAX: verificar disponibilidad y calcular precio
    public function handle_ajax() {
        check_ajax_referer( 'rt_public_nonce', 'nonce' );

        $action = sanitize_key( $_POST['rt_action'] ?? '' );

        switch ( $action ) {

            case 'check_availability':
                $room_id  = absint( $_POST['room_id'] );
                $checkin  = sanitize_text_field( $_POST['checkin'] );
                $checkout = sanitize_text_field( $_POST['checkout'] );

                if ( ! $room_id || ! $checkin || ! $checkout ) {
                    wp_send_json_error( 'Datos incompletos.' );
                }

                $available = RT()->room->is_available( $room_id, $checkin, $checkout );
                $price     = $available ? RT()->pricing->calculate( $room_id, $checkin, $checkout ) : null;

                wp_send_json_success( [
                    'available' => $available,
                    'price'     => $price,
                ] );
                break;

            case 'create_booking':
                $result = RT()->booking->create( $_POST );
                if ( ! $result['success'] ) {
                    wp_send_json_error( $result['message'] );
                }

                // Crear preferencia de pago en MP
                $pref = RT()->mercadopago->create_preference( $result['booking_id'] );
                if ( ! $pref ) {
                    wp_send_json_error( 'No se pudo generar el pago. Verificá la configuración de MercadoPago.' );
                }

                wp_send_json_success( [
                    'booking_id'    => $result['booking_id'],
                    'booking_code'  => $result['booking_code'],
                    'total'         => $result['total'],
                    'currency'      => $result['currency'],
                    'mp_init_point' => $pref['init_point'],
                    'preference_id' => $pref['preference_id'],
                    'sandbox'       => $pref['sandbox_mode'],
                ] );
                break;

            case 'get_occupied_dates':
                $room_id = absint( $_POST['room_id'] );
                $dates   = RT()->room->get_occupied_dates( $room_id );
                wp_send_json_success( [ 'dates' => $dates ] );
                break;

            default:
                wp_send_json_error( 'Acción no reconocida.' );
        }
    }

    // Procesar retorno desde MercadoPago
    public function handle_payment_return() {
        if ( ! isset( $_GET['rt_booking_return'] ) ) return;

        $booking_id = absint( $_GET['booking_id'] );
        $status     = sanitize_key( $_GET['status'] );

        if ( ! $booking_id ) return;

        $booking = RT()->booking->get( $booking_id );
        if ( ! $booking ) return;

        $page_id = get_option( 'rt_booking_page_id', 0 );
        $url     = $page_id ? get_permalink( $page_id ) : home_url('/');

        // La confirmación real la hace el webhook; aquí solo mostramos feedback al usuario
        $msg = [
            'success' => 'Tu pago fue procesado. Recibirás un email de confirmación.',
            'failure' => 'Hubo un problema con el pago. Podés intentarlo nuevamente.',
            'pending' => 'Tu pago está siendo procesado. Te avisaremos por email.',
        ];

        set_transient( 'rt_payment_msg_' . $booking_id, [
            'status'  => $status,
            'message' => $msg[ $status ] ?? '',
            'code'    => $booking->booking_code,
        ], 300 );

        wp_safe_redirect( add_query_arg( [ 'rt_paid' => $booking_id ], $url ) );
        exit;
    }
}
