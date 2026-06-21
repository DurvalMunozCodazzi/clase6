<?php
defined('ABSPATH') || exit;

class Luna_Admin {

    public function __construct() {
        add_action('admin_menu',            [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_notices',         [$this, 'show_notices']);
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
        add_submenu_page('luna-workspace', 'Licencia',      'Licencia',      'manage_options', 'luna-license',   [$this, 'render_license_page']);
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
        $key = get_option('luna_license_key', '');
        if (empty($key)) {
            echo '<div class="notice notice-warning"><p><strong>Luna Workspace:</strong> No hay licencia configurada. <a href="' . admin_url('admin.php?page=luna-license') . '">Activar licencia →</a></p></div>';
        }
    }

    // ── Main settings page ────────────────────────────────────────────────────
    public function render_main_page() {
        if (isset($_POST['luna_save_settings']) && check_admin_referer('luna_settings')) {
            update_option('luna_page_slug',   sanitize_text_field($_POST['luna_page_slug'] ?? 'luna-app'));
            update_option('luna_session_hours', absint($_POST['luna_session_hours'] ?? 24));
            Luna_Activator::regenerate_app_config();
            echo '<div class="notice notice-success"><p>Configuración guardada.</p></div>';
        }
        $slug        = get_option('luna_page_slug', 'luna-app');
        $hours       = get_option('luna_session_hours', 24);
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
              </table>
              <p><button type="submit" name="luna_save_settings" class="button button-primary">Guardar cambios</button></p>
            </form>
          </div>
        </div>
        <?php
    }

    // ── License page ──────────────────────────────────────────────────────────
    public function render_license_page() {
        if (isset($_POST['luna_save_license_settings']) && check_admin_referer('luna_license_settings')) {
            $new_key = sanitize_text_field($_POST['luna_license_key'] ?? '');
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

}

