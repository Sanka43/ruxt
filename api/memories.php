<?php
// api/memories.php - User-Isolated Memory Vault API
require_once __DIR__ . '/../config.php';

$user = requireLogin(true);
$userId = (int)$user['id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT id, category, content, created_at FROM memories WHERE user_id = :uid ORDER BY id DESC");
    $stmt->execute([':uid' => $userId]);
    $memories = $stmt->fetchAll();
    sendJsonResponse(['memories' => $memories]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['action']) && $input['action'] === 'clear_all') {
        $stmt = $pdo->prepare("DELETE FROM memories WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);

        // Reset user onboarding state when memories are wiped
        $resetUserStmt = $pdo->prepare("
            UPDATE `users` 
            SET onboarding_completed = 0, 
                job_field = NULL, 
                specialized_role = NULL, 
                onboarding_data = NULL 
            WHERE id = :id
        ");
        $resetUserStmt->execute([':id' => $userId]);

        // Reset Chief of Staff executive role
        $pdo->prepare("
            UPDATE executives 
            SET title = 'Chief of Staff & Lead Private Assistant', 
                department = 'Office of the CEO', 
                bio = 'Strategic partner, multi-agent orchestrator, and high-level project breakdown engine.',
                personality = 'Authoritative yet approachable, highly structured, proactive, visionary'
            WHERE is_chief_of_staff = 1
        ")->execute();

        // Delete any old onboarding sessions so a clean fresh welcome session can start
        $delOldOnboard = $pdo->prepare("DELETE FROM sessions WHERE user_id = :uid AND (title LIKE '%Role Setup%' OR title LIKE '%Welcome%')");
        $delOldOnboard->execute([':uid' => $userId]);

        sendJsonResponse([
            'success' => true,
            'needs_onboarding' => true,
            'message' => 'All your memories cleared successfully and onboarding has been reset.'
        ]);
    }

    $content = isset($input['content']) ? trim($input['content']) : '';
    $category = isset($input['category']) ? trim($input['category']) : 'general';

    if (empty($content)) {
        sendJsonResponse(['error' => 'Memory content is required'], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO memories (category, content, user_id) VALUES (:category, :content, :uid)");
    $stmt->execute([
        ':category' => $category,
        ':content' => $content,
        ':uid' => $userId
    ]);

    sendJsonResponse([
        'success' => true,
        'memory' => [
            'id' => $pdo->lastInsertId(),
            'category' => $category,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ], 201);
}

if ($method === 'DELETE') {
    $clearAll = (isset($_GET['all']) && ($_GET['all'] === 'true' || $_GET['all'] === '1')) ||
                (isset($_GET['id']) && strtolower(trim((string)$_GET['id'])) === 'all');

    if ($clearAll) {
        $stmt = $pdo->prepare("DELETE FROM memories WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);

        // Reset user onboarding state when memories are wiped
        $resetUserStmt = $pdo->prepare("
            UPDATE `users` 
            SET onboarding_completed = 0, 
                job_field = NULL, 
                specialized_role = NULL, 
                onboarding_data = NULL 
            WHERE id = :id
        ");
        $resetUserStmt->execute([':id' => $userId]);

        // Reset Chief of Staff executive role
        $pdo->prepare("
            UPDATE executives 
            SET title = 'Chief of Staff & Lead Private Assistant', 
                department = 'Office of the CEO', 
                bio = 'Strategic partner, multi-agent orchestrator, and high-level project breakdown engine.',
                personality = 'Authoritative yet approachable, highly structured, proactive, visionary'
            WHERE is_chief_of_staff = 1
        ")->execute();

        // Delete any old onboarding sessions so a clean fresh welcome session can start
        $delOldOnboard = $pdo->prepare("DELETE FROM sessions WHERE user_id = :uid AND (title LIKE '%Role Setup%' OR title LIKE '%Welcome%')");
        $delOldOnboard->execute([':uid' => $userId]);

        sendJsonResponse([
            'success' => true,
            'needs_onboarding' => true,
            'message' => 'All your memories cleared successfully and onboarding has been reset.'
        ]);
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        sendJsonResponse(['error' => 'Invalid ID'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM memories WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $id, ':uid' => $userId]);

    sendJsonResponse(['success' => true]);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
