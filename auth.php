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

// Check if current user has administrator role
function isAdmin(): bool {
    $user = getCurrentUser();
    return ($user && !empty($user['role']) && $user['role'] === 'admin');
}

// Require admin privileges; redirect or return 403
function requireAdmin(bool $isApi = false): ?array {
    $user = requireLogin($isApi);

    if (empty($user['role']) || $user['role'] !== 'admin') {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isJsonRequest = $isApi || (strpos($uri, '/api/') !== false) || (strpos($accept, 'application/json') !== false);

        if ($isJsonRequest) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Admin privileges required to access this resource.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header("Location: index.php?error=access_denied");
        exit;
    }

    return $user;
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

/**
 * Retrieve Google OAuth Client ID from environment or database settings
 */
function getGoogleClientId(): string {
    if (defined('GOOGLE_CLIENT_ID') && !empty(GOOGLE_CLIENT_ID)) {
        return trim((string)GOOGLE_CLIENT_ID);
    }
    $envVal = getenv('GOOGLE_CLIENT_ID');
    if (!empty($envVal)) {
        return trim((string)$envVal);
    }
    if (isset($_ENV['GOOGLE_CLIENT_ID']) && !empty($_ENV['GOOGLE_CLIENT_ID'])) {
        return trim((string)$_ENV['GOOGLE_CLIENT_ID']);
    }
    if (function_exists('getSetting')) {
        return trim((string)getSetting('google_client_id', ''));
    }
    return '';
}

/**
 * Retrieve Google OAuth Client Secret from environment or database settings
 */
function getGoogleClientSecret(): string {
    if (defined('GOOGLE_CLIENT_SECRET') && !empty(GOOGLE_CLIENT_SECRET)) {
        return trim((string)GOOGLE_CLIENT_SECRET);
    }
    $envVal = getenv('GOOGLE_CLIENT_SECRET');
    if (!empty($envVal)) {
        return trim((string)$envVal);
    }
    if (isset($_ENV['GOOGLE_CLIENT_SECRET']) && !empty($_ENV['GOOGLE_CLIENT_SECRET'])) {
        return trim((string)$_ENV['GOOGLE_CLIENT_SECRET']);
    }
    if (function_exists('getSetting')) {
        return trim((string)getSetting('google_client_secret', ''));
    }
    return '';
}

/**
 * Authenticate or auto-register a user via Google Identity Services (GIS) ID Token
 */
function authenticateWithGoogle(string $idToken): array {
    global $pdo;

    $idToken = trim($idToken);
    if (empty($idToken)) {
        return ['success' => false, 'error' => 'Google authentication token is missing.'];
    }

    $configuredClientId = getGoogleClientId();

    // Verify ID Token with Google's public tokeninfo endpoint
    $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
    $tokenData = null;

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Prevent Windows/XAMPP CA bundle issues
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $tokenData = json_decode($response, true);
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 12,
                'ignore_errors' => true
            ]
        ]);
        $response = @file_get_contents($verifyUrl, false, $ctx);
        if ($response) {
            $decoded = json_decode($response, true);
            if (!empty($decoded['sub'])) {
                $tokenData = $decoded;
            }
        }
    }

    if (!$tokenData || empty($tokenData['sub'])) {
        return ['success' => false, 'error' => 'Google token verification failed. The token may be expired or invalid.'];
    }

    // Validate token issuer
    $validIssuers = ['accounts.google.com', 'https://accounts.google.com'];
    if (empty($tokenData['iss']) || !in_array($tokenData['iss'], $validIssuers, true)) {
        return ['success' => false, 'error' => 'Invalid token issuer.'];
    }

    // Validate audience if client ID is configured
    if (!empty($configuredClientId) && !empty($tokenData['aud'])) {
        if ($tokenData['aud'] !== $configuredClientId) {
            return ['success' => false, 'error' => 'Google Client ID mismatch. This token was not issued for this application.'];
        }
    }

    $googleId = (string)$tokenData['sub'];
    $email = !empty($tokenData['email']) ? strtolower(trim($tokenData['email'])) : '';
    $name = !empty($tokenData['name']) ? trim($tokenData['name']) : '';
    $avatar = !empty($tokenData['picture']) ? trim($tokenData['picture']) : null;
    $emailVerified = !empty($tokenData['email_verified']) && (
        $tokenData['email_verified'] === true || 
        $tokenData['email_verified'] === 'true' || 
        $tokenData['email_verified'] === 1 || 
        $tokenData['email_verified'] === '1'
    );

    if (empty($email) || !$emailVerified) {
        return ['success' => false, 'error' => 'Your Google account email address must be verified.'];
    }

    if (empty($name)) {
        $emailParts = explode('@', $email);
        $name = ucfirst($emailParts[0]);
    }

    // 1. Check if user already exists by google_id
    $user = getUserByGoogleId($googleId);

    // 2. If not found by google_id, check by email
    if (!$user) {
        $user = getUserByUsernameOrEmail($email);
        if ($user) {
            // Link existing account to Google
            linkUserGoogleId($user['id'], $googleId, $avatar);
            $user = getUserById($user['id']);
        }
    }

    // 3. If user does not exist at all, auto-register new account
    if (!$user) {
        $totalUsers = countUsers();
        $role = ($totalUsers === 0) ? 'admin' : 'user';

        // Derive valid unique username from email
        $cleanEmailPrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '', explode('@', $email)[0]);
        if (strlen($cleanEmailPrefix) < 3) {
            $cleanEmailPrefix = 'user_' . substr(md5($email), 0, 6);
        }
        $username = substr($cleanEmailPrefix, 0, 25);
        $counter = 1;
        while (getUserByUsernameOrEmail($username) !== null) {
            $username = substr($cleanEmailPrefix, 0, 20) . '_' . $counter;
            $counter++;
        }

        // Generate unguessable random password hash
        $randomPassword = bin2hex(random_bytes(32));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);

        // Fallback avatar if Google did not supply picture
        if (empty($avatar)) {
            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=173d34&color=fcfaf5&bold=true';
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (name, username, email, password_hash, avatar_url, role, google_id)
                VALUES (:name, :username, :email, :hash, :avatar, :role, :gid)
            ");
            $stmt->execute([
                ':name' => $name,
                ':username' => $username,
                ':email' => $email,
                ':hash' => $passwordHash,
                ':avatar' => $avatar,
                ':role' => $role,
                ':gid' => $googleId
            ]);

            $newUserId = (int)$pdo->lastInsertId();

            // Initial user setup
            if ($totalUsers === 0) {
                setSetting('user_name', $name);
                $pdo->exec("UPDATE sessions SET user_id = $newUserId WHERE user_id IS NULL");
                $pdo->exec("UPDATE notes SET user_id = $newUserId WHERE user_id IS NULL");
                $pdo->exec("UPDATE memories SET user_id = $newUserId WHERE user_id IS NULL");
                $pdo->exec("UPDATE ventures SET user_id = $newUserId WHERE user_id IS NULL");
                $pdo->exec("UPDATE projects SET user_id = $newUserId WHERE user_id IS NULL");
            } else {
                // Provision clean workspace & primary project
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
        } catch (\Throwable $e) {
            error_log("Failed to create Google user: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error while creating user profile: ' . $e->getMessage()];
        }
    } else {
        // If user already existed, update avatar if empty
        if ((empty($user['avatar_url']) || strpos($user['avatar_url'], 'ui-avatars.com') !== false) && !empty($avatar)) {
            $stmt = $pdo->prepare("UPDATE users SET avatar_url = :avatar WHERE id = :id");
            $stmt->execute([':avatar' => $avatar, ':id' => (int)$user['id']]);
            $user['avatar_url'] = $avatar;
        }
    }

    // Log the user in
    loginUser($user, true);

    $cleanUser = getUserById($user['id']);
    unset($cleanUser['password_hash']);

    return [
        'success' => true,
        'message' => 'Signed in with Google successfully! Welcome, ' . htmlspecialchars($cleanUser['name']) . '.',
        'user' => $cleanUser
    ];
}
