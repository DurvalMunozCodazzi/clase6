<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Funciones compartidas entre vistas del panel admin (dashboard, listado de
// reservas, detalle de reserva), para que estén disponibles sin importar
// qué vista se cargue primero en cada request.

if ( ! function_exists( 'rt_booking_status_badge' ) ) {
	function rt_booking_status_badge( $status ) {
		$labels = [
			'pending'   => [ 'Pendiente',   '#fb8c00', '#fff3e0' ],
			'confirmed' => [ 'Confirmada',  '#2e7d32', '#e8f5e9' ],
			'cancelled' => [ 'Cancelada',   '#c62828', '#ffebee' ],
			'completed' => [ 'Completada',  '#1565c0', '#e3f2fd' ],
		];
		$l = $labels[ $status ] ?? [ $status, '#888', '#f5f5f5' ];
		return sprintf(
			'<span style="background:%s;color:%s;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:500;">%s</span>',
			esc_attr( $l[2] ), esc_attr( $l[1] ), esc_html( $l[0] )
		);
	}
}

if ( ! function_exists( 'rt_payment_badge' ) ) {
	function rt_payment_badge( $status ) {
		$labels = [
			'pending'  => [ 'Pendiente', '#fb8c00', '#fff3e0' ],
			'paid'     => [ 'Pagado',    '#2e7d32', '#e8f5e9' ],
			'failed'   => [ 'Fallido',   '#c62828', '#ffebee' ],
			'partial'  => [ 'Parcial',   '#1565c0', '#e3f2fd' ],
			'refunded' => [ 'Reembolso', '#6a1b9a', '#f3e5f5' ],
		];
		$l = $labels[ $status ] ?? [ $status, '#888', '#f5f5f5' ];
		return sprintf(
			'<span style="background:%s;color:%s;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:500;">%s</span>',
			esc_attr( $l[2] ), esc_attr( $l[1] ), esc_html( $l[0] )
		);
	}
}
