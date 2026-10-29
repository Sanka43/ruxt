<?php
// api/bots.php
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, name, role, icon, color, system_prompt, is_system, created_at FROM bots ORDER BY id ASC");
    $bots = $stmt->fetchAll();
    sendJsonResponse(['bots' => $bots]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = isset($input['name']) ? trim($input['name']) : '';
    $role = isset($input['role']) ? trim($input['role']) : '';
    $icon = isset($input['icon']) ? trim($input['icon']) : 'bot';
    $color = isset($input['color']) ? trim($input['color']) : 'indigo';
    $systemPrompt = isset($input['system_prompt']) ? trim($input['system_prompt']) : '';

    if (empty($name) || empty($role)) {
        sendJsonResponse(['error' => 'Bot name and role are required'], 400);
    }

    // Auto-generate system prompt using AI if not provided
    if (empty($systemPrompt)) {
        $promptGenMessages = [
            [
                'role' => 'user',
                'content' => "Generate an expert AI persona system instruction prompt for a specialized bot named '{$name}' with the job role: '{$role}'. It should instruct the AI to be an elite specialist in this domain, give actionable advice, write code or strategies when needed, and communicate clearly in English or whatever language the user speaks. Return ONLY the prompt text, no extra markdown or chit-chat."
            ]
        ];
        $res = callGeminiApi($promptGenMessages, "You are an AI prompt engineer.", 'prompt_generation');
        if (isset($res['reply'])) {
            $systemPrompt = trim($res['reply']);
        } else {
            $systemPrompt = "You are {$name}, an elite specialist in {$role}. Provide expert advice, solutions, and strategies tailored to the user's specific context.";
        }
    }

    $stmt = $pdo->prepare("INSERT INTO bots (name, role, icon, color, system_prompt) VALUES (:name, :role, :icon, :color, :system_prompt)");
    $stmt->execute([
        ':name' => $name,
        ':role' => $role,
        ':icon' => $icon,
        ':color' => $color,
        ':system_prompt' => $systemPrompt
    ]);

    sendJsonResponse([
        'success' => true,
        'bot' => [
            'id' => $pdo->lastInsertId(),
            'name' => $name,
            'role' => $role,
            'icon' => $icon,
            'color' => $color,
            'system_prompt' => $systemPrompt,
            'is_system' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ], 201);
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        sendJsonResponse(['error' => 'Invalid bot ID'], 400);
    }

    // Protect system bots
    $stmt = $pdo->prepare("SELECT is_system FROM bots WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $bot = $stmt->fetch();
    if ($bot && $bot['is_system'] == 1) {
        sendJsonResponse(['error' => 'Core system bots cannot be deleted'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM bots WHERE id = :id");
    $stmt->execute([':id' => $id]);

    sendJsonResponse(['success' => true]);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
