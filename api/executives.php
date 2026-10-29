<?php
// api/executives.php
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $ventureId = isset($_GET['venture_id']) ? intval($_GET['venture_id']) : 0;

    if ($ventureId > 0) {
        // Return RuXt (Chief of Staff) + specialists for this specific venture
        $stmt = $pdo->prepare("
            SELECT id, name, title, department, avatar_url, bio, personality, system_prompt, is_system, is_chief_of_staff, venture_id, voice_id, voice_gender 
            FROM executives 
            WHERE is_chief_of_staff = 1 OR venture_id = :vid
            ORDER BY is_chief_of_staff DESC, id ASC
        ");
        $stmt->execute([':vid' => $ventureId]);
    } else {
        $stmt = $pdo->query("SELECT id, name, title, department, avatar_url, bio, personality, system_prompt, is_system, is_chief_of_staff, venture_id, voice_id, voice_gender FROM executives ORDER BY is_chief_of_staff DESC, id ASC");
    }
    $executives = $stmt->fetchAll();
    sendJsonResponse(['executives' => $executives]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = isset($input['name']) ? trim($input['name']) : '';
    $title = isset($input['title']) ? trim($input['title']) : '';
    $department = isset($input['department']) ? trim($input['department']) : 'Executive Leadership';
    $ventureId = isset($input['venture_id']) && intval($input['venture_id']) > 0 ? intval($input['venture_id']) : 1;
    $avatarUrl = isset($input['avatar_url']) && !empty($input['avatar_url']) 
        ? trim($input['avatar_url']) 
        : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80';
    $bio = isset($input['bio']) ? trim($input['bio']) : '';
    $personality = isset($input['personality']) ? trim($input['personality']) : '';
    $systemPrompt = isset($input['system_prompt']) ? trim($input['system_prompt']) : '';
    $voiceGender = isset($input['voice_gender']) && in_array(strtolower($input['voice_gender']), ['male', 'female']) ? strtolower($input['voice_gender']) : 'female';
    $voiceId = isset($input['voice_id']) && !empty($input['voice_id']) ? trim($input['voice_id']) : ($voiceGender === 'male' ? 'fable' : 'alloy');

    if (empty($name) || empty($title)) {
        sendJsonResponse(['error' => 'Executive name and executive title are required'], 400);
    }

    if (empty($systemPrompt)) {
        // Auto-generate realistic human executive persona via AI
        $promptGenMessages = [
            [
                'role' => 'user',
                'content' => "Generate a realistic, authoritative, and sharp C-level executive persona system instruction for '{$name}', who holds the position of '{$title}' in '{$department}'. They treat the user as their CEO/Founder. They should talk like a real seasoned human executive, not a chatbot. They communicate with executive clarity in English and seamlessly adapt to whatever language the CEO speaks. Return ONLY the system instruction text."
            ]
        ];
        $res = callGeminiApi($promptGenMessages, "You are an executive talent recruiter and AI architect.", 'executive_recruitment');
        $systemPrompt = isset($res['reply']) ? trim($res['reply']) : "You are {$name}, {$title}. You advise your CEO with executive precision.";
    }

    $stmt = $pdo->prepare("INSERT INTO executives (name, title, department, avatar_url, bio, personality, system_prompt, is_system, venture_id, voice_id, voice_gender) VALUES (:name, :title, :department, :avatar_url, :bio, :personality, :system_prompt, 0, :venture_id, :voice_id, :voice_gender)");
    $stmt->execute([
        ':name' => $name,
        ':title' => $title,
        ':department' => $department,
        ':avatar_url' => $avatarUrl,
        ':bio' => $bio,
        ':personality' => $personality,
        ':system_prompt' => $systemPrompt,
        ':venture_id' => $ventureId,
        ':voice_id' => $voiceId,
        ':voice_gender' => $voiceGender
    ]);

    sendJsonResponse([
        'success' => true,
        'executive' => [
            'id' => $pdo->lastInsertId(),
            'name' => $name,
            'title' => $title,
            'department' => $department,
            'avatar_url' => $avatarUrl,
            'bio' => $bio,
            'personality' => $personality,
            'system_prompt' => $systemPrompt,
            'venture_id' => $ventureId,
            'voice_id' => $voiceId,
            'voice_gender' => $voiceGender,
            'is_system' => 0
        ]
    ], 201);
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        sendJsonResponse(['error' => 'Invalid executive ID'], 400);
    }

    $stmt = $pdo->prepare("SELECT is_system, is_chief_of_staff FROM executives WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $ex = $stmt->fetch();
    if ($ex && ($ex['is_system'] == 1 || $ex['is_chief_of_staff'] == 1)) {
        sendJsonResponse(['error' => 'Core leadership and Chief of Staff cannot be removed'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM executives WHERE id = :id");
    $stmt->execute([':id' => $id]);

    sendJsonResponse(['success' => true]);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
