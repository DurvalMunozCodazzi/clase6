<?php
/**
 * Módulo de horóscopo: recibe los datos generados por la app local
 * (horoscopo-diario) vía REST, los guarda, y los muestra con el
 * shortcode [horoscopo].
 */

if (!defined('ABSPATH')) {
    exit;
}

const TDT_HOROSCOPO_SIGNOS = array(
    'aries'       => array('nombre' => 'Aries',       'simbolo' => '♈'),
    'tauro'       => array('nombre' => 'Tauro',       'simbolo' => '♉'),
    'geminis'     => array('nombre' => 'Géminis',     'simbolo' => '♊'),
    'cancer'      => array('nombre' => 'Cáncer',      'simbolo' => '♋'),
    'leo'         => array('nombre' => 'Leo',         'simbolo' => '♌'),
    'virgo'       => array('nombre' => 'Virgo',       'simbolo' => '♍'),
    'libra'       => array('nombre' => 'Libra',       'simbolo' => '♎'),
    'escorpio'    => array('nombre' => 'Escorpio',    'simbolo' => '♏'),
    'sagitario'   => array('nombre' => 'Sagitario',   'simbolo' => '♐'),
    'capricornio' => array('nombre' => 'Capricornio', 'simbolo' => '♑'),
    'acuario'     => array('nombre' => 'Acuario',     'simbolo' => '♒'),
    'piscis'      => array('nombre' => 'Piscis',      'simbolo' => '♓'),
);

const TDT_HOROSCOPO_SECCIONES = array('💼 En el trabajo', '❤️ En el amor', '⚠️ La advertencia final', '🌟 Síntesis del día');

/* ===================== TOKEN ===================== */

function tdt_horoscopo_token() {
    $token = get_option('tdt_horoscopo_token');
    if (!$token) {
        $token = wp_generate_password(32, false);
        update_option('tdt_horoscopo_token', $token);
    }
    return $token;
}

function tdt_horoscopo_regenerar_token() {
    $token = wp_generate_password(32, false);
    update_option('tdt_horoscopo_token', $token);
    return $token;
}

/* ===================== GUARDAR / VALIDAR PAYLOAD ===================== */

function tdt_horoscopo_validar_dia($dia) {
    if (!is_array($dia) || empty($dia['fecha']) || empty($dia['signos']) || !is_array($dia['signos'])) {
        return false;
    }
    foreach (array_keys(TDT_HOROSCOPO_SIGNOS) as $clave) {
        if (!isset($dia['signos'][$clave])) {
            return false;
        }
    }
    return true;
}

function tdt_horoscopo_sanear_dia($dia) {
    $limpio = array(
        'fecha' => sanitize_text_field($dia['fecha']),
        'panorama' => isset($dia['panorama']) ? sanitize_textarea_field($dia['panorama']) : '',
        'signos' => array(),
    );
    foreach (array_keys(TDT_HOROSCOPO_SIGNOS) as $clave) {
        $limpio['signos'][$clave] = wp_kses_post($dia['signos'][$clave]);
    }
    return $limpio;
}

function tdt_horoscopo_guardar($payload) {
    if (!is_array($payload) || !tdt_horoscopo_validar_dia($payload['hoy'] ?? null) || !tdt_horoscopo_validar_dia($payload['manana'] ?? null)) {
        return new WP_Error('tdt_payload_invalido', 'El JSON no tiene el formato esperado (hoy/manana con fecha, panorama y los 12 signos).', array('status' => 400));
    }
    update_option('tdt_horoscopo_hoy', tdt_horoscopo_sanear_dia($payload['hoy']));
    update_option('tdt_horoscopo_manana', tdt_horoscopo_sanear_dia($payload['manana']));
    update_option('tdt_horoscopo_actualizado', current_time('mysql'));
    return array(
        'fecha_hoy' => $payload['hoy']['fecha'],
        'fecha_manana' => $payload['manana']['fecha'],
    );
}

/* ===================== REST ===================== */

add_action('rest_api_init', function () {
    register_rest_route('tirada-de-tarot/v1', '/horoscopo', array(
        'methods' => 'POST',
        'callback' => 'tdt_horoscopo_rest_recibir',
        'permission_callback' => '__return_true',
    ));
});

