<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RT_Room {

    // Obtener todas las habitaciones activas
    public function get_all( $args = [] ) {
        global $wpdb;
        $defaults = [ 'status' => 'active', 'limit' => 100 ];
        $args = wp_parse_args( $args, $defaults );

        $where = $wpdb->prepare( "WHERE r.status = %s", $args['status'] );
        $limit = absint( $args['limit'] );

        return $wpdb->get_results(
            "SELECT r.*, p.post_title as name, p.post_content as description
             FROM {$wpdb->prefix}rt_rooms r
             LEFT JOIN {$wpdb->posts} p ON r.post_id = p.ID
             {$where}
             ORDER BY p.post_title ASC
             LIMIT {$limit}"
        );
    }

    // Obtener una habitación por ID de tabla rt_rooms
    public function get( $room_id ) {
        global $wpdb;
        $room_id = absint( $room_id );
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT r.*, p.post_title as name, p.post_content as description
             FROM {$wpdb->prefix}rt_rooms r
             LEFT JOIN {$wpdb->posts} p ON r.post_id = p.ID
             WHERE r.id = %d",
            $room_id
        ) );
    }

    // Obtener una habitación por post_id
    public function get_by_post( $post_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rt_rooms WHERE post_id = %d",
            absint( $post_id )
        ) );
    }

    // Guardar/actualizar datos de habitación (metabox)
    public function save( $post_id, $data ) {
        global $wpdb;
        $existing = $this->get_by_post( $post_id );

        $record = [
            'post_id'    => absint( $post_id ),
            'type'       => sanitize_text_field( $data['type'] ?? 'room' ),
            'capacity'   => absint( $data['capacity'] ?? 2 ),
            'min_nights' => absint( $data['min_nights'] ?? 1 ),
            'max_nights' => absint( $data['max_nights'] ?? 30 ),
            'base_price' => floatval( $data['base_price'] ?? 0 ),
            'currency'   => strtoupper( sanitize_text_field( $data['currency'] ?? 'ARS' ) ),
            'status'     => in_array( $data['status'] ?? 'active', ['active','inactive'] ) ? $data['status'] : 'active',
        ];

        if ( $existing ) {
            $wpdb->update( $wpdb->prefix . 'rt_rooms', $record, [ 'post_id' => absint( $post_id ) ] );
        } else {
            $wpdb->insert( $wpdb->prefix . 'rt_rooms', $record );
        }
    }

    // Verificar disponibilidad
    public function is_available( $room_id, $checkin, $checkout ) {
        global $wpdb;
        $room_id  = absint( $room_id );
        $checkin  = sanitize_text_field( $checkin );
        $checkout = sanitize_text_field( $checkout );

        // Verificar reservas confirmadas que se superpongan
        $bookings = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rt_bookings
             WHERE room_id = %d
               AND booking_status IN ('pending','confirmed')
               AND checkin  < %s
               AND checkout > %s",
            $room_id, $checkout, $checkin
        ) );

        if ( $bookings > 0 ) return false;

        // Verificar bloqueos manuales
        $blocks = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rt_blocks
             WHERE room_id = %d
               AND date_from < %s
               AND date_to   > %s",
            $room_id, $checkout, $checkin
        ) );

        return $blocks == 0;
    }

    // Obtener fechas ocupadas para el calendario (array de fechas)
    public function get_occupied_dates( $room_id ) {
        global $wpdb;
        $room_id = absint( $room_id );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT checkin, checkout FROM {$wpdb->prefix}rt_bookings
             WHERE room_id = %d
               AND booking_status IN ('pending','confirmed')
               AND checkout >= CURDATE()
             UNION
             SELECT date_from as checkin, date_to as checkout
             FROM {$wpdb->prefix}rt_blocks
             WHERE room_id = %d AND date_to >= CURDATE()",
            $room_id, $room_id
        ) );

        $dates = [];
        foreach ( $rows as $row ) {
            $current = strtotime( $row->checkin );
            $end     = strtotime( $row->checkout );
            while ( $current < $end ) {
                $dates[] = date( 'Y-m-d', $current );
                $current = strtotime( '+1 day', $current );
            }
        }

        return array_unique( $dates );
    }
}
