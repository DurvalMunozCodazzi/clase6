<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rt-admin-wrap">
    <h1 class="rt-page-title">
        <span class="dashicons dashicons-calendar"></span>
        Calendario de disponibilidad
    </h1>

    <?php if ( empty($rooms) ) : ?>
    <p>No hay habitaciones activas. <a href="<?php echo esc_url(admin_url('post-new.php?post_type=rt_room')); ?>">Agregar habitación →</a></p>
    <?php return; endif; ?>

    <!-- Selector de habitación -->
    <div style="margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <label for="rt-cal-room" style="font-weight:600;">Habitación:</label>
        <select id="rt-cal-room" style="min-width:220px;">
            <?php foreach ( $rooms as $r ) : ?>
            <option value="<?php echo esc_attr($r->id); ?>"><?php echo esc_html($r->name); ?></option>
            <?php endforeach; ?>
        </select>

        <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
            <label style="font-weight:600;">Bloquear fechas:</label>
            <input type="date" id="rt-block-from" placeholder="Desde">
            <input type="date" id="rt-block-to"   placeholder="Hasta">
            <input type="text" id="rt-block-reason" placeholder="Motivo (opcional)" style="min-width:160px;">
            <button id="rt-btn-block" class="button button-secondary">Bloquear</button>
        </div>
    </div>

    <!-- Leyenda -->
    <div style="margin-bottom:12px;display:flex;gap:20px;font-size:13px;">
        <span><span style="display:inline-block;width:14px;height:14px;background:#ffcdd2;border-radius:3px;vertical-align:middle;margin-right:4px;"></span>Ocupado (reserva)</span>
        <span><span style="display:inline-block;width:14px;height:14px;background:#b0bec5;border-radius:3px;vertical-align:middle;margin-right:4px;"></span>Bloqueado manualmente</span>
        <span><span style="display:inline-block;width:14px;height:14px;background:#c8e6c9;border-radius:3px;vertical-align:middle;margin-right:4px;"></span>Disponible</span>
    </div>

    <!-- Calendario HTML generado por JS -->
    <div id="rt-calendar-grid" style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;">
        <p style="color:#888;text-align:center;">Cargando calendario…</p>
    </div>
</div>

<script>
(function($){
    var currentRoomId = 0;
    var occupiedDates = [];

    function renderCalendar(occupied) {
        var grid = $('#rt-calendar-grid');
        grid.empty();
        var today = new Date();
        var html  = '';

        // Mostrar 3 meses
        for ( var m = 0; m < 3; m++ ) {
            var d    = new Date(today.getFullYear(), today.getMonth() + m, 1);
            var year = d.getFullYear();
            var mon  = d.getMonth();
            var monthName = d.toLocaleString('es-AR', {month:'long',year:'numeric'});

            html += '<div style="display:inline-block;vertical-align:top;margin:0 16px 24px 0;">';
            html += '<div style="font-weight:600;font-size:15px;margin-bottom:8px;text-transform:capitalize;">' + monthName + '</div>';
            html += '<table style="border-collapse:collapse;">';
            html += '<tr>';
            ['Lu','Ma','Mi','Ju','Vi','Sa','Do'].forEach(function(day){
                html += '<th style="width:34px;text-align:center;font-size:12px;color:#888;padding:4px;">' + day + '</th>';
            });
            html += '</tr><tr>';

            var firstDay = new Date(year, mon, 1).getDay(); // 0=dom
            var offset   = (firstDay === 0) ? 6 : firstDay - 1;
            for ( var i = 0; i < offset; i++ ) html += '<td></td>';

            var daysInMonth = new Date(year, mon+1, 0).getDate();
            var col = offset;

            for ( var day = 1; day <= daysInMonth; day++ ) {
                var dateStr = year + '-' + String(mon+1).padStart(2,'0') + '-' + String(day).padStart(2,'0');
                var isPast  = new Date(year,mon,day) < new Date(today.getFullYear(),today.getMonth(),today.getDate());
                var isOcc   = occupied.indexOf(dateStr) >= 0;
                var bg      = isPast ? '#f5f5f5' : (isOcc ? '#ffcdd2' : '#c8e6c9');
                var color   = isPast ? '#bbb'    : (isOcc ? '#b71c1c' : '#1b5e20');

                html += '<td style="text-align:center;padding:2px;">';
                html += '<div style="width:30px;height:30px;line-height:30px;border-radius:4px;font-size:13px;background:'+bg+';color:'+color+';">' + day + '</div>';
                html += '</td>';

                col++;
                if ( col % 7 === 0 && day < daysInMonth ) html += '</tr><tr>';
            }
            html += '</tr></table></div>';
        }

        grid.html(html);
    }

    function loadOccupied(roomId) {
        $.post(RT_Admin.ajax_url, {
            action:'rt_admin_action', nonce:RT_Admin.nonce,
            rt_action:'get_occupied_dates', room_id:roomId
        }, function(r){
            if(r.success) {
                occupiedDates = r.data.dates;
                renderCalendar(occupiedDates);
            }
        });
    }

    $('#rt-cal-room').on('change', function(){
        currentRoomId = parseInt($(this).val());
        loadOccupied(currentRoomId);
    }).trigger('change');

    $('#rt-btn-block').on('click', function(){
        var from   = $('#rt-block-from').val();
        var to     = $('#rt-block-to').val();
        var reason = $('#rt-block-reason').val();
        if(!from||!to){ alert('Completá las fechas.'); return; }
        if(from>=to){ alert('La fecha "Hasta" debe ser posterior a "Desde".'); return; }

        $.post(RT_Admin.ajax_url, {
            action:'rt_admin_action', nonce:RT_Admin.nonce,
            rt_action:'block_dates', room_id:currentRoomId,
            date_from:from, date_to:to, reason:reason
        }, function(r){
            if(r.success){
                alert('Fechas bloqueadas correctamente.');
                loadOccupied(currentRoomId);
                $('#rt-block-from,#rt-block-to,#rt-block-reason').val('');
            }
        });
    });
})(jQuery);
</script>
