/* ReservaTotal Admin JS */
(function($){
    'use strict';

    // Confirm booking status changes via inline buttons in dashboard
    $(document).on('click', '.rt-confirm-btn', function(){
        var btn = $(this);
        var id  = btn.data('id');
        var status = btn.data('status');
        if (!confirm('¿Confirmar esta reserva?')) return;

        btn.prop('disabled', true).text('Procesando…');
        $.post(RT_Admin.ajax_url, {
            action    : 'rt_admin_action',
            nonce     : RT_Admin.nonce,
            rt_action : 'change_booking_status',
            booking_id: id,
            status    : status
        }, function(r) {
            if (r.success) location.reload();
            else { alert('Error al cambiar el estado.'); btn.prop('disabled', false).text('Confirmar'); }
        });
    });

})(jQuery);
