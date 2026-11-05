import React from 'react';
import { Agent } from '../../types';
import { Sparkles, Store, PanelLeftClose, PanelLeftOpen, Settings as SettingsIcon, Activity, Zap } from 'lucide-react';

interface SidebarProps {
  agents: Agent[];
  activeAgentId: string;
  onSelectAgent: (id: string) => void;
  currentView: 'home' | 'agents' | 'messages' | 'forum' | 'settings' | 'workspace' | 'studio' | 'marketplace';
  onSelectView: (view: 'home' | 'agents' | 'messages' | 'forum' | 'settings' | 'workspace' | 'studio' | 'marketplace') => void;
  isCollapsed?: boolean;
  onToggleCollapse?: () => void;
  onOpenSettings?: () => void;
  messageCount?: number;
  threadCount?: number;
  userName?: string;
}

export const Sidebar: React.FC<SidebarProps> = ({
  agents,
  activeAgentId,
  onSelectAgent,
  currentView,
  onSelectView,
  isCollapsed = false,
  onToggleCollapse,
  onOpenSettings,
  messageCount = 0,
  threadCount = 0,
  userName = 'Kosala'
}) => {
  const installedAgents = agents.filter((a) => a.isInstalled);

  const handleNavView = (view: 'home' | 'agents' | 'messages' | 'forum' | 'settings' | 'workspace' | 'studio' | 'marketplace') => {
    onSelectView(view);
    if (window.innerWidth < 768 && onToggleCollapse && !isCollapsed) {
      onToggleCollapse();
    }
  };

  const handleNavAgent = (id: string) => {
    onSelectAgent(id);
    if (window.innerWidth < 768 && onToggleCollapse && !isCollapsed) {
      onToggleCollapse();
    }
  };

  return (
    <>
      {/* Mobile Backdrop */}
      {!isCollapsed && (
        <div
          onClick={onToggleCollapse}
          className="fixed inset-0 bg-black/60 z-30 md:hidden backdrop-blur-xs transition-opacity"
        />
      )}

      <aside
        style={{
          backgroundColor: 'var(--sidebar)',
          color: 'var(--sidebar-text)',
          borderColor: 'var(--sidebar-line)'
        }}
        className={`${
          isCollapsed
            ? 'hidden md:flex md:w-16'
            : 'fixed inset-y-0 left-0 w-64 z-40 md:relative md:w-60 md:z-20 shadow-2xl md:shadow-none'
        } border-r flex flex-col justify-between select-none transition-all duration-300 shrink-0 h-full`}
      >
      {/* Brand & Logo Header */}
      <div
        className={`p-3 border-b flex items-center ${isCollapsed ? 'justify-center' : 'justify-between'}`}
        style={{ borderColor: 'var(--sidebar-line)' }}
      >
        {!isCollapsed ? (
          <div className="flex flex-col items-center justify-center w-full px-1 py-0.5 space-y-1">
            <img
              src="/ruxt-logo.png"
              alt="ruxt"
              className="ruxt-lockup cursor-pointer hover:opacity-95 transition shadow-xs"
              onClick={() => handleNavView('home')}
            />
            <div className="flex items-center space-x-1 text-[9px] font-mono tracking-wider text-emerald-400">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
              <span>SINGLE-PORT :1234</span>
            </div>
          </div>
        ) : (
          <div
            onClick={() => handleNavView('home')}
            className="w-10 h-10 rounded-xl flex items-center justify-center text-white cursor-pointer font-bold text-lg shadow-sm"
            style={{ backgroundColor: 'var(--ruxt)' }}
            title="RuXt Next-Gen"
          >
            ✦
          </div>
        )}

        {onToggleCollapse && !isCollapsed && (
          <button
            onClick={onToggleCollapse}
            className="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition"
            title="Collapse Sidebar"
          >
            <PanelLeftClose className="w-4 h-4" />
          </button>
        )}
      </div>

      {/* Main Navigation List */}
      <div className="flex-1 overflow-y-auto px-2 py-3 space-y-4">
        {/* Workspace Nav Items */}
        <div className="space-y-1">
          {!isCollapsed && (
            <span className="eyebrow px-2 mb-1.5" style={{ color: 'var(--sidebar-muted)' }}>
              WORKSPACE
            </span>
          )}

          {/* 1. Home */}
          <button
            onClick={() => handleNavView('home')}
            className={`w-full flex items-center ${
              isCollapsed ? 'justify-center p-2.5' : 'justify-between px-3 py-2'
            } rounded-lg text-xs font-semibold transition ${
              currentView === 'home'
                ? 'text-white shadow-xs'
                : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
            }`}
            style={
              currentView === 'home'
                ? { backgroundColor: 'var(--sidebar-active)', color: '#fff' }
                : {}
            }
            title="Home"
          >
            <div className="flex items-center space-x-2.5">
              <span className="text-sm">⌂</span>
              {!isCollapsed && <span>Home</span>}
            </div>
          </button>

          {/* 2. AI Agents */}
          <button
            onClick={() => handleNavView('agents')}
            className={`w-full flex items-center ${
              isCollapsed ? 'justify-center p-2.5' : 'justify-between px-3 py-2'
            } rounded-lg text-xs font-semibold transition ${
              currentView === 'agents'
                ? 'text-white shadow-xs'
                : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
            }`}
            style={
              currentView === 'agents'
                ? { backgroundColor: 'var(--sidebar-active)', color: '#fff' }
                : {}
            }
            title="AI Agents"
          >
            <div className="flex items-center space-x-2.5">
              <span className="text-sm">✦</span>
              {!isCollapsed && <span>AI agents</span>}
            </div>
            {!isCollapsed && (
              <span
                className="px-1.5 py-0.2 rounded-full text-[10px] font-mono text-white font-bold"
                style={{ backgroundColor: 'var(--ruxt)' }}
              >
                {agents.length}
              </span>
            )}
          </button>

          {/* 3. Messages */}
          <button
            onClick={() => handleNavView('messages')}
            className={`w-full flex items-center ${
              isCollapsed ? 'justify-center p-2.5' : 'justify-between px-3 py-2'
            } rounded-lg text-xs font-semibold transition ${
              currentView === 'messages'
                ? 'text-white shadow-xs'
                : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
            }`}
            style={
              currentView === 'messages'
                ? { backgroundColor: 'var(--sidebar-active)', color: '#fff' }
                : {}
            }
            title="Messages"
          >
            <div className="flex items-center space-x-2.5">
              <span className="text-sm">✉</span>
              {!isCollapsed && <span>Messages</span>}
            </div>
            {!isCollapsed && (
              <span
                className="px-1.5 py-0.2 rounded-full text-[10px] font-mono text-white font-bold"
                style={{ backgroundColor: 'var(--ruxt)' }}
              >
                {messageCount}
              </span>
            )}
          </button>

          {/* 4. Threads / Forum */}
          <button
            onClick={() => handleNavView('forum')}
            className={`w-full flex items-center ${
              isCollapsed ? 'justify-center p-2.5' : 'justify-between px-3 py-2'
            } rounded-lg text-xs font-semibold transition ${
              currentView === 'forum'
                ? 'text-white shadow-xs'
                : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
            }`}
            style={
              currentView === 'forum'
                ? { backgroundColor: 'var(--sidebar-active)', color: '#fff' }
                : {}
            }
            title="Threads"
          >
            <div className="flex items-center space-x-2.5">
              <span className="text-sm">↗</span>
              {!isCollapsed && <span>Threads</span>}
            </div>
            {!isCollapsed && (
              <span
                className="px-1.5 py-0.2 rounded-full text-[10px] font-mono text-white font-bold"
                style={{ backgroundColor: 'var(--ruxt)' }}
              >
                {threadCount}
              </span>
            )}
          </button>

          {/* 5. Settings */}
          <button
            onClick={() => handleNavView('settings')}
            className={`w-full flex items-center ${
              isCollapsed ? 'justify-center p-2.5' : 'justify-between px-3 py-2'
            } rounded-lg text-xs font-semibold transition ${
              currentView === 'settings'
                ? 'text-white shadow-xs'
                : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
            }`}
            style={
              currentView === 'settings'
                ? { backgroundColor: 'var(--sidebar-active)', color: '#fff' }
                : {}
            }
            title="Settings"
          >
            <div className="flex items-center space-x-2.5">
              <SettingsIcon className="w-3.5 h-3.5" />
              {!isCollapsed && <span>Settings</span>}
            </div>
          </button>
        </div>

        {/* Active Copilots Shelf Shortcuts */}
        <div className="space-y-1">
          {!isCollapsed && (
            <span className="eyebrow px-2 mb-1.5" style={{ color: 'var(--sidebar-muted)' }}>
              ACTIVE COPILOTS ({installedAgents.length})
            </span>
          )}
          <div className="space-y-0.5">
            {installedAgents.map((agent) => {
              const isActive = currentView === 'workspace' && activeAgentId === agent.id;
              return (
                <button
                  key={agent.id}
                  onClick={() => {
                    handleNavAgent(agent.id);
                    handleNavView('workspace');
                  }}
                  title={`${agent.name} (${agent.role})`}
                  className={`w-full flex items-center ${
                    isCollapsed ? 'justify-center p-2' : 'space-x-2.5 px-2.5 py-1.5'
                  } rounded-lg text-left transition ${
                    isActive
                      ? 'text-white font-semibold shadow-xs'
                      : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
                  }`}
                  style={
                    isActive
                      ? {
                          backgroundColor: 'var(--sidebar-active)',
                          color: '#fff'
                        }
                      : {}
                  }
                >
                  <span className="text-base shrink-0">{agent.avatar}</span>
                  {!isCollapsed && (
                    <div className="flex-1 min-w-0">
                      <div className="text-xs font-medium truncate">{agent.name}</div>
                      <div className="text-[10px] truncate" style={{ color: 'var(--sidebar-muted)' }}>
                        {agent.role}
                      </div>
                    </div>
                  )}
                  {!isCollapsed && agent.canvasType !== 'none' && (
                    <span
                      className="w-1.5 h-1.5 rounded-full shrink-0"
                      style={{ backgroundColor: 'var(--accent)' }}
                      title="Micro-frontend Dashboard Active"
                    />
                  )}
                </button>
              );
            })}
          </div>
        </div>

        {/* Platform Hub */}
        <div className="space-y-1 pt-1">
          {!isCollapsed && (
            <span className="eyebrow px-2 mb-1.5" style={{ color: 'var(--sidebar-muted)' }}>
              PLATFORM HUB
            </span>
          )}
          <div className="space-y-0.5">
            <button
              onClick={() => handleNavView('studio')}
              title="Admin Agent Studio"
              className={`w-full flex items-center ${
                isCollapsed ? 'justify-center p-2' : 'space-x-2.5 px-3 py-1.5'
              } rounded-lg text-xs font-semibold transition ${
                currentView === 'studio'
                  ? 'text-white shadow-xs'
                  : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
              }`}
              style={
                currentView === 'studio'
                  ? { backgroundColor: 'var(--sidebar-active)', color: '#fff' }
                  : {}
              }
            >
              <Sparkles className="w-3.5 h-3.5 text-indigo-400 shrink-0" />
              {!isCollapsed && <span>Agent Studio</span>}
            </button>

            <button
              onClick={() => handleNavView('marketplace')}
              title="Agent Marketplace"
              className={`w-full flex items-center ${
                isCollapsed ? 'justify-center p-2' : 'space-x-2.5 px-3 py-1.5'
              } rounded-lg text-xs font-semibold transition ${
                currentView === 'marketplace'
                  ? 'text-white shadow-xs'
                  : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
              }`}
              style={
                currentView === 'marketplace'
                  ? { backgroundColor: 'var(--sidebar-active)', color: '#fff' }
                  : {}
              }
            >
              <Store className="w-3.5 h-3.5 text-emerald-400 shrink-0" />
              {!isCollapsed && <span>Marketplace</span>}
            </button>
          </div>
        </div>
      </div>

      {/* Footer Status & Profile matching ruxt-handoff-v21 */}
      <div
        className="p-2.5 border-t space-y-2"
        style={{ borderColor: 'var(--sidebar-line)', backgroundColor: 'var(--sidebar-panel)' }}
      >
        {isCollapsed ? (
          <button
            onClick={onToggleCollapse}
            className="w-full flex items-center justify-center p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition"
            title="Expand Sidebar"
          >
            <PanelLeftOpen className="w-4 h-4 text-indigo-400" />
          </button>
        ) : (
          <>
            {/* Local workspace runtime indicator */}
            <div
              className="rounded-lg p-2 border text-[11px] flex items-center justify-between"
              style={{
                borderColor: 'var(--sidebar-line)',
                color: 'var(--sidebar-text)'
              }}
            >
              <div className="flex items-center space-x-2 min-w-0">
                <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0" />
                <div className="min-w-0">
                  <div className="font-semibold text-xs leading-none">Local workspace</div>
                  <div className="text-[10px] mt-0.5" style={{ color: 'var(--sidebar-muted)' }}>
                    Agent runtime ready
                  </div>
                </div>
              </div>
              <span className="text-[9px] font-mono px-1.5 py-0.5 rounded border opacity-75"
                style={{ borderColor: 'var(--sidebar-line)' }}
              >
                v1.2.0
              </span>
            </div>

            {/* Profile */}
            <div
              onClick={() => handleNavView('settings')}
              className="rounded-lg p-1.5 flex items-center space-x-2.5 cursor-pointer hover:bg-white/5 transition"
              title="Open Profile Settings"
            >
              <div
                className="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs shrink-0 shadow-xs"
                style={{ backgroundColor: 'var(--ruxt-soft)', color: 'var(--ruxt)' }}
              >
                {userName.charAt(0).toUpperCase()}
              </div>
              <div className="min-w-0 flex-1">
                <div className="text-xs font-semibold leading-tight text-white truncate">{userName}</div>
                <div className="text-[10px] truncate" style={{ color: 'var(--sidebar-muted)' }}>
                  Personal workspace
                </div>
              </div>
            </div>
          </>
        )}
      </div>
    </aside>
    </>
  );
};
