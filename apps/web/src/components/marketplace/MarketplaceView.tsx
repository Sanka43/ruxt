import React from 'react';
import { Store, Plus, Check, Download, Sparkles } from 'lucide-react';
import { Agent } from '../../types';

interface MarketplaceViewProps {
  agents: Agent[];
  onToggleInstall: (agentId: string) => void;
  onOpenStudio?: () => void;
  isAdmin?: boolean;
}

export const MarketplaceView: React.FC<MarketplaceViewProps> = ({
  agents,
  onToggleInstall,
  onOpenStudio,
  isAdmin = false
}) => {
  return (
    <div className="flex-1 flex flex-col h-full bg-[#0B0F19] overflow-y-auto p-6 max-w-6xl mx-auto w-full space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between border-b border-white/5 pb-4">
        <div className="flex items-center space-x-3">
          <div className="w-10 h-10 rounded-xl bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
            <Store className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-white">Agent Marketplace</h2>
            <p className="text-xs text-slate-400">1-Click install specialized AI colleagues into your workspace</p>
          </div>
        </div>
        {isAdmin && onOpenStudio && (
          <button
            onClick={onOpenStudio}
            className="flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-semibold text-white shadow-lg transition cursor-pointer"
          >
            <Sparkles className="w-3.5 h-3.5" />
            <span>Create New Agent (Studio)</span>
          </button>
        )}
      </div>

      {/* Agents Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        {agents.map((agent) => {
          const isInstalled = agent.isInstalled;

          return (
            <div
              key={agent.id}
              className="bg-surface/70 rounded-2xl p-5 border border-white/5 hover:border-white/10 transition flex flex-col justify-between space-y-4 group"
            >
              <div className="space-y-3">
                <div className="flex items-start justify-between">
                  <div className="flex items-center space-x-3">
                    <div className="w-12 h-12 rounded-2xl bg-black/40 border border-white/10 flex items-center justify-center text-2xl shadow-inner">
                      {agent.avatar}
                    </div>
                    <div>
                      <h3 className="text-sm font-bold text-white group-hover:text-indigo-400 transition">
                        {agent.name}
                      </h3>
                      <span className="text-xs text-indigo-400/90 font-medium block">{agent.role}</span>
                    </div>
                  </div>
                  {agent.isBuiltin && (
                    <span className="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-white/5 text-slate-400 border border-white/5">
                      Core
                    </span>
                  )}
                </div>

                <p className="text-xs text-slate-400 leading-relaxed line-clamp-3">
                  {agent.description}
                </p>

                {/* Skills tags */}
                <div className="flex flex-wrap gap-1 pt-1">
                  {agent.skills?.slice(0, 3).map((skill, sIdx) => (
                    <span
                      key={sIdx}
                      className="text-[10px] bg-black/30 border border-white/5 px-2 py-0.5 rounded-md text-slate-300 font-medium"
                    >
                      {skill}
                    </span>
                  ))}
                  {agent.canvasType !== 'none' && (
                    <span className="text-[10px] bg-purple-950/40 border border-purple-500/20 px-2 py-0.5 rounded-md text-purple-300 font-medium">
                      Canvas: {agent.canvasType}
                    </span>
                  )}
                </div>
              </div>

              {/* Install / Uninstall button */}
              <button
                onClick={() => onToggleInstall(agent.id)}
                className={`w-full flex items-center justify-center space-x-1.5 py-2 rounded-xl text-xs font-semibold transition ${
                  isInstalled
                    ? 'bg-emerald-500/15 hover:bg-rose-500/15 text-emerald-400 hover:text-rose-400 border border-emerald-500/30 hover:border-rose-500/30'
                    : 'bg-white/10 hover:bg-white/20 text-white'
                }`}
              >
                {isInstalled ? (
                  <>
                    <Check className="w-3.5 h-3.5" />
                    <span>Installed in Workspace</span>
                  </>
                ) : (
                  <>
                    <Plus className="w-3.5 h-3.5" />
                    <span>Add to Workspace</span>
                  </>
                )}
              </button>
            </div>
          );
        })}
      </div>
    </div>
  );
};
