<?php
// config.php
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/auth.php';

// Automatically ensure secure session is active
startSecureSession();

// Helper for sending standard JSON responses
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Function to call Gemini API with smart fallback
function callGeminiApi($messages, $systemInstruction = null, $feature = 'chat') {
    $apiKey = getSetting('gemini_api_key');
    if (empty($apiKey)) {
        return ['error' => 'Gemini API Key is not configured. Please add your API key in Settings.'];
    }

    $preferredModel = getSetting('model', 'gemini-3.1-flash-lite');

    // List of active candidate models in priority order with resilient fallback
    $candidateModels = array_filter(array_unique([
        $preferredModel,
        'gemini-3.8-flash',
        'gemini-3-flash-preview',
        'gemini-3.1-flash-lite',
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-1.5-pro'
    ]));

    // Format Gemini payload with full multimodal attachment support
    $contents = [];
    $totalMessages = count($messages);
    $msgIndex = 0;

    foreach ($messages as $msg) {
        $msgIndex++;
        $role = ($msg['role'] === 'user') ? 'user' : 'model';
        $parts = [];

        // Check for attachments
        $rawAttachments = $msg['attachments'] ?? null;
        $attachments = [];
        if (is_string($rawAttachments) && !empty($rawAttachments)) {
            $decoded = json_decode($rawAttachments, true);
            if (is_array($decoded)) $attachments = $decoded;
        } elseif (is_array($rawAttachments)) {
            $attachments = $rawAttachments;
        }

        // Include multimodal inline data for the most recent 4 messages, text reference for older
        $isRecent = ($totalMessages - $msgIndex) < 4;

        if (!empty($attachments)) {
            foreach ($attachments as $att) {
                $filePath = !empty($att['file_path']) ? $att['file_path'] : (!empty($att['url']) ? $att['url'] : null);
                $fullPath = $filePath ? realpath(__DIR__ . '/' . ltrim($filePath, '/\\')) : null;
                $origName = $att['original_name'] ?? 'attachment';
                $mimeType = $att['mime_type'] ?? 'application/octet-stream';
                $isImage = !empty($att['is_image']) || strpos($mimeType, 'image/') === 0;
                $isPdf = ($mimeType === 'application/pdf') || (strtolower($att['extension'] ?? '') === 'pdf');

                if ($fullPath && file_exists($fullPath)) {
                    $fileSize = filesize($fullPath);

                    if ($isRecent && $isImage && $fileSize <= 15 * 1024 * 1024) {
                        // Multimodal image inline data
                        $rawBytes = file_get_contents($fullPath);
                        if ($rawBytes !== false) {
                            $parts[] = [
                                'inlineData' => [
                                    'mimeType' => $mimeType,
                                    'data' => base64_encode($rawBytes)
                                ]
                            ];
                        }
                    } elseif ($isRecent && $isPdf && $fileSize <= 20 * 1024 * 1024) {
                        // Multimodal PDF inline data
                        $rawBytes = file_get_contents($fullPath);
                        if ($rawBytes !== false) {
                            $parts[] = [
                                'inlineData' => [
                                    'mimeType' => 'application/pdf',
                                    'data' => base64_encode($rawBytes)
                                ]
                            ];
                        }
                    } elseif ($fileSize <= 200 * 1024) {
                        // Text / code document: inject text content
                        $rawText = file_get_contents($fullPath);
                        if ($rawText !== false) {
                            $parts[] = [
                                'text' => "--- ATTACHED REFERENCE FILE: {$origName} ---\n{$rawText}\n--- END OF REFERENCE FILE ---"
                            ];
                        }
                    } else {
                        // Summary note for larger or older non-image file
                        $parts[] = [
                            'text' => "[Attached Reference Document: {$origName} ({$mimeType})]"
                        ];
                    }
                } else {
                    // File record exists but physical file might be missing or remote
                    $parts[] = [
                        'text' => "[Attached Reference: {$origName}]"
                    ];
                }
            }
        }

        // Add user or model text content
        $text = trim($msg['content'] ?? '');
        if (!empty($text)) {
            $parts[] = ['text' => $text];
        } elseif (empty($parts)) {
            $parts[] = ['text' => '(No content)'];
        } elseif (empty($text) && !empty($attachments) && $role === 'user') {
            $parts[] = ['text' => 'Please thoroughly analyze the attached reference file(s) and provide your executive assessment, observations, and recommendations.'];
        }

        $contents[] = [
            'role' => $role,
            'parts' => $parts
        ];
    }

    $payload = [
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'topP' => 0.95,
            'maxOutputTokens' => 8192,
        ]
    ];

    if (!empty($systemInstruction)) {
        $payload['systemInstruction'] = [
            'parts' => [
                ['text' => $systemInstruction]
            ]
        ];
    }

    $lastError = 'Unknown error';

    // Try candidate models with automatic fallback
    foreach ($candidateModels as $model) {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Prevent Windows/XAMPP CA bundle issues

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            $lastError = 'cURL Error: ' . $curlError;
            continue;
        }

        $responseData = json_decode($response, true);

        if ($httpCode === 200 && isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            // If the model had to fall back, update current setting so future calls are direct
            if ($model !== $preferredModel) {
                setSetting('model', $model);
            }

            $usage = $responseData['usageMetadata'] ?? [];
            $promptTokens = intval($usage['promptTokenCount'] ?? 0);
            $completionTokens = intval($usage['candidatesTokenCount'] ?? 0);
            $thoughtsTokens = intval($usage['thoughtsTokenCount'] ?? 0);
            $totalTokens = intval($usage['totalTokenCount'] ?? ($promptTokens + $completionTokens + $thoughtsTokens));

            // Record token usage automatically
            if (function_exists('recordTokenUsage')) {
                recordTokenUsage($model, $promptTokens, $completionTokens, $totalTokens, $feature, $thoughtsTokens);
            }

            return [
                'success' => true,
                'reply' => $responseData['candidates'][0]['content']['parts'][0]['text'],
                'model' => $model,
                'usage' => [
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'thoughts_tokens' => $thoughtsTokens,
                    'total_tokens' => $totalTokens
                ]
            ];
        }

        $lastError = $responseData['error']['message'] ?? ("API Error with HTTP status " . $httpCode);
    }

    return ['error' => $lastError];
}
