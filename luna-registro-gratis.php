<?php
/**
 * Luna Workspace — Registro Plan Gratis
 * Colocar en: websobreruedas.com/luna-registro-gratis.php
 *
 * Tablas necesarias (se crean automáticamente al primer acceso):
 *   luna_free_pending  — OTPs pendientes de verificación
 *   luna_free_licenses — Licencias gratuitas emitidas
 */

// ── Configuración ──────────────────────────────────────────────────────────────
define('LR_DB_HOST',   'localhost');
define('LR_DB_NAME',   'websobreruedas_licencias');  // ajustar
define('LR_DB_USER',   'db_user');                   // ajustar
define('LR_DB_PASS',   'db_password');               // ajustar
define('LR_HMAC_KEY',  'ef1b13da2d745cd296dc66b90f950f440089b77080f3e3611f2000fdb162240d');

// Email remitente
define('LR_MAIL_FROM',     'no-reply@websobreruedas.com');
define('LR_MAIL_FROMNAME', 'Luna Workspace');

// WhatsApp Business — UltraMsg (obtener token en ultramsg.com)
// Dejar vacío para deshabilitar envío automático por WA
define('LR_WA_TOKEN',    '');       // ej: 'abc123xyz'
define('LR_WA_INSTANCE', '');       // ej: 'instance12345'

// Número propio de soporte (para que el usuario te escriba)
define('LR_WA_SOPORTE', '5491112345678'); // ajustar sin + ni espacios

// OTP expira en 10 minutos
define('LR_OTP_TTL', 600);

