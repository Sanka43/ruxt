<?php
// api/sessions.php - Isolated Multi-User Chat Sessions API
require_once __DIR__ . '/../config.php';

$user = requireLogin(true);
$userId = (int)$user['id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Automatically purge any empty sessions of this user that have 0 messages
    $purgeStmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = :uid AND id NOT IN (SELECT DISTINCT session_id FROM messages)");
    $purgeStmt->execute([':uid' => $userId]);

    $ventureId = isset($_GET['venture_id']) ? intval($_GET['venture_id']) : 0;
    
    if ($ventureId > 0) {
        $stmt = $pdo->prepare("
            SELECT s.id, s.title, s.executive_id, s.venture_id, s.project_id, s.project_name, s.created_at, s.updated_at,
                   e.name as executive_name, e.title as executive_title, e.avatar_url, e.department,
                   (SELECT content FROM messages WHERE session_id = s.id ORDER BY id DESC LIMIT 1) as last_message,
                   (SELECT COUNT(*) FROM messages WHERE session_id = s.id) as message_count
            FROM sessions s
            LEFT JOIN executives e ON e.id = s.executive_id
            WHERE s.user_id = :uid
              AND (s.venture_id = :vid OR s.venture_id IS NULL OR e.is_chief_of_staff = 1)
              AND (SELECT COUNT(*) FROM messages WHERE session_id = s.id) > 0
            ORDER BY s.updated_at DESC
        ");
        $stmt->execute([':uid' => $userId, ':vid' => $ventureId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT s.id, s.title, s.executive_id, s.venture_id, s.project_id, s.project_name, s.created_at, s.updated_at,
                   e.name as executive_name, e.title as executive_title, e.avatar_url, e.department,
                   (SELECT content FROM messages WHERE session_id = s.id ORDER BY id DESC LIMIT 1) as last_message,
                   (SELECT COUNT(*) FROM messages WHERE session_id = s.id) as message_count
            FROM sessions s
            LEFT JOIN executives e ON e.id = s.executive_id
            WHERE s.user_id = :uid
              AND (SELECT COUNT(*) FROM messages WHERE session_id = s.id) > 0
            ORDER BY s.updated_at DESC
        ");
        $stmt->execute([':uid' => $userId]);
    }
    $sessions = $stmt->fetchAll();
    sendJsonResponse(['sessions' => $sessions]);
}

if ($method === 'POST') {
    // Create new session (or reuse existing unstarted session)
    $input = json_decode(file_get_contents('php://input'), true);
    $title = !empty($input['title']) ? trim($input['title']) : 'Executive Briefing';
    $executiveId = !empty($input['executive_id']) ? intval($input['executive_id']) : 1;
    $ventureId = !empty($input['venture_id']) ? intval($input['venture_id']) : 1;
    $projectId = isset($input['project_id']) && intval($input['project_id']) > 0 ? intval($input['project_id']) : null;
    $projectName = isset($input['project_name']) ? trim($input['project_name']) : null;

    // Check if an empty session already exists for this executive and user to avoid clutter
    $chkStmt = $pdo->prepare("SELECT id, title FROM sessions WHERE user_id = :uid AND executive_id = :eid AND (venture_id = :vid OR venture_id IS NULL) AND (SELECT COUNT(*) FROM messages WHERE session_id = sessions.id) = 0 LIMIT 1");
    $chkStmt->execute([':uid' => $userId, ':eid' => $executiveId, ':vid' => $ventureId]);
    $existingEmpty = $chkStmt->fetch();

    if ($existingEmpty) {
        $sessionId = intval($existingEmpty['id']);
        $title = $existingEmpty['title'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO sessions (title, executive_id, venture_id, project_id, project_name, user_id) VALUES (:title, :executive_id, :venture_id, :project_id, :project_name, :uid)");
        $stmt->execute([
            ':title' => $title,
            ':executive_id' => $executiveId,
            ':venture_id' => $ventureId,
            ':project_id' => $projectId,
            ':project_name' => $projectName,
            ':uid' => $userId
        ]);
        $sessionId = $pdo->lastInsertId();
    }

    // Fetch executive details
    $exStmt = $pdo->prepare("SELECT name, title, department, avatar_url FROM executives WHERE id = :id");
    $exStmt->execute([':id' => $executiveId]);
    $ex = $exStmt->fetch();

    sendJsonResponse([
        'success' => true,
        'session' => [
            'id' => $sessionId,
            'title' => $title,
            'executive_id' => $executiveId,
            'venture_id' => $ventureId,
            'executive_name' => $ex ? $ex['name'] : 'Executive',
            'executive_title' => $ex ? $ex['title'] : 'Advisor',
            'avatar_url' => $ex ? $ex['avatar_url'] : '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ], 201);
}

if ($method === 'DELETE') {
    // Delete session and clean up attached files
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        sendJsonResponse(['error' => 'Invalid session ID'], 400);
    }

    // Verify session belongs to user
    $chkStmt = $pdo->prepare("SELECT id FROM sessions WHERE id = :id AND user_id = :uid");
    $chkStmt->execute([':id' => $id, ':uid' => $userId]);
    if ($chkStmt->fetch()) {
        // Find all attachments in this session to clean up from disk
        $attStmt = $pdo->prepare("SELECT attachments FROM messages WHERE session_id = :id AND attachments IS NOT NULL AND attachments != ''");
        $attStmt->execute([':id' => $id]);
        $msgAttachments = $attStmt->fetchAll();
        $baseAttachmentsDir = realpath(__DIR__ . '/../uploads/attachments');

        foreach ($msgAttachments as $mRow) {
            $atts = json_decode($mRow['attachments'], true);
            if (is_array($atts)) {
                foreach ($atts as $att) {
                    $filePath = !empty($att['file_path']) ? $att['file_path'] : (!empty($att['url']) ? $att['url'] : null);
                    if ($filePath && $baseAttachmentsDir) {
                        $fullPath = realpath(__DIR__ . '/../' . ltrim($filePath, '/\\'));
                        if ($fullPath && file_exists($fullPath) && strpos($fullPath, $baseAttachmentsDir) === 0) {
                            @unlink($fullPath);
                        }
                    }
                }
            }
        }

        $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    sendJsonResponse(['success' => true]);
}

if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? intval($input['id']) : 0;

    if ($id <= 0) {
        sendJsonResponse(['error' => 'Invalid parameters'], 400);
    }

    $updates = [];
    $params = [':id' => $id, ':uid' => $userId];

    if (isset($input['title']) && !empty(trim($input['title']))) {
        $updates[] = "title = :title";
        $params[':title'] = trim($input['title']);
    }

    if (isset($input['project_id'])) {
        $updates[] = "project_id = :project_id";
        $params[':project_id'] = intval($input['project_id']) > 0 ? intval($input['project_id']) : null;
    }

    if (isset($input['project_name'])) {
        $updates[] = "project_name = :project_name";
        $params[':project_name'] = trim($input['project_name']);
    }

    if (!empty($updates)) {
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        $sql = "UPDATE sessions SET " . implode(", ", $updates) . " WHERE id = :id AND user_id = :uid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    sendJsonResponse(['success' => true]);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
