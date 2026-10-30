<?php
// api/settings.php
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? [];
if (!empty($input['action'])) {
    $action = $input['action'];
}

$currentUser = getCurrentUser();
$userId = $currentUser ? (int)$currentUser['id'] : null;
$isAdmin = $currentUser && (!empty($currentUser['role']) && $currentUser['role'] === 'admin');

if ($method === 'GET') {
    $userKey = $userId ? getUserGeminiKey($userId) : null;
    $systemKey = getSetting('gemini_api_key', '');
    $envKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');

    $effectiveKey = '';
    $keySource = 'none';

    if (!empty($userKey)) {
        $effectiveKey = $userKey;
        $keySource = 'personal';
    } elseif (!empty($systemKey)) {
        $effectiveKey = $systemKey;
        $keySource = 'system';
    } elseif (!empty($envKey)) {
        $effectiveKey = $envKey;
        $keySource = 'server_env';
    }

    $maskedKey = '';
    if (!empty($effectiveKey)) {
        if (strlen($effectiveKey) > 8) {
            $maskedKey = substr($effectiveKey, 0, 4) . '...' . substr($effectiveKey, -4);
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
    $googleClientId = getGoogleClientId();
    $googleClientSecret = getGoogleClientSecret();
    $maskedGoogleSecret = !empty($googleClientSecret) ? (strlen($googleClientSecret) > 6 ? substr($googleClientSecret, 0, 3) . '...' . substr($googleClientSecret, -3) : '***') : '';

    $activeModel = getEffectiveGeminiModel($userId);

    sendJsonResponse([
        'settings' => [
            'has_api_key' => !empty($effectiveKey),
            'key_source' => $keySource,
            'is_personal_key' => ($keySource === 'personal'),
            'masked_api_key' => $maskedKey,
            'model' => $activeModel,
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
            'google_client_id' => $googleClientId,
            'has_google_client_id' => !empty($googleClientId),
            'has_google_client_secret' => !empty($googleClientSecret),
            'masked_google_client_secret' => $maskedGoogleSecret,
            'memories_count' => $memoriesCount,
            'token_usage' => $tokenStats
        ]
    ]);
}

if ($method === 'POST') {
    // 1. ACTION: TEST API KEY (Real-time verification)
    if ($action === 'test_key' || !empty($input['test_key'])) {
        $candidateKey = trim($input['gemini_api_key'] ?? '');
        if (empty($candidateKey)) {
            $candidateKey = getEffectiveGeminiApiKey($userId);
        }

        if (empty($candidateKey)) {
            sendJsonResponse([
                'success' => false,
                'error' => 'Please enter a Gemini API Key to test.'
            ], 400);
        }

        $testModel = trim($input['model'] ?? getEffectiveGeminiModel($userId));

        // Send a minimal test prompt to Google Gemini
        $testMessages = [
            ['role' => 'user', 'content' => 'Ping check. Reply with: OK']
        ];
        $testResult = callGeminiApi($testMessages, "You are an API connection tester. Reply with 'OK'.", 'test_ping', $candidateKey, $testModel);

        if (!empty($testResult['success'])) {
            // If requested to save immediately on successful test:
            $autoSave = !empty($input['save_on_success']);
            if ($autoSave) {
                if ($userId) {
                    setUserGeminiKey($userId, $candidateKey, $testModel);
                }
                if ($isAdmin || empty($userId)) {
                    setSetting('gemini_api_key', $candidateKey);
                    if (!empty($testModel)) {
                        setSetting('model', $testModel);
                    }
                }
            }

            sendJsonResponse([
                'success' => true,
                'message' => 'Gemini API Key verified successfully! Model ' . ($testResult['model'] ?? $testModel) . ' is operational.',
                'model' => $testResult['model'] ?? $testModel,
                'saved' => $autoSave
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'error' => $testResult['error'] ?? 'API Key validation failed. Please ensure the key was copied correctly.'
            ], 400);
        }
    }

    if (!empty($input['reset_token_usage'])) {
        resetTokenUsage();
        sendJsonResponse([
            'success' => true,
            'message' => 'Token usage statistics reset successfully',
            'token_usage' => getTokenUsageStats()
        ]);
    }

    if (!empty($input['clear_all_memories'])) {
        if ($userId) {
            $stmt = $pdo->prepare("DELETE FROM memories WHERE user_id = :uid");
            $stmt->execute([':uid' => $userId]);
        } else {
            $pdo->exec("DELETE FROM memories");
        }
    }

    // Save or Clear Gemini API Key
    if (!empty($input['clear_gemini_api_key'])) {
        if ($userId) {
            setUserGeminiKey($userId, '');
        }
        if ($isAdmin || empty($userId)) {
            setSetting('gemini_api_key', '');
        }
    } elseif (isset($input['gemini_api_key']) && trim($input['gemini_api_key']) !== '') {
        $newKey = trim($input['gemini_api_key']);
        $newModel = isset($input['model']) ? trim($input['model']) : null;
        if ($userId) {
            setUserGeminiKey($userId, $newKey, $newModel);
        }
        if ($isAdmin || empty($userId)) {
            setSetting('gemini_api_key', $newKey);
        }
    }

    // Model preference
    if (isset($input['model'])) {
        $mVal = trim($input['model']);
        if ($userId) {
            $existingK = getUserGeminiKey($userId) ?: '';
            setUserGeminiKey($userId, $existingK, $mVal);
        }
        if ($isAdmin || empty($userId)) {
            setSetting('model', $mVal);
        }
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
    if (!empty($input['clear_google_client_id'])) {
        setSetting('google_client_id', '');
    } elseif (isset($input['google_client_id'])) {
        setSetting('google_client_id', trim($input['google_client_id']));
    }
    if (!empty($input['clear_google_client_secret'])) {
        setSetting('google_client_secret', '');
    } elseif (isset($input['google_client_secret']) && trim($input['google_client_secret']) !== '') {
        setSetting('google_client_secret', trim($input['google_client_secret']));
    }

    sendJsonResponse(['success' => true, 'message' => 'Settings updated successfully']);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
