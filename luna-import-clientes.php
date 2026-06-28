<?php
/**
 * Script de importación de clientes — USO ÚNICO
 * Subir al directorio raíz de WordPress, abrir en el navegador
 * estando logueado como administrador de WordPress.
 * BORRAR DEL SERVIDOR INMEDIATAMENTE DESPUÉS DE USARLO.
 */

// Bootstrap WordPress
$root = __DIR__;
while ( ! file_exists( $root . '/wp-load.php' ) && strlen( $root ) > 3 ) {
    $root = dirname( $root );
}
if ( ! file_exists( $root . '/wp-load.php' ) ) {
    die( 'No se encontró wp-load.php. Colocá el archivo en la raíz de WordPress.' );
}
require_once $root . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Acceso denegado. Iniciá sesión en WordPress como administrador primero.' );
}

global $wpdb;
$p = $wpdb->prefix . 'luna_';
$table = "{$p}clients";

// ── Agregar columnas nuevas si no existen ─────────────────────────────────────
$add_col = function ( $col, $def ) use ( $wpdb, $table ) {
    $exists = $wpdb->get_var( "SHOW COLUMNS FROM `{$table}` LIKE '$col'" );
    if ( ! $exists ) {
        $wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN $col $def" );
    }
};
$add_col( 'domain',         "VARCHAR(200) NOT NULL DEFAULT '' AFTER `name`" );
$add_col( 'renewal_date',   "DATE DEFAULT NULL" );
$add_col( 'renewal_amount', "DECIMAL(10,2) DEFAULT 0.00" );

// ── Datos importados del CSV ──────────────────────────────────────────────────
// [ nombre, dominio, renewal_date, renewal_amount, notas ]
$clientes = [
    [ 'torcuatoenglish.com.ar',          'torcuatoenglish.com.ar',          '2026-01-15', 340000,   'PAGO'  ],
    [ 'doctoresadrogue.com.ar',           'doctoresadrogue.com.ar',           '2026-01-29', 340000,   'PAGO'  ],
    [ 'Publicidad Adrogue',               '',                                 '2026-01-05', 345000,   'PAGO'  ],
    [ 'Rediseño web Adrogue',             '',                                 null,         1160000,  'PAGO'  ],
    [ 'escuadronfenix.org.ar',            'escuadronfenix.org.ar',            '2026-02-12', 330000,   'PAGO'  ],
    [ 'estheticfresh.com.ar',             'estheticfresh.com.ar',             '2026-02-03', 149000,   'PAGO'  ],
    [ 'colegiodontorcuato.edu.ar',        'colegiodontorcuato.edu.ar',        '2026-02-24', 330000,   'PAGO'  ],
    [ 'arquenna.com.ar',                  'arquenna.com.ar',                  '2026-03-01', 330000,   'PAGO'  ],
    [ 'villaindumentaria.com.ar',         'villaindumentaria.com.ar',         '2026-03-03', 330000,   'PAGO'  ],
    [ 'masterenservicios.com.ar',         'masterenservicios.com.ar',         '2026-03-15', 330000,   'PAGO'  ],
    [ 'colchoneriasanmartin.com.ar',      'colchoneriasanmartin.com.ar',      '2026-03-15', 330000,   'PAGO'  ],
    [ 'asegpp.com.ar',                    'asegpp.com.ar',                    '2026-03-16', 330000,   'PAGO'  ],
    [ 'neumaticosposadas.com.ar',         'neumaticosposadas.com.ar',         '2026-04-01', 330000,   'PAGO'  ],
    [ 'gastrotecnica.com',                'gastrotecnica.com',                '2026-04-05', 330000,   'PAGO'  ],
    [ 'epinac.com',                       'epinac.com',                       '2026-04-06', 330000,   'PAGO'  ],
    [ 'bellouk.com.ar',                   'bellouk.com.ar',                   '2026-04-07', 330000,   'PAGO'  ],
    [ 'farli.com',                        'farli.com',                        '2026-04-09', 330000,   'PAGO'  ],
    [ 'indumentariatomy.com.ar',          'indumentariatomy.com.ar',          '2026-04-22', 330000,   'PAGO'  ],
    [ 'colchoneriabelen.com.ar',          'colchoneriabelen.com.ar',          '2026-05-02', 89000,    'PAGO'  ],
    [ 'trasumetzincados.com.ar',          'trasumetzincados.com.ar',          '2026-05-07', 330000,   'PAGO'  ],
    [ 'colchonesamalfi.com.ar',           'colchonesamalfi.com.ar',           '2026-05-16', 330000,   'DEBE'  ],
    [ 'brasalenachurrasqueria.com.ar',    'brasalenachurrasqueria.com.ar',    '2026-05-23', 330000,   'PAGO'  ],
    [ 'doctoresadrogue.com',              'doctoresadrogue.com',              '2026-07-13', 330000,   'DEBE'  ],
    [ 'accordpropiedades.com.ar',         'accordpropiedades.com.ar',         '2026-08-13', 330000,   'DEBE'  ],
    [ 'nanys.com.ar',                     'nanys.com.ar',                     '2026-08-13', 330000,   'DEBE'  ],
    [ 'millasa.com.ar',                   'millasa.com.ar',                   '2026-08-16', 330000,   'DEBE'  ],
    [ 'mintech.com.ar',                   'mintech.com.ar',                   '2026-08-23', 330000,   'DEBE'  ],
    [ 'omargentina.com',                  'omargentina.com',                  '2026-08-23', 330000,   'DEBE'  ],
    [ 'prestorapido.com.ar',              'prestorapido.com.ar',              '2026-08-11', 330000,   'DEBE'  ],
    [ 'heladeriasancayetnano.com.ar',     'heladeriasancayetnano.com.ar',     '2026-09-04', 330000,   'DEBE'  ],
    [ 'atmcargo.com.ar',                  'atmcargo.com.ar',                  '2026-09-11', 330000,   'DEBE'  ],
    [ 'kopruchconstrucciones.com.ar',     'kopruchconstrucciones.com.ar',     '2026-09-20', 330000,   'DEBE'  ],
    [ 'arquenna.com',                     'arquenna.com',                     '2026-10-03', 330000,   'DEBE'  ],
    [ 'iraola.com.ar',                    'iraola.com.ar',                    '2026-10-10', 330000,   'DEBE'  ],
    [ 'cercomat.com.ar',                  'cercomat.com.ar',                  '2026-10-29', 330000,   'DEBE'  ],
    [ 'cenemi.com',                       'cenemi.com',                       '2026-11-29', 330000,   'DEBE'  ],
    [ 'dahuasa.com.ar',                   'dahuasa.com.ar',                   '2026-11-29', 330000,   'DEBE'  ],
    [ 'ladelfi.com.ar',                   'ladelfi.com.ar',                   '2026-12-11', 630000,   'DEBE'  ],
    [ 'torcuatoenglishcenter.com.ar',     'torcuatoenglishcenter.com.ar',     '2026-12-13', 330000,   'DEBE'  ],
];

