<?php
/**
 * Plugin Name:       Luna Workspace
 * Plugin URI:        https://websobreruedas.com
 * Description:       Pizarra Colaborativa, gestión de tareas, equipos y proyectos. Versión 11.1.3 | Por Web Sobre Ruedas | 2026 | misioneslahermosa.com
 * Version:           11.1.3
 * Author:            Web Sobre Ruedas
 * License:           Proprietary
 * Text Domain:       luna-workspace
 */

defined('ABSPATH') || exit;

define('LUNA_VERSION',     '11.1.3');
define('LUNA_PLUGIN_DIR',  plugin_dir_path(__FILE__));
define('LUNA_PLUGIN_URL',  plugin_dir_url(__FILE__));
define('LUNA_APP_DIR',     LUNA_PLUGIN_DIR . 'app/');
define('LUNA_APP_URL',     LUNA_PLUGIN_URL . 'app/');

require_once LUNA_PLUGIN_DIR . 'includes/class-luna-activator.php';
require_once LUNA_PLUGIN_DIR . 'includes/class-luna-admin.php';
require_once LUNA_PLUGIN_DIR . 'includes/class-luna-license.php';
require_once LUNA_PLUGIN_DIR . 'includes/class-luna-register.php';
add_action('init', ['Luna_Register', 'init']);

register_activation_hook(__FILE__,   ['Luna_Activator', 'activate']);
register_activation_hook(__FILE__,   'luna_set_activation_redirect');
register_deactivation_hook(__FILE__, ['Luna_Activator', 'deactivate']);

function luna_set_activation_redirect() {
    // Solo redirigir si es una activación real (no una actualización masiva)
    if (!isset($_GET['activate-multi'])) {
        set_transient('luna_activation_redirect', 1, 60);
    }
}

add_action('plugins_loaded', 'luna_init');
function luna_init() {
    // Always regenerate app/luna-wp-config.php if missing.
    if (!file_exists(LUNA_APP_DIR . 'luna-wp-config.php')) {
        Luna_Activator::regenerate_app_config();
    }


    if (is_admin()) {
        new Luna_Admin();
    }
    add_shortcode('luna_workspace', 'luna_render_shortcode');
    add_action('template_redirect',  'luna_fullpage_redirect');
    add_action('init',               'luna_handle_admin_enter');
    add_action('init',               'luna_handle_admin_enter_legacy');
    add_action('wp_ajax_luna_save_license',        'luna_ajax_save_license');
    add_action('wp_ajax_luna_check_license_status','luna_ajax_check_license_status');
    add_action('luna_hourly_check', 'luna_run_hourly_check');
}

function luna_run_hourly_check() {
    // Read reminder hour from Luna DB
    global $wpdb;
    $p = $wpdb->prefix . 'luna_';
    $row = $wpdb->get_row("SELECT meta_value FROM `{$p}app_settings` WHERE meta_key='reminder_schedule'", ARRAY_A);
    $schedule = $row ? json_decode($row['meta_value'], true) : [];
    if (empty($schedule['enabled'])) return;

    $hour = (int)($schedule['hour'] ?? 8);
    $current_hour = (int)date('G'); // server hour
    if ($current_hour !== $hour) return;

    // Fire the reminders endpoint — cron_secret va en el body POST (no en la URL para evitar logs)
    $secret = get_option('luna_cron_secret', '');
    if (!$secret) return;
    $url = LUNA_APP_URL . 'api/reminders.php?action=send';
    wp_remote_post($url, [
        'timeout'   => 60,
        'blocking'  => false,
        'headers'   => ['Content-Type' => 'application/json'],
        'body'      => json_encode(['cron_secret' => $secret]),
    ]);
}