function tdt_horoscopo_rest_recibir(WP_REST_Request $request) {
    $token_recibido = $request->get_header('x-tdt-token');
    if (!$token_recibido) {
        $token_recibido = $request->get_param('token');
    }
    if (!$token_recibido || !hash_equals(tdt_horoscopo_token(), (string) $token_recibido)) {
        return new WP_Error('tdt_token_invalido', 'Token inválido o faltante.', array('status' => 401));
    }

    $payload = $request->get_json_params();
    $resultado = tdt_horoscopo_guardar($payload);
    if (is_wp_error($resultado)) {
        return $resultado;
    }
    return rest_ensure_response($resultado);
}

/* ===================== PARSER DEL TEXTO ANALÍTICO ===================== */

function tdt_horoscopo_inline_esc($texto) {
    return esc_html(trim($texto));
}

function tdt_horoscopo_render_sintesis($bloque) {
    $lineas = array_values(array_filter(array_map('trim', explode("\n", $bloque))));
    $lineas_tabla = array_values(array_filter($lineas, function ($l) { return strpos($l, '|') === 0; }));
    $lineas_texto = array_values(array_filter($lineas, function ($l) { return strpos($l, '|') !== 0; }));

    $html = '';
    if (count($lineas_tabla) >= 2) {
        $parsear_fila = function ($linea) {
            $celdas = explode('|', $linea);
            $celdas = array_map('trim', $celdas);
            // Quita los elementos vacíos de los bordes ("| a | b |" arranca y termina con "").
            if (count($celdas) && $celdas[0] === '') array_shift($celdas);
            if (count($celdas) && end($celdas) === '') array_pop($celdas);
            return $celdas;
        };
        $encabezado = $parsear_fila($lineas_tabla[0]);
        $filas = array_slice($lineas_tabla, 2);

        $html .= '<table class="tdt-sintesis-table"><thead><tr>';
        foreach ($encabezado as $h) {
            $html .= '<th>' . tdt_horoscopo_inline_esc($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($filas as $fila_txt) {
            $celdas = $parsear_fila($fila_txt);
            $html .= '<tr>';
            foreach ($celdas as $c) {
                $html .= '<td>' . tdt_horoscopo_inline_esc($c) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    }
    if (!empty($lineas_texto)) {
        $html .= '<p>' . tdt_horoscopo_inline_esc(implode(' ', $lineas_texto)) . '</p>';
    }
    return $html;
}

function tdt_horoscopo_render_texto($texto) {
    $posiciones = array();
    foreach (TDT_HOROSCOPO_SECCIONES as $marcador) {
        $idx = mb_strpos($texto, $marcador);
        if ($idx !== false) {
            $posiciones[] = array('marcador' => $marcador, 'idx' => $idx);
        }
    }

    if (empty($posiciones)) {
        return '<p>' . tdt_horoscopo_inline_esc($texto) . '</p>';
    }

    usort($posiciones, function ($a, $b) { return $a['idx'] <=> $b['idx']; });

    $html = '';
    $total = count($posiciones);
    for ($i = 0; $i < $total; $i++) {
        $marcador = $posiciones[$i]['marcador'];
        $inicio = $posiciones[$i]['idx'] + mb_strlen($marcador);
        $fin = ($i + 1 < $total) ? $posiciones[$i + 1]['idx'] : mb_strlen($texto);
        $cuerpo = trim(mb_substr($texto, $inicio, $fin - $inicio));

        $html .= '<h4>' . esc_html($marcador) . '</h4>';
        $html .= (strpos($marcador, 'Síntesis') !== false)
            ? tdt_horoscopo_render_sintesis($cuerpo)
            : '<p>' . tdt_horoscopo_inline_esc($cuerpo) . '</p>';
    }
    return $html;
}

/* ===================== SHORTCODE ===================== */

function tdt_horoscopo_enqueue_assets() {
    wp_enqueue_style('tdt-horoscopo-style', TDT_PLUGIN_URL . 'assets/css/horoscopo.css', array(), TDT_VERSION);
    wp_enqueue_script('tdt-horoscopo-script', TDT_PLUGIN_URL . 'assets/js/horoscopo.js', array(), TDT_VERSION, true);
}

function tdt_horoscopo_shortcode() {
    $hoy = get_option('tdt_horoscopo_hoy');
    $manana = get_option('tdt_horoscopo_manana');

    if (!$hoy || !$manana) {
        return '<p class="tdt-horoscopo-vacio">Todavía no se cargó el horóscopo de hoy.</p>';
    }

    tdt_horoscopo_enqueue_assets();

    $dias = array('hoy' => $hoy, 'manana' => $manana);
    $primer_signo = array_key_first(TDT_HOROSCOPO_SIGNOS);

    ob_start();
    echo tdt_header_image_html();
    ?>
    <div class="tdt-horoscopo" id="tdtHoroscopo">
      <div class="tdt-horoscopo-tabs">
        <button type="button" class="tdt-tab active" data-dia="hoy">Hoy</button>
        <button type="button" class="tdt-tab" data-dia="manana">Mañana</button>
      </div>

      <?php foreach ($dias as $dia_key => $dia_data): ?>
        <p class="tdt-panorama<?php echo $dia_key === 'hoy' ? ' active' : ''; ?>" data-panorama="<?php echo esc_attr($dia_key); ?>">
          <?php echo esc_html($dia_data['fecha']); ?> — <?php echo esc_html($dia_data['panorama']); ?>
        </p>
      <?php endforeach; ?>

      <div class="tdt-signos-selector">
        <?php foreach (TDT_HOROSCOPO_SIGNOS as $clave => $info): ?>
          <button type="button" class="tdt-signo-btn<?php echo $clave === $primer_signo ? ' active' : ''; ?>" data-signo="<?php echo esc_attr($clave); ?>">
            <span class="tdt-signo-simbolo"><?php echo esc_html($info['simbolo']); ?></span>
            <?php echo esc_html($info['nombre']); ?>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="tdt-signo-contenido-wrap">
        <?php foreach ($dias as $dia_key => $dia_data): ?>
          <?php foreach (TDT_HOROSCOPO_SIGNOS as $clave => $info): ?>
            <div class="tdt-signo-contenido<?php echo ($dia_key === 'hoy' && $clave === $primer_signo) ? ' active' : ''; ?>"
                 data-dia="<?php echo esc_attr($dia_key); ?>" data-signo="<?php echo esc_attr($clave); ?>">
              <h3><?php echo esc_html($info['simbolo'] . ' ' . $info['nombre']); ?></h3>
              <?php echo tdt_horoscopo_render_texto($dia_data['signos'][$clave]); ?>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('horoscopo', 'tdt_horoscopo_shortcode');

/* ===================== PANEL DE ADMINISTRACIÓN ===================== */

add_action('admin_menu', function () {
    add_menu_page(
        'Tirada de Tarot',
        'Tirada de Tarot',
        'manage_options',
        'tirada-de-tarot',
        'tdt_admin_page',
        'dashicons-star-filled',
        58
    );
});

function tdt_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $mensaje = tdt_significados_admin_procesar();

    if (isset($_POST['tdt_regenerar_token']) && check_admin_referer('tdt_regenerar_token_action')) {
        tdt_horoscopo_regenerar_token();
        $mensaje = '<div class="notice notice-success"><p>Token regenerado. Actualizalo en la app de Mac.</p></div>';
    }

    if (isset($_POST['tdt_guardar_ajustes']) && check_admin_referer('tdt_guardar_ajustes_action')) {
        $imagen = esc_url_raw(wp_unslash($_POST['tdt_header_image'] ?? ''));
        update_option('tdt_header_image', $imagen);
        $mensaje = $imagen
            ? '<div class="notice notice-success"><p>Imagen de cabecera guardada. Ya se muestra arriba de ambos widgets.</p></div>'
            : '<div class="notice notice-success"><p>Imagen de cabecera quitada.</p></div>';
    }

    if (isset($_POST['tdt_importar_json']) && check_admin_referer('tdt_importar_json_action')) {
        $json_crudo = wp_unslash($_POST['tdt_json'] ?? '');
        $payload = json_decode($json_crudo, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $mensaje = '<div class="notice notice-error"><p>El JSON no es válido: ' . esc_html(json_last_error_msg()) . '</p></div>';
        } else {
            $resultado = tdt_horoscopo_guardar($payload);
            if (is_wp_error($resultado)) {
                $mensaje = '<div class="notice notice-error"><p>' . esc_html($resultado->get_error_message()) . '</p></div>';
            } else {
                $mensaje = '<div class="notice notice-success"><p>Horóscopo importado: ' . esc_html($resultado['fecha_hoy']) . ' (hoy) y ' . esc_html($resultado['fecha_manana']) . ' (mañana).</p></div>';
            }
        }
    }

    $token = tdt_horoscopo_token();
    $site_url = home_url('/');
    $actualizado = get_option('tdt_horoscopo_actualizado');
    $hoy = get_option('tdt_horoscopo_hoy');
    $manana = get_option('tdt_horoscopo_manana');
    ?>
    <div class="wrap">
      <h1>🔮 Tirada de Tarot</h1>
      <?php echo $mensaje; ?>

      <h2>Ajustes</h2>

      <h3>Shortcodes para insertar en tu web (Divi, Gutenberg o cualquier editor)</h3>
      <p>Copiá el shortcode y pegalo en un módulo de <strong>Código</strong> o <strong>Texto</strong> de Divi, en la página donde quieras que aparezca.</p>
      <table class="form-table">
        <tr>
          <th scope="row">Tirada de tarot (3 cartas)</th>
          <td><input type="text" readonly value="[tirada_tarot]" style="width:250px;font-family:monospace;" onclick="this.select();document.execCommand('copy');"> <em>clic para copiar</em></td>
        </tr>
        <tr>
          <th scope="row">Horóscopo diario (12 signos)</th>
          <td><input type="text" readonly value="[horoscopo]" style="width:250px;font-family:monospace;" onclick="this.select();document.execCommand('copy');"> <em>clic para copiar</em></td>
        </tr>
      </table>

      <h3>Imagen de cabecera</h3>
      <p>Se muestra arriba de ambos widgets. Subí tu imagen a <strong>Medios → Añadir nuevo</strong>, copiá su URL (botón "Copiar URL al portapapeles" en la biblioteca) y pegala acá. Dejá el campo vacío y guardá para quitarla.</p>
      <form method="post">
        <?php wp_nonce_field('tdt_guardar_ajustes_action'); ?>
        <input type="url" name="tdt_header_image" value="<?php echo esc_attr(get_option('tdt_header_image', '')); ?>" placeholder="https://tusitio.com/wp-content/uploads/2026/07/mi-imagen.jpg" style="width:520px;">
        <button type="submit" name="tdt_guardar_ajustes" value="1" class="button button-primary">Guardar ajustes</button>
      </form>
      <?php $imagen_actual = get_option('tdt_header_image'); if ($imagen_actual): ?>
        <p style="margin-top:10px;"><img src="<?php echo esc_url($imagen_actual); ?>" alt="Vista previa de la cabecera" style="max-width:420px;height:auto;border-radius:8px;"></p>
      <?php endif; ?>

      <hr>

      <?php tdt_significados_admin_section(); ?>

      <h2>Conexión con las apps locales</h2>
      <p>Pegá estos dos datos en la app <code>horoscopo-diario</code> de tu Mac, sección "Configuración de envío a WordPress".</p>
      <table class="form-table">
        <tr>
          <th scope="row">URL del sitio</th>
          <td><input type="text" readonly value="<?php echo esc_attr($site_url); ?>" style="width:400px;" onclick="this.select();"></td>
        </tr>
        <tr>
          <th scope="row">Token</th>
          <td><input type="text" readonly value="<?php echo esc_attr($token); ?>" style="width:400px;" onclick="this.select();"></td>
        </tr>
      </table>
      <form method="post">
        <?php wp_nonce_field('tdt_regenerar_token_action'); ?>
        <button type="submit" name="tdt_regenerar_token" value="1" class="button" onclick="return confirm('Esto invalida el token actual — vas a tener que actualizarlo en la app de Mac. ¿Continuar?');">Regenerar token</button>
      </form>

      <hr>

      <h2>Estado actual</h2>
      <?php if ($hoy && $manana): ?>
        <p>Último envío recibido: <strong><?php echo esc_html($actualizado ?: '—'); ?></strong></p>
        <p>Hoy (<?php echo esc_html($hoy['fecha']); ?>) y Mañana (<?php echo esc_html($manana['fecha']); ?>) cargados correctamente.</p>
        <p>Usá el shortcode <code>[horoscopo]</code> en cualquier página o entrada para mostrarlo.</p>
      <?php else: ?>
        <p>Todavía no se recibió ningún horóscopo.</p>
      <?php endif; ?>

      <hr>

      <h2>Importar manualmente (si "Enviar directo a WordPress" no funciona)</h2>
      <p>Pegá acá el JSON que copia la app de Mac con el botón "Copiar JSON para WordPress".</p>
      <form method="post">
        <?php wp_nonce_field('tdt_importar_json_action'); ?>
        <textarea name="tdt_json" rows="10" style="width:100%;font-family:monospace;" placeholder='{"hoy": {...}, "manana": {...}}'></textarea>
        <p><button type="submit" name="tdt_importar_json" value="1" class="button button-primary">Importar</button></p>
      </form>
    </div>
    <?php
}
