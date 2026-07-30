<?php
/**
 * Plugin Name: Tirada de Tarot — 3 Cartas
 * Description: Widget de tirada de tarot de 3 cartas (Pasado / Presente / Futuro) con modo digital (baraja mezclada al azar) y modo físico (registro de una tirada real hecha en mesa). Insértalo con el shortcode [tirada_tarot].
 * Version: 1.5.0
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

define('TDT_VERSION', '1.5.0');
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
        'significados' => tdt_significados(),
    ));
}

/**
 * Imagen de cabecera configurable desde el panel admin (Ajustes).
 * Se muestra arriba de ambos widgets ([tirada_tarot] y [horoscopo]).
 */
function tdt_header_image_html() {
    $url = get_option('tdt_header_image');
    if (!$url) {
        return '';
    }
    return '<div class="tdt-header-image" style="max-width:880px;margin:0 auto 1rem;">'
        . '<img src="' . esc_url($url) . '" alt="" style="display:block;width:100%;height:auto;border-radius:1rem;box-shadow:0 6px 20px rgba(0,0,0,0.45);" />'
        . '</div>';
}

function tdt_shortcode() {
    tdt_enqueue_assets();
    ob_start();
    include TDT_PLUGIN_PATH . 'templates/app.php';
    return tdt_header_image_html() . ob_get_clean();
}
add_shortcode('tirada_tarot', 'tdt_shortcode');

require_once TDT_PLUGIN_PATH . 'includes/significados.php';
require_once TDT_PLUGIN_PATH . 'includes/horoscopo.php';
