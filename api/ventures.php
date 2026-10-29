<?php
// api/ventures.php - User-Isolated Multi-Venture & Multi-Team Management
require_once __DIR__ . '/../config.php';

$user = requireLogin(true);
$userId = (int)$user['id'];

// Ensure user has at least 1 venture
$vCheck = $pdo->prepare("SELECT id FROM ventures WHERE user_id = :uid ORDER BY id ASC LIMIT 1");
$vCheck->execute([':uid' => $userId]);
$primaryVenture = $vCheck->fetch(PDO::FETCH_ASSOC);

if (!$primaryVenture) {
    $ins = $pdo->prepare("INSERT INTO ventures (name, domain, description, icon, color, user_id) VALUES ('Primary Workspace', 'General Operations', 'Main workspace for private AI assistance, strategic planning, and operational execution.', 'briefcase', 'indigo', :uid)");
    $ins->execute([':uid' => $userId]);
    $primaryVentureId = (int)$pdo->lastInsertId();
} else {
    $primaryVentureId = (int)$primaryVenture['id'];
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("
        SELECT v.*,
               (SELECT COUNT(*) FROM executives WHERE venture_id = v.id) as team_count,
               (SELECT COUNT(*) FROM notes WHERE venture_id = v.id AND is_done = 0 AND user_id = :uid1) as open_tasks_count,
               (SELECT COUNT(*) FROM board_meetings WHERE venture_id = v.id) as meetings_count
        FROM ventures v
        WHERE v.user_id = :uid2
        ORDER BY v.id ASC
    ");
    $stmt->execute([':uid1' => $userId, ':uid2' => $userId]);
    $ventures = $stmt->fetchAll();
    sendJsonResponse(['ventures' => $ventures]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = isset($input['name']) ? trim($input['name']) : '';
    $domain = isset($input['domain']) ? trim($input['domain']) : 'Business & Strategy';
    $description = isset($input['description']) ? trim($input['description']) : '';
    $icon = isset($input['icon']) ? trim($input['icon']) : 'briefcase';
    $color = isset($input['color']) ? trim($input['color']) : 'amber';
    $autoGenerateTeam = !empty($input['auto_generate_team']);

    if (empty($name)) {
        sendJsonResponse(['error' => 'Venture name is required'], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO ventures (name, domain, description, icon, color, user_id) VALUES (:name, :domain, :description, :icon, :color, :uid)");
    $stmt->execute([
        ':name' => $name,
        ':domain' => $domain,
        ':description' => $description,
        ':icon' => $icon,
        ':color' => $color,
        ':uid' => $userId
    ]);
    $ventureId = $pdo->lastInsertId();

    $generatedTeam = [];

    // Auto-generate specialized 4-person dream team via Gemini AI
    if ($autoGenerateTeam) {
        $portraitPool = [
            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80'
        ];

        $systemPrompt = "You are an elite corporate architect. The CEO launched a new venture named '{$name}' in the domain '{$domain}'. Description: '{$description}'. Generate exactly 4 specialized C-level or VP-level executive AI agents to form this company's executive committee. Return ONLY a valid JSON array of 4 objects with: name, title, department, personality, system_prompt, bio.";

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
                        ':venture_id' => $ventureId
                    ]);
                    $member['id'] = $pdo->lastInsertId();
                    $member['avatar_url'] = $avatar;
                    $generatedTeam[] = $member;
                }
            }
        }
    }

    sendJsonResponse([
        'success' => true,
        'venture' => [
            'id' => $ventureId,
            'name' => $name,
            'domain' => $domain,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
            'created_at' => date('Y-m-d H:i:s'),
            'team_count' => count($generatedTeam),
            'generated_team' => $generatedTeam
        ]
    ], 201);
}

if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? intval($input['id']) : 0;

    if ($id <= 0) {
        sendJsonResponse(['error' => 'Invalid venture ID'], 400);
    }

    if (isset($input['name'])) {
        $stmt = $pdo->prepare("UPDATE ventures SET name = :name WHERE id = :id AND user_id = :uid");
        $stmt->execute([':name' => trim($input['name']), ':id' => $id, ':uid' => $userId]);
    }
    if (isset($input['domain'])) {
        $stmt = $pdo->prepare("UPDATE ventures SET domain = :domain WHERE id = :id AND user_id = :uid");
        $stmt->execute([':domain' => trim($input['domain']), ':id' => $id, ':uid' => $userId]);
    }
    if (isset($input['description'])) {
        $stmt = $pdo->prepare("UPDATE ventures SET description = :description WHERE id = :id AND user_id = :uid");
        $stmt->execute([':description' => trim($input['description']), ':id' => $id, ':uid' => $userId]);
    }

    sendJsonResponse(['success' => true]);
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        sendJsonResponse(['error' => 'Invalid venture ID'], 400);
    }

    // Protect user's primary venture
    if ($id === $primaryVentureId) {
        sendJsonResponse(['error' => 'Core primary workspace cannot be deleted'], 400);
    }

    // Safely migrate associated entities of this user to Primary Workspace before deleting
    $pdo->prepare("UPDATE projects SET venture_id = :pvid WHERE venture_id = :id AND user_id = :uid")->execute([':pvid' => $primaryVentureId, ':id' => $id, ':uid' => $userId]);
    $pdo->prepare("UPDATE notes SET venture_id = :pvid WHERE venture_id = :id AND user_id = :uid")->execute([':pvid' => $primaryVentureId, ':id' => $id, ':uid' => $userId]);
    $pdo->prepare("UPDATE sessions SET venture_id = :pvid WHERE venture_id = :id AND user_id = :uid")->execute([':pvid' => $primaryVentureId, ':id' => $id, ':uid' => $userId]);

    $stmt = $pdo->prepare("DELETE FROM ventures WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $id, ':uid' => $userId]);

    sendJsonResponse(['success' => true]);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
