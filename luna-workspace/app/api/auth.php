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

    $st = $db->prepare("SELECT * FROM ".tb('users')." WHERE (username=? OR email=?) AND active=1");
    $st->execute([$login, $login]);
    $user = $st->fetch();

    if (!$user || !password_verify($pass, $user['password'])) {
        jsonErr('Usuario o contraseña incorrectos', 401);
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

jsonErr('Acción no encontrada', 404);
