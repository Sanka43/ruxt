import React, { useState, useEffect } from 'react';
import {
  X,
  Sparkles,
  Zap,
  ShieldCheck,
  CheckCircle2,
  Mail,
  ArrowRight,
  ExternalLink,
  Cpu,
  Layers,
  Check
} from 'lucide-react';

interface AntigravityConnectModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConnected: (accountInfo: any) => void;
  currentEmail?: string;
  isAlreadyConnected?: boolean;
}

export const AntigravityConnectModal: React.FC<AntigravityConnectModalProps> = ({
  isOpen,
  onClose,
  onConnected,
  currentEmail = '',
  isAlreadyConnected = false
}) => {
  const [email, setEmail] = useState(currentEmail);
  const [model, setModel] = useState('antigravity-pro-3.8-flash');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isSuccess, setIsSuccess] = useState(false);
  const [connectedData, setConnectedData] = useState<any>(null);

  useEffect(() => {
    if (isOpen) {
      setEmail(currentEmail || localStorage.getItem('ruxt_antigravity_email') || '');
      setIsSuccess(false);
      setError(null);
    }
  }, [isOpen, currentEmail]);

  if (!isOpen) return null;

  const handleConnect = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email || !email.includes('@')) {
      setError('Please enter a valid Antigravity account email address.');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const res = await fetch('/api/antigravity/connect', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: email.trim(),
          model
        })
      });

      const data = await res.json();
      if (!res.ok || !data.ok) {
        throw new Error(data.error || 'Failed to authenticate Antigravity Pro subscription.');
      }

      // Persist in localStorage
      localStorage.setItem('ruxt_antigravity_email', email.trim());
      localStorage.setItem('ruxt_antigravity_model', model);
      localStorage.setItem('ruxt_active_provider', 'antigravity_pro');

      setConnectedData(data.account);
      setIsSuccess(true);
      onConnected(data.account);

      setTimeout(() => {
        setIsSuccess(false);
        onClose();
      }, 1600);
    } catch (err: any) {
      setError(err.message || 'Connection failed. Please check network.');
    } finally {
      setLoading(false);
    }
  };

  const handleDisconnect = async () => {
    setLoading(true);
    try {
      await fetch('/api/antigravity/disconnect', { method: 'POST' });
      localStorage.removeItem('ruxt_antigravity_email');
      onConnected({ connected: false });
      onClose();
    } catch (_) {
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-in fade-in duration-200">
      <div className="relative w-full max-w-lg bg-[#0F1422] border border-indigo-500/30 rounded-3xl shadow-2xl shadow-indigo-900/40 overflow-hidden flex flex-col">
        {/* Top Decorative Banner */}
        <div className="relative h-28 bg-gradient-to-r from-purple-900/60 via-indigo-900/50 to-blue-950/80 p-6 flex items-center justify-between border-b border-white/10 overflow-hidden">
          <div className="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(99,102,241,0.25),transparent_60%)] pointer-events-none" />
          
          <div className="relative z-10 flex items-center space-x-3.5">
            {/* RuXt Logo */}
            <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 p-0.5 shadow-lg shadow-indigo-500/30 flex items-center justify-center">
              <div className="w-full h-full bg-[#0F1422] rounded-[14px] flex items-center justify-center font-black text-indigo-400 text-sm tracking-tighter">
                RX
              </div>
            </div>

            {/* Pulse Link */}
            <div className="flex items-center space-x-1 px-1 text-indigo-400">
              <div className="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-ping" />
              <div className="w-6 h-[1.5px] bg-gradient-to-r from-indigo-500 to-purple-400" />
              <div className="w-1.5 h-1.5 rounded-full bg-purple-400" />
            </div>

            {/* Antigravity Logo */}
            <div className="w-11 h-11 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 p-0.5 shadow-lg shadow-purple-500/30 flex items-center justify-center">
              <div className="w-full h-full bg-[#0F1422] rounded-[14px] flex items-center justify-center text-purple-300">
                <Sparkles className="w-5 h-5 text-purple-400 animate-pulse" />
              </div>
            </div>

            <div>
              <div className="flex items-center space-x-2">
                <h2 className="text-base font-bold text-white tracking-tight">Antigravity Pro</h2>
                <span className="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-gradient-to-r from-purple-500/30 to-indigo-500/30 border border-purple-400/40 text-purple-300">
                  PRO SUITE
                </span>
              </div>
              <p className="text-[11px] text-slate-300">Cloud-to-Local Neural Bridge</p>
            </div>
          </div>

          <button
            onClick={onClose}
            className="relative z-10 p-2 text-slate-400 hover:text-white rounded-xl hover:bg-white/10 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Modal Body */}
        <div className="p-6 space-y-5">
          {isSuccess ? (
            <div className="py-8 text-center space-y-3 animate-in zoom-in-95 duration-200">
              <div className="w-14 h-14 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center mx-auto shadow-lg shadow-emerald-500/20">
                <CheckCircle2 className="w-8 h-8" />
              </div>
              <h3 className="text-lg font-bold text-white">Antigravity Pro Linked!</h3>
              <p className="text-xs text-slate-300 max-w-sm mx-auto">
                Verified subscription for <span className="text-indigo-400 font-semibold">{email}</span>. Active engine set to Antigravity Pro Neural Brain.
              </p>
            </div>
          ) : (
            <>
              <div>
                <p className="text-xs text-slate-300 leading-relaxed">
                  Connect your registered Antigravity Pro subscription to activate Google DeepMind cognitive reasoning, sub-second code generation, and multi-agent coordination.
                </p>
              </div>

              {/* Feature Highlights Grid */}
              <div className="grid grid-cols-2 gap-2.5 p-3.5 bg-black/40 border border-white/5 rounded-2xl">
                <div className="flex items-start space-x-2">
                  <Cpu className="w-4 h-4 text-purple-400 shrink-0 mt-0.5" />
                  <div>
                    <div className="text-[11px] font-semibold text-white">Neural Reasoning</div>
                    <div className="text-[10px] text-slate-400">DeepMind 3.8 & Ultra thinking</div>
                  </div>
                </div>
                <div className="flex items-start space-x-2">
                  <ShieldCheck className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                  <div>
                    <div className="text-[11px] font-semibold text-white">Unlimited Pro Tokens</div>
                    <div className="text-[10px] text-slate-400">Uncapped multi-turn pipelines</div>
                  </div>
                </div>
                <div className="flex items-start space-x-2">
                  <Layers className="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" />
                  <div>
                    <div className="text-[11px] font-semibold text-white">Agent Swarms</div>
                    <div className="text-[10px] text-slate-400">Multi-agent parallel execution</div>
                  </div>
                </div>
                <div className="flex items-start space-x-2">
                  <Zap className="w-4 h-4 text-amber-400 shrink-0 mt-0.5" />
                  <div>
                    <div className="text-[11px] font-semibold text-white">Instant Sync</div>
                    <div className="text-[10px] text-slate-400">Bridges with local Antigravity IDE</div>
                  </div>
                </div>
              </div>

              {/* Form */}
              <form onSubmit={handleConnect} className="space-y-4">
                <div className="space-y-1.5">
                  <label className="text-xs font-semibold text-slate-200 flex items-center justify-between">
                    <span>Antigravity Pro Account Email</span>
                    <span className="text-[10px] text-purple-400 font-mono">Pro / Main License</span>
                  </label>
                  <div className="relative">
                    <Mail className="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                    <input
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      placeholder="e.g. isanka@antigravity.pro or your-name@gmail.com"
                      required
                      className="w-full bg-black/50 border border-white/10 rounded-xl pl-10 pr-3.5 py-2.5 text-xs text-white placeholder-slate-500 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                    />
                  </div>
                  <p className="text-[10px] text-slate-400">
                    Use the email address linked to your purchased Antigravity Pro subscription.
                  </p>
                </div>

                <div className="space-y-1.5">
                  <label className="text-xs font-semibold text-slate-200 block">Pro Neural Model Target</label>
                  <select
                    value={model}
                    onChange={(e) => setModel(e.target.value)}
                    className="w-full bg-black/50 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white outline-none focus:border-indigo-500"
                  >
                    <option value="antigravity-pro-3.8-flash">Antigravity Pro 3.8 Flash (High Speed Neural)</option>
                    <option value="antigravity-ultra-2.0">Antigravity Ultra 2.0 (Deep Reasoning Swarm)</option>
                    <option value="antigravity-coder-max">Antigravity Coder Max (Autonomous Software Eng)</option>
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
                    <span>{loading ? 'Authenticating Pro Subscription...' : 'Authorize & Link Account'}</span>
                    <ArrowRight className="w-3.5 h-3.5" />
                  </button>

                  {isAlreadyConnected && (
                    <button
                      type="button"
                      onClick={handleDisconnect}
                      disabled={loading}
                      className="py-2.5 px-3 bg-white/5 hover:bg-rose-500/20 hover:text-rose-300 text-slate-400 rounded-xl text-xs font-medium border border-white/5 transition"
                    >
                      Unlink
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
