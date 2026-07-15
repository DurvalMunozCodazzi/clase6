<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RT_Pricing {

    // Calcular precio total para un período
    public function calculate( $room_id, $checkin, $checkout ) {
        global $wpdb;

        $room = RT()->room->get( $room_id );
        if ( ! $room ) return null;

        $current    = strtotime( $checkin );
        $end        = strtotime( $checkout );
        $total      = 0.0;
        $breakdown  = [];

        while ( $current < $end ) {
            $date   = date( 'Y-m-d', $current );
            $price  = $this->get_price_for_date( $room_id, $date, $room->base_price );
            $total += $price;
            $breakdown[] = [ 'date' => $date, 'price' => $price ];
            $current = strtotime( '+1 day', $current );
        }

        return [
            'total'     => round( $total, 2 ),
            'breakdown' => $breakdown,
            'currency'  => $room->currency,
            'nights'    => count( $breakdown ),
        ];
    }

    // Precio para una fecha específica (considera tarifas especiales)
    private function get_price_for_date( $room_id, $date, $base_price ) {
        global $wpdb;

        // Buscar tarifa especial (season/weekend/special) con mayor prioridad
        $rate = $wpdb->get_row( $wpdb->prepare(
            "SELECT price FROM {$wpdb->prefix}rt_rates
             WHERE room_id = %d
               AND date_from <= %s
               AND date_to   >= %s
             ORDER BY FIELD(type,'special','season','weekend') ASC
             LIMIT 1",
            absint( $room_id ), $date, $date
        ) );

        if ( $rate ) return floatval( $rate->price );

        // Precio base con posible recargo de fin de semana (sábado=6, domingo=0)
        $day = (int) date( 'w', strtotime( $date ) );
        if ( in_array( $day, [0, 6] ) ) {
            $weekend_pct = floatval( get_option( 'rt_weekend_surcharge', 0 ) );
            if ( $weekend_pct > 0 ) {
                return round( $base_price * ( 1 + $weekend_pct / 100 ), 2 );
            }
        }

        return floatval( $base_price );
    }

    // Guardar tarifa
    public function save_rate( $data ) {
        global $wpdb;

        $record = [
            'room_id'   => absint( $data['room_id'] ),
            'name'      => sanitize_text_field( $data['name'] ),
            'date_from' => sanitize_text_field( $data['date_from'] ),
            'date_to'   => sanitize_text_field( $data['date_to'] ),
            'price'     => floatval( $data['price'] ),
            'type'      => in_array( $data['type'] ?? 'season', ['season','weekend','special'] ) ? $data['type'] : 'season',
        ];

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $wpdb->prefix . 'rt_rates', $record, [ 'id' => absint( $data['id'] ) ] );
            return $data['id'];
        }

        $wpdb->insert( $wpdb->prefix . 'rt_rates', $record );
        return $wpdb->insert_id;
    }

    // Obtener tarifas de una habitación
    public function get_rates( $room_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rt_rates WHERE room_id = %d ORDER BY date_from ASC",
            absint( $room_id )
        ) );
    }

    // Eliminar tarifa
    public function delete_rate( $rate_id ) {
        global $wpdb;
        return $wpdb->delete( $wpdb->prefix . 'rt_rates', [ 'id' => absint( $rate_id ) ] );
    }
}
