<?php
// api/projects.php - User-Isolated Multi-Project Management API
require_once __DIR__ . '/../config.php';

$user = requireLogin(true);
$userId = (int)$user['id'];

// Ensure user has at least 1 project
$pCountStmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = :uid");
$pCountStmt->execute([':uid' => $userId]);
if ($pCountStmt->fetchColumn() == 0) {
    $pvStmt = $pdo->prepare("SELECT id FROM ventures WHERE user_id = :uid ORDER BY id ASC LIMIT 1");
    $pvStmt->execute([':uid' => $userId]);
    $pvId = $pvStmt->fetchColumn() ?: 1;

    $insP = $pdo->prepare("INSERT INTO projects (venture_id, name, description, color, icon, status, user_id) VALUES (:vid, 'General Operations', 'Primary workspace roadmap and execution deliverables', 'indigo', 'folder-kanban', 'active', :uid)");
    $insP->execute([':vid' => $pvId, ':uid' => $userId]);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $ventureId = isset($_GET['venture_id']) ? intval($_GET['venture_id']) : 0;
    
    $whereConditions = ["p.user_id = :uid"];
    $params = [
        ':uid' => $userId,
        ':uid1' => $userId,
        ':uid2' => $userId,
        ':uid3' => $userId,
        ':uid4' => $userId,
        ':uid5' => $userId
    ];
    
    if ($ventureId > 0) {
        $whereConditions[] = "(p.venture_id = :vid OR p.venture_id IS NULL)";
        $params[':vid'] = $ventureId;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
    $sql = "
        SELECT p.*,
               v.name as venture_name,
               v.color as venture_color,
               (SELECT COUNT(*) FROM notes WHERE user_id = :uid1 AND (project_id = p.id OR (project_name = p.name AND (venture_id = p.venture_id OR venture_id IS NULL)))) as total_tasks,
               (SELECT COUNT(*) FROM notes WHERE user_id = :uid2 AND (project_id = p.id OR (project_name = p.name AND (venture_id = p.venture_id OR venture_id IS NULL))) AND (status = 'completed' OR is_done = 1)) as completed_tasks,
               (SELECT COUNT(*) FROM notes WHERE user_id = :uid3 AND (project_id = p.id OR (project_name = p.name AND (venture_id = p.venture_id OR venture_id IS NULL))) AND status = 'in_progress') as in_progress_tasks,
               (SELECT COUNT(*) FROM notes WHERE user_id = :uid4 AND (project_id = p.id OR (project_name = p.name AND (venture_id = p.venture_id OR venture_id IS NULL))) AND status = 'review') as review_tasks,
               (SELECT COUNT(*) FROM notes WHERE user_id = :uid5 AND (project_id = p.id OR (project_name = p.name AND (venture_id = p.venture_id OR venture_id IS NULL))) AND (status = 'todo' OR status IS NULL) AND (is_done = 0 OR is_done IS NULL)) as todo_tasks
        FROM projects p
        LEFT JOIN ventures v ON v.id = p.venture_id
        {$whereClause}
        ORDER BY p.updated_at DESC, p.id DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();

    foreach ($projects as &$p) {
        $total = intval($p['total_tasks']);
        $completed = intval($p['completed_tasks']);
        $p['progress_pct'] = $total > 0 ? round(($completed / $total) * 100) : 0;
    }
    
    sendJsonResponse(['projects' => $projects]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = isset($input['name']) ? trim($input['name']) : '';
    $description = isset($input['description']) ? trim($input['description']) : '';
    $ventureId = isset($input['venture_id']) && intval($input['venture_id']) > 0 ? intval($input['venture_id']) : 1;
    $color = isset($input['color']) ? trim($input['color']) : 'indigo';
    $icon = isset($input['icon']) ? trim($input['icon']) : 'folder-kanban';
    $status = isset($input['status']) && in_array($input['status'], ['active', 'completed', 'archived']) ? $input['status'] : 'active';

    if (empty($name)) {
        sendJsonResponse(['error' => 'Project name is required'], 400);
    }

    // Check if project with same name exists under this venture and user
    $checkStmt = $pdo->prepare("SELECT * FROM projects WHERE LOWER(name) = LOWER(:name) AND user_id = :uid AND (venture_id = :vid OR venture_id IS NULL) LIMIT 1");
    $checkStmt->execute([':name' => $name, ':uid' => $userId, ':vid' => $ventureId]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        sendJsonResponse([
            'success' => true,
            'project' => $existing,
            'is_existing' => true
        ]);
    }

    $stmt = $pdo->prepare("INSERT INTO projects (name, description, venture_id, color, icon, status, user_id, created_at, updated_at) VALUES (:name, :description, :vid, :color, :icon, :status, :uid, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':vid' => $ventureId,
        ':color' => $color,
        ':icon' => $icon,
        ':status' => $status,
        ':uid' => $userId
    ]);
    $projectId = $pdo->lastInsertId();

    sendJsonResponse([
        'success' => true,
        'project' => [
            'id' => $projectId,
            'name' => $name,
            'description' => $description,
            'venture_id' => $ventureId,
            'color' => $color,
            'icon' => $icon,
            'status' => $status,
            'total_tasks' => 0,
            'completed_tasks' => 0,
            'in_progress_tasks' => 0,
            'todo_tasks' => 0,
            'progress_pct' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ], 201);
}

if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? intval($input['id']) : 0;

    if ($id <= 0) {
        sendJsonResponse(['error' => 'Valid Project ID is required'], 400);
    }

    $updates = [];
    $params = [':id' => $id, ':uid' => $userId];

    if (isset($input['name']) && !empty(trim($input['name']))) {
        $newName = trim($input['name']);
        $updates[] = "name = :name";
        $params[':name'] = $newName;

        // Sync old project_name across notes belonging to this user
        $oldPStmt = $pdo->prepare("SELECT name FROM projects WHERE id = :id AND user_id = :uid");
        $oldPStmt->execute([':id' => $id, ':uid' => $userId]);
        $oldName = $oldPStmt->fetchColumn();
        if ($oldName && $oldName !== $newName) {
            $updNotesStmt = $pdo->prepare("UPDATE notes SET project_name = :nname WHERE project_name = :oname AND user_id = :uid");
            $updNotesStmt->execute([':nname' => $newName, ':oname' => $oldName, ':uid' => $userId]);
        }
    }

    if (isset($input['description'])) {
        $updates[] = "description = :description";
        $params[':description'] = trim($input['description']);
    }

    if (isset($input['color'])) {
        $updates[] = "color = :color";
        $params[':color'] = trim($input['color']);
    }

    if (isset($input['icon'])) {
        $updates[] = "icon = :icon";
        $params[':icon'] = trim($input['icon']);
    }

    if (isset($input['status']) && in_array($input['status'], ['active', 'completed', 'archived'])) {
        $updates[] = "status = :status";
        $params[':status'] = $input['status'];
    }

    if (!empty($updates)) {
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        $sql = "UPDATE projects SET " . implode(", ", $updates) . " WHERE id = :id AND user_id = :uid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $id, ':uid' => $userId]);
    $updatedProject = $stmt->fetch();

    sendJsonResponse([
        'success' => true,
        'project' => $updatedProject
    ]);
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        sendJsonResponse(['error' => 'Invalid Project ID'], 400);
    }

    // Protect core General Operations project (if only 1 project remains)
    $pCount = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = :uid");
    $pCount->execute([':uid' => $userId]);
    if ($pCount->fetchColumn() <= 1) {
        sendJsonResponse(['error' => 'Cannot delete the only remaining project'], 400);
    }

    // Nullify references in notes belonging to this user
    $pdo->prepare("UPDATE notes SET project_id = NULL WHERE project_id = :id AND user_id = :uid")->execute([':id' => $id, ':uid' => $userId]);

    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $id, ':uid' => $userId]);

    sendJsonResponse(['success' => true]);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
