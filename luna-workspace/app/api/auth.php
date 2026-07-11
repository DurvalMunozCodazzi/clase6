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

// POST forgot_password — genera una contraseña nueva y la envía por WhatsApp
// al teléfono que el usuario ya tiene configurado en su perfil (mismo
// mecanismo de CallMeBot que usan las notificaciones — requiere que el
// usuario ya haya vinculado su número y API Key con anterioridad).
if ($method === 'POST' && $action === 'forgot_password') {
    $body  = json_decode(file_get_contents('php://input'), true);
    $email = trim($body['email'] ?? '');
    $generic = 'Si el email corresponde a una cuenta activa con WhatsApp configurado, vas a recibir la contraseña nueva en breve.';
    if (!$email) jsonErr('Ingresá tu email');

    // ── Rate limiting: máx 5 solicitudes por IP en 15 minutos ────────────────
    $ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $rateKey = 'rate_forgot_' . substr(hash('sha256', $ip), 0, 20);
    try {
        $rateSt = $db->prepare("SELECT meta_value FROM ".tb('app_settings')." WHERE meta_key=? LIMIT 1");
        $rateSt->execute([$rateKey]);
        $rateRow  = $rateSt->fetch();
        $rateData = $rateRow ? json_decode($rateRow['meta_value'], true) : null;
        if (!is_array($rateData)) $rateData = ['count' => 0, 'since' => time()];
        if ((time() - ($rateData['since'] ?? 0)) >= 900) $rateData = ['count' => 0, 'since' => time()];
        if ($rateData['count'] >= 5) {
            $wait = 900 - (time() - $rateData['since']);
            jsonErr('Demasiados intentos. Esperá ' . ceil($wait / 60) . ' minuto(s).', 429);
        }
        $rateData['count']++;
        $db->prepare("INSERT INTO ".tb('app_settings')." (meta_key,meta_value) VALUES (?,?) ON DUPLICATE KEY UPDATE meta_value=?")
           ->execute([$rateKey, json_encode($rateData), json_encode($rateData)]);
    } catch (Exception $e) {}
    // ────────────────────────────────────────────────────────────────────────

    $st = $db->prepare("SELECT id, name, phone, whatsapp_apikey FROM ".tb('users')." WHERE email=? AND active=1 LIMIT 1");
    $st->execute([$email]);
    $user = $st->fetch();

    // Mismo mensaje exista o no la cuenta — no revelar qué emails están registrados
    if (!$user || empty($user['phone']) || empty($user['whatsapp_apikey'])) {
        jsonOut(['message' => $generic]);
    }

    $new_pass = bin2hex(random_bytes(8)); // 16 caracteres, legible

    // Enviar PRIMERO y solo cambiar la contraseña si CallMeBot confirmó el
    // envío — si el mensaje no sale (número mal configurado, sin señal, etc.)
    // no tiene sentido invalidar la clave vieja: el usuario quedaría afuera
    // sin ninguna forma de saber la nueva.
    $text = "🔑 Luna Workspace — tu contraseña nueva es: {$new_pass}\n\nUsala para ingresar y, si querés, cambiala después desde tu perfil.";
    $url  = 'https://api.callmebot.com/whatsapp.php?' . http_build_query([
        'phone'  => $user['phone'],
        'text'   => $text,
        'apikey' => $user['whatsapp_apikey'],
    ]);
    $ctx  = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
    $resp = @file_get_contents($url, false, $ctx);
    $sent = ($resp !== false && (stripos($resp, 'Message Sent') !== false || stripos($resp, 'Message queued') !== false));

    $updated = false;
    $verify_after = false;
    if ($sent) {
        $hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $upd  = $db->prepare("UPDATE ".tb('users')." SET password=? WHERE id=?");
        $upd->execute([$hash, $user['id']]);
        $updated = ($upd->rowCount() > 0);
        // Invalidar sesiones activas — obliga a re-loguear con la clave nueva
        try { $db->prepare("DELETE FROM ".tb('sessions')." WHERE user_id=?")->execute([$user['id']]); } catch (Exception $e) {}
        // Releer inmediatamente para confirmar que lo grabado verifica de
        // verdad contra la clave recién generada (detecta cualquier trigger
        // o normalización que corrompa el hash en el camino).
        $chk = $db->prepare("SELECT password FROM ".tb('users')." WHERE id=?");
        $chk->execute([$user['id']]);
        $storedNow = $chk->fetchColumn();
        $verify_after = ($storedNow && password_verify($new_pass, $storedNow));
    }

    // Registro para diagnóstico (?action=diag_last_reset&login=...): no
    // guarda la contraseña ni el hash, solo si el envío se confirmó, si el
    // UPDATE afectó una fila, y si la relectura verifica correctamente.
    try {
        $log = json_encode([
            'time'          => date('Y-m-d H:i:s'),
            'resp_fue_false'=> ($resp === false),
            'callmebot_ok'  => (stripos((string)$resp, 'Message Sent') !== false || stripos((string)$resp, 'Message queued') !== false),
            'sent'          => $sent,
            'update_afecto_fila' => $updated,
            'verifica_tras_releer' => $verify_after,
        ]);
        $db->prepare("INSERT INTO ".tb('app_settings')." (meta_key,meta_value) VALUES (?,?) ON DUPLICATE KEY UPDATE meta_value=?")
           ->execute(['last_reset_' . $user['id'], $log, $log]);
    } catch (Exception $e) {}

    jsonOut(['message' => $generic]);
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

// GET diag_user — diagnóstico de UN usuario puntual, sin exponer la clave:
// confirma si existe, si está activo, y si tiene WhatsApp configurado (para
// saber por qué falla el login o por qué "olvidé mi contraseña" no entrega
// nada), sin revelar el hash ni datos de otros usuarios.
if ($method === 'GET' && $action === 'diag_user') {
    $login = trim($_GET['login'] ?? '');
    if (!$login) jsonErr('Usá ?login=usuario_o_email');
    $st = $db->prepare("SELECT username, email, role, active, phone, whatsapp_apikey, LENGTH(password) AS pass_len FROM ".tb('users')." WHERE username=? OR email=?");
    $st->execute([$login, $login]);
    $rows = $st->fetchAll();
    $out = array_map(function($u) {
        $email = $u['email'] ?? '';
        $at    = strpos($email, '@');
        $emailMasked = $at !== false
            ? substr($email, 0, min(2, $at)) . str_repeat('*', max(0, $at - 2)) . substr($email, $at)
            : '';
        return [
            'username'          => $u['username'],
            'email_enmascarado' => $emailMasked,
            'role'              => $u['role'],
            'active'            => (int) $u['active'],
            'whatsapp_ok'       => (!empty($u['phone']) && !empty($u['whatsapp_apikey'])),
            'password_set'      => ((int) $u['pass_len']) > 0,
        ];
    }, $rows ?: []);
    jsonOut(['encontrados' => count($out), 'detalle' => $out]);
}

// GET diag_verify — confirma si una contraseña puntual coincide con el hash
// guardado, sin exponer el hash. Comparte el mismo contador de rate-limit
// que el login real para no convertirse en un oráculo de fuerza bruta.
if ($method === 'GET' && $action === 'diag_verify') {
    $login = trim($_GET['login'] ?? '');
    $pass  = trim($_GET['password'] ?? '');
    if (!$login || !$pass) jsonErr('Usá ?login=usuario_o_email&password=clave');

    $ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $rateKey = 'rate_login_' . substr(hash('sha256', $ip), 0, 20);
    $maxAttempts = 10;
    $windowSec   = 900;
    $rateData    = null;
    try {
        $rateSt = $db->prepare("SELECT meta_value FROM ".tb('app_settings')." WHERE meta_key=? LIMIT 1");
        $rateSt->execute([$rateKey]);
        $rateRow = $rateSt->fetch();
        $rateData = $rateRow ? json_decode($rateRow['meta_value'], true) : null;
        if (!is_array($rateData)) $rateData = ['count' => 0, 'since' => time()];
        if ((time() - ($rateData['since'] ?? 0)) >= $windowSec) $rateData = ['count' => 0, 'since' => time()];
        if ($rateData['count'] >= $maxAttempts) {
            $wait = $windowSec - (time() - $rateData['since']);
            jsonErr('Demasiados intentos fallidos. Esperá ' . ceil($wait / 60) . ' minuto(s).', 429);
        }
    } catch (Exception $e) { $rateData = null; }

    $st = $db->prepare("SELECT password FROM ".tb('users')." WHERE (username=? OR email=?) AND active=1");
    $st->execute([$login, $login]);
    $user = $st->fetch();
    $matches = ($user && password_verify($pass, $user['password']));

    if ($rateData !== null && !$matches) {
        $rateData['count']++;
        try {
            $db->prepare("INSERT INTO ".tb('app_settings')." (meta_key,meta_value) VALUES (?,?) ON DUPLICATE KEY UPDATE meta_value=?")
               ->execute([$rateKey, json_encode($rateData), json_encode($rateData)]);
        } catch (Exception $e) {}
    }

    jsonOut([
        'usuario_encontrado' => (bool) $user,
        'password_matches'   => $matches,
    ]);
}

// GET fix_password_column — ensancha la columna `password` a VARCHAR(255)
// si quedó definida más angosta. Un hash bcrypt mide 60 caracteres: si la
// columna es más chica, cada UPDATE de contraseña lo trunca en silencio y
// el hash queda corrupto para siempre, sin importar que la clave enviada
// sea la correcta. Esto pasa en cuentas creadas con una versión vieja del
// plugin, porque la definición de columna solo se aplica al activar el
// plugin por primera vez, no al subir un ZIP nuevo encima. Operación
// segura: solo ensancha, nunca acorta ni borra datos existentes.
if ($method === 'GET' && $action === 'fix_password_column') {
    $table = tb('users');
    $st = $db->prepare("SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME='password'");
    $st->execute([$table]);
    $before = $st->fetchColumn();
    $before = ($before !== false && $before !== null) ? (int) $before : null;

    $changed = false;
    if ($before !== null && $before < 255) {
        $db->exec("ALTER TABLE `{$table}` MODIFY COLUMN `password` VARCHAR(255) NOT NULL");
        $changed = true;
    }

    jsonOut(['largo_anterior' => $before, 'ensanchada' => $changed]);
}

// GET diag_roundtrip — escribe un hash de prueba y lo vuelve a leer DENTRO
// del mismo request, y restaura el hash original al final (no cambia la
// contraseña real de la cuenta). Objetivo: separar dos causas posibles
// distintas — (a) el UPDATE se corrompe en la base (trigger, charset,
// etc.) o (b) el pedido real de "olvidé mi contraseña" del sitio ni
// siquiera está llegando a este código (caché/WAF/ruta equivocada).
if ($method === 'GET' && $action === 'diag_roundtrip') {
    $login = trim($_GET['login'] ?? '');
    if (!$login) jsonErr('Usá ?login=usuario_o_email');

    $st = $db->prepare("SELECT id, password FROM ".tb('users')." WHERE username=? OR email=?");
    $st->execute([$login, $login]);
    $user = $st->fetch();
    if (!$user) jsonErr('Usuario no encontrado');

    $original = $user['password'];
    $testPlain = 'roundtrip_' . bin2hex(random_bytes(4));
    $testHash  = password_hash($testPlain, PASSWORD_BCRYPT);

    $db->prepare("UPDATE ".tb('users')." SET password=? WHERE id=?")->execute([$testHash, $user['id']]);

    $st2 = $db->prepare("SELECT password FROM ".tb('users')." WHERE id=?");
    $st2->execute([$user['id']]);
    $after = $st2->fetchColumn();

    // Restaurar el hash original inmediatamente
    $db->prepare("UPDATE ".tb('users')." SET password=? WHERE id=?")->execute([$original, $user['id']]);

    jsonOut([
        'largo_hash_original'  => strlen($original),
        'largo_hash_escrito'   => strlen($testHash),
        'largo_hash_leido'     => $after ? strlen($after) : 0,
        'escritura_identica'   => ($after === $testHash),
        'verifica_tras_leer'   => $after ? password_verify($testPlain, $after) : false,
    ]);
}

// GET diag_whatsapp — dispara la MISMA condición de envío que usa
// forgot_password (buscar "Message Sent" o "Message queued" en la
// respuesta de CallMeBot) pero con un mensaje de diagnóstico, no con
// una contraseña. Sirve para
// ver la respuesta cruda de CallMeBot y confirmar si esa condición se
// cumple de verdad — si no se cumple, forgot_password nunca graba la
// clave nueva aunque el WhatsApp llegue igual.
if ($method === 'GET' && $action === 'diag_whatsapp') {
    $login = trim($_GET['login'] ?? '');
    if (!$login) jsonErr('Usá ?login=usuario_o_email');

    $st = $db->prepare("SELECT phone, whatsapp_apikey FROM ".tb('users')." WHERE username=? OR email=?");
    $st->execute([$login, $login]);
    $user = $st->fetch();
    if (!$user) jsonErr('Usuario no encontrado');
    if (empty($user['phone']) || empty($user['whatsapp_apikey'])) {
        jsonOut(['error' => 'Usuario sin phone/whatsapp_apikey configurados']);
    }

    $text = "🔧 Luna Workspace — mensaje de diagnóstico, podés ignorar esto.";
    $url  = 'https://api.callmebot.com/whatsapp.php?' . http_build_query([
        'phone'  => $user['phone'],
        'text'   => $text,
        'apikey' => $user['whatsapp_apikey'],
    ]);
    $ctx  = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
    $resp = @file_get_contents($url, false, $ctx);
    $sent = ($resp !== false && (stripos($resp, 'Message Sent') !== false || stripos($resp, 'Message queued') !== false));

    jsonOut([
        'resp_fue_false'    => ($resp === false),
        'respuesta_cruda'   => $resp === false ? null : $resp,
        'sent_detectado'    => $sent,
    ]);
}

// GET diag_last_reset — lee el registro que forgot_password guarda cada
// vez que se usa de verdad (no una simulación): si CallMeBot confirmó el
// envío, si el UPDATE afectó una fila, y si al releer inmediatamente el
// hash guardado verifica contra la clave recién generada. Nunca expone
// la contraseña ni el hash.
if ($method === 'GET' && $action === 'diag_last_reset') {
    $login = trim($_GET['login'] ?? '');
    if (!$login) jsonErr('Usá ?login=usuario_o_email');
    $st = $db->prepare("SELECT id FROM ".tb('users')." WHERE username=? OR email=?");
    $st->execute([$login, $login]);
    $user = $st->fetch();
    if (!$user) jsonErr('Usuario no encontrado');

    $st2 = $db->prepare("SELECT meta_value FROM ".tb('app_settings')." WHERE meta_key=?");
    $st2->execute(['last_reset_' . $user['id']]);
    $row = $st2->fetch();
    if (!$row) jsonOut(['existe' => false, 'mensaje' => 'Todavía no se usó "olvidé mi contraseña" con esta versión.']);

    jsonOut(['existe' => true] + json_decode($row['meta_value'], true));
}

jsonErr('Acción no encontrada', 404);
