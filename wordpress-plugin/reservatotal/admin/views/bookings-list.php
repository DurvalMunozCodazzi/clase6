<?php
/* ─── bookings-list.php ─────────────────────────────────── */
// Incluir este archivo como admin/views/bookings-list.php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap rt-admin-wrap">
    <h1 class="rt-page-title">
        <span class="dashicons dashicons-calendar-alt"></span>
        Reservas
        <a href="<?php echo esc_url( admin_url('admin.php?page=reservatotal') ); ?>" class="page-title-action">← Panel</a>
    </h1>

    <!-- Filtros rápidos -->
    <ul class="subsubsub">
        <?php
        $filters = [''=>'Todas','pending'=>'Pendientes','confirmed'=>'Confirmadas','cancelled'=>'Canceladas','completed'=>'Completadas'];
        foreach ( $filters as $s => $l ) :
            $url = admin_url('admin.php?page=reservatotal-reservas' . ($s ? '&status='.$s : ''));
        ?>
        <li><a href="<?php echo esc_url($url); ?>" <?php echo ($status===$s)?'class="current"':''; ?>><?php echo esc_html($l); ?></a> |</li>
        <?php endforeach; ?>
    </ul>

    <div class="rt-section">
        <?php if ( $bookings ) : ?>
        <table class="rt-table widefat striped">
            <thead><tr>
                <th>Código</th><th>Huésped</th><th>Email</th>
                <th>Habitación</th><th>Check-in</th><th>Check-out</th>
                <th>Total</th><th>Pago</th><th>Estado</th><th>Acciones</th>
            </tr></thead>
            <tbody>
            <?php foreach ( $bookings as $b ) : ?>
            <tr id="rt-row-<?php echo $b->id; ?>">
                <td><strong><?php echo esc_html($b->booking_code); ?></strong></td>
                <td><?php echo esc_html($b->guest_name); ?></td>
                <td><a href="mailto:<?php echo esc_attr($b->guest_email); ?>"><?php echo esc_html($b->guest_email); ?></a></td>
                <td><?php echo esc_html($b->room_name); ?></td>
                <td><?php echo date('d/m/Y', strtotime($b->checkin)); ?></td>
                <td><?php echo date('d/m/Y', strtotime($b->checkout)); ?></td>
                <td>$ <?php echo number_format($b->total_price,0,',','.'); ?></td>
                <td><?php echo rt_payment_badge($b->payment_status); ?></td>
                <td><?php echo rt_booking_status_badge($b->booking_status); ?></td>
                <td>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=reservatotal-reservas&action=view&id='.$b->id)); ?>" class="button button-small">Ver</a>
                    <?php if ( $b->booking_status === 'pending' ) : ?>
                    <button class="button button-small rt-confirm-btn" data-id="<?php echo $b->id; ?>" data-status="confirmed">Confirmar</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p style="padding:20px;color:#888;">No hay reservas<?php echo $status ? ' con ese estado' : ''; ?>.</p>
        <?php endif; ?>
    </div>
</div>
<script>
jQuery('.rt-confirm-btn').on('click', function(){
    var btn = jQuery(this), id = btn.data('id'), s = btn.data('status');
    if(!confirm('¿Confirmar esta reserva?')) return;
    jQuery.post(RT_Admin.ajax_url, {action:'rt_admin_action',nonce:RT_Admin.nonce,rt_action:'change_booking_status',booking_id:id,status:s}, function(r){
        if(r.success) location.reload();
        else alert('Error al cambiar estado.');
    });
});
</script>
