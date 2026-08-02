<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php if ( ! $room ) : ?>
<p style="color:#888;">Guardá la habitación primero para agregar tarifas.</p>
<?php return; endif; ?>

<div id="rt-rates-wrap">
    <?php if ( $rates ) : ?>
    <table class="widefat" id="rt-rates-table">
        <thead><tr><th>Nombre</th><th>Tipo</th><th>Desde</th><th>Hasta</th><th>Precio/noche</th><th></th></tr></thead>
        <tbody>
        <?php foreach ( $rates as $r ) : ?>
        <tr id="rt-rate-<?php echo $r->id; ?>">
            <td><?php echo esc_html($r->name); ?></td>
            <td><?php echo esc_html(ucfirst($r->type)); ?></td>
            <td><?php echo date('d/m/Y', strtotime($r->date_from)); ?></td>
            <td><?php echo date('d/m/Y', strtotime($r->date_to)); ?></td>
            <td>$ <?php echo number_format($r->price,0,',','.'); ?></td>
            <td>
                <button class="button button-small rt-delete-rate" data-id="<?php echo $r->id; ?>">✕</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Formulario nueva tarifa -->
    <hr style="margin:16px 0;">
    <strong>Agregar tarifa / temporada</strong>
    <table class="form-table" style="margin-top:8px;">
        <tr>
            <td><label>Nombre</label><br><input type="text" id="rt-rate-name" placeholder="Ej: Temporada alta" style="width:160px;"></td>
            <td><label>Tipo</label><br>
                <select id="rt-rate-type">
                    <option value="season">Temporada</option>
                    <option value="special">Especial</option>
                    <option value="weekend">Fines de semana</option>
                </select>
            </td>
            <td><label>Desde</label><br><input type="date" id="rt-rate-from"></td>
            <td><label>Hasta</label><br><input type="date" id="rt-rate-to"></td>
            <td><label>Precio/noche</label><br><input type="number" id="rt-rate-price" min="0" step="100" style="width:100px;"></td>
            <td style="vertical-align:bottom;">
                <button type="button" id="rt-add-rate" class="button button-primary">Agregar</button>
            </td>
        </tr>
    </table>
</div>

<script>
(function($){
    var roomId = <?php echo intval($room->id); ?>;

    $('#rt-add-rate').on('click', function(){
        var data = {
            action:'rt_admin_action', nonce:RT_Admin.nonce, rt_action:'save_rate',
            room_id:roomId, name:$('#rt-rate-name').val(), type:$('#rt-rate-type').val(),
            date_from:$('#rt-rate-from').val(), date_to:$('#rt-rate-to').val(), price:$('#rt-rate-price').val()
        };
        if(!data.name||!data.date_from||!data.date_to||!data.price){ alert('Completá todos los campos.'); return; }
        $.post(RT_Admin.ajax_url, data, function(r){ if(r.success) location.reload(); else alert('Error.'); });
    });

    $(document).on('click','.rt-delete-rate',function(){
        var btn=$(this), id=btn.data('id');
        if(!confirm('¿Eliminar esta tarifa?')) return;
        $.post(RT_Admin.ajax_url,{action:'rt_admin_action',nonce:RT_Admin.nonce,rt_action:'delete_rate',rate_id:id},function(r){
            if(r.success) $('#rt-rate-'+id).remove();
        });
    });
})(jQuery);
</script>
