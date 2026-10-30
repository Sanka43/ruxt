<?php
require_once __DIR__ . '/config.php';

// If already logged in, redirect directly to index.php
if (isLoggedIn()) {
    $redirect = !empty($_GET['redirect']) ? filter_var($_GET['redirect'], FILTER_SANITIZE_URL) : 'index.php';
    header("Location: " . $redirect);
    exit;
}

$totalUsers = countUsers();
$isFirstUser = ($totalUsers === 0);
$defaultTab = (!empty($_GET['tab']) && $_GET['tab'] === 'register') || $isFirstUser ? 'register' : 'login';
$googleClientId = getGoogleClientId();
$hasGoogleAuth = !empty($googleClientId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Sign In & Register · RuXt AI</title>

    <!-- Google Identity Services (GIS) -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <!-- PWA Settings -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#fcfaf5">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">

    <!-- Fonts: DM Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"DM Sans"', 'system-ui', '-apple-system', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace']
                    },
                    colors: {
                        ruxtBg: '#fcfaf5',
                        ruxtPanel: '#f5f2e9',
                        ruxtCard: '#ffffff',
                        ruxtLine: '#ddd8cc',
                        ruxtLineSoft: '#e8e3d9',
                        ruxtText: '#202725',
                        ruxtMuted: '#68716d',
                        ruxtGreen: '#173d34',
                        ruxtGreenHover: '#285c4e',
                        ruxtOchre: '#b7843f',
                        ruxtSage: '#799a87'
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            background-color: #fcfaf5;
            background-image: 
                radial-gradient(circle at 15% 20%, rgba(23, 61, 52, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 85% 80%, rgba(183, 132, 63, 0.05) 0%, transparent 45%);
            min-height: 100dvh;
        }
        .tab-btn.active {
            background-color: #ffffff;
            color: #173d34;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px -1px rgba(0, 0, 0, 0.08);
            font-weight: 700;
        }
        .tab-btn:not(.active) {
            color: #68716d;
            font-weight: 500;
        }
        .tab-btn:not(.active):hover {
            color: #173d34;
        }
    </style>
</head>
<body class="text-[#202725] flex flex-col justify-between p-3 sm:p-6 font-sans antialiased">

    <!-- Top Minimal Brand Bar -->
    <header class="w-full max-w-5xl mx-auto flex items-center justify-between py-2 flex-shrink-0">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center -rotate-3 shadow-md shadow-[#173d34]/20 flex-shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 9h13.5a8.5 8.5 0 0 1 0 17H9z"/>
                    <path d="M9 26l10 7"/>
                    <path d="M17 17h14"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-1.5">
                    <span class="text-base font-extrabold tracking-tight text-[#173d34]">RuXt</span>
                    <span class="text-[9px] bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20 px-1.5 py-0.5 rounded-full font-bold uppercase tracking-wider">AI ASSISTANT</span>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-2 text-xs text-[#68716d]">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="hidden sm:inline font-mono font-medium text-[11px]">System Ready</span>
        </div>
    </header>

    <!-- Main Auth Container -->
    <main class="w-full max-w-md mx-auto my-auto py-6">
        
        <!-- Welcome First-Time Notice (If no users exist yet) -->
        <?php if ($isFirstUser): ?>
        <div class="mb-4 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 shadow-sm flex items-start space-x-3">
            <div class="w-7 h-7 rounded-xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
            </div>
            <div class="text-xs">
                <div class="font-bold text-emerald-900">Welcome to RuXt AI!</div>
                <div class="text-emerald-700 mt-0.5 leading-relaxed">
                    No accounts have been created yet. Register below to establish your master Administrator profile.
                </div>
            </div>
        </div>
        <?php elseif (!empty($_GET['logged_out'])): ?>
        <div class="mb-4 p-3 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-800 flex items-center space-x-2">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-amber-600 flex-shrink-0"></i>
            <span>You have been signed out successfully.</span>
        </div>
        <?php endif; ?>

        <!-- Auth Card -->
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl p-5 sm:p-8 shadow-xl relative overflow-hidden">
            
            <!-- Card Header -->
            <div class="text-center mb-6">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#202725]" id="formTitle">
                    <?= $isFirstUser ? 'Create Admin Account' : 'Welcome to RuXt' ?>
                </h1>
                <p class="text-xs text-[#68716d] mt-1" id="formSubtitle">
                    <?= $isFirstUser ? 'Set up your private enterprise assistant workspace' : 'Sign in to access your personal AI executive boardroom' ?>
                </p>
            </div>

            <!-- Tab Switcher (Sign In vs Register) -->
            <div class="flex items-center p-1 bg-[#f5f2e9] border border-[#ddd8cc] rounded-2xl mb-6">
                <button type="button" id="tabLoginBtn" onclick="switchTab('login')" class="tab-btn flex-1 py-2 rounded-xl text-xs flex items-center justify-center space-x-1.5 transition-all <?= ($defaultTab === 'login') ? 'active' : '' ?>">
                    <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                    <span>Sign In</span>
                </button>
                <button type="button" id="tabRegisterBtn" onclick="switchTab('register')" class="tab-btn flex-1 py-2 rounded-xl text-xs flex items-center justify-center space-x-1.5 transition-all <?= ($defaultTab === 'register') ? 'active' : '' ?>">
                    <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                    <span>Create Account</span>
                    <?php if ($isFirstUser): ?>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-ping"></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Global Error / Success Alert Box -->
            <div id="alertBox" class="hidden mb-4 p-3 rounded-xl text-xs flex items-start space-x-2 transition-all">
                <i id="alertIcon" data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                <span id="alertMsg" class="leading-relaxed"></span>
            </div>

            <!-- ================= FORM 1: SIGN IN ================= -->
            <form id="loginForm" class="<?= ($defaultTab === 'login') ? '' : 'hidden' ?> space-y-4" onsubmit="handleLogin(event)">
                <input type="hidden" name="action" value="login">
                
                <div>
                    <label for="loginIdentifier" class="block text-xs font-semibold text-[#3d4942] mb-1.5">Username or Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#68716d]">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </div>
                        <input type="text" id="loginIdentifier" name="identifier" required autocomplete="username" placeholder="your_username or you@example.com" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] focus:border-[#173d34] rounded-xl pl-9 pr-3 py-2.5 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none transition-all">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="loginPassword" class="block text-xs font-semibold text-[#3d4942]">Password</label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#68716d]">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <input type="password" id="loginPassword" name="password" required autocomplete="current-password" placeholder="••••••••" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] focus:border-[#173d34] rounded-xl pl-9 pr-10 py-2.5 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none transition-all">
                        <button type="button" onclick="togglePasswordVisibility('loginPassword', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#68716d] hover:text-[#173d34] transition-colors" title="Toggle password visibility">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center space-x-2 text-xs text-[#68716d] cursor-pointer">
                        <input type="checkbox" id="loginRemember" name="remember" class="w-4 h-4 rounded border-[#ddd8cc] text-[#173d34] focus:ring-0 cursor-pointer" checked>
                        <span>Remember me for 30 days</span>
                    </label>
                </div>

                <button type="submit" id="loginSubmitBtn" class="w-full py-2.5 px-4 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] font-bold rounded-full text-xs flex items-center justify-center space-x-2 transition-all shadow-md shadow-[#173d34]/20 active:scale-[0.98] cursor-pointer mt-2">
                    <span id="loginBtnText">Sign In</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>

                <div class="text-center pt-2">
                    <p class="text-xs text-[#68716d]">
                        New to RuXt? 
                        <button type="button" onclick="switchTab('register')" class="text-[#173d34] hover:text-[#285c4e] font-bold underline underline-offset-2 ml-1 cursor-pointer">
                            Create an account
                        </button>
                    </p>
                </div>
            </form>

            <!-- ================= FORM 2: REGISTER ================= -->
            <form id="registerForm" class="<?= ($defaultTab === 'register') ? '' : 'hidden' ?> space-y-3.5" onsubmit="handleRegister(event)">
                <input type="hidden" name="action" value="register">

                <div>
                    <label for="regName" class="block text-xs font-semibold text-[#3d4942] mb-1.5">Your Full Name</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#68716d]">
                            <i data-lucide="badge-check" class="w-4 h-4"></i>
                        </div>
                        <input type="text" id="regName" name="name" required placeholder="e.g. Isanka Silva" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] focus:border-[#173d34] rounded-xl pl-9 pr-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label for="regUsername" class="block text-xs font-semibold text-[#3d4942] mb-1.5">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#68716d]">
                                <i data-lucide="at-sign" class="w-4 h-4"></i>
                            </div>
                            <input type="text" id="regUsername" name="username" required pattern="[a-zA-Z0-9_\-]+" placeholder="isanka" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] focus:border-[#173d34] rounded-xl pl-9 pr-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none transition-all">
                        </div>
                    </div>

                    <div>
                        <label for="regEmail" class="block text-xs font-semibold text-[#3d4942] mb-1.5">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#68716d]">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </div>
                            <input type="email" id="regEmail" name="email" required placeholder="name@company.com" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] focus:border-[#173d34] rounded-xl pl-9 pr-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none transition-all">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label for="regPassword" class="block text-xs font-semibold text-[#3d4942] mb-1.5">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#68716d]">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </div>
                            <input type="password" id="regPassword" name="password" required minlength="6" placeholder="Min. 6 characters" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] focus:border-[#173d34] rounded-xl pl-9 pr-9 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none transition-all">
                            <button type="button" onclick="togglePasswordVisibility('regPassword', this)" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-[#68716d] hover:text-[#173d34]" title="Toggle visibility">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="regConfirmPassword" class="block text-xs font-semibold text-[#3d4942] mb-1.5">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#68716d]">
                                <i data-lucide="shield-check" class="w-4 h-4"></i>
                            </div>
                            <input type="password" id="regConfirmPassword" name="confirm_password" required minlength="6" placeholder="Re-enter password" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] focus:border-[#173d34] rounded-xl pl-9 pr-9 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none transition-all">
                            <button type="button" onclick="togglePasswordVisibility('regConfirmPassword', this)" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-[#68716d] hover:text-[#173d34]" title="Toggle visibility">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-1">
                    <p class="text-[11px] text-[#68716d] leading-relaxed">
                        By creating an account, your chats, memory vaults, and AI workspaces will be safely linked to your personal profile.
                    </p>
                </div>

                <button type="submit" id="registerSubmitBtn" class="w-full py-2.5 px-4 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] font-bold rounded-full text-xs flex items-center justify-center space-x-2 transition-all shadow-md shadow-[#173d34]/20 active:scale-[0.98] cursor-pointer mt-2">
                    <span id="regBtnText"><?= $isFirstUser ? 'Create Administrator Account' : 'Create Account & Sign In' ?></span>
                    <i data-lucide="check" class="w-4 h-4"></i>
                </button>

                <div class="text-center pt-2">
                    <p class="text-xs text-[#68716d]">
                        Already have an account? 
                        <button type="button" onclick="switchTab('login')" class="text-[#173d34] hover:text-[#285c4e] font-bold underline underline-offset-2 ml-1 cursor-pointer">
                            Sign in here
                        </button>
                    </p>
                </div>
            </form>

            <!-- ================= GOOGLE SIGN-IN SECTION ================= -->
            <div class="relative my-4 flex items-center justify-center">
                <div class="border-t border-[#ddd8cc] w-full"></div>
                <span class="bg-[#ffffff] px-2.5 text-[11px] font-medium text-[#68716d] uppercase tracking-wider">or</span>
            </div>

            <div class="w-full flex flex-col items-center justify-center space-y-2">
                <?php if ($hasGoogleAuth): ?>
                    <!-- Google Identity Services Container -->
                    <div id="g_id_onload"
                         data-client_id="<?= htmlspecialchars($googleClientId) ?>"
                         data-context="signin"
                         data-ux_mode="popup"
                         data-callback="handleGoogleSignInResponse"
                         data-auto_prompt="false">
                    </div>
                    <div class="g_id_signin w-full flex justify-center"
                         data-type="standard"
                         data-shape="pill"
                         data-theme="outline"
                         data-text="continue_with"
                         data-size="large"
                         data-logo_alignment="left"
                         data-width="320">
                    </div>
                <?php else: ?>
                    <!-- Professional Customer-Facing Google Button (No Developer Popup) -->
                    <button type="button" onclick="handleUnconfiguredGoogleSignIn()" class="w-full py-2.5 px-4 bg-white hover:bg-stone-50 border border-[#ddd8cc] hover:border-[#173d34] text-[#202725] font-semibold rounded-full text-xs flex items-center justify-center space-x-2.5 transition-all shadow-sm active:scale-[0.98] cursor-pointer">
                        <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                        <span>Continue with Google</span>
                    </button>
                <?php endif; ?>
            </div>

        </div>

        <!-- Privacy & Local SQLite Note -->
        <div class="mt-6 text-center text-[11px] text-[#68716d] flex items-center justify-center space-x-1.5">
            <i data-lucide="shield" class="w-3.5 h-3.5 text-[#173d34]"></i>
            <span>100% Private & Self-Hosted • Passwords securely encrypted with PHP BCRYPT</span>
        </div>
    </main>

    <!-- Footer Copyright -->
    <footer class="w-full max-w-5xl mx-auto py-3 text-center text-xs text-[#68716d] flex-shrink-0">
        RuXt AI &copy; <?= date('Y') ?> · Autonomous Multi-Agent Workspace
    </footer>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Switch between Login and Register tabs
        function switchTab(tab) {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const tabLoginBtn = document.getElementById('tabLoginBtn');
            const tabRegisterBtn = document.getElementById('tabRegisterBtn');
            const formTitle = document.getElementById('formTitle');
            const formSubtitle = document.getElementById('formSubtitle');

            hideAlert();

            if (tab === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                tabLoginBtn.classList.add('active');
                tabRegisterBtn.classList.remove('active');
                formTitle.textContent = 'Welcome Back';
                formSubtitle.textContent = 'Sign in to access your personal AI executive boardroom';
                document.getElementById('loginIdentifier').focus();
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                tabLoginBtn.classList.remove('active');
                tabRegisterBtn.classList.add('active');
                formTitle.textContent = 'Create Your Profile';
                formSubtitle.textContent = 'Register an account to secure and personalize your assistant';
                document.getElementById('regName').focus();
            }
        }

        // Toggle password visibility
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = '<i data-lucide="eye-off" class="w-4 h-4"></i>';
            } else {
                input.type = 'password';
                btn.innerHTML = '<i data-lucide="eye" class="w-4 h-4"></i>';
            }
            lucide.createIcons();
        }

        // Show alert message
        function showAlert(msg, isError = true) {
            const box = document.getElementById('alertBox');
            const text = document.getElementById('alertMsg');
            const icon = document.getElementById('alertIcon');

            box.className = isError 
                ? 'mb-4 p-3 rounded-xl text-xs flex items-start space-x-2 bg-rose-50 border border-rose-200 text-rose-800'
                : 'mb-4 p-3 rounded-xl text-xs flex items-start space-x-2 bg-emerald-50 border border-emerald-200 text-emerald-800';

            icon.setAttribute('data-lucide', isError ? 'alert-circle' : 'check-circle-2');
            text.textContent = msg;
            box.classList.remove('hidden');
            lucide.createIcons();
        }

        function hideAlert() {
            document.getElementById('alertBox').classList.add('hidden');
        }

        // Handle Login Submission
        async function handleLogin(e) {
            e.preventDefault();
            hideAlert();

            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('loginSubmitBtn');
            const btnText = document.getElementById('loginBtnText');
            const identifier = document.getElementById('loginIdentifier').value.trim();
            const password = document.getElementById('loginPassword').value;
            const remember = document.getElementById('loginRemember').checked;

            if (!identifier || !password) {
                showAlert('Please fill in both your username/email and password.');
                return;
            }

            // Button loading state
            submitBtn.disabled = true;
            btnText.textContent = 'Signing in...';

            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier, password, remember })
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    showAlert('Sign in successful! Redirecting...', false);
                    submitBtn.classList.remove('bg-[#173d34]', 'hover:bg-[#285c4e]');
                    submitBtn.classList.add('bg-emerald-600');
                    btnText.textContent = 'Success!';

                    // Get redirect target from URL query param if present
                    const urlParams = new URLSearchParams(window.location.search);
                    const target = urlParams.get('redirect') || data.redirect || 'index.php';

                    setTimeout(() => {
                        window.location.href = target;
                    }, 500);
                } else {
                    showAlert(data.error || 'Failed to sign in. Please verify your credentials.');
                    submitBtn.disabled = false;
                    btnText.textContent = 'Sign In';
                }
            } catch (err) {
                console.error('Login error:', err);
                showAlert('Network or server connection failed. Please try again.');
                submitBtn.disabled = false;
                btnText.textContent = 'Sign In';
            }
        }

        // Handle Register Submission
        async function handleRegister(e) {
            e.preventDefault();
            hideAlert();

            const submitBtn = document.getElementById('registerSubmitBtn');
            const btnText = document.getElementById('regBtnText');
            
            const name = document.getElementById('regName').value.trim();
            const username = document.getElementById('regUsername').value.trim();
            const email = document.getElementById('regEmail').value.trim();
            const password = document.getElementById('regPassword').value;
            const confirm_password = document.getElementById('regConfirmPassword').value;

            if (!name || !username || !email || !password || !confirm_password) {
                showAlert('Please complete all registration fields.');
                return;
            }

            if (password !== confirm_password) {
                showAlert('Passwords do not match. Please verify your password entry.');
                return;
            }

            if (password.length < 6) {
                showAlert('Password must contain at least 6 characters.');
                return;
            }

            submitBtn.disabled = true;
            btnText.textContent = 'Creating Account...';

            try {
                const res = await fetch('api/auth.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, username, email, password, confirm_password })
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    showAlert('Account created successfully! Preparing your workspace...', false);
                    submitBtn.classList.remove('bg-[#173d34]', 'hover:bg-[#285c4e]');
                    submitBtn.classList.add('bg-emerald-600');
                    btnText.textContent = 'Welcome aboard!';

                    const urlParams = new URLSearchParams(window.location.search);
                    const target = urlParams.get('redirect') || data.redirect || 'index.php';

                    setTimeout(() => {
                        window.location.href = target;
                    }, 600);
                } else {
                    showAlert(data.error || 'Registration failed. Please check your details.');
                    submitBtn.disabled = false;
                    btnText.textContent = 'Create Account';
                }
            } catch (err) {
                console.error('Registration error:', err);
                showAlert('Network or server connection error. Please try again.');
                submitBtn.disabled = false;
                btnText.textContent = 'Create Account';
            }
        }

        // Handle Google Sign-in click when OAuth is not yet configured (customer-friendly)
        function handleUnconfiguredGoogleSignIn() {
            showAlert('Google Sign-In is temporarily unavailable. Please sign in with your username/email and password.');
        }

        // Handle Google Identity Services Credential Callback
        async function handleGoogleSignInResponse(response) {
            if (!response || !response.credential) {
                showAlert('No Google credential token received.');
                return;
            }

            hideAlert();
            showAlert('Verifying Google credentials with secure backend...', false);

            try {
                const res = await fetch('api/auth.php?action=google_login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ credential: response.credential })
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    showAlert(data.message || 'Signed in with Google successfully! Redirecting...', false);

                    const urlParams = new URLSearchParams(window.location.search);
                    const target = urlParams.get('redirect') || data.redirect || 'index.php';

                    setTimeout(() => {
                        window.location.href = target;
                    }, 500);
                } else {
                    showAlert(data.error || 'Google Sign-In authentication failed.');
                }
            } catch (err) {
                console.error('Google Sign-In Error:', err);
                showAlert('Network or connection error during Google authentication.');
            }
        }
    </script>
</body>
</html>