// ── Base de datos ──────────────────────────────────────────────────────────────
function lr_db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO(
        'mysql:host='.LR_DB_HOST.';dbname='.LR_DB_NAME.';charset=utf8mb4',
        LR_DB_USER, LR_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    // Crear tablas si no existen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS luna_free_pending (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            token      CHAR(64) NOT NULL UNIQUE,
            name       VARCHAR(100) NOT NULL,
            email      VARCHAR(180) NOT NULL,
            phone      VARCHAR(30)  NOT NULL,
            domain     VARCHAR(255) NOT NULL,
            otp        CHAR(6)      NOT NULL,
            attempts   TINYINT      DEFAULT 0,
            created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
            INDEX(token), INDEX(email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS luna_free_licenses (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            license_key  CHAR(36) NOT NULL UNIQUE,
            name         VARCHAR(100) NOT NULL,
            email        VARCHAR(180) NOT NULL,
            phone        VARCHAR(30)  NOT NULL,
            domain       VARCHAR(255) NOT NULL,
            plan         VARCHAR(20)  DEFAULT 'free',
            status       ENUM('active','revoked','expired') DEFAULT 'active',
            created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_verify  DATETIME DEFAULT NULL,
            INDEX(license_key), INDEX(email), INDEX(domain)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    return $pdo;
}

// ── Helpers ────────────────────────────────────────────────────────────────────
function lr_clean(string $v): string { return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8')); }
function lr_json(array $data): void  { header('Content-Type: application/json'); echo json_encode($data); exit; }

function lr_gen_otp(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function lr_gen_token(): string {
    return bin2hex(random_bytes(32));
}

function lr_gen_key(): string {
    // UUID v4
    $b = random_bytes(16);
    $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
    $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

function lr_clean_domain(string $url): string {
    $url = trim($url);
    if (!preg_match('/^https?:\/\//i', $url)) $url = 'https://' . $url;
    return strtolower(parse_url($url, PHP_URL_HOST) ?: '');
}

function lr_rate_limit(string $key, int $max, int $window): bool {
    // Implementación simple con base de datos o archivo
    $file = sys_get_temp_dir() . '/lr_rl_' . md5($key);
    $now  = time();
    $hits = [];
    if (file_exists($file)) {
        $hits = array_filter(json_decode(file_get_contents($file), true) ?: [], fn($t) => $t > $now - $window);
    }
    if (count($hits) >= $max) return false;
    $hits[] = $now;
    file_put_contents($file, json_encode(array_values($hits)), LOCK_EX);
    return true;
}

// ── Email ──────────────────────────────────────────────────────────────────────
function lr_send_otp_email(string $to, string $name, string $otp, string $domain): bool {
    $subject = "Tu código de verificación Luna Workspace: $otp";
    $body = lr_email_tpl($name, "
        <p>Ingresaste <strong>$domain</strong> como tu dominio para el Plan Gratis de Luna Workspace.</p>
        <p>Tu código de verificación es:</p>
        <div style='text-align:center;margin:32px 0'>
          <span style='font-size:42px;font-weight:900;letter-spacing:10px;color:#5b6af0;font-family:monospace'>$otp</span>
        </div>
        <p style='color:#8892c0;font-size:13px'>Este código expira en <strong>10 minutos</strong>. Si no solicitaste esto, ignorá este mensaje.</p>
    ");
    return lr_mail($to, $name, $subject, $body);
}

function lr_send_key_email(string $to, string $name, string $key, string $domain): bool {
    $subject = '🌙 Tu clave de Luna Workspace Plan Gratis';
    $body = lr_email_tpl($name, "
        <p>¡Bienvenido/a a <strong>Luna Workspace</strong>! Tu registro fue confirmado para el dominio:</p>
        <p style='text-align:center;color:#67e8f9;font-weight:700;font-size:16px;margin:16px 0'>$domain</p>
        <p>Tu clave de licencia Plan Gratis es:</p>
        <div style='background:#0f1322;border:1.5px solid rgba(91,106,240,.4);border-radius:12px;padding:20px;text-align:center;margin:24px 0'>
          <code style='font-size:15px;letter-spacing:2px;color:#a5b4fc;font-family:monospace;word-break:break-all'>$key</code>
        </div>
        <p><strong>Cómo activar:</strong></p>
        <ol style='color:#8892c0;font-size:13px;padding-left:20px;line-height:2'>
          <li>Instalá el plugin <strong>luna-workspace.zip</strong> en WordPress</li>
          <li>Andá a <strong>Luna Workspace → Licencia</strong> en el menú de admin</li>
          <li>Pegá la clave y guardá</li>
        </ol>
        <p style='color:#8892c0;font-size:12px;margin-top:24px'>
          Plan Gratis incluye: 1 usuario · 1 pizarra · tareas ilimitadas · calendario.<br>
          Para más usuarios o funciones, visitá <a href='https://websobreruedas.com/luna' style='color:#5b6af0'>websobreruedas.com/luna</a>
        </p>
    ");
    return lr_mail($to, $name, $subject, $body);
}

function lr_mail(string $to, string $name, string $subject, string $htmlBody): bool {
    $from     = LR_MAIL_FROM;
    $fromName = LR_MAIL_FROMNAME;
    $headers  = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        'From: =?UTF-8?B?' . base64_encode($fromName) . "?= <$from>",
        'Reply-To: ' . $from,
        'X-Mailer: Luna-Register/1.0',
    ]);
    $enc = chunk_split(base64_encode($htmlBody));
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $enc, $headers);
}

function lr_email_tpl(string $name, string $content): string {
    $firstName = explode(' ', $name)[0];
    return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#080b14;font-family:'Segoe UI',Arial,sans-serif">
<div style="max-width:560px;margin:40px auto;background:#0f1322;border:1px solid #1e2540;border-radius:16px;overflow:hidden">
  <div style="background:linear-gradient(135deg,#0a0d1a,#111630);padding:32px;text-align:center;border-bottom:1px solid #1e2540">
    <div style="font-size:28px;margin-bottom:8px">🌙</div>
    <div style="font-size:20px;font-weight:900;color:#e8edf8;letter-spacing:-0.5px">Luna Workspace</div>
    <div style="font-size:12px;color:#3d4470;margin-top:4px">websobreruedas.com</div>
  </div>
  <div style="padding:32px;color:#8892c0;font-size:14px;line-height:1.7">
    <p style="color:#e8edf8;font-size:15px;font-weight:700;margin-bottom:16px">Hola, $firstName 👋</p>
    $content
  </div>
  <div style="padding:20px 32px;border-top:1px solid #1e2540;text-align:center;font-size:11px;color:#3d4470">
    Luna Workspace · websobreruedas.com · Este email fue enviado porque solicitaste una licencia gratuita.
  </div>
</div>
</body></html>
HTML;
}

// ── WhatsApp (UltraMsg) ────────────────────────────────────────────────────────
function lr_send_whatsapp(string $phone, string $msg): bool {
    if (!LR_WA_TOKEN || !LR_WA_INSTANCE) return false;
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (!$phone) return false;
    $url  = "https://api.ultramsg.com/" . LR_WA_INSTANCE . "/messages/chat";
    $data = http_build_query(['token' => LR_WA_TOKEN, 'to' => $phone, 'body' => $msg]);
    $ctx  = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $data,
        'timeout' => 10,
        'ignore_errors' => true,
    ]]);
    $r = @file_get_contents($url, false, $ctx);
    return $r !== false;
}

// ── Acción AJAX: Paso 1 — Enviar OTP ──────────────────────────────────────────
function action_send_otp(): void {
    $name   = lr_clean($_POST['name']   ?? '');
    $email  = filter_var(trim($_POST['email']  ?? ''), FILTER_VALIDATE_EMAIL);
    $phone  = preg_replace('/[^0-9+\s\-()]/', '', $_POST['phone'] ?? '');
    $domain = lr_clean_domain($_POST['domain'] ?? '');

    if (!$name)   lr_json(['ok' => false, 'field' => 'name',   'msg' => 'Ingresá tu nombre completo.']);
    if (!$email)  lr_json(['ok' => false, 'field' => 'email',  'msg' => 'Email inválido.']);
    if (!$phone || strlen(preg_replace('/\D/', '', $phone)) < 8)
                  lr_json(['ok' => false, 'field' => 'phone',  'msg' => 'Teléfono inválido (mínimo 8 dígitos).']);
    if (!$domain) lr_json(['ok' => false, 'field' => 'domain', 'msg' => 'Dominio inválido.']);

    // Rate limit: 3 intentos por IP cada 10 minutos
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!lr_rate_limit('otp_ip_' . $ip, 3, 600))
        lr_json(['ok' => false, 'msg' => 'Demasiados intentos. Esperá 10 minutos.']);

    $db = lr_db();

    // Dominio ya registrado?
    $chk = $db->prepare("SELECT id FROM luna_free_licenses WHERE domain = ? AND status = 'active'");
    $chk->execute([$domain]);
    if ($chk->fetch())
        lr_json(['ok' => false, 'field' => 'domain', 'msg' => 'Ese dominio ya tiene una licencia activa. Revisá tu email o escribinos.']);

    // Email ya registrado?
    $chkE = $db->prepare("SELECT id FROM luna_free_licenses WHERE email = ? AND status = 'active'");
    $chkE->execute([$email]);
    if ($chkE->fetch())
        lr_json(['ok' => false, 'field' => 'email', 'msg' => 'Ese email ya tiene una licencia activa. Revisá tu bandeja de entrada.']);

    // Borrar pendientes viejos
    $db->prepare("DELETE FROM luna_free_pending WHERE email = ? OR created_at < DATE_SUB(NOW(), INTERVAL 20 MINUTE)")->execute([$email]);

    $otp   = lr_gen_otp();
    $token = lr_gen_token();

    $db->prepare("INSERT INTO luna_free_pending (token, name, email, phone, domain, otp) VALUES (?,?,?,?,?,?)")
       ->execute([$token, $name, $email, $phone, $domain, $otp]);

    if (!lr_send_otp_email($email, $name, $otp, $domain))
        lr_json(['ok' => false, 'msg' => 'No se pudo enviar el email. Verificá la dirección.']);

    lr_json(['ok' => true, 'token' => $token, 'email_hint' => substr($email, 0, 3) . '***']);
}

