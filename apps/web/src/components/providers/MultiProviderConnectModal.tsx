import React, { useState, useEffect } from 'react';
import {
  X,
  Sparkles,
  Zap,
  ShieldCheck,
  CheckCircle2,
  Mail,
  Key,
  ArrowRight,
  Cpu,
  Layers,
  Terminal,
  Server,
  Bot,
  ExternalLink,
  Check
} from 'lucide-react';

export type ProviderType = 'antigravity_pro' | 'cursor' | 'claude' | 'gemini' | 'codex' | 'ollama';

interface MultiProviderConnectModalProps {
  isOpen: boolean;
  onClose: () => void;
  initialProvider?: ProviderType;
  onProviderConnected: (providerId: string, info: any) => void;
}

interface ProviderMeta {
  id: ProviderType;
  title: string;
  badge: string;
  tagline: string;
  iconBg: string;
  gradient: string;
  authType: 'email' | 'key' | 'both' | 'endpoint';
  models: Array<{ id: string; name: string }>;
  features: string[];
}

const PROVIDERS_CONFIG: Record<ProviderType, ProviderMeta> = {
  antigravity_pro: {
    id: 'antigravity_pro',
    title: 'Antigravity Pro',
    badge: 'PRO NEURAL',
    tagline: 'Google DeepMind Cognitive Reasoning & Swarm Pipelines',
    iconBg: 'from-purple-600 to-indigo-600',
    gradient: 'from-purple-900/40 via-indigo-900/30 to-blue-950/80',
    authType: 'email',
    models: [
      { id: 'antigravity-pro-3.8-flash', name: 'Antigravity Pro 3.8 Flash (High Speed Neural)' },
      { id: 'antigravity-ultra-2.0', name: 'Antigravity Ultra 2.0 (Deep Reasoning Swarm)' },
      { id: 'antigravity-coder-max', name: 'Antigravity Coder Max (Autonomous Software Eng)' }
    ],
    features: [
      'Unlimited DeepMind Pro cognitive reasoning tokens',
      'Multi-Agent parallel swarms & canvas events',
      'Sub-second token streaming with domain defense',
      'Persistent Outbound WebSocket Bridge to local IDE'
    ]
  },
  cursor: {
    id: 'cursor',
    title: 'Cursor AI & Composer',
    badge: 'COMPOSER',
    tagline: 'Speculative Multi-File Diffs & Context Indexing',
    iconBg: 'from-indigo-600 to-cyan-600',
    gradient: 'from-indigo-900/40 via-cyan-950/30 to-blue-950/80',
    authType: 'email',
    models: [
      { id: 'cursor-composer-claude-3.5', name: 'Cursor Composer (Claude 3.5 Sonnet)' },
      { id: 'cursor-composer-gpt-4o', name: 'Cursor Composer (GPT-4o)' },
      { id: 'cursor-fast', name: 'Cursor Fast Tab & Edit Engine' }
    ],
    features: [
      'Composer multi-file speculative diff application',
      'Semantic symbol indexing across local projects',
      'Bridges with local Cursor binary via outbound tunnel',
      'Automated syntax & linter invariant validation'
    ]
  },
  claude: {
    id: 'claude',
    title: 'Anthropic Claude',
    badge: 'SONNET 3.7',
    tagline: 'Constitutional High-Precision Engineering & Architecture',
    iconBg: 'from-amber-600 to-orange-600',
    gradient: 'from-amber-950/40 via-orange-950/30 to-slate-950/80',
    authType: 'both',
    models: [
      { id: 'claude-3-7-sonnet-20250219', name: 'Claude 3.7 Sonnet (Latest Hybrid Reasoning)' },
      { id: 'claude-3-5-sonnet-20241022', name: 'Claude 3.5 Sonnet (State-of-the-Art Code)' },
      { id: 'claude-3-5-haiku-20241022', name: 'Claude 3.5 Haiku (Sub-second Velocity)' },
      { id: 'claude-3-opus-20240229', name: 'Claude 3 Opus (Complex Philosophy & Theory)' }
    ],
    features: [
      'Superior code architecture with zero hallucinations',
      'Support for both Anthropic API keys (sk-ant-...) or Pro Email',
      'Extended 200k context window ingestion',
      'Artifact visualization and live micro-frontend updates'
    ]
  },
  gemini: {
    id: 'gemini',
    title: 'Google Gemini Pro',
    badge: 'CLOUD NEURAL',
    tagline: 'Multi-Modal Reasoning with 2 Million Token Context',
    iconBg: 'from-blue-600 to-indigo-600',
    gradient: 'from-blue-950/40 via-indigo-950/30 to-slate-950/80',
    authType: 'key',
    models: [
      { id: 'gemini-1.5-flash', name: 'Gemini 1.5 Flash (Ultra Fast / Low Latency)' },
      { id: 'gemini-1.5-pro', name: 'Gemini 1.5 Pro (Deep Multi-Modal Reasoning)' },
      { id: 'gemini-2.0-flash-exp', name: 'Gemini 2.0 Flash Exp (Next-Gen)' }
    ],
    features: [
      'Up to 2M token context window ingestion',
      'Audio, image, and code multi-modal processing',
      'Google AI Studio API key integration',
      'Sub-second response delivery over WebSockets'
    ]
  },
  codex: {
    id: 'codex',
    title: 'OpenAI Codex',
    badge: 'AUTONOMOUS SWE',
    tagline: 'Test-Driven Autonomous Software Engineering & o1/o3-mini',
    iconBg: 'from-emerald-600 to-teal-600',
    gradient: 'from-emerald-950/40 via-teal-950/30 to-slate-950/80',
    authType: 'both',
    models: [
      { id: 'o3-mini', name: 'OpenAI o3-mini (High Speed Coding Reasoning)' },
      { id: 'o1', name: 'OpenAI o1 (Deep Autonomous Thought)' },
      { id: 'gpt-4o', name: 'GPT-4o (Omni Multi-Modal)' },
      { id: 'codex-pro-v2', name: 'Codex Autonomous SWE Engine v2' }
    ],
    features: [
      'Self-healing code generation with unit test assertions',
      'Supports OpenAI API key or ChatGPT Pro/Team account email',
      'Optimized time & space algorithmic complexity targets',
      'Direct integration with local workspace file systems'
    ]
  },
  ollama: {
    id: 'ollama',
    title: 'Local Ollama',
    badge: '100% PRIVATE',
    tagline: 'Air-Gapped Private Local Neural Server (Llama 3, DeepSeek-R1)',
    iconBg: 'from-slate-700 to-slate-900',
    gradient: 'from-slate-900/60 via-zinc-900/40 to-black/80',
    authType: 'endpoint',
    models: [
      { id: 'llama3', name: 'Llama 3 / 3.3 (Meta Open Weights)' },
      { id: 'deepseek-r1', name: 'DeepSeek-R1 (Open Reasoning Benchmark)' },
      { id: 'mistral', name: 'Mistral 7B / Nemo' },
      { id: 'qwen2.5-coder', name: 'Qwen 2.5 Coder (State of the art open code)' }
    ],
    features: [
      'Zero telemetry leaving your computer (100% offline)',
      'Local GPU acceleration via CUDA / Metal',
      'Detects local endpoint at http://localhost:11434',
      'No subscription or API fees required'
    ]
  }
};

