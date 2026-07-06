<?php
require_once '../config.php';
$db = getDB();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// POST login
if ($method === 'POST' && $action === 'login') {
    $body = json_decode(file_get_contents('php://input'), true);
    $login = trim($body['username'] ?? '');
    $pass  = trim($body['password'] ?? '');
    if (!$login || !$pass) jsonErr('Usuario y contraseña requeridos');

    // ── Rate limiting: máx 10 intentos fallidos por IP en 15 minutos ────────
    $ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $rateKey = 'rate_login_' . substr(hash('sha256', $ip), 0, 20);
    $maxAttempts = 10;
    $windowSec   = 900; // 15 minutos
    $rateData    = null;
    try {
        $rateSt = $db->prepare("SELECT meta_value FROM ".tb('app_settings')." WHERE meta_key=? LIMIT 1");
        $rateSt->execute([$rateKey]);
        $rateRow = $rateSt->fetch();
        $rateData = $rateRow ? json_decode($rateRow['meta_value'], true) : null;
        if (!is_array($rateData)) $rateData = ['count' => 0, 'since' => time()];
        // Reset window if expired
        if ((time() - ($rateData['since'] ?? 0)) >= $windowSec) {
            $rateData = ['count' => 0, 'since' => time()];
        }
        if ($rateData['count'] >= $maxAttempts) {
            $wait = $windowSec - (time() - $rateData['since']);
            jsonErr('Demasiados intentos fallidos. Esperá ' . ceil($wait / 60) . ' minuto(s).', 429);
        }
    } catch (Exception $e) { $rateData = null; } // Si la tabla no existe, omitir rate limiting
    // ────────────────────────────────────────────────────────────────────────

    try {
        $st = $db->prepare("SELECT * FROM ".tb('users')." WHERE (username=? OR email=?) AND active=1");
        $st->execute([$login, $login]);
        $user = $st->fetch();
    } catch (Exception $e) {
        // Tabla users inexistente con este prefijo → mensaje claro en vez de 500 mudo
        jsonErr('No se pudo leer la tabla de usuarios (prefijo "' . LUNA_TB_PREFIX . '"). '
              . 'Desactivá y reactivá el plugin Luna Workspace en WordPress para regenerar la configuración.', 500);
    }

    if (!$user || !password_verify($pass, $user['password'])) {
        // Incrementar contador de intentos fallidos
        if ($rateData !== null) {
            $rateData['count']++;
            try {
                $db->prepare("INSERT INTO ".tb('app_settings')." (meta_key,meta_value) VALUES (?,?) ON DUPLICATE KEY UPDATE meta_value=?")
                   ->execute([$rateKey, json_encode($rateData), json_encode($rateData)]);
            } catch (Exception $e) {}
        }
        jsonErr('Usuario o contraseña incorrectos', 401);
    }

    // Login exitoso — limpiar contador
    if ($rateData !== null) {
        try { $db->prepare("DELETE FROM ".tb('app_settings')." WHERE meta_key=?")->execute([$rateKey]); } catch (Exception $e) {}
    }

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + SESSION_HOURS * 3600);
    $db->prepare("INSERT INTO ".tb('sessions')." (token, user_id, expires_at) VALUES (?,?,?)")
       ->execute([$token, $user['id'], $expires]);
    try {
        $db->prepare("UPDATE ".tb('users')." SET last_login=NOW() WHERE id=?")
           ->execute([$user['id']]);
    } catch (PDOException $e) { /* column may not exist in older installs */ }
    $db->exec("DELETE FROM ".tb('sessions')." WHERE expires_at < NOW()");

    // También setear cookie para que funcione sin header Authorization
    $secure   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $cookieExpires = time() + SESSION_HOURS * 3600;
    setcookie('luna_token', $token, [
        'expires'  => $cookieExpires,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    jsonOut([
        'token' => $token,
        'user'  => [
            'id'       => (int)$user['id'],
            'name'     => $user['name'] ?? '',
            'username' => $user['username'] ?? '',
            'email'    => $user['email'] ?? '',
            'role'     => $user['role'] ?? 'member',
            'color'    => $user['color'] ?? '#5b6af0',
            'cargo'    => $user['cargo'] ?? '',
            'dept'     => $user['dept'] ?? '',
        ]
    ]);
}

// POST logout
if ($method === 'POST' && $action === 'logout') {
    $token = getBearerToken();
    if ($token) $db->prepare("DELETE FROM ".tb('sessions')." WHERE token=?")->execute([$token]);
    setcookie('luna_token', '', time() - 3600, '/');
    jsonOut(['ok' => true]);
}

// GET me
if ($method === 'GET' && $action === 'me') {
    $user = requireAuth();
    jsonOut(['user' => [
        'id'       => (int)$user['id'],
        'name'     => $user['name'] ?? '',
        'username' => $user['username'] ?? '',
        'email'    => $user['email'] ?? '',
        'role'     => $user['role'] ?? 'member',
        'color'    => $user['color'] ?? '#5b6af0',
        'cargo'    => $user['cargo'] ?? '',
        'dept'     => $user['dept'] ?? '',
    ]]);
}

// GET diag — diagnóstico mínimo, sin datos sensibles (no expone credenciales
// ni nombres de usuarios; solo el prefijo en uso y conteos, para soporte)
if ($method === 'GET' && $action === 'diag') {
    $out = [
        'version'  => defined('LUNA_VERSION') ? LUNA_VERSION : '?',
        'prefix'   => LUNA_TB_PREFIX,
        'db'       => 'ERROR',
        'users'    => null,
        'sessions' => null,
    ];
    try {
        $out['users'] = (int) $db->query("SELECT COUNT(*) FROM " . tb('users'))->fetchColumn();
        $out['db']    = 'OK';
    } catch (Exception $e) {}
    try {
        $out['sessions'] = (int) $db->query("SELECT COUNT(*) FROM " . tb('sessions'))->fetchColumn();
    } catch (Exception $e) {}
    jsonOut($out);
}

jsonErr('Acción no encontrada', 404);
