<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<table class="form-table">
    <tr>
        <th><label for="rt_type">Tipo</label></th>
        <td>
            <select name="type" id="rt_type">
                <option value="room"   <?php selected( $room->type ?? 'room', 'room' ); ?>>Habitación</option>
                <option value="cabin"  <?php selected( $room->type ?? '', 'cabin' ); ?>>Cabaña</option>
                <option value="suite"  <?php selected( $room->type ?? '', 'suite' ); ?>>Suite</option>
                <option value="apt"    <?php selected( $room->type ?? '', 'apt' ); ?>>Departamento</option>
            </select>
        </td>
    </tr>
    <tr>
        <th><label for="rt_capacity">Capacidad máx. (personas)</label></th>
        <td>
            <input type="number" name="capacity" id="rt_capacity" min="1" max="30"
                   value="<?php echo esc_attr( $room->capacity ?? 2 ); ?>" style="width:80px;">
        </td>
    </tr>
    <tr>
        <th><label for="rt_base_price">Precio base por noche</label></th>
        <td>
            <input type="number" name="base_price" id="rt_base_price" min="0" step="100"
                   value="<?php echo esc_attr( $room->base_price ?? 0 ); ?>" style="width:120px;">
            <select name="currency" style="margin-left:8px;">
                <?php foreach ( ['ARS','USD','BRL'] as $c ) : ?>
                <option value="<?php echo $c; ?>" <?php selected( $room->currency ?? 'ARS', $c ); ?>><?php echo $c; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <th><label>Estadía mínima / máxima (noches)</label></th>
        <td>
            <input type="number" name="min_nights" min="1" max="30" style="width:70px;"
                   value="<?php echo esc_attr( $room->min_nights ?? 1 ); ?>"> mín. &nbsp;
            <input type="number" name="max_nights" min="1" max="365" style="width:70px;"
                   value="<?php echo esc_attr( $room->max_nights ?? 30 ); ?>"> máx.
        </td>
    </tr>
    <tr>
        <th><label>Estado</label></th>
        <td>
            <select name="status">
                <option value="active"   <?php selected( $room->status ?? 'active', 'active' ); ?>>Activa (acepta reservas)</option>
                <option value="inactive" <?php selected( $room->status ?? '', 'inactive' ); ?>>Inactiva</option>
            </select>
        </td>
    </tr>
</table>
