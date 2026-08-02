<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RT_Admin {

    public function __construct() {
        add_action( 'admin_menu',           [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts',[ $this, 'enqueue_assets' ] );
        add_action( 'admin_notices',        function() { RT()->license->admin_notices(); } );
        add_action( 'add_meta_boxes',       [ $this, 'add_metaboxes' ] );
        add_action( 'save_post_rt_room',    [ $this, 'save_room_metabox' ] );
        add_action( 'wp_ajax_rt_admin_action', [ $this, 'handle_ajax' ] );
        // Footer de crédito en todas las páginas del plugin
        add_filter( 'admin_footer_text', [ $this, 'admin_footer_credit' ] );
        add_filter( 'update_footer',     [ $this, 'admin_footer_version' ], 999 );
    }

    public function register_menus() {
        add_menu_page(
            'ReservaTotal',
            'ReservaTotal',
            'manage_options',
            'reservatotal',
            [ $this, 'page_dashboard' ],
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page( 'reservatotal', 'Panel principal',   'Panel',      'manage_options', 'reservatotal',               [ $this, 'page_dashboard' ] );
        add_submenu_page( 'reservatotal', 'Reservas',          'Reservas',   'manage_options', 'reservatotal-reservas',      [ $this, 'page_bookings' ] );
        add_submenu_page( 'reservatotal', 'Calendario',        'Calendario', 'manage_options', 'reservatotal-calendario',    [ $this, 'page_calendar' ] );
        add_submenu_page( 'reservatotal', 'Habitaciones',      'Habitaciones','manage_options','edit.php?post_type=rt_room',  null );
        add_submenu_page( 'reservatotal', 'Configuración',     'Configuración','manage_options','reservatotal-config',       [ $this, 'page_config' ] );
        add_submenu_page( 'reservatotal', 'Licencia',          'Licencia',   'manage_options', 'reservatotal-licencia',      [ $this, 'page_license' ] );
    }

    public function enqueue_assets( $hook ) {
        // Solo en páginas del plugin
        if ( strpos( $hook, 'reservatotal' ) === false && get_post_type() !== 'rt_room' ) return;

        wp_enqueue_style(  'rt-admin-css', RT_PLUGIN_URL . 'admin/admin.css', [], RT_VERSION );
        wp_enqueue_script( 'rt-admin-js',  RT_PLUGIN_URL . 'admin/admin.js',  [ 'jquery' ], RT_VERSION, true );
        wp_localize_script( 'rt-admin-js', 'RT_Admin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'rt_admin_nonce' ),
        ] );
    }

    // ─── PÁGINAS ───────────────────────────────────────────────

    public function page_dashboard() {
        $this->require_license();
        $stats = RT()->booking->get_stats();
        include RT_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function page_bookings() {
        $this->require_license();
        $action     = sanitize_key( $_GET['action'] ?? 'list' );
        $booking_id = absint( $_GET['id'] ?? 0 );

        if ( $action === 'view' && $booking_id ) {
            $booking = RT()->booking->get( $booking_id );
            include RT_PLUGIN_DIR . 'admin/views/booking-detail.php';
        } else {
            $status   = sanitize_key( $_GET['status'] ?? '' );
            $bookings = RT()->booking->get_list( [ 'status' => $status, 'limit' => 50 ] );
            include RT_PLUGIN_DIR . 'admin/views/bookings-list.php';
        }
    }

    public function page_calendar() {
        $this->require_license();
        $rooms = RT()->room->get_all();
        include RT_PLUGIN_DIR . 'admin/views/calendar.php';
    }

    public function page_config() {
        $this->require_license();
        if ( isset( $_POST['rt_save_config'] ) && check_admin_referer( 'rt_save_config' ) ) {
            $this->save_config();
            echo '<div class="notice notice-success"><p>✓ Configuración guardada.</p></div>';
        }
        include RT_PLUGIN_DIR . 'admin/views/config.php';
    }

    public function page_license() {
        $message = '';
        if ( isset( $_POST['rt_activate_license'] ) && check_admin_referer( 'rt_license_action' ) ) {
            $key     = sanitize_text_field( $_POST['rt_license_key'] ?? '' );
            $message = RT()->license->activate( $key );
        }
        include RT_PLUGIN_DIR . 'admin/views/license.php';
    }

    // ─── METABOX HABITACIÓN ────────────────────────────────────

    public function add_metaboxes() {
        add_meta_box(
            'rt_room_details',
            'Detalles de la habitación / cabaña',
            [ $this, 'render_room_metabox' ],
            'rt_room',
            'normal',
            'high'
        );
        add_meta_box(
            'rt_room_rates',
            'Tarifas y temporadas',
            [ $this, 'render_rates_metabox' ],
            'rt_room',
            'normal',
            'default'
        );
    }

    public function render_room_metabox( $post ) {
        wp_nonce_field( 'rt_room_metabox', 'rt_room_nonce' );
        $room = RT()->room->get_by_post( $post->ID );
        include RT_PLUGIN_DIR . 'admin/views/metabox-room.php';
    }

    public function render_rates_metabox( $post ) {
        $room  = RT()->room->get_by_post( $post->ID );
        $rates = $room ? RT()->pricing->get_rates( $room->id ) : [];
        include RT_PLUGIN_DIR . 'admin/views/metabox-rates.php';
    }

    public function save_room_metabox( $post_id ) {
        if ( ! isset( $_POST['rt_room_nonce'] ) || ! wp_verify_nonce( $_POST['rt_room_nonce'], 'rt_room_metabox' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        RT()->room->save( $post_id, $_POST );
    }

    // ─── AJAX ──────────────────────────────────────────────────

    public function handle_ajax() {
        check_ajax_referer( 'rt_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );

        $action = sanitize_key( $_POST['rt_action'] ?? '' );

        switch ( $action ) {
            case 'change_booking_status':
                $id     = absint( $_POST['booking_id'] );
                $status = sanitize_key( $_POST['status'] );
                $ok     = RT()->booking->update_status( $id, $status );
                wp_send_json( [ 'success' => $ok ] );
                break;

            case 'save_rate':
                $rate_id = RT()->pricing->save_rate( $_POST );
                wp_send_json( [ 'success' => true, 'rate_id' => $rate_id ] );
                break;

            case 'delete_rate':
                $ok = RT()->pricing->delete_rate( absint( $_POST['rate_id'] ) );
                wp_send_json( [ 'success' => (bool) $ok ] );
                break;

            case 'block_dates':
                global $wpdb;
                $wpdb->insert( $wpdb->prefix . 'rt_blocks', [
                    'room_id'   => absint( $_POST['room_id'] ),
                    'date_from' => sanitize_text_field( $_POST['date_from'] ),
                    'date_to'   => sanitize_text_field( $_POST['date_to'] ),
                    'reason'    => sanitize_text_field( $_POST['reason'] ?? '' ),
                ] );
                wp_send_json( [ 'success' => true, 'block_id' => $wpdb->insert_id ] );
                break;

            case 'get_occupied_dates':
                $dates = RT()->room->get_occupied_dates( absint( $_POST['room_id'] ) );
                wp_send_json_success( [ 'dates' => $dates ] );
                break;

            default:
                wp_send_json( [ 'success' => false, 'message' => 'Acción desconocida.' ] );
        }
    }

    // ─── Footer de crédito ─────────────────────────────────────

    public function admin_footer_credit( $text ) {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'reservatotal' ) === false ) return $text;
        return '🏨 <strong>ReservaTotal</strong> — Desarrollado por <a href="https://reservatotal.com.ar" target="_blank">Durval Muñoz Codazzi</a>';
    }

    public function admin_footer_version( $text ) {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'reservatotal' ) === false ) return $text;
        return 'Versión ' . RT_VERSION;
    }

    // ─── Helpers ───────────────────────────────────────────────

    private function require_license() {
        if ( ! RT()->license->is_active() ) {
            include RT_PLUGIN_DIR . 'admin/views/license-required.php';
            // No hacer exit — la vista debe hacer die() si corresponde
        }
    }

    private function save_config() {
        $fields = [
            'rt_mp_sandbox'             => 'checkbox',
            'rt_mp_access_token_test'   => 'text',
            'rt_mp_public_key_test'     => 'text',
            'rt_mp_access_token_prod'   => 'text',
            'rt_mp_public_key_prod'     => 'text',
            'rt_admin_email'            => 'email',
            'rt_weekend_surcharge'      => 'float',
            'rt_currency'               => 'text',
            'rt_checkin_time'           => 'text',
            'rt_checkout_time'          => 'text',
            'rt_booking_page_id'        => 'int',
        ];

        foreach ( $fields as $key => $type ) {
            switch ( $type ) {
                case 'checkbox':
                    update_option( $key, isset( $_POST[ $key ] ) ? '1' : '0' );
                    break;
                case 'email':
                    update_option( $key, sanitize_email( $_POST[ $key ] ?? '' ) );
                    break;
                case 'float':
                    update_option( $key, floatval( $_POST[ $key ] ?? 0 ) );
                    break;
                case 'int':
                    update_option( $key, absint( $_POST[ $key ] ?? 0 ) );
                    break;
                default:
                    update_option( $key, sanitize_text_field( $_POST[ $key ] ?? '' ) );
            }
        }
    }
}
