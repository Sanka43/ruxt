<?php
// api/admin.php - Dedicated Administration API for RuXt AI Platform Management
require_once __DIR__ . '/../config.php';

// Strict Admin Privileges Enforcement
$adminUser = requireAdmin(true);
$adminId = (int)$adminUser['id'];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Support JSON input body
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? [];
if (!empty($input['action'])) {
    $action = $input['action'];
}

// -------------------------------------------------------------
// 1. STATS / OVERVIEW TELEMETRY
// -------------------------------------------------------------
if ($action === 'stats' && $method === 'GET') {
    try {
        // Users counts
        $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $adminUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        $regularUsers = $totalUsers - $adminUsers;

        // Workspaces (ventures) counts
        $totalWorkspaces = (int)$pdo->query("SELECT COUNT(*) FROM ventures")->fetchColumn();
        
        // Executives count
        $totalExecutives = (int)$pdo->query("SELECT COUNT(*) FROM executives")->fetchColumn();

        // Projects & Tasks
        $totalProjects = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
        $activeProjects = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'active'")->fetchColumn();
        
        $totalTasks = (int)$pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
        $completedTasks = (int)$pdo->query("SELECT COUNT(*) FROM notes WHERE is_done = 1")->fetchColumn();
        $pendingTasks = $totalTasks - $completedTasks;

        // Sessions & Messages
        $totalSessions = (int)$pdo->query("SELECT COUNT(*) FROM sessions")->fetchColumn();
        $totalMessages = (int)$pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();

        // Token Usage Stats
        $tokenStats = getTokenUsageStats();

        // Recent 5 users
        $stmtRecentUsers = $pdo->query("SELECT id, name, username, email, role, created_at, avatar_url FROM users ORDER BY id DESC LIMIT 5");
        $recentUsers = $stmtRecentUsers->fetchAll();

        // Recent 5 workspaces
        $stmtRecentWorkspaces = $pdo->query("
            SELECT v.id, v.name, v.domain, v.icon, v.color, v.created_at, u.name as owner_name, u.username as owner_username
            FROM ventures v
            LEFT JOIN users u ON v.user_id = u.id
            ORDER BY v.id DESC LIMIT 5
        ");
        $recentWorkspaces = $stmtRecentWorkspaces->fetchAll();

        // System Settings
        $geminiKey = getSetting('gemini_api_key', '');
        $hasGemini = !empty($geminiKey);
        $activeModel = getSetting('model', 'gemini-3.1-flash-lite');

        sendJsonResponse([
            'success' => true,
            'stats' => [
                'users' => [
                    'total' => $totalUsers,
                    'admins' => $adminUsers,
                    'regular' => $regularUsers
                ],
                'workspaces' => [
                    'total' => $totalWorkspaces
                ],
                'executives' => [
                    'total' => $totalExecutives
                ],
                'projects' => [
                    'total' => $totalProjects,
                    'active' => $activeProjects
                ],
                'tasks' => [
                    'total' => $totalTasks,
                    'completed' => $completedTasks,
                    'pending' => $pendingTasks
                ],
                'conversations' => [
                    'sessions' => $totalSessions,
                    'messages' => $totalMessages
                ],
                'tokens' => $tokenStats,
                'ai_status' => [
                    'configured' => $hasGemini,
                    'active_model' => $activeModel
                ]
            ],
            'recent_users' => $recentUsers,
            'recent_workspaces' => $recentWorkspaces
        ]);
    } catch (\Throwable $e) {
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// -------------------------------------------------------------
// 2. USERS MANAGEMENT
// -------------------------------------------------------------

// 2.1 LIST USERS
if ($action === 'users' && $method === 'GET') {
    try {
        $search = trim($_GET['search'] ?? '');
        $roleFilter = trim($_GET['role'] ?? 'all');

        $sql = "
            SELECT u.id, u.username, u.email, u.name, u.avatar_url, u.role, u.job_field, u.specialized_role,
                   u.onboarding_completed, u.created_at, u.last_login,
                   (SELECT COUNT(*) FROM ventures WHERE user_id = u.id) as workspaces_count,
                   (SELECT COUNT(*) FROM sessions WHERE user_id = u.id) as sessions_count,
                   (SELECT COUNT(*) FROM notes WHERE user_id = u.id) as tasks_count
            FROM users u
            WHERE 1=1
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (u.name LIKE :s1 OR u.username LIKE :s2 OR u.email LIKE :s3)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
        }

        if ($roleFilter === 'admin' || $roleFilter === 'user') {
            $sql .= " AND u.role = :r";
            $params[':r'] = $roleFilter;
        }

        $sql .= " ORDER BY u.id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        sendJsonResponse(['success' => true, 'users' => $users]);
    } catch (\Throwable $e) {
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// 2.2 CREATE USER
if ($action === 'create_user' && $method === 'POST') {
    $name = trim($input['name'] ?? $_POST['name'] ?? '');
    $username = trim($input['username'] ?? $_POST['username'] ?? '');
    $email = strtolower(trim($input['email'] ?? $_POST['email'] ?? ''));
    $password = $input['password'] ?? $_POST['password'] ?? '';
    $role = ($input['role'] ?? $_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $createWorkspace = !empty($input['create_workspace'] ?? $_POST['create_workspace'] ?? true);
    $workspaceTitle = trim($input['workspace_title'] ?? $_POST['workspace_title'] ?? 'Primary Workspace');
    $workspaceDomain = trim($input['workspace_domain'] ?? $_POST['workspace_domain'] ?? 'General Operations');

    if (mb_strlen($name) < 2) {
        sendJsonResponse(['success' => false, 'error' => 'Full name must be at least 2 characters.'], 400);
    }
    if (mb_strlen($username) < 3 || mb_strlen($username) > 30 || !preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
        sendJsonResponse(['success' => false, 'error' => 'Username must be 3-30 alphanumeric/underscore characters.'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(['success' => false, 'error' => 'Valid email address is required.'], 400);
    }
    if (strlen($password) < 6) {
        sendJsonResponse(['success' => false, 'error' => 'Password must be at least 6 characters.'], 400);
    }

    // Uniqueness checks
    $chk = getUserByUsernameOrEmail($username);
    if ($chk && strtolower($chk['username']) === strtolower($username)) {
        sendJsonResponse(['success' => false, 'error' => 'Username is already taken.'], 400);
    }
    $chkEmail = getUserByUsernameOrEmail($email);
    if ($chkEmail && strtolower($chkEmail['email']) === $email) {
        sendJsonResponse(['success' => false, 'error' => 'Email address is already in use.'], 400);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=173d34&color=fcfaf5&bold=true';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, username, email, password_hash, avatar_url, role, onboarding_completed)
            VALUES (:name, :username, :email, :hash, :avatar, :role, 1)
        ");
        $stmt->execute([
            ':name' => $name,
            ':username' => $username,
            ':email' => $email,
            ':hash' => $passwordHash,
            ':avatar' => $avatarUrl,
            ':role' => $role
        ]);
        $newUserId = (int)$pdo->lastInsertId();

        // Optionally provision dedicated workspace and default team
        if ($createWorkspace) {
            $insVenture = $pdo->prepare("
                INSERT INTO ventures (name, domain, description, icon, color, user_id) 
                VALUES (:name, :domain, 'Main workspace for private AI assistance, strategic planning, and operational execution.', 'briefcase', 'indigo', :uid)
            ");
            $insVenture->execute([
                ':name' => $workspaceTitle ?: 'Primary Workspace',
                ':domain' => $workspaceDomain ?: 'General Operations',
                ':uid' => $newUserId
            ]);
            $userVentureId = (int)$pdo->lastInsertId();

            // Insert initial default Project
            $insProj = $pdo->prepare("
                INSERT INTO projects (venture_id, name, description, color, icon, status, user_id) 
                VALUES (:vid, 'General Operations', 'Primary workspace roadmap and execution deliverables', 'indigo', 'folder-kanban', 'active', :uid)
            ");
            $insProj->execute([':vid' => $userVentureId, ':uid' => $newUserId]);

            // Insert Starter Executive Team
            $starterTeam = [
                ['name' => 'RuXt Chief', 'title' => 'Chief of Staff', 'dept' => 'Executive', 'icon' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80', 'personality' => 'Strategic, precise, reassuring', 'prompt' => 'You are the Chief of Staff, orchestrating operations and aligning team actions.'],
                ['name' => 'Elena Rostova', 'title' => 'VP of Strategy & Growth', 'dept' => 'Strategy', 'icon' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80', 'personality' => 'Analytical, visionary, razor-sharp', 'prompt' => 'You are the VP of Strategy, specializing in market expansion, revenue engines, and scaling.'],
                ['name' => 'Marcus Vance', 'title' => 'Chief Technology Officer', 'dept' => 'Engineering', 'icon' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80', 'personality' => 'Pragmatic, architectural, cutting-edge', 'prompt' => 'You are the CTO, leading technology architecture, cloud execution, and code quality.'],
                ['name' => 'Samantha Lin', 'title' => 'Chief Marketing Officer', 'dept' => 'Marketing', 'icon' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80', 'personality' => 'Creative, metrics-driven, viral storyteller', 'prompt' => 'You are the CMO, managing audience growth, brand positioning, and conversions.']
            ];

            $insExec = $pdo->prepare("
                INSERT INTO executives (name, title, department, avatar_url, bio, personality, system_prompt, is_system, venture_id) 
                VALUES (:name, :title, :dept, :avatar, :bio, :personality, :prompt, 0, :vid)
            ");
            foreach ($starterTeam as $exec) {
                $insExec->execute([
                    ':name' => $exec['name'],
                    ':title' => $exec['title'],
                    ':dept' => $exec['dept'],
                    ':avatar' => $exec['icon'],
                    ':bio' => "Core executive advisory member for {$workspaceTitle}.",
                    ':personality' => $exec['personality'],
                    ':prompt' => $exec['prompt'],
                    ':vid' => $userVentureId
                ]);
            }
        }

        $createdUser = getUserById($newUserId);
        unset($createdUser['password_hash']);

        sendJsonResponse([
            'success' => true,
            'message' => 'User created successfully.',
            'user' => $createdUser
        ], 201);
    } catch (\Throwable $e) {
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// 2.3 UPDATE USER
if ($action === 'update_user' && $method === 'POST') {
    $userId = (int)($input['user_id'] ?? $_POST['user_id'] ?? 0);
    $name = trim($input['name'] ?? $_POST['name'] ?? '');
    $username = trim($input['username'] ?? $_POST['username'] ?? '');
    $email = strtolower(trim($input['email'] ?? $_POST['email'] ?? ''));
    $role = ($input['role'] ?? $_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $newPassword = $input['password'] ?? $_POST['password'] ?? '';

    if ($userId <= 0) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid user ID.'], 400);
    }

    $existing = getUserById($userId);
    if (!$existing) {
        sendJsonResponse(['success' => false, 'error' => 'User not found.'], 404);
    }

    // Safety: Prevent admin from revoking their own admin rights
    if ($userId === $adminId && $role !== 'admin') {
        sendJsonResponse(['success' => false, 'error' => 'You cannot revoke your own administrator privileges.'], 400);
    }

    if (mb_strlen($name) < 2) {
        sendJsonResponse(['success' => false, 'error' => 'Name must be at least 2 characters.'], 400);
    }
    if (mb_strlen($username) < 3 || mb_strlen($username) > 30 || !preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
        sendJsonResponse(['success' => false, 'error' => 'Username must be 3-30 valid characters.'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(['success' => false, 'error' => 'Valid email address is required.'], 400);
    }

    // Check unique username & email against other users
    $chkUser = getUserByUsernameOrEmail($username);
    if ($chkUser && (int)$chkUser['id'] !== $userId && strtolower($chkUser['username']) === strtolower($username)) {
        sendJsonResponse(['success' => false, 'error' => 'Username is already taken by another user.'], 400);
    }
    $chkEmail = getUserByUsernameOrEmail($email);
    if ($chkEmail && (int)$chkEmail['id'] !== $userId && strtolower($chkEmail['email']) === $email) {
        sendJsonResponse(['success' => false, 'error' => 'Email is already taken by another user.'], 400);
    }

    try {
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                sendJsonResponse(['success' => false, 'error' => 'New password must be at least 6 characters.'], 400);
            }
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = :n, username = :u, email = :e, role = :r, password_hash = :p WHERE id = :id");
            $stmt->execute([
                ':n' => $name,
                ':u' => $username,
                ':e' => $email,
                ':r' => $role,
                ':p' => $hash,
                ':id' => $userId
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = :n, username = :u, email = :e, role = :r WHERE id = :id");
            $stmt->execute([
                ':n' => $name,
                ':u' => $username,
                ':e' => $email,
                ':r' => $role,
                ':id' => $userId
            ]);
        }

        $updated = getUserById($userId);
        unset($updated['password_hash']);

        sendJsonResponse([
            'success' => true,
            'message' => 'User updated successfully.',
            'user' => $updated
        ]);
    } catch (\Throwable $e) {
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// 2.4 TOGGLE USER ROLE
if ($action === 'toggle_role' && $method === 'POST') {
    $userId = (int)($input['user_id'] ?? $_POST['user_id'] ?? 0);
    $targetRole = ($input['role'] ?? $_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';

    if ($userId <= 0) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid user ID.'], 400);
    }

    if ($userId === $adminId && $targetRole !== 'admin') {
        sendJsonResponse(['success' => false, 'error' => 'You cannot demote yourself from admin.'], 400);
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET role = :r WHERE id = :id");
        $stmt->execute([':r' => $targetRole, ':id' => $userId]);

        sendJsonResponse([
            'success' => true,
            'message' => "User role successfully changed to {$targetRole}."
        ]);
    } catch (\Throwable $e) {
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// 2.5 DELETE USER
if ($action === 'delete_user' && $method === 'POST') {
    $userId = (int)($input['user_id'] ?? $_POST['user_id'] ?? 0);

    if ($userId <= 0) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid user ID.'], 400);
    }

    if ($userId === $adminId) {
        sendJsonResponse(['success' => false, 'error' => 'You cannot delete your own logged-in account.'], 400);
    }

    try {
        // Cascade cleanup of associated entities
        $pdo->beginTransaction();

        // 1. Get user ventures
        $vStmt = $pdo->prepare("SELECT id FROM ventures WHERE user_id = :uid");
        $vStmt->execute([':uid' => $userId]);
        $ventureIds = $vStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($ventureIds)) {
            $inClause = implode(',', array_map('intval', $ventureIds));
            // Delete executives in these ventures
            $pdo->exec("DELETE FROM executives WHERE venture_id IN ($inClause)");
            // Delete board meetings & dialogues
            $pdo->exec("DELETE FROM board_meetings WHERE venture_id IN ($inClause)");
        }

        // 2. Delete user's messages (via sessions cascade or direct)
        $pdo->prepare("DELETE FROM messages WHERE session_id IN (SELECT id FROM sessions WHERE user_id = :uid)")->execute([':uid' => $userId]);
        $pdo->prepare("DELETE FROM sessions WHERE user_id = :uid")->execute([':uid' => $userId]);

        // 3. Delete user's notes / tasks
        $pdo->prepare("DELETE FROM notes WHERE user_id = :uid")->execute([':uid' => $userId]);

        // 4. Delete user's projects
        $pdo->prepare("DELETE FROM projects WHERE user_id = :uid")->execute([':uid' => $userId]);

        // 5. Delete user's memories
        $pdo->prepare("DELETE FROM memories WHERE user_id = :uid")->execute([':uid' => $userId]);

        // 6. Delete user's ventures
        $pdo->prepare("DELETE FROM ventures WHERE user_id = :uid")->execute([':uid' => $userId]);

        // 7. Delete user
        $pdo->prepare("DELETE FROM users WHERE id = :uid")->execute([':uid' => $userId]);

        $pdo->commit();

        sendJsonResponse([
            'success' => true,
            'message' => 'User and all associated workspace data have been completely removed.'
        ]);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// -------------------------------------------------------------
// 3. WORKSPACE MANAGEMENT
// -------------------------------------------------------------

// 3.1 LIST WORKSPACES
if ($action === 'workspaces' && $method === 'GET') {
    try {
        $search = trim($_GET['search'] ?? '');
        $userIdFilter = (int)($_GET['user_id'] ?? 0);

        $sql = "
            SELECT v.id, v.name, v.domain, v.description, v.icon, v.color, v.user_id, v.created_at,
                   u.name as owner_name, u.username as owner_username, u.email as owner_email, u.avatar_url as owner_avatar,
                   (SELECT COUNT(*) FROM executives WHERE venture_id = v.id) as team_count,
                   (SELECT COUNT(*) FROM projects WHERE venture_id = v.id) as projects_count,
                   (SELECT COUNT(*) FROM notes WHERE venture_id = v.id) as tasks_count,
                   (SELECT COUNT(*) FROM notes WHERE venture_id = v.id AND is_done = 0) as open_tasks_count,
                   (SELECT COUNT(*) FROM sessions WHERE venture_id = v.id) as sessions_count
            FROM ventures v
            LEFT JOIN users u ON v.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (v.name LIKE :s1 OR v.domain LIKE :s2 OR v.description LIKE :s3 OR u.name LIKE :s4)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
            $params[':s4'] = "%{$search}%";
        }

        if ($userIdFilter > 0) {
            $sql .= " AND v.user_id = :uid";
            $params[':uid'] = $userIdFilter;
        }

        $sql .= " ORDER BY v.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $workspaces = $stmt->fetchAll();

        sendJsonResponse(['success' => true, 'workspaces' => $workspaces]);
    } catch (\Throwable $e) {
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// 3.2 WORKSPACE DETAILS (WITH EXECUTIVES & PROJECTS)
if ($action === 'workspace_details' && $method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid workspace ID.'], 400);
    }

    try {
        $stmt = $pdo->prepare("
            SELECT v.*, u.name as owner_name, u.username as owner_username, u.email as owner_email
            FROM ventures v
            LEFT JOIN users u ON v.user_id = u.id
            WHERE v.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $workspace = $stmt->fetch();

        if (!$workspace) {
            sendJsonResponse(['success' => false, 'error' => 'Workspace not found.'], 404);
        }

        // Executives in this workspace
        $execStmt = $pdo->prepare("SELECT * FROM executives WHERE venture_id = :id ORDER BY id ASC");
        $execStmt->execute([':id' => $id]);
        $executives = $execStmt->fetchAll();

        // Projects in this workspace
        $projStmt = $pdo->prepare("SELECT * FROM projects WHERE venture_id = :id ORDER BY id ASC");
        $projStmt->execute([':id' => $id]);
        $projects = $projStmt->fetchAll();

        // Recent tasks in this workspace
        $taskStmt = $pdo->prepare("SELECT * FROM notes WHERE venture_id = :id ORDER BY id DESC LIMIT 10");
        $taskStmt->execute([':id' => $id]);
        $tasks = $taskStmt->fetchAll();

        sendJsonResponse([
            'success' => true,
            'workspace' => $workspace,
            'executives' => $executives,
            'projects' => $projects,
            'tasks' => $tasks
        ]);
    } catch (\Throwable $e) {
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// 3.3 CREATE WORKSPACE
if ($action === 'create_workspace' && $method === 'POST') {
    $targetUserId = (int)($input['user_id'] ?? $_POST['user_id'] ?? $adminId);
    $name = trim($input['name'] ?? $_POST['name'] ?? '');
    $domain = trim($input['domain'] ?? $_POST['domain'] ?? 'General Operations');
    $description = trim($input['description'] ?? $_POST['description'] ?? '');
    $icon = trim($input['icon'] ?? $_POST['icon'] ?? 'briefcase');
    $color = trim($input['color'] ?? $_POST['color'] ?? 'indigo');
    $autoGenerateTeam = !empty($input['auto_generate_team'] ?? $_POST['auto_generate_team'] ?? false);
    $templateId = (int)($input['template_id'] ?? $_POST['template_id'] ?? 0);

    if (empty($name)) {
        sendJsonResponse(['success' => false, 'error' => 'Workspace name is required.'], 400);
    }

    // Verify target user exists
    $targetUser = getUserById($targetUserId);
    if (!$targetUser) {
        sendJsonResponse(['success' => false, 'error' => 'Selected target user does not exist.'], 400);
    }

    try {
        // If template selected, provision from template
        if ($templateId > 0) {
            $tplStmt = $pdo->prepare("SELECT * FROM workspace_templates WHERE id = :id");
            $tplStmt->execute([':id' => $templateId]);
            $template = $tplStmt->fetch();

            if ($template) {
                require_once __DIR__ . '/workspace_store.php';
                $newVenture = provisionWorkspaceFromTemplate($pdo, $targetUserId, $template, 'admin_provision', 0.00, 'ADMIN-' . uniqid());
                sendJsonResponse([
                    'success' => true,
                    'message' => "Workspace '{$template['title']}' provisioned from template for {$targetUser['name']}.",
                    'venture' => $newVenture
                ], 201);
            }
        }

        // Standard custom creation
        $stmt = $pdo->prepare("
            INSERT INTO ventures (name, domain, description, icon, color, user_id) 
            VALUES (:name, :domain, :description, :icon, :color, :uid)
        ");
        $stmt->execute([
            ':name' => $name,
            ':domain' => $domain,
            ':description' => $description,
            ':icon' => $icon,
            ':color' => $color,
            ':uid' => $targetUserId
        ]);
        $newVentureId = (int)$pdo->lastInsertId();

        // Create default starter project
        $insProj = $pdo->prepare("
            INSERT INTO projects (venture_id, name, description, color, icon, status, user_id) 
            VALUES (:vid, :name, 'Roadmap & Key Deliverables for this workspace', :color, 'folder-kanban', 'active', :uid)
        ");
        $insProj->execute([
            ':vid' => $newVentureId,
            ':name' => "{$name} Operations",
            ':color' => $color,
            ':uid' => $targetUserId
        ]);

        $generatedTeam = [];

        // Auto-generate specialized executive dream team via Gemini if requested
        if ($autoGenerateTeam) {
            $portraitPool = [
                'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80'
            ];

            $systemPrompt = "You are an elite corporate architect. The executive launched a new venture named '{$name}' in the domain '{$domain}'. Description: '{$description}'. Generate exactly 4 specialized C-level or VP-level executive AI agents to form this company's executive committee. Return ONLY a valid JSON array of 4 objects with: name, title, department, personality, system_prompt, bio.";
            $messages = [
                ['role' => 'user', 'content' => "Generate the 4-person executive team for '{$name}' ({$domain}). Return pure JSON array only."]
            ];

            $geminiRes = callGeminiApi($messages, $systemPrompt, 'venture_creation');

            if (!empty($geminiRes['candidates'][0]['content']['parts'][0]['text'])) {
                $rawJson = $geminiRes['candidates'][0]['content']['parts'][0]['text'];
                $rawJson = preg_replace('/```json\s*/i', '', $rawJson);
                $rawJson = preg_replace('/```\s*$/i', '', $rawJson);
                $parsedTeam = json_decode(trim($rawJson), true);

                if (is_array($parsedTeam)) {
                    $insExec = $pdo->prepare("
                        INSERT INTO executives (name, title, department, avatar_url, bio, personality, system_prompt, is_system, venture_id) 
                        VALUES (:name, :title, :department, :avatar_url, :bio, :personality, :system_prompt, 0, :venture_id)
                    ");

                    foreach ($parsedTeam as $idx => $member) {
                        if (empty($member['name']) || empty($member['title'])) continue;
                        $avatar = $portraitPool[$idx % count($portraitPool)];
                        $insExec->execute([
                            ':name' => $member['name'],
                            ':title' => $member['title'],
                            ':department' => $member['department'] ?? $domain,
                            ':avatar_url' => $avatar,
                            ':bio' => $member['bio'] ?? "Specialist executive in {$domain}",
                            ':personality' => $member['personality'] ?? 'Driven, analytical, visionary',
                            ':system_prompt' => $member['system_prompt'] ?? "You are {$member['name']}, {$member['title']} of {$name}.",
                            ':venture_id' => $newVentureId
                        ]);
                        $member['id'] = $pdo->lastInsertId();
                        $member['avatar_url'] = $avatar;
                        $generatedTeam[] = $member;
                    }
                }
            }
        }

        // If team was not generated via Gemini, insert standard balanced team
        if (empty($generatedTeam)) {
            $defaultTeam = [
                ['name' => 'Strategic Lead', 'title' => 'Director of Strategy', 'dept' => $domain, 'icon' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80', 'bio' => "Strategic lead overseeing operations in {$domain}."],
                ['name' => 'Technical Advisor', 'title' => 'Chief Technology Lead', 'dept' => 'Engineering', 'icon' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80', 'bio' => "Technical advisor focused on systems, tooling, and execution."],
                ['name' => 'Operations Officer', 'title' => 'Head of Execution', 'dept' => 'Operations', 'icon' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80', 'bio' => "Directs daily workflows, milestones, and deliverables."],
                ['name' => 'Growth Architect', 'title' => 'Lead Growth Strategist', 'dept' => 'Growth', 'icon' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80', 'bio' => "Identifies expansion avenues, partnerships, and distribution."]
            ];

            $insExec = $pdo->prepare("
                INSERT INTO executives (name, title, department, avatar_url, bio, personality, system_prompt, is_system, venture_id) 
                VALUES (:name, :title, :dept, :avatar, :bio, 'Analytical and proactive', :prompt, 0, :vid)
            ");
            foreach ($defaultTeam as $ex) {
                $insExec->execute([
                    ':name' => $ex['name'],
                    ':title' => $ex['title'],
                    ':dept' => $ex['dept'],
                    ':avatar' => $ex['icon'],
                    ':bio' => $ex['bio'],
                    ':prompt' => "You are {$ex['name']}, {$ex['title']} of {$name}. Deliver actionable advisory for {$domain}.",
                    ':vid' => $newVentureId
                ]);
            }
        }

        sendJsonResponse([
            'success' => true,
            'message' => "Workspace '{$name}' created successfully for {$targetUser['name']}.",
            'workspace' => [
                'id' => $newVentureId,
                'name' => $name,
                'domain' => $domain,
                'description' => $description,
                'icon' => $icon,
                'color' => $color,
                'user_id' => $targetUserId
            ]
        ], 201);
    } catch (\Throwable $e) {
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// 3.4 UPDATE WORKSPACE
if ($action === 'update_workspace' && $method === 'POST') {
    $id = (int)($input['id'] ?? $_POST['id'] ?? 0);
    $name = trim($input['name'] ?? $_POST['name'] ?? '');
    $domain = trim($input['domain'] ?? $_POST['domain'] ?? '');
    $description = trim($input['description'] ?? $_POST['description'] ?? '');
    $icon = trim($input['icon'] ?? $_POST['icon'] ?? 'briefcase');
    $color = trim($input['color'] ?? $_POST['color'] ?? 'indigo');
    $newUserId = isset($input['user_id']) ? (int)$input['user_id'] : null;

    if ($id <= 0 || empty($name)) {
        sendJsonResponse(['success' => false, 'error' => 'Valid workspace ID and name are required.'], 400);
    }

    try {
        $chk = $pdo->prepare("SELECT * FROM ventures WHERE id = :id");
        $chk->execute([':id' => $id]);
        $existing = $chk->fetch();

        if (!$existing) {
            sendJsonResponse(['success' => false, 'error' => 'Workspace not found.'], 404);
        }

        $fields = [
            'name = :name',
            'domain = :domain',
            'description = :description',
            'icon = :icon',
            'color = :color'
        ];
        $params = [
            ':name' => $name,
            ':domain' => $domain ?: 'General Operations',
            ':description' => $description,
            ':icon' => $icon,
            ':color' => $color,
            ':id' => $id
        ];

        // Transfer workspace to another user
        if ($newUserId !== null && $newUserId > 0 && $newUserId !== (int)$existing['user_id']) {
            $uChk = getUserById($newUserId);
            if (!$uChk) {
                sendJsonResponse(['success' => false, 'error' => 'Target new owner user not found.'], 400);
            }
            $fields[] = 'user_id = :uid';
            $params[':uid'] = $newUserId;

            // Also update associated projects and tasks to new user
            $pdo->prepare("UPDATE projects SET user_id = :uid WHERE venture_id = :id")->execute([':uid' => $newUserId, ':id' => $id]);
            $pdo->prepare("UPDATE notes SET user_id = :uid WHERE venture_id = :id")->execute([':uid' => $newUserId, ':id' => $id]);
        }

        $sql = "UPDATE ventures SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        sendJsonResponse([
            'success' => true,
            'message' => 'Workspace updated successfully.'
        ]);
    } catch (\Throwable $e) {
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// 3.5 DELETE WORKSPACE
if ($action === 'delete_workspace' && $method === 'POST') {
    $id = (int)($input['id'] ?? $_POST['id'] ?? 0);

    if ($id <= 0) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid workspace ID.'], 400);
    }

    try {
        $vStmt = $pdo->prepare("SELECT * FROM ventures WHERE id = :id");
        $vStmt->execute([':id' => $id]);
        $venture = $vStmt->fetch();

        if (!$venture) {
            sendJsonResponse(['success' => false, 'error' => 'Workspace not found.'], 404);
        }

        $ownerId = (int)$venture['user_id'];

        // Check how many workspaces this user has
        $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM ventures WHERE user_id = :uid");
        $cntStmt->execute([':uid' => $ownerId]);
        $userTotalVentures = (int)$cntStmt->fetchColumn();

        if ($userTotalVentures <= 1) {
            sendJsonResponse(['success' => false, 'error' => 'Cannot delete this workspace because it is the only workspace belonging to this user. Create an alternate workspace first.'], 400);
        }

        // Find fallback workspace for this user
        $fallbackStmt = $pdo->prepare("SELECT id FROM ventures WHERE user_id = :uid AND id != :id ORDER BY id ASC LIMIT 1");
        $fallbackStmt->execute([':uid' => $ownerId, ':id' => $id]);
        $fallbackVentureId = (int)$fallbackStmt->fetchColumn();

        $pdo->beginTransaction();

        // Migrate or remove associated items
        $pdo->prepare("UPDATE projects SET venture_id = :fid WHERE venture_id = :id")->execute([':fid' => $fallbackVentureId, ':id' => $id]);
        $pdo->prepare("UPDATE notes SET venture_id = :fid WHERE venture_id = :id")->execute([':fid' => $fallbackVentureId, ':id' => $id]);
        $pdo->prepare("UPDATE sessions SET venture_id = :fid WHERE venture_id = :id")->execute([':fid' => $fallbackVentureId, ':id' => $id]);

        // Remove executives in this workspace
        $pdo->prepare("DELETE FROM executives WHERE venture_id = :id")->execute([':id' => $id]);

        // Remove venture
        $pdo->prepare("DELETE FROM ventures WHERE id = :id")->execute([':id' => $id]);

        $pdo->commit();

        sendJsonResponse([
            'success' => true,
            'message' => 'Workspace deleted successfully. Associated roadmap entities safely migrated to user\'s alternate workspace.'
        ]);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// -------------------------------------------------------------
// 4. SYSTEM & AI TOOLS MANAGEMENT
// -------------------------------------------------------------

// 4.1 SYSTEM INFO & DATABASE STATUS
if ($action === 'system_info' && $method === 'GET') {
    try {
        $rawKey = getSetting('gemini_api_key', '');
        $maskedKey = !empty($rawKey) ? (strlen($rawKey) > 8 ? substr($rawKey, 0, 4) . '...' . substr($rawKey, -4) : '••••••••') : '';

        // Tables count
        $tables = ['users', 'ventures', 'executives', 'projects', 'notes', 'sessions', 'messages', 'memories', 'settings', 'token_usage', 'workspace_templates'];
        $tableCounts = [];
        foreach ($tables as $tbl) {
            try {
                $count = (int)$pdo->query("SELECT COUNT(*) FROM `{$tbl}`")->fetchColumn();
                $tableCounts[$tbl] = $count;
            } catch (\Throwable $e) {
                $tableCounts[$tbl] = 0;
            }
        }

        // MySQL version
        $dbVersion = 'Unknown';
        try {
            $dbVersion = $pdo->query("SELECT VERSION()")->fetchColumn() ?: 'Unknown';
        } catch (\Throwable $e) {}

        sendJsonResponse([
            'success' => true,
            'system' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache',
                'db_driver' => defined('DB_DRIVER') ? DB_DRIVER : 'mysql',
                'db_version' => $dbVersion,
                'max_upload_size' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'memory_limit' => ini_get('memory_limit')
            ],
            'ai' => [
                'has_api_key' => !empty($rawKey),
                'masked_api_key' => $maskedKey,
                'active_model' => getSetting('model', 'gemini-3.1-flash-lite'),
                'company_name' => getSetting('company_name', 'Enterprises'),
                'assistant_name' => getSetting('assistant_name', 'RuXt'),
                'tts_provider' => getSetting('tts_provider', 'browser')
            ],
            'table_counts' => $tableCounts
        ]);
    } catch (\Throwable $e) {
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// 4.2 TEST AI CONNECTIVITY
if ($action === 'test_ai' && $method === 'POST') {
    $startTime = microtime(true);
    $messages = [
        ['role' => 'user', 'content' => "Ping check! Please reply with exactly: 'RuXt AI Engine Online: [Your Model Name] - System Operational'"]
    ];

    $res = callGeminiApi($messages, "You are RuXt AI System Monitor.", 'test_ping');
    $latencyMs = round((microtime(true) - $startTime) * 1000);

    if (!empty($res['error'])) {
        sendJsonResponse([
            'success' => false,
            'error' => $res['error'],
            'latency_ms' => $latencyMs
        ], 400);
    }

    $reply = $res['candidates'][0]['content']['parts'][0]['text'] ?? 'Connected, no text candidate returned.';

    sendJsonResponse([
        'success' => true,
        'reply' => trim($reply),
        'latency_ms' => $latencyMs,
        'model' => getSetting('model', 'gemini-3.1-flash-lite')
    ]);
}

// Default fallback
sendJsonResponse([
    'success' => false,
    'error' => 'Invalid or unrecognized admin action.'
], 400);
