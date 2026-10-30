<?php
// dashboard.php - Management Dashboard for RuXt AI (Workspaces, Users, and System Diagnostics)
require_once __DIR__ . '/config.php';
$currentUser = requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>RuXt AI · Management Dashboard</title>

    <!-- PWA / Favicon -->
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
            darkMode: 'class',
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
        /* Custom scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f5f2e9; }
        ::-webkit-scrollbar-thumb { background: #ddd8cc; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #b7843f; }
        
        .tab-btn.active {
            background-color: #173d34;
            color: #fcfaf5;
            font-weight: 700;
        }
        .tab-btn.active i {
            color: #fcfaf5;
        }
    </style>
</head>
<body class="bg-[#fcfaf5] text-[#202725] font-sans h-screen h-[100dvh] w-screen overflow-hidden flex select-none">

    <!-- Mobile Drawer Overlay -->
    <div id="mobileDrawerOverlay" class="fixed inset-0 bg-[#202725]/50 backdrop-blur-sm z-30 hidden md:hidden" onclick="toggleMobileSidebar()"></div>

    <!-- Left Navigation Sidebar -->
    <aside id="dashboardSidebar" class="fixed md:static inset-y-0 left-0 z-40 w-72 bg-[#f5f2e9] border-r border-[#ddd8cc] flex flex-col justify-between -translate-x-full md:translate-x-0 transition-transform duration-200 ease-in-out h-full">
        
        <!-- Sidebar Header -->
        <div class="p-4 border-b border-[#ddd8cc] bg-[#f0ede4]">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center -rotate-3 shadow-md shadow-[#173d34]/20">
                        <svg class="w-5 h-5" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 9h13.5a8.5 8.5 0 0 1 0 17H9z"/>
                            <path d="M9 26l10 7"/>
                            <path d="M17 17h14"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-base font-extrabold tracking-tight text-[#173d34]">RuXt</span>
                            <span class="text-[9px] bg-amber-500/15 text-amber-900 border border-amber-500/30 px-1.5 py-0.5 rounded-full font-mono font-bold uppercase tracking-wider">ADMIN CONSOLE</span>
                        </div>
                        <p class="text-[11px] text-[#68716d] font-medium">Platform Management</p>
                    </div>
                </div>
                <button onclick="toggleMobileSidebar()" class="md:hidden text-[#68716d] hover:text-[#173d34] p-1.5 rounded-xl hover:bg-[#e8e3d9]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Quick Back to Assistant button -->
            <a href="index.php" class="w-full py-2 px-3 bg-[#ffffff] hover:bg-[#fffefa] text-[#173d34] border border-[#ddd8cc] hover:border-[#173d34]/40 font-bold rounded-xl text-xs flex items-center justify-between transition-all shadow-sm group">
                <span class="flex items-center space-x-2">
                    <i data-lucide="arrow-left" class="w-4 h-4 text-[#68716d] group-hover:text-[#173d34] transition-colors"></i>
                    <span>Back to RuXt AI</span>
                </span>
                <span class="text-[10px] text-[#68716d] font-mono group-hover:text-[#173d34]">App &rarr;</span>
            </a>
        </div>

        <!-- Sidebar Navigation Links -->
        <div class="flex-1 overflow-y-auto p-3 space-y-1">
            <div class="px-2 mb-2 text-[10px] font-bold uppercase tracking-wider text-[#68716d] font-mono">Management Views</div>

            <!-- 1. Overview -->
            <button onclick="switchView('overview')" id="navBtn-overview" class="tab-btn active w-full flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] transition-all">
                <span class="flex items-center space-x-2.5">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 text-[#68716d]"></i>
                    <span>Overview & Stats</span>
                </span>
                <span id="badge-overview" class="text-[10px] bg-white/20 px-1.5 py-0.5 rounded-md font-mono hidden">Live</span>
            </button>

            <!-- 2. Workspaces -->
            <button onclick="switchView('workspaces')" id="navBtn-workspaces" class="tab-btn w-full flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] transition-all">
                <span class="flex items-center space-x-2.5">
                    <i data-lucide="layers" class="w-4 h-4 text-[#68716d]"></i>
                    <span>Workspaces</span>
                </span>
                <span id="badge-workspaces-count" class="text-[10px] bg-[#173d34]/10 text-[#173d34] font-mono font-bold px-1.5 py-0.5 rounded-full border border-[#173d34]/20">0</span>
            </button>

            <!-- 3. Users -->
            <button onclick="switchView('users')" id="navBtn-users" class="tab-btn w-full flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] transition-all">
                <span class="flex items-center space-x-2.5">
                    <i data-lucide="users" class="w-4 h-4 text-[#68716d]"></i>
                    <span>User Management</span>
                </span>
                <span id="badge-users-count" class="text-[10px] bg-[#173d34]/10 text-[#173d34] font-mono font-bold px-1.5 py-0.5 rounded-full border border-[#173d34]/20">0</span>
            </button>

            <!-- 4. AI Engine & System -->
            <button onclick="switchView('system')" id="navBtn-system" class="tab-btn w-full flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] transition-all">
                <span class="flex items-center space-x-2.5">
                    <i data-lucide="cpu" class="w-4 h-4 text-[#68716d]"></i>
                    <span>AI Engine & System</span>
                </span>
                <span id="badge-ai-model" class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded-md font-mono font-bold">Online</span>
            </button>
        </div>

        <!-- Sidebar Footer: Admin Profile -->
        <div class="p-3 border-t border-[#ddd8cc] bg-[#f0ede4]">
            <div class="flex items-center justify-between p-2 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center font-bold text-xs flex-shrink-0">
                        <?= htmlspecialchars(strtoupper(substr($currentUser['name'] ?? 'A', 0, 1))) ?>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-[#202725] truncate"><?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?></div>
                        <div class="text-[10px] text-emerald-700 font-mono font-bold uppercase">Administrator</div>
                    </div>
                </div>
                <a href="logout.php" class="w-7 h-7 rounded-lg hover:bg-[#fff1f2] text-[#68716d] hover:text-[#e11d48] flex items-center justify-center transition-colors" title="Log Out">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Viewport Area -->
    <main class="flex-1 flex flex-col h-full bg-[#fcfaf5] relative min-w-0 overflow-hidden">
        
        <!-- Top Action Header -->
        <header class="h-16 border-b border-[#ddd8cc] bg-[#ffffff]/80 backdrop-blur-md px-4 sm:px-6 flex items-center justify-between z-10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <button onclick="toggleMobileSidebar()" class="md:hidden text-[#68716d] hover:text-[#173d34] p-2 rounded-xl hover:bg-[#ede8dc]">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div>
                    <h1 id="viewHeaderTitle" class="text-base sm:text-lg font-extrabold text-[#202725]">Overview & Metrics</h1>
                    <p id="viewHeaderSubtitle" class="text-xs text-[#68716d] hidden sm:block">Real-time health, user analytics, and workspace telemetry</p>
                </div>
            </div>

            <!-- Dynamic Header Actions -->
            <div class="flex items-center space-x-1.5 sm:space-x-2 flex-shrink-0" id="headerActionButtons">
                <button onclick="refreshCurrentView()" class="px-2.5 sm:px-3 py-1.5 text-xs font-semibold text-[#68716d] hover:text-[#173d34] bg-[#f5f2e9] hover:bg-[#ede8dc] border border-[#ddd8cc] rounded-xl flex items-center space-x-1.5 transition-all shadow-sm">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    <span class="hidden sm:inline">Refresh</span>
                </button>
                <button onclick="openNewWorkspaceModal()" class="px-2.5 sm:px-3.5 py-1.5 text-xs font-bold text-[#fcfaf5] bg-[#173d34] hover:bg-[#285c4e] rounded-xl flex items-center space-x-1 sm:space-x-1.5 transition-all shadow-sm">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span class="hidden sm:inline">New Workspace</span>
                    <span class="sm:hidden">New</span>
                </button>
                <button onclick="openNewUserModal()" class="px-2.5 sm:px-3.5 py-1.5 text-xs font-bold text-[#173d34] bg-white hover:bg-[#fcfaf5] border border-[#ddd8cc] hover:border-[#173d34] rounded-xl flex items-center space-x-1 sm:space-x-1.5 transition-all shadow-sm">
                    <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                    <span class="hidden sm:inline">Add User</span>
                </button>
            </div>
        </header>

        <!-- Main Body Scroll Container -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">

            <!-- ======================================================== -->
            <!-- 1. OVERVIEW VIEW                                         -->
            <!-- ======================================================== -->
            <section id="view-overview" class="space-y-6">
                <!-- 4 Top KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <!-- KPI 1: Total Users -->
                    <div class="p-4 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm relative overflow-hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-[#68716d]">Total Users</span>
                            <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <i data-lucide="users" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-[#202725] font-mono" id="stat-total-users">-</div>
                        <div class="mt-2 flex items-center space-x-2 text-[11px] text-[#68716d]">
                            <span class="font-bold text-emerald-700" id="stat-admin-users">- Admins</span>
                            <span>&bull;</span>
                            <span id="stat-reg-users">- Standard</span>
                        </div>
                    </div>

                    <!-- KPI 2: Total Workspaces -->
                    <div class="p-4 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm relative overflow-hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-[#68716d]">Workspaces</span>
                            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                <i data-lucide="layers" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-[#202725] font-mono" id="stat-total-workspaces">-</div>
                        <div class="mt-2 text-[11px] text-[#68716d] flex items-center space-x-1">
                            <span>Active Ventures across users</span>
                        </div>
                    </div>

                    <!-- KPI 3: AI Executives -->
                    <div class="p-4 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm relative overflow-hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-[#68716d]">Executive AI Agents</span>
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <i data-lucide="bot" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-[#202725] font-mono" id="stat-total-executives">-</div>
                        <div class="mt-2 text-[11px] text-[#68716d]">Specialized Dream Team Members</div>
                    </div>

                    <!-- KPI 4: Tasks & Roadmap -->
                    <div class="p-4 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm relative overflow-hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-[#68716d]">Deliverables & Tasks</span>
                            <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-[#202725] font-mono" id="stat-total-tasks">-</div>
                        <div class="mt-2 flex items-center space-x-2 text-[11px] text-[#68716d]">
                            <span class="font-bold text-emerald-700" id="stat-completed-tasks">- Done</span>
                            <span>&bull;</span>
                            <span id="stat-pending-tasks">- Pending</span>
                        </div>
                    </div>
                </div>

                <!-- Secondary Intelligence Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
                    <!-- Conversations -->
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-[#ffffff] to-[#f5f2e9] border border-[#ddd8cc] shadow-sm flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-[#68716d] uppercase tracking-wider font-mono">Consultations</div>
                            <div class="text-xl font-extrabold text-[#173d34] font-mono mt-1" id="stat-total-sessions">- Sessions</div>
                            <div class="text-[11px] text-[#68716d] mt-0.5" id="stat-total-messages">- messages exchanged</div>
                        </div>
                        <div class="w-10 h-10 rounded-2xl bg-[#173d34]/10 text-[#173d34] flex items-center justify-center">
                            <i data-lucide="messages-square" class="w-5 h-5"></i>
                        </div>
                    </div>

                    <!-- Gemini AI Engine Status -->
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-[#ffffff] to-[#f5f2e9] border border-[#ddd8cc] shadow-sm flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-[#68716d] uppercase tracking-wider font-mono">Gemini AI Model</div>
                            <div class="text-sm font-extrabold text-[#202725] font-mono mt-1 truncate max-w-[200px]" id="stat-active-model">-</div>
                            <div class="text-[11px] text-emerald-700 font-bold mt-0.5 flex items-center space-x-1">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span id="stat-gemini-status">Engine Connected</span>
                            </div>
                        </div>
                        <button onclick="pingGeminiAI()" class="p-2 rounded-xl bg-white hover:bg-emerald-50 text-emerald-700 border border-[#ddd8cc] text-xs font-bold shadow-sm transition-all flex items-center space-x-1" title="Test Gemini latency">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                            <span>Ping</span>
                        </button>
                    </div>

                    <!-- Token Consumption -->
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-[#ffffff] to-[#f5f2e9] border border-[#ddd8cc] shadow-sm flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-[#68716d] uppercase tracking-wider font-mono">Token Telemetry</div>
                            <div class="text-xl font-extrabold text-[#b7843f] font-mono mt-1" id="stat-total-tokens">-</div>
                            <div class="text-[11px] text-[#68716d] mt-0.5" id="stat-today-tokens">- tokens used today</div>
                        </div>
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-700 flex items-center justify-center">
                            <i data-lucide="zap" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>

                <!-- Two Split Tables: Recent Workspaces & Recent Users -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Workspaces -->
                    <div class="p-5 rounded-3xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm flex flex-col">
                        <div class="flex items-center justify-between pb-3 border-b border-[#e8e3d9] mb-3">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="layers" class="w-4 h-4 text-[#173d34]"></i>
                                <h3 class="text-sm font-bold text-[#202725]">Recent Workspaces</h3>
                            </div>
                            <button onclick="switchView('workspaces')" class="text-xs font-bold text-[#173d34] hover:underline flex items-center space-x-1">
                                <span>View All</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                        <div class="space-y-2 flex-1" id="recentWorkspacesContainer">
                            <div class="text-xs text-[#68716d] py-6 text-center">Loading recent workspaces...</div>
                        </div>
                    </div>

                    <!-- Recent Users -->
                    <div class="p-5 rounded-3xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm flex flex-col">
                        <div class="flex items-center justify-between pb-3 border-b border-[#e8e3d9] mb-3">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="users" class="w-4 h-4 text-[#173d34]"></i>
                                <h3 class="text-sm font-bold text-[#202725]">Recently Registered Users</h3>
                            </div>
                            <button onclick="switchView('users')" class="text-xs font-bold text-[#173d34] hover:underline flex items-center space-x-1">
                                <span>Manage Users</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                        <div class="space-y-2 flex-1" id="recentUsersContainer">
                            <div class="text-xs text-[#68716d] py-6 text-center">Loading recent users...</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ======================================================== -->
            <!-- 2. WORKSPACES VIEW (TWO-TIER DEV TEAM & USER INSTANCES)   -->
            <!-- ======================================================== -->
            <section id="view-workspaces" class="space-y-4 hidden">
                <!-- Two-Tier Sub-Tab Switcher -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#ddd8cc] pb-3">
                    <div class="flex items-center space-x-1 bg-[#f5f2e9] p-1 rounded-2xl border border-[#ddd8cc]">
                        <button type="button" onclick="switchWsSubTab('blueprints')" id="wsTabBtn-blueprints" class="px-4 py-2 text-xs font-bold rounded-xl transition-all shadow-sm bg-[#173d34] text-white flex items-center space-x-2">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                            <span>Workspace Blueprints (Dev Team)</span>
                            <span id="badge-blueprints-count" class="text-[10px] bg-white/20 px-1.5 py-0.2 rounded-full font-mono">0</span>
                        </button>
                        <button type="button" onclick="switchWsSubTab('instances')" id="wsTabBtn-instances" class="px-4 py-2 text-xs font-semibold rounded-xl transition-all text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] flex items-center space-x-2">
                            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                            <span>Client Active Workspaces (Users)</span>
                            <span id="badge-instances-count" class="text-[10px] bg-black/10 px-1.5 py-0.2 rounded-full font-mono">0</span>
                        </button>
                    </div>

                    <!-- Right Quick Actions -->
                    <div class="flex items-center space-x-2" id="wsSubTabActions">
                        <button onclick="openUploadBlueprintModal()" class="px-3.5 py-2 bg-white hover:bg-[#fcfaf5] text-[#173d34] border border-[#ddd8cc] hover:border-[#173d34] rounded-xl text-xs font-bold flex items-center space-x-1.5 transition-all shadow-sm">
                            <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                            <span>Upload Blueprint (.json)</span>
                        </button>
                        <button onclick="openCreateBlueprintModal()" class="px-3.5 py-2 bg-[#173d34] hover:bg-[#285c4e] text-white rounded-xl text-xs font-bold flex items-center space-x-1.5 transition-all shadow-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Create Blueprint</span>
                        </button>
                    </div>
                </div>

                <!-- SUB-PANEL 1: WORKSPACE BLUEPRINTS (DEV TEAM CATALOG) -->
                <div id="wsSubView-blueprints" class="space-y-4">
                    <!-- Filter Bar for Blueprints -->
                    <div class="p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center justify-between">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <div class="relative flex-1">
                                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#68716d]"></i>
                                <input type="text" id="blueprintSearchInput" oninput="filterBlueprints()" placeholder="Search blueprints by title, domain, or slug..." class="w-full pl-9 pr-3 py-2 text-xs bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl focus:outline-none focus:border-[#173d34]">
                            </div>
                            <select id="blueprintTierFilter" onchange="filterBlueprints()" class="px-3 py-2 text-xs bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl focus:outline-none focus:border-[#173d34]">
                                <option value="all">All Tiers</option>
                                <option value="free">Free Starter</option>
                                <option value="paid">Paid Premium</option>
                            </select>
                            <select id="blueprintStatusFilter" onchange="filterBlueprints()" class="px-3 py-2 text-xs bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl focus:outline-none focus:border-[#173d34]">
                                <option value="all">All Statuses</option>
                                <option value="1">Live in Store</option>
                                <option value="0">Draft (Hidden)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Blueprints Table -->
                    <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-[#f5f2e9] text-[#68716d] border-b border-[#ddd8cc] font-bold font-mono uppercase text-[10px]">
                                        <th class="p-3.5">Blueprint Title & Domain</th>
                                        <th class="p-3.5">Tier & Pricing</th>
                                        <th class="p-3.5 text-center">Dream Team</th>
                                        <th class="p-3.5 text-center">Deliverables</th>
                                        <th class="p-3.5 text-center">Store Status</th>
                                        <th class="p-3.5 text-center">Client Adoptions</th>
                                        <th class="p-3.5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="blueprintsTableBody" class="divide-y divide-[#e8e3d9]">
                                    <tr>
                                        <td colspan="7" class="p-8 text-center text-[#68716d]">Loading blueprints catalog...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SUB-PANEL 2: CLIENT ACTIVE INSTANCES (USER WORKSPACES) -->
                <div id="wsSubView-instances" class="space-y-4 hidden">
                    <!-- Filter Bar for Instances -->
                    <div class="p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center justify-between">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <div class="relative flex-1">
                                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#68716d]"></i>
                                <input type="text" id="workspaceSearchInput" oninput="filterWorkspaces()" placeholder="Search active client workspaces by name or owner..." class="w-full pl-9 pr-3 py-2 text-xs bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl focus:outline-none focus:border-[#173d34]">
                            </div>
                            <select id="workspaceOwnerFilter" onchange="filterWorkspaces()" class="px-3 py-2 text-xs bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl focus:outline-none focus:border-[#173d34]">
                                <option value="0">All Owners</option>
                            </select>
                        </div>
                        <button onclick="openNewWorkspaceModal()" class="px-4 py-2 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold flex items-center justify-center space-x-1.5 transition-all shadow-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Provision Custom to User</span>
                        </button>
                    </div>

                    <!-- Workspaces Table / Grid Container -->
                    <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-[#f5f2e9] text-[#68716d] border-b border-[#ddd8cc] font-bold font-mono uppercase text-[10px]">
                                        <th class="p-3.5">Workspace</th>
                                        <th class="p-3.5">Assigned Client</th>
                                        <th class="p-3.5 text-center">Team</th>
                                        <th class="p-3.5 text-center">Roadmaps</th>
                                        <th class="p-3.5 text-center">Tasks</th>
                                        <th class="p-3.5">Created Date</th>
                                        <th class="p-3.5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="workspacesTableBody" class="divide-y divide-[#e8e3d9]">
                                    <tr>
                                        <td colspan="7" class="p-8 text-center text-[#68716d]">Loading workspaces...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ======================================================== -->
            <!-- 3. USERS VIEW                                            -->
            <!-- ======================================================== -->
            <section id="view-users" class="space-y-4 hidden">
                <!-- Filter Bar -->
                <div class="p-4 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
                    <div class="flex-1 flex flex-col sm:flex-row gap-2">
                        <!-- Search Box -->
                        <div class="relative flex-1">
                            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#68716d]"></i>
                            <input type="text" id="userSearchInput" oninput="filterUsers()" placeholder="Search users by name, username, or email..." class="w-full pl-9 pr-3 py-2 text-xs bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl focus:outline-none focus:border-[#173d34]">
                        </div>
                        <!-- Role Filter -->
                        <select id="userRoleFilter" onchange="filterUsers()" class="px-3 py-2 text-xs bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl focus:outline-none focus:border-[#173d34]">
                            <option value="all">All Roles</option>
                            <option value="admin">Administrators Only</option>
                            <option value="user">Standard Users Only</option>
                        </select>
                    </div>
                    <button onclick="openNewUserModal()" class="px-4 py-2 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold flex items-center justify-center space-x-1.5 transition-all shadow-sm">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        <span>Add New User</span>
                    </button>
                </div>

                <!-- Users Table Container -->
                <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-[#f5f2e9] text-[#68716d] border-b border-[#ddd8cc] font-bold font-mono uppercase text-[10px]">
                                    <th class="p-3.5">User</th>
                                    <th class="p-3.5">Email</th>
                                    <th class="p-3.5">Role</th>
                                    <th class="p-3.5 text-center">Workspaces</th>
                                    <th class="p-3.5 text-center">Sessions</th>
                                    <th class="p-3.5">Registered</th>
                                    <th class="p-3.5">Last Login</th>
                                    <th class="p-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody" class="divide-y divide-[#e8e3d9]">
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-[#68716d]">Loading users...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ======================================================== -->
            <!-- 4. AI ENGINE & SYSTEM VIEW                               -->
            <!-- ======================================================== -->
            <section id="view-system" class="space-y-6 hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Card 1: Gemini AI Diagnostics -->
                    <div class="p-5 rounded-3xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-[#e8e3d9]">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="sparkles" class="w-4 h-4 text-[#173d34]"></i>
                                <h3 class="text-sm font-bold text-[#202725]">Gemini AI Core Diagnostics</h3>
                            </div>
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 border border-emerald-300 px-2 py-0.5 rounded-full font-mono font-bold">ACTIVE</span>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div class="p-3 rounded-2xl bg-[#fcfaf5] border border-[#ddd8cc] flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] text-[#68716d] uppercase font-mono font-semibold block">Configured API Key</span>
                                    <span class="font-mono font-bold text-[#202725]" id="systemMaskedKey">••••••••••••</span>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-bold">Secured</span>
                            </div>

                            <div class="p-3 rounded-2xl bg-[#fcfaf5] border border-[#ddd8cc] flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] text-[#68716d] uppercase font-mono font-semibold block">Primary Active Model</span>
                                    <span class="font-mono font-bold text-[#173d34]" id="systemActiveModel">gemini-3.1-flash-lite</span>
                                </div>
                                <span class="text-[10px] font-mono text-[#68716d]">Auto-Fallback Enabled</span>
                            </div>

                            <!-- Ping Test Box -->
                            <div class="p-4 rounded-2xl bg-gradient-to-br from-[#f5f2e9] to-[#ffffff] border border-[#ddd8cc] space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-[#173d34]">Live AI Connection Benchmark</span>
                                    <button onclick="pingGeminiAI()" id="btnSystemPing" class="px-3 py-1.5 bg-[#173d34] hover:bg-[#285c4e] text-white rounded-xl text-xs font-bold flex items-center space-x-1.5 shadow-sm transition-all">
                                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                        <span>Send Ping Prompt</span>
                                    </button>
                                </div>
                                <div id="pingResultBox" class="hidden p-3 rounded-xl bg-white border border-[#ddd8cc] text-[11px] font-mono text-[#202725]">
                                    <!-- Results dynamically populated -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Server & Environment -->
                    <div class="p-5 rounded-3xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-[#e8e3d9]">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="server" class="w-4 h-4 text-[#173d34]"></i>
                                <h3 class="text-sm font-bold text-[#202725]">Server & Environment Status</h3>
                            </div>
                            <span class="text-[10px] bg-slate-100 text-slate-800 border border-slate-300 px-2 py-0.5 rounded-full font-mono font-bold">HEALTHY</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-3 rounded-2xl bg-[#fcfaf5] border border-[#ddd8cc]">
                                <span class="text-[10px] text-[#68716d] uppercase font-mono block">PHP Version</span>
                                <span class="font-bold text-[#202725] font-mono" id="sysPhpVersion">-</span>
                            </div>
                            <div class="p-3 rounded-2xl bg-[#fcfaf5] border border-[#ddd8cc]">
                                <span class="text-[10px] text-[#68716d] uppercase font-mono block">DB Driver & Engine</span>
                                <span class="font-bold text-[#202725] font-mono" id="sysDbDriver">-</span>
                            </div>
                            <div class="p-3 rounded-2xl bg-[#fcfaf5] border border-[#ddd8cc]">
                                <span class="text-[10px] text-[#68716d] uppercase font-mono block">Max Upload File Size</span>
                                <span class="font-bold text-[#202725] font-mono" id="sysUploadSize">-</span>
                            </div>
                            <div class="p-3 rounded-2xl bg-[#fcfaf5] border border-[#ddd8cc]">
                                <span class="text-[10px] text-[#68716d] uppercase font-mono block">Memory Allocation</span>
                                <span class="font-bold text-[#202725] font-mono" id="sysMemory">-</span>
                            </div>
                        </div>

                        <!-- Database Tables Summary -->
                        <div class="pt-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#68716d] font-mono block mb-2">Database Row Telemetry</span>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] font-mono" id="dbTablesGrid">
                                <!-- Populated dynamically -->
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- ============================================================ -->
    <!-- MODALS: WORKSPACES & USERS MANAGEMENT                         -->
    <!-- ============================================================ -->

    <!-- MODAL 1: CREATE WORKSPACE -->
    <div id="modalNewWorkspace" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
            <div class="px-6 py-4 bg-[#f5f2e9] border-b border-[#ddd8cc] flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-[#173d34] text-white flex items-center justify-center">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#202725]">Create New Workspace</h3>
                        <p class="text-[10px] text-[#68716d]">Provision a dedicated workspace & executive team for a user</p>
                    </div>
                </div>
                <button onclick="closeModal('modalNewWorkspace')" class="text-[#68716d] hover:text-[#173d34] p-1.5 rounded-xl hover:bg-[#e8e3d9]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form id="formNewWorkspace" onsubmit="submitNewWorkspace(event)" class="p-6 overflow-y-auto space-y-3.5 text-xs">
                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Assign Workspace Owner (User) *</label>
                    <select id="newWsUserId" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                        <!-- Populated dynamically -->
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Workspace Title *</label>
                    <input type="text" id="newWsName" required placeholder="e.g. Media Production Hub, Mobile App Studio" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                </div>

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Domain / Industry</label>
                    <input type="text" id="newWsDomain" placeholder="e.g. Content Creation, Software Engineering, E-Commerce" value="General Operations" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                </div>

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Strategic Mission & Description</label>
                    <textarea id="newWsDescription" rows="2" placeholder="Brief outline of workspace goals and operational scope..." class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Icon Style</label>
                        <select id="newWsIcon" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                            <option value="briefcase">💼 Briefcase</option>
                            <option value="code">💻 Code / Tech</option>
                            <option value="video">🎬 Video & Media</option>
                            <option value="shopping-bag">🛍️ Retail / Commerce</option>
                            <option value="leaf">🌿 Nature & Art</option>
                            <option value="rocket">🚀 Growth Launchpad</option>
                            <option value="cpu">⚙️ Architecture</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Color Palette</label>
                        <select id="newWsColor" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                            <option value="indigo">Indigo Blue</option>
                            <option value="emerald">Emerald Green</option>
                            <option value="amber">Warm Amber</option>
                            <option value="rose">Rose Red</option>
                            <option value="purple">Royal Purple</option>
                            <option value="cyan">Cyan Teal</option>
                        </select>
                    </div>
                </div>

                <!-- Auto generate team checkbox -->
                <div class="p-3 rounded-2xl bg-[#f5f2e9] border border-[#ddd8cc] flex items-center space-x-3">
                    <input type="checkbox" id="newWsAutoTeam" class="w-4 h-4 text-[#173d34] rounded focus:ring-0 cursor-pointer">
                    <label for="newWsAutoTeam" class="cursor-pointer select-none">
                        <span class="font-bold text-[#173d34] block">Auto-generate specialized AI Executive Team</span>
                        <span class="text-[10px] text-[#68716d] block">Invokes Gemini AI to craft 4 tailored C-level executives matching this workspace's domain</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-[#ddd8cc] flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalNewWorkspace')" class="px-4 py-2 border border-[#ddd8cc] hover:bg-[#ede8dc] rounded-xl font-bold text-[#68716d]">Cancel</button>
                    <button type="submit" id="btnSubmitNewWs" class="px-5 py-2 bg-[#173d34] hover:bg-[#285c4e] text-white rounded-xl font-bold flex items-center space-x-1.5 shadow-sm">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Create Workspace</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDIT WORKSPACE -->
    <div id="modalEditWorkspace" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
            <div class="px-6 py-4 bg-[#f5f2e9] border-b border-[#ddd8cc] flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-amber-600 text-white flex items-center justify-center">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#202725]">Edit Workspace</h3>
                        <p class="text-[10px] text-[#68716d]">Modify settings or transfer ownership</p>
                    </div>
                </div>
                <button onclick="closeModal('modalEditWorkspace')" class="text-[#68716d] hover:text-[#173d34] p-1.5 rounded-xl hover:bg-[#e8e3d9]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form id="formEditWorkspace" onsubmit="submitEditWorkspace(event)" class="p-6 overflow-y-auto space-y-3.5 text-xs">
                <input type="hidden" id="editWsId">

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Transfer Ownership to User</label>
                    <select id="editWsUserId" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                        <!-- Populated dynamically -->
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Workspace Name *</label>
                    <input type="text" id="editWsName" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                </div>

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Domain</label>
                    <input type="text" id="editWsDomain" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                </div>

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Description</label>
                    <textarea id="editWsDescription" rows="2" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Icon Style</label>
                        <select id="editWsIcon" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                            <option value="briefcase">💼 Briefcase</option>
                            <option value="code">💻 Code / Tech</option>
                            <option value="video">🎬 Video & Media</option>
                            <option value="shopping-bag">🛍️ Retail / Commerce</option>
                            <option value="leaf">🌿 Nature & Art</option>
                            <option value="rocket">🚀 Growth Launchpad</option>
                            <option value="cpu">⚙️ Architecture</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Color Palette</label>
                        <select id="editWsColor" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                            <option value="indigo">Indigo Blue</option>
                            <option value="emerald">Emerald Green</option>
                            <option value="amber">Warm Amber</option>
                            <option value="rose">Rose Red</option>
                            <option value="purple">Royal Purple</option>
                            <option value="cyan">Cyan Teal</option>
                        </select>
                    </div>
                </div>

                <div class="pt-3 border-t border-[#ddd8cc] flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalEditWorkspace')" class="px-4 py-2 border border-[#ddd8cc] hover:bg-[#ede8dc] rounded-xl font-bold text-[#68716d]">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-[#173d34] hover:bg-[#285c4e] text-white rounded-xl font-bold flex items-center space-x-1.5 shadow-sm">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: VIEW WORKSPACE DETAILS (EXECUTIVE TEAM & ROADMAP) -->
    <div id="modalViewWorkspace" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 bg-gradient-to-r from-[#173d34] to-[#285c4e] text-white flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/15 text-white flex items-center justify-center font-bold text-lg">
                        <i id="viewWsIcon" data-lucide="layers" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 id="viewWsTitle" class="text-base font-extrabold text-white">Workspace Details</h3>
                        <p id="viewWsDomain" class="text-xs text-emerald-100">Domain info</p>
                    </div>
                </div>
                <button onclick="closeModal('modalViewWorkspace')" class="text-white/80 hover:text-white p-1.5 rounded-xl hover:bg-white/10">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-5 text-xs">
                <!-- Overview Row -->
                <div class="p-3.5 rounded-2xl bg-[#fcfaf5] border border-[#ddd8cc] flex flex-col sm:flex-row justify-between gap-3">
                    <div>
                        <span class="text-[10px] text-[#68716d] uppercase font-mono block">Assigned Owner</span>
                        <span id="viewWsOwner" class="font-bold text-[#202725] text-sm">Owner Name</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-[#68716d] uppercase font-mono block">Created Date</span>
                        <span id="viewWsCreated" class="font-bold text-[#202725]">Date</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-[#68716d] uppercase font-mono block">Executive Team Size</span>
                        <span id="viewWsTeamSize" class="font-bold text-[#173d34] font-mono">0 Members</span>
                    </div>
                </div>

                <!-- Executives List -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#173d34] font-mono mb-2 flex items-center space-x-1.5">
                        <i data-lucide="bot" class="w-3.5 h-3.5"></i>
                        <span>AI Executive Committee</span>
                    </h4>
                    <div id="viewWsExecList" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <!-- Populated dynamically -->
                    </div>
                </div>

                <!-- Projects & Roadmaps -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#173d34] font-mono mb-2 flex items-center space-x-1.5">
                        <i data-lucide="folder-kanban" class="w-3.5 h-3.5"></i>
                        <span>Roadmaps & Projects</span>
                    </h4>
                    <div id="viewWsProjList" class="space-y-1.5">
                        <!-- Populated dynamically -->
                    </div>
                </div>
            </div>

            <div class="px-6 py-3 bg-[#f5f2e9] border-t border-[#ddd8cc] flex justify-end">
                <button type="button" onclick="closeModal('modalViewWorkspace')" class="px-5 py-2 bg-[#173d34] text-white rounded-xl font-bold">Close</button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: ADD NEW USER -->
    <div id="modalNewUser" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
            <div class="px-6 py-4 bg-[#f5f2e9] border-b border-[#ddd8cc] flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-[#173d34] text-white flex items-center justify-center">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#202725]">Add New User</h3>
                        <p class="text-[10px] text-[#68716d]">Register client or team member credentials</p>
                    </div>
                </div>
                <button onclick="closeModal('modalNewUser')" class="text-[#68716d] hover:text-[#173d34] p-1.5 rounded-xl hover:bg-[#e8e3d9]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form id="formNewUser" onsubmit="submitNewUser(event)" class="p-6 overflow-y-auto space-y-3.5 text-xs">
                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Full Name *</label>
                    <input type="text" id="newUserName" required placeholder="e.g. Kasun Fernando" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Username *</label>
                        <input type="text" id="newUserUsername" required placeholder="kasun_f" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                    </div>
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Role *</label>
                        <select id="newUserRole" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                            <option value="user">Standard User</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Email Address *</label>
                    <input type="email" id="newUserEmail" required placeholder="kasun@example.com" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Initial Password *</label>
                        <input type="password" id="newUserPassword" required minlength="6" placeholder="Min 6 characters" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                    </div>
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Confirm Password *</label>
                        <input type="password" id="newUserPasswordConfirm" required minlength="6" placeholder="Repeat password" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                    </div>
                </div>

                <!-- Auto-provision workspace checkbox -->
                <div class="p-3 rounded-2xl bg-[#f5f2e9] border border-[#ddd8cc] flex items-center space-x-3">
                    <input type="checkbox" id="newUserCreateWs" checked class="w-4 h-4 text-[#173d34] rounded focus:ring-0 cursor-pointer">
                    <label for="newUserCreateWs" class="cursor-pointer select-none">
                        <span class="font-bold text-[#173d34] block">Automatically provision Primary Workspace</span>
                        <span class="text-[10px] text-[#68716d] block">Sets up a ready-to-use workspace with starter executive team</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-[#ddd8cc] flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalNewUser')" class="px-4 py-2 border border-[#ddd8cc] hover:bg-[#ede8dc] rounded-xl font-bold text-[#68716d]">Cancel</button>
                    <button type="submit" id="btnSubmitNewUser" class="px-5 py-2 bg-[#173d34] hover:bg-[#285c4e] text-white rounded-xl font-bold flex items-center space-x-1.5 shadow-sm">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Register User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: EDIT USER -->
    <div id="modalEditUser" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
            <div class="px-6 py-4 bg-[#f5f2e9] border-b border-[#ddd8cc] flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-amber-600 text-white flex items-center justify-center">
                        <i data-lucide="user-cog" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#202725]">Edit User Account</h3>
                        <p class="text-[10px] text-[#68716d]">Update profile details, permissions, or password</p>
                    </div>
                </div>
                <button onclick="closeModal('modalEditUser')" class="text-[#68716d] hover:text-[#173d34] p-1.5 rounded-xl hover:bg-[#e8e3d9]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form id="formEditUser" onsubmit="submitEditUser(event)" class="p-6 overflow-y-auto space-y-3.5 text-xs">
                <input type="hidden" id="editUserId">

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Full Name *</label>
                    <input type="text" id="editUserName" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Username *</label>
                        <input type="text" id="editUserUsername" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                    </div>
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Role *</label>
                        <select id="editUserRole" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                            <option value="user">Standard User</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Email Address *</label>
                    <input type="email" id="editUserEmail" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                </div>

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Reset Password (leave empty to keep unchanged)</label>
                    <input type="password" id="editUserPassword" placeholder="Enter new password if changing" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                </div>

                <div class="pt-3 border-t border-[#ddd8cc] flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalEditUser')" class="px-4 py-2 border border-[#ddd8cc] hover:bg-[#ede8dc] rounded-xl font-bold text-[#68716d]">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-[#173d34] hover:bg-[#285c4e] text-white rounded-xl font-bold flex items-center space-x-1.5 shadow-sm">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 6: UPLOAD WORKSPACE BLUEPRINT (DEV TEAM) -->
    <div id="modalUploadBlueprint" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
            <div class="px-6 py-4 bg-[#f5f2e9] border-b border-[#ddd8cc] flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-[#173d34] text-white flex items-center justify-center">
                        <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#202725]">Upload Workspace Blueprint</h3>
                        <p class="text-[10px] text-[#68716d]">Import pre-built JSON configuration crafted by Dev Team</p>
                    </div>
                </div>
                <button onclick="closeModal('modalUploadBlueprint')" class="text-[#68716d] hover:text-[#173d34] p-1.5 rounded-xl hover:bg-[#e8e3d9]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form id="formUploadBlueprint" onsubmit="submitUploadBlueprint(event)" class="p-6 overflow-y-auto space-y-4 text-xs">
                <!-- Dropzone / File Picker -->
                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Select Blueprint File (.json)</label>
                    <div class="border-2 border-dashed border-[#ddd8cc] hover:border-[#173d34] rounded-2xl p-4 text-center bg-[#fcfaf5] transition-colors cursor-pointer" onclick="document.getElementById('blueprintFileInput').click()">
                        <i data-lucide="file-code-2" class="w-7 h-7 mx-auto text-[#173d34] mb-1"></i>
                        <span class="text-xs font-bold text-[#202725] block" id="blueprintFileLabel">Click or drag a .json blueprint file</span>
                        <span class="text-[10px] text-[#68716d]">Supports official RuXt Blueprint schemas</span>
                        <input type="file" id="blueprintFileInput" accept=".json" class="hidden" onchange="handleBlueprintFileSelected(event)">
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <div class="flex-1 h-px bg-[#e8e3d9]"></div>
                    <span class="text-[10px] text-[#68716d] font-mono uppercase font-bold">OR PASTE RAW JSON</span>
                    <div class="flex-1 h-px bg-[#e8e3d9]"></div>
                </div>

                <!-- Raw JSON Textarea -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-semibold text-[#202725]">JSON Blueprint Code</label>
                        <button type="button" onclick="previewBlueprintJson()" class="text-[10px] font-bold text-[#173d34] hover:underline flex items-center space-x-1">
                            <i data-lucide="scan" class="w-3 h-3"></i>
                            <span>Validate & Preview</span>
                        </button>
                    </div>
                    <textarea id="blueprintJsonInput" rows="6" placeholder='{\n  "title": "FinTech & Payments Engine",\n  "domain": "FinTech",\n  "description": "...",\n  "executives": [...]\n}' class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl p-3 font-mono text-[11px] text-[#202725] focus:outline-none focus:border-[#173d34]" oninput="previewBlueprintJson()"></textarea>
                </div>

                <!-- Preview Card -->
                <div id="blueprintPreviewBox" class="hidden p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 space-y-1 text-xs">
                    <div class="flex items-center justify-between font-bold">
                        <span id="previewBpTitle">Title</span>
                        <span id="previewBpDomain" class="text-[10px] uppercase font-mono px-2 py-0.5 rounded-full bg-emerald-200 text-emerald-800">Domain</span>
                    </div>
                    <div class="text-[11px] text-emerald-700" id="previewBpCounts">
                        0 AI Executives &bull; 0 Roadmaps &bull; 0 Starter Tasks
                    </div>
                </div>

                <div class="pt-3 border-t border-[#ddd8cc] flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalUploadBlueprint')" class="px-4 py-2 border border-[#ddd8cc] hover:bg-[#ede8dc] rounded-xl font-bold text-[#68716d]">Cancel</button>
                    <button type="submit" id="btnSubmitUploadBp" class="px-5 py-2 bg-[#173d34] hover:bg-[#285c4e] text-white rounded-xl font-bold flex items-center space-x-1.5 shadow-sm">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Upload & Publish to Catalog</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 7: VISUAL BLUEPRINT STUDIO (CREATE / EDIT TEMPLATE) -->
    <div id="modalStudioBlueprint" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-3xl w-full max-w-xl overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
            <div class="px-6 py-4 bg-[#f5f2e9] border-b border-[#ddd8cc] flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-purple-700 text-white flex items-center justify-center">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#202725]" id="studioModalTitle">Workspace Blueprint Studio</h3>
                        <p class="text-[10px] text-[#68716d]">Design or fine-tune a pre-built workspace for users</p>
                    </div>
                </div>
                <button onclick="closeModal('modalStudioBlueprint')" class="text-[#68716d] hover:text-[#173d34] p-1.5 rounded-xl hover:bg-[#e8e3d9]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form id="formStudioBlueprint" onsubmit="submitBlueprintStudio(event)" class="p-6 overflow-y-auto space-y-3.5 text-xs">
                <input type="hidden" id="studioBpId">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Blueprint Title *</label>
                        <input type="text" id="studioBpTitle" required placeholder="e.g. Legal & Corporate Advisory Hub" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                    </div>
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">URL Identifier (Slug)</label>
                        <input type="text" id="studioBpSlug" placeholder="legal-advisory-hub (auto-generated if empty)" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Domain / Industry *</label>
                        <input type="text" id="studioBpDomain" required placeholder="e.g. Legal & Compliance" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                    </div>
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Catalog Badge</label>
                        <input type="text" id="studioBpBadge" placeholder="e.g. FREE STARTER, ENTERPRISE ENGINE" value="DEV STARTER" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Mission & Scope Description</label>
                    <textarea id="studioBpDescription" rows="2" placeholder="Describe how this workspace empowers the user..." class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]"></textarea>
                </div>

                <div class="grid grid-cols-4 gap-2">
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Tier</label>
                        <select id="studioBpTier" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-2.5 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                            <option value="free">Free</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Price ($)</label>
                        <input type="number" step="0.01" id="studioBpPrice" value="0.00" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-2.5 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                    </div>
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Icon</label>
                        <select id="studioBpIcon" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-2 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                            <option value="briefcase">💼 Briefcase</option>
                            <option value="code">💻 Tech</option>
                            <option value="video">🎬 Media</option>
                            <option value="shopping-bag">🛍️ Retail</option>
                            <option value="leaf">🌿 Nature</option>
                            <option value="rocket">🚀 Growth</option>
                            <option value="cpu">⚙️ Engineering</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-[#202725] mb-1">Color</label>
                        <select id="studioBpColor" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-2 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                            <option value="indigo">Indigo</option>
                            <option value="emerald">Emerald</option>
                            <option value="amber">Amber</option>
                            <option value="rose">Rose</option>
                            <option value="purple">Purple</option>
                            <option value="cyan">Cyan</option>
                        </select>
                    </div>
                </div>

                <!-- AI Team auto-generation checkbox -->
                <div class="p-3 rounded-2xl bg-[#f5f2e9] border border-[#ddd8cc] flex items-center space-x-3">
                    <input type="checkbox" id="studioBpAutoTeam" class="w-4 h-4 text-[#173d34] rounded focus:ring-0 cursor-pointer">
                    <label for="studioBpAutoTeam" class="cursor-pointer select-none">
                        <span class="font-bold text-[#173d34] block">Auto-generate 4-person Executive AI Team via Gemini</span>
                        <span class="text-[10px] text-[#68716d] block">AI creates C-level avatars, specialized roles, personalities, and system prompts matching this domain</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-[#ddd8cc] flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalStudioBlueprint')" class="px-4 py-2 border border-[#ddd8cc] hover:bg-[#ede8dc] rounded-xl font-bold text-[#68716d]">Cancel</button>
                    <button type="submit" id="btnSubmitStudioBp" class="px-5 py-2 bg-[#173d34] hover:bg-[#285c4e] text-white rounded-xl font-bold flex items-center space-x-1.5 shadow-sm">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Save to Catalog</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 8: DEPLOY BLUEPRINT TO USER -->
    <div id="modalDeployTemplate" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-3xl w-full max-w-md overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 bg-[#173d34] text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-white/20 text-white flex items-center justify-center">
                        <i data-lucide="send" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Deploy Workspace to Client</h3>
                        <p class="text-[10px] text-emerald-100">Directly activate pre-built workspace for a user</p>
                    </div>
                </div>
                <button onclick="closeModal('modalDeployTemplate')" class="text-white/80 hover:text-white p-1.5 rounded-xl hover:bg-white/10">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form id="formDeployTemplate" onsubmit="submitDeployTemplate(event)" class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Select Target User *</label>
                    <select id="deployTargetUserId" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                        <!-- Populated dynamically -->
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-[#202725] mb-1">Select Workspace Blueprint *</label>
                    <select id="deployTemplateId" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#173d34]">
                        <!-- Populated dynamically -->
                    </select>
                </div>

                <div class="p-3 rounded-2xl bg-[#f5f2e9] border border-[#ddd8cc] text-[11px] text-[#68716d]">
                    <span class="font-bold text-[#173d34] block mb-0.5">Automated Provisioning</span>
                    Clones the complete Executive AI dream team, starter roadmaps, deliverables, and prompts into the client's account instantly.
                </div>

                <div class="pt-3 border-t border-[#ddd8cc] flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalDeployTemplate')" class="px-4 py-2 border border-[#ddd8cc] hover:bg-[#ede8dc] rounded-xl font-bold text-[#68716d]">Cancel</button>
                    <button type="submit" id="btnSubmitDeploy" class="px-5 py-2 bg-[#173d34] hover:bg-[#285c4e] text-white rounded-xl font-bold flex items-center space-x-1.5 shadow-sm">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span>Deploy Now</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 9: INSPECT BLUEPRINT (FULL EXECUTIVE DREAM TEAM & ADOPTIONS) -->
    <div id="modalInspectTemplate" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 bg-gradient-to-r from-[#173d34] to-[#285c4e] text-white flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/15 text-white flex items-center justify-center font-bold text-lg">
                        <i id="inspectTplIcon" data-lucide="sparkles" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h3 id="inspectTplTitle" class="text-base font-extrabold text-white">Blueprint Details</h3>
                            <span id="inspectTplBadge" class="text-[9px] bg-white/20 text-white px-2 py-0.5 rounded-full font-mono uppercase font-bold">STARTER</span>
                        </div>
                        <p id="inspectTplDomain" class="text-xs text-emerald-100">Domain</p>
                    </div>
                </div>
                <button onclick="closeModal('modalInspectTemplate')" class="text-white/80 hover:text-white p-1.5 rounded-xl hover:bg-white/10">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-5 text-xs">
                <!-- Overview Row -->
                <div class="p-3.5 rounded-2xl bg-[#fcfaf5] border border-[#ddd8cc] flex flex-col sm:flex-row justify-between gap-3">
                    <div>
                        <span class="text-[10px] text-[#68716d] uppercase font-mono block">Pricing & Tier</span>
                        <span id="inspectTplPrice" class="font-bold text-[#173d34] text-sm">Free</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-[#68716d] uppercase font-mono block">Status in Store</span>
                        <span id="inspectTplStatus" class="font-bold text-emerald-700">Live in Store</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-[#68716d] uppercase font-mono block">Client Adoptions</span>
                        <span id="inspectTplAdoptionsCount" class="font-bold text-[#202725] font-mono">0 Clients</span>
                    </div>
                </div>

                <!-- Executives Committee -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#173d34] font-mono mb-2 flex items-center space-x-1.5">
                        <i data-lucide="bot" class="w-3.5 h-3.5"></i>
                        <span>Executive AI Committee</span>
                    </h4>
                    <div id="inspectTplExecList" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <!-- Populated dynamically -->
                    </div>
                </div>

                <!-- Roadmaps & Tasks -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-[#173d34] font-mono mb-2 flex items-center space-x-1.5">
                            <i data-lucide="folder-kanban" class="w-3.5 h-3.5"></i>
                            <span>Starter Roadmaps</span>
                        </h4>
                        <div id="inspectTplProjList" class="space-y-1.5"></div>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-[#173d34] font-mono mb-2 flex items-center space-x-1.5">
                            <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
                            <span>Starter Deliverables</span>
                        </h4>
                        <div id="inspectTplTaskList" class="space-y-1.5"></div>
                    </div>
                </div>

                <!-- Clients who adopted this workspace -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#173d34] font-mono mb-2 flex items-center space-x-1.5">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        <span>Active Client Users</span>
                    </h4>
                    <div id="inspectTplUsersList" class="space-y-1.5"></div>
                </div>
            </div>

            <div class="px-6 py-3 bg-[#f5f2e9] border-t border-[#ddd8cc] flex justify-end">
                <button type="button" onclick="closeModal('modalInspectTemplate')" class="px-5 py-2 bg-[#173d34] text-white rounded-xl font-bold">Close</button>
            </div>
        </div>
    </div>

    <!-- MODAL 10: CONFIRM DELETE MODAL -->
    <div id="modalConfirmDelete" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 text-center space-y-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 id="deleteModalTitle" class="text-base font-extrabold text-[#202725]">Confirm Action</h3>
                <p id="deleteModalDesc" class="text-xs text-[#68716d] mt-1">Are you sure you want to permanently delete this record? This action cannot be undone.</p>
            </div>
            <div class="flex items-center justify-center space-x-2 pt-2">
                <button type="button" onclick="closeModal('modalConfirmDelete')" class="px-4 py-2 border border-[#ddd8cc] hover:bg-[#ede8dc] rounded-xl font-bold text-[#68716d] text-xs">Cancel</button>
                <button type="button" id="btnConfirmDeleteAction" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-xs shadow-sm flex items-center space-x-1">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Delete Permanently</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Floating Toast Notification -->
    <div id="toastContainer" class="fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none"></div>

    <!-- Application Dashboard Logic -->
    <script>
        // Global State
        let currentActiveView = 'overview';
        let currentWsSubTab = 'blueprints';
        let rawUsers = [];
        let rawWorkspaces = [];
        let rawTemplates = [];
        let pendingDeleteAction = null;

        // View Navigation
        function switchView(viewName) {
            currentActiveView = viewName;
            document.querySelectorAll('section[id^="view-"]').forEach(el => el.classList.add('hidden'));
            const targetSection = document.getElementById('view-' + viewName);
            if (targetSection) targetSection.classList.remove('hidden');

            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            const activeBtn = document.getElementById('navBtn-' + viewName);
            if (activeBtn) activeBtn.classList.add('active');

            // Header titles
            const titles = {
                overview: ['Overview & Metrics', 'Real-time health, user analytics, and workspace telemetry'],
                workspaces: ['Workspace Management & Blueprints', 'Design, upload, and deploy pre-built AI workspaces for users'],
                users: ['User Management', 'Manage user credentials, assign admin roles, and inspect client activity'],
                system: ['AI Engine & System Diagnostics', 'Gemini AI connection benchmark, token usage, and system specifications']
            };
            if (titles[viewName]) {
                document.getElementById('viewHeaderTitle').innerText = titles[viewName][0];
                document.getElementById('viewHeaderSubtitle').innerText = titles[viewName][1];
            }

            // Close mobile sidebar if open
            const sidebar = document.getElementById('dashboardSidebar');
            if (!sidebar.classList.contains('-translate-x-full')) {
                toggleMobileSidebar();
            }

            // Lazy load data for views
            if (viewName === 'overview') loadOverviewStats();
            if (viewName === 'workspaces') {
                loadTemplates();
                loadWorkspaces();
            }
            if (viewName === 'users') loadUsers();
            if (viewName === 'system') loadSystemInfo();

            lucide.createIcons();
        }

        // Sub-Tab Switcher for Workspaces (Blueprints vs Instances)
        function switchWsSubTab(tab) {
            currentWsSubTab = tab;
            const btnBp = document.getElementById('wsTabBtn-blueprints');
            const btnIns = document.getElementById('wsTabBtn-instances');
            const viewBp = document.getElementById('wsSubView-blueprints');
            const viewIns = document.getElementById('wsSubView-instances');
            const actions = document.getElementById('wsSubTabActions');

            if (tab === 'blueprints') {
                btnBp.className = 'px-4 py-2 text-xs font-bold rounded-xl transition-all shadow-sm bg-[#173d34] text-white flex items-center space-x-2';
                btnIns.className = 'px-4 py-2 text-xs font-semibold rounded-xl transition-all text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] flex items-center space-x-2';
                viewBp.classList.remove('hidden');
                viewIns.classList.add('hidden');
                actions.classList.remove('hidden');
                loadTemplates();
            } else {
                btnIns.className = 'px-4 py-2 text-xs font-bold rounded-xl transition-all shadow-sm bg-[#173d34] text-white flex items-center space-x-2';
                btnBp.className = 'px-4 py-2 text-xs font-semibold rounded-xl transition-all text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] flex items-center space-x-2';
                viewIns.classList.remove('hidden');
                viewBp.classList.add('hidden');
                actions.classList.add('hidden');
                loadWorkspaces();
            }
            lucide.createIcons();
        }

        function toggleMobileSidebar() {
            const sidebar = document.getElementById('dashboardSidebar');
            const overlay = document.getElementById('mobileDrawerOverlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
            lucide.createIcons();
        }

        function refreshCurrentView() {
            loadOverviewStats();
            loadWorkspaces();
            loadUsers();
            loadSystemInfo();
            showToast('Refreshed data across all panels', 'info');
        }

        // ==========================================
        // 1. OVERVIEW DATA LOADER
        // ==========================================
        async function loadOverviewStats() {
            try {
                const res = await fetch('api/admin.php?action=stats');
                const data = await res.json();
                if (!data.success) return showToast(data.error || 'Failed to fetch stats', 'error');

                const stats = data.stats;
                document.getElementById('stat-total-users').innerText = stats.users.total;
                document.getElementById('stat-admin-users').innerText = stats.users.admins + ' Admins';
                document.getElementById('stat-reg-users').innerText = stats.users.regular + ' Standard';

                document.getElementById('stat-total-workspaces').innerText = stats.workspaces.total;
                document.getElementById('stat-total-executives').innerText = stats.executives.total;

                document.getElementById('stat-total-tasks').innerText = stats.tasks.total;
                document.getElementById('stat-completed-tasks').innerText = stats.tasks.completed + ' Done';
                document.getElementById('stat-pending-tasks').innerText = stats.tasks.pending + ' Pending';

                document.getElementById('stat-total-sessions').innerText = stats.conversations.sessions + ' Sessions';
                document.getElementById('stat-total-messages').innerText = stats.conversations.messages + ' messages exchanged';

                document.getElementById('stat-active-model').innerText = stats.ai_status.active_model;
                document.getElementById('badge-ai-model').innerText = stats.ai_status.active_model;
                
                const totalTokens = stats.tokens ? (stats.tokens.total_tokens || 0) : 0;
                const todayTokens = stats.tokens ? (stats.tokens.today_tokens || 0) : 0;
                document.getElementById('stat-total-tokens').innerText = Number(totalTokens).toLocaleString() + ' T';
                document.getElementById('stat-today-tokens').innerText = Number(todayTokens).toLocaleString();

                document.getElementById('badge-workspaces-count').innerText = stats.workspaces.total;
                document.getElementById('badge-users-count').innerText = stats.users.total;

                // Render Recent Workspaces
                const wsContainer = document.getElementById('recentWorkspacesContainer');
                if (data.recent_workspaces && data.recent_workspaces.length > 0) {
                    wsContainer.innerHTML = data.recent_workspaces.map(w => `
                        <div class="p-2.5 rounded-2xl bg-[#fcfaf5] hover:bg-[#f5f2e9] border border-[#ddd8cc] flex items-center justify-between transition-colors">
                            <div class="flex items-center space-x-2.5 min-w-0">
                                <div class="w-8 h-8 rounded-xl bg-[#173d34]/10 text-[#173d34] flex items-center justify-center font-bold flex-shrink-0">
                                    <i data-lucide="${w.icon || 'briefcase'}" class="w-4 h-4"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-[#202725] truncate">${escapeHtml(w.name)}</div>
                                    <div class="text-[10px] text-[#68716d] truncate">Owner: <span class="font-semibold text-[#173d34]">${escapeHtml(w.owner_name || 'System')}</span> &bull; ${escapeHtml(w.domain)}</div>
                                </div>
                            </div>
                            <button onclick="inspectWorkspace(${w.id})" class="px-2.5 py-1 text-[11px] font-bold text-[#173d34] bg-white hover:bg-[#173d34] hover:text-white border border-[#ddd8cc] rounded-lg transition-all flex items-center space-x-1 shadow-2xs">
                                <span>Inspect</span>
                            </button>
                        </div>
                    `).join('');
                } else {
                    wsContainer.innerHTML = '<div class="text-xs text-[#68716d] text-center py-4">No workspaces found.</div>';
                }

                // Render Recent Users
                const uContainer = document.getElementById('recentUsersContainer');
                if (data.recent_users && data.recent_users.length > 0) {
                    uContainer.innerHTML = data.recent_users.map(u => `
                        <div class="p-2.5 rounded-2xl bg-[#fcfaf5] hover:bg-[#f5f2e9] border border-[#ddd8cc] flex items-center justify-between transition-colors">
                            <div class="flex items-center space-x-2.5 min-w-0">
                                <img src="${u.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(u.name)}" class="w-8 h-8 rounded-xl object-cover border border-[#ddd8cc] flex-shrink-0">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-[#202725] truncate">${escapeHtml(u.name)}</div>
                                    <div class="text-[10px] text-[#68716d] truncate">@${escapeHtml(u.username)} &bull; ${escapeHtml(u.email)}</div>
                                </div>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-bold uppercase ${u.role === 'admin' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-700 border border-slate-300'}">
                                ${u.role}
                            </span>
                        </div>
                    `).join('');
                } else {
                    uContainer.innerHTML = '<div class="text-xs text-[#68716d] text-center py-4">No users found.</div>';
                }

                lucide.createIcons();
            } catch (err) {
                console.error(err);
            }
        }

        // ==========================================
        // 2. WORKSPACE BLUEPRINTS (DEV TEAM) & INSTANCES
        // ==========================================
        async function loadTemplates() {
            try {
                const res = await fetch('api/admin.php?action=templates');
                const data = await res.json();
                if (!data.success) return showToast(data.error || 'Failed to fetch blueprints', 'error');

                rawTemplates = data.templates || [];
                const bBadge = document.getElementById('badge-blueprints-count');
                if (bBadge) bBadge.innerText = rawTemplates.length;

                populateTemplateDropdowns();
                renderTemplatesTable(rawTemplates);
            } catch (err) {
                console.error(err);
            }
        }

        function renderTemplatesTable(list) {
            const tbody = document.getElementById('blueprintsTableBody');
            if (!tbody) return;

            if (!list || list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="p-8 text-center text-[#68716d]">No workspace blueprints found. Click "Upload Blueprint" or "Create Blueprint" to add one.</td></tr>`;
                return;
            }

            tbody.innerHTML = list.map(t => {
                const isLive = parseInt(t.is_active) === 1;
                return `
                <tr class="hover:bg-[#fcfaf5] transition-colors">
                    <td class="p-3.5">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-xl bg-${t.color || 'indigo'}-50 text-${t.color || 'indigo'}-700 border border-${t.color || 'indigo'}-200 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="${t.icon || 'briefcase'}" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-[#202725] text-xs flex items-center gap-1.5">
                                    <span class="truncate">${escapeHtml(t.title)}</span>
                                    <span class="text-[9px] bg-slate-100 text-slate-700 px-1.5 py-0.2 rounded font-mono font-semibold">${escapeHtml(t.badge || 'STARTER')}</span>
                                </div>
                                <div class="text-[10px] text-[#68716d] font-mono">${escapeHtml(t.domain)} &bull; <span class="text-[#8c5e1e]">/${escapeHtml(t.slug)}</span></div>
                            </div>
                        </div>
                    </td>
                    <td class="p-3.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-mono font-bold text-[10px] uppercase ${t.tier === 'paid' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300'}">
                            ${t.tier === 'paid' ? '$' + Number(t.price).toFixed(2) + ' ' + (t.currency || 'USD') : 'FREE'}
                        </span>
                    </td>
                    <td class="p-3.5 text-center font-mono font-bold text-[#173d34]">
                        ${t.team_count || 0} Agents
                    </td>
                    <td class="p-3.5 text-center font-mono text-xs text-[#202725]">
                        ${t.projects_count || 0} Roadmaps &bull; ${t.tasks_count || 0} SOPs
                    </td>
                    <td class="p-3.5 text-center">
                        <button onclick="toggleTemplateStatus(${t.id})" class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full font-mono font-bold text-[10px] uppercase transition-all shadow-2xs ${
                            isLive ? 'bg-emerald-50 text-emerald-700 border border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border border-slate-300 hover:bg-slate-200'
                        }" title="Click to toggle publish status">
                            <span class="w-1.5 h-1.5 rounded-full ${isLive ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'}"></span>
                            <span>${isLive ? 'Live in Store' : 'Draft'}</span>
                        </button>
                    </td>
                    <td class="p-3.5 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-mono font-bold text-[11px] bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20">
                            ${t.adoption_count || 0} Clients
                        </span>
                    </td>
                    <td class="p-3.5 text-right">
                        <div class="flex items-center justify-end space-x-1">
                            <button onclick="openDeployTemplateModal(${t.id})" class="p-1.5 text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors" title="Deploy Workspace to Client User">
                                <i data-lucide="send" class="w-4 h-4"></i>
                            </button>
                            <button onclick="exportTemplateBlueprint(${t.id})" class="p-1.5 text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors" title="Export as JSON Blueprint">
                                <i data-lucide="download" class="w-4 h-4"></i>
                            </button>
                            <button onclick="inspectTemplateDetails(${t.id})" class="p-1.5 text-[#173d34] hover:bg-[#173d34]/10 rounded-lg transition-colors" title="Inspect Full Dream Team & Roadmaps">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button onclick="openEditBlueprintModal(${t.id})" class="p-1.5 text-amber-700 hover:bg-amber-50 rounded-lg transition-colors" title="Edit Blueprint">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button onclick="confirmDeleteTemplate(${t.id}, '${escapeHtml(t.title)}')" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Delete Blueprint">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                `;
            }).join('');

            lucide.createIcons();
        }

        function filterBlueprints() {
            const q = document.getElementById('blueprintSearchInput').value.toLowerCase().trim();
            const tier = document.getElementById('blueprintTierFilter').value;
            const status = document.getElementById('blueprintStatusFilter').value;

            const filtered = rawTemplates.filter(t => {
                const matchQ = !q || (t.title && t.title.toLowerCase().includes(q)) ||
                               (t.domain && t.domain.toLowerCase().includes(q)) ||
                               (t.slug && t.slug.toLowerCase().includes(q));
                const matchTier = tier === 'all' || t.tier === tier;
                const matchStatus = status === 'all' || String(t.is_active) === status;
                return matchQ && matchTier && matchStatus;
            });

            renderTemplatesTable(filtered);
        }

        function populateTemplateDropdowns() {
            const deploySelect = document.getElementById('deployTemplateId');
            if (deploySelect) {
                deploySelect.innerHTML = rawTemplates.map(t => `<option value="${t.id}">${escapeHtml(t.title)} (${escapeHtml(t.domain)})</option>`).join('');
            }
        }

        // Blueprint Upload Modal
        function openUploadBlueprintModal() {
            document.getElementById('formUploadBlueprint').reset();
            document.getElementById('blueprintFileLabel').innerText = 'Click or drag a .json blueprint file';
            document.getElementById('blueprintPreviewBox').classList.add('hidden');
            document.getElementById('modalUploadBlueprint').classList.remove('hidden');
            lucide.createIcons();
        }

        function handleBlueprintFileSelected(e) {
            const file = e.target.files[0];
            if (!file) return;

            document.getElementById('blueprintFileLabel').innerText = file.name;
            const reader = new FileReader();
            reader.onload = function(evt) {
                document.getElementById('blueprintJsonInput').value = evt.target.result;
                previewBlueprintJson();
            };
            reader.readAsText(file);
        }

        function previewBlueprintJson() {
            const raw = document.getElementById('blueprintJsonInput').value.trim();
            const previewBox = document.getElementById('blueprintPreviewBox');
            if (!raw) {
                previewBox.classList.add('hidden');
                return;
            }

            try {
                const data = JSON.parse(raw);
                if (!data.title) {
                    previewBox.classList.add('hidden');
                    return;
                }

                document.getElementById('previewBpTitle').innerText = data.title;
                document.getElementById('previewBpDomain').innerText = data.domain || 'General Operations';
                
                const execs = data.executives || data.executives_json || [];
                const projs = data.projects || data.projects_json || [];
                const tasks = data.tasks || data.tasks_json || [];

                document.getElementById('previewBpCounts').innerText = `${execs.length} AI Executives • ${projs.length} Roadmaps • ${tasks.length} Deliverables`;
                previewBox.classList.remove('hidden');
            } catch (err) {
                previewBox.classList.add('hidden');
            }
        }

        async function submitUploadBlueprint(e) {
            e.preventDefault();
            const raw = document.getElementById('blueprintJsonInput').value.trim();
            if (!raw) return showToast('Please select a JSON blueprint file or paste JSON code.', 'error');

            let parsed = null;
            try {
                parsed = JSON.parse(raw);
            } catch (err) {
                return showToast('Invalid JSON syntax. Please verify and try again.', 'error');
            }

            const btn = document.getElementById('btnSubmitUploadBp');
            btn.disabled = true;

            try {
                const payload = {
                    action: 'upload_blueprint',
                    ...parsed
                };

                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    showToast(data.message || 'Blueprint uploaded and published!', 'success');
                    closeModal('modalUploadBlueprint');
                    loadTemplates();
                    loadOverviewStats();
                } else {
                    showToast(data.error || 'Failed to upload blueprint', 'error');
                }
            } catch (err) {
                showToast('Error uploading blueprint', 'error');
            } finally {
                btn.disabled = false;
            }
        }

        // Blueprint Studio (Create / Edit)
        function openCreateBlueprintModal() {
            document.getElementById('formStudioBlueprint').reset();
            document.getElementById('studioBpId').value = '';
            document.getElementById('studioModalTitle').innerText = 'Create Workspace Blueprint';
            document.getElementById('modalStudioBlueprint').classList.remove('hidden');
            lucide.createIcons();
        }

        function openEditBlueprintModal(id) {
            const t = rawTemplates.find(item => item.id == id);
            if (!t) return;

            document.getElementById('studioBpId').value = t.id;
            document.getElementById('studioBpTitle').value = t.title;
            document.getElementById('studioBpSlug').value = t.slug;
            document.getElementById('studioBpDomain').value = t.domain;
            document.getElementById('studioBpBadge').value = t.badge || 'DEV STARTER';
            document.getElementById('studioBpDescription').value = t.description || '';
            document.getElementById('studioBpTier').value = t.tier || 'free';
            document.getElementById('studioBpPrice').value = t.price || '0.00';
            document.getElementById('studioBpIcon').value = t.icon || 'briefcase';
            document.getElementById('studioBpColor').value = t.color || 'indigo';
            document.getElementById('studioBpAutoTeam').checked = false;

            document.getElementById('studioModalTitle').innerText = 'Edit Workspace Blueprint';
            document.getElementById('modalStudioBlueprint').classList.remove('hidden');
            lucide.createIcons();
        }

        async function submitBlueprintStudio(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitStudioBp');
            btn.disabled = true;

            try {
                const payload = {
                    action: 'upload_blueprint',
                    id: document.getElementById('studioBpId').value,
                    title: document.getElementById('studioBpTitle').value.trim(),
                    slug: document.getElementById('studioBpSlug').value.trim(),
                    domain: document.getElementById('studioBpDomain').value.trim(),
                    badge: document.getElementById('studioBpBadge').value.trim(),
                    description: document.getElementById('studioBpDescription').value.trim(),
                    tier: document.getElementById('studioBpTier').value,
                    price: parseFloat(document.getElementById('studioBpPrice').value) || 0.00,
                    icon: document.getElementById('studioBpIcon').value,
                    color: document.getElementById('studioBpColor').value,
                    auto_generate_team: document.getElementById('studioBpAutoTeam').checked
                };

                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    showToast(data.message || 'Blueprint saved successfully!', 'success');
                    closeModal('modalStudioBlueprint');
                    loadTemplates();
                    loadOverviewStats();
                } else {
                    showToast(data.error || 'Failed to save blueprint', 'error');
                }
            } catch (err) {
                showToast('Error saving blueprint', 'error');
            } finally {
                btn.disabled = false;
            }
        }

        async function toggleTemplateStatus(id) {
            try {
                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle_template_status', id: id })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    loadTemplates();
                } else {
                    showToast(data.error || 'Failed to toggle status', 'error');
                }
            } catch (err) {
                showToast('Error toggling blueprint status', 'error');
            }
        }

        function exportTemplateBlueprint(id) {
            window.location.href = `api/admin.php?action=export_template&id=${id}`;
            showToast('Downloading JSON blueprint...', 'info');
        }

        // Deploy to User
        function openDeployTemplateModal(templateId) {
            populateUserFilterDropdowns();
            populateTemplateDropdowns();
            if (templateId) {
                document.getElementById('deployTemplateId').value = templateId;
            }
            document.getElementById('modalDeployTemplate').classList.remove('hidden');
            lucide.createIcons();
        }

        async function submitDeployTemplate(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitDeploy');
            btn.disabled = true;

            try {
                const payload = {
                    action: 'deploy_to_user',
                    template_id: parseInt(document.getElementById('deployTemplateId').value),
                    user_id: parseInt(document.getElementById('deployTargetUserId').value)
                };

                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    showToast(data.message || 'Workspace deployed to client successfully!', 'success');
                    closeModal('modalDeployTemplate');
                    loadWorkspaces();
                    loadTemplates();
                    loadOverviewStats();
                } else {
                    showToast(data.error || 'Failed to deploy workspace', 'error');
                }
            } catch (err) {
                showToast('Error deploying workspace to client', 'error');
            } finally {
                btn.disabled = false;
            }
        }

        // Inspect Blueprint
        async function inspectTemplateDetails(id) {
            try {
                const res = await fetch(`api/admin.php?action=template_details&id=${id}`);
                const data = await res.json();
                if (!data.success) return showToast(data.error || 'Could not load blueprint', 'error');

                const t = data.template;
                document.getElementById('inspectTplTitle').innerText = t.title;
                document.getElementById('inspectTplDomain').innerText = t.domain;
                document.getElementById('inspectTplBadge').innerText = t.badge || 'STARTER';
                document.getElementById('inspectTplPrice').innerText = t.tier === 'paid' ? `$${Number(t.price).toFixed(2)} USD` : 'Free Starter';
                document.getElementById('inspectTplStatus').innerText = parseInt(t.is_active) === 1 ? 'Live in Store' : 'Draft';
                document.getElementById('inspectTplAdoptionsCount').innerText = `${data.adoptions ? data.adoptions.length : 0} Clients`;

                // Render Executives
                const execContainer = document.getElementById('inspectTplExecList');
                if (t.executives && t.executives.length > 0) {
                    execContainer.innerHTML = t.executives.map(ex => `
                        <div class="p-2.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] flex items-center space-x-2.5 shadow-2xs">
                            <img src="${ex.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(ex.name)}" class="w-9 h-9 rounded-xl object-cover border border-[#ddd8cc]">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-[#202725] text-xs truncate">${escapeHtml(ex.name)}</div>
                                <div class="text-[10px] text-[#173d34] font-semibold truncate">${escapeHtml(ex.title)}</div>
                                <div class="text-[9px] text-[#68716d] font-mono truncate">${escapeHtml(ex.department)}</div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    execContainer.innerHTML = '<div class="text-xs text-[#68716d] p-3 text-center col-span-2">No executives configured.</div>';
                }

                // Render Roadmaps
                const projContainer = document.getElementById('inspectTplProjList');
                if (t.projects && t.projects.length > 0) {
                    projContainer.innerHTML = t.projects.map(p => `
                        <div class="p-2 rounded-xl bg-[#ffffff] border border-[#ddd8cc] flex items-center justify-between">
                            <span class="font-bold text-[#202725] text-xs truncate">${escapeHtml(p.name)}</span>
                            <span class="text-[9px] font-mono font-bold px-1.5 py-0.2 rounded bg-indigo-50 text-indigo-700 uppercase">${escapeHtml(p.status || 'active')}</span>
                        </div>
                    `).join('');
                } else {
                    projContainer.innerHTML = '<div class="text-xs text-[#68716d] p-2">None</div>';
                }

                // Render Tasks
                const taskContainer = document.getElementById('inspectTplTaskList');
                if (t.tasks && t.tasks.length > 0) {
                    taskContainer.innerHTML = t.tasks.map(tsk => `
                        <div class="p-2 rounded-xl bg-[#ffffff] border border-[#ddd8cc] flex items-center justify-between">
                            <span class="text-xs text-[#202725] truncate">${escapeHtml(tsk.title)}</span>
                            <span class="text-[9px] font-mono font-bold text-[#68716d]">${escapeHtml(tsk.priority || 'Med')}</span>
                        </div>
                    `).join('');
                } else {
                    taskContainer.innerHTML = '<div class="text-xs text-[#68716d] p-2">None</div>';
                }

                // Render Adoptions
                const adoptContainer = document.getElementById('inspectTplUsersList');
                if (data.adoptions && data.adoptions.length > 0) {
                    adoptContainer.innerHTML = data.adoptions.map(u => `
                        <div class="p-2 rounded-xl bg-[#ffffff] border border-[#ddd8cc] flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <img src="${u.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(u.user_name)}" class="w-6 h-6 rounded-lg object-cover">
                                <span class="font-bold text-[#202725] text-xs">${escapeHtml(u.user_name)} (@${escapeHtml(u.username)})</span>
                            </div>
                            <span class="text-[10px] text-[#68716d] font-mono">${u.created_at ? u.created_at.split(' ')[0] : ''}</span>
                        </div>
                    `).join('');
                } else {
                    adoptContainer.innerHTML = '<div class="text-xs text-[#68716d] p-2">No clients have adopted this workspace yet.</div>';
                }

                document.getElementById('modalInspectTemplate').classList.remove('hidden');
                lucide.createIcons();
            } catch (err) {
                showToast('Failed to inspect blueprint', 'error');
            }
        }

        function confirmDeleteTemplate(id, title) {
            document.getElementById('deleteModalTitle').innerText = 'Delete Workspace Blueprint';
            document.getElementById('deleteModalDesc').innerText = `Are you sure you want to delete blueprint "${title}" from the system catalog? Existing client instances will remain unaffected.`;

            pendingDeleteAction = async () => {
                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_template', id: id })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Blueprint deleted from catalog', 'success');
                    closeModal('modalConfirmDelete');
                    loadTemplates();
                } else {
                    showToast(data.error || 'Failed to delete template', 'error');
                }
            };

            document.getElementById('modalConfirmDelete').classList.remove('hidden');
            lucide.createIcons();
        }

        // ==========================================
        // 2.B CLIENT ACTIVE WORKSPACES (INSTANCES)
        // ==========================================
        async function loadWorkspaces() {
            try {
                const res = await fetch('api/admin.php?action=workspaces');
                const data = await res.json();
                if (!data.success) return showToast(data.error || 'Failed to fetch workspaces', 'error');

                rawWorkspaces = data.workspaces || [];
                const iBadge = document.getElementById('badge-instances-count');
                if (iBadge) iBadge.innerText = rawWorkspaces.length;
                
                const wBadge = document.getElementById('badge-workspaces-count');
                if (wBadge) wBadge.innerText = rawWorkspaces.length;

                populateUserFilterDropdowns();
                renderWorkspacesTable(rawWorkspaces);
            } catch (err) {
                console.error(err);
            }
        }

        function renderWorkspacesTable(list) {
            const tbody = document.getElementById('workspacesTableBody');
            if (!list || list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="p-8 text-center text-[#68716d]">No workspaces match your query.</td></tr>`;
                return;
            }

            tbody.innerHTML = list.map(w => `
                <tr class="hover:bg-[#fcfaf5] transition-colors">
                    <td class="p-3.5">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-xl bg-${w.color || 'indigo'}-50 text-${w.color || 'indigo'}-700 border border-${w.color || 'indigo'}-200 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="${w.icon || 'briefcase'}" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-[#202725] text-xs">${escapeHtml(w.name)}</div>
                                <div class="text-[10px] text-[#68716d] font-mono">${escapeHtml(w.domain || 'General')}</div>
                            </div>
                        </div>
                    </td>
                    <td class="p-3.5">
                        <div class="flex items-center space-x-2">
                            <img src="${w.owner_avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(w.owner_name || 'U')}" class="w-6 h-6 rounded-lg object-cover">
                            <div class="min-w-0">
                                <div class="font-semibold text-[#202725] text-xs truncate">${escapeHtml(w.owner_name || 'Unassigned')}</div>
                                <div class="text-[10px] text-[#68716d] font-mono">@${escapeHtml(w.owner_username || '')}</div>
                            </div>
                        </div>
                    </td>
                    <td class="p-3.5 text-center">
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full font-mono font-bold text-[11px] bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20">
                            ${w.team_count} Agents
                        </span>
                    </td>
                    <td class="p-3.5 text-center font-mono font-bold text-[#202725]">
                        ${w.projects_count}
                    </td>
                    <td class="p-3.5 text-center">
                        <span class="font-mono text-xs font-bold ${w.open_tasks_count > 0 ? 'text-amber-700' : 'text-[#68716d]'}">
                            ${w.open_tasks_count} open / ${w.tasks_count}
                        </span>
                    </td>
                    <td class="p-3.5 text-[#68716d] font-mono text-[11px]">
                        ${w.created_at ? w.created_at.split(' ')[0] : '-'}
                    </td>
                    <td class="p-3.5 text-right">
                        <div class="flex items-center justify-end space-x-1">
                            <button onclick="inspectWorkspace(${w.id})" class="p-1.5 text-[#173d34] hover:bg-[#173d34]/10 rounded-lg transition-colors" title="View Team & Details">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button onclick="openEditWorkspaceModal(${w.id})" class="p-1.5 text-amber-700 hover:bg-amber-100/60 rounded-lg transition-colors" title="Edit Workspace">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button onclick="confirmDeleteWorkspace(${w.id}, '${escapeHtml(w.name)}')" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Delete Workspace">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            lucide.createIcons();
        }

        function filterWorkspaces() {
            const q = document.getElementById('workspaceSearchInput').value.toLowerCase().trim();
            const ownerId = parseInt(document.getElementById('workspaceOwnerFilter').value) || 0;

            const filtered = rawWorkspaces.filter(w => {
                const matchQuery = !q || (w.name && w.name.toLowerCase().includes(q)) ||
                                   (w.domain && w.domain.toLowerCase().includes(q)) ||
                                   (w.owner_name && w.owner_name.toLowerCase().includes(q));
                const matchOwner = ownerId === 0 || parseInt(w.user_id) === ownerId;
                return matchQuery && matchOwner;
            });

            renderWorkspacesTable(filtered);
        }

        function populateUserFilterDropdowns() {
            const ownerFilter = document.getElementById('workspaceOwnerFilter');
            const newWsOwner = document.getElementById('newWsUserId');
            const editWsOwner = document.getElementById('editWsUserId');

            const optionsHtml = rawUsers.map(u => `<option value="${u.id}">${escapeHtml(u.name)} (@${escapeHtml(u.username)})</option>`).join('');

            if (newWsOwner) newWsOwner.innerHTML = optionsHtml;
            if (editWsOwner) editWsOwner.innerHTML = optionsHtml;

            if (ownerFilter) {
                const curVal = ownerFilter.value;
                ownerFilter.innerHTML = '<option value="0">All Owners</option>' + optionsHtml;
                ownerFilter.value = curVal;
            }
        }

        function openNewWorkspaceModal() {
            document.getElementById('formNewWorkspace').reset();
            document.getElementById('modalNewWorkspace').classList.remove('hidden');
            lucide.createIcons();
        }

        async function submitNewWorkspace(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitNewWs');
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader" class="w-4 h-4 animate-spin"></i><span>Provisioning...</span>`;
            lucide.createIcons();

            try {
                const payload = {
                    action: 'create_workspace',
                    user_id: parseInt(document.getElementById('newWsUserId').value),
                    name: document.getElementById('newWsName').value.trim(),
                    domain: document.getElementById('newWsDomain').value.trim(),
                    description: document.getElementById('newWsDescription').value.trim(),
                    icon: document.getElementById('newWsIcon').value,
                    color: document.getElementById('newWsColor').value,
                    auto_generate_team: document.getElementById('newWsAutoTeam').checked
                };

                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    showToast(data.message || 'Workspace created successfully!', 'success');
                    closeModal('modalNewWorkspace');
                    loadWorkspaces();
                    loadOverviewStats();
                } else {
                    showToast(data.error || 'Failed to create workspace', 'error');
                }
            } catch (err) {
                showToast('An error occurred while creating workspace', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<i data-lucide="check" class="w-4 h-4"></i><span>Create Workspace</span>`;
                lucide.createIcons();
            }
        }

        function openEditWorkspaceModal(id) {
            const ws = rawWorkspaces.find(w => w.id == id);
            if (!ws) return;

            document.getElementById('editWsId').value = ws.id;
            document.getElementById('editWsUserId').value = ws.user_id;
            document.getElementById('editWsName').value = ws.name;
            document.getElementById('editWsDomain').value = ws.domain || '';
            document.getElementById('editWsDescription').value = ws.description || '';
            document.getElementById('editWsIcon').value = ws.icon || 'briefcase';
            document.getElementById('editWsColor').value = ws.color || 'indigo';

            document.getElementById('modalEditWorkspace').classList.remove('hidden');
            lucide.createIcons();
        }

        async function submitEditWorkspace(e) {
            e.preventDefault();
            try {
                const payload = {
                    action: 'update_workspace',
                    id: parseInt(document.getElementById('editWsId').value),
                    user_id: parseInt(document.getElementById('editWsUserId').value),
                    name: document.getElementById('editWsName').value.trim(),
                    domain: document.getElementById('editWsDomain').value.trim(),
                    description: document.getElementById('editWsDescription').value.trim(),
                    icon: document.getElementById('editWsIcon').value,
                    color: document.getElementById('editWsColor').value
                };

                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    showToast('Workspace updated successfully!', 'success');
                    closeModal('modalEditWorkspace');
                    loadWorkspaces();
                    loadOverviewStats();
                } else {
                    showToast(data.error || 'Failed to update workspace', 'error');
                }
            } catch (err) {
                showToast('Network error while updating workspace', 'error');
            }
        }

        function confirmDeleteWorkspace(id, name) {
            document.getElementById('deleteModalTitle').innerText = 'Delete Workspace';
            document.getElementById('deleteModalDesc').innerText = `Are you sure you want to delete "${name}"? If this is the user's only workspace, creation of a fallback workspace is required.`;
            
            pendingDeleteAction = async () => {
                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_workspace', id: id })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message || 'Workspace deleted successfully', 'success');
                    closeModal('modalConfirmDelete');
                    loadWorkspaces();
                    loadOverviewStats();
                } else {
                    showToast(data.error || 'Could not delete workspace', 'error');
                }
            };

            document.getElementById('modalConfirmDelete').classList.remove('hidden');
            lucide.createIcons();
        }

        async function inspectWorkspace(id) {
            try {
                const res = await fetch(`api/admin.php?action=workspace_details&id=${id}`);
                const data = await res.json();
                if (!data.success) return showToast(data.error || 'Could not load details', 'error');

                const ws = data.workspace;
                document.getElementById('viewWsTitle').innerText = ws.name;
                document.getElementById('viewWsDomain').innerText = ws.domain || 'General Operations';
                document.getElementById('viewWsOwner').innerText = `${ws.owner_name} (@${ws.owner_username})`;
                document.getElementById('viewWsCreated').innerText = ws.created_at ? ws.created_at.split(' ')[0] : '-';
                document.getElementById('viewWsTeamSize').innerText = (data.executives ? data.executives.length : 0) + ' Members';

                // Render Executives
                const execContainer = document.getElementById('viewWsExecList');
                if (data.executives && data.executives.length > 0) {
                    execContainer.innerHTML = data.executives.map(ex => `
                        <div class="p-2.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] flex items-center space-x-2.5 shadow-2xs">
                            <img src="${ex.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(ex.name)}" class="w-9 h-9 rounded-xl object-cover border border-[#ddd8cc]">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-[#202725] text-xs truncate">${escapeHtml(ex.name)}</div>
                                <div class="text-[10px] text-[#173d34] font-semibold truncate">${escapeHtml(ex.title)}</div>
                                <div class="text-[9px] text-[#68716d] font-mono truncate">${escapeHtml(ex.department)}</div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    execContainer.innerHTML = '<div class="text-xs text-[#68716d] p-3 text-center col-span-2">No executives currently in this workspace.</div>';
                }

                // Render Projects
                const projContainer = document.getElementById('viewWsProjList');
                if (data.projects && data.projects.length > 0) {
                    projContainer.innerHTML = data.projects.map(pr => `
                        <div class="p-2.5 rounded-xl bg-[#ffffff] border border-[#ddd8cc] flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="${pr.icon || 'folder-kanban'}" class="w-4 h-4 text-[#173d34]"></i>
                                <span class="font-bold text-[#202725] text-xs">${escapeHtml(pr.name)}</span>
                            </div>
                            <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 uppercase">${escapeHtml(pr.status)}</span>
                        </div>
                    `).join('');
                } else {
                    projContainer.innerHTML = '<div class="text-xs text-[#68716d] p-3 text-center">No projects assigned.</div>';
                }

                document.getElementById('modalViewWorkspace').classList.remove('hidden');
                lucide.createIcons();
            } catch (err) {
                showToast('Failed to inspect workspace', 'error');
            }
        }

        // ==========================================
        // 3. USERS MANAGEMENT
        // ==========================================
        async function loadUsers() {
            try {
                const res = await fetch('api/admin.php?action=users');
                const data = await res.json();
                if (!data.success) return showToast(data.error || 'Failed to fetch users', 'error');

                rawUsers = data.users || [];
                document.getElementById('badge-users-count').innerText = rawUsers.length;
                populateUserFilterDropdowns();
                renderUsersTable(rawUsers);
            } catch (err) {
                console.error(err);
            }
        }

        function renderUsersTable(list) {
            const tbody = document.getElementById('usersTableBody');
            if (!list || list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="p-8 text-center text-[#68716d]">No user accounts match your criteria.</td></tr>`;
                return;
            }

            tbody.innerHTML = list.map(u => `
                <tr class="hover:bg-[#fcfaf5] transition-colors">
                    <td class="p-3.5">
                        <div class="flex items-center space-x-2.5">
                            <img src="${u.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(u.name)}" class="w-8 h-8 rounded-xl object-cover border border-[#ddd8cc]">
                            <div class="min-w-0">
                                <div class="font-bold text-[#202725] text-xs">${escapeHtml(u.name)}</div>
                                <div class="text-[10px] text-[#68716d] font-mono">@${escapeHtml(u.username)}</div>
                            </div>
                        </div>
                    </td>
                    <td class="p-3.5 text-[#202725] font-mono text-xs">
                        ${escapeHtml(u.email)}
                    </td>
                    <td class="p-3.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-mono font-bold text-[10px] uppercase ${u.role === 'admin' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-700 border border-slate-300'}">
                            ${u.role}
                        </span>
                    </td>
                    <td class="p-3.5 text-center font-mono font-bold text-[#173d34]">
                        ${u.workspaces_count}
                    </td>
                    <td class="p-3.5 text-center font-mono text-xs text-[#68716d]">
                        ${u.sessions_count} chats / ${u.tasks_count} tasks
                    </td>
                    <td class="p-3.5 text-[#68716d] font-mono text-[11px]">
                        ${u.created_at ? u.created_at.split(' ')[0] : '-'}
                    </td>
                    <td class="p-3.5 text-[#68716d] font-mono text-[11px]">
                        ${u.last_login ? u.last_login.split(' ')[0] : 'Never'}
                    </td>
                    <td class="p-3.5 text-right">
                        <div class="flex items-center justify-end space-x-1">
                            <button onclick="toggleUserRole(${u.id}, '${u.role === 'admin' ? 'user' : 'admin'}')" class="p-1.5 ${u.role === 'admin' ? 'text-slate-600 hover:bg-slate-100' : 'text-emerald-700 hover:bg-emerald-50'} rounded-lg transition-colors" title="${u.role === 'admin' ? 'Demote to Standard User' : 'Promote to Admin'}">
                                <i data-lucide="${u.role === 'admin' ? 'shield-minus' : 'shield-check'}" class="w-4 h-4"></i>
                            </button>
                            <button onclick="openEditUserModal(${u.id})" class="p-1.5 text-amber-700 hover:bg-amber-50 rounded-lg transition-colors" title="Edit User">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button onclick="confirmDeleteUser(${u.id}, '${escapeHtml(u.name)}')" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Delete User">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            lucide.createIcons();
        }

        function filterUsers() {
            const q = document.getElementById('userSearchInput').value.toLowerCase().trim();
            const role = document.getElementById('userRoleFilter').value;

            const filtered = rawUsers.filter(u => {
                const matchQuery = !q || (u.name && u.name.toLowerCase().includes(q)) ||
                                   (u.username && u.username.toLowerCase().includes(q)) ||
                                   (u.email && u.email.toLowerCase().includes(q));
                const matchRole = role === 'all' || u.role === role;
                return matchQuery && matchRole;
            });

            renderUsersTable(filtered);
        }

        function openNewUserModal() {
            document.getElementById('formNewUser').reset();
            document.getElementById('modalNewUser').classList.remove('hidden');
            lucide.createIcons();
        }

        async function submitNewUser(e) {
            e.preventDefault();
            const p1 = document.getElementById('newUserPassword').value;
            const p2 = document.getElementById('newUserPasswordConfirm').value;
            if (p1 !== p2) {
                return showToast('Passwords do not match.', 'error');
            }

            const btn = document.getElementById('btnSubmitNewUser');
            btn.disabled = true;

            try {
                const payload = {
                    action: 'create_user',
                    name: document.getElementById('newUserName').value.trim(),
                    username: document.getElementById('newUserUsername').value.trim(),
                    email: document.getElementById('newUserEmail').value.trim(),
                    role: document.getElementById('newUserRole').value,
                    password: p1,
                    create_workspace: document.getElementById('newUserCreateWs').checked
                };

                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    showToast('User created successfully!', 'success');
                    closeModal('modalNewUser');
                    loadUsers();
                    loadWorkspaces();
                    loadOverviewStats();
                } else {
                    showToast(data.error || 'Failed to create user', 'error');
                }
            } catch (err) {
                showToast('Network error while registering user', 'error');
            } finally {
                btn.disabled = false;
            }
        }

        function openEditUserModal(id) {
            const u = rawUsers.find(item => item.id == id);
            if (!u) return;

            document.getElementById('editUserId').value = u.id;
            document.getElementById('editUserName').value = u.name;
            document.getElementById('editUserUsername').value = u.username;
            document.getElementById('editUserEmail').value = u.email;
            document.getElementById('editUserRole').value = u.role || 'user';
            document.getElementById('editUserPassword').value = '';

            document.getElementById('modalEditUser').classList.remove('hidden');
            lucide.createIcons();
        }

        async function submitEditUser(e) {
            e.preventDefault();
            try {
                const payload = {
                    action: 'update_user',
                    user_id: parseInt(document.getElementById('editUserId').value),
                    name: document.getElementById('editUserName').value.trim(),
                    username: document.getElementById('editUserUsername').value.trim(),
                    email: document.getElementById('editUserEmail').value.trim(),
                    role: document.getElementById('editUserRole').value,
                    password: document.getElementById('editUserPassword').value
                };

                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    showToast('User updated successfully!', 'success');
                    closeModal('modalEditUser');
                    loadUsers();
                    loadOverviewStats();
                } else {
                    showToast(data.error || 'Failed to update user', 'error');
                }
            } catch (err) {
                showToast('Network error while updating user', 'error');
            }
        }

        async function toggleUserRole(userId, newRole) {
            try {
                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle_role', user_id: userId, role: newRole })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message || 'User role updated', 'success');
                    loadUsers();
                    loadOverviewStats();
                } else {
                    showToast(data.error || 'Failed to toggle role', 'error');
                }
            } catch (err) {
                showToast('Error modifying role', 'error');
            }
        }

        function confirmDeleteUser(id, name) {
            document.getElementById('deleteModalTitle').innerText = 'Delete User Account';
            document.getElementById('deleteModalDesc').innerText = `Are you sure you want to permanently delete user "${name}"? All associated workspaces, AI discussions, and notes will be completely purged.`;

            pendingDeleteAction = async () => {
                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_user', user_id: id })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('User and associated data removed', 'success');
                    closeModal('modalConfirmDelete');
                    loadUsers();
                    loadWorkspaces();
                    loadOverviewStats();
                } else {
                    showToast(data.error || 'Failed to delete user', 'error');
                }
            };

            document.getElementById('modalConfirmDelete').classList.remove('hidden');
            lucide.createIcons();
        }

        // ==========================================
        // 4. SYSTEM & AI DIAGNOSTICS
        // ==========================================
        async function loadSystemInfo() {
            try {
                const res = await fetch('api/admin.php?action=system_info');
                const data = await res.json();
                if (!data.success) return;

                document.getElementById('systemMaskedKey').innerText = data.ai.masked_api_key || 'Not configured';
                document.getElementById('systemActiveModel').innerText = data.ai.active_model;

                document.getElementById('sysPhpVersion').innerText = data.system.php_version;
                document.getElementById('sysDbDriver').innerText = (data.system.db_driver || 'mysql').toUpperCase() + ' (' + data.system.db_version.substring(0, 15) + ')';
                document.getElementById('sysUploadSize').innerText = data.system.max_upload_size;
                document.getElementById('sysMemory').innerText = data.system.memory_limit;

                // Render Tables Grid
                const dbGrid = document.getElementById('dbTablesGrid');
                if (data.table_counts) {
                    dbGrid.innerHTML = Object.entries(data.table_counts).map(([tbl, cnt]) => `
                        <div class="p-2 rounded-xl bg-[#f5f2e9] border border-[#ddd8cc]">
                            <span class="text-[#68716d] block truncate">${tbl}</span>
                            <span class="font-bold text-[#173d34] text-xs">${cnt} rows</span>
                        </div>
                    `).join('');
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function pingGeminiAI() {
            const resBox = document.getElementById('pingResultBox');
            resBox.classList.remove('hidden');
            resBox.innerHTML = '<span class="text-amber-700 animate-pulse">Pinging Gemini AI model candidate...</span>';

            try {
                const res = await fetch('api/admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'test_ai' })
                });
                const data = await res.json();

                if (data.success) {
                    resBox.innerHTML = `
                        <div class="text-emerald-700 font-bold mb-1">Status: OK &bull; Response Time: ${data.latency_ms}ms &bull; Model: ${data.model}</div>
                        <div class="text-[#202725] bg-slate-50 p-2 rounded-lg border border-[#ddd8cc]">${escapeHtml(data.reply)}</div>
                    `;
                    showToast(`Gemini responded in ${data.latency_ms}ms!`, 'success');
                } else {
                    resBox.innerHTML = `<span class="text-rose-600 font-bold">Error: ${escapeHtml(data.error || 'Ping failed')}</span>`;
                    showToast('Ping failed: ' + (data.error || ''), 'error');
                }
            } catch (err) {
                resBox.innerHTML = '<span class="text-rose-600">Connection error while testing AI API.</span>';
            }
        }

        // ==========================================
        // HELPERS & TOASTS
        // ==========================================
        function closeModal(modalId) {
            const m = document.getElementById(modalId);
            if (m) m.classList.add('hidden');
        }

        document.getElementById('btnConfirmDeleteAction').addEventListener('click', () => {
            if (pendingDeleteAction) pendingDeleteAction();
        });

        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `p-3 rounded-2xl shadow-xl border text-xs font-bold pointer-events-auto transition-all flex items-center space-x-2 animate-in slide-in-from-bottom-3 duration-200 ${
                type === 'success' ? 'bg-[#173d34] text-white border-emerald-600' :
                type === 'error' ? 'bg-rose-600 text-white border-rose-700' :
                'bg-white text-[#202725] border-[#ddd8cc]'
            }`;

            const iconName = type === 'success' ? 'check-circle' : type === 'error' ? 'alert-triangle' : 'info';
            toast.innerHTML = `<i data-lucide="${iconName}" class="w-4 h-4 flex-shrink-0"></i><span>${escapeHtml(message)}</span>`;
            container.appendChild(toast);
            lucide.createIcons();

            setTimeout(() => {
                toast.classList.add('opacity-0', 'scale-95');
                setTimeout(() => toast.remove(), 200);
            }, 3500);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            loadOverviewStats();
            loadTemplates();
            loadWorkspaces();
            loadUsers();
            loadSystemInfo();
        });
    </script>
</body>
</html>
