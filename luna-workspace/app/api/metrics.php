<?php
require_once '../config.php';
$me = requireAuth();
$db = getDB();

$wsId = intval($_SERVER['HTTP_X_WORKSPACE_ID'] ?? $_GET['ws'] ?? 1);

// ── GET dashboard metrics ─────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'dashboard';

if ($method === 'GET' && $action === 'dashboard') {
    // Total tasks
    $total = $db->prepare("SELECT COUNT(*) FROM ".tb('cards')." WHERE workspace_id=?");
    $total->execute([$wsId]);
    $totalCards = (int)$total->fetchColumn();

    // By column
    $byCols = $db->prepare("
        SELECT k.id, k.title, k.color, k.position, COUNT(c.id) as count
        FROM ".tb('columns_k')." k LEFT JOIN ".tb('cards')." c ON c.column_id=k.id AND c.workspace_id=?
        WHERE k.workspace_id=? GROUP BY k.id, k.title, k.color, k.position ORDER BY k.position
    ");
    $byCols->execute([$wsId, $wsId]);
    $byColumn = $byCols->fetchAll();

    // Completed (last column or title contains 'complet')
    $done = $db->prepare("
        SELECT COUNT(*) FROM ".tb('cards')." c
        JOIN ".tb('columns_k')." k ON k.id=c.column_id
        WHERE c.workspace_id=? AND (k.position=(SELECT MAX(position) FROM ".tb('columns_k')." WHERE workspace_id=?) OR k.title LIKE '%complet%')
    ");
    $done->execute([$wsId, $wsId]);
    $completed = (int)$done->fetchColumn();

    // Overdue — only pending tasks (excludes completed columns)
    $ov = $db->prepare("
        SELECT COUNT(*) FROM ".tb('cards')." c
        JOIN ".tb('columns_k')." k ON k.id = c.column_id
        WHERE c.workspace_id=? AND c.due_date < CURDATE() AND c.due_date IS NOT NULL
          AND k.title NOT LIKE '%complet%'
          AND k.position < (SELECT MAX(position) FROM ".tb('columns_k')." WHERE workspace_id=?)
    ");
    $ov->execute([$wsId, $wsId]);
    $overdue = (int)$ov->fetchColumn();

    // Due this week — only pending tasks
    $week = $db->prepare("
        SELECT COUNT(*) FROM ".tb('cards')." c
        JOIN ".tb('columns_k')." k ON k.id = c.column_id
        WHERE c.workspace_id=? AND c.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
          AND k.title NOT LIKE '%complet%'
          AND k.position < (SELECT MAX(position) FROM ".tb('columns_k')." WHERE workspace_id=?)
    ");
    $week->execute([$wsId, $wsId]);
    $dueWeek = (int)$week->fetchColumn();

    // By priority
    $byPrio = $db->prepare("
        SELECT COALESCE(priority,'none') as priority, COUNT(*) as count
        FROM ".tb('cards')." WHERE workspace_id=? GROUP BY priority
    ");
    $byPrio->execute([$wsId]);
    $byPriority = $byPrio->fetchAll();

    // By assignee (top 8)
    $byUser = $db->prepare("
        SELECT ca.user_id, u.name, u.color, u.photo, COUNT(ca.card_id) as count
        FROM ".tb('card_assignees')." ca
        JOIN ".tb('users')." u ON u.id=ca.user_id
        JOIN ".tb('cards')." c ON c.id=ca.card_id AND c.workspace_id=?
        GROUP BY ca.user_id, u.name, u.color, u.photo ORDER BY count DESC LIMIT 8
    ");
    $byUser->execute([$wsId]);
    $byAssignee = $byUser->fetchAll();

    // Completed per week (last 8 weeks)
    $weekly = $db->prepare("
        SELECT YEAR(c.updated_at) as yr, WEEK(c.updated_at) as wk, COUNT(*) as count
        FROM ".tb('cards')." c
        JOIN ".tb('columns_k')." k ON k.id=c.column_id
        WHERE c.workspace_id=?
          AND (k.title LIKE '%complet%' OR k.position=(SELECT MAX(position) FROM ".tb('columns_k')." WHERE workspace_id=?))
          AND c.updated_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
        GROUP BY yr, wk ORDER BY yr, wk
    ");
    $weekly->execute([$wsId, $wsId]);
    $weeklyData = $weekly->fetchAll();

    // Tasks created per week (last 8 weeks)
    $created = $db->prepare("
        SELECT YEAR(created_at) as yr, WEEK(created_at) as wk, COUNT(*) as count
        FROM ".tb('cards')." WHERE workspace_id=?
          AND created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
        GROUP BY yr, wk ORDER BY yr, wk
    ");
    $created->execute([$wsId]);
    $createdData = $created->fetchAll();

    // Team velocity (avg tasks completed per week)
    $velocity = count($weeklyData) > 0
        ? round(array_sum(array_column($weeklyData,'count')) / max(1,count($weeklyData)), 1)
        : 0;

    jsonOut([
        'total'       => $totalCards,
        'completed'   => $completed,
        'overdue'     => $overdue,
        'due_week'    => $dueWeek,
        'velocity'    => $velocity,
        'by_column'   => $byColumn,
        'by_priority' => $byPriority,
        'by_assignee' => $byAssignee,
        'weekly_done' => $weeklyData,
        'weekly_created' => $createdData,
    ]);
}

jsonErr('Acción no encontrada', 404);
