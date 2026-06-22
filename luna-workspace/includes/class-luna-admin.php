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
    }

    public function maybe_migrate(): void {
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
        // Show initial admin password once after fresh installation
        $initial_pass = get_option('luna_initial_admin_pass');
        if ($initial_pass) {
            echo '<div class="notice notice-warning"><p><strong>Luna Workspace — Contraseña inicial del admin:</strong> <code style="font-size:14px;background:#fff3cd;padding:2px 8px;border-radius:4px">' . esc_html($initial_pass) . '</code> — Cámbiala desde el perfil del usuario.</p></div>';
            delete_option('luna_initial_admin_pass');
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
                <thead><tr><th>Plan</th><th>Workspaces</th><th>Sitios</th></tr></thead>
                <tbody>
                  <tr><td><strong>Starter</strong></td><td>1</td><td>1</td></tr>
                  <tr><td><strong>Professional</strong></td><td>3</td><td>3</td></tr>
                  <tr><td><strong>Unlimited</strong></td><td>Ilimitado</td><td>Ilimitado</td></tr>
                </tbody>
              </table>
              <p style="margin-top:12px;color:#666;font-size:12px">
                Licencias anuales, vinculadas al dominio para prevenir uso no autorizado.<br>
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
        $p     = $wpdb->prefix . 'luna_';
        $users = $wpdb->get_results(
            "SELECT id, name, email, phone, whatsapp_apikey, telegram_chat_id, notification_channel, active
             FROM `{$p}users` ORDER BY name ASC",
            ARRAY_A
        ) ?: [];

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
            <h2 style="margin-top:0">Usuarios y estado de notificaciones</h2>
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
                <tr style="<?= $u['active'] ? '' : 'opacity:.5' ?>">
                  <td>
                    <strong><?= esc_html($u['name']) ?></strong>
                    <?= !$u['active'] ? '<br><em style="color:#94a3b8;font-size:11px">inactivo</em>' : '' ?>
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
            btn.prop('disabled', true).text('…');
            $.post(ajaxurl, {
              action:  'luna_test_notification',
              nonce:   nonce,
              user_id: uid,
              channel: channel
            }, function(res){
              var box = $('#luna-notif-result');
              if (res.success) {
                box.html('<div class="notice notice-success" style="padding:10px 14px"><p>✅ Notificación de prueba enviada a <strong>'+name+'</strong>.</p></div>').show();
              } else {
                box.html('<div class="notice notice-error" style="padding:10px 14px"><p>❌ Error: ' + (res.data || 'No se pudo enviar') + '</p></div>').show();
              }
              btn.prop('disabled', false).text(channel === 'wa' ? '💬' : '📧');
              $('html,body').animate({scrollTop: box.offset().top - 40}, 300);
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

        // ── Email: leer config SMTP de la tabla de Luna y enviar sin app/config.php ──
        if (empty($user['email'])) wp_send_json_error('El usuario no tiene email configurado');

        $st  = $wpdb->get_row("SELECT meta_value FROM `{$p}app_settings` WHERE meta_key='email_settings' LIMIT 1");
        $cfg = $st ? (json_decode($st->meta_value, true) ?: []) : [];

        if (empty($cfg['enabled']) || empty($cfg['smtp_user']) || empty($cfg['smtp_pass'])) {
            wp_send_json_error('SMTP no configurado o deshabilitado. Configuralo dentro de Luna → Configuración → Email.');
        }

        $html = '<p>Hola <strong>' . esc_html($user['name']) . '</strong>,</p>'
              . '<p>Esta es una notificación de prueba de Luna Workspace. Si la recibís, todo está configurado correctamente.</p>';

        // Usar wp_mail con configuración SMTP dinámica via phpmailer_init
        $to      = $user['email'];
        $to_name = $user['name'];
        $smtp    = $cfg;

        add_action('phpmailer_init', function($mailer) use ($smtp, $to, $to_name) {
            $mailer->isSMTP();
            $mailer->Host       = $smtp['smtp_host']  ?? 'smtp.gmail.com';
            $mailer->SMTPAuth   = true;
            $mailer->Username   = $smtp['smtp_user'];
            $mailer->Password   = $smtp['smtp_pass'];
            $mailer->SMTPSecure = ($smtp['encryption'] ?? 'tls') === 'ssl' ? 'ssl' : 'tls';
            $mailer->Port       = (int)($smtp['smtp_port'] ?? 587);
            $mailer->setFrom($smtp['from_email'] ?? $smtp['smtp_user'], $smtp['from_name'] ?? 'Luna Workspace');
        });

        add_filter('wp_mail_content_type', fn() => 'text/html');

        $sent = wp_mail($to, $subject, $html);

        remove_all_filters('wp_mail_content_type');

        if ($sent) {
            wp_send_json_success();
        } else {
            wp_send_json_error('wp_mail() devolvió false. Verificá la configuración SMTP en Luna → Configuración → Email.');
        }
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

        global $wpdb;
        $p = $wpdb->prefix . 'luna_';

        $result = $wpdb->update(
            "{$p}users",
            [
                'phone'                => $phone,
                'whatsapp_apikey'      => $wakey,
                'notification_channel' => $channel,
            ],
            ['id' => $uid],
            ['%s', '%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error('Error al guardar: ' . $wpdb->last_error);
        }
        wp_send_json_success();
    }

}

