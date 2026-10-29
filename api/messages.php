<?php
// api/messages.php - User-Isolated Chat Messages API
require_once __DIR__ . '/../config.php';

$user = requireLogin(true);
$userId = (int)$user['id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
    if ($sessionId <= 0) {
        sendJsonResponse(['error' => 'Invalid session ID'], 400);
    }

    // Verify session belongs to logged in user
    $chkStmt = $pdo->prepare("SELECT id FROM sessions WHERE id = :sid AND user_id = :uid");
    $chkStmt->execute([':sid' => $sessionId, ':uid' => $userId]);
    if (!$chkStmt->fetch()) {
        sendJsonResponse(['error' => 'Session not found or access denied'], 404);
    }

    $stmt = $pdo->prepare("SELECT id, role, content, attachments, discussion_id, workspace_id, workspace_data, work_preview_id, actions_data, created_at FROM messages WHERE session_id = :session_id ORDER BY id ASC");
    $stmt->execute([':session_id' => $sessionId]);
    $messages = $stmt->fetchAll();

    // Cache preloaded previews
    $previewCache = [];

    foreach ($messages as &$msg) {
        $msg['attachments'] = !empty($msg['attachments']) ? json_decode($msg['attachments'], true) : [];
        $msg['actions'] = !empty($msg['actions_data']) ? json_decode($msg['actions_data'], true) : null;
        if (!empty($msg['workspace_data'])) {
            $msg['workspace'] = json_decode($msg['workspace_data'], true);
        } else {
            $msg['workspace'] = null;
        }

        $msg['work_preview'] = null;
        if (!empty($msg['work_preview_id'])) {
            $pid = intval($msg['work_preview_id']);
            if (!isset($previewCache[$pid])) {
                $pStmt = $pdo->prepare("SELECT * FROM member_work_previews WHERE id = :pid");
                $pStmt->execute([':pid' => $pid]);
                $pRow = $pStmt->fetch();
                if ($pRow) {
                    $pRow['checklist'] = !empty($pRow['checklist_json']) ? json_decode($pRow['checklist_json'], true) : [];
                    $pRow['terminal_logs'] = !empty($pRow['terminal_logs']) ? json_decode($pRow['terminal_logs'], true) : [];
                    $previewCache[$pid] = $pRow;
                } else {
                    $previewCache[$pid] = null;
                }
            }
            $msg['work_preview'] = $previewCache[$pid];
        }
    }

    sendJsonResponse(['messages' => $messages]);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