// ── Importar ──────────────────────────────────────────────────────────────────
$importados = 0;
$omitidos   = 0;
$log        = [];

foreach ( $clientes as $c ) {
    $nombre = $c[0];
    $exists = $wpdb->get_var(
        $wpdb->prepare( "SELECT id FROM `{$table}` WHERE name = %s LIMIT 1", $nombre )
    );
    if ( $exists ) {
        $omitidos++;
        $log[] = [ 'skip', $nombre ];
        continue;
    }
    $res = $wpdb->insert( $table, [
        'name'           => $nombre,
        'domain'         => $c[1],
        'renewal_date'   => $c[2],
        'renewal_amount' => $c[3],
        'notes'          => $c[4],
        'active'         => 1,
        'created_at'     => current_time( 'mysql' ),
    ] );
    if ( $res ) {
        $importados++;
        $log[] = [ 'ok', $nombre ];
    } else {
        $log[] = [ 'err', $nombre . ' — ' . $wpdb->last_error ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Importación de Clientes</title>
<style>
  body { font-family: -apple-system, sans-serif; padding: 40px; max-width: 700px; margin: 0 auto; background: #f5f5f5; }
  .card { background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  h2 { margin-top: 0; }
  .stats { display: flex; gap: 20px; margin: 20px 0; }
  .stat { flex: 1; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; text-align: center; }
  .stat.skip { background: #fefce8; border-color: #fde68a; }
  .stat big { display: block; font-size: 36px; font-weight: 700; color: #16a34a; }
  .stat.skip big { color: #d97706; }
  .warn { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px; margin: 20px 0; color: #dc2626; font-weight: 600; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 16px; }
  th { background: #f3f4f6; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; }
  td { padding: 7px 10px; border-bottom: 1px solid #f0f0f0; }
  .ok { color: #16a34a; font-weight: 700; }
  .sk { color: #d97706; }
  .er { color: #dc2626; }
</style>
</head>
<body>
<div class="card">
  <h2>✅ Importación de Clientes — Luna Workspace</h2>
  <div class="stats">
    <div class="stat"><big><?= $importados ?></big>Importados</div>
    <div class="stat skip"><big><?= $omitidos ?></big>Ya existían</div>
  </div>
  <div class="warn">⚠️ <strong>Borrá este archivo del servidor ahora.</strong><br>Ruta: <?= __FILE__ ?></div>
  <table>
    <thead><tr><th>Estado</th><th>Cliente</th></tr></thead>
    <tbody>
    <?php foreach ( $log as $l ) : ?>
      <tr>
        <td class="<?= $l[0] === 'ok' ? 'ok' : ( $l[0] === 'skip' ? 'sk' : 'er' ) ?>">
          <?= $l[0] === 'ok' ? '✓ Importado' : ( $l[0] === 'skip' ? '— Omitido' : '✗ Error' ) ?>
        </td>
        <td><?= esc_html( $l[1] ) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
