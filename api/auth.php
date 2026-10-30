<?php
// api/auth.php - Authentication API for RuXt AI
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Parse JSON payload if sent as application/json
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? [];

if (!empty($input['action'])) {
    $action = $input['action'];
}

// 1. GET STATUS / USER INFO
if ($method === 'GET' && ($action === 'status' || empty($action))) {
    $user = getCurrentUser();
    $totalUsers = countUsers();
    sendJsonResponse([
        'success' => true,
        'logged_in' => ($user !== null),
        'user' => $user,
        'needs_onboarding' => ($user !== null && (empty($user['onboarding_completed']) || intval($user['onboarding_completed']) === 0)),
        'total_users' => $totalUsers
    ]);
}

// 2. LOGOUT
if ($action === 'logout') {
    logoutUser();
    sendJsonResponse([
        'success' => true,
        'message' => 'Logged out successfully.',
        'redirect' => 'login.php'
    ]);
}

// 3. LOGIN
if ($method === 'POST' && ($action === 'login' || empty($action) && isset($input['password']))) {
    $identifier = $input['identifier'] ?? $input['username'] ?? $_POST['identifier'] ?? $_POST['username'] ?? '';
    $password = $input['password'] ?? $_POST['password'] ?? '';
    $remember = !empty($input['remember']) || !empty($_POST['remember']);

    $result = authenticateUser($identifier, $password, $remember);
    if ($result['success']) {
        $redirect = 'index.php';
        if (!empty($_GET['redirect'])) {
            $redirect = filter_var($_GET['redirect'], FILTER_SANITIZE_URL);
        }
        $needsOnboarding = empty($result['user']['onboarding_completed']) || intval($result['user']['onboarding_completed']) === 0;
        $hasGemini = !empty(getEffectiveGeminiApiKey($result['user']['id']));
        sendJsonResponse([
            'success' => true,
            'message' => $result['message'],
            'user' => $result['user'],
            'needs_onboarding' => $needsOnboarding,
            'has_gemini_key' => $hasGemini,
            'redirect' => $redirect
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'error' => $result['error']
        ], 400);
    }
}

// 3.5. GOOGLE SIGN IN
if ($method === 'POST' && ($action === 'google_login' || $action === 'google')) {
    $credential = $input['credential'] ?? $input['id_token'] ?? $_POST['credential'] ?? $_POST['id_token'] ?? '';

    if (empty($credential)) {
        sendJsonResponse([
            'success' => false,
            'error' => 'Missing Google credential token.'
        ], 400);
    }

    $result = authenticateWithGoogle($credential);
    if ($result['success']) {
        $redirect = 'index.php';
        if (!empty($_GET['redirect'])) {
            $redirect = filter_var($_GET['redirect'], FILTER_SANITIZE_URL);
        }
        $needsOnboarding = empty($result['user']['onboarding_completed']) || intval($result['user']['onboarding_completed']) === 0;
        $hasGemini = !empty(getEffectiveGeminiApiKey($result['user']['id']));
        sendJsonResponse([
            'success' => true,
            'message' => $result['message'],
            'user' => $result['user'],
            'needs_onboarding' => $needsOnboarding,
            'has_gemini_key' => $hasGemini,
            'redirect' => $redirect
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'error' => $result['error']
        ], 400);
    }
}

// 4. REGISTER
if ($method === 'POST' && $action === 'register') {
    $name = $input['name'] ?? $_POST['name'] ?? '';
    $username = $input['username'] ?? $_POST['username'] ?? '';
    $email = $input['email'] ?? $_POST['email'] ?? '';
    $password = $input['password'] ?? $_POST['password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? $_POST['confirm_password'] ?? '';

    $result = registerUser($name, $username, $email, $password, $confirmPassword);
    if ($result['success']) {
        $redirect = 'index.php';
        if (!empty($_GET['redirect'])) {
            $redirect = filter_var($_GET['redirect'], FILTER_SANITIZE_URL);
        }
        $hasGemini = !empty(getEffectiveGeminiApiKey($result['user']['id']));
        sendJsonResponse([
            'success' => true,
            'message' => $result['message'],
            'user' => $result['user'],
            'needs_onboarding' => true,
            'has_gemini_key' => $hasGemini,
            'redirect' => $redirect
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'error' => $result['error']
        ], 400);
    }
}

// 5. UPDATE PROFILE
if ($method === 'POST' && $action === 'update_profile') {
    $user = getCurrentUser();
    if (!$user) {
        sendJsonResponse(['success' => false, 'error' => 'You must be logged in to update your profile.'], 401);
    }

    $name = trim($input['name'] ?? $_POST['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? $_POST['email'] ?? ''));
    $currentPassword = $input['current_password'] ?? $_POST['current_password'] ?? '';
    $newPassword = $input['new_password'] ?? $_POST['new_password'] ?? '';

    if (mb_strlen($name) < 2) {
        sendJsonResponse(['success' => false, 'error' => 'Full name must be at least 2 characters long.'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(['success' => false, 'error' => 'Please provide a valid email address.'], 400);
    }

    // Check email uniqueness if changed
    $existing = getUserByUsernameOrEmail($email);
    if ($existing && (int)$existing['id'] !== (int)$user['id']) {
        sendJsonResponse(['success' => false, 'error' => 'This email address is already in use.'], 400);
    }

    $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=173d34&color=fcfaf5&bold=true';

    // If updating password
    if (!empty($newPassword)) {
        if (strlen($newPassword) < 6) {
            sendJsonResponse(['success' => false, 'error' => 'New password must be at least 6 characters.'], 400);
        }

        $chkStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
        $chkStmt->execute([':id' => $user['id']]);
        $row = $chkStmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
            sendJsonResponse(['success' => false, 'error' => 'Incorrect current password. Please verify and try again.'], 400);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email, avatar_url = :avatar, password_hash = :hash WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':avatar' => $avatarUrl,
            ':hash' => $newHash,
            ':id' => $user['id']
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email, avatar_url = :avatar WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':avatar' => $avatarUrl,
            ':id' => $user['id']
        ]);
    }

    setSetting('user_name', $name);
    $_SESSION['user_name'] = $name;

    $updatedUser = getUserById($user['id']);
    unset($updatedUser['password_hash']);

    sendJsonResponse([
        'success' => true,
        'message' => 'Profile updated successfully!',
        'user' => $updatedUser
    ]);
}

// 6. RESET ONBOARDING (Allow re-running role discovery)
if ($action === 'reset_onboarding') {
    $user = getCurrentUser();
    if (!$user) {
        sendJsonResponse(['success' => false, 'error' => 'Authentication required.'], 401);
    }

    resetUserOnboarding($user['id']);

    sendJsonResponse([
        'success' => true,
        'message' => 'Onboarding reset successfully. RuXt will initiate role configuration upon your next consultation.',
        'user' => getUserById($user['id'])
    ]);
}

// Invalid action
sendJsonResponse([
    'success' => false,
    'error' => 'Invalid authentication request.'
], 400);
