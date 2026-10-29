<?php
// auth.php - Authentication and Session Management for RuXt AI

require_once __DIR__ . '/database/db.php';

// Initialize session with secure configuration
function startSecureSession() {
    static $isHandlingAutoLogin = false;

    if (session_status() === PHP_SESSION_NONE) {
        // Enforce secure cookie attributes
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        
        session_set_cookie_params([
            'lifetime' => 0, // Until browser closes (or extended via remember me)
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();
    }

    // Auto-login via remember-me cookie if not logged in (prevent recursive re-entry)
    if (!$isHandlingAutoLogin && !isset($_SESSION['user_id']) && !empty($_COOKIE['ruxt_remember_token'])) {
        $isHandlingAutoLogin = true;
        try {
            $user = getUserByRememberToken($_COOKIE['ruxt_remember_token']);
            if ($user) {
                loginUser($user, false);
            } else {
                // Invalidate bad cookie
                $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
                setcookie('ruxt_remember_token', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                unset($_COOKIE['ruxt_remember_token']);
            }
        } finally {
            $isHandlingAutoLogin = false;
        }
    }
}

// Check if current visitor is authenticated
function isLoggedIn(): bool {
    startSecureSession();
    return !empty($_SESSION['user_id']);
}

// Retrieve current logged in user details
function getCurrentUser(): ?array {
    startSecureSession();

    if (!empty($_SESSION['user_id'])) {
        $user = getUserById($_SESSION['user_id']);
        if ($user) {
            unset($user['password_hash']);
            return $user;
        }
        logoutUser();
        return null;
    }

    return null;
}

// Retrieve current logged in user ID directly
function getCurrentUserId(): ?int {
    $user = getCurrentUser();
    return $user ? (int)$user['id'] : null;
}

// Require login; redirect or return 401
function requireLogin(bool $isApi = false): ?array {
    startSecureSession();
    $user = getCurrentUser();

    if ($user) {
        return $user;
    }

    // Determine if this is an API call
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isJsonRequest = $isApi || (strpos($uri, '/api/') !== false) || (strpos($accept, 'application/json') !== false);

    if ($isJsonRequest) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required. Please sign in.',
            'require_login' => true
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Web request: redirect to login
    $redirect = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
    header("Location: login.php?redirect=" . $redirect);
    exit;
}

// Log in user
function loginUser(array $user, bool $remember = false): void {
    if (session_status() === PHP_SESSION_NONE) {
        startSecureSession();
    }

    // Set user session state immediately
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'] ?? 'user';
    $_SESSION['just_logged_in'] = true;
    
    // Mitigate session fixation
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        session_regenerate_id(true);
    }

    updateUserLastLogin($user['id']);

    if ($remember && !headers_sent()) {
        $token = bin2hex(random_bytes(32));
        updateUserRememberToken($user['id'], $token);

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        setcookie('ruxt_remember_token', $token, [
            'expires' => time() + (30 * 86400), // 30 days
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        $_COOKIE['ruxt_remember_token'] = $token;
    }
}

// Log out user
function logoutUser(): void {
    if (session_status() === PHP_SESSION_NONE) {
        startSecureSession();
    }

    if (!empty($_SESSION['user_id'])) {
        updateUserRememberToken($_SESSION['user_id'], null);
    }

    $_SESSION = [];

    if (isset($_COOKIE['ruxt_remember_token'])) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        setcookie('ruxt_remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        unset($_COOKIE['ruxt_remember_token']);
    }

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

// Register a new user
function registerUser(string $name, string $username, string $email, string $password, string $confirmPassword): array {
    global $pdo;

    $name = trim($name);
    $username = trim($username);
    $email = strtolower(trim($email));

    // Validations
    if (mb_strlen($name) < 2) {
        return ['success' => false, 'error' => 'Full name must be at least 2 characters long.'];
    }

    if (mb_strlen($username) < 3 || mb_strlen($username) > 30) {
        return ['success' => false, 'error' => 'Username must be between 3 and 30 characters.'];
    }

    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
        return ['success' => false, 'error' => 'Username can only contain letters, numbers, underscores, and hyphens.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please provide a valid email address.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'error' => 'Password must be at least 6 characters long.'];
    }

    if ($password !== $confirmPassword) {
        return ['success' => false, 'error' => 'Passwords do not match.'];
    }

    // Check for existing username or email
    $existing = getUserByUsernameOrEmail($username);
    if ($existing && strtolower($existing['username']) === strtolower($username)) {
        return ['success' => false, 'error' => 'This username is already taken. Please choose another.'];
    }

    $existingEmail = getUserByUsernameOrEmail($email);
    if ($existingEmail && strtolower($existingEmail['email']) === $email) {
        return ['success' => false, 'error' => 'An account with this email address already exists.'];
    }

    // First user is granted admin privileges
    $totalUsers = countUsers();
    $role = ($totalUsers === 0) ? 'admin' : 'user';

    // Password Hash
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Initial avatar using modern UI Avatar service
    $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=173d34&color=fcfaf5&bold=true';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, username, email, password_hash, avatar_url, role)
            VALUES (:name, :username, :email, :hash, :avatar, :role)
        ");
        $stmt->execute([
            ':name' => $name,
            ':username' => $username,
            ':email' => $email,
            ':hash' => $passwordHash,
            ':avatar' => $avatarUrl,
            ':role' => $role
        ]);

        $newUserId = (int)$pdo->lastInsertId();

        // If this is the initial user, synchronize the system user_name setting
        if ($totalUsers === 0) {
            setSetting('user_name', $name);
            
            // Associate any legacy unassigned records with this first user
            $pdo->exec("UPDATE sessions SET user_id = $newUserId WHERE user_id IS NULL");
            $pdo->exec("UPDATE notes SET user_id = $newUserId WHERE user_id IS NULL");
            $pdo->exec("UPDATE memories SET user_id = $newUserId WHERE user_id IS NULL");
            $pdo->exec("UPDATE ventures SET user_id = $newUserId WHERE user_id IS NULL");
            $pdo->exec("UPDATE projects SET user_id = $newUserId WHERE user_id IS NULL");
        } else {
            // Provision dedicated clean workspace & primary project for this new user
            $insVenture = $pdo->prepare("
                INSERT INTO ventures (name, domain, description, icon, color, user_id) 
                VALUES ('Primary Workspace', 'General Operations', 'Main workspace for private AI assistance, strategic planning, and operational execution.', 'briefcase', 'indigo', :uid)
            ");
            $insVenture->execute([':uid' => $newUserId]);
            $userVentureId = (int)$pdo->lastInsertId();

            $insProj = $pdo->prepare("
                INSERT INTO projects (venture_id, name, description, color, icon, status, user_id) 
                VALUES (:vid, 'General Operations', 'Primary workspace roadmap and execution deliverables', 'indigo', 'folder-kanban', 'active', :uid)
            ");
            $insProj->execute([':vid' => $userVentureId, ':uid' => $newUserId]);
        }

        $user = getUserById($newUserId);
        
        // Auto-login upon registration
        loginUser($user, true);

        return [
            'success' => true,
            'message' => 'Registration successful! Welcome to RuXt AI.',
            'user' => $user
        ];
    } catch (\Throwable $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()];
    }
}

// Authenticate user via username/email + password
function authenticateUser(string $identifier, string $password, bool $remember = false): array {
    $identifier = trim($identifier);
    if (empty($identifier) || empty($password)) {
        return ['success' => false, 'error' => 'Please enter your username/email and password.'];
    }

    $user = getUserByUsernameOrEmail($identifier);
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid username/email or password.'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid username/email or password.'];
    }

    loginUser($user, $remember);

    // Refresh user info
    $cleanUser = getUserById($user['id']);
    unset($cleanUser['password_hash']);

    return [
        'success' => true,
        'message' => 'Welcome back, ' . htmlspecialchars($cleanUser['name']) . '!',
        'user' => $cleanUser
    ];
}
