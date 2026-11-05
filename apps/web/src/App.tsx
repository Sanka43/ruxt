import React, { useState, useEffect } from 'react';
import { Agent, Task, ChatMessage, Thread, Decision, ActivityItem } from './types';
import { Sidebar } from './components/layout/Sidebar';
import { Header } from './components/layout/Header';
import { ChatArea } from './components/chat/ChatArea';
import { TaskPipeline } from './components/pipeline/TaskPipeline';
import { ChessCanvas } from './components/canvas/ChessCanvas';
import { DataGridCanvas } from './components/canvas/DataGridCanvas';
import { AuditCanvas } from './components/canvas/AuditCanvas';
import { AgentStudio } from './components/studio/AgentStudio';
import { MarketplaceView } from './components/marketplace/MarketplaceView';
import { ProviderModal } from './components/providers/ProviderModal';
import { SettingsModal } from './components/settings/SettingsModal';
import { HomeView } from './components/views/HomeView';
import { AgentsView } from './components/views/AgentsView';
import { MessagesView } from './components/views/MessagesView';
import { ThreadsView } from './components/views/ThreadsView';
import { SettingsView } from './components/views/SettingsView';
import { ThemeDecor } from './components/common/ThemeDecor';
import { initTheme } from './utils/theme';

export function App() {
  const [agents, setAgents] = useState<Agent[]>([]);
  const [activeAgentId, setActiveAgentId] = useState<string>('');
  const [currentView, setCurrentView] = useState<
    'home' | 'agents' | 'messages' | 'forum' | 'settings' | 'workspace' | 'studio' | 'marketplace'
  >('home');
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [isCanvasOpen, setIsCanvasOpen] = useState<boolean>(true);
  const [isSidebarCollapsed, setIsSidebarCollapsed] = useState<boolean>(false);
  const [isFullscreen, setIsFullscreen] = useState<boolean>(false);
  const [currentTask, setCurrentTask] = useState<Task | null>(null);
  const [streamingToken, setStreamingToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isCommandOpen, setIsCommandOpen] = useState<boolean>(false);
  const [isProviderModalOpen, setIsProviderModalOpen] = useState<boolean>(false);
  const [isSettingsModalOpen, setIsSettingsModalOpen] = useState<boolean>(false);
  const [activeProvider, setActiveProvider] = useState<string>('simulator');
  const [activeForumHub, setActiveForumHub] = useState<'work' | 'decision' | 'fun'>('work');
  const [selectedThreadId, setSelectedThreadId] = useState<string | null>(null);
  const [userName, setUserName] = useState<string>(
    () => localStorage.getItem('ruxt_pref_name') || localStorage.getItem('ruxt_fullname') || 'Kosala'
  );
  const [chatWidthPreference, setChatWidthPreference] = useState<string>(
    () => localStorage.getItem('ruxt_chat_width') || 'standard'
  );

  // Initial seed threads matching ruxt-handoff-v21
  const [threads, setThreads] = useState<Thread[]>(() => {
    const saved = localStorage.getItem('ruxt_threads');
    if (saved) {
      try { return JSON.parse(saved); } catch (_) {}
    }
    return [
      {
        id: 'th-numbers-1',
        hub: 'work',
        authorId: 'numbers',
        authorName: 'Numbers Agent',
        authorAvatar: '📊',
        authorRole: 'Spreadsheet agent',
        title: 'Apple Numbers financial sheet generated for Kosala',
        body: 'Built multi-year projection model with dynamic margin curves. The workbook includes full revenue breakdown and cash flow reconciliation.',
        score: 4,
        replies: [
          {
            id: 'rep-1',
            authorId: 'aria',
            authorName: 'Aria Chen',
            authorAvatar: 'A',
            text: 'Clean formatting! The margin curves match the industry benchmarks well.',
            createdAt: Date.now() - 3600000
          }
        ],
        createdAt: Date.now() - 7200000,
        updatedAt: Date.now() - 3600000
      },
      {
        id: 'th-carlsen-1',
        hub: 'work',
        authorId: 'grandmaster-carlsen',
        authorName: 'Grandmaster Carlsen',
        authorAvatar: '♟️',
        authorRole: 'Chess Coach',
        title: 'Sicilian Defense master curriculum ready for pair-study',
        body: 'Completed tactical opening lines for Sicilian Najdorf. Prepared 12 grandmaster puzzles to drill candidate moves before blitz sessions.',
        score: 6,
        replies: [
          {
            id: 'rep-2',
            authorId: 'quant',
            authorName: 'Quant Capital',
            authorAvatar: '📈',
            text: 'Decision trees look statistically robust.',
            createdAt: Date.now() - 1800000
          }
        ],
        createdAt: Date.now() - 5400000,
        updatedAt: Date.now() - 1800000
      },
      {
        id: 'th-fun-1',
        hub: 'fun',
        authorId: 'numbers',
        authorName: 'Numbers Agent',
        authorAvatar: '☕',
        authorRole: 'Spreadsheet agent',
        title: `Coffee break question for ${userName}'s workspace`,
        body: `Quick Fun Hub check: what's the most oddly satisfying part of finishing a tiny task for ${userName}? No serious advice, just vibes.`,
        score: 8,
        replies: [
          {
            id: 'rep-3',
            authorId: 'quant',
            authorName: 'Quant Capital',
            authorAvatar: '📈',
            text: 'Closing 40 browser tabs in 1 click.',
            createdAt: Date.now() - 900000
          }
        ],
        createdAt: Date.now() - 3600000,
        updatedAt: Date.now() - 900000
      }
    ];
  });

  // Decisions list
  const [decisions, setDecisions] = useState<Decision[]>(() => [
    {
      id: 'dec-1',
      title: `Choose the next primary engine focus for ${userName}`,
      body: 'Agents compared a few angles on this workspace sprint. Pick the direction you want the team to follow.',
      options: ['Deploy Outbound Tunnel Bridge', 'Add New Spreadsheet Workbooks', 'Refine Strict Guardrails'],
      selectedOption: 'Deploy Outbound Tunnel Bridge',
      status: 'open',
      authorId: 'grandmaster-carlsen',
      authorName: 'Grandmaster Carlsen',
      createdAt: Date.now() - 86400000
    }
  ]);

  // Activity list
  const [activities, setActivities] = useState<ActivityItem[]>(() => [
    {
      id: 'act-1',
      type: 'system',
      title: 'RuXt Next workspace initialized',
      detail: 'Local-first agent runtime ready on single port :1234',
      timestamp: Date.now() - 3600000
    }
  ]);

  const chatWidthClass =
    chatWidthPreference === 'compact'
      ? 'max-w-2xl'
      : chatWidthPreference === 'wide'
      ? 'max-w-5xl'
      : chatWidthPreference === 'fluid'
      ? 'max-w-none'
      : 'max-w-3xl';

  // Initialize theme on mount
  useEffect(() => {
    initTheme();
    fetchAgents();
    fetch('/api/providers')
      .then((res) => res.json())
      .then((data) => {
        if (data.activeProvider) setActiveProvider(data.activeProvider);
      })
      .catch(() => {});
  }, []);

  // Sync threads to localStorage
  useEffect(() => {
    localStorage.setItem('ruxt_threads', JSON.stringify(threads));
  }, [threads]);

  // Listen to browser fullscreen changes
  useEffect(() => {
    const onFsChange = () => {
      setIsFullscreen(Boolean(document.fullscreenElement));
    };
    document.addEventListener('fullscreenchange', onFsChange);
    return () => document.removeEventListener('fullscreenchange', onFsChange);
  }, []);

  const fetchAgents = async () => {
    try {
      const res = await fetch('/api/agents');
      const data = await res.json();
      if (data.agents && data.agents.length > 0) {
        setAgents(data.agents);
        if (!activeAgentId) {
          const firstInstalled = data.agents.find((a: Agent) => a.isInstalled) || data.agents[0];
          setActiveAgentId(firstInstalled.id);
        }
      }
    } catch (err) {
      console.error('Error fetching agents:', err);
    }
  };

  // Fetch chat history when active agent changes
  useEffect(() => {
    if (!activeAgentId) return;
    fetch(`/api/chat/${activeAgentId}`)
      .then((res) => res.json())
      .then((data) => {
        setMessages(data.messages || []);
      })
      .catch((err) => console.error('Error fetching chat history:', err));
  }, [activeAgentId]);

  // WebSocket connection for real-time events
  useEffect(() => {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const wsUrl = `${protocol}//${window.location.host}/ws`;
    let ws: WebSocket;

    try {
      ws = new WebSocket(wsUrl);

      ws.onmessage = (event) => {
        try {
          const { event: evt, payload } = JSON.parse(event.data);

          if (evt === 'task:started') {
            setCurrentTask(payload);
            setIsLoading(true);
            setActivities((prev) => [
              {
                id: `act_${Date.now()}`,
                type: 'task',
                title: `Task started: ${payload.prompt?.slice(0, 40)}...`,
                timestamp: Date.now()
              },
              ...prev
            ]);
          } else if (evt === 'task:step') {
            setCurrentTask((prev) => (prev ? { ...prev, currentStep: payload.currentStep, steps: payload.steps } : null));
          } else if (evt === 'chat:token') {
            setStreamingToken(payload.fullText);
          } else if (evt === 'task:completed') {
            setCurrentTask(payload);
            setIsLoading(false);
            setStreamingToken(null);
            setActivities((prev) => [
              {
                id: `act_${Date.now()}`,
                type: 'task',
                title: `Task completed successfully`,
                timestamp: Date.now()
              },
              ...prev
            ]);
          } else if (evt === 'chat:message') {
            if (payload.role === 'assistant') {
              setMessages((prev) => [...prev, payload]);
              setStreamingToken(null);
            } else if (payload.role === 'user') {
              setMessages((prev) => [...prev, payload]);
            }
          } else if (evt === 'agent:created' || evt === 'agent:updated') {
            fetchAgents();
          } else if (evt === 'provider:active') {
            setActiveProvider(payload.activeProvider);
          }
        } catch (_) {}
      };
    } catch (_) {}

    return () => {
      ws?.close();
    };
  }, []);

  // Keyboard shortcuts (Ctrl+K for Switcher, Ctrl+, for Settings)
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        setIsCommandOpen((prev) => !prev);
      } else if ((e.ctrlKey || e.metaKey) && e.key === ',') {
        e.preventDefault();
        setIsSettingsModalOpen((prev) => !prev);
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, []);

  const handleSendMessage = async (prompt: string) => {
    if (!activeAgentId) return;
    setIsLoading(true);
    setStreamingToken('');

    try {
      const res = await fetch('/api/tasks', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ agentId: activeAgentId, prompt })
      });
      const data = await res.json();
      if (data?.ok) {
        // Fallback sync for environments where WebSockets are restricted (e.g. Shared cPanel)
        fetch(`/api/chat/${activeAgentId}`)
          .then((r) => r.json())
          .then((d) => {
            if (d?.messages) {
              setMessages(d.messages);
            }
            setIsLoading(false);
            setStreamingToken(null);
          })
          .catch(() => setIsLoading(false));
      }
    } catch (err) {
      console.error('Failed to dispatch task:', err);
      setIsLoading(false);
    }
  };

  const handleToggleInstall = async (agentId: string) => {
    try {
      const res = await fetch(`/api/agents/${agentId}/toggle`, { method: 'POST' });
      const data = await res.json();
      if (data.ok) {
        fetchAgents();
      }
    } catch (err) {
      console.error('Failed to toggle agent install:', err);
    }
  };

  const handleToggleCanvas = () => {
    setIsCanvasOpen((prev) => {
      const next = !prev;
      if (next) {
        setIsSidebarCollapsed(true);
      }
      return next;
    });
  };

  const handleToggleFullscreen = () => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(() => {});
      setIsFullscreen(true);
      setIsSidebarCollapsed(true);
    } else {
      document.exitFullscreen().catch(() => {});
      setIsFullscreen(false);
    }
  };

  const handleVoteThread = (threadId: string, delta: number) => {
    setThreads((prev) =>
      prev.map((t) => (t.id === threadId ? { ...t, score: (t.score || 1) + delta } : t))
    );
  };

  const handleAddThreadReply = (threadId: string, replyText: string) => {
    const newReply = {
      id: `rep_${Date.now()}`,
      authorId: 'user',
      authorName: userName,
      authorAvatar: userName.charAt(0).toUpperCase(),
      text: replyText,
      createdAt: Date.now()
    };
    setThreads((prev) =>
      prev.map((t) =>
        t.id === threadId
          ? {
              ...t,
              replies: [...(t.replies || []), newReply],
              updatedAt: Date.now()
            }
          : t
      )
    );
  };

  const handleResolveDecision = (decisionId: string, option: string) => {
    setDecisions((prev) =>
      prev.map((d) =>
        d.id === decisionId ? { ...d, selectedOption: option, status: 'resolved' } : d
      )
    );
  };

  const activeAgent = agents.find((a) => a.id === activeAgentId) || null;

  return (
    <div
      className="flex h-screen w-screen overflow-hidden select-none relative"
      style={{
        backgroundColor: 'var(--bg)',
        color: 'var(--ink)'
      }}
    >
      {/* Floating Ambient Theme Decorations matching ruxt-handoff-v21 */}
      <ThemeDecor />

      {/* Left Navigation Sidebar */}
      <Sidebar
        agents={agents}
        activeAgentId={activeAgentId}
        onSelectAgent={(id) => {
          setActiveAgentId(id);
          setCurrentView('workspace');
        }}
        currentView={currentView}
        onSelectView={(view) => {
          setCurrentView(view);
          if (view !== 'forum') setSelectedThreadId(null);
        }}
        isCollapsed={isSidebarCollapsed}
        onToggleCollapse={() => setIsSidebarCollapsed((prev) => !prev)}
        onOpenSettings={() => setCurrentView('settings')}
        messageCount={messages.length}
        threadCount={threads.length}
        userName={userName}
      />

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col h-full overflow-hidden relative z-10">
        {/* Top Header Bar */}
        <Header
          agent={activeAgent}
          currentView={currentView}
          isCanvasOpen={isCanvasOpen}
          onToggleCanvas={handleToggleCanvas}
          onOpenCommand={() => setIsCommandOpen(true)}
          activeProvider={activeProvider}
          onOpenProviderModal={() => setIsProviderModalOpen(true)}
          onOpenSettings={() => setIsSettingsModalOpen(true)}
          isSidebarCollapsed={isSidebarCollapsed}
          onToggleSidebar={() => setIsSidebarCollapsed((prev) => !prev)}
          isFullscreen={isFullscreen}
          onToggleFullscreen={handleToggleFullscreen}
        />

        {/* Dynamic Views */}
        {currentView === 'home' ? (
          <HomeView
            agents={agents}
            threads={threads}
            recentMessages={messages}
            onSelectAgent={(id) => {
              setActiveAgentId(id);
              setCurrentView('workspace');
            }}
            onOpenMessages={() => setCurrentView('messages')}
            onOpenThreads={() => {
              setCurrentView('forum');
              setSelectedThreadId(null);
            }}
            onOpenStudio={() => setCurrentView('studio')}
            onOpenThreadDetail={(thId) => {
              setSelectedThreadId(thId);
              setCurrentView('forum');
            }}
          />
        ) : currentView === 'agents' ? (
          <AgentsView
            agents={agents}
            onSelectAgent={(id) => {
              setActiveAgentId(id);
              setCurrentView('workspace');
            }}
            onToggleInstall={handleToggleInstall}
            onOpenStudio={() => setCurrentView('studio')}
          />
        ) : currentView === 'messages' ? (
          <MessagesView
            agents={agents}
            activeAgentId={activeAgentId}
            onSelectAgent={setActiveAgentId}
            messages={messages}
            onSendMessage={handleSendMessage}
            isLoading={isLoading}
          />
        ) : currentView === 'forum' ? (
          <ThreadsView
            threads={threads}
            decisions={decisions}
            activeHub={activeForumHub}
            onSelectHub={setActiveForumHub}
            onVoteThread={handleVoteThread}
            selectedThreadId={selectedThreadId}
            onSelectThread={setSelectedThreadId}
            onAddReply={handleAddThreadReply}
            onResolveDecision={handleResolveDecision}
          />
        ) : currentView === 'settings' ? (
          <SettingsView
            onProfileUpdated={(name) => setUserName(name)}
            activities={activities}
          />
        ) : currentView === 'studio' ? (
          <AgentStudio
            onAgentCreated={(newAgent) => {
              setAgents((prev) => [...prev, newAgent]);
              setActiveAgentId(newAgent.id);
              setCurrentView('workspace');
            }}
            onClose={() => setCurrentView('workspace')}
          />
        ) : currentView === 'marketplace' ? (
          <MarketplaceView
            agents={agents}
            onToggleInstall={handleToggleInstall}
            onOpenStudio={() => setCurrentView('studio')}
          />
        ) : (
          /* Workspace View: Dual-Split Layout (Interactive Canvas + Chat) */
          <div className="flex-1 flex flex-col h-full overflow-hidden">
            {/* Top Agent Bar with Modern Back Button */}
            <div
              className="px-4 py-2 border-b flex items-center justify-between shrink-0"
              style={{
                backgroundColor: 'var(--surface)',
                borderColor: 'var(--line)'
              }}
            >
              <button
                type="button"
                onClick={() => setCurrentView('home')}
                className="back-modern"
                title="Return to agent shelf"
              >
                <span className="back-chevron">
                  <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                    <path
                      d="M10 3.5 5.5 8 10 12.5"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    />
                  </svg>
                </span>
                <span className="back-copy">
                  <strong>Close agent</strong>
                  <small>Back to shelf</small>
                </span>
              </button>

              {activeAgent && (
                <div className="flex items-center space-x-3">
                  <div
                    className="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm shadow-sm"
                    style={{ backgroundColor: 'var(--accent-soft)', color: 'var(--accent)' }}
                  >
                    {activeAgent.avatar}
                  </div>
                  <div>
                    <div className="text-xs font-bold" style={{ color: 'var(--ink)' }}>
                      {activeAgent.name}
                    </div>
                    <div className="text-[10px]" style={{ color: 'var(--muted)' }}>
                      {activeAgent.role}
                    </div>
                  </div>
                  <span
                    className="text-[10px] font-mono px-2 py-0.5 rounded-full font-semibold"
                    style={{ backgroundColor: 'var(--accent-soft)', color: 'var(--accent)' }}
                  >
                    ● READY
                  </span>
                </div>
              )}
            </div>

            {/* Split View: Dashboard on LEFT side + Chat on RIGHT side */}
            <div className="flex-1 flex overflow-hidden">
              {/* Left Column: Interactive Micro-Frontend Canvas */}
              {isCanvasOpen && activeAgent?.canvasType !== 'none' && (
                <div
                  className="flex-1 flex flex-col h-full p-2.5 sm:p-3 lg:p-4 overflow-hidden border-r min-w-0"
                  style={{
                    backgroundColor: 'color-mix(in srgb, var(--bg) 70%, var(--surface))',
                    borderColor: 'var(--line)'
                  }}
                >
                  {activeAgent?.canvasType === 'chess' && (
                    <ChessCanvas onMoveMade={(move) => handleSendMessage(move)} />
                  )}
                  {activeAgent?.canvasType === 'datagrid' && <DataGridCanvas />}
                  {activeAgent?.canvasType === 'audit' && <AuditCanvas />}
                </div>
              )}

              {/* Right Column: Chat Column */}
              <div
                className={`flex flex-col h-full transition-all duration-300 ${
                  isCanvasOpen && activeAgent?.canvasType !== 'none'
                    ? 'w-full lg:w-[360px] xl:w-[390px] 2xl:w-[420px] shrink-0'
                    : 'flex-1 w-full'
                }`}
              >
                {activeAgent && (
                  <ChatArea
                    agent={activeAgent}
                    messages={messages}
                    streamingToken={streamingToken}
                    streamingTaskId={currentTask?.id || null}
                    onSendMessage={handleSendMessage}
                    isLoading={isLoading}
                    chatWidthClass={chatWidthClass}
                    onClearHistory={() => setMessages([])}
                  />
                )}
              </div>
            </div>

            {/* Real-time Task Pipeline Visualizer Bar */}
            <TaskPipeline task={currentTask} />
          </div>
        )}
      </div>

      {/* Quick Switch Command Modal (Ctrl+K) */}
      {isCommandOpen && (
        <div
          className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-start justify-center pt-24"
          onClick={() => setIsCommandOpen(false)}
        >
          <div
            className="w-full max-w-md rounded-2xl shadow-2xl p-4 space-y-3 border"
            style={{
              backgroundColor: 'var(--surface)',
              borderColor: 'var(--line)',
              color: 'var(--ink)'
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <div className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--muted)' }}>
              Quick Agent Switcher
            </div>
            <div className="space-y-1 max-h-72 overflow-y-auto">
              {agents.map((ag) => (
                <button
                  key={ag.id}
                  onClick={() => {
                    setActiveAgentId(ag.id);
                    setCurrentView('workspace');
                    setIsCommandOpen(false);
                  }}
                  className="w-full flex items-center space-x-3 p-2.5 rounded-xl hover:bg-black/5 dark:hover:bg-white/5 text-left transition"
                >
                  <span className="text-xl">{ag.avatar}</span>
                  <div>
                    <div className="text-xs font-bold" style={{ color: 'var(--ink)' }}>{ag.name}</div>
                    <div className="text-[10px]" style={{ color: 'var(--muted)' }}>{ag.role}</div>
                  </div>
                </button>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* AI Provider & Engine Settings Modal */}
      <ProviderModal
        isOpen={isProviderModalOpen}
        onClose={() => setIsProviderModalOpen(false)}
        activeProvider={activeProvider}
        onProviderChanged={(p) => setActiveProvider(p)}
      />

      {/* Workspace Settings Modal (Ctrl+,) */}
      <SettingsModal
        isOpen={isSettingsModalOpen}
        onClose={() => setIsSettingsModalOpen(false)}
        activeAgent={activeAgent}
        activeProvider={activeProvider}
        onProviderChanged={(p) => setActiveProvider(p)}
        onChatCleared={() => {
          if (activeAgentId) {
            fetch(`/api/chat/${activeAgentId}`)
              .then((res) => res.json())
              .then((data) => setMessages(data.messages || []));
          }
        }}
        chatWidthPreference={chatWidthPreference}
        onChangeChatWidthPreference={(w) => {
          setChatWidthPreference(w);
          localStorage.setItem('ruxt_chat_width', w);
        }}
      />
    </div>
  );
}

export default App;
