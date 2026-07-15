<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RT_Database {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // Tabla de habitaciones/cabañas
        $sql_rooms = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rt_rooms (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id       BIGINT UNSIGNED NOT NULL,
            type          VARCHAR(50)  NOT NULL DEFAULT 'room',
            capacity      TINYINT UNSIGNED NOT NULL DEFAULT 2,
            min_nights    TINYINT UNSIGNED NOT NULL DEFAULT 1,
            max_nights    SMALLINT UNSIGNED NOT NULL DEFAULT 30,
            base_price    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            currency      VARCHAR(3) NOT NULL DEFAULT 'ARS',
            status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset;";

        // Tabla de tarifas / temporadas
        $sql_rates = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rt_rates (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            room_id     BIGINT UNSIGNED NOT NULL,
            name        VARCHAR(100) NOT NULL,
            date_from   DATE NOT NULL,
            date_to     DATE NOT NULL,
            price       DECIMAL(10,2) NOT NULL,
            type        ENUM('season','weekend','special') NOT NULL DEFAULT 'season',
            PRIMARY KEY (id),
            KEY room_id (room_id),
            KEY date_from (date_from),
            KEY date_to   (date_to)
        ) $charset;";

        // Tabla principal de reservas
        $sql_bookings = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rt_bookings (
            id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            room_id          BIGINT UNSIGNED NOT NULL,
            booking_code     VARCHAR(20) NOT NULL,
            guest_name       VARCHAR(150) NOT NULL,
            guest_email      VARCHAR(150) NOT NULL,
            guest_phone      VARCHAR(30) NOT NULL DEFAULT '',
            guest_dni        VARCHAR(20) NOT NULL DEFAULT '',
            checkin          DATE NOT NULL,
            checkout         DATE NOT NULL,
            nights           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            guests_count     TINYINT UNSIGNED NOT NULL DEFAULT 1,
            total_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            paid_amount      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            currency         VARCHAR(3) NOT NULL DEFAULT 'ARS',
            payment_status   ENUM('pending','partial','paid','refunded','failed') NOT NULL DEFAULT 'pending',
            booking_status   ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
            mp_preference_id VARCHAR(100) DEFAULT NULL,
            mp_payment_id    VARCHAR(100) DEFAULT NULL,
            mp_status        VARCHAR(50) DEFAULT NULL,
            notes            TEXT DEFAULT NULL,
            created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY booking_code (booking_code),
            KEY room_id (room_id),
            KEY checkin  (checkin),
            KEY checkout (checkout),
            KEY payment_status (payment_status),
            KEY booking_status (booking_status)
        ) $charset;";

        // Tabla de bloqueos manuales (fechas no disponibles)
        $sql_blocks = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rt_blocks (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            room_id    BIGINT UNSIGNED NOT NULL,
            date_from  DATE NOT NULL,
            date_to    DATE NOT NULL,
            reason     VARCHAR(200) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY room_id (room_id)
        ) $charset;";

        // Tabla de log de pagos MercadoPago
        $sql_payments = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rt_payments (
            id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id   BIGINT UNSIGNED NOT NULL,
            mp_payment_id VARCHAR(100) NOT NULL,
            mp_status    VARCHAR(50) NOT NULL,
            amount       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            currency     VARCHAR(3) NOT NULL DEFAULT 'ARS',
            raw_data     LONGTEXT DEFAULT NULL,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY mp_payment_id (mp_payment_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_rooms );
        dbDelta( $sql_rates );
        dbDelta( $sql_bookings );
        dbDelta( $sql_blocks );
        dbDelta( $sql_payments );

        update_option( 'rt_db_version', RT_DB_VERSION );
    }

    // Genera un código único de reserva tipo RT-20250001
    public static function generate_booking_code() {
        global $wpdb;
        $year  = date( 'Y' );
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rt_bookings WHERE YEAR(created_at) = {$year}" );
        $seq   = str_pad( $count + 1, 4, '0', STR_PAD_LEFT );
        return 'RT-' . $year . $seq;
    }
}
