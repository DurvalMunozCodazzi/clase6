<?php
/**
 * Luna Workspace — Herramienta de mantenimiento
 *
 * USO: Subir temporalmente al servidor vía FTP. Borrar después de usar.
 * NUNCA incluir en el ZIP de distribución (excluido en el proceso de empaquetado).
 *
 * Acceso protegido por contraseña. Cambiar LUNA_MAINT_PASS antes de subir.
 */

define('LUNA_MAINT_PASS', 'wsr2024luna!'); // ← CAMBIAR antes de subir

// ── Autenticación ─────────────────────────────────────────────────────────────
session_start();

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['password'])) {
    if ($_POST['password'] === LUNA_MAINT_PASS) {
        $_SESSION['luna_maint_ok'] = true;
    } else {
        $login_error = 'Contraseña incorrecta.';
    }
}

$authed = !empty($_SESSION['luna_maint_ok']);

// ── Login page ────────────────────────────────────────────────────────────────
if (!$authed) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Luna — Mantenimiento</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f1117;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
        .box{background:#1a1d2e;border:1px solid #2d3155;border-radius:16px;padding:40px;width:380px;text-align:center}
        h1{color:#fff;font-size:20px;margin:16px 0 6px}
        p{color:#8888aa;font-size:13px;margin-bottom:28px}
        input{width:100%;padding:12px 16px;border-radius:10px;border:1px solid #3a3d55;background:#0f1117;color:#fff;font-size:15px;outline:none;margin-bottom:12px}
        input:focus{border-color:#5b6af0}
        button{width:100%;padding:12px;border-radius:10px;border:none;background:#5b6af0;color:#fff;font-size:15px;font-weight:700;cursor:pointer}
        button:hover{background:#4a58d4}
        .err{background:#2d1a1a;color:#f87171;border:1px solid #7f1d1d;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:16px}
        .moon{font-size:40px}
    </style>
    </head>
    <body>
    <div class="box">
        <div class="moon">🌙</div>
        <h1>Luna Workspace</h1>
        <p>Herramienta de mantenimiento<br>Acceso restringido a Web Sobre Ruedas</p>
        <?php if (!empty($login_error)): ?>
            <div class="err"><?php echo htmlspecialchars($login_error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Contraseña" autofocus autocomplete="current-password">
            <button type="submit">Acceder</button>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// ── Conectar a la DB de Luna ──────────────────────────────────────────────────
$cfg_file = __DIR__ . '/app/luna-wp-config.php';
$cfg_defs = [];
$pdo      = null;
$pfx      = '';
$db_error = '';

if (file_exists($cfg_file)) {
    preg_match_all("/define\('([^']+)',\s*'([^']*)'\)/", file_get_contents($cfg_file), $cm, PREG_SET_ORDER);
    foreach ($cm as $row) $cfg_defs[$row[1]] = $row[2];
    $pfx = $cfg_defs['LUNA_TB_PREFIX'] ?? '';
    try {
        $dsn = "mysql:host={$cfg_defs['DB_HOST']};dbname={$cfg_defs['DB_NAME']};charset=" . ($cfg_defs['DB_CHARSET'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $cfg_defs['DB_USER'] ?? '', $cfg_defs['DB_PASS'] ?? '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Exception $e) {
        $db_error = $e->getMessage();
    }
} else {
    $db_error = 'No se encontró luna-wp-config.php en ' . $cfg_file;
}

// Tablas conocidas de Luna
$table_names = [
    'users', 'workspaces', 'workspace_members', 'workspace_labels',
    'columns_k', 'cards', 'card_tags', 'card_assignees', 'card_checklist',
    'card_dependencies', 'attachments', 'sessions',
    'notifications', 'app_settings', 'workspace_templates', 'activity_log', 'user_meta',
];

// ── Procesar acciones POST ────────────────────────────────────────────────────
$action_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo && isset($_POST['op'])) {
    $op = $_POST['op'];

    $existing = [];
    foreach ($table_names as $t) {
        $full = $pfx . $t;
        $found = $pdo->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=" . $pdo->quote($full))->fetchColumn();
        if ($found) $existing[] = "`{$full}`";
    }

    $table_list = implode(', ', $existing);

    switch ($op) {
        case 'check':
        case 'optimize':
        case 'repair':
            $sql_op = strtoupper($op);
            try {
                $rows = $pdo->query("{$sql_op} TABLE {$table_list}")->fetchAll();
                $errors = array_filter($rows, fn($r) => ($r['Msg_type'] ?? '') === 'error');
                $action_result = ['ok' => true, 'op' => $sql_op, 'rows' => $rows, 'errors' => count($errors)];
            } catch (Exception $e) {
                $action_result = ['ok' => false, 'msg' => $e->getMessage()];
            }
            break;

        case 'clean_sessions':
            try {
                $st = $pdo->prepare("DELETE FROM `{$pfx}sessions` WHERE expires_at < NOW()");
                $st->execute();
                $action_result = ['ok' => true, 'msg' => $st->rowCount() . ' sesión(es) expirada(s) eliminada(s).'];
            } catch (Exception $e) {
                $action_result = ['ok' => false, 'msg' => $e->getMessage()];
            }
            break;

        case 'regen_config':
            // Buscar wp-load.php subiendo directorios desde la ubicación de este archivo
            $wp_load = null;
            $dir = __DIR__;
            for ($i = 0; $i < 8; $i++) {
                $dir = dirname($dir);
                if (file_exists($dir . '/wp-load.php')) { $wp_load = $dir . '/wp-load.php'; break; }
            }
            if ($wp_load) {
                try {
                    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    require_once $wp_load;
                    // Llamar al activador de Luna si está disponible
                    if (class_exists('Luna_Activator')) {
                        Luna_Activator::regenerate_app_config();
                        $action_result = ['ok' => true, 'msg' => 'luna-wp-config.php regenerado exitosamente.'];
                    } else {
                        $action_result = ['ok' => false, 'msg' => 'Luna_Activator no disponible. Asegurate de que el plugin esté activo.'];
                    }
                } catch (Exception $e) {
                    $action_result = ['ok' => false, 'msg' => 'Error al cargar WordPress: ' . $e->getMessage()];
                }
            } else {
                $action_result = ['ok' => false, 'msg' => 'No se encontró wp-load.php. Regenerá el config manualmente desde el admin de WordPress.'];
            }
            break;
    }
}

// ── Recopilar estado de tablas ────────────────────────────────────────────────
$tables = [];
if ($pdo) {
    foreach ($table_names as $tname) {
        $full = $pfx . $tname;
        $row  = ['name' => $tname, 'full' => $full, 'rows' => null, 'size_kb' => null, 'error' => ''];
        try {
            $r = $pdo->query("SELECT TABLE_ROWS, ROUND((DATA_LENGTH+INDEX_LENGTH)/1024,1) AS kb
                               FROM information_schema.TABLES
                               WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=" . $pdo->quote($full))->fetch();
            if ($r) { $row['rows'] = (int)$r['TABLE_ROWS']; $row['size_kb'] = (float)$r['kb']; }
        } catch (Exception $e) { $row['error'] = $e->getMessage(); }
        $tables[] = $row;
    }
}

$total_rows = array_sum(array_filter(array_column($tables, 'rows'), fn($v) => $v !== null));

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Luna — Herramienta de mantenimiento</title>
<style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{background:#0f1117;color:#e2e8f0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:13px;min-height:100vh}
    .topbar{background:#1a1d2e;border-bottom:1px solid #2d3155;padding:0 28px;height:56px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
    .topbar h1{font-size:16px;font-weight:800;color:#fff;display:flex;align-items:center;gap:10px}
    .topbar .badge{font-size:10px;background:#dc2626;color:#fff;padding:2px 8px;border-radius:99px;font-weight:700}
    .wrap{max-width:1200px;margin:0 auto;padding:28px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
    .card{background:#1a1d2e;border:1px solid #2d3155;border-radius:12px;padding:24px}
    .card h2{font-size:13px;font-weight:700;color:#8888aa;text-transform:uppercase;letter-spacing:.8px;margin-bottom:16px}
    table{width:100%;border-collapse:collapse}
    th{text-align:left;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:.5px;padding:8px 10px;border-bottom:1px solid #2d3155}
    td{padding:8px 10px;border-bottom:1px solid #1e2235;font-size:12px}
    tr:last-child td{border-bottom:none}
    code{background:#0f1117;border-radius:4px;padding:2px 6px;font-size:11px;color:#a5b4fc;font-family:'JetBrains Mono',monospace}
    .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;border:none;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s}
    .btn-primary{background:#5b6af0;color:#fff} .btn-primary:hover{background:#4a58d4}
    .btn-warn{background:#d97706;color:#fff} .btn-warn:hover{background:#b45309}
    .btn-danger{background:#dc2626;color:#fff} .btn-danger:hover{background:#b91c1c}
    .btn-ghost{background:#2d3155;color:#e2e8f0} .btn-ghost:hover{background:#3a3d66}
    .btn:disabled{opacity:.5;cursor:not-allowed}
    .info-row{display:flex;align-items:flex-start;gap:8px;padding:6px 0;border-bottom:1px solid #2d3155;font-size:12px}
    .info-row:last-child{border-bottom:none}
    .info-label{color:#64748b;min-width:150px;flex-shrink:0}
    .info-val{color:#e2e8f0;font-family:monospace;word-break:break-all}
    .ok{color:#22d3a0} .err{color:#f87171} .warn{color:#f59e0b}
    .result-box{border-radius:8px;padding:12px 16px;margin-top:16px;font-size:12px;line-height:1.6}
    .result-ok{background:#0d2d1a;border:1px solid #22d3a0;color:#6ee7b7}
    .result-err{background:#2d0d0d;border:1px solid #f87171;color:#fca5a5}
    .result-table{margin-top:12px;background:#0f1117;border-radius:8px;overflow:hidden}
    .result-table th{background:#1a1d2e}
    .stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
    .stat-box{background:#0f1117;border-radius:8px;padding:14px;text-align:center}
    .stat-num{font-size:26px;font-weight:800}
    .stat-lbl{font-size:10px;color:#64748b;margin-top:3px;text-transform:uppercase;letter-spacing:.5px}
    .logout-btn{background:transparent;border:1px solid #3a3d55;color:#8888aa;border-radius:8px;padding:6px 14px;cursor:pointer;font-size:12px}
    .logout-btn:hover{color:#fff;border-color:#5b6af0}
    .section-label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px}
    .action-group{display:flex;flex-direction:column;gap:8px}
    .action-item{background:#0f1117;border-radius:8px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:16px}
    .action-desc strong{display:block;font-size:12px;color:#e2e8f0;margin-bottom:2px}
    .action-desc span{font-size:11px;color:#64748b}
</style>
</head>
<body>

<div class="topbar">
    <h1>🌙 Luna Workspace <span class="badge">MANTENIMIENTO</span></h1>
    <form method="POST" style="margin:0">
        <button name="logout" value="1" class="logout-btn">Cerrar sesión</button>
    </form>
</div>

<div class="wrap">

    <?php // ── Resultado de la última acción ─────────────────────────────── ?>
    <?php if ($action_result): ?>
        <div class="result-box <?php echo $action_result['ok'] ? 'result-ok' : 'result-err' ?>">
            <?php if ($action_result['ok']): ?>
                ✅ <?php echo isset($action_result['msg']) ? htmlspecialchars($action_result['msg']) : 'Operación completada.' ?>
                <?php if (isset($action_result['errors'])): ?>
                    — <?php echo $action_result['errors'] ?> error(es) encontrado(s).
                <?php endif; ?>
            <?php else: ?>
                ❌ <?php echo htmlspecialchars($action_result['msg'] ?? 'Error desconocido') ?>
            <?php endif; ?>
        </div>
        <?php if (!empty($action_result['rows'])): ?>
            <div class="result-table">
                <table>
                    <thead><tr><th>Tabla</th><th>Operación</th><th>Tipo</th><th>Resultado</th></tr></thead>
                    <tbody>
                    <?php foreach ($action_result['rows'] as $r): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($r['Table'] ?? '') ?></code></td>
                            <td><?php echo htmlspecialchars($r['Op'] ?? '') ?></td>
                            <td><?php echo htmlspecialchars($r['Msg_type'] ?? '') ?></td>
                            <td class="<?php echo ($r['Msg_text'] ?? '') === 'OK' || ($r['Msg_text'] ?? '') === 'Table is already up to date' ? 'ok' : 'err' ?>">
                                <?php echo htmlspecialchars($r['Msg_text'] ?? '') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <div style="height:20px"></div>
    <?php endif; ?>

    <div class="grid">

        <?php // ── Conexión y credenciales ───────────────────────────────── ?>
        <div class="card">
            <h2>🔌 Conexión</h2>
            <?php if ($db_error): ?>
                <div class="result-box result-err">❌ <?php echo htmlspecialchars($db_error) ?></div>
            <?php else: ?>
                <div class="info-row"><span class="info-label">Estado</span><span class="info-val ok">✅ Conectado</span></div>
                <div class="info-row"><span class="info-label">Host</span><span class="info-val"><code><?php echo htmlspecialchars($cfg_defs['DB_HOST'] ?? '—') ?></code></span></div>
                <div class="info-row"><span class="info-label">Base de datos</span><span class="info-val"><code><?php echo htmlspecialchars($cfg_defs['DB_NAME'] ?? '—') ?></code></span></div>
                <div class="info-row"><span class="info-label">Usuario MySQL</span><span class="info-val"><code><?php echo htmlspecialchars($cfg_defs['DB_USER'] ?? '—') ?></code></span></div>
                <div class="info-row"><span class="info-label">Prefijo tablas</span><span class="info-val"><code><?php echo $pfx !== '' ? htmlspecialchars($pfx) : '(sin prefijo)' ?></code></span></div>
                <div class="info-row"><span class="info-label">Charset</span><span class="info-val"><code><?php echo htmlspecialchars($cfg_defs['DB_CHARSET'] ?? 'utf8mb4') ?></code></span></div>
                <div class="info-row"><span class="info-label">Config file</span><span class="info-val <?php echo file_exists($cfg_file) ? 'ok' : 'err' ?>">
                    <?php echo file_exists($cfg_file) ? '✅ Existe' : '❌ No encontrado' ?>
                </span></div>
                <div class="info-row"><span class="info-label">Servidor</span><span class="info-val"><code><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?></code></span></div>
                <div class="info-row"><span class="info-label">PHP</span><span class="info-val"><code><?php echo PHP_VERSION ?></code></span></div>
                <div class="info-row"><span class="info-label">Hora servidor</span><span class="info-val"><code><?php echo date('d/m/Y H:i:s') ?></code></span></div>
            <?php endif; ?>
        </div>

        <?php // ── Resumen ───────────────────────────────────────────────── ?>
        <div class="card">
            <h2>📊 Estado general</h2>
            <?php
                $ok_c  = count(array_filter($tables, fn($t) => $t['rows'] !== null && !$t['error']));
                $err_c = count(array_filter($tables, fn($t) => $t['error'] !== ''));
                $mis_c = count(array_filter($tables, fn($t) => $t['rows'] === null && !$t['error']));
                $total_kb = array_sum(array_filter(array_column($tables, 'size_kb'), fn($v) => $v !== null));
            ?>
            <div class="stat-grid">
                <div class="stat-box">
                    <div class="stat-num ok"><?php echo number_format($total_rows) ?></div>
                    <div class="stat-lbl">Registros totales</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num" style="color:#a5b4fc"><?php echo round($total_kb / 1024, 2) ?> MB</div>
                    <div class="stat-lbl">Tamaño total</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num ok"><?php echo $ok_c ?>/<?php echo count($tables) ?></div>
                    <div class="stat-lbl">Tablas OK</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num <?php echo $err_c > 0 ? 'err' : 'ok' ?>"><?php echo $err_c ?></div>
                    <div class="stat-lbl">Con error</div>
                </div>
            </div>
            <?php if ($mis_c > 0): ?>
                <p class="warn" style="font-size:11px">⚠️ <?php echo $mis_c ?> tabla(s) no encontradas</p>
            <?php endif; ?>
        </div>

    </div>

    <?php // ── Acciones de mantenimiento ─────────────────────────────────── ?>
    <div class="card" style="margin-bottom:20px">
        <h2>⚡ Acciones de mantenimiento</h2>
        <?php if (!$pdo): ?>
            <p class="err">No hay conexión activa — corregí el error de conexión primero.</p>
        <?php else: ?>
        <div class="action-group">

            <div class="action-item">
                <div class="action-desc">
                    <strong>🔍 Verificar integridad de tablas</strong>
                    <span>CHECK TABLE — analiza índices y detecta errores. No modifica ningún dato.</span>
                </div>
                <form method="POST" style="margin:0;flex-shrink:0">
                    <input type="hidden" name="op" value="check">
                    <button class="btn btn-ghost" onclick="return confirm('¿Verificar integridad de todas las tablas de Luna?')">Ejecutar</button>
                </form>
            </div>

            <div class="action-item">
                <div class="action-desc">
                    <strong>⚙️ Optimizar y reconstruir índices</strong>
                    <span>OPTIMIZE TABLE — libera espacio y reconstruye índices. Sin pérdida de datos.</span>
                </div>
                <form method="POST" style="margin:0;flex-shrink:0">
                    <input type="hidden" name="op" value="optimize">
                    <button class="btn btn-ghost" onclick="return confirm('¿Optimizar todas las tablas de Luna?')">Ejecutar</button>
                </form>
            </div>

            <div class="action-item">
                <div class="action-desc">
                    <strong>🔧 Reparar tablas corruptas</strong>
                    <span>REPAIR TABLE — repara índices corruptos. Opción más agresiva, sin borrar datos.</span>
                </div>
                <form method="POST" style="margin:0;flex-shrink:0">
                    <input type="hidden" name="op" value="repair">
                    <button class="btn btn-warn" onclick="return confirm('¿Reparar todas las tablas de Luna?\n\nEsta es la operación más agresiva — asegurate de tener un backup antes.')">Ejecutar</button>
                </form>
            </div>

            <div class="action-item">
                <div class="action-desc">
                    <strong>🧹 Limpiar sesiones vencidas</strong>
                    <span>Elimina tokens de acceso expirados. No borra usuarios ni datos.</span>
                </div>
                <form method="POST" style="margin:0;flex-shrink:0">
                    <input type="hidden" name="op" value="clean_sessions">
                    <button class="btn btn-ghost" onclick="return confirm('¿Limpiar sesiones vencidas?')">Ejecutar</button>
                </form>
            </div>

            <div class="action-item" style="border:1px solid #7c3aed22;background:#1a1023">
                <div class="action-desc">
                    <strong>🔄 Regenerar archivo de configuración</strong>
                    <span>Recrea luna-wp-config.php consultando la base de datos de WordPress. Requiere acceso a wp-load.php.</span>
                </div>
                <form method="POST" style="margin:0;flex-shrink:0">
                    <input type="hidden" name="op" value="regen_config">
                    <button class="btn btn-primary" onclick="return confirm('¿Regenerar luna-wp-config.php?\n\nEsto actualizará la configuración de conexión de la app Luna.')">Ejecutar</button>
                </form>
            </div>

        </div>
        <?php endif; ?>
    </div>

    <?php // ── Estado detallado de tablas ─────────────────────────────────── ?>
    <div class="card">
        <h2>📋 Detalle por tabla</h2>
        <table>
            <thead>
                <tr>
                    <th>Tabla</th>
                    <th style="text-align:right">Registros</th>
                    <th style="text-align:right">Tamaño</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tables as $t):
                $exists = $t['rows'] !== null && !$t['error'];
            ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($t['full']) ?></code></td>
                    <td style="text-align:right"><?php echo $exists ? '<strong>' . number_format($t['rows']) . '</strong>' : '<span style="color:#475569">—</span>' ?></td>
                    <td style="text-align:right"><?php echo $exists ? number_format($t['size_kb'], 1) . ' KB' : '<span style="color:#475569">—</span>' ?></td>
                    <td>
                        <?php if ($t['error']): ?>
                            <span class="err">❌ <?php echo htmlspecialchars($t['error']) ?></span>
                        <?php elseif (!$exists): ?>
                            <span style="color:#475569">No existe</span>
                        <?php else: ?>
                            <span class="ok">✓ OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p style="text-align:center;color:#3a3d55;font-size:11px;margin-top:24px;padding-bottom:24px">
        Luna Workspace <?php echo defined('LUNA_VERSION') ? LUNA_VERSION : '' ?> — Herramienta de mantenimiento interna — Web Sobre Ruedas<br>
        <strong style="color:#dc2626">Recordatorio: borrar este archivo del servidor después de usar.</strong>
    </p>

</div>

</body>
</html>
