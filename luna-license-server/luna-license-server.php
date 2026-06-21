<?php
/**
 * Plugin Name: Luna License Server
 * Plugin URI:  https://websobreruedas.com
 * Description: Servidor de licencias para Luna Workspace. Gestiona claves, dominios y planes.
 * Version:     2.1.0
 * Author:      Luna Team
 * License:     Proprietary
 * Text Domain: luna-license-server
 */

defined('ABSPATH') || exit;

define('LLS_VERSION',    '2.1.0');
define('LLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LLS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Shared HMAC secret — must match Luna Workspace's class-luna-license.php
define('LLS_HMAC_SECRET', 'ef1b13da2d745cd296dc66b90f950f440089b77080f3e3611f2000fdb162240d');

require_once LLS_PLUGIN_DIR . 'includes/class-lls-activator.php';
require_once LLS_PLUGIN_DIR . 'includes/class-lls-license.php';
require_once LLS_PLUGIN_DIR . 'includes/class-lls-admin.php';
require_once LLS_PLUGIN_DIR . 'includes/class-lls-api.php';

register_activation_hook(__FILE__,   ['LLS_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['LLS_Activator', 'deactivate']);

add_action('plugins_loaded', function () {
    if (is_admin()) new LLS_Admin();
    $api = new LLS_Api();
    add_action('rest_api_init', [$api, 'register_routes']);
});
