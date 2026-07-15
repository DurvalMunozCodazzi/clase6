/* global jQuery, RT_Public */
(function($) {
    'use strict';

    if ( ! $('#rt-booking-app').length ) return;

    // Estado de la reserva
    var state = {
        room_id   : 0,
        checkin   : '',
        checkout  : '',
        guests    : 1,
        price     : null,
        available : false
    };

    // ── Cambio de habitación: cargar fechas ocupadas ──
    $('#rt-room').on('change', function() {
        state.room_id = parseInt( $(this).val() ) || 0;
        state.available = false;
        $('#rt-price-preview, #rt-availability-msg').hide();
        $('#rt-btn-next1').hide();
        if ( state.room_id ) loadOccupiedDates( state.room_id );
    }).trigger('change');

    // ── Cambio de fechas ──
    $('#rt-checkin, #rt-checkout').on('change', function() {
        state.checkin  = $('#rt-checkin').val();
        state.checkout = $('#rt-checkout').val();
        state.available = false;
        $('#rt-price-preview, #rt-availability-msg').hide();
        $('#rt-btn-next1').hide();

        // Auto-set checkout mínimo
        if ( state.checkin ) {
            var minCheckout = new Date( state.checkin );
            var roomSel = document.getElementById('rt-room');
            var minNights = parseInt( roomSel.options[ roomSel.selectedIndex ]?.dataset?.min || 1 );
            minCheckout.setDate( minCheckout.getDate() + minNights );
            $('#rt-checkout').attr('min', minCheckout.toISOString().slice(0,10) );
            if ( state.checkout && state.checkout <= state.checkin ) {
                $('#rt-checkout').val('');
                state.checkout = '';
            }
        }
    });

    // ── Verificar disponibilidad ──
    $('#rt-btn-check').on('click', function() {
        if ( ! state.room_id ) { showMsg('Seleccioná una habitación.','error'); return; }
        if ( ! state.checkin )  { showMsg('Seleccioná fecha de check-in.','error'); return; }
        if ( ! state.checkout ) { showMsg('Seleccioná fecha de check-out.','error'); return; }

        $(this).prop('disabled', true).text('Verificando…');

        $.post( RT_Public.ajax_url, {
            action   : 'rt_public_action',
            rt_action: 'check_availability',
            nonce    : RT_Public.nonce,
            room_id  : state.room_id,
            checkin  : state.checkin,
            checkout : state.checkout
        }, function(res) {
            $('#rt-btn-check').prop('disabled', false).text('Verificar disponibilidad');
            if ( ! res.success ) { showMsg( res.data || 'Error al verificar.', 'error'); return; }

            state.available = res.data.available;
            state.price     = res.data.price;

            if ( res.data.available && res.data.price ) {
                var p = res.data.price;
                $('#rt-nights-label').text( p.nights + ' noche' + (p.nights>1?'s':'') );
                $('#rt-total-label').text(
                    '$ ' + parseFloat(p.total).toLocaleString('es-AR') + ' ' + p.currency
                );
                $('#rt-price-preview').show();
                $('#rt-btn-next1').show();
                showMsg('✓ Disponible para las fechas seleccionadas.','success');
            } else {
                showMsg('✗ No disponible para esas fechas. Probá otras.','error');
                $('#rt-btn-next1').hide();
            }
        }).fail(function() {
            $('#rt-btn-check').prop('disabled',false).text('Verificar disponibilidad');
            showMsg('Error de conexión. Intentá nuevamente.','error');
        });
    });

    // ── Paso 1 → 2 ──
    $('#rt-btn-next1').on('click', function() {
        if ( ! state.available ) return;
        var room = document.getElementById('rt-room');
        var roomName = room.options[room.selectedIndex].text;
        var p = state.price;

        $('#rt-summary-text').html(
            '<strong>' + escHtml(roomName) + '</strong><br>' +
            'Check-in: ' + formatDate(state.checkin) + ' — Check-out: ' + formatDate(state.checkout) + '<br>' +
            p.nights + ' noche' + (p.nights>1?'s':'') +
            ' · <strong>$ ' + parseFloat(p.total).toLocaleString('es-AR') + ' ' + p.currency + '</strong>'
        );
        $('#rt-step-1').hide();
        $('#rt-step-2').show();
    });

    // ── Paso 2 → 1 ──
    $('#rt-btn-back1').on('click', function() {
        $('#rt-step-2').hide();
        $('#rt-step-1').show();
    });

    // ── Pagar con MercadoPago ──
    $('#rt-btn-pay').on('click', function() {
        var name  = $.trim( $('#rt-name').val() );
        var email = $.trim( $('#rt-email').val() );
        if ( ! name )  { alert('Ingresá tu nombre completo.'); return; }
        if ( ! email ) { alert('Ingresá tu email.'); return; }
        if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ) { alert('Email inválido.'); return; }

        $('#rt-step-2').hide();
        $('#rt-step-loading').show();

        $.post( RT_Public.ajax_url, {
            action        : 'rt_public_action',
            rt_action     : 'create_booking',
            nonce         : RT_Public.nonce,
            room_id       : state.room_id,
            checkin       : state.checkin,
            checkout      : state.checkout,
            guests_count  : $('#rt-guests').val(),
            guest_name    : name,
            guest_email   : email,
            guest_phone   : $('#rt-phone').val(),
            guest_dni     : $('#rt-dni').val(),
            notes         : $('#rt-notes').val()
        }, function(res) {
            if ( ! res.success ) {
                $('#rt-step-loading').hide();
                $('#rt-step-2').show();
                alert( res.data || 'Error al crear la reserva.' );
                return;
            }
            // Redirigir a MercadoPago
            window.location.href = res.data.mp_init_point;
        }).fail(function() {
            $('#rt-step-loading').hide();
            $('#rt-step-2').show();
            alert('Error de conexión. Intentá nuevamente.');
        });
    });

    // ── Cargar fechas ocupadas (para deshabilitar en el datepicker nativo) ──
    function loadOccupiedDates( room_id ) {
        $.post( RT_Public.ajax_url, {
            action   : 'rt_public_action',
            rt_action: 'get_occupied_dates',
            nonce    : RT_Public.nonce,
            room_id  : room_id
        }, function(res) {
            if ( res.success && res.data.dates.length ) {
                // Para inputs type=date nativos no hay una API simple de deshabilitar fechas;
                // guardamos las fechas para validación
                state.occupiedDates = res.data.dates;
            }
        });
    }

    // ── Helpers ──
    function showMsg( msg, type ) {
        var m = $('#rt-availability-msg');
        m.removeClass('rt-msg-success rt-msg-error rt-msg-warning')
         .addClass( 'rt-msg-' + type )
         .text( msg )
         .show();
    }

    function formatDate( iso ) {
        if ( ! iso ) return '';
        var parts = iso.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

})(jQuery);
