<?php
// api/tts.php
require_once __DIR__ . '/../config.php';

// Support both GET (for audio src tags) and POST (for long payloads)
$text = '';
$executiveId = 0;
$voiceId = '';
$gender = '';
$provider = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $text = $input['text'] ?? '';
    $executiveId = intval($input['executive_id'] ?? 0);
    $voiceId = trim($input['voice_id'] ?? '');
    $gender = trim($input['gender'] ?? '');
    $provider = trim($input['provider'] ?? '');
} else {
    $text = $_GET['text'] ?? '';
    $executiveId = intval($_GET['executive_id'] ?? 0);
    $voiceId = trim($_GET['voice_id'] ?? '');
    $gender = trim($_GET['gender'] ?? '');
    $provider = trim($_GET['provider'] ?? '');
}

$text = trim($text);
if (empty($text)) {
    sendJsonResponse(['error' => 'Text to speak is required'], 400);
}

// Lookup executive profile if ID provided
if ($executiveId > 0) {
    $stmt = $pdo->prepare("SELECT name, voice_id, voice_gender FROM executives WHERE id = :id");
    $stmt->execute([':id' => $executiveId]);
    $exec = $stmt->fetch();
    if ($exec) {
        if (empty($voiceId)) $voiceId = $exec['voice_id'];
        if (empty($gender)) $gender = $exec['voice_gender'];
    }
}

if (empty($gender)) $gender = 'female';
if (empty($voiceId)) $voiceId = ($gender === 'male' ? 'fable' : 'nova');

// Determine provider
$configuredProvider = getSetting('tts_provider', 'browser');
$effectiveProvider = !empty($provider) ? $provider : $configuredProvider;

// Text sanitizer: clean markdown, code blocks, links, and formatting
$cleanText = preg_replace('/```[\s\S]*?```/', 'Code snippet omitted.', $text);
$cleanText = preg_replace('/`([^`]+)`/', '$1', $cleanText);
$cleanText = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $cleanText);
$cleanText = preg_replace('/[*#_~>|]/', '', $cleanText);
$cleanText = preg_replace('/\s+/', ' ', $cleanText);
$cleanText = trim($cleanText);

// Truncate to reasonable safe limit for audio generation (max 3000 chars)
if (mb_strlen($cleanText) > 3000) {
    $cleanText = mb_substr($cleanText, 0, 3000) . '...';
}

// Voice ID Mappings for ElevenLabs
$elevenVoiceMap = [
    'nova'    => '21m00Tcm4TlvDq8ikWAM', // Rachel (Warm, clear, executive)
    'shimmer' => 'EXAVITQu4vr4xnSDxMaL', // Bella (Sharp, confident female)
    'alloy'   => 'MF3mGyEYCl7XYWbV9V6O', // Elli (Young, energetic female)
    'fable'   => 'ErXwobaYiN019PkySvjV', // Antoni (Charismatic male)
    'onyx'    => 'VR6AewLTigWG4xSOukaG', // Arnold (Deep, calm, measured male)
    'echo'    => 'pNInz6obpgDQGcFmaJgB', // Adam (Authoritative, narrative male)
];

// Check ElevenLabs
if ($effectiveProvider === 'elevenlabs') {
    $apiKey = getSetting('elevenlabs_api_key', '');
    if (!empty($apiKey)) {
        $elevenVoiceId = $elevenVoiceMap[strtolower($voiceId)] ?? $voiceId;
        // Verify cache
        $cacheHash = md5("elevenlabs_{$elevenVoiceId}_{$cleanText}");
        $cacheFile = __DIR__ . "/../assets/audio_cache/{$cacheHash}.mp3";

        if (file_exists($cacheFile) && filesize($cacheFile) > 0) {
            header('Content-Type: audio/mpeg');
            header('Content-Length: ' . filesize($cacheFile));
            header('Accept-Ranges: bytes');
            header('Cache-Control: public, max-age=86400');
            readfile($cacheFile);
            exit;
        }

        $url = "https://api.elevenlabs.io/v1/text-to-speech/{$elevenVoiceId}";
        $payload = [
            'text' => $cleanText,
            'model_id' => 'eleven_multilingual_v2',
            'voice_settings' => [
                'stability' => 0.65,
                'similarity_boost' => 0.80
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'xi-api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $audio = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && !empty($audio)) {
            @mkdir(dirname($cacheFile), 0777, true);
            file_put_contents($cacheFile, $audio);
            header('Content-Type: audio/mpeg');
            header('Content-Length: ' . strlen($audio));
            header('Accept-Ranges: bytes');
            header('Cache-Control: public, max-age=86400');
            echo $audio;
            exit;
        }
    }
}

// Check OpenAI TTS
if ($effectiveProvider === 'openai') {
    $apiKey = getSetting('openai_api_key', '');
    if (!empty($apiKey)) {
        $openAIVoices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
        $chosenVoice = in_array(strtolower($voiceId), $openAIVoices) 
            ? strtolower($voiceId) 
            : ($gender === 'male' ? 'onyx' : 'nova');

        $cacheHash = md5("openai_{$chosenVoice}_{$cleanText}");
        $cacheFile = __DIR__ . "/../assets/audio_cache/{$cacheHash}.mp3";

        if (file_exists($cacheFile) && filesize($cacheFile) > 0) {
            header('Content-Type: audio/mpeg');
            header('Content-Length: ' . filesize($cacheFile));
            header('Accept-Ranges: bytes');
            header('Cache-Control: public, max-age=86400');
            readfile($cacheFile);
            exit;
        }

        $url = "https://api.openai.com/v1/audio/speech";
        $payload = [
            'model' => 'tts-1',
            'input' => $cleanText,
            'voice' => $chosenVoice
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $audio = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && !empty($audio)) {
            @mkdir(dirname($cacheFile), 0777, true);
            file_put_contents($cacheFile, $audio);
            header('Content-Type: audio/mpeg');
            header('Content-Length: ' . strlen($audio));
            header('Accept-Ranges: bytes');
            header('Cache-Control: public, max-age=86400');
            echo $audio;
            exit;
        }
    }
}

// Client-side high-definition neural fallback response
// Returns clean parameters for browser-side speech synthesis
sendJsonResponse([
    'provider' => 'browser',
    'clean_text' => $cleanText,
    'voice_id' => $voiceId,
    'gender' => $gender,
    'pitch' => ($gender === 'female' ? 1.05 : 0.95),
    'rate' => 1.0
]);
