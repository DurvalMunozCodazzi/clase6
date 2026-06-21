<?php
// ── Luna Workspace — Setup / Reconfiguration ──────────────────────────────────
// Accedé a este archivo UNA VEZ para configurar la conexión a la base de datos.
// Luego borralo o protegelo con .htaccess.
// URL: https://tudominio.com/wp-content/plugins/luna-workspace/app/luna-setup.php

define('SETUP_FILE', __DIR__ . '/luna-wp-config.php');

$error   = '';
$success = '';
$tested  = false;

// ── Test / Save ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host    = trim($_POST['db_host']    ?? 'localhost');
    $name    = trim($_POST['db_name']    ?? '');
    $user    = trim($_POST['db_user']    ?? '');
    $pass    = trim($_POST['db_pass']    ?? '');
    $prefix  = trim($_POST['tb_prefix']  ?? '');
    $siteurl = rtrim(trim($_POST['site_url'] ?? ''), '/');
    $action  = $_POST['action'] ?? 'test';

    if (!$name || !$user) {
        $error = 'El nombre de la base de datos y el usuario son obligatorios.';
    } else {
        // Test connection
        try {
            $port = '';
            $socket = '';
            $h = $host;
            if (strpos($h, ':/') !== false) {
                [$h, $socket] = explode(':', $h, 2);
            } elseif (preg_match('/^(.*):(\d+)$/', $h, $hm)) {
                $h = $hm[1]; $port = $hm[2];
            }
            if ($socket)   $dsn = "mysql:unix_socket={$socket};dbname={$name};charset=utf8mb4";
            elseif ($port) $dsn = "mysql:host={$h};port={$port};dbname={$name};charset=utf8mb4";
            else           $dsn = "mysql:host={$h};dbname={$name};charset=utf8mb4";

            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            // Check if tables exist with the given prefix
            $testTable = $prefix . 'users';
            $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($testTable))->fetchColumn();
            $tableStatus = $exists
                ? "<span style='color:#22c55e'>✓ Tabla <code>{$testTable}</code> encontrada</span>"
                : "<span style='color:#f59e0b'>⚠ Tabla <code>{$testTable}</code> no encontrada — se crearán nuevas tablas al activar</span>";

            $tested = true;

            if ($action === 'save') {
                // Detect upload path
                $uploadDir = __DIR__ . '/uploads/';
                $uploadUrl = $siteurl . '/uploads/';

                $content  = "<?php\n";
                $content .= "// Luna Workspace — configuración generada por luna-setup.php\n";
                $content .= "// " . date('Y-m-d H:i:s') . "\n\n";
                $content .= "define('DB_HOST',        " . var_export($host,    true) . ");\n";
                $content .= "define('DB_NAME',        " . var_export($name,    true) . ");\n";
                $content .= "define('DB_USER',        " . var_export($user,    true) . ");\n";
                $content .= "define('DB_PASS',        " . var_export($pass,    true) . ");\n";
                $content .= "define('DB_CHARSET',     'utf8mb4');\n";
                $content .= "define('LUNA_TB_PREFIX', " . var_export($prefix,  true) . ");\n";
                $content .= "define('LUNA_SITE_URL',  " . var_export($siteurl . '/', true) . ");\n";
                $content .= "define('LUNA_APP_URL',   " . var_export($siteurl . '/', true) . ");\n";
                $content .= "define('LUNA_UPLOAD_URL'," . var_export($uploadUrl, true) . ");\n";
                $content .= "define('UPLOAD_DIR',     " . var_export($uploadDir, true) . ");\n";
                $content .= "define('UPLOAD_URL',     " . var_export($uploadUrl, true) . ");\n";
                $content .= "define('SESSION_HOURS',  24);\n";
                $content .= "define('LUNA_VERSION',   '2.0');\n";
                $content .= "define('LUNA_LICENSE_KEY',    '');\n";
                $content .= "define('LUNA_LICENSE_SERVER', 'https://websobreruedas.com/licencias/api/verify.php');\n";
                $content .= "define('LUNA_CRON_SECRET',    '');\n";

                if (file_put_contents(SETUP_FILE, $content) !== false) {
                    $success = 'Configuración guardada correctamente. <strong>Podés eliminar este archivo (luna-setup.php) por seguridad.</strong>';
                } else {
                    $error = 'No se pudo escribir luna-wp-config.php — verificá permisos de escritura en el directorio app/.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Error de conexión: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Detect existing config ─────────────────────────────────────────────────────
$current = [];
if (file_exists(SETUP_FILE)) {
    $raw = file_get_contents(SETUP_FILE);
    $fields = ['DB_HOST','DB_NAME','DB_USER','DB_PASS','LUNA_TB_PREFIX','LUNA_SITE_URL'];
    foreach ($fields as $f) {
        if (preg_match("/define\s*\(\s*'{$f}'\s*,\s*'([^']*)'\s*\)/", $raw, $m)) {
            $current[$f] = $m[1];
        }
    }
}
$defHost   = htmlspecialchars($_POST['db_host']   ?? $current['DB_HOST']        ?? 'localhost');
$defName   = htmlspecialchars($_POST['db_name']   ?? $current['DB_NAME']        ?? '');
$defUser   = htmlspecialchars($_POST['db_user']   ?? $current['DB_USER']        ?? '');
$defPass   = htmlspecialchars($_POST['db_pass']   ?? $current['DB_PASS']        ?? '');
$defPrefix = htmlspecialchars($_POST['tb_prefix'] ?? $current['LUNA_TB_PREFIX'] ?? '');
$defUrl    = htmlspecialchars($_POST['site_url']  ?? rtrim($current['LUNA_SITE_URL'] ?? '', '/'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Luna Workspace — Configuración</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#0e0e1a;color:#e0e0f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:16px;padding:36px;width:100%;max-width:480px;box-shadow:0 8px 48px rgba(0,0,0,.5)}
.logo{font-size:24px;font-weight:800;color:#5b6af0;margin-bottom:24px;display:flex;align-items:center;gap:10px}
h2{font-size:14px;font-weight:700;color:#8888aa;text-transform:uppercase;letter-spacing:.5px;margin-bottom:20px}
.fg{margin-bottom:14px}
label{display:block;font-size:12px;font-weight:600;color:#8888aa;margin-bottom:5px}
input{width:100%;background:#0e0e1a;border:1px solid #2a2a4a;border-radius:8px;padding:10px 12px;font-size:13px;color:#e0e0f0;outline:none;transition:.15s}
input:focus{border-color:#5b6af0}
.hint{font-size:11px;color:#555580;margin-top:4px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.btn{display:inline-flex;align-items:center;gap:8px;padding:11px 20px;border-radius:8px;font-size:13px;font-weight:700;border:none;cursor:pointer;transition:.15s;text-decoration:none}
.btn-test{background:#2a2a4a;color:#8888cc}
.btn-test:hover{background:#3a3a6a}
.btn-save{background:#5b6af0;color:#fff}
.btn-save:hover{background:#4a59df}
.actions{display:flex;gap:10px;margin-top:20px}
.alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;line-height:1.5}
.alert-err{background:#3a1a1a;border:1px solid #7f2020;color:#ffaaaa}
.alert-ok{background:#1a3a1a;border:1px solid #207f20;color:#aaffaa}
.alert-info{background:#1a1a3a;border:1px solid #3a3a7f;color:#aaaaff;font-size:12px;margin-top:16px}
code{background:#0e0e1a;padding:2px 6px;border-radius:4px;font-family:monospace;font-size:11px;color:#5b6af0}
.existing{background:#111130;border:1px solid #2a2a4a;border-radius:8px;padding:12px;margin-bottom:20px;font-size:12px;color:#8888aa}
.existing strong{color:#aaaacc}
</style>
</head>
<body>
<div class="card">
  <div class="logo">🌙 Luna Workspace</div>

  <?php if (file_exists(SETUP_FILE)): ?>
  <div class="existing">
    <strong>Configuración existente detectada</strong><br>
    BD: <code><?=$current['DB_NAME']??'—'?></code> · Prefijo: <code><?php echo ($current['LUNA_TB_PREFIX']??'' )=='' ? '(sin prefijo)' : htmlspecialchars($current['LUNA_TB_PREFIX']??'') ?></code>
  </div>
  <?php endif; ?>

  <?php if ($error): ?>
  <div class="alert alert-err">❌ <?=$error?></div>
  <?php endif; ?>
  <?php if ($success): ?>
  <div class="alert alert-ok">✅ <?=$success?><br><br>
    <a href="index.html" class="btn btn-save" style="margin-top:8px">Ir a Luna →</a>
  </div>
  <?php else: ?>

  <h2>Conexión a la base de datos</h2>
  <form method="post">
    <div class="row">
      <div class="fg"><label>Host MySQL</label><input name="db_host" value="<?=$defHost?>" placeholder="localhost"></div>
      <div class="fg"><label>Base de datos</label><input name="db_name" value="<?=$defName?>" placeholder="admin_luna"></div>
    </div>
    <div class="row">
      <div class="fg"><label>Usuario MySQL</label><input name="db_user" value="<?=$defUser?>" placeholder="usuario"></div>
      <div class="fg"><label>Contraseña MySQL</label><input name="db_pass" value="<?=$defPass?>" type="password" placeholder="••••••••"></div>
    </div>
    <div class="fg">
      <label>Prefijo de tablas</label>
      <input name="tb_prefix" value="<?=$defPrefix?>" placeholder="Dejar vacío si las tablas son: users, cards, etc.">
      <div class="hint">Vacío = sin prefijo (<code>users</code>, <code>cards</code>…) · <code>wp_luna_</code> si instalaste con WordPress</div>
    </div>
    <div class="fg">
      <label>URL del sitio (sin barra final)</label>
      <input name="site_url" value="<?=$defUrl?>" placeholder="https://tudominio.com/ruta/app">
      <div class="hint">La URL donde está esta carpeta <code>app/</code></div>
    </div>

    <?php if ($tested && !$error): ?>
    <div class="alert alert-ok">✓ Conexión exitosa. <?=$tableStatus??''?></div>
    <?php endif; ?>

    <div class="actions">
      <button class="btn btn-test" type="submit" name="action" value="test">Probar conexión</button>
      <button class="btn btn-save" type="submit" name="action" value="save">Guardar y continuar →</button>
    </div>
  </form>

  <div class="alert alert-info">
    <strong>Seguridad:</strong> Eliminá o renombrá este archivo una vez configurado.<br>
    Ruta: <code><?=htmlspecialchars(__FILE__)?></code>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
