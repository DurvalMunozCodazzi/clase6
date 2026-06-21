<?php
require_once '../config.php';
require_once 'email.php';
function isAdmin($me){ return isset($me['role']) && $me['role']==='admin'; }
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = intval($_GET['id'] ?? 0);
$me = requireAuth();

// Workspace activo: viene del header X-Workspace-Id o query param
$wsId = intval($_SERVER['HTTP_X_WORKSPACE_ID'] ?? $_GET['ws'] ?? 1);
if ($wsId < 1) $wsId = 1;

// Verificar acceso al workspace
if ($me['role'] !== 'admin') {
    $acc = $db->prepare("SELECT role FROM ".tb('workspace_members')." WHERE workspace_id=? AND user_id=?");
    $acc->execute([$wsId, $me['id']]);
    if (!$acc->fetch()) {
        // Si no tiene acceso explícito, intentar workspace 1
        $wsId = 1;
    }
}

requireAuth();

// ── GET full board state ──────────────────────────────────────────
if ($method === 'GET' && $action === 'board') {
    // Columns
    $cols = $db->prepare("SELECT * FROM ".tb('columns_k')." WHERE workspace_id=? ORDER BY position,id");
    $cols->execute([$wsId]);
    $columns = $cols->fetchAll();

    // Cards with tags, assignees, attachments counts, chat counts
    $cards = $db->prepare("
        SELECT c.*,
               (SELECT COUNT(*) FROM ".tb('card_tags')." t WHERE t.card_id=c.id) AS tag_count,
               (SELECT COUNT(*) FROM ".tb('card_assignees')." a WHERE a.card_id=c.id) AS assignee_count,
               (SELECT COUNT(*) FROM ".tb('attachments')." at WHERE at.card_id=c.id) AS attachment_count,
               (SELECT COUNT(*) FROM ".tb('card_checklist')." cc WHERE cc.card_id=c.id) AS checklist_total,
               (SELECT COUNT(*) FROM ".tb('card_checklist')." cc WHERE cc.card_id=c.id AND cc.is_done=1) AS checklist_done,
               (SELECT GROUP_CONCAT(depends_on_id) FROM ".tb('card_dependencies')." WHERE card_id=c.id) AS dep_ids,
               (SELECT COUNT(*) FROM ".tb('chat_messages')." ch WHERE ch.card_id=c.id) AS chat_count
        FROM ".tb('cards')." c WHERE c.workspace_id=? ORDER BY c.column_id, c.position, c.id");
    $cards->execute([$wsId]);
    $allCards = $cards->fetchAll();

    // Tags
    $tags = $db->query("SELECT ct.* FROM ".tb('card_tags')." ct JOIN ".tb('cards')." c ON c.id=ct.card_id WHERE c.workspace_id=$wsId")->fetchAll();
    // Assignees
    $asn = $db->query("SELECT ca.card_id, ca.user_id, u.name, u.color FROM ".tb('card_assignees')." ca JOIN ".tb('users')." u ON u.id=ca.user_id JOIN ".tb('cards')." c ON c.id=ca.card_id WHERE c.workspace_id=$wsId")->fetchAll();
    // Workspace info
    $ws = $db->prepare("SELECT name, canvas FROM ".tb('workspaces')." WHERE id=?");
    $ws->execute([$wsId]);
    $workspace = $ws->fetch();

    jsonOut([
        'workspace' => $workspace,
        'columns'   => $columns,
        'cards'     => $allCards,
        'tags'      => $tags,
        'assignees' => $asn,
    ]);
}

// ── POST create column ────────────────────────────────────────────
if ($method === 'POST' && $action === 'add_column') {
    $b = json_decode(file_get_contents('php://input'), true);
    $pos = $db->query("SELECT COALESCE(MAX(position),0)+1 FROM ".tb('columns_k')." WHERE workspace_id=$wsId")->fetchColumn();
    $st = $db->prepare("INSERT INTO ".tb('columns_k')." (workspace_id,title,color,position) VALUES (?,?,?,?)");
    $st->execute([$wsId, $b['title']??'Nueva columna', $b['color']??'#5b6af0', $pos]);
    $newId = $db->lastInsertId();
    $col = $db->prepare("SELECT * FROM ".tb('columns_k')." WHERE id=?");
    $col->execute([$newId]);
    jsonOut(['column' => $col->fetch()], 201);
}

// ── PUT update column ─────────────────────────────────────────────
if ($method === 'PUT' && $action === 'update_column') {
    $b = json_decode(file_get_contents('php://input'), true);
    $sets = []; $vals = [];
    if (isset($b['title'])) { $sets[]='title=?'; $vals[]=$b['title']; }
    if (isset($b['color'])) { $sets[]='color=?'; $vals[]=$b['color']; }
    if (!$sets) jsonErr('Nada que actualizar');
    $vals[] = $id;
    $db->prepare("UPDATE ".tb('columns_k')." SET ".implode(',',$sets)." WHERE id=?")->execute($vals);
    jsonOut(['ok'=>true]);
}

// ── DELETE column ─────────────────────────────────────────────────
if ($method === 'DELETE' && $action === 'delete_column') {
    $db->prepare("DELETE FROM ".tb('columns_k')." WHERE id=?")->execute([$id]);
    jsonOut(['ok'=>true]);
}

// ── POST create card ──────────────────────────────────────────────
if ($method === 'POST' && $action === 'add_card') {
    $b = json_decode(file_get_contents('php://input'), true);
    $colId = intval($b['column_id'] ?? 0);
    if (!$colId) jsonErr('column_id requerido');
    $pos = $db->query("SELECT COALESCE(MAX(position),0)+1 FROM ".tb('cards')." WHERE column_id=$colId")->fetchColumn();
    $st = $db->prepare("INSERT INTO ".tb('cards')." (column_id,workspace_id,title,description,priority,due_date,estimated,position) VALUES (?,?,?,?,?,?,?,?)");
    $st->execute([$colId,$wsId,$b['title']??'Nueva tarea',$b['description']??'',$b['priority']??'',$b['due_date']??null,$b['estimated']??null,$pos]);
    $newId = $db->lastInsertId();
    $card = $db->prepare("SELECT * FROM ".tb('cards')." WHERE id=?");
    $card->execute([$newId]);
    jsonOut(['card' => $card->fetch()], 201);
}

// ── PUT update card ───────────────────────────────────────────────
if ($method === 'PUT' && ($action === 'update_card' || $action === 'save_card')) {
    $b = json_decode(file_get_contents('php://input'), true);
    $fields = [];
    foreach (['title','description','priority','due_date','start_date','estimated','column_id','position','progress'] as $f) {
        if (array_key_exists($f, $b)) $fields[$f] = $b[$f] ?: null;
    }
    if ($fields) {
        $sets = implode(',', array_map(function($k){ return "$k=?"; }, array_keys($fields)));
        $vals = array_values($fields); $vals[] = $id;
        $db->prepare("UPDATE ".tb('cards')." SET updated_at=NOW(),$sets WHERE id=?")->execute($vals);
    }
    // Tags
    if (isset($b['tags'])) {
        $db->prepare("DELETE FROM ".tb('card_tags')." WHERE card_id=?")->execute([$id]);
        foreach ($b['tags'] as $t) {
            $db->prepare("INSERT INTO ".tb('card_tags')." (card_id,label,color) VALUES (?,?,?)")->execute([$id,$t['label'],$t['color']??'#5b6af0']);
        }
    }
    // Assignees
    if (isset($b['assignees'])) {
        $prev = $db->prepare("SELECT user_id FROM ".tb('card_assignees')." WHERE card_id=?");
        $prev->execute([$id]);
        $prevIds = array_column($prev->fetchAll(), 'user_id');
        $db->prepare("DELETE FROM ".tb('card_assignees')." WHERE card_id=?")->execute([$id]);
        foreach ($b['assignees'] as $uid) {
            $db->prepare("INSERT IGNORE INTO ".tb('card_assignees')." (card_id,user_id) VALUES (?,?)")->execute([$id,(int)$uid]);
            if (!in_array((int)$uid, $prevIds)) {
                createNotification($db, (int)$uid, $me['id'], 'assigned', $id, $wsId, '');
            }
        }
    }
    // Attachments
    if (isset($b['attachments'])) {
        $db->prepare("DELETE FROM ".tb('attachments')." WHERE card_id=?")->execute([$id]);
        foreach ($b['attachments'] as $a) {
            $db->prepare("INSERT INTO ".tb('attachments')." (card_id,name,type,url,drive_id) VALUES (?,?,?,?,?)")->execute([$id,$a['name'],$a['type']??'local',$a['url']??'',$a['drive_id']??'']);
        }
    }
    // Checklist
    if (isset($b['checklist'])) {
        $db->prepare("DELETE FROM ".tb('card_checklist')." WHERE card_id=?")->execute([$id]);
        foreach ($b['checklist'] as $pos => $item) {
            $db->prepare("INSERT INTO ".tb('card_checklist')." (card_id,title,is_done,position) VALUES (?,?,?,?)")
               ->execute([$id, substr(trim($item['title']),0,500), $item['is_done']?1:0, $pos]);
        }
    }
    // Dependencies
    if (isset($b['dependencies'])) {
        try {
            $db->prepare("DELETE FROM ".tb('card_dependencies')." WHERE card_id=?")->execute([$id]);
            foreach ($b['dependencies'] as $depId) {
                $depId = (int)$depId;
                if ($depId && $depId != $id) {
                    $db->prepare("INSERT IGNORE INTO ".tb('card_dependencies')." (card_id,depends_on_id) VALUES (?,?)")
                       ->execute([$id, $depId]);
                }
            }
        } catch (Exception $e) {}
    }
    // Activity log
    if (isset($fields['column_id'])) {
        try {
            $db->prepare("INSERT INTO ".tb('activity_log')." (card_id,workspace_id,user_id,action,field,new_value) VALUES (?,?,?,'update','column_id',?)")
               ->execute([$id,$wsId,$me['id'],$fields['column_id']]);
        } catch (Exception $e) {}

        // Check if moved to completed column → notify dependents
        try {
            $col = $db->prepare("SELECT title, position FROM ".tb('columns_k')." WHERE id=?");
            $col->execute([$fields['column_id']]);
            $colRow = $col->fetch();
            $maxPos = $db->query("SELECT MAX(position) FROM ".tb('columns_k')." WHERE workspace_id=$wsId")->fetchColumn();
            $isCompleted = $colRow && (
                stripos($colRow['title'], 'complet') !== false ||
                $colRow['position'] == $maxPos
            );
            if ($isCompleted) {
                // Find tasks that depend on this card
                $depTasks = $db->prepare("
                    SELECT cd.card_id, c.title as dep_title, ca.user_id
                    FROM ".tb('card_dependencies')." cd
                    JOIN ".tb('cards')." c ON c.id = cd.card_id
                    JOIN ".tb('card_assignees')." ca ON ca.card_id = cd.card_id
                    WHERE cd.depends_on_id = ?
                ");
                $depTasks->execute([$id]);
                $completedCard = $db->prepare("SELECT title FROM ".tb('cards')." WHERE id=?");
                $completedCard->execute([$id]);
                $completedTitle = $completedCard->fetchColumn();
                foreach ($depTasks->fetchAll() as $dep) {
                    createNotification($db, $dep['user_id'], $me['id'], 'dependency_done',
                        $dep['card_id'], $wsId,
                        '✅ Se completó: ' . $completedTitle . ' — tu tarea \'' . $dep['dep_title'] . '\' ya puede empezar'
                    );
                }
            }
        } catch (Exception $e) {}
    }
    jsonOut(['ok'=>true]);
}

// ── DELETE card ───────────────────────────────────────────────────
if ($method === 'DELETE' && $action === 'delete_card') {
    $db->prepare("DELETE FROM ".tb('cards')." WHERE id=?")->execute([$id]);
    jsonOut(['ok'=>true]);
}

// ── GET card detail (tags, assignees, attachments, chat) ──────────
if ($method === 'GET' && $action === 'card_detail') {
    $card = $db->prepare("SELECT * FROM ".tb('cards')." WHERE id=?");
    $card->execute([$id]);
    $c = $card->fetch();
    if (!$c) jsonErr('Tarjeta no encontrada', 404);
    $tags = $db->prepare("SELECT * FROM ".tb('card_tags')." WHERE card_id=?");
    $tags->execute([$id]);
    $asn = $db->prepare("SELECT u.id,u.name,u.color,u.cargo,u.dept,u.role FROM ".tb('card_assignees')." ca JOIN ".tb('users')." u ON u.id=ca.user_id WHERE ca.card_id=?");
    $asn->execute([$id]);
    $atts = $db->prepare("SELECT * FROM ".tb('attachments')." WHERE card_id=? ORDER BY id");
    $atts->execute([$id]);
    $chat = $db->prepare("SELECT cm.*,u.name as user_name,u.color as user_color FROM ".tb('chat_messages')." cm JOIN ".tb('users')." u ON u.id=cm.user_id WHERE cm.card_id=? ORDER BY cm.created_at");
    $chat->execute([$id]);
    $checklist=$db->prepare("SELECT * FROM ".tb('card_checklist')." WHERE card_id=? ORDER BY position");
    $checklist->execute([$id]);
    $activity=$db->prepare("SELECT al.*,u.name as user_name,u.color as user_color FROM ".tb('activity_log')." al JOIN ".tb('users')." u ON u.id=al.user_id WHERE al.card_id=? ORDER BY al.created_at DESC LIMIT 20");
    $activity->execute([$id]);
    // Dependencies
    $deps=[];
    try{
        $depSt=$db->prepare("SELECT cd.depends_on_id, c.title as depends_on_title FROM ".tb('card_dependencies')." cd LEFT JOIN ".tb('cards')." c ON c.id=cd.depends_on_id WHERE cd.card_id=?");
        $depSt->execute([$id]);
        $deps=$depSt->fetchAll();
    }catch(Exception $e){}
    jsonOut([
        'card'=>$c,
        'tags'=>$tags->fetchAll(),
        'assignees'=>$asn->fetchAll(),
        'attachments'=>$atts->fetchAll(),
        'chat'=>$chat->fetchAll(),
        'checklist'=>$checklist->fetchAll(),
        'activity'=>$activity->fetchAll(),
        'dependencies'=>$deps,
    ]);
}

// ── POST move card ────────────────────────────────────────────────
if ($method === 'POST' && $action === 'move_card') {
    $b = json_decode(file_get_contents('php://input'), true);
    $db->prepare("UPDATE ".tb('cards')." SET column_id=?,position=? WHERE id=?")->execute([$b['column_id'],$b['position'],$id]);
    jsonOut(['ok'=>true]);
}

// ── PUT reorder columns ───────────────────────────────────────────
if ($method === 'PUT' && $action === 'reorder_columns') {
    $b = json_decode(file_get_contents('php://input'), true);
    foreach ($b['order'] as $pos => $colId) {
        $db->prepare("UPDATE ".tb('columns_k')." SET position=? WHERE id=?")->execute([$pos, $colId]);
    }
    jsonOut(['ok'=>true]);
}

// ── POST add chat message ─────────────────────────────────────────
if ($method === 'POST' && $action === 'add_chat') {
    $me = requireAuth();
    $b  = json_decode(file_get_contents('php://input'), true);
    $msg = trim($b['message'] ?? '');
    if (!$msg) jsonErr('Mensaje vacio');
    $db->prepare("INSERT INTO ".tb('chat_messages')." (card_id,user_id,message) VALUES (?,?,?)")->execute([$id, $me['id'], $msg]);
    $newId = $db->lastInsertId();
    $row = $db->prepare("SELECT cm.*,u.name as user_name,u.color as user_color FROM ".tb('chat_messages')." cm JOIN ".tb('users')." u ON u.id=cm.user_id WHERE cm.id=?");
    $row->execute([$newId]);
    jsonOut(['message' => $row->fetch()], 201);
}

// ── PUT update workspace name / canvas ───────────────────────────
if ($method === 'PUT' && $action === 'update_workspace') {
    $b = json_decode(file_get_contents('php://input'), true);
    $sets=[]; $vals=[];
    if (isset($b['name']))   { $sets[]='name=?';   $vals[]=$b['name']; }
    if (isset($b['canvas'])) { $sets[]='canvas=?'; $vals[]=$b['canvas']; }
    if ($sets) { $vals[]=$wsId; $db->prepare("UPDATE ".tb('workspaces')." SET ".implode(',',$sets)." WHERE id=?")->execute($vals); }
    jsonOut(['ok'=>true]);
}

// ── GET SSE polling for live updates ─────────────────────────────
if ($method === 'GET' && $action === 'poll') {
    $ws = $db->prepare("SELECT updated_at FROM ".tb('workspaces')." WHERE id=?");
    $ws->execute([$wsId]);
    $r = $ws->fetch();
    $lastCards = $db->query("SELECT MAX(updated_at) FROM ".tb('cards')." WHERE workspace_id=$wsId")->fetchColumn();
    jsonOut(['ts' => max($r['updated_at'], $lastCards ?? '')]);
}

// ── GET global labels ──────────────────────────────────────────
if ($method === 'GET' && $action === 'labels') {
    $st = $db->prepare("SELECT * FROM ".tb('workspace_labels')." WHERE workspace_id=? ORDER BY name");
    $st->execute([$wsId]);
    jsonOut(['labels' => $st->fetchAll()]);
}

// ── POST create label ──────────────────────────────────────────
if ($method === 'POST' && $action === 'create_label') {
    $b    = json_decode(file_get_contents('php://input'), true);
    $name = trim($b['name'] ?? '');
    if (!$name) jsonErr('Nombre requerido');
    $color = substr(trim($b['color'] ?? '#5b6af0'), 0, 10);
    $db->prepare("INSERT INTO ".tb('workspace_labels')." (workspace_id,name,color,created_by) VALUES (?,?,?,?)")
       ->execute([$wsId, $name, $color, $me['id']]);
    $newId = $db->lastInsertId();
    jsonOut(['label' => ['id'=>$newId,'name'=>$name,'color'=>$color,'workspace_id'=>$wsId]], 201);
}

// ── DELETE label ────────────────────────────────────────────────
if ($method === 'DELETE' && $action === 'delete_label') {
    if ($me['role'] !== 'admin') jsonErr('Solo admins', 403);
    $labelName = $db->prepare("SELECT name FROM ".tb('workspace_labels')." WHERE id=? AND workspace_id=?");
    $labelName->execute([$id, $wsId]);
    $row = $labelName->fetch();
    if ($row) {
        $db->prepare("DELETE FROM ".tb('workspace_labels')." WHERE id=?")->execute([$id]);
        $db->prepare("DELETE ct FROM ".tb('card_tags')." ct JOIN ".tb('cards')." c ON c.id=ct.card_id WHERE c.workspace_id=? AND ct.label=?")->execute([$wsId, $row['name']]);
    }
    jsonOut(['ok' => true]);
}

jsonErr('Accion no encontrada', 404);

