<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rt-admin-wrap">
    <h1 class="rt-page-title">
        <span class="dashicons dashicons-admin-settings"></span>
        ReservaTotal — Configuración
    </h1>

    <form method="post" action="">
        <?php wp_nonce_field( 'rt_save_config' ); ?>

        <!-- MercadoPago -->
        <div class="rt-card">
            <h2 style="margin-top:0;">🟡 MercadoPago</h2>
            <table class="form-table">
                <tr>
                    <th>Modo</th>
                    <td>
                        <label>
                            <input type="checkbox" name="rt_mp_sandbox" value="1"
                                <?php checked( get_option('rt_mp_sandbox','1'), '1' ); ?>>
                            Sandbox (modo pruebas)
                        </label>
                        <p class="description">Desactivá esto solo cuando estés listo para producción.</p>
                    </td>
                </tr>
                <tr>
                    <th>Access Token (Pruebas)</th>
                    <td>
                        <input type="password" name="rt_mp_access_token_test" class="regular-text"
                               value="<?php echo esc_attr( get_option('rt_mp_access_token_test') ); ?>"
                               autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <th>Public Key (Pruebas)</th>
                    <td>
                        <input type="text" name="rt_mp_public_key_test" class="regular-text"
                               value="<?php echo esc_attr( get_option('rt_mp_public_key_test') ); ?>">
                    </td>
                </tr>
                <tr>
                    <th>Access Token (Producción)</th>
                    <td>
                        <input type="password" name="rt_mp_access_token_prod" class="regular-text"
                               value="<?php echo esc_attr( get_option('rt_mp_access_token_prod') ); ?>"
                               autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <th>Public Key (Producción)</th>
                    <td>
                        <input type="text" name="rt_mp_public_key_prod" class="regular-text"
                               value="<?php echo esc_attr( get_option('rt_mp_public_key_prod') ); ?>">
                    </td>
                </tr>
            </table>
            <p class="description" style="padding:0 8px;">
                Obtenés las credenciales en <a href="https://www.mercadopago.com.ar/developers/panel" target="_blank">mercadopago.com.ar/developers/panel</a>
            </p>
        </div>

        <!-- General -->
        <div class="rt-card" style="margin-top:24px;">
            <h2 style="margin-top:0;">⚙️ General</h2>
            <table class="form-table">
                <tr>
                    <th>Email de notificaciones</th>
                    <td>
                        <input type="email" name="rt_admin_email" class="regular-text"
                               value="<?php echo esc_attr( get_option('rt_admin_email', get_option('admin_email')) ); ?>">
                        <p class="description">Recibe notificaciones de reservas nuevas.</p>
                    </td>
                </tr>
                <tr>
                    <th>Moneda</th>
                    <td>
                        <select name="rt_currency">
                            <?php foreach ( ['ARS'=>'ARS — Peso argentino','USD'=>'USD — Dólar','BRL'=>'BRL — Real','CLP'=>'CLP — Peso chileno'] as $code => $label ) : ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected(get_option('rt_currency','ARS'),$code); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Recargo fin de semana (%)</th>
                    <td>
                        <input type="number" name="rt_weekend_surcharge" min="0" max="100" step="0.5" style="width:80px;"
                               value="<?php echo esc_attr( get_option('rt_weekend_surcharge',0) ); ?>">
                        <p class="description">0 = sin recargo. Ej: 20 = +20% sábado y domingo.</p>
                    </td>
                </tr>
                <tr>
                    <th>Hora de check-in</th>
                    <td>
                        <input type="time" name="rt_checkin_time"
                               value="<?php echo esc_attr( get_option('rt_checkin_time','14:00') ); ?>">
                    </td>
                </tr>
                <tr>
                    <th>Hora de check-out</th>
                    <td>
                        <input type="time" name="rt_checkout_time"
                               value="<?php echo esc_attr( get_option('rt_checkout_time','10:00') ); ?>">
                    </td>
                </tr>
                <tr>
                    <th>Página de reservas</th>
                    <td>
                        <?php
                        wp_dropdown_pages([
                            'name'             => 'rt_booking_page_id',
                            'selected'         => get_option('rt_booking_page_id',0),
                            'show_option_none' => '— Seleccionar página —',
                            'option_none_value'=> 0,
                        ]);
                        ?>
                        <p class="description">Página donde pusiste el shortcode <code>[reservatotal_form]</code></p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <button type="submit" name="rt_save_config" class="button button-primary button-large">
                Guardar configuración
            </button>
        </p>
    </form>
</div>
