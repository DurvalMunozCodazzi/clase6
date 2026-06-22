<?php
defined('ABSPATH') || exit;

class LLS_Activator {

    public static function activate(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $t_lic   = $wpdb->prefix . 'lls_licenses';
        $t_log   = $wpdb->prefix . 'lls_verify_log';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE {$t_lic} (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key   VARCHAR(64)  NOT NULL,
            customer_name VARCHAR(120) NOT NULL DEFAULT '',
            customer_email VARCHAR(120) NOT NULL DEFAULT '',
            domain        VARCHAR(255) NOT NULL DEFAULT '',
            plan          VARCHAR(32)  NOT NULL DEFAULT 'starter',
            status        ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
            max_workspaces SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            max_sites      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            expires_at    DATE         NULL DEFAULT NULL,
            notes         TEXT         NULL,
            created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY   uq_key (license_key),
            KEY          idx_domain (domain),
            KEY          idx_status (status)
        ) {$charset};");

        dbDelta("CREATE TABLE {$t_log} (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key VARCHAR(64)  NOT NULL,
            domain      VARCHAR(255) NOT NULL DEFAULT '',
            result      ENUM('valid','invalid','expired','suspended','not_found') NOT NULL,
            ip          VARCHAR(45)  NOT NULL DEFAULT '',
            verified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_key (license_key),
            KEY idx_date (verified_at)
        ) {$charset};");

        // Migration: add missing columns to licenses table if created from old schema
        $lic_cols = array_column($wpdb->get_results("SHOW COLUMNS FROM `{$t_lic}`", ARRAY_A), 'Field');
        if (!in_array('max_workspaces', $lic_cols)) {
            $wpdb->query("ALTER TABLE `{$t_lic}` ADD COLUMN `max_workspaces` SMALLINT UNSIGNED NOT NULL DEFAULT 1");
        }
        if (!in_array('max_sites', $lic_cols)) {
            $wpdb->query("ALTER TABLE `{$t_lic}` ADD COLUMN `max_sites` SMALLINT UNSIGNED NOT NULL DEFAULT 1");
        }
        if (!in_array('notes', $lic_cols)) {
            $wpdb->query("ALTER TABLE `{$t_lic}` ADD COLUMN `notes` TEXT NULL");
        }
        if (!in_array('updated_at', $lic_cols)) {
            $wpdb->query("ALTER TABLE `{$t_lic}` ADD COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }

        // Migration: add verified_at if table existed with old schema (created_at)
        $cols = $wpdb->get_results("SHOW COLUMNS FROM `{$t_log}`", ARRAY_A);
        $col_names = array_column($cols, 'Field');
        if (!in_array('verified_at', $col_names)) {
            if (in_array('created_at', $col_names)) {
                $wpdb->query("ALTER TABLE `{$t_log}` CHANGE `created_at` `verified_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
            } else {
                $wpdb->query("ALTER TABLE `{$t_log}` ADD COLUMN `verified_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
                $wpdb->query("ALTER TABLE `{$t_log}` ADD KEY `idx_date` (`verified_at`)");
            }
        }

        update_option('lls_version', LLS_VERSION);
    }

    public static function deactivate(): void {}
}
