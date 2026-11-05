import React, { useState, useEffect } from 'react';
import {
  X,
  Search,
  Sliders,
  Cpu,
  Shield,
  Radio,
  Palette,
  Database,
  Info,
  Check,
  Copy,
  Trash2,
  RefreshCw,
  Terminal,
  Zap,
  Volume2,
  VolumeX,
  Eye,
  Key,
  Layers,
  Sparkles,
  Server,
  ArrowRight
} from 'lucide-react';
import { Agent, ProviderStatus } from '../../types';

interface SettingsModalProps {
  isOpen: boolean;
  onClose: () => void;
  activeAgent: Agent | null;
  activeProvider: string;
  onProviderChanged: (provider: string) => void;
  onChatCleared?: () => void;
  chatWidthPreference: string;
  onChangeChatWidthPreference: (width: string) => void;
}

type SettingsTab = 'general' | 'models' | 'guardrails' | 'tunnel' | 'appearance' | 'storage' | 'about';

export const SettingsModal: React.FC<SettingsModalProps> = ({
  isOpen,
  onClose,
  activeAgent,
  activeProvider,
  onProviderChanged,
  onChatCleared,
  chatWidthPreference,
  onChangeChatWidthPreference
}) => {
  const [activeTab, setActiveTab] = useState<SettingsTab>('general');
  const [searchQuery, setSearchQuery] = useState('');

  // General Settings State
  const [workspaceName, setWorkspaceName] = useState(() => localStorage.getItem('ruxt_workspace_name') || 'RuXt Lab Main');
  const [autoScroll, setAutoScroll] = useState(() => localStorage.getItem('ruxt_autoscroll') !== 'false');
  const [soundEnabled, setSoundEnabled] = useState(() => localStorage.getItem('ruxt_sound') === 'true');
  const [streamSpeed, setStreamSpeed] = useState(() => localStorage.getItem('ruxt_streamspeed') || 'normal');

  // Appearance Settings
  const [accentColor, setAccentColor] = useState(() => localStorage.getItem('ruxt_accent_color') || 'indigo');
  const [uiDensity, setUiDensity] = useState(() => localStorage.getItem('ruxt_ui_density') || 'comfortable');

  // Guardrail Settings
  const [globalGuardrail, setGlobalGuardrail] = useState(() => localStorage.getItem('ruxt_global_guardrails') !== 'false');
  const [pipelineDebugMode, setPipelineDebugMode] = useState(() => localStorage.getItem('ruxt_pipeline_debug') === 'true');
  const [reasoningEffort, setReasoningEffort] = useState(() => localStorage.getItem('ruxt_reasoning_effort') || 'medium');

  // Model & Provider State
  const [providers, setProviders] = useState<ProviderStatus[]>([]);
  const [geminiKey, setGeminiKey] = useState('');
  const [geminiModel, setGeminiModel] = useState('gemini-1.5-flash');
  const [ollamaUrl, setOllamaUrl] = useState('http://localhost:11434');
  const [ollamaModel, setOllamaModel] = useState('llama3');
  const [testingProvider, setTestingProvider] = useState<string | null>(null);
  const [providerTestMsg, setProviderTestMsg] = useState<{ [k: string]: { ok: boolean; message: string } }>({});

  // Tunnel State
  const [allowRemoteExec, setAllowRemoteExec] = useState(() => localStorage.getItem('ruxt_remote_exec') !== 'false');
  const [tunnelTestOutput, setTunnelTestOutput] = useState<string | null>(null);
  const [isTunnelTesting, setIsTunnelTesting] = useState(false);

  // Storage / System Stats
  const [systemStats, setSystemStats] = useState<any>(null);
  const [clearingChat, setClearingChat] = useState(false);
  const [actionNotice, setActionNotice] = useState<string | null>(null);
  const [copiedKey, setCopiedKey] = useState<string | null>(null);

  // Load providers and stats on open
  useEffect(() => {
    if (isOpen) {
      fetchProviders();
      fetchSystemStats();
    }
  }, [isOpen]);

  // Handle escape key
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen) {
        onClose();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  const showNotice = (msg: string) => {
    setActionNotice(msg);
    setTimeout(() => setActionNotice(null), 3000);
  };

  const copyToClipboard = (key: string, text: string) => {
    navigator.clipboard.writeText(text);
    setCopiedKey(key);
    setTimeout(() => setCopiedKey(null), 2000);
  };

  const fetchProviders = async () => {
    try {
      const res = await fetch('/api/providers');
      const data = await res.json();
      if (data.providers) setProviders(data.providers);
    } catch (_) {}
  };

  const fetchSystemStats = async () => {
    try {
      const res = await fetch('/api/system/stats');
      const data = await res.json();
      if (data.ok) setSystemStats(data);
    } catch (_) {}
  };

  const handleSaveGeneral = () => {
    localStorage.setItem('ruxt_workspace_name', workspaceName);
    localStorage.setItem('ruxt_autoscroll', String(autoScroll));
    localStorage.setItem('ruxt_sound', String(soundEnabled));
    localStorage.setItem('ruxt_streamspeed', streamSpeed);
    showNotice('General preferences saved successfully.');
  };

  const handleSaveAppearance = () => {
    localStorage.setItem('ruxt_accent_color', accentColor);
    localStorage.setItem('ruxt_ui_density', uiDensity);
    showNotice('Appearance updated.');
  };

  const handleSaveGuardrails = () => {
    localStorage.setItem('ruxt_global_guardrails', String(globalGuardrail));
    localStorage.setItem('ruxt_pipeline_debug', String(pipelineDebugMode));
    localStorage.setItem('ruxt_reasoning_effort', reasoningEffort);
    showNotice('Cognitive guardrail rules updated.');
  };

  const handleSetActiveProvider = async (id: string) => {
    try {
      const res = await fetch('/api/providers/active', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ provider: id })
      });
      const data = await res.json();
      if (data.ok) {
        onProviderChanged(id);
        fetchProviders();
        showNotice(`Active model engine switched to ${id.toUpperCase()}`);
      }
    } catch (_) {}
  };

  const handleSaveGemini = async () => {
    setTestingProvider('gemini');
    try {
      const res = await fetch('/api/providers/configure', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ provider: 'gemini', apiKey: geminiKey, model: geminiModel })
      });
      const data = await res.json();
      if (data.ok) {
        const testRes = await fetch('/api/providers/test', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ provider: 'gemini' })
        });
        const testData = await testRes.json();
        setProviderTestMsg((prev) => ({ ...prev, gemini: testData }));
        fetchProviders();
        showNotice('Gemini configuration saved and tested.');
      }
    } catch (e: any) {
      setProviderTestMsg((prev) => ({ ...prev, gemini: { ok: false, message: e.message } }));
    } finally {
      setTestingProvider(null);
    }
  };

  const handleSaveOllama = async () => {
    setTestingProvider('ollama');
    try {
      await fetch('/api/providers/configure', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ provider: 'ollama', endpoint: ollamaUrl, model: ollamaModel })
      });
      const testRes = await fetch('/api/providers/test', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ provider: 'ollama' })
      });
      const testData = await testRes.json();
      setProviderTestMsg((prev) => ({ ...prev, ollama: testData }));
      fetchProviders();
      showNotice(testData.ok ? 'Connected to local Ollama!' : 'Ollama connection test failed.');
    } catch (e: any) {
      setProviderTestMsg((prev) => ({ ...prev, ollama: { ok: false, message: e.message } }));
    } finally {
      setTestingProvider(null);
    }
  };

  const handleTestTunnel = async () => {
    setIsTunnelTesting(true);
    setTunnelTestOutput(null);
    try {
      const res = await fetch('/api/tunnel/exec', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ command: 'echo "RuXt IDE Bridge Active!"' })
      });
      const data = await res.json();
      if (data.ok) {
        setTunnelTestOutput(data.result?.stdout || 'Success: Outbound tunnel companion acknowledged command.');
      } else {
        setTunnelTestOutput(`Status: ${data.error}`);
      }
    } catch (err: any) {
      setTunnelTestOutput(`Error: ${err.message}`);
    } finally {
      setIsTunnelTesting(false);
    }
  };

  const handleClearCurrentAgentChat = async () => {
    if (!activeAgent) return;
    if (!confirm(`Are you sure you want to clear conversation history for ${activeAgent.name}?`)) return;
    setClearingChat(true);
    try {
      await fetch(`/api/chat/${activeAgent.id}`, { method: 'DELETE' });
      fetchSystemStats();
      if (onChatCleared) onChatCleared();
      showNotice(`Conversation history cleared for ${activeAgent.name}.`);
    } catch (_) {
    } finally {
      setClearingChat(false);
    }
  };

  const handlePurgeAllChat = async () => {
    if (!confirm('CAUTION: This will permanently purge ALL conversation logs and task steps in SQLite WAL. Continue?')) return;
    setClearingChat(true);
    try {
      await fetch('/api/chat', { method: 'DELETE' });
      fetchSystemStats();
      if (onChatCleared) onChatCleared();
      showNotice('All conversation and task logs purged.');
    } catch (_) {
    } finally {
      setClearingChat(false);
    }
  };

  const handleExportJSON = () => {
    if (!activeAgent) return;
    fetch(`/api/chat/${activeAgent.id}`)
      .then((res) => res.json())
      .then((data) => {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `ruxt-chat-${activeAgent.slug}-${Date.now()}.json`;
        a.click();
        URL.revokeObjectURL(url);
        showNotice('Chat history exported to JSON.');
      });
  };

  if (!isOpen) return null;

  const tabs = [
    { id: 'general' as const, label: 'General & Workspace', icon: Sliders, badge: null },
    { id: 'models' as const, label: 'Models & Providers', icon: Cpu, badge: activeProvider },
    { id: 'guardrails' as const, label: 'Guardrails & Cognition', icon: Shield, badge: 'Active' },
    { id: 'tunnel' as const, label: 'Cloud Tunnel & Bridge', icon: Radio, badge: 'Port 1234' },
    { id: 'appearance' as const, label: 'Appearance & Layout', icon: Palette, badge: null },
    { id: 'storage' as const, label: 'SQLite Storage & WAL', icon: Database, badge: null },
    { id: 'about' as const, label: 'System & Diagnostics', icon: Info, badge: 'v1.0.0' }
  ];

  const filteredTabs = searchQuery.trim()
    ? tabs.filter((t) => t.label.toLowerCase().includes(searchQuery.toLowerCase()))
    : tabs;

  return (
    <div
      className="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-3 sm:p-5 select-none animate-in fade-in duration-200"
      onClick={onClose}
    >
      <div
        className="w-full max-w-5xl h-[88vh] max-h-[760px] bg-[#0B0F19] border border-white/10 rounded-2xl shadow-2xl flex flex-col overflow-hidden text-slate-200 glass"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Antigravity Header Bar */}
        <div className="h-14 px-5 border-b border-white/5 bg-[#0E131F]/90 flex items-center justify-between shrink-0">
          <div className="flex items-center space-x-3">
            <div className="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-600/30">
              <Sparkles className="w-4 h-4 text-white" />
            </div>
            <div>
              <div className="flex items-center space-x-2">
                <h2 className="text-sm font-bold text-white tracking-wide">Workspace Settings</h2>
                <span className="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-mono">
                  Antigravity Engine
                </span>
              </div>
              <p className="text-[10px] text-slate-400">Configure neural models, cognitive guardrails, tunnel bridges, and UI ergonomics</p>
            </div>
          </div>

          <div className="flex items-center space-x-3">
            {/* Search Settings input */}
            <div className="relative hidden sm:block w-52 md:w-64">
              <Search className="w-3.5 h-3.5 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                placeholder="Search settings..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full bg-black/40 border border-white/10 rounded-xl pl-8 pr-3 py-1.5 text-xs text-white placeholder-slate-500 outline-none focus:border-indigo-500/60 focus:ring-1 focus:ring-indigo-500/20 transition"
              />
            </div>

            <button
              onClick={onClose}
              className="p-1.5 rounded-xl hover:bg-white/10 text-slate-400 hover:text-white transition"
              title="Close (Esc)"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* Action Notice Toast */}
        {actionNotice && (
          <div className="bg-indigo-600/20 border-b border-indigo-500/30 px-5 py-2 text-xs text-indigo-300 flex items-center justify-between">
            <div className="flex items-center space-x-2">
              <Check className="w-3.5 h-3.5 text-indigo-400" />
              <span>{actionNotice}</span>
            </div>
          </div>
        )}

        {/* Main 2-Column Split Body */}
        <div className="flex-1 flex overflow-hidden">
          {/* Left Navigation Sidebar */}
          <div className="w-56 sm:w-64 bg-[#0E131F]/60 border-r border-white/5 p-2.5 sm:p-3 flex flex-col justify-between shrink-0 overflow-y-auto">
            <div className="space-y-1">
              <span className="text-[10px] uppercase font-bold text-slate-500 px-2.5 py-1 block tracking-wider">
                Categories
              </span>
              {filteredTabs.map((tab) => {
                const Icon = tab.icon;
                const isActive = activeTab === tab.id;

                return (
                  <button
                    key={tab.id}
                    onClick={() => setActiveTab(tab.id)}
                    className={`w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition text-left ${
                      isActive
                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-semibold'
                        : 'text-slate-400 hover:text-white hover:bg-white/5'
                    }`}
                  >
                    <div className="flex items-center space-x-2.5 truncate">
                      <Icon className={`w-4 h-4 shrink-0 ${isActive ? 'text-white' : 'text-slate-400'}`} />
                      <span className="truncate">{tab.label}</span>
                    </div>
                    {tab.badge && (
                      <span
                        className={`text-[9px] px-1.5 py-0.5 rounded font-mono uppercase truncate ${
                          isActive ? 'bg-white/20 text-white' : 'bg-white/5 text-slate-400'
                        }`}
                      >
                        {tab.badge}
                      </span>
                    )}
                  </button>
                );
              })}
            </div>

            <div className="p-2.5 bg-black/30 rounded-xl border border-white/5 text-[11px] text-slate-400 space-y-1">
              <div className="flex items-center justify-between text-slate-300 font-mono text-[10px]">
                <span>FASTIFY HTTP/WS</span>
                <span className="text-emerald-400">ACTIVE</span>
              </div>
              <div className="text-[10px] text-slate-500 truncate">Port 1234 • Local-First</div>
            </div>
          </div>

          {/* Right Content Area */}
          <div className="flex-1 overflow-y-auto p-4 sm:p-6 bg-[#0B0F19]/90 space-y-6">
            {/* TAB 1: GENERAL & WORKSPACE */}
            {activeTab === 'general' && (
              <div className="space-y-6 max-w-2xl">
                <div>
                  <h3 className="text-base font-bold text-white">General & Workspace</h3>
                  <p className="text-xs text-slate-400 mt-0.5">Control workspace preferences, interaction flow, and streaming behaviors.</p>
                </div>

                <div className="space-y-4">
                  {/* Workspace Title */}
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 space-y-2">
                    <label className="text-xs font-semibold text-slate-200 block">Workspace Identifier</label>
                    <input
                      type="text"
                      value={workspaceName}
                      onChange={(e) => setWorkspaceName(e.target.value)}
                      className="w-full bg-black/40 border border-white/10 rounded-xl px-3.5 py-2 text-xs text-white outline-none focus:border-indigo-500 transition"
                    />
                    <p className="text-[11px] text-slate-500">Displayed on companion CLI terminals and internal bridge sessions.</p>
                  </div>

                  {/* Auto scroll toggle */}
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                      <div className="text-xs font-semibold text-slate-200">Auto-Scroll During Stream</div>
                      <div className="text-[11px] text-slate-400">Automatically stick to bottom as agent streams tokens.</div>
                    </div>
                    <button
                      onClick={() => setAutoScroll(!autoScroll)}
                      className={`w-11 h-6 rounded-full transition p-1 ${autoScroll ? 'bg-indigo-600' : 'bg-white/10'}`}
                    >
                      <div className={`w-4 h-4 rounded-full bg-white transition transform ${autoScroll ? 'translate-x-5' : 'translate-x-0'}`} />
                    </button>
                  </div>

                  {/* Sound feedback toggle */}
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                      {soundEnabled ? <Volume2 className="w-4 h-4 text-indigo-400" /> : <VolumeX className="w-4 h-4 text-slate-500" />}
                      <div>
                        <div className="text-xs font-semibold text-slate-200">Sound & Audio Haptics</div>
                        <div className="text-[11px] text-slate-400">Play subtle auditory cues on task completion and chess moves.</div>
                      </div>
                    </div>
                    <button
                      onClick={() => setSoundEnabled(!soundEnabled)}
                      className={`w-11 h-6 rounded-full transition p-1 ${soundEnabled ? 'bg-indigo-600' : 'bg-white/10'}`}
                    >
                      <div className={`w-4 h-4 rounded-full bg-white transition transform ${soundEnabled ? 'translate-x-5' : 'translate-x-0'}`} />
                    </button>
                  </div>

                  {/* Simulation Token Streaming Speed */}
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 space-y-2">
                    <label className="text-xs font-semibold text-slate-200 block">Simulation Stream Cadence</label>
                    <div className="grid grid-cols-3 gap-2">
                      {[
                        { id: 'fast', label: 'Ultra Fast (15ms)' },
                        { id: 'normal', label: 'Balanced (30ms)' },
                        { id: 'natural', label: 'Natural Thought (50ms)' }
                      ].map((sp) => (
                        <button
                          key={sp.id}
                          onClick={() => setStreamSpeed(sp.id)}
                          className={`px-3 py-2 rounded-xl text-xs font-medium border transition ${
                            streamSpeed === sp.id
                              ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300'
                              : 'bg-black/30 border-white/5 text-slate-400 hover:text-white'
                          }`}
                        >
                          {sp.label}
                        </button>
                      ))}
                    </div>
                  </div>

                  <button
                    onClick={handleSaveGeneral}
                    className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition shadow-md shadow-indigo-600/30"
                  >
                    Save General Settings
                  </button>
                </div>
              </div>
            )}

            {/* TAB 2: MODELS & PROVIDERS */}
            {activeTab === 'models' && (
              <div className="space-y-6 max-w-2xl">
                <div>
                  <h3 className="text-base font-bold text-white">AI Model Providers & Routing</h3>
                  <p className="text-xs text-slate-400 mt-0.5">
                    Toggle between the zero-setup offline simulator or live neural models (Gemini Pro/Flash, Local Ollama).
                  </p>
                </div>

                {/* Active Provider Selector Grid */}
                <div className="space-y-3">
                  <label className="text-xs font-semibold text-slate-300 block">Active Engine Selection</label>
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    {[
                      {
                        id: 'simulator',
                        title: 'Simulator Engine',
                        desc: 'Instant offline simulation with dynamic persona tokens',
                        badge: 'Zero Setup'
                      },
                      {
                        id: 'gemini',
                        title: 'Google Gemini',
                        desc: 'Gemini 1.5 Pro / Flash with high-intelligence reasoning',
                        badge: 'Cloud Neural'
                      },
                      {
                        id: 'ollama',
                        title: 'Local Ollama',
                        desc: 'Llama 3, DeepSeek-R1, Mistral running 100% locally',
                        badge: 'Private Local'
                      }
                    ].map((p) => {
                      const isActive = activeProvider === p.id;
                      return (
                        <button
                          key={p.id}
                          onClick={() => handleSetActiveProvider(p.id)}
                          className={`p-3.5 rounded-2xl border text-left transition flex flex-col justify-between ${
                            isActive
                              ? 'bg-indigo-600/20 border-indigo-500 text-white shadow-lg shadow-indigo-600/20'
                              : 'bg-surface/60 border-white/5 text-slate-400 hover:text-white hover:border-white/10'
                          }`}
                        >
                          <div>
                            <div className="flex items-center justify-between mb-1">
                              <span className="text-xs font-bold text-white">{p.title}</span>
                              <span className="text-[9px] px-1.5 py-0.5 rounded bg-white/10 font-mono">{p.badge}</span>
                            </div>
                            <p className="text-[11px] text-slate-400 leading-relaxed">{p.desc}</p>
                          </div>
                          <div className="mt-3 flex items-center space-x-1 text-[11px] font-semibold text-indigo-400">
                            {isActive ? (
                              <span className="flex items-center space-x-1 text-emerald-400">
                                <Check className="w-3.5 h-3.5" />
                                <span>Active</span>
                              </span>
                            ) : (
                              <span>Click to activate</span>
                            )}
                          </div>
                        </button>
                      );
                    })}
                  </div>
                </div>

                {/* Gemini Config */}
                <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 space-y-3">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-white">Google Gemini Configuration</span>
                    {providerTestMsg.gemini && (
                      <span
                        className={`text-[10px] font-mono px-2 py-0.5 rounded ${
                          providerTestMsg.gemini.ok ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'
                        }`}
                      >
                        {providerTestMsg.gemini.ok ? 'Verified Online' : providerTestMsg.gemini.message}
                      </span>
                    )}
                  </div>
                  <div className="space-y-2">
                    <label className="text-[11px] text-slate-400 block">Gemini API Key</label>
                    <input
                      type="password"
                      placeholder="AIzaSy..."
                      value={geminiKey}
                      onChange={(e) => setGeminiKey(e.target.value)}
                      className="w-full bg-black/40 border border-white/10 rounded-xl px-3.5 py-2 text-xs text-white outline-none focus:border-indigo-500"
                    />
                  </div>
                  <div className="grid grid-cols-2 gap-2">
                    <div>
                      <label className="text-[11px] text-slate-400 block mb-1">Model Name</label>
                      <select
                        value={geminiModel}
                        onChange={(e) => setGeminiModel(e.target.value)}
                        className="w-full bg-black/40 border border-white/10 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-indigo-500"
                      >
                        <option value="gemini-1.5-flash">gemini-1.5-flash (Fast)</option>
                        <option value="gemini-1.5-pro">gemini-1.5-pro (Reasoning)</option>
                        <option value="gemini-2.0-flash-exp">gemini-2.0-flash-exp</option>
                      </select>
                    </div>
                    <div className="flex items-end">
                      <button
                        onClick={handleSaveGemini}
                        disabled={testingProvider === 'gemini'}
                        className="w-full py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition"
                      >
                        {testingProvider === 'gemini' ? 'Testing...' : 'Save & Verify Key'}
                      </button>
                    </div>
                  </div>
                </div>

                {/* Ollama Config */}
                <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 space-y-3">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-white">Local Ollama Configuration</span>
                    {providerTestMsg.ollama && (
                      <span
                        className={`text-[10px] font-mono px-2 py-0.5 rounded ${
                          providerTestMsg.ollama.ok ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'
                        }`}
                      >
                        {providerTestMsg.ollama.message}
                      </span>
                    )}
                  </div>
                  <div className="grid grid-cols-2 gap-2">
                    <div>
                      <label className="text-[11px] text-slate-400 block mb-1">Ollama Host</label>
                      <input
                        type="text"
                        value={ollamaUrl}
                        onChange={(e) => setOllamaUrl(e.target.value)}
                        className="w-full bg-black/40 border border-white/10 rounded-xl px-3 py-2 text-xs text-white outline-none"
                      />
                    </div>
                    <div>
                      <label className="text-[11px] text-slate-400 block mb-1">Local Model Tag</label>
                      <input
                        type="text"
                        value={ollamaModel}
                        onChange={(e) => setOllamaModel(e.target.value)}
                        placeholder="llama3, mistral, deepseek-r1"
                        className="w-full bg-black/40 border border-white/10 rounded-xl px-3 py-2 text-xs text-white outline-none"
                      />
                    </div>
                  </div>
                  <button
                    onClick={handleSaveOllama}
                    disabled={testingProvider === 'ollama'}
                    className="px-4 py-2 bg-white/10 hover:bg-white/15 text-white rounded-xl text-xs font-semibold transition"
                  >
                    {testingProvider === 'ollama' ? 'Checking...' : 'Ping & Save Ollama'}
                  </button>
                </div>
              </div>
            )}

            {/* TAB 3: GUARDRAILS & COGNITION */}
            {activeTab === 'guardrails' && (
              <div className="space-y-6 max-w-2xl">
                <div>
                  <h3 className="text-base font-bold text-white">Cognitive Guardrails & Thinking Pipeline</h3>
                  <p className="text-xs text-slate-400 mt-0.5">
                    RuXt Next-Gen operates a 4-Phase Thinking Pipeline (Understand → Plan → Execute → Verify) with strict domain defense.
                  </p>
                </div>

                <div className="space-y-4">
                  {/* Strict Guardrails Toggle */}
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                      <div className="text-xs font-semibold text-white flex items-center space-x-1.5">
                        <Shield className="w-3.5 h-3.5 text-amber-400" />
                        <span>Enforce Strict Domain Boundaries</span>
                      </div>
                      <div className="text-[11px] text-slate-400 mt-0.5">
                        When enabled, asking a chess coach for coding or cooking triggers in-character polite interceptors.
                      </div>
                    </div>
                    <button
                      onClick={() => setGlobalGuardrail(!globalGuardrail)}
                      className={`w-11 h-6 rounded-full transition p-1 ${globalGuardrail ? 'bg-amber-600' : 'bg-white/10'}`}
                    >
                      <div className={`w-4 h-4 rounded-full bg-white transition transform ${globalGuardrail ? 'translate-x-5' : 'translate-x-0'}`} />
                    </button>
                  </div>

                  {/* 4-Phase Pipeline Debug Inspection */}
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                      <div className="text-xs font-semibold text-white flex items-center space-x-1.5">
                        <Layers className="w-3.5 h-3.5 text-indigo-400" />
                        <span>Show Detailed Step Telemetry in Chat</span>
                      </div>
                      <div className="text-[11px] text-slate-400 mt-0.5">
                        Displays phase verification timestamps and internal cognitive checkpoints.
                      </div>
                    </div>
                    <button
                      onClick={() => setPipelineDebugMode(!pipelineDebugMode)}
                      className={`w-11 h-6 rounded-full transition p-1 ${pipelineDebugMode ? 'bg-indigo-600' : 'bg-white/10'}`}
                    >
                      <div className={`w-4 h-4 rounded-full bg-white transition transform ${pipelineDebugMode ? 'translate-x-5' : 'translate-x-0'}`} />
                    </button>
                  </div>

                  {/* Reasoning Effort Selector */}
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 space-y-2">
                    <label className="text-xs font-semibold text-slate-200 block">Domain Reasoning Effort</label>
                    <div className="grid grid-cols-3 gap-2">
                      {[
                        { id: 'low', label: 'Fast Response' },
                        { id: 'medium', label: 'Balanced (Standard)' },
                        { id: 'high', label: 'Deep Strategic Analysis' }
                      ].map((eff) => (
                        <button
                          key={eff.id}
                          onClick={() => setReasoningEffort(eff.id)}
                          className={`px-3 py-2 rounded-xl text-xs font-medium border transition ${
                            reasoningEffort === eff.id
                              ? 'bg-amber-500/20 border-amber-500 text-amber-300'
                              : 'bg-black/30 border-white/5 text-slate-400 hover:text-white'
                          }`}
                        >
                          {eff.label}
                        </button>
                      ))}
                    </div>
                  </div>

                  <button
                    onClick={handleSaveGuardrails}
                    className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition"
                  >
                    Save Cognitive Rules
                  </button>
                </div>
              </div>
            )}

            {/* TAB 4: TUNNEL & BRIDGE */}
            {activeTab === 'tunnel' && (
              <div className="space-y-6 max-w-2xl">
                <div>
                  <h3 className="text-base font-bold text-white">Outbound Tunnel & Local IDE Bridge</h3>
                  <p className="text-xs text-slate-400 mt-0.5">
                    Connect local IDE agents (Antigravity, Cursor, Claude Code) through an outbound persistent WebSocket.
                  </p>
                </div>

                {/* Tunnel connection info */}
                <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 space-y-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                      <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                      <span className="text-xs font-bold text-white">Outbound WebSocket Server Listening</span>
                    </div>
                    <span className="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
                      ws://localhost:1234/ws
                    </span>
                  </div>

                  <p className="text-[11px] text-slate-400 leading-relaxed">
                    No inbound ports or firewall punching required. Run this command on your machine to bridge CLI actions directly:
                  </p>

                  <div className="bg-black/60 border border-white/10 rounded-xl p-3 flex items-center justify-between font-mono text-xs text-emerald-300">
                    <code>npm run tunnel</code>
                    <button
                      onClick={() => copyToClipboard('tunnel_cmd', 'npm run tunnel')}
                      className="text-slate-400 hover:text-white transition p-1"
                      title="Copy command"
                    >
                      {copiedKey === 'tunnel_cmd' ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                    </button>
                  </div>

                  <div className="flex items-center justify-between pt-2">
                    <span className="text-xs text-slate-300">Allow Remote Shell Execution</span>
                    <button
                      onClick={() => setAllowRemoteExec(!allowRemoteExec)}
                      className={`w-11 h-6 rounded-full transition p-1 ${allowRemoteExec ? 'bg-indigo-600' : 'bg-white/10'}`}
                    >
                      <div className={`w-4 h-4 rounded-full bg-white transition transform ${allowRemoteExec ? 'translate-x-5' : 'translate-x-0'}`} />
                    </button>
                  </div>

                  <div className="pt-2 border-t border-white/5 flex items-center justify-between">
                    <button
                      onClick={handleTestTunnel}
                      disabled={isTunnelTesting}
                      className="px-4 py-2 bg-surface hover:bg-white/10 text-white rounded-xl text-xs font-medium border border-white/10 transition flex items-center space-x-2"
                    >
                      <Terminal className="w-3.5 h-3.5 text-indigo-400" />
                      <span>{isTunnelTesting ? 'Pinging Tunnel...' : 'Test Bridge Command'}</span>
                    </button>
                    {tunnelTestOutput && (
                      <span className="text-[11px] font-mono text-slate-300 truncate max-w-xs">{tunnelTestOutput}</span>
                    )}
                  </div>
                </div>
              </div>
            )}

            {/* TAB 5: APPEARANCE & LAYOUT */}
            {activeTab === 'appearance' && (
              <div className="space-y-6 max-w-2xl">
                <div>
                  <h3 className="text-base font-bold text-white">Appearance & UI Ergonomics</h3>
                  <p className="text-xs text-slate-400 mt-0.5">Customize chat stream max-width, color themes, and interface density.</p>
                </div>

                <div className="space-y-4">
                  {/* Chat Container Width Selection */}
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 space-y-3">
                    <label className="text-xs font-semibold text-white block">Chat Width Mode (Full Screen)</label>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                      {[
                        { id: 'compact', label: 'Compact', detail: '640px' },
                        { id: 'standard', label: 'Standard (Ideal)', detail: '768px' },
                        { id: 'wide', label: 'Wide', detail: '960px' },
                        { id: 'fluid', label: 'Fluid', detail: '100% Full' }
                      ].map((w) => (
                        <button
                          key={w.id}
                          onClick={() => {
                            onChangeChatWidthPreference(w.id);
                            showNotice(`Chat width updated to ${w.label}`);
                          }}
                          className={`p-3 rounded-xl border text-center transition ${
                            chatWidthPreference === w.id
                              ? 'bg-indigo-600/20 border-indigo-500 text-white shadow-md'
                              : 'bg-black/30 border-white/5 text-slate-400 hover:text-white'
                          }`}
                        >
                          <div className="text-xs font-bold">{w.label}</div>
                          <div className="text-[10px] text-slate-500 font-mono mt-0.5">{w.detail}</div>
                        </button>
                      ))}
                    </div>
                    <p className="text-[11px] text-slate-400">
                      Standard (768px) centers messages for optimal reading ergonomics without horizontal eye strain.
                    </p>
                  </div>

                  {/* Accent Color Picker */}
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 space-y-2">
                    <label className="text-xs font-semibold text-white block">Theme Accent Palette</label>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                      {[
                        { id: 'indigo', label: 'Antigravity Indigo', color: 'bg-indigo-600' },
                        { id: 'emerald', label: 'Cyber Emerald', color: 'bg-emerald-600' },
                        { id: 'amber', label: 'Grandmaster Amber', color: 'bg-amber-600' },
                        { id: 'purple', label: 'Cosmic Violet', color: 'bg-purple-600' }
                      ].map((th) => (
                        <button
                          key={th.id}
                          onClick={() => setAccentColor(th.id)}
                          className={`p-2.5 rounded-xl border flex items-center space-x-2 text-left transition ${
                            accentColor === th.id
                              ? 'border-white/30 bg-white/5 text-white'
                              : 'border-white/5 bg-black/30 text-slate-400 hover:text-white'
                          }`}
                        >
                          <div className={`w-3.5 h-3.5 rounded-full ${th.color}`} />
                          <span className="text-xs font-medium">{th.label}</span>
                        </button>
                      ))}
                    </div>
                  </div>

                  {/* UI Density */}
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 space-y-2">
                    <label className="text-xs font-semibold text-white block">Display Density</label>
                    <div className="grid grid-cols-2 gap-2">
                      {[
                        { id: 'comfortable', label: 'Comfortable', desc: 'Standard paddings and bubble spacing' },
                        { id: 'compact', label: 'Compact', desc: 'Reduced padding for high-density monitors' }
                      ].map((d) => (
                        <button
                          key={d.id}
                          onClick={() => setUiDensity(d.id)}
                          className={`p-3 rounded-xl border text-left transition ${
                            uiDensity === d.id
                              ? 'bg-indigo-600/20 border-indigo-500 text-white'
                              : 'bg-black/30 border-white/5 text-slate-400 hover:text-white'
                          }`}
                        >
                          <div className="text-xs font-bold">{d.label}</div>
                          <div className="text-[10px] text-slate-500 mt-0.5">{d.desc}</div>
                        </button>
                      ))}
                    </div>
                  </div>

                  <button
                    onClick={handleSaveAppearance}
                    className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition"
                  >
                    Save Appearance Settings
                  </button>
                </div>
              </div>
            )}

            {/* TAB 6: STORAGE & WAL */}
            {activeTab === 'storage' && (
              <div className="space-y-6 max-w-2xl">
                <div>
                  <h3 className="text-base font-bold text-white">SQLite WAL Storage & Chat Memory</h3>
                  <p className="text-xs text-slate-400 mt-0.5">
                    Inspect database metrics, clear conversational context, or export session transcripts.
                  </p>
                </div>

                {/* Storage Metrics Cards */}
                <div className="grid grid-cols-3 gap-3">
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-3.5">
                    <div className="text-[10px] text-slate-500 font-semibold uppercase">Total Messages</div>
                    <div className="text-lg font-bold text-white mt-1">{systemStats?.messageCount ?? '...'}</div>
                  </div>
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-3.5">
                    <div className="text-[10px] text-slate-500 font-semibold uppercase">Dispatched Tasks</div>
                    <div className="text-lg font-bold text-white mt-1">{systemStats?.taskCount ?? '...'}</div>
                  </div>
                  <div className="bg-surface/60 border border-white/5 rounded-2xl p-3.5">
                    <div className="text-[10px] text-slate-500 font-semibold uppercase">Memory RSS</div>
                    <div className="text-lg font-bold text-white mt-1">{systemStats?.memoryUsageMB ?? '...'} MB</div>
                  </div>
                </div>

                {/* Actions */}
                <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 space-y-4">
                  <span className="text-xs font-bold text-white block">Data Management</span>

                  <div className="flex flex-wrap gap-2.5">
                    {activeAgent && (
                      <button
                        onClick={handleClearCurrentAgentChat}
                        disabled={clearingChat}
                        className="px-3.5 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-xs font-medium text-amber-300 transition flex items-center space-x-1.5"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                        <span>Clear {activeAgent.name}'s Chat</span>
                      </button>
                    )}

                    <button
                      onClick={handleExportJSON}
                      className="px-3.5 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-xs font-medium text-slate-300 transition flex items-center space-x-1.5"
                    >
                      <Copy className="w-3.5 h-3.5" />
                      <span>Export Current Agent Memory</span>
                    </button>

                    <button
                      onClick={handlePurgeAllChat}
                      disabled={clearingChat}
                      className="px-3.5 py-2 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 rounded-xl text-xs font-medium text-rose-300 transition flex items-center space-x-1.5"
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                      <span>Purge All Messages in WAL</span>
                    </button>
                  </div>
                </div>
              </div>
            )}

            {/* TAB 7: ABOUT & DIAGNOSTICS */}
            {activeTab === 'about' && (
              <div className="space-y-6 max-w-2xl">
                <div>
                  <h3 className="text-base font-bold text-white">System Diagnostics & Platform Architecture</h3>
                  <p className="text-xs text-slate-400 mt-0.5">
                    RuXt Next-Gen local-first multi-agent operating workspace runtime details.
                  </p>
                </div>

                <div className="bg-surface/60 border border-white/5 rounded-2xl p-4 space-y-3 font-mono text-xs">
                  <div className="flex justify-between py-1 border-b border-white/5">
                    <span className="text-slate-400">Application Version</span>
                    <span className="text-white">RuXt Next-Gen v1.0.0</span>
                  </div>
                  <div className="flex justify-between py-1 border-b border-white/5">
                    <span className="text-slate-400">Architecture Invariant</span>
                    <span className="text-emerald-400">Zero Legacy Contamination (100% fresh)</span>
                  </div>
                  <div className="flex justify-between py-1 border-b border-white/5">
                    <span className="text-slate-400">Network Delivery</span>
                    <span className="text-white">Unified Fastify Single-Port (1234)</span>
                  </div>
                  <div className="flex justify-between py-1 border-b border-white/5">
                    <span className="text-slate-400">Active Node Runtime</span>
                    <span className="text-white">{systemStats?.nodeVersion || 'v20.x'}</span>
                  </div>
                  <div className="flex justify-between py-1 border-b border-white/5">
                    <span className="text-slate-400">Active OS Platform</span>
                    <span className="text-white">{systemStats?.platform || 'win32'}</span>
                  </div>
                  <div className="flex justify-between py-1 border-b border-white/5">
                    <span className="text-slate-400">Process Uptime</span>
                    <span className="text-white">{systemStats?.uptimeSeconds ? `${systemStats.uptimeSeconds} seconds` : 'Active'}</span>
                  </div>
                  <div className="flex justify-between py-1">
                    <span className="text-slate-400">Connected Tunnel Companions</span>
                    <span className="text-emerald-400">{systemStats?.tunnelClientsCount ?? 0} Active</span>
                  </div>
                </div>

                <div className="p-4 rounded-2xl bg-indigo-600/10 border border-indigo-500/20 text-xs text-indigo-300 space-y-1">
                  <div className="font-bold flex items-center space-x-1.5">
                    <Sparkles className="w-3.5 h-3.5" />
                    <span>Designed for Google Antigravity & Agentic Pairs</span>
                  </div>
                  <p className="text-[11px] text-slate-400 leading-relaxed">
                    Operate locally without public open ports. Connect outbound websockets to your IDE agent and build autonomous domain workflows with instant feedback.
                  </p>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
