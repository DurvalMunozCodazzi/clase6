<?php
/**
 * Base de significados curados por carta (reconstrucción del módulo de la
 * v1.4.0, sembrado con los datos exportados de esa versión).
 *
 * - Se guardan en la opción `tdt_significados` con esta forma:
 *   { "el-loco": { "up": ["...", ...], "rev": ["...", ...] }, ... }
 * - El widget web los recibe vía TDT_CONFIG.significados y los usa en las
 *   lecturas en lugar de las palabras clave genéricas cuando existen.
 * - La app local "Tarot Personal" puede pedirlos por REST (GET, con token).
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ===================== NOMBRES DE CARTAS ===================== */

function tdt_cartas_lista() {
    $mayores = array(
        'el-loco' => '0. El Loco', 'el-mago' => 'I. El Mago', 'la-sacerdotisa' => 'II. La Sacerdotisa',
        'la-emperatriz' => 'III. La Emperatriz', 'el-emperador' => 'IV. El Emperador',
        'el-hierofante' => 'V. El Hierofante', 'los-enamorados' => 'VI. Los Enamorados',
        'el-carro' => 'VII. El Carro', 'la-fuerza' => 'VIII. La Fuerza', 'el-ermitano' => 'IX. El Ermitaño',
        'la-rueda' => 'X. La Rueda de la Fortuna', 'la-justicia' => 'XI. La Justicia',
        'el-colgado' => 'XII. El Colgado', 'la-muerte' => 'XIII. La Muerte',
        'la-templanza' => 'XIV. La Templanza', 'el-diablo' => 'XV. El Diablo',
        'la-torre' => 'XVI. La Torre', 'la-estrella' => 'XVII. La Estrella',
        'la-luna' => 'XVIII. La Luna', 'el-sol' => 'XIX. El Sol',
        'el-juicio' => 'XX. El Juicio', 'el-mundo' => 'XXI. El Mundo',
    );
    $palos = array('bastos' => 'Bastos', 'copas' => 'Copas', 'espadas' => 'Espadas', 'oros' => 'Oros');
    $rangos = array(
        'as' => 'As', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7',
        '8' => '8', '9' => '9', '10' => '10', 'paje' => 'Paje', 'caballo' => 'Caballo',
        'reina' => 'Reina', 'rey' => 'Rey',
    );
    $lista = $mayores;
    foreach ($palos as $palo_key => $palo_nombre) {
        foreach ($rangos as $rango_key => $rango_nombre) {
            $lista[$palo_key . '-' . $rango_key] = $rango_nombre . ' de ' . $palo_nombre;
        }
    }
    return $lista;
}

/* ===================== DATOS ===================== */

function tdt_significados() {
    $datos = get_option('tdt_significados');
    if (!is_array($datos) || empty($datos)) {
        // Primera vez: sembrar con los datos curados que vienen con el plugin.
        $semilla_path = TDT_PLUGIN_PATH . 'data/significados-iniciales.json';
        $datos = array();
        if (file_exists($semilla_path)) {
            $decodificado = json_decode(file_get_contents($semilla_path), true);
            if (is_array($decodificado)) {
                $datos = $decodificado;
            }
        }
        update_option('tdt_significados', $datos);
    }
    return $datos;
}

function tdt_significados_agregar($carta, $orientacion, $texto) {
    $cartas_validas = tdt_cartas_lista();
    if (!isset($cartas_validas[$carta])) {
        return new WP_Error('tdt_carta_invalida', 'Carta no reconocida.');
    }
    $clave = $orientacion === 'rev' ? 'rev' : 'up';
    $texto = sanitize_textarea_field($texto);
    if ($texto === '') {
        return new WP_Error('tdt_texto_vacio', 'El significado está vacío.');
    }
    $datos = tdt_significados();
    if (!isset($datos[$carta])) {
        $datos[$carta] = array('up' => array(), 'rev' => array());
    }
    if (in_array($texto, $datos[$carta][$clave], true)) {
        return new WP_Error('tdt_duplicado', 'Ese significado ya estaba cargado para esa carta.');
    }
    $datos[$carta][$clave][] = $texto;
    update_option('tdt_significados', $datos);
    return true;
}

