import React from 'react';
import { Agent } from '../../types';
import { Columns, Terminal, Zap, Maximize2, Minimize2, PanelLeftOpen, PanelLeftClose, Settings as SettingsIcon } from 'lucide-react';

interface HeaderProps {
  agent: Agent | null;
  currentView: 'home' | 'agents' | 'messages' | 'forum' | 'settings' | 'workspace' | 'studio' | 'marketplace';
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
  onToggleFullscreen
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
      case 'workspace':
        return agent ? agent.name : 'Workspace';
      default:
        return 'Home';
    }
  };

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
        {/* Antigravity / Tools Connection Status */}
        <div
          className="hidden lg:flex items-center space-x-1.5 px-2.5 py-1 rounded-full border text-[11px] font-mono select-none"
          style={{
            borderColor: 'var(--line)',
            backgroundColor: 'color-mix(in srgb, var(--bg) 70%, var(--surface))',
            color: 'var(--muted)'
          }}
        >
          <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
          <span>Bridge Ready</span>
        </div>

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

        {/* AI Engine & Model Provider Selector Badge */}
        <button
          onClick={onOpenProviderModal}
          className="flex items-center space-x-1.5 px-2.5 py-1 rounded-full border text-[11px] font-medium transition cursor-pointer shadow-xs"
          style={{
            borderColor: 'color-mix(in srgb, var(--accent) 35%, var(--line))',
            backgroundColor: 'var(--accent-soft)',
            color: 'var(--accent)'
          }}
          title="Configure AI engines (Simulator, Gemini, Ollama, Outbound Tunnel)"
        >
          <Zap className="w-3 h-3 fill-current" />
          <span className="capitalize hidden sm:inline font-semibold">
            {activeProvider === 'simulator'
              ? 'Simulated Engine'
              : activeProvider === 'gemini'
              ? 'Gemini Pro'
              : activeProvider === 'openai'
              ? 'OpenAI GPT-4o'
              : activeProvider === 'ollama'
              ? 'Local Ollama'
              : activeProvider}
          </span>
          <span className="sm:hidden font-mono uppercase text-[10px]">{activeProvider.slice(0, 3)}</span>
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
      </div>
    </header>
  );
};