// ── Admin enter con token permanente (no requiere sesión WP, no expira) ──────
function luna_handle_admin_enter() {
    if (!isset($_GET['luna_enter'])) return;

    $stored = get_option('luna_entry_token', '');
    if (!$stored || !hash_equals($stored, (string)$_GET['luna_enter'])) {
        wp_die('Token inválido. Regenerá el token en Luna Workspace → Configuración.');
    }

    global $wpdb;
    $p = $wpdb->prefix . 'luna_';

    // Buscar usuario admin de Luna
    $admin = $wpdb->get_row("SELECT * FROM `{$p}users` WHERE role='admin' AND active=1 ORDER BY id LIMIT 1", ARRAY_A);
    if (!$admin) {
        Luna_Activator::activate();
        $admin = $wpdb->get_row("SELECT * FROM `{$p}users` WHERE role='admin' AND active=1 ORDER BY id LIMIT 1", ARRAY_A);
    }
    if (!$admin) {
        wp_die('No se encontró usuario admin de Luna. Ir a Luna Workspace → Base de datos → Resetear contraseña admin.');
    }

    // Crear sesión Luna
    $token   = bin2hex(random_bytes(32));
    $hours   = (int) get_option('luna_session_hours', 24);
    $expires = date('Y-m-d H:i:s', time() + $hours * 3600);
    $wpdb->insert("{$p}sessions", ['token' => $token, 'user_id' => $admin['id'], 'expires_at' => $expires]);
    $wpdb->update("{$p}users", ['last_login' => current_time('mysql')], ['id' => $admin['id']]);

    // Cookie para el frontend
    setcookie('luna_token', $token, [
        'expires'  => time() + $hours * 3600,
        'path'     => '/',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    wp_redirect(home_url('/?luna_app=1'));
    exit;
}

// ── Admin enter legado (con nonce WP) — se mantiene por compatibilidad ───────
function luna_handle_admin_enter_legacy() {
    if (!isset($_GET['luna_admin_enter'])) return;
    if (!current_user_can('manage_options')) wp_die('Sin permisos. Iniciá sesión en WordPress primero.');
    if (!check_admin_referer('luna_admin_enter')) wp_die('Nonce inválido o expirado. Volvé al panel de WordPress y usá el botón desde ahí.');

    global $wpdb;
    $p = $wpdb->prefix . 'luna_';
    $admin = $wpdb->get_row("SELECT * FROM `{$p}users` WHERE role='admin' AND active=1 ORDER BY id LIMIT 1", ARRAY_A);
    if (!$admin) { wp_redirect(admin_url('admin.php?page=luna-database&luna_msg=no_admin')); exit; }

    $token   = bin2hex(random_bytes(32));
    $hours   = (int) get_option('luna_session_hours', 24);
    $expires = date('Y-m-d H:i:s', time() + $hours * 3600);
    $wpdb->insert("{$p}sessions", ['token' => $token, 'user_id' => $admin['id'], 'expires_at' => $expires]);
    $wpdb->update("{$p}users", ['last_login' => current_time('mysql')], ['id' => $admin['id']]);
    setcookie('luna_token', $token, ['expires' => time() + $hours * 3600, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax']);
    wp_redirect(home_url('/?luna_app=1'));
    exit;
}

// ── Full-page redirect ──────────────────────────────────────────────────────
function luna_fullpage_redirect() {
    $slug = get_option('luna_page_slug', 'luna-app');
    if (is_page($slug) || !empty($_GET['luna_app'])) {
        luna_serve_app();
        exit;
    }
}

// ── Shortcode ───────────────────────────────────────────────────────────────
function luna_render_shortcode($atts) {
    $height = isset($atts['height']) ? esc_attr($atts['height']) : '100vh';
    $app_url = home_url('/?luna_app=1');
    $license = get_option('luna_license_key', '');
    ob_start(); ?>
    <div id="luna-wp-container" style="width:100%;height:<?php echo $height ?>;overflow:hidden;position:relative">
      <script>
        window.LUNA_WP = {
          licenseKey: <?php echo json_encode($license) ?>,
          apiUrl:     <?php echo json_encode(LUNA_APP_URL . 'api') ?>,
          ajaxUrl:    <?php echo json_encode(admin_url('admin-ajax.php')) ?>,
          nonce:      <?php echo json_encode(wp_create_nonce('luna_nonce')) ?>,
          showGantt:  <?php echo get_option('luna_show_gantt', 1) ? 'true' : 'false' ?>
        };
      </script>
      <iframe src="<?php echo esc_url($app_url) ?>"
              style="width:100%;height:100%;border:none;display:block"
              allow="fullscreen">
      </iframe>
    </div>
    <?php return ob_get_clean();
}

// ── Full-page server (bypasses WP theme entirely) ───────────────────────────
function luna_serve_app() {
    $index = LUNA_APP_DIR . 'index.html';
    if (!file_exists($index)) {
        wp_die('Luna Workspace: el archivo app/index.html no está instalado en el plugin.');
    }
    // Prevent browser and proxy caching of the app shell
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

    $license = get_option('luna_license_key', '');
    $content = file_get_contents($index);
    // Inject LUNA_WP config before </head>
    $inject = '<style>'
        . '.ws-item .ws-img{width:38px!important;height:38px!important;border-radius:10px!important;flex-shrink:0!important;object-fit:cover;display:block}'
        . '.ws-item{position:relative;overflow:visible!important}'
        . '.ws-item .ws-actions{position:relative;z-index:2}'
        . '</style>'
        . '<script>window.LUNA_WP={licenseKey:' . json_encode($license)
        . ',apiUrl:'    . json_encode(LUNA_APP_URL . 'api')
        . ',ajaxUrl:'   . json_encode(admin_url('admin-ajax.php'))
        . ',nonce:'     . json_encode(wp_create_nonce('luna_nonce'))
        . ',showGantt:' . (get_option('luna_show_gantt', 1) ? 'true' : 'false')
        . '};</script>';
    $content = str_replace('</head>', $inject . '</head>', $content);
    // Fix relative API paths to absolute plugin paths
    $content = str_replace('src="api/', 'src="' . LUNA_APP_URL . 'api/', $content);
    $content = str_replace("src='api/", "src='" . LUNA_APP_URL . "api/", $content);
    // Inject permanent branding bar — always visible on all plans
    $content = str_replace('</body>', luna_branding_bar() . '</body>', $content);
    header('Content-Type: text/html; charset=utf-8');
    echo $content;
}

// ── Branding bar — JS-driven so it survives SPA body replacement ─────────────
function luna_branding_bar(): string {
    $css = '#lads{'
        . 'position:fixed!important;bottom:0!important;left:0!important;right:0!important;'
        . 'height:36px!important;background:#050810!important;'
        . 'border-top:1px solid #161d38!important;'
        . 'display:flex!important;align-items:center!important;justify-content:center!important;'
        . 'z-index:2147483647!important;font-family:\"Segoe UI\",system-ui,sans-serif!important;'
        . '}'
        . '#lads a{'
        . 'color:#2e3a6e!important;text-decoration:none!important;font-size:11px!important;'
        . 'font-weight:600!important;letter-spacing:.4px!important;'
        . 'display:flex!important;align-items:center!important;gap:5px!important;'
        . '}'
        . '#lads a:hover{color:#5b6af0!important;}';

    return '<script>'
        . '(function(){'
        .   'var css=' . json_encode($css) . ';'
        .   'function injectStyle(){'
        .     'if(document.getElementById("lads-css"))return;'
        .     'var s=document.createElement("style");s.id="lads-css";s.textContent=css;'
        .     '(document.head||document.documentElement).appendChild(s);'
        .   '}'
        .   'function injectBar(){'
        .     'injectStyle();'
        .     'if(document.getElementById("lads"))return;'
        .     'var d=document.createElement("div");d.id="lads";'
        .     'd.innerHTML="<a href=\"https://websobreruedas.com\" target=\"_blank\" rel=\"noopener\">'
        .       '🌙 Luna Workspace  ·  websobreruedas.com</a>";'
        .     'document.body.appendChild(d);'
        .     'document.documentElement.style.setProperty("padding-bottom","36px","important");'
        .   '}'
        .   'function watch(){'
        .     'new MutationObserver(function(){injectBar();}).observe(document.body,{childList:true});'
        .   '}'
        .   'if(document.readyState==="loading"){'
        .     'document.addEventListener("DOMContentLoaded",function(){injectBar();watch();});'
        .   '}else{injectBar();watch();}'
        . '})();'
        . '</script>';
}

// ── AJAX: save license key ───────────────────────────────────────────────────
function luna_ajax_save_license() {
    check_ajax_referer('luna_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    $key = sanitize_text_field($_POST['license_key'] ?? '');
    update_option('luna_license_key', $key);
    // Also write to app/config-license.php so API files can read it without WP
    luna_write_license_config($key);
    wp_send_json_success(['message' => 'Licencia guardada']);
}

// ── AJAX: verify license status ──────────────────────────────────────────────
function luna_ajax_check_license_status() {
    check_ajax_referer('luna_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    $key    = get_option('luna_license_key', '');
    $result = Luna_License::verify($key, $_SERVER['HTTP_HOST'] ?? '');
    wp_send_json($result);
}

// ── When license key is saved, regenerate credentials file ───────────────────
function luna_write_license_config($key) {
    Luna_Activator::regenerate_app_config();
    // Also delete license cache so it gets re-verified with new key
    @unlink(LUNA_APP_DIR . 'luna-license-cache.json');
}
