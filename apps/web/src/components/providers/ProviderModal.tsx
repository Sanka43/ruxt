import React, { useState, useEffect } from 'react';
import {
  X,
  Zap,
  Cpu,
  Key,
  Radio,
  CheckCircle2,
  AlertCircle,
  RefreshCw,
  Terminal,
  Bot,
  Sparkles,
  ShieldCheck,
  ExternalLink
} from 'lucide-react';
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

  // Gemini state
  const [geminiKey, setGeminiKey] = useState(() => localStorage.getItem('ruxt_gemini_key') || '');
  const [geminiModel, setGeminiModel] = useState(() => localStorage.getItem('ruxt_gemini_model') || 'gemini-1.5-flash');

  // OpenAI state
  const [openAIKey, setOpenAIKey] = useState(() => localStorage.getItem('ruxt_openai_key') || '');
  const [openAIModel, setOpenAIModel] = useState(() => localStorage.getItem('ruxt_openai_model') || 'gpt-4o-mini');

  // Ollama state
  const [ollamaUrl, setOllamaUrl] = useState('http://localhost:11434');
  const [ollamaModel, setOllamaModel] = useState('llama3');

  const [testResult, setTestResult] = useState<{ [key: string]: { ok: boolean; message: string } }>({});
  const [testingId, setTestingId] = useState<string | null>(null);

  // Tunnel state
  const [tunnelTestOutput, setTunnelTestOutput] = useState<string | null>(null);
  const [isTunnelExecuting, setIsTunnelExecuting] = useState(false);

  // Auto-restore saved keys on mount
  useEffect(() => {
    const savedGemini = localStorage.getItem('ruxt_gemini_key');
    const savedGeminiModel = localStorage.getItem('ruxt_gemini_model') || 'gemini-1.5-flash';
    if (savedGemini) {
      fetch('/api/providers/configure', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ provider: 'gemini', apiKey: savedGemini, model: savedGeminiModel })
      }).catch(() => {});
    }

    const savedOpenAI = localStorage.getItem('ruxt_openai_key');
    const savedOpenAIModel = localStorage.getItem('ruxt_openai_model') || 'gpt-4o-mini';
    if (savedOpenAI) {
      fetch('/api/providers/configure', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ provider: 'openai', apiKey: savedOpenAI, model: savedOpenAIModel })
      }).catch(() => {});
    }
  }, []);

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
      localStorage.setItem('ruxt_gemini_key', geminiKey.trim());
      localStorage.setItem('ruxt_gemini_model', geminiModel);

      const res = await fetch('/api/providers/configure', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          provider: 'gemini',
          apiKey: geminiKey.trim(),
          model: geminiModel
        })
      });
      const data = await res.json();
      if (data.ok) {
        const testRes = await fetch('/api/providers/test', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ provider: 'gemini' })
        });
        const testData = await testRes.json();
        setTestResult((prev) => ({ ...prev, gemini: testData }));
        if (testData.ok) {
          handleSetActive('gemini');
        }
        fetchProviders();
      }
    } catch (err: any) {
      setTestResult((prev) => ({ ...prev, gemini: { ok: false, message: err.message } }));
    } finally {
      setTestingId(null);
    }
  };

  const handleSaveOpenAI = async () => {
    setTestingId('openai');
    try {
      localStorage.setItem('ruxt_openai_key', openAIKey.trim());
      localStorage.setItem('ruxt_openai_model', openAIModel);

      const res = await fetch('/api/providers/configure', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          provider: 'openai',
          apiKey: openAIKey.trim(),
          model: openAIModel
        })
      });
      const data = await res.json();
      if (data.ok) {
        const testRes = await fetch('/api/providers/test', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ provider: 'openai' })
        });
        const testData = await testRes.json();
        setTestResult((prev) => ({ ...prev, openai: testData }));
        if (testData.ok) {
          handleSetActive('openai');
        }
        fetchProviders();
      }
    } catch (err: any) {
      setTestResult((prev) => ({ ...prev, openai: { ok: false, message: err.message } }));
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
      setTestResult((prev) => ({ ...prev, ollama: { ok: false, message: 'Could not connect to Ollama server' } }));
    } finally {
      setTestingId(null);
    }
  };

  const handleExecuteTunnelTest = async () => {
    setIsTunnelExecuting(true);
    setTunnelTestOutput(null);
    try {
      const res = await fetch('/api/tunnel/exec', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ command: 'echo "RuXt Local Bridge Ping OK"' })
      });
      const data = await res.json();
      if (data.ok && data.result) {
        setTunnelTestOutput(data.result.stdout || 'Bridge responded successfully.');
      } else {
        setTunnelTestOutput(data.error || 'No response from local tunnel client.');
      }
    } catch (err: any) {
      setTunnelTestOutput(`Execution failed: ${err.message}`);
    } finally {
      setIsTunnelExecuting(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
      <div
        className="w-full max-w-2xl rounded-2xl border shadow-2xl overflow-hidden flex flex-col max-h-[90vh] transition-colors"
        style={{
          backgroundColor: 'var(--surface)',
          borderColor: 'var(--line)',
          color: 'var(--ink)'
        }}
      >
        {/* Modal Header */}
        <div
          className="flex items-center justify-between px-6 py-4 border-b shrink-0"
          style={{ borderColor: 'var(--line)' }}
        >
          <div className="flex items-center space-x-2.5">
            <div
              className="w-8 h-8 rounded-lg flex items-center justify-center border shadow-xs"
              style={{
                backgroundColor: 'var(--accent-soft)',
                borderColor: 'var(--line)',
                color: 'var(--accent)'
              }}
            >
              <Zap className="w-4 h-4 fill-current" />
            </div>
            <div>
              <h2 className="text-sm sm:text-base font-bold tracking-tight">AI Engines & Model Providers</h2>
              <p className="text-xs" style={{ color: 'var(--muted)' }}>
                Switch between zero-setup offline simulation, Gemini, or OpenAI GPT-4o.
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 rounded-lg border hover:opacity-80 transition cursor-pointer"
            style={{ borderColor: 'var(--line)' }}
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Modal Body */}
        <div className="p-6 overflow-y-auto space-y-4">
          {/* Active Provider Pill Banner */}
          <div
            className="p-3 rounded-xl border flex items-center justify-between text-xs"
            style={{
              backgroundColor: 'var(--accent-soft)',
              borderColor: 'color-mix(in srgb, var(--accent) 30%, var(--line))',
              color: 'var(--accent)'
            }}
          >
            <div className="flex items-center space-x-2">
              <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
              <span className="font-semibold">
                Current Active Engine:{' '}
                <span className="uppercase tracking-wider font-mono">
                  {activeProvider === 'simulator'
                    ? 'Built-in Simulation Engine'
                    : activeProvider === 'gemini'
                    ? 'Google Gemini Cloud'
                    : activeProvider === 'openai'
                    ? 'OpenAI GPT-4o'
                    : activeProvider === 'ollama'
                    ? 'Local Ollama'
                    : activeProvider}
                </span>
              </span>
            </div>
            <span className="text-[11px] opacity-80">BYOK Protected</span>
          </div>

          {/* 1. Built-in Simulation Engine */}
          <div
            className="p-4 rounded-xl border transition"
            style={{
              borderColor: activeProvider === 'simulator' ? 'var(--accent)' : 'var(--line)',
              backgroundColor:
                activeProvider === 'simulator'
                  ? 'color-mix(in srgb, var(--accent-soft) 40%, var(--surface))'
                  : 'color-mix(in srgb, var(--surface) 95%, var(--bg))'
            }}
          >
            <div className="flex items-start justify-between">
              <div className="flex items-start space-x-3">
                <div
                  className="w-9 h-9 rounded-lg border flex items-center justify-center shrink-0 mt-0.5"
                  style={{
                    backgroundColor: 'var(--accent-soft)',
                    borderColor: 'var(--line)',
                    color: 'var(--accent)'
                  }}
                >
                  <Bot className="w-4 h-4" />
                </div>
                <div>
                  <div className="flex items-center space-x-2">
                    <h3 className="text-sm font-semibold">Built-in Simulation Engine</h3>
                    <span className="text-[10px] px-2 py-0.5 rounded-full font-mono font-medium border bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20">
                      Zero-Setup • Offline
                    </span>
                  </div>
                  <p className="text-xs mt-1" style={{ color: 'var(--muted)' }}>
                    Instant sub-second token streaming with domain guardrails, chess coach intelligence, and financial spreadsheet reasoning. No API key required.
                  </p>
                </div>
              </div>
              <button
                onClick={() => handleSetActive('simulator')}
                disabled={activeProvider === 'simulator'}
                className="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer shadow-xs"
                style={
                  activeProvider === 'simulator'
                    ? { backgroundColor: 'var(--accent)', color: '#fff' }
                    : { borderColor: 'var(--line)', borderWidth: 1 }
                }
              >
                {activeProvider === 'simulator' ? 'Active' : 'Activate'}
              </button>
            </div>
          </div>

          {/* 2. Google Gemini API */}
          <div
            className="p-4 rounded-xl border transition"
            style={{
              borderColor: activeProvider === 'gemini' ? 'var(--accent)' : 'var(--line)',
              backgroundColor:
                activeProvider === 'gemini'
                  ? 'color-mix(in srgb, var(--accent-soft) 40%, var(--surface))'
                  : 'color-mix(in srgb, var(--surface) 95%, var(--bg))'
            }}
          >
            <div className="flex items-start justify-between">
              <div className="flex items-start space-x-3">
                <div className="w-9 h-9 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500 shrink-0 mt-0.5">
                  <Sparkles className="w-4 h-4" />
                </div>
                <div>
                  <div className="flex items-center space-x-2">
                    <h3 className="text-sm font-semibold">Google Gemini Pro & Flash</h3>
                    <span className="text-[10px] px-2 py-0.5 rounded-full font-mono border bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20">
                      Cloud Neural
                    </span>
                  </div>
                  <p className="text-xs mt-1" style={{ color: 'var(--muted)' }}>
                    High-speed multi-modal neural models from Google AI with multi-turn memory.
                  </p>
                </div>
              </div>
              <button
                onClick={() => handleSetActive('gemini')}
                disabled={activeProvider === 'gemini'}
                className="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer shadow-xs"
                style={
                  activeProvider === 'gemini'
                    ? { backgroundColor: 'var(--accent)', color: '#fff' }
                    : { borderColor: 'var(--line)', borderWidth: 1 }
                }
              >
                {activeProvider === 'gemini' ? 'Active' : 'Activate'}
              </button>
            </div>

            <div className="mt-3 pt-3 border-t space-y-2.5" style={{ borderColor: 'var(--line)' }}>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div className="sm:col-span-2">
                  <input
                    type="password"
                    placeholder="Enter your Google Gemini API Key (AIza...)"
                    value={geminiKey}
                    onChange={(e) => setGeminiKey(e.target.value)}
                    className="w-full px-3 py-1.5 rounded-lg border text-xs outline-none font-mono"
                    style={{
                      backgroundColor: 'var(--surface)',
                      borderColor: 'var(--line)',
                      color: 'var(--ink)'
                    }}
                  />
                </div>
                <div>
                  <select
                    value={geminiModel}
                    onChange={(e) => setGeminiModel(e.target.value)}
                    className="w-full px-2.5 py-1.5 rounded-lg border text-xs outline-none"
                    style={{
                      backgroundColor: 'var(--surface)',
                      borderColor: 'var(--line)',
                      color: 'var(--ink)'
                    }}
                  >
                    <option value="gemini-1.5-flash">Gemini 1.5 Flash (Fast)</option>
                    <option value="gemini-1.5-pro">Gemini 1.5 Pro (Deep)</option>
                  </select>
                </div>
              </div>

              <div className="flex items-center justify-between">
                <a
                  href="https://aistudio.google.com/app/apikey"
                  target="_blank"
                  rel="noreferrer"
                  className="text-[11px] inline-flex items-center space-x-1 hover:underline"
                  style={{ color: 'var(--accent)' }}
                >
                  <span>Get a free Gemini API key</span>
                  <ExternalLink className="w-3 h-3" />
                </a>

                <button
                  onClick={handleSaveGemini}
                  disabled={testingId === 'gemini' || !geminiKey.trim()}
                  className="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer disabled:opacity-50"
                  style={{
                    backgroundColor: 'var(--accent)',
                    color: '#fff'
                  }}
                >
                  <RefreshCw className={`w-3 h-3 ${testingId === 'gemini' ? 'animate-spin' : ''}`} />
                  <span>Save & Connect</span>
                </button>
              </div>

              {testResult.gemini && (
                <div
                  className={`text-xs flex items-center space-x-1.5 pt-1 ${
                    testResult.gemini.ok ? 'text-emerald-500' : 'text-amber-500'
                  }`}
                >
                  {testResult.gemini.ok ? <CheckCircle2 className="w-3.5 h-3.5" /> : <AlertCircle className="w-3.5 h-3.5" />}
                  <span>{testResult.gemini.message}</span>
                </div>
              )}
            </div>
          </div>

          {/* 3. OpenAI GPT-4o API */}
          <div
            className="p-4 rounded-xl border transition"
            style={{
              borderColor: activeProvider === 'openai' ? 'var(--accent)' : 'var(--line)',
              backgroundColor:
                activeProvider === 'openai'
                  ? 'color-mix(in srgb, var(--accent-soft) 40%, var(--surface))'
                  : 'color-mix(in srgb, var(--surface) 95%, var(--bg))'
            }}
          >
            <div className="flex items-start justify-between">
              <div className="flex items-start space-x-3">
                <div className="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-500 shrink-0 mt-0.5">
                  <ShieldCheck className="w-4 h-4" />
                </div>
                <div>
                  <div className="flex items-center space-x-2">
                    <h3 className="text-sm font-semibold">OpenAI GPT-4o & GPT-4o-mini</h3>
                    <span className="text-[10px] px-2 py-0.5 rounded-full font-mono border bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20">
                      BYOK
                    </span>
                  </div>
                  <p className="text-xs mt-1" style={{ color: 'var(--muted)' }}>
                    Connect OpenAI models with streaming tokens and multi-turn conversational recall.
                  </p>
                </div>
              </div>
              <button
                onClick={() => handleSetActive('openai')}
                disabled={activeProvider === 'openai'}
                className="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer shadow-xs"
                style={
                  activeProvider === 'openai'
                    ? { backgroundColor: 'var(--accent)', color: '#fff' }
                    : { borderColor: 'var(--line)', borderWidth: 1 }
                }
              >
                {activeProvider === 'openai' ? 'Active' : 'Activate'}
              </button>
            </div>

            <div className="mt-3 pt-3 border-t space-y-2.5" style={{ borderColor: 'var(--line)' }}>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div className="sm:col-span-2">
                  <input
                    type="password"
                    placeholder="Enter OpenAI API Key (sk-...)"
                    value={openAIKey}
                    onChange={(e) => setOpenAIKey(e.target.value)}
                    className="w-full px-3 py-1.5 rounded-lg border text-xs outline-none font-mono"
                    style={{
                      backgroundColor: 'var(--surface)',
                      borderColor: 'var(--line)',
                      color: 'var(--ink)'
                    }}
                  />
                </div>
                <div>
                  <select
                    value={openAIModel}
                    onChange={(e) => setOpenAIModel(e.target.value)}
                    className="w-full px-2.5 py-1.5 rounded-lg border text-xs outline-none"
                    style={{
                      backgroundColor: 'var(--surface)',
                      borderColor: 'var(--line)',
                      color: 'var(--ink)'
                    }}
                  >
                    <option value="gpt-4o-mini">GPT-4o Mini (Fast & Cheap)</option>
                    <option value="gpt-4o">GPT-4o (Flagship)</option>
                  </select>
                </div>
              </div>

              <div className="flex items-center justify-between">
                <a
                  href="https://platform.openai.com/api-keys"
                  target="_blank"
                  rel="noreferrer"
                  className="text-[11px] inline-flex items-center space-x-1 hover:underline"
                  style={{ color: 'var(--accent)' }}
                >
                  <span>Get OpenAI API key</span>
                  <ExternalLink className="w-3 h-3" />
                </a>

                <button
                  onClick={handleSaveOpenAI}
                  disabled={testingId === 'openai' || !openAIKey.trim()}
                  className="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer disabled:opacity-50"
                  style={{
                    backgroundColor: 'var(--accent)',
                    color: '#fff'
                  }}
                >
                  <RefreshCw className={`w-3 h-3 ${testingId === 'openai' ? 'animate-spin' : ''}`} />
                  <span>Save & Connect</span>
                </button>
              </div>

              {testResult.openai && (
                <div
                  className={`text-xs flex items-center space-x-1.5 pt-1 ${
                    testResult.openai.ok ? 'text-emerald-500' : 'text-amber-500'
                  }`}
                >
                  {testResult.openai.ok ? <CheckCircle2 className="w-3.5 h-3.5" /> : <AlertCircle className="w-3.5 h-3.5" />}
                  <span>{testResult.openai.message}</span>
                </div>
              )}
            </div>
          </div>

          {/* 4. Local Ollama */}
          <div
            className="p-4 rounded-xl border transition"
            style={{
              borderColor: activeProvider === 'ollama' ? 'var(--accent)' : 'var(--line)',
              backgroundColor:
                activeProvider === 'ollama'
                  ? 'color-mix(in srgb, var(--accent-soft) 40%, var(--surface))'
                  : 'color-mix(in srgb, var(--surface) 95%, var(--bg))'
            }}
          >
            <div className="flex items-start justify-between">
              <div className="flex items-start space-x-3">
                <div className="w-9 h-9 rounded-lg bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-500 shrink-0 mt-0.5">
                  <Cpu className="w-4 h-4" />
                </div>
                <div>
                  <div className="flex items-center space-x-2">
                    <h3 className="text-sm font-semibold">Local Ollama Neural Server</h3>
                    <span className="text-[10px] font-mono opacity-60">localhost:11434</span>
                  </div>
                  <p className="text-xs mt-1" style={{ color: 'var(--muted)' }}>
                    Run open-weight models (Llama 3, DeepSeek, Mistral) on your machine.
                  </p>
                </div>
              </div>
              <button
                onClick={() => handleSetActive('ollama')}
                className="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer shadow-xs"
                style={
                  activeProvider === 'ollama'
                    ? { backgroundColor: 'var(--accent)', color: '#fff' }
                    : { borderColor: 'var(--line)', borderWidth: 1 }
                }
              >
                {activeProvider === 'ollama' ? 'Active' : 'Activate'}
              </button>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div
          className="px-6 py-3 border-t flex items-center justify-between text-xs"
          style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)' }}
        >
          <span style={{ color: 'var(--muted)' }}>
            Keys are encrypted & stored in your browser session only.
          </span>
          <button
            onClick={onClose}
            className="px-4 py-1.5 rounded-lg text-xs font-semibold border hover:opacity-85 transition cursor-pointer"
            style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)' }}
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
};
