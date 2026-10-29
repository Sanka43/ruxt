<?php
// api/upload.php - Secure Chat File & Image Attachment Uploader
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['error' => 'Method not allowed'], 405);
}

$user = requireLogin(true);
$userId = (int)$user['id'];

if (empty($_FILES)) {
    sendJsonResponse(['error' => 'No files uploaded'], 400);
}

$uploadDir = realpath(__DIR__ . '/../uploads/attachments');
if (!$uploadDir || !is_dir($uploadDir)) {
    $created = @mkdir(__DIR__ . '/../uploads/attachments', 0755, true);
    $uploadDir = realpath(__DIR__ . '/../uploads/attachments');
    if (!$uploadDir) {
        sendJsonResponse(['error' => 'Failed to initialize attachments directory'], 500);
    }
}

// Max constraints
$maxFiles = 5;
$maxFileSize = 20 * 1024 * 1024; // 20 MB

// Allowed extensions
$allowedExtensions = [
    // Images
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp',
    // Documents & PDFs
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    // Text & Code
    'txt', 'md', 'markdown', 'csv', 'json', 'xml', 'log',
    'html', 'htm', 'css', 'js', 'ts', 'jsx', 'tsx',
    'php', 'py', 'sql', 'java', 'c', 'cpp', 'h', 'cs',
    'yml', 'yaml', 'env', 'sh', 'bat', 'cmd'
];

$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
$textExtensions = [
    'txt', 'md', 'markdown', 'csv', 'json', 'xml', 'log',
    'html', 'htm', 'css', 'js', 'ts', 'jsx', 'tsx',
    'php', 'py', 'sql', 'java', 'c', 'cpp', 'h', 'cs',
    'yml', 'yaml', 'env', 'sh'
];

function formatBytes($bytes, $precision = 1) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Normalize files array whether sent as files[] or single file
$rawFiles = [];
if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
    $count = count($_FILES['files']['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $rawFiles[] = [
            'name' => $_FILES['files']['name'][$i],
            'type' => $_FILES['files']['type'][$i],
            'tmp_name' => $_FILES['files']['tmp_name'][$i],
            'error' => $_FILES['files']['error'][$i],
            'size' => $_FILES['files']['size'][$i]
        ];
    }
} elseif (isset($_FILES['file'])) {
    if ($_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $rawFiles[] = $_FILES['file'];
    }
} elseif (isset($_FILES['files']) && !is_array($_FILES['files']['name'])) {
    if ($_FILES['files']['error'] !== UPLOAD_ERR_NO_FILE) {
        $rawFiles[] = $_FILES['files'];
    }
}

if (empty($rawFiles)) {
    sendJsonResponse(['error' => 'No files were received'], 400);
}

if (count($rawFiles) > $maxFiles) {
    sendJsonResponse(['error' => "You can upload a maximum of {$maxFiles} files per message."], 400);
}

$uploaded = [];
$errors = [];

foreach ($rawFiles as $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Upload failed for '{$file['name']}' (error code: {$file['error']})";
        continue;
    }

    if ($file['size'] > $maxFileSize) {
        $errors[] = "'{$file['name']}' exceeds the maximum allowed size of 20MB.";
        continue;
    }

    $origName = basename($file['name']);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions)) {
        $errors[] = "File type '.{$ext}' is not supported for security reasons.";
        continue;
    }

    // Determine MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!$mimeType) {
        $mimeType = !empty($file['type']) ? $file['type'] : 'application/octet-stream';
    }

    $isImage = in_array($ext, $imageExtensions) || strpos($mimeType, 'image/') === 0;

    // Secure, collision-free filename
    $uniqueId = bin2hex(random_bytes(8));
    $timestamp = time();
    $storedName = "att_{$userId}_{$timestamp}_{$uniqueId}.{$ext}";
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $errors[] = "Failed to store file '{$origName}'.";
        continue;
    }

    // Text content excerpt for text / code files
    $textContent = null;
    if (in_array($ext, $textExtensions) && $file['size'] <= 100 * 1024) {
        $textContent = @file_get_contents($destination);
    }

    $uploaded[] = [
        'id' => "att_{$uniqueId}",
        'original_name' => $origName,
        'stored_name' => $storedName,
        'file_path' => "uploads/attachments/{$storedName}",
        'url' => "uploads/attachments/{$storedName}",
        'mime_type' => $mimeType,
        'extension' => $ext,
        'size' => (int)$file['size'],
        'size_formatted' => formatBytes($file['size']),
        'is_image' => $isImage,
        'has_text' => ($textContent !== null),
        'text_preview' => ($textContent !== null) ? mb_substr($textContent, 0, 500) : null
    ];
}

if (empty($uploaded)) {
    sendJsonResponse([
        'error' => !empty($errors) ? implode(' ', $errors) : 'No files could be uploaded.'
    ], 400);
}

sendJsonResponse([
    'success' => true,
    'files' => $uploaded,
    'warnings' => !empty($errors) ? $errors : null
]);
