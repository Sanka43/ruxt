<?php
// api/settings.php
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rawKey = getSetting('gemini_api_key', '');
    $maskedKey = '';
    if (!empty($rawKey)) {
        if (strlen($rawKey) > 8) {
            $maskedKey = substr($rawKey, 0, 4) . '...' . substr($rawKey, -4);
        } else {
            $maskedKey = '••••••••';
        }
    }

    $rawElevenKey = getSetting('elevenlabs_api_key', '');
    $maskedElevenKey = '';
    if (!empty($rawElevenKey)) {
        $maskedElevenKey = strlen($rawElevenKey) > 8 ? substr($rawElevenKey, 0, 4) . '...' . substr($rawElevenKey, -4) : '••••••••';
    }

    $rawOpenAIKey = getSetting('openai_api_key', '');
    $maskedOpenAIKey = '';
    if (!empty($rawOpenAIKey)) {
        $maskedOpenAIKey = strlen($rawOpenAIKey) > 8 ? substr($rawOpenAIKey, 0, 4) . '...' . substr($rawOpenAIKey, -4) : '••••••••';
    }

    $currentUser = getCurrentUser();
    $userId = $currentUser ? (int)$currentUser['id'] : null;

    if ($userId) {
        $memCountStmt = $pdo->prepare("SELECT COUNT(*) FROM memories WHERE user_id = :uid");
        $memCountStmt->execute([':uid' => $userId]);
        $memoriesCount = (int)$memCountStmt->fetchColumn();
    } else {
        $memCountStmt = $pdo->query("SELECT COUNT(*) FROM memories");
        $memoriesCount = (int)$memCountStmt->fetchColumn();
    }

    $tokenStats = getTokenUsageStats();
    $userName = !empty($currentUser['name']) ? $currentUser['name'] : getSetting('user_name', 'CEO');

    sendJsonResponse([
        'settings' => [
            'has_api_key' => !empty($rawKey),
            'masked_api_key' => $maskedKey,
            'model' => getSetting('model', 'gemini-3-flash-preview'),
            'company_name' => getSetting('company_name', 'Enterprises'),
            'assistant_name' => getSetting('assistant_name', 'RuXt'),
            'user_name' => $userName,
            'current_user' => $currentUser,
            'system_instruction' => getSetting('system_instruction', ''),
            'tts_provider' => getSetting('tts_provider', 'browser'),
            'has_elevenlabs_key' => !empty($rawElevenKey),
            'masked_elevenlabs_key' => $maskedElevenKey,
            'has_openai_key' => !empty($rawOpenAIKey),
            'masked_openai_key' => $maskedOpenAIKey,
            'voice_input_lang' => getSetting('voice_input_lang', 'en-US'),
            'voice_auto_speak' => (getSetting('voice_auto_speak', '0') === '1'),
            'memories_count' => $memoriesCount,
            'token_usage' => $tokenStats
        ]
    ]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!empty($input['reset_token_usage'])) {
        resetTokenUsage();
        sendJsonResponse([
            'success' => true,
            'message' => 'Token usage statistics reset successfully',
            'token_usage' => getTokenUsageStats()
        ]);
    }

    if (!empty($input['clear_all_memories'])) {
        $currentUser = getCurrentUser();
        $userId = $currentUser ? (int)$currentUser['id'] : null;
        if ($userId) {
            $stmt = $pdo->prepare("DELETE FROM memories WHERE user_id = :uid");
            $stmt->execute([':uid' => $userId]);
        } else {
            $pdo->exec("DELETE FROM memories");
        }
    }

    if (!empty($input['clear_gemini_api_key'])) {
        setSetting('gemini_api_key', '');
    } elseif (isset($input['gemini_api_key']) && trim($input['gemini_api_key']) !== '') {
        setSetting('gemini_api_key', trim($input['gemini_api_key']));
    }
    if (isset($input['model'])) {
        setSetting('model', trim($input['model']));
    }
    if (isset($input['company_name'])) {
        setSetting('company_name', trim($input['company_name']));
    }
    if (isset($input['assistant_name'])) {
        setSetting('assistant_name', trim($input['assistant_name']));
    }
    if (isset($input['user_name'])) {
        setSetting('user_name', trim($input['user_name']));
    }
    if (isset($input['system_instruction'])) {
        setSetting('system_instruction', trim($input['system_instruction']));
    }
    if (isset($input['tts_provider'])) {
        setSetting('tts_provider', trim($input['tts_provider']));
    }
    if (!empty($input['clear_elevenlabs_api_key'])) {
        setSetting('elevenlabs_api_key', '');
    } elseif (isset($input['elevenlabs_api_key']) && trim($input['elevenlabs_api_key']) !== '') {
        setSetting('elevenlabs_api_key', trim($input['elevenlabs_api_key']));
    }
    if (!empty($input['clear_openai_api_key'])) {
        setSetting('openai_api_key', '');
    } elseif (isset($input['openai_api_key']) && trim($input['openai_api_key']) !== '') {
        setSetting('openai_api_key', trim($input['openai_api_key']));
    }
    if (isset($input['voice_input_lang'])) {
        setSetting('voice_input_lang', trim($input['voice_input_lang']));
    }
    if (isset($input['voice_auto_speak'])) {
        setSetting('voice_auto_speak', $input['voice_auto_speak'] ? '1' : '0');
    }

    sendJsonResponse(['success' => true, 'message' => 'Settings updated successfully']);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
