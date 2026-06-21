<?php
// ── Capturar cualquier error PHP y devolverlo como JSON (quitar en producción) ──
set_exception_handler(function($e) {
    http_response_code(500);
    @header('Content-Type: application/json');
    echo json_encode([
        'error' => 'PHP Exception: ' . $e->getMessage(),
        'file'  => str_replace($_SERVER['DOCUMENT_ROOT'] ?? '', '', $e->getFile()),
        'line'  => $e->getLine(),
    ]);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Respect @ operator: when error_reporting() returns 0 the error was suppressed
    if (!(error_reporting() & $errno)) return false;
    throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
}, E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// 1) Intentar cargar credenciales generadas por el plugin WP
$_luna_cred = __DIR__ . '/luna-wp-config.php';
if (file_exists($_luna_cred)) {
    require_once $_luna_cred;
}

// 2) Fallback: leer wp-config.php sin ejecutarlo (extrae solo los define)
if (!defined('DB_HOST')) {
    $dir = __DIR__;
    for ($i = 0; $i < 10; $i++) {
        $dir = dirname($dir);
        $candidate = $dir . '/wp-config.php';
        if (file_exists($candidate)) {
            $raw = file_get_contents($candidate);
            // Extract define('CONSTANT', 'value') — single-quoted values
            preg_match_all("/define\s*\(\s*['\"](\w+)['\"]\s*,\s*'([^']*)'\s*\)/", $raw, $mm, PREG_SET_ORDER);
            foreach ($mm as $m) {
                if (!defined($m[1])) define($m[1], $m[2]);
            }
            // Double-quoted values
            preg_match_all('/define\s*\(\s*[\'"](\w+)[\'"]\s*,\s*"([^"]*)"\s*\)/', $raw, $mm, PREG_SET_ORDER);
            foreach ($mm as $m) {
                if (!defined($m[1])) define($m[1], $m[2]);
            }
            // $table_prefix
            if (!defined('WP_TABLE_PREFIX')) {
                preg_match('/\$table_prefix\s*=\s*[\'"]([^\'"]+)[\'"]/', $raw, $pm);
                if ($pm) define('WP_TABLE_PREFIX', $pm[1]);
            }
            break;
        }
    }
    // Build LUNA_TB_PREFIX from WP prefix if not already defined
    if (!defined('LUNA_TB_PREFIX') && defined('WP_TABLE_PREFIX')) {
        define('LUNA_TB_PREFIX', WP_TABLE_PREFIX . 'luna_');
    }
    // wp-config.php uses DB_PASSWORD; alias to DB_PASS used by getDB()
    if (defined('DB_PASSWORD') && !defined('DB_PASS')) {
        define('DB_PASS', DB_PASSWORD);
    }
}

// 3) Defaults si aún no están definidas
if (!defined('DB_HOST'))        define('DB_HOST',        'localhost');
if (!defined('DB_CHARSET'))     define('DB_CHARSET',     'utf8mb4');
if (!defined('LUNA_TB_PREFIX')) define('LUNA_TB_PREFIX', 'wp_luna_');
if (!defined('SESSION_HOURS'))  define('SESSION_HOURS',  24);
if (!defined('LUNA_VERSION'))   define('LUNA_VERSION',   '2.0');
if (!defined('LUNA_LICENSE_KEY'))    define('LUNA_LICENSE_KEY',    '');
if (!defined('LUNA_LICENSE_SERVER')) define('LUNA_LICENSE_SERVER', 'https://websobreruedas.com/licencias/api/verify.php');
if (!defined('LUNA_SITE_URL'))       define('LUNA_SITE_URL',   '');
if (!defined('LUNA_UPLOAD_URL'))     define('LUNA_UPLOAD_URL', '');
if (!defined('LUNA_CRON_SECRET'))    define('LUNA_CRON_SECRET', '');

// ── Funciones principales ─────────────────────────────────────────────────────

function getDB() {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        // Parse DB_HOST: may contain port (host:port) or socket (localhost:/path/socket)
        $host   = DB_HOST;
        $port   = '';
        $socket = '';
        if (strpos($host, ':/') !== false) {
            [$host, $socket] = explode(':', $host, 2);
        } elseif (preg_match('/^(.*):(\d+)$/', $host, $hm)) {
            $host = $hm[1];
            $port = $hm[2];
        }
        if ($socket)    $dsn = "mysql:unix_socket={$socket};dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        elseif ($port)  $dsn = "mysql:host={$host};port={$port};dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        else            $dsn = "mysql:host={$host};dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
    }
}

function tb($name) { return LUNA_TB_PREFIX . $name; }

