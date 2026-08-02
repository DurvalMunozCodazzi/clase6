<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RT_Notifications {

    private function get_email_header() {
        $site = get_bloginfo( 'name' );
        return "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f9f9f9;padding:0;'>
        <div style='background:#1a73e8;padding:24px 32px;'>
            <h1 style='color:#fff;margin:0;font-size:22px;'>🏨 ReservaTotal</h1>
            <p style='color:#c5d8ff;margin:4px 0 0;font-size:13px;'>{$site}</p>
        </div>
        <div style='background:#fff;padding:32px;border-left:1px solid #e0e0e0;border-right:1px solid #e0e0e0;'>
        ";
    }

    private function get_email_footer() {
        return "
        </div>
        <div style='background:#f1f1f1;padding:16px 32px;border:1px solid #e0e0e0;border-top:none;text-align:center;'>
            <p style='color:#888;font-size:12px;margin:0;'>Powered by <a href='https://reservatotal.com.ar' style='color:#1a73e8;text-decoration:none;'>ReservaTotal</a></p>
        </div>
        </div>
        ";
    }

    // Email al admin cuando llega una reserva nueva
    public function send_admin_new_booking( $booking_id ) {
        $booking = RT()->booking->get( $booking_id );
        if ( ! $booking ) return;

        $admin_email = get_option( 'rt_admin_email', get_option( 'admin_email' ) );
        $subject     = sprintf( '[ReservaTotal] Nueva reserva %s — %s', $booking->booking_code, $booking->guest_name );

        $body  = $this->get_email_header();
        $body .= "<h2 style='color:#333;margin-top:0;'>Nueva reserva recibida</h2>";
        $body .= $this->get_booking_table( $booking );
        $body .= "<p style='margin-top:24px;'>";
        $body .= "<a href='" . esc_url( admin_url( 'admin.php?page=reservatotal-reservas&action=view&id=' . $booking_id ) ) . "'
                     style='background:#1a73e8;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;'>
                     Ver reserva en el panel →
                  </a>";
        $body .= "</p>";
        $body .= $this->get_email_footer();

        wp_mail( $admin_email, $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );
    }

    // Confirmación al huésped (pago aprobado)
    public function send_guest_confirmation( $booking_id ) {
        $booking = RT()->booking->get( $booking_id );
        if ( ! $booking ) return;

        $subject = sprintf( 'Confirmación de reserva %s — %s', $booking->booking_code, get_bloginfo('name') );

        $body  = $this->get_email_header();
        $body .= "<h2 style='color:#2e7d32;margin-top:0;'>✓ ¡Reserva confirmada!</h2>";
        $body .= "<p style='color:#555;'>Hola <strong>{$booking->guest_name}</strong>, tu reserva está confirmada. Te esperamos.</p>";
        $body .= $this->get_booking_table( $booking );
        $body .= "<div style='background:#e8f5e9;border-left:4px solid #4caf50;padding:16px;margin-top:24px;border-radius:4px;'>
                    <p style='margin:0;color:#2e7d32;'><strong>Código de reserva:</strong> {$booking->booking_code}</p>
                    <p style='margin:8px 0 0;color:#555;font-size:13px;'>Guardá este código para cualquier consulta.</p>
                  </div>";
        $body .= $this->get_email_footer();

        wp_mail( $booking->guest_email, $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );
    }

    // Cancelación al huésped
    public function send_guest_cancellation( $booking_id ) {
        $booking = RT()->booking->get( $booking_id );
        if ( ! $booking ) return;

        $subject = sprintf( 'Reserva %s cancelada — %s', $booking->booking_code, get_bloginfo('name') );

        $body  = $this->get_email_header();
        $body .= "<h2 style='color:#c62828;margin-top:0;'>Reserva cancelada</h2>";
        $body .= "<p style='color:#555;'>Hola <strong>{$booking->guest_name}</strong>, tu reserva fue cancelada.</p>";
        $body .= $this->get_booking_table( $booking );
        $body .= "<p style='color:#555;'>Si tenés alguna consulta, contactanos directamente.</p>";
        $body .= $this->get_email_footer();

        wp_mail( $booking->guest_email, $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );
    }

    // Recordatorio 24hs antes del check-in (se puede llamar desde un cron)
    public function send_checkin_reminder( $booking_id ) {
        $booking = RT()->booking->get( $booking_id );
        if ( ! $booking ) return;

        $subject = sprintf( 'Recordatorio: mañana es tu check-in en %s', get_bloginfo('name') );

        $body  = $this->get_email_header();
        $body .= "<h2 style='color:#1565c0;margin-top:0;'>🗓 Recordatorio de check-in</h2>";
        $body .= "<p style='color:#555;'>Hola <strong>{$booking->guest_name}</strong>, mañana es tu llegada. ¡Te esperamos!</p>";
        $body .= $this->get_booking_table( $booking );
        $body .= $this->get_email_footer();

        wp_mail( $booking->guest_email, $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );
    }

    // Tabla HTML con datos de la reserva
    private function get_booking_table( $booking ) {
        $html  = "<table style='width:100%;border-collapse:collapse;margin-top:16px;font-size:14px;'>";
        $rows  = [
            'Habitación / Cabaña' => $booking->room_name,
            'Check-in'            => date( 'd/m/Y', strtotime( $booking->checkin ) ),
            'Check-out'           => date( 'd/m/Y', strtotime( $booking->checkout ) ),
            'Noches'              => $booking->nights,
            'Huéspedes'           => $booking->guests_count,
            'Total'               => number_format( $booking->total_price, 2, ',', '.' ) . ' ' . $booking->currency,
            'Estado pago'         => $this->payment_status_label( $booking->payment_status ),
        ];
        $alt = false;
        foreach ( $rows as $label => $value ) {
            $bg   = $alt ? '#f9f9f9' : '#fff';
            $html .= "<tr style='background:{$bg};'>
                        <td style='padding:10px 12px;border-bottom:1px solid #eee;color:#888;width:45%;'>{$label}</td>
                        <td style='padding:10px 12px;border-bottom:1px solid #eee;color:#333;font-weight:500;'>{$value}</td>
                      </tr>";
            $alt = ! $alt;
        }
        $html .= "</table>";
        return $html;
    }

    private function payment_status_label( $status ) {
        $labels = [
            'pending'  => '⏳ Pendiente',
            'partial'  => '◑ Pago parcial',
            'paid'     => '✓ Pagado',
            'refunded' => '↩ Reembolsado',
            'failed'   => '✗ Fallido',
        ];
        return $labels[ $status ] ?? $status;
    }
}
