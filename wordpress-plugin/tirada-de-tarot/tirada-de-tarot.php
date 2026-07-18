<?php
/**
 * Plugin Name: Tirada de Tarot — 3 Cartas
 * Description: Widget de tirada de tarot de 3 cartas (Pasado / Presente / Futuro) con modo digital (baraja mezclada al azar) y modo físico (registro de una tirada real hecha en mesa). Insértalo con el shortcode [tirada_tarot].
 * Version: 1.0.1
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Author: Durval Muñoz Codazzi
 * Author URI: https://websobreruedas.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tirada-de-tarot
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TDT_VERSION', '1.0.1');
define('TDT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TDT_PLUGIN_PATH', plugin_dir_path(__FILE__));

function tdt_enqueue_assets() {
    wp_enqueue_style(
        'tdt-style',
        TDT_PLUGIN_URL . 'assets/css/tarot.css',
        array(),
        TDT_VERSION
    );

    wp_enqueue_script(
        'tdt-script',
        TDT_PLUGIN_URL . 'assets/js/tarot.js',
        array(),
        TDT_VERSION,
        true
    );

    wp_localize_script('tdt-script', 'TDT_CONFIG', array(
        'imageBaseUrl' => TDT_PLUGIN_URL . 'assets/images/cards/',
    ));
}

function tdt_shortcode() {
    tdt_enqueue_assets();
    ob_start();
    include TDT_PLUGIN_PATH . 'templates/app.php';
    return ob_get_clean();
}
add_shortcode('tirada_tarot', 'tdt_shortcode');