function tdt_significados_importar($json_crudo) {
    $nuevo = json_decode($json_crudo, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($nuevo)) {
        return new WP_Error('tdt_json_invalido', 'El JSON no es válido: ' . json_last_error_msg());
    }
    $cartas_validas = tdt_cartas_lista();
    $datos = tdt_significados();
    $agregados = 0;
    foreach ($nuevo as $carta => $contenido) {
        if (!isset($cartas_validas[$carta]) || !is_array($contenido)) {
            continue;
        }
        if (!isset($datos[$carta])) {
            $datos[$carta] = array('up' => array(), 'rev' => array());
        }
        foreach (array('up', 'rev') as $clave) {
            if (empty($contenido[$clave]) || !is_array($contenido[$clave])) {
                continue;
            }
            foreach ($contenido[$clave] as $texto) {
                $texto = sanitize_textarea_field((string) $texto);
                if ($texto !== '' && !in_array($texto, $datos[$carta][$clave], true)) {
                    $datos[$carta][$clave][] = $texto;
                    $agregados++;
                }
            }
        }
    }
    update_option('tdt_significados', $datos);
    return $agregados;
}

function tdt_significados_conteo() {
    $datos = tdt_significados();
    $con_contenido = 0;
    foreach ($datos as $contenido) {
        if (!empty($contenido['up']) || !empty($contenido['rev'])) {
            $con_contenido++;
        }
    }
    return $con_contenido;
}

/* ===================== REST (para la app "Tarot Personal" de la Mac) ===================== */

add_action('rest_api_init', function () {
    register_rest_route('tirada-de-tarot/v1', '/significados', array(
        'methods' => 'GET',
        'callback' => 'tdt_significados_rest_leer',
        'permission_callback' => '__return_true',
    ));
});

function tdt_significados_rest_leer(WP_REST_Request $request) {
    $token_recibido = $request->get_header('x-tdt-token');
    if (!$token_recibido) {
        $token_recibido = $request->get_param('token');
    }
    if (!$token_recibido || !hash_equals(tdt_horoscopo_token(), (string) $token_recibido)) {
        return new WP_Error('tdt_token_invalido', 'Token inválido o faltante.', array('status' => 401));
    }
    return rest_ensure_response(tdt_significados());
}

/* ===================== SECCIÓN DEL PANEL ADMIN ===================== */

function tdt_significados_admin_procesar() {
    $mensaje = '';

    if (isset($_POST['tdt_agregar_significado']) && check_admin_referer('tdt_agregar_significado_action')) {
        $resultado = tdt_significados_agregar(
            sanitize_text_field(wp_unslash($_POST['tdt_sig_carta'] ?? '')),
            sanitize_text_field(wp_unslash($_POST['tdt_sig_orientacion'] ?? 'up')),
            wp_unslash($_POST['tdt_sig_texto'] ?? '')
        );
        $mensaje = is_wp_error($resultado)
            ? '<div class="notice notice-error"><p>' . esc_html($resultado->get_error_message()) . '</p></div>'
            : '<div class="notice notice-success"><p>Significado agregado.</p></div>';
    }

    if (isset($_POST['tdt_importar_significados']) && check_admin_referer('tdt_importar_significados_action')) {
        $resultado = tdt_significados_importar(wp_unslash($_POST['tdt_sig_json'] ?? ''));
        $mensaje = is_wp_error($resultado)
            ? '<div class="notice notice-error"><p>' . esc_html($resultado->get_error_message()) . '</p></div>'
            : '<div class="notice notice-success"><p>Importación lista: ' . intval($resultado) . ' significados nuevos agregados (los repetidos se ignoran).</p></div>';
    }

    return $mensaje;
}

