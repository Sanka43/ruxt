<?php
// api/onboarding.php - RuXt Onboarding & Dynamic Role Identification Engine
require_once __DIR__ . '/../config.php';

$user = requireLogin(true);
$userId = (int)$user['id'];
$userName = !empty($user['name']) ? $user['name'] : 'there';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 1. GET STATUS / PROVISION ONBOARDING SESSION
if ($method === 'GET') {
    $force = !empty($_GET['force']);

    // Check if user has active sessions or memories
    $sessCountStmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE user_id = :uid");
    $sessCountStmt->execute([':uid' => $userId]);
    $totalSessions = (int)$sessCountStmt->fetchColumn();

    $memCountStmt = $pdo->prepare("SELECT COUNT(*) FROM memories WHERE user_id = :uid");
    $memCountStmt->execute([':uid' => $userId]);
    $totalMemories = (int)$memCountStmt->fetchColumn();

    // User needs onboarding if forced, or flag is not set (0 or null)
    $isCompleted = !empty($user['onboarding_completed']) && intval($user['onboarding_completed']) === 1;
    $needsOnboarding = ($force || !$isCompleted);

    if (!$needsOnboarding) {
        sendJsonResponse([
            'success' => true,
            'needs_onboarding' => false,
            'job_field' => $user['job_field'] ?? '',
            'specialized_role' => $user['specialized_role'] ?? '',
            'onboarding_data' => !empty($user['onboarding_data']) ? json_decode($user['onboarding_data'], true) : null
        ]);
    }

    // If onboarding is explicitly forced and user flag was 1, reset it in database
    if ($force && !empty($user['onboarding_completed'])) {
        resetUserOnboarding($userId);
    }

    // Chief of Staff ID for RuXt
    $chiefStmt = $pdo->query("SELECT id, name, title, avatar_url FROM executives WHERE is_chief_of_staff = 1 LIMIT 1");
    $chief = $chiefStmt->fetch(PDO::FETCH_ASSOC);
    $chiefId = $chief ? intval($chief['id']) : 1;

    // Check if an existing onboarding session already exists for this user
    $sessionStmt = $pdo->prepare("
        SELECT id, title, created_at 
        FROM sessions 
        WHERE user_id = :uid 
          AND (title LIKE '%Role Setup%' OR title LIKE '%Welcome%')
        ORDER BY id DESC 
        LIMIT 1
    ");
    $sessionStmt->execute([':uid' => $userId]);
    $existingSession = $sessionStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingSession && !$force) {
        $sessionId = (int)$existingSession['id'];
    } else {
        if ($existingSession && $force) {
            $pdo->prepare("DELETE FROM sessions WHERE id = :id AND user_id = :uid")->execute([':id' => $existingSession['id'], ':uid' => $userId]);
        }

        // Create a new onboarding session
        $sessTitle = 'RuXt · Welcome & Role Setup';
        $vStmt = $pdo->prepare("SELECT id FROM ventures WHERE user_id = :uid ORDER BY id ASC LIMIT 1");
        $vStmt->execute([':uid' => $userId]);
        $userVid = $vStmt->fetchColumn();
        $assignedVentureId = $userVid ? (int)$userVid : null;

        $createStmt = $pdo->prepare("
            INSERT INTO sessions (title, executive_id, venture_id, user_id, project_name) 
            VALUES (:title, :eid, :vid, :uid, 'Onboarding')
        ");
        $createStmt->execute([
            ':title' => $sessTitle,
            ':eid' => $chiefId,
            ':vid' => $assignedVentureId,
            ':uid' => $userId
        ]);
        $sessionId = (int)$pdo->lastInsertId();
    }

    // Check if greeting message exists in this session
    $msgCheck = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE session_id = :sid");
    $msgCheck->execute([':sid' => $sessionId]);
    $msgCount = (int)$msgCheck->fetchColumn();

    if ($msgCount === 0) {
        // Fetch random free workspace templates from database
        $tplStmt = $pdo->query("SELECT slug, title, domain, icon FROM workspace_templates WHERE tier = 'free' AND is_active = 1 ORDER BY RAND() LIMIT 5");
        $freeTemplates = $tplStmt ? $tplStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $emojiMap = [
            'video' => '🎬',
            'trending-up' => '📈',
            'code' => '💻',
            'code-2' => '💻',
            'briefcase' => '💼',
            'shopping-bag' => '🛒',
            'shopping-cart' => '🛒',
            'home' => '🏠',
            'building' => '🏢',
            'zap' => '⚡',
            'target' => '🎯',
            'graduation-cap' => '🎓'
        ];

        $quickOptions = [];
        foreach ($freeTemplates as $ft) {
            $ico = $ft['icon'] ?? 'briefcase';
            $emo = $emojiMap[$ico] ?? '🚀';
            $quickOptions[] = "{$emo} {$ft['title']}";
        }

        if (empty($quickOptions)) {
            $quickOptions = [
                '🎬 YouTube & Content Creator Media Hub',
                '📈 Digital Marketing & Creative Agency',
                '💻 SaaS & Full-Stack Tech Studio',
                '💼 Solo Freelancer & Client Operating System',
                '🛒 E-Commerce & Dropshipping Growth Engine'
            ];
        }

        // Append Other / Custom option
        $quickOptions[] = '✨ Other / Custom';
        $quickJson = json_encode($quickOptions, JSON_UNESCAPED_UNICODE);

        $welcomeText = "Hello **{$userName}**! Welcome to RuXt AI. 🌟\n\n" .
            "I am **RuXt**, your personal AI partner and Chief of Staff. Before we begin, let's take a quick moment to get to know you and your work so I can tailor my job role and expertise specifically to your field!\n\n" .
            "Could you tell me:\n" .
            "1. **What field or industry do you work in?** *(e.g., Software & Web Development, E-Commerce, Freelancing / Agency, Digital Marketing, Operations)*\n" .
            "2. **What is your primary role or business?** *(e.g., Founder, Software Engineer, Freelancer, Store Owner, Manager)*\n" .
            "3. **What are the top things you want me to assist you with every day?**\n\n" .
            "*(You can select one of our free starter workspaces below, specify your own domain under Other, or skip to start chatting immediately 👇)*\n\n" .
            "[[QUICK_FIELDS: {$quickJson}]]";

        $insMsg = $pdo->prepare("INSERT INTO messages (session_id, role, content) VALUES (:sid, 'model', :content)");
        $insMsg->execute([
            ':sid' => $sessionId,
            ':content' => $welcomeText
        ]);
    }

    sendJsonResponse([
        'success' => true,
        'needs_onboarding' => true,
        'session_id' => $sessionId,
        'executive_id' => $chiefId,
        'executive_name' => $chief ? $chief['name'] : 'RuXt',
        'executive_title' => $chief ? $chief['title'] : 'Chief of Staff & Lead Private Assistant',
        'avatar_url' => $chief ? $chief['avatar_url'] : ''
    ]);
}

// 2. POST ACTIONS
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
    $postAction = $input['action'] ?? $action;

    if ($postAction === 'reset') {
        resetUserOnboarding($userId);
        sendJsonResponse([
            'success' => true,
            'message' => 'Onboarding reset. Ready for new role configuration.'
        ]);
    }

    if ($postAction === 'skip') {
        updateUserOnboarding($userId, 'General Operations', 'Chief of Staff & Lead Private Assistant', [
            'field' => 'General Operations',
            'role_title' => 'Chief of Staff & Lead Private Assistant',
            'skipped' => true,
            'skipped_at' => date('Y-m-d H:i:s')
        ]);

        try {
            $memCheck = $pdo->prepare("SELECT COUNT(*) FROM memories WHERE user_id = :uid AND category = 'user_profile'");
            $memCheck->execute([':uid' => $userId]);
            if ((int)$memCheck->fetchColumn() === 0) {
                $memStmt = $pdo->prepare("INSERT INTO memories (category, content, user_id) VALUES ('user_profile', 'User prefers general executive assistance across all domains.', :uid)");
                $memStmt->execute([':uid' => $userId]);
            }
        } catch (\Throwable $e) {}

        sendJsonResponse([
            'success' => true,
            'message' => 'Onboarding skipped. You can continue the conversation directly.'
        ]);
    }

    sendJsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
