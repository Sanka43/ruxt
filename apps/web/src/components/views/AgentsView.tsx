import React, { useState } from 'react';
import { Agent } from '../../types';
import { Search, Shield, Columns, Plus } from 'lucide-react';

interface AgentsViewProps {
  agents: Agent[];
  onSelectAgent: (id: string) => void;
  onToggleInstall: (id: string) => void;
  onOpenStudio: () => void;
}

export const AgentsView: React.FC<AgentsViewProps> = ({
  agents,
  onSelectAgent,
  onToggleInstall,
  onOpenStudio
}) => {
  const [searchQuery, setSearchQuery] = useState('');

  const filtered = agents.filter(
    (a) =>
      a.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      a.role.toLowerCase().includes(searchQuery.toLowerCase()) ||
      a.description.toLowerCase().includes(searchQuery.toLowerCase())
  );

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
    <div className="flex-1 overflow-y-auto p-6 md:p-8 space-y-6 max-w-6xl mx-auto w-full">
      {/* Page Header */}
      <div
        className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-4 border-b"
        style={{ borderColor: 'var(--line)' }}
      >
        <div>
          <span className="eyebrow">AGENT LIBRARY</span>
          <h1 className="text-2xl sm:text-4xl font-bold tracking-tight mt-1" style={{ color: 'var(--ink)' }}>
            AI <em className="not-italic text-[#1967ff]">agents.</em>
          </h1>
          <p className="text-xs sm:text-sm mt-1" style={{ color: 'var(--muted)' }}>
            Every active agent, in one place.
          </p>
        </div>

        <div className="flex items-center space-x-3">
          <button
            onClick={onOpenStudio}
            className="inline-flex items-center space-x-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-white shadow-sm transition hover:opacity-90"
            style={{ backgroundColor: 'var(--ruxt)' }}
          >
            <Plus className="w-3.5 h-3.5" />
            <span>＋ Import agent</span>
          </button>
        </div>
      </div>

      {/* Search Bar */}
      <div className="relative max-w-md">
        <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input
          type="text"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          placeholder="Filter agents by name, skill, or role..."
          className="w-full pl-9 pr-4 py-2 text-xs rounded-xl border outline-none transition"
          style={{
            borderColor: 'var(--line)',
            backgroundColor: 'var(--surface)',
            color: 'var(--ink)'
          }}
        />
      </div>

      {/* Agent Cards Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {filtered.map((agent) => {
          const photoUrl = getAgentPhoto(agent);

          return (
            <article
              key={agent.id}
              className="agent-card flex-col items-start justify-between"
              style={{ minHeight: '190px' }}
            >
              <div className="flex items-start space-x-3.5 w-full">
                <div
                  className="w-14 h-14 rounded-xl flex items-center justify-center font-bold text-2xl shrink-0 overflow-hidden shadow-sm"
                  style={{ backgroundColor: 'var(--accent-soft)', color: 'var(--accent)' }}
                >
                  {photoUrl ? (
                    <img src={photoUrl} alt={agent.name} className="w-full h-full object-cover" />
                  ) : (
                    <span>{agent.avatar}</span>
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center space-x-1.5">
                    <span className="eyebrow truncate">{agent.role}</span>
                  </div>
                  <h3 className="text-sm font-bold truncate mt-0.5" style={{ color: 'var(--ink)' }}>
                    {agent.name}
                  </h3>
                  <p className="text-xs line-clamp-2 mt-1" style={{ color: 'var(--muted)' }}>
                    {agent.description}
                  </p>
                </div>
              </div>

              {/* Tags and Badges */}
              <div className="w-full pt-3 mt-3 border-t flex flex-wrap items-center justify-between gap-2"
                style={{ borderColor: 'var(--line)' }}
              >
                <div className="flex flex-wrap gap-1">
                  {agent.strictGuardrails && (
                    <span
                      className="text-[10px] px-1.5 py-0.5 rounded font-medium flex items-center space-x-1"
                      style={{ backgroundColor: 'var(--accent-soft)', color: 'var(--accent)' }}
                    >
                      <Shield className="w-2.5 h-2.5" />
                      <span>Guardrails</span>
                    </span>
                  )}
                  {agent.canvasType !== 'none' && (
                    <span
                      className="text-[10px] px-1.5 py-0.5 rounded font-medium flex items-center space-x-1 text-[#1967ff]"
                      style={{ backgroundColor: 'color-mix(in srgb, #1967ff 12%, transparent)' }}
                    >
                      <Columns className="w-2.5 h-2.5" />
                      <span>Interactive</span>
                    </span>
                  )}
                </div>

                <div className="flex items-center space-x-2">
                  <button
                    onClick={() => onToggleInstall(agent.id)}
                    className="text-[11px] px-2.5 py-1 rounded-lg border font-medium transition hover:opacity-85"
                    style={{
                      borderColor: 'var(--line)',
                      backgroundColor: 'color-mix(in srgb, var(--bg) 60%, var(--surface))',
                      color: 'var(--muted)'
                    }}
                  >
                    {agent.isInstalled ? 'Installed' : 'Install'}
                  </button>
                  <button
                    onClick={() => onSelectAgent(agent.id)}
                    className="text-[11px] px-3 py-1 rounded-lg font-semibold text-white shadow-sm transition hover:opacity-90"
                    style={{ backgroundColor: 'var(--ruxt)' }}
                  >
                    Open
                  </button>
                </div>
              </div>
            </article>
          );
        })}
      </div>
    </div>
  );
};
