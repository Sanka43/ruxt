<?php
// api/notes.php - User-Isolated Enterprise Task & Sprint Deliverables API
require_once __DIR__ . '/../config.php';

$user = requireLogin(true);
$userId = (int)$user['id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $ventureId = isset($_GET['venture_id']) ? intval($_GET['venture_id']) : 0;
    $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
    $discussionId = isset($_GET['discussion_id']) ? intval($_GET['discussion_id']) : 0;
    $projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    $projectName = isset($_GET['project_name']) ? trim($_GET['project_name']) : '';

    $conditions = ["n.user_id = :uid"];
    $params = [':uid' => $userId];

    if ($ventureId > 0) {
        $conditions[] = "(n.venture_id = :vid OR n.venture_id IS NULL)";
        $params[':vid'] = $ventureId;
    }

    if (!empty($statusFilter) && in_array($statusFilter, ['todo', 'in_progress', 'review', 'completed'])) {
        $conditions[] = "n.status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($discussionId > 0) {
        $conditions[] = "n.discussion_id = :did";
        $params[':did'] = $discussionId;
    }

    if ($projectId > 0) {
        $conditions[] = "(n.project_id = :pid OR n.project_name = (SELECT name FROM projects WHERE id = :pid2 AND user_id = :uid2))";
        $params[':pid'] = $projectId;
        $params[':pid2'] = $projectId;
        $params[':uid2'] = $userId;
    } elseif (!empty($projectName)) {
        $conditions[] = "n.project_name = :pname";
        $params[':pname'] = $projectName;
    }

    $whereClause = "WHERE " . implode(" AND ", $conditions);

    $sql = "
        SELECT n.*,
               p.color as project_color,
               p.icon as project_icon,
               COALESCE(n.project_name, p.name, 'General') as resolved_project_name,
               CASE n.status 
                   WHEN 'todo' THEN 1 
                   WHEN 'in_progress' THEN 2 
                   WHEN 'review' THEN 3 
                   WHEN 'completed' THEN 4 
                   ELSE 5 
               END as status_order
        FROM notes n
        LEFT JOIN projects p ON p.id = n.project_id
        {$whereClause} 
        ORDER BY status_order ASC, n.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notes = $stmt->fetchAll();

    // Ensure status is never null and project_name is filled
    foreach ($notes as &$n) {
        if (empty($n['status'])) {
            $n['status'] = (!empty($n['is_done']) && $n['is_done'] == 1) ? 'completed' : 'todo';
        }
        if (empty($n['project_name'])) {
            $n['project_name'] = $n['resolved_project_name'] ?? 'General';
        }
    }

    sendJsonResponse(['notes' => $notes]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $title = isset($input['title']) ? trim($input['title']) : '';
    $content = isset($input['content']) ? trim($input['content']) : '';
    $dueDate = isset($input['due_date']) ? trim($input['due_date']) : null;
    $assignedTo = isset($input['assigned_to']) ? trim($input['assigned_to']) : 'General';
    $assignedAvatar = isset($input['assigned_avatar']) ? trim($input['assigned_avatar']) : '';
    $priority = isset($input['priority']) ? trim($input['priority']) : 'Medium';
    $ventureId = isset($input['venture_id']) && intval($input['venture_id']) > 0 ? intval($input['venture_id']) : 1;
    $status = isset($input['status']) && in_array($input['status'], ['todo', 'in_progress', 'review', 'completed']) ? $input['status'] : 'todo';
    $discussionId = isset($input['discussion_id']) && intval($input['discussion_id']) > 0 ? intval($input['discussion_id']) : null;
    $projectId = isset($input['project_id']) && intval($input['project_id']) > 0 ? intval($input['project_id']) : null;
    $projectName = isset($input['project_name']) ? trim($input['project_name']) : '';

    if (empty($title)) {
        sendJsonResponse(['error' => 'Title is required'], 400);
    }

    // Resolve project_id & project_name for this user
    if (!empty($projectName) && empty($projectId)) {
        $pCheck = $pdo->prepare("SELECT id, name FROM projects WHERE LOWER(name) = LOWER(:name) AND user_id = :uid AND (venture_id = :vid OR venture_id IS NULL) LIMIT 1");
        $pCheck->execute([':name' => $projectName, ':uid' => $userId, ':vid' => $ventureId]);
        $foundP = $pCheck->fetch();
        if ($foundP) {
            $projectId = $foundP['id'];
            $projectName = $foundP['name'];
        } else {
            $insP = $pdo->prepare("INSERT INTO projects (name, venture_id, status, user_id) VALUES (:name, :vid, 'active', :uid)");
            $insP->execute([':name' => $projectName, ':vid' => $ventureId, ':uid' => $userId]);
            $projectId = $pdo->lastInsertId();
        }
    } elseif ($projectId > 0 && empty($projectName)) {
        $pCheck = $pdo->prepare("SELECT name FROM projects WHERE id = :id AND user_id = :uid");
        $pCheck->execute([':id' => $projectId, ':uid' => $userId]);
        $foundP = $pCheck->fetch();
        if ($foundP) {
            $projectName = $foundP['name'];
        }
    }

    if (empty($projectName)) {
        $projectName = 'General';
    }

    $isDone = ($status === 'completed') ? 1 : 0;

    $stmt = $pdo->prepare("
        INSERT INTO notes 
        (title, content, due_date, assigned_to, assigned_avatar, priority, venture_id, status, is_done, discussion_id, project_id, project_name, user_id) 
        VALUES (:title, :content, :due_date, :assigned_to, :assigned_avatar, :priority, :venture_id, :status, :is_done, :discussion_id, :project_id, :project_name, :uid)
    ");
    $stmt->execute([
        ':title' => $title,
        ':content' => $content,
        ':due_date' => $dueDate,
        ':assigned_to' => $assignedTo,
        ':assigned_avatar' => $assignedAvatar,
        ':priority' => $priority,
        ':venture_id' => $ventureId,
        ':status' => $status,
        ':is_done' => $isDone,
        ':discussion_id' => $discussionId,
        ':project_id' => $projectId,
        ':project_name' => $projectName,
        ':uid' => $userId
    ]);

    $newId = $pdo->lastInsertId();

    sendJsonResponse([
        'success' => true,
        'note' => [
            'id' => $newId,
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'is_done' => $isDone,
            'due_date' => $dueDate,
            'assigned_to' => $assignedTo,
            'assigned_avatar' => $assignedAvatar,
            'priority' => $priority,
            'venture_id' => $ventureId,
            'discussion_id' => $discussionId,
            'project_id' => $projectId,
            'project_name' => $projectName,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ], 201);
}

if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? intval($input['id']) : 0;

    // Batch update by project or all for this user
    if ($id <= 0) {
        $status = isset($input['status']) && in_array($input['status'], ['todo', 'in_progress', 'review', 'completed']) ? $input['status'] : null;
        if (!$status && isset($input['is_done'])) {
            $status = $input['is_done'] ? 'completed' : 'todo';
        }
        if ($status) {
            $isDone = ($status === 'completed') ? 1 : 0;
            $batchConditions = ["user_id = :buid"];
            $batchParams = [':status' => $status, ':is_done' => $isDone, ':buid' => $userId];
            
            if (!empty($input['project_id'])) {
                $batchConditions[] = "(project_id = :bpid OR project_name = (SELECT name FROM projects WHERE id = :bpid2 AND user_id = :buid2))";
                $batchParams[':bpid'] = intval($input['project_id']);
                $batchParams[':bpid2'] = intval($input['project_id']);
                $batchParams[':buid2'] = $userId;
            } elseif (!empty($input['project_name'])) {
                $batchConditions[] = "LOWER(project_name) = LOWER(:bpname)";
                $batchParams[':bpname'] = trim($input['project_name']);
            }
            
            if (!empty($input['venture_id'])) {
                $batchConditions[] = "(venture_id = :bvid OR venture_id IS NULL)";
                $batchParams[':bvid'] = intval($input['venture_id']);
            }

            if (!empty($batchConditions)) {
                $batchSql = "UPDATE notes SET status = :status, is_done = :is_done WHERE " . implode(" AND ", $batchConditions);
                $stmt = $pdo->prepare($batchSql);
                $stmt->execute($batchParams);
                sendJsonResponse([
                    'success' => true,
                    'batch_updated' => true,
                    'status' => $status,
                    'affected_rows' => $stmt->rowCount()
                ]);
            }
        }
        sendJsonResponse(['error' => 'Invalid ID or target for update'], 400);
    }

    $updates = [];
    $params = [':id' => $id, ':uid' => $userId];

    // Status update (todo, in_progress, review, completed)
    if (isset($input['status']) && in_array($input['status'], ['todo', 'in_progress', 'review', 'completed'])) {
        $updates[] = "status = :status";
        $updates[] = "is_done = :is_done";
        $params[':status'] = $input['status'];
        $params[':is_done'] = ($input['status'] === 'completed') ? 1 : 0;
    } elseif (isset($input['is_done'])) {
        $isDone = $input['is_done'] ? 1 : 0;
        $updates[] = "is_done = :is_done";
        $updates[] = "status = :status";
        $params[':is_done'] = $isDone;
        $params[':status'] = $isDone ? 'completed' : 'todo';
    }

    if (isset($input['title'])) {
        $updates[] = "title = :title";
        $params[':title'] = trim($input['title']);
    }

    if (isset($input['content'])) {
        $updates[] = "content = :content";
        $params[':content'] = trim($input['content']);
    }

    if (isset($input['priority'])) {
        $updates[] = "priority = :priority";
        $params[':priority'] = trim($input['priority']);
    }

    if (isset($input['assigned_to'])) {
        $updates[] = "assigned_to = :assigned_to";
        $params[':assigned_to'] = trim($input['assigned_to']);
    }

    if (isset($input['assigned_avatar'])) {
        $updates[] = "assigned_avatar = :assigned_avatar";
        $params[':assigned_avatar'] = trim($input['assigned_avatar']);
    }

    if (isset($input['due_date'])) {
        $updates[] = "due_date = :due_date";
        $params[':due_date'] = trim($input['due_date']);
    }

    if (isset($input['project_id'])) {
        $pId = intval($input['project_id']);
        $updates[] = "project_id = :project_id";
        $params[':project_id'] = $pId > 0 ? $pId : null;

        if ($pId > 0 && !isset($input['project_name'])) {
            $pNameStmt = $pdo->prepare("SELECT name FROM projects WHERE id = :pid AND user_id = :uid3");
            $pNameStmt->execute([':pid' => $pId, ':uid3' => $userId]);
            $pRow = $pNameStmt->fetch();
            if ($pRow) {
                $updates[] = "project_name = :project_name";
                $params[':project_name'] = $pRow['name'];
            }
        }
    }

    if (isset($input['project_name']) && !isset($params[':project_name'])) {
        $updates[] = "project_name = :project_name";
        $params[':project_name'] = trim($input['project_name']);
    }

    if (!empty($updates)) {
        $sql = "UPDATE notes SET " . implode(", ", $updates) . " WHERE id = :id AND user_id = :uid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // Fetch updated note
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $id, ':uid' => $userId]);
    $updatedNote = $stmt->fetch();

    sendJsonResponse([
        'success' => true,
        'note' => $updatedNote
    ]);
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        sendJsonResponse(['error' => 'Invalid ID'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $id, ':uid' => $userId]);

    sendJsonResponse(['success' => true]);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
