<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rt-admin-wrap">
    <h1 class="rt-page-title">
        <span class="dashicons dashicons-calendar-alt"></span>
        ReservaTotal — Panel principal
    </h1>

    <?php if ( ! RT()->license->is_active() ) : ?>
    <div class="rt-notice rt-notice-error">
        <strong>Licencia inactiva.</strong>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=reservatotal-licencia' ) ); ?>">Activá tu licencia para comenzar →</a>
    </div>
    <?php else : ?>

    <!-- Stats cards -->
    <div class="rt-stats-grid">
        <div class="rt-stat-card">
            <span class="rt-stat-icon dashicons dashicons-calendar-alt" style="color:#1a73e8;"></span>
            <div>
                <div class="rt-stat-value"><?php echo intval( $stats['confirmed'] ); ?></div>
                <div class="rt-stat-label">Reservas confirmadas</div>
            </div>
        </div>
        <div class="rt-stat-card">
            <span class="rt-stat-icon dashicons dashicons-clock" style="color:#fb8c00;"></span>
            <div>
                <div class="rt-stat-value"><?php echo intval( $stats['pending'] ); ?></div>
                <div class="rt-stat-label">Pendientes de pago</div>
            </div>
        </div>
        <div class="rt-stat-card">
            <span class="rt-stat-icon dashicons dashicons-money-alt" style="color:#43a047;"></span>
            <div>
                <div class="rt-stat-value">$ <?php echo number_format( $stats['revenue'], 0, ',', '.' ); ?></div>
                <div class="rt-stat-label">Ingresos cobrados</div>
            </div>
        </div>
        <div class="rt-stat-card">
            <span class="rt-stat-icon dashicons dashicons-groups" style="color:#8e24aa;"></span>
            <div>
                <div class="rt-stat-value"><?php echo intval( $stats['this_month'] ); ?></div>
                <div class="rt-stat-label">Reservas este mes</div>
            </div>
        </div>
    </div>

    <!-- Últimas reservas -->
    <div class="rt-section">
        <div class="rt-section-header">
            <h2>Últimas reservas</h2>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=reservatotal-reservas' ) ); ?>" class="button">Ver todas</a>
        </div>
        <?php
        $recent = RT()->booking->get_list( [ 'limit' => 8, 'orderby' => 'created_at', 'order' => 'DESC' ] );
        if ( $recent ) :
        ?>
        <table class="rt-table widefat">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Huésped</th>
                    <th>Habitación</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $recent as $b ) : ?>
                <tr>
                    <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=reservatotal-reservas&action=view&id=' . $b->id ) ); ?>">
                        <?php echo esc_html( $b->booking_code ); ?>
                    </a></td>
                    <td><?php echo esc_html( $b->guest_name ); ?></td>
                    <td><?php echo esc_html( $b->room_name ); ?></td>
                    <td><?php echo esc_html( date( 'd/m/Y', strtotime( $b->checkin ) ) ); ?></td>
                    <td><?php echo esc_html( date( 'd/m/Y', strtotime( $b->checkout ) ) ); ?></td>
                    <td>$ <?php echo number_format( $b->total_price, 0, ',', '.' ); ?></td>
                    <td><?php echo rt_booking_status_badge( $b->booking_status ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p style="color:#888;">Todavía no hay reservas.</p>
        <?php endif; ?>
    </div>

    <!-- Accesos rápidos -->
    <div class="rt-quick-links">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=reservatotal-calendario' ) ); ?>" class="rt-quick-link">
            <span class="dashicons dashicons-calendar"></span> Ver calendario
        </a>
        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=rt_room' ) ); ?>" class="rt-quick-link">
            <span class="dashicons dashicons-plus-alt"></span> Agregar habitación
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=reservatotal-config' ) ); ?>" class="rt-quick-link">
            <span class="dashicons dashicons-admin-settings"></span> Configuración
        </a>
    </div>

    <?php endif; ?>
</div>