export const MultiProviderConnectModal: React.FC<MultiProviderConnectModalProps> = ({
  isOpen,
  onClose,
  initialProvider = 'antigravity_pro',
  onProviderConnected
}) => {
  const [selectedProvider, setSelectedProvider] = useState<ProviderType>(initialProvider);
  const [email, setEmail] = useState('');
  const [apiKey, setApiKey] = useState('');
  const [endpoint, setEndpoint] = useState('http://localhost:11434');
  const [model, setModel] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isSuccess, setIsSuccess] = useState(false);
  const [connectedStatus, setConnectedStatus] = useState<Record<string, boolean>>({});

  useEffect(() => {
    if (isOpen) {
      setSelectedProvider(initialProvider);
      setIsSuccess(false);
      setError(null);
      loadProviderDefaults(initialProvider);
      fetchLiveStatuses();
    }
  }, [isOpen, initialProvider]);

  const loadProviderDefaults = (p: ProviderType) => {
    setError(null);
    setIsSuccess(false);
    const cfg = PROVIDERS_CONFIG[p];
    setModel(cfg.models[0]?.id || '');

    if (p === 'antigravity_pro') {
      setEmail(localStorage.getItem('ruxt_antigravity_email') || '');
    } else if (p === 'cursor') {
      setEmail(localStorage.getItem('ruxt_cursor_email') || '');
    } else if (p === 'claude') {
      setEmail(localStorage.getItem('ruxt_claude_email') || '');
      setApiKey(localStorage.getItem('ruxt_claude_key') || '');
    } else if (p === 'gemini') {
      setApiKey(localStorage.getItem('ruxt_gemini_key') || '');
    } else if (p === 'codex') {
      setEmail(localStorage.getItem('ruxt_codex_email') || '');
      setApiKey(localStorage.getItem('ruxt_openai_key') || '');
    } else if (p === 'ollama') {
      setEndpoint('http://localhost:11434');
    }
  };

  const fetchLiveStatuses = async () => {
    try {
      const res = await fetch('/api/providers');
      const data = await res.json();
      if (data.providers) {
        const map: Record<string, boolean> = {};
        for (const p of data.providers) {
          map[p.id] = p.connected;
        }
        setConnectedStatus(map);
      }
    } catch (_) {}
  };

  const handleSelectTab = (p: ProviderType) => {
    setSelectedProvider(p);
    loadProviderDefaults(p);
  };

  const handleConnect = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    const cfg = PROVIDERS_CONFIG[selectedProvider];

    try {
      let endpointUrl = '';
      let payload: any = { provider: selectedProvider, model };

      if (selectedProvider === 'antigravity_pro') {
        endpointUrl = '/api/antigravity/connect';
        payload = { email: email.trim(), model };
      } else if (selectedProvider === 'cursor') {
        endpointUrl = '/api/cursor/connect';
        payload = { email: email.trim(), model };
      } else if (selectedProvider === 'claude') {
        endpointUrl = '/api/claude/connect';
        payload = { email: email.trim(), apiKey: apiKey.trim(), model };
      } else if (selectedProvider === 'codex') {
        endpointUrl = '/api/codex/connect';
        payload = { email: email.trim(), apiKey: apiKey.trim(), model };
      } else if (selectedProvider === 'gemini') {
        endpointUrl = '/api/providers/configure';
        payload = { provider: 'gemini', apiKey: apiKey.trim(), model };
      } else if (selectedProvider === 'ollama') {
        endpointUrl = '/api/providers/configure';
        payload = { provider: 'ollama', endpoint: endpoint.trim(), model };
      }

      const res = await fetch(endpointUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (!res.ok || data.ok === false) {
        throw new Error(data.error || `Failed to connect ${cfg.title}`);
      }

      // Persist in localStorage
      if (email) localStorage.setItem(`ruxt_${selectedProvider}_email`, email);
      if (apiKey) localStorage.setItem(`ruxt_${selectedProvider}_key`, apiKey);
      localStorage.setItem('ruxt_active_provider', selectedProvider);

      setIsSuccess(true);
      onProviderConnected(selectedProvider, data);
      fetchLiveStatuses();

      setTimeout(() => {
        setIsSuccess(false);
        onClose();
      }, 1500);
    } catch (err: any) {
      setError(err.message || 'Connection failed. Please check parameters.');
    } finally {
      setLoading(false);
    }
  };

  const handleDisconnect = async () => {
    setLoading(true);
    try {
      let endpointUrl = `/api/${selectedProvider}/disconnect`;
      if (selectedProvider === 'gemini' || selectedProvider === 'ollama') {
        endpointUrl = '/api/providers/active';
      }
      await fetch(endpointUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ provider: 'simulator' })
      });

      onProviderConnected('simulator', { connected: false });
      fetchLiveStatuses();
      onClose();
    } catch (_) {
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  const currentCfg = PROVIDERS_CONFIG[selectedProvider];
  const isConnected = Boolean(connectedStatus[selectedProvider]);

  return (
    <div className="fixed inset-0 z-[125] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-in fade-in duration-200">
      <div className="relative w-full max-w-2xl bg-[#0B0F19] border border-white/10 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
        {/* Top Header */}
        <div className={`relative px-6 py-5 bg-gradient-to-r ${currentCfg.gradient} border-b border-white/10 flex items-center justify-between`}>
          <div className="flex items-center space-x-3.5">
            <div className={`w-11 h-11 rounded-2xl bg-gradient-to-br ${currentCfg.iconBg} p-0.5 shadow-lg flex items-center justify-center`}>
              <div className="w-full h-full bg-[#0B0F19] rounded-[14px] flex items-center justify-center text-white">
                <Sparkles className="w-5 h-5 text-indigo-400" />
              </div>
            </div>
            <div>
              <div className="flex items-center space-x-2">
                <h2 className="text-base font-bold text-white">{currentCfg.title}</h2>
                <span className="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-white/10 border border-white/20 text-white font-mono">
                  {currentCfg.badge}
                </span>
                {isConnected && (
                  <span className="text-[9px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center space-x-1">
                    <Check className="w-2.5 h-2.5" />
                    <span>CONNECTED</span>
                  </span>
                )}
              </div>
              <p className="text-[11px] text-slate-300 truncate max-w-md">{currentCfg.tagline}</p>
            </div>
          </div>

          <button onClick={onClose} className="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-white/10 transition">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Tab Switcher Pills */}
        <div className="px-6 py-2.5 bg-black/40 border-b border-white/5 flex items-center space-x-2 overflow-x-auto no-scrollbar">
          {(Object.keys(PROVIDERS_CONFIG) as ProviderType[]).map((key) => {
            const meta = PROVIDERS_CONFIG[key];
            const isTabActive = selectedProvider === key;
            const hasLink = Boolean(connectedStatus[key]);
            return (
              <button
                key={key}
                onClick={() => handleSelectTab(key)}
                className={`px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition flex items-center space-x-1.5 ${
                  isTabActive
                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                    : 'text-slate-400 hover:text-white hover:bg-white/5'
                }`}
              >
                <span>{meta.title}</span>
                {hasLink && <span className="w-1.5 h-1.5 rounded-full bg-emerald-400" />}
              </button>
            );
          })}
        </div>

        {/* Body Content */}
        <div className="p-6 overflow-y-auto space-y-5">
          {isSuccess ? (
            <div className="py-10 text-center space-y-3 animate-in zoom-in-95 duration-200">
              <div className="w-14 h-14 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center mx-auto shadow-lg shadow-emerald-500/20">
                <CheckCircle2 className="w-8 h-8" />
              </div>
              <h3 className="text-lg font-bold text-white">{currentCfg.title} Connected!</h3>
              <p className="text-xs text-slate-300 max-w-sm mx-auto">
                Switched active neural engine to <span className="text-indigo-400 font-semibold">{currentCfg.title}</span>. Your workspace is now operating with this provider.
              </p>
            </div>
          ) : (
            <>
              {/* Features Grid */}
              <div className="p-3.5 bg-black/40 border border-white/5 rounded-2xl space-y-2">
                <div className="text-[11px] font-semibold text-slate-300 uppercase tracking-wider font-mono">
                  Engine Capabilities
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                  {currentCfg.features.map((feat, idx) => (
                    <div key={idx} className="flex items-start space-x-2 text-slate-300">
                      <Check className="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5" />
                      <span className="text-[11px] leading-tight">{feat}</span>
                    </div>
                  ))}
                </div>
              </div>

              {/* Form Input Section */}
              <form onSubmit={handleConnect} className="space-y-4">
                {(currentCfg.authType === 'email' || currentCfg.authType === 'both') && (
                  <div className="space-y-1.5">
                    <label className="text-xs font-semibold text-slate-200 flex items-center justify-between">
                      <span>{currentCfg.title} Account Email</span>
                      <span className="text-[10px] text-slate-400 font-mono">Pro / Team License</span>
                    </label>
                    <div className="relative">
                      <Mail className="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                      <input
                        type="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        placeholder={`e.g. your-account@${selectedProvider}.ai or gmail.com`}
                        className="w-full bg-black/50 border border-white/10 rounded-xl pl-10 pr-3.5 py-2.5 text-xs text-white placeholder-slate-500 outline-none focus:border-indigo-500 transition"
                      />
                    </div>
                  </div>
                )}

                {(currentCfg.authType === 'key' || currentCfg.authType === 'both') && (
                  <div className="space-y-1.5">
                    <label className="text-xs font-semibold text-slate-200 flex items-center justify-between">
                      <span>API Secret Key</span>
                      <span className="text-[10px] text-slate-400 font-mono">
                        {selectedProvider === 'gemini' ? 'AIzaSy...' : selectedProvider === 'claude' ? 'sk-ant-...' : 'sk-...'}
                      </span>
                    </label>
                    <div className="relative">
                      <Key className="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                      <input
                        type="password"
                        value={apiKey}
                        onChange={(e) => setApiKey(e.target.value)}
                        placeholder={
                          selectedProvider === 'gemini'
                            ? 'Paste Google Gemini API Key'
                            : selectedProvider === 'claude'
                            ? 'Paste Anthropic API Key (sk-ant-...)'
                            : 'Paste OpenAI API Key (sk-...)'
                        }
                        className="w-full bg-black/50 border border-white/10 rounded-xl pl-10 pr-3.5 py-2.5 text-xs text-white placeholder-slate-500 outline-none focus:border-indigo-500 transition"
                      />
                    </div>
                  </div>
                )}

                {currentCfg.authType === 'endpoint' && (
                  <div className="space-y-1.5">
                    <label className="text-xs font-semibold text-slate-200 block">Local Ollama Host Endpoint</label>
                    <div className="relative">
                      <Server className="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                      <input
                        type="text"
                        value={endpoint}
                        onChange={(e) => setEndpoint(e.target.value)}
                        placeholder="http://localhost:11434"
                        className="w-full bg-black/50 border border-white/10 rounded-xl pl-10 pr-3.5 py-2.5 text-xs text-white outline-none focus:border-indigo-500 transition"
                      />
                    </div>
                  </div>
                )}

                {/* Model Selector */}
                <div className="space-y-1.5">
                  <label className="text-xs font-semibold text-slate-200 block">Active Target Model</label>
                  <select
                    value={model}
                    onChange={(e) => setModel(e.target.value)}
                    className="w-full bg-black/50 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white outline-none focus:border-indigo-500 transition"
                  >
                    {currentCfg.models.map((m) => (
                      <option key={m.id} value={m.id}>
                        {m.name}
                      </option>
                    ))}
                  </select>
                </div>

                {error && (
                  <div className="p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs">
                    {error}
                  </div>
                )}

                <div className="pt-2 flex items-center space-x-2.5">
                  <button
                    type="submit"
                    disabled={loading}
                    className="flex-1 py-2.5 px-4 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 flex items-center justify-center space-x-2 disabled:opacity-50"
                  >
                    <Sparkles className="w-4 h-4" />
                    <span>{loading ? `Connecting ${currentCfg.title}...` : `Connect & Authorize ${currentCfg.title}`}</span>
                    <ArrowRight className="w-3.5 h-3.5" />
                  </button>

                  {isConnected && (
                    <button
                      type="button"
                      onClick={handleDisconnect}
                      disabled={loading}
                      className="py-2.5 px-3 bg-white/5 hover:bg-rose-500/20 hover:text-rose-300 text-slate-400 rounded-xl text-xs font-medium border border-white/5 transition"
                    >
                      Disconnect
                    </button>
                  )}
                </div>
              </form>
            </>
          )}
        </div>
      </div>
    </div>
  );
};
