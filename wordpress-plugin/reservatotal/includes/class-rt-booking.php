<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RT_Booking {

    // Crear una reserva nueva
    public function create( $data ) {
        global $wpdb;

        // Validaciones básicas
        $required = [ 'room_id', 'guest_name', 'guest_email', 'checkin', 'checkout', 'guests_count' ];
        foreach ( $required as $field ) {
            if ( empty( $data[ $field ] ) ) {
                return [ 'success' => false, 'message' => "Campo requerido faltante: {$field}" ];
            }
        }

        $room_id  = absint( $data['room_id'] );
        $checkin  = sanitize_text_field( $data['checkin'] );
        $checkout = sanitize_text_field( $data['checkout'] );

        // Validar fechas
        if ( strtotime( $checkin ) >= strtotime( $checkout ) ) {
            return [ 'success' => false, 'message' => 'La fecha de check-out debe ser posterior al check-in.' ];
        }
        if ( strtotime( $checkin ) < strtotime( 'today' ) ) {
            return [ 'success' => false, 'message' => 'La fecha de check-in no puede ser en el pasado.' ];
        }

        // Verificar disponibilidad
        $room = RT()->room;
        if ( ! $room->is_available( $room_id, $checkin, $checkout ) ) {
            return [ 'success' => false, 'message' => 'La habitación no está disponible para las fechas seleccionadas.' ];
        }

        // Calcular precio
        $nights = (int) ( ( strtotime( $checkout ) - strtotime( $checkin ) ) / DAY_IN_SECONDS );
        $price  = RT()->pricing->calculate( $room_id, $checkin, $checkout );

        if ( ! $price || $price['total'] <= 0 ) {
            return [ 'success' => false, 'message' => 'No se pudo calcular el precio. Verificá las tarifas.' ];
        }

        $booking_code = RT_Database::generate_booking_code();

        $record = [
            'room_id'        => $room_id,
            'booking_code'   => $booking_code,
            'guest_name'     => sanitize_text_field( $data['guest_name'] ),
            'guest_email'    => sanitize_email( $data['guest_email'] ),
            'guest_phone'    => sanitize_text_field( $data['guest_phone'] ?? '' ),
            'guest_dni'      => sanitize_text_field( $data['guest_dni'] ?? '' ),
            'checkin'        => $checkin,
            'checkout'       => $checkout,
            'nights'         => $nights,
            'guests_count'   => absint( $data['guests_count'] ),
            'total_price'    => $price['total'],
            'paid_amount'    => 0.00,
            'currency'       => $price['currency'],
            'payment_status' => 'pending',
            'booking_status' => 'pending',
            'notes'          => sanitize_textarea_field( $data['notes'] ?? '' ),
        ];

        $result = $wpdb->insert( $wpdb->prefix . 'rt_bookings', $record );

        if ( ! $result ) {
            return [ 'success' => false, 'message' => 'Error al guardar la reserva. Intentá nuevamente.' ];
        }

        $booking_id = $wpdb->insert_id;

        // Notificar al admin
        RT()->notifications->send_admin_new_booking( $booking_id );

        return [
            'success'      => true,
            'booking_id'   => $booking_id,
            'booking_code' => $booking_code,
            'total'        => $price['total'],
            'currency'     => $price['currency'],
            'message'      => 'Reserva creada exitosamente.',
        ];
    }

    // Obtener reserva por ID
    public function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT b.*, r.post_id,
                    p.post_title as room_name
             FROM {$wpdb->prefix}rt_bookings b
             LEFT JOIN {$wpdb->prefix}rt_rooms r ON b.room_id = r.id
             LEFT JOIN {$wpdb->posts} p ON r.post_id = p.ID
             WHERE b.id = %d",
            absint( $id )
        ) );
    }

    // Obtener reserva por código
    public function get_by_code( $code ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rt_bookings WHERE booking_code = %s",
            sanitize_text_field( $code )
        ) );
    }

    // Listar reservas (panel admin)
    public function get_list( $args = [] ) {
        global $wpdb;
        $defaults = [
            'status'   => '',
            'room_id'  => 0,
            'month'    => '',
            'limit'    => 50,
            'offset'   => 0,
            'orderby'  => 'checkin',
            'order'    => 'ASC',
        ];
        $args = wp_parse_args( $args, $defaults );

        $where = '1=1';
        $values = [];

        if ( ! empty( $args['status'] ) ) {
            $where .= ' AND b.booking_status = %s';
            $values[] = $args['status'];
        }
        if ( ! empty( $args['room_id'] ) ) {
            $where .= ' AND b.room_id = %d';
            $values[] = absint( $args['room_id'] );
        }
        if ( ! empty( $args['month'] ) ) {
            $where .= ' AND DATE_FORMAT(b.checkin, "%%Y-%%m") = %s';
            $values[] = $args['month'];
        }

        $order   = in_array( strtoupper( $args['order'] ), ['ASC','DESC'] ) ? $args['order'] : 'ASC';
        $orderby = in_array( $args['orderby'], ['checkin','checkout','created_at','total_price','guest_name'] ) ? $args['orderby'] : 'checkin';
        $limit   = absint( $args['limit'] );
        $offset  = absint( $args['offset'] );

        $query = "SELECT b.*, p.post_title as room_name
                  FROM {$wpdb->prefix}rt_bookings b
                  LEFT JOIN {$wpdb->prefix}rt_rooms r ON b.room_id = r.id
                  LEFT JOIN {$wpdb->posts} p ON r.post_id = p.ID
                  WHERE {$where}
                  ORDER BY b.{$orderby} {$order}
                  LIMIT {$limit} OFFSET {$offset}";

        if ( ! empty( $values ) ) {
            $query = $wpdb->prepare( $query, ...$values );
        }

        return $wpdb->get_results( $query );
    }

    // Cambiar estado de una reserva
    public function update_status( $booking_id, $status, $payment_status = null ) {
        global $wpdb;

        $allowed_booking = ['pending','confirmed','cancelled','completed'];
        $allowed_payment = ['pending','partial','paid','refunded','failed'];

        if ( ! in_array( $status, $allowed_booking ) ) return false;

        $data = [ 'booking_status' => $status ];
        if ( $payment_status && in_array( $payment_status, $allowed_payment ) ) {
            $data['payment_status'] = $payment_status;
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'rt_bookings',
            $data,
            [ 'id' => absint( $booking_id ) ]
        );

        // Si se confirma, enviar email al huésped
        if ( $status === 'confirmed' ) {
            RT()->notifications->send_guest_confirmation( $booking_id );
        }
        if ( $status === 'cancelled' ) {
            RT()->notifications->send_guest_cancellation( $booking_id );
        }

        return $result !== false;
    }

    // Actualizar pago (llamado desde webhook MP)
    public function update_payment( $booking_id, $mp_payment_id, $mp_status, $amount ) {
        global $wpdb;

        $booking_status = 'pending';
        $payment_status = 'pending';

        switch ( $mp_status ) {
            case 'approved':
                $payment_status = 'paid';
                $booking_status = 'confirmed';
                break;
            case 'pending':
            case 'in_process':
                $payment_status = 'pending';
                $booking_status = 'pending';
                break;
            case 'rejected':
            case 'cancelled':
                $payment_status = 'failed';
                break;
            case 'refunded':
                $payment_status = 'refunded';
                $booking_status = 'cancelled';
                break;
        }

        $wpdb->update(
            $wpdb->prefix . 'rt_bookings',
            [
                'mp_payment_id'  => sanitize_text_field( $mp_payment_id ),
                'mp_status'      => sanitize_text_field( $mp_status ),
                'paid_amount'    => floatval( $amount ),
                'payment_status' => $payment_status,
                'booking_status' => $booking_status,
            ],
            [ 'id' => absint( $booking_id ) ]
        );

        // Log del pago
        $wpdb->insert( $wpdb->prefix . 'rt_payments', [
            'booking_id'    => absint( $booking_id ),
            'mp_payment_id' => sanitize_text_field( $mp_payment_id ),
            'mp_status'     => sanitize_text_field( $mp_status ),
            'amount'        => floatval( $amount ),
            'currency'      => 'ARS',
        ] );

        if ( $booking_status === 'confirmed' ) {
            RT()->notifications->send_guest_confirmation( $booking_id );
        }
    }

    // Stats para el dashboard
    public function get_stats() {
        global $wpdb;
        return [
            'total'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rt_bookings WHERE booking_status != 'cancelled'" ),
            'confirmed' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rt_bookings WHERE booking_status = 'confirmed'" ),
            'pending'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rt_bookings WHERE booking_status = 'pending'" ),
            'revenue'   => (float) $wpdb->get_var( "SELECT SUM(paid_amount) FROM {$wpdb->prefix}rt_bookings WHERE payment_status = 'paid'" ),
            'this_month'=> (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rt_bookings WHERE booking_status != 'cancelled' AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')" ),
        ];
    }
}
