<?php
/**
 * Plugin Name: Reserva Total
 * Plugin URI: https://github.com/DurvalMunozCodazzi/clase6
 * Description: Sistema de reservas de habitaciones. Usá el shortcode [reserva_total] en cualquier página para mostrar el buscador y el formulario de reservas, y el menú "Reserva Total" del escritorio para administrar habitaciones y reservas.
 * Version: 1.0.0
 * Author: Reserva Total
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: reserva-total
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Salir si se accede directamente.
}

define( 'RESERVA_TOTAL_VERSION', '1.0.0' );
define( 'RESERVA_TOTAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'RESERVA_TOTAL_URL', plugin_dir_url( __FILE__ ) );

require_once RESERVA_TOTAL_DIR . 'includes/class-reserva-total-db.php';
require_once RESERVA_TOTAL_DIR . 'includes/class-reserva-total-admin.php';
require_once RESERVA_TOTAL_DIR . 'includes/class-reserva-total-rest.php';
require_once RESERVA_TOTAL_DIR . 'includes/class-reserva-total-shortcode.php';

register_activation_hook( __FILE__, array( 'Reserva_Total_DB', 'install' ) );

add_action(
	'plugins_loaded',
	function () {
		new Reserva_Total_Admin();
		new Reserva_Total_REST();
		new Reserva_Total_Shortcode();
	}
);
