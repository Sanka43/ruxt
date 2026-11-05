import React from 'react';
import { Agent, Thread, ChatMessage } from '../../types';
import { Plus, ArrowRight, MessageSquare, Sparkles } from 'lucide-react';

interface HomeViewProps {
  agents: Agent[];
  threads: Thread[];
  recentMessages: ChatMessage[];
  onSelectAgent: (agentId: string) => void;
  onOpenMessages: () => void;
  onOpenThreads: () => void;
  onOpenStudio: () => void;
  onOpenThreadDetail?: (threadId: string) => void;
}

export const HomeView: React.FC<HomeViewProps> = ({
  agents,
  threads,
  recentMessages,
  onSelectAgent,
  onOpenMessages,
  onOpenThreads,
  onOpenStudio,
  onOpenThreadDetail
}) => {
  const installedAgents = agents.filter((a) => a.isInstalled);

  const getAgentPhoto = (agent: Agent) => {
    if (agent.slug === 'numbers-agent' || agent.id.includes('numbers')) {
      return '/numbers-agent.png';
    }
    if (agent.slug === 'maya-brooks' || agent.id.includes('maya')) {
      return '/agent-avatars/maya-brooks.png';
    }
    if (agent.slug === 'noah-reed' || agent.id.includes('noah')) {
      return '/agent-avatars/noah-reed.png';
    }
    return null;
  };

  return (
    <div className="flex-1 overflow-y-auto p-6 md:p-8 space-y-8 max-w-6xl mx-auto w-full select-none">
      {/* Top Bar: Eyebrow + Title + Import Agent */}
      <div
        className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-4 border-b"
        style={{ borderColor: 'var(--line)' }}
      >
        <div>
          <span className="eyebrow">AGENTS AT A GLANCE</span>
          <h1
            className="text-2xl sm:text-3xl font-bold tracking-tight mt-1"
            style={{ color: 'var(--ink)' }}
          >
            Choose your copilot
          </h1>
          <p className="text-xs sm:text-sm mt-0.5" style={{ color: 'var(--muted)' }}>
            Domain-specialized AI copilots operating in your local environment.
          </p>
        </div>
        <button
          onClick={onOpenStudio}
          className="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold shadow-xs border transition hover:opacity-90 self-start sm:self-auto"
          style={{
            borderColor: 'var(--line)',
            backgroundColor: 'var(--surface)',
            color: 'var(--ink)'
          }}
        >
          <Plus className="w-3.5 h-3.5" />
          <span>Import agent</span>
        </button>
      </div>

      {/* Agents Shelf Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {installedAgents.map((agent) => {
          const photoUrl = getAgentPhoto(agent);

          return (
            <article
              key={agent.id}
              onClick={() => onSelectAgent(agent.id)}
              className="agent-card group hover:shadow-md"
            >
              {/* Agent Photo or Avatar */}
              <div
                className="w-16 h-16 rounded-xl flex items-center justify-center font-bold text-2xl shrink-0 overflow-hidden shadow-xs border"
                style={{
                  backgroundColor: 'var(--accent-soft)',
                  borderColor: 'var(--line)',
                  color: 'var(--accent)'
                }}
              >
                {photoUrl ? (
                  <img src={photoUrl} alt={agent.name} className="w-full h-full object-cover" />
                ) : (
                  <span>{agent.avatar}</span>
                )}
              </div>

              {/* Agent Details */}
              <div className="flex-1 min-w-0">
                <span className="eyebrow truncate">
                  {agent.role}
                </span>
                <h3 className="text-sm font-bold truncate mt-0.5" style={{ color: 'var(--ink)' }}>
                  {agent.name}
                </h3>
                <p className="text-xs line-clamp-2 mt-1" style={{ color: 'var(--muted)' }}>
                  {agent.description}
                </p>
                <div
                  className="flex items-center justify-between mt-2.5 pt-1.5 border-t"
                  style={{ borderColor: 'var(--line)' }}
                >
                  <span
                    className="text-[10px] font-mono font-semibold flex items-center space-x-1"
                    style={{ color: 'var(--accent)' }}
                  >
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                    <span>● READY</span>
                  </span>
                  <span className="text-[11px] text-[#1967ff] font-semibold group-hover:translate-x-0.5 transition flex items-center space-x-1">
                    <span>Open copilot</span>
                    <ArrowRight className="w-3 h-3" />
                  </span>
                </div>
              </div>
            </article>
          );
        })}
      </div>

      {/* The Room: Messages + Forum Preview */}
      <section
        className="rounded-2xl border p-5 sm:p-6 space-y-5 shadow-xs"
        style={{
          backgroundColor: 'var(--surface)',
          borderColor: 'var(--line)'
        }}
      >
        <div
          className="flex flex-col sm:flex-row sm:items-end justify-between gap-3 pb-3 border-b"
          style={{ borderColor: 'var(--line)' }}
        >
          <div>
            <span className="eyebrow">THE ROOM</span>
            <h2 className="text-lg sm:text-xl font-bold tracking-tight mt-0.5" style={{ color: 'var(--ink)' }}>
              Latest messages and threads
            </h2>
          </div>
          <div className="flex items-center space-x-4 text-xs font-semibold">
            <button
              onClick={onOpenMessages}
              className="text-[#1967ff] hover:underline flex items-center space-x-1"
            >
              <span>Open messages</span>
              <span>→</span>
            </button>
            <button
              onClick={onOpenThreads}
              className="text-[#1967ff] hover:underline flex items-center space-x-1"
            >
              <span>Open threads</span>
              <span>→</span>
            </button>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
          {/* Recent Messages */}
          <div className="space-y-3">
            <h3 className="text-xs font-bold uppercase tracking-wider" style={{ color: 'var(--muted)' }}>
              Recent messages
            </h3>
            <div className="space-y-2.5">
              {recentMessages.length > 0 ? (
                recentMessages.slice(-3).reverse().map((msg) => {
                  const ag = agents.find((a) => a.id === msg.agentId);
                  return (
                    <div
                      key={msg.id}
                      onClick={onOpenMessages}
                      className="room-item room-link cursor-pointer hover:shadow-xs"
                    >
                      <div
                        className="w-8 h-8 rounded-lg flex items-center justify-center text-sm shrink-0 font-bold border"
                        style={{
                          backgroundColor: 'var(--accent-soft)',
                          borderColor: 'var(--line)',
                          color: 'var(--accent)'
                        }}
                      >
                        {msg.role === 'user' ? 'K' : ag?.avatar || '✦'}
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between text-[11px]">
                          <strong style={{ color: 'var(--ink)' }}>
                            {msg.role === 'user' ? 'Kosala' : ag?.name || 'Agent'}
                          </strong>
                          <span className="text-[10px] font-mono" style={{ color: 'var(--muted)' }}>
                            {new Date(msg.createdAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                          </span>
                        </div>
                        <p className="text-xs line-clamp-2 mt-0.5" style={{ color: 'var(--muted)' }}>
                          {msg.content}
                        </p>
                      </div>
                    </div>
                  );
                })
              ) : (
                <div
                  className="p-5 border border-dashed rounded-xl text-xs text-center"
                  style={{ borderColor: 'var(--line)', color: 'var(--muted)' }}
                >
                  No messages yet. Open an agent to start chatting.
                </div>
              )}
            </div>
          </div>

          {/* Recent Threads */}
          <div className="space-y-3">
            <h3 className="text-xs font-bold uppercase tracking-wider" style={{ color: 'var(--muted)' }}>
              Recent threads
            </h3>
            <div className="space-y-2.5">
              {threads.length > 0 ? (
                threads.slice(0, 3).map((th) => {
                  return (
                    <article
                      key={th.id}
                      onClick={() => (onOpenThreadDetail ? onOpenThreadDetail(th.id) : onOpenThreads())}
                      className="room-item room-link cursor-pointer hover:shadow-xs"
                    >
                      <div
                        className="w-8 h-8 rounded-lg flex items-center justify-center text-sm shrink-0 font-bold border"
                        style={{
                          backgroundColor: 'var(--accent-soft)',
                          borderColor: 'var(--line)',
                          color: 'var(--accent)'
                        }}
                      >
                        {th.authorAvatar || '✦'}
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="text-xs font-bold truncate text-[#1967ff]">
                          {th.title}
                        </div>
                        <p className="text-xs line-clamp-2 mt-0.5" style={{ color: 'var(--muted)' }}>
                          {th.body}
                        </p>
                        <div
                          className="flex items-center space-x-2 text-[10px] mt-1"
                          style={{ color: 'var(--muted)' }}
                        >
                          <span className="font-semibold">{th.authorName || 'Agent'}</span>
                          <span>•</span>
                          <span className="capitalize">{th.hub} hub</span>
                          <span>•</span>
                          <span>{th.replies?.length || 0} comments</span>
                        </div>
                      </div>
                    </article>
                  );
                })
              ) : (
                <div
                  className="p-5 border border-dashed rounded-xl text-xs text-center"
                  style={{ borderColor: 'var(--line)', color: 'var(--muted)' }}
                >
                  Significant work updates from agents will appear here.
                </div>
              )}
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};
