<?php
defined('ABSPATH') || exit;

class Luna_Admin {

    public function __construct() {
        add_action('admin_menu',            [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_notices',         [$this, 'show_notices']);
        add_action('admin_init',            [$this, 'maybe_migrate']);
        add_action('wp_ajax_luna_test_notification',  [$this, 'ajax_test_notification']);
        add_action('wp_ajax_luna_save_user_contact',  [$this, 'ajax_save_user_contact']);
        add_action('wp_ajax_luna_reset_admin_pass',   [$this, 'ajax_reset_admin_pass']);
        add_action('wp_ajax_luna_db_maintenance',     [$this, 'ajax_db_maintenance']);
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
        $app_direct    = LUNA_APP_URL . 'index.html';

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
                <p style="margin:10px 0 0;font-size:12px;color:#854d0e">Entrá a Luna con usuario <strong>admin</strong> y esta contraseña, luego cámbiala desde tu perfil.</p>
              </div>
              <?php delete_option('luna_initial_admin_pass'); ?>
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
        </div>

        <script>
        jQuery(function($){
          var nonce = <?php echo wp_json_encode(wp_create_nonce('luna_admin_nonce')); ?>;
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
            'card_dependencies', 'attachments', 'chat_messages', 'sessions',
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
                  <strong style="display:block">🔍 VERIFICAR INTEGRIDAD</strong>
                  <span style="font-size:11px;font-weight:normal;opacity:.85">verifica índices y detecta errores en las tablas</span>
                </button>
                <button class="button button-secondary luna-db-action" data-action="optimize"
                        style="text-align:left;padding:10px 16px;height:auto;line-height:1.3">
                  <strong style="display:block">⚙️ OPTIMIZAR TABLAS</strong>
                  <span style="font-size:11px;font-weight:normal;opacity:.85">reconstruye índices y recupera espacio en disco, sin tocar datos</span>
                </button>
                <button class="button button-secondary luna-db-action" data-action="repair"
                        style="text-align:left;padding:10px 16px;height:auto;line-height:1.3">
                  <strong style="display:block">🔧 REPARAR TABLAS</strong>
                  <span style="font-size:11px;font-weight:normal;opacity:.85">repara índices corruptos, no borra ningún dato</span>
                </button>
                <button class="button button-secondary luna-db-action" data-action="clean_sessions"
                        style="text-align:left;padding:10px 16px;height:auto;line-height:1.3">
                  <strong style="display:block">🧹 LIMPIAR SESIONES</strong>
                  <span style="font-size:11px;font-weight:normal;opacity:.85">borra solo los tokens de acceso vencidos, no los usuarios</span>
                </button>
                <button class="button luna-db-action" data-action="regen_config"
                        style="text-align:left;padding:10px 16px;height:auto;line-height:1.3;background:#fef9c3;border-color:#d97706;color:#92400e">
                  <strong style="display:block">🔄 REGENERAR CONFIGURACIÓN</strong>
                  <span style="font-size:11px;font-weight:normal;opacity:.85">recrea luna-wp-config.php si WordPress lo borró durante una actualización</span>
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
            'card_dependencies', 'attachments', 'chat_messages', 'sessions',
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

}

