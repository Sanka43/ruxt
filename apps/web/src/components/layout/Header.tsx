import React from 'react';
import { Agent, User } from '../../types';
import {
  Columns,
  Terminal,
  Zap,
  Maximize2,
  Minimize2,
  PanelLeftOpen,
  PanelLeftClose,
  Settings as SettingsIcon,
  LogOut,
  Brain
} from 'lucide-react';

interface HeaderProps {
  agent: Agent | null;
  currentView: 'home' | 'agents' | 'messages' | 'forum' | 'settings' | 'workspace' | 'studio' | 'marketplace' | 'users';
  isCanvasOpen: boolean;
  onToggleCanvas: () => void;
  onOpenCommand: () => void;
  activeProvider: string;
  onOpenProviderModal: () => void;
  onOpenSettings?: () => void;
  isSidebarCollapsed?: boolean;
  onToggleSidebar?: () => void;
  isFullscreen?: boolean;
  onToggleFullscreen?: () => void;
  currentUser?: User | null;
  onLogout?: () => void;
  isTunnelConnected?: boolean;
  tunnelInfo?: { clientCount?: number; tools?: any };
}

export const Header: React.FC<HeaderProps> = ({
  agent,
  currentView,
  isCanvasOpen,
  onToggleCanvas,
  onOpenCommand,
  activeProvider,
  onOpenProviderModal,
  onOpenSettings,
  isSidebarCollapsed,
  onToggleSidebar,
  isFullscreen,
  onToggleFullscreen,
  currentUser,
  onLogout,
  isTunnelConnected = false,
  tunnelInfo = {}
}) => {
  const getPageTitle = () => {
    switch (currentView) {
      case 'home':
        return 'Home';
      case 'agents':
        return 'AI agents';
      case 'messages':
        return 'Messages';
      case 'forum':
        return 'Threads';
      case 'settings':
        return 'Settings';
      case 'studio':
        return 'Agent Studio';
      case 'marketplace':
        return 'Marketplace';
      case 'users':
        return 'User Management & Access';
      case 'workspace':
        return agent ? agent.name : 'Workspace';
      default:
        return 'Home';
    }
  };

  const getBrainInfo = () => {
    switch (activeProvider) {
      case 'gemini':
        return {
          title: 'Google Gemini Pro',
          eyebrow: 'NEURAL CLOUD',
          color: '#1967ff',
          bg: 'rgba(25, 103, 255, 0.12)',
          border: 'rgba(25, 103, 255, 0.35)'
        };
      case 'openai':
        return {
          title: 'OpenAI GPT-4o',
          eyebrow: 'NEURAL CLOUD',
          color: '#10a37f',
          bg: 'rgba(16, 163, 127, 0.12)',
          border: 'rgba(16, 163, 127, 0.35)'
        };
      case 'ollama':
        return {
          title: 'Local Ollama',
          eyebrow: 'NEURAL LOCAL',
          color: '#f59e0b',
          bg: 'rgba(245, 158, 11, 0.12)',
          border: 'rgba(245, 158, 11, 0.35)'
        };
      case 'antigravity':
        return {
          title: 'Antigravity IDE',
          eyebrow: 'LOCAL AGENT BRAIN',
          color: '#8b5cf6',
          bg: 'rgba(139, 92, 246, 0.12)',
          border: 'rgba(139, 92, 246, 0.35)'
        };
      case 'simulator':
      default:
        return {
          title: 'Simulated Engine',
          eyebrow: 'ZERO-SETUP OFFLINE',
          color: '#6366f1',
          bg: 'rgba(99, 102, 241, 0.12)',
          border: 'rgba(99, 102, 241, 0.35)'
        };
    }
  };

  const brain = getBrainInfo();

  return (
    <header
      style={{
        backgroundColor: 'var(--surface)',
        borderColor: 'var(--line)',
        color: 'var(--ink)'
      }}
      className="h-14 border-b px-4 flex items-center justify-between select-none shrink-0 z-10 transition-colors shadow-2xs"
    >
      {/* Breadcrumb Navigation & Collapse Toggle */}
      <div className="flex items-center space-x-3 min-w-0">
        {onToggleSidebar && (
          <button
            onClick={onToggleSidebar}
            className="p-1.5 rounded-lg border hover:opacity-80 transition cursor-pointer"
            style={{
              borderColor: 'var(--line)',
              backgroundColor: 'color-mix(in srgb, var(--surface) 90%, var(--bg))'
            }}
            title={isSidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'}
          >
            {isSidebarCollapsed ? (
              <PanelLeftOpen className="w-4 h-4 text-[#1967ff]" />
            ) : (
              <PanelLeftClose className="w-4 h-4 text-slate-500" />
            )}
          </button>
        )}

        <div className="text-xs flex items-center space-x-1.5 truncate">
          <span className="crumb-brand font-bold text-[#1967ff] tracking-tight">ruxt</span>
          <span className="text-slate-400">/</span>
          <span className="text-slate-500">Workspace</span>
          <span className="text-slate-400">/</span>
          <strong className="font-semibold truncate" style={{ color: 'var(--ink)' }}>
            {getPageTitle()}
          </strong>
        </div>

        {currentView === 'workspace' && agent && (
          <div className="hidden sm:flex items-center space-x-2 ml-2 pl-2 border-l" style={{ borderColor: 'var(--line)' }}>
            <span
              className="text-xs px-2 py-0.5 rounded-full font-medium"
              style={{ backgroundColor: 'var(--accent-soft)', color: 'var(--accent)' }}
            >
              {agent.role}
            </span>
          </div>
        )}
      </div>

      {/* Right Tools & Status Bar */}
      <div className="flex items-center space-x-2 sm:space-x-2.5">
        {/* Main Connected Brain Badge */}
        <button
          onClick={onOpenProviderModal}
          className="flex items-center space-x-2 px-2.5 sm:px-3 py-1.5 rounded-xl border transition cursor-pointer shadow-xs hover:opacity-95 active:scale-98"
          style={{
            borderColor: brain.border,
            backgroundColor: brain.bg,
            color: 'var(--ink)'
          }}
          title="Click to view or switch connected AI Brain & Model Providers"
        >
          <div className="relative flex items-center justify-center">
            <Brain className="w-4 h-4 shrink-0" style={{ color: brain.color }} />
            <span
              className="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full border border-white dark:border-black animate-pulse"
              style={{ backgroundColor: brain.color }}
            />
          </div>
          <div className="flex flex-col text-left leading-none space-y-0.5">
            <div className="flex items-center space-x-1">
              <span className="text-[9px] font-mono tracking-wider font-bold uppercase" style={{ color: brain.color }}>
                MAIN BRAIN
              </span>
              <span className="text-[9px] opacity-40 hidden sm:inline">•</span>
              <span className="text-[9px] opacity-75 font-mono hidden sm:inline uppercase" style={{ color: 'var(--muted)' }}>
                {brain.eyebrow}
              </span>
            </div>
            <span className="text-xs font-bold truncate max-w-[105px] sm:max-w-[150px]">
              {brain.title}
            </span>
          </div>
        </button>

        {/* IDE Bridge Status Indicator */}
        {isTunnelConnected ? (
          <div
            onClick={onOpenProviderModal}
            className="hidden sm:flex items-center space-x-1.5 px-2.5 py-1.5 rounded-xl border text-[11px] font-mono cursor-pointer select-none transition hover:opacity-90 shadow-xs"
            style={{
              borderColor: '#10b98140',
              backgroundColor: '#10b98115',
              color: '#10b981'
            }}
            title="Local IDE Outbound WebSocket Tunnel is Live (Antigravity / Cursor / CLI)"
          >
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
            <span className="font-semibold">IDE: Live</span>
          </div>
        ) : (
          <button
            onClick={onOpenProviderModal}
            className="hidden lg:flex items-center space-x-1.5 px-2.5 py-1.5 rounded-xl border text-[11px] font-mono select-none hover:opacity-85 transition cursor-pointer"
            style={{
              borderColor: 'var(--line)',
              backgroundColor: 'color-mix(in srgb, var(--bg) 70%, var(--surface))',
              color: 'var(--muted)'
            }}
            title="Click to connect Local IDE Bridge via Outbound Tunnel"
          >
            <span className="w-1.5 h-1.5 rounded-full bg-slate-400" />
            <span>IDE: Standby</span>
          </button>
        )}

        {/* Quick Command switcher pill (Ctrl+K) */}
        <button
          onClick={onOpenCommand}
          className="hidden md:flex items-center space-x-1.5 px-2.5 py-1.5 rounded-lg border text-xs transition hover:opacity-85 cursor-pointer shadow-xs"
          style={{
            borderColor: 'var(--line)',
            backgroundColor: 'color-mix(in srgb, var(--surface) 95%, var(--bg))',
            color: 'var(--muted)'
          }}
        >
          <Terminal className="w-3.5 h-3.5" />
          <span className="hidden xl:inline">Switch</span>
          <kbd
            className="text-[10px] px-1 rounded font-mono border"
            style={{ borderColor: 'var(--line)' }}
          >
            Ctrl+K
          </kbd>
        </button>

        {/* Interactive Canvas Toggle (if active in workspace and supports canvas) */}
        {currentView === 'workspace' && agent && agent.canvasType !== 'none' && (
          <button
            onClick={onToggleCanvas}
            className="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer shadow-xs"
            style={
              isCanvasOpen
                ? {
                    backgroundColor: 'var(--ruxt)',
                    color: '#fff'
                  }
                : {
                    border: '1px solid var(--line)',
                    backgroundColor: 'var(--surface)',
                    color: 'var(--ink)'
                  }
            }
          >
            <Columns className="w-3.5 h-3.5" />
            <span>{isCanvasOpen ? 'Canvas Active' : 'Open Canvas'}</span>
          </button>
        )}

        {/* Settings Toggle (Ctrl+,) */}
        {onOpenSettings && (
          <button
            onClick={onOpenSettings}
            className="p-1.5 rounded-lg border hover:opacity-85 transition cursor-pointer"
            style={{
              borderColor: 'var(--line)',
              backgroundColor: 'color-mix(in srgb, var(--surface) 90%, var(--bg))',
              color: 'var(--muted)'
            }}
            title="Settings (Ctrl+,)"
          >
            <SettingsIcon className="w-4 h-4" />
          </button>
        )}

        {/* Fullscreen Toggle */}
        {onToggleFullscreen && (
          <button
            onClick={onToggleFullscreen}
            className="p-1.5 rounded-lg border hover:opacity-85 transition cursor-pointer"
            style={{
              borderColor: 'var(--line)',
              backgroundColor: 'color-mix(in srgb, var(--surface) 90%, var(--bg))',
              color: 'var(--muted)'
            }}
            title={isFullscreen ? 'Exit Full Screen' : 'Full Screen Mode'}
          >
            {isFullscreen ? <Minimize2 className="w-4 h-4" /> : <Maximize2 className="w-4 h-4" />}
          </button>
        )}

        {/* User Profile & Logout */}
        {currentUser && (
          <div className="flex items-center space-x-1.5 pl-1.5 sm:pl-2 border-l" style={{ borderColor: 'var(--line)' }}>
            <div
              className="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs shrink-0 select-none shadow-xs border"
              style={{
                backgroundColor: 'var(--accent-soft)',
                borderColor: 'var(--line)',
                color: 'var(--accent)'
              }}
              title={`${currentUser.name} (${currentUser.email})`}
            >
              {currentUser.avatar || currentUser.name.charAt(0).toUpperCase()}
            </div>
            <span className="hidden xl:inline text-xs font-semibold max-w-[110px] truncate" style={{ color: 'var(--ink)' }}>
              {currentUser.name}
            </span>
            {onLogout && (
              <button
                onClick={onLogout}
                className="p-1.5 rounded-lg border hover:opacity-85 transition cursor-pointer text-rose-500"
                style={{
                  borderColor: 'var(--line)',
                  backgroundColor: 'color-mix(in srgb, var(--surface) 90%, var(--bg))'
                }}
                title="Log Out"
              >
                <LogOut className="w-3.5 h-3.5" />
              </button>
            )}
          </div>
        )}
      </div>
    </header>
  );
};
