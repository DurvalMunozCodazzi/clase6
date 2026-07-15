<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rt-admin-wrap">
    <h1 class="rt-page-title">
        <span class="dashicons dashicons-admin-network"></span>
        ReservaTotal — Licencia
    </h1>

    <?php if ( ! empty( $message ) ) : ?>
    <div class="notice notice-<?php echo $message['success'] ? 'success' : 'error'; ?> is-dismissible">
        <p><?php echo esc_html( $message['message'] ); ?></p>
    </div>
    <?php endif; ?>

    <div class="rt-card" style="max-width:600px;">
        <h2 style="margin-top:0;">Estado de la licencia</h2>

        <?php
        $status  = RT()->license->get_status();
        $days    = RT()->license->days_until_expiry();
        $expiry  = RT()->license->get_expiry();
        $domain  = RT()->license->get_clean_domain();
        $key_m   = RT()->license->get_key_masked();

        $status_labels = [
            'active'   => [ '✓ Activa',       '#2e7d32', '#e8f5e9' ],
            'inactive' => [ '○ Inactiva',      '#888',    '#f5f5f5' ],
            'expired'  => [ '✗ Vencida',       '#c62828', '#ffebee' ],
            'invalid'  => [ '✗ Inválida',      '#c62828', '#ffebee' ],
            'suspended'=> [ '⚠ Suspendida',    '#e65100', '#fff3e0' ],
        ];
        $sl = $status_labels[ $status ] ?? [ $status, '#888', '#f5f5f5' ];
        ?>

        <table class="form-table" style="margin-bottom:0;">
            <tr>
                <th>Estado</th>
                <td>
                    <span style="background:<?php echo esc_attr($sl[2]); ?>;color:<?php echo esc_attr($sl[1]); ?>;padding:4px 14px;border-radius:20px;font-weight:600;">
                        <?php echo esc_html( $sl[0] ); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Dominio</th>
                <td><code><?php echo esc_html( $domain ); ?></code></td>
            </tr>
            <?php if ( $key_m ) : ?>
            <tr>
                <th>Clave</th>
                <td><code><?php echo esc_html( $key_m ); ?></code></td>
            </tr>
            <?php endif; ?>
            <?php if ( $expiry ) : ?>
            <tr>
                <th>Vencimiento</th>
                <td>
                    <?php echo esc_html( date( 'd/m/Y', strtotime( $expiry ) ) ); ?>
                    <?php if ( $days !== null ) : ?>
                    <span style="color:<?php echo $days <= 30 ? '#e65100' : '#43a047'; ?>;font-size:12px;margin-left:8px;">
                        (<?php echo $days; ?> días)
                    </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>

        <?php if ( $status !== 'active' ) : ?>
        <!-- Formulario de activación -->
        <hr style="margin:24px 0;">
        <h3 style="margin-top:0;">Activar licencia</h3>
        <form method="post" action="">
            <?php wp_nonce_field( 'rt_license_action' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="rt_license_key">Clave de licencia</label></th>
                    <td>
                        <input type="text" id="rt_license_key" name="rt_license_key"
                               class="regular-text" placeholder="RT-XXXX-XXXX-XXXX-XXXX"
                               style="font-family:monospace;letter-spacing:1px;" required>
                        <p class="description">Ingresá la clave que recibiste al comprar en <a href="https://reservatotal.com.ar" target="_blank">reservatotal.com.ar</a></p>
                    </td>
                </tr>
            </table>
            <button type="submit" name="rt_activate_license" class="button button-primary button-hero">
                Activar licencia
            </button>
        </form>

        <?php else : ?>
        <!-- Renovación -->
        <div style="margin-top:24px;padding:16px;background:#e3f2fd;border-radius:6px;border-left:4px solid #1a73e8;">
            <strong>¿Necesitás renovar tu licencia?</strong><br>
            <a href="https://reservatotal.com.ar/renovar" target="_blank">Renovar licencia anual →</a>
        </div>

        <div style="margin-top:24px;padding:16px;background:#e3f2fd;border-radius:6px;border-left:4px solid #1a73e8;">
            <strong>¿Necesitás renovar?</strong><br>
            <a href="https://reservatotal.com.ar/renovar" target="_blank">Renovar licencia anual →</a>
        </div>
        <?php endif; ?>
    </div>
</div>
