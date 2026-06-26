<?php
defined('ABSPATH') || exit;

class Luna_Admin {

    public function __construct() {
        add_action('admin_menu',            [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_notices',         [$this, 'show_notices']);
        add_action('admin_init',            [$this, 'maybe_migrate']);
        add_action('admin_init',            [$this, 'maybe_redirect_to_wizard']);
        add_action('wp_ajax_luna_wizard_validate_license', [$this, 'ajax_wizard_validate_license']);
        add_action('wp_ajax_luna_dismiss_pass',            [$this, 'ajax_dismiss_initial_pass']);
        add_action('wp_ajax_luna_wizard_done',             [$this, 'ajax_wizard_done']);
        add_action('wp_ajax_luna_test_notification',  [$this, 'ajax_test_notification']);
        add_action('wp_ajax_luna_save_user_contact',  [$this, 'ajax_save_user_contact']);
        add_action('wp_ajax_luna_reset_admin_pass',   [$this, 'ajax_reset_admin_pass']);
        add_action('wp_ajax_luna_db_maintenance',     [$this, 'ajax_db_maintenance']);
        add_action('wp_ajax_luna_save_reminders',     [$this, 'ajax_save_reminders']);
        add_action('wp_ajax_luna_send_reminders_now', [$this, 'ajax_send_reminders_now']);
        add_action('wp_ajax_luna_backup_create',      [$this, 'ajax_backup_create']);
        add_action('wp_ajax_luna_backup_list',        [$this, 'ajax_backup_list']);
        add_action('wp_ajax_luna_backup_delete',      [$this, 'ajax_backup_delete']);
        add_action('wp_ajax_luna_backup_download',    [$this, 'ajax_backup_download']);
        add_action('wp_ajax_luna_backup_restore',     [$this, 'ajax_backup_restore']);
    }

    public function show_db_diagnostic() {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'luna-notifications') === false) return;

        global $wpdb;
        $cfg_file = plugin_dir_path(__FILE__) . '../app/luna-wp-config.php';
        $cfg_exists = file_exists($cfg_file);
        $cfg_defs = [];
        if ($cfg_exists) {
            preg_match_all("/define\('([^']+)',\s*'([^']*)'\)/", file_get_contents($cfg_file), $cm, PREG_SET_ORDER);
            foreach ($cm as $row) $cfg_defs[$row[1]] = $row[2];
        }
        $cfg_db   = $cfg_defs['DB_NAME']        ?? '—';
        $cfg_host = $cfg_defs['DB_HOST']         ?? '—';
        $cfg_user = $cfg_defs['DB_USER']         ?? '—';
        $cfg_pfx  = $cfg_defs['LUNA_TB_PREFIX']  ?? '—';

        $app_count = 0;
        $app_error = '';
        $appDb = $this->get_app_db();
        $appPfx = $this->get_app_prefix();
        if ($appDb && $appPfx !== null) {
            $tbl = $appPfx ? "`{$appPfx}users`" : '`users`';
            try {
                $app_count = (int) $appDb->query("SELECT COUNT(*) FROM {$tbl}")->fetchColumn();
            } catch (Exception $e) {
                $app_error = $e->getMessage();
            }
        } elseif (!$appDb) {
            $app_error = 'no se pudo conectar (config inválida o DB inaccesible)';
        } else {
            $app_error = 'LUNA_TB_PREFIX no encontrado en config';
        }

        $wp_p = $wpdb->prefix . 'luna_';
        $wp_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$wp_p}users`");
        $wp_error = $wpdb->last_error;

        echo '<div class="notice notice-info" style="font-family:monospace;font-size:12px">';
        echo '<p><strong>🔍 Luna DB Diagnóstico (Notificaciones)</strong></p>';
        echo '<table style="border-collapse:collapse">';
        echo '<tr><td style="padding:2px 12px 2px 0;color:#555">Config existe:</td><td>' . ($cfg_exists ? '<b style="color:green">Sí</b>' : '<b style="color:red">NO</b>') . '</td></tr>';
        echo '<tr><td style="padding:2px 12px 2px 0;color:#555">Config DB_HOST:</td><td>' . esc_html($cfg_host) . '</td></tr>';
        echo '<tr><td style="padding:2px 12px 2px 0;color:#555">Config DB_NAME:</td><td>' . esc_html($cfg_db) . '</td></tr>';
        echo '<tr><td style="padding:2px 12px 2px 0;color:#555">Config DB_USER:</td><td>' . esc_html($cfg_user) . '</td></tr>';
        echo '<tr><td style="padding:2px 12px 2px 0;color:#555">Config LUNA_TB_PREFIX:</td><td>' . esc_html($cfg_pfx) . '</td></tr>';
        echo '<tr><td style="padding:2px 12px 2px 0;color:#555">App DB usuarios:</td><td><b>' . $app_count . '</b>' . ($app_error ? ' — <span style="color:red">' . esc_html($app_error) . '</span>' : '') . '</td></tr>';
        echo '<tr><td style="padding:2px 12px 2px 0;color:#555">WP DB (' . esc_html($wpdb->dbname) . ') prefix ' . esc_html($wp_p) . ' usuarios:</td><td><b>' . $wp_count . '</b>' . ($wp_error ? ' — <span style="color:red">' . esc_html($wp_error) . '</span>' : '') . '</td></tr>';
        echo '</table></div>';
    }

    // Connect to the same DB the Luna app uses (may differ from WordPress DB)
    private function get_app_db(): ?PDO {
        $cfg = plugin_dir_path(__FILE__) . '../app/luna-wp-config.php';
        if (!file_exists($cfg)) return null;
        $defs = [];
        preg_match_all("/define\('([^']+)',\s*'([^']*)'\)/", file_get_contents($cfg), $m, PREG_SET_ORDER);
        foreach ($m as $row) $defs[$row[1]] = $row[2];
        if (empty($defs['DB_HOST']) || empty($defs['DB_NAME'])) return null;
        try {
            $dsn = "mysql:host={$defs['DB_HOST']};dbname={$defs['DB_NAME']};charset=" . ($defs['DB_CHARSET'] ?? 'utf8mb4');
            $pdo = new PDO($dsn, $defs['DB_USER'] ?? '', $defs['DB_PASS'] ?? '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return $pdo;
        } catch (Exception $e) { return null; }
    }

    // Return the table prefix the app uses, or null if the config file is missing.
    // Empty string is a valid prefix (means tables have no prefix: `users`, `workspaces`…).
    private function get_app_prefix(): ?string {
        $cfg = plugin_dir_path(__FILE__) . '../app/luna-wp-config.php';
        if (!file_exists($cfg)) return null;
        preg_match("/define\('LUNA_TB_PREFIX',\s*'([^']*)'\)/", file_get_contents($cfg), $m);
        return $m[1] ?? null; // null = define not found; '' = explicitly empty (valid)
    }

    public function maybe_migrate(): void {
        // Run at most once per day — prevents SHOW COLUMNS on every AJAX request
        if (get_transient('luna_migration_done_' . LUNA_VERSION)) return;
        global $wpdb;
        $p = $wpdb->prefix . 'luna_';
        Luna_Activator::add_column_if_missing($wpdb, "{$p}users", 'phone',               "VARCHAR(30) DEFAULT ''");
        Luna_Activator::add_column_if_missing($wpdb, "{$p}users", 'whatsapp_apikey',      "VARCHAR(100) DEFAULT ''");
        Luna_Activator::add_column_if_missing($wpdb, "{$p}users", 'telegram_chat_id',     "VARCHAR(50) DEFAULT NULL");
        Luna_Activator::add_column_if_missing($wpdb, "{$p}users", 'notification_channel', "ENUM('email','whatsapp','telegram','all','none') DEFAULT 'email'");
        Luna_Activator::add_column_if_missing($wpdb, "{$p}users", 'cargo',                "VARCHAR(120) DEFAULT ''");
        Luna_Activator::add_column_if_missing($wpdb, "{$p}users", 'dept',                 "VARCHAR(120) DEFAULT ''");
        Luna_Activator::add_column_if_missing($wpdb, "{$p}users", 'color',                "VARCHAR(10) DEFAULT '#5b6af0'");
        Luna_Activator::add_column_if_missing($wpdb, "{$p}users", 'photo',                "MEDIUMTEXT DEFAULT NULL");
        Luna_Activator::add_column_if_missing($wpdb, "{$p}users", 'notes',                "TEXT DEFAULT ''");
        Luna_Activator::add_column_if_missing($wpdb, "{$p}users", 'last_login',           "DATETIME NULL");
        set_transient('luna_migration_done_' . LUNA_VERSION, 1, DAY_IN_SECONDS);
    }

    public function register_menu() {
        add_menu_page(
            'Luna Workspace',
            'Luna Workspace',
            'manage_options',
            'luna-workspace',
            [$this, 'render_main_page'],
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>'),
            30
        );
        add_submenu_page('luna-workspace', 'Configuración', 'Configuración', 'manage_options', 'luna-workspace', [$this, 'render_main_page']);
        add_submenu_page('luna-workspace', 'Licencia',      'Licencia',      'manage_options', 'luna-license',         [$this, 'render_license_page']);
        add_submenu_page('luna-workspace', 'Notificaciones','Notificaciones','manage_options', 'luna-notifications',   [$this, 'render_notifications_page']);
        add_submenu_page('luna-workspace', 'Base de datos', 'Base de datos', 'manage_options', 'luna-database',        [$this, 'render_database_page']);
        add_submenu_page('luna-workspace', 'Backup & Restore', 'Backup & Restore', 'manage_options', 'luna-backup', [$this, 'render_backup_page']);
        // Wizard — oculto del menú pero accesible por URL
        add_submenu_page(null, 'Luna — Configuración inicial', '', 'manage_options', 'luna-onboarding', [$this, 'render_onboarding_wizard']);
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'luna') === false) return;
        wp_enqueue_style('luna-admin-css', LUNA_PLUGIN_URL . 'admin/admin.css', [], LUNA_VERSION);
        wp_enqueue_script('luna-admin-js', LUNA_PLUGIN_URL . 'admin/admin.js', ['jquery'], LUNA_VERSION, true);
        wp_localize_script('luna-admin-js', 'lunaAdmin', [
            'nonce'   => wp_create_nonce('luna_admin_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ]);
    }

    public function show_notices() {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'luna') === false) return;
        // Show banner but do NOT delete the option here — render_main_page() deletes it
        // after displaying it in the yellow box so the user can copy it
        $initial_pass = get_option('luna_initial_admin_pass');
        if ($initial_pass) {
            echo '<div class="notice notice-warning is-dismissible"><p>'
                . '<strong>Luna Workspace — Primera instalación:</strong> '
                . 'La contraseña del admin se muestra más abajo en esta página bajo <strong>"🔐 Contraseña del administrador Luna"</strong>. '
                . 'Andate a <a href="' . admin_url('admin.php?page=luna-workspace') . '">Luna Workspace → Configuración</a> para verla.</p></div>';
        }
        $key = get_option('luna_license_key', '');
        if (empty($key)) {
            echo '<div class="notice notice-warning"><p><strong>Luna Workspace:</strong> No hay licencia configurada. <a href="' . admin_url('admin.php?page=luna-license') . '">Activar licencia →</a></p></div>';
        }
    }

    // ── Main settings page ────────────────────────────────────────────────────
    public function render_main_page() {
        if (isset($_POST['luna_save_settings']) && check_admin_referer('luna_settings')) {
            update_option('luna_page_slug',    sanitize_text_field($_POST['luna_page_slug'] ?? 'luna-app'));
            update_option('luna_session_hours', absint($_POST['luna_session_hours'] ?? 24));
            update_option('luna_show_gantt',   isset($_POST['luna_show_gantt']) ? 1 : 0);
            Luna_Activator::regenerate_app_config();
            echo '<div class="notice notice-success"><p>Configuración guardada.</p></div>';
        }
        $slug        = get_option('luna_page_slug', 'luna-app');
        $hours       = get_option('luna_session_hours', 24);
        $show_gantt  = get_option('luna_show_gantt', 1);
        $entry_token = get_option('luna_entry_token', '');
        // Generar token si todavía no existe (instalaciones anteriores)
        if (!$entry_token) {
            $entry_token = bin2hex(random_bytes(24));
            update_option('luna_entry_token', $entry_token);
        }
        $permanent_url = add_query_arg('luna_enter', $entry_token, home_url('/'));
        $app_direct    = home_url('/?luna_app=1');

        // ¿Regenerar token?
        if (isset($_POST['luna_regen_token']) && check_admin_referer('luna_settings')) {
            $entry_token   = bin2hex(random_bytes(24));
            update_option('luna_entry_token', $entry_token);
            $permanent_url = add_query_arg('luna_enter', $entry_token, home_url('/'));
            echo '<div class="notice notice-success"><p>✅ Token regenerado. Actualizá el botón en Plesk con la nueva URL.</p></div>';
        }
        ?>
        <div class="wrap luna-wrap">
          <h1>🌙 Luna Workspace — Configuración</h1>

          <div class="luna-card" style="margin-bottom:20px;background:linear-gradient(135deg,#5b6af0,#7c3aed);color:#fff;border-radius:12px;padding:28px 32px">
            <h2 style="color:#fff;margin:0 0 6px">🚀 Acceso directo a Luna</h2>
            <p style="color:rgba(255,255,255,.8);margin:0 0 14px;font-size:13px">Esta URL es permanente — no expira y no requiere estar logueado en WordPress. Usala en Plesk o como marcador.</p>
            <div style="display:flex;align-items:center;gap:10px;background:rgba(0,0,0,.25);border-radius:8px;padding:10px 14px;margin-bottom:14px">
              <code id="luna-perm-url" style="color:#fff;font-size:12px;word-break:break-all;flex:1"><?php echo esc_url($permanent_url) ?></code>
              <button onclick="navigator.clipboard.writeText(document.getElementById('luna-perm-url').textContent);this.textContent='✓ Copiado!';setTimeout(()=>this.textContent='Copiar',2000)"
                      style="background:#fff;color:#5b6af0;border:none;border-radius:6px;padding:6px 14px;font-weight:700;cursor:pointer;white-space:nowrap">Copiar</button>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
              <a href="<?php echo esc_url($permanent_url) ?>" target="_blank"
                 style="display:inline-block;background:#fff;color:#5b6af0;padding:10px 24px;border-radius:8px;font-weight:700;font-size:14px;text-decoration:none">
                Entrar a Luna →
              </a>
              <form method="POST" style="margin:0">
                <?php wp_nonce_field('luna_settings') ?>
                <button type="submit" name="luna_regen_token"
                        style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.4);border-radius:8px;padding:10px 18px;font-size:13px;cursor:pointer"
                        onclick="return confirm('¿Regenerar token? La URL anterior dejará de funcionar.')">
                  🔄 Regenerar token
                </button>
              </form>
            </div>
          </div>

          <div class="luna-grid">
            <div class="luna-card">
              <h2>URL directa del app</h2>
              <p><a href="<?php echo esc_url($app_direct) ?>" target="_blank"><?php echo esc_html($app_direct) ?></a></p>
              <p style="color:#666;font-size:12px">Sin auto-login. Mostrará el formulario de ingreso de Luna.</p>
              <p>Shortcode: <code>[luna_workspace]</code></p>
            </div>
            <form method="POST" class="luna-card">
              <?php wp_nonce_field('luna_settings') ?>
              <h2>Ajustes generales</h2>
              <table class="form-table">
                <tr>
                  <th>Slug de la página</th>
                  <td><input type="text" name="luna_page_slug" value="<?php echo esc_attr($slug) ?>" class="regular-text">
                      <p class="description">URL: <?php echo home_url('/') ?>[ slug ]/</p></td>
                </tr>
                <tr>
                  <th>Duración de sesión (horas)</th>
                  <td><input type="number" name="luna_session_hours" value="<?php echo esc_attr($hours) ?>" min="1" max="720" class="small-text"></td>
                </tr>
                <tr>
                  <th>Vista Gantt</th>
                  <td>
                    <label>
                      <input type="checkbox" name="luna_show_gantt" value="1" <?php checked(1, $show_gantt) ?>>
                      Mostrar pestaña Gantt en la aplicación
                    </label>
                    <p class="description">Si está desactivada, solo se ve la pizarra Kanban.</p>
                  </td>
                </tr>
              </table>
              <p><button type="submit" name="luna_save_settings" class="button button-primary">Guardar cambios</button></p>
            </form>
          </div>

          <?php
          // ── Admin password panel ─────────────────────────────────────────────
          // Check if there's a stored initial/reset password to show
          $pending_pass = get_option('luna_initial_admin_pass', '');
          ?>
          <div class="luna-card" style="margin-top:20px" id="luna-admin-pass-card">
            <h2 style="margin-top:0">🔐 Contraseña del administrador Luna</h2>
            <p style="color:#666;font-size:13px;margin-top:-8px">
              El usuario <code>admin</code> es quien ingresa al tablero de Luna.
              Si olvidaste la contraseña, generá una nueva acá — no necesitás entrar a MySQL.
            </p>
            <?php if ($pending_pass): ?>
              <div style="background:#fef9c3;border:1px solid #fde047;border-radius:10px;padding:16px 20px;margin-bottom:16px">
                <strong style="color:#854d0e">⚠️ Contraseña inicial — guardala ahora:</strong><br>
                <div style="display:flex;align-items:center;gap:10px;margin-top:10px">
                  <code id="luna-init-pass" style="font-size:18px;letter-spacing:2px;background:#fff;padding:8px 14px;border-radius:8px;border:1px solid #fde047;color:#1e1e1e"><?php echo esc_html($pending_pass) ?></code>
                  <button onclick="navigator.clipboard.writeText('<?php echo esc_js($pending_pass) ?>');this.textContent='✓ Copiado!';setTimeout(()=>this.textContent='Copiar',2000)"
                          style="background:#854d0e;color:#fff;border:none;border-radius:7px;padding:8px 16px;font-weight:700;cursor:pointer">Copiar</button>
                </div>
                <p style="margin:10px 0 0;font-size:12px;color:#854d0e">
                  Entrá a Luna con usuario <strong>admin</strong> y esta contraseña. Podés cambiarla desde tu perfil dentro de Luna.<br>
                  <a href="#" onclick="if(confirm('¿Confirmas que ya guardaste la contraseña?')){fetch('<?php echo esc_js(admin_url('admin-ajax.php')) ?>?action=luna_dismiss_pass&nonce=<?php echo wp_create_nonce('luna_dismiss_pass') ?>').then(()=>location.reload())};return false"
                     style="font-size:11px;color:#854d0e;margin-top:4px;display:inline-block">✓ Ya la guardé, ocultar este cuadro</a>
                </p>
              </div>
            <?php endif; ?>
            <div id="luna-new-pass-result" style="display:none;background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:16px 20px;margin-bottom:16px">
              <strong style="color:#166534">✅ Nueva contraseña generada — guardala ahora:</strong><br>
              <div style="display:flex;align-items:center;gap:10px;margin-top:10px">
                <code id="luna-new-pass" style="font-size:18px;letter-spacing:2px;background:#fff;padding:8px 14px;border-radius:8px;border:1px solid #86efac;color:#1e1e1e"></code>
                <button onclick="navigator.clipboard.writeText(document.getElementById('luna-new-pass').textContent);this.textContent='✓ Copiado!';setTimeout(()=>this.textContent='Copiar',2000)"
                        style="background:#166534;color:#fff;border:none;border-radius:7px;padding:8px 16px;font-weight:700;cursor:pointer">Copiar</button>
              </div>
              <p style="margin:10px 0 0;font-size:12px;color:#166534">Ingresá a Luna con usuario <strong>admin</strong> y esta contraseña.</p>
            </div>
            <button id="luna-btn-reset-pass" class="button button-secondary" style="font-size:13px;padding:6px 18px">
              🔄 Generar nueva contraseña para admin Luna
            </button>
            <span id="luna-reset-pass-msg" style="margin-left:10px;font-size:13px;color:#dc2626;display:none"></span>
          </div>

          <?php
          // ── SMTP settings ────────────────────────────────────────────────────
          global $wpdb;
          $smtp_p = $wpdb->prefix . 'luna_';
          if (isset($_POST['luna_save_smtp']) && check_admin_referer('luna_settings')) {
              $smtp_cfg = [
                  'enabled'    => !empty($_POST['smtp_enabled']),
                  'smtp_host'  => sanitize_text_field($_POST['smtp_host']  ?? 'smtp.gmail.com'),
                  'smtp_port'  => absint($_POST['smtp_port']  ?? 587),
                  'encryption' => sanitize_text_field($_POST['smtp_enc']   ?? 'tls'),
                  'smtp_user'  => sanitize_text_field($_POST['smtp_user']  ?? ''),
                  'smtp_pass'  => $_POST['smtp_pass'] ?? '',
                  'from_email' => sanitize_email($_POST['from_email']      ?? ''),
                  'from_name'  => sanitize_text_field($_POST['from_name']  ?? 'Luna Workspace'),
              ];
              $existing = $wpdb->get_var("SELECT meta_value FROM `{$smtp_p}app_settings` WHERE meta_key='email_settings'");
              if ($existing !== null) {
                  $wpdb->update("{$smtp_p}app_settings", ['meta_value' => wp_json_encode($smtp_cfg)], ['meta_key' => 'email_settings']);
              } else {
                  $wpdb->insert("{$smtp_p}app_settings", ['meta_key' => 'email_settings', 'meta_value' => wp_json_encode($smtp_cfg)]);
              }
              echo '<div class="notice notice-success"><p>✅ Configuración SMTP guardada.</p></div>';
          }
          $smtp_row = $wpdb->get_var("SELECT meta_value FROM `{$smtp_p}app_settings` WHERE meta_key='email_settings'");
          $smtp_cfg = $smtp_row ? (json_decode($smtp_row, true) ?: []) : [];
          ?>
          <div class="luna-card" style="margin-top:20px">
            <h2 style="margin-top:0">📧 Configuración SMTP (Email)</h2>
            <p style="color:#666;font-size:13px;margin-top:-8px">Estos datos se usan para enviar notificaciones por email desde Luna Workspace.</p>
            <form method="POST">
              <?php wp_nonce_field('luna_settings') ?>
              <table class="form-table">
                <tr>
                  <th>Activar SMTP</th>
                  <td><label><input type="checkbox" name="smtp_enabled" value="1" <?php checked(!empty($smtp_cfg['enabled'])) ?>> Enviar emails via SMTP personalizado</label></td>
                </tr>
                <tr>
                  <th>Host SMTP</th>
                  <td><input type="text" name="smtp_host" value="<?php echo esc_attr($smtp_cfg['smtp_host'] ?? 'smtp.gmail.com') ?>" class="regular-text" placeholder="smtp.gmail.com"></td>
                </tr>
                <tr>
                  <th>Puerto</th>
                  <td><input type="number" name="smtp_port" value="<?php echo esc_attr($smtp_cfg['smtp_port'] ?? 587) ?>" class="small-text" placeholder="587">
                      <span class="description"> (587 para TLS, 465 para SSL)</span></td>
                </tr>
                <tr>
                  <th>Cifrado</th>
                  <td>
                    <select name="smtp_enc">
                      <option value="tls" <?php selected(($smtp_cfg['encryption'] ?? 'tls'), 'tls') ?>>TLS (recomendado)</option>
                      <option value="ssl" <?php selected(($smtp_cfg['encryption'] ?? 'tls'), 'ssl') ?>>SSL</option>
                    </select>
                  </td>
                </tr>
                <tr>
                  <th>Usuario SMTP</th>
                  <td><input type="text" name="smtp_user" value="<?php echo esc_attr($smtp_cfg['smtp_user'] ?? '') ?>" class="regular-text" placeholder="tu@gmail.com"></td>
                </tr>
                <tr>
                  <th>Contraseña SMTP</th>
                  <td><input type="password" name="smtp_pass" value="<?php echo esc_attr($smtp_cfg['smtp_pass'] ?? '') ?>" class="regular-text" placeholder="contraseña o app-password">
                      <p class="description">Para Gmail usá una <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a> (requiere 2FA activo).</p></td>
                </tr>
                <tr>
                  <th>Email remitente</th>
                  <td><input type="email" name="from_email" value="<?php echo esc_attr($smtp_cfg['from_email'] ?? '') ?>" class="regular-text" placeholder="notificaciones@tudominio.com"></td>
                </tr>
                <tr>
                  <th>Nombre remitente</th>
                  <td><input type="text" name="from_name" value="<?php echo esc_attr($smtp_cfg['from_name'] ?? 'Luna Workspace') ?>" class="regular-text"></td>
                </tr>
              </table>
              <p><button type="submit" name="luna_save_smtp" class="button button-primary">Guardar configuración SMTP</button></p>
            </form>
          </div>

          <?php
          // ── Reminder settings ────────────────────────────────────────────────
          $appDb  = $this->get_app_db();
          $appPfx = $this->get_app_prefix();
          $rem    = [];
          if ($appDb && $appPfx !== null) {
              $tbl = $appPfx !== '' ? "{$appPfx}app_settings" : 'app_settings';
              try {
                  $row = $appDb->query("SELECT meta_value FROM `{$tbl}` WHERE meta_key='reminder_schedule' LIMIT 1")->fetch();
                  $rem = $row ? (json_decode($row['meta_value'], true) ?: []) : [];
              } catch (Exception $e) {}
          }
          if (empty($rem)) {
              $row = $wpdb->get_var("SELECT meta_value FROM `{$smtp_p}app_settings` WHERE meta_key='reminder_schedule'");
              $rem = $row ? (json_decode($row, true) ?: []) : [];
          }
          $rem_enabled = !empty($rem['enabled']);
          $rem_hour    = (int)($rem['hour'] ?? 8);
          $last_sent   = '';
          if ($appDb && $appPfx !== null) {
              $tbl = $appPfx !== '' ? "{$appPfx}app_settings" : 'app_settings';
              try {
                  $r = $appDb->query("SELECT meta_value FROM `{$tbl}` WHERE meta_key='reminders_last_sent' LIMIT 1")->fetch();
                  $last_sent = $r ? $r['meta_value'] : '';
              } catch (Exception $e) {}
          }
          if (!$last_sent) {
              $last_sent = $wpdb->get_var("SELECT meta_value FROM `{$smtp_p}app_settings` WHERE meta_key='reminders_last_sent'") ?: '';
          }
          ?>
          <div class="luna-card" style="margin-top:20px" id="luna-reminders-card">
            <h2 style="margin-top:0">⏰ Recordatorios diarios</h2>
            <p style="color:#666;font-size:13px;margin-top:-8px">
              Cada día a la hora configurada Luna envía a cada usuario un resumen personalizado de sus tareas vencidas,
              de hoy y de esta semana — por email, WhatsApp o Telegram según la preferencia de cada uno.
            </p>

            <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding:14px 18px;background:#f0fdf4;border:1px solid #86efac;border-radius:10px">
              <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600">
                <input type="checkbox" id="rem-enabled" <?php checked($rem_enabled) ?>
                       style="width:18px;height:18px;accent-color:#16a34a;cursor:pointer">
                Activar recordatorios automáticos
              </label>
              <?php if ($rem_enabled): ?>
                <span style="font-size:12px;color:#16a34a;font-weight:600">✅ Activo</span>
              <?php else: ?>
                <span style="font-size:12px;color:#94a3b8">Desactivado</span>
              <?php endif; ?>
            </div>

            <table class="form-table" style="margin-bottom:16px">
              <tr>
                <th>Hora de envío</th>
                <td>
                  <select id="rem-hour" style="font-size:13px;padding:5px 10px;border-radius:7px;border:1px solid #ddd">
                    <?php for ($h = 0; $h < 24; $h++):
                      $label = sprintf('%02d:00 hs', $h);
                      if ($h === 8)  $label .= ' (mañana — recomendado)';
                      if ($h === 9)  $label .= ' (mañana)';
                      if ($h === 18) $label .= ' (tarde)';
                      if ($h === 19) $label .= ' (tarde)';
                    ?>
                      <option value="<?php echo $h ?>" <?php selected($rem_hour, $h) ?>><?php echo $label ?></option>
                    <?php endfor; ?>
                  </select>
                  <p class="description" style="margin-top:6px">
                    Hora del servidor. <?php
                      echo 'Ahora son las <strong>' . date('H:i') . ' hs</strong> en el servidor.';
                    ?>
                  </p>
                </td>
              </tr>
              <tr>
                <th>Último envío</th>
                <td>
                  <span style="font-size:13px">
                    <?php echo $last_sent
                      ? '<strong style="color:#16a34a">' . esc_html($last_sent) . '</strong>'
                      : '<span style="color:#94a3b8">Nunca enviado</span>'; ?>
                  </span>
                </td>
              </tr>
            </table>

            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
              <button id="luna-btn-save-reminders" class="button button-primary" style="font-size:13px;padding:6px 18px">
                💾 Guardar configuración
              </button>
              <button id="luna-btn-send-now" class="button button-secondary" style="font-size:13px;padding:6px 18px">
                🚀 Enviar ahora (prueba)
              </button>
              <span id="luna-rem-msg" style="font-size:13px;display:none"></span>
            </div>

            <div id="luna-rem-preview" style="display:none;margin-top:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px">
              <strong style="font-size:13px">📋 Resultado del envío:</strong>
              <div id="luna-rem-preview-body" style="margin-top:10px;font-size:12px"></div>
            </div>
          </div>
        </div>

        <script>
        jQuery(function($){
          var nonce = <?php echo wp_json_encode(wp_create_nonce('luna_admin_nonce')); ?>;

          // ── Reset admin password ─────────────────────────────────────────────
          $('#luna-btn-reset-pass').on('click', function(){
            if (!confirm('¿Generar una nueva contraseña para el admin de Luna?\nLa contraseña actual dejará de funcionar y se cerrarán todas las sesiones.')) return;
            var btn = $(this);
            var msg = $('#luna-reset-pass-msg');
            btn.prop('disabled', true).text('Generando…');
            msg.hide();
            $.post(ajaxurl, { action: 'luna_reset_admin_pass', nonce: nonce }, function(res){
              btn.prop('disabled', false).text('🔄 Generar nueva contraseña para admin Luna');
              if (res.success && res.data && res.data.password) {
                $('#luna-new-pass').text(res.data.password);
                $('#luna-new-pass-result').show();
                $('html,body').animate({scrollTop: $('#luna-new-pass-result').offset().top - 80}, 300);
              } else {
                msg.text('Error: ' + (res.data || 'No se pudo resetear')).show();
              }
            }).fail(function(){
              btn.prop('disabled', false).text('🔄 Generar nueva contraseña para admin Luna');
              msg.text('Error de conexión. Recargá la página e intentá de nuevo.').show();
            });
          });

          // ── Guardar config recordatorios ─────────────────────────────────────
          $('#luna-btn-save-reminders').on('click', function(){
            var btn = $(this);
            var msg = $('#luna-rem-msg');
            btn.prop('disabled', true).text('Guardando…');
            msg.hide();
            $.post(ajaxurl, {
              action:  'luna_save_reminders',
              nonce:   nonce,
              enabled: $('#rem-enabled').is(':checked') ? 1 : 0,
              hour:    parseInt($('#rem-hour').val())
            }, function(res){
              btn.prop('disabled', false).text('💾 Guardar configuración');
              if (res.success) {
                msg.css('color','#16a34a').text('✅ ' + (res.data.message || 'Configuración guardada.')).show();
              } else {
                msg.css('color','#dc2626').text('❌ ' + (res.data || 'Error al guardar.')).show();
              }
              setTimeout(function(){ msg.fadeOut(); }, 4000);
            }).fail(function(){
              btn.prop('disabled', false).text('💾 Guardar configuración');
              msg.css('color','#dc2626').text('❌ Error de conexión.').show();
            });
          });

          // ── Enviar recordatorios ahora ───────────────────────────────────────
          $('#luna-btn-send-now').on('click', function(){
            if (!confirm('¿Enviar recordatorios ahora a todos los usuarios con tareas pendientes?\n(Ignorará el control de "ya enviado hoy")')) return;
            var btn = $(this);
            var msg = $('#luna-rem-msg');
            btn.prop('disabled', true).text('Enviando…');
            msg.hide();
            $('#luna-rem-preview').hide();
            $.ajax({
              url:     ajaxurl,
              type:    'POST',
              timeout: 60000,
              data:    { action: 'luna_send_reminders_now', nonce: nonce },
              success: function(res){
                btn.prop('disabled', false).text('🚀 Enviar ahora (prueba)');
                if (res.success && res.data) {
                  var d = res.data;
                  msg.css('color','#16a34a').text('✅ Enviados: ' + d.sent + ' · Errores: ' + (d.errors||0)).show();
                  if (d.preview && d.preview.length) {
                    var rows = '';
                    $.each(d.preview, function(_, p){
                      rows += '<div style="padding:6px 0;border-bottom:1px solid #e2e8f0">'
                            + '<strong>' + p.name + '</strong> (' + p.to + ') — '
                            + p.total + ' tarea(s) · <em style="color:#64748b;font-size:11px">' + p.subject + '</em></div>';
                    });
                    if (!rows) rows = '<p style="color:#94a3b8;margin:0">No hay tareas próximas a vencer para ningún usuario.</p>';
                    $('#luna-rem-preview-body').html(rows);
                    $('#luna-rem-preview').show();
                  }
                } else {
                  msg.css('color','#dc2626').text('❌ ' + (res.data || 'Error al enviar.')).show();
                }
              },
              error: function(xhr, status){
                btn.prop('disabled', false).text('🚀 Enviar ahora (prueba)');
                var m = status === 'timeout' ? 'Tiempo de espera agotado (60s).' : 'Error de conexión.';
                msg.css('color','#dc2626').text('❌ ' + m).show();
              }
            });
          });
        });
        </script>
        <?php
    }

    // ── License page ──────────────────────────────────────────────────────────
    public function render_license_page() {
        if (isset($_POST['luna_save_license_settings']) && check_admin_referer('luna_license_settings')) {
            $new_key = sanitize_text_field($_POST['luna_license_key'] ?? '');
            if (!empty($_POST['luna_license_server_url'])) {
                update_option('luna_license_server_url', esc_url_raw(trim($_POST['luna_license_server_url'])));
            }
            update_option('luna_license_key', $new_key);
            Luna_Activator::regenerate_app_config();
            @unlink(LUNA_APP_DIR . 'luna-license-cache.json');
            Luna_License::clear_cache($new_key);
            echo '<div class="notice notice-success"><p>✅ Licencia guardada. Verificando...</p></div>';
        }

        if (isset($_POST['luna_clear_license_cache']) && check_admin_referer('luna_license_settings')) {
            $cur_key = get_option('luna_license_key', '');
            if ($cur_key) Luna_License::clear_cache($cur_key);
            echo '<div class="notice notice-success"><p>🔄 Cache de licencia limpiada. Verificando de nuevo...</p></div>';
        }

        $key    = get_option('luna_license_key', '');
        $domain = parse_url(get_site_url(), PHP_URL_HOST) ?? ($_SERVER['HTTP_HOST'] ?? '');

        $status_html = '';
        if ($key) {
            $result = Luna_License::verify($key, $domain);
            if (!empty($result['valid'])) {
                $plan    = Luna_License::plan_label($result['plan'] ?? 'unknown');
                $expires = $result['expires_at'] ?? '—';
                $extra   = '';
                if (!empty($result['offline'])) $extra .= ' <em style="color:#f59e0b">(verificación offline)</em>';
                if (!empty($result['grace']))   $extra .= ' <em style="color:#f59e0b">⚠️ Gracia: ' . ($result['grace_days'] ?? 0) . ' días restantes</em>';
                $status_html = '<div class="notice notice-success inline" style="margin:0"><p>✅ Licencia <strong>activa</strong> — Plan: <strong>' . esc_html($plan) . '</strong> — Vence: <strong>' . esc_html($expires) . '</strong>' . $extra . '</p></div>';
            } else {
                $msg    = $result['message'] ?? ($result['reason'] ?? 'Licencia inválida');
                $reason = $result['reason'] ?? '';
                $detail = '';
                if ($reason === 'server_unreachable') {
                    $detail = '<br><small style="color:#999">Servidor: <code>' . esc_html(get_option('luna_license_server_url', Luna_License::SERVER)) . '</code> — verificá que el plugin Luna Licencias esté activo en ese dominio y que el dominio sea accesible.</small>';
                } else {
                    $detail = '<br><small style="color:#999">Razón: <code>' . esc_html($reason ?: 'sin_razón') . '</code> — Dominio enviado: <code>' . esc_html($domain) . '</code></small>';
                }
                $status_html = '<div class="notice notice-error inline" style="margin:0"><p>❌ ' . esc_html($msg) . $detail . '</p></div>';
            }
        }
        ?>
        <div class="wrap luna-wrap">
          <h1>🔑 Luna Workspace — Activación</h1>

          <?php if ($status_html): ?>
            <div style="margin-bottom:20px"><?php echo $status_html ?></div>
          <?php endif; ?>

          <div class="luna-grid">
            <div class="luna-card">
              <h2>Activar licencia</h2>
              <p style="color:#666;margin-bottom:16px">Ingresá la clave que recibiste al comprar Luna Workspace. La verificación es automática.</p>
              <form method="POST">
                <?php wp_nonce_field('luna_license_settings') ?>
                <table class="form-table">
                  <tr>
                    <th scope="row"><label for="luna-license-input">Clave de licencia</label></th>
                    <td>
                      <input type="text" id="luna-license-input" name="luna_license_key"
                             value="<?php echo esc_attr($key) ?>"
                             placeholder="LUNA-XXXX-XXXX-XXXX-XXXX"
                             class="regular-text"
                             style="font-family:monospace;letter-spacing:2px;font-size:16px;width:320px">
                      <p class="description" style="margin-top:6px">
                        Tu dominio: <code><?php echo esc_html($domain) ?></code> — se valida automáticamente.
                      </p>
                    </td>
                  </tr>
                  <tr>
                    <th scope="row"><label for="luna-server-url">URL del servidor de licencias</label></th>
                    <td>
                      <input type="url" id="luna-server-url" name="luna_license_server_url"
                             value="<?php echo esc_attr(get_option('luna_license_server_url', Luna_License::SERVER)) ?>"
                             class="large-text" style="font-family:monospace;font-size:12px">
                      <p class="description">Dejá el valor por defecto si no cambiaste el servidor.</p>
                    </td>
                  </tr>
                </table>
                <p>
                  <button type="submit" name="luna_save_license_settings" class="button button-primary" style="font-size:14px;padding:6px 20px">
                    ✅ Activar licencia
                  </button>
                  &nbsp;
                  <button type="submit" name="luna_clear_license_cache" class="button"
                          title="Borra el cache guardado y vuelve a consultar el servidor de licencias">
                    🔄 Limpiar cache y reverificar
                  </button>
                </p>
              </form>
              <div id="luna-license-result" style="margin-top:16px;display:none"></div>
            </div>

            <div class="luna-card">
              <h2>Planes</h2>
              <table class="widefat striped">
                <thead><tr><th>Plan</th><th>Precio/mes</th><th>Usuarios</th><th>Pizarras</th><th>Notificaciones</th></tr></thead>
                <tbody>
                  <tr><td><strong>Gratis</strong></td><td>$0</td><td>1</td><td>1</td><td>No</td></tr>
                  <tr><td><strong>Básico</strong></td><td>$19</td><td>Hasta 5</td><td>Ilimitadas</td><td>Sí</td></tr>
                  <tr><td><strong>Profesional</strong></td><td>$39</td><td>Hasta 20</td><td>Ilimitadas</td><td>Sí</td></tr>
                  <tr><td><strong>Corporativo</strong></td><td>$89</td><td>Ilimitados</td><td>Ilimitadas</td><td>Sí</td></tr>
                </tbody>
              </table>
              <p style="margin-top:12px;color:#666;font-size:12px">
                Licencias mensuales, vinculadas al dominio para prevenir uso no autorizado.<br>
                Comprar o renovar: <a href="https://websobreruedas.com" target="_blank">websobreruedas.com</a>
              </p>
            </div>
          </div>
        </div>
        <?php
    }

    // ── Notifications page ────────────────────────────────────────────────────
    public function render_notifications_page() {
        global $wpdb;

        // Try app DB first (may differ from WP DB)
        $appDb  = $this->get_app_db();
        $appPfx = $this->get_app_prefix();
        $users  = [];
        $db_source = '';
        if ($appDb !== null && $appPfx !== null) {
            $usersTable = $appPfx !== '' ? "`{$appPfx}users`" : '`users`';
            $pfxLabel   = $appPfx !== '' ? $appPfx : '(sin prefijo)';
            // First attempt: full query with optional columns
            try {
                $st = $appDb->query(
                    "SELECT id, name, email,
                            COALESCE(phone,'') AS phone,
                            COALESCE(whatsapp_apikey,'') AS whatsapp_apikey,
                            telegram_chat_id,
                            COALESCE(notification_channel,'email') AS notification_channel,
                            active
                     FROM {$usersTable} ORDER BY name ASC"
                );
                $users = $st->fetchAll();
                $db_source = "App DB (<code>{$cfg_db_name}</code>) · prefix <code>{$pfxLabel}</code>";
            } catch (Exception $e) {
                // Optional columns may not exist yet — retry with basic columns only
                // so we still get the users even without phone/channel fields
                try {
                    $st = $appDb->query(
                        "SELECT id, name, email,
                                '' AS phone,
                                '' AS whatsapp_apikey,
                                NULL AS telegram_chat_id,
                                'email' AS notification_channel,
                                active
                         FROM {$usersTable} ORDER BY name ASC"
                    );
                    $users = $st->fetchAll();
                    $db_source = "App DB (<code>{$cfg_db_name}</code>) · prefix <code>{$pfxLabel}</code> (columnas básicas)";
                } catch (Exception $e2) {
                    $db_source = "Error App DB: " . esc_html($e2->getMessage());
                }
            }
        }
        // Fallback: WordPress DB (same two-level approach)
        if (empty($users)) {
            $p = $wpdb->prefix . 'luna_';
            $users = $wpdb->get_results(
                "SELECT id, name, email,
                        COALESCE(phone,'') AS phone,
                        COALESCE(whatsapp_apikey,'') AS whatsapp_apikey,
                        telegram_chat_id,
                        COALESCE(notification_channel,'email') AS notification_channel,
                        active
                 FROM `{$p}users` ORDER BY name ASC",
                ARRAY_A
            ) ?: [];
            if (empty($users) && !$wpdb->last_error) {
                // Retry with basic columns
                $users = $wpdb->get_results(
                    "SELECT id, name, email,
                            '' AS phone,
                            '' AS whatsapp_apikey,
                            NULL AS telegram_chat_id,
                            'email' AS notification_channel,
                            active
                     FROM `{$p}users` ORDER BY name ASC",
                    ARRAY_A
                ) ?: [];
            }
            $db_source = $db_source ?: "WP DB (<code>{$wpdb->dbname}</code>) · prefix <code>{$p}</code>";
        }

        $channel_labels = [
            'email'     => '📧 Email',
            'whatsapp'  => '💬 WhatsApp',
            'telegram'  => '✈️ Telegram',
            'all'       => '📡 Todos',
            'none'      => '🔕 Ninguno',
        ];
        ?>
        <div class="wrap luna-wrap">
          <h1>🔔 Luna Workspace — Notificaciones</h1>

          <div class="luna-card" style="margin-bottom:20px">
            <h2 style="margin-top:0">Cómo funciona cada canal</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-top:12px">
              <div style="padding:14px;background:#eff6ff;border-radius:10px;border:1px solid #bfdbfe">
                <strong>📧 Email</strong><br>
                <span style="font-size:12px;color:#374151">Requiere SMTP configurado en Luna → Configuración → Email. Funciona sin datos extra en el perfil.</span>
              </div>
              <div style="padding:14px;background:#f0fdf4;border-radius:10px;border:1px solid #86efac">
                <strong>💬 WhatsApp (CallMeBot)</strong><br>
                <span style="font-size:12px;color:#374151">El usuario debe: 1) Enviar <code>I allow callmebot to send me messages</code> a <strong>+34 644 59 60 32</strong> en WhatsApp. 2) Ingresar su número y API Key en su perfil de Luna.</span>
              </div>
              <div style="padding:14px;background:#fdf4ff;border-radius:10px;border:1px solid #d8b4fe">
                <strong>✈️ Telegram</strong><br>
                <span style="font-size:12px;color:#374151">El usuario debe iniciar chat con el bot de Luna y copiar su Chat ID en su perfil.</span>
              </div>
            </div>
          </div>

          <div class="luna-card">
            <h2 style="margin-top:0">Usuarios y estado de notificaciones <span style="font-size:13px;font-weight:normal;color:#6b7280">(<?= count($users) ?> usuario<?= count($users) != 1 ? 's' : '' ?> en Luna)</span></h2>
            <p style="color:#666;font-size:13px;margin-top:-8px">Podés editar el teléfono, WA API Key y canal directamente aquí. Hacé clic en 💾 para guardar cada fila.</p>
            <table class="widefat fixed striped" style="font-size:12px">
              <thead>
                <tr>
                  <th style="width:130px">Nombre</th>
                  <th style="width:160px">Email</th>
                  <th style="width:130px">Teléfono</th>
                  <th style="width:130px">WA API Key</th>
                  <th style="width:120px">Canal</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u):
                  $channel = $u['notification_channel'] ?: 'email';
                  $waReady = !empty($u['phone']) && !empty($u['whatsapp_apikey']);
                ?>
                <tr>
                  <td>
                    <strong><?= esc_html($u['name']) ?></strong>
                    <?= !$u['active'] ? ' <span style="background:#fee2e2;color:#dc2626;font-size:10px;padding:1px 5px;border-radius:4px;vertical-align:middle">inactivo</span>' : '' ?>
                  </td>
                  <td style="font-size:11px"><?= esc_html($u['email'] ?: '—') ?></td>
                  <td>
                    <input type="text" class="luna-uf-phone" data-uid="<?= (int)$u['id'] ?>"
                           value="<?= esc_attr($u['phone'] ?? '') ?>"
                           placeholder="+549..." style="width:100%;font-size:11px;padding:3px 5px">
                  </td>
                  <td>
                    <input type="text" class="luna-uf-wakey" data-uid="<?= (int)$u['id'] ?>"
                           value="<?= esc_attr($u['whatsapp_apikey'] ?? '') ?>"
                           placeholder="API Key CallMeBot" style="width:100%;font-size:11px;padding:3px 5px">
                  </td>
                  <td>
                    <select class="luna-uf-channel" data-uid="<?= (int)$u['id'] ?>" style="width:100%;font-size:11px">
                      <?php foreach ($channel_labels as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= selected($channel, $val, false) ?>><?= $lbl ?></option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td>
                    <button class="button button-small luna-save-user" data-uid="<?= (int)$u['id'] ?>" style="margin-right:3px">💾</button>
                    <?php if ($u['active']): ?>
                      <button class="button button-small luna-test-notif" data-uid="<?= (int)$u['id'] ?>" data-name="<?= esc_attr($u['name']) ?>" style="margin-right:3px" title="Probar email">📧</button>
                      <?php if ($waReady): ?>
                        <button class="button button-small luna-test-notif-wa" data-uid="<?= (int)$u['id'] ?>" data-name="<?= esc_attr($u['name']) ?>" title="Probar WhatsApp">💬</button>
                      <?php endif; ?>
                    <?php endif; ?>
                    <span class="luna-save-msg-<?= (int)$u['id'] ?>" style="font-size:11px;margin-left:4px"></span>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                  <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:20px">No hay usuarios en Luna Workspace todavía.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div id="luna-notif-result" style="display:none;margin-top:16px"></div>
        </div>

        <script>
        jQuery(function($){
          var nonce = '<?= wp_create_nonce('luna_admin_nonce') ?>';

          // Reset admin password (runs on both main page and notifications page)
          $(document).on('click', '#luna-btn-reset-pass', function(){
            if (!confirm('¿Generar una nueva contraseña para el admin de Luna?\nLa contraseña actual dejará de funcionar.')) return;
            var btn = $(this);
            var msg = $('#luna-reset-pass-msg');
            btn.prop('disabled', true).text('Generando…');
            msg.hide();
            $.post(ajaxurl, { action: 'luna_reset_admin_pass', nonce: nonce }, function(res){
              btn.prop('disabled', false).text('🔄 Generar nueva contraseña para admin Luna');
              if (res.success && res.data && res.data.password) {
                $('#luna-new-pass').text(res.data.password);
                $('#luna-new-pass-result').show();
                $('html,body').animate({scrollTop: $('#luna-new-pass-result').offset().top - 60}, 300);
              } else {
                msg.text('Error: ' + (res.data || 'No se pudo resetear')).show();
              }
            }).fail(function(){
              btn.prop('disabled', false).text('🔄 Generar nueva contraseña para admin Luna');
              msg.text('Error de conexión. Recargá la página e intentá de nuevo.').show();
            });
          });

          // Save user contact fields
          $(document).on('click', '.luna-save-user', function(){
            var uid  = $(this).data('uid');
            var row  = $(this).closest('tr');
            var msg  = $('.luna-save-msg-'+uid);
            $(this).prop('disabled', true).text('…');
            $.post(ajaxurl, {
              action:  'luna_save_user_contact',
              nonce:   nonce,
              user_id: uid,
              phone:   row.find('.luna-uf-phone').val(),
              wakey:   row.find('.luna-uf-wakey').val(),
              channel: row.find('.luna-uf-channel').val()
            }, function(res){
              if (res.success) {
                msg.css('color','#166534').text('✓ Guardado');
                // Enable WA test button if both fields filled
                var phone = row.find('.luna-uf-phone').val().trim();
                var wakey = row.find('.luna-uf-wakey').val().trim();
                if (phone && wakey && !row.find('.luna-test-notif-wa').length) {
                  row.find('.luna-test-notif').after(' <button class="button button-small luna-test-notif-wa" data-uid="'+uid+'" data-name="'+row.find('strong').first().text()+'" title="Probar WhatsApp">💬</button>');
                }
              } else {
                msg.css('color','#dc2626').text('✗ ' + (res.data || 'Error'));
              }
              setTimeout(function(){ msg.text(''); }, 3000);
              $('.luna-save-user[data-uid='+uid+']').prop('disabled', false).text('💾');
            });
          });

          // Test notifications
          function testNotif(uid, name, channel) {
            var btn = $('[data-uid="'+uid+'"]').filter(channel === 'wa' ? '.luna-test-notif-wa' : '.luna-test-notif');
            btn.prop('disabled', true).text('Enviando…');
            $.ajax({
              url:     ajaxurl,
              type:    'POST',
              timeout: 25000,
              data: {
                action:  'luna_test_notification',
                nonce:   nonce,
                user_id: uid,
                channel: channel
              },
              success: function(res){
                var box = $('#luna-notif-result');
                if (res.success) {
                  box.html('<div class="notice notice-success" style="padding:10px 14px"><p>✅ Notificación de prueba enviada a <strong>'+name+'</strong>.</p></div>').show();
                } else {
                  box.html('<div class="notice notice-error" style="padding:10px 14px"><p>❌ Error: ' + (res.data || 'No se pudo enviar') + '</p></div>').show();
                }
                btn.prop('disabled', false).text(channel === 'wa' ? '💬' : '📧');
                $('html,body').animate({scrollTop: box.offset().top - 40}, 300);
              },
              error: function(xhr, status){
                var box = $('#luna-notif-result');
                var msg = status === 'timeout'
                  ? 'Tiempo de espera agotado (504). El servidor no pudo conectarse al SMTP. Verificá Host/Puerto/SSL en Configuración → SMTP.'
                  : 'Error HTTP ' + xhr.status + '. Revisá los logs de PHP en el hosting.';
                box.html('<div class="notice notice-error" style="padding:10px 14px"><p>❌ ' + msg + '</p></div>').show();
                btn.prop('disabled', false).text(channel === 'wa' ? '💬' : '📧');
              }
            });
          }

          $(document).on('click', '.luna-test-notif',    function(){ testNotif($(this).data('uid'), $(this).data('name'), 'email'); });
          $(document).on('click', '.luna-test-notif-wa', function(){ testNotif($(this).data('uid'), $(this).data('name'), 'wa'); });
        });
        </script>
        <?php
    }

    // ── AJAX: send test notification ──────────────────────────────────────────
    public function ajax_test_notification() {
        check_ajax_referer('luna_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');

        $uid     = (int)($_POST['user_id'] ?? 0);
        $channel = sanitize_text_field($_POST['channel'] ?? 'email');

        global $wpdb;
        $p    = $wpdb->prefix . 'luna_';
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT name, email, phone, whatsapp_apikey, telegram_chat_id, notification_channel FROM `{$p}users` WHERE id=%d AND active=1",
            $uid
        ), ARRAY_A);

        if (!$user) wp_send_json_error('Usuario no encontrado');

        $subject = '🔔 Prueba de notificación — Luna Workspace';
        $plain   = 'Hola ' . $user['name'] . ', esta es una notificación de prueba de Luna Workspace. Si la recibís, todo está correcto.';

        // ── WhatsApp ──────────────────────────────────────────────────────────
        if ($channel === 'wa') {
            if (empty($user['phone']) || empty($user['whatsapp_apikey'])) {
                wp_send_json_error('El usuario no tiene teléfono o API Key de CallMeBot configurado');
            }
            $url = 'https://api.callmebot.com/whatsapp.php?' . http_build_query([
                'phone'  => $user['phone'],
                'text'   => $plain,
                'apikey' => $user['whatsapp_apikey'],
            ]);
            $resp = wp_remote_get($url, ['timeout' => 15]);
            if (is_wp_error($resp)) wp_send_json_error('CallMeBot no respondió: ' . $resp->get_error_message());
            wp_send_json_success();
        }

        // ── Email via Postfix local (localhost:25, sin SSL, sin auth) ─────────────
        // Conecta directo al MTA del servidor — sin colgar, sin 504
        if (empty($user['email'])) wp_send_json_error('El usuario no tiene email configurado');

        $st  = $wpdb->get_row("SELECT meta_value FROM `{$p}app_settings` WHERE meta_key='email_settings' LIMIT 1");
        $cfg = $st ? (json_decode($st->meta_value, true) ?: []) : [];

        $from_email = !empty($cfg['from_email']) ? $cfg['from_email'] : (!empty($cfg['smtp_user']) ? $cfg['smtp_user'] : 'info@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $from_name  = !empty($cfg['from_name'])  ? $cfg['from_name']  : 'Luna Workspace';
        $to         = $user['email'];

        $html = '<p>Hola <strong>' . esc_html($user['name']) . '</strong>,</p>'
              . '<p>Esta es una notificación de prueba de Luna Workspace. Si la recibís, todo está configurado correctamente.</p>';

        // Inyectar via localhost:25 (Postfix local — Plesk siempre lo tiene activo)
        // Sin SSL, sin auth, timeout 5s → respuesta instantánea
        $mail_error = '';
        add_action('phpmailer_init', function($m) use ($from_email, $from_name) {
            $m->isSMTP();
            $m->Host       = '127.0.0.1';
            $m->Port       = 25;
            $m->SMTPAuth   = false;
            $m->SMTPSecure = '';
            $m->Timeout    = 5;
            $m->setFrom($from_email, $from_name);
        });
        add_action('wp_mail_failed', function($e) use (&$mail_error) {
            $mail_error = $e->get_error_message();
        });
        add_filter('wp_mail_content_type', fn() => 'text/html');

        $sent = wp_mail($to, $subject, $html);

        remove_all_actions('phpmailer_init');
        remove_all_actions('wp_mail_failed');
        remove_all_filters('wp_mail_content_type');

        if ($sent) {
            wp_send_json_success('✅ Email enviado a ' . $to . ' desde ' . $from_email);
        } else {
            // Fallback: intentar PHP mail() directo
            $headers  = "From: {$from_name} <{$from_email}>\r\nContent-Type: text/html; charset=UTF-8\r\n";
            $fallback = @mail($to, $subject, $html, $headers);
            if ($fallback) {
                wp_send_json_success('✅ Email enviado (vía mail() nativo) a ' . $to);
            } else {
                wp_send_json_error('Error: ' . ($mail_error ?: 'No se pudo conectar al servidor de correo local (localhost:25). Verificá en Plesk → Mail → Mail Settings que el servicio de correo esté activo.'));
            }
        }
    }

    // ── AJAX: reset Luna admin password ──────────────────────────────────────────
    public function ajax_reset_admin_pass() {
        check_ajax_referer('luna_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');

        global $wpdb;
        $p = $wpdb->prefix . 'luna_';

        // Verificar que existe la tabla
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$p}users'");
        if (!$table_exists) {
            wp_send_json_error('Las tablas de Luna no están instaladas. Desactivá y reactivá el plugin.');
        }

        $new_pass = bin2hex(random_bytes(8)); // 16 chars, legible
        $hash     = password_hash($new_pass, PASSWORD_BCRYPT);

        $result = $wpdb->update(
            "{$p}users",
            ['password' => $hash],
            ['role' => 'admin', 'active' => 1],
            ['%s'],
            ['%s', '%d']
        );

        if ($result === false) {
            wp_send_json_error('Error al actualizar: ' . $wpdb->last_error);
        }

        if ($result === 0) {
            // No había admin activo — intentar crear uno
            Luna_Activator::activate();
            $new_pass2 = get_option('luna_initial_admin_pass', '');
            if ($new_pass2) {
                delete_option('luna_initial_admin_pass');
                wp_send_json_success(['password' => $new_pass2]);
            }
            wp_send_json_error('No se encontró usuario admin en Luna. Intentá desactivar y reactivar el plugin.');
        }

        // También invalidar todas las sesiones activas del admin para forzar re-login
        $admin = $wpdb->get_row("SELECT id FROM `{$p}users` WHERE role='admin' AND active=1 ORDER BY id LIMIT 1", ARRAY_A);
        if ($admin) {
            $wpdb->delete("{$p}sessions", ['user_id' => $admin['id']], ['%d']);
        }

        wp_send_json_success(['password' => $new_pass]);
    }

    // ── AJAX: save user contact fields from notifications page ─────────────────
    public function ajax_save_user_contact() {
        check_ajax_referer('luna_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');

        $uid     = (int)($_POST['user_id'] ?? 0);
        $phone   = sanitize_text_field($_POST['phone']   ?? '');
        $wakey   = sanitize_text_field($_POST['wakey']   ?? '');
        $channel = sanitize_text_field($_POST['channel'] ?? 'email');

        if (!$uid) wp_send_json_error('Usuario inválido');

        $valid_channels = ['email', 'whatsapp', 'telegram', 'all', 'none'];
        if (!in_array($channel, $valid_channels, true)) $channel = 'email';

        // Primary: app DB (may differ from WP DB — same logic as render_notifications_page)
        $appDb  = $this->get_app_db();
        $appPfx = $this->get_app_prefix();
        if ($appDb && $appPfx !== null) {
            $usersTable = $appPfx !== '' ? "`{$appPfx}users`" : '`users`';
            try {
                $st = $appDb->prepare(
                    "UPDATE {$usersTable} SET phone=?, whatsapp_apikey=?, notification_channel=? WHERE id=?"
                );
                $st->execute([$phone, $wakey, $channel, $uid]);
                wp_send_json_success();
            } catch (Exception $e) {
                wp_send_json_error('Error al guardar: ' . $e->getMessage());
            }
        }
        // Fallback: WordPress DB
        global $wpdb;
        $p      = $wpdb->prefix . 'luna_';
        $result = $wpdb->update(
            "{$p}users",
            ['phone' => $phone, 'whatsapp_apikey' => $wakey, 'notification_channel' => $channel],
            ['id' => $uid], ['%s', '%s', '%s'], ['%d']
        );
        if ($result === false) wp_send_json_error('Error al guardar: ' . $wpdb->last_error);
        wp_send_json_success();
    }

    // ── Database page ─────────────────────────────────────────────────────────
    public function render_database_page() {
        $appDb  = $this->get_app_db();
        $appPfx = $this->get_app_prefix();
        global $wpdb;

        // Determine which connection and prefix to use
        $useAppDb  = ($appDb !== null && $appPfx !== null);
        $db        = $useAppDb ? $appDb  : null;
        $pfx       = $useAppDb ? $appPfx : $wpdb->prefix . 'luna_';
        $source    = $useAppDb ? 'App DB (admin_luna)' : 'WP DB (' . $wpdb->dbname . ')';

        // Config file info
        $cfg_file = plugin_dir_path(__FILE__) . '../app/luna-wp-config.php';
        $cfg_defs = [];
        if (file_exists($cfg_file)) {
            preg_match_all("/define\('([^']+)',\s*'([^']*)'\)/", file_get_contents($cfg_file), $cm, PREG_SET_ORDER);
            foreach ($cm as $row) $cfg_defs[$row[1]] = $row[2];
        }

        // Known Luna tables (without prefix)
        $table_names = [
            'users', 'workspaces', 'workspace_members', 'workspace_labels',
            'columns_k', 'cards', 'card_tags', 'card_assignees', 'card_checklist',
            'card_dependencies', 'attachments', 'sessions',
            'notifications', 'app_settings', 'workspace_templates', 'activity_log',
            'user_meta',
        ];

        // Collect table stats
        $tables = [];
        foreach ($table_names as $tname) {
            $full = $pfx . $tname;
            $row  = ['table' => $full, 'rows' => null, 'size_kb' => null, 'status' => '—', 'error' => ''];
            if ($useAppDb) {
                try {
                    $r = $appDb->query("SELECT TABLE_ROWS, ROUND((DATA_LENGTH+INDEX_LENGTH)/1024,1) AS kb
                                        FROM information_schema.TABLES
                                        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=" . $appDb->quote($full))->fetch();
                    if ($r) { $row['rows'] = (int)$r['TABLE_ROWS']; $row['size_kb'] = $r['kb']; }
                } catch (Exception $e) { $row['error'] = $e->getMessage(); }
            } else {
                $r = $wpdb->get_row($wpdb->prepare(
                    "SELECT TABLE_ROWS, ROUND((DATA_LENGTH+INDEX_LENGTH)/1024,1) AS kb
                     FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s", $full
                ), ARRAY_A);
                if ($r) { $row['rows'] = (int)$r['TABLE_ROWS']; $row['size_kb'] = $r['kb']; }
            }
            $tables[] = $row;
        }
        ?>
        <div class="wrap luna-wrap">
          <h1>🛠️ Luna Workspace — Base de datos</h1>

          <?php // ── Connection summary ──────────────────────────────────── ?>
          <div class="luna-grid" style="margin-bottom:20px">
            <div class="luna-card">
              <h2 style="margin-top:0">🔌 Conexión activa</h2>
              <table style="font-size:13px;border-collapse:collapse;width:100%">
                <tr><td style="padding:4px 12px 4px 0;color:#64748b;width:160px">Fuente</td><td><strong><?php echo esc_html($source) ?></strong></td></tr>
                <tr><td style="color:#64748b">Host</td><td><code><?php echo esc_html($cfg_defs['DB_HOST'] ?? DB_HOST) ?></code></td></tr>
                <tr><td style="color:#64748b">Base de datos</td><td><code><?php echo esc_html($cfg_defs['DB_NAME'] ?? DB_NAME) ?></code></td></tr>
                <tr><td style="color:#64748b">Usuario MySQL</td><td><code><?php echo esc_html($cfg_defs['DB_USER'] ?? DB_USER) ?></code></td></tr>
                <tr><td style="color:#64748b">Prefijo tablas</td><td><code><?php echo esc_html($pfx ?: '(sin prefijo)') ?></code></td></tr>
                <tr><td style="color:#64748b">Config file</td>
                  <td><?php echo file_exists($cfg_file)
                    ? '<span style="color:#16a34a">✅ existe</span>'
                    : '<span style="color:#dc2626">❌ NO encontrado — regenerar abajo</span>' ?></td></tr>
              </table>
            </div>

            <div class="luna-card">
              <h2 style="margin-top:0">⚡ Acciones de mantenimiento</h2>
              <div style="display:flex;flex-direction:column;gap:8px">
                <button class="button button-primary luna-db-action" data-action="check"
                        style="text-align:left;padding:10px 16px;height:auto;line-height:1.3">
                  <strong style="display:block">🔍 VERIFICAR INTEGRIDAD DE TABLAS</strong>
                  <span style="font-size:11px;font-weight:normal;opacity:.85">analiza los índices y detecta errores sin modificar ningún dato</span>
                </button>
                <button class="button button-secondary luna-db-action" data-action="optimize"
                        style="text-align:left;padding:10px 16px;height:auto;line-height:1.3">
                  <strong style="display:block">⚙️ OPTIMIZAR Y RECONSTRUIR ÍNDICES</strong>
                  <span style="font-size:11px;font-weight:normal;opacity:.85">reconstruye los índices y recupera espacio en disco, sin tocar los datos</span>
                </button>
                <button class="button button-secondary luna-db-action" data-action="repair"
                        style="text-align:left;padding:10px 16px;height:auto;line-height:1.3">
                  <strong style="display:block">🔧 REPARAR TABLAS CORRUPTAS</strong>
                  <span style="font-size:11px;font-weight:normal;opacity:.85">repara índices corruptos, es la opción más agresiva pero no borra ningún dato</span>
                </button>
                <button class="button button-secondary luna-db-action" data-action="clean_sessions"
                        style="text-align:left;padding:10px 16px;height:auto;line-height:1.3">
                  <strong style="display:block">🧹 LIMPIAR SESIONES VENCIDAS</strong>
                  <span style="font-size:11px;font-weight:normal;opacity:.85">borra solo los tokens de acceso expirados, no elimina usuarios ni datos</span>
                </button>
                <button class="button luna-db-action" data-action="regen_config"
                        style="text-align:left;padding:10px 16px;height:auto;line-height:1.3;background:#fef9c3;border-color:#d97706;color:#92400e">
                  <strong style="display:block">🔄 REGENERAR ARCHIVO DE CONFIGURACIÓN</strong>
                  <span style="font-size:11px;font-weight:normal;opacity:.85">recrea luna-wp-config.php si WordPress lo eliminó durante una actualización del plugin</span>
                </button>
              </div>
              <div id="luna-db-action-result" style="margin-top:14px;display:none"></div>
            </div>
          </div>

          <?php // ── Table stats ─────────────────────────────────────────── ?>
          <div class="luna-card">
            <h2 style="margin-top:0">📊 Estado de tablas
              <span style="font-size:13px;font-weight:normal;color:#64748b">
                — <?php echo array_sum(array_filter(array_column($tables, 'rows'), fn($v) => $v !== null)) ?> filas totales
              </span>
            </h2>
            <table class="widefat fixed striped" style="font-size:12px">
              <thead>
                <tr>
                  <th style="width:220px">Tabla</th>
                  <th style="width:80px">Filas</th>
                  <th style="width:80px">Tamaño</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tables as $t):
                  $exists = $t['rows'] !== null;
                ?>
                <tr>
                  <td><code style="font-size:11px"><?php echo esc_html($t['table']) ?></code></td>
                  <td><?php echo $exists ? '<strong>' . number_format($t['rows']) . '</strong>' : '<span style="color:#94a3b8">—</span>' ?></td>
                  <td><?php echo $exists ? esc_html($t['size_kb']) . ' KB' : '<span style="color:#94a3b8">—</span>' ?></td>
                  <td>
                    <?php if ($t['error']): ?>
                      <span style="color:#dc2626;font-size:11px">❌ <?php echo esc_html($t['error']) ?></span>
                    <?php elseif (!$exists): ?>
                      <span style="color:#94a3b8;font-size:11px">No existe</span>
                    <?php else: ?>
                      <span style="color:#16a34a;font-size:11px">✓ OK</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <p style="font-size:11px;color:#94a3b8;margin-top:8px">
              * Los conteos de filas son estimaciones de InnoDB. Para conteos exactos usá CHECK TABLE.
            </p>
          </div>

          <?php // ── Action result area ──────────────────────────────────── ?>
          <div id="luna-db-report" style="display:none;margin-top:20px" class="luna-card">
            <h2 style="margin-top:0" id="luna-db-report-title">Resultado</h2>
            <div id="luna-db-report-body"></div>
          </div>
        </div>

        <script>
        jQuery(function($){
          var nonce = <?php echo wp_json_encode(wp_create_nonce('luna_admin_nonce')); ?>;

          $('.luna-db-action').on('click', function(){
            var action = $(this).data('action');
            var btn    = $(this);
            var result = $('#luna-db-action-result');

            var labels = {
              check:          '🔍 Verificando integridad de tablas...',
              optimize:       '⚙️ Optimizando tablas, puede demorar unos segundos...',
              repair:         '🔧 Reparando tablas...',
              clean_sessions: '🧹 Limpiando sesiones expiradas...',
              regen_config:   '🔄 Regenerando archivo de configuración...'
            };
            btn.prop('disabled', true);
            result.html('<span style="color:#64748b">' + (labels[action] || 'Procesando...') + '</span>').show();

            $.post(ajaxurl, { action: 'luna_db_maintenance', nonce: nonce, op: action }, function(res){
              btn.prop('disabled', false);
              if (res.success) {
                result.html('<span style="color:#16a34a">✅ ' + (res.data.message || 'Completado') + '</span>');
                if (res.data.rows) {
                  var title = { check: 'Resultado CHECK TABLE', optimize: 'Resultado OPTIMIZE TABLE', repair: 'Resultado REPAIR TABLE' };
                  var html  = '<table class="widefat striped" style="font-size:12px"><thead><tr><th>Tabla</th><th>Operación</th><th>Tipo</th><th>Mensaje</th></tr></thead><tbody>';
                  $.each(res.data.rows, function(_, r){
                    var ok  = (r.Msg_text === 'OK' || r.Msg_text === 'Table is already up to date');
                    var col = ok ? '#16a34a' : '#dc2626';
                    html += '<tr><td><code style="font-size:11px">' + r.Table + '</code></td>'
                          + '<td>' + r.Op + '</td>'
                          + '<td>' + r.Msg_type + '</td>'
                          + '<td style="color:' + col + '">' + r.Msg_text + '</td></tr>';
                  });
                  html += '</tbody></table>';
                  $('#luna-db-report-title').text(title[action] || 'Resultado');
                  $('#luna-db-report-body').html(html);
                  $('#luna-db-report').show();
                  $('html,body').animate({scrollTop: $('#luna-db-report').offset().top - 40}, 300);
                }
                if (action === 'regen_config') setTimeout(function(){ location.reload(); }, 1200);
              } else {
                result.html('<span style="color:#dc2626">❌ ' + (res.data || 'Error desconocido') + '</span>');
              }
            }).fail(function(){
              btn.prop('disabled', false);
              result.html('<span style="color:#dc2626">❌ Error de conexión. Recargá e intentá de nuevo.</span>');
            });
          });
        });
        </script>
        <?php
    }

    // ── AJAX: database maintenance operations ─────────────────────────────────
    public function ajax_db_maintenance() {
        check_ajax_referer('luna_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');

        $op     = sanitize_key($_POST['op'] ?? '');
        $appDb  = $this->get_app_db();
        $appPfx = $this->get_app_prefix();
        global $wpdb;

        $useAppDb = ($appDb !== null && $appPfx !== null);
        $pfx      = $useAppDb ? $appPfx : $wpdb->prefix . 'luna_';

        $table_names = [
            'users', 'workspaces', 'workspace_members', 'workspace_labels',
            'columns_k', 'cards', 'card_tags', 'card_assignees', 'card_checklist',
            'card_dependencies', 'attachments', 'sessions',
            'notifications', 'app_settings', 'workspace_templates', 'activity_log',
            'user_meta',
        ];

        // Build list of existing tables
        $existing = [];
        foreach ($table_names as $t) {
            $full = $pfx . $t;
            if ($useAppDb) {
                try {
                    $found = $appDb->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=" . $appDb->quote($full))->fetchColumn();
                    if ($found) $existing[] = "`{$full}`";
                } catch (Exception $e) {}
            } else {
                $found = $wpdb->get_var($wpdb->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s", $full));
                if ($found) $existing[] = "`{$full}`";
            }
        }

        if (empty($existing)) {
            wp_send_json_error('No se encontraron tablas Luna en la base de datos.');
        }

        $table_list = implode(', ', $existing);

        switch ($op) {

            case 'check':
            case 'optimize':
            case 'repair':
                $sql_op  = strtoupper($op);
                $rows    = [];
                if ($useAppDb) {
                    try {
                        $st = $appDb->query("{$sql_op} TABLE {$table_list}");
                        $rows = $st->fetchAll();
                    } catch (Exception $e) {
                        wp_send_json_error("Error en {$sql_op}: " . $e->getMessage());
                    }
                } else {
                    $results = $wpdb->get_results("{$sql_op} TABLE {$table_list}", ARRAY_A);
                    if ($results === null) wp_send_json_error("Error en {$sql_op}: " . $wpdb->last_error);
                    $rows = $results;
                }
                $errors = array_filter($rows, fn($r) => isset($r['Msg_type']) && $r['Msg_type'] === 'error');
                $msg = count($errors)
                    ? count($errors) . ' tabla(s) con errores — revisá el detalle abajo.'
                    : 'Todas las tablas procesadas correctamente.';
                wp_send_json_success(['message' => $msg, 'rows' => $rows]);

            case 'clean_sessions':
                $tbl = "`{$pfx}sessions`";
                if ($useAppDb) {
                    try {
                        $st      = $appDb->prepare("DELETE FROM {$tbl} WHERE expires_at < NOW()");
                        $st->execute();
                        $deleted = $st->rowCount();
                    } catch (Exception $e) {
                        wp_send_json_error('Error: ' . $e->getMessage());
                    }
                } else {
                    $wpdb->query("DELETE FROM {$tbl} WHERE expires_at < NOW()");
                    $deleted = $wpdb->rows_affected;
                }
                wp_send_json_success(['message' => "{$deleted} sesión(es) expirada(s) eliminada(s)."]);

            case 'regen_config':
                $pfx_detected = Luna_Activator::regenerate_app_config();
                wp_send_json_success(['message' => "luna-wp-config.php regenerado. Prefijo detectado: '{$pfx_detected}'"]);

            default:
                wp_send_json_error('Operación desconocida.');
        }
    }

    // ── Save daily reminder schedule ─────────────────────────────────────
    public function ajax_save_reminders() {
        check_ajax_referer('luna_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');

        $enabled = (int)($_POST['enabled'] ?? 0) ? 1 : 0;
        $hour    = max(0, min(23, (int)($_POST['hour'] ?? 8)));
        $data    = json_encode(['enabled' => (bool)$enabled, 'hour' => $hour]);

        // Try app DB first, fall back to WP options
        $appDb  = $this->get_app_db();
        $appPfx = $this->get_app_prefix();
        $saved  = false;

        if ($appDb && $appPfx !== null) {
            $tbl = $appPfx !== '' ? "`{$appPfx}app_settings`" : '`app_settings`';
            try {
                $appDb->prepare("INSERT INTO {$tbl} (meta_key, meta_value) VALUES ('reminder_schedule', ?)
                                 ON DUPLICATE KEY UPDATE meta_value=?")
                      ->execute([$data, $data]);
                $saved = true;
            } catch (Exception $e) {}
        }

        if (!$saved) {
            update_option('luna_reminder_schedule', $data);
        }

        // Reschedule WP cron based on new config
        $hook = 'luna_send_daily_reminders';
        wp_clear_scheduled_hook($hook);
        if ($enabled) {
            $next = mktime($hour, 0, 0);
            if ($next < time()) $next = strtotime('+1 day', $next);
            wp_schedule_event($next, 'daily', $hook);
        }

        wp_send_json_success([
            'message' => $enabled
                ? "Recordatorios activados — se enviarán diariamente a las {$hour}:00 hs."
                : 'Recordatorios desactivados.',
        ]);
    }

    // ── Send reminders immediately (test/preview) ────────────────────────
    public function ajax_send_reminders_now() {
        check_ajax_referer('luna_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');

        // Build the URL to reminders.php
        $secret   = defined('LUNA_CRON_SECRET') ? LUNA_CRON_SECRET : get_option('luna_cron_secret', '');
        $app_url  = defined('LUNA_APP_URL')      ? LUNA_APP_URL      : '';

        if (!$app_url) {
            // Derive from the plugin directory URL
            $app_url = plugin_dir_url(__FILE__) . '../app/';
        }

        $url = rtrim($app_url, '/') . '/api/reminders.php?action=send';

        // Enviar cron_secret en el body POST (no en la URL para evitar que quede en logs del servidor)
        $resp = wp_remote_post($url, [
            'timeout'   => 60,
            'sslverify' => false,
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => $secret ? json_encode(['cron_secret' => $secret]) : '{}',
        ]);

        if (is_wp_error($resp)) {
            // Fall back: run directly via PHP include (same process)
            ob_start();
            try {
                $reminders_file = plugin_dir_path(__FILE__) . '../app/api/reminders.php';
                if (file_exists($reminders_file)) {
                    $_GET['action'] = 'send';
                    $_GET['force']  = '1';
                    include $reminders_file;
                    $out = ob_get_clean();
                    $decoded = json_decode($out, true);
                    if ($decoded) {
                        wp_send_json_success($decoded);
                    }
                    wp_send_json_success(['message' => 'Enviado (CLI). ' . $out]);
                }
            } catch (Exception $e) {
                ob_end_clean();
            }
            wp_send_json_error('No se pudo contactar reminders.php: ' . $resp->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);
        $data = json_decode($body, true);

        if (!$data || !isset($data['ok'])) {
            wp_send_json_error("Respuesta inesperada (HTTP {$code}): " . substr($body, 0, 300));
        }

        $sent    = $data['sent']    ?? 0;
        $errors  = $data['errors']  ?? 0;
        $preview = $data['preview'] ?? [];
        $skipped = $data['skipped'] ?? false;

        $lines = $skipped
            ? ['⚠️ Ya se enviaron recordatorios hoy. Usá force=1 desde CLI para forzar.']
            : ["✅ Enviados: {$sent} | ❌ Errores: {$errors}"];

        foreach ($preview as $p) {
            $lines[] = "  → {$p['name']} &lt;{$p['to']}&gt; — {$p['total']} tarea(s)";
        }

        wp_send_json_success(['message' => implode('<br>', $lines), 'detail' => $data]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  ONBOARDING WIZARD
    // ══════════════════════════════════════════════════════════════════════════

    public function maybe_redirect_to_wizard() {
        if (!get_transient('luna_activation_redirect')) return;
        if (!current_user_can('manage_options')) return;
        delete_transient('luna_activation_redirect');
        // Solo redirigir si el wizard no fue completado todavía
        if (!get_option('luna_onboarding_done')) {
            wp_redirect(admin_url('admin.php?page=luna-onboarding'));
            exit;
        }
    }

    // AJAX: dismiss contraseña inicial (usuario confirmó que la guardó)
    public function ajax_dismiss_initial_pass() {
        check_ajax_referer('luna_dismiss_pass', 'nonce');
        if (!current_user_can('manage_options')) wp_die('Sin permisos');
        delete_option('luna_initial_admin_pass');
        wp_send_json_success();
    }

    // AJAX: marcar wizard como completado
    public function ajax_wizard_done() {
        if (!check_ajax_referer('luna_admin_nonce', 'nonce', false)) wp_die();
        if (!current_user_can('manage_options')) wp_die('Sin permisos');
        update_option('luna_onboarding_done', 1);
        wp_send_json_success();
    }

    // AJAX: validar licencia desde el wizard
    public function ajax_wizard_validate_license() {
        check_ajax_referer('luna_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');

        $key    = sanitize_text_field($_POST['license_key'] ?? '');
        $domain = parse_url(get_site_url(), PHP_URL_HOST) ?? ($_SERVER['HTTP_HOST'] ?? '');

        if (!$key) {
            wp_send_json_error('Ingresá una clave de licencia');
        }

        $result = Luna_License::verify($key, $domain);

        if (!empty($result['valid'])) {
            update_option('luna_license_key', $key);
            Luna_Activator::regenerate_app_config();
            @unlink(LUNA_APP_DIR . 'luna-license-cache.json');
            $plan = Luna_License::plan_label($result['plan'] ?? 'unknown');
            wp_send_json_success([
                'plan'       => $plan,
                'expires_at' => $result['expires_at'] ?? '—',
            ]);
        } else {
            $msg = $result['message'] ?? 'Licencia inválida';
            wp_send_json_error($msg);
        }
    }

    // ── Wizard de onboarding ──────────────────────────────────────────────────
    public function render_onboarding_wizard() {
        if (!current_user_can('manage_options')) return;

        // Si ya completó el wizard → redirigir a configuración
        if (get_option('luna_onboarding_done') && !isset($_GET['force'])) {
            wp_redirect(admin_url('admin.php?page=luna-workspace'));
            exit;
        }

        $nonce        = wp_create_nonce('luna_admin_nonce');
        $license_key  = get_option('luna_license_key', '');
        $pass         = get_option('luna_initial_admin_pass', '');
        $entry_token  = get_option('luna_entry_token', '');
        $permanent_url = $entry_token ? add_query_arg('luna_enter', $entry_token, home_url('/')) : '';
        $has_license  = !empty($license_key);

        // Marcar wizard como completado si se llega al paso 3
        if (isset($_GET['step']) && (int)$_GET['step'] === 3) {
            update_option('luna_onboarding_done', 1);
        }
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Luna Workspace — Configuración inicial</title>
            <?php wp_head(); ?>
            <style>
                *{box-sizing:border-box;margin:0;padding:0}
                body{background:#f0f2ff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
                .wz-wrap{width:100%;max-width:560px}
                .wz-logo{text-align:center;margin-bottom:28px}
                .wz-logo span{font-size:40px}
                .wz-logo h1{font-size:22px;font-weight:800;color:#1e1e3f;margin-top:8px}
                .wz-logo p{color:#6b7280;font-size:14px;margin-top:4px}
                /* Progress steps */
                .wz-steps{display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:32px}
                .wz-step{display:flex;flex-direction:column;align-items:center;gap:6px;flex:1;position:relative}
                .wz-step-num{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;border:2px solid #d1d5db;background:#fff;color:#9ca3af;transition:all .3s;z-index:1}
                .wz-step-label{font-size:11px;color:#9ca3af;font-weight:600;white-space:nowrap}
                .wz-step.active .wz-step-num{background:#5b6af0;border-color:#5b6af0;color:#fff}
                .wz-step.active .wz-step-label{color:#5b6af0}
                .wz-step.done .wz-step-num{background:#22c55e;border-color:#22c55e;color:#fff}
                .wz-step.done .wz-step-label{color:#22c55e}
                .wz-step:not(:last-child)::after{content:'';position:absolute;top:18px;left:calc(50% + 18px);right:calc(-50% + 18px);height:2px;background:#d1d5db}
                .wz-step.done:not(:last-child)::after{background:#22c55e}
                /* Card */
                .wz-card{background:#fff;border-radius:16px;padding:36px;box-shadow:0 4px 24px rgba(0,0,0,.08)}
                .wz-card h2{font-size:20px;font-weight:800;color:#1e1e3f;margin-bottom:8px}
                .wz-card p.sub{color:#6b7280;font-size:14px;margin-bottom:24px;line-height:1.6}
                /* License input */
                .wz-input{width:100%;padding:14px 16px;border:2px solid #e5e7eb;border-radius:10px;font-size:15px;font-family:monospace;letter-spacing:2px;outline:none;transition:border .2s}
                .wz-input:focus{border-color:#5b6af0}
                .wz-input.err{border-color:#ef4444}
                .wz-input.ok{border-color:#22c55e}
                /* Buttons */
                .wz-btn{width:100%;padding:14px;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;border:none;transition:all .2s;margin-top:12px}
                .wz-btn-primary{background:#5b6af0;color:#fff}
                .wz-btn-primary:hover{background:#4a58d4}
                .wz-btn-primary:disabled{background:#a5b4fc;cursor:not-allowed}
                .wz-btn-secondary{background:#f3f4f6;color:#374151;border:2px solid #e5e7eb}
                .wz-btn-secondary:hover{background:#e9eaf0}
                .wz-btn-green{background:#22c55e;color:#fff}
                .wz-btn-green:hover{background:#16a34a}
                /* Msg */
                .wz-msg{padding:12px 16px;border-radius:8px;font-size:13px;margin-top:12px;display:none;line-height:1.5}
                .wz-msg.err{background:#fef2f2;color:#dc2626;border:1px solid #fca5a5}
                .wz-msg.ok{background:#f0fdf4;color:#166534;border:1px solid #86efac}
                /* Password box */
                .wz-pass-box{background:#fefce8;border:2px solid #fde047;border-radius:12px;padding:20px;margin:20px 0}
                .wz-pass-box label{font-size:12px;font-weight:700;color:#854d0e;display:block;margin-bottom:8px}
                .wz-pass-row{display:flex;align-items:center;gap:10px}
                .wz-pass-val{font-size:20px;font-family:monospace;letter-spacing:3px;background:#fff;padding:10px 16px;border-radius:8px;border:1px solid #fde047;color:#1e1e3f;flex:1;word-break:break-all}
                .wz-copy-btn{background:#854d0e;color:#fff;border:none;border-radius:8px;padding:10px 16px;font-weight:700;font-size:13px;cursor:pointer;white-space:nowrap}
                .wz-copy-btn:hover{background:#92400e}
                /* Success icon */
                .wz-success-icon{text-align:center;font-size:64px;margin-bottom:16px}
                .wz-skip{text-align:center;margin-top:16px}
                .wz-skip a{font-size:12px;color:#9ca3af;text-decoration:none}
                .wz-skip a:hover{color:#6b7280}
            </style>
        </head>
        <body>
        <div class="wz-wrap">

            <!-- Logo -->
            <div class="wz-logo">
                <span>🌙</span>
                <h1>Luna Workspace</h1>
                <p>Configuración inicial — solo toma 2 minutos</p>
            </div>

            <!-- Steps indicator -->
            <div class="wz-steps" id="wz-steps">
                <div class="wz-step active" id="step-ind-1">
                    <div class="wz-step-num">1</div>
                    <div class="wz-step-label">Licencia</div>
                </div>
                <div class="wz-step" id="step-ind-2">
                    <div class="wz-step-num">2</div>
                    <div class="wz-step-label">Credenciales</div>
                </div>
                <div class="wz-step" id="step-ind-3">
                    <div class="wz-step-num">3</div>
                    <div class="wz-step-label">¡Listo!</div>
                </div>
            </div>

            <!-- Paso 1: Licencia -->
            <div class="wz-card" id="wz-step-1">
                <h2>🔑 Activá tu licencia</h2>
                <p class="sub">Ingresá la clave que recibiste al registrarte. La verificación es automática y solo tarda unos segundos.</p>
                <input type="text" id="wz-license-input" class="wz-input"
                       placeholder="LUNA-XXXX-XXXX-XXXX-XXXX"
                       value="<?php echo esc_attr($license_key) ?>"
                       spellcheck="false" autocomplete="off">
                <div class="wz-msg" id="wz-license-msg"></div>
                <button class="wz-btn wz-btn-primary" id="wz-btn-license">Validar licencia →</button>
                <div class="wz-skip">
                    <a href="#" id="wz-skip-license">Continuar sin licencia (modo limitado)</a>
                </div>
            </div>

            <!-- Paso 2: Credenciales -->
            <div class="wz-card" id="wz-step-2" style="display:none">
                <h2>🔐 Tus credenciales de acceso</h2>
                <p class="sub">Estas son las credenciales para ingresar a Luna Workspace. Guardalas en un lugar seguro — también las vas a encontrar siempre en <strong>Luna Workspace → Configuración</strong>.</p>

                <div style="background:#f8f9fa;border-radius:10px;padding:16px;margin-bottom:16px">
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e9ecef;font-size:14px">
                        <span style="color:#6b7280;font-weight:600">Usuario</span>
                        <strong style="font-family:monospace">admin</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;font-size:14px">
                        <span style="color:#6b7280;font-weight:600">Contraseña</span>
                        <span style="display:flex;align-items:center;gap:8px">
                            <strong id="wz-pass-display" style="font-family:monospace;font-size:16px;letter-spacing:2px">
                                <?php echo esc_html($pass ?: '(generá una desde Configuración)') ?>
                            </strong>
                        </span>
                    </div>
                </div>

                <?php if ($pass): ?>
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($pass) ?>');this.textContent='✓ ¡Copiada!';this.style.background='#16a34a';setTimeout(()=>{this.textContent='📋 Copiar contraseña';this.style.background=''},2000)"
                        style="width:100%;padding:12px;background:#5b6af0;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;margin-bottom:12px">
                    📋 Copiar contraseña
                </button>
                <?php endif; ?>

                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 14px;font-size:12px;color:#1e40af;line-height:1.6;margin-bottom:16px">
                    💡 <strong>Tip:</strong> Una vez adentro de Luna, andá a tu perfil y cambiá la contraseña por una que recuerdes fácilmente.
                </div>

                <button class="wz-btn wz-btn-primary" id="wz-btn-credentials">Continuar →</button>
            </div>

            <!-- Paso 3: Listo -->
            <div class="wz-card" id="wz-step-3" style="display:none">
                <div class="wz-success-icon">🎉</div>
                <h2 style="text-align:center;margin-bottom:12px">¡Luna está lista!</h2>
                <p class="sub" style="text-align:center">Todo está configurado. Hacé clic en el botón para ingresar a tu nuevo espacio de trabajo.</p>

                <?php if ($permanent_url): ?>
                <a href="<?php echo esc_url($permanent_url) ?>"
                   style="display:block;width:100%;padding:16px;background:linear-gradient(135deg,#5b6af0,#7c3aed);color:#fff;border:none;border-radius:12px;font-size:16px;font-weight:800;cursor:pointer;text-align:center;text-decoration:none;margin-bottom:12px">
                    🚀 Entrar a Luna Workspace →
                </a>
                <?php endif; ?>

                <a href="<?php echo admin_url('admin.php?page=luna-workspace') ?>"
                   style="display:block;width:100%;padding:12px;background:#f3f4f6;color:#374151;border:2px solid #e5e7eb;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-align:center;text-decoration:none">
                    ⚙️ Ir a Configuración
                </a>
            </div>

        </div>

        <script>
        (function(){
            const nonce = '<?php echo esc_js($nonce) ?>';
            const ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')) ?>';
            let currentStep = <?php echo $has_license ? 2 : 1 ?>;

            function goToStep(n) {
                currentStep = n;
                [1,2,3].forEach(i => {
                    document.getElementById('wz-step-' + i).style.display = i === n ? '' : 'none';
                    const ind = document.getElementById('step-ind-' + i);
                    ind.className = 'wz-step' + (i < n ? ' done' : i === n ? ' active' : '');
                    if (i < n) ind.querySelector('.wz-step-num').textContent = '✓';
                    else if (i >= n) ind.querySelector('.wz-step-num').textContent = i;
                });
                // Marcar wizard como completado al llegar al paso 3
                if (n === 3) {
                    fetch(ajaxUrl + '?action=luna_wizard_done&nonce=' + nonce);
                }
            }

            // Si ya tiene licencia, ir directo al paso 2
            if (currentStep === 2) goToStep(2);

            // ── Paso 1: Validar licencia ──────────────────────────────────────
            function showMsg(id, text, type) {
                const el = document.getElementById(id);
                el.textContent = text;
                el.className = 'wz-msg ' + type;
                el.style.display = 'block';
            }

            document.getElementById('wz-btn-license').addEventListener('click', function() {
                const key = document.getElementById('wz-license-input').value.trim();
                if (!key) { showMsg('wz-license-msg', 'Ingresá tu clave de licencia', 'err'); return; }

                this.disabled = true;
                this.textContent = '⏳ Validando...';
                document.getElementById('wz-license-input').className = 'wz-input';

                const fd = new FormData();
                fd.append('action', 'luna_wizard_validate_license');
                fd.append('nonce', nonce);
                fd.append('license_key', key);

                fetch(ajaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(r => {
                        if (r.success) {
                            document.getElementById('wz-license-input').className = 'wz-input ok';
                            showMsg('wz-license-msg', '✅ Licencia activada — Plan: ' + r.data.plan + ' (vence: ' + r.data.expires_at + ')', 'ok');
                            setTimeout(() => goToStep(2), 1200);
                        } else {
                            document.getElementById('wz-license-input').className = 'wz-input err';
                            showMsg('wz-license-msg', '❌ ' + r.data, 'err');
                        }
                    })
                    .catch(() => showMsg('wz-license-msg', '❌ Error de conexión', 'err'))
                    .finally(() => {
                        this.disabled = false;
                        this.textContent = 'Validar licencia →';
                    });
            });

            document.getElementById('wz-skip-license').addEventListener('click', function(e) {
                e.preventDefault();
                goToStep(2);
            });

            // ── Paso 2: Credenciales ──────────────────────────────────────────
            document.getElementById('wz-btn-credentials').addEventListener('click', function() {
                goToStep(3);
            });
        })();
        </script>
        <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  BACKUP & RESTORE
    // ══════════════════════════════════════════════════════════════════════════

    private function backup_dir() {
        $dir = plugin_dir_path(__FILE__) . '../app/backups/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        // Bloquear acceso web directo
        $ht = $dir . '.htaccess';
        if (!file_exists($ht)) {
            file_put_contents($ht, "Options -Indexes\nDeny from all\n");
        }
        return $dir;
    }

    private function luna_tables() {
        return [
            // Settings y templates primero (sin dependencias)
            'app_settings', 'workspace_templates',
            // Entidades base
            'users', 'workspaces', 'workspace_members', 'user_meta',
            // Kanban
            'columns_k', 'cards',
            // Relaciones de tarjetas
            'card_tags', 'card_assignees', 'card_checklist', 'card_dependencies',
            // Contenido
            'attachments', 'notifications',
            // Extras
            'workspace_labels', 'activity_log',
        ];
    }

    // ── Crear backup ──────────────────────────────────────────────────────────
    public function ajax_backup_create() {
        check_ajax_referer('luna_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');

        global $wpdb;
        $p = $wpdb->prefix . 'luna_';

        $backup = [
            'luna_backup'    => true,
            'schema_version' => '1.0',
            'plugin_version' => LUNA_VERSION,
            'created_at'     => current_time('c'),
            'wp_prefix'      => $wpdb->prefix,
            'tables'         => [],
            'counts'         => [],
        ];

        foreach ($this->luna_tables() as $table) {
            $full = $p . $table;
            if (!$wpdb->get_var("SHOW TABLES LIKE '{$full}'")) continue;
            $rows = $wpdb->get_results("SELECT * FROM `{$full}`", ARRAY_A) ?: [];
            // Excluir rate limiting de app_settings (efímero, no sirve en un restore)
            if ($table === 'app_settings') {
                $rows = array_values(array_filter($rows, fn($r) => strpos($r['meta_key'] ?? '', 'rate_login_') !== 0));
            }
            $backup['tables'][$table] = $rows;
            $backup['counts'][$table] = count($rows);
        }

        $dir      = $this->backup_dir();
        $filename = 'luna-backup-' . date('Ymd-His') . '.json';
        $filepath = $dir . $filename;
        $json     = json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if (file_put_contents($filepath, $json) === false) {
            wp_send_json_error('No se pudo escribir el archivo. Verificá permisos en: ' . $dir);
        }

        // Mantener solo los últimos 10 backups
        $files = glob($dir . 'luna-backup-*.json') ?: [];
        if (count($files) > 10) {
            sort($files);
            foreach (array_slice($files, 0, count($files) - 10) as $old) @unlink($old);
        }

        wp_send_json_success([
            'filename' => $filename,
            'size'     => size_format(strlen($json)),
            'total'    => array_sum($backup['counts']),
            'counts'   => $backup['counts'],
        ]);
    }

    // ── Listar backups ────────────────────────────────────────────────────────
    public function ajax_backup_list() {
        check_ajax_referer('luna_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');

        $dir   = $this->backup_dir();
        $files = glob($dir . 'luna-backup-*.json') ?: [];
        rsort($files);

        $list = [];
        foreach ($files as $f) {
            $name  = basename($f);
            $mtime = filemtime($f);
            // Leer solo los primeros 8KB para obtener counts sin cargar todo el archivo
            $head   = file_get_contents($f, false, null, 0, 8192);
            $meta   = json_decode($head, true);
            $counts = $meta['counts'] ?? [];
            $list[] = [
                'filename' => $name,
                'size'     => size_format(filesize($f)),
                'date'     => date_i18n('d/m/Y H:i:s', $mtime),
                'total'    => array_sum($counts),
                'counts'   => $counts,
            ];
        }

        wp_send_json_success(['backups' => $list]);
    }

    // ── Descargar backup ──────────────────────────────────────────────────────
    public function ajax_backup_download() {
        if (!check_ajax_referer('luna_admin_nonce', 'nonce', false)) wp_die('Nonce inválido');
        if (!current_user_can('manage_options')) wp_die('Sin permisos');

        $filename = sanitize_file_name($_GET['filename'] ?? '');
        if (!preg_match('/^luna-backup-\d{8}-\d{6}\.json$/', $filename)) wp_die('Archivo inválido');

        $filepath = $this->backup_dir() . $filename;
        if (!file_exists($filepath)) wp_die('Archivo no encontrado');

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($filepath);
        exit;
    }

    // ── Eliminar backup ───────────────────────────────────────────────────────
    public function ajax_backup_delete() {
        check_ajax_referer('luna_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');

        $filename = sanitize_file_name($_POST['filename'] ?? '');
        if (!preg_match('/^luna-backup-\d{8}-\d{6}\.json$/', $filename)) {
            wp_send_json_error('Nombre de archivo inválido');
        }

        $filepath = $this->backup_dir() . $filename;
        if (!file_exists($filepath)) wp_send_json_error('Archivo no encontrado');
        if (!unlink($filepath)) wp_send_json_error('No se pudo eliminar el archivo');

        wp_send_json_success(['deleted' => $filename]);
    }

    // ── Restaurar backup ──────────────────────────────────────────────────────
    public function ajax_backup_restore() {
        check_ajax_referer('luna_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');

        if (empty($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('No se recibió ningún archivo válido');
        }

        $json = file_get_contents($_FILES['backup_file']['tmp_name']);
        $data = json_decode($json, true);

        if (!$data || empty($data['luna_backup']) || !isset($data['tables'])) {
            wp_send_json_error('Archivo inválido — no es un backup de Luna Workspace');
        }

        global $wpdb;
        $p = $wpdb->prefix . 'luna_';

        // Crear backup automático antes de restaurar (seguro)
        $this->ajax_backup_create_silent();

        $wpdb->query('SET FOREIGN_KEY_CHECKS=0');
        $restored = [];

        foreach ($this->luna_tables() as $table) {
            if (!isset($data['tables'][$table])) continue;
            $full = $p . $table;
            if (!$wpdb->get_var("SHOW TABLES LIKE '{$full}'")) continue;

            $wpdb->query("TRUNCATE TABLE `{$full}`");
            $count = 0;
            foreach ($data['tables'][$table] as $row) {
                if ($wpdb->insert($full, $row) !== false) $count++;
            }
            $restored[$table] = $count;
        }

        $wpdb->query('SET FOREIGN_KEY_CHECKS=1');

        // Regenerar luna-wp-config.php por si cambió algo
        Luna_Activator::regenerate_app_config();

        wp_send_json_success([
            'message'  => 'Restore completado exitosamente',
            'restored' => $restored,
            'total'    => array_sum($restored),
        ]);
    }

    // Crear backup silencioso (sin respuesta JSON) — usado internamente antes de restore
    private function ajax_backup_create_silent() {
        global $wpdb;
        $p = $wpdb->prefix . 'luna_';
        $backup = [
            'luna_backup' => true, 'schema_version' => '1.0',
            'plugin_version' => LUNA_VERSION, 'created_at' => current_time('c'),
            'wp_prefix' => $wpdb->prefix, 'tables' => [], 'counts' => [],
        ];
        foreach ($this->luna_tables() as $table) {
            $full = $p . $table;
            if (!$wpdb->get_var("SHOW TABLES LIKE '{$full}'")) continue;
            $rows = $wpdb->get_results("SELECT * FROM `{$full}`", ARRAY_A) ?: [];
            $backup['tables'][$table] = $rows;
            $backup['counts'][$table] = count($rows);
        }
        $dir      = $this->backup_dir();
        $filename = 'luna-backup-' . date('Ymd-His') . '-pre-restore.json';
        file_put_contents($dir . $filename, json_encode($backup, JSON_UNESCAPED_UNICODE));
    }

    // ── Página Backup & Restore ───────────────────────────────────────────────
    public function render_backup_page() {
        if (!current_user_can('manage_options')) return;
        $nonce = wp_create_nonce('luna_admin_nonce');
        ?>
        <div class="wrap luna-admin-wrap">
            <h1 style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
                <span style="font-size:24px">🗄️</span> Backup &amp; Restore
            </h1>
            <p style="color:#666;margin-bottom:24px;font-size:13px">
                Exporta e importa todos los datos de Luna Workspace. Los archivos adjuntos físicos (imágenes, PDFs) no se incluyen en el backup — solo sus metadatos (nombre, URL). Se guardan hasta 10 backups en el servidor.
            </p>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

                <!-- Crear backup -->
                <div class="luna-card">
                    <h2 style="margin-top:0;font-size:15px;display:flex;align-items:center;gap:8px">
                        <span>📦</span> Crear Backup
                    </h2>
                    <p style="color:#555;font-size:13px;margin-bottom:16px">
                        Genera un archivo JSON con todas las tablas de Luna: tareas, usuarios, columnas, etiquetas, configuraciones y más.
                    </p>
                    <button id="luna-btn-backup" class="button button-primary" style="font-size:13px;padding:6px 18px">
                        ⬇️ Descargar backup completo
                    </button>
                    <div id="luna-backup-result" style="margin-top:12px;display:none"></div>
                </div>

                <!-- Restaurar -->
                <div class="luna-card">
                    <h2 style="margin-top:0;font-size:15px;display:flex;align-items:center;gap:8px">
                        <span>♻️</span> Restaurar
                    </h2>
                    <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#856404;line-height:1.5">
                        ⚠️ <strong>Atención:</strong> Restaurar reemplaza TODOS los datos actuales de Luna con los del archivo. Antes de restaurar se crea un backup automático de seguridad.
                    </div>
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px">Seleccioná un archivo .json de backup:</label>
                    <input type="file" id="luna-restore-file" accept=".json" style="margin-bottom:12px;display:block;font-size:13px">
                    <button id="luna-btn-restore" class="button" style="font-size:13px;padding:6px 18px;border-color:#dc3545;color:#dc3545" disabled>
                        ♻️ Restaurar desde archivo
                    </button>
                    <div id="luna-restore-result" style="margin-top:12px;display:none"></div>
                </div>

            </div>

            <!-- Lista de backups -->
            <div class="luna-card" style="margin-top:24px">
                <h2 style="margin-top:0;font-size:15px;display:flex;align-items:center;justify-content:space-between">
                    <span>📋 Backups guardados en el servidor</span>
                    <button id="luna-btn-refresh-list" class="button" style="font-size:11px">↻ Actualizar</button>
                </h2>
                <div id="luna-backup-list"><p style="color:#999;font-size:13px">Cargando...</p></div>
            </div>
        </div>

        <script>
        jQuery(function($){
            const nonce   = '<?php echo esc_js($nonce); ?>';
            const ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';

            function showMsg(selector, html, type) {
                const bg = { ok:'#d4edda', err:'#f8d7da', info:'#cce5ff' };
                $(selector).html('<div style="padding:10px 14px;border-radius:6px;background:' + (bg[type]||bg.info) + ';font-size:13px;line-height:1.6">' + html + '</div>').show();
            }

            // ── Crear backup ──────────────────────────────────────────────────
            $('#luna-btn-backup').on('click', function() {
                const $btn = $(this).prop('disabled', true).text('⏳ Generando...');
                $.post(ajaxUrl, { action: 'luna_backup_create', nonce }, function(r) {
                    if (r.success) {
                        const d = r.data;
                        const dlUrl = ajaxUrl + '?action=luna_backup_download&nonce=' + nonce + '&filename=' + encodeURIComponent(d.filename);
                        showMsg('#luna-backup-result',
                            '✅ <strong>' + d.filename + '</strong> creado (' + d.size + ' — ' + d.total + ' registros)<br>' +
                            '<a href="' + dlUrl + '" style="color:#0d6efd;font-weight:600">⬇️ Descargar ahora</a>',
                            'ok');
                        loadList();
                    } else {
                        showMsg('#luna-backup-result', '❌ ' + r.data, 'err');
                    }
                }).fail(() => showMsg('#luna-backup-result', '❌ Error de conexión', 'err'))
                  .always(() => $btn.prop('disabled', false).text('⬇️ Descargar backup completo'));
            });

            // ── Restaurar ─────────────────────────────────────────────────────
            $('#luna-restore-file').on('change', function() {
                $('#luna-btn-restore').prop('disabled', !this.files.length);
            });

            $('#luna-btn-restore').on('click', function() {
                if (!confirm('⚠️ Esta acción reemplazará TODOS los datos de Luna con el archivo seleccionado.\n\nAntes de continuar se creará un backup automático de seguridad.\n\n¿Confirmas?')) return;
                const $btn = $(this).prop('disabled', true).text('⏳ Restaurando...');
                const fd = new FormData();
                fd.append('action', 'luna_backup_restore');
                fd.append('nonce', nonce);
                fd.append('backup_file', $('#luna-restore-file')[0].files[0]);
                $.ajax({
                    url: ajaxUrl, type: 'POST', data: fd,
                    contentType: false, processData: false,
                    success(r) {
                        if (r.success) {
                            const rows = Object.entries(r.data.restored)
                                .filter(([,v]) => v > 0)
                                .map(([k,v]) => k + ': ' + v).join(' · ');
                            showMsg('#luna-restore-result',
                                '✅ <strong>' + r.data.message + '</strong> — ' + r.data.total + ' registros restaurados.<br><small style="color:#555">' + rows + '</small>',
                                'ok');
                            loadList();
                        } else {
                            showMsg('#luna-restore-result', '❌ ' + r.data, 'err');
                        }
                    },
                    error() { showMsg('#luna-restore-result', '❌ Error de conexión', 'err'); },
                    complete() { $btn.prop('disabled', false).text('♻️ Restaurar desde archivo'); }
                });
            });

            // ── Lista de backups ──────────────────────────────────────────────
            function loadList() {
                $.post(ajaxUrl, { action: 'luna_backup_list', nonce }, function(r) {
                    if (!r.success || !r.data.backups.length) {
                        $('#luna-backup-list').html('<p style="color:#999;font-size:13px">No hay backups guardados aún. Creá el primero con el botón de arriba.</p>');
                        return;
                    }
                    let html = '<table style="width:100%;border-collapse:collapse;font-size:13px">'
                        + '<thead><tr style="border-bottom:2px solid #eee;text-align:left">'
                        + '<th style="padding:8px 10px">Fecha</th>'
                        + '<th style="padding:8px 10px">Archivo</th>'
                        + '<th style="padding:8px 10px;text-align:right">Tamaño</th>'
                        + '<th style="padding:8px 10px;text-align:right">Registros</th>'
                        + '<th style="padding:8px 10px;text-align:right">Acciones</th>'
                        + '</tr></thead><tbody>';
                    r.data.backups.forEach(function(b) {
                        const dlUrl = ajaxUrl + '?action=luna_backup_download&nonce=' + nonce + '&filename=' + encodeURIComponent(b.filename);
                        const isPre = b.filename.includes('pre-restore');
                        html += '<tr style="border-bottom:1px solid #f3f3f3' + (isPre ? ';background:#fffbf0' : '') + '">'
                            + '<td style="padding:8px 10px">' + b.date + (isPre ? ' <span style="font-size:10px;color:#856404;background:#fff3cd;padding:1px 6px;border-radius:4px">pre-restore</span>' : '') + '</td>'
                            + '<td style="padding:8px 10px;font-family:monospace;font-size:11px;color:#555">' + b.filename + '</td>'
                            + '<td style="padding:8px 10px;text-align:right">' + b.size + '</td>'
                            + '<td style="padding:8px 10px;text-align:right">' + b.total + '</td>'
                            + '<td style="padding:8px 10px;text-align:right;white-space:nowrap">'
                            + '<a href="' + dlUrl + '" class="button button-small" style="margin-right:6px">⬇️ Descargar</a>'
                            + '<button class="button button-small luna-del-backup" data-file="' + b.filename + '" style="color:#dc3545;border-color:#dc3545">🗑️</button>'
                            + '</td></tr>';
                    });
                    html += '</tbody></table>';
                    $('#luna-backup-list').html(html);
                }).fail(() => $('#luna-backup-list').html('<p style="color:#c00;font-size:13px">Error al cargar la lista de backups.</p>'));
            }

            $(document).on('click', '.luna-del-backup', function() {
                const filename = $(this).data('file');
                if (!confirm('¿Eliminar el backup "' + filename + '"?\nEsta acción no se puede deshacer.')) return;
                $.post(ajaxUrl, { action: 'luna_backup_delete', nonce, filename }, function(r) {
                    if (r.success) loadList();
                    else alert('Error: ' + r.data);
                });
            });

            $('#luna-btn-refresh-list').on('click', loadList);
            loadList();
        });
        </script>
        <?php
    }

}

