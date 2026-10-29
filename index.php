<?php
require_once __DIR__ . '/config.php';
requireLogin();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>RuXt AI · Your AI agents, working together</title>
    
    <!-- PWA Settings -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#fcfaf5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">

    <!-- Google Fonts: DM Sans & JetBrains Mono (Official RuXt Typography) -->
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
                        sans: ['"DM Sans"', 'system-ui', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', 'sans-serif'],
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
                        ruxtSage: '#799a87',
                        // Compatibility aliases for existing classes
                        darkBg: '#fcfaf5',
                        panelBg: '#f5f2e9',
                        cardBg: '#ffffff',
                        borderLight: '#ddd8cc',
                        goldAccent: '#173d34'
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Marked JS (Markdown) & DOMPurify (Sanitizer) -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.1.6/purify.min.js"></script>

    <!-- Highlight.js for Code Highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

    <!-- Custom CSS Design System (RuXt Official Light Theme) -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="bg-[#fcfaf5] text-[#202725] flex h-screen h-[100dvh] w-screen overflow-hidden select-none font-sans">

    <!-- Mobile Drawer Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-[#202725]/50 backdrop-blur-sm z-30 hidden md:hidden"></div>

    <!-- Left Sidebar: Clean, Modern RuXt Light Navigation -->
    <aside id="sidebar" class="sidebar-transition fixed md:static inset-y-0 left-0 z-40 w-[84vw] max-w-xs md:w-80 bg-[#f5f2e9] border-r border-[#ddd8cc] flex flex-col justify-between -translate-x-full md:translate-x-0 h-full">
        
        <!-- Sidebar Top Area -->
        <div class="p-4 border-b border-[#ddd8cc] bg-[#f0ede4]">
            <!-- App Brand & Mobile Close -->
            <div class="flex items-center justify-between mb-3.5">
                <div class="flex items-center space-x-3 cursor-pointer group" id="sidebarBrandContainer" title="RuXt AI - Private AI Assistant">
                    <!-- RuXt Official Signature Brand Mark -->
                    <div class="w-9 h-9 rounded-xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center -rotate-3 shadow-md shadow-[#173d34]/20 group-hover:rotate-0 transition-transform flex-shrink-0">
                        <svg class="w-5 h-5" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 9h13.5a8.5 8.5 0 0 1 0 17H9z"/>
                            <path d="M9 26l10 7"/>
                            <path d="M17 17h14"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="text-base font-extrabold tracking-tight text-[#173d34] font-sans">RuXt</span>
                            <span class="text-[9px] bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20 px-1.5 py-0.5 rounded-full font-bold uppercase tracking-wider">AI ASSISTANT</span>
                        </div>
                        <p class="text-[11px] text-[#68716d] font-medium truncate max-w-[160px]" id="sidebarCompanyName">Enterprises</p>
                    </div>
                </div>
                <button id="closeSidebarBtn" class="md:hidden text-[#68716d] hover:text-[#173d34] p-1.5 rounded-xl hover:bg-[#e8e3d9] transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Workspace / Active Venture Pill Dropdown -->
            <div class="relative mb-3">
                <div class="flex items-center justify-between mb-1.5 px-0.5">
                    <span class="kicker flex items-center gap-1">
                        <i data-lucide="layers" class="w-3 h-3 text-[#173d34]"></i> Workspace
                    </span>
                    <div class="flex items-center space-x-1">
                        <button id="openWorkspaceStoreBtn" type="button" class="text-amber-800 hover:text-amber-900 text-[10px] font-bold flex items-center gap-1 bg-amber-50 hover:bg-amber-100/80 px-2 py-0.5 rounded-full border border-amber-300 transition-all shadow-sm" title="Explore Predefined Workspaces & Templates">
                            <i data-lucide="sparkles" class="w-2.5 h-2.5 text-amber-600"></i> Store
                        </button>
                        <button id="openNewVentureBtn" type="button" class="text-[#173d34] hover:text-[#285c4e] text-[10px] font-bold flex items-center gap-0.5 bg-[#ffffff] hover:bg-[#fcfaf5] px-2 py-0.5 rounded-full border border-[#ddd8cc] transition-all shadow-sm" title="Create Custom Venture">
                            <i data-lucide="plus" class="w-2.5 h-2.5"></i> New
                        </button>
                    </div>
                </div>
                
                <button id="ventureDropdownBtn" type="button" class="w-full flex items-center justify-between p-2 rounded-xl bg-[#ffffff] hover:bg-[#fffefa] border border-[#ddd8cc] hover:border-[#173d34]/40 text-left transition-all shadow-sm">
                    <div class="flex items-center space-x-2.5 overflow-hidden flex-1">
                        <div id="activeVentureIconContainer" class="w-7 h-7 rounded-lg bg-[#173d34]/10 text-[#173d34] flex items-center justify-center flex-shrink-0 border border-[#173d34]/20">
                            <i id="activeVentureIcon" data-lucide="briefcase" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div id="activeVentureName" class="text-xs font-bold text-[#202725] truncate">Primary Workspace</div>
                            <div id="activeVentureDomain" class="text-[10px] text-[#68716d] truncate">General Operations</div>
                        </div>
                    </div>
                    <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 text-[#68716d] flex-shrink-0"></i>
                </button>

                <!-- Venture Dropdown Menu -->
                <div id="ventureDropdownMenu" class="hidden absolute top-full left-0 right-0 mt-1.5 bg-[#ffffff] border border-[#ddd8cc] rounded-2xl shadow-xl p-1.5 z-50 space-y-1">
                    <div id="ventureItemsList" class="max-h-52 overflow-y-auto space-y-1">
                        <!-- Populated dynamically -->
                    </div>
                    <div class="pt-1.5 border-t border-[#e8e3d9] space-y-1">
                        <button id="btnOpenStoreDropdown" type="button" class="w-full flex items-center justify-between p-2 rounded-xl text-xs font-bold text-[#173d34] bg-[#173d34]/5 hover:bg-[#173d34]/10 transition-colors">
                            <span class="flex items-center space-x-2">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-500"></i>
                                <span>Browse Templates</span>
                            </span>
                            <span class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold bg-amber-500/20 text-amber-700">STORE</span>
                        </button>
                        <button id="btnAddNewVentureDropdown" type="button" class="w-full flex items-center space-x-2 p-2 rounded-xl text-xs font-semibold text-[#173d34] hover:bg-[#f5f2e9] transition-colors">
                            <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                            <span>+ Launch Custom Venture</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- PRIMARY ACTION: New Chat Button (RuXt Signature Pill Button) -->
            <button id="newConsultationBtn" class="w-full py-2.5 px-4 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] font-bold rounded-full text-xs flex items-center justify-center space-x-2 transition-all duration-200 shadow-md shadow-[#173d34]/15 active:scale-[0.98]">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>New Chat</span>
            </button>
        </div>

        <!-- Navigation Tabs: Clean & Organized -->
        <div class="flex-1 overflow-y-auto p-3 space-y-4">
            
            <!-- Main View Navigation Section -->
            <div class="space-y-1">
                <div class="px-2 mb-1.5 flex items-center justify-between">
                    <span class="kicker">Navigation</span>
                </div>

                <!-- 1. Assistant Chat (RuXt) -->
                <button id="navRuXtBtn" class="w-full flex items-center justify-between p-2.5 rounded-xl bg-[#ffffff] border border-[#ddd8cc] text-left hover:border-[#173d34] transition-all text-[#173d34] shadow-sm group">
                    <div class="flex items-center space-x-2.5 overflow-hidden flex-1">
                        <div class="relative flex-shrink-0">
                            <img id="sidebarRuXtAvatar" src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80" alt="RuXt" class="w-7 h-7 rounded-full object-cover border border-[#173d34]/40 shadow-sm">
                            <span class="absolute bottom-0 right-0 w-2 h-2 rounded-full bg-emerald-500 border border-[#ffffff]"></span>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-xs font-bold text-[#173d34] truncate">RuXt Assistant</h3>
                            <p class="text-[10px] text-[#68716d] truncate">Private AI & Chief of Staff</p>
                        </div>
                    </div>
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-[#b7843f] flex-shrink-0"></i>
                </button>

                <!-- 2. Task & Sprint Board -->
                <button id="navTasksBoardBtn" class="w-full flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold text-[#3d4942] hover:bg-[#ede8dc] hover:text-[#173d34] transition-all">
                    <div class="flex items-center space-x-2.5">
                        <i data-lucide="kanban-square" class="w-4 h-4 text-[#173d34]"></i>
                        <span>Tasks & Projects</span>
                    </div>
                    <span id="navTasksCountBadge" class="text-[10px] bg-[#173d34]/10 text-[#173d34] px-2 py-0.5 rounded-full font-mono font-bold">0</span>
                </button>

                <!-- 3. Executive Boardroom -->
                <button id="navBoardroomBtn" class="w-full flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold text-[#3d4942] hover:bg-[#ede8dc] hover:text-[#173d34] transition-all">
                    <div class="flex items-center space-x-2.5">
                        <i data-lucide="users-round" class="w-4 h-4 text-[#799a87]"></i>
                        <span>Executive Boardroom</span>
                    </div>
                    <span class="text-[10px] bg-[#799a87]/20 text-[#285c4e] px-2 py-0.5 rounded-full font-mono font-bold">Live</span>
                </button>

                <!-- 4. Team War Room Discussions -->
                <button id="navDiscussionsBtn" class="w-full flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold text-[#3d4942] hover:bg-[#ede8dc] hover:text-[#173d34] transition-all">
                    <div class="flex items-center space-x-2.5">
                        <i data-lucide="message-circle-code" class="w-4 h-4 text-[#b7843f]"></i>
                        <span>War Room Deliberations</span>
                    </div>
                    <span id="navDiscussionsCountBadge" class="text-[10px] bg-[#b7843f]/15 text-[#9a6d32] px-2 py-0.5 rounded-full font-mono font-bold">0</span>
                </button>

                <!-- Quick Convene Meeting Action -->
                <button id="callMeetingBtn" class="w-full py-1.5 px-3 mt-1 rounded-lg text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] text-[11px] font-semibold flex items-center justify-center space-x-1.5 transition-colors border border-transparent hover:border-[#ddd8cc]">
                    <i data-lucide="landmark" class="w-3.5 h-3.5 text-[#173d34]"></i>
                    <span>+ Convene Strategy Meeting</span>
                </button>
            </div>

            <!-- AI Specialists Section (1-on-1 Access) -->
            <div>
                <div class="flex items-center justify-between px-2 mb-1.5">
                    <div class="flex items-center space-x-1.5 overflow-hidden">
                        <span class="kicker">Specialist Team</span>
                        <span id="ventureBadgeInSidebar" class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20 truncate max-w-[100px]">Studio</span>
                    </div>
                    <button id="openHireModalBtn" class="text-[10px] text-[#173d34] hover:text-[#285c4e] font-bold flex items-center space-x-1">
                        <i data-lucide="user-plus" class="w-3 h-3"></i>
                        <span>Hire</span>
                    </button>
                </div>
                <div class="space-y-1" id="executivesList">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>

            <!-- Recent Chat Conversations History -->
            <div>
                <div class="flex items-center justify-between px-2 mb-1.5">
                    <span class="kicker">Recent Chats</span>
                </div>
                <div class="space-y-1" id="sessionsList">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
        </div>

        <!-- Sidebar Footer: Clean Profile & Hub Button with Logout -->
        <div class="p-3 border-t border-[#ddd8cc] bg-[#f0ede4] space-y-2">
            <?php if (($currentUser['role'] ?? 'user') === 'admin'): ?>
            <a href="dashboard.php" class="w-full flex items-center justify-between p-2 rounded-xl bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] transition-all shadow-sm group text-left cursor-pointer active:scale-[0.99]" title="Open Management Dashboard">
                <div class="flex items-center space-x-2">
                    <div class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-white">
                        <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
                    </div>
                    <span class="text-xs font-bold">Admin Dashboard</span>
                </div>
                <span class="text-[9px] bg-amber-500/30 text-amber-200 border border-amber-400/30 font-mono font-bold px-1.5 py-0.5 rounded-full uppercase">Manage</span>
            </a>
            <?php endif; ?>
            <div class="flex items-center gap-1.5">
                <button id="openProfileBtn" type="button" onclick="window.openProfileModal()" class="flex-1 min-w-0 flex items-center justify-between p-2 rounded-2xl bg-[#ffffff] hover:bg-[#fffefa] border border-[#ddd8cc] hover:border-[#173d34]/40 transition-all shadow-sm group text-left cursor-pointer">
                    <div class="flex items-center space-x-2 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0 group-hover:scale-105 transition-transform">
                            <?= htmlspecialchars(strtoupper(substr($currentUser['name'] ?? 'U', 0, 1))) ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold text-[#202725] truncate"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></div>
                            <div class="text-[10px] text-[#68716d] truncate">@<?= htmlspecialchars($currentUser['username'] ?? 'user') ?></div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-1 flex-shrink-0 text-[#68716d] group-hover:text-[#173d34] pl-1">
                        <span class="text-[9px] bg-[#173d34]/10 text-[#173d34] font-mono font-bold px-1.5 py-0.5 rounded-full border border-[#173d34]/20 uppercase">Hub</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </div>
                </button>
                <a href="logout.php" class="w-9 h-9 rounded-2xl bg-[#ffffff] hover:bg-[#fff1f2] text-[#68716d] hover:text-[#e11d48] border border-[#ddd8cc] hover:border-[#fecdd3] transition-all shadow-sm flex items-center justify-center flex-shrink-0 cursor-pointer active:scale-95" title="Sign Out / Log Out">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Workspace Area -->
    <main class="flex-1 flex flex-col h-full bg-[#fcfaf5] relative min-w-0">
        
        <!-- Top Navigation Header (Matching RuXt Site Header) -->
        <header class="h-14 border-b border-[#ddd8cc] flex items-center justify-between px-2.5 sm:px-4 md:px-5 glass-panel z-20 flex-shrink-0">
            
            <!-- Left: Toggle & Active Executive Banner -->
            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
                <button id="mobileMenuBtn" class="md:hidden text-[#68716d] hover:text-[#173d34] p-2 rounded-xl hover:bg-[#ede8dc] transition-colors flex-shrink-0" aria-label="Open Navigation Drawer">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>

                <!-- Active Profile Info -->
                <div class="flex items-center space-x-2 sm:space-x-2.5 min-w-0 cursor-pointer" id="headerProfileArea" onclick="if(window.innerWidth < 768 && window.switchToChatView) window.switchToChatView()">
                    <div class="relative flex-shrink-0">
                        <img id="headerActiveAvatar" src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80" alt="Executive" class="w-8 h-8 rounded-full object-cover border border-[#173d34]/30 shadow-sm">
                        <span class="absolute bottom-0 right-0 w-2 h-2 rounded-full bg-emerald-500 border border-[#ffffff]"></span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-1.5">
                            <h2 id="currentChatTitle" class="text-xs sm:text-sm font-bold text-[#202725] truncate max-w-[100px] sm:max-w-[160px] md:max-w-none">RuXt</h2>
                            <span class="hidden sm:inline-flex text-[9px] px-1.5 py-0.2 rounded-full bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20 font-semibold items-center space-x-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Online</span>
                            </span>
                        </div>
                        <p id="headerActiveSubtitle" class="text-[10px] text-[#68716d] truncate max-w-[110px] sm:max-w-[200px] hidden sm:block">Private AI Assistant</p>
                    </div>
                </div>
            </div>

            <!-- Center: View Switcher (Desktop RuXt Pill Toggle) -->
            <div class="hidden lg:flex items-center p-0.5 bg-[#ede8dc] border border-[#ddd8cc] rounded-full space-x-1">
                <button id="topToggleChatBtn" type="button" class="px-3.5 py-1 text-xs font-bold rounded-full text-[#173d34] bg-[#ffffff] border border-[#ddd8cc] shadow-sm flex items-center space-x-1.5 transition-all">
                    <i data-lucide="message-square" class="w-3.5 h-3.5 text-[#173d34]"></i>
                    <span>Chat</span>
                </button>
                <button id="topToggleTasksBtn" type="button" class="px-3.5 py-1 text-xs font-semibold rounded-full text-[#68716d] hover:text-[#173d34] flex items-center space-x-1.5 transition-all">
                    <i data-lucide="kanban-square" class="w-3.5 h-3.5 text-[#799a87]"></i>
                    <span>Tasks</span>
                </button>
                <button id="topToggleBoardroomBtn" type="button" class="px-3.5 py-1 text-xs font-semibold rounded-full text-[#68716d] hover:text-[#173d34] flex items-center space-x-1.5 transition-all">
                    <i data-lucide="users-round" class="w-3.5 h-3.5 text-[#b7843f]"></i>
                    <span>Boardroom</span>
                </button>
            </div>

            <!-- Right Controls: Voice Call, Language, Sound, Model -->
            <div class="flex items-center space-x-1.5 sm:space-x-2 flex-shrink-0">
                
                <!-- Live Voice Call Button (RuXt Pill CTA) -->
                <button id="startVoiceCallBtn" type="button" class="px-3 sm:px-3.5 py-1.5 rounded-full bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] font-bold text-xs flex items-center space-x-1 sm:space-x-1.5 shadow-md shadow-[#173d34]/15 transition-all active:scale-95" title="Start Live Voice Conversation">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <i data-lucide="phone-call" class="w-3.5 h-3.5 text-[#fcfaf5]"></i>
                    <span class="font-bold hidden sm:inline">Voice Call</span>
                    <span class="font-bold sm:hidden text-[11px]">Call</span>
                </button>

                <!-- Voice Speech Audio Output Toggle -->
                <button id="soundToggleBtn" class="p-1.5 sm:p-2 text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] rounded-full transition-colors" title="Voice Output Audio: OFF">
                    <i id="soundToggleIcon" data-lucide="volume-x" class="w-4 h-4"></i>
                </button>

                <!-- Active Venture & Project Pills (Subtle) -->
                <button id="headerVenturePill" type="button" class="hidden xl:flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-[#ffffff] border border-[#ddd8cc] text-xs font-medium text-[#202725] shadow-sm" title="Active Venture">
                    <span id="headerVentureDot" class="w-1.5 h-1.5 rounded-full bg-[#173d34]"></span>
                    <span id="headerVentureName" class="truncate max-w-[100px] text-[11px] font-bold text-[#173d34]">Primary Workspace</span>
                </button>

                <button id="headerProjectPill" type="button" class="hidden xl:flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-[#ffffff] border border-[#ddd8cc] text-xs font-medium text-[#202725] shadow-sm" title="Active Project">
                    <i data-lucide="folder-kanban" class="w-3 h-3 text-[#799a87]"></i>
                    <span id="headerProjectName" class="truncate max-w-[100px] text-[11px] font-bold text-[#3d4942]">All Projects</span>
                </button>

                <!-- Workspace Template Marketplace Header Button -->
                <button id="headerStoreBtn" type="button" class="hidden lg:flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-amber-50 hover:bg-amber-100/80 border border-amber-300 text-xs font-bold text-amber-900 transition-all shadow-sm" title="Explore Predefined Workspaces & Templates">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-600"></i>
                    <span class="text-[11px]">Templates Store</span>
                    <span class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold bg-amber-200 text-amber-800">FREE & PRO</span>
                </button>

                <!-- AI Model Badge -->
                <button onclick="document.getElementById('settingsModal').classList.remove('hidden')" id="currentModelBadge" class="hidden sm:inline-block text-[10px] bg-[#ffffff] hover:bg-[#f5f2e9] text-[#68716d] border border-[#ddd8cc] px-2.5 py-1 rounded-full font-mono transition-colors shadow-sm" title="Configure AI Model">
                    gemini-3-flash-preview
                </button>

                <!-- Live Token Usage Header Pill -->
                <button onclick="document.getElementById('settingsModal').classList.remove('hidden')" id="headerTokenBadge" class="hidden sm:inline-flex items-center space-x-1.5 text-[10px] bg-[#ffffff] hover:bg-[#f5f2e9] text-[#173d34] border border-[#ddd8cc] px-2.5 py-1 rounded-full font-mono transition-colors shadow-sm cursor-pointer" title="Click to view detailed Token Analytics in Settings">
                    <i data-lucide="zap" class="w-3 h-3 text-[#b7843f]"></i>
                    <span id="headerTokenCountText">0 Tokens</span>
                </button>

                <!-- Current User Pill & Profile Hub + Logout -->
                <div class="flex items-center space-x-1 sm:space-x-1.5 pl-1.5 border-l border-[#ddd8cc]">
                    <button type="button" onclick="window.openProfileModal()" class="flex items-center space-x-1.5 p-1 rounded-full hover:bg-[#ede8dc] transition-colors cursor-pointer group" title="Open Profile & Hub">
                        <div class="w-7 h-7 rounded-full bg-[#173d34] text-[#fcfaf5] flex items-center justify-center font-bold text-[11px] shadow-sm flex-shrink-0 group-hover:scale-105 transition-transform">
                            <?= htmlspecialchars(strtoupper(substr($currentUser['name'] ?? 'U', 0, 1))) ?>
                        </div>
                        <span class="hidden md:inline text-xs font-bold text-[#202725] max-w-[90px] truncate"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></span>
                    </button>
                    <a href="logout.php" class="flex items-center space-x-1 px-2.5 py-1 rounded-full text-xs font-semibold text-[#be123c] hover:text-[#ffffff] bg-[#fff1f2] hover:bg-[#e11d48] border border-[#fecdd3] hover:border-[#e11d48] transition-all shadow-2xs cursor-pointer active:scale-95" title="Sign Out of RuXt">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Dynamic Content Viewport -->
        <div id="mainViewport" class="flex-1 overflow-y-auto relative">

            <!-- 1. TASK & SPRINT KANBAN BOARD VIEW -->
            <div id="tasksBoardView" class="hidden h-full flex flex-col overflow-hidden bg-[#fcfaf5]">
                <!-- Kanban Header & Controls -->
                <div class="p-3 md:px-5 md:py-3 border-b border-[#ddd8cc] bg-[#f5f2e9] flex flex-col md:flex-row md:items-center justify-between gap-2.5 sm:gap-3 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0 shadow-sm">
                                <i data-lucide="kanban-square" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center space-x-2">
                                    <h2 class="text-xs sm:text-sm font-bold text-[#202725] tracking-tight">Task & Deliverables Board</h2>
                                    <span id="tasksVentureBadge" class="text-[9px] sm:text-[10px] px-2 py-0.5 rounded-full bg-[#173d34]/10 text-[#173d34] font-mono font-bold border border-[#173d34]/20 truncate max-w-[100px] sm:max-w-[130px]">Software Studio</span>
                                </div>
                                <p class="text-[10px] text-[#68716d] hidden sm:block">Deliverables delegated by RuXt and your executive team</p>
                            </div>
                        </div>

                        <!-- Mobile Action Buttons -->
                        <div class="flex items-center space-x-1.5 md:hidden">
                            <button onclick="document.getElementById('openNewProjectModalBtn').click()" type="button" class="p-1.5 bg-[#ffffff] hover:bg-[#f5f2e9] text-[#173d34] border border-[#ddd8cc] rounded-xl text-xs font-bold transition-all shadow-sm" title="New Project">
                                <i data-lucide="folder-plus" class="w-4 h-4"></i>
                            </button>
                            <button onclick="document.getElementById('openNewTaskModalBtn').click()" type="button" class="px-2.5 py-1 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold transition-all flex items-center space-x-1 shadow-sm">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Task</span>
                            </button>
                        </div>
                    </div>

                    <!-- Metrics & Action Buttons -->
                    <div class="flex items-center flex-wrap gap-1.5 sm:gap-2">
                        <!-- View Toggle -->
                        <div class="flex items-center p-0.5 bg-[#ede8dc] border border-[#ddd8cc] rounded-full shadow-sm">
                            <button id="btnViewKanban" type="button" class="px-2 sm:px-2.5 py-1 text-[11px] sm:text-xs font-bold rounded-full bg-[#ffffff] text-[#173d34] shadow-sm flex items-center space-x-1 sm:space-x-1.5 transition-all">
                                <i data-lucide="columns" class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-[#173d34]"></i>
                                <span>Lanes</span>
                            </button>
                            <button id="btnViewProjectWise" type="button" class="px-2 sm:px-2.5 py-1 text-[11px] sm:text-xs font-semibold rounded-full text-[#68716d] hover:text-[#173d34] flex items-center space-x-1 sm:space-x-1.5 transition-all">
                                <i data-lucide="folder-kanban" class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-[#799a87]"></i>
                                <span>By Project</span>
                            </button>
                        </div>

                        <!-- Progress Bar -->
                        <div class="flex items-center space-x-1.5 sm:space-x-2 bg-[#ffffff] border border-[#ddd8cc] px-2.5 sm:px-3 py-1 rounded-full shadow-sm">
                            <span class="text-[9px] sm:text-[10px] uppercase font-bold text-[#68716d]">Progress</span>
                            <div class="w-12 sm:w-16 bg-[#ede8dc] h-1.5 sm:h-2 rounded-full overflow-hidden">
                                <div id="tasksProgressBar" class="bg-[#173d34] h-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <span id="tasksProgressPct" class="text-[11px] sm:text-xs font-mono font-bold text-[#173d34]">0%</span>
                        </div>

                        <!-- Project & Assignee Filters -->
                        <select id="taskProjectFilter" class="bg-[#ffffff] border border-[#ddd8cc] text-[#202725] text-[11px] sm:text-xs rounded-xl px-2 py-1 focus:outline-none focus:border-[#173d34] max-w-[125px] sm:max-w-none shadow-sm">
                            <option value="">All Projects</option>
                        </select>

                        <select id="taskAssigneeFilter" class="bg-[#ffffff] border border-[#ddd8cc] text-[#202725] text-[11px] sm:text-xs rounded-xl px-2 py-1 focus:outline-none focus:border-[#173d34] max-w-[125px] sm:max-w-none shadow-sm">
                            <option value="">All Assignees</option>
                        </select>

                        <!-- Add Project & Task Buttons (Desktop) -->
                        <button onclick="window.openTaskLiveWorkPreview()" type="button" class="px-3 py-1 bg-[#b7843f]/15 hover:bg-[#b7843f]/25 text-[#9a6d32] border border-[#b7843f]/30 rounded-full text-xs font-bold transition-all flex items-center space-x-1.5 shadow-sm" title="Inspect currently executing team member live stream">
                            <span class="w-2 h-2 rounded-full bg-[#b7843f] animate-ping"></span>
                            <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                            <span>Live Execution Stream</span>
                        </button>

                        <button id="openNewProjectModalBtn" type="button" class="hidden md:flex px-3 py-1 bg-[#ffffff] hover:bg-[#f5f2e9] text-[#173d34] border border-[#ddd8cc] rounded-full text-xs font-bold transition-all items-center space-x-1 shadow-sm">
                            <i data-lucide="folder-plus" class="w-3.5 h-3.5 text-[#173d34]"></i>
                            <span>New Project</span>
                        </button>

                        <button id="openNewTaskModalBtn" class="hidden md:flex px-3.5 py-1 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-full text-xs font-bold transition-all items-center space-x-1 shadow-md shadow-[#173d34]/15">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>New Task</span>
                        </button>
                    </div>
                </div>

                <!-- Project Quick-Filter Pills Bar -->
                <div id="projectPillsBar" class="px-3 md:px-5 py-2 bg-[#f0ede4] border-b border-[#ddd8cc] flex items-center space-x-2 overflow-x-auto flex-shrink-0 scrollbar-none">
                    <!-- Populated dynamically via JS -->
                </div>

                <!-- Mobile Quick Lane Tabs (Direct 1-tap lane jumping) -->
                <div id="mobileKanbanTabs" class="md:hidden flex items-center justify-between p-1 mx-3 mt-2 mb-1 bg-[#ede8dc] border border-[#ddd8cc] rounded-full text-[11px] flex-shrink-0">
                    <button type="button" onclick="window.scrollKanbanToColumn('all')" class="mobile-lane-tab px-2.5 py-1 rounded-full font-bold bg-[#ffffff] text-[#173d34] shadow-sm" data-target="all">All</button>
                    <button type="button" onclick="window.scrollKanbanToColumn('colTodo')" class="mobile-lane-tab px-2.5 py-1 rounded-full text-[#68716d]" data-target="colTodo">To Do (<span id="mobileCountTodo">0</span>)</button>
                    <button type="button" onclick="window.scrollKanbanToColumn('colInProgress')" class="mobile-lane-tab px-2.5 py-1 rounded-full text-[#b7843f]" data-target="colInProgress">Progress (<span id="mobileCountInProgress">0</span>)</button>
                    <button type="button" onclick="window.scrollKanbanToColumn('colReview')" class="mobile-lane-tab px-2.5 py-1 rounded-full text-[#799a87]" data-target="colReview">Review (<span id="mobileCountReview">0</span>)</button>
                    <button type="button" onclick="window.scrollKanbanToColumn('colCompleted')" class="mobile-lane-tab px-2.5 py-1 rounded-full text-[#173d34]" data-target="colCompleted">Done (<span id="mobileCountCompleted">0</span>)</button>
                </div>

                <!-- 1. Kanban Lanes View (Columns: To Do, In Progress, Review, Completed - Light Theme) -->
                <div id="kanbanLanesContainer" class="flex-1 overflow-x-auto p-3 md:p-5 scroll-smooth">
                    <div class="flex flex-row md:grid md:grid-cols-4 gap-3.5 h-full overflow-x-auto snap-x snap-mandatory scroll-smooth pb-3 md:pb-0">
                        <!-- Column 1: To Do -->
                        <div id="colTodoLane" class="w-[84vw] sm:w-[320px] md:w-auto flex-shrink-0 snap-center flex flex-col bg-[#f5f2e9] border border-[#ddd8cc] rounded-2xl overflow-hidden shadow-sm h-full">
                            <div class="p-3 border-b border-[#ddd8cc] bg-[#ebe6da] flex items-center justify-between flex-shrink-0">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2 h-2 rounded-full bg-[#68716d]"></span>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#202725]">To Do</h3>
                                </div>
                                <span id="countColTodo" class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#ffffff] text-[#68716d] font-bold border border-[#ddd8cc]">0</span>
                            </div>
                            <div id="colTodoCards" class="flex-1 overflow-y-auto p-2.5 space-y-2.5">
                                <!-- Cards rendered via JS -->
                            </div>
                        </div>

                        <!-- Column 2: In Progress -->
                        <div id="colInProgressLane" class="w-[84vw] sm:w-[320px] md:w-auto flex-shrink-0 snap-center flex flex-col bg-[#f5f2e9] border border-[#b7843f]/30 rounded-2xl overflow-hidden shadow-sm h-full">
                            <div class="p-3 border-b border-[#ddd8cc] bg-[#ebe6da] flex items-center justify-between flex-shrink-0">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2 h-2 rounded-full bg-[#b7843f] animate-pulse"></span>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#9a6d32]">In Progress</h3>
                                </div>
                                <span id="countColInProgress" class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#b7843f]/15 text-[#9a6d32] font-bold border border-[#b7843f]/30">0</span>
                            </div>
                            <div id="colInProgressCards" class="flex-1 overflow-y-auto p-2.5 space-y-2.5">
                                <!-- Cards rendered via JS -->
                            </div>
                        </div>

                        <!-- Column 3: In Review -->
                        <div id="colReviewLane" class="w-[84vw] sm:w-[320px] md:w-auto flex-shrink-0 snap-center flex flex-col bg-[#f5f2e9] border border-[#799a87]/30 rounded-2xl overflow-hidden shadow-sm h-full">
                            <div class="p-3 border-b border-[#ddd8cc] bg-[#ebe6da] flex items-center justify-between flex-shrink-0">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2 h-2 rounded-full bg-[#799a87]"></span>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#285c4e]">In Review</h3>
                                </div>
                                <span id="countColReview" class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#799a87]/15 text-[#285c4e] font-bold border border-[#799a87]/30">0</span>
                            </div>
                            <div id="colReviewCards" class="flex-1 overflow-y-auto p-2.5 space-y-2.5">
                                <!-- Cards rendered via JS -->
                            </div>
                        </div>

                        <!-- Column 4: Completed -->
                        <div id="colCompletedLane" class="w-[84vw] sm:w-[320px] md:w-auto flex-shrink-0 snap-center flex flex-col bg-[#f5f2e9] border border-[#173d34]/30 rounded-2xl overflow-hidden shadow-sm h-full">
                            <div class="p-3 border-b border-[#ddd8cc] bg-[#ebe6da] flex items-center justify-between flex-shrink-0">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2 h-2 rounded-full bg-[#173d34]"></span>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#173d34]">Completed</h3>
                                </div>
                                <span id="countColCompleted" class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#173d34]/15 text-[#173d34] font-bold border border-[#173d34]/30">0</span>
                            </div>
                            <div id="colCompletedCards" class="flex-1 overflow-y-auto p-2.5 space-y-2.5">
                                <!-- Cards rendered via JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Project-Wise Grouped View -->
                <div id="projectWiseContainer" class="hidden flex-1 overflow-y-auto p-4 md:p-6 space-y-5">
                    <!-- Project Blocks rendered via JS -->
                </div>
            </div>

            <!-- 2. BOARDROOM VIEW (Multi-Agent Round Table in RuXt Light Theme) -->
            <div id="boardroomView" class="hidden p-3 md:p-8 max-w-5xl mx-auto space-y-4 md:space-y-6">
                <!-- Round Table Seating Visualization -->
                <div class="p-4 sm:p-6 rounded-3xl bg-[#ffffff] border border-[#ddd8cc] shadow-xl shadow-[#173d34]/5 relative overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#173d34] animate-ping"></span>
                            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-[#173d34]">Executive Boardroom Conference Table</h3>
                        </div>
                        <button onclick="document.getElementById('callMeetingModal').classList.remove('hidden')" class="px-3.5 py-1.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-full text-xs font-bold transition-all flex items-center justify-center space-x-1.5 shadow-md shadow-[#173d34]/15">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>New Meeting Agenda</span>
                        </button>
                    </div>

                    <!-- Executive Seats Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2.5 sm:gap-3 pt-2" id="boardroomSeats">
                        <!-- Rendered dynamically -->
                    </div>
                </div>

                <!-- Live Meeting Stream / Selected Meeting Dialogues -->
                <div class="space-y-4" id="boardroomDialoguesStream">
                    <!-- Meeting dialogues rendered here -->
                </div>
            </div>

            <!-- 3. AI CHAT VIEW (Primary Conversational Interface) -->
            <div id="chatView" class="h-full flex flex-col justify-between">
                
                <!-- Mobile Specialist Fast Switcher Bar (1-Tap direct executive messaging) -->
                <div id="mobileExecutiveBar" class="md:hidden flex items-center space-x-2 px-3 py-2 bg-[#f5f2e9] border-b border-[#ddd8cc] overflow-x-auto scrollbar-none flex-shrink-0">
                    <!-- Populated dynamically via JS -->
                </div>

                <div id="chatContainer" class="flex-1 overflow-y-auto px-3 sm:px-4 py-4 md:py-6 md:px-8 space-y-3.5 sm:space-y-4">
                    
                    <!-- Welcome Hero State (Clean, Warm, Editorial RuXt Look) -->
                    <div id="emptyState" class="min-h-full flex flex-col items-center justify-center text-center max-w-xl mx-auto py-4 sm:py-8">
                        <div class="relative mb-4 sm:mb-5">
                            <div class="w-20 h-20 md:w-24 md:h-24 rounded-3xl overflow-hidden border-2 border-[#173d34]/20 shadow-xl p-0.5 bg-[#f5f2e9]">
                                <img id="emptyStateAvatar" src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80" alt="RuXt" class="w-full h-full object-cover rounded-[22px]">
                            </div>
                            <span class="absolute -bottom-1 -right-1 px-2 py-0.5 rounded-full bg-[#173d34] text-[#fcfaf5] font-black text-[9px] shadow-md uppercase tracking-wider">ONLINE</span>
                        </div>

                        <h3 class="text-xl md:text-2xl font-extrabold text-[#202725] mb-1 tracking-tight" id="emptyStateTitle">RuXt Assistant</h3>
                        <p class="kicker mb-2" id="emptyStateSubtitle">Private AI Chief of Staff</p>
                        
                        <p class="text-xs md:text-sm text-[#68716d] mb-5 sm:mb-6 leading-relaxed max-w-md px-2" id="emptyStateBio">
                            "Hello! I am your private AI partner and Chief of Staff. Ask me anything in any language. I can break down projects, delegate to specialist executives, track tasks, and optimize your business."
                        </p>

                        <!-- Clean 4-Grid Suggestion Chips (RuXt Light Cards) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 w-full text-left" id="executiveChips">
                            <button onclick="window.sendPrompt('I have a new project, please break down the deliverables and delegate to the executive team')" class="interactive-chip p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] text-xs text-[#202725] flex items-start space-x-3 group shadow-sm">
                                <div class="w-8 h-8 rounded-xl bg-[#f5f2e9] text-[#173d34] flex items-center justify-center flex-shrink-0 mt-0.5 border border-[#ddd8cc]">
                                    <i data-lucide="split" class="w-4 h-4"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">Break Down a Project</div>
                                    <div class="text-[10px] text-[#68716d]">Delegate tasks to CTO, CMO, CFO</div>
                                </div>
                            </button>

                            <button onclick="window.sendPrompt('Review our pending corporate deliverables and action items')" class="interactive-chip p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] text-xs text-[#202725] flex items-start space-x-3 group shadow-sm">
                                <div class="w-8 h-8 rounded-xl bg-[#f5f2e9] text-[#173d34] flex items-center justify-center flex-shrink-0 mt-0.5 border border-[#ddd8cc]">
                                    <i data-lucide="check-square" class="w-4 h-4"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">Review Deliverables</div>
                                    <div class="text-[10px] text-[#68716d]">Check pending sprint action items</div>
                                </div>
                            </button>

                            <button onclick="window.sendPrompt('Analyze our Q1 product launch readiness across Elena, Marcus, David, and Sarah')" class="interactive-chip p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] text-xs text-[#202725] flex items-start space-x-3 group shadow-sm">
                                <div class="w-8 h-8 rounded-xl bg-[#f5f2e9] text-[#173d34] flex items-center justify-center flex-shrink-0 mt-0.5 border border-[#ddd8cc]">
                                    <i data-lucide="layers" class="w-4 h-4"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">Multi-Department Plan</div>
                                    <div class="text-[10px] text-[#68716d]">Align marketing, tech & finance</div>
                                </div>
                            </button>

                            <button onclick="window.sendPrompt('Help me plan my executive schedule and focus areas for today')" class="interactive-chip p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] text-xs text-[#202725] flex items-start space-x-3 group shadow-sm">
                                <div class="w-8 h-8 rounded-xl bg-[#f5f2e9] text-[#173d34] flex items-center justify-center flex-shrink-0 mt-0.5 border border-[#ddd8cc]">
                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">Daily Focus & Briefing</div>
                                    <div class="text-[10px] text-[#68716d]">Organize schedule & priorities</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Messages List Stream -->
                    <div id="messagesList" class="max-w-3xl mx-auto flex flex-col">
                        <!-- Messages appended dynamically -->
                    </div>

                    <!-- Typing Indicator -->
                    <div id="typingIndicator" class="hidden max-w-3xl mx-auto flex items-center space-x-3 text-[#68716d] text-xs pl-2">
                        <div class="w-7 h-7 rounded-full overflow-hidden border border-[#173d34]/30">
                            <img id="typingAvatar" src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80" class="w-full h-full object-cover">
                        </div>
                        <div class="flex items-center space-x-1.5 bg-[#ffffff] border border-[#ddd8cc] px-3.5 py-1.5 rounded-full shadow-sm">
                            <span class="text-[#202725] font-medium mr-1 text-[11px]" id="typingName">Thinking</span>
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                        </div>
                    </div>
                </div>

                <!-- Floating Bottom Chat Input Dock (RuXt Signature Clean White Surface) -->
                <div class="relative p-2.5 sm:p-3 md:p-4 border-t border-[#ddd8cc] glass-panel z-20 flex-shrink-0" id="chatInputDockContainer">
                    <!-- Drop Zone Overlay (Shown when dragging files over chat dock) -->
                    <div id="chatDropZone" class="hidden absolute inset-0 bg-[#173d34]/15 backdrop-blur-xs border-2 border-dashed border-[#173d34] rounded-2xl z-30 flex flex-col items-center justify-center p-4 transition-all pointer-events-none">
                        <div class="p-3 rounded-full bg-[#173d34] text-[#fcfaf5] mb-2 shadow-lg">
                            <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                        </div>
                        <span class="text-xs font-bold text-[#173d34]">Drop images or files here to attach</span>
                        <span class="text-[10px] text-[#202725] font-medium">Screenshots, documents, code & diagrams supported</span>
                    </div>

                    <div class="max-w-3xl mx-auto">
                        <!-- Attachment Preview Staging Tray (Appears when files are staged) -->
                        <div id="attachmentPreviewTray" class="hidden mb-2 p-2 bg-[#ffffff] border border-[#ddd8cc] rounded-2xl shadow-sm flex flex-wrap gap-2 items-center transition-all duration-200">
                            <!-- Injected dynamically: thumbnails, file badges & remove buttons -->
                        </div>

                        <div class="relative flex items-center bg-[#ffffff] border border-[#ddd8cc] rounded-2xl p-1.5 focus-within:border-[#173d34] focus-within:ring-2 focus-within:ring-[#173d34]/10 transition-all shadow-lg shadow-[#173d34]/5">
                            <!-- File Attachment Button -->
                            <button id="attachBtn" type="button" class="p-2 sm:p-2.5 text-[#68716d] hover:text-[#173d34] rounded-xl transition-all duration-200 flex-shrink-0 hover:bg-[#f5f2e9] cursor-pointer" title="Attach image or document (or drag & drop / Ctrl+V paste)">
                                <i data-lucide="paperclip" class="w-5 h-5"></i>
                            </button>
                            <input type="file" id="chatFileInput" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.md,.markdown,.csv,.json,.xml,.log,.html,.htm,.css,.js,.ts,.jsx,.tsx,.php,.py,.sql,.java,.c,.cpp,.h,.cs,.yml,.yaml,.sh" class="hidden">

                            <!-- Mic Voice Input Button -->
                            <button id="micBtn" type="button" class="p-2 sm:p-2.5 text-[#68716d] hover:text-[#173d34] rounded-xl transition-all duration-200 flex-shrink-0 hover:bg-[#f5f2e9]" title="Voice Input">
                                <i data-lucide="mic" class="w-5 h-5"></i>
                            </button>

                            <!-- Text Input Area -->
                            <textarea id="chatInput" rows="1" placeholder="Ask RuXt anything in any language (or paste screenshot / attach files)..." class="w-full bg-transparent text-[#202725] placeholder-[#9ca3af] text-sm py-2 px-2.5 resize-none focus:outline-none max-h-36 leading-relaxed select-text"></textarea>

                            <!-- Send Message Button (RuXt Forest Green) -->
                            <button id="sendBtn" type="button" class="p-2.5 sm:p-2.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl transition-all duration-200 flex-shrink-0 shadow-md shadow-[#173d34]/20 active:scale-95 cursor-pointer">
                                <i data-lucide="send" class="w-4 h-4 font-bold"></i>
                            </button>
                        </div>

                        <!-- Friendly bottom hint -->
                        <div class="flex items-center justify-between text-[11px] text-[#68716d] mt-1.5 sm:mt-2 px-1.5">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="sparkles" class="w-3 h-3 text-[#b7843f]"></i>
                                <span>Voice & text, drag-and-drop & screenshot paste (Ctrl+V)</span>
                            </span>
                            <span class="hidden sm:inline">Press <kbd class="px-1.5 py-0.5 rounded bg-[#f5f2e9] border border-[#ddd8cc] font-mono text-[9px] text-[#68716d]">Enter</kbd> to send</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Bottom Navigation Bar (RuXt Light App Navigation) -->
        <nav id="mobileBottomNav" class="md:hidden glass-panel border-t border-[#ddd8cc] flex items-center justify-around px-1 py-1.5 z-30 flex-shrink-0 transition-all duration-200">
            <!-- 1. Chat Tab -->
            <button id="bottomNavChatBtn" type="button" class="flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-[#173d34] font-bold transition-colors">
                <div class="relative">
                    <i data-lucide="message-square" class="w-5 h-5"></i>
                    <span class="absolute -top-0.5 -right-1 w-2 h-2 rounded-full bg-emerald-500"></span>
                </div>
                <span class="text-[10px] font-bold mt-0.5 tracking-tight">Chat</span>
            </button>

            <!-- 2. Tasks Tab -->
            <button id="bottomNavTasksBtn" type="button" class="flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-[#68716d] hover:text-[#173d34] transition-colors">
                <div class="relative">
                    <i data-lucide="kanban-square" class="w-5 h-5"></i>
                    <span id="bottomNavTasksBadge" class="absolute -top-1 -right-2 px-1.5 py-0.2 rounded-full bg-[#173d34]/10 text-[#173d34] text-[9px] font-mono font-bold border border-[#173d34]/20">0</span>
                </div>
                <span class="text-[10px] font-semibold mt-0.5 tracking-tight">Tasks</span>
            </button>

            <!-- 3. Boardroom Tab -->
            <button id="bottomNavBoardroomBtn" type="button" class="flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-[#68716d] hover:text-[#173d34] transition-colors">
                <div class="relative">
                    <i data-lucide="users-round" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-semibold mt-0.5 tracking-tight">Boardroom</span>
            </button>

            <!-- 4. War Room / Deliberations Tab -->
            <button id="bottomNavDiscussionsBtn" type="button" class="flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-[#68716d] hover:text-[#173d34] transition-colors">
                <div class="relative">
                    <i data-lucide="message-circle-code" class="w-5 h-5"></i>
                    <span id="bottomNavDiscussionsBadge" class="absolute -top-1 -right-2 px-1 py-0.2 rounded-full bg-[#b7843f]/15 text-[#9a6d32] text-[9px] font-mono font-bold border border-[#b7843f]/30 hidden">0</span>
                </div>
                <span class="text-[10px] font-semibold mt-0.5 tracking-tight">War Room</span>
            </button>

            <!-- 5. Team / Drawer Tab -->
            <button id="bottomNavMenuBtn" type="button" class="flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-[#68716d] hover:text-[#173d34] transition-colors">
                <div class="relative">
                    <i data-lucide="layers" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-semibold mt-0.5 tracking-tight">Team</span>
            </button>
        </nav>
    </main>

    <!-- ============================================ -->
    <!-- MODALS: CLEAN, REFINED RUXT LIGHT DIALOGS   -->
    <!-- ============================================ -->

    <!-- MODAL 0: USER PROFILE & WORKSPACE HUB (REDESIGNED) -->
    <div id="profileModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden transition-all">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[92dvh] sm:max-h-[88vh] animate-in zoom-in-95 duration-150">
            
            <!-- Hero Gradient Cover Banner -->
            <div class="relative bg-gradient-to-r from-[#173d34] via-[#1f4e43] to-[#285c4e] px-5 pt-4 pb-12 flex items-start justify-between overflow-hidden flex-shrink-0">
                <!-- Ambient Backdrop Overlay & Glow -->
                <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-[#b7843f]/25 rounded-full blur-2xl pointer-events-none"></div>

                <!-- Brand Status Pill -->
                <div class="relative z-10 flex items-center space-x-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-[#fcfaf5] text-[10px] font-mono font-bold tracking-wide">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>RuXt Private Profile</span>
                </div>

                <!-- Close Button -->
                <button type="button" onclick="window.closeProfileModal()" class="relative z-10 w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 text-[#fcfaf5] flex items-center justify-center transition-all cursor-pointer" title="Close Profile (Esc)">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Floating Hero Profile Identity Section -->
            <div class="px-5 sm:px-6 -mt-8 relative z-10 flex-shrink-0 bg-transparent">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                    <div class="flex items-end space-x-3.5 min-w-0">
                        <!-- Avatar with Status Dot -->
                        <div class="relative flex-shrink-0">
                            <div class="w-16 h-16 sm:w-18 sm:h-18 rounded-2xl bg-gradient-to-br from-[#173d34] to-[#285c4e] text-[#fcfaf5] shadow-xl border-4 border-white flex items-center justify-center font-extrabold text-2xl flex-shrink-0">
                                <span id="profileAvatarLetter"><?= htmlspecialchars(strtoupper(substr($currentUser['name'] ?? 'U', 0, 1))) ?></span>
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white shadow-sm" title="Active & Online"></span>
                        </div>

                        <!-- Name & Handle -->
                        <div class="min-w-0 pb-0.5">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <h3 class="text-base sm:text-lg font-extrabold text-[#202725] truncate" id="profileDisplayName"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></h3>
                                <span class="text-[9px] bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20 px-2 py-0.5 rounded-full font-mono font-bold uppercase tracking-wider">
                                    <?= htmlspecialchars($currentUser['role'] ?? 'ADMINISTRATOR') ?>
                                </span>
                            </div>
                            <div class="text-xs text-[#68716d] flex items-center gap-1 truncate mt-0.5" id="profileDisplayEmail">
                                <i data-lucide="mail" class="w-3 h-3 text-[#68716d]/80"></i>
                                <span><?= htmlspecialchars($currentUser['email'] ?? '') ?></span>
                            </div>
                            <div class="text-[11px] text-[#8c5e1e] font-mono font-medium mt-0.5 flex items-center gap-1">
                                <span>@<span id="profileDisplayUsername"><?= htmlspecialchars($currentUser['username'] ?? 'user') ?></span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Actions: Sign Out -->
                    <div class="flex items-center gap-2 flex-shrink-0 self-start sm:self-end">
                        <a href="logout.php" class="px-3.5 py-1.5 bg-[#fff1f2] hover:bg-[#e11d48] text-[#be123c] hover:text-[#ffffff] border border-[#fecdd3] hover:border-[#e11d48] rounded-xl text-xs font-bold transition-all shadow-sm flex items-center space-x-1.5 cursor-pointer active:scale-95" title="Sign out of RuXt">
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                            <span>Sign Out</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Segmented Pill Tab Switcher -->
            <div class="px-5 sm:px-6 pt-3 pb-1 bg-[#ffffff] flex-shrink-0">
                <div class="p-1 bg-[#f5f2e9] border border-[#ddd8cc] rounded-2xl flex items-center space-x-1">
                    <button type="button" id="profileTabBtnTools" onclick="window.switchProfileTab('tools')" class="flex-1 py-1.5 px-3 text-xs font-bold rounded-xl transition-all shadow-sm bg-[#ffffff] text-[#173d34] border border-[#ddd8cc] flex items-center justify-center space-x-1.5 cursor-pointer">
                        <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                        <span>Workspace Hub</span>
                    </button>
                    <button type="button" id="profileTabBtnSecurity" onclick="window.switchProfileTab('security')" class="flex-1 py-1.5 px-3 text-xs font-semibold rounded-xl transition-all text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] border border-transparent flex items-center justify-center space-x-1.5 cursor-pointer">
                        <i data-lucide="user-cog" class="w-3.5 h-3.5"></i>
                        <span>Account & Security</span>
                    </button>
                </div>
            </div>

            <!-- Scrollable Tab Content Viewport -->
            <div class="p-5 sm:p-6 overflow-y-auto flex-1 bg-[#ffffff] space-y-4">
                
                <!-- PANE 1: WORKSPACE HUB (THE 3 CORE TOOLS) -->
                <div id="profileTabPaneTools" class="space-y-3.5">
                    
                    <!-- RuXt Active AI Specialization Card -->
                    <div class="p-3.5 rounded-2xl bg-gradient-to-br from-[#fcfaf5] to-[#f5f2e9] border border-[#ddd8cc] shadow-sm">
                        <div class="flex items-center justify-between pb-2 border-b border-[#ddd8cc]/60 mb-2.5">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 rounded-lg bg-[#173d34] text-[#fcfaf5] flex items-center justify-center">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                </div>
                                <span class="text-xs font-bold text-[#173d34] uppercase tracking-wider font-mono">RuXt Specialization</span>
                            </div>
                            <button type="button" onclick="window.restartOnboarding()" class="px-2.5 py-1 text-[11px] font-bold text-[#173d34] hover:bg-[#173d34]/10 rounded-lg transition-colors flex items-center space-x-1 border border-[#173d34]/20 cursor-pointer" title="Reconfigure RuXt Role">
                                <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                                <span>Reconfigure Role</span>
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div class="bg-white p-2.5 rounded-xl border border-[#ddd8cc]">
                                <span class="text-[10px] text-[#68716d] block font-medium uppercase font-mono">Your Field</span>
                                <span id="profileUserJobField" class="font-bold text-[#202725] block truncate mt-0.5"><?= htmlspecialchars(!empty($currentUser['job_field']) ? $currentUser['job_field'] : 'Pending Onboarding') ?></span>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-[#ddd8cc]">
                                <span class="text-[10px] text-[#68716d] block font-medium uppercase font-mono">RuXt's Role</span>
                                <span id="profileRuxtSpecializedRole" class="font-bold text-[#173d34] block truncate mt-0.5"><?= htmlspecialchars(!empty($currentUser['specialized_role']) ? $currentUser['specialized_role'] : 'Chief of Staff & Lead Assistant') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- 3-Metric Overview Cards Ribbon -->
                    <div class="grid grid-cols-3 gap-2">
                        <div class="p-2.5 rounded-2xl bg-[#fcfaf5] border border-[#ddd8cc] text-center">
                            <span class="text-[10px] text-[#68716d] block font-medium">Memory Vault</span>
                            <span id="profileMemoriesSummary" class="text-xs sm:text-sm font-bold text-[#173d34] font-mono mt-0.5 block">0 Facts</span>
                        </div>
                        <div class="p-2.5 rounded-2xl bg-[#fcfaf5] border border-[#ddd8cc] text-center">
                            <span class="text-[10px] text-[#68716d] block font-medium">Quick Tasks</span>
                            <span id="profileTasksSummary" class="text-xs sm:text-sm font-bold text-[#285c4e] font-mono mt-0.5 block">Active</span>
                        </div>
                        <div class="p-2.5 rounded-2xl bg-[#fcfaf5] border border-[#ddd8cc] text-center">
                            <span class="text-[10px] text-[#68716d] block font-medium">Token Analytics</span>
                            <span id="profileTokensSummary" class="text-xs sm:text-sm font-bold text-[#8c5e1e] font-mono mt-0.5 block">0 Tokens</span>
                        </div>
                    </div>

                    <!-- THE 3 HUB CARDS (MATCHING USER SCREENSHOT WITH HIGH POLISH) -->
                    <div class="space-y-2.5">
                        
                        <!-- 1. Memory Vault Card -->
                        <button id="openMemoriesBtn" type="button" class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-[#ffffff] hover:bg-[#fcfaf5] border border-[#ddd8cc] hover:border-[#173d34] transition-all text-left shadow-sm hover:shadow-md group cursor-pointer active:scale-[0.99]">
                            <div class="flex items-center space-x-3.5 overflow-hidden flex-1">
                                <div class="w-10 h-10 rounded-2xl bg-[#173d34]/10 text-[#173d34] flex items-center justify-center flex-shrink-0 border border-[#173d34]/20 group-hover:bg-[#173d34] group-hover:text-[#fcfaf5] transition-all shadow-sm">
                                    <i data-lucide="briefcase" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs sm:text-sm font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">
                                        Memory Vault
                                    </div>
                                    <p class="text-[11px] text-[#68716d] truncate mt-0.5">Corporate knowledge, tech stack rules & remembered facts</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2.5 flex-shrink-0 ml-2">
                                <span id="memoriesCountBadge" class="text-xs bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20 px-2.5 py-0.5 rounded-full font-mono font-bold">0</span>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-[#68716d] group-hover:text-[#173d34] group-hover:translate-x-0.5 transition-all"></i>
                            </div>
                        </button>

                        <!-- 2. Quick Tasks Card -->
                        <button id="openTasksBtn" type="button" class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-[#ffffff] hover:bg-[#fcfaf5] border border-[#ddd8cc] hover:border-[#799a87] transition-all text-left shadow-sm hover:shadow-md group cursor-pointer active:scale-[0.99]">
                            <div class="flex items-center space-x-3.5 overflow-hidden flex-1">
                                <div class="w-10 h-10 rounded-2xl bg-[#799a87]/15 text-[#285c4e] flex items-center justify-center flex-shrink-0 border border-[#799a87]/30 group-hover:bg-[#799a87] group-hover:text-[#ffffff] transition-all shadow-sm">
                                    <i data-lucide="check-square" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs sm:text-sm font-bold text-[#202725] group-hover:text-[#285c4e] transition-colors">
                                        Quick Tasks
                                    </div>
                                    <p class="text-[11px] text-[#68716d] truncate mt-0.5">Quick scratchpad checklist & action deliverables</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2.5 flex-shrink-0 ml-2">
                                <span class="text-xs bg-[#ffffff] px-2.5 py-0.5 rounded-full text-[#68716d] border border-[#ddd8cc] font-mono font-bold shadow-2xs">Modal</span>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-[#68716d] group-hover:text-[#285c4e] group-hover:translate-x-0.5 transition-all"></i>
                            </div>
                        </button>

                        <!-- 3. Settings & API Card -->
                        <button id="openSettingsBtn" type="button" class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-[#ffffff] hover:bg-[#fcfaf5] border border-[#ddd8cc] hover:border-[#b7843f] transition-all text-left shadow-sm hover:shadow-md group cursor-pointer active:scale-[0.99]">
                            <div class="flex items-center space-x-3.5 overflow-hidden flex-1">
                                <div class="w-10 h-10 rounded-2xl bg-[#b7843f]/15 text-[#8c5e1e] flex items-center justify-center flex-shrink-0 border border-[#b7843f]/30 group-hover:bg-[#b7843f] group-hover:text-[#ffffff] transition-all shadow-sm">
                                    <i data-lucide="settings" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs sm:text-sm font-bold text-[#202725] group-hover:text-[#8c5e1e] transition-colors">
                                        Settings & API
                                    </div>
                                    <p class="text-[11px] text-[#68716d] truncate mt-0.5">AI engine models, voice speech configs & token analytics</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2.5 flex-shrink-0 ml-2">
                                <span id="sidebarTokenBadge" class="text-xs bg-[#b7843f]/10 text-[#8c5e1e] border border-[#b7843f]/25 px-2.5 py-0.5 rounded-full font-mono font-bold">0 T</span>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-[#68716d] group-hover:text-[#8c5e1e] group-hover:translate-x-0.5 transition-all"></i>
                            </div>
                        </button>

                        <?php if (($currentUser['role'] ?? 'user') === 'admin'): ?>
                        <!-- 4. Management Dashboard Link Card -->
                        <a href="dashboard.php" class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-[#ffffff] hover:bg-[#fcfaf5] border border-[#ddd8cc] hover:border-[#173d34] transition-all text-left shadow-sm hover:shadow-md group cursor-pointer active:scale-[0.99] block">
                            <div class="flex items-center space-x-3.5 overflow-hidden flex-1">
                                <div class="w-10 h-10 rounded-2xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <div class="text-xs sm:text-sm font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">
                                            Management Dashboard
                                        </div>
                                        <span class="text-[9px] bg-amber-500/20 text-amber-800 border border-amber-500/30 font-mono font-bold px-1.5 py-0.5 rounded-full uppercase">Admin</span>
                                    </div>
                                    <p class="text-[11px] text-[#68716d] truncate mt-0.5">Manage workspaces, users, executive dream teams & platform tools</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2.5 flex-shrink-0 ml-2">
                                <span class="text-xs bg-[#173d34] text-white px-2.5 py-0.5 rounded-full font-mono font-bold shadow-2xs">Open &rarr;</span>
                            </div>
                        </a>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- PANE 2: ACCOUNT & SECURITY SETTINGS -->
                <div id="profileTabPaneSecurity" class="space-y-3.5 hidden">
                    <form id="editProfileForm" class="space-y-3" onsubmit="window.handleUpdateProfile(event)">
                        <div id="profileAlertBox" class="hidden p-3 rounded-2xl text-xs flex items-center space-x-2"></div>

                        <div>
                            <label class="block text-xs font-semibold text-[#3d4942] mb-1.5">Full Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#68716d]">
                                    <i data-lucide="user" class="w-4 h-4"></i>
                                </div>
                                <input type="text" id="editProfileNameInput" value="<?= htmlspecialchars($currentUser['name'] ?? '') ?>" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] focus:border-[#173d34] rounded-xl pl-9 pr-3 py-2 text-xs text-[#202725] focus:outline-none transition-all">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#3d4942] mb-1.5">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#68716d]">
                                    <i data-lucide="mail" class="w-4 h-4"></i>
                                </div>
                                <input type="email" id="editProfileEmailInput" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] focus:border-[#173d34] rounded-xl pl-9 pr-3 py-2 text-xs text-[#202725] focus:outline-none transition-all">
                            </div>
                        </div>

                        <div class="pt-2 border-t border-[#e8e3d9] space-y-2.5">
                            <label class="block text-xs font-semibold text-[#3d4942]">Change Password <span class="text-[#68716d] font-normal text-[11px]">(Optional)</span></label>
                            
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#68716d]">
                                    <i data-lucide="key" class="w-4 h-4"></i>
                                </div>
                                <input type="password" id="editProfileNewPasswordInput" placeholder="New password (min 6 chars)" minlength="6" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] focus:border-[#173d34] rounded-xl pl-9 pr-3 py-2 text-xs text-[#202725] focus:outline-none transition-all">
                            </div>

                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#68716d]">
                                    <i data-lucide="lock" class="w-4 h-4"></i>
                                </div>
                                <input type="password" id="editProfileCurrentPasswordInput" placeholder="Current password (required only if updating password)" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] focus:border-[#173d34] rounded-xl pl-9 pr-3 py-2 text-xs text-[#202725] focus:outline-none transition-all">
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-end space-x-2">
                            <button type="submit" id="saveProfileBtn" class="w-full py-2.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold transition-all shadow-md shadow-[#173d34]/15 cursor-pointer">
                                Save Profile Changes
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-3 bg-[#f5f2e9] border-t border-[#ddd8cc] flex items-center justify-between text-xs text-[#68716d] flex-shrink-0">
                <span class="flex items-center space-x-1.5 text-[11px]">
                    <i data-lucide="shield" class="w-3.5 h-3.5 text-[#173d34]"></i>
                    <span>Private &amp; Self-Hosted</span>
                </span>
                <div class="flex items-center space-x-2">
                    <a href="logout.php" class="px-3.5 py-1.5 bg-[#ffffff] hover:bg-[#fff1f2] text-[#be123c] hover:text-[#e11d48] border border-[#ddd8cc] hover:border-[#fecdd3] rounded-xl font-bold transition-all text-xs flex items-center space-x-1.5 cursor-pointer active:scale-95">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                        <span>Log Out</span>
                    </a>
                    <button type="button" onclick="window.closeProfileModal()" class="px-4 py-1.5 bg-[#173d34] hover:bg-[#285c4e] text-white rounded-xl font-semibold transition-colors cursor-pointer">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: CONVENE BOARD MEETING -->
    <div id="callMeetingModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-xl overflow-hidden shadow-2xl flex flex-col max-h-[92dvh] sm:max-h-[90vh]">
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-[#f5f2e9] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i data-lucide="landmark" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#202725] text-sm sm:text-base">Convene Board of Directors Meeting</h3>
                        <p class="text-[10px] sm:text-[11px] text-[#68716d]">Summon your executive specialists to deliberate and align on strategy</p>
                    </div>
                </div>
                <button id="closeCallMeetingBtn" class="text-[#68716d] hover:text-[#173d34] p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="boardMeetingForm" class="p-4 sm:p-5 space-y-4 overflow-y-auto flex-1 bg-[#ffffff]">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#3d4942] mb-2">Meeting Strategic Agenda</label>
                    <textarea id="meetingAgendaInput" rows="3" required placeholder="e.g. 'We need to decide our Q1 SaaS product pricing, technical launch readiness, marketing budget, and operational roadmap.'" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text leading-relaxed"></textarea>
                    <p class="text-[10px] sm:text-[11px] text-[#68716d] mt-1">Each C-suite member will analyze this agenda from their domain and debate in real-time.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#3d4942] mb-2">Select Attendees</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" id="meetingAttendeesList">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="submitConveneBtn" class="w-full py-3 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold uppercase tracking-wider shadow-lg shadow-[#173d34]/20 transition-all flex items-center justify-center space-x-2">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>Commence Board Meeting</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: HIRE NEW EXECUTIVE -->
    <div id="hireModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[92dvh] sm:max-h-[90vh]">
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-[#f5f2e9] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#202725] text-sm sm:text-base">Hire an AI Specialist</h3>
                        <p class="text-[10px] sm:text-[11px] text-[#68716d]">Add a specialized domain leader to your team</p>
                    </div>
                </div>
                <button id="closeHireModalBtn" class="text-[#68716d] hover:text-[#173d34] p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="hireForm" class="p-4 sm:p-5 space-y-3.5 overflow-y-auto flex-1 bg-[#ffffff]">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#3d4942] mb-1">Executive Name</label>
                        <input type="text" id="hireName" placeholder="e.g. Jonathan Burke" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#3d4942] mb-1">C-Level Title</label>
                        <input type="text" id="hireTitle" placeholder="e.g. Chief Product Officer (CPO)" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34]">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Department</label>
                    <input type="text" id="hireDept" placeholder="e.g. Product Strategy & User Experience" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Assign to Business Venture</label>
                    <select id="hireVentureSelect" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] focus:outline-none focus:border-[#173d34]">
                        <!-- Populated dynamically via JS -->
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Avatar Photo URL</label>
                    <input type="url" id="hireAvatar" placeholder="Leave empty for auto-assigned portrait photo" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Background & Bio</label>
                    <textarea id="hireBio" rows="2" placeholder="Brief background of their past corporate career and achievements..." class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34]"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">System Instructions (Optional - auto-generated if left blank)</label>
                    <textarea id="hirePrompt" rows="2" placeholder="Leave empty and our AI will engineer an elite C-level executive persona!" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34]"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" id="submitHireBtn" class="w-full py-2.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-md shadow-[#173d34]/15">
                        Appoint & Onboard Executive
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: COMPANY & CEO PROFILE (MEMORY VAULT) -->
    <div id="memoriesModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[92dvh] sm:max-h-[85vh]">
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-[#f5f2e9] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i data-lucide="briefcase" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#202725] text-sm sm:text-base">Assistant Memory Vault</h3>
                        <p class="text-[10px] sm:text-[11px] text-[#68716d]">Facts, Tech Stack, Preferences & Rules remembered by RuXt</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button id="clearAllMemoriesFromModalBtn" type="button" title="Clear all memories" class="text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-2.5 py-1 rounded-xl text-xs font-semibold flex items-center space-x-1 border border-rose-200 transition-colors">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Clear All</span>
                    </button>
                    <button id="closeMemoriesBtn" class="text-[#68716d] hover:text-[#173d34] p-1 rounded-lg">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <div class="p-3 sm:p-4 border-b border-[#ddd8cc] bg-[#f5f2e9] space-y-2 flex-shrink-0">
                <p class="text-xs font-semibold text-[#3d4942]">Teach RuXt a new fact to remember:</p>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-2">
                    <select id="newMemoryCategory" class="bg-[#ffffff] border border-[#ddd8cc] rounded-xl px-2.5 py-2 text-xs text-[#202725] focus:outline-none focus:border-[#173d34]">
                        <option value="company">🏢 Company / Strategy</option>
                        <option value="tech">💻 Tech Stack / Tools</option>
                        <option value="finance">💰 Financial / Pricing</option>
                        <option value="preference">⭐ User Preference</option>
                        <option value="habit">⏰ Daily Routine</option>
                    </select>
                    <input type="text" id="newMemoryInput" placeholder="e.g. 'We use PostgreSQL & PHP', 'Our target ARR is $100K'" class="flex-1 bg-[#ffffff] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text">
                    <button id="addMemoryBtn" class="px-4 py-2 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold uppercase transition-all flex items-center justify-center space-x-1 shadow-sm">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Save</span>
                    </button>
                </div>
            </div>

            <div id="memoriesList" class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-2 bg-[#ffffff]">
                <!-- Rendered dynamically -->
            </div>
        </div>
    </div>

    <!-- MODAL 4: ACTION ITEMS & DELIVERABLES -->
    <div id="tasksModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-md overflow-hidden shadow-2xl flex flex-col max-h-[92dvh] sm:max-h-[85vh]">
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-[#f5f2e9] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i data-lucide="check-square" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#202725] text-sm sm:text-base">Quick Action Items</h3>
                        <p class="text-[10px] sm:text-[11px] text-[#68716d]">Simple task list and action items</p>
                    </div>
                </div>
                <button id="closeTasksBtn" class="text-[#68716d] hover:text-[#173d34] p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-3 sm:p-4 border-b border-[#ddd8cc] bg-[#f5f2e9] flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <input type="text" id="newTaskInput" placeholder="Add an executive action item..." class="flex-1 bg-[#ffffff] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text">
                    <button id="addTaskBtn" class="p-2.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-semibold transition-all shadow-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <div id="tasksList" class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-2 bg-[#ffffff]">
                <!-- Rendered dynamically -->
            </div>
        </div>
    </div>

    <!-- MODAL 5: SETTINGS & API (RuXt Light Theme) -->
    <div id="settingsModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[92dvh] sm:max-h-[90vh]">
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-[#f5f2e9] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#202725] text-sm sm:text-base">Assistant Settings & API</h3>
                        <p class="text-[10px] sm:text-[11px] text-[#68716d]">AI model configurations, token analytics and personal preferences</p>
                    </div>
                </div>
                <button id="closeSettingsBtn" class="text-[#68716d] hover:text-[#173d34] p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="settingsForm" class="p-4 sm:p-5 space-y-3.5 overflow-y-auto flex-1 bg-[#ffffff]">
                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Gemini API Key</label>
                    <input type="password" id="apiKeyInput" placeholder="Enter your Gemini API key" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#3d4942] mb-1">AI Engine Model</label>
                        <select id="modelSelect" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] focus:outline-none focus:border-[#173d34]">
                            <option value="gemini-3.8-flash">Gemini 3.8 Flash (High Reasoning)</option>
                            <option value="gemini-3-flash-preview">Gemini 3 Flash (Fast & Sharp)</option>
                            <option value="gemini-3.1-flash-lite">Gemini 3.1 Flash Lite (Ultra Fast)</option>
                            <option value="gemini-2.5-flash">Gemini 2.5 Flash (Balanced & Fast)</option>
                            <option value="gemini-2.0-flash">Gemini 2.0 Flash (Stable GA)</option>
                            <option value="gemini-1.5-flash">Gemini 1.5 Flash (Standard)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#3d4942] mb-1">Your Name / Title</label>
                        <input type="text" id="userNameInput" placeholder="CEO" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Company / Organization Name</label>
                    <input type="text" id="companyNameInput" placeholder="Enterprises" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text">
                </div>

                <!-- AI Token Usage & API Consumption Analytics Card -->
                <div class="pt-3 border-t border-[#ddd8cc]">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#173d34] flex items-center space-x-1.5">
                            <i data-lucide="zap" class="w-3.5 h-3.5 text-[#b7843f]"></i>
                            <span>Token Usage & API Analytics</span>
                        </span>
                        <div class="flex items-center space-x-1.5">
                            <span id="settingsTokenTodayBadge" class="text-[9px] bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded-full border border-emerald-200 font-mono font-bold">Today: 0 T</span>
                            <button type="button" id="refreshTokenStatsBtn" title="Refresh Live Token Stats" class="p-1 rounded-lg text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] transition-colors cursor-pointer">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>

                    <!-- 4-Stat Metric Cards Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-2.5">
                        <!-- Total Tokens -->
                        <div class="p-2.5 rounded-xl bg-[#fcfaf5] border border-[#ddd8cc] flex flex-col justify-between hover:border-[#b7843f]/60 transition-colors">
                            <span class="text-[10px] font-semibold text-[#68716d] flex items-center justify-between">
                                <span>Total Tokens</span>
                                <i data-lucide="coins" class="w-3 h-3 text-[#b7843f]"></i>
                            </span>
                            <div class="mt-1">
                                <div id="tokenTotalCount" class="text-sm sm:text-base font-extrabold text-[#173d34] font-mono leading-tight">0</div>
                                <div class="text-[9px] text-[#68716d] mt-0.5">Lifetime consumed</div>
                            </div>
                        </div>

                        <!-- Prompt (In) Tokens -->
                        <div class="p-2.5 rounded-xl bg-[#fcfaf5] border border-[#ddd8cc] flex flex-col justify-between hover:border-sky-300 transition-colors">
                            <span class="text-[10px] font-semibold text-[#68716d] flex items-center justify-between">
                                <span>Prompt (In)</span>
                                <i data-lucide="arrow-down-left" class="w-3 h-3 text-sky-600"></i>
                            </span>
                            <div class="mt-1">
                                <div id="tokenPromptCount" class="text-sm sm:text-base font-bold text-[#202725] font-mono leading-tight">0</div>
                                <div class="text-[9px] text-[#68716d] mt-0.5">Context & prompt</div>
                            </div>
                        </div>

                        <!-- Output Tokens -->
                        <div class="p-2.5 rounded-xl bg-[#fcfaf5] border border-[#ddd8cc] flex flex-col justify-between hover:border-emerald-300 transition-colors">
                            <span class="text-[10px] font-semibold text-[#68716d] flex items-center justify-between">
                                <span>Output (Out)</span>
                                <i data-lucide="arrow-up-right" class="w-3 h-3 text-emerald-600"></i>
                            </span>
                            <div class="mt-1">
                                <div id="tokenCompletionCount" class="text-sm sm:text-base font-bold text-[#202725] font-mono leading-tight">0</div>
                                <div class="text-[9px] text-[#68716d] mt-0.5">AI responses</div>
                            </div>
                        </div>

                        <!-- API Requests -->
                        <div class="p-2.5 rounded-xl bg-[#fcfaf5] border border-[#ddd8cc] flex flex-col justify-between hover:border-purple-300 transition-colors">
                            <span class="text-[10px] font-semibold text-[#68716d] flex items-center justify-between">
                                <span>API Calls</span>
                                <i data-lucide="activity" class="w-3 h-3 text-purple-600"></i>
                            </span>
                            <div class="mt-1">
                                <div id="tokenRequestsCount" class="text-sm sm:text-base font-bold text-[#202725] font-mono leading-tight">0</div>
                                <div class="text-[9px] text-[#68716d] mt-0.5">Total requests</div>
                            </div>
                        </div>
                    </div>

                    <!-- Model Breakdown & Reset Section -->
                    <div class="p-2.5 rounded-xl bg-[#f5f2e9]/70 border border-[#ddd8cc] text-[11px] space-y-2">
                        <div class="flex items-center justify-between text-[#3d4942]">
                            <span class="font-bold flex items-center space-x-1">
                                <i data-lucide="layers" class="w-3 h-3 text-[#173d34]"></i>
                                <span>Model Usage Breakdown</span>
                            </span>
                            <button type="button" id="resetTokenUsageBtn" class="text-[10px] text-rose-600 hover:text-rose-800 font-semibold flex items-center space-x-1 cursor-pointer transition-colors" title="Reset Token Tracking Counter">
                                <i data-lucide="rotate-ccw" class="w-2.5 h-2.5"></i>
                                <span>Reset Stats</span>
                            </button>
                        </div>

                        <!-- Models Container -->
                        <div id="tokenModelsContainer" class="space-y-1.5">
                            <div class="text-[10px] text-[#68716d] italic">Loading usage breakdown...</div>
                        </div>

                        <!-- Last Request Footnote -->
                        <div id="tokenLastRequestInfo" class="pt-1.5 border-t border-[#ddd8cc]/80 text-[10px] text-[#68716d] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                            <span>Last Call: <span id="tokenLastModelText" class="font-mono font-semibold text-[#202725]">-</span></span>
                            <span id="tokenLastCountText" class="font-mono text-[#173d34] font-bold">-</span>
                        </div>
                    </div>
                </div>

                <!-- Voice & Audio Configuration Section -->
                <div class="pt-3 border-t border-[#ddd8cc]">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#173d34] flex items-center space-x-1.5">
                            <i data-lucide="volume-2" class="w-3.5 h-3.5 text-[#173d34]"></i>
                            <span>Voice & Speech System</span>
                        </span>
                        <button type="button" id="testVoiceBtn" class="px-2.5 py-1 rounded-full bg-[#f5f2e9] hover:bg-[#ede8dc] text-[#173d34] text-[10px] font-bold flex items-center space-x-1 border border-[#ddd8cc] transition-colors">
                            <i data-lucide="play" class="w-2.5 h-2.5 text-[#173d34]"></i>
                            <span>Test Voice</span>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-[11px] font-medium text-[#3d4942] mb-1">Voice Audio Provider</label>
                            <select id="ttsProviderSelect" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] focus:outline-none focus:border-[#173d34]">
                                <option value="browser">High-Definition Neural (Default, 100% Free)</option>
                                <option value="elevenlabs">ElevenLabs Studio AI (Hyper-Realistic)</option>
                                <option value="openai">OpenAI TTS (tts-1)</option>
                            </select>
                        </div>

                        <!-- ElevenLabs Key Input -->
                        <div id="elevenlabsKeyGroup" class="space-y-1">
                            <label class="block text-[11px] font-medium text-[#3d4942]">ElevenLabs API Key <span class="text-[#68716d]">(Optional)</span></label>
                            <input type="password" id="elevenlabsKeyInput" placeholder="Enter your ElevenLabs API key" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text">
                        </div>

                        <!-- OpenAI Key Input -->
                        <div id="openaiKeyGroup" class="space-y-1">
                            <label class="block text-[11px] font-medium text-[#3d4942]">OpenAI API Key <span class="text-[#68716d]">(Optional)</span></label>
                            <input type="password" id="openaiKeyInput" placeholder="Enter your OpenAI API key" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text">
                        </div>

                        <!-- Auto Spoken Response Checkbox -->
                        <label class="flex items-center space-x-2 text-xs text-[#3d4942] cursor-pointer pt-1">
                            <input type="checkbox" id="autoSpeakCheckbox" class="rounded border-[#ddd8cc] bg-[#fcfaf5] text-[#173d34] focus:ring-0">
                            <span>Read assistant chat replies aloud automatically with voice</span>
                        </label>
                    </div>
                </div>

                <!-- CEO Memory Vault & Knowledge Section -->
                <div class="pt-3 border-t border-[#ddd8cc]">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#173d34] flex items-center space-x-1.5">
                            <i data-lucide="brain" class="w-3.5 h-3.5 text-[#173d34]"></i>
                            <span>CEO Permanent Memory Vault</span>
                        </span>
                        <span id="settingsMemoriesCountBadge" class="text-[10px] bg-[#173d34]/10 text-[#173d34] px-2 py-0.5 rounded-full border border-[#173d34]/20 font-mono font-bold">0 memories</span>
                    </div>
                    <p class="text-[11px] text-[#68716d] leading-relaxed mb-2.5">
                        RuXt remembers your background, preferences, strategic directives, and corporate decisions across all conversations.
                    </p>
                    <div class="p-3 rounded-xl bg-rose-50/70 border border-rose-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                        <div>
                            <div class="text-xs font-bold text-rose-900 flex items-center space-x-1.5">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-600"></i>
                                <span>Wipe CEO Memories</span>
                            </div>
                            <div class="text-[10px] text-rose-700 mt-0.5">Permanently erase all facts & knowledge remembered about you</div>
                        </div>
                        <button type="button" id="clearAllMemoriesBtn" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center space-x-1.5 self-start sm:self-auto flex-shrink-0 cursor-pointer">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            <span>Clear All Memories</span>
                        </button>
                    </div>
                </div>

                <!-- Account & Authentication Section -->
                <div class="pt-3 border-t border-[#ddd8cc]">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#173d34] flex items-center space-x-1.5">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-[#173d34]"></i>
                            <span>Account & Authentication</span>
                        </span>
                        <span class="text-[10px] bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded-full font-mono font-bold">
                            <?= strtoupper(htmlspecialchars($currentUser['role'] ?? 'USER')) ?>
                        </span>
                    </div>

                    <div class="p-3 rounded-xl bg-[#f5f2e9]/70 border border-[#ddd8cc] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center space-x-3 min-w-0">
                            <div class="w-10 h-10 rounded-2xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                                <?= htmlspecialchars(strtoupper(substr($currentUser['name'] ?? 'U', 0, 1))) ?>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-[#202725] truncate"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></div>
                                <div class="text-[11px] text-[#68716d] truncate"><?= htmlspecialchars($currentUser['email'] ?? '') ?></div>
                                <div class="text-[10px] text-[#8c5e1e] font-mono mt-0.5">@<?= htmlspecialchars($currentUser['username'] ?? 'user') ?></div>
                            </div>
                        </div>
                        <a href="logout.php" class="px-3.5 py-1.5 bg-[#fff1f2] hover:bg-[#e11d48] text-[#be123c] hover:text-[#ffffff] border border-[#fecdd3] hover:border-[#e11d48] rounded-xl text-xs font-bold transition-all shadow-sm flex items-center space-x-1.5 self-start sm:self-center cursor-pointer active:scale-95" title="Sign out of your account">
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                            <span>Sign Out</span>
                        </a>
                    </div>
                </div>

                <!-- Google OAuth Configuration Section -->
                <div class="pt-3 border-t border-[#ddd8cc]">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#173d34] flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                            </svg>
                            <span>Google Sign-In OAuth</span>
                        </span>
                        <span id="googleAuthStatusBadge" class="text-[10px] bg-stone-100 text-stone-600 px-2 py-0.5 rounded-full border border-stone-200 font-mono font-bold">
                            Checking...
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div>
                            <label class="block text-[11px] font-medium text-[#3d4942] mb-1">Google OAuth Client ID</label>
                            <input type="text" id="googleClientIdInput" placeholder="xxxx.apps.googleusercontent.com" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text font-mono text-[11px]">
                        </div>
                        <p class="text-[10px] text-[#68716d]">
                            Enables 1-click "Sign in with Google" on the login screen. Obtain this from Google Cloud Console.
                        </p>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-md shadow-[#173d34]/15">
                        Save Configurations
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 6: LAUNCH NEW BUSINESS VENTURE & AI SPECIALIST TEAM -->
    <div id="ventureModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[92dvh] sm:max-h-[90vh]">
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-[#f5f2e9] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i data-lucide="briefcase" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#202725] text-sm sm:text-base">Launch New Business Venture</h3>
                        <p class="text-[10px] sm:text-[11px] text-[#68716d]">Create an independent workspace with its own AI Specialist Team</p>
                    </div>
                </div>
                <button id="closeVentureModalBtn" type="button" class="text-[#68716d] hover:text-[#173d34] p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="ventureForm" class="p-4 sm:p-5 space-y-3.5 overflow-y-auto flex-1 bg-[#ffffff]">
                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Business / Venture Name</label>
                    <input type="text" id="ventureNameInput" placeholder="e.g. AI Labs, Nexa Healthtech, RuXt Digital Media" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Domain / Industry</label>
                    <input type="text" id="ventureDomainInput" placeholder="e.g. Artificial Intelligence & SaaS, E-Commerce, Hospitality" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Strategic Mission & Scope</label>
                    <textarea id="ventureDescInput" rows="2" placeholder="Briefly describe what this venture does, its target audience, and primary revenue model..." class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text leading-relaxed"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#3d4942] mb-1">Icon Style</label>
                        <select id="ventureIconSelect" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] focus:outline-none focus:border-[#173d34]">
                            <option value="code">💻 Code & Tech</option>
                            <option value="shopping-bag">🛍️ Retail & E-Commerce</option>
                            <option value="cpu">🧠 AI & Machine Learning</option>
                            <option value="rocket">🚀 Startup / Innovation</option>
                            <option value="bar-chart-3">📈 Finance & Investment</option>
                            <option value="globe">🌐 Media & Global Agency</option>
                            <option value="stethoscope">🩺 Health & Wellness</option>
                            <option value="wrench">⚙️ Engineering & Hardware</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#3d4942] mb-1">Brand Accent Color</label>
                        <select id="ventureColorSelect" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] focus:outline-none focus:border-[#173d34]">
                            <option value="cyan">Cyan / Tech</option>
                            <option value="emerald">Emerald / Growth</option>
                            <option value="purple">Purple / Creative</option>
                            <option value="amber">Amber / Prestige</option>
                            <option value="rose">Rose / Consumer</option>
                            <option value="indigo">Indigo / Enterprise</option>
                        </select>
                    </div>
                </div>

                <!-- Auto Generate Specialist AI Team Checkbox -->
                <div class="p-3 rounded-2xl bg-[#f5f2e9] border border-[#ddd8cc]">
                    <label class="flex items-start space-x-2.5 cursor-pointer">
                        <input type="checkbox" id="autoGenerateTeamCheck" checked class="w-4 h-4 mt-0.5 rounded text-[#173d34] bg-[#ffffff] border-[#ddd8cc] focus:ring-0">
                        <div class="text-xs">
                            <span class="font-bold text-[#173d34] block">Auto-Recruit 4 AI Specialists with Gemini AI</span>
                            <span class="text-[#68716d] block mt-0.5">Gemini will automatically recruit 4 specialized domain leaders tailored to this venture with real portraits and personalities!</span>
                        </div>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" id="submitVentureBtn" class="w-full py-2.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold uppercase tracking-wider shadow-lg shadow-[#173d34]/20 transition-all flex items-center justify-center space-x-2">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>Launch Venture & Recruit Team</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 7: LIVE EXECUTIVE VOICE CALL (HANDS-FREE REAL VOICE CONVERSATION) -->
    <div id="voiceCallModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-xl hidden">
        <div class="relative w-full max-w-lg bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl overflow-hidden shadow-2xl p-4 sm:p-6 flex flex-col items-center text-center max-h-[95dvh] overflow-y-auto">
            
            <!-- Top Controls: Switch Executive & Close -->
            <div class="w-full flex items-center justify-between mb-2">
                <div class="flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#173d34]">Executive Voice Link</span>
                </div>
                <div class="flex items-center space-x-2">
                    <!-- Executive Selector Dropdown during call -->
                    <select id="callExecutiveSelect" class="bg-[#f5f2e9] border border-[#ddd8cc] text-xs text-[#173d34] font-semibold px-2.5 py-1 rounded-xl focus:outline-none focus:border-[#173d34] cursor-pointer">
                        <!-- Populated dynamically via JS -->
                    </select>
                    <button id="closeVoiceCallModalBtn" type="button" class="text-[#68716d] hover:text-[#173d34] p-1 rounded-lg hover:bg-[#f5f2e9] transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Animated Speaker Avatar with Audio Pulse Rings -->
            <div class="relative my-3 sm:my-4 flex items-center justify-center">
                <div id="callPulseRing" class="absolute w-32 h-32 sm:w-36 sm:h-36 md:w-44 md:h-44 rounded-full border-2 border-[#173d34]/20 animate-ping opacity-60"></div>
                <div id="callPulseRing2" class="absolute w-28 h-28 sm:w-32 sm:h-32 md:w-40 md:h-40 rounded-full border border-[#799a87]/30 animate-pulse"></div>
                <img id="callExecutiveAvatar" src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80" alt="Executive" class="relative w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 rounded-full object-cover border-4 border-[#173d34] shadow-2xl shadow-[#173d34]/20 transition-transform duration-300">
            </div>

            <!-- Executive Identity -->
            <h3 id="callExecutiveName" class="text-lg sm:text-xl font-bold text-[#202725] mb-0.5">RuXt</h3>
            <p id="callExecutiveTitle" class="text-[11px] sm:text-xs text-[#173d34] font-semibold uppercase tracking-wider mb-2.5 sm:mb-3">Private AI Assistant & Chief of Staff</p>

            <!-- Status Indicator Badge -->
            <div id="callStatusPill" class="px-3.5 py-1.5 rounded-full bg-[#f5f2e9] border border-[#ddd8cc] text-xs font-semibold text-[#173d34] flex items-center space-x-2 mb-2.5 sm:mb-3 shadow-sm">
                <span id="callStatusDot" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span id="callStatusText">Ready • Speak naturally</span>
            </div>

            <!-- Dynamic Audio Waveform Canvas / Bars -->
            <div class="w-full h-8 sm:h-10 flex items-center justify-center space-x-1.5 px-4 mb-2.5 sm:mb-3" id="callWaveform">
                <span class="waveform-bar active" style="height: 12px;"></span>
                <span class="waveform-bar active" style="height: 24px; animation-delay: 0.1s;"></span>
                <span class="waveform-bar active" style="height: 18px; animation-delay: 0.2s;"></span>
                <span class="waveform-bar active" style="height: 32px; animation-delay: 0.3s;"></span>
                <span class="waveform-bar active" style="height: 20px; animation-delay: 0.15s;"></span>
                <span class="waveform-bar active" style="height: 28px; animation-delay: 0.25s;"></span>
                <span class="waveform-bar active" style="height: 14px; animation-delay: 0.05s;"></span>
            </div>

            <!-- Live Subtitles / Conversation Box -->
            <div class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-2xl p-2.5 sm:p-3 min-h-[65px] sm:min-h-[75px] max-h-32 sm:max-h-36 overflow-y-auto text-left text-xs leading-relaxed mb-4 sm:mb-5 space-y-1.5 shadow-inner" id="callTranscriptBox">
                <p class="text-[#68716d] italic text-center text-[11px] pt-2">Conversation speech & replies appear here in real time...</p>
            </div>

            <!-- Call Controls Bar -->
            <div class="w-full flex items-center justify-center space-x-2 sm:space-x-3 pt-1">
                <!-- Hands-Free Auto Turn Toggle -->
                <button id="callHandsFreeBtn" type="button" class="px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-2xl bg-[#173d34]/10 border border-[#173d34]/30 text-[#173d34] text-xs font-semibold flex items-center space-x-1 sm:space-x-1.5 transition-all" title="Hands-free auto turn loop">
                    <i data-lucide="repeat" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Hands-Free: ON</span>
                </button>

                <!-- Mute / Unmute Mic Button -->
                <button id="callMuteBtn" type="button" class="p-2.5 sm:p-3.5 rounded-2xl bg-[#f5f2e9] hover:bg-[#ede8dc] text-[#173d34] border border-[#ddd8cc] transition-all" title="Mute/Unmute Mic">
                    <i id="callMuteIcon" data-lucide="mic" class="w-5 h-5 text-[#173d34]"></i>
                </button>

                <!-- End Call Button (Red Hero) -->
                <button id="endVoiceCallBtn" type="button" class="px-4 sm:px-5 py-2.5 sm:py-3 rounded-2xl bg-gradient-to-r from-red-600 to-rose-700 hover:from-red-500 hover:to-rose-600 text-white font-bold text-xs uppercase tracking-wider flex items-center space-x-1.5 sm:space-x-2 shadow-lg shadow-red-600/20 transition-all active:scale-95" title="End Voice Call">
                    <i data-lucide="phone-off" class="w-4 h-4"></i>
                    <span>End Call</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 8: TEAM DELIBERATION & WORK BREAKDOWN TRANSCRIPT -->
    <div id="discussionModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-3xl overflow-hidden shadow-2xl flex flex-col max-h-[92dvh] sm:max-h-[90vh]">
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-[#f5f2e9] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i data-lucide="message-circle-code" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2">
                            <h3 id="modalDiscussionTitle" class="font-bold text-[#202725] text-sm sm:text-base truncate">Team Deliberation Transcript</h3>
                            <span class="text-[9px] sm:text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20 font-bold">War Room</span>
                        </div>
                        <p id="modalDiscussionSubtitle" class="text-[10px] sm:text-[11px] text-[#68716d] truncate">RuXt orchestrating specialist work breakdown</p>
                    </div>
                </div>
                <button id="closeDiscussionModalBtn" type="button" class="text-[#68716d] hover:text-[#173d34] p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4 bg-[#ffffff]">
                <!-- Mandate Card -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-[#f5f2e9] border border-[#ddd8cc]">
                    <div class="kicker mb-1 flex items-center space-x-1.5">
                        <i data-lucide="compass" class="w-3.5 h-3.5 text-[#173d34]"></i>
                        <span>Strategic Mandate</span>
                    </div>
                    <p id="modalDiscussionPrompt" class="text-xs text-[#202725] leading-relaxed italic"></p>
                </div>

                <!-- Dialogue Turns Stream -->
                <div id="modalDiscussionTurnsStream" class="space-y-3">
                    <!-- Dialogue turns rendered via JS -->
                </div>

                <!-- Tasks Created From this Discussion -->
                <div class="pt-4 border-t border-[#ddd8cc]">
                    <div class="text-xs font-bold text-[#202725] mb-2.5 flex items-center space-x-2">
                        <i data-lucide="check-square" class="w-4 h-4 text-[#173d34]"></i>
                        <span>Action Items Agreed & Created</span>
                    </div>
                    <div id="modalDiscussionTasksList" class="space-y-2">
                        <!-- Tasks rendered via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 9: TEAM WAR ROOM / DELIBERATIONS ARCHIVE -->
    <div id="discussionsListModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[92dvh] sm:max-h-[85vh]">
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-[#f5f2e9] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i data-lucide="message-circle-code" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#202725] text-sm sm:text-base">Team War Room Archive</h3>
                        <p class="text-[10px] sm:text-[11px] text-[#68716d]">Cross-specialist deliberation sessions conducted by RuXt</p>
                    </div>
                </div>
                <button id="closeDiscussionsListModalBtn" type="button" class="text-[#68716d] hover:text-[#173d34] p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div id="discussionsArchiveList" class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-2.5 bg-[#ffffff]">
                <!-- Rendered dynamically -->
            </div>
        </div>
    </div>

    <!-- MODAL 10: CREATE / EDIT ACTION ITEM & SPRINT TASK -->
    <div id="taskDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[92dvh] sm:max-h-[90vh]">
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-[#f5f2e9] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i data-lucide="check-square" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 id="taskModalTitle" class="font-bold text-[#202725] text-sm sm:text-base">Create Action Item</h3>
                        <p class="text-[10px] sm:text-[11px] text-[#68716d]">Assign a deliverable to an executive specialist</p>
                    </div>
                </div>
                <button id="closeTaskDetailModalBtn" type="button" class="text-[#68716d] hover:text-[#173d34] p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="taskDetailForm" class="p-4 sm:p-5 space-y-3.5 overflow-y-auto flex-1 bg-[#ffffff]">
                <input type="hidden" id="taskEditId" value="">

                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Task Title</label>
                    <input type="text" id="taskTitleInput" placeholder="e.g. Build authentication endpoints and JWT validation" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Deliverable Description</label>
                    <textarea id="taskContentInput" rows="2" placeholder="Key specifications, API requirements, or acceptance criteria..." class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text leading-relaxed"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#3d4942] mb-1">Project / Initiative</label>
                        <select id="taskProjectSelect" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] focus:outline-none focus:border-[#173d34]">
                            <!-- Populated dynamically via JS -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#3d4942] mb-1">Assigned Specialist</label>
                        <select id="taskAssigneeSelect" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] focus:outline-none focus:border-[#173d34]">
                            <!-- Populated dynamically via JS -->
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#3d4942] mb-1">Sprint Status</label>
                        <select id="taskStatusSelect" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] focus:outline-none focus:border-[#173d34]">
                            <option value="todo">📋 To Do</option>
                            <option value="in_progress">⚡ In Progress</option>
                            <option value="review">🔍 In Review</option>
                            <option value="completed">✅ Completed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#3d4942] mb-1">Priority</label>
                        <select id="taskPrioritySelect" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] focus:outline-none focus:border-[#173d34]">
                            <option value="High">🔴 High Priority</option>
                            <option value="Medium" selected>🟡 Medium Priority</option>
                            <option value="Low">🟢 Low Priority</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#3d4942] mb-1">Target Due Date</label>
                        <input type="date" id="taskDueDateInput" class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] focus:outline-none focus:border-[#173d34]">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="saveTaskDetailBtn" class="w-full py-2.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold uppercase tracking-wider shadow-lg shadow-[#173d34]/20 transition-all flex items-center justify-center space-x-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Save Action Item</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 11: NEW PROJECT MODAL -->
    <div id="newProjectModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-md overflow-hidden shadow-2xl flex flex-col max-h-[92dvh] sm:max-h-[90vh]">
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-[#f5f2e9] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i data-lucide="folder-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#202725] text-sm sm:text-base">New Project</h3>
                        <p class="text-[10px] sm:text-[11px] text-[#68716d]">Create a separate project to track deliverables with RuXt</p>
                    </div>
                </div>
                <button id="closeNewProjectModalBtn" type="button" class="text-[#68716d] hover:text-[#173d34] p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="newProjectForm" class="p-4 sm:p-5 space-y-4 overflow-y-auto flex-1 bg-[#ffffff]">
                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Project Name</label>
                    <input type="text" id="newProjectNameInput" placeholder="e.g. Real-Time Chat Engine, WhatsApp Bot" required class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Description / Goals</label>
                    <textarea id="newProjectDescInput" rows="2" placeholder="Brief objectives, tech stack, or target outcomes..." class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34] select-text leading-relaxed"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#3d4942] mb-1">Color Theme</label>
                    <div class="flex items-center space-x-2" id="projectColorPicker">
                        <button type="button" data-color="indigo" class="w-7 h-7 rounded-lg bg-[#173d34] border-2 border-[#173d34] project-color-opt shadow-sm"></button>
                        <button type="button" data-color="cyan" class="w-7 h-7 rounded-lg bg-[#799a87] border-2 border-transparent project-color-opt shadow-sm"></button>
                        <button type="button" data-color="emerald" class="w-7 h-7 rounded-lg bg-[#285c4e] border-2 border-transparent project-color-opt shadow-sm"></button>
                        <button type="button" data-color="amber" class="w-7 h-7 rounded-lg bg-[#b7843f] border-2 border-transparent project-color-opt shadow-sm"></button>
                        <button type="button" data-color="rose" class="w-7 h-7 rounded-lg bg-rose-500 border-2 border-transparent project-color-opt shadow-sm"></button>
                        <button type="button" data-color="purple" class="w-7 h-7 rounded-lg bg-purple-500 border-2 border-transparent project-color-opt shadow-sm"></button>
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" id="submitNewProjectBtn" class="w-full py-2.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold uppercase tracking-wider shadow-lg shadow-[#173d34]/20 transition-all flex items-center justify-center space-x-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Create Project</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 12: MEMBER LIVE WORK INSPECTOR & PREVIEW CONSOLE -->
    <div id="memberWorkPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-4xl overflow-hidden shadow-2xl flex flex-col max-h-[94dvh] sm:max-h-[92vh]">
            <!-- Header -->
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-[#f5f2e9] flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3.5 min-w-0">
                    <div class="relative flex-shrink-0">
                        <img id="workPreviewAvatar" src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80" alt="Executive" class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl object-cover border-2 border-[#173d34]/30 shadow-md">
                        <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-[#ffffff]"></span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2">
                            <h3 id="workPreviewExecutiveName" class="font-bold text-[#202725] text-sm sm:text-base truncate">Executive Specialist</h3>
                            <span id="workPreviewStatusBadge" class="text-[9px] sm:text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#b7843f]/15 text-[#9a6d32] border border-[#b7843f]/30 flex items-center space-x-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#b7843f] animate-ping"></span>
                                <span>IN PROGRESS</span>
                            </span>
                        </div>
                        <p id="workPreviewExecutiveTitle" class="text-[11px] text-[#68716d] font-mono truncate">Lead Specialist</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button id="closeWorkPreviewModalBtn" type="button" class="text-[#68716d] hover:text-[#173d34] p-1.5 rounded-xl hover:bg-[#ede8dc] transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Task Progress Strip -->
            <div class="px-4 sm:px-5 py-3 bg-[#f0ede4] border-b border-[#ddd8cc] flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 flex-shrink-0">
                <div class="min-w-0">
                    <div class="flex items-center space-x-2">
                        <span class="kicker">Current Mandate:</span>
                        <span id="workPreviewTaskTitle" class="text-xs font-semibold text-[#202725] truncate">Architecture & Execution Deliverable</span>
                    </div>
                    <div class="flex items-center space-x-2 mt-0.5 text-[10px] text-[#68716d] font-mono">
                        <i data-lucide="globe" class="w-3 h-3 text-[#173d34]"></i>
                        <span id="workPreviewTargetSystem" class="truncate text-[#173d34]">Platform Architecture</span>
                    </div>
                </div>
                <div class="w-full sm:w-56 flex-shrink-0">
                    <div class="flex items-center justify-between text-[10px] font-mono mb-1">
                        <span class="text-[#68716d]">Execution Progress</span>
                        <span id="workPreviewProgressText" class="text-[#173d34] font-bold">75%</span>
                    </div>
                    <div class="w-full bg-[#ede8dc] rounded-full h-2 overflow-hidden border border-[#ddd8cc]">
                        <div id="workPreviewProgressBar" class="h-2 rounded-full bg-[#173d34] transition-all duration-500" style="width: 75%"></div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation Bar -->
            <div class="flex items-center px-4 sm:px-5 pt-3 pb-0 bg-[#f5f2e9] border-b border-[#ddd8cc] space-x-2 overflow-x-auto scrollbar-none flex-shrink-0">
                <button type="button" onclick="window.switchWorkPreviewTab('terminal')" id="wpTabBtnTerminal" class="px-3.5 py-2 text-xs font-semibold rounded-t-xl transition-all border-b-2 border-[#173d34] text-[#173d34] bg-[#ffffff] flex items-center space-x-1.5 flex-shrink-0 shadow-sm">
                    <i data-lucide="terminal" class="w-3.5 h-3.5 text-[#173d34]"></i>
                    <span>Live Code & Terminal Stream</span>
                </button>
                <button type="button" onclick="window.switchWorkPreviewTab('visual')" id="wpTabBtnVisual" class="px-3.5 py-2 text-xs font-semibold rounded-t-xl transition-all border-b-2 border-transparent text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] flex items-center space-x-1.5 flex-shrink-0">
                    <i data-lucide="layout" class="w-3.5 h-3.5 text-[#799a87]"></i>
                    <span>Visual & Live Environment</span>
                </button>
                <button type="button" onclick="window.switchWorkPreviewTab('findings')" id="wpTabBtnFindings" class="px-3.5 py-2 text-xs font-semibold rounded-t-xl transition-all border-b-2 border-transparent text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] flex items-center space-x-1.5 flex-shrink-0">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-[#173d34]"></i>
                    <span>Audit Findings & Safety Check</span>
                </button>
            </div>

            <!-- Tab Contents Container -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-5 bg-[#fcfaf5]">
                <!-- Tab Pane 1: Terminal Logs -->
                <div id="wpPaneTerminal" class="space-y-4">
                    <!-- Step Checklist -->
                    <div class="p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm">
                        <div class="kicker mb-2 flex items-center space-x-1.5">
                            <i data-lucide="list-checks" class="w-3.5 h-3.5 text-[#173d34]"></i>
                            <span>Execution Roadmap Checklist:</span>
                        </div>
                        <div id="workPreviewChecklist" class="space-y-1.5">
                            <!-- Checklist items populated via JS -->
                        </div>
                    </div>

                    <!-- Live Terminal Output -->
                    <div class="rounded-2xl bg-[#173d34] border border-[#173d34]/40 shadow-xl overflow-hidden font-mono text-xs">
                        <div class="px-3.5 py-2 bg-[#12372f] border-b border-white/10 flex items-center justify-between text-[11px] text-[#c3d1b9]">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                                <span class="text-[#f6f5ea] text-[10px] ml-1">ruxt@executive-terminal: ~/workspace</span>
                            </div>
                            <span class="text-[10px] text-[#c3d1b9] flex items-center space-x-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                <span>ONLINE</span>
                            </span>
                        </div>
                        <div id="workPreviewTerminalLogs" class="p-4 space-y-2 max-h-72 overflow-y-auto font-mono text-xs leading-relaxed text-[#f6f5ea]">
                            <!-- Logs populated via JS -->
                        </div>
                    </div>
                </div>

                <!-- Tab Pane 2: Visual System Inspection Preview -->
                <div id="wpPaneVisual" class="space-y-4 hidden">
                    <div class="rounded-2xl bg-[#ffffff] border border-[#ddd8cc] overflow-hidden shadow-xl">
                        <div class="px-3.5 py-2.5 bg-[#f5f2e9] border-b border-[#ddd8cc] flex items-center space-x-3 text-xs">
                            <div class="flex items-center space-x-1.5 text-[#68716d]">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            </div>
                            <div class="flex-1 bg-[#ffffff] border border-[#ddd8cc] rounded-lg px-3 py-1 text-[11px] text-[#202725] font-mono flex items-center space-x-2">
                                <i data-lucide="lock" class="w-3 h-3 text-[#173d34]"></i>
                                <span id="workPreviewUrlDisplay" class="truncate">https://workspace.internal/inspection</span>
                            </div>
                            <span class="text-[10px] bg-[#173d34]/10 text-[#173d34] px-2 py-0.5 rounded-full font-mono font-bold">SYSTEM INSPECTION</span>
                        </div>

                        <div id="workPreviewVisualContainer" class="p-4 space-y-4 bg-[#fcfaf5]">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>

                <!-- Tab Pane 3: Audit Findings & Safety Verification -->
                <div id="wpPaneFindings" class="space-y-4 hidden">
                    <div id="workPreviewFindingsContainer" class="space-y-4">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>
            </div>

            <!-- Footer Action Controls -->
            <div class="p-3.5 sm:p-4 bg-[#f5f2e9] border-t border-[#ddd8cc] flex flex-col sm:flex-row items-center justify-between gap-3 flex-shrink-0">
                <span class="text-[11px] text-[#68716d] flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-[#173d34] animate-pulse"></span>
                    <span id="workPreviewLiveStatusText">Live member execution active • Real-time terminal output</span>
                </span>
                <div class="flex items-center flex-wrap gap-2 w-full sm:w-auto justify-end">
                    <button type="button" id="simulateStepWorkBtn" onclick="window.simulateWorkPreviewStep()" class="px-3.5 py-1.5 bg-[#ffffff] hover:bg-[#ede8dc] text-[#173d34] border border-[#ddd8cc] rounded-full text-xs font-bold transition-all shadow-sm flex items-center space-x-1.5 active:scale-95" title="Run next execution step in real-time">
                        <i data-lucide="play" class="w-3 h-3 fill-current text-[#b7843f]"></i>
                        <span>Simulate Next Step</span>
                    </button>
                    <button type="button" id="chatWithMemberBtn" onclick="window.chatWithExecutiveAboutCurrentTask()" class="px-3.5 py-1.5 bg-[#ffffff] hover:bg-[#ede8dc] text-[#173d34] border border-[#ddd8cc] rounded-full text-xs font-bold transition-all shadow-sm flex items-center space-x-1.5">
                        <i data-lucide="message-square" class="w-3 h-3 text-[#173d34]"></i>
                        <span>Brief in Chat</span>
                    </button>
                    <button type="button" id="deliverWorkReportBtn" onclick="window.deliverWorkReportNow()" class="px-4 py-1.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] font-bold rounded-full text-xs flex items-center justify-center space-x-1.5 shadow-md shadow-[#173d34]/15 active:scale-95 transition-all">
                        <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                        <span>Deliver Final Report</span>
                    </button>
                    <button type="button" id="closeWorkPreviewFooterBtn" class="px-3.5 py-1.5 bg-[#ede8dc] hover:bg-[#ddd8cc] text-[#202725] rounded-full text-xs font-semibold transition-all">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 13: CONFIRM CLEAR ALL MEMORIES -->
    <div id="confirmClearMemoriesModal" class="fixed inset-0 z-[70] flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-rose-200 rounded-2xl sm:rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl flex flex-col animate-in fade-in zoom-in-95 duration-150">
            <div class="p-5 text-center flex flex-col items-center">
                <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mb-3 shadow-inner">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-[#202725] text-base mb-1">Clear RuXt Memories?</h3>
                <p class="text-xs text-[#68716d] leading-relaxed mb-3">
                    Are you sure you want to permanently erase all <span id="confirmMemCount" class="font-bold text-rose-700">0</span> memories stored about you (CEO)?
                </p>
                <div class="w-full bg-[#fcfaf5] border border-amber-200 rounded-xl p-2.5 text-[11px] text-amber-800 text-left flex items-start space-x-2">
                    <i data-lucide="info" class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5"></i>
                    <span>This action is permanent and cannot be undone. RuXt will forget all your personal preferences, company guidelines, and past facts.</span>
                </div>
            </div>
            <div class="p-3.5 sm:p-4 bg-[#f5f2e9] border-t border-[#ddd8cc] flex items-center justify-end space-x-2">
                <button type="button" id="cancelClearMemoriesBtn" class="px-4 py-2 rounded-xl text-xs font-semibold text-[#68716d] hover:bg-[#ede8dc] hover:text-[#202725] transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="button" id="confirmClearMemoriesBtn" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-rose-600/20 flex items-center space-x-1.5 cursor-pointer">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Yes, Clear All</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 14: PREDEFINED WORKSPACE STORE & TEMPLATES MARKETPLACE -->
    <div id="workspaceStoreModal" class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/50 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-5xl overflow-hidden shadow-2xl flex flex-col max-h-[92dvh] sm:max-h-[90vh]">
            <!-- Store Header -->
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-[#fcfaf5] flex flex-col sm:flex-row sm:items-center justify-between gap-3 flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/15 border border-amber-500/30 text-amber-700 flex items-center justify-center flex-shrink-0 shadow-sm">
                        <i data-lucide="sparkles" class="w-5 h-5 text-amber-600"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-[#202725] text-sm sm:text-base">Predefined Workspace Marketplace</h3>
                            <span class="text-[9px] px-2 py-0.5 rounded-full font-mono font-bold bg-amber-100 text-amber-800 border border-amber-300">TURNKEY BLUEPRINTS</span>
                        </div>
                        <p class="text-[10px] sm:text-[11px] text-[#68716d]">Deploy industry-tailored workspaces pre-loaded with specialized C-suite AI teams, roadmaps & SOPs.</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="relative flex-1 sm:w-56">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-[#68716d] absolute left-3 top-2.5"></i>
                        <input type="text" id="storeSearchInput" placeholder="Search blueprints..." class="w-full bg-[#ffffff] border border-[#ddd8cc] rounded-xl pl-8 pr-3 py-1.5 text-xs text-[#202725] placeholder-[#9ca3af] focus:outline-none focus:border-[#173d34]">
                    </div>
                    <button id="closeWorkspaceStoreBtn" type="button" class="text-[#68716d] hover:text-[#173d34] p-1.5 rounded-lg hover:bg-[#ede8dc] transition-colors" title="Close Store">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Category Filter Pills -->
            <div class="px-4 py-2.5 bg-[#f5f2e9] border-b border-[#ddd8cc] flex items-center space-x-1.5 overflow-x-auto no-scrollbar flex-shrink-0 text-xs">
                <button type="button" class="store-filter-btn px-3 py-1 rounded-full font-bold transition-all bg-[#173d34] text-[#fcfaf5] shadow-sm" data-filter="all">All Blueprints</button>
                <button type="button" class="store-filter-btn px-3 py-1 rounded-full font-semibold transition-all bg-[#ffffff] text-[#3d4942] hover:bg-[#ede8dc] border border-[#ddd8cc]" data-filter="free">🎁 Free Starters</button>
                <button type="button" class="store-filter-btn px-3 py-1 rounded-full font-semibold transition-all bg-[#ffffff] text-[#3d4942] hover:bg-[#ede8dc] border border-[#ddd8cc]" data-filter="paid">💎 Pro Suites ($USD)</button>
                <button type="button" class="store-filter-btn px-3 py-1 rounded-full font-semibold transition-all bg-[#ffffff] text-[#3d4942] hover:bg-[#ede8dc] border border-[#ddd8cc]" data-filter="content">Content & Media</button>
                <button type="button" class="store-filter-btn px-3 py-1 rounded-full font-semibold transition-all bg-[#ffffff] text-[#3d4942] hover:bg-[#ede8dc] border border-[#ddd8cc]" data-filter="ecommerce">E-Commerce & DTC</button>
                <button type="button" class="store-filter-btn px-3 py-1 rounded-full font-semibold transition-all bg-[#ffffff] text-[#3d4942] hover:bg-[#ede8dc] border border-[#ddd8cc]" data-filter="tech">SaaS & Tech</button>
                <button type="button" class="store-filter-btn px-3 py-1 rounded-full font-semibold transition-all bg-[#ffffff] text-[#3d4942] hover:bg-[#ede8dc] border border-[#ddd8cc]" data-filter="business">Consulting & Property</button>
            </div>

            <!-- Templates Grid Container -->
            <div class="p-4 sm:p-5 overflow-y-auto flex-1 bg-[#ffffff]">
                <div id="workspaceStoreLoading" class="flex flex-col items-center justify-center py-16 space-y-3">
                    <span class="w-8 h-8 border-3 border-[#173d34] border-t-transparent rounded-full animate-spin"></span>
                    <span class="text-xs text-[#68716d] font-mono">Loading curated workspace blueprints...</span>
                </div>
                <div id="workspaceStoreGrid" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Populated dynamically via JS -->
                </div>
                <div id="workspaceStoreEmpty" class="hidden text-center py-12">
                    <div class="w-12 h-12 rounded-2xl bg-[#f5f2e9] text-[#68716d] flex items-center justify-center mx-auto mb-2">
                        <i data-lucide="package-search" class="w-6 h-6"></i>
                    </div>
                    <div class="text-xs font-bold text-[#202725]">No workspace blueprints matched your search</div>
                    <div class="text-[11px] text-[#68716d] mt-0.5">Try searching for keywords like "YouTube", "Marketing", "SaaS", or "Shopify"</div>
                </div>
            </div>

            <!-- Store Footer Notice -->
            <div class="p-3 sm:p-4 bg-[#fcfaf5] border-t border-[#ddd8cc] flex flex-col sm:flex-row items-center justify-between text-xs text-[#68716d] gap-2 flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-[11px]">Deploying a predefined workspace is voluntary. Your <strong>Primary Workspace</strong> remains untouched.</span>
                </div>
                <button type="button" onclick="document.getElementById('workspaceStoreModal').classList.add('hidden')" class="px-4 py-1.5 rounded-xl border border-[#ddd8cc] hover:bg-[#ede8dc] text-[#202725] text-xs font-semibold transition-all">
                    Close Marketplace
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 15: WORKSPACE TEMPLATE PREVIEW DRAWER -->
    <div id="workspacePreviewModal" class="fixed inset-0 z-[60] flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-3xl overflow-hidden shadow-2xl flex flex-col max-h-[92dvh]">
            <!-- Preview Header -->
            <div id="previewHeaderBanner" class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-gradient-to-r from-emerald-500/10 to-transparent flex items-start justify-between flex-shrink-0">
                <div class="flex items-start space-x-3.5">
                    <div id="previewIconBox" class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-700 flex items-center justify-center flex-shrink-0 border border-emerald-500/30 shadow-sm mt-0.5">
                        <i id="previewIcon" data-lucide="briefcase" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 id="previewTitle" class="font-bold text-[#202725] text-base sm:text-lg">Workspace Title</h3>
                            <span id="previewBadge" class="text-[9px] px-2 py-0.5 rounded-full font-mono font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">STARTER</span>
                            <span id="previewPriceBadge" class="text-[10px] px-2.5 py-0.5 rounded-full font-bold bg-[#173d34] text-[#fcfaf5] shadow-sm">FREE</span>
                        </div>
                        <div id="previewDomain" class="text-xs text-[#173d34] font-semibold font-mono">Industry & Domain</div>
                    </div>
                </div>
                <button id="closePreviewModalBtn" type="button" class="text-[#68716d] hover:text-[#173d34] p-1.5 rounded-lg hover:bg-[#ede8dc] transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Preview Body Details -->
            <div class="p-4 sm:p-6 overflow-y-auto space-y-5 flex-1 bg-[#ffffff]">
                <!-- Description -->
                <div>
                    <h4 class="text-xs font-bold text-[#3d4942] uppercase tracking-wider mb-1.5">Executive Overview</h4>
                    <p id="previewDescription" class="text-xs text-[#202725] leading-relaxed bg-[#fcfaf5] p-3 rounded-2xl border border-[#ddd8cc]"></p>
                </div>

                <!-- Features Highlights -->
                <div>
                    <h4 class="text-xs font-bold text-[#3d4942] uppercase tracking-wider mb-2">Turnkey Capabilities Included</h4>
                    <div id="previewFeaturesList" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <!-- Populated via JS -->
                    </div>
                </div>

                <!-- AI Executive Team Roster -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold text-[#3d4942] uppercase tracking-wider">Specialized AI Executive Team</h4>
                        <span id="previewTeamCountBadge" class="text-[10px] font-mono text-[#68716d]">4 Members</span>
                    </div>
                    <div id="previewExecutivesList" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <!-- Populated via JS -->
                    </div>
                </div>

                <!-- Starter Roadmaps & Projects -->
                <div>
                    <h4 class="text-xs font-bold text-[#3d4942] uppercase tracking-wider mb-2">Pre-Loaded Roadmaps & Deliverables</h4>
                    <div id="previewProjectsList" class="space-y-2">
                        <!-- Populated via JS -->
                    </div>
                </div>

                <!-- Starter SOPs & Tasks -->
                <div>
                    <h4 class="text-xs font-bold text-[#3d4942] uppercase tracking-wider mb-2">Standard Operating Procedures (SOP Checklists)</h4>
                    <div id="previewTasksList" class="space-y-1.5">
                        <!-- Populated via JS -->
                    </div>
                </div>
            </div>

            <!-- Preview Actions Footer -->
            <div class="p-4 bg-[#f5f2e9] border-t border-[#ddd8cc] flex items-center justify-between flex-shrink-0">
                <button type="button" id="previewBackBtn" class="px-4 py-2 rounded-xl text-xs font-semibold text-[#68716d] hover:bg-[#ede8dc] hover:text-[#202725] transition-colors">
                    Back to Marketplace
                </button>
                <div class="flex items-center space-x-2">
                    <button type="button" id="previewPrimaryActionBtn" class="px-5 py-2.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold shadow-md shadow-[#173d34]/20 transition-all flex items-center space-x-2">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span id="previewPrimaryActionText">Deploy Workspace</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 16: WORKSPACE CHECKOUT & PROMO CODE (SANDBOX SIMULATOR) -->
    <div id="workspaceCheckoutModal" class="fixed inset-0 z-[70] flex items-center justify-center p-2.5 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-md overflow-hidden shadow-2xl flex flex-col max-h-[92dvh]">
            <!-- Checkout Header -->
            <div class="p-4 sm:p-5 border-b border-[#ddd8cc] bg-gradient-to-r from-amber-500/10 to-transparent flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-800 flex items-center justify-center flex-shrink-0 border border-amber-500/30">
                        <i data-lucide="shield-check" class="w-5 h-5 text-amber-700"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#202725] text-sm sm:text-base">Unlock Pro Blueprint</h3>
                        <p class="text-[10px] text-[#68716d]">Secure checkout & instant provisioning</p>
                    </div>
                </div>
                <button id="closeCheckoutModalBtn" type="button" class="text-[#68716d] hover:text-[#173d34] p-1.5 rounded-lg hover:bg-[#ede8dc] transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Checkout Body -->
            <div class="p-4 sm:p-5 space-y-4 overflow-y-auto flex-1 bg-[#ffffff]">
                <!-- Order Summary Card -->
                <div class="p-3.5 rounded-2xl bg-[#fcfaf5] border border-[#ddd8cc] space-y-2.5">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-[10px] font-mono uppercase font-bold text-[#173d34]" id="checkoutTemplateDomain">E-Commerce</div>
                            <div class="text-sm font-bold text-[#202725]" id="checkoutTemplateTitle">Blueprint Title</div>
                        </div>
                        <div class="text-right">
                            <div class="text-base font-bold text-[#173d34]" id="checkoutPriceText">$29.00 USD</div>
                            <div class="text-[9px] text-[#68716d]">One-Time Payment</div>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-[#e8e3d9] text-[11px] text-[#68716d] space-y-1">
                        <div class="flex items-center space-x-1.5">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0"></i>
                            <span>Complete dedicated C-Suite AI executive roster</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0"></i>
                            <span>Pre-loaded roadmaps, milestones & SOP checklists</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0"></i>
                            <span>Lifetime access with unrestricted multi-venture switching</span>
                        </div>
                    </div>
                </div>

                <!-- Sandbox Developer Notice -->
                <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/25 text-xs text-amber-900 flex items-start space-x-2.5">
                    <i data-lucide="info" class="w-4 h-4 text-amber-700 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <span class="font-bold block">Developer Test Sandbox Active</span>
                        <span class="text-[11px] text-amber-800 leading-relaxed block mt-0.5">Test mode simulates successful payment instantly without charging any real funds.</span>
                    </div>
                </div>

                <!-- Mock Payment Card Details -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-[#3d4942]">Test Card Number</label>
                    <div class="relative">
                        <i data-lucide="credit-card" class="w-4 h-4 text-[#68716d] absolute left-3 top-2.5"></i>
                        <input type="text" id="sandboxCardInput" value="4242 •••• •••• 4242" readonly class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl pl-9 pr-3 py-2 text-xs font-mono text-[#202725] focus:outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <label class="block text-[10px] text-[#68716d] mb-1">Expires</label>
                            <input type="text" value="12/28" readonly class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-1.5 text-xs font-mono text-[#202725] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] text-[#68716d] mb-1">CVC (Sandbox)</label>
                            <input type="text" value="789" readonly class="w-full bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-1.5 text-xs font-mono text-[#202725] focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Instant Test Purchase CTA -->
                <button type="button" id="submitSandboxPayBtn" class="w-full py-2.5 px-4 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] font-bold rounded-xl text-xs flex items-center justify-center space-x-2 transition-all shadow-md shadow-[#173d34]/20 cursor-pointer">
                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                    <span id="sandboxPayBtnText">Simulate Successful Test Payment</span>
                </button>

                <!-- Divider: Or Promo Code -->
                <div class="relative flex py-1 items-center">
                    <div class="flex-grow border-t border-[#e8e3d9]"></div>
                    <span class="flex-shrink mx-3 text-[10px] font-mono text-[#68716d] uppercase">Or Redeem Access Code</span>
                    <div class="flex-grow border-t border-[#e8e3d9]"></div>
                </div>

                <!-- Promo / License Key Section -->
                <div class="space-y-1.5">
                    <div class="flex items-center space-x-1.5">
                        <input type="text" id="checkoutPromoInput" placeholder="Enter VIP/License Code (e.g. VIPPRO2026)" class="flex-1 bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl px-3 py-2 text-xs font-mono uppercase text-[#202725] focus:outline-none focus:border-[#173d34]">
                        <button type="button" id="applyPromoBtn" class="px-3.5 py-2 bg-[#ffffff] hover:bg-[#f5f2e9] text-[#173d34] border border-[#ddd8cc] rounded-xl text-xs font-bold transition-all flex items-center space-x-1">
                            <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                            <span>Apply</span>
                        </button>
                    </div>
                    <div class="text-[10px] text-[#68716d] flex items-center justify-between">
                        <span>VIP Codes: <code class="font-bold text-[#173d34]">VIPPRO2026</code>, <code class="font-bold text-[#173d34]">LAUNCHFREE</code></span>
                    </div>
                </div>
            </div>

            <!-- Checkout Footer -->
            <div class="p-3 bg-[#fcfaf5] border-t border-[#ddd8cc] text-center text-[10px] text-[#68716d] flex-shrink-0">
                🔒 Safe & non-destructive. Added as an independent venture.
            </div>
        </div>
    </div>

    <!-- MODAL 17: WORKSPACE DEPLOYMENT CELEBRATION MODAL -->
    <div id="workspaceCelebrationModal" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-4 bg-[#202725]/60 backdrop-blur-md hidden">
        <div class="bg-[#ffffff] border border-[#ddd8cc] rounded-2xl sm:rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl flex flex-col text-center p-5 sm:p-6 animate-in fade-in zoom-in-95 duration-200">
            <div class="w-14 h-14 rounded-3xl bg-emerald-500/15 text-emerald-700 flex items-center justify-center mx-auto mb-3 border border-emerald-500/30 shadow-inner">
                <i data-lucide="check-check" class="w-7 h-7 text-emerald-600"></i>
            </div>
            <h3 class="font-bold text-[#202725] text-base mb-1" id="celebrationTitle">Workspace Ready!</h3>
            <p class="text-xs text-[#68716d] leading-relaxed mb-4" id="celebrationDesc">
                Your new workspace has been provisioned with its specialized AI executive team and starter deliverables.
            </p>
            <div class="space-y-2">
                <button type="button" id="celebrationSwitchNowBtn" class="w-full py-2.5 px-4 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold shadow-md shadow-[#173d34]/20 transition-all flex items-center justify-center space-x-1.5 cursor-pointer">
                    <i data-lucide="arrow-right-circle" class="w-4 h-4"></i>
                    <span>Switch to New Workspace Now</span>
                </button>
                <button type="button" id="celebrationStayBtn" class="w-full py-2 px-4 bg-[#ffffff] hover:bg-[#f5f2e9] text-[#68716d] hover:text-[#202725] rounded-xl text-xs font-semibold border border-[#ddd8cc] transition-all cursor-pointer">
                    Stay in Primary Workspace
                </button>
            </div>
        </div>
    </div>

    <!-- Image Lightbox Modal for Full-Resolution Reference Inspection -->
    <div id="imageLightboxModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md animate-fade-in" onclick="window.closeImageLightbox(event)">
        <div class="relative max-w-5xl max-h-[92vh] flex flex-col items-center">
            <button type="button" onclick="window.closeImageLightbox()" class="absolute -top-12 right-0 p-2 text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-full transition-all cursor-pointer">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            <img id="lightboxImg" src="" alt="Enlarged Reference Preview" class="max-w-full max-h-[82vh] object-contain rounded-2xl shadow-2xl border border-white/10" onclick="event.stopPropagation()">
            <div id="lightboxCaption" class="mt-3 text-xs font-mono text-white/90 bg-black/60 px-4 py-1.5 rounded-full backdrop-blur-sm truncate max-w-md text-center"></div>
        </div>
    </div>

    <!-- Main Logic Script -->
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
    <script>
        // Register Service Worker for Mobile PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('RuXt AI PWA registered:', reg.scope))
                    .catch(err => console.log('RuXt AI PWA registration note:', err));
            });
        }
    </script>
</body>
</html>
