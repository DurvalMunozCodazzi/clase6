<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Reserva_Total_REST {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'reserva-total/v1',
			'/rooms',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_rooms' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'reserva-total/v1',
			'/reservations',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_reservations' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_reservation' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			'reserva-total/v1',
			'/reservations/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_reservation' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'validate_callback' => function ( $value ) {
							return is_numeric( $value );
						},
					),
				),
			)
		);
	}

	private function is_valid_date( $value ) {
		if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return false;
		}
		$date = DateTime::createFromFormat( 'Y-m-d', $value );
		return $date && $date->format( 'Y-m-d' ) === $value;
	}

	public function get_rooms( WP_REST_Request $request ) {
		$check_in  = sanitize_text_field( (string) $request->get_param( 'checkIn' ) );
		$check_out = sanitize_text_field( (string) $request->get_param( 'checkOut' ) );
		$guests    = $request->get_param( 'guests' );

		$rooms  = Reserva_Total_DB::get_rooms();
		$result = array();

		foreach ( $rooms as $room ) {
			$available = true;

			if ( $check_in && $check_out ) {
				if ( ! $this->is_valid_date( $check_in ) || ! $this->is_valid_date( $check_out ) || $check_in >= $check_out ) {
					$available = false;
				} else {
					$available = Reserva_Total_DB::is_room_available( (int) $room->id, $check_in, $check_out );
				}
			}

			if ( $guests && (int) $room->capacity < (int) $guests ) {
				$available = false;
			}

			$result[] = array(
				'id'               => (int) $room->id,
				'name'             => $room->name,
				'type'             => $room->type,
				'capacity'         => (int) $room->capacity,
				'price_per_night'  => (float) $room->price_per_night,
				'description'      => $room->description,
				'available'        => $available,
			);
		}

		return rest_ensure_response( $result );
	}

	private function format_reservations( $reservations ) {
		return array_map(
			function ( $r ) {
				return array(
					'id'              => (int) $r->id,
					'room_id'         => (int) $r->room_id,
					'room_name'       => $r->room_name,
					'guest_name'      => $r->guest_name,
					'guest_email'     => $r->guest_email,
					'check_in'        => $r->check_in,
					'check_out'       => $r->check_out,
					'guests'          => (int) $r->guests,
					'price_per_night' => (float) $r->price_per_night,
				);
			},
			$reservations
		);
	}

	public function get_reservations( WP_REST_Request $request ) {
		$email    = sanitize_email( (string) $request->get_param( 'email' ) );
		$is_admin = current_user_can( 'manage_options' );

		if ( ! $email && ! $is_admin ) {
			return new WP_Error( 'reserva_total_email_required', 'Indicá un email para consultar tus reservas.', array( 'status' => 400 ) );
		}

		$reservations = Reserva_Total_DB::get_reservations( $email );
		return rest_ensure_response( $this->format_reservations( $reservations ) );
	}

	public function create_reservation( WP_REST_Request $request ) {
		$room_id     = (int) $request->get_param( 'roomId' );
		$guest_name  = sanitize_text_field( (string) $request->get_param( 'guestName' ) );
		$guest_email = sanitize_email( (string) $request->get_param( 'guestEmail' ) );
		$check_in    = sanitize_text_field( (string) $request->get_param( 'checkIn' ) );
		$check_out   = sanitize_text_field( (string) $request->get_param( 'checkOut' ) );
		$guests      = (int) $request->get_param( 'guests' );

		if ( ! $room_id || ! $guest_name || ! $guest_email || ! $check_in || ! $check_out || ! $guests ) {
			return new WP_Error( 'reserva_total_missing_fields', 'Faltan datos obligatorios.', array( 'status' => 400 ) );
		}
		if ( ! is_email( $guest_email ) ) {
			return new WP_Error( 'reserva_total_invalid_email', 'Email inválido.', array( 'status' => 400 ) );
		}
		if ( ! $this->is_valid_date( $check_in ) || ! $this->is_valid_date( $check_out ) ) {
			return new WP_Error( 'reserva_total_invalid_date', 'Fechas inválidas.', array( 'status' => 400 ) );
		}
		if ( $check_in >= $check_out ) {
			return new WP_Error( 'reserva_total_invalid_range', 'La fecha de salida debe ser posterior a la de entrada.', array( 'status' => 400 ) );
		}

		$room = Reserva_Total_DB::get_room( $room_id );
		if ( ! $room ) {
			return new WP_Error( 'reserva_total_room_not_found', 'Habitación no encontrada.', array( 'status' => 404 ) );
		}
		if ( $guests > (int) $room->capacity ) {
			return new WP_Error(
				'reserva_total_capacity',
				sprintf( 'La habitación admite hasta %d huéspedes.', (int) $room->capacity ),
				array( 'status' => 400 )
			);
		}
		if ( ! Reserva_Total_DB::is_room_available( $room_id, $check_in, $check_out ) ) {
			return new WP_Error( 'reserva_total_unavailable', 'La habitación no está disponible en esas fechas.', array( 'status' => 409 ) );
		}

		$id = Reserva_Total_DB::insert_reservation(
			array(
				'room_id'     => $room_id,
				'guest_name'  => $guest_name,
				'guest_email' => $guest_email,
				'check_in'    => $check_in,
				'check_out'   => $check_out,
				'guests'      => $guests,
			)
		);

		$reservation  = Reserva_Total_DB::get_reservation( $id );
		$formatted    = $this->format_reservations( array( $reservation ) );

		$response = rest_ensure_response( $formatted[0] );
		$response->set_status( 201 );
		return $response;
	}

	public function delete_reservation( WP_REST_Request $request ) {
		global $wpdb;

		$id       = (int) $request->get_param( 'id' );
		$email    = sanitize_email( (string) $request->get_param( 'email' ) );
		$is_admin = current_user_can( 'manage_options' );

		$table       = Reserva_Total_DB::reservations_table();
		$reservation = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

		if ( ! $reservation ) {
			return new WP_Error( 'reserva_total_not_found', 'Reserva no encontrada.', array( 'status' => 404 ) );
		}
		if ( ! $is_admin && ( ! $email || strtolower( $reservation->guest_email ) !== strtolower( $email ) ) ) {
			return new WP_Error( 'reserva_total_forbidden', 'No podés cancelar esta reserva.', array( 'status' => 403 ) );
		}

		Reserva_Total_DB::delete_reservation( $id );
		return new WP_REST_Response( null, 204 );
	}
}
