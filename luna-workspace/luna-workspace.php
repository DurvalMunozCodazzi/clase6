<?php
/**
 * Plugin Name:       Luna Workspace
 * Plugin URI:        https://websobreruedas.ar
 * Description:       Workspace colaborativo estilo Kanban con gestión de tareas, equipos y proyectos.
 * Version:           2.0.0
 * Author:            Luna Team
 * License:           Proprietary
 * Text Domain:       luna-workspace
 */

defined('ABSPATH') || exit;

define('LUNA_VERSION',     '2.0.0');
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

    // Always ensure the ad bar is injected into index.html.
    // On plugin update the file is replaced — this re-injects on the next page load.
    luna_ensure_ad_bar();

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

    wp_redirect(LUNA_APP_URL . 'index.html');
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
    wp_redirect(LUNA_APP_URL . 'index.html');
    exit;
}

// ── Full-page redirect ──────────────────────────────────────────────────────
function luna_fullpage_redirect() {
    $slug = get_option('luna_page_slug', 'luna-app');
    if (is_page($slug) || get_query_var('luna_app')) {
        luna_serve_app();
        exit;
    }
}

// ── Shortcode ───────────────────────────────────────────────────────────────
function luna_render_shortcode($atts) {
    $height = isset($atts['height']) ? esc_attr($atts['height']) : '100vh';
    $app_url = LUNA_APP_URL . 'index.html';
    $license = get_option('luna_license_key', '');
    ob_start(); ?>
    <div id="luna-wp-container" style="width:100%;height:<?php echo $height ?>;overflow:hidden;position:relative">
      <script>
        window.LUNA_WP = {
          licenseKey: <?php echo json_encode($license) ?>,
          apiUrl:     <?php echo json_encode(LUNA_APP_URL . 'api') ?>,
          ajaxUrl:    <?php echo json_encode(admin_url('admin-ajax.php')) ?>,
          nonce:      <?php echo json_encode(wp_create_nonce('luna_nonce')) ?>,
          showGantt:  <?php echo get_option('luna_show_gantt', 1) ? 'true' : 'false' ?>,
          showAds:    <?php echo empty(get_option('luna_license_key', '')) ? 'true' : 'false' ?>
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
        . ',showAds:'   . (empty(get_option('luna_license_key', '')) ? 'true' : 'false')
        . '};</script>';
    $content = str_replace('</head>', $inject . '</head>', $content);
    // Fix relative API paths to absolute plugin paths
    $content = str_replace('src="api/', 'src="' . LUNA_APP_URL . 'api/', $content);
    $content = str_replace("src='api/", "src='" . LUNA_APP_URL . "api/", $content);
    header('Content-Type: text/html; charset=utf-8');
    echo $content;
}

// ── Ad bar: inject into index.html and keep it there across plugin updates ───
function luna_ensure_ad_bar() {
    $index = LUNA_APP_DIR . 'index.html';
    if (!file_exists($index) || !is_writable($index)) return;

    $content = file_get_contents($index);
    if ($content === false) return;

    // Already injected — nothing to do
    if (strpos($content, '<!-- LUNA-ADS-BAR -->') !== false) return;

    $bar = '<!-- LUNA-ADS-BAR -->'
        . '<style>'
        . '#lads{position:fixed;bottom:0;left:0;right:0;height:62px;background:linear-gradient(135deg,#06090f 0%,#0d1128 100%);border-top:1px solid #1a2040;display:flex;align-items:center;padding:0 14px;gap:10px;z-index:2147483647;box-shadow:0 -3px 20px rgba(0,0,0,.55);font-family:"Segoe UI",system-ui,sans-serif;box-sizing:border-box}'
        . '#lads *{box-sizing:border-box}'
        . '#lads-pub{font-size:8px;color:#2a3260;text-transform:uppercase;letter-spacing:.6px;font-weight:800;writing-mode:vertical-rl;transform:rotate(180deg);flex-shrink:0;user-select:none}'
        . '#lads-cnt{flex:1;display:flex;align-items:center;gap:10px;overflow:hidden;min-width:0}'
        . '#lads-img{height:38px;width:auto;border-radius:6px;object-fit:cover;flex-shrink:0;display:none}'
        . '#lads-txt{font-size:12px;color:#7880a8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;line-height:1.3}'
        . '#lads-lnk{font-size:11px;color:#a5b4fc;text-decoration:none;font-weight:700;white-space:nowrap;flex-shrink:0;transition:.15s}'
        . '#lads-lnk:hover{color:#c4b5fd}'
        . '#lads-cta{background:linear-gradient(135deg,#5b6af0,#8b5cf6);color:#fff!important;padding:9px 15px;border-radius:8px;font-size:11px;font-weight:800;text-decoration:none!important;white-space:nowrap;flex-shrink:0;box-shadow:0 2px 12px rgba(91,106,240,.35);transition:.15s}'
        . '#lads-cta:hover{box-shadow:0 4px 18px rgba(91,106,240,.55);transform:translateY(-1px)}'
        . '</style>'
        . '<div id="lads">'
        .   '<span id="lads-pub">Pub</span>'
        .   '<div id="lads-cnt">'
        .     '<img id="lads-img" src="" alt="">'
        .     '<span id="lads-txt"></span>'
        .     '<a id="lads-lnk" href="https://websobreruedas.com/luna-planes" target="_blank" rel="noopener" style="display:none"></a>'
        .   '</div>'
        .   '<a id="lads-cta" href="https://websobreruedas.com/luna-planes" target="_blank" rel="noopener">⚡ Quitar anuncios</a>'
        . '</div>'
        . '<script>'
        . '(function(){'
        .   'var W=window.LUNA_WP||(window.parent&&window.parent.LUNA_WP)||{};'
        .   'if(W.showAds===false){document.getElementById("lads").style.display="none";return;}'
        .   'document.documentElement.style.paddingBottom="62px";'
        .   'var DEF=['
        .     '{text:"Actualizá al plan Básico — equipos de hasta 5 personas desde $19/mes",link:"https://websobreruedas.com/luna-planes",cta:"Ver planes →"},'
        .     '{text:"Luna Workspace Pro — WhatsApp, Telegram y métricas avanzadas para tu equipo",link:"https://websobreruedas.com/luna-planes",cta:"Conocer →"},'
        .     '{text:"Gantt, recordatorios automáticos y soporte prioritario — Plan Profesional",link:"https://websobreruedas.com/luna-planes",cta:"Ver más →"}'
        .   '];'
        .   'var banners=DEF,cur=0,upUrl="https://websobreruedas.com/luna-planes";'
        .   'function render(){'
        .     'var b=banners[cur%banners.length];'
        .     'var img=document.getElementById("lads-img");'
        .     'var txt=document.getElementById("lads-txt");'
        .     'var lnk=document.getElementById("lads-lnk");'
        .     'var cta=document.getElementById("lads-cta");'
        .     'if(b.img){img.src=b.img;img.style.display="block";}else{img.style.display="none";}'
        .     'txt.textContent=b.text||"";'
        .     'if(b.link&&b.cta){lnk.href=b.link;lnk.textContent=b.cta;lnk.style.display="inline";}else{lnk.style.display="none";}'
        .     'if(upUrl)cta.href=upUrl;'
        .   '}'
        .   'fetch("https://websobreruedas.com/luna-ads.json",{cache:"default"})'
        .     '.then(function(r){return r.json();})'
        .     '.then(function(d){'
        .       'if(d.banners&&d.banners.length)banners=d.banners;'
        .       'if(d.upgrade_url)upUrl=d.upgrade_url;'
        .       'if(d.upgrade_text)document.getElementById("lads-cta").textContent=d.upgrade_text;'
        .       'render();'
        .     '}).catch(function(){});'
        .   'render();'
        .   'setInterval(function(){cur++;render();},9000);'
        . '})();'
        . '</script>';

    $content = str_replace('</body>', $bar . '</body>', $content);
    file_put_contents($index, $content, LOCK_EX);
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
