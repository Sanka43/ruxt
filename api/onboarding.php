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
    $needsOnboarding = ($force || empty($user['onboarding_completed']) || intval($user['onboarding_completed']) === 0);

    if (!$needsOnboarding) {
        sendJsonResponse([
            'success' => true,
            'needs_onboarding' => false,
            'job_field' => $user['job_field'] ?? '',
            'specialized_role' => $user['specialized_role'] ?? '',
            'onboarding_data' => !empty($user['onboarding_data']) ? json_decode($user['onboarding_data'], true) : null
        ]);
    }

    // Chief of Staff ID for RuXt
    $chiefStmt = $pdo->query("SELECT id, name, title, avatar_url FROM executives WHERE is_chief_of_staff = 1 LIMIT 1");
    $chief = $chiefStmt->fetch(PDO::FETCH_ASSOC);
    $chiefId = $chief ? intval($chief['id']) : 1;

    // Check if an existing onboarding session already exists for this user
    $sessionStmt = $pdo->prepare("
        SELECT id, title, created_at 
        FROM sessions 
        WHERE (user_id = :uid OR user_id IS NULL) 
          AND title LIKE '%Role Setup%'
        ORDER BY id DESC 
        LIMIT 1
    ");
    $sessionStmt->execute([':uid' => $userId]);
    $existingSession = $sessionStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingSession && !$force) {
        $sessionId = (int)$existingSession['id'];
    } else {
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
        $welcomeText = "Hello **{$userName}**! Welcome to RuXt AI. 🌟\n\n" .
            "I am **RuXt**, your personal AI partner and Chief of Staff. Before we begin, let's take a quick moment to get to know you and your work so I can tailor my job role and expertise specifically to your field!\n\n" .
            "Could you tell me:\n" .
            "1. **What field or industry do you work in?** *(e.g., Software & Web Development, E-Commerce, Freelancing / Agency, Digital Marketing, Operations)*\n" .
            "2. **What is your primary role or business?** *(e.g., Founder, Software Engineer, Freelancer, Store Owner, Manager)*\n" .
            "3. **What are the top things you want me to assist you with every day?**\n\n" .
            "*(You can reply directly or click one of the quick options below to get started immediately 👇)*\n\n" .
            "[[QUICK_FIELDS: [\"💻 Software & Web Development\", \"🛒 E-Commerce & Retail\", \"💼 Freelance & Client Work\", \"📈 Marketing & Growth\", \"🏢 Executive Chief of Staff\", \"✨ Other / Custom\"]]]";

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

    sendJsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
