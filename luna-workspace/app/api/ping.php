<?php
// Diagnóstico de conexión — eliminar después de resolver el problema
require_once '../config.php';
$out = [
    'php'        => PHP_VERSION,
    'db_host'    => defined('DB_HOST') ? DB_HOST : 'NO DEFINIDO',
    'db_name'    => defined('DB_NAME') ? DB_NAME : 'NO DEFINIDO',
    'db_user'    => defined('DB_USER') ? DB_USER : 'NO DEFINIDO',
    'db_pass'    => defined('DB_PASS') ? (DB_PASS ? '****' : '(vacío)') : 'NO DEFINIDO',
    'tb_prefix'  => defined('LUNA_TB_PREFIX') ? LUNA_TB_PREFIX : 'NO DEFINIDO',
    'cred_file'  => file_exists(__DIR__ . '/../luna-wp-config.php') ? 'OK' : 'FALTA',
];
try {
    $db = getDB();
    $out['db_connection'] = 'OK';
    $out['test_query']    = $db->query("SELECT COUNT(*) FROM " . tb('users'))->fetchColumn() . ' usuarios';
} catch (Exception $e) {
    $out['db_connection'] = 'ERROR: ' . $e->getMessage();
}
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