// ── Acción AJAX: Paso 2 — Verificar OTP y emitir clave ────────────────────────
function action_verify_otp(): void {
    $token = preg_replace('/[^a-f0-9]/', '', $_POST['token'] ?? '');
    $otp   = preg_replace('/\D/', '', $_POST['otp'] ?? '');

    if (!$token || !$otp)
        lr_json(['ok' => false, 'msg' => 'Datos incompletos.']);

    // Rate limit: 5 intentos por token
    if (!lr_rate_limit('otp_tok_' . $token, 5, 600))
        lr_json(['ok' => false, 'msg' => 'Demasiados intentos. Solicitá un nuevo código.']);

    $db = lr_db();
    $row = $db->prepare("SELECT * FROM luna_free_pending WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $row->execute([$token]);
    $pending = $row->fetch();

    if (!$pending)
        lr_json(['ok' => false, 'msg' => 'El código expiró. Iniciá el registro nuevamente.', 'expired' => true]);

    // Incrementar intentos
    $db->prepare("UPDATE luna_free_pending SET attempts = attempts + 1 WHERE token = ?")->execute([$token]);

    if ($pending['otp'] !== $otp) {
        $left = 5 - (int)$pending['attempts'];
        lr_json(['ok' => false, 'msg' => "Código incorrecto. Intentos restantes: $left."]);
    }

    // OTP correcto → generar licencia
    $key = lr_gen_key();

    $db->prepare("INSERT INTO luna_free_licenses (license_key, name, email, phone, domain) VALUES (?,?,?,?,?)")
       ->execute([$key, $pending['name'], $pending['email'], $pending['phone'], $pending['domain']]);

    // Limpiar pendiente
    $db->prepare("DELETE FROM luna_free_pending WHERE token = ?")->execute([$token]);

    // Enviar clave por email
    lr_send_key_email($pending['email'], $pending['name'], $key, $pending['domain']);

    // Enviar clave por WhatsApp si está configurado
    $firstName = explode(' ', $pending['name'])[0];
    $waMsg = "🌙 *Luna Workspace — Plan Gratis*\n\n"
           . "Hola {$firstName}! Tu registro fue confirmado ✅\n\n"
           . "*Dominio:* {$pending['domain']}\n"
           . "*Tu clave de licencia:*\n`$key`\n\n"
           . "Pegala en _Luna Workspace → Licencia_ en tu panel de WordPress.\n\n"
           . "¿Dudas? Escribinos 👇\nwa.me/" . LR_WA_SOPORTE;
    lr_send_whatsapp($pending['phone'], $waMsg);

    lr_json(['ok' => true, 'key' => $key, 'name' => $pending['name'], 'domain' => $pending['domain']]);
}

// ── Reenviar OTP ───────────────────────────────────────────────────────────────
function action_resend_otp(): void {
    $token = preg_replace('/[^a-f0-9]/', '', $_POST['token'] ?? '');
    if (!$token) lr_json(['ok' => false, 'msg' => 'Token inválido.']);

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!lr_rate_limit('resend_' . $ip, 2, 600))
        lr_json(['ok' => false, 'msg' => 'Límite de reenvíos alcanzado. Esperá 10 minutos.']);

    $db  = lr_db();
    $row = $db->prepare("SELECT * FROM luna_free_pending WHERE token = ?");
    $row->execute([$token]);
    $p = $row->fetch();
    if (!$p) lr_json(['ok' => false, 'msg' => 'Sesión expirada. Iniciá el registro nuevamente.', 'expired' => true]);

    $otp = lr_gen_otp();
    $db->prepare("UPDATE luna_free_pending SET otp = ?, attempts = 0, created_at = NOW() WHERE token = ?")->execute([$otp, $token]);
    lr_send_otp_email($p['email'], $p['name'], $otp, $p['domain']);
    lr_json(['ok' => true]);
}