function tdt_significados_admin_section() {
    $cartas = tdt_cartas_lista();
    $datos = tdt_significados();
    $total = count($cartas);
    $con_contenido = tdt_significados_conteo();
    $export_json = json_encode($datos, JSON_UNESCAPED_UNICODE);
    ?>
    <h2>Base de significados</h2>
    <p>Cartas con contenido cargado: <strong><?php echo intval($con_contenido); ?> / <?php echo intval($total); ?></strong></p>
    <p>Estos significados se usan automáticamente en las lecturas del widget <code>[tirada_tarot]</code> de tu web (cuando una carta tiene contenido curado, se muestra en lugar de las palabras clave genéricas), y también los puede leer la herramienta "Tarot Personal" de tu Mac.</p>

    <h3>Exportar significados (para la herramienta local de tarot)</h3>
    <p>Copiá este bloque completo y pegalo en la herramienta "Tarot Personal" de tu Mac (sección "Importar significados curados").</p>
    <textarea readonly rows="6" style="width:100%;font-family:monospace;font-size:12px;" onclick="this.select();"><?php echo esc_textarea($export_json); ?></textarea>

    <h3>Agregar un significado</h3>
    <form method="post">
      <?php wp_nonce_field('tdt_agregar_significado_action'); ?>
      <table class="form-table">
        <tr>
          <th scope="row">Carta</th>
          <td>
            <select name="tdt_sig_carta">
              <option value="">Elegí una carta...</option>
              <?php foreach ($cartas as $id => $nombre): ?>
                <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($nombre); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row">Orientación</th>
          <td>
            <label><input type="radio" name="tdt_sig_orientacion" value="up" checked> Derecha</label>
            &nbsp;&nbsp;
            <label><input type="radio" name="tdt_sig_orientacion" value="rev"> Invertida</label>
          </td>
        </tr>
        <tr>
          <th scope="row">Significado</th>
          <td>
            <textarea name="tdt_sig_texto" rows="4" style="width:100%;" placeholder="Pegá acá el significado real, en 1-3 oraciones, tal como lo extrajiste del video."></textarea>
            <p class="description">Tip: pegale la transcripción del video a Claude y pedile que te devuelva el significado limpio de cada carta mencionada, así lo cargás acá directo.</p>
          </td>
        </tr>
      </table>
      <p><button type="submit" name="tdt_agregar_significado" value="1" class="button button-primary">Agregar significado</button></p>
    </form>

    <h3>Importación masiva (todas las cartas de un video de una vez)</h3>
    <p>Pegale la transcripción del video a Claude y pedile "dame el JSON para importación masiva del panel de tarot". Pegá acá abajo lo que te devuelva (un bloque que arranca con <code>{</code> y termina con <code>}</code>) y apretá Importar. Se suma a lo que ya tenés, no se pisa nada.</p>
    <form method="post">
      <?php wp_nonce_field('tdt_importar_significados_action'); ?>
      <textarea name="tdt_sig_json" rows="6" style="width:100%;font-family:monospace;font-size:12px;" placeholder='{"el-loco": {"up": ["..."], "rev": []}, "la-fuerza": {"up": ["..."], "rev": []}}'></textarea>
      <p><button type="submit" name="tdt_importar_significados" value="1" class="button">Importar</button></p>
    </form>

    <h3>Contenido cargado por carta</h3>
    <details>
      <summary style="cursor:pointer;">Ver el detalle de las <?php echo intval($total); ?> cartas</summary>
      <ul style="columns:3;margin-top:10px;">
        <?php foreach ($cartas as $id => $nombre):
            $u = isset($datos[$id]['up']) ? count($datos[$id]['up']) : 0;
            $r = isset($datos[$id]['rev']) ? count($datos[$id]['rev']) : 0;
        ?>
          <li><?php echo esc_html($nombre); ?> — <?php echo intval($u); ?> ⬆ / <?php echo intval($r); ?> ⬇</li>
        <?php endforeach; ?>
      </ul>
    </details>
    <hr>
    <?php
}
