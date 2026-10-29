// assets/js/app.js - Enterprise C-Suite & Boardroom Operating System

document.addEventListener('DOMContentLoaded', () => {
    // App State
    const state = {
        activeView: 'chat', // 'chat', 'boardroom', 'tasks'
        currentVentureId: 1,
        ventures: [],
        projects: [],
        taskFilterProject: '',
        taskViewMode: 'kanban', // 'kanban' or 'project_wise'
        selectedProjectColor: 'indigo',
        discussions: [],
        currentDiscussionId: null,
        taskFilterAssignee: '',
        currentSessionId: null,
        selectedExecutiveId: 1,
        executives: [],
        meetings: [],
        currentMeetingId: null,
        sessions: [],
        notes: [],
        memories: [],
        settings: {
            has_api_key: false,
            masked_api_key: '',
            model: 'gemini-3-flash-preview',
            company_name: 'Enterprises',
            user_name: 'CEO',
            assistant_name: 'Elena Vance'
        },
        isRecording: false,
        speechLang: 'en-US',
        autoSpeak: false,
        recognition: null,
        synth: window.speechSynthesis || null,
        cachedVoices: [],
        isSending: false,
        ttsProvider: 'browser',
        activeAudio: null,
        isCallActive: false,
        isCallMuted: false,
        callHandsFree: true,
        callRecognition: null,
        callCurrentExecutiveId: 1,
        isMeetingPlaying: false,
        pendingAttachments: [],
        storeTemplates: [],
        selectedStoreTemplate: null,
        activeStoreFilter: 'all',
        pendingCelebrationVentureId: null
    };

    if (state.synth) {
        state.cachedVoices = state.synth.getVoices();
        state.synth.onvoiceschanged = () => {
            state.cachedVoices = state.synth.getVoices();
        };
    }

    // DOM Elements
    const mainViewport = document.getElementById('mainViewport');
    const boardroomView = document.getElementById('boardroomView');
    const chatView = document.getElementById('chatView');
    const chatContainer = document.getElementById('chatContainer');
    const emptyState = document.getElementById('emptyState');
    const messagesList = document.getElementById('messagesList');
    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    const micBtn = document.getElementById('micBtn');
    const attachBtn = document.getElementById('attachBtn');
    const chatFileInput = document.getElementById('chatFileInput');
    const attachmentPreviewTray = document.getElementById('attachmentPreviewTray');
    const chatDropZone = document.getElementById('chatDropZone');
    const chatInputDockContainer = document.getElementById('chatInputDockContainer');
    const imageLightboxModal = document.getElementById('imageLightboxModal');
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const sessionsList = document.getElementById('sessionsList');
    const executivesList = document.getElementById('executivesList');
    const newConsultationBtn = document.getElementById('newConsultationBtn');
    const navBoardroomBtn = document.getElementById('navBoardroomBtn');
    const navRuXtBtn = document.getElementById('navRuXtBtn');
    const callMeetingBtn = document.getElementById('callMeetingBtn');
    const currentChatTitle = document.getElementById('currentChatTitle');
    const headerActiveAvatar = document.getElementById('headerActiveAvatar');
    const headerActiveSubtitle = document.getElementById('headerActiveSubtitle');
    const typingIndicator = document.getElementById('typingIndicator');
    const typingAvatar = document.getElementById('typingAvatar');
    const typingName = document.getElementById('typingName');
    const soundToggleBtn = document.getElementById('soundToggleBtn');
    const soundToggleIcon = document.getElementById('soundToggleIcon');
    const micLangBtn = document.getElementById('micLangBtn');
    const micLangText = document.getElementById('micLangText');
    const sidebarCompanyName = document.getElementById('sidebarCompanyName');

    // Venture Elements
    const ventureDropdownBtn = document.getElementById('ventureDropdownBtn');
    const ventureDropdownMenu = document.getElementById('ventureDropdownMenu');
    const ventureItemsList = document.getElementById('ventureItemsList');
    const activeVentureName = document.getElementById('activeVentureName');
    const activeVentureDomain = document.getElementById('activeVentureDomain');
    const activeVentureIcon = document.getElementById('activeVentureIcon');
    const activeVentureIconContainer = document.getElementById('activeVentureIconContainer');
    const headerVenturePill = document.getElementById('headerVenturePill');
    const headerVentureName = document.getElementById('headerVentureName');
    const headerVentureDot = document.getElementById('headerVentureDot');
    const ventureBadgeInSidebar = document.getElementById('ventureBadgeInSidebar');
    const openNewVentureBtn = document.getElementById('openNewVentureBtn');
    const btnAddNewVentureDropdown = document.getElementById('btnAddNewVentureDropdown');
    const ventureModal = document.getElementById('ventureModal');
    const closeVentureModalBtn = document.getElementById('closeVentureModalBtn');
    const ventureForm = document.getElementById('ventureForm');
    const hireVentureSelect = document.getElementById('hireVentureSelect');

    // Workspace Marketplace & Predefined Store Elements
    const openWorkspaceStoreBtn = document.getElementById('openWorkspaceStoreBtn');
    const btnOpenStoreDropdown = document.getElementById('btnOpenStoreDropdown');
    const headerStoreBtn = document.getElementById('headerStoreBtn');
    const workspaceStoreModal = document.getElementById('workspaceStoreModal');
    const closeWorkspaceStoreBtn = document.getElementById('closeWorkspaceStoreBtn');
    const storeSearchInput = document.getElementById('storeSearchInput');
    const workspaceStoreGrid = document.getElementById('workspaceStoreGrid');
    const workspaceStoreLoading = document.getElementById('workspaceStoreLoading');
    const workspaceStoreEmpty = document.getElementById('workspaceStoreEmpty');
    const workspacePreviewModal = document.getElementById('workspacePreviewModal');
    const closePreviewModalBtn = document.getElementById('closePreviewModalBtn');
    const previewBackBtn = document.getElementById('previewBackBtn');
    const previewPrimaryActionBtn = document.getElementById('previewPrimaryActionBtn');
    const previewPrimaryActionText = document.getElementById('previewPrimaryActionText');
    const workspaceCheckoutModal = document.getElementById('workspaceCheckoutModal');
    const closeCheckoutModalBtn = document.getElementById('closeCheckoutModalBtn');
    const submitSandboxPayBtn = document.getElementById('submitSandboxPayBtn');
    const sandboxPayBtnText = document.getElementById('sandboxPayBtnText');
    const checkoutPromoInput = document.getElementById('checkoutPromoInput');
    const applyPromoBtn = document.getElementById('applyPromoBtn');
    const workspaceCelebrationModal = document.getElementById('workspaceCelebrationModal');
    const celebrationSwitchNowBtn = document.getElementById('celebrationSwitchNowBtn');
    const celebrationStayBtn = document.getElementById('celebrationStayBtn');

    // Sidebar & Drawer
    const sidebar = document.getElementById('sidebar');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const closeSidebarBtn = document.getElementById('closeSidebarBtn');
    const mobileOverlay = document.getElementById('mobileOverlay');

    // Modals
    const callMeetingModal = document.getElementById('callMeetingModal');
    const closeCallMeetingBtn = document.getElementById('closeCallMeetingBtn');
    const boardMeetingForm = document.getElementById('boardMeetingForm');
    const meetingAgendaInput = document.getElementById('meetingAgendaInput');
    const meetingAttendeesList = document.getElementById('meetingAttendeesList');
    const submitConveneBtn = document.getElementById('submitConveneBtn');
    const boardroomSeats = document.getElementById('boardroomSeats');
    const boardroomDialoguesStream = document.getElementById('boardroomDialoguesStream');

    const hireModal = document.getElementById('hireModal');
    const openHireModalBtn = document.getElementById('openHireModalBtn');
    const closeHireModalBtn = document.getElementById('closeHireModalBtn');
    const hireForm = document.getElementById('hireForm');

    const memoriesModal = document.getElementById('memoriesModal');
    const openMemoriesBtn = document.getElementById('openMemoriesBtn');
    const closeMemoriesBtn = document.getElementById('closeMemoriesBtn');
    const memoriesList = document.getElementById('memoriesList');
    const newMemoryInput = document.getElementById('newMemoryInput');
    const newMemoryCategory = document.getElementById('newMemoryCategory');
    const addMemoryBtn = document.getElementById('addMemoryBtn');
    const memoriesCountBadge = document.getElementById('memoriesCountBadge');

    const tasksModal = document.getElementById('tasksModal');
    const openTasksBtn = document.getElementById('openTasksBtn');
    const closeTasksBtn = document.getElementById('closeTasksBtn');
    const tasksList = document.getElementById('tasksList');
    const newTaskInput = document.getElementById('newTaskInput');
    const addTaskBtn = document.getElementById('addTaskBtn');

    const settingsModal = document.getElementById('settingsModal');
    const openSettingsBtn = document.getElementById('openSettingsBtn');
    const closeSettingsBtn = document.getElementById('closeSettingsBtn');
    const settingsForm = document.getElementById('settingsForm');
    const apiKeyInput = document.getElementById('apiKeyInput');
    const modelSelect = document.getElementById('modelSelect');
    const userNameInput = document.getElementById('userNameInput');
    const companyNameInput = document.getElementById('companyNameInput');

    // Tasks Board View Elements
    const tasksBoardView = document.getElementById('tasksBoardView');
    const navTasksBoardBtn = document.getElementById('navTasksBoardBtn');
    const navDiscussionsBtn = document.getElementById('navDiscussionsBtn');
    const topToggleChatBtn = document.getElementById('topToggleChatBtn');
    const topToggleBoardroomBtn = document.getElementById('topToggleBoardroomBtn');
    const topToggleTasksBtn = document.getElementById('topToggleTasksBtn');
    const navTasksCountBadge = document.getElementById('navTasksCountBadge');
    const navDiscussionsCountBadge = document.getElementById('navDiscussionsCountBadge');
    const tasksVentureBadge = document.getElementById('tasksVentureBadge');
    const tasksProgressBar = document.getElementById('tasksProgressBar');
    const tasksProgressPct = document.getElementById('tasksProgressPct');
    const taskAssigneeFilter = document.getElementById('taskAssigneeFilter');
    const openNewTaskModalBtn = document.getElementById('openNewTaskModalBtn');
    const colTodoCards = document.getElementById('colTodoCards');
    const colInProgressCards = document.getElementById('colInProgressCards');
    const colReviewCards = document.getElementById('colReviewCards');
    const colCompletedCards = document.getElementById('colCompletedCards');
    const countColTodo = document.getElementById('countColTodo');
    const countColInProgress = document.getElementById('countColInProgress');
    const countColReview = document.getElementById('countColReview');
    const countColCompleted = document.getElementById('countColCompleted');

    // Project & Task View Mode Elements
    const btnViewKanban = document.getElementById('btnViewKanban');
    const btnViewProjectWise = document.getElementById('btnViewProjectWise');
    const taskProjectFilter = document.getElementById('taskProjectFilter');
    const projectPillsBar = document.getElementById('projectPillsBar');
    const kanbanLanesContainer = document.getElementById('kanbanLanesContainer');
    const projectWiseContainer = document.getElementById('projectWiseContainer');
    const headerProjectPill = document.getElementById('headerProjectPill');
    const headerProjectName = document.getElementById('headerProjectName');
    const taskProjectSelect = document.getElementById('taskProjectSelect');
    const newProjectModal = document.getElementById('newProjectModal');
    const openNewProjectModalBtn = document.getElementById('openNewProjectModalBtn');
    const closeNewProjectModalBtn = document.getElementById('closeNewProjectModalBtn');
    const newProjectForm = document.getElementById('newProjectForm');
    const newProjectNameInput = document.getElementById('newProjectNameInput');
    const newProjectDescInput = document.getElementById('newProjectDescInput');
    const projectColorPicker = document.getElementById('projectColorPicker');

    // Discussion Modals Elements
    const discussionModal = document.getElementById('discussionModal');
    const closeDiscussionModalBtn = document.getElementById('closeDiscussionModalBtn');
    const modalDiscussionTitle = document.getElementById('modalDiscussionTitle');
    const modalDiscussionSubtitle = document.getElementById('modalDiscussionSubtitle');
    const modalDiscussionPrompt = document.getElementById('modalDiscussionPrompt');
    const modalDiscussionTurnsStream = document.getElementById('modalDiscussionTurnsStream');
    const modalDiscussionTasksList = document.getElementById('modalDiscussionTasksList');

    const discussionsListModal = document.getElementById('discussionsListModal');
    const closeDiscussionsListModalBtn = document.getElementById('closeDiscussionsListModalBtn');
    const discussionsArchiveList = document.getElementById('discussionsArchiveList');

    // Task Detail Modal Elements
    const taskDetailModal = document.getElementById('taskDetailModal');
    const closeTaskDetailModalBtn = document.getElementById('closeTaskDetailModalBtn');
    const taskDetailForm = document.getElementById('taskDetailForm');
    const taskEditId = document.getElementById('taskEditId');
    const taskTitleInput = document.getElementById('taskTitleInput');
    const taskContentInput = document.getElementById('taskContentInput');
    const taskAssigneeSelect = document.getElementById('taskAssigneeSelect');
    const taskStatusSelect = document.getElementById('taskStatusSelect');
    const taskPrioritySelect = document.getElementById('taskPrioritySelect');
    const taskDueDateInput = document.getElementById('taskDueDateInput');
    const taskModalTitle = document.getElementById('taskModalTitle');

    // Live Voice Call Elements
    const startVoiceCallBtn = document.getElementById('startVoiceCallBtn');
    const voiceCallModal = document.getElementById('voiceCallModal');
    const closeVoiceCallModalBtn = document.getElementById('closeVoiceCallModalBtn');
    const endVoiceCallBtn = document.getElementById('endVoiceCallBtn');
    const callExecutiveSelect = document.getElementById('callExecutiveSelect');
    const callExecutiveAvatar = document.getElementById('callExecutiveAvatar');
    const callExecutiveName = document.getElementById('callExecutiveName');
    const callExecutiveTitle = document.getElementById('callExecutiveTitle');
    const callStatusPill = document.getElementById('callStatusPill');
    const callStatusDot = document.getElementById('callStatusDot');
    const callStatusText = document.getElementById('callStatusText');
    const callWaveform = document.getElementById('callWaveform');
    const callTranscriptBox = document.getElementById('callTranscriptBox');
    const callMicLangBtn = document.getElementById('callMicLangBtn');
    const callMicLangText = document.getElementById('callMicLangText');
    const callHandsFreeBtn = document.getElementById('callHandsFreeBtn');
    const callMuteBtn = document.getElementById('callMuteBtn');
    const callMuteIcon = document.getElementById('callMuteIcon');
    const callPulseRing = document.getElementById('callPulseRing');
    const callPulseRing2 = document.getElementById('callPulseRing2');

    // Voice Settings Elements
    const ttsProviderSelect = document.getElementById('ttsProviderSelect');
    const voiceLangSelect = document.getElementById('voiceLangSelect');
    const elevenlabsKeyGroup = document.getElementById('elevenlabsKeyGroup');
    const elevenlabsKeyInput = document.getElementById('elevenlabsKeyInput');
    const openaiKeyGroup = document.getElementById('openaiKeyGroup');
    const openaiKeyInput = document.getElementById('openaiKeyInput');
    const autoSpeakCheckbox = document.getElementById('autoSpeakCheckbox');
    const testVoiceBtn = document.getElementById('testVoiceBtn');

    // Markdown Parser
    if (typeof marked !== 'undefined') {
        marked.setOptions({ breaks: true, gfm: true });
    }

    // ============================================
    // 1. Enterprise Real Voice Engine & Audio Controller
    // ============================================
    function stopAllAudio() {
        if (state.synth) {
            state.synth.cancel();
        }
        if (state.activeAudio) {
            state.activeAudio.pause();
            state.activeAudio.currentTime = 0;
            state.activeAudio = null;
        }
        document.querySelectorAll('.speaker-glow, .voice-speaking-pulse').forEach(el => {
            el.classList.remove('speaker-glow', 'voice-speaking-pulse');
        });
        if (callExecutiveAvatar) {
            callExecutiveAvatar.classList.remove('voice-speaking-pulse', 'speaker-glow');
        }
    }

    function cleanTextForSpeech(text) {
        if (!text) return '';
        return text
            .replace(/```[\s\S]*?```/g, 'Code snippet omitted.')
            .replace(/`([^`]+)`/g, '$1')
            .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
            .replace(/[*#_~>|]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function playExecutiveVoice(text, executiveId = null, onEndCallback = null) {
        stopAllAudio();

        const cleanText = cleanTextForSpeech(text);
        if (!cleanText) {
            if (onEndCallback) onEndCallback();
            return;
        }

        const targetId = executiveId || state.selectedExecutiveId;
        const exec = state.executives.find(e => e.id == targetId) || {
            name: 'RuXt',
            voice_id: 'nova',
            voice_gender: 'female'
        };

        // If provider is ElevenLabs or OpenAI:
        if (state.ttsProvider === 'elevenlabs' || state.ttsProvider === 'openai') {
            if (cleanText.length > 400) {
                fetch('api/tts.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        text: cleanText,
                        executive_id: targetId,
                        provider: state.ttsProvider
                    })
                })
                .then(res => {
                    const contentType = res.headers.get('content-type') || '';
                    if (contentType.includes('audio/')) {
                        return res.blob();
                    }
                    throw new Error('Fallback to browser');
                })
                .then(blob => {
                    const blobUrl = URL.createObjectURL(blob);
                    const audio = new Audio(blobUrl);
                    state.activeAudio = audio;
                    audio.onended = () => {
                        URL.revokeObjectURL(blobUrl);
                        state.activeAudio = null;
                        if (onEndCallback) onEndCallback();
                    };
                    audio.onerror = () => {
                        URL.revokeObjectURL(blobUrl);
                        playBrowserSynthesis(cleanText, exec, onEndCallback);
                    };
                    audio.play().catch(() => playBrowserSynthesis(cleanText, exec, onEndCallback));
                })
                .catch(() => playBrowserSynthesis(cleanText, exec, onEndCallback));
                return;
            }

            const audioUrl = `api/tts.php?text=${encodeURIComponent(cleanText)}&executive_id=${targetId}&provider=${state.ttsProvider}`;
            const audio = new Audio(audioUrl);
            state.activeAudio = audio;

            audio.onended = () => {
                state.activeAudio = null;
                if (onEndCallback) onEndCallback();
            };

            audio.onerror = () => {
                console.warn('Cloud audio stream failed; falling back to high-definition neural browser voice.');
                playBrowserSynthesis(cleanText, exec, onEndCallback);
            };

            audio.play().catch(err => {
                console.warn('Audio play prevented or failed, fallback to browser synthesis:', err);
                playBrowserSynthesis(cleanText, exec, onEndCallback);
            });
            return;
        }

        // Default: High-Definition Client-side Neural Synthesis with tuned persona
        playBrowserSynthesis(cleanText, exec, onEndCallback);
    }

    function playBrowserSynthesis(cleanText, exec, onEndCallback) {
        if (!state.synth) {
            if (onEndCallback) onEndCallback();
            return;
        }

        state.synth.cancel();

        const utterance = new SpeechSynthesisUtterance(cleanText);
        const voices = (state.cachedVoices && state.cachedVoices.length > 0) ? state.cachedVoices : state.synth.getVoices();
        const hasSpecialScript = /[\u0D80-\u0DFF]/.test(cleanText);

        // Map pitch, rate, and timbre per executive identity
        let pitch = 1.0;
        let rate = 1.0;
        const gender = (exec.voice_gender || 'female').toLowerCase();

        switch (exec.voice_id) {
            case 'nova':
                pitch = 1.06;
                rate = 1.0;
                break;
            case 'shimmer': // Elena Vance (CTO)
                pitch = 1.02;
                rate = 1.03;
                break;
            case 'alloy': // Sarah Jenkins (COO) / Sophia
                pitch = 1.1;
                rate = 1.04;
                break;
            case 'fable': // Marcus Sterling (CMO)
                pitch = 0.94;
                rate = 1.05;
                break;
            case 'onyx': // David Chen (CFO)
                pitch = 0.86;
                rate = 0.95;
                break;
            case 'echo': // Alexander Wright (Counsel)
                pitch = 0.82;
                rate = 0.93;
                break;
            default:
                pitch = gender === 'female' ? 1.05 : 0.92;
                rate = 1.0;
        }

        utterance.pitch = pitch;
        utterance.rate = rate;

        // Smart voice selection
        let chosenVoice = null;
        if (hasSpecialScript) {
            chosenVoice = voices.find(v => v.lang.startsWith('si'));
        }

        if (!chosenVoice) {
            const naturalEnglish = voices.filter(v => 
                v.lang.startsWith('en') && (v.name.includes('Natural') || v.name.includes('Google') || v.name.includes('Online'))
            );

            if (gender === 'female') {
                chosenVoice = naturalEnglish.find(v => 
                    v.name.includes('Jenny') || v.name.includes('Aria') || v.name.includes('Zira') || 
                    v.name.includes('Samantha') || v.name.includes('Victoria') || v.name.includes('Female')
                ) || naturalEnglish[0];
            } else {
                chosenVoice = naturalEnglish.find(v => 
                    v.name.includes('Guy') || v.name.includes('David') || v.name.includes('George') || 
                    v.name.includes('Daniel') || v.name.includes('Male')
                ) || naturalEnglish[naturalEnglish.length > 1 ? 1 : 0];
            }
        }

        if (!chosenVoice && voices.length > 0) {
            chosenVoice = voices[0];
        }

        if (chosenVoice) {
            utterance.voice = chosenVoice;
        }

        utterance.onend = () => {
            if (onEndCallback) onEndCallback();
        };

        utterance.onerror = () => {
            if (onEndCallback) onEndCallback();
        };

        state.synth.speak(utterance);
    }

    // ============================================
    // 2. Chat Speech-to-Text Recognition
    // ============================================
    function initSpeechRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            if (micBtn) micBtn.style.display = 'none';
            if (micLangBtn) micLangBtn.style.display = 'none';
            return;
        }

        state.recognition = new SpeechRecognition();
        state.recognition.continuous = false;
        state.recognition.interimResults = true;
        state.recognition.lang = state.speechLang;

        state.recognition.onstart = () => {
            state.isRecording = true;
            micBtn.classList.add('mic-active');
            chatInput.placeholder = 'Listening... Speak in any language';
        };

        state.recognition.onresult = (event) => {
            let transcript = '';
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                transcript += event.results[i][0].transcript;
            }
            chatInput.value = transcript;
            autoResizeInput();
        };

        state.recognition.onerror = () => stopRecording();
        state.recognition.onend = () => {
            stopRecording();
            if (chatInput.value.trim().length > 0) chatInput.focus();
        };
    }

    function toggleRecording() {
        if (!state.recognition) return;
        if (state.isRecording) {
            state.recognition.stop();
        } else {
            try {
                stopAllAudio();
                state.recognition.lang = state.speechLang;
                state.recognition.start();
            } catch (e) {
                console.error(e);
            }
        }
    }

    function stopRecording() {
        state.isRecording = false;
        if (micBtn) micBtn.classList.remove('mic-active');
        if (chatInput) chatInput.placeholder = 'Address your executive in any language...';
    }

    function toggleMicLanguage() {
        state.speechLang = (state.speechLang === 'en-US') ? 'en-GB' : 'en-US';
        const label = 'EN';
        if (micLangText) micLangText.textContent = label;
        if (callMicLangText) callMicLangText.textContent = label;
        if (state.recognition) state.recognition.lang = state.speechLang;
        if (state.callRecognition) state.callRecognition.lang = state.speechLang;
    }

    // ============================================
    // 3. Hands-Free Interactive Live Voice Call
    // ============================================
    function initCallSpeechRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) return;

        state.callRecognition = new SpeechRecognition();
        state.callRecognition.continuous = false;
        state.callRecognition.interimResults = true;
        state.callRecognition.lang = state.speechLang;

        state.callRecognition.onstart = () => {
            if (!state.isCallActive) return;
            setCallStatus('listening', 'Listening to CEO... Speak naturally');
            setWaveformActive(true);
        };

        state.callRecognition.onresult = (event) => {
            if (!state.isCallActive) return;
            let interimTranscript = '';
            let finalTranscript = '';
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalTranscript += event.results[i][0].transcript;
                } else {
                    interimTranscript += event.results[i][0].transcript;
                }
            }

            const currentSpoken = finalTranscript || interimTranscript;
            if (currentSpoken && callTranscriptBox) {
                renderCallTranscriptSnippet('CEO', currentSpoken, false);
            }

            if (finalTranscript && finalTranscript.trim().length > 0) {
                handleCallUserSpeech(finalTranscript.trim());
            }
        };

        state.callRecognition.onerror = () => {
            if (state.isCallActive && state.callHandsFree && !state.isCallMuted) {
                setTimeout(() => resumeCallListening(), 800);
            }
        };

        state.callRecognition.onend = () => {
            setWaveformActive(false);
            if (state.isCallActive && state.callHandsFree && !state.isCallMuted && state.callStatus === 'listening') {
                setTimeout(() => resumeCallListening(), 600);
            }
        };
    }

    function setCallStatus(type, message) {
        state.callStatus = type;
        if (!callStatusText || !callStatusDot) return;
        callStatusText.textContent = message;

        callStatusDot.className = 'w-2 h-2 rounded-full';
        if (type === 'listening') {
            callStatusDot.classList.add('bg-emerald-400', 'animate-pulse');
        } else if (type === 'thinking') {
            callStatusDot.classList.add('bg-amber-400', 'animate-ping');
        } else if (type === 'speaking') {
            callStatusDot.classList.add('bg-cyan-400', 'animate-pulse');
        } else {
            callStatusDot.classList.add('bg-slate-500');
        }
    }

    function setWaveformActive(active) {
        if (!callWaveform) return;
        const bars = callWaveform.querySelectorAll('.waveform-bar');
        bars.forEach(b => {
            if (active) b.classList.add('active');
            else b.classList.remove('active');
        });
    }

    function renderCallTranscriptSnippet(speaker, text) {
        if (!callTranscriptBox) return;
        const item = document.createElement('div');
        item.className = 'text-xs leading-relaxed';
        const isUser = speaker === 'CEO';
        item.innerHTML = `<span class="font-bold ${isUser ? 'text-amber-400' : 'text-emerald-400'}">${escapeHtml(speaker)}:</span> <span class="text-slate-200">${escapeHtml(text)}</span>`;
        callTranscriptBox.appendChild(item);
        callTranscriptBox.scrollTop = callTranscriptBox.scrollHeight;
    }

    function startLiveVoiceCall(executiveId = null) {
        const targetId = executiveId || state.selectedExecutiveId;
        const exec = state.executives.find(e => e.id == targetId) || state.executives[0];
        if (!exec) return;

        state.isCallActive = true;
        state.isCallMuted = false;
        state.callCurrentExecutiveId = exec.id;

        // Populate Call Executive Dropdown
        if (callExecutiveSelect) {
            callExecutiveSelect.innerHTML = '';
            state.executives.forEach(ex => {
                const opt = document.createElement('option');
                opt.value = ex.id;
                opt.textContent = `${ex.name} (${ex.title.split('(')[0].trim()})`;
                if (ex.id == exec.id) opt.selected = true;
                callExecutiveSelect.appendChild(opt);
            });
            callExecutiveSelect.onchange = (e) => {
                switchCallExecutive(parseInt(e.target.value));
            };
        }

        updateCallExecutiveUI(exec);
        if (callTranscriptBox) {
            callTranscriptBox.innerHTML = `<p class="text-slate-500 italic text-center text-[11px] pt-1">Connecting to ${escapeHtml(exec.name)}...</p>`;
        }

        if (callMuteIcon) callMuteIcon.setAttribute('data-lucide', 'mic');
        if (callMicLangText) callMicLangText.textContent = 'EN';

        voiceCallModal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();

        // Initial Greeting
        const greeting = `Good day CEO. I am ${exec.name}. I am on the line and ready. How can I assist you?`;
        setCallStatus('speaking', `${exec.name} speaking...`);
        if (callExecutiveAvatar) callExecutiveAvatar.classList.add('voice-speaking-pulse');
        setWaveformActive(true);

        playExecutiveVoice(greeting, exec.id, () => {
            if (callExecutiveAvatar) callExecutiveAvatar.classList.remove('voice-speaking-pulse');
            setWaveformActive(false);
            renderCallTranscriptSnippet(exec.name, greeting);
            if (state.isCallActive) {
                resumeCallListening();
            }
        });
    }

    function updateCallExecutiveUI(exec) {
        if (callExecutiveAvatar) callExecutiveAvatar.src = exec.avatar_url;
        if (callExecutiveName) callExecutiveName.textContent = exec.name;
        if (callExecutiveTitle) callExecutiveTitle.textContent = exec.title;
    }

    function switchCallExecutive(newExecId) {
        const exec = state.executives.find(e => e.id == newExecId);
        if (!exec) return;
        state.callCurrentExecutiveId = exec.id;
        updateCallExecutiveUI(exec);

        const switchMsg = `CEO, ${exec.name} here. I have taken over the briefing. What are your instructions?`;
        setCallStatus('speaking', `${exec.name} speaking...`);
        if (callExecutiveAvatar) callExecutiveAvatar.classList.add('voice-speaking-pulse');
        setWaveformActive(true);

        playExecutiveVoice(switchMsg, exec.id, () => {
            if (callExecutiveAvatar) callExecutiveAvatar.classList.remove('voice-speaking-pulse');
            setWaveformActive(false);
            renderCallTranscriptSnippet(exec.name, switchMsg);
            if (state.isCallActive) resumeCallListening();
        });
    }

    function resumeCallListening() {
        if (!state.isCallActive || state.isCallMuted) return;
        if (!state.callRecognition) initCallSpeechRecognition();

        try {
            state.callRecognition.lang = state.speechLang;
            state.callRecognition.start();
            setCallStatus('listening', 'Listening to CEO... Speak naturally');
            setWaveformActive(true);
        } catch (e) {
            // Already active
        }
    }

    async function handleCallUserSpeech(userSpeech) {
        if (!state.isCallActive) return;
        setCallStatus('thinking', 'Executive analyzing instruction...');
        setWaveformActive(false);

        try {
            const res = await fetch('api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: userSpeech,
                    session_id: state.currentSessionId,
                    executive_id: state.callCurrentExecutiveId,
                    venture_id: state.currentVentureId
                })
            });
            const data = await res.json();
            if (!state.isCallActive) return;

            if (data.reply) {
                state.currentSessionId = data.session_id;
                if (data.token_stats) renderTokenUsage(data.token_stats);
                const exec = state.executives.find(e => e.id == state.callCurrentExecutiveId) || { name: 'Executive' };
                renderCallTranscriptSnippet(exec.name, data.reply);
                setCallStatus('speaking', `${exec.name} speaking...`);
                if (callExecutiveAvatar) callExecutiveAvatar.classList.add('voice-speaking-pulse');
                setWaveformActive(true);

                playExecutiveVoice(data.reply, state.callCurrentExecutiveId, () => {
                    if (callExecutiveAvatar) callExecutiveAvatar.classList.remove('voice-speaking-pulse');
                    setWaveformActive(false);
                    if (state.isCallActive && state.callHandsFree && !state.isCallMuted) {
                        resumeCallListening();
                    }
                });
            } else {
                setCallStatus('idle', 'Response issue. Resuming...');
                if (state.isCallActive) resumeCallListening();
            }
        } catch (err) {
            console.error('Call speech error:', err);
            if (state.isCallActive) resumeCallListening();
        }
    }

    function toggleCallMute() {
        state.isCallMuted = !state.isCallMuted;
        if (state.isCallMuted) {
            if (callMuteIcon) callMuteIcon.setAttribute('data-lucide', 'mic-off');
            callMuteBtn.classList.add('bg-red-900/60', 'border-red-500/50');
            setCallStatus('idle', 'Microphone Muted');
            if (state.callRecognition) {
                try { state.callRecognition.stop(); } catch (e) {}
            }
        } else {
            if (callMuteIcon) callMuteIcon.setAttribute('data-lucide', 'mic');
            callMuteBtn.classList.remove('bg-red-900/60', 'border-red-500/50');
            resumeCallListening();
        }
        if (window.lucide) lucide.createIcons();
    }

    function toggleCallHandsFree() {
        state.callHandsFree = !state.callHandsFree;
        if (callHandsFreeBtn) {
            const label = callHandsFreeBtn.querySelector('span');
            if (state.callHandsFree) {
                callHandsFreeBtn.className = 'px-3 py-2.5 rounded-2xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-400 text-xs font-semibold flex items-center space-x-1.5 transition-all';
                if (label) label.textContent = 'Hands-Free: ON';
            } else {
                callHandsFreeBtn.className = 'px-3 py-2.5 rounded-2xl bg-slate-800 border border-slate-700 text-slate-400 text-xs font-semibold flex items-center space-x-1.5 transition-all';
                if (label) label.textContent = 'Hands-Free: OFF';
            }
        }
    }

    function endLiveVoiceCall() {
        state.isCallActive = false;
        stopAllAudio();
        if (state.callRecognition) {
            try { state.callRecognition.stop(); } catch (e) {}
        }
        voiceCallModal.classList.add('hidden');
        if (state.currentSessionId) {
            loadSessionMessages(state.currentSessionId);
            loadSessions();
        }
    }

    // 2. C-Suite Executives Management
    async function loadExecutives() {
        try {
            const url = state.currentVentureId ? `api/executives.php?venture_id=${state.currentVentureId}` : 'api/executives.php';
            const res = await fetch(url);
            const data = await res.json();
            if (data.executives) {
                state.executives = data.executives;
                
                // Find RuXt (Chief of Staff & Lead Private Assistant)
                const ruxt = state.executives.find(e => e.is_chief_of_staff == 1);
                if (ruxt) {
                    const ruxtAvatar = document.getElementById('sidebarRuXtAvatar');
                    if (ruxtAvatar) ruxtAvatar.src = ruxt.avatar_url;
                    
                    const navRuXtBtn = document.getElementById('navRuXtBtn');
                    if (navRuXtBtn) {
                        navRuXtBtn.onclick = () => selectExecutive(ruxt.id);
                    }

                    const sidebarBrandContainer = document.getElementById('sidebarBrandContainer');
                    if (sidebarBrandContainer) {
                        sidebarBrandContainer.onclick = () => selectExecutive(ruxt.id);
                    }
                }

                renderExecutivesList();
                renderBoardroomSeats();
                populateMeetingAttendees();

                // Select default executive (RuXt if current selected is not in team)
                const exists = state.executives.some(e => e.id == state.selectedExecutiveId);
                if (!exists && ruxt) {
                    selectExecutive(ruxt.id);
                } else if (!state.selectedExecutiveId && ruxt) {
                    selectExecutive(ruxt.id);
                } else if (state.selectedExecutiveId) {
                    const currentEx = state.executives.find(e => e.id == state.selectedExecutiveId);
                    if (currentEx) {
                        currentChatTitle.textContent = currentEx.name;
                        headerActiveSubtitle.textContent = currentEx.title;
                        headerActiveAvatar.src = currentEx.avatar_url;
                    }
                }
            }
        } catch (err) {
            console.error('Failed to load executives:', err);
        }
    }

    function renderExecutivesList() {
        if (!executivesList) return;
        executivesList.innerHTML = '';

        const ruxt = state.executives.find(e => e.is_chief_of_staff == 1);
        const navRuXtBtn = document.getElementById('navRuXtBtn');
        if (navRuXtBtn && ruxt) {
            if (state.selectedExecutiveId == ruxt.id && state.activeView === 'chat') {
                navRuXtBtn.classList.add('border-[#ddd8cc]', 'bg-[#ffffff]', 'text-[#173d34]', 'shadow-sm');
                navRuXtBtn.classList.remove('text-[#3d4942]', 'border-transparent');
            } else {
                navRuXtBtn.classList.remove('border-[#ddd8cc]', 'bg-[#ffffff]', 'text-[#173d34]', 'shadow-sm');
                navRuXtBtn.classList.add('text-[#3d4942]', 'border-transparent');
            }
        }

        // Render C-Suite Leaders (excluding RuXt who is pinned at top)
        const teamLeaders = state.executives.filter(e => e.is_chief_of_staff != 1);

        teamLeaders.forEach(ex => {
            const isSelected = ex.id == state.selectedExecutiveId && state.activeView === 'chat';
            const btn = document.createElement('button');
            btn.className = `w-full flex items-center justify-between p-2 rounded-xl text-left transition-all duration-200 ${
                isSelected 
                    ? 'bg-[#ffffff] border border-[#ddd8cc] text-[#173d34] font-bold shadow-sm' 
                    : 'text-[#3d4942] hover:bg-[#ede8dc] hover:text-[#173d34]'
            }`;

            btn.onclick = () => selectExecutive(ex.id);

            btn.innerHTML = `
                <div class="flex items-center space-x-2.5 overflow-hidden flex-1">
                    <div class="relative flex-shrink-0">
                        <img src="${escapeHtml(ex.avatar_url)}" alt="${escapeHtml(ex.name)}" class="w-8 h-8 rounded-full object-cover border border-[#ddd8cc]">
                        <span class="absolute bottom-0 right-0 w-2 h-2 rounded-full bg-emerald-500 border border-[#ffffff]"></span>
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-xs font-bold truncate">${escapeHtml(ex.name)}</h4>
                        <p class="text-[10px] text-[#68716d] truncate">${escapeHtml(ex.title)}</p>
                    </div>
                </div>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-[#68716d] flex-shrink-0"></i>
            `;
            executivesList.appendChild(btn);
        });

        // Render Mobile Specialist Fast Switcher Bar (1-Tap direct executive messaging)
        const mobileExecutiveBar = document.getElementById('mobileExecutiveBar');
        if (mobileExecutiveBar) {
            mobileExecutiveBar.innerHTML = '';
            state.executives.forEach(ex => {
                const isSelected = ex.id == state.selectedExecutiveId && state.activeView === 'chat';
                const pill = document.createElement('button');
                pill.type = 'button';
                pill.className = `flex-shrink-0 flex items-center space-x-1.5 px-2.5 py-1 rounded-full text-xs transition-all active:scale-95 ${
                    isSelected
                        ? 'bg-[#ffffff] border border-[#173d34] text-[#173d34] font-bold shadow-sm'
                        : 'bg-[#ffffff] border border-[#ddd8cc] text-[#68716d] hover:text-[#202725]'
                }`;
                pill.onclick = () => selectExecutive(ex.id);

                const shortTitle = ex.title.includes('(') ? ex.title.split('(')[1].replace(')', '').trim() : ex.title.split(' ')[0];
                const firstName = ex.name.split(' ')[0];

                pill.innerHTML = `
                    <div class="relative flex-shrink-0">
                        <img src="${escapeHtml(ex.avatar_url)}" alt="${escapeHtml(ex.name)}" class="w-5 h-5 rounded-full object-cover border border-[#ddd8cc]">
                        <span class="absolute bottom-0 right-0 w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    </div>
                    <span class="truncate max-w-[85px]">${escapeHtml(firstName)}</span>
                    <span class="text-[9px] px-1 py-0.2 rounded font-mono font-semibold ${isSelected ? 'bg-[#173d34]/15 text-[#173d34]' : 'bg-[#ede8dc] text-[#68716d]'}">${escapeHtml(shortTitle)}</span>
                `;
                mobileExecutiveBar.appendChild(pill);
            });

            // Quick Hire button in mobile bar
            const hireBtn = document.createElement('button');
            hireBtn.type = 'button';
            hireBtn.className = 'flex-shrink-0 flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#ffffff] border border-[#ddd8cc] text-[#173d34] hover:bg-[#ede8dc] transition-all shadow-sm';
            hireBtn.onclick = openHireModal;
            hireBtn.innerHTML = `<i data-lucide="user-plus" class="w-3 h-3 text-[#173d34]"></i><span>Hire</span>`;
            mobileExecutiveBar.appendChild(hireBtn);
        }

        if (window.lucide) lucide.createIcons();
    }

    function populateMeetingAttendees() {
        if (!meetingAttendeesList) return;
        meetingAttendeesList.innerHTML = '';

        state.executives.forEach(ex => {
            const item = document.createElement('label');
            item.className = 'flex items-center space-x-2.5 p-2 rounded-xl bg-[#fcfaf5] border border-[#ddd8cc] hover:border-[#173d34] cursor-pointer shadow-sm';

            item.innerHTML = `
                <input type="checkbox" name="attendee" value="${ex.id}" checked class="w-4 h-4 rounded text-[#173d34] bg-[#ffffff] border-[#ddd8cc] focus:ring-0">
                <img src="${escapeHtml(ex.avatar_url)}" class="w-6 h-6 rounded-full object-cover border border-[#ddd8cc]">
                <div class="min-w-0">
                    <span class="text-xs font-semibold text-[#202725] block truncate">${escapeHtml(ex.name)}</span>
                    <span class="text-[10px] text-[#68716d] block truncate">${escapeHtml(ex.title)}</span>
                </div>
            `;
            meetingAttendeesList.appendChild(item);
        });
    }

    async function selectExecutive(execId) {
        state.selectedExecutiveId = execId;
        state.activeView = 'chat';
        closeMobileSidebar();
        switchToChatView();

        const exec = state.executives.find(e => e.id == execId) || state.executives[0];
        if (exec) {
            currentChatTitle.textContent = exec.name;
            headerActiveSubtitle.textContent = exec.title;
            headerActiveAvatar.src = exec.avatar_url;
            typingAvatar.src = exec.avatar_url;
            typingName.textContent = `${exec.name} is formulating response`;

            // Update empty state briefing
            document.getElementById('emptyStateAvatar').src = exec.avatar_url;
            document.getElementById('emptyStateTitle').textContent = `${exec.name} — ${exec.title}`;
            document.getElementById('emptyStateSubtitle').textContent = exec.is_chief_of_staff == 1 ? 'Lead Private Assistant & Work Breakdown Engine' : `${exec.department} Channel`;
            document.getElementById('emptyStateBio').textContent = `"${exec.bio || 'Ready for your executive consultation, CEO.'}"`;

            // Update prompt chips based on whether it is RuXt (Chief of Staff) or C-Suite Specialist
            const chipsContainer = document.getElementById('executiveChips');
            if (chipsContainer) {
                if (exec.is_chief_of_staff == 1) {
                    chipsContainer.innerHTML = `
                        <button onclick="window.sendPrompt('I have a new project, please break down deliverables and delegate to the executive team')" class="interactive-chip p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] text-xs text-[#202725] transition-all flex items-start space-x-2.5 group shadow-sm">
                            <i data-lucide="split" class="w-4 h-4 text-[#173d34] flex-shrink-0 mt-0.5"></i>
                            <span class="font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">Break down & delegate a project</span>
                        </button>
                        <button onclick="window.sendPrompt('Analyze our Q1 product launch readiness across Elena, Marcus, David, and Sarah')" class="interactive-chip p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] text-xs text-[#202725] transition-all flex items-start space-x-2.5 group shadow-sm">
                            <i data-lucide="layers" class="w-4 h-4 text-[#799a87] flex-shrink-0 mt-0.5"></i>
                            <span class="font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">Multi-department launch plan</span>
                        </button>
                        <button onclick="window.sendPrompt('Review our pending corporate deliverables and action items')" class="interactive-chip p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] text-xs text-[#202725] transition-all flex items-start space-x-2.5 group shadow-sm">
                            <i data-lucide="check-square" class="w-4 h-4 text-[#173d34] flex-shrink-0 mt-0.5"></i>
                            <span class="font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">Review executive deliverables</span>
                        </button>
                        <button onclick="window.sendPrompt('Help me plan my executive schedule and focus areas for today')" class="interactive-chip p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] text-xs text-[#202725] transition-all flex items-start space-x-2.5 group shadow-sm">
                            <i data-lucide="calendar" class="w-4 h-4 text-[#b7843f] flex-shrink-0 mt-0.5"></i>
                            <span class="font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">CEO daily focus & briefing</span>
                        </button>
                    `;
                } else {
                    chipsContainer.innerHTML = `
                        <button onclick="window.sendPrompt('What are your top priorities and recommendations for your department?')" class="interactive-chip p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] text-xs text-[#202725] transition-all flex items-start space-x-2.5 group shadow-sm">
                            <i data-lucide="target" class="w-4 h-4 text-[#173d34] flex-shrink-0 mt-0.5"></i>
                            <span class="font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">Departmental strategic priorities</span>
                        </button>
                        <button onclick="window.sendPrompt('Analyze the risk and return on our current roadmap')" class="interactive-chip p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] text-xs text-[#202725] transition-all flex items-start space-x-2.5 group shadow-sm">
                            <i data-lucide="shield-check" class="w-4 h-4 text-[#173d34] flex-shrink-0 mt-0.5"></i>
                            <span class="font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">Risk & feasibility analysis</span>
                        </button>
                        <button onclick="window.sendPrompt('How can we optimize our execution speed and reduce costs?')" class="interactive-chip p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] text-xs text-[#202725] transition-all flex items-start space-x-2.5 group shadow-sm">
                            <i data-lucide="trending-up" class="w-4 h-4 text-[#799a87] flex-shrink-0 mt-0.5"></i>
                            <span class="font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">Optimize performance & speed</span>
                        </button>
                        <button onclick="window.sendPrompt('Give me your executive assessment on our latest milestone')" class="interactive-chip p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] text-xs text-[#202725] transition-all flex items-start space-x-2.5 group shadow-sm">
                            <i data-lucide="file-text" class="w-4 h-4 text-[#b7843f] flex-shrink-0 mt-0.5"></i>
                            <span class="font-bold text-[#202725] group-hover:text-[#173d34] transition-colors">Executive milestone briefing</span>
                        </button>
                    `;
                }
                if (window.lucide) lucide.createIcons();
            }
        }

        renderExecutivesList();

        // Find existing session with messages for this executive
        const existingSession = state.sessions.find(s => s.executive_id == execId && parseInt(s.message_count, 10) > 0);
        if (existingSession) {
            selectSession(existingSession.id);
        } else {
            startNewChat(execId);
        }
    }

    // 3. Boardroom Round Table Mode
    function renderBoardroomSeats() {
        if (!boardroomSeats) return;
        boardroomSeats.innerHTML = '';

        state.executives.forEach(ex => {
            const seat = document.createElement('div');
            seat.className = 'flex flex-col items-center p-3 rounded-2xl bg-[#f5f2e9] border border-[#ddd8cc] hover:border-[#173d34] hover:bg-[#ffffff] text-center transition-all cursor-pointer group shadow-sm';
            seat.onclick = () => selectExecutive(ex.id);

            const activeTask = state.notes.find(n => (n.assigned_to || '').toLowerCase().includes(ex.name.toLowerCase()) && n.status === 'in_progress');
            const watchBtn = activeTask ? `
                <button type="button" onclick="event.stopPropagation(); window.openTaskLiveWorkPreview(${activeTask.id})" class="mt-2 text-[10px] px-2.5 py-0.5 rounded-full bg-[#b7843f]/15 hover:bg-[#b7843f]/25 text-[#9a6d32] border border-[#b7843f]/30 font-bold flex items-center space-x-1 transition-all shadow-xs" title="Watch ${escapeHtml(activeTask.title)} live execution">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#b7843f] animate-ping"></span>
                    <span>⚡ Watch Work</span>
                </button>
            ` : `
                <button type="button" onclick="event.stopPropagation(); window.openExecutiveLiveWork(${ex.id})" class="mt-2 text-[10px] px-2.5 py-0.5 rounded-full bg-[#173d34]/10 hover:bg-[#173d34]/20 text-[#173d34] border border-[#173d34]/20 font-bold flex items-center space-x-1 transition-all" title="Inspect executive live stream">
                    <span>⚡ Live Work</span>
                </button>
            `;

            seat.innerHTML = `
                <div class="relative mb-2">
                    <img src="${escapeHtml(ex.avatar_url)}" alt="${escapeHtml(ex.name)}" class="w-14 h-14 rounded-full object-cover border-2 border-[#ddd8cc] group-hover:border-[#173d34] transition-all shadow-md">
                    <span class="absolute bottom-0 right-0 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-[#ffffff]" title="Attending in person"></span>
                </div>
                <h4 class="text-xs font-bold text-[#202725] group-hover:text-[#173d34] truncate w-full">${escapeHtml(ex.name)}</h4>
                <p class="text-[10px] text-[#173d34] font-bold truncate w-full uppercase tracking-wider">${escapeHtml(ex.title)}</p>
                <p class="text-[10px] text-[#68716d] truncate w-full mt-0.5">${escapeHtml(ex.department)}</p>
                ${watchBtn}
            `;
            boardroomSeats.appendChild(seat);
        });
    }

    function switchToChatView() {
        state.activeView = 'chat';
        if (chatView) chatView.classList.remove('hidden');
        if (boardroomView) boardroomView.classList.add('hidden');
        if (tasksBoardView) tasksBoardView.classList.add('hidden');
        updateViewSwitchButtons();
        renderExecutivesList();
    }

    function switchToBoardroomView() {
        state.activeView = 'boardroom';
        closeMobileSidebar();
        if (chatView) chatView.classList.add('hidden');
        if (boardroomView) boardroomView.classList.remove('hidden');
        if (tasksBoardView) tasksBoardView.classList.add('hidden');
        updateViewSwitchButtons();
        renderExecutivesList();
        loadMeetings();
    }

    function switchToTasksView() {
        state.activeView = 'tasks';
        closeMobileSidebar();
        if (chatView) chatView.classList.add('hidden');
        if (boardroomView) boardroomView.classList.add('hidden');
        if (tasksBoardView) tasksBoardView.classList.remove('hidden');
        updateViewSwitchButtons();
        renderExecutivesList();
        loadNotes();
    }

    function updateViewSwitchButtons() {
        // Sidebar active styles
        const ruxt = state.executives.find(e => e.is_chief_of_staff == 1);
        if (navRuXtBtn && ruxt) {
            if (state.activeView === 'chat' && state.selectedExecutiveId == ruxt.id) {
                navRuXtBtn.className = 'w-full flex items-center justify-between p-2.5 rounded-xl bg-[#ffffff] border border-[#ddd8cc] text-left text-[#173d34] font-bold shadow-sm group';
            } else {
                navRuXtBtn.className = 'w-full flex items-center justify-between p-2.5 rounded-xl bg-transparent border border-transparent text-left text-[#3d4942] hover:bg-[#ede8dc] hover:text-[#173d34] group transition-all';
            }
        }
        if (navBoardroomBtn) {
            if (state.activeView === 'boardroom') {
                navBoardroomBtn.className = 'w-full flex items-center justify-between p-2.5 rounded-xl text-xs font-bold bg-[#ffffff] border border-[#ddd8cc] text-[#173d34] shadow-sm transition-all';
            } else {
                navBoardroomBtn.className = 'w-full flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold text-[#3d4942] hover:bg-[#ede8dc] hover:text-[#173d34] transition-all';
            }
        }
        if (navTasksBoardBtn) {
            if (state.activeView === 'tasks') {
                navTasksBoardBtn.className = 'w-full flex items-center justify-between p-2.5 rounded-xl text-xs font-bold bg-[#ffffff] border border-[#ddd8cc] text-[#173d34] shadow-sm transition-all';
            } else {
                navTasksBoardBtn.className = 'w-full flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold text-[#3d4942] hover:bg-[#ede8dc] hover:text-[#173d34] transition-all';
            }
        }

        // Top Header 3-way toggle (RuXt Pill Toggle)
        if (topToggleChatBtn) {
            topToggleChatBtn.className = state.activeView === 'chat'
                ? 'px-3.5 py-1 text-xs font-bold rounded-full text-[#173d34] bg-[#ffffff] border border-[#ddd8cc] shadow-sm flex items-center space-x-1.5 transition-all'
                : 'px-3.5 py-1 text-xs font-semibold rounded-full text-[#68716d] hover:text-[#173d34] flex items-center space-x-1.5 transition-all';
        }
        if (topToggleBoardroomBtn) {
            topToggleBoardroomBtn.className = state.activeView === 'boardroom'
                ? 'px-3.5 py-1 text-xs font-bold rounded-full text-[#173d34] bg-[#ffffff] border border-[#ddd8cc] shadow-sm flex items-center space-x-1.5 transition-all'
                : 'px-3.5 py-1 text-xs font-semibold rounded-full text-[#68716d] hover:text-[#173d34] flex items-center space-x-1.5 transition-all';
        }
        if (topToggleTasksBtn) {
            topToggleTasksBtn.className = state.activeView === 'tasks'
                ? 'px-3.5 py-1 text-xs font-bold rounded-full text-[#173d34] bg-[#ffffff] border border-[#ddd8cc] shadow-sm flex items-center space-x-1.5 transition-all'
                : 'px-3.5 py-1 text-xs font-semibold rounded-full text-[#68716d] hover:text-[#173d34] flex items-center space-x-1.5 transition-all';
        }

        // Mobile Bottom Navigation Bar Active Tab Highlights
        const bottomNavChatBtn = document.getElementById('bottomNavChatBtn');
        const bottomNavTasksBtn = document.getElementById('bottomNavTasksBtn');
        const bottomNavBoardroomBtn = document.getElementById('bottomNavBoardroomBtn');

        if (bottomNavChatBtn) {
            bottomNavChatBtn.className = state.activeView === 'chat'
                ? 'flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-[#173d34] font-bold transition-colors'
                : 'flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-[#68716d] hover:text-[#202725] transition-colors';
        }
        if (bottomNavTasksBtn) {
            bottomNavTasksBtn.className = state.activeView === 'tasks'
                ? 'flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-[#173d34] font-bold transition-colors'
                : 'flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-[#68716d] hover:text-[#202725] transition-colors';
        }
        if (bottomNavBoardroomBtn) {
            bottomNavBoardroomBtn.className = state.activeView === 'boardroom'
                ? 'flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-[#173d34] font-bold transition-colors'
                : 'flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-xl text-[#68716d] hover:text-[#202725] transition-colors';
        }
    }

    async function loadMeetings() {
        try {
            const url = state.currentVentureId ? `api/meetings.php?venture_id=${state.currentVentureId}` : 'api/meetings.php';
            const res = await fetch(url);
            const data = await res.json();
            if (data.meetings && data.meetings.length > 0) {
                state.meetings = data.meetings;
                if (!state.currentMeetingId || !state.meetings.some(m => m.id == state.currentMeetingId)) {
                    loadMeetingDetails(state.meetings[0].id);
                }
            } else {
                state.meetings = [];
                state.currentMeetingId = null;
                boardroomDialoguesStream.innerHTML = `
                    <div class="p-8 text-center rounded-2xl bg-slate-900/40 border border-slate-800 text-slate-400 text-xs">
                        <i data-lucide="landmark" class="w-10 h-10 mx-auto mb-2 opacity-30 text-amber-400"></i>
                        <p class="text-sm font-bold text-slate-300 mb-1">No Strategy Meetings Convened Yet for this Venture</p>
                        <p>Click "New Meeting Agenda" or "Convene Strategy Meeting" to summon the team!</p>
                    </div>
                `;
                if (window.lucide) lucide.createIcons();
            }
        } catch (err) {
            console.error('Failed to load meetings:', err);
        }
    }

    async function loadMeetingDetails(meetingId) {
        state.currentMeetingId = meetingId;
        try {
            const res = await fetch(`api/meetings.php?id=${meetingId}`);
            const data = await res.json();
            if (data.dialogues) {
                renderMeetingDialogues(data.meeting, data.dialogues);
            }
        } catch (err) {
            console.error('Failed to load meeting dialogues:', err);
        }
    }

    function renderMeetingDialogues(meeting, dialogues) {
        boardroomDialoguesStream.innerHTML = '';

        // Meeting Header Card with Audio Playback Action
        const headerCard = document.createElement('div');
        headerCard.className = 'p-5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm';
        headerCard.innerHTML = `
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20">Official Board Meeting Record</span>
                <span class="text-xs text-[#68716d] font-mono">${escapeHtml(meeting.created_at)}</span>
            </div>
            <h3 class="text-base font-bold text-[#202725] mb-1">${escapeHtml(meeting.title)}</h3>
            <p class="text-xs text-[#68716d] leading-relaxed italic mb-3"><span class="font-bold text-[#173d34]">Strategic Agenda:</span> "${escapeHtml(meeting.agenda)}"</p>
            <div class="flex items-center justify-between pt-3 border-t border-[#ddd8cc]">
                <span class="text-[11px] text-[#68716d] font-medium">${dialogues.length} Executive Statements Recorded</span>
                <button id="playMeetingDiscussionBtn" class="px-3 py-1.5 rounded-full bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] font-bold text-xs flex items-center space-x-1.5 shadow-md shadow-[#173d34]/15 transition-all active:scale-95">
                    <i data-lucide="play" class="w-3.5 h-3.5"></i>
                    <span>Listen to Discussion</span>
                </button>
            </div>
        `;
        boardroomDialoguesStream.appendChild(headerCard);

        const playBtn = headerCard.querySelector('#playMeetingDiscussionBtn');
        if (playBtn) {
            playBtn.onclick = () => {
                if (state.isMeetingPlaying) {
                    stopMeetingDiscussionAudio();
                } else {
                    playBoardroomMeetingAudio(dialogues);
                }
            };
        }

        // Render each dialogue turn
        dialogues.forEach(d => {
            const isSummary = d.executive_id == 0;
            const turnEl = document.createElement('div');
            turnEl.id = `dialogueTurn_${d.id}`;
            turnEl.className = `meeting-dialogue-turn p-5 rounded-2xl transition-all shadow-sm ${
                isSummary 
                    ? 'bg-[#f5f2e9] border border-[#173d34]/30' 
                    : 'bg-[#ffffff] border border-[#ddd8cc]'
            }`;

            const parsedContent = typeof marked !== 'undefined' 
                ? (typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(marked.parse(d.content)) : marked.parse(d.content))
                : escapeHtml(d.content);

            turnEl.innerHTML = `
                <div class="flex items-start space-x-3.5 mb-3">
                    <img src="${escapeHtml(d.avatar_url)}" alt="${escapeHtml(d.speaker_name)}" class="w-10 h-10 rounded-full object-cover border ${isSummary ? 'border-[#b7843f]' : 'border-[#ddd8cc]'} shadow-sm flex-shrink-0">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-bold text-[#202725]">${escapeHtml(d.speaker_name)}</h4>
                            <div class="flex items-center space-x-2">
                                <button class="turn-speak-btn text-[#68716d] hover:text-[#173d34] p-1 rounded-lg transition-colors" title="Listen to this statement">
                                    <i data-lucide="volume-2" class="w-3.5 h-3.5"></i>
                                </button>
                                <span class="text-[10px] text-[#68716d] font-mono">${escapeHtml(d.created_at)}</span>
                            </div>
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider ${isSummary ? 'text-[#b7843f]' : 'text-[#68716d]'}">${escapeHtml(d.speaker_title)}</p>
                    </div>
                </div>
                <div class="prose-custom text-[#202725] text-xs md:text-sm pl-0 sm:pl-13 leading-relaxed">
                    ${parsedContent}
                </div>
            `;
            boardroomDialoguesStream.appendChild(turnEl);

            const singleSpeakBtn = turnEl.querySelector('.turn-speak-btn');
            if (singleSpeakBtn) {
                singleSpeakBtn.onclick = () => playExecutiveVoice(d.content, d.executive_id);
            }
        });

        if (window.lucide) lucide.createIcons();
    }

    function playBoardroomMeetingAudio(dialogues, startIdx = 0) {
        if (!dialogues || dialogues.length === 0) return;
        state.isMeetingPlaying = true;
        const playBtn = document.getElementById('playMeetingDiscussionBtn');
        if (playBtn) {
            playBtn.innerHTML = `<i data-lucide="square" class="w-3.5 h-3.5 text-rose-400"></i><span>Pause Meeting Audio</span>`;
            if (window.lucide) lucide.createIcons();
        }

        function playTurn(idx) {
            if (!state.isMeetingPlaying || idx >= dialogues.length) {
                stopMeetingDiscussionAudio();
                return;
            }

            const d = dialogues[idx];
            document.querySelectorAll('.meeting-dialogue-turn').forEach(el => el.classList.remove('speaker-glow', 'border-amber-400'));
            const turnCard = document.getElementById(`dialogueTurn_${d.id}`);
            if (turnCard) {
                turnCard.classList.add('speaker-glow', 'border-amber-400');
                turnCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            playExecutiveVoice(d.content, d.executive_id, () => {
                if (turnCard) turnCard.classList.remove('speaker-glow');
                if (!state.isMeetingPlaying) return;
                setTimeout(() => {
                    playTurn(idx + 1);
                }, 500);
            });
        }

        playTurn(startIdx);
    }

    function stopMeetingDiscussionAudio() {
        state.isMeetingPlaying = false;
        stopAllAudio();
        document.querySelectorAll('.meeting-dialogue-turn').forEach(el => el.classList.remove('speaker-glow', 'border-amber-400'));
        const playBtn = document.getElementById('playMeetingDiscussionBtn');
        if (playBtn) {
            playBtn.innerHTML = `<i data-lucide="play" class="w-3.5 h-3.5 text-emerald-400"></i><span>Listen to Discussion</span>`;
            if (window.lucide) lucide.createIcons();
        }
    }

    async function handleConveneBoardMeeting(e) {
        e.preventDefault();
        const agenda = meetingAgendaInput.value.trim();
        if (!agenda) return;

        const checkboxes = meetingAttendeesList.querySelectorAll('input[name="attendee"]:checked');
        const attendeeIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

        submitConveneBtn.disabled = true;
        submitConveneBtn.innerHTML = `
            <span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>Deliberating in Boardroom...</span>
        `;

        try {
            const res = await fetch('api/meetings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ agenda, attendee_ids: attendeeIds, venture_id: state.currentVentureId })
            });

            const data = await res.json();
            if (data.success) {
                meetingAgendaInput.value = '';
                closeCallMeetingModal();
                switchToBoardroomView();
                renderMeetingDialogues(data.meeting, data.dialogues);
            } else {
                alert(data.error || 'Failed to convene meeting');
            }
        } catch (err) {
            console.error('Error convening meeting:', err);
        } finally {
            submitConveneBtn.disabled = false;
            submitConveneBtn.innerHTML = `
                <i data-lucide="users" class="w-4 h-4"></i>
                <span>Commence Board Meeting</span>
            `;
            if (window.lucide) lucide.createIcons();
        }
    }

    // Onboarding & Role Discovery Engine
    let isOnboardingChecking = false;
    async function checkAndTriggerOnboarding(force = false) {
        if (isOnboardingChecking) return false;
        isOnboardingChecking = true;
        try {
            const url = `api/onboarding.php${force ? '?force=1' : ''}`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.success && data.needs_onboarding && data.session_id) {
                // Ensure sessions list is up-to-date
                const sUrl = state.currentVentureId ? `api/sessions.php?venture_id=${state.currentVentureId}` : 'api/sessions.php';
                const sRes = await fetch(sUrl);
                const sData = await sRes.json();
                if (sData.sessions) {
                    state.sessions = sData.sessions.filter(s => parseInt(s.message_count, 10) > 0);
                    renderSessions();
                }
                await selectSession(data.session_id);
                return true;
            }
            return false;
        } catch (err) {
            console.error('Failed to trigger onboarding:', err);
            return false;
        } finally {
            isOnboardingChecking = false;
        }
    }
    window.checkAndTriggerOnboarding = checkAndTriggerOnboarding;

    async function restartOnboarding() {
        if (!confirm('Reconfigure RuXt\'s specialized role and restart the onboarding consultation?')) return;
        try {
            if (typeof window.closeProfileModal === 'function') window.closeProfileModal();
            const res = await fetch('api/onboarding.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reset' })
            });
            const data = await res.json();
            if (data.success) {
                await checkAndTriggerOnboarding(true);
                showToastNotification('RuXt role discovery conversation initiated.');
            }
        } catch (err) {
            console.error('Failed to restart onboarding:', err);
        }
    }
    window.restartOnboarding = restartOnboarding;

    // 4. Consultations & Sessions
    async function loadSessions() {
        try {
            const url = state.currentVentureId ? `api/sessions.php?venture_id=${state.currentVentureId}` : 'api/sessions.php';
            const res = await fetch(url);
            const data = await res.json();
            if (data.sessions) {
                // Strictly keep only sessions with actual messages (never keep empty chats)
                state.sessions = data.sessions.filter(s => parseInt(s.message_count, 10) > 0);
                renderSessions();
                if (state.sessions.length > 0) {
                    const currentValid = state.sessions.some(s => s.id == state.currentSessionId);
                    if (!currentValid) {
                        // Only auto-select first session if not deliberately in a fresh new chat view
                        if (state.currentSessionId !== null) {
                            selectSession(state.sessions[0].id);
                        }
                    }
                } else {
                    if (state.currentSessionId !== null) {
                        state.currentSessionId = null;
                        setEmptyChatView();
                    }
                    // Auto-trigger onboarding only if user hasn't completed or skipped it
                    if (!state.user || !state.user.onboarding_completed || parseInt(state.user.onboarding_completed, 10) === 0) {
                        checkAndTriggerOnboarding(true);
                    }
                }
            }
        } catch (err) {
            console.error('Failed to load sessions:', err);
        }
    }

    function renderSessions() {
        if (!sessionsList) return;
        sessionsList.innerHTML = '';
        if (state.sessions.length === 0) {
            sessionsList.innerHTML = `<div class="text-xs text-[#68716d] text-center py-4 italic">No past briefings yet</div>`;
            return;
        }

        state.sessions.forEach(sess => {
            const isActive = sess.id == state.currentSessionId && state.activeView === 'chat';
            const item = document.createElement('div');
            item.className = `group flex items-center justify-between p-2 rounded-xl cursor-pointer text-xs transition-all ${
                isActive 
                    ? 'bg-[#ffffff] text-[#173d34] border border-[#ddd8cc] font-bold shadow-sm' 
                    : 'text-[#68716d] hover:text-[#202725] hover:bg-[#ede8dc]'
            }`;

            item.innerHTML = `
                <div class="flex items-center space-x-2 overflow-hidden flex-1" onclick="window.selectChatSession(${sess.id})">
                    <i data-lucide="message-square" class="w-3.5 h-3.5 flex-shrink-0 ${isActive ? 'text-[#173d34]' : 'text-[#68716d]'}"></i>
                    <span class="truncate">${escapeHtml(sess.title || 'Briefing')}</span>
                </div>
                <button onclick="event.stopPropagation(); window.deleteChatSession(${sess.id})" class="opacity-0 group-hover:opacity-100 p-1 hover:text-rose-500 rounded transition-opacity" title="Delete conversation">
                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                </button>
            `;
            sessionsList.appendChild(item);
        });

        if (window.lucide) lucide.createIcons();
    }

    async function selectSession(sessionId) {
        state.currentSessionId = sessionId;
        clearPendingAttachments();
        switchToChatView();
        renderSessions();
        closeMobileSidebar();

        const current = state.sessions.find(s => s.id == sessionId);
        if (current) {
            currentChatTitle.textContent = current.executive_name || current.title;
            headerActiveSubtitle.textContent = current.executive_title || 'Executive Advisor';
            if (current.avatar_url) headerActiveAvatar.src = current.avatar_url;
            if (current.executive_id) state.selectedExecutiveId = current.executive_id;
            if (headerProjectName) headerProjectName.textContent = current.project_name || 'All Projects';
            renderExecutivesList();
        }

        // Fetch messages
        try {
            const res = await fetch(`api/messages.php?session_id=${sessionId}`);
            const data = await res.json();
            if (data.messages && data.messages.length > 0) {
                emptyState.classList.add('hidden');
                messagesList.innerHTML = '';
                data.messages.forEach(msg => appendMessage(msg.role, msg.content, false, msg.discussion_id, null, null, msg.workspace, null, msg.work_preview, msg.actions, null, msg.attachments));
                scrollToBottom();
            } else {
                // If somehow an empty session was selected, delete it from DB and start clean!
                await fetch(`api/sessions.php?id=${sessionId}`, { method: 'DELETE' });
                startNewChat(state.selectedExecutiveId);
                loadSessions();
            }
        } catch (err) {
            console.error('Failed to load messages:', err);
        }
    }

    function startNewChat(execId = null) {
        const executiveId = execId || state.selectedExecutiveId || 1;
        state.currentSessionId = null; // Do NOT insert empty record into DB!
        state.selectedExecutiveId = executiveId;
        clearPendingAttachments();
        switchToChatView();
        renderSessions();
        closeMobileSidebar();

        const exec = state.executives.find(e => e.id == executiveId) || state.executives[0];
        if (exec) {
            currentChatTitle.textContent = exec.name;
            headerActiveSubtitle.textContent = exec.title;
            if (headerActiveAvatar && exec.avatar_url) headerActiveAvatar.src = exec.avatar_url;
            if (typingAvatar && exec.avatar_url) typingAvatar.src = exec.avatar_url;
            if (typingName) typingName.textContent = `${exec.name} is formulating response`;
            renderExecutivesList();

            // Update empty state briefing
            const esAvatar = document.getElementById('emptyStateAvatar');
            if (esAvatar && exec.avatar_url) esAvatar.src = exec.avatar_url;
            const esTitle = document.getElementById('emptyStateTitle');
            if (esTitle) esTitle.textContent = `${exec.name} — ${exec.title}`;
            const esSub = document.getElementById('emptyStateSubtitle');
            if (esSub) esSub.textContent = exec.is_chief_of_staff == 1 ? 'Lead Private Assistant & Work Breakdown Engine' : `${exec.department} Channel`;
            const esBio = document.getElementById('emptyStateBio');
            if (esBio) esBio.textContent = `"${exec.bio || 'Ready for your executive consultation, CEO.'}"`;
        }

        setEmptyChatView();
        if (chatInput) chatInput.focus();
    }

    async function createNewSession(execId = null) {
        startNewChat(execId);
    }

    async function deleteSession(sessionId) {
        if (!confirm('Are you sure you want to delete this executive consultation?')) return;
        try {
            await fetch(`api/sessions.php?id=${sessionId}`, { method: 'DELETE' });
            state.sessions = state.sessions.filter(s => s.id != sessionId);
            if (state.sessions.length === 0) {
                state.currentSessionId = null;
                setEmptyChatView();
                // When all consultations are deleted, trigger onboarding only if pending
                if (!state.user || !state.user.onboarding_completed || parseInt(state.user.onboarding_completed, 10) === 0) {
                    await checkAndTriggerOnboarding(true);
                }
            } else if (state.currentSessionId == sessionId) {
                state.currentSessionId = state.sessions[0].id;
                await selectSession(state.currentSessionId);
            }
            renderSessions();
        } catch (err) {
            console.error('Failed to delete session:', err);
        }
    }

    function setEmptyChatView() {
        messagesList.innerHTML = '';
        emptyState.classList.remove('hidden');
    }

    // Attachment System Helpers
    function formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i];
    }

    function handleFileSelection(files) {
        if (!files || files.length === 0) return;
        const maxFiles = 5;
        const maxFileSize = 20 * 1024 * 1024; // 20MB

        const incoming = Array.from(files);
        if (state.pendingAttachments.length + incoming.length > maxFiles) {
            showToastNotification(`⚠️ You can attach a maximum of ${maxFiles} files per message.`);
            return;
        }

        incoming.forEach(file => {
            if (file.size > maxFileSize) {
                showToastNotification(`⚠️ '${file.name}' exceeds the 20MB limit.`);
                return;
            }

            const isImage = file.type.startsWith('image/');
            const previewUrl = URL.createObjectURL(file);

            state.pendingAttachments.push({
                file: file,
                name: file.name,
                size: file.size,
                sizeFormatted: formatFileSize(file.size),
                isImage: isImage,
                previewUrl: previewUrl,
                extension: (file.name.split('.').pop() || 'file').toLowerCase()
            });
        });

        renderPendingAttachments();
    }

    function renderPendingAttachments() {
        if (!attachmentPreviewTray) return;

        if (state.pendingAttachments.length === 0) {
            attachmentPreviewTray.classList.add('hidden');
            attachmentPreviewTray.innerHTML = '';
            return;
        }

        attachmentPreviewTray.classList.remove('hidden');
        attachmentPreviewTray.innerHTML = `
            <div class="flex items-center space-x-1.5 text-[10px] font-bold text-[#173d34] uppercase font-mono mr-2">
                <i data-lucide="paperclip" class="w-3.5 h-3.5 text-[#173d34]"></i>
                <span>Attached (${state.pendingAttachments.length}/5):</span>
            </div>
            <div class="flex flex-wrap gap-2 items-center">
                ${state.pendingAttachments.map((att, idx) => `
                    <div class="flex items-center space-x-2 bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl p-1.5 pr-2.5 shadow-2xs group hover:border-[#173d34] transition-all">
                        ${att.isImage ? `
                            <img src="${att.previewUrl}" class="w-8 h-8 rounded-lg object-cover border border-[#ddd8cc]">
                        ` : `
                            <div class="w-8 h-8 rounded-lg bg-[#173d34]/10 text-[#173d34] flex items-center justify-center font-mono font-bold text-[9px] uppercase">
                                ${att.extension.slice(0, 4)}
                            </div>
                        `}
                        <div class="min-w-0 max-w-[120px] sm:max-w-[160px]">
                            <div class="text-[11px] font-bold text-[#202725] truncate">${escapeHtml(att.name)}</div>
                            <div class="text-[9px] text-[#68716d] font-mono">${att.sizeFormatted}</div>
                        </div>
                        <button type="button" onclick="window.removePendingAttachment(${idx})" class="text-[#68716d] hover:text-rose-600 p-1 rounded-md hover:bg-rose-50 transition-colors cursor-pointer" title="Remove attachment">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                `).join('')}
            </div>
        `;

        if (window.lucide) lucide.createIcons();
    }

    function removePendingAttachment(idx) {
        if (state.pendingAttachments[idx]) {
            URL.revokeObjectURL(state.pendingAttachments[idx].previewUrl);
            state.pendingAttachments.splice(idx, 1);
            renderPendingAttachments();
        }
    }
    window.removePendingAttachment = removePendingAttachment;

    function clearPendingAttachments() {
        state.pendingAttachments.forEach(att => {
            if (att.previewUrl) URL.revokeObjectURL(att.previewUrl);
        });
        state.pendingAttachments = [];
        renderPendingAttachments();
    }

    // Lightbox Modal Functions
    window.openImageLightbox = function(src, caption) {
        if (!imageLightboxModal || !lightboxImg) return;
        lightboxImg.src = src;
        if (lightboxCaption) lightboxCaption.textContent = caption || 'Reference Preview';
        imageLightboxModal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    };

    window.closeImageLightbox = function(e) {
        if (!imageLightboxModal) return;
        if (!e || e.target === imageLightboxModal || e.target.closest('button')) {
            imageLightboxModal.classList.add('hidden');
            if (lightboxImg) lightboxImg.src = '';
        }
    };

    // 5. Message Handling
    async function sendMessage(textToSend = null) {
        const text = textToSend || chatInput.value.trim();
        const hasAttachments = state.pendingAttachments.length > 0;
        if ((!text && !hasAttachments) || state.isSending) return;

        if (!state.settings.has_api_key) {
            openSettings();
            alert('Please enter your Gemini API Key in Settings to start executive consultations.');
            return;
        }

        state.isSending = true;
        chatInput.value = '';
        autoResizeInput();
        emptyState.classList.add('hidden');

        // Optimistic display copies of attachments
        const displayAttachments = state.pendingAttachments.map(att => ({
            original_name: att.name,
            file_path: att.previewUrl,
            url: att.previewUrl,
            mime_type: att.file.type,
            size_formatted: att.sizeFormatted,
            is_image: att.isImage,
            extension: att.extension
        }));

        const stagedFilesToUpload = [...state.pendingAttachments];
        clearPendingAttachments();

        const displayText = text || (hasAttachments ? '' : '');
        appendMessage('user', displayText, true, null, null, null, null, null, null, null, null, displayAttachments);
        scrollToBottom();

        typingIndicator.classList.remove('hidden');
        scrollToBottom();

        try {
            // 1. Upload any staged files first to server
            let uploadedAttachmentsMeta = [];
            if (stagedFilesToUpload.length > 0) {
                const formData = new FormData();
                stagedFilesToUpload.forEach(att => {
                    formData.append('files[]', att.file);
                });

                try {
                    const upRes = await fetch('api/upload.php', {
                        method: 'POST',
                        body: formData
                    });
                    const upData = await upRes.json();
                    if (upData.success && Array.isArray(upData.files)) {
                        uploadedAttachmentsMeta = upData.files;
                    } else if (upData.error) {
                        showToastNotification(`⚠️ Attachment upload issue: ${upData.error}`);
                    }
                } catch (upErr) {
                    console.error('File upload error:', upErr);
                    showToastNotification('⚠️ Could not complete file upload to server.');
                }
            }

            const payload = {
                message: text,
                session_id: state.currentSessionId,
                executive_id: state.selectedExecutiveId,
                venture_id: state.currentVentureId,
                attachments: uploadedAttachmentsMeta
            };

            const res = await fetch('api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            typingIndicator.classList.add('hidden');

            if (data.error) {
                appendMessage('model', `⚠️ **Executive Notice:** ${data.error}`);
            } else {
                state.currentSessionId = data.session_id;
                const discId = data.team_discussion ? data.team_discussion.id : (data.assistant_message ? data.assistant_message.discussion_id : null);
                const detectedProj = data.detected_project || (data.team_discussion && data.team_discussion.project_name ? { name: data.team_discussion.project_name } : null);

                // Handle Model Switch dynamically
                if (data.switched_model) {
                    state.settings.model = data.switched_model;
                    const badge = document.getElementById('currentModelBadge');
                    if (badge) badge.textContent = data.switched_model;
                    const mSelect = document.getElementById('modelSelect');
                    if (mSelect) mSelect.value = data.switched_model;
                    showToastNotification(`🧠 Switched AI Model to ${data.switched_model}`);
                }

                if (data.token_stats) {
                    renderTokenUsage(data.token_stats);
                }

                // Check if current session is onboarding/welcome consultation
                const curSession = state.sessions.find(s => s.id == state.currentSessionId);
                const isWelcomeSess = curSession && (
                    (curSession.title && (curSession.title.includes('Welcome') || curSession.title.includes('Role Setup'))) ||
                    curSession.project_name === 'Onboarding'
                );

                // Handle Open Workspace Modal instruction (suppressed in welcome chat)
                if (data.open_workspace_modal && !isWelcomeSess) {
                    openVentureModal();
                }

                // Handle Open Predefined Workspace Store instruction
                if (data.open_workspace_store && !isWelcomeSess) {
                    openWorkspaceStore('all');
                }

                const passedWorkspace = isWelcomeSess ? null : data.created_workspace;
                const passedPromptCard = isWelcomeSess ? null : data.workspace_prompt_card;

                // Append the response with live workspace & prompt cards & work preview & system actions & role configuration
                appendMessage('model', data.reply, true, discId, detectedProj, data.delegated_tasks, passedWorkspace, passedPromptCard, data.work_preview, data.actions, data.role_configured);

                if (data.role_configured) {
                    showToastNotification(`✨ RuXt role activated: ${data.role_configured.role_title}`);
                    if (headerActiveSubtitle) headerActiveSubtitle.textContent = data.role_configured.role_title;
                    const fEl = document.getElementById('profileUserJobField');
                    if (fEl) fEl.textContent = data.role_configured.field;
                    const rEl = document.getElementById('profileRuxtSpecializedRole');
                    if (rEl) rEl.textContent = data.role_configured.role_title;
                    if (state.user) {
                        state.user.onboarding_completed = 1;
                        state.user.job_field = data.role_configured.field;
                        state.user.specialized_role = data.role_configured.role_title;
                    }
                }

                if (detectedProj && headerProjectName) {
                    headerProjectName.textContent = detectedProj.name;
                }

                // Live Workspace Switch & Synchronization
                if (data.created_workspace) {
                    showToastNotification(`🚀 Workspace "${data.created_workspace.name}" created and switched LIVE!`);
                    await loadVentures();
                    await switchVenture(data.created_workspace.id);
                }

                if (state.autoSpeak) playExecutiveVoice(data.reply, state.selectedExecutiveId);
                loadSessions();
                loadMemories();
                loadDiscussions();
                loadProjects();
                loadNotes();
            }
        } catch (err) {
            typingIndicator.classList.add('hidden');
            appendMessage('model', `⚠️ **Network Error:** Could not reach executive office. ${err.message}`);
        } finally {
            state.isSending = false;
            chatInput.focus();
        }
    }

    function appendMessage(role, content, animate = false, discussionId = null, detectedProj = null, delegatedTasks = null, createdWorkspace = null, workspacePromptCard = null, workPreview = null, actions = null, roleConfigured = null, attachments = null) {
        const isUser = role === 'user';
        const msgDiv = document.createElement('div');
        msgDiv.className = `flex w-full mb-5 ${isUser ? 'justify-end' : 'justify-start'} ${animate ? 'animate-fade-in' : ''}`;

        let safeRawContent = content || '';
        let quickFields = [];
        const quickMatch = safeRawContent.match(/\[\[QUICK_FIELDS:\s*(\[[\s\S]*?\])\s*\]\]/);
        if (quickMatch) {
            try {
                quickFields = JSON.parse(quickMatch[1]);
            } catch (e) {}
            safeRawContent = safeRawContent.replace(/\[\[QUICK_FIELDS:\s*\[[\s\S]*?\]\s*\]\]/g, '').trim();
        }
        // Cleanup any stray trailing bracket from older message parsing
        safeRawContent = safeRawContent.replace(/\n\s*\]\s*$/g, '').trim();

        const parsedContent = isUser 
            ? escapeHtml(safeRawContent) 
            : (typeof marked !== 'undefined' 
                ? (typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(marked.parse(safeRawContent)) : marked.parse(safeRawContent))
                : escapeHtml(safeRawContent));
        const exec = state.executives.find(e => e.id == state.selectedExecutiveId);
        const avatarUrl = exec ? exec.avatar_url : 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80';

        const avatar = isUser
            ? `<div class="w-8 h-8 rounded-full bg-[#173d34] text-[#fcfaf5] flex items-center justify-center text-xs font-bold shadow-sm flex-shrink-0 ml-3 mt-1">CEO</div>`
            : `<img src="${avatarUrl}" class="w-8 h-8 rounded-full object-cover border border-[#ddd8cc] shadow-sm flex-shrink-0 mr-3 mt-1">`;

        const projectBadge = (!isUser && detectedProj && detectedProj.name) ? `
            <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20 text-[10px] font-bold mb-2">
                <i data-lucide="folder-kanban" class="w-3 h-3 text-[#173d34]"></i>
                <span>Project: ${escapeHtml(detectedProj.name)}</span>
            </div>
        ` : '';

        const workPreviewCard = (!isUser && workPreview) ? `
            <div class="mt-3.5 p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-md relative overflow-hidden group">
                <div class="flex items-center justify-between pb-2.5 border-b border-[#ddd8cc] mb-2.5">
                    <div class="flex items-center space-x-2.5 overflow-hidden">
                        <div class="w-8 h-8 rounded-xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center flex-shrink-0">
                            <i data-lucide="scan-eye" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-[#202725] flex items-center gap-2 truncate">
                                <span>Live Member Work Preview</span>
                                <span class="text-[9px] font-mono px-1.5 py-0.5 rounded ${workPreview.status === 'completed' ? 'bg-[#173d34]/15 text-[#173d34] border border-[#173d34]/20' : 'bg-[#b7843f]/15 text-[#9a6d32] border border-[#b7843f]/30'} flex items-center gap-1 font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full ${workPreview.status === 'completed' ? 'bg-emerald-500' : 'bg-[#b7843f] animate-ping'}"></span>
                                    <span>${workPreview.status === 'completed' ? 'COMPLETED' : 'IN PROGRESS'}</span>
                                </span>
                            </div>
                            <div class="text-[10px] text-[#68716d] font-mono truncate">${escapeHtml(workPreview.task_title || 'Platform Architecture Task')}</div>
                        </div>
                    </div>
                    <button type="button" onclick="window.openWorkPreviewModal(${workPreview.id})" class="px-3 py-1.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-full text-xs font-bold transition-all flex items-center space-x-1.5 shadow-md shadow-[#173d34]/15 active:scale-95 flex-shrink-0">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>View Live Preview</span>
                    </button>
                </div>
                <div class="space-y-1.5 text-[11px] text-[#202725]">
                    <div class="flex items-center justify-between text-[10px] text-[#68716d] font-mono">
                        <span>Target: ${escapeHtml(workPreview.target_system || 'Platform')}</span>
                        <span class="text-[#173d34] font-bold">${workPreview.progress_percent || 75}%</span>
                    </div>
                    <div class="w-full bg-[#ede8dc] rounded-full h-1.5 overflow-hidden border border-[#ddd8cc]">
                        <div class="h-1.5 rounded-full bg-[#173d34] transition-all duration-500" style="width: ${workPreview.progress_percent || 75}%"></div>
                    </div>
                </div>
            </div>
        ` : '';

        const discussionCard = (!isUser && discussionId) ? `
            <div class="mt-3.5 p-3 rounded-xl bg-[#ffffff] border border-[#ddd8cc] flex items-center justify-between gap-2 shadow-sm">
                <div class="flex items-center space-x-2.5 overflow-hidden">
                    <div class="w-7 h-7 rounded-lg bg-[#b7843f]/15 text-[#9a6d32] flex items-center justify-center border border-[#b7843f]/25 flex-shrink-0">
                        <i data-lucide="message-circle-code" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="text-xs font-bold text-[#202725] block truncate">Internal Team Deliberation Transcript</span>
                        <span class="text-[10px] text-[#68716d] block truncate">RuXt & specialists aligned on this work breakdown</span>
                    </div>
                </div>
                <button type="button" onclick="window.openDiscussionModal(${discussionId})" class="px-3 py-1 bg-[#ffffff] hover:bg-[#f5f2e9] text-[#173d34] border border-[#ddd8cc] rounded-full text-xs font-bold transition-all flex items-center space-x-1 shadow-sm flex-shrink-0 active:scale-95">
                    <span>View Dialogue</span>
                    <i data-lucide="external-link" class="w-3 h-3"></i>
                </button>
            </div>
        ` : '';

        const delegatedCard = (!isUser && delegatedTasks && delegatedTasks.length > 0) ? `
            <div class="mt-3 p-3 rounded-xl bg-[#ffffff] border border-[#ddd8cc] flex items-center justify-between gap-2 shadow-sm">
                <div class="flex items-center space-x-2.5 overflow-hidden">
                    <div class="w-7 h-7 rounded-lg bg-[#173d34]/10 text-[#173d34] flex items-center justify-center border border-[#173d34]/20 flex-shrink-0">
                        <i data-lucide="folder-kanban" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="text-xs font-bold text-[#202725] block truncate">Project: ${escapeHtml(detectedProj ? detectedProj.name : 'Deliverables')}</span>
                        <span class="text-[10px] text-[#68716d] block truncate">${delegatedTasks.length} Action Items delegated to Sprint Board</span>
                    </div>
                </div>
                <button type="button" onclick="window.filterByProjectAndOpenBoard('${escapeHtml(detectedProj ? detectedProj.name : '')}')" class="px-3 py-1 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-full text-xs font-bold transition-all flex items-center space-x-1 shadow-sm flex-shrink-0 active:scale-95">
                    <span>View Project Board</span>
                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                </button>
            </div>
        ` : '';

        const promptCardHtml = (!isUser && workspacePromptCard) ? `
            <div class="mt-3.5 p-3.5 rounded-2xl bg-cyan-950/40 border border-cyan-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-lg">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-cyan-500/20 text-cyan-300 flex items-center justify-center border border-cyan-500/30 flex-shrink-0">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-white block">Workspace Launchpad</span>
                        <span class="text-[11px] text-cyan-300/80 block">Reply with details or launch directly using the visual creator</span>
                    </div>
                </div>
                <button type="button" onclick="window.openWorkspaceModal()" class="w-full sm:w-auto px-3.5 py-1.5 bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 text-slate-950 rounded-xl text-xs font-bold flex items-center justify-center space-x-1.5 shadow-md shadow-cyan-500/20 active:scale-95 transition-all">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span>Open Setup Modal</span>
                </button>
            </div>
        ` : '';

        let liveWorkspaceHtml = '';
        if (!isUser && createdWorkspace) {
            const ws = createdWorkspace;
            const team = ws.generated_team || [];
            const teamCount = ws.team_count || team.length;

            let teamMembersGrid = '';
            if (team.length > 0) {
                teamMembersGrid = `
                    <div class="mt-3 pt-3 border-t border-cyan-500/20">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-cyan-300 mb-2 flex items-center gap-1.5">
                            <i data-lucide="users" class="w-3 h-3 text-cyan-400"></i>
                            <span>Recruited AI Specialist Team (${teamCount} Leaders):</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            ${team.map(m => `
                                <div class="flex items-center space-x-2.5 p-2 rounded-xl bg-slate-900/90 border border-slate-800 shadow-sm">
                                    <img src="${escapeHtml(m.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400')}" class="w-8 h-8 rounded-lg object-cover border border-cyan-500/30 flex-shrink-0">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-bold text-white truncate">${escapeHtml(m.name)}</div>
                                        <div class="text-[10px] text-cyan-300/90 truncate font-mono">${escapeHtml(m.title)}</div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            liveWorkspaceHtml = `
                <div class="mt-4 p-4 rounded-2xl bg-gradient-to-br from-cyan-950/70 via-slate-900/90 to-slate-950 border border-cyan-500/50 shadow-2xl overflow-hidden relative group">
                    <div class="flex items-center justify-between pb-3 border-b border-cyan-500/25 mb-3">
                        <div class="flex items-center space-x-2.5 overflow-hidden">
                            <div class="w-8 h-8 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center border border-cyan-500/40 flex-shrink-0 shadow-inner">
                                <i data-lucide="${escapeHtml(ws.icon || 'briefcase')}" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-extrabold text-white flex items-center gap-2 truncate">
                                    <span>${escapeHtml(ws.name)}</span>
                                    <span class="text-[9px] font-mono px-1.5 py-0.5 rounded bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">WORKSPACE #${ws.id}</span>
                                </div>
                                <div class="text-[10px] text-slate-300 truncate font-mono">${escapeHtml(ws.domain)}</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-1.5 text-[10px] text-emerald-400 font-bold bg-emerald-500/15 border border-emerald-500/30 px-2.5 py-1 rounded-full flex-shrink-0 shadow-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                            <span>LIVE DEPLOYED</span>
                        </div>
                    </div>

                    <div class="space-y-1.5 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800 text-[11px]">
                        <div class="flex items-center space-x-2 text-emerald-300 font-medium">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-400 flex-shrink-0"></i>
                            <span>Enterprise Workspace Provisioned: <strong>${escapeHtml(ws.name)}</strong></span>
                        </div>
                        <div class="flex items-center space-x-2 text-emerald-300 font-medium">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-400 flex-shrink-0"></i>
                            <span>Strategic Domain Bound: <em>${escapeHtml(ws.domain)}</em></span>
                        </div>
                        <div class="flex items-center space-x-2 text-emerald-300 font-medium">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-400 flex-shrink-0"></i>
                            <span>AI Specialist Roster: <strong>${teamCount} Domain Leaders Recruited</strong></span>
                        </div>
                        <div class="flex items-center space-x-2 text-cyan-300 font-semibold">
                            <i data-lucide="zap" class="w-3.5 h-3.5 text-cyan-400 flex-shrink-0"></i>
                            <span>Live Context Switched to Workspace #${ws.id}</span>
                        </div>
                    </div>

                    ${teamMembersGrid}

                    <div class="mt-3 pt-3 border-t border-slate-800/80 flex items-center justify-between gap-2">
                        <span class="text-[10px] text-slate-400 flex items-center gap-1">
                            <i data-lucide="sparkles" class="w-3 h-3 text-cyan-400"></i>
                            <span>Active Workspace Synchronized</span>
                        </span>
                        <button type="button" onclick="window.switchVenture(${ws.id})" class="px-3 py-1 bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 text-slate-950 rounded-lg text-xs font-bold transition-all flex items-center space-x-1 shadow-sm active:scale-95 flex-shrink-0">
                            <i data-lucide="check" class="w-3 h-3"></i>
                            <span>Active Now</span>
                        </button>
                    </div>
                </div>
            `;
        }

        let systemActionsCard = '';
        if (!isUser && actions && Array.isArray(actions) && actions.length > 0) {
            systemActionsCard = `
                <div class="mt-3.5 p-3.5 rounded-2xl bg-[#ffffff] border-2 border-emerald-500/40 shadow-lg relative overflow-hidden group">
                    <div class="flex items-center justify-between pb-2.5 border-b border-[#ddd8cc] mb-3">
                        <div class="flex items-center space-x-2.5 overflow-hidden">
                            <div class="w-8 h-8 rounded-xl bg-emerald-700 text-white flex items-center justify-center flex-shrink-0 shadow-md">
                                <i data-lucide="terminal" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-[#202725] flex items-center gap-2 truncate">
                                    <span>⚡ Live Host System Execution</span>
                                    <span class="text-[9px] font-mono px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>VERIFIED ON DISK</span>
                                    </span>
                                </div>
                                <div class="text-[10px] text-[#68716d] font-mono truncate">Executed via RuXt System Engine at C:\\xampp\\htdocs</div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2.5">
                        ${actions.map(act => {
                            if (act.action === 'create_project') {
                                const filesList = (act.files || []).map(f => `
                                    <div class="flex items-center justify-between text-[11px] font-mono text-[#202725] py-0.5 border-b border-emerald-500/10 last:border-none">
                                        <span class="flex items-center gap-1.5 truncate">
                                            <i data-lucide="file-code" class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0"></i>
                                            <span class="truncate">${escapeHtml(f.path)}</span>
                                        </span>
                                        <span class="text-[10px] text-[#68716d] font-bold flex-shrink-0 ml-2">${escapeHtml(f.size_formatted || '')}</span>
                                    </div>
                                `).join('');

                                return `
                                    <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-xl p-3">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                            <div>
                                                <div class="text-xs font-bold text-emerald-950 flex items-center gap-1.5">
                                                    <i data-lucide="folder-check" class="w-4 h-4 text-emerald-600"></i>
                                                    <span>${escapeHtml(act.title || act.folder)}</span>
                                                </div>
                                                <div class="text-[10px] text-emerald-800 font-mono">${escapeHtml(act.full_path || '')}</div>
                                            </div>
                                            <a href="${escapeHtml(act.url || 'http://localhost/' + act.folder)}" target="_blank" class="px-3 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-bold transition-all flex items-center justify-center space-x-1.5 shadow-sm active:scale-95 flex-shrink-0">
                                                <span>🚀 Open Localhost</span>
                                                <i data-lucide="external-link" class="w-3 h-3"></i>
                                            </a>
                                        </div>
                                        <div class="mt-2 bg-white/90 rounded-lg p-2 border border-emerald-200/50">
                                            <div class="text-[10px] font-bold text-[#68716d] uppercase mb-1">Generated Files (${act.files_count || 0}):</div>
                                            ${filesList}
                                        </div>
                                    </div>
                                `;
                            } else if (act.action === 'write_file') {
                                return `
                                    <div class="bg-[#f8f6f0] border border-[#ddd8cc] rounded-xl p-2.5 text-xs font-mono">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2 truncate">
                                                <i data-lucide="file-plus-2" class="w-4 h-4 text-emerald-600 flex-shrink-0"></i>
                                                <span class="truncate font-bold text-[#202725]">${escapeHtml(act.path)}</span>
                                            </div>
                                            <span class="text-[10px] text-emerald-700 font-bold px-1.5 py-0.5 rounded bg-emerald-100 flex-shrink-0 ml-2">${escapeHtml(act.size_formatted || 'Wrote')}</span>
                                        </div>
                                        ${act.auto_linked ? `
                                            <div class="mt-1.5 text-[10px] font-mono text-emerald-800 bg-emerald-100/80 p-1.5 rounded-lg flex items-center gap-1.5 border border-emerald-200">
                                                <i data-lucide="link" class="w-3.5 h-3.5 text-emerald-700 flex-shrink-0"></i>
                                                <span>Auto-Linked: Synchronized <strong>${escapeHtml(act.auto_linked.target_file)}</strong> with ${escapeHtml(act.auto_linked.linked_page)}</span>
                                            </div>
                                        ` : ''}
                                    </div>
                                `;
                            } else if (act.action === 'edit_file') {
                                return `
                                    <div class="bg-[#f8f6f0] border border-cyan-500/30 rounded-xl p-2.5 flex items-center justify-between text-xs font-mono">
                                        <div class="flex items-center space-x-2 truncate">
                                            <i data-lucide="file-edit" class="w-4 h-4 text-cyan-600 flex-shrink-0"></i>
                                            <span class="truncate font-bold text-[#202725]">${escapeHtml(act.path)}</span>
                                            <span class="text-[10px] text-cyan-800 bg-cyan-100 px-1.5 py-0.5 rounded font-sans font-bold">Synchronized</span>
                                        </div>
                                        <span class="text-[10px] text-emerald-700 font-bold px-1.5 py-0.5 rounded bg-emerald-100 flex-shrink-0 ml-2">${escapeHtml(act.size_formatted || 'Edited')}</span>
                                    </div>
                                `;
                            } else if (act.action === 'mkdir') {
                                return `
                                    <div class="bg-[#f8f6f0] border border-[#ddd8cc] rounded-xl p-2.5 flex items-center space-x-2 text-xs font-mono">
                                        <i data-lucide="folder-plus" class="w-4 h-4 text-emerald-600 flex-shrink-0"></i>
                                        <span class="truncate font-bold text-[#202725]">${escapeHtml(act.path)}</span>
                                        <span class="text-[10px] text-emerald-700 font-bold px-1.5 py-0.5 rounded bg-emerald-100 ml-auto flex-shrink-0">Created</span>
                                    </div>
                                `;
                            } else if (act.action === 'run_command') {
                                return `
                                    <div class="bg-slate-950 text-slate-100 rounded-xl p-3 border border-slate-800 font-mono text-[11px]">
                                        <div class="flex items-center justify-between text-[10px] text-slate-400 mb-1.5 pb-1 border-b border-slate-800">
                                            <span class="flex items-center gap-1"><i data-lucide="terminal" class="w-3 h-3 text-emerald-400"></i> Command</span>
                                            <span class="${act.exit_code === 0 ? 'text-emerald-400' : 'text-rose-400'} font-bold">Exit: ${act.exit_code}</span>
                                        </div>
                                        <div class="text-emerald-300 font-bold mb-1.5">&gt; ${escapeHtml(act.command || '')}</div>
                                        ${act.output ? `<pre class="text-slate-300 whitespace-pre-wrap max-h-40 overflow-y-auto text-[10px] leading-relaxed bg-slate-900/80 p-2 rounded">${escapeHtml(act.output)}</pre>` : ''}
                                    </div>
                                `;
                            } else {
                                return `
                                    <div class="bg-[#f8f6f0] border border-[#ddd8cc] rounded-xl p-2.5 text-xs">
                                        <span class="font-bold text-[#202725]">${escapeHtml(act.action)}</span>: ${escapeHtml(act.message || 'Executed')}
                                    </div>
                                `;
                            }
                        }).join('')}
                    </div>
                </div>
            `;
        }

        let quickFieldsHtml = '';
        if (!isUser && quickFields && Array.isArray(quickFields) && quickFields.length > 0) {
            const qfUid = 'qf_' + Math.random().toString(36).substring(2, 9);
            const hasOther = quickFields.some(f => /other|custom|තවත්|වෙනත්/i.test(f));
            const needsOnboarding = !state.user || !state.user.onboarding_completed || parseInt(state.user.onboarding_completed, 10) === 0;

            quickFieldsHtml = `
                <div class="mt-3.5 pt-3 border-t border-[#ddd8cc]/70">
                    <div class="text-[10px] font-bold text-[#173d34] mb-2 flex items-center space-x-1.5 font-mono uppercase tracking-wider">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>Select A Free Workspace / Quick Reply:</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        ${quickFields.map(f => {
                            const isOther = /other|custom|තවත්|වෙනත්/i.test(f);
                            if (isOther) {
                                return `
                                    <button type="button" onclick="window.toggleQuickOtherInput('${qfUid}')" class="p-2.5 rounded-xl bg-[#ffffff] hover:bg-[#173d34] text-[#202725] hover:text-[#fcfaf5] border border-[#ddd8cc] hover:border-[#173d34] transition-all text-xs font-bold text-left flex items-center justify-between shadow-2xs group cursor-pointer active:scale-[0.98]">
                                        <span class="truncate flex items-center gap-1.5">
                                            <i data-lucide="pen-tool" class="w-3.5 h-3.5 text-amber-500 group-hover:text-amber-300"></i>
                                            <span>${escapeHtml(f)}</span>
                                        </span>
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-[#68716d] group-hover:text-[#fcfaf5] transition-colors flex-shrink-0 ml-1"></i>
                                    </button>
                                `;
                            }
                            return `
                                <button type="button" onclick="window.sendPrompt('${escapeHtml(f)}')" class="p-2.5 rounded-xl bg-[#ffffff] hover:bg-[#173d34] text-[#202725] hover:text-[#fcfaf5] border border-[#ddd8cc] hover:border-[#173d34] transition-all text-xs font-bold text-left flex items-center justify-between shadow-2xs group cursor-pointer active:scale-[0.98]">
                                    <span class="truncate">${escapeHtml(f)}</span>
                                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-[#68716d] group-hover:text-[#fcfaf5] transition-colors flex-shrink-0 ml-1"></i>
                                </button>
                            `;
                        }).join('')}
                    </div>

                    <!-- Custom / Other Input Container -->
                    <div id="${qfUid}_other_box" class="hidden mt-3 p-3 rounded-2xl bg-[#ffffff] border-2 border-[#173d34]/25 shadow-md transition-all animate-fade-in">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-[11px] font-bold text-[#173d34] flex items-center gap-1.5 font-mono uppercase tracking-wider">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5 text-amber-500"></i>
                                <span>Specify Your Custom Workspace or Domain:</span>
                            </label>
                            <button type="button" onclick="window.toggleQuickOtherInput('${qfUid}', false)" class="text-[11px] text-[#68716d] hover:text-[#202725] px-2 py-0.5 rounded-lg hover:bg-[#ede8dc] transition-colors cursor-pointer font-semibold">✕ Close</button>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" id="${qfUid}_input" placeholder="e.g. Graphic Designer, Medical Research, 3D Artist, Student, Real Estate..." class="flex-1 bg-[#fcfaf5] border border-[#ddd8cc] focus:border-[#173d34] text-[#202725] text-xs rounded-xl px-3.5 py-2.5 outline-none shadow-inner" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();window.submitQuickOtherInput('${qfUid}');}">
                            <button type="button" onclick="window.submitQuickOtherInput('${qfUid}')" class="px-4 py-2.5 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5 shadow-sm active:scale-95 cursor-pointer flex-shrink-0">
                                <span>Submit</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                        <div class="text-[10px] text-[#68716d] mt-2 font-medium flex items-center gap-1">
                            <i data-lucide="info" class="w-3 h-3 text-amber-500"></i>
                            <span>RuXt will configure its specialized role to match what you enter.</span>
                        </div>
                    </div>

                    ${(hasOther || needsOnboarding) ? `
                        <!-- Skip / Continue Directly Bar -->
                        <div class="mt-3 pt-2.5 border-t border-[#ddd8cc]/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <div class="text-[11px] text-[#68716d] flex items-center gap-1.5">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-500 flex-shrink-0"></i>
                                <span>Prefer to skip setup and start chatting right away?</span>
                            </div>
                            <button type="button" onclick="window.skipOnboarding()" class="px-3.5 py-1.5 rounded-xl bg-[#ffffff] hover:bg-[#ede8dc] text-[#173d34] border border-[#ddd8cc] hover:border-[#173d34]/40 font-bold transition-all flex items-center justify-center space-x-1.5 shadow-2xs active:scale-95 cursor-pointer flex-shrink-0">
                                <span>Skip & Continue</span>
                                <i data-lucide="chevrons-right" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
        }

        let roleConfiguredHtml = '';
        if (!isUser && roleConfigured && roleConfigured.role_title) {
            roleConfiguredHtml = `
                <div class="mt-3.5 p-3.5 rounded-2xl bg-gradient-to-br from-[#ffffff] via-[#fcfaf5] to-[#f5f2e9] border-2 border-[#173d34]/30 shadow-md relative overflow-hidden animate-in zoom-in-95 duration-200">
                    <div class="flex items-center justify-between pb-2.5 border-b border-[#ddd8cc]/80 mb-2.5">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-xl bg-[#173d34] text-[#fcfaf5] flex items-center justify-center shadow-sm">
                                <i data-lucide="award" class="w-4 h-4 text-amber-300"></i>
                            </div>
                            <div>
                                <span class="text-[9px] font-mono font-bold uppercase tracking-wider text-[#173d34] bg-[#173d34]/10 px-2 py-0.5 rounded-full border border-[#173d34]/20">Role Activated</span>
                                <h4 class="text-xs sm:text-sm font-extrabold text-[#202725] mt-0.5">${escapeHtml(roleConfigured.role_title)}</h4>
                            </div>
                        </div>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>ACTIVE</span>
                        </span>
                    </div>
                    
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center justify-between bg-white p-2 rounded-xl border border-[#ddd8cc]">
                            <span class="text-[10px] text-[#68716d] font-mono uppercase">User Field:</span>
                            <span class="font-bold text-[#202725]">${escapeHtml(roleConfigured.field)}</span>
                        </div>
                        <div class="bg-white p-2 rounded-xl border border-[#ddd8cc]">
                            <span class="text-[10px] text-[#68716d] font-mono uppercase block mb-0.5">Specialized Focus:</span>
                            <span class="font-semibold text-[#173d34]">${escapeHtml(roleConfigured.specialization || 'Strategic domain assistance & execution')}</span>
                        </div>
                    </div>

                    <div class="mt-2.5 pt-2 border-t border-[#ddd8cc]/60 text-[10px] text-[#68716d] flex items-center justify-between">
                        <span>Memory vault & system instruction updated</span>
                        <span class="text-[#173d34] font-bold">Ready to assist →</span>
                    </div>
                </div>
            `;
        }

        let attachmentsHtml = '';
        let attList = [];
        if (typeof attachments === 'string') {
            try { attList = JSON.parse(attachments); } catch(e) {}
        } else if (Array.isArray(attachments)) {
            attList = attachments;
        }

        if (attList && attList.length > 0) {
            const images = attList.filter(a => a.is_image || (a.mime_type && a.mime_type.startsWith('image/')));
            const docs = attList.filter(a => !(a.is_image || (a.mime_type && a.mime_type.startsWith('image/'))));

            let imagesHtml = '';
            if (images.length > 0) {
                imagesHtml = `
                    <div class="grid ${images.length === 1 ? 'grid-cols-1' : (images.length === 2 ? 'grid-cols-2' : 'grid-cols-2 sm:grid-cols-3')} gap-2 my-2">
                        ${images.map(img => {
                            const imgSrc = escapeHtml(img.url || img.file_path || '');
                            const imgName = escapeHtml(img.original_name || 'Reference Image');
                            return `
                                <div class="relative group rounded-xl overflow-hidden border border-[#ddd8cc] bg-black/5 aspect-video sm:aspect-auto sm:max-h-60 cursor-pointer shadow-2xs" onclick="window.openImageLightbox('${imgSrc}', '${imgName}')">
                                    <img src="${imgSrc}" alt="${imgName}" class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105" loading="lazy">
                                    <div class="absolute inset-0 bg-black/35 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                        <div class="p-2 rounded-full bg-white/20 backdrop-blur-xs flex items-center justify-center">
                                            <i data-lucide="zoom-in" class="w-5 h-5 drop-shadow"></i>
                                        </div>
                                    </div>
                                    <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-2 text-[10px] text-white truncate font-mono">
                                        ${imgName}
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            }

            let docsHtml = '';
            if (docs.length > 0) {
                docsHtml = `
                    <div class="flex flex-col gap-1.5 my-2">
                        ${docs.map(doc => {
                            const docUrl = escapeHtml(doc.url || doc.file_path || '#');
                            const docName = escapeHtml(doc.original_name || 'Document');
                            const docSize = escapeHtml(doc.size_formatted || '');
                            const ext = escapeHtml((doc.extension || 'FILE').toUpperCase());
                            return `
                                <a href="${docUrl}" target="_blank" download="${docName}" class="flex items-center justify-between p-2.5 rounded-xl border border-[#ddd8cc] ${isUser ? 'bg-white/95 text-[#202725]' : 'bg-[#fcfaf5] text-[#202725]'} hover:border-[#173d34] transition-all group shadow-2xs">
                                    <div class="flex items-center space-x-2.5 overflow-hidden">
                                        <div class="w-8 h-8 rounded-lg bg-[#173d34]/10 text-[#173d34] flex items-center justify-center flex-shrink-0 font-bold text-[10px] font-mono border border-[#173d34]/15">
                                            ${ext}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold truncate group-hover:text-[#173d34] transition-colors">${docName}</div>
                                            <div class="text-[10px] text-[#68716d] font-mono">${docSize}</div>
                                        </div>
                                    </div>
                                    <div class="p-1.5 text-[#68716d] group-hover:text-[#173d34] flex-shrink-0">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </div>
                                </a>
                            `;
                        }).join('')}
                    </div>
                `;
            }

            attachmentsHtml = `
                <div class="attachment-gallery mb-2.5">
                    ${imagesHtml}
                    ${docsHtml}
                </div>
            `;
        }

        const bubble = `
            <div class="max-w-[85%] md:max-w-[75%] rounded-2xl p-4 ${isUser ? 'user-bubble' : 'assistant-bubble prose-custom'}">
                ${projectBadge}
                ${attachmentsHtml}
                ${parsedContent ? `<div class="message-body">${parsedContent}</div>` : ''}
                ${quickFieldsHtml}
                ${roleConfiguredHtml}
                ${systemActionsCard}
                ${workPreviewCard}
                ${promptCardHtml}
                ${liveWorkspaceHtml}
                ${discussionCard}
                ${delegatedCard}
                ${!isUser ? `
                    <div class="flex items-center justify-between mt-3 pt-2 border-t border-[#ddd8cc] text-[11px] text-[#68716d]">
                        <span class="text-[#173d34] font-bold uppercase tracking-wider text-[10px]">${escapeHtml(exec ? exec.name : 'Executive')}</span>
                        <div class="flex items-center space-x-2">
                            <button class="speak-btn hover:text-[#173d34] flex items-center space-x-1 transition-colors" title="Read aloud">
                                <i data-lucide="volume-2" class="w-3.5 h-3.5"></i>
                            </button>
                            <button class="copy-btn hover:text-[#173d34] flex items-center space-x-1 transition-colors" title="Copy text">
                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;

        msgDiv.innerHTML = isUser ? (bubble + avatar) : (avatar + bubble);
        messagesList.appendChild(msgDiv);

        if (!isUser) {
            const speakBtn = msgDiv.querySelector('.speak-btn');
            const copyBtn = msgDiv.querySelector('.copy-btn');
            if (speakBtn) speakBtn.onclick = () => playExecutiveVoice(content, state.selectedExecutiveId);
            if (copyBtn) copyBtn.onclick = () => {
                navigator.clipboard.writeText(content);
                copyBtn.innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i>`;
                if (window.lucide) lucide.createIcons();
                setTimeout(() => {
                    copyBtn.innerHTML = `<i data-lucide="copy" class="w-3.5 h-3.5"></i>`;
                    if (window.lucide) lucide.createIcons();
                }, 1800);
            };

            if (window.hljs) {
                msgDiv.querySelectorAll('pre code').forEach(el => hljs.highlightElement(el));
            }
        }

        if (window.lucide) lucide.createIcons();
        scrollToBottom();
    }

    function scrollToBottom() {
        chatContainer.scrollTo({ top: chatContainer.scrollHeight, behavior: 'smooth' });
    }

    function autoResizeInput() {
        chatInput.style.height = 'auto';
        chatInput.style.height = Math.min(chatInput.scrollHeight, 140) + 'px';
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // 6. Hire Executive Handler
    async function handleHireExecutive(e) {
        e.preventDefault();
        const name = document.getElementById('hireName').value.trim();
        const title = document.getElementById('hireTitle').value.trim();
        const department = document.getElementById('hireDept').value.trim();
        const avatar_url = document.getElementById('hireAvatar').value.trim();
        const bio = document.getElementById('hireBio').value.trim();
        const system_prompt = document.getElementById('hirePrompt').value.trim();
        const ventureId = hireVentureSelect && hireVentureSelect.value ? parseInt(hireVentureSelect.value) : state.currentVentureId;

        if (!name || !title) return;

        const submitBtn = document.getElementById('submitHireBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Appointing Executive...';

        try {
            const res = await fetch('api/executives.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, title, department, avatar_url, bio, system_prompt, venture_id: ventureId })
            });

            const data = await res.json();
            if (data.success) {
                hireForm.reset();
                closeHireModal();
                if (ventureId != state.currentVentureId) {
                    await switchVenture(ventureId);
                } else {
                    await loadExecutives();
                }
                selectExecutive(data.executive.id);
            } else {
                alert(data.error || 'Failed to hire executive');
            }
        } catch (err) {
            console.error('Error hiring executive:', err);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Appoint & Onboard Executive';
        }
    }

    // 7. Memory & Company Vault
    async function loadMemories() {
        try {
            const res = await fetch('api/memories.php');
            const data = await res.json();
            if (data.memories) {
                state.memories = data.memories;
                if (memoriesCountBadge) memoriesCountBadge.textContent = state.memories.length;
                const settingsMemBadge = document.getElementById('settingsMemoriesCountBadge');
                if (settingsMemBadge) {
                    settingsMemBadge.textContent = `${state.memories.length} ${state.memories.length === 1 ? 'memory' : 'memories'}`;
                }
                renderMemories();
            }
        } catch (err) {
            console.error('Failed to load memories:', err);
        }
    }

    function renderMemories() {
        if (!memoriesList) return;
        memoriesList.innerHTML = '';
        if (state.memories.length === 0) {
            memoriesList.innerHTML = `<div class="text-center py-6 text-[#68716d] text-xs">No strategic facts added yet.</div>`;
            return;
        }

        state.memories.forEach(mem => {
            const item = document.createElement('div');
            item.className = 'flex items-start justify-between p-3 rounded-xl bg-[#fcfaf5] border border-[#ddd8cc] text-xs shadow-sm';
            item.innerHTML = `
                <div class="flex items-start space-x-2 flex-1 pr-2">
                    <span class="text-[9px] uppercase font-bold px-2 py-0.5 rounded-full border border-[#173d34]/20 bg-[#173d34]/10 text-[#173d34] mt-0.5 flex-shrink-0">${escapeHtml(mem.category)}</span>
                    <span class="text-[#202725] font-medium leading-relaxed">${escapeHtml(mem.content)}</span>
                </div>
                <button onclick="window.deleteMemoryItem(${mem.id})" class="text-[#68716d] hover:text-rose-500 p-1 rounded flex-shrink-0">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            `;
            memoriesList.appendChild(item);
        });

        if (window.lucide) lucide.createIcons();
    }

    async function addMemory() {
        const text = newMemoryInput.value.trim();
        const category = newMemoryCategory.value;
        if (!text) return;

        try {
            const res = await fetch('api/memories.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content: text, category })
            });
            const data = await res.json();
            if (data.success) {
                newMemoryInput.value = '';
                loadMemories();
            }
        } catch (err) {
            console.error('Failed to add memory:', err);
        }
    }

    async function deleteMemory(id) {
        try {
            await fetch(`api/memories.php?id=${id}`, { method: 'DELETE' });
            loadMemories();
        } catch (err) {
            console.error('Failed to delete memory:', err);
        }
    }

    function promptClearAllMemories() {
        if (!state.memories || state.memories.length === 0) {
            if (typeof showToastNotification === 'function') {
                showToastNotification('RuXt memory vault is already empty. No CEO memories to clear.', 'error');
            } else {
                alert('RuXt memory vault is already empty.');
            }
            return;
        }

        const confirmModal = document.getElementById('confirmClearMemoriesModal');
        const confirmMemCount = document.getElementById('confirmMemCount');
        if (confirmMemCount) {
            confirmMemCount.textContent = state.memories.length;
        }

        if (confirmModal) {
            confirmModal.classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        } else {
            if (confirm(`Are you sure you want to permanently clear all ${state.memories.length} memories stored about you (CEO)? This action cannot be undone.`)) {
                executeClearAllMemories();
            }
        }
    }

    function closeConfirmClearMemories() {
        const confirmModal = document.getElementById('confirmClearMemoriesModal');
        if (confirmModal) {
            confirmModal.classList.add('hidden');
        }
    }

    async function executeClearAllMemories() {
        const confirmBtn = document.getElementById('confirmClearMemoriesBtn');
        const origContent = confirmBtn ? confirmBtn.innerHTML : '';
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="inline-block animate-spin mr-1">⌛</span> Clearing...';
        }

        try {
            const res = await fetch('api/memories.php?all=true', { method: 'DELETE' });
            const data = await res.json();
            if (data.success) {
                state.memories = [];
                if (memoriesCountBadge) memoriesCountBadge.textContent = '0';
                const settingsMemBadge = document.getElementById('settingsMemoriesCountBadge');
                if (settingsMemBadge) settingsMemBadge.textContent = '0 memories';
                renderMemories();
                closeConfirmClearMemories();

                // Reset user onboarding state in client
                if (state.user) {
                    state.user.onboarding_completed = 0;
                    state.user.job_field = null;
                    state.user.specialized_role = null;
                }
                const pField = document.getElementById('profileUserJobField');
                if (pField) pField.textContent = 'Pending Onboarding';
                const pRole = document.getElementById('profileRuxtSpecializedRole');
                if (pRole) pRole.textContent = 'Chief of Staff & Lead Assistant';

                // Automatically trigger the welcome onboarding consultation
                await checkAndTriggerOnboarding(true);

                if (typeof showToastNotification === 'function') {
                    showToastNotification('All memories cleared. RuXt welcome consultation initiated.');
                } else {
                    alert('All memories cleared. RuXt welcome consultation initiated.');
                }
            } else {
                alert('Failed to clear memories: ' + (data.error || 'Unknown error'));
            }
        } catch (err) {
            console.error('Failed to clear memories:', err);
            alert('A network error occurred while clearing memories.');
        } finally {
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = origContent;
            }
        }
    }

    // ============================================
    // 8. Projects & Multi-Project Task Management
    // ============================================
    async function loadProjects() {
        try {
            const url = state.currentVentureId ? `api/projects.php?venture_id=${state.currentVentureId}` : 'api/projects.php';
            const res = await fetch(url);
            const data = await res.json();
            if (data.projects) {
                state.projects = data.projects;
                renderProjectPills();
                populateTaskProjectSelect();
                updateProjectFilterDropdown();
            }
        } catch (err) {
            console.error('Failed to load projects:', err);
        }
    }

    function renderProjectPills() {
        if (!projectPillsBar) return;
        projectPillsBar.innerHTML = '';

        const allPill = document.createElement('button');
        allPill.type = 'button';
        const isAllActive = !state.taskFilterProject;
        allPill.className = `px-3.5 py-1 rounded-full text-xs font-bold flex items-center space-x-1.5 transition-all flex-shrink-0 ${
            isAllActive ? 'bg-[#173d34] text-[#fcfaf5] shadow-sm' : 'bg-[#ffffff] text-[#68716d] hover:text-[#173d34] border border-[#ddd8cc]'
        }`;
        allPill.innerHTML = `
            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
            <span>All Projects</span>
            <span class="text-[10px] px-1.5 py-0.2 rounded-full ${isAllActive ? 'bg-white/20 text-white' : 'bg-[#ede8dc] text-[#68716d]'} font-mono">${state.notes.length}</span>
        `;
        allPill.onclick = () => {
            state.taskFilterProject = '';
            if (taskProjectFilter) taskProjectFilter.value = '';
            renderProjectPills();
            renderTasksBoard();
        };
        projectPillsBar.appendChild(allPill);

        state.projects.forEach(p => {
            const pTasks = state.notes.filter(n => (n.project_id == p.id) || (n.project_name && n.project_name.toLowerCase() === p.name.toLowerCase()));
            const isSelected = state.taskFilterProject && state.taskFilterProject.toLowerCase() === p.name.toLowerCase();
            const pill = document.createElement('button');
            pill.type = 'button';
            pill.className = `px-3.5 py-1 rounded-full text-xs font-bold flex items-center space-x-1.5 transition-all flex-shrink-0 ${
                isSelected ? 'bg-[#173d34] text-[#fcfaf5] shadow-sm border-transparent' : 'bg-[#ffffff] text-[#3d4942] hover:text-[#173d34] border border-[#ddd8cc]'
            }`;
            pill.innerHTML = `
                <i data-lucide="folder-kanban" class="w-3.5 h-3.5 ${isSelected ? 'text-white' : 'text-[#799a87]'}"></i>
                <span class="truncate max-w-[130px]">${escapeHtml(p.name)}</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full ${isSelected ? 'bg-white/20 text-white' : 'bg-[#ede8dc] text-[#68716d]'} font-mono">${pTasks.length}</span>
            `;
            pill.onclick = () => {
                state.taskFilterProject = p.name;
                if (taskProjectFilter) taskProjectFilter.value = p.name;
                renderProjectPills();
                renderTasksBoard();
            };
            projectPillsBar.appendChild(pill);
        });

        const addPill = document.createElement('button');
        addPill.type = 'button';
        addPill.className = `px-3 py-1 rounded-full text-xs font-bold text-[#173d34] hover:bg-[#ede8dc] bg-[#ffffff] border border-[#ddd8cc] flex items-center space-x-1 transition-all flex-shrink-0 shadow-sm`;
        addPill.innerHTML = `
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            <span>New Project</span>
        `;
        addPill.onclick = () => openNewProjectModal();
        projectPillsBar.appendChild(addPill);

        if (window.lucide) lucide.createIcons();
    }

    function populateTaskProjectSelect(selectedProjectNameOrId = '') {
        if (!taskProjectSelect) return;
        taskProjectSelect.innerHTML = '<option value="">General Project</option>';
        state.projects.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.name;
            opt.dataset.id = p.id;
            opt.textContent = `📁 ${p.name}`;
            if (selectedProjectNameOrId && (selectedProjectNameOrId == p.id || selectedProjectNameOrId.toLowerCase() === p.name.toLowerCase())) {
                opt.selected = true;
            }
            taskProjectSelect.appendChild(opt);
        });
    }

    function updateProjectFilterDropdown() {
        if (!taskProjectFilter) return;
        const cur = state.taskFilterProject;
        taskProjectFilter.innerHTML = `<option value="">All Projects (${state.notes.length})</option>`;
        state.projects.forEach(p => {
            const pTasks = state.notes.filter(n => (n.project_id == p.id) || (n.project_name && n.project_name.toLowerCase() === p.name.toLowerCase()));
            const opt = document.createElement('option');
            opt.value = p.name;
            opt.textContent = `📁 ${p.name} (${pTasks.length})`;
            if (cur && cur.toLowerCase() === p.name.toLowerCase()) {
                opt.selected = true;
            }
            taskProjectFilter.appendChild(opt);
        });
    }

    // 8. Deliverables & Tasks
    async function loadNotes() {
        try {
            const url = state.currentVentureId ? `api/notes.php?venture_id=${state.currentVentureId}` : 'api/notes.php';
            const res = await fetch(url);
            const data = await res.json();
            if (data.notes) {
                state.notes = data.notes;
                renderTasks();
                renderProjectPills();
                updateProjectFilterDropdown();
                renderTasksBoard();
            }
        } catch (err) {
            console.error('Failed to load tasks:', err);
        }
    }

    function renderTasks() {
        if (!tasksList) return;
        tasksList.innerHTML = '';
        if (state.notes.length === 0) {
            tasksList.innerHTML = `<div class="text-center py-6 text-[#68716d] text-xs">No active deliverables. Add one above!</div>`;
            return;
        }

        state.notes.forEach(note => {
            const isDone = note.status === 'completed' || note.is_done == 1;
            const taskEl = document.createElement('div');
            taskEl.className = `flex items-center justify-between p-3 rounded-xl border transition-all text-xs ${
                isDone ? 'bg-[#f5f2e9] border-[#ddd8cc] text-[#68716d]' : 'bg-[#ffffff] border-[#ddd8cc] text-[#202725] shadow-sm'
            }`;

            const assignedBadge = note.assigned_to ? `
                <div class="flex items-center space-x-1.5 mt-1.5">
                    ${note.assigned_avatar ? `<img src="${escapeHtml(note.assigned_avatar)}" class="w-4 h-4 rounded-full object-cover border border-[#ddd8cc]">` : ''}
                    <span class="text-[10px] text-[#173d34] font-bold tracking-wide">${escapeHtml(note.assigned_to)}</span>
                    ${note.priority ? `<span class="text-[9px] px-1.5 py-0.2 rounded bg-[#ede8dc] text-[#68716d] font-mono">${escapeHtml(note.priority)}</span>` : ''}
                    ${note.project_name ? `<span class="text-[9px] px-1.5 py-0.2 rounded bg-[#173d34]/10 text-[#173d34] font-mono border border-[#173d34]/20 font-bold">📁 ${escapeHtml(note.project_name)}</span>` : ''}
                </div>
            ` : '';

            // Status Badge
            let statusBadge = '';
            if (isDone) {
                statusBadge = '<span class="text-[9px] px-1.5 py-0.5 rounded bg-[#173d34]/15 text-[#173d34] font-bold border border-[#173d34]/30">Done</span>';
            } else if (note.status === 'in_progress') {
                statusBadge = '<span class="text-[9px] px-1.5 py-0.5 rounded bg-[#b7843f]/15 text-[#9a6d32] font-bold border border-[#b7843f]/30 animate-pulse">In Progress</span>';
            } else if (note.status === 'review') {
                statusBadge = '<span class="text-[9px] px-1.5 py-0.5 rounded bg-[#799a87]/15 text-[#285c4e] font-bold border border-[#799a87]/30">In Review</span>';
            } else {
                statusBadge = '<span class="text-[9px] px-1.5 py-0.5 rounded bg-[#ede8dc] text-[#68716d] font-mono">To Do</span>';
            }

            // Quick Status Action Button
            let quickActionBtn = '';
            if (!isDone && note.status !== 'in_progress') {
                quickActionBtn = `<button type="button" onclick="event.stopPropagation(); window.moveTaskStatus(${note.id}, 'in_progress')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-[#b7843f]/15 hover:bg-[#b7843f]/25 text-[#9a6d32] border border-[#b7843f]/30 flex items-center space-x-1 transition-all mr-1 shadow-xs" title="Start Task (In Progress)"><span>Start</span> <i data-lucide="play" class="w-2.5 h-2.5 fill-current"></i></button>`;
            } else if (note.status === 'in_progress') {
                quickActionBtn = `<button type="button" onclick="event.stopPropagation(); window.moveTaskStatus(${note.id}, 'completed')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-[#173d34]/15 hover:bg-[#173d34]/25 text-[#173d34] border border-[#173d34]/30 flex items-center space-x-1 transition-all mr-1 shadow-xs" title="Mark Done"><span>Done</span> <i data-lucide="check" class="w-2.5 h-2.5"></i></button>`;
            }

            taskEl.innerHTML = `
                <div class="flex items-start space-x-3 overflow-hidden flex-1 cursor-pointer" onclick="window.toggleTaskStatus(${note.id}, ${isDone ? 0 : 1})">
                    <input type="checkbox" ${isDone ? 'checked' : ''} class="w-4 h-4 mt-0.5 rounded text-[#173d34] bg-[#ffffff] border-[#ddd8cc] focus:ring-0 cursor-pointer pointer-events-none">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center space-x-2">
                            <span class="${isDone ? 'line-through text-[#68716d]' : 'text-[#202725] font-semibold'} truncate">${escapeHtml(note.title)}</span>
                            ${statusBadge}
                        </div>
                        ${assignedBadge}
                    </div>
                </div>
                <div class="flex items-center space-x-1 ml-2 flex-shrink-0">
                    <button type="button" onclick="event.stopPropagation(); window.openTaskLiveWorkPreview(${note.id})" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-[#173d34]/10 hover:bg-[#173d34]/20 text-[#173d34] border border-[#173d34]/30 flex items-center space-x-1 transition-all mr-1 shadow-xs" title="Watch live work">
                        <i data-lucide="terminal" class="w-2.5 h-2.5 text-[#173d34]"></i>
                        <span>Live Work</span>
                    </button>
                    ${quickActionBtn}
                    <button onclick="window.deleteTaskItem(${note.id})" class="text-[#68716d] hover:text-rose-500 p-1 rounded">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            `;
            tasksList.appendChild(taskEl);
        });

        if (window.lucide) lucide.createIcons();
    }

    function renderTasksBoard() {
        if (!tasksBoardView) return;

        // Update venture badge in task board header
        const currentVenture = state.ventures.find(v => v.id == state.currentVentureId);
        if (tasksVentureBadge && currentVenture) {
            tasksVentureBadge.textContent = currentVenture.name;
        }

        // Toggle buttons style
        if (btnViewKanban && btnViewProjectWise) {
            if (state.taskViewMode === 'kanban') {
                btnViewKanban.className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-[#173d34] text-[#fcfaf5] shadow-sm flex items-center space-x-1.5 transition-all';
                btnViewProjectWise.className = 'px-2.5 py-1 text-xs font-semibold rounded-lg text-[#68716d] hover:text-[#173d34] flex items-center space-x-1.5 transition-all';
            } else {
                btnViewKanban.className = 'px-2.5 py-1 text-xs font-semibold rounded-lg text-[#68716d] hover:text-[#173d34] flex items-center space-x-1.5 transition-all';
                btnViewProjectWise.className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-[#173d34] text-[#fcfaf5] shadow-sm flex items-center space-x-1.5 transition-all';
            }
        }

        // Populate Task Assignee Filter
        if (taskAssigneeFilter) {
            const currentSelected = state.taskFilterAssignee;
            taskAssigneeFilter.innerHTML = '<option value="">All Team Members</option>';
            state.executives.forEach(ex => {
                const opt = document.createElement('option');
                opt.value = ex.name;
                opt.textContent = `${ex.name} (${ex.title.split('(')[0].trim()})`;
                if (currentSelected === ex.name) opt.selected = true;
                taskAssigneeFilter.appendChild(opt);
            });
        }

        // Filter notes
        let filteredNotes = state.notes;
        if (state.taskFilterAssignee) {
            filteredNotes = filteredNotes.filter(n => n.assigned_to === state.taskFilterAssignee);
        }
        if (state.taskFilterProject) {
            filteredNotes = filteredNotes.filter(n => (n.project_name || 'General').toLowerCase() === state.taskFilterProject.toLowerCase());
        }

        // Progress bar calculation
        const totalCount = filteredNotes.length;
        const completedTotal = filteredNotes.filter(n => n.status === 'completed' || n.is_done == 1).length;
        const pct = totalCount > 0 ? Math.round((completedTotal / totalCount) * 100) : 0;
        if (tasksProgressBar) tasksProgressBar.style.width = `${pct}%`;
        if (tasksProgressPct) tasksProgressPct.textContent = `${pct}%`;

        // Nav badge: count open/active tasks
        const openTasksCount = state.notes.filter(n => n.status !== 'completed' && n.is_done != 1).length;
        if (navTasksCountBadge) navTasksCountBadge.textContent = openTasksCount;
        const bottomNavTasksBadge = document.getElementById('bottomNavTasksBadge');
        if (bottomNavTasksBadge) bottomNavTasksBadge.textContent = openTasksCount;

        // Mode switch rendering
        if (state.taskViewMode === 'project_wise') {
            if (kanbanLanesContainer) kanbanLanesContainer.classList.add('hidden');
            if (projectWiseContainer) projectWiseContainer.classList.remove('hidden');
            renderProjectWiseBoard(filteredNotes);
        } else {
            if (kanbanLanesContainer) kanbanLanesContainer.classList.remove('hidden');
            if (projectWiseContainer) projectWiseContainer.classList.add('hidden');

            // Group by 4 status lanes
            const todoList = filteredNotes.filter(n => (n.status || 'todo') === 'todo' && n.is_done != 1);
            const inProgressList = filteredNotes.filter(n => n.status === 'in_progress' && n.is_done != 1);
            const reviewList = filteredNotes.filter(n => n.status === 'review' && n.is_done != 1);
            const completedList = filteredNotes.filter(n => n.status === 'completed' || n.is_done == 1);

            if (countColTodo) countColTodo.textContent = todoList.length;
            if (countColInProgress) countColInProgress.textContent = inProgressList.length;
            if (countColReview) countColReview.textContent = reviewList.length;
            if (countColCompleted) countColCompleted.textContent = completedList.length;

            // Update mobile lane counter badges
            const mobileCountTodo = document.getElementById('mobileCountTodo');
            const mobileCountInProgress = document.getElementById('mobileCountInProgress');
            const mobileCountReview = document.getElementById('mobileCountReview');
            const mobileCountCompleted = document.getElementById('mobileCountCompleted');
            if (mobileCountTodo) mobileCountTodo.textContent = todoList.length;
            if (mobileCountInProgress) mobileCountInProgress.textContent = inProgressList.length;
            if (mobileCountReview) mobileCountReview.textContent = reviewList.length;
            if (mobileCountCompleted) mobileCountCompleted.textContent = completedList.length;

            // Render each lane
            renderKanbanColumn(colTodoCards, todoList, 'todo');
            renderKanbanColumn(colInProgressCards, inProgressList, 'in_progress');
            renderKanbanColumn(colReviewCards, reviewList, 'review');
            renderKanbanColumn(colCompletedCards, completedList, 'completed');
            setupKanbanDragAndDrop();
        }

        if (window.lucide) lucide.createIcons();
    }

    function renderProjectWiseBoard(notesToRender = null) {
        if (!projectWiseContainer) return;
        projectWiseContainer.innerHTML = '';

        const activeNotes = notesToRender !== null ? notesToRender : state.notes;
        const projectGroups = {};

        // 1. Initialize known projects from state.projects
        state.projects.forEach(p => {
            projectGroups[p.name] = {
                id: p.id,
                name: p.name,
                description: p.description || '',
                color: p.color || 'indigo',
                icon: p.icon || 'folder-kanban',
                status: p.status || 'active',
                tasks: []
            };
        });

        // 2. Put notes into project groups
        activeNotes.forEach(note => {
            const pName = note.project_name || 'General';
            if (!projectGroups[pName]) {
                projectGroups[pName] = {
                    id: note.project_id || null,
                    name: pName,
                    description: '',
                    color: 'indigo',
                    icon: 'folder-kanban',
                    status: 'active',
                    tasks: []
                };
            }
            projectGroups[pName].tasks.push(note);
        });

        // 3. Filter keys if project filter is active
        let groupNames = Object.keys(projectGroups);
        if (state.taskFilterProject) {
            groupNames = groupNames.filter(name => name.toLowerCase() === state.taskFilterProject.toLowerCase());
        }

        if (groupNames.length === 0) {
            projectWiseContainer.innerHTML = `
                <div class="h-48 flex flex-col items-center justify-center text-center p-6 rounded-2xl border border-dashed border-slate-800 text-slate-500">
                    <i data-lucide="folder-x" class="w-8 h-8 mb-2 text-slate-600"></i>
                    <span class="text-sm font-semibold text-slate-400">No project deliverables found</span>
                    <span class="text-xs text-slate-500 mt-1">Start chatting with RuXt or click "+ New Project" above!</span>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            return;
        }

        groupNames.forEach(pName => {
            const grp = projectGroups[pName];
            const tasks = grp.tasks;
            const total = tasks.length;
            const completed = tasks.filter(t => t.status === 'completed' || t.is_done == 1).length;
            const inProg = tasks.filter(t => t.status === 'in_progress').length;
            const review = tasks.filter(t => t.status === 'review').length;
            const todo = tasks.filter(t => (t.status || 'todo') === 'todo' && t.is_done != 1).length;
            const pct = total > 0 ? Math.round((completed / total) * 100) : 0;

            const colorTheme = {
                indigo: { badge: 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30', bar: 'from-indigo-500 to-cyan-500' },
                cyan: { badge: 'bg-cyan-500/15 text-cyan-300 border-cyan-500/30', bar: 'from-cyan-500 to-teal-400' },
                emerald: { badge: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30', bar: 'from-emerald-500 to-teal-400' },
                amber: { badge: 'bg-amber-500/15 text-amber-300 border-amber-500/30', bar: 'from-amber-500 to-orange-400' },
                rose: { badge: 'bg-rose-500/15 text-rose-300 border-rose-500/30', bar: 'from-rose-500 to-pink-500' },
                purple: { badge: 'bg-purple-500/15 text-purple-300 border-purple-500/30', bar: 'from-purple-500 to-indigo-500' }
            }[grp.color] || { badge: 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30', bar: 'from-indigo-500 to-cyan-500' };

            const pBlock = document.createElement('div');
            pBlock.className = 'p-4 md:p-5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34]/40 shadow-sm space-y-4 transition-all';

            let tasksListHtml = '';
            if (tasks.length === 0) {
                tasksListHtml = `
                    <div class="p-4 rounded-xl border border-dashed border-[#ddd8cc] text-center text-[#68716d] text-xs">
                        No action items created yet under this project.
                    </div>
                `;
            } else {
                tasks.forEach(t => {
                    const isDone = t.status === 'completed' || t.is_done == 1;
                    tasksListHtml += `
                        <div class="flex items-center justify-between p-3 rounded-xl border text-xs transition-all ${
                            isDone ? 'bg-[#f5f2e9] border-[#ddd8cc] opacity-75' : 'bg-[#ffffff] border-[#ddd8cc] hover:border-[#173d34] shadow-sm'
                        }">
                            <div class="flex items-start space-x-3 overflow-hidden flex-1 cursor-pointer" onclick="window.toggleTaskStatus(${t.id}, ${isDone ? 0 : 1})">
                                <input type="checkbox" ${isDone ? 'checked' : ''} class="w-4 h-4 mt-0.5 rounded text-[#173d34] bg-[#ffffff] border-[#ddd8cc] focus:ring-0 cursor-pointer pointer-events-none">
                                <div class="min-w-0">
                                    <span class="${isDone ? 'line-through text-[#68716d]' : 'text-[#202725]'} font-semibold block truncate">${escapeHtml(t.title)}</span>
                                    <div class="flex items-center flex-wrap gap-2 mt-1">
                                        ${t.assigned_to ? `
                                            <div class="flex items-center space-x-1">
                                                ${t.assigned_avatar ? `<img src="${escapeHtml(t.assigned_avatar)}" class="w-3.5 h-3.5 rounded-full object-cover">` : ''}
                                                <span class="text-[10px] text-[#68716d]">${escapeHtml(t.assigned_to)}</span>
                                            </div>
                                        ` : ''}
                                        <span class="text-[9px] uppercase font-mono px-1.5 py-0.2 rounded ${
                                            t.priority === 'High' ? 'bg-rose-500/15 text-rose-700 border border-rose-500/30' : 'bg-[#ede8dc] text-[#68716d]'
                                        }">${escapeHtml(t.priority || 'Medium')}</span>
                                        <span class="text-[9px] font-semibold px-1.5 py-0.2 rounded ${
                                            isDone ? 'bg-[#173d34]/15 text-[#173d34]' : (t.status === 'in_progress' ? 'bg-[#b7843f]/15 text-[#9a6d32]' : 'bg-[#ede8dc] text-[#68716d]')
                                        }">${escapeHtml(t.status || 'todo')}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1 ml-2 flex-shrink-0">
                                <button type="button" onclick="event.stopPropagation(); window.openTaskLiveWorkPreview(${t.id})" class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#173d34]/10 hover:bg-[#173d34]/20 text-[#173d34] border border-[#173d34]/25 flex items-center space-x-1 transition-all mr-1" title="Watch Live Execution Stream">
                                    <i data-lucide="activity" class="w-2.5 h-2.5 text-[#173d34]"></i>
                                    <span>Live ⚡</span>
                                </button>
                                ${(!isDone && t.status !== 'in_progress') ? `
                                    <button type="button" onclick="event.stopPropagation(); window.moveTaskStatus(${t.id}, 'in_progress')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#b7843f]/15 hover:bg-[#b7843f]/25 text-[#9a6d32] border border-[#b7843f]/30 flex items-center space-x-1 transition-all mr-1" title="Start Task (In Progress)">
                                        <span>Start</span>
                                        <i data-lucide="play" class="w-2.5 h-2.5 fill-current"></i>
                                    </button>
                                ` : ''}
                                ${t.status === 'in_progress' ? `
                                    <button type="button" onclick="event.stopPropagation(); window.moveTaskStatus(${t.id}, 'completed')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#173d34]/15 hover:bg-[#173d34]/25 text-[#173d34] border border-[#173d34]/30 flex items-center space-x-1 transition-all mr-1" title="Mark Done">
                                        <span>Done</span>
                                        <i data-lucide="check" class="w-2.5 h-2.5"></i>
                                    </button>
                                ` : ''}
                                <button onclick="window.openTaskEditModal(${t.id})" class="text-[#68716d] hover:text-[#173d34] p-1 rounded transition-colors" title="Edit Task">
                                    <i data-lucide="edit-3" class="w-3 h-3"></i>
                                </button>
                                <button onclick="window.deleteTaskItem(${t.id})" class="text-[#68716d] hover:text-rose-500 p-1 rounded transition-colors" title="Delete Task">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
            }

            pBlock.innerHTML = `
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#ddd8cc]">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-[#173d34]/10 text-[#173d34] flex items-center justify-center border border-[#173d34]/20 flex-shrink-0">
                            <i data-lucide="folder-kanban" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-sm font-bold text-[#202725] tracking-tight">${escapeHtml(grp.name)}</h3>
                                <span class="text-[9px] uppercase font-mono font-bold px-1.5 py-0.2 rounded bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20">Project</span>
                            </div>
                            ${grp.description ? `<p class="text-[11px] text-[#68716d] line-clamp-1 mt-0.5">${escapeHtml(grp.description)}</p>` : ''}
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <button type="button" onclick="window.openProjectLiveSprintPreview('${escapeHtml(grp.name)}')" class="px-3 py-1 text-xs rounded-full bg-[#173d34]/10 hover:bg-[#173d34]/20 text-[#173d34] border border-[#173d34]/25 font-bold flex items-center space-x-1 transition-all shadow-sm" title="Watch live project sprint execution">
                            <i data-lucide="activity" class="w-3 h-3 text-[#173d34]"></i>
                            <span>Live Sprint ⚡</span>
                        </button>
                        ${todo > 0 ? `
                            <button type="button" onclick="window.startAllProjectTasks('${escapeHtml(grp.name)}')" class="px-3 py-1 text-xs rounded-full bg-[#b7843f]/15 hover:bg-[#b7843f]/25 text-[#9a6d32] border border-[#b7843f]/30 font-bold flex items-center space-x-1 transition-all shadow-sm" title="Start all To Do deliverables in this project">
                                <i data-lucide="play" class="w-3 h-3 fill-current"></i>
                                <span>Start All</span>
                            </button>
                        ` : ''}
                        <button type="button" onclick="window.openChatWithRuXtAboutProject('${escapeHtml(grp.name)}')" class="px-3 py-1 text-xs rounded-full bg-[#ffffff] hover:bg-[#f5f2e9] text-[#173d34] border border-[#ddd8cc] font-bold flex items-center space-x-1 transition-all shadow-sm">
                            <i data-lucide="message-square" class="w-3 h-3 text-[#173d34]"></i>
                            <span>Brief RuXt</span>
                        </button>
                        <button type="button" onclick="window.openTaskCreateModalForProject('${escapeHtml(grp.name)}', ${grp.id || 'null'})" class="px-3 py-1 text-xs rounded-full bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] font-bold flex items-center space-x-1 shadow-sm transition-all">
                            <i data-lucide="plus" class="w-3 h-3"></i>
                            <span>Add Task</span>
                        </button>
                    </div>
                </div>

                <!-- Project Progress & Metrics Bar -->
                <div class="flex items-center justify-between text-xs bg-[#f5f2e9] p-2.5 rounded-xl border border-[#ddd8cc]">
                    <div class="flex items-center space-x-3 text-[11px] flex-wrap gap-y-1">
                        <span class="text-[#68716d]">Total: <strong class="text-[#202725]">${total}</strong></span>
                        <span class="text-[#ddd8cc]">•</span>
                        <span class="text-[#68716d]">To Do: <strong class="text-[#202725]">${todo}</strong></span>
                        <span class="text-[#ddd8cc]">•</span>
                        <span class="text-[#b7843f]">In Progress: <strong>${inProg}</strong></span>
                        <span class="text-[#ddd8cc]">•</span>
                        <span class="text-[#799a87]">Review: <strong>${review}</strong></span>
                        <span class="text-[#ddd8cc]">•</span>
                        <span class="text-[#173d34]">Done: <strong>${completed}</strong></span>
                    </div>
                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <div class="w-24 bg-[#ede8dc] h-2 rounded-full overflow-hidden border border-[#ddd8cc]">
                            <div class="bg-[#173d34] h-full transition-all" style="width: ${pct}%"></div>
                        </div>
                        <span class="font-mono font-bold text-[11px] text-[#173d34]">${pct}%</span>
                    </div>
                </div>

                <!-- Deliverables Task List -->
                <div class="space-y-2 pt-1">
                    ${tasksListHtml}
                </div>
            `;

            projectWiseContainer.appendChild(pBlock);
        });

        if (window.lucide) lucide.createIcons();
    }

    function renderKanbanColumn(container, list, columnStatus) {
        if (!container) return;
        container.innerHTML = '';

        if (list.length === 0) {
            container.innerHTML = `
                <div class="h-28 flex flex-col items-center justify-center text-center p-3 rounded-xl border border-dashed border-[#ddd8cc] text-[#68716d] text-[11px]">
                    <span>No tasks in this lane</span>
                </div>
            `;
            return;
        }

        list.forEach(note => {
            const card = document.createElement('div');
            const isCompleted = note.status === 'completed' || note.is_done == 1;
            card.className = `p-3 rounded-xl border transition-all text-xs bg-[#ffffff] hover:border-[#173d34] shadow-sm space-y-2.5 cursor-grab active:cursor-grabbing ${
                isCompleted ? 'border-[#ddd8cc] opacity-80 bg-[#f5f2e9]' : 'border-[#ddd8cc]'
            }`;

            // HTML5 Drag and Drop attributes
            card.setAttribute('draggable', 'true');
            card.dataset.id = note.id;
            card.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', note.id.toString());
                e.dataTransfer.effectAllowed = 'move';
                card.classList.add('opacity-40');
            });
            card.addEventListener('dragend', () => {
                card.classList.remove('opacity-40');
            });

            // Priority styling
            const priorityColors = {
                High: 'bg-rose-500/15 text-rose-700 border-rose-500/30',
                Medium: 'bg-[#b7843f]/15 text-[#9a6d32] border border-[#b7843f]/30',
                Low: 'bg-[#ede8dc] text-[#68716d] border border-[#ddd8cc]'
            };
            const prioClass = priorityColors[note.priority] || priorityColors.Medium;

            // Project tag
            const projectTagHtml = note.project_name ? `
                <span onclick="event.stopPropagation(); window.filterByProject('${escapeHtml(note.project_name)}')" class="inline-flex items-center space-x-1 text-[10px] px-2 py-0.5 rounded-full bg-[#173d34]/10 text-[#173d34] hover:bg-[#173d34]/20 border border-[#173d34]/20 cursor-pointer font-bold transition-all" title="Filter by Project">
                    <i data-lucide="folder-kanban" class="w-3 h-3 text-[#173d34]"></i>
                    <span class="truncate max-w-[140px]">${escapeHtml(note.project_name)}</span>
                </span>
            ` : '';

            // Assignee display
            const assigneeHtml = note.assigned_to ? `
                <div class="flex items-center space-x-1.5">
                    ${note.assigned_avatar ? `<img src="${escapeHtml(note.assigned_avatar)}" class="w-4 h-4 rounded-full object-cover border border-[#ddd8cc]">` : ''}
                    <span class="text-[11px] text-[#202725] font-semibold truncate max-w-[110px]">${escapeHtml(note.assigned_to)}</span>
                </div>
            ` : `<span class="text-[10px] text-[#68716d] italic">Unassigned</span>`;

            // Discussion link button
            const discussionHtml = note.discussion_id ? `
                <button type="button" onclick="window.openDiscussionModal(${note.discussion_id})" class="inline-flex items-center space-x-1 text-[10px] px-2 py-0.5 rounded-full bg-[#b7843f]/15 text-[#9a6d32] hover:bg-[#b7843f]/25 border border-[#b7843f]/30 transition-all font-bold">
                    <i data-lucide="message-circle" class="w-3 h-3 text-[#b7843f]"></i>
                    <span>Team Dialogue</span>
                </button>
            ` : '';

            // Quick status transition button
            let quickActionHtml = '';
            if (!isCompleted && (note.status === 'todo' || !note.status)) {
                quickActionHtml = `<button type="button" onclick="event.stopPropagation(); window.moveTaskStatus(${note.id}, 'in_progress')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#b7843f]/15 hover:bg-[#b7843f]/30 text-[#9a6d32] border border-[#b7843f]/30 flex items-center space-x-1 transition-all" title="Start Sprint Task (Move to In Progress)"><span>Start</span> <i data-lucide="play" class="w-2.5 h-2.5 fill-current"></i></button>`;
            } else if (note.status === 'in_progress') {
                quickActionHtml = `<button type="button" onclick="event.stopPropagation(); window.moveTaskStatus(${note.id}, 'completed')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#173d34]/15 hover:bg-[#173d34]/30 text-[#173d34] border border-[#173d34]/30 flex items-center space-x-1 transition-all" title="Mark Task Complete"><span>Done</span> <i data-lucide="check" class="w-2.5 h-2.5"></i></button>`;
            } else if (note.status === 'review') {
                quickActionHtml = `<button type="button" onclick="event.stopPropagation(); window.moveTaskStatus(${note.id}, 'completed')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#173d34]/15 hover:bg-[#173d34]/30 text-[#173d34] border border-[#173d34]/30 flex items-center space-x-1 transition-all" title="Approve Task"><span>Approve</span> <i data-lucide="check-check" class="w-2.5 h-2.5"></i></button>`;
            } else if (isCompleted) {
                quickActionHtml = `<button type="button" onclick="event.stopPropagation(); window.moveTaskStatus(${note.id}, 'todo')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#ede8dc] hover:bg-[#ddd8cc] text-[#68716d] border border-[#ddd8cc] flex items-center space-x-1 transition-all" title="Reopen Task"><span>Reopen</span> <i data-lucide="rotate-ccw" class="w-2.5 h-2.5"></i></button>`;
            }

            // Status select dropdown
            const statusOptions = [
                { val: 'todo', label: 'To Do' },
                { val: 'in_progress', label: 'In Progress' },
                { val: 'review', label: 'In Review' },
                { val: 'completed', label: 'Done' }
            ];

            let selectHtml = `<select onchange="window.moveTaskStatus(${note.id}, this.value)" class="bg-[#f5f2e9] border border-[#ddd8cc] text-[10px] rounded-lg px-2 py-1 text-[#202725] focus:outline-none focus:border-[#173d34] cursor-pointer">`;
            statusOptions.forEach(opt => {
                selectHtml += `<option value="${opt.val}" ${note.status === opt.val ? 'selected' : ''}>${opt.label}</option>`;
            });
            selectHtml += `</select>`;

            card.innerHTML = `
                <div class="flex items-start justify-between gap-2">
                    <span class="text-[9px] uppercase tracking-wider font-mono font-bold px-1.5 py-0.5 rounded border ${prioClass}">${escapeHtml(note.priority || 'Medium')}</span>
                    <div class="flex items-center space-x-1">
                        <button onclick="window.openTaskEditModal(${note.id})" title="Edit Task" class="text-[#68716d] hover:text-[#173d34] p-1 rounded transition-colors">
                            <i data-lucide="edit-3" class="w-3 h-3"></i>
                        </button>
                        <button onclick="window.deleteTaskItem(${note.id})" title="Delete Task" class="text-[#68716d] hover:text-rose-600 p-1 rounded transition-colors">
                            <i data-lucide="trash-2" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>

                <div class="cursor-pointer" onclick="window.openTaskEditModal(${note.id})">
                    <h4 class="text-xs font-semibold text-[#202725] leading-snug line-clamp-2 hover:text-[#173d34] transition-colors ${isCompleted ? 'line-through text-[#68716d]' : ''}">
                        ${escapeHtml(note.title)}
                    </h4>
                    ${note.content ? `<p class="text-[11px] text-[#68716d] line-clamp-2 mt-1 leading-relaxed">${escapeHtml(note.content)}</p>` : ''}
                </div>

                <div class="flex items-center flex-wrap gap-1.5 pt-1">
                    ${projectTagHtml}
                    ${discussionHtml}
                </div>

                <div class="pt-2 border-t border-[#ddd8cc] flex items-center justify-between gap-2">
                    ${assigneeHtml}
                    <div class="flex items-center space-x-1.5 flex-shrink-0">
                        <button type="button" onclick="event.stopPropagation(); window.openTaskLiveWorkPreview(${note.id})" class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#173d34]/10 hover:bg-[#173d34]/20 text-[#173d34] border border-[#173d34]/25 flex items-center space-x-1 transition-all" title="Watch Live Execution Stream">
                            <i data-lucide="activity" class="w-2.5 h-2.5 text-[#173d34]"></i>
                            <span>Live ⚡</span>
                        </button>
                        ${quickActionHtml}
                        ${selectHtml}
                    </div>
                </div>
            `;

            container.appendChild(card);
        });
    }

    function setupKanbanDragAndDrop() {
        const lanes = [
            { id: 'colTodoLane', status: 'todo' },
            { id: 'colInProgressLane', status: 'in_progress' },
            { id: 'colReviewLane', status: 'review' },
            { id: 'colCompletedLane', status: 'completed' }
        ];

        lanes.forEach(({ id, status }) => {
            const laneEl = document.getElementById(id);
            if (!laneEl || laneEl.dataset.dragInit) return;
            laneEl.dataset.dragInit = 'true';

            laneEl.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                laneEl.classList.add('ring-2', 'ring-[#173d34]', 'bg-[#ede8dc]');
            });

            laneEl.addEventListener('dragleave', (e) => {
                if (!laneEl.contains(e.relatedTarget)) {
                    laneEl.classList.remove('ring-2', 'ring-[#173d34]', 'bg-[#ede8dc]');
                }
            });

            laneEl.addEventListener('drop', (e) => {
                e.preventDefault();
                laneEl.classList.remove('ring-2', 'ring-[#173d34]', 'bg-[#ede8dc]');
                const noteId = e.dataTransfer.getData('text/plain');
                if (noteId) {
                    moveTaskStatus(parseInt(noteId), status);
                }
            });
        });
    }

    async function moveTaskStatus(id, newStatus) {
        try {
            const res = await fetch('api/notes.php', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, status: newStatus })
            });
            const data = await res.json();
            if (data.success) {
                const n = state.notes.find(item => item.id == id);
                if (n) {
                    n.status = newStatus;
                    n.is_done = newStatus === 'completed' ? 1 : 0;
                }
                const labelMap = {
                    todo: 'To Do',
                    in_progress: 'In Progress',
                    review: 'In Review',
                    completed: 'Done'
                };
                showToastNotification(`📋 Task moved to ${labelMap[newStatus] || newStatus}`);
                await loadProjects();
                renderTasks();
                renderTasksBoard();
            }
        } catch (err) {
            console.error('Failed to update task status:', err);
        }
    }

    async function startAllProjectTasks(projectName, targetStatus = 'in_progress') {
        try {
            const res = await fetch('api/notes.php', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    project_name: projectName,
                    status: targetStatus,
                    venture_id: state.currentVentureId
                })
            });
            const data = await res.json();
            if (data.success) {
                const label = targetStatus === 'in_progress' ? 'In Progress' : targetStatus;
                showToastNotification(`🚀 All "${projectName}" tasks moved to ${label}!`);
                await loadProjects();
                await loadNotes();
            }
        } catch (err) {
            console.error('Failed to batch update project tasks:', err);
        }
    }

    function openTaskCreateModal(defaultProject = '', defaultProjectId = null) {
        if (!taskDetailModal) return;
        taskEditId.value = '';
        taskModalTitle.textContent = 'Create Action Item & Sprint Task';
        taskTitleInput.value = '';
        taskContentInput.value = '';
        taskStatusSelect.value = 'todo';
        taskPrioritySelect.value = 'Medium';
        taskDueDateInput.value = '';

        populateTaskAssigneeSelect();
        populateTaskProjectSelect(defaultProject || state.taskFilterProject);

        taskDetailModal.classList.remove('hidden');
        taskTitleInput.focus();
    }

    function openTaskEditModal(id) {
        const note = state.notes.find(n => n.id == id);
        if (!note || !taskDetailModal) return;

        taskEditId.value = note.id;
        taskModalTitle.textContent = 'Edit Action Item & Sprint Task';
        taskTitleInput.value = note.title || '';
        taskContentInput.value = note.content || '';
        taskStatusSelect.value = note.status || (note.is_done == 1 ? 'completed' : 'todo');
        taskPrioritySelect.value = note.priority || 'Medium';
        taskDueDateInput.value = note.due_date || '';

        populateTaskAssigneeSelect(note.assigned_to);
        populateTaskProjectSelect(note.project_name || note.project_id);

        taskDetailModal.classList.remove('hidden');
        taskTitleInput.focus();
    }

    function populateTaskAssigneeSelect(selectedName = '') {
        if (!taskAssigneeSelect) return;
        taskAssigneeSelect.innerHTML = '<option value="">Unassigned</option>';
        state.executives.forEach(ex => {
            const opt = document.createElement('option');
            opt.value = ex.name;
            opt.dataset.avatar = ex.avatar_url;
            opt.textContent = `${ex.name} — ${ex.title.split('(')[0].trim()}`;
            if (selectedName && selectedName === ex.name) opt.selected = true;
            taskAssigneeSelect.appendChild(opt);
        });
    }

    function closeTaskDetailModal() {
        if (taskDetailModal) taskDetailModal.classList.add('hidden');
    }

    async function handleSaveTaskDetail(e) {
        e.preventDefault();
        const id = taskEditId.value;
        const title = taskTitleInput.value.trim();
        const content = taskContentInput.value.trim();
        const assigned_to = taskAssigneeSelect.value;
        const selectedOption = taskAssigneeSelect.selectedOptions[0];
        const assigned_avatar = selectedOption ? (selectedOption.dataset.avatar || '') : '';
        const status = taskStatusSelect.value;
        const priority = taskPrioritySelect.value;
        const due_date = taskDueDateInput.value || null;
        const project_name = taskProjectSelect ? taskProjectSelect.value : '';
        const project_id = taskProjectSelect && taskProjectSelect.selectedOptions[0] ? (taskProjectSelect.selectedOptions[0].dataset.id || null) : null;

        if (!title) return;

        try {
            if (id) {
                await fetch('api/notes.php', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: parseInt(id),
                        title,
                        content,
                        assigned_to,
                        assigned_avatar,
                        status,
                        priority,
                        due_date,
                        project_name,
                        project_id
                    })
                });
            } else {
                await fetch('api/notes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        venture_id: state.currentVentureId,
                        title,
                        content,
                        assigned_to,
                        assigned_avatar,
                        status,
                        priority,
                        due_date,
                        project_name,
                        project_id
                    })
                });
            }
            closeTaskDetailModal();
            await loadProjects();
            await loadNotes();
        } catch (err) {
            console.error('Failed to save task:', err);
        }
    }

    // New Project Modal handlers
    function openNewProjectModal() {
        if (!newProjectModal) return;
        newProjectNameInput.value = '';
        newProjectDescInput.value = '';
        state.selectedProjectColor = 'indigo';
        updateProjectColorSelection();
        newProjectModal.classList.remove('hidden');
        newProjectNameInput.focus();
    }

    function closeNewProjectModal() {
        if (newProjectModal) newProjectModal.classList.add('hidden');
    }

    function updateProjectColorSelection() {
        if (!projectColorPicker) return;
        const btns = projectColorPicker.querySelectorAll('.project-color-opt');
        btns.forEach(b => {
            if (b.dataset.color === state.selectedProjectColor) {
                b.classList.remove('border-transparent');
                b.classList.add('border-white', 'scale-110');
            } else {
                b.classList.remove('border-white', 'scale-110');
                b.classList.add('border-transparent');
            }
        });
    }

    // ============================================
    // Team Deliberations & War Room
    // ============================================
    async function loadDiscussions() {
        try {
            const url = state.currentVentureId 
                ? `api/discussions.php?venture_id=${state.currentVentureId}` 
                : 'api/discussions.php';
            const res = await fetch(url);
            const data = await res.json();
            if (data.discussions) {
                state.discussions = data.discussions;
                if (navDiscussionsCountBadge) {
                    navDiscussionsCountBadge.textContent = state.discussions.length;
                }
                const bottomNavDiscussionsBadge = document.getElementById('bottomNavDiscussionsBadge');
                if (bottomNavDiscussionsBadge) {
                    bottomNavDiscussionsBadge.textContent = state.discussions.length;
                    if (state.discussions.length > 0) bottomNavDiscussionsBadge.classList.remove('hidden');
                    else bottomNavDiscussionsBadge.classList.add('hidden');
                }
            }
        } catch (err) {
            console.error('Failed to load discussions:', err);
        }
    }

    async function openDiscussionModal(discussionId) {
        if (!discussionModal) return;
        try {
            const res = await fetch(`api/discussions.php?id=${discussionId}`);
            const data = await res.json();
            if (!data.discussion) {
                alert('Discussion details not found.');
                return;
            }

            const disc = data.discussion;
            state.currentDiscussionId = disc.id;

            if (modalDiscussionTitle) modalDiscussionTitle.textContent = disc.title;
            if (modalDiscussionSubtitle) {
                modalDiscussionSubtitle.textContent = `${disc.venture_name || 'Venture'} • ${disc.created_at || ''}`;
            }
            if (modalDiscussionPrompt) {
                modalDiscussionPrompt.textContent = disc.user_prompt || 'CEO Instruction';
            }

            // Render Dialogue Stream
            if (modalDiscussionTurnsStream) {
                modalDiscussionTurnsStream.innerHTML = '';
                if (!disc.dialogues || disc.dialogues.length === 0) {
                    modalDiscussionTurnsStream.innerHTML = `<div class="text-center py-6 text-slate-500 text-xs">No dialogues recorded for this session.</div>`;
                } else {
                    disc.dialogues.forEach(turn => {
                        const bubble = document.createElement('div');
                        const isChief = turn.speaker_name && (turn.speaker_name.toLowerCase().includes('ruxt') || turn.speaker_name.toLowerCase().includes('nova'));
                        bubble.className = `p-3.5 rounded-2xl border transition-all text-xs ${
                            isChief 
                                ? 'bg-[#f5f2e9] border-[#173d34]/30' 
                                : 'bg-[#ffffff] border-[#ddd8cc]'
                        }`;

                        bubble.innerHTML = `
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <img src="${escapeHtml(turn.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80')}" class="w-6 h-6 rounded-full object-cover border ${isChief ? 'border-[#173d34]' : 'border-[#ddd8cc]'}">
                                    <div>
                                        <span class="font-bold ${isChief ? 'text-[#173d34]' : 'text-[#202725]'}">${escapeHtml(turn.speaker_name)}</span>
                                        <span class="text-[10px] text-[#68716d] ml-1.5 font-mono">(${escapeHtml(turn.speaker_title || 'Specialist')})</span>
                                    </div>
                                </div>
                                <span class="text-[9px] uppercase tracking-wider font-mono px-2 py-0.5 rounded-full ${isChief ? 'bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20' : 'bg-[#ede8dc] text-[#68716d]'} font-bold">
                                    ${escapeHtml(turn.turn_type || 'Executive Turn')}
                                </span>
                            </div>
                            <div class="text-[#202725] text-xs leading-relaxed pl-8">
                                ${typeof marked !== 'undefined' ? (typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(marked.parse(turn.content)) : marked.parse(turn.content)) : escapeHtml(turn.content)}
                            </div>
                        `;
                        modalDiscussionTurnsStream.appendChild(bubble);
                    });
                }
            }

            // Render Tasks Created From this Discussion
            if (modalDiscussionTasksList) {
                modalDiscussionTasksList.innerHTML = '';
                if (!disc.tasks || disc.tasks.length === 0) {
                    modalDiscussionTasksList.innerHTML = `<div class="text-[#68716d] text-xs italic pl-1">No action items formally tagged in this deliberation.</div>`;
                } else {
                    disc.tasks.forEach(task => {
                        const isDone = task.status === 'completed';
                        const item = document.createElement('div');
                        item.className = 'flex items-center justify-between p-2.5 rounded-xl bg-[#fcfaf5] border border-[#ddd8cc] text-xs';
                        item.innerHTML = `
                            <div class="flex items-center space-x-2.5 overflow-hidden flex-1">
                                <span class="w-2 h-2 rounded-full ${isDone ? 'bg-emerald-500' : 'bg-amber-500'} flex-shrink-0"></span>
                                <div class="min-w-0 flex-1">
                                    <span class="font-semibold text-[#202725] block truncate ${isDone ? 'line-through text-[#68716d]' : ''}">${escapeHtml(task.title)}</span>
                                    ${task.assigned_to ? `<span class="text-[10px] text-[#b7843f] font-mono">Assigned: ${escapeHtml(task.assigned_to)}</span>` : ''}
                                </div>
                            </div>
                            <span class="text-[10px] uppercase font-mono px-2 py-0.5 rounded ${isDone ? 'bg-emerald-500/20 text-emerald-700' : 'bg-[#ede8dc] text-[#68716d]'}">
                                ${escapeHtml(task.status || 'todo')}
                            </span>
                        `;
                        modalDiscussionTasksList.appendChild(item);
                    });
                }
            }

            discussionModal.classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        } catch (err) {
            console.error('Failed to open discussion modal:', err);
        }
    }

    function closeDiscussionModal() {
        if (discussionModal) discussionModal.classList.add('hidden');
    }

    // Member Work Preview Modal Engine
    const memberWorkPreviewModal = document.getElementById('memberWorkPreviewModal');
    const closeWorkPreviewModalBtn = document.getElementById('closeWorkPreviewModalBtn');
    const closeWorkPreviewFooterBtn = document.getElementById('closeWorkPreviewFooterBtn');
    const deliverWorkReportBtn = document.getElementById('deliverWorkReportBtn');

    let currentWorkPreview = null;

    function closeWorkPreviewModal() {
        if (memberWorkPreviewModal) memberWorkPreviewModal.classList.add('hidden');
    }

    if (closeWorkPreviewModalBtn) closeWorkPreviewModalBtn.addEventListener('click', closeWorkPreviewModal);
    if (closeWorkPreviewFooterBtn) closeWorkPreviewFooterBtn.addEventListener('click', closeWorkPreviewModal);

    function switchWorkPreviewTab(tabName) {
        const btnTerminal = document.getElementById('wpTabBtnTerminal');
        const btnVisual = document.getElementById('wpTabBtnVisual');
        const btnFindings = document.getElementById('wpTabBtnFindings');

        const paneTerminal = document.getElementById('wpPaneTerminal');
        const paneVisual = document.getElementById('wpPaneVisual');
        const paneFindings = document.getElementById('wpPaneFindings');

        if (!btnTerminal || !paneTerminal) return;

        const inactiveClass = 'px-3.5 py-2 text-xs font-semibold rounded-t-xl transition-all border-b-2 border-transparent text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] flex items-center space-x-1.5 flex-shrink-0';
        const activeClass = 'px-3.5 py-2 text-xs font-semibold rounded-t-xl transition-all border-b-2 border-[#173d34] text-[#173d34] bg-[#ffffff] flex items-center space-x-1.5 flex-shrink-0 shadow-sm';

        [btnTerminal, btnVisual, btnFindings].forEach(b => {
            if (b) b.className = inactiveClass;
        });
        [paneTerminal, paneVisual, paneFindings].forEach(p => {
            if (p) p.classList.add('hidden');
        });

        if (tabName === 'terminal') {
            btnTerminal.className = activeClass;
            paneTerminal.classList.remove('hidden');
        } else if (tabName === 'visual') {
            btnVisual.className = activeClass;
            paneVisual.classList.remove('hidden');
        } else if (tabName === 'findings') {
            btnFindings.className = activeClass;
            paneFindings.classList.remove('hidden');
        }

        if (window.lucide) lucide.createIcons();
    }

    function renderWorkPreviewModalData(p) {
        currentWorkPreview = p;

        // Populate Header
        const avatarImg = document.getElementById('workPreviewAvatar');
        const nameEl = document.getElementById('workPreviewExecutiveName');
        const titleEl = document.getElementById('workPreviewExecutiveTitle');
        const statusBadge = document.getElementById('workPreviewStatusBadge');

        const execAvatar = (p.executive && p.executive.avatar_url) ? p.executive.avatar_url : (p.executive_avatar || 'assets/avatars/default.svg');
        const execName = p.executive_name || (p.executive && p.executive.name) || 'Executive Specialist';
        const execTitle = (p.executive && p.executive.title) || 'Team Lead';

        if (avatarImg) avatarImg.src = execAvatar;
        if (nameEl) nameEl.textContent = execName;
        if (titleEl) titleEl.textContent = execTitle;

        const isCompleted = p.status === 'completed' || (p.progress_percent >= 100);

        if (statusBadge) {
            if (isCompleted) {
                statusBadge.className = 'text-[9px] sm:text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-800 border border-emerald-500/30 flex items-center space-x-1';
                statusBadge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span><span>COMPLETED</span>`;
            } else {
                statusBadge.className = 'text-[9px] sm:text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-[#b7843f]/15 text-[#9a6d32] border border-[#b7843f]/30 flex items-center space-x-1';
                statusBadge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-[#b7843f] animate-ping"></span><span>LIVE STREAM</span>`;
            }
        }

        // Populate Progress Strip
        const taskTitleEl = document.getElementById('workPreviewTaskTitle');
        const targetSysEl = document.getElementById('workPreviewTargetSystem');
        const progressText = document.getElementById('workPreviewProgressText');
        const progressBar = document.getElementById('workPreviewProgressBar');
        const liveStatusText = document.getElementById('workPreviewLiveStatusText');

        if (taskTitleEl) taskTitleEl.textContent = p.task_title || 'Platform Task Execution';
        if (targetSysEl) targetSysEl.textContent = p.target_system || 'Workspace Environment';
        const pct = Math.min(100, Math.max(0, p.progress_percent || 0));
        if (progressText) progressText.textContent = `${pct}%`;
        if (progressBar) progressBar.style.width = `${pct}%`;

        if (liveStatusText) {
            liveStatusText.textContent = isCompleted
                ? 'All milestones reached • Deliverable report verified and ready'
                : 'Live member execution active • Real-time terminal output stream';
        }

        // Populate Checklist
        const checklistContainer = document.getElementById('workPreviewChecklist');
        if (checklistContainer && Array.isArray(p.checklist)) {
            let foundCurrent = false;
            checklistContainer.innerHTML = p.checklist.map((item) => {
                let isCurrent = false;
                if (!item.done && !foundCurrent) {
                    isCurrent = true;
                    foundCurrent = true;
                }

                if (item.done) {
                    return `
                        <div class="flex items-center space-x-2 text-xs text-[#173d34] font-medium p-2 rounded-xl bg-[#173d34]/5 border border-[#173d34]/15">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-[#173d34] flex-shrink-0"></i>
                            <span class="truncate flex-1 font-semibold">${escapeHtml(item.text)}</span>
                            <span class="text-[9px] uppercase font-mono font-bold text-[#173d34] px-1.5 py-0.5 rounded bg-[#173d34]/15">Done</span>
                        </div>
                    `;
                } else if (isCurrent) {
                    return `
                        <div class="flex items-center space-x-2 text-xs text-[#9a6d32] font-bold p-2 rounded-xl bg-[#b7843f]/10 border border-[#b7843f]/30 shadow-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#b7843f] animate-ping flex-shrink-0 ml-0.5"></span>
                            <span class="truncate flex-1">${escapeHtml(item.text)}</span>
                            <span class="text-[9px] uppercase font-mono font-bold text-[#9a6d32] px-2 py-0.5 rounded-full bg-[#b7843f]/20">Active Step</span>
                        </div>
                    `;
                } else {
                    return `
                        <div class="flex items-center space-x-2 text-xs text-[#68716d] p-2 rounded-xl bg-[#ffffff] border border-[#ddd8cc]/60">
                            <i data-lucide="circle-dashed" class="w-4 h-4 text-[#68716d]/50 flex-shrink-0"></i>
                            <span class="truncate flex-1">${escapeHtml(item.text)}</span>
                            <span class="text-[9px] uppercase font-mono text-[#68716d] px-1.5 py-0.5 rounded bg-[#ede8dc]">Queued</span>
                        </div>
                    `;
                }
            }).join('');
        }

        // Populate Terminal Logs
        const logsContainer = document.getElementById('workPreviewTerminalLogs');
        if (logsContainer && Array.isArray(p.terminal_logs)) {
            logsContainer.innerHTML = p.terminal_logs.map(log => {
                let color = 'text-[#e0ecdf]';
                if (log.type === 'success') color = 'text-emerald-400 font-semibold';
                if (log.type === 'warning') color = 'text-amber-300 font-semibold';
                if (log.type === 'info') color = 'text-cyan-300';
                if (log.type === 'error') color = 'text-rose-400 font-semibold';
                return `
                    <div class="flex items-start space-x-2 font-mono text-[11px] leading-relaxed">
                        <span class="text-[#799a87] select-none flex-shrink-0">[${escapeHtml(log.time || '')}]</span>
                        <span class="${color} break-all">${escapeHtml(log.text || '')}</span>
                    </div>
                `;
            }).join('');
            logsContainer.scrollTop = logsContainer.scrollHeight;
        }

        // Populate Visual Tab
        const urlDisplay = document.getElementById('workPreviewUrlDisplay');
        if (urlDisplay) {
            urlDisplay.textContent = p.preview_url || `https://${encodeURIComponent((p.target_system || 'workspace').replace(/\s+/g, '-').toLowerCase())}.internal/system-check`;
        }

        const visualContainer = document.getElementById('workPreviewVisualContainer');
        if (visualContainer) {
            let vd = p.visual_data || {};
            if (typeof vd === 'string') {
                try { vd = JSON.parse(vd); } catch (e) { vd = {}; }
            }
            if (!vd || Object.keys(vd).length === 0) {
                if (p.visual_data_json) {
                    try { vd = JSON.parse(p.visual_data_json); } catch (e) { vd = {}; }
                }
            }

            const badgesHtml = (vd.badges && Array.isArray(vd.badges)) ? vd.badges.map(b => `
                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-md bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20">${escapeHtml(b)}</span>
            `).join(' ') : '';

            const metricsHtml = (vd.metrics && Array.isArray(vd.metrics)) ? `
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 my-3">
                    ${vd.metrics.map(m => `
                        <div class="p-3 bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl shadow-xs">
                            <span class="text-[10px] text-[#68716d] block font-semibold truncate">${escapeHtml(m.label || '')}</span>
                            <span class="text-sm font-bold font-mono text-[#173d34] block mt-1">${escapeHtml(m.value || '')}</span>
                        </div>
                    `).join('')}
                </div>
            ` : '';

            const codeHtml = vd.code_snippet ? `
                <div class="mt-3 rounded-xl bg-[#173d34] border border-[#173d34]/30 overflow-hidden shadow-sm">
                    <div class="px-3.5 py-2 bg-[#12372f] border-b border-white/10 flex items-center justify-between text-[11px] text-[#c3d1b9]">
                        <span class="font-mono text-[10px] text-[#f6f5ea] truncate">${escapeHtml(vd.code_title || 'Live System Configuration')}</span>
                        <span class="text-[9px] uppercase font-mono px-2 py-0.5 rounded bg-white/10 text-white font-bold">Active Artifact</span>
                    </div>
                    <pre class="p-3 font-mono text-[11px] text-[#e0ecdf] overflow-x-auto leading-relaxed max-h-56"><code>${escapeHtml(vd.code_snippet)}</code></pre>
                </div>
            ` : '';

            visualContainer.innerHTML = `
                <div class="p-4 sm:p-5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] space-y-3 shadow-sm">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-xl bg-[#173d34]/10 text-[#173d34] flex items-center justify-center font-bold">
                                <i data-lucide="layout" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-[#202725]">${escapeHtml(p.task_title || 'System Execution')}</h4>
                                <p class="text-[10px] text-[#68716d] font-mono">Target: ${escapeHtml(p.target_system || 'Platform')}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-1.5 flex-wrap">
                            ${badgesHtml}
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-800 border border-emerald-500/20 font-bold">LIVE STAGING</span>
                        </div>
                    </div>

                    ${metricsHtml}
                    ${codeHtml}

                    <div class="p-3 bg-[#f5f2e9] rounded-xl border border-[#ddd8cc] text-xs text-[#202725] space-y-1.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-[#68716d]">Lead Specialist:</span>
                            <span class="font-bold text-[#173d34]">${escapeHtml(execName)} (${escapeHtml(execTitle)})</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-[#68716d]">Current State:</span>
                            <span class="font-mono font-bold ${isCompleted ? 'text-[#173d34]' : 'text-[#9a6d32]'}">
                                ${isCompleted ? 'Verification Passed & Delivered' : 'Active Execution Stream'} (${pct}%)
                            </span>
                        </div>
                    </div>
                </div>
            `;
        }

        // Populate Findings Tab
        const findingsContainer = document.getElementById('workPreviewFindingsContainer');
        if (findingsContainer) {
            if (p.findings_markdown) {
                const parsedFindings = typeof marked !== 'undefined'
                    ? (typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(marked.parse(p.findings_markdown)) : marked.parse(p.findings_markdown))
                    : escapeHtml(p.findings_markdown);
                findingsContainer.innerHTML = `
                    <div class="p-5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] prose-custom text-xs text-[#202725] shadow-sm leading-relaxed">
                        ${parsedFindings}
                    </div>
                `;
            } else {
                findingsContainer.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <div class="p-4 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm">
                            <div class="flex items-center space-x-2 text-xs font-bold text-[#173d34] mb-2">
                                <div class="w-6 h-6 rounded-lg bg-[#173d34]/10 text-[#173d34] flex items-center justify-center">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                </div>
                                <span>Task Scope & Safety</span>
                            </div>
                            <p class="text-[11px] text-[#68716d] leading-relaxed">
                                All operations executed under sandbox simulation. Production stability and database integrity remained fully protected.
                            </p>
                        </div>
                        <div class="p-4 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] shadow-sm">
                            <div class="flex items-center space-x-2 text-xs font-bold text-[#173d34] mb-2">
                                <div class="w-6 h-6 rounded-lg bg-[#b7843f]/20 text-[#9a6d32] flex items-center justify-center">
                                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                                </div>
                                <span>Executive Audit Verdict</span>
                            </div>
                            <p class="text-[11px] text-[#68716d] leading-relaxed">
                                <strong>Ready for Delivery:</strong> Deliverables for <em>${escapeHtml(p.task_title || 'the current sprint')}</em> have passed verification gates.
                            </p>
                        </div>
                    </div>
                `;
            }
        }

        // Action Buttons Setup
        const simulateBtn = document.getElementById('simulateStepWorkBtn');
        if (simulateBtn) {
            if (isCompleted) {
                simulateBtn.disabled = true;
                simulateBtn.className = 'px-3.5 py-1.5 bg-[#ede8dc] text-[#68716d] rounded-full text-xs font-semibold cursor-not-allowed opacity-60';
                simulateBtn.innerHTML = `<i data-lucide="check" class="w-3 h-3 text-emerald-600"></i><span>All Steps Completed</span>`;
            } else {
                simulateBtn.disabled = false;
                simulateBtn.className = 'px-3.5 py-1.5 bg-[#ffffff] hover:bg-[#ede8dc] text-[#173d34] border border-[#ddd8cc] rounded-full text-xs font-bold transition-all shadow-sm flex items-center space-x-1.5 active:scale-95';
                simulateBtn.innerHTML = `<i data-lucide="play" class="w-3 h-3 fill-current text-[#b7843f]"></i><span>Simulate Next Step</span>`;
            }
        }

        if (deliverWorkReportBtn) {
            if (isCompleted) {
                deliverWorkReportBtn.innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5"></i><span>Report Already Delivered</span>`;
                deliverWorkReportBtn.disabled = true;
                deliverWorkReportBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                deliverWorkReportBtn.innerHTML = `<i data-lucide="zap" class="w-3.5 h-3.5"></i><span>Deliver Final Report</span>`;
                deliverWorkReportBtn.disabled = false;
                deliverWorkReportBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        if (window.lucide) lucide.createIcons();
    }

    async function openWorkPreviewModal(previewId = null, taskId = null, executiveId = null) {
        if (!memberWorkPreviewModal) return;
        try {
            let url = 'api/work_preview.php';
            if (previewId) {
                url += `?id=${encodeURIComponent(previewId)}`;
            } else if (taskId) {
                url += `?task_id=${encodeURIComponent(taskId)}`;
            } else if (executiveId) {
                url += `?executive_id=${encodeURIComponent(executiveId)}`;
            }

            const res = await fetch(url);
            const data = await res.json();
            if (!data.preview) {
                showToastNotification('⚠️ Preview data could not be loaded.');
                return;
            }

            renderWorkPreviewModalData(data.preview);

            // Switch to Terminal by default
            switchWorkPreviewTab('terminal');

            // Open Modal
            memberWorkPreviewModal.classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        } catch (err) {
            console.error('Error opening work preview:', err);
            showToastNotification('⚠️ Network error loading work preview.');
        }
    }

    async function simulateWorkPreviewStep() {
        if (!currentWorkPreview) return;
        const btn = document.getElementById('simulateStepWorkBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<span class="inline-block animate-spin mr-1">⚡</span> Running Step...`;
        }

        try {
            const res = await fetch('api/work_preview.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'simulate_step',
                    preview_id: currentWorkPreview.id
                })
            });
            const data = await res.json();
            if (data.success && data.preview) {
                renderWorkPreviewModalData(data.preview);
                showToastNotification(`⚡ Step simulated: ${data.preview.progress_percent}% completed!`);
                if (typeof loadNotes === 'function') loadNotes();
            } else {
                showToastNotification(data.message || '⚠️ Step simulation reached completion.');
            }
        } catch (err) {
            console.error('Error simulating step:', err);
            showToastNotification('⚠️ Network error simulating step.');
        } finally {
            if (btn && currentWorkPreview && currentWorkPreview.status !== 'completed' && currentWorkPreview.progress_percent < 100) {
                btn.disabled = false;
                btn.innerHTML = `<i data-lucide="play" class="w-3 h-3 fill-current text-[#b7843f]"></i><span>Simulate Next Step</span>`;
                if (window.lucide) lucide.createIcons();
            }
        }
    }

    function chatWithExecutiveAboutCurrentTask() {
        if (!currentWorkPreview) return;
        const execName = currentWorkPreview.executive_name || 'Executive';
        const taskTitle = currentWorkPreview.task_title || 'task';
        closeWorkPreviewModal();

        if (typeof switchToChatView === 'function') {
            switchToChatView();
        }
        const chatInput = document.getElementById('chatInput');
        if (chatInput) {
            chatInput.value = `@${execName} Please give me a live briefing on your current work on "${taskTitle}". What is the status, blockers, and next milestone?`;
            chatInput.focus();
            if (typeof autoResizeInput === 'function') autoResizeInput();
        }
    }

    function openTaskLiveWorkPreview(taskId) {
        openWorkPreviewModal(null, taskId, null);
    }

    function openExecutiveLiveWork(executiveId) {
        openWorkPreviewModal(null, null, executiveId);
    }

    function openProjectLiveSprintPreview(projectName) {
        const pTasks = (state.notes || []).filter(t => (t.project_name || 'General').toLowerCase() === (projectName || '').toLowerCase());
        if (pTasks.length > 0) {
            const activeTask = pTasks.find(t => t.status === 'in_progress') || pTasks[0];
            openWorkPreviewModal(null, activeTask.id, null);
        } else {
            openWorkPreviewModal();
        }
    }

    async function deliverWorkReportNow() {
        if (!currentWorkPreview) return;
        const btn = document.getElementById('deliverWorkReportBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<span class="inline-block animate-spin mr-1">⏳</span> Delivering Report...`;
        }

        try {
            const res = await fetch('api/work_preview.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'deliver_report',
                    preview_id: currentWorkPreview.id
                })
            });
            const data = await res.json();
            if (data.success) {
                closeWorkPreviewModal();
                const leadName = (currentWorkPreview && currentWorkPreview.executive_name) ? currentWorkPreview.executive_name : 'Executive';
                showToastNotification(`✅ ${leadName} has delivered the architectural report!`);

                // Reload notes so task card moves to Done on the Kanban board
                if (typeof loadNotes === 'function') await loadNotes();

                // Reload messages for the active session to display the new report
                if (state.currentSessionId) {
                    await selectSession(state.currentSessionId);
                } else if (currentWorkPreview.session_id) {
                    await selectSession(currentWorkPreview.session_id);
                }
            } else {
                showToastNotification(`⚠️ Could not deliver report: ${data.error || 'Unknown error'}`);
                if (btn) btn.disabled = false;
            }
        } catch (err) {
            console.error('Error delivering report:', err);
            showToastNotification('⚠️ Network error delivering report.');
            if (btn) btn.disabled = false;
        }
    }

    async function openDiscussionsListModal() {
        closeMobileSidebar();
        if (!discussionsListModal) return;
        await loadDiscussions();
        if (discussionsArchiveList) {
            discussionsArchiveList.innerHTML = '';
            if (state.discussions.length === 0) {
                discussionsArchiveList.innerHTML = `
                    <div class="text-center py-8 text-[#68716d] text-xs">
                        <i data-lucide="message-circle-code" class="w-8 h-8 mx-auto mb-2 opacity-30 text-[#b7843f]"></i>
                        <p class="font-bold text-[#202725]">No Team Deliberations Yet</p>
                        <p class="mt-1 text-[#68716d]">Give RuXt a strategic task in Chief of Staff chat to trigger team deliberations!</p>
                    </div>
                `;
            } else {
                state.discussions.forEach(disc => {
                    const item = document.createElement('div');
                    item.className = 'p-3.5 rounded-2xl bg-[#ffffff] border border-[#ddd8cc] hover:border-[#173d34] transition-all cursor-pointer space-y-2 shadow-sm';
                    item.onclick = () => {
                        closeDiscussionsListModal();
                        openDiscussionModal(disc.id);
                    };
                    item.innerHTML = `
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-[#202725] hover:text-[#173d34] transition-colors">${escapeHtml(disc.title)}</h4>
                            <span class="text-[10px] text-[#68716d] font-mono">${escapeHtml(disc.created_at)}</span>
                        </div>
                        <p class="text-[11px] text-[#68716d] line-clamp-2 italic">CEO: "${escapeHtml(disc.user_prompt)}"</p>
                        <div class="flex items-center space-x-3 text-[10px] text-[#68716d] font-mono pt-1">
                            <span class="flex items-center space-x-1">
                                <i data-lucide="messages-square" class="w-3 h-3 text-[#b7843f]"></i>
                                <span>${disc.dialogue_count || 0} Deliberation Turns</span>
                            </span>
                            <span class="flex items-center space-x-1">
                                <i data-lucide="check-square" class="w-3 h-3 text-[#173d34]"></i>
                                <span>${disc.task_count || 0} Action Items</span>
                            </span>
                        </div>
                    `;
                    discussionsArchiveList.appendChild(item);
                });
            }
        }
        discussionsListModal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeDiscussionsListModal() {
        if (discussionsListModal) discussionsListModal.classList.add('hidden');
    }

    async function addTask() {
        const text = newTaskInput.value.trim();
        if (!text) return;
        try {
            const res = await fetch('api/notes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: text, venture_id: state.currentVentureId })
            });
            const data = await res.json();
            if (data.success) {
                newTaskInput.value = '';
                loadNotes();
            }
        } catch (err) {
            console.error('Failed to add task:', err);
        }
    }

    async function toggleTask(id, isDone) {
        try {
            await fetch('api/notes.php', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, is_done: isDone })
            });
            loadNotes();
        } catch (err) {
            console.error('Failed to toggle task:', err);
        }
    }

    async function deleteTask(id) {
        try {
            await fetch(`api/notes.php?id=${id}`, { method: 'DELETE' });
            loadNotes();
        } catch (err) {
            console.error('Failed to delete task:', err);
        }
    }

    // 9. Corporate Settings
    async function loadSettings() {
        try {
            const res = await fetch('api/settings.php');
            const data = await res.json();
            if (data.settings) {
                state.settings = data.settings;
                modelSelect.value = state.settings.model || 'gemini-3-flash-preview';
                userNameInput.value = state.settings.user_name || 'CEO';
                companyNameInput.value = state.settings.company_name || 'Enterprises';

                if (state.settings.current_user) {
                    state.user = state.settings.current_user;
                    if (state.user.specialized_role && headerActiveSubtitle) {
                        headerActiveSubtitle.textContent = state.user.specialized_role;
                    }
                    const fEl = document.getElementById('profileUserJobField');
                    if (fEl && state.user.job_field) fEl.textContent = state.user.job_field;
                    const rEl = document.getElementById('profileRuxtSpecializedRole');
                    if (rEl && state.user.specialized_role) rEl.textContent = state.user.specialized_role;
                }

                // Voice & Audio Configuration
                state.ttsProvider = state.settings.tts_provider || 'browser';
                state.speechLang = state.settings.voice_input_lang || 'en-US';
                state.autoSpeak = !!state.settings.voice_auto_speak;

                if (ttsProviderSelect) ttsProviderSelect.value = state.ttsProvider;
                if (voiceLangSelect) voiceLangSelect.value = state.speechLang;
                if (autoSpeakCheckbox) autoSpeakCheckbox.checked = state.autoSpeak;
                if (micLangText) micLangText.textContent = 'EN';
                if (callMicLangText) callMicLangText.textContent = 'EN';

                if (soundToggleBtn && soundToggleIcon) {
                    if (state.autoSpeak) {
                        soundToggleIcon.setAttribute('data-lucide', 'volume-2');
                        soundToggleBtn.classList.add('text-amber-400');
                        soundToggleBtn.title = 'Voice Output: ON';
                    } else {
                        soundToggleIcon.setAttribute('data-lucide', 'volume-x');
                        soundToggleBtn.classList.remove('text-amber-400');
                        soundToggleBtn.title = 'Voice Output: OFF';
                    }
                }

                if (state.settings.has_elevenlabs_key && elevenlabsKeyInput) {
                    elevenlabsKeyInput.placeholder = `Configured (${state.settings.masked_elevenlabs_key})`;
                }
                if (state.settings.has_openai_key && openaiKeyInput) {
                    openaiKeyInput.placeholder = `Configured (${state.settings.masked_openai_key})`;
                }

                if (sidebarCompanyName) {
                    sidebarCompanyName.textContent = state.settings.company_name || 'Enterprises';
                }
                if (state.settings.has_api_key) {
                    apiKeyInput.placeholder = `Configured (${state.settings.masked_api_key})`;
                }
                const modelBadge = document.getElementById('currentModelBadge');
                if (modelBadge) {
                    modelBadge.textContent = state.settings.model || 'gemini-3-flash-preview';
                }
                if (data.settings.memories_count !== undefined) {
                    const settingsMemBadge = document.getElementById('settingsMemoriesCountBadge');
                    if (settingsMemBadge) {
                        settingsMemBadge.textContent = `${data.settings.memories_count} ${data.settings.memories_count === 1 ? 'memory' : 'memories'}`;
                    }
                }
                if (data.settings.token_usage) {
                    renderTokenUsage(data.settings.token_usage);
                }
            }
        } catch (err) {
            console.error('Failed to load settings:', err);
        }
    }

    function formatCompactTokens(num) {
        num = Number(num) || 0;
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
        return num.toString();
    }

    function renderTokenUsage(usage) {
        if (!usage) return;
        state.tokenUsage = usage;

        const totalEl = document.getElementById('tokenTotalCount');
        const promptEl = document.getElementById('tokenPromptCount');
        const compEl = document.getElementById('tokenCompletionCount');
        const reqEl = document.getElementById('tokenRequestsCount');
        const todayBadge = document.getElementById('settingsTokenTodayBadge');
        const headerBadge = document.getElementById('headerTokenBadge');
        const headerText = document.getElementById('headerTokenCountText');
        const sidebarBadge = document.getElementById('sidebarTokenBadge');
        const modelsContainer = document.getElementById('tokenModelsContainer');
        const lastModelText = document.getElementById('tokenLastModelText');
        const lastCountText = document.getElementById('tokenLastCountText');

        const totalNum = usage.total_tokens || 0;
        if (totalEl) totalEl.textContent = totalNum.toLocaleString();
        if (promptEl) promptEl.textContent = (usage.prompt_tokens || 0).toLocaleString();
        if (compEl) compEl.textContent = (usage.completion_tokens || 0).toLocaleString();
        if (reqEl) reqEl.textContent = (usage.total_requests || 0).toLocaleString();
        if (todayBadge) todayBadge.textContent = `Today: ${formatCompactTokens(usage.today_tokens)} T`;

        // Update header & sidebar badges
        if (headerText) headerText.textContent = `${formatCompactTokens(totalNum)} Tokens`;
        if (headerBadge) {
            headerBadge.title = `Total Tokens: ${totalNum.toLocaleString()} | Today: ${(usage.today_tokens || 0).toLocaleString()} (Click for analytics in Settings)`;
            headerBadge.classList.remove('hidden');
        }
        if (sidebarBadge) {
            sidebarBadge.textContent = `${formatCompactTokens(totalNum)} T`;
            sidebarBadge.title = `Total Tokens: ${totalNum.toLocaleString()}`;
        }

        // Render model breakdown
        if (modelsContainer) {
            if (Array.isArray(usage.models) && usage.models.length > 0) {
                modelsContainer.innerHTML = usage.models.map(m => {
                    const pct = m.percentage || 0;
                    return `
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="font-mono font-bold text-[#202725] truncate max-w-[190px]">${escapeHtml(m.model)}</span>
                                <span class="font-mono text-[#68716d] flex items-center space-x-1.5">
                                    <span class="font-bold text-[#173d34]">${(m.total_tokens || 0).toLocaleString()}</span>
                                    <span>(${pct}%)</span>
                                </span>
                            </div>
                            <div class="w-full h-1.5 bg-[#ddd8cc] rounded-full overflow-hidden">
                                <div class="h-full bg-[#173d34] rounded-full transition-all duration-500" style="width: ${Math.min(pct, 100)}%"></div>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                modelsContainer.innerHTML = `<div class="text-[10px] text-[#68716d] italic">No model usage recorded yet.</div>`;
            }
        }

        // Render last call information
        if (lastModelText && lastCountText) {
            if (usage.last_request) {
                const lr = usage.last_request;
                const thoughtInfo = lr.thoughts_tokens > 0 ? ` • think: ${Number(lr.thoughts_tokens).toLocaleString()}` : '';
                lastModelText.textContent = `${lr.model} (${lr.feature || 'chat'})`;
                lastCountText.textContent = `${Number(lr.total_tokens).toLocaleString()} tokens (in: ${Number(lr.prompt_tokens).toLocaleString()} • out: ${Number(lr.completion_tokens).toLocaleString()}${thoughtInfo})`;
            } else {
                lastModelText.textContent = 'None';
                lastCountText.textContent = '-';
            }
        }

        if (window.lucide) lucide.createIcons();
    }

    async function resetTokenStats() {
        if (!confirm('Are you sure you want to reset all token usage counters? This will clear all recorded token analytics back to zero.')) {
            return;
        }
        try {
            const res = await fetch('api/settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reset_token_usage: 1 })
            });
            const data = await res.json();
            if (data.token_usage) {
                renderTokenUsage(data.token_usage);
            } else {
                await loadSettings();
            }
            showToastNotification('AI Token counters have been reset successfully!', 'success');
        } catch (err) {
            console.error('Failed to reset token usage:', err);
        }
    }

    async function refreshTokenStats() {
        const btn = document.getElementById('refreshTokenStatsBtn');
        if (btn) btn.classList.add('animate-spin');
        try {
            await loadSettings();
        } finally {
            if (btn) setTimeout(() => btn.classList.remove('animate-spin'), 500);
        }
    }

    async function saveSettings(e) {
        e.preventDefault();
        const payload = {
            model: modelSelect.value,
            user_name: userNameInput.value.trim(),
            company_name: companyNameInput.value.trim(),
            tts_provider: ttsProviderSelect ? ttsProviderSelect.value : state.ttsProvider,
            voice_input_lang: voiceLangSelect ? voiceLangSelect.value : state.speechLang,
            voice_auto_speak: autoSpeakCheckbox ? (autoSpeakCheckbox.checked ? 1 : 0) : (state.autoSpeak ? 1 : 0)
        };
        if (apiKeyInput.value.trim()) {
            payload.gemini_api_key = apiKeyInput.value.trim();
        }
        if (elevenlabsKeyInput && elevenlabsKeyInput.value.trim()) {
            payload.elevenlabs_api_key = elevenlabsKeyInput.value.trim();
        }
        if (openaiKeyInput && openaiKeyInput.value.trim()) {
            payload.openai_api_key = openaiKeyInput.value.trim();
        }

        try {
            const res = await fetch('api/settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                apiKeyInput.value = '';
                if (elevenlabsKeyInput) elevenlabsKeyInput.value = '';
                if (openaiKeyInput) openaiKeyInput.value = '';
                closeSettings();
                await loadSettings();
                showToastNotification('HQ Corporate Configuration saved successfully!', 'success');
            }
        } catch (err) {
            console.error('Failed to save settings:', err);
        }
    }

    // Modals
    function openCallMeetingModal() { closeMobileSidebar(); callMeetingModal.classList.remove('hidden'); }
    function closeCallMeetingModal() { callMeetingModal.classList.add('hidden'); }
    function openHireModal() { closeMobileSidebar(); hireModal.classList.remove('hidden'); }
    function closeHireModal() { hireModal.classList.add('hidden'); }
    function openMemories() { closeMobileSidebar(); closeProfileModal(); memoriesModal.classList.remove('hidden'); loadMemories(); }
    function closeMemories() { memoriesModal.classList.add('hidden'); }
    function openTasks() { closeMobileSidebar(); closeProfileModal(); tasksModal.classList.remove('hidden'); loadNotes(); }
    function closeTasks() { tasksModal.classList.add('hidden'); }
    function openSettings() { closeMobileSidebar(); closeProfileModal(); settingsModal.classList.remove('hidden'); loadMemories(); loadSettings(); }
    function closeSettings() { settingsModal.classList.add('hidden'); }

    function openProfileModal() {
        closeMobileSidebar();
        const profileModal = document.getElementById('profileModal');
        if (profileModal) {
            profileModal.classList.remove('hidden');
            if (typeof loadMemories === 'function') loadMemories();
            if (typeof loadSettings === 'function') loadSettings();
            if (typeof loadNotes === 'function') loadNotes();

            // Default to tools tab
            if (typeof switchProfileTab === 'function') switchProfileTab('tools');

            // Update live metrics on Profile summary ribbon
            const memSum = document.getElementById('profileMemoriesSummary');
            if (memSum && state && state.memories) {
                memSum.textContent = `${state.memories.length} Facts`;
            }
            const taskSum = document.getElementById('profileTasksSummary');
            if (taskSum && state && state.notes) {
                const openTasks = state.notes.filter(n => !n.is_done && n.status !== 'completed').length;
                taskSum.textContent = `${openTasks} Open`;
            }
            const tokenSum = document.getElementById('profileTokensSummary');
            const tokenBadge = document.getElementById('headerTokenCountText');
            if (tokenSum && tokenBadge) {
                tokenSum.textContent = tokenBadge.textContent || '0 Tokens';
            }

            if (window.lucide) window.lucide.createIcons();
        }
    }
    function closeProfileModal() {
        const profileModal = document.getElementById('profileModal');
        if (profileModal) profileModal.classList.add('hidden');
    }
    window.openProfileModal = openProfileModal;
    window.closeProfileModal = closeProfileModal;

    function switchProfileTab(tab) {
        const paneTools = document.getElementById('profileTabPaneTools');
        const paneSecurity = document.getElementById('profileTabPaneSecurity');
        const tabToolsBtn = document.getElementById('profileTabBtnTools');
        const tabSecurityBtn = document.getElementById('profileTabBtnSecurity');

        if (tab === 'tools') {
            if (paneTools) paneTools.classList.remove('hidden');
            if (paneSecurity) paneSecurity.classList.add('hidden');
            if (tabToolsBtn) {
                tabToolsBtn.className = 'flex-1 py-1.5 px-3 text-xs font-bold rounded-xl transition-all shadow-sm bg-[#ffffff] text-[#173d34] border border-[#ddd8cc] flex items-center justify-center space-x-1.5 cursor-pointer';
            }
            if (tabSecurityBtn) {
                tabSecurityBtn.className = 'flex-1 py-1.5 px-3 text-xs font-semibold rounded-xl transition-all text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] border border-transparent flex items-center justify-center space-x-1.5 cursor-pointer';
            }
        } else {
            if (paneTools) paneTools.classList.add('hidden');
            if (paneSecurity) paneSecurity.classList.remove('hidden');
            if (tabToolsBtn) {
                tabToolsBtn.className = 'flex-1 py-1.5 px-3 text-xs font-semibold rounded-xl transition-all text-[#68716d] hover:text-[#173d34] hover:bg-[#ede8dc] border border-transparent flex items-center justify-center space-x-1.5 cursor-pointer';
            }
            if (tabSecurityBtn) {
                tabSecurityBtn.className = 'flex-1 py-1.5 px-3 text-xs font-bold rounded-xl transition-all shadow-sm bg-[#ffffff] text-[#173d34] border border-[#ddd8cc] flex items-center justify-center space-x-1.5 cursor-pointer';
            }
        }
        if (window.lucide) window.lucide.createIcons();
    }
    window.switchProfileTab = switchProfileTab;

    function toggleEditProfileSection() {
        const form = document.getElementById('editProfileForm');
        const chevron = document.getElementById('editProfileChevron');
        if (!form) return;
        form.classList.toggle('hidden');
        if (chevron) chevron.classList.toggle('rotate-180');
    }
    window.toggleEditProfileSection = toggleEditProfileSection;

    async function handleUpdateProfile(e) {
        e.preventDefault();
        const alertBox = document.getElementById('profileAlertBox');
        const nameInput = document.getElementById('editProfileNameInput');
        const emailInput = document.getElementById('editProfileEmailInput');
        const newPassInput = document.getElementById('editProfileNewPasswordInput');
        const curPassInput = document.getElementById('editProfileCurrentPasswordInput');
        const saveBtn = document.getElementById('saveProfileBtn');

        if (!nameInput || !emailInput) return;

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        try {
            const res = await fetch('api/auth.php?action=update_profile', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: nameInput.value.trim(),
                    email: emailInput.value.trim(),
                    new_password: newPassInput ? newPassInput.value : '',
                    current_password: curPassInput ? curPassInput.value : ''
                })
            });

            const data = await res.json();
            if (res.ok && data.success) {
                alertBox.className = 'p-2.5 rounded-xl text-xs bg-emerald-50 border border-emerald-200 text-emerald-800';
                alertBox.textContent = data.message || 'Profile updated successfully!';
                alertBox.classList.remove('hidden');

                const dispName = document.getElementById('profileDisplayName');
                const dispEmail = document.getElementById('profileDisplayEmail');
                const avatarLetter = document.getElementById('profileAvatarLetter');
                if (dispName) dispName.textContent = data.user.name;
                if (dispEmail) dispEmail.textContent = data.user.email;
                if (avatarLetter) avatarLetter.textContent = data.user.name.charAt(0).toUpperCase();

                if (newPassInput) newPassInput.value = '';
                if (curPassInput) curPassInput.value = '';

                setTimeout(() => { alertBox.classList.add('hidden'); }, 3500);
            } else {
                alertBox.className = 'p-2.5 rounded-xl text-xs bg-rose-50 border border-rose-200 text-rose-800';
                alertBox.textContent = data.error || 'Failed to update profile.';
                alertBox.classList.remove('hidden');
            }
        } catch (err) {
            alertBox.className = 'p-2.5 rounded-xl text-xs bg-rose-50 border border-rose-200 text-rose-800';
            alertBox.textContent = 'Network or server error.';
            alertBox.classList.remove('hidden');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Changes';
        }
    }
    window.handleUpdateProfile = handleUpdateProfile;

    // ============================================
    // Business Ventures & Multi-Team Orchestration
    // ============================================
    async function loadVentures() {
        try {
            const res = await fetch('api/ventures.php');
            const data = await res.json();
            if (data.ventures && data.ventures.length > 0) {
                state.ventures = data.ventures;
                const exists = state.ventures.some(v => v.id == state.currentVentureId);
                if (!exists) {
                    state.currentVentureId = state.ventures[0].id;
                }
                updateActiveVentureUI();
                renderVentureDropdown();
                populateHireVentureSelect();
            }
        } catch (err) {
            console.error('Failed to load ventures:', err);
        }
    }

    function updateActiveVentureUI() {
        const v = state.ventures.find(ven => ven.id == state.currentVentureId) || state.ventures[0];
        if (!v) return;

        if (activeVentureName) activeVentureName.textContent = v.name;
        if (activeVentureDomain) activeVentureDomain.textContent = v.domain;
        if (headerVentureName) headerVentureName.textContent = v.name;
        if (ventureBadgeInSidebar) ventureBadgeInSidebar.textContent = v.name;

        const iconName = v.icon || 'briefcase';
        if (activeVentureIcon) {
            activeVentureIcon.setAttribute('data-lucide', iconName);
        }

        const colorClasses = {
            cyan: { bg: 'bg-cyan-500/20', text: 'text-cyan-400', border: 'border-cyan-500/30', dot: 'bg-cyan-400' },
            emerald: { bg: 'bg-emerald-500/20', text: 'text-emerald-400', border: 'border-emerald-500/30', dot: 'bg-emerald-400' },
            purple: { bg: 'bg-purple-500/20', text: 'text-purple-400', border: 'border-purple-500/30', dot: 'bg-purple-400' },
            amber: { bg: 'bg-amber-500/20', text: 'text-amber-400', border: 'border-amber-500/30', dot: 'bg-amber-400' },
            rose: { bg: 'bg-rose-500/20', text: 'text-rose-400', border: 'border-rose-500/30', dot: 'bg-rose-400' },
            indigo: { bg: 'bg-indigo-500/20', text: 'text-indigo-400', border: 'border-indigo-500/30', dot: 'bg-indigo-400' }
        };

        const theme = colorClasses[v.color] || colorClasses.cyan;
        if (activeVentureIconContainer) {
            activeVentureIconContainer.className = `w-7 h-7 rounded-lg ${theme.bg} ${theme.text} flex items-center justify-center flex-shrink-0 border ${theme.border}`;
        }
        if (headerVentureDot) {
            headerVentureDot.className = `w-2 h-2 rounded-full ${theme.dot}`;
        }
        if (ventureBadgeInSidebar) {
            ventureBadgeInSidebar.className = `text-[9px] px-1.5 py-0.2 rounded font-mono font-bold ${theme.bg} ${theme.text} border ${theme.border} truncate max-w-[110px]`;
        }

        if (window.lucide) lucide.createIcons();
    }

    function renderVentureDropdown() {
        if (!ventureItemsList) return;
        ventureItemsList.innerHTML = '';

        state.ventures.forEach(v => {
            const isActive = v.id == state.currentVentureId;
            const item = document.createElement('button');
            item.type = 'button';
            item.className = `w-full flex items-center justify-between p-2 rounded-xl text-left transition-all ${
                isActive 
                    ? 'bg-[#173d34]/10 border border-[#173d34]/30 text-[#173d34] font-bold' 
                    : 'text-[#202725] hover:bg-[#f5f2e9]'
            }`;

            item.innerHTML = `
                <div class="flex items-center space-x-2.5 overflow-hidden flex-1">
                    <div class="w-6 h-6 rounded-lg bg-[#173d34]/10 flex items-center justify-center flex-shrink-0 text-[#173d34]">
                        <i data-lucide="${escapeHtml(v.icon || 'briefcase')}" class="w-3.5 h-3.5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold truncate">${escapeHtml(v.name)}</div>
                        <div class="text-[10px] text-[#68716d] truncate font-mono">${escapeHtml(v.domain)}</div>
                    </div>
                </div>
                ${isActive ? '<i data-lucide="check" class="w-3.5 h-3.5 text-[#173d34] flex-shrink-0"></i>' : ''}
            `;

            item.onclick = () => switchVenture(v.id);
            ventureItemsList.appendChild(item);
        });

        if (window.lucide) lucide.createIcons();
    }

    function populateHireVentureSelect() {
        if (!hireVentureSelect) return;
        hireVentureSelect.innerHTML = '';
        state.ventures.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.id;
            opt.textContent = `${v.name} (${v.domain})`;
            if (v.id == state.currentVentureId) opt.selected = true;
            hireVentureSelect.appendChild(opt);
        });
    }

    async function switchVenture(ventureId) {
        if (state.currentVentureId == ventureId) {
            closeVentureDropdown();
            return;
        }

        state.currentVentureId = ventureId;
        closeVentureDropdown();
        updateActiveVentureUI();
        renderVentureDropdown();
        populateHireVentureSelect();

        // Reload data for this active venture
        state.taskFilterProject = '';
        if (taskProjectFilter) taskProjectFilter.value = '';
        await loadProjects();
        await loadExecutives();
        await loadSessions();
        if (state.activeView === 'boardroom') {
            await loadMeetings();
        }
        await loadDiscussions();
        loadNotes();
    }

    function toggleVentureDropdown() {
        if (!ventureDropdownMenu) return;
        ventureDropdownMenu.classList.toggle('hidden');
    }

    function closeVentureDropdown() {
        if (ventureDropdownMenu) ventureDropdownMenu.classList.add('hidden');
    }

    function openVentureModal() {
        closeVentureDropdown();
        closeMobileSidebar();
        if (ventureModal) ventureModal.classList.remove('hidden');
    }

    function closeVentureModal() {
        if (ventureModal) ventureModal.classList.add('hidden');
    }

    async function handleCreateVenture(e) {
        e.preventDefault();
        const name = document.getElementById('ventureNameInput').value.trim();
        const domain = document.getElementById('ventureDomainInput').value.trim();
        const description = document.getElementById('ventureDescInput').value.trim();
        const icon = document.getElementById('ventureIconSelect').value;
        const color = document.getElementById('ventureColorSelect').value;
        const auto_generate_team = document.getElementById('autoGenerateTeamCheck').checked;

        if (!name || !domain) return;

        const submitBtn = document.getElementById('submitVentureBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>Recruiting AI Specialists...</span>
        `;

        try {
            const res = await fetch('api/ventures.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, domain, description, icon, color, auto_generate_team })
            });

            const data = await res.json();
            if (data.success) {
                ventureForm.reset();
                closeVentureModal();
                await loadVentures();
                await switchVenture(data.venture.id);
                alert(`🚀 Business Venture "${data.venture.name}" created successfully!\n${data.team_recruited ? '4 AI Specialists recruited to your team.' : ''}`);
            } else {
                alert(data.error || 'Failed to create venture');
            }
        } catch (err) {
            console.error('Error creating venture:', err);
            alert('Error creating venture. Please check console.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                <span>Launch Venture & Recruit Team</span>
            `;
            if (window.lucide) lucide.createIcons();
        }
    }

    function openMobileSidebar() {
        sidebar.classList.remove('-translate-x-full');
        mobileOverlay.classList.remove('hidden');
    }
    function closeMobileSidebar() {
        sidebar.classList.add('-translate-x-full');
        mobileOverlay.classList.add('hidden');
    }

    function showToastNotification(msg, type = 'success', title = null) {
        if (!msg) return;

        let container = document.getElementById('ruxtToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'ruxtToastContainer';
            container.className = 'fixed top-4 right-4 sm:top-5 sm:right-5 z-[99999] flex flex-col space-y-2.5 pointer-events-none max-w-sm w-full px-3 sm:px-0';
            document.body.appendChild(container);
        }

        const isSuccess = type === 'success';
        const isError = type === 'error';
        const isWarning = type === 'warning';
        
        let iconName = 'sparkles';
        let badgeBg = 'bg-[#173d34]/10 text-[#173d34] border-[#173d34]/20';
        let toastTypeClass = 'ruxt-toast-success';

        if (isError) {
            iconName = 'alert-circle';
            badgeBg = 'bg-rose-50 text-rose-600 border-rose-200';
            toastTypeClass = 'ruxt-toast-error';
        } else if (isWarning) {
            iconName = 'alert-triangle';
            badgeBg = 'bg-amber-50 text-amber-700 border-amber-200';
            toastTypeClass = 'ruxt-toast-warning';
        } else if (type === 'info') {
            iconName = 'info';
            badgeBg = 'bg-sky-50 text-sky-700 border-sky-200';
            toastTypeClass = 'ruxt-toast-info';
        }

        const toast = document.createElement('div');
        toast.className = `ruxt-toast-item ${toastTypeClass} p-3 sm:p-3.5 rounded-2xl flex items-start space-x-3 transform translate-y-[-10px] opacity-0 transition-all duration-300 ease-out shadow-xl`;
        
        toast.innerHTML = `
            <div class="w-8 h-8 rounded-xl ${badgeBg} border flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm">
                <i data-lucide="${iconName}" class="w-4 h-4"></i>
            </div>
            <div class="flex-1 min-w-0 pr-1">
                ${title ? `<div class="ruxt-toast-title mb-0.5">${escapeHtml(title)}</div>` : ''}
                <div class="ruxt-toast-text">${escapeHtml(msg)}</div>
            </div>
            <button type="button" class="text-[#68716d] hover:text-[#202725] p-1 rounded-lg transition-colors flex-shrink-0 cursor-pointer" title="Dismiss">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        `;

        container.appendChild(toast);
        if (window.lucide) lucide.createIcons();

        // Animate entrance
        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-[-10px]', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        });

        // Close button handler
        const closeBtn = toast.querySelector('button');
        let dismissTimer = null;

        const dismiss = () => {
            if (dismissTimer) clearTimeout(dismissTimer);
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('opacity-0', 'translate-x-4');
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 300);
        };

        if (closeBtn) closeBtn.addEventListener('click', dismiss);

        // Auto dismiss after 4.5 seconds with pause-on-hover
        dismissTimer = setTimeout(dismiss, 4500);
        toast.addEventListener('mouseenter', () => { if (dismissTimer) clearTimeout(dismissTimer); });
        toast.addEventListener('mouseleave', () => { dismissTimer = setTimeout(dismiss, 2500); });
    }

    // ============================================
    // Predefined Workspaces & Monetization Flow
    // ============================================
    async function loadStoreTemplates() {
        if (workspaceStoreLoading) workspaceStoreLoading.classList.remove('hidden');
        if (workspaceStoreGrid) workspaceStoreGrid.classList.add('hidden');
        if (workspaceStoreEmpty) workspaceStoreEmpty.classList.add('hidden');

        try {
            const res = await fetch('api/workspace_store.php');
            const data = await res.json();
            if (data.templates) {
                state.storeTemplates = data.templates;
                renderStoreCards();
            }
        } catch (err) {
            console.error('Failed to load workspace store templates:', err);
            showToastNotification('Failed to load templates from server', 'error');
        } finally {
            if (workspaceStoreLoading) workspaceStoreLoading.classList.add('hidden');
        }
    }

    function openWorkspaceStore(filter = 'all') {
        closeVentureDropdown();
        closeMobileSidebar();
        state.activeStoreFilter = filter;

        // Update filter button active states
        document.querySelectorAll('.store-filter-btn').forEach(btn => {
            const isMatch = btn.dataset.filter === filter;
            if (isMatch) {
                btn.className = 'store-filter-btn px-3 py-1 rounded-full font-bold transition-all bg-[#173d34] text-[#fcfaf5] shadow-sm';
            } else {
                btn.className = 'store-filter-btn px-3 py-1 rounded-full font-semibold transition-all bg-[#ffffff] text-[#3d4942] hover:bg-[#ede8dc] border border-[#ddd8cc]';
            }
        });

        if (storeSearchInput) storeSearchInput.value = '';
        if (workspaceStoreModal) workspaceStoreModal.classList.remove('hidden');

        loadStoreTemplates();
        if (window.lucide) lucide.createIcons();
    }

    function closeWorkspaceStore() {
        if (workspaceStoreModal) workspaceStoreModal.classList.add('hidden');
    }

    function renderStoreCards() {
        if (!workspaceStoreGrid) return;
        workspaceStoreGrid.innerHTML = '';

        const filter = state.activeStoreFilter || 'all';
        const search = (storeSearchInput ? storeSearchInput.value.trim().toLowerCase() : '');

        const filtered = state.storeTemplates.filter(t => {
            // Category filter
            if (filter === 'free' && t.tier !== 'free') return false;
            if (filter === 'paid' && t.tier !== 'paid') return false;
            if (filter === 'content' && !t.domain.toLowerCase().includes('content') && !t.domain.toLowerCase().includes('media')) return false;
            if (filter === 'ecommerce' && !t.domain.toLowerCase().includes('commerce') && !t.domain.toLowerCase().includes('retail')) return false;
            if (filter === 'tech' && !t.domain.toLowerCase().includes('software') && !t.domain.toLowerCase().includes('saas')) return false;
            if (filter === 'business' && !t.domain.toLowerCase().includes('freelance') && !t.domain.toLowerCase().includes('real estate') && !t.domain.toLowerCase().includes('consulting')) return false;

            // Search filter
            if (search) {
                const combined = (t.title + ' ' + t.domain + ' ' + t.description + ' ' + (t.badge || '')).toLowerCase();
                if (!combined.includes(search)) return false;
            }
            return true;
        });

        if (filtered.length === 0) {
            workspaceStoreGrid.classList.add('hidden');
            if (workspaceStoreEmpty) workspaceStoreEmpty.classList.remove('hidden');
            return;
        }

        if (workspaceStoreEmpty) workspaceStoreEmpty.classList.add('hidden');
        workspaceStoreGrid.classList.remove('hidden');

        filtered.forEach(tpl => {
            const isFree = tpl.tier === 'free';
            const price = parseFloat(tpl.price) || 0;
            const priceLabel = isFree ? 'FREE' : `$${price.toFixed(2)} USD`;
            const badgeText = tpl.badge || (isFree ? 'STARTER' : 'PRO SUITE');
            const isDeployed = tpl.is_deployed;
            const isUnlocked = tpl.is_unlocked;

            const card = document.createElement('div');
            card.className = 'bg-[#fcfaf5] border border-[#ddd8cc] hover:border-[#173d34]/40 rounded-2xl p-4 flex flex-col justify-between transition-all duration-200 hover:shadow-lg group';

            const execCount = tpl.executives ? tpl.executives.length : 0;
            const execAvatars = (tpl.executives || []).slice(0, 4).map((ex, idx) => `
                <img src="${escapeHtml(ex.avatar_url || '')}" alt="${escapeHtml(ex.name)}" title="${escapeHtml(ex.name)} (${escapeHtml(ex.title)})" class="w-6 h-6 rounded-full border-2 border-[#ffffff] object-cover -ml-1.5 first:ml-0 shadow-sm" onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80'">
            `).join('');

            const featuresList = (tpl.features || []).slice(0, 3).map(f => `
                <div class="flex items-start space-x-1.5 text-[11px] text-[#3d4942]">
                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0 mt-0.5"></i>
                    <span class="truncate">${escapeHtml(f)}</span>
                </div>
            `).join('');

            let primaryBtnHtml = '';
            if (isFree) {
                primaryBtnHtml = `
                    <button type="button" class="btn-deploy-template flex-1 py-2 px-3 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center space-x-1.5" data-slug="${escapeHtml(tpl.slug)}">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        <span>Deploy Free</span>
                    </button>
                `;
            } else if (isUnlocked) {
                primaryBtnHtml = `
                    <button type="button" class="btn-deploy-template flex-1 py-2 px-3 bg-[#173d34] hover:bg-[#285c4e] text-[#fcfaf5] rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center space-x-1.5" data-slug="${escapeHtml(tpl.slug)}">
                        <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                        <span>Deploy Unlocked</span>
                    </button>
                `;
            } else {
                primaryBtnHtml = `
                    <button type="button" class="btn-buy-template flex-1 py-2 px-3 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-[#ffffff] rounded-xl text-xs font-bold transition-all shadow-md shadow-amber-600/20 flex items-center justify-center space-x-1.5" data-slug="${escapeHtml(tpl.slug)}">
                        <i data-lucide="credit-card" class="w-3.5 h-3.5"></i>
                        <span>Unlock Pro ($${price.toFixed(0)})</span>
                    </button>
                `;
            }

            card.innerHTML = `
                <div>
                    <!-- Header Pill & Badge -->
                    <div class="flex items-center justify-between mb-2.5">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-xl bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20 flex items-center justify-center flex-shrink-0 shadow-sm">
                                <i data-lucide="${escapeHtml(tpl.icon || 'briefcase')}" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <span class="text-[9px] px-2 py-0.5 rounded-full font-mono font-bold ${isFree ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-900 border border-amber-300'}">${escapeHtml(badgeText)}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold ${isFree ? 'text-emerald-700' : 'text-[#173d34]'}">${priceLabel}</span>
                        </div>
                    </div>

                    <!-- Title & Domain -->
                    <h4 class="font-bold text-[#202725] text-xs sm:text-sm group-hover:text-[#173d34] transition-colors leading-snug mb-0.5">${escapeHtml(tpl.title)}</h4>
                    <div class="text-[10px] text-[#68716d] font-mono mb-2">${escapeHtml(tpl.domain)}</div>

                    <!-- Description -->
                    <p class="text-[11px] text-[#3d4942] leading-relaxed line-clamp-2 mb-3">${escapeHtml(tpl.description)}</p>

                    <!-- Features -->
                    <div class="space-y-1 mb-3 bg-[#ffffff] p-2.5 rounded-xl border border-[#ddd8cc]/60">
                        ${featuresList}
                    </div>

                    <!-- Executive Team Avatars -->
                    <div class="flex items-center justify-between pt-2 border-t border-[#ddd8cc]/60 mb-3.5">
                        <div class="flex items-center">
                            ${execAvatars}
                        </div>
                        <span class="text-[10px] font-mono text-[#68716d] font-semibold">${execCount} AI Specialists</span>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="space-y-1.5 pt-1">
                    ${isDeployed ? `<div class="text-center text-[10px] text-emerald-700 font-semibold flex items-center justify-center gap-1 mb-1"><i data-lucide="check-circle" class="w-3 h-3 text-emerald-600"></i> Already In Your Workspaces</div>` : ''}
                    <div class="flex items-center space-x-1.5">
                        <button type="button" class="btn-preview-template px-3 py-2 bg-[#ffffff] hover:bg-[#f5f2e9] text-[#202725] rounded-xl text-xs font-semibold border border-[#ddd8cc] transition-colors flex items-center justify-center" data-slug="${escapeHtml(tpl.slug)}" title="Inspect Team, Roadmaps & SOPs">
                            <span>Preview</span>
                        </button>
                        ${primaryBtnHtml}
                    </div>
                </div>
            `;

            workspaceStoreGrid.appendChild(card);
        });

        // Attach action handlers to buttons
        workspaceStoreGrid.querySelectorAll('.btn-preview-template').forEach(b => {
            b.addEventListener('click', () => previewStoreTemplate(b.dataset.slug));
        });

        workspaceStoreGrid.querySelectorAll('.btn-deploy-template').forEach(b => {
            b.addEventListener('click', () => {
                const tpl = state.storeTemplates.find(t => t.slug === b.dataset.slug);
                if (tpl) deployFreeTemplate(tpl, b);
            });
        });

        workspaceStoreGrid.querySelectorAll('.btn-buy-template').forEach(b => {
            b.addEventListener('click', () => {
                const tpl = state.storeTemplates.find(t => t.slug === b.dataset.slug);
                if (tpl) openCheckoutModal(tpl);
            });
        });

        if (window.lucide) lucide.createIcons();
    }

    function previewStoreTemplate(slug) {
        const tpl = state.storeTemplates.find(t => t.slug === slug);
        if (!tpl) return;

        state.selectedStoreTemplate = tpl;

        // Populate Preview Header
        const previewIcon = document.getElementById('previewIcon');
        if (previewIcon) previewIcon.setAttribute('data-lucide', tpl.icon || 'briefcase');

        const previewTitle = document.getElementById('previewTitle');
        if (previewTitle) previewTitle.textContent = tpl.title;

        const previewDomain = document.getElementById('previewDomain');
        if (previewDomain) previewDomain.textContent = tpl.domain;

        const previewBadge = document.getElementById('previewBadge');
        if (previewBadge) previewBadge.textContent = tpl.badge || (tpl.tier === 'free' ? 'STARTER' : 'PRO');

        const previewPriceBadge = document.getElementById('previewPriceBadge');
        if (previewPriceBadge) {
            previewPriceBadge.textContent = tpl.tier === 'free' ? 'FREE' : `$${parseFloat(tpl.price).toFixed(2)} USD`;
        }

        const previewDescription = document.getElementById('previewDescription');
        if (previewDescription) previewDescription.textContent = tpl.description;

        // Features
        const previewFeaturesList = document.getElementById('previewFeaturesList');
        if (previewFeaturesList) {
            previewFeaturesList.innerHTML = (tpl.features || []).map(f => `
                <div class="flex items-center space-x-2 p-2 bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl text-xs text-[#202725]">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 flex-shrink-0"></i>
                    <span class="font-medium">${escapeHtml(f)}</span>
                </div>
            `).join('');
        }

        // Executives Roster
        const previewExecutivesList = document.getElementById('previewExecutivesList');
        const previewTeamCountBadge = document.getElementById('previewTeamCountBadge');
        if (previewTeamCountBadge) previewTeamCountBadge.textContent = `${(tpl.executives || []).length} AI Specialists`;

        if (previewExecutivesList) {
            previewExecutivesList.innerHTML = (tpl.executives || []).map(ex => `
                <div class="p-3 bg-[#fcfaf5] border border-[#ddd8cc] rounded-2xl flex items-start space-x-3">
                    <img src="${escapeHtml(ex.avatar_url)}" alt="${escapeHtml(ex.name)}" class="w-10 h-10 rounded-2xl object-cover border border-[#ddd8cc] flex-shrink-0 shadow-sm" onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80'">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <h5 class="text-xs font-bold text-[#202725] truncate">${escapeHtml(ex.name)}</h5>
                            <span class="text-[9px] px-1.5 py-0.2 rounded font-mono bg-[#173d34]/10 text-[#173d34] border border-[#173d34]/20">${escapeHtml(ex.voice_id || 'nova')}</span>
                        </div>
                        <div class="text-[10px] text-[#173d34] font-semibold truncate mb-1">${escapeHtml(ex.title)}</div>
                        <p class="text-[10px] text-[#68716d] leading-relaxed line-clamp-2">${escapeHtml(ex.bio)}</p>
                    </div>
                </div>
            `).join('');
        }

        // Roadmaps / Projects
        const previewProjectsList = document.getElementById('previewProjectsList');
        if (previewProjectsList) {
            previewProjectsList.innerHTML = (tpl.projects || []).map(pr => `
                <div class="p-2.5 bg-[#fcfaf5] border border-[#ddd8cc] rounded-xl flex items-center space-x-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-700 flex items-center justify-center flex-shrink-0 border border-indigo-500/20">
                        <i data-lucide="${escapeHtml(pr.icon || 'folder-kanban')}" class="w-3.5 h-3.5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold text-[#202725]">${escapeHtml(pr.name)}</div>
                        <div class="text-[10px] text-[#68716d] truncate">${escapeHtml(pr.description)}</div>
                    </div>
                </div>
            `).join('');
        }

        // Tasks / SOPs
        const previewTasksList = document.getElementById('previewTasksList');
        if (previewTasksList) {
            previewTasksList.innerHTML = (tpl.tasks || []).map(tsk => `
                <div class="p-2 bg-[#ffffff] border border-[#ddd8cc] rounded-xl flex items-center justify-between text-xs">
                    <div class="flex items-center space-x-2 min-w-0 flex-1 mr-2">
                        <i data-lucide="check-square" class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0"></i>
                        <span class="font-medium text-[#202725] truncate">${escapeHtml(tsk.title)}</span>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-mono bg-[#f5f2e9] text-[#68716d] border border-[#ddd8cc] flex-shrink-0">
                        ${escapeHtml(tsk.assigned_to || 'Assigned')}
                    </span>
                </div>
            `).join('');
        }

        // Primary Action Button
        if (previewPrimaryActionText && previewPrimaryActionBtn) {
            if (tpl.tier === 'free' || tpl.is_unlocked) {
                previewPrimaryActionText.textContent = 'Deploy Free Workspace Now';
                previewPrimaryActionBtn.onclick = () => deployFreeTemplate(tpl, previewPrimaryActionBtn);
            } else {
                previewPrimaryActionText.textContent = `Unlock Pro Workspace ($${parseFloat(tpl.price).toFixed(0)} USD)`;
                previewPrimaryActionBtn.onclick = () => {
                    closePreviewModal();
                    openCheckoutModal(tpl);
                };
            }
        }

        if (workspacePreviewModal) workspacePreviewModal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closePreviewModal() {
        if (workspacePreviewModal) workspacePreviewModal.classList.add('hidden');
    }

    async function deployFreeTemplate(template, triggerBtn = null) {
        if (!template) return;

        let originalContent = '';
        if (triggerBtn) {
            originalContent = triggerBtn.innerHTML;
            triggerBtn.disabled = true;
            triggerBtn.innerHTML = `
                <span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>Deploying Blueprint...</span>
            `;
        }

        try {
            const res = await fetch('api/workspace_store.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'deploy_free',
                    slug: template.slug
                })
            });

            const data = await res.json();
            if (data.success && data.venture) {
                closePreviewModal();
                closeWorkspaceStore();
                await loadVentures();
                showCelebrationModal(data.venture);
            } else {
                alert(data.error || 'Failed to deploy workspace');
            }
        } catch (err) {
            console.error('Error deploying free template:', err);
            alert('Error deploying workspace. Please try again.');
        } finally {
            if (triggerBtn) {
                triggerBtn.disabled = false;
                triggerBtn.innerHTML = originalContent;
                if (window.lucide) lucide.createIcons();
            }
        }
    }

    function openCheckoutModal(template) {
        if (!template) return;
        state.selectedStoreTemplate = template;

        const checkoutTemplateTitle = document.getElementById('checkoutTemplateTitle');
        if (checkoutTemplateTitle) checkoutTemplateTitle.textContent = template.title;

        const checkoutTemplateDomain = document.getElementById('checkoutTemplateDomain');
        if (checkoutTemplateDomain) checkoutTemplateDomain.textContent = template.domain;

        const checkoutPriceText = document.getElementById('checkoutPriceText');
        const price = parseFloat(template.price) || 0;
        if (checkoutPriceText) checkoutPriceText.textContent = `$${price.toFixed(2)} USD`;

        if (checkoutPromoInput) checkoutPromoInput.value = '';
        if (workspaceCheckoutModal) workspaceCheckoutModal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeCheckoutModal() {
        if (workspaceCheckoutModal) workspaceCheckoutModal.classList.add('hidden');
    }

    async function processSandboxCheckout() {
        if (!state.selectedStoreTemplate) return;

        const tpl = state.selectedStoreTemplate;
        if (submitSandboxPayBtn) {
            submitSandboxPayBtn.disabled = true;
            if (sandboxPayBtnText) sandboxPayBtnText.textContent = 'Simulating Card Authorization...';
        }

        try {
            const res = await fetch('api/workspace_store.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'dummy_checkout',
                    slug: tpl.slug,
                    card_last4: '4242'
                })
            });

            const data = await res.json();
            if (data.success && data.venture) {
                closeCheckoutModal();
                closePreviewModal();
                closeWorkspaceStore();
                await loadVentures();
                showCelebrationModal(data.venture);
            } else {
                alert(data.error || 'Test payment failed');
            }
        } catch (err) {
            console.error('Error processing sandbox checkout:', err);
            alert('Error during simulated payment.');
        } finally {
            if (submitSandboxPayBtn) {
                submitSandboxPayBtn.disabled = false;
                if (sandboxPayBtnText) sandboxPayBtnText.textContent = 'Simulate Successful Test Payment';
                if (window.lucide) lucide.createIcons();
            }
        }
    }

    async function redeemStorePromoCode() {
        if (!state.selectedStoreTemplate) return;
        const code = (checkoutPromoInput ? checkoutPromoInput.value.trim().toUpperCase() : '');
        if (!code) {
            alert('Please enter a VIP or Promo code.');
            return;
        }

        if (applyPromoBtn) {
            applyPromoBtn.disabled = true;
            applyPromoBtn.textContent = 'Validating...';
        }

        try {
            const res = await fetch('api/workspace_store.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'redeem_promo',
                    slug: state.selectedStoreTemplate.slug,
                    promo_code: code
                })
            });

            const data = await res.json();
            if (data.success && data.venture) {
                closeCheckoutModal();
                closePreviewModal();
                closeWorkspaceStore();
                await loadVentures();
                showCelebrationModal(data.venture);
            } else {
                alert(data.error || 'Invalid promo code');
            }
        } catch (err) {
            console.error('Error redeeming code:', err);
            alert('Failed to validate code.');
        } finally {
            if (applyPromoBtn) {
                applyPromoBtn.disabled = false;
                applyPromoBtn.innerHTML = `<i data-lucide="tag" class="w-3.5 h-3.5"></i><span>Apply</span>`;
                if (window.lucide) lucide.createIcons();
            }
        }
    }

    function showCelebrationModal(venture) {
        state.pendingCelebrationVentureId = venture.id;

        const celebrationTitle = document.getElementById('celebrationTitle');
        if (celebrationTitle) celebrationTitle.textContent = `🚀 "${venture.name}" Ready!`;

        const celebrationDesc = document.getElementById('celebrationDesc');
        if (celebrationDesc) {
            celebrationDesc.textContent = `Your workspace has been provisioned with its specialized AI executive team and starter deliverables. You can switch to it now or continue in your Primary Workspace.`;
        }

        if (workspaceCelebrationModal) workspaceCelebrationModal.classList.remove('hidden');
        showToastNotification(`🎉 Workspace "${venture.name}" is now ready!`, 'success');
        if (window.lucide) lucide.createIcons();
    }

    // Window Exports
    window.openWorkspaceStore = openWorkspaceStore;
    window.closeWorkspaceStore = closeWorkspaceStore;
    window.previewStoreTemplate = previewStoreTemplate;
    window.startNewChat = startNewChat;
    window.createNewSession = startNewChat;
    window.showToastNotification = showToastNotification;
    window.showToast = showToastNotification;
    window.openWorkspaceModal = openVentureModal;
    window.closeWorkspaceModal = closeVentureModal;
    window.selectExecutive = selectExecutive;
    window.selectChatSession = selectSession;
    window.deleteChatSession = deleteSession;
    window.toggleTaskStatus = toggleTask;
    window.deleteTaskItem = deleteTask;
    window.deleteMemoryItem = deleteMemory;
    window.promptClearAllMemories = promptClearAllMemories;
    window.executeClearAllMemories = executeClearAllMemories;
    window.closeConfirmClearMemories = closeConfirmClearMemories;
    window.switchVenture = switchVenture;
    window.openDiscussionModal = openDiscussionModal;
    window.openWorkPreviewModal = openWorkPreviewModal;
    window.closeWorkPreviewModal = closeWorkPreviewModal;
    window.switchWorkPreviewTab = switchWorkPreviewTab;
    window.deliverWorkReportNow = deliverWorkReportNow;
    window.simulateWorkPreviewStep = simulateWorkPreviewStep;
    window.chatWithExecutiveAboutCurrentTask = chatWithExecutiveAboutCurrentTask;
    window.openTaskLiveWorkPreview = openTaskLiveWorkPreview;
    window.openExecutiveLiveWork = openExecutiveLiveWork;
    window.openProjectLiveSprintPreview = openProjectLiveSprintPreview;
    window.openTaskEditModal = openTaskEditModal;
    window.moveTaskStatus = moveTaskStatus;
    window.startAllProjectTasks = startAllProjectTasks;
    window.switchToTasksView = switchToTasksView;
    window.switchToChatView = switchToChatView;
    window.switchToBoardroomView = switchToBoardroomView;
    window.scrollKanbanToColumn = (columnKey) => {
        const container = document.getElementById('kanbanLanesContainer');
        if (!container) return;
        const tabs = document.querySelectorAll('.mobile-lane-tab');
        tabs.forEach(t => {
            if (t.dataset.target === columnKey) {
                t.className = 'mobile-lane-tab px-2.5 py-1 rounded-full font-bold bg-[#ffffff] text-[#173d34] shadow-sm';
            } else {
                t.className = 'mobile-lane-tab px-2.5 py-1 rounded-full text-[#68716d]';
            }
        });

        if (columnKey === 'all') {
            container.scrollTo({ left: 0, behavior: 'smooth' });
            return;
        }

        const targetCol = document.getElementById(columnKey + 'Lane');
        if (targetCol) {
            targetCol.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }
    };
    window.filterByProjectAndOpenBoard = (projectName) => {
        state.taskFilterProject = projectName;
        if (taskProjectFilter) taskProjectFilter.value = projectName;
        switchToTasksView();
        renderProjectPills();
        renderTasksBoard();
    };
    window.filterByProject = (projectName) => {
        state.taskFilterProject = projectName;
        if (taskProjectFilter) taskProjectFilter.value = projectName;
        renderProjectPills();
        renderTasksBoard();
    };
    window.openChatWithRuXtAboutProject = (projectName) => {
        switchToChatView();
        const ruxt = state.executives.find(e => e.is_chief_of_staff == 1);
        if (ruxt) {
            state.selectedExecutiveId = ruxt.id;
            renderExecutivesList();
        }
        if (chatInput) {
            chatInput.value = `RuXt, let's discuss project "${projectName}". What is the current status and what deliverables should we execute next?`;
            chatInput.focus();
            autoResizeInput();
        }
    };
    window.openTaskCreateModalForProject = (projectName, projectId) => {
        openTaskCreateModal(projectName, projectId);
    };
    window.sendPrompt = (prompt) => {
        chatInput.value = prompt;
        sendMessage();
    };
    window.toggleQuickOtherInput = (uid, forceOpen = null) => {
        const box = document.getElementById(`${uid}_other_box`);
        if (!box) return;
        const shouldOpen = forceOpen !== null ? forceOpen : box.classList.contains('hidden');
        if (shouldOpen) {
            box.classList.remove('hidden');
            const inp = document.getElementById(`${uid}_input`);
            if (inp) {
                inp.focus();
            }
            if (chatInput) {
                chatInput.placeholder = "Enter your custom domain / role, or ask any question...";
            }
            if (window.lucide) lucide.createIcons();
        } else {
            box.classList.add('hidden');
        }
    };
    window.submitQuickOtherInput = (uid) => {
        const inp = document.getElementById(`${uid}_input`);
        if (!inp) return;
        const val = inp.value.trim();
        if (!val) {
            inp.focus();
            inp.classList.add('border-rose-400');
            setTimeout(() => inp.classList.remove('border-rose-400'), 1500);
            return;
        }
        window.sendPrompt(`My field and focus is: ${val}`);
    };
    window.skipOnboarding = async () => {
        try {
            showToastNotification('Skipping role setup... Initializing Chief of Staff.');
            await fetch('api/onboarding.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'skip' })
            });
            if (state.user) {
                state.user.onboarding_completed = 1;
                state.user.job_field = 'General Operations';
                state.user.specialized_role = 'Chief of Staff & Lead Private Assistant';
            }
            const pField = document.getElementById('profileUserJobField');
            if (pField) pField.textContent = 'General Operations';
            const pRole = document.getElementById('profileRuxtSpecializedRole');
            if (pRole) pRole.textContent = 'Chief of Staff & Lead Private Assistant';

            // Seamlessly continue conversation directly!
            window.sendPrompt("Let's skip the role setup for now. How can you assist me today?");
        } catch (err) {
            console.error('Failed to skip onboarding:', err);
            window.sendPrompt("Let's skip the role setup for now and continue directly.");
        }
    };

    // Event Listeners
    sendBtn.addEventListener('click', () => sendMessage());
    chatInput.addEventListener('input', autoResizeInput);
    chatInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // File Attachment Controls
    if (attachBtn && chatFileInput) {
        attachBtn.addEventListener('click', () => chatFileInput.click());
        chatFileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files.length > 0) {
                handleFileSelection(e.target.files);
                chatFileInput.value = '';
            }
        });
    }

    // Clipboard Paste Listener (Ctrl+V for Screenshots & Files)
    if (chatInput) {
        chatInput.addEventListener('paste', (e) => {
            const items = (e.clipboardData || window.clipboardData)?.items;
            if (!items) return;
            const pastedFiles = [];
            for (let i = 0; i < items.length; i++) {
                if (items[i].kind === 'file') {
                    const file = items[i].getAsFile();
                    if (file) pastedFiles.push(file);
                }
            }
            if (pastedFiles.length > 0) {
                handleFileSelection(pastedFiles);
            }
        });
    }

    // Drag and Drop Handling across the Chat Dock
    let dragCounter = 0;
    ['dragenter', 'dragover'].forEach(eventName => {
        window.addEventListener(eventName, (e) => {
            // Check if dragging files
            if (e.dataTransfer && e.dataTransfer.types && Array.from(e.dataTransfer.types).includes('Files')) {
                e.preventDefault();
                dragCounter++;
                if (chatDropZone) chatDropZone.classList.remove('hidden');
            }
        });
    });

    ['dragleave', 'dragend'].forEach(eventName => {
        window.addEventListener(eventName, (e) => {
            e.preventDefault();
            dragCounter--;
            if (dragCounter <= 0 && chatDropZone) {
                chatDropZone.classList.add('hidden');
                dragCounter = 0;
            }
        });
    });

    window.addEventListener('drop', (e) => {
        e.preventDefault();
        dragCounter = 0;
        if (chatDropZone) chatDropZone.classList.add('hidden');
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleFileSelection(e.dataTransfer.files);
        }
    });

    micBtn.addEventListener('click', toggleRecording);
    if (micLangBtn) micLangBtn.addEventListener('click', toggleMicLanguage);

    soundToggleBtn.addEventListener('click', () => {
        state.autoSpeak = !state.autoSpeak;
        if (state.autoSpeak) {
            soundToggleIcon.setAttribute('data-lucide', 'volume-2');
            soundToggleBtn.classList.add('text-amber-400');
            soundToggleBtn.title = 'Voice Output: ON';
        } else {
            soundToggleIcon.setAttribute('data-lucide', 'volume-x');
            soundToggleBtn.classList.remove('text-amber-400');
            soundToggleBtn.title = 'Voice Output: OFF';
            stopAllAudio();
        }
        if (window.lucide) lucide.createIcons();
    });

    // Live Voice Call Listeners
    if (startVoiceCallBtn) {
        startVoiceCallBtn.addEventListener('click', () => startLiveVoiceCall(state.selectedExecutiveId));
    }
    if (closeVoiceCallModalBtn) {
        closeVoiceCallModalBtn.addEventListener('click', endLiveVoiceCall);
    }
    if (endVoiceCallBtn) {
        endVoiceCallBtn.addEventListener('click', endLiveVoiceCall);
    }
    if (callMicLangBtn) {
        callMicLangBtn.addEventListener('click', toggleMicLanguage);
    }
    if (callHandsFreeBtn) {
        callHandsFreeBtn.addEventListener('click', toggleCallHandsFree);
    }
    if (callMuteBtn) {
        callMuteBtn.addEventListener('click', toggleCallMute);
    }

    // Voice Test Button in Settings
    if (testVoiceBtn) {
        testVoiceBtn.addEventListener('click', () => {
            const exec = state.executives.find(e => e.id == state.selectedExecutiveId) || { name: 'Elena Vance' };
            const sampleText = `Hello CEO, this is ${exec.name}. Your real voice system is operating with maximum clarity and precision.`;
            playExecutiveVoice(sampleText, state.selectedExecutiveId);
        });
    }

    // Venture Dropdown & Modal Listeners
    if (ventureDropdownBtn) {
        ventureDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleVentureDropdown();
        });
    }
    if (headerVenturePill) {
        headerVenturePill.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleVentureDropdown();
        });
    }
    if (openNewVentureBtn) openNewVentureBtn.addEventListener('click', openVentureModal);
    if (btnAddNewVentureDropdown) btnAddNewVentureDropdown.addEventListener('click', openVentureModal);
    if (closeVentureModalBtn) closeVentureModalBtn.addEventListener('click', closeVentureModal);
    if (ventureForm) ventureForm.addEventListener('submit', handleCreateVenture);

    // Workspace Marketplace & Predefined Blueprints Listeners
    if (openWorkspaceStoreBtn) openWorkspaceStoreBtn.addEventListener('click', () => openWorkspaceStore('all'));
    if (btnOpenStoreDropdown) btnOpenStoreDropdown.addEventListener('click', () => { closeVentureDropdown(); openWorkspaceStore('all'); });
    if (headerStoreBtn) headerStoreBtn.addEventListener('click', () => openWorkspaceStore('all'));
    if (closeWorkspaceStoreBtn) closeWorkspaceStoreBtn.addEventListener('click', closeWorkspaceStore);

    if (storeSearchInput) {
        storeSearchInput.addEventListener('input', () => renderStoreCards());
    }

    document.querySelectorAll('.store-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const f = btn.dataset.filter || 'all';
            state.activeStoreFilter = f;
            document.querySelectorAll('.store-filter-btn').forEach(b => {
                const isMatch = b.dataset.filter === f;
                b.className = isMatch 
                    ? 'store-filter-btn px-3 py-1 rounded-full font-bold transition-all bg-[#173d34] text-[#fcfaf5] shadow-sm'
                    : 'store-filter-btn px-3 py-1 rounded-full font-semibold transition-all bg-[#ffffff] text-[#3d4942] hover:bg-[#ede8dc] border border-[#ddd8cc]';
            });
            renderStoreCards();
        });
    });

    if (closePreviewModalBtn) closePreviewModalBtn.addEventListener('click', closePreviewModal);
    if (previewBackBtn) previewBackBtn.addEventListener('click', closePreviewModal);

    if (closeCheckoutModalBtn) closeCheckoutModalBtn.addEventListener('click', closeCheckoutModal);
    if (submitSandboxPayBtn) submitSandboxPayBtn.addEventListener('click', processSandboxCheckout);
    if (applyPromoBtn) applyPromoBtn.addEventListener('click', redeemStorePromoCode);
    if (checkoutPromoInput) {
        checkoutPromoInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') redeemStorePromoCode();
        });
    }

    if (celebrationSwitchNowBtn) {
        celebrationSwitchNowBtn.addEventListener('click', async () => {
            if (workspaceCelebrationModal) workspaceCelebrationModal.classList.add('hidden');
            if (state.pendingCelebrationVentureId) {
                await switchVenture(state.pendingCelebrationVentureId);
                state.pendingCelebrationVentureId = null;
            }
        });
    }
    if (celebrationStayBtn) {
        celebrationStayBtn.addEventListener('click', () => {
            if (workspaceCelebrationModal) workspaceCelebrationModal.classList.add('hidden');
            state.pendingCelebrationVentureId = null;
        });
    }

    if (workspaceStoreModal) {
        workspaceStoreModal.addEventListener('click', (e) => {
            if (e.target === workspaceStoreModal) closeWorkspaceStore();
        });
    }
    if (workspacePreviewModal) {
        workspacePreviewModal.addEventListener('click', (e) => {
            if (e.target === workspacePreviewModal) closePreviewModal();
        });
    }
    if (workspaceCheckoutModal) {
        workspaceCheckoutModal.addEventListener('click', (e) => {
            if (e.target === workspaceCheckoutModal) closeCheckoutModal();
        });
    }
    if (workspaceCelebrationModal) {
        workspaceCelebrationModal.addEventListener('click', (e) => {
            if (e.target === workspaceCelebrationModal) {
                workspaceCelebrationModal.classList.add('hidden');
                state.pendingCelebrationVentureId = null;
            }
        });
    }

    document.addEventListener('click', (e) => {
        if (ventureDropdownMenu && !ventureDropdownMenu.contains(e.target) && (!ventureDropdownBtn || !ventureDropdownBtn.contains(e.target)) && (!headerVenturePill || !headerVenturePill.contains(e.target))) {
            closeVentureDropdown();
        }
    });

    newConsultationBtn.addEventListener('click', () => startNewChat(state.selectedExecutiveId));
    navBoardroomBtn.addEventListener('click', switchToBoardroomView);
    callMeetingBtn.addEventListener('click', openCallMeetingModal);
    closeCallMeetingBtn.addEventListener('click', closeCallMeetingModal);
    boardMeetingForm.addEventListener('submit', handleConveneBoardMeeting);

    // Tasks Board & Discussions Listeners
    if (navTasksBoardBtn) navTasksBoardBtn.addEventListener('click', switchToTasksView);
    if (navDiscussionsBtn) navDiscussionsBtn.addEventListener('click', openDiscussionsListModal);
    if (topToggleChatBtn) topToggleChatBtn.addEventListener('click', switchToChatView);
    if (topToggleBoardroomBtn) topToggleBoardroomBtn.addEventListener('click', switchToBoardroomView);
    if (topToggleTasksBtn) topToggleTasksBtn.addEventListener('click', switchToTasksView);
    if (btnViewKanban) {
        btnViewKanban.addEventListener('click', () => {
            state.taskViewMode = 'kanban';
            renderTasksBoard();
        });
    }
    if (btnViewProjectWise) {
        btnViewProjectWise.addEventListener('click', () => {
            state.taskViewMode = 'project_wise';
            renderTasksBoard();
        });
    }
    if (taskProjectFilter) {
        taskProjectFilter.addEventListener('change', (e) => {
            state.taskFilterProject = e.target.value;
            renderProjectPills();
            renderTasksBoard();
        });
    }
    if (headerProjectPill) {
        headerProjectPill.addEventListener('click', () => {
            if (headerProjectName && headerProjectName.textContent !== 'All Projects') {
                window.filterByProjectAndOpenBoard(headerProjectName.textContent);
            } else {
                switchToTasksView();
            }
        });
    }
    if (openNewProjectModalBtn) openNewProjectModalBtn.addEventListener('click', openNewProjectModal);
    if (closeNewProjectModalBtn) closeNewProjectModalBtn.addEventListener('click', closeNewProjectModal);
    if (newProjectForm) {
        newProjectForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = newProjectNameInput.value.trim();
            const description = newProjectDescInput.value.trim();
            const color = state.selectedProjectColor || 'indigo';
            if (!name) return;

            const submitBtn = document.getElementById('submitNewProjectBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Creating Project...';
            }

            try {
                const res = await fetch('api/projects.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        venture_id: state.currentVentureId,
                        name,
                        description,
                        color
                    })
                });
                const data = await res.json();
                if (data.success) {
                    closeNewProjectModal();
                    state.taskFilterProject = name;
                    await loadProjects();
                    await loadNotes();
                }
            } catch (err) {
                console.error('Failed to create project:', err);
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `<i data-lucide="check" class="w-4 h-4"></i><span>Create Project</span>`;
                    if (window.lucide) lucide.createIcons();
                }
            }
        });
    }
    if (projectColorPicker) {
        projectColorPicker.addEventListener('click', (e) => {
            const btn = e.target.closest('.project-color-opt');
            if (btn) {
                state.selectedProjectColor = btn.dataset.color;
                updateProjectColorSelection();
            }
        });
    }
    if (taskAssigneeFilter) {
        taskAssigneeFilter.addEventListener('change', (e) => {
            state.taskFilterAssignee = e.target.value;
            renderTasksBoard();
        });
    }
    if (openNewTaskModalBtn) openNewTaskModalBtn.addEventListener('click', () => openTaskCreateModal());
    if (closeTaskDetailModalBtn) closeTaskDetailModalBtn.addEventListener('click', closeTaskDetailModal);
    if (taskDetailForm) taskDetailForm.addEventListener('submit', handleSaveTaskDetail);
    if (closeDiscussionModalBtn) closeDiscussionModalBtn.addEventListener('click', closeDiscussionModal);
    if (closeDiscussionsListModalBtn) closeDiscussionsListModalBtn.addEventListener('click', closeDiscussionsListModal);

    openHireModalBtn.addEventListener('click', openHireModal);
    closeHireModalBtn.addEventListener('click', closeHireModal);
    hireForm.addEventListener('submit', handleHireExecutive);

    openMemoriesBtn.addEventListener('click', openMemories);
    closeMemoriesBtn.addEventListener('click', closeMemories);
    addMemoryBtn.addEventListener('click', addMemory);
    newMemoryInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') addMemory(); });

    const clearAllMemoriesBtn = document.getElementById('clearAllMemoriesBtn');
    if (clearAllMemoriesBtn) clearAllMemoriesBtn.addEventListener('click', promptClearAllMemories);

    const clearAllMemoriesFromModalBtn = document.getElementById('clearAllMemoriesFromModalBtn');
    if (clearAllMemoriesFromModalBtn) clearAllMemoriesFromModalBtn.addEventListener('click', promptClearAllMemories);

    const cancelClearMemoriesBtn = document.getElementById('cancelClearMemoriesBtn');
    if (cancelClearMemoriesBtn) cancelClearMemoriesBtn.addEventListener('click', closeConfirmClearMemories);

    const confirmClearMemoriesBtn = document.getElementById('confirmClearMemoriesBtn');
    if (confirmClearMemoriesBtn) confirmClearMemoriesBtn.addEventListener('click', executeClearAllMemories);

    const confirmClearMemoriesModal = document.getElementById('confirmClearMemoriesModal');
    if (confirmClearMemoriesModal) {
        confirmClearMemoriesModal.addEventListener('click', (e) => {
            if (e.target === confirmClearMemoriesModal) closeConfirmClearMemories();
        });
    }

    const profileModalElem = document.getElementById('profileModal');
    if (profileModalElem) {
        profileModalElem.addEventListener('click', (e) => {
            if (e.target === profileModalElem) closeProfileModal();
        });
    }

    openTasksBtn.addEventListener('click', openTasks);
    closeTasksBtn.addEventListener('click', closeTasks);
    addTaskBtn.addEventListener('click', addTask);
    newTaskInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') addTask(); });

    openSettingsBtn.addEventListener('click', openSettings);
    closeSettingsBtn.addEventListener('click', closeSettings);
    settingsForm.addEventListener('submit', saveSettings);

    const resetTokenUsageBtn = document.getElementById('resetTokenUsageBtn');
    if (resetTokenUsageBtn) resetTokenUsageBtn.addEventListener('click', resetTokenStats);

    const refreshTokenStatsBtn = document.getElementById('refreshTokenStatsBtn');
    if (refreshTokenStatsBtn) refreshTokenStatsBtn.addEventListener('click', refreshTokenStats);

    mobileMenuBtn.addEventListener('click', openMobileSidebar);
    closeSidebarBtn.addEventListener('click', closeMobileSidebar);
    mobileOverlay.addEventListener('click', closeMobileSidebar);

    // Mobile Bottom Navigation Bar Listeners
    const bottomNavChatBtn = document.getElementById('bottomNavChatBtn');
    const bottomNavTasksBtn = document.getElementById('bottomNavTasksBtn');
    const bottomNavBoardroomBtn = document.getElementById('bottomNavBoardroomBtn');
    const bottomNavDiscussionsBtn = document.getElementById('bottomNavDiscussionsBtn');
    const bottomNavMenuBtn = document.getElementById('bottomNavMenuBtn');

    if (bottomNavChatBtn) bottomNavChatBtn.addEventListener('click', switchToChatView);
    if (bottomNavTasksBtn) bottomNavTasksBtn.addEventListener('click', switchToTasksView);
    if (bottomNavBoardroomBtn) bottomNavBoardroomBtn.addEventListener('click', switchToBoardroomView);
    if (bottomNavDiscussionsBtn) bottomNavDiscussionsBtn.addEventListener('click', openDiscussionsListModal);
    if (bottomNavMenuBtn) bottomNavMenuBtn.addEventListener('click', openMobileSidebar);

    // Mobile virtual keyboard handling (hide bottom navigation while typing for maximum chat area)
    const mobileBottomNav = document.getElementById('mobileBottomNav');
    if (chatInput && mobileBottomNav) {
        chatInput.addEventListener('focus', () => {
            if (window.innerWidth < 768) {
                mobileBottomNav.classList.add('hidden');
            }
        });
        chatInput.addEventListener('blur', () => {
            if (window.innerWidth < 768) {
                mobileBottomNav.classList.remove('hidden');
            }
        });
    }

    // Touch swipe gesture to close mobile sidebar drawer
    if (sidebar) {
        let touchStartX = 0;
        let touchEndX = 0;
        sidebar.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        sidebar.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            if (touchStartX - touchEndX > 50) {
                closeMobileSidebar();
            }
        }, { passive: true });
    }

    // Global Escape key handler to close popups
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const profileModal = document.getElementById('profileModal');
            if (profileModal && !profileModal.classList.contains('hidden')) {
                closeProfileModal();
            }
        }
    });

    // Startup
    initSpeechRecognition();
    loadSettings().then(() => {
        loadVentures().then(async () => {
            await loadProjects();
            await loadExecutives();
            await loadSessions();
            await loadMemories();
            await loadDiscussions();
            await loadNotes();

            // Auto-trigger onboarding only if user needs role configuration (not completed or skipped)
            if (!state.user || !state.user.onboarding_completed || parseInt(state.user.onboarding_completed, 10) === 0) {
                checkAndTriggerOnboarding(false);
            }
        });
    });
    if (window.lucide) lucide.createIcons();
});
