<?php
/* ─── booking-detail.php ────────────────────────────────── */
// Espera $booking (objeto de RT_Booking::get()) desde class-rt-admin.php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! $booking ) : ?>
<div class="wrap rt-admin-wrap">
    <h1 class="rt-page-title">Reserva no encontrada</h1>
    <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=reservatotal-reservas' ) ); ?>" class="button">← Volver a reservas</a></p>
</div>
<?php return; endif; ?>

<div class="wrap rt-admin-wrap">
    <h1 class="rt-page-title">
        <span class="dashicons dashicons-calendar-alt"></span>
        Reserva <?php echo esc_html( $booking->booking_code ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=reservatotal-reservas' ) ); ?>" class="page-title-action">← Reservas</a>
    </h1>

    <div class="rt-card" style="max-width:720px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div>
                <?php echo rt_booking_status_badge( $booking->booking_status ); ?>
                <?php echo rt_payment_badge( $booking->payment_status ); ?>
            </div>
            <span style="color:#888;font-size:13px;">Creada el <?php echo esc_html( date( 'd/m/Y H:i', strtotime( $booking->created_at ) ) ); ?></span>
        </div>

        <table class="form-table" style="margin-top:0;">
            <tr>
                <th>Habitación / Cabaña</th>
                <td><?php echo esc_html( $booking->room_name ); ?></td>
            </tr>
            <tr>
                <th>Huésped</th>
                <td><?php echo esc_html( $booking->guest_name ); ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><a href="mailto:<?php echo esc_attr( $booking->guest_email ); ?>"><?php echo esc_html( $booking->guest_email ); ?></a></td>
            </tr>
            <?php if ( $booking->guest_phone ) : ?>
            <tr>
                <th>Teléfono</th>
                <td><?php echo esc_html( $booking->guest_phone ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( $booking->guest_dni ) : ?>
            <tr>
                <th>DNI / Pasaporte</th>
                <td><?php echo esc_html( $booking->guest_dni ); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Check-in</th>
                <td><?php echo esc_html( date( 'd/m/Y', strtotime( $booking->checkin ) ) ); ?></td>
            </tr>
            <tr>
                <th>Check-out</th>
                <td><?php echo esc_html( date( 'd/m/Y', strtotime( $booking->checkout ) ) ); ?></td>
            </tr>
            <tr>
                <th>Noches</th>
                <td><?php echo intval( $booking->nights ); ?></td>
            </tr>
            <tr>
                <th>Huéspedes</th>
                <td><?php echo intval( $booking->guests_count ); ?></td>
            </tr>
            <tr>
                <th>Total</th>
                <td>$ <?php echo number_format( $booking->total_price, 2, ',', '.' ); ?> <?php echo esc_html( $booking->currency ); ?></td>
            </tr>
            <tr>
                <th>Pagado</th>
                <td>$ <?php echo number_format( $booking->paid_amount, 2, ',', '.' ); ?> <?php echo esc_html( $booking->currency ); ?></td>
            </tr>
            <?php if ( $booking->mp_payment_id ) : ?>
            <tr>
                <th>Pago MercadoPago</th>
                <td>ID <?php echo esc_html( $booking->mp_payment_id ); ?> — estado <?php echo esc_html( $booking->mp_status ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( $booking->notes ) : ?>
            <tr>
                <th>Comentarios</th>
                <td><?php echo nl2br( esc_html( $booking->notes ) ); ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <p class="submit" style="display:flex;gap:8px;">
            <?php if ( $booking->booking_status === 'pending' ) : ?>
            <button class="button button-primary rt-detail-status-btn" data-id="<?php echo intval( $booking->id ); ?>" data-status="confirmed">Confirmar reserva</button>
            <?php endif; ?>
            <?php if ( in_array( $booking->booking_status, [ 'pending', 'confirmed' ], true ) ) : ?>
            <button class="button rt-detail-status-btn" data-id="<?php echo intval( $booking->id ); ?>" data-status="cancelled">Cancelar reserva</button>
            <?php endif; ?>
        </p>
    </div>
</div>
<script>
jQuery('.rt-detail-status-btn').on('click', function(){
    var btn = jQuery(this), id = btn.data('id'), s = btn.data('status');
    if(!confirm('¿Confirmás este cambio de estado?')) return;
    jQuery.post(RT_Admin.ajax_url, {action:'rt_admin_action',nonce:RT_Admin.nonce,rt_action:'change_booking_status',booking_id:id,status:s}, function(r){
        if(r.success) location.reload();
        else alert('Error al cambiar estado.');
    });
});
</script>
