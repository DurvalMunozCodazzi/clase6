<?php
/* ─── availability-calendar.php ─────────────────────────── */
// Espera $room_id y $occupied (array de fechas 'Y-m-d') desde class-rt-public.php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="rt-availability-wrap" data-room-id="<?php echo esc_attr( $room_id ); ?>">
    <div class="rt-availability-legend" style="margin-bottom:12px;display:flex;gap:20px;font-size:13px;">
        <span><span style="display:inline-block;width:14px;height:14px;background:#ffcdd2;border-radius:3px;vertical-align:middle;margin-right:4px;"></span>Ocupado</span>
        <span><span style="display:inline-block;width:14px;height:14px;background:#c8e6c9;border-radius:3px;vertical-align:middle;margin-right:4px;"></span>Disponible</span>
    </div>
    <div class="rt-availability-grid" style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;overflow-x:auto;">
        <?php
        $today = new DateTime( 'today' );
        for ( $m = 0; $m < 2; $m++ ) :
            $month_start = ( clone $today )->modify( "first day of +{$m} month" );
            $month_name  = date_i18n( 'F Y', $month_start->getTimestamp() );
            $days_in_month = (int) $month_start->format( 't' );
            $first_weekday = (int) $month_start->format( 'N' ); // 1=lunes .. 7=domingo
        ?>
        <div style="display:inline-block;vertical-align:top;margin:0 24px 20px 0;">
            <div style="font-weight:600;font-size:15px;margin-bottom:8px;"><?php echo esc_html( $month_name ); ?></div>
            <table style="border-collapse:collapse;">
                <tr>
                    <?php foreach ( [ 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do' ] as $day_label ) : ?>
                    <th style="width:34px;text-align:center;font-size:12px;color:#888;padding:4px;"><?php echo esc_html( $day_label ); ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <?php for ( $i = 1; $i < $first_weekday; $i++ ) : ?>
                    <td></td>
                    <?php endfor; ?>
                    <?php
                    $col = $first_weekday - 1;
                    for ( $day = 1; $day <= $days_in_month; $day++ ) :
                        $date_obj = ( clone $month_start )->modify( ( $day - 1 ) . ' days' );
                        $date_str = $date_obj->format( 'Y-m-d' );
                        $is_past  = $date_obj < $today;
                        $is_occ   = in_array( $date_str, $occupied, true );
                        $bg    = $is_past ? '#f5f5f5' : ( $is_occ ? '#ffcdd2' : '#c8e6c9' );
                        $color = $is_past ? '#bbb'    : ( $is_occ ? '#b71c1c' : '#1b5e20' );
                    ?>
                    <td style="text-align:center;padding:2px;">
                        <div style="width:30px;height:30px;line-height:30px;border-radius:4px;font-size:13px;background:<?php echo esc_attr( $bg ); ?>;color:<?php echo esc_attr( $color ); ?>;">
                            <?php echo (int) $day; ?>
                        </div>
                    </td>
                    <?php
                        $col++;
                        if ( $col % 7 === 0 && $day < $days_in_month ) echo '</tr><tr>';
                    endfor;
                    ?>
                </tr>
            </table>
        </div>
        <?php endfor; ?>
    </div>
</div>
