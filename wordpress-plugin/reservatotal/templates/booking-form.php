<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="rt-booking-wrap" id="rt-booking-app">

    <!-- Mensaje de retorno de pago -->
    <?php
    $paid_id = absint( $_GET['rt_paid'] ?? 0 );
    $msg     = $paid_id ? get_transient( 'rt_payment_msg_' . $paid_id ) : null;
    if ( $msg ) :
        delete_transient( 'rt_payment_msg_' . $paid_id );
        $icon_map = [ 'success' => '✓', 'failure' => '✗', 'pending' => '⏳' ];
        $cls_map  = [ 'success' => 'rt-alert-success', 'failure' => 'rt-alert-error', 'pending' => 'rt-alert-warning' ];
    ?>
    <div class="rt-alert <?php echo esc_attr( $cls_map[ $msg['status'] ] ?? '' ); ?>">
        <strong><?php echo esc_html( $icon_map[ $msg['status'] ] ?? '' ); ?> Reserva <?php echo esc_html( $msg['code'] ); ?></strong><br>
        <?php echo esc_html( $msg['message'] ); ?>
    </div>
    <?php endif; ?>

    <div class="rt-form-container">
        <h2 class="rt-form-title"><?php echo esc_html( $atts['title'] ); ?></h2>

        <!-- Paso 1: Selección -->
        <div class="rt-step" id="rt-step-1">
            <div class="rt-step-header">
                <span class="rt-step-number">1</span>
                <span>Elegí tu alojamiento y fechas</span>
            </div>
            <div class="rt-form-body">
                <div class="rt-field-row">
                    <div class="rt-field">
                        <label for="rt-room">Habitación / Cabaña</label>
                        <select id="rt-room" name="room_id">
                            <option value="">— Seleccionar —</option>
                            <?php foreach ( $rooms as $room ) : ?>
                            <option value="<?php echo esc_attr( $room->id ); ?>"
                                <?php selected( $selected_room, $room->id ); ?>
                                data-base-price="<?php echo esc_attr( $room->base_price ); ?>"
                                data-currency="<?php echo esc_attr( $room->currency ); ?>"
                                data-capacity="<?php echo esc_attr( $room->capacity ); ?>"
                                data-min="<?php echo esc_attr( $room->min_nights ); ?>"
                                data-max="<?php echo esc_attr( $room->max_nights ); ?>">
                                <?php echo esc_html( $room->name ); ?> (<?php echo esc_html( $room->capacity ); ?> personas)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rt-field">
                        <label for="rt-guests">Huéspedes</label>
                        <select id="rt-guests" name="guests_count">
                            <?php for ( $i = 1; $i <= 10; $i++ ) : ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> persona<?php echo $i > 1 ? 's' : ''; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="rt-field-row">
                    <div class="rt-field">
                        <label for="rt-checkin">Check-in</label>
                        <input type="date" id="rt-checkin" name="checkin"
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    <div class="rt-field">
                        <label for="rt-checkout">Check-out</label>
                        <input type="date" id="rt-checkout" name="checkout">
                    </div>
                </div>
                <div id="rt-price-preview" class="rt-price-preview" style="display:none;">
                    <div class="rt-price-nights">
                        <span id="rt-nights-label"></span>
                    </div>
                    <div class="rt-price-total">
                        Total: <strong id="rt-total-label"></strong>
                    </div>
                </div>
                <div id="rt-availability-msg" class="rt-availability-msg" style="display:none;"></div>
                <button type="button" id="rt-btn-check" class="rt-btn rt-btn-secondary">
                    Verificar disponibilidad
                </button>
                <button type="button" id="rt-btn-next1" class="rt-btn rt-btn-primary" style="display:none;">
                    Continuar → Datos personales
                </button>
            </div>
        </div>

        <!-- Paso 2: Datos del huésped -->
        <div class="rt-step" id="rt-step-2" style="display:none;">
            <div class="rt-step-header">
                <span class="rt-step-number">2</span>
                <span>Tus datos</span>
            </div>
            <div class="rt-form-body">
                <div class="rt-field-row">
                    <div class="rt-field">
                        <label for="rt-name">Nombre completo *</label>
                        <input type="text" id="rt-name" name="guest_name" required placeholder="Ej: María García">
                    </div>
                    <div class="rt-field">
                        <label for="rt-email">Email *</label>
                        <input type="email" id="rt-email" name="guest_email" required placeholder="tu@email.com">
                    </div>
                </div>
                <div class="rt-field-row">
                    <div class="rt-field">
                        <label for="rt-phone">Teléfono</label>
                        <input type="tel" id="rt-phone" name="guest_phone" placeholder="+54 9 XXXX XXXXXX">
                    </div>
                    <div class="rt-field">
                        <label for="rt-dni">DNI / Pasaporte</label>
                        <input type="text" id="rt-dni" name="guest_dni" placeholder="12345678">
                    </div>
                </div>
                <div class="rt-field">
                    <label for="rt-notes">Comentarios o pedidos especiales</label>
                    <textarea id="rt-notes" name="notes" rows="3" placeholder="Ej: llegamos tarde, necesitamos cuna, etc."></textarea>
                </div>

                <!-- Resumen de la reserva -->
                <div class="rt-booking-summary">
                    <strong>Resumen:</strong>
                    <div id="rt-summary-text"></div>
                </div>

                <div class="rt-form-actions">
                    <button type="button" id="rt-btn-back1" class="rt-btn rt-btn-secondary">← Volver</button>
                    <button type="button" id="rt-btn-pay" class="rt-btn rt-btn-primary rt-btn-pay">
                        💳 Pagar con MercadoPago
                    </button>
                </div>
                <p class="rt-terms">
                    Al reservar aceptás los <a href="#" target="_blank">términos y condiciones</a>.
                    El pago se procesa de forma segura a través de MercadoPago.
                </p>
            </div>
        </div>

        <!-- Paso 3: Procesando -->
        <div class="rt-step" id="rt-step-loading" style="display:none;">
            <div class="rt-loading-indicator">
                <div class="rt-spinner"></div>
                <p>Generando tu reserva y redirigiendo a MercadoPago…</p>
            </div>
        </div>

    </div><!-- .rt-form-container -->
    <p class="rt-powered-by">
        Reservas gestionadas por <a href="https://reservatotal.com.ar" target="_blank">ReservaTotal</a>
        v<?php echo defined('RT_VERSION') ? RT_VERSION : '2.3'; ?> &mdash; Durval Muñoz Codazzi
    </p>

</div><!-- .rt-booking-wrap -->
