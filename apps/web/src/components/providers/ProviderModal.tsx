import React, { useState, useEffect } from 'react';
import { X, Zap, Cpu, Key, Radio, CheckCircle2, AlertCircle, RefreshCw, Terminal, Check } from 'lucide-react';
import { ProviderStatus } from '../../types';

interface ProviderModalProps {
  isOpen: boolean;
  onClose: () => void;
  activeProvider: string;
  onProviderChanged: (providerId: string) => void;
}

export const ProviderModal: React.FC<ProviderModalProps> = ({
  isOpen,
  onClose,
  activeProvider,
  onProviderChanged
}) => {
  const [providers, setProviders] = useState<ProviderStatus[]>([]);
  const [loading, setLoading] = useState(false);
  const [geminiKey, setGeminiKey] = useState('');
  const [geminiModel, setGeminiModel] = useState('gemini-1.5-flash');
  const [ollamaUrl, setOllamaUrl] = useState('http://localhost:11434');
  const [ollamaModel, setOllamaModel] = useState('llama3');
  const [testResult, setTestResult] = useState<{ [key: string]: { ok: boolean; message: string } }>({});
  const [testingId, setTestingId] = useState<string | null>(null);

  // Tunnel test
  const [tunnelTestOutput, setTunnelTestOutput] = useState<string | null>(null);
  const [isTunnelExecuting, setIsTunnelExecuting] = useState(false);

  useEffect(() => {
    if (isOpen) {
      fetchProviders();
    }
  }, [isOpen]);

  const fetchProviders = async () => {
    setLoading(true);
    try {
      const res = await fetch('/api/providers');
      const data = await res.json();
      if (data.providers) {
        setProviders(data.providers);
      }
    } catch (err) {
      console.error('Failed to fetch providers:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSetActive = async (id: string) => {
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
      }
    } catch (err) {
      console.error('Failed to change active provider:', err);
    }
  };

  const handleSaveGemini = async () => {
    setTestingId('gemini');
    try {
      const res = await fetch('/api/providers/configure', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          provider: 'gemini',
          apiKey: geminiKey,
          model: geminiModel
        })
      });
      const data = await res.json();
      if (data.ok) {
        // Run test
        const testRes = await fetch('/api/providers/test', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ provider: 'gemini' })
        });
        const testData = await testRes.json();
        setTestResult((prev) => ({ ...prev, gemini: testData }));
        fetchProviders();
      }
    } catch (err: any) {
      setTestResult((prev) => ({ ...prev, gemini: { ok: false, message: err.message } }));
    } finally {
      setTestingId(null);
    }
  };

  const handleTestOllama = async () => {
    setTestingId('ollama');
    try {
      await fetch('/api/providers/configure', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          provider: 'ollama',
          endpoint: ollamaUrl,
          model: ollamaModel
        })
      });

      const res = await fetch('/api/providers/test', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ provider: 'ollama' })
      });
      const data = await res.json();
      setTestResult((prev) => ({ ...prev, ollama: data }));
      fetchProviders();
    } catch (err: any) {
      setTestResult((prev) => ({ ...prev, ollama: { ok: false, message: err.message } }));
    } finally {
      setTestingId(null);
    }
  };

  const handleTestTunnelExec = async () => {
    setIsTunnelExecuting(true);
    setTunnelTestOutput(null);
    try {
      const res = await fetch('/api/tunnel/exec', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ command: 'echo "RuXt Outbound Tunnel is operational on $(date)"' })
      });
      const data = await res.json();
      if (data.ok && data.result) {
        setTunnelTestOutput(data.result.stdout || 'Command completed successfully');
      } else {
        setTunnelTestOutput(data.error || 'Failed to execute command over tunnel');
      }
    } catch (err: any) {
      setTunnelTestOutput(`Error: ${err.message}`);
    } finally {
      setIsTunnelExecuting(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black/70 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div
        className="w-full max-w-2xl bg-[#0E131F] border border-white/10 rounded-2xl shadow-2xl p-6 space-y-5 max-h-[90vh] overflow-y-auto"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between border-b border-white/5 pb-4">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
              <Cpu className="w-5 h-5" />
            </div>
            <div>
              <h2 className="text-base font-bold text-white">AI Engine & Model Providers</h2>
              <p className="text-xs text-slate-400">
                Switch between zero-setup simulation, local neural models, and cloud API endpoints
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 rounded-lg bg-surface hover:bg-white/10 text-slate-400 hover:text-white transition"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Provider Cards */}
        <div className="space-y-4">
          {/* 1. Simulator */}
          <div
            className={`p-4 rounded-xl border transition ${
              activeProvider === 'simulator'
                ? 'bg-indigo-950/20 border-indigo-500/50'
                : 'bg-black/30 border-white/5 hover:border-white/10'
            }`}
          >
            <div className="flex items-start justify-between">
              <div className="flex items-start space-x-3">
                <div className="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0 mt-0.5">
                  <Zap className="w-4 h-4" />
                </div>
                <div>
                  <div className="flex items-center space-x-2">
                    <h3 className="text-sm font-semibold text-white">Zero-Setup Simulation Engine</h3>
                    <span className="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-medium">
                      Always Ready
                    </span>
                  </div>
                  <p className="text-xs text-slate-400 mt-1">
                    Instant sub-second token streaming with domain guardrails, chess master intelligence, and automated tool reasoning. No API keys required.
                  </p>
                </div>
              </div>
              <button
                onClick={() => handleSetActive('simulator')}
                disabled={activeProvider === 'simulator'}
                className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${
                  activeProvider === 'simulator'
                    ? 'bg-indigo-600 text-white shadow-md'
                    : 'bg-surface hover:bg-white/10 text-slate-300'
                }`}
              >
                {activeProvider === 'simulator' ? 'Active Engine' : 'Activate'}
              </button>
            </div>
          </div>

          {/* 2. Local Ollama */}
          <div
            className={`p-4 rounded-xl border transition ${
              activeProvider === 'ollama'
                ? 'bg-indigo-950/20 border-indigo-500/50'
                : 'bg-black/30 border-white/5 hover:border-white/10'
            }`}
          >
            <div className="flex items-start justify-between">
              <div className="flex items-start space-x-3">
                <div className="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 shrink-0 mt-0.5">
                  <Cpu className="w-4 h-4" />
                </div>
                <div>
                  <div className="flex items-center space-x-2">
                    <h3 className="text-sm font-semibold text-white">Local Ollama Neural Server</h3>
                    <span className="text-[10px] text-slate-400 font-mono">http://localhost:11434</span>
                  </div>
                  <p className="text-xs text-slate-400 mt-1">
                    Run open-weight neural models on local hardware without cloud data egress.
                  </p>
                </div>
              </div>
              <button
                onClick={() => handleSetActive('ollama')}
                className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${
                  activeProvider === 'ollama'
                    ? 'bg-indigo-600 text-white shadow-md'
                    : 'bg-surface hover:bg-white/10 text-slate-300'
                }`}
              >
                {activeProvider === 'ollama' ? 'Active Engine' : 'Activate'}
              </button>
            </div>

            <div className="mt-3 pt-3 border-t border-white/5 grid grid-cols-1 sm:grid-cols-3 gap-2">
              <div className="sm:col-span-2 flex space-x-2">
                <input
                  type="text"
                  placeholder="Model name (e.g. llama3, mistral, deepseek-r1)"
                  value={ollamaModel}
                  onChange={(e) => setOllamaModel(e.target.value)}
                  className="flex-1 px-3 py-1.5 rounded-lg bg-black/40 border border-white/10 text-xs text-white placeholder-slate-600 outline-none"
                />
              </div>
              <button
                onClick={handleTestOllama}
                disabled={testingId === 'ollama'}
                className="flex items-center justify-center space-x-1.5 px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-xs text-slate-300 border border-white/5 transition"
              >
                <RefreshCw className={`w-3.5 h-3.5 ${testingId === 'ollama' ? 'animate-spin' : ''}`} />
                <span>Test Connection</span>
              </button>
            </div>

            {testResult.ollama && (
              <div
                className={`mt-2 text-xs flex items-center space-x-1.5 ${
                  testResult.ollama.ok ? 'text-emerald-400' : 'text-amber-400'
                }`}
              >
                {testResult.ollama.ok ? <CheckCircle2 className="w-3.5 h-3.5" /> : <AlertCircle className="w-3.5 h-3.5" />}
                <span>{testResult.ollama.message}</span>
              </div>
            )}
          </div>

          {/* 3. Google Gemini API */}
          <div
            className={`p-4 rounded-xl border transition ${
              activeProvider === 'gemini'
                ? 'bg-indigo-950/20 border-indigo-500/50'
                : 'bg-black/30 border-white/5 hover:border-white/10'
            }`}
          >
            <div className="flex items-start justify-between">
              <div className="flex items-start space-x-3">
                <div className="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0 mt-0.5">
                  <Key className="w-4 h-4" />
                </div>
                <div>
                  <div className="flex items-center space-x-2">
                    <h3 className="text-sm font-semibold text-white">Google Gemini Cloud Models</h3>
                    <span className="text-[10px] text-amber-400 font-mono">Gemini 1.5/2.0 Flash</span>
                  </div>
                  <p className="text-xs text-slate-400 mt-1">
                    Connect real Google Gemini models with low-latency streaming and high intelligence.
                  </p>
                </div>
              </div>
              <button
                onClick={() => handleSetActive('gemini')}
                className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${
                  activeProvider === 'gemini'
                    ? 'bg-indigo-600 text-white shadow-md'
                    : 'bg-surface hover:bg-white/10 text-slate-300'
                }`}
              >
                {activeProvider === 'gemini' ? 'Active Engine' : 'Activate'}
              </button>
            </div>

            <div className="mt-3 pt-3 border-t border-white/5 space-y-2">
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <input
                  type="password"
                  placeholder="Enter Gemini API Key (AIzaSy...)"
                  value={geminiKey}
                  onChange={(e) => setGeminiKey(e.target.value)}
                  className="sm:col-span-2 px-3 py-1.5 rounded-lg bg-black/40 border border-white/10 text-xs text-white placeholder-slate-600 outline-none"
                />
                <button
                  onClick={handleSaveGemini}
                  disabled={testingId === 'gemini' || !geminiKey.trim()}
                  className="flex items-center justify-center space-x-1 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs font-medium text-white transition disabled:opacity-50"
                >
                  <RefreshCw className={`w-3.5 h-3.5 ${testingId === 'gemini' ? 'animate-spin' : ''}`} />
                  <span>Save & Test Key</span>
                </button>
              </div>
              {testResult.gemini && (
                <div
                  className={`text-xs flex items-center space-x-1.5 ${
                    testResult.gemini.ok ? 'text-emerald-400' : 'text-amber-400'
                  }`}
                >
                  {testResult.gemini.ok ? <CheckCircle2 className="w-3.5 h-3.5" /> : <AlertCircle className="w-3.5 h-3.5" />}
                  <span>{testResult.gemini.message}</span>
                </div>
              )}
            </div>
          </div>

          {/* 4. Outbound Tunnel Companion */}
          <div className="p-4 rounded-xl border bg-black/30 border-white/5 space-y-3">
            <div className="flex items-start justify-between">
              <div className="flex items-start space-x-3">
                <div className="w-8 h-8 rounded-lg bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 shrink-0 mt-0.5">
                  <Radio className="w-4 h-4" />
                </div>
                <div>
                  <div className="flex items-center space-x-2">
                    <h3 className="text-sm font-semibold text-white">Outbound Tunnel Companion Bridge</h3>
                    <span className="text-[10px] text-cyan-400 font-mono">ws://localhost:1234/ws</span>
                  </div>
                  <p className="text-xs text-slate-400 mt-1">
                    Connects local IDEs (Antigravity IDE, Cursor, Claude Code) to execute local shell commands & filesystem tools securely without opening inbound ports.
                  </p>
                </div>
              </div>
              <button
                onClick={handleTestTunnelExec}
                disabled={isTunnelExecuting}
                className="flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-cyan-600/20 hover:bg-cyan-600/30 border border-cyan-500/30 text-cyan-300 text-xs font-medium transition"
              >
                <Terminal className="w-3.5 h-3.5" />
                <span>Test Remote Exec</span>
              </button>
            </div>

            <div className="bg-black/50 rounded-lg p-2.5 border border-white/5 text-[11px] font-mono text-slate-300 flex items-center justify-between">
              <span>CLI Command to launch tunnel on your machine:</span>
              <code className="text-indigo-400 bg-black/60 px-2 py-0.5 rounded border border-white/5">
                npm --workspace=apps/tunnel-cli run start
              </code>
            </div>

            {tunnelTestOutput && (
              <div className="p-2.5 rounded-lg bg-black/60 border border-white/10 text-xs font-mono text-slate-300 whitespace-pre-wrap">
                {tunnelTestOutput}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
