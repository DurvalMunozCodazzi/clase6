<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Reserva_Total_DB {

	public static function rooms_table() {
		global $wpdb;
		return $wpdb->prefix . 'reserva_total_rooms';
	}

	public static function reservations_table() {
		global $wpdb;
		return $wpdb->prefix . 'reserva_total_reservations';
	}

	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate     = $wpdb->get_charset_collate();
		$rooms_table          = self::rooms_table();
		$reservations_table   = self::reservations_table();

		$sql_rooms = "CREATE TABLE $rooms_table (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			type VARCHAR(100) NOT NULL,
			capacity INT UNSIGNED NOT NULL,
			price_per_night DECIMAL(10,2) NOT NULL,
			description TEXT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_rooms );

		$sql_reservations = "CREATE TABLE $reservations_table (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			room_id BIGINT UNSIGNED NOT NULL,
			guest_name VARCHAR(191) NOT NULL,
			guest_email VARCHAR(191) NOT NULL,
			check_in DATE NOT NULL,
			check_out DATE NOT NULL,
			guests INT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY room_id (room_id),
			KEY guest_email (guest_email)
		) $charset_collate;";
		dbDelta( $sql_reservations );

		self::maybe_seed_rooms();
	}

	public static function maybe_seed_rooms() {
		global $wpdb;
		$table = self::rooms_table();
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );

		if ( $count > 0 ) {
			return;
		}

		$rooms = array(
			array( 'Habitación Individual 101', 'Individual', 1, 35000, 'Habitación cómoda con escritorio, ideal para viajeros solos.' ),
			array( 'Habitación Doble 102', 'Doble', 2, 52000, 'Cama matrimonial, vista al jardín.' ),
			array( 'Habitación Doble 103', 'Doble', 2, 52000, 'Dos camas individuales, ideal para amigos o colegas.' ),
			array( 'Habitación Triple 201', 'Triple', 3, 68000, 'Espaciosa, con balcón y vista a la ciudad.' ),
			array( 'Suite Familiar 202', 'Suite', 4, 95000, 'Sala de estar separada, ideal para familias.' ),
			array( 'Suite Premium 301', 'Suite', 2, 120000, 'Jacuzzi privado y vista panorámica.' ),
		);

		foreach ( $rooms as $room ) {
			$wpdb->insert(
				$table,
				array(
					'name'             => $room[0],
					'type'             => $room[1],
					'capacity'         => $room[2],
					'price_per_night'  => $room[3],
					'description'      => $room[4],
				)
			);
		}
	}

	public static function get_rooms() {
		global $wpdb;
		$table = self::rooms_table();
		return $wpdb->get_results( "SELECT * FROM $table ORDER BY price_per_night ASC" );
	}

	public static function get_room( $id ) {
		global $wpdb;
		$table = self::rooms_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public static function insert_room( $data ) {
		global $wpdb;
		$wpdb->insert(
			self::rooms_table(),
			array(
				'name'            => $data['name'],
				'type'            => $data['type'],
				'capacity'        => (int) $data['capacity'],
				'price_per_night' => (float) $data['price_per_night'],
				'description'     => $data['description'],
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function update_room( $id, $data ) {
		global $wpdb;
		return $wpdb->update(
			self::rooms_table(),
			array(
				'name'            => $data['name'],
				'type'            => $data['type'],
				'capacity'        => (int) $data['capacity'],
				'price_per_night' => (float) $data['price_per_night'],
				'description'     => $data['description'],
			),
			array( 'id' => $id )
		);
	}

	public static function delete_room( $id ) {
		global $wpdb;
		return $wpdb->delete( self::rooms_table(), array( 'id' => $id ) );
	}

	public static function room_reservation_count( $room_id ) {
		global $wpdb;
		$table = self::reservations_table();
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE room_id = %d", $room_id ) );
	}

	public static function is_room_available( $room_id, $check_in, $check_out, $exclude_id = 0 ) {
		global $wpdb;
		$table  = self::reservations_table();
		$sql    = "SELECT COUNT(*) FROM $table WHERE room_id = %d AND check_in < %s AND check_out > %s";
		$params = array( $room_id, $check_out, $check_in );

		if ( $exclude_id ) {
			$sql     .= ' AND id != %d';
			$params[] = $exclude_id;
		}

		$count = (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );
		return 0 === $count;
	}

	public static function get_reservations( $email = '' ) {
		global $wpdb;
		$reservations_table = self::reservations_table();
		$rooms_table        = self::rooms_table();

		$sql = "SELECT r.*, rm.name AS room_name, rm.price_per_night
				FROM $reservations_table r
				JOIN $rooms_table rm ON rm.id = r.room_id";

		if ( $email ) {
			$sql .= $wpdb->prepare( ' WHERE r.guest_email = %s', $email );
		}

		$sql .= ' ORDER BY r.check_in ASC';

		return $wpdb->get_results( $sql );
	}

	public static function get_reservation( $id ) {
		global $wpdb;
		$reservations_table = self::reservations_table();
		$rooms_table        = self::rooms_table();

		$sql = "SELECT r.*, rm.name AS room_name, rm.price_per_night
				FROM $reservations_table r
				JOIN $rooms_table rm ON rm.id = r.room_id
				WHERE r.id = %d";

		return $wpdb->get_row( $wpdb->prepare( $sql, $id ) );
	}

	public static function insert_reservation( $data ) {
		global $wpdb;
		$wpdb->insert(
			self::reservations_table(),
			array(
				'room_id'     => (int) $data['room_id'],
				'guest_name'  => $data['guest_name'],
				'guest_email' => $data['guest_email'],
				'check_in'    => $data['check_in'],
				'check_out'   => $data['check_out'],
				'guests'      => (int) $data['guests'],
				'created_at'  => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function delete_reservation( $id ) {
		global $wpdb;
		return $wpdb->delete( self::reservations_table(), array( 'id' => $id ) );
	}
}
