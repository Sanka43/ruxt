<?php
// api/discussions.php
require_once __DIR__ . '/../config.php';
requireLogin(true);

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id > 0) {
        // Fetch specific discussion with dialogues and created tasks
        $stmt = $pdo->prepare("SELECT * FROM team_discussions WHERE id = :id AND (user_id = :uid OR user_id IS NULL)");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $discussion = $stmt->fetch();

        if (!$discussion) {
            sendJsonResponse(['error' => 'Discussion not found'], 404);
        }

        // Fetch dialogue turns
        $diagStmt = $pdo->prepare("SELECT * FROM team_discussion_dialogues WHERE discussion_id = :did ORDER BY id ASC");
        $diagStmt->execute([':did' => $id]);
        $dialogues = $diagStmt->fetchAll();

        // Fetch created tasks
        $taskStmt = $pdo->prepare("SELECT * FROM notes WHERE discussion_id = :did AND (user_id = :uid OR user_id IS NULL) ORDER BY id ASC");
        $taskStmt->execute([':did' => $id, ':uid' => $userId]);
        $tasks = $taskStmt->fetchAll();

        $discussion['dialogues'] = $dialogues;
        $discussion['tasks'] = $tasks;

        sendJsonResponse([
            'discussion' => $discussion,
            'dialogues' => $dialogues,
            'tasks' => $tasks
        ]);
    } else {
        // List discussions filtered by venture and user
        $ventureId = isset($_GET['venture_id']) ? intval($_GET['venture_id']) : 0;
        if ($ventureId > 0) {
            $stmt = $pdo->prepare("
                SELECT d.*, 
                       (SELECT COUNT(*) FROM team_discussion_dialogues WHERE discussion_id = d.id) as dialogue_count,
                       (SELECT COUNT(*) FROM notes WHERE discussion_id = d.id AND (user_id = :uid OR user_id IS NULL)) as task_count
                FROM team_discussions d 
                WHERE (d.user_id = :uid OR d.user_id IS NULL)
                  AND (d.venture_id = :vid OR d.venture_id IS NULL)
                ORDER BY d.id DESC
            ");
            $stmt->execute([':vid' => $ventureId, ':uid' => $userId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT d.*, 
                       (SELECT COUNT(*) FROM team_discussion_dialogues WHERE discussion_id = d.id) as dialogue_count,
                       (SELECT COUNT(*) FROM notes WHERE discussion_id = d.id AND (user_id = :uid OR user_id IS NULL)) as task_count
                FROM team_discussions d 
                WHERE (d.user_id = :uid OR d.user_id IS NULL)
                ORDER BY d.id DESC
            ");
            $stmt->execute([':uid' => $userId]);
        }
        $discussions = $stmt->fetchAll();
        sendJsonResponse(['discussions' => $discussions]);
    }
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        sendJsonResponse(['error' => 'Invalid discussion ID'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM team_discussions WHERE id = :id AND (user_id = :uid OR user_id IS NULL)");
    $stmt->execute([':id' => $id, ':uid' => $userId]);

    sendJsonResponse(['success' => true]);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