// ── Router AJAX ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action'])) {
    match($_POST['_action']) {
        'send_otp'    => action_send_otp(),
        'verify_otp'  => action_verify_otp(),
        'resend_otp'  => action_resend_otp(),
        default       => lr_json(['ok' => false, 'msg' => 'Acción desconocida.']),
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Luna Workspace — Registro Plan Gratis</title>
<style>
:root{
  --acc:#5b6af0;--acc2:#8b5cf6;--grn:#22c55e;--red:#ef4444;--ora:#f59e0b;
  --bg:#080b14;--surf:#0f1322;--elev:#161b2e;--bdr:#1e2540;--bdrl:#2a3260;
  --t1:#e8edf8;--t2:#8892c0;--t3:#3d4470;--f:'Segoe UI',system-ui,sans-serif;
  --r:10px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--t1);font-family:var(--f);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px}

/* glow bg */
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 50% -10%,rgba(91,106,240,.18),transparent);pointer-events:none}

/* card */
.card{background:var(--surf);border:1px solid var(--bdr);border-radius:20px;width:100%;max-width:480px;overflow:hidden;position:relative;z-index:1;box-shadow:0 24px 80px rgba(0,0,0,.5)}

/* header */
.card-head{background:linear-gradient(135deg,#0a0d1a,#111630);padding:36px 32px 28px;text-align:center;border-bottom:1px solid var(--bdr);position:relative}
.card-head::after{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:200px;height:1px;background:linear-gradient(90deg,transparent,rgba(91,106,240,.5),transparent)}
.moon{font-size:36px;margin-bottom:10px;display:block}
.brand{font-size:20px;font-weight:900;letter-spacing:-.3px;color:var(--t1)}
.brand span{background:linear-gradient(135deg,#a5b4fc,#67e8f9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.tagline{font-size:12px;color:var(--t3);margin-top:4px}

/* steps indicator */
.steps{display:flex;align-items:center;justify-content:center;gap:0;padding:20px 32px 0}
.step-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;border:1.5px solid var(--bdr);color:var(--t3);background:var(--elev);transition:.3s;flex-shrink:0}
.step-dot.active{background:var(--acc);border-color:var(--acc);color:#fff;box-shadow:0 0 16px rgba(91,106,240,.5)}
.step-dot.done{background:var(--grn);border-color:var(--grn);color:#fff}
.step-line{flex:1;height:1.5px;background:var(--bdr);transition:.3s;max-width:60px}
.step-line.done{background:var(--grn)}
.step-lbl{display:flex;justify-content:space-between;padding:6px 32px 0;font-size:10px;color:var(--t3);font-weight:700;text-transform:uppercase;letter-spacing:.4px}

/* body */
.card-body{padding:28px 32px 32px}
.step-title{font-size:17px;font-weight:800;color:var(--t1);margin-bottom:4px}
.step-sub{font-size:12px;color:var(--t2);margin-bottom:22px;line-height:1.55}

/* form */
.field{margin-bottom:16px}
.field label{display:block;font-size:11px;font-weight:700;color:var(--t2);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.field input{width:100%;background:var(--elev);border:1.5px solid var(--bdr);border-radius:var(--r);padding:11px 14px;color:var(--t1);font-size:14px;font-family:var(--f);outline:none;transition:.2s;caret-color:var(--acc)}
.field input:focus{border-color:var(--acc);box-shadow:0 0 0 3px rgba(91,106,240,.15)}
.field input.err{border-color:var(--red)}
.field input::placeholder{color:var(--t3)}
.field-hint{font-size:11px;color:var(--t3);margin-top:5px}
.field-err{font-size:11px;color:var(--red);margin-top:5px;display:none}
.field.has-err .field-err{display:block}
.field.has-err input{border-color:var(--red)}

/* otp input */
.otp-wrap{display:flex;gap:8px;justify-content:center;margin:8px 0 4px}
.otp-wrap input{width:48px;height:56px;text-align:center;font-size:22px;font-weight:900;font-family:monospace;background:var(--elev);border:1.5px solid var(--bdr);border-radius:var(--r);color:var(--t1);outline:none;transition:.2s;caret-color:var(--acc)}
.otp-wrap input:focus{border-color:var(--acc);box-shadow:0 0 0 3px rgba(91,106,240,.15)}
.otp-wrap.err input{border-color:var(--red)}

/* btn */
.btn{width:100%;padding:13px;border-radius:var(--r);border:none;font-size:14px;font-weight:800;font-family:var(--f);cursor:pointer;transition:.2s;position:relative;overflow:hidden}
.btn-primary{background:linear-gradient(135deg,var(--acc),var(--acc2));color:#fff;box-shadow:0 4px 20px rgba(91,106,240,.35)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 28px rgba(91,106,240,.5)}
.btn-primary:active{transform:translateY(0)}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;transform:none}
.btn-ghost{background:transparent;color:var(--t2);border:1.5px solid var(--bdr);margin-top:10px;font-size:12px;padding:10px}
.btn-ghost:hover{border-color:var(--bdrl);color:var(--t1)}

/* spinner */
.spin{display:none;width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;margin:0 auto}
@keyframes spin{to{transform:rotate(360deg)}}
.btn.loading .btn-text{display:none}
.btn.loading .spin{display:block}

/* global error */
.g-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:var(--r);padding:12px 14px;font-size:12px;color:#fca5a5;margin-bottom:16px;display:none}
.g-err.show{display:block}

/* success */
.success-wrap{text-align:center;padding:8px 0}
.success-icon{font-size:48px;margin-bottom:16px}
.success-title{font-size:20px;font-weight:900;color:var(--grn);margin-bottom:8px}
.success-sub{font-size:13px;color:var(--t2);margin-bottom:24px;line-height:1.6}
.key-box{background:var(--elev);border:1.5px solid rgba(91,106,240,.35);border-radius:var(--r);padding:16px;font-family:monospace;font-size:13px;color:#a5b4fc;letter-spacing:1px;word-break:break-all;position:relative;cursor:pointer;transition:.2s;text-align:left}
.key-box:hover{border-color:var(--acc)}
.key-box-lbl{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--t3);margin-bottom:8px}
.copy-tip{font-size:11px;color:var(--t3);margin-top:8px;text-align:center}
.wa-link{display:inline-flex;align-items:center;gap:8px;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#86efac;padding:10px 20px;border-radius:99px;font-size:12px;font-weight:700;text-decoration:none;margin-top:16px;transition:.2s}
.wa-link:hover{background:rgba(34,197,94,.2)}
.plan-info{background:var(--elev);border-radius:var(--r);padding:14px;margin-top:20px;font-size:11px;color:var(--t2);line-height:1.7}
.plan-info ul{list-style:none;padding:0}
.plan-info ul li::before{content:'✓ ';color:var(--grn);font-weight:700}

/* resend */
.resend-row{text-align:center;margin-top:14px;font-size:12px;color:var(--t3)}
.resend-row a{color:var(--acc);cursor:pointer;text-decoration:none;font-weight:700}
.resend-row a:hover{text-decoration:underline}
#resend-timer{font-weight:700;color:var(--t2)}

/* disclaimer */
.disclaimer{font-size:10px;color:var(--t3);text-align:center;margin-top:20px;line-height:1.5;padding:0 4px}
.disclaimer a{color:var(--t2)}

@media(max-width:520px){
  .card{border-radius:16px}
  .card-head,.card-body{padding-left:20px;padding-right:20px}
  .steps,.step-lbl{padding-left:20px;padding-right:20px}
  .otp-wrap input{width:40px;height:50px;font-size:18px}
}
</style>
</head>
<body>

<div class="card">

  <!-- Header -->
  <div class="card-head">
    <span class="moon">🌙</span>
    <div class="brand">Luna <span>Workspace</span></div>
    <div class="tagline">Registro · Plan Gratis</div>
  </div>

  <!-- Steps indicator -->
  <div class="steps">
    <div class="step-dot active" id="dot-1">1</div>
    <div class="step-line" id="line-1"></div>
    <div class="step-dot" id="dot-2">2</div>
    <div class="step-line" id="line-2"></div>
    <div class="step-dot" id="dot-3">3</div>
  </div>
  <div class="step-lbl">
    <span>Tus datos</span>
    <span>Verificación</span>
    <span>¡Listo!</span>
  </div>

  <!-- Body -->
  <div class="card-body">

    <!-- PASO 1: Formulario -->
    <div id="step-1">
      <div class="step-title">Crear cuenta gratis</div>
      <p class="step-sub">Completá tus datos. Recibirás un código de verificación por email.</p>

      <div class="g-err" id="err-1"></div>

      <div class="field" id="f-name">
        <label>Nombre completo</label>
        <input type="text" id="inp-name" placeholder="María García" autocomplete="name">
        <div class="field-err" id="e-name"></div>
      </div>
      <div class="field" id="f-email">
        <label>Email</label>
        <input type="email" id="inp-email" placeholder="maria@empresa.com" autocomplete="email">
        <div class="field-err" id="e-email"></div>
      </div>
      <div class="field" id="f-phone">
        <label>Teléfono WhatsApp</label>
        <input type="tel" id="inp-phone" placeholder="+54 9 11 1234 5678" autocomplete="tel">
        <div class="field-hint">Con código de país. Ejemplo: +54911234567</div>
        <div class="field-err" id="e-phone"></div>
      </div>
      <div class="field" id="f-domain">
        <label>Dominio de tu sitio WordPress</label>
        <input type="text" id="inp-domain" placeholder="miempresa.com" autocomplete="url">
        <div class="field-hint">La licencia quedará vinculada a este dominio.</div>
        <div class="field-err" id="e-domain"></div>
      </div>

      <button class="btn btn-primary" id="btn-1" onclick="sendOtp()">
        <span class="btn-text">Enviar código de verificación</span>
        <div class="spin"></div>
      </button>

      <div class="disclaimer">
        Al registrarte aceptás que almacenemos tu email y teléfono para gestionar tu licencia.<br>
        No compartimos tus datos con terceros.
      </div>
    </div>

    <!-- PASO 2: OTP -->
    <div id="step-2" style="display:none">
      <div class="step-title">Verificá tu email</div>
      <p class="step-sub" id="otp-sub">Ingresá el código de 6 dígitos que enviamos a tu email.</p>

      <div class="g-err" id="err-2"></div>

      <div class="otp-wrap" id="otp-inputs">
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
      </div>

      <button class="btn btn-primary" id="btn-2" style="margin-top:20px" onclick="verifyOtp()">
        <span class="btn-text">Verificar y obtener mi clave</span>
        <div class="spin"></div>
      </button>
      <button class="btn btn-ghost" onclick="backToStep1()">← Cambiar datos</button>

      <div class="resend-row">
        ¿No llegó? <a id="resend-link" onclick="resendOtp()" style="display:none">Reenviar código</a>
        <span id="resend-timer"></span>
      </div>
    </div>

    <!-- PASO 3: Éxito -->
    <div id="step-3" style="display:none">
      <div class="success-wrap">
        <div class="success-icon">🎉</div>
        <div class="success-title">¡Ya tenés tu clave!</div>
        <p class="success-sub">Te enviamos la clave por email<span id="wa-sent"> y por WhatsApp</span>. También podés copiarla desde acá:</p>

        <div class="key-box-lbl">Tu clave de licencia Plan Gratis</div>
        <div class="key-box" id="key-display" onclick="copyKey()" title="Clic para copiar"></div>
        <div class="copy-tip" id="copy-tip">Clic para copiar</div>

        <a class="wa-link" id="wa-support-link" href="#" target="_blank">
          📲 Escribinos por WhatsApp si necesitás ayuda
        </a>

        <div class="plan-info">
          <strong style="color:var(--t1);font-size:12px">Plan Gratis incluye:</strong>
          <ul style="margin-top:8px">
            <li>1 usuario</li>
            <li>1 pizarra kanban</li>
            <li>Tareas y calendario ilimitados</li>
            <li>Modo oscuro y temas</li>
          </ul>
          <p style="margin-top:10px;color:var(--t3)">
            Para agregar más usuarios o recibir notificaciones por WhatsApp, pasá al
            <a href="https://websobreruedas.com/luna" style="color:var(--acc)">Plan Básico desde $19/mes</a>.
          </p>
        </div>
      </div>
    </div>

  </div><!-- /card-body -->
</div><!-- /card -->

<script>
const $ = id => document.getElementById(id);
let SESSION_TOKEN = '';
let resendInterval = null;

// ── OTP inputs: navegación automática ──
document.querySelectorAll('#otp-inputs input').forEach((inp, i, all) => {
  inp.addEventListener('input', e => {
    const v = e.target.value.replace(/\D/,'');
    e.target.value = v;
    if (v && i < all.length - 1) all[i+1].focus();
    checkOtpFull();
  });
  inp.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !e.target.value && i > 0) all[i-1].focus();
  });
  inp.addEventListener('paste', e => {
    e.preventDefault();
    const digits = (e.clipboardData.getData('text')||'').replace(/\D/g,'').slice(0,6);
    [...digits].forEach((d,j) => { if(all[j]) all[j].value = d; });
    if (all[digits.length-1]) all[digits.length-1].focus();
    checkOtpFull();
  });
});

function getOtp() {
  return [...document.querySelectorAll('#otp-inputs input')].map(i=>i.value).join('');
}

function checkOtpFull() {
  $('btn-2').disabled = getOtp().length < 6;
}

// ── Paso 1: Enviar OTP ──
async function sendOtp() {
  clearFieldErrors();
  const name   = $('inp-name').value.trim();
  const email  = $('inp-email').value.trim();
  const phone  = $('inp-phone').value.trim();
  const domain = $('inp-domain').value.trim();

  setLoading('btn-1', true);
  try {
    const fd = new FormData();
    fd.append('_action','send_otp');
    fd.append('name', name);
    fd.append('email', email);
    fd.append('phone', phone);
    fd.append('domain', domain);

    const r = await fetch('', { method:'POST', body: fd });
    const d = await r.json();

    if (!d.ok) {
      if (d.field) showFieldErr(d.field, d.msg);
      else showGlobalErr('err-1', d.msg);
      return;
    }

    SESSION_TOKEN = d.token;
    $('otp-sub').textContent = `Ingresá el código de 6 dígitos que enviamos a ${d.email_hint}...`;
    goStep(2);
    startResendTimer(60);
    document.querySelectorAll('#otp-inputs input')[0].focus();
    $('btn-2').disabled = true;

  } catch(e) {
    showGlobalErr('err-1', 'Error de conexión. Intentá nuevamente.');
  } finally {
    setLoading('btn-1', false);
  }
}

// ── Paso 2: Verificar OTP ──
async function verifyOtp() {
  const otp = getOtp();
  if (otp.length < 6) return;

  setLoading('btn-2', true);
  $('otp-inputs').classList.remove('err');

  try {
    const fd = new FormData();
    fd.append('_action','verify_otp');
    fd.append('token', SESSION_TOKEN);
    fd.append('otp', otp);

    const r = await fetch('', { method:'POST', body: fd });
    const d = await r.json();

    if (!d.ok) {
      $('otp-inputs').classList.add('err');
      if (d.expired) { showGlobalErr('err-2', d.msg); backToStep1(); return; }
      showGlobalErr('err-2', d.msg);
      document.querySelectorAll('#otp-inputs input').forEach(i=>i.value='');
      document.querySelectorAll('#otp-inputs input')[0].focus();
      return;
    }

    // Éxito
    $('key-display').textContent = d.key;
    $('wa-support-link').href = `https://wa.me/<?= LR_WA_SOPORTE ?>?text=${encodeURIComponent('Hola! Acabo de activar Luna Workspace Plan Gratis en ' + d.domain + ' y necesito ayuda.')}`;
    clearInterval(resendInterval);
    goStep(3);

  } catch(e) {
    showGlobalErr('err-2', 'Error de conexión. Intentá nuevamente.');
  } finally {
    setLoading('btn-2', false);
  }
}

// ── Reenviar OTP ──
async function resendOtp() {
  $('resend-link').style.display = 'none';
  try {
    const fd = new FormData();
    fd.append('_action','resend_otp');
    fd.append('token', SESSION_TOKEN);
    const r = await fetch('', { method:'POST', body: fd });
    const d = await r.json();
    if (!d.ok) {
      if (d.expired) { backToStep1(); return; }
      showGlobalErr('err-2', d.msg);
    } else {
      document.querySelectorAll('#otp-inputs input').forEach(i=>i.value='');
      document.querySelectorAll('#otp-inputs input')[0].focus();
      $('otp-inputs').classList.remove('err');
      hideGlobalErr('err-2');
      startResendTimer(90);
    }
  } catch(e) {
    showGlobalErr('err-2', 'Error al reenviar. Intentá nuevamente.');
  }
}

// ── Timer de reenvío ──
function startResendTimer(sec) {
  clearInterval(resendInterval);
  $('resend-link').style.display = 'none';
  $('resend-timer').textContent  = `Podés reenviar en ${sec}s`;
  resendInterval = setInterval(() => {
    sec--;
    if (sec <= 0) {
      clearInterval(resendInterval);
      $('resend-timer').textContent  = '';
      $('resend-link').style.display = 'inline';
    } else {
      $('resend-timer').textContent = `Podés reenviar en ${sec}s`;
    }
  }, 1000);
}

// ── Copiar clave ──
function copyKey() {
  const key = $('key-display').textContent;
  navigator.clipboard.writeText(key).then(() => {
    $('copy-tip').textContent = '✓ Copiado!';
    $('copy-tip').style.color = 'var(--grn)';
    setTimeout(() => { $('copy-tip').textContent = 'Clic para copiar'; $('copy-tip').style.color = ''; }, 2000);
  });
}

// ── Navegación entre pasos ──
function goStep(n) {
  [1,2,3].forEach(i => $(`step-${i}`).style.display = i===n ? '' : 'none');
  // dots
  [1,2,3].forEach(i => {
    const dot  = $(`dot-${i}`);
    dot.className = 'step-dot' + (i < n ? ' done' : i === n ? ' active' : '');
    if (i < n) dot.textContent = '✓';
    else dot.textContent = i;
  });
  // lines
  [1,2].forEach(i => {
    $(`line-${i}`).className = 'step-line' + (i < n ? ' done' : '');
  });
}

function backToStep1() {
  clearInterval(resendInterval);
  SESSION_TOKEN = '';
  document.querySelectorAll('#otp-inputs input').forEach(i=>i.value='');
  hideGlobalErr('err-2');
  goStep(1);
}

// ── UI helpers ──
function setLoading(btnId, on) {
  const btn = $(btnId);
  if (on) { btn.classList.add('loading'); btn.disabled = true; }
  else    { btn.classList.remove('loading'); btn.disabled = false; }
}

function showFieldErr(field, msg) {
  const f = $(`f-${field}`), e = $(`e-${field}`);
  if (f) f.classList.add('has-err');
  if (e) { e.textContent = msg; e.style.display = 'block'; }
}

function clearFieldErrors() {
  ['name','email','phone','domain'].forEach(f => {
    const el = $(`f-${f}`), er = $(`e-${f}`);
    if (el) el.classList.remove('has-err');
    if (er) er.style.display = 'none';
  });
  hideGlobalErr('err-1');
}

function showGlobalErr(id, msg) {
  const el = $(id);
  if (el) { el.textContent = msg; el.classList.add('show'); }
}

function hideGlobalErr(id) {
  const el = $(id);
  if (el) el.classList.remove('show');
}

// ── Enter en campos ──
['inp-name','inp-email','inp-phone','inp-domain'].forEach(id => {
  $(id)?.addEventListener('keydown', e => { if(e.key==='Enter') sendOtp(); });
});
</script>

</body>
</html>
