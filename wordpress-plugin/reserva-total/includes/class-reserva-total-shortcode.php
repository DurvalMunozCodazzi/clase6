<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Reserva_Total_Shortcode {

	public function __construct() {
		add_shortcode( 'reserva_total', array( $this, 'render' ) );
	}

	public function render() {
		wp_enqueue_style( 'reserva-total', RESERVA_TOTAL_URL . 'assets/css/reserva-total.css', array(), RESERVA_TOTAL_VERSION );
		wp_enqueue_script( 'reserva-total', RESERVA_TOTAL_URL . 'assets/js/reserva-total.js', array(), RESERVA_TOTAL_VERSION, true );
		wp_localize_script(
			'reserva-total',
			'ReservaTotalConfig',
			array(
				'apiUrl' => esc_url_raw( rest_url( 'reserva-total/v1/' ) ),
			)
		);

		ob_start();
		?>
		<div class="reserva-total">
			<nav class="rt-nav">
				<button type="button" class="rt-nav-link active" data-view="rooms">Habitaciones</button>
				<button type="button" class="rt-nav-link" data-view="my-reservations">Mis reservas</button>
			</nav>

			<div id="rt-toast" class="rt-toast rt-hidden"></div>

			<section id="rt-view-rooms" class="rt-view">
				<section class="rt-search-card">
					<h3>Buscar disponibilidad</h3>
					<form id="rt-search-form" class="rt-search-form">
						<div class="rt-field">
							<label for="rt-checkIn">Entrada</label>
							<input type="date" id="rt-checkIn" required />
						</div>
						<div class="rt-field">
							<label for="rt-checkOut">Salida</label>
							<input type="date" id="rt-checkOut" required />
						</div>
						<div class="rt-field">
							<label for="rt-guests">Huéspedes</label>
							<input type="number" id="rt-guests" min="1" value="1" required />
						</div>
						<button type="submit" class="rt-btn rt-btn-primary">Buscar</button>
					</form>
				</section>

				<section id="rt-rooms-grid" class="rt-rooms-grid">
					<p class="rt-empty">Cargando habitaciones...</p>
				</section>
			</section>

			<section id="rt-view-my-reservations" class="rt-view rt-hidden">
				<section class="rt-search-card">
					<h3>Mis reservas</h3>
					<form id="rt-my-reservations-form" class="rt-search-form">
						<div class="rt-field">
							<label for="rt-lookupEmail">Tu email</label>
							<input type="email" id="rt-lookupEmail" placeholder="nombre@correo.com" required />
						</div>
						<button type="submit" class="rt-btn rt-btn-primary">Buscar reservas</button>
					</form>
				</section>
				<section id="rt-reservations-list" class="rt-reservations-list"></section>
			</section>

			<div id="rt-modal" class="rt-modal rt-hidden">
				<div class="rt-modal-content">
					<button type="button" class="rt-modal-close" id="rt-modal-close">&times;</button>
					<h3>Confirmar reserva</h3>
					<p id="rt-modal-summary" class="rt-modal-summary"></p>
					<form id="rt-reservation-form">
						<input type="hidden" id="rt-modalRoomId" />
						<div class="rt-field">
							<label for="rt-guestName">Nombre completo</label>
							<input type="text" id="rt-guestName" required />
						</div>
						<div class="rt-field">
							<label for="rt-guestEmail">Email</label>
							<input type="email" id="rt-guestEmail" required />
						</div>
						<button type="submit" class="rt-btn rt-btn-primary rt-btn-block">Confirmar reserva</button>
					</form>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
