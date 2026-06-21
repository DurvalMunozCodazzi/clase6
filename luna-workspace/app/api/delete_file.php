<?php
require_once '../config.php';
$me = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') jsonErr('Método no permitido', 405);

$id = intval($_GET['id'] ?? 0);
if (!$id) jsonErr('ID requerido');

$db = getDB();
$att = $db->prepare("SELECT * FROM ".tb('attachments')." WHERE id=?");
$att->execute([$id]);
$row = $att->fetch();
if (!$row) jsonErr('Adjunto no encontrado', 404);

// Solo admin o el propietario de la tarjeta puede eliminar
if ($me['role'] !== 'admin') {
    $card = $db->prepare("SELECT c.id FROM ".tb('cards')." c WHERE c.id=?");
    $card->execute([$row['card_id']]);
    if (!$card->fetch()) jsonErr('Sin permisos', 403);
}

// Eliminar archivo físico si existe
if ($row['type'] === 'local' && $row['url']) {
    $filePath = dirname(__DIR__) . $row['url'];
    if (file_exists($filePath)) unlink($filePath);
}

$db->prepare("DELETE FROM ".tb('attachments')." WHERE id=?")->execute([$id]);
$db->prepare("UPDATE ".tb('cards')." SET updated_at=NOW() WHERE id=?")->execute([$row['card_id']]);

jsonOut(['ok' => true]);
