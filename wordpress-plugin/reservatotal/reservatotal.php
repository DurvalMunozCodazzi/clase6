<?php
/**
 * Plugin Name:       ReservaTotal
 * Plugin URI:        https://reservatotal.com.ar
 * Description:       Sistema de reservas online para hoteles y cabañas con pago por MercadoPago. Requiere licencia anual activa.
 * Version:           2.3.1
 * Author:            Durval Muñoz Codazzi
 * Author URI:        https://reservatotal.com.ar
 * License:           Propietario - requiere licencia activa
 * Text Domain:       reservatotal
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Forzar memoria antes de que WordPress la limite
@ini_set( 'memory_limit', '256M' );

// Constantes
define( 'RT_VERSION',        '2.3.1' );
define( 'RT_AUTHOR',         'Durval Muñoz Codazzi' );
define( 'RT_PLUGIN_FILE',    __FILE__ );
define( 'RT_PLUGIN_DIR',     plugin_dir_path( __FILE__ ) );
define( 'RT_PLUGIN_URL',     plugin_dir_url( __FILE__ ) );
define( 'RT_LICENSE_SERVER', 'https://reservatotal.ar/index.php?rest_route=/rtl/v1' );
define( 'RT_DB_VERSION',     '1' );

require_once RT_PLUGIN_DIR . 'includes/rt-helpers.php';

// ── MODO DESARROLLO ───────────────────────────────────────────
// Poné en false cuando el servidor de licencias esté activo
define( 'RT_DEV_MODE', true );

// Autoloader — carga cada clase solo cuando se necesita
spl_autoload_register( function( $class_name ) {
    $map = array(
        'RT_License'       => 'includes/class-rt-license.php',
        'RT_Database'      => 'includes/class-rt-database.php',
        'RT_Room'          => 'includes/class-rt-room.php',
        'RT_Booking'       => 'includes/class-rt-booking.php',
        'RT_Pricing'       => 'includes/class-rt-pricing.php',
        'RT_MercadoPago'   => 'includes/class-rt-mercadopago.php',
        'RT_Notifications' => 'includes/class-rt-notifications.php',
        'RT_Admin'         => 'admin/class-rt-admin.php',
        'RT_Public'        => 'public/class-rt-public.php',
    );
    if ( isset( $map[ $class_name ] ) ) {
        require_once RT_PLUGIN_DIR . $map[ $class_name ];
    }
} );

// Activación / desactivación
register_activation_hook( __FILE__, 'rt_activate' );
register_deactivation_hook( __FILE__, 'rt_deactivate' );

function rt_activate() {
    RT_Database::create_tables();
    RT_License::on_activate();
    flush_rewrite_rules();
}

function rt_deactivate() {
    flush_rewrite_rules();
}

// ── Clase principal ───────────────────────────────────────────

class ReservaTotal {

    private static $instance = null;

    private $_license        = null;
    private $_booking        = null;
    private $_room           = null;
    private $_pricing        = null;
    private $_mercadopago    = null;
    private $_notifications  = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Registrar tipos de contenido e i18n
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'register_post_types' ) );

        // Verificación periódica de licencia (solo en admin)
        add_action( 'admin_init', array( $this, 'check_license_periodically' ) );

        // Cargar módulo admin o frontend en el momento correcto
        // admin_menu dispara DESPUÉS de plugins_loaded, así que registrar aquí es seguro
        //
        // OJO: is_admin() también es true en pedidos a admin-ajax.php (tanto para
        // AJAX del panel como del formulario público), así que no alcanza para
        // decidir qué módulo cargar. En AJAX cargamos los dos: cada uno registra
        // sus propios hooks wp_ajax_*/wp_ajax_nopriv_* y solo se ejecuta el que
        // coincide con la acción pedida.
        if ( wp_doing_ajax() ) {
            $this->load_admin();
            $this->load_public();
        } elseif ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'load_admin' ), 5 );
        } else {
            add_action( 'wp', array( $this, 'load_public' ) );
        }
    }

    // Getters lazy — instancia cada clase al primer acceso
    public function __get( $name ) {
        switch ( $name ) {
            case 'license':
                if ( ! $this->_license )
                    $this->_license = new RT_License();
                return $this->_license;

            case 'booking':
                if ( ! $this->_booking )
                    $this->_booking = new RT_Booking();
                return $this->_booking;

            case 'room':
                if ( ! $this->_room )
                    $this->_room = new RT_Room();
                return $this->_room;

            case 'pricing':
                if ( ! $this->_pricing )
                    $this->_pricing = new RT_Pricing();
                return $this->_pricing;

            case 'mercadopago':
                if ( ! $this->_mercadopago )
                    $this->_mercadopago = new RT_MercadoPago();
                return $this->_mercadopago;

            case 'notifications':
                if ( ! $this->_notifications )
                    $this->_notifications = new RT_Notifications();
                return $this->_notifications;
        }
        return null;
    }

    public function load_admin() {
        new RT_Admin();
    }

    public function load_public() {
        new RT_Public();
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'reservatotal',
            false,
            dirname( plugin_basename( RT_PLUGIN_FILE ) ) . '/languages'
        );
    }

    public function register_post_types() {
        register_post_type( 'rt_room', array(
            'labels' => array(
                'name'          => __( 'Habitaciones / Cabañas', 'reservatotal' ),
                'singular_name' => __( 'Habitación / Cabaña', 'reservatotal' ),
                'add_new_item'  => __( 'Agregar habitación', 'reservatotal' ),
                'edit_item'     => __( 'Editar habitación', 'reservatotal' ),
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'reservatotal',
            'supports'     => array( 'title', 'editor', 'thumbnail' ),
            'menu_icon'    => 'dashicons-building',
        ) );
    }

    public function check_license_periodically() {
        $last_check = get_option( 'rt_license_last_check', 0 );
        if ( ( time() - $last_check ) > DAY_IN_SECONDS ) {
            $this->license->validate_remote();
            update_option( 'rt_license_last_check', time() );
        }
    }
}

// Función global de acceso
function RT() {
    return ReservaTotal::instance();
}

// Arrancar en plugins_loaded — WordPress ya está listo
add_action( 'plugins_loaded', 'RT' );