function jsonOut($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function jsonErr($msg, $code = 400) { jsonOut(['error' => $msg], $code); }

function getBearerToken() {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (function_exists('apache_request_headers')) {
        foreach (apache_request_headers() as $k => $v)
            if (strtolower($k) === 'authorization') { $h = $v; break; }
    }
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) return trim($m[1]);
    if (!empty($_GET['token']))         return trim($_GET['token']);
    if (!empty($_COOKIE['luna_token'])) return trim($_COOKIE['luna_token']);
    return null;
}

function requireAuth() {
    $token = getBearerToken();
    if (!$token) jsonErr('No autenticado', 401);
    $db = getDB();
    $st = $db->prepare("SELECT u.* FROM " . tb('sessions') . " s
        JOIN " . tb('users') . " u ON u.id = s.user_id
        WHERE s.token=? AND s.expires_at>NOW() AND u.active=1");
    $st->execute([$token]);
    $user = $st->fetch();
    if (!$user) jsonErr('Sesión inválida o expirada', 401);
    return $user;
}

function requireAdmin() {
    $user = requireAuth();
    if ($user['role'] !== 'admin') jsonErr('Solo el administrador puede realizar esta acción', 403);
    return $user;
}

function getLicenseInfo() {
    static $info = null;
    if ($info !== null) return $info;
    $key = LUNA_LICENSE_KEY;
    if (!$key) { $info = ['valid' => false, 'plan' => 'none', 'max_workspaces' => 1]; return $info; }
    $cache_file = __DIR__ . '/luna-license-cache.json';
    if (file_exists($cache_file)) {
        $c = json_decode(file_get_contents($cache_file), true);
        if ($c && isset($c['expires']) && $c['expires'] > time()) { $info = $c; return $info; }
    }
    try {
        $domain  = $_SERVER['HTTP_HOST'] ?? '';
        $payload = json_encode(['license_key' => $key, 'domain' => $domain]);
        $r       = null;

        // Usar cURL si está disponible (más confiable que file_get_contents)
        if (function_exists('curl_init')) {
            $ch = curl_init(LUNA_LICENSE_SERVER);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $r    = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 429) {
                $info = ['valid' => true, 'plan' => 'offline', 'max_workspaces' => 1,
                         'message' => 'Rate limit del servidor de licencias.'];
                return $info;
            }
            if (!$r || $code < 200 || $code >= 300) $r = null;
        } elseif (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create(['http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\nAccept: application/json",
                'content'       => $payload,
                'timeout'       => 8,
                'ignore_errors' => true,
            ]]);
            $r = @file_get_contents(LUNA_LICENSE_SERVER, false, $ctx);
        }

        $data = $r ? json_decode($r, true) : null;

        if ($data && isset($data['valid'])) {
            // Verificar firma HMAC si está disponible
            if (!empty($data['hmac']) && !empty($data['issued_at']) && defined('LUNA_HMAC_SECRET') && LUNA_HMAC_SECRET) {
                $sign_payload = implode('|', [
                    $key,
                    $data['domain']     ?? $domain,
                    !empty($data['valid']) ? 'true' : 'false',
                    $data['plan']       ?? '',
                    $data['expires_at'] ?? '',
                    $data['issued_at'],
                ]);
                $expected = hash_hmac('sha256', $sign_payload, LUNA_HMAC_SECRET);
                if (!hash_equals($expected, $data['hmac'])) {
                    // Firma inválida — tratar como offline restrictivo
                    $info = ['valid' => false, 'plan' => 'none', 'max_workspaces' => 0,
                             'reason' => 'invalid_signature'];
                    return $info;
                }
            }
            $data['expires'] = time() + 86400 * (($data['grace'] ?? false) ? 1 : 30);
            @file_put_contents($cache_file, json_encode($data));
            $info = $data;
        } else {
            // Servidor inalcanzable — usar caché expirada (tolerancia a caídas temporales)
            if (file_exists($cache_file)) {
                $c = json_decode(file_get_contents($cache_file), true);
                if ($c && is_array($c)) { $info = $c; return $info; }
            }
            // Sin caché: plan más restrictivo (nunca 999 como fallback)
            $info = ['valid' => true, 'plan' => 'offline', 'max_workspaces' => 1, 'max_sites' => 1];
        }
    } catch (\Exception $e) {
        if (file_exists($cache_file)) {
            $c = @json_decode(@file_get_contents($cache_file), true);
            if ($c && is_array($c)) { $info = $c; return $info; }
        }
        $info = ['valid' => true, 'plan' => 'offline', 'max_workspaces' => 1, 'max_sites' => 1];
    }
    return $info;
}

// ── CORS headers ──────────────────────────────────────────────────────────────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Expose-Headers: Authorization');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); exit;
}
