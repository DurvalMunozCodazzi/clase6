<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Reserva_Total_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_reserva_total_save_room', array( $this, 'handle_save_room' ) );
		add_action( 'admin_post_reserva_total_delete_room', array( $this, 'handle_delete_room' ) );
		add_action( 'admin_post_reserva_total_cancel_reservation', array( $this, 'handle_cancel_reservation' ) );
	}

	public function register_menu() {
		add_menu_page(
			'Reserva Total',
			'Reserva Total',
			'manage_options',
			'reserva-total',
			array( $this, 'render_reservations_page' ),
			'dashicons-calendar-alt'
		);
		add_submenu_page( 'reserva-total', 'Reservas', 'Reservas', 'manage_options', 'reserva-total', array( $this, 'render_reservations_page' ) );
		add_submenu_page( 'reserva-total', 'Habitaciones', 'Habitaciones', 'manage_options', 'reserva-total-rooms', array( $this, 'render_rooms_page' ) );
	}

	private function render_notice() {
		if ( empty( $_GET['reserva_total_notice'] ) ) {
			return;
		}

		$type     = sanitize_text_field( wp_unslash( $_GET['reserva_total_notice'] ) );
		$messages = array(
			'room_saved'             => array( 'success', 'Habitación guardada.' ),
			'room_deleted'           => array( 'success', 'Habitación eliminada.' ),
			'room_has_reservations'  => array( 'error', 'No se puede eliminar: la habitación tiene reservas asociadas.' ),
			'reservation_cancelled'  => array( 'success', 'Reserva cancelada.' ),
		);

		if ( ! isset( $messages[ $type ] ) ) {
			return;
		}

		list( $class, $text ) = $messages[ $type ];
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $class ), esc_html( $text ) );
	}

	public function render_reservations_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$reservations = Reserva_Total_DB::get_reservations();
		?>
		<div class="wrap">
			<h1>Reservas</h1>
			<?php $this->render_notice(); ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Habitación</th>
						<th>Huésped</th>
						<th>Email</th>
						<th>Entrada</th>
						<th>Salida</th>
						<th>Huéspedes</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $reservations ) ) : ?>
					<tr><td colspan="7">No hay reservas registradas.</td></tr>
				<?php else : ?>
					<?php foreach ( $reservations as $r ) : ?>
						<tr>
							<td><?php echo esc_html( $r->room_name ); ?></td>
							<td><?php echo esc_html( $r->guest_name ); ?></td>
							<td><?php echo esc_html( $r->guest_email ); ?></td>
							<td><?php echo esc_html( $r->check_in ); ?></td>
							<td><?php echo esc_html( $r->check_out ); ?></td>
							<td><?php echo esc_html( $r->guests ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('¿Cancelar esta reserva?');">
									<input type="hidden" name="action" value="reserva_total_cancel_reservation" />
									<input type="hidden" name="id" value="<?php echo (int) $r->id; ?>" />
									<?php wp_nonce_field( 'reserva_total_cancel_reservation_' . $r->id ); ?>
									<button type="submit" class="button button-small">Cancelar</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_rooms_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$rooms     = Reserva_Total_DB::get_rooms();
		$edit_room = null;

		if ( isset( $_GET['edit'] ) ) {
			$edit_room = Reserva_Total_DB::get_room( (int) $_GET['edit'] );
		}
		?>
		<div class="wrap">
			<h1>Habitaciones</h1>
			<?php $this->render_notice(); ?>

			<h2><?php echo $edit_room ? 'Editar habitación' : 'Nueva habitación'; ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="reserva_total_save_room" />
				<?php if ( $edit_room ) : ?>
					<input type="hidden" name="id" value="<?php echo (int) $edit_room->id; ?>" />
				<?php endif; ?>
				<?php wp_nonce_field( 'reserva_total_save_room' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="rt-name">Nombre</label></th>
						<td><input type="text" id="rt-name" name="name" class="regular-text" required value="<?php echo esc_attr( $edit_room->name ?? '' ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="rt-type">Tipo</label></th>
						<td><input type="text" id="rt-type" name="type" class="regular-text" required value="<?php echo esc_attr( $edit_room->type ?? '' ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="rt-capacity">Capacidad</label></th>
						<td><input type="number" id="rt-capacity" name="capacity" min="1" required value="<?php echo esc_attr( $edit_room->capacity ?? 1 ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="rt-price">Precio por noche</label></th>
						<td><input type="number" id="rt-price" name="price_per_night" min="1" step="0.01" required value="<?php echo esc_attr( $edit_room->price_per_night ?? '' ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="rt-description">Descripción</label></th>
						<td><textarea id="rt-description" name="description" class="large-text" rows="3"><?php echo esc_textarea( $edit_room->description ?? '' ); ?></textarea></td>
					</tr>
				</table>
				<?php submit_button( $edit_room ? 'Guardar cambios' : 'Crear habitación' ); ?>
			</form>

			<h2>Habitaciones existentes</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Nombre</th>
						<th>Tipo</th>
						<th>Capacidad</th>
						<th>Precio/noche</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $rooms ) ) : ?>
					<tr><td colspan="5">No hay habitaciones cargadas.</td></tr>
				<?php else : ?>
					<?php foreach ( $rooms as $room ) : ?>
						<tr>
							<td><?php echo esc_html( $room->name ); ?></td>
							<td><?php echo esc_html( $room->type ); ?></td>
							<td><?php echo esc_html( $room->capacity ); ?></td>
							<td><?php echo esc_html( number_format( (float) $room->price_per_night, 2 ) ); ?></td>
							<td>
								<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'edit', $room->id ) ); ?>">Editar</a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('¿Eliminar esta habitación?');">
									<input type="hidden" name="action" value="reserva_total_delete_room" />
									<input type="hidden" name="id" value="<?php echo (int) $room->id; ?>" />
									<?php wp_nonce_field( 'reserva_total_delete_room_' . $room->id ); ?>
									<button type="submit" class="button button-small">Eliminar</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function handle_save_room() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No autorizado' );
		}
		check_admin_referer( 'reserva_total_save_room' );

		$data = array(
			'name'            => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'type'            => sanitize_text_field( wp_unslash( $_POST['type'] ?? '' ) ),
			'capacity'        => (int) ( $_POST['capacity'] ?? 0 ),
			'price_per_night' => (float) ( $_POST['price_per_night'] ?? 0 ),
			'description'     => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
		);

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

		if ( $id ) {
			Reserva_Total_DB::update_room( $id, $data );
		} else {
			Reserva_Total_DB::insert_room( $data );
		}

		wp_safe_redirect( add_query_arg( 'reserva_total_notice', 'room_saved', admin_url( 'admin.php?page=reserva-total-rooms' ) ) );
		exit;
	}

	public function handle_delete_room() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No autorizado' );
		}

		$id = (int) ( $_POST['id'] ?? 0 );
		check_admin_referer( 'reserva_total_delete_room_' . $id );

		$notice = 'room_deleted';
		if ( Reserva_Total_DB::room_reservation_count( $id ) > 0 ) {
			$notice = 'room_has_reservations';
		} else {
			Reserva_Total_DB::delete_room( $id );
		}

		wp_safe_redirect( add_query_arg( 'reserva_total_notice', $notice, admin_url( 'admin.php?page=reserva-total-rooms' ) ) );
		exit;
	}

	public function handle_cancel_reservation() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No autorizado' );
		}

		$id = (int) ( $_POST['id'] ?? 0 );
		check_admin_referer( 'reserva_total_cancel_reservation_' . $id );

		Reserva_Total_DB::delete_reservation( $id );

		wp_safe_redirect( add_query_arg( 'reserva_total_notice', 'reservation_cancelled', admin_url( 'admin.php?page=reserva-total' ) ) );
		exit;
	}
}
