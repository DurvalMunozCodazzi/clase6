<?php
defined('ABSPATH') || exit;

class LLS_Admin {

    public function __construct() {
        add_action('admin_menu',             [$this, 'add_menu']);
        add_action('admin_enqueue_scripts',  [$this, 'enqueue']);
        add_action('admin_post_lls_create',  [$this, 'handle_create']);
        add_action('admin_post_lls_update',  [$this, 'handle_update']);
        add_action('admin_post_lls_delete',  [$this, 'handle_delete']);
        add_action('admin_post_lls_toggle',  [$this, 'handle_toggle']);
        // Run schema migrations on every admin load (not just on plugin activation)
        add_action('admin_init', [$this, 'maybe_migrate']);
    }

    public function maybe_migrate(): void {
        global $wpdb;
        $t_lic = $wpdb->prefix . 'lls_licenses';
        $t_log = $wpdb->prefix . 'lls_verify_log';

        // Licenses table: add missing columns
        $lic_cols = array_column($wpdb->get_results("SHOW COLUMNS FROM `{$t_lic}`", ARRAY_A), 'Field');
        if (!in_array('max_workspaces', $lic_cols))
            $wpdb->query("ALTER TABLE `{$t_lic}` ADD COLUMN `max_workspaces` SMALLINT UNSIGNED NOT NULL DEFAULT 1");
        if (!in_array('max_sites', $lic_cols))
            $wpdb->query("ALTER TABLE `{$t_lic}` ADD COLUMN `max_sites` SMALLINT UNSIGNED NOT NULL DEFAULT 1");
        if (!in_array('notes', $lic_cols))
            $wpdb->query("ALTER TABLE `{$t_lic}` ADD COLUMN `notes` TEXT NULL");
        if (!in_array('updated_at', $lic_cols))
            $wpdb->query("ALTER TABLE `{$t_lic}` ADD COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        // Ensure expires_at allows NULL
        $wpdb->query("ALTER TABLE `{$t_lic}` MODIFY COLUMN `expires_at` DATE NULL DEFAULT NULL");

        // Log table: add verified_at if missing
        $log_cols = array_column($wpdb->get_results("SHOW COLUMNS FROM `{$t_log}`", ARRAY_A), 'Field');
        if (!in_array('verified_at', $log_cols)) {
            if (in_array('created_at', $log_cols)) {
                $wpdb->query("ALTER TABLE `{$t_log}` CHANGE `created_at` `verified_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
            } else {
                $wpdb->query("ALTER TABLE `{$t_log}` ADD COLUMN `verified_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
            }
        }
    }

    public function add_menu(): void {
        add_menu_page(
            'Luna Licenses',
            'Luna Licenses',
            'manage_options',
            'luna-licenses',
            [$this, 'page_list'],
            'dashicons-admin-network',
            58
        );
        add_submenu_page('luna-licenses', 'Todas las Licencias', 'Todas las Licencias', 'manage_options', 'luna-licenses',          [$this, 'page_list']);
        add_submenu_page('luna-licenses', 'Nueva Licencia',      'Nueva Licencia',      'manage_options', 'luna-licenses-new',       [$this, 'page_new']);
        add_submenu_page('luna-licenses', 'Log de Verificaciones','Log',                'manage_options', 'luna-licenses-log',       [$this, 'page_log']);
        add_submenu_page('luna-licenses', 'Configuración',        'Configuración',       'manage_options', 'luna-licenses-settings',  [$this, 'page_settings']);
    }

    public function enqueue(string $hook): void {
        if (!str_contains($hook, 'luna-licenses')) return;
        wp_enqueue_style('lls-admin', LLS_PLUGIN_URL . 'admin/admin.css', [], LLS_VERSION);
    }

    // ── LIST ──────────────────────────────────────────────────────────────────
    public function page_list(): void {
        $search = sanitize_text_field($_GET['s']     ?? '');
        $status = sanitize_text_field($_GET['status'] ?? '');
        $page   = max(1, (int)($_GET['paged'] ?? 1));
        $data   = LLS_License::get_all(25, $page, $search, $status);
        require LLS_PLUGIN_DIR . 'admin/views/list.php';
    }

    // ── NEW ───────────────────────────────────────────────────────────────────
    public function page_new(): void {
        $editing = null;
        if (!empty($_GET['edit'])) {
            $editing = LLS_License::get_by_key(sanitize_text_field($_GET['edit']));
        }
        require LLS_PLUGIN_DIR . 'admin/views/form.php';
    }

    // ── LOG ───────────────────────────────────────────────────────────────────
    public function page_log(): void {
        $key  = sanitize_text_field($_GET['key'] ?? '');
        $logs = LLS_License::get_log($key, 100);
        require LLS_PLUGIN_DIR . 'admin/views/log.php';
    }

    // ── SETTINGS ─────────────────────────────────────────────────────────────
    public function page_settings(): void {
        if (isset($_POST['lls_settings_nonce']) && wp_verify_nonce($_POST['lls_settings_nonce'], 'lls_settings')) {
            update_option('lls_hmac_secret', sanitize_text_field($_POST['hmac_secret'] ?? ''));
            echo '<div class="notice notice-success"><p>Configuración guardada.</p></div>';
        }
        $hmac = get_option('lls_hmac_secret', LLS_HMAC_SECRET);
        require LLS_PLUGIN_DIR . 'admin/views/settings.php';
    }

    // ── HANDLERS ─────────────────────────────────────────────────────────────
    public function handle_create(): void {
        check_admin_referer('lls_create');
        if (!current_user_can('manage_options')) wp_die('Sin permisos');

        $id = LLS_License::create([
            'customer_name'  => $_POST['customer_name']  ?? '',
            'customer_email' => $_POST['customer_email'] ?? '',
            'domain'         => $_POST['domain']         ?? '',
            'plan'           => $_POST['plan']           ?? 'starter',
            'expires_at'     => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
            'notes'          => $_POST['notes']          ?? '',
        ]);

        $redirect = admin_url('admin.php?page=luna-licenses');
        if ($id) {
            wp_redirect($redirect . '&msg=created');
        } else {
            global $wpdb;
            $err = urlencode($wpdb->last_error ?: 'Error desconocido al insertar en la base de datos.');
            wp_redirect($redirect . '&msg=error&dberr=' . $err);
        }
        exit;
    }

    public function handle_update(): void {
        check_admin_referer('lls_update');
        if (!current_user_can('manage_options')) wp_die('Sin permisos');

        $id = (int)($_POST['id'] ?? 0);
        LLS_License::update($id, [
            'customer_name'  => $_POST['customer_name']  ?? '',
            'customer_email' => $_POST['customer_email'] ?? '',
            'domain'         => $_POST['domain']         ?? '',
            'plan'           => $_POST['plan']           ?? 'starter',
            'status'         => $_POST['status']         ?? 'active',
            'expires_at'     => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
            'notes'          => $_POST['notes']          ?? '',
        ]);

        wp_redirect(admin_url('admin.php?page=luna-licenses&msg=updated'));
        exit;
    }

    public function handle_delete(): void {
        check_admin_referer('lls_delete');
        if (!current_user_can('manage_options')) wp_die('Sin permisos');

        $id = (int)($_POST['id'] ?? 0);
        LLS_License::delete($id);
        wp_redirect(admin_url('admin.php?page=luna-licenses&msg=deleted'));
        exit;
    }

    public function handle_toggle(): void {
        check_admin_referer('lls_toggle');
        if (!current_user_can('manage_options')) wp_die('Sin permisos');

        $id     = (int)($_POST['id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'active');
        LLS_License::update($id, ['status' => $status === 'active' ? 'inactive' : 'active']);
        wp_redirect(admin_url('admin.php?page=luna-licenses&msg=updated'));
        exit;
    }
}
