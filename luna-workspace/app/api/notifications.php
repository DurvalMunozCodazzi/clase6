<?php
require_once '../config.php';
$me     = requireAuth();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── GET unread notifications ──────────────────
if ($method === 'GET' && $action === 'list') {
    $st = $db->prepare("
        SELECT n.*, u.name as from_name, u.color as from_color,
               c.title as card_title, w.name as workspace_name
        FROM ".tb('notifications')." n
        LEFT JOIN ".tb('users')." u ON u.id = n.from_user_id
        LEFT JOIN ".tb('cards')." c ON c.id = n.card_id
        LEFT JOIN ".tb('workspaces')." w ON w.id = n.workspace_id
        WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT 50
    ");
    $st->execute([$me['id']]);
    jsonOut(['notifications' => $st->fetchAll()]);
}

// ── POST mark as read ─────────────────────────
if ($method === 'POST' && $action === 'read') {
    $b  = json_decode(file_get_contents('php://input'), true);
    $id = intval($b['id'] ?? 0);
    if ($id) {
        $db->prepare("UPDATE ".tb('notifications')." SET is_read=1 WHERE id=? AND user_id=?")->execute([$id, $me['id']]);
    } else {
        $db->prepare("UPDATE ".tb('notifications')." SET is_read=1 WHERE user_id=?")->execute([$me['id']]);
    }
    jsonOut(['ok' => true]);
}

// ── POST mark all read ────────────────────────
if ($method === 'POST' && $action === 'read_all') {
    $db->prepare("UPDATE ".tb('notifications')." SET is_read=1 WHERE user_id=?")->execute([$me['id']]);
    jsonOut(['ok' => true]);
}

// ── GET unread count ──────────────────────────
if ($method === 'GET' && $action === 'count') {
    $count = $db->prepare("SELECT COUNT(*) FROM ".tb('notifications')." WHERE user_id=? AND is_read=0");
    $count->execute([$me['id']]);
    jsonOut(['count' => (int)$count->fetchColumn()]);
}

jsonErr('Acción no encontrada', 404);
