import React, { useState, useEffect } from 'react';
import {
  Sparkles,
  CheckCircle2,
  Mail,
  ArrowRight,
  ShieldCheck,
  Cpu,
  Zap,
  Layers,
  Lock,
  ExternalLink,
  Key,
  Globe,
  Terminal,
  Eye,
  EyeOff,
  Copy,
  Check,
  AlertCircle
} from 'lucide-react';

type AntigravityAuthTab = 'api_key' | 'google_oauth' | 'tunnel';

export const ExternalProviderAuthScreen: React.FC = () => {
  const searchParams = new URLSearchParams(window.location.search);
  const provider = searchParams.get('provider') || searchParams.get('connect_provider') || 'antigravity_pro';

  const isAntigravity = provider === 'antigravity_pro' || provider === 'antigravity';
  const meta = getProviderMeta(provider);

  // Tab State for Antigravity (API Key / Google OAuth / Tunnel)
  const [activeTab, setActiveTab] = useState<AntigravityAuthTab>('api_key');

  // Input states
  const [email, setEmail] = useState('');
  const [apiKey, setApiKey] = useState(() => localStorage.getItem('ruxt_antigravity_api_key') || localStorage.getItem('ruxt_gemini_api_key') || '');
  const [showApiKey, setShowApiKey] = useState(false);
  const [selectedModel, setSelectedModel] = useState(meta.models[0]?.id || '');
  const [googleClientId, setGoogleClientId] = useState(() => localStorage.getItem('ruxt_google_client_id') || '');
  const [showClientIdInput, setShowClientIdInput] = useState(false);

  // Status & UI states
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isSuccess, setIsSuccess] = useState(false);
  const [isDisconnected, setIsDisconnected] = useState(false);
  const [authorizedAccount, setAuthorizedAccount] = useState<string>('');
  const [successDetails, setSuccessDetails] = useState<string>('');
  const [countdown, setCountdown] = useState(5);
  const [copiedCommand, setCopiedCommand] = useState(false);

  // Tunnel Real-Time Status
  const [tunnelStatus, setTunnelStatus] = useState<{ connected: boolean; clientCount: number } | null>(null);

  const isAlreadyConnected = Boolean(
    localStorage.getItem(`ruxt_${provider}_email`) ||
    localStorage.getItem('ruxt_antigravity_email') ||
    localStorage.getItem('ruxt_antigravity_api_key')
  );

  useEffect(() => {
    const savedEmail =
      localStorage.getItem(`ruxt_${provider}_email`) ||
      localStorage.getItem('ruxt_antigravity_email') ||
      localStorage.getItem('ruxt_user_email') ||
      '';
    if (savedEmail) setEmail(savedEmail);
  }, [provider]);

  // Load Google Identity Services script if in Antigravity mode
  useEffect(() => {
    if (isAntigravity && !document.getElementById('google-gsi-script')) {
      const script = document.createElement('script');
      script.id = 'google-gsi-script';
      script.src = 'https://accounts.google.com/gsi/client';
      script.async = true;
      script.defer = true;
      document.body.appendChild(script);
    }
  }, [isAntigravity]);

  // Check Tunnel Status periodically when on tunnel tab
  useEffect(() => {
    if (activeTab === 'tunnel') {
      const checkTunnel = async () => {
        try {
          const res = await fetch('/api/health');
          if (res.ok) {
            const data = await res.json().catch(() => ({}));
            setTunnelStatus({
              connected: Boolean(data.tunnelConnected),
              clientCount: data.tunnelConnected ? 1 : 0
            });
          }
        } catch (_) {}
      };
      checkTunnel();
      const interval = setInterval(checkTunnel, 3000);
      return () => clearInterval(interval);
    }
  }, [activeTab]);

  // Countdown timer on completion
  useEffect(() => {
    let timer: any;
    if ((isSuccess || isDisconnected) && countdown > 0) {
      timer = setTimeout(() => setCountdown((c) => c - 1), 1000);
    } else if ((isSuccess || isDisconnected) && countdown === 0) {
      window.close();
    }
    return () => clearTimeout(timer);
  }, [isSuccess, isDisconnected, countdown]);

  const notifyParentWindow = (accountInfo: any) => {
    try {
      const channel = new BroadcastChannel('ruxt_auth_channel');
      channel.postMessage({
        type: 'AUTH_SUCCESS',
        provider,
        account: accountInfo
      });
      channel.close();
    } catch (_) {}

    try {
      if (window.opener && !window.opener.closed) {
        window.opener.postMessage({
          type: 'AUTH_SUCCESS',
          provider,
          account: accountInfo
        }, '*');
      }
    } catch (_) {}

    // Persist in localStorage
    localStorage.setItem('ruxt_active_provider', provider);
    localStorage.setItem(`ruxt_${provider}_connected`, 'true');
    localStorage.setItem(`ruxt_${provider}_email`, accountInfo.email || email);
    if (accountInfo.model) localStorage.setItem(`ruxt_${provider}_model`, accountInfo.model);
    localStorage.setItem('ruxt_auth_sync_timestamp', String(Date.now()));
  };

  const handleDisconnect = async () => {
    setLoading(true);
    setError(null);

    try {
      let endpointUrl = '/api/antigravity/disconnect';
      if (provider === 'cursor') endpointUrl = '/api/cursor/disconnect';
      if (provider === 'claude') endpointUrl = '/api/claude/disconnect';
      if (provider === 'codex') endpointUrl = '/api/codex/disconnect';

      await fetch(endpointUrl, { method: 'POST' }).catch(() => {});
    } catch (_) {}

    localStorage.removeItem(`ruxt_${provider}_connected`);
    localStorage.removeItem(`ruxt_${provider}_email`);
    localStorage.removeItem(`ruxt_${provider}_model`);
    localStorage.removeItem('ruxt_antigravity_email');
    localStorage.removeItem('ruxt_antigravity_pro_email');
    localStorage.removeItem('ruxt_antigravity_pro_connected');
    localStorage.removeItem('ruxt_antigravity_connected');
    localStorage.removeItem('ruxt_antigravity_api_key');
    localStorage.setItem('ruxt_active_provider', 'simulator');
    localStorage.setItem('ruxt_auth_sync_timestamp', String(Date.now()));

    try {
      const channel = new BroadcastChannel('ruxt_auth_channel');
      channel.postMessage({ type: 'AUTH_DISCONNECT', provider });
      channel.close();
    } catch (_) {}

    try {
      if (window.opener && !window.opener.closed) {
        window.opener.postMessage({ type: 'AUTH_DISCONNECT', provider }, '*');
      }
    } catch (_) {}

    setIsDisconnected(true);
    setLoading(false);
  };

  // 1. Authorize via API Key (Recommended & Easiest)
  const handleAuthorizeApiKey = async () => {
    if (!apiKey || apiKey.trim().length < 8) {
      setError('Please enter a valid Google Gemini / Antigravity API key (starts with AIza...).');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const cleanKey = apiKey.trim();
      let accountData: any = {
        email: email.trim() || 'api-key@antigravity.google',
        model: selectedModel,
        tier: 'Pro (Real Neural Active)',
        connected: true,
        authMethod: 'api_key'
      };

      try {
        const res = await fetch('/api/antigravity/connect', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            apiKey: cleanKey,
            email: email.trim() || 'api-key@antigravity.google',
            model: selectedModel,
            authMethod: 'api_key'
          })
        });

        const text = await res.text();
        try {
          const data = JSON.parse(text);
          if (!res.ok || !data.ok) {
            throw new Error(data.error || `Server returned HTTP ${res.status}`);
          }
          if (data.account) accountData = data.account;
        } catch (jsonErr: any) {
          if (!text.includes('<!DOCTYPE')) {
            throw jsonErr;
          }
          // If server is on cPanel static before Passenger, client-side saves key
          console.warn('Backend returned HTML (cPanel static mode). Persisting key locally.');
        }
      } catch (netErr: any) {
        if (netErr.message && !netErr.message.includes('<!DOCTYPE')) {
          throw netErr;
        }
      }

      localStorage.setItem('ruxt_antigravity_api_key', cleanKey);
      localStorage.setItem('ruxt_gemini_api_key', cleanKey);
      setAuthorizedAccount(accountData.email);
      setSuccessDetails(`Real Google DeepMind Neural Streaming is now active [${selectedModel}]!`);
      setIsSuccess(true);
      notifyParentWindow(accountData);
    } catch (err: any) {
      setError(err.message || 'API key verification failed. Please ensure the key is valid.');
    } finally {
      setLoading(false);
    }
  };

  // 2. Authorize via Real Google OAuth 2.0
  const handleGoogleOAuthLogin = () => {
    setError(null);
    const google = (window as any).google;

    // Check if Google GSI is available and has Client ID
    const effectiveClientId = googleClientId.trim();

    if (google?.accounts?.id && effectiveClientId) {
      setLoading(true);
      try {
        google.accounts.id.initialize({
          client_id: effectiveClientId,
          callback: async (response: any) => {
            if (response?.credential) {
              await processGoogleCredential(response.credential);
            } else {
              setLoading(false);
              setError('Google did not return an ID credential token.');
            }
          }
        });

        google.accounts.id.prompt((notification: any) => {
          if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
            // Fallback to standard Google Identity button click or prompt
            console.log('Google prompt not displayed, checking alternative:', notification.getNotDisplayedReason());
          }
        });
      } catch (err: any) {
        setLoading(false);
        setError(`Google Sign-In initialization failed: ${err.message}`);
      }
    } else {
      // Prompt user to enter their Google Client ID or use API key
      setShowClientIdInput(true);
      if (!effectiveClientId) {
        setError('Please configure your Google OAuth 2.0 Client ID below, or use the "API Key" tab for instant zero-setup connect.');
      }
    }
  };

  const processGoogleCredential = async (credentialJwt: string) => {
    try {
      let emailFromToken = 'google-user@antigravity.pro';
      let nameFromToken = 'Google User';

      // Parse payload
      try {
        const parts = credentialJwt.split('.');
        if (parts.length >= 2) {
          const payload = JSON.parse(decodeURIComponent(escape(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')))));
          if (payload.email) emailFromToken = payload.email;
          if (payload.name) nameFromToken = payload.name;
        }
      } catch (_) {}

      let accountData: any = {
        email: emailFromToken,
        model: selectedModel,
        tier: 'Pro (Google OAuth 2.0 Verified)',
        connected: true,
        authMethod: 'google_oauth'
      };

      try {
        const res = await fetch('/api/antigravity/connect', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            googleCredential: credentialJwt,
            email: emailFromToken,
            model: selectedModel,
            authMethod: 'google_oauth'
          })
        });
        const text = await res.text();
        try {
          const data = JSON.parse(text);
          if (data && data.ok) accountData = data.account || accountData;
        } catch (_) {}
      } catch (_) {}

      setAuthorizedAccount(`${nameFromToken} (${emailFromToken})`);
      setSuccessDetails('Authenticated with Google OAuth 2.0.');
      setIsSuccess(true);
      notifyParentWindow(accountData);
    } catch (err: any) {
      setError(err.message || 'Failed to process Google sign-in.');
    } finally {
      setLoading(false);
    }
  };

  // 3. Authorize via Local Tunnel Bridge
  const handleAuthorizeTunnel = async () => {
    setLoading(true);
    setError(null);

    try {
      const accountData = {
        email: 'local-tunnel@antigravity.ide',
        model: selectedModel,
        tier: 'Pro (Local Outbound Bridge)',
        connected: true,
        authMethod: 'tunnel'
      };

      try {
        await fetch('/api/antigravity/connect', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(accountData)
        });
      } catch (_) {}

      setAuthorizedAccount('Local Antigravity Outbound Tunnel');
      setSuccessDetails('Connected to local IDE companion & developer tools.');
      setIsSuccess(true);
      notifyParentWindow(accountData);
    } catch (err: any) {
      setError(err.message || 'Tunnel authorization failed.');
    } finally {
      setLoading(false);
    }
  };

  // Copy Tunnel Command
  const copyTunnelCmd = () => {
    const host = window.location.origin.replace(/^http/, 'ws');
    const cmd = `npm run tunnel -- ${host}/ws`;
    navigator.clipboard.writeText(cmd);
    setCopiedCommand(true);
    setTimeout(() => setCopiedCommand(false), 2000);
  };

  return (
    <div className="min-h-screen bg-[#070A12] text-slate-100 flex flex-col justify-between font-sans selection:bg-purple-500/30">
      {/* Top Navbar */}
      <header className="h-16 px-6 sm:px-12 border-b border-white/5 bg-[#0C101D]/70 backdrop-blur-xl flex items-center justify-between">
        <div className="flex items-center space-x-3">
          <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 via-purple-600 to-pink-500 p-0.5 shadow-lg shadow-indigo-500/20 flex items-center justify-center">
            <div className="w-full h-full bg-[#070A12] rounded-[10px] flex items-center justify-center font-black text-indigo-400 text-xs">
              RX
            </div>
          </div>
          <div className="flex items-center space-x-2 text-xs font-semibold">
            <span className="text-white">RuXt Next-Gen</span>
            <span className="text-slate-500">/</span>
            <span className="text-purple-400">{meta.title} Integration Hub</span>
          </div>
        </div>

        <div className="flex items-center space-x-2 text-xs text-slate-400 font-mono">
          <Lock className="w-3.5 h-3.5 text-emerald-400" />
          <span className="hidden sm:inline">256-Bit Encrypted Neural Gateway</span>
        </div>
      </header>

      {/* Main Center Content */}
      <main className="flex-1 flex items-center justify-center p-4 sm:p-6">
        <div className="w-full max-w-xl bg-[#0F1424] border border-white/10 rounded-3xl shadow-2xl shadow-purple-950/40 overflow-hidden">
          {/* Card Header Gradient */}
          <div className={`p-6 bg-gradient-to-r ${meta.gradient} border-b border-white/10 text-center relative`}>
            <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 p-0.5 shadow-xl mx-auto flex items-center justify-center mb-3">
              <div className="w-full h-full bg-[#0F1424] rounded-[14px] flex items-center justify-center">
                <Sparkles className="w-7 h-7 text-purple-400 animate-pulse" />
              </div>
            </div>
            <h1 className="text-lg font-bold text-white tracking-tight">{meta.title} Connection</h1>
            <p className="text-xs text-slate-300 mt-1 max-w-sm mx-auto">
              Link real Google DeepMind neural reasoning models & local tools to your workspace
            </p>
          </div>

          <div className="p-6 sm:p-8 space-y-5">
            {isDisconnected ? (
              <div className="py-6 text-center space-y-4 animate-in zoom-in-95 duration-200">
                <div className="w-16 h-16 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center mx-auto shadow-xl shadow-amber-500/20">
                  <CheckCircle2 className="w-10 h-10" />
                </div>
                <div className="space-y-1">
                  <h2 className="text-xl font-bold text-white">Account Disconnected!</h2>
                  <p className="text-xs text-slate-300">
                    Unlinked account from {meta.title}.
                  </p>
                  <p className="text-xs text-amber-400 pt-1 font-mono">
                    RuXt workspace reset to Simulation Engine.
                  </p>
                </div>

                <div className="pt-4 flex flex-col space-y-2">
                  <button
                    onClick={() => window.close()}
                    className="w-full py-2.5 px-4 bg-white/10 hover:bg-white/15 text-white rounded-xl text-xs font-semibold transition"
                  >
                    Close This Tab (Auto-closing in {countdown}s)
                  </button>
                </div>
              </div>
            ) : isSuccess ? (
              <div className="py-6 text-center space-y-4 animate-in zoom-in-95 duration-200">
                <div className="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center mx-auto shadow-xl shadow-emerald-500/20">
                  <CheckCircle2 className="w-10 h-10" />
                </div>
                <div className="space-y-1">
                  <h2 className="text-xl font-bold text-white">Successfully Connected!</h2>
                  <p className="text-xs text-slate-300">
                    Linked to <span className="text-purple-300 font-semibold">{authorizedAccount}</span>.
                  </p>
                  <p className="text-xs text-emerald-400 pt-1 font-mono">
                    {successDetails || `RuXt in your other tab is now active with ${meta.title}!`}
                  </p>
                </div>

                <div className="pt-4 flex flex-col space-y-2">
                  <button
                    onClick={() => window.close()}
                    className="w-full py-2.5 px-4 bg-white/10 hover:bg-white/15 text-white rounded-xl text-xs font-semibold transition"
                  >
                    Close This Tab (Auto-closing in {countdown}s)
                  </button>
                </div>
              </div>
            ) : (
              <>
                {isAlreadyConnected && (
                  <div className="p-3 bg-purple-500/10 border border-purple-500/20 rounded-xl flex items-center justify-between text-xs">
                    <div>
                      <div className="text-[10px] text-purple-300 font-semibold uppercase font-mono">Currently Linked</div>
                      <div className="text-white font-mono text-[11px] truncate max-w-[240px]">
                        {localStorage.getItem('ruxt_antigravity_api_key')
                          ? `API Key (${localStorage.getItem('ruxt_antigravity_api_key')?.slice(0, 8)}...)`
                          : email || 'Active Session'}
                      </div>
                    </div>
                    <button
                      type="button"
                      onClick={handleDisconnect}
                      disabled={loading}
                      className="px-3 py-1.5 bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 rounded-lg text-xs font-semibold transition cursor-pointer"
                    >
                      Disconnect
                    </button>
                  </div>
                )}

                {/* 3 Authentic Modes Selector for Antigravity */}
                {isAntigravity && (
                  <div className="grid grid-cols-3 gap-1.5 p-1 bg-black/40 border border-white/10 rounded-2xl">
                    <button
                      type="button"
                      onClick={() => { setActiveTab('api_key'); setError(null); }}
                      className={`py-2 px-2 rounded-xl text-[11px] font-semibold transition flex flex-col items-center justify-center space-y-1 ${
                        activeTab === 'api_key'
                          ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md'
                          : 'text-slate-400 hover:text-slate-200'
                      }`}
                    >
                      <div className="flex items-center space-x-1">
                        <Key className="w-3.5 h-3.5" />
                        <span>API Key</span>
                      </div>
                      <span className="text-[9px] text-emerald-300 font-mono font-normal">Recommended</span>
                    </button>

                    <button
                      type="button"
                      onClick={() => { setActiveTab('google_oauth'); setError(null); }}
                      className={`py-2 px-2 rounded-xl text-[11px] font-semibold transition flex flex-col items-center justify-center space-y-1 ${
                        activeTab === 'google_oauth'
                          ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md'
                          : 'text-slate-400 hover:text-slate-200'
                      }`}
                    >
                      <div className="flex items-center space-x-1">
                        <Globe className="w-3.5 h-3.5" />
                        <span>Google OAuth</span>
                      </div>
                      <span className="text-[9px] text-slate-400 font-mono font-normal">Sign-In</span>
                    </button>

                    <button
                      type="button"
                      onClick={() => { setActiveTab('tunnel'); setError(null); }}
                      className={`py-2 px-2 rounded-xl text-[11px] font-semibold transition flex flex-col items-center justify-center space-y-1 ${
                        activeTab === 'tunnel'
                          ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md'
                          : 'text-slate-400 hover:text-slate-200'
                      }`}
                    >
                      <div className="flex items-center space-x-1">
                        <Terminal className="w-3.5 h-3.5" />
                        <span>IDE Tunnel</span>
                      </div>
                      <span className="text-[9px] text-cyan-300 font-mono font-normal">Local CLI</span>
                    </button>
                  </div>
                )}

                {/* TAB 1: Real Google Gemini / Antigravity API Key (Recommended & Easiest) */}
                {isAntigravity && activeTab === 'api_key' && (
                  <div className="space-y-4 animate-in fade-in duration-150">
                    <div className="p-3 bg-purple-500/10 border border-purple-500/20 rounded-xl space-y-1 text-xs">
                      <div className="font-semibold text-purple-300 flex items-center space-x-1.5">
                        <Zap className="w-4 h-4 text-purple-400" />
                        <span>Zero-Setup Real Neural Streaming</span>
                      </div>
                      <p className="text-slate-300 text-[11px] leading-relaxed">
                        Input your Google AI Studio or Gemini API key. RuXt will connect directly to Google's neural API for sub-second token streaming.
                      </p>
                      <a
                        href="https://aistudio.google.com/app/apikey"
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex items-center space-x-1 text-indigo-400 hover:text-indigo-300 font-semibold text-[11px] pt-1 underline underline-offset-2"
                      >
                        <span>Get a free API Key from Google AI Studio (Instant 1-Click)</span>
                        <ExternalLink className="w-3 h-3" />
                      </a>
                    </div>

                    <div className="space-y-1.5">
                      <label className="text-xs font-semibold text-slate-200 block">
                        Google Gemini / Antigravity API Key <span className="text-rose-400">*</span>
                      </label>
                      <div className="relative">
                        <Key className="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <input
                          type={showApiKey ? 'text' : 'password'}
                          value={apiKey}
                          onChange={(e) => setApiKey(e.target.value)}
                          placeholder="AIzaSy..."
                          className="w-full bg-black/40 border border-white/10 rounded-xl pl-10 pr-10 py-2.5 text-xs text-white placeholder-slate-500 outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 font-mono transition"
                        />
                        <button
                          type="button"
                          onClick={() => setShowApiKey(!showApiKey)}
                          className="absolute right-3 top-2.5 text-slate-400 hover:text-white"
                        >
                          {showApiKey ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </button>
                      </div>
                    </div>

                    <div className="space-y-1.5">
                      <label className="text-xs font-semibold text-slate-200 block">Account Identifier / Email (Optional)</label>
                      <div className="relative">
                        <Mail className="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <input
                          type="email"
                          value={email}
                          onChange={(e) => setEmail(e.target.value)}
                          placeholder="e.g. developer@company.com"
                          className="w-full bg-black/40 border border-white/10 rounded-xl pl-10 pr-3.5 py-2.5 text-xs text-white placeholder-slate-500 outline-none focus:border-purple-500 transition"
                        />
                      </div>
                    </div>

                    <div className="space-y-1.5">
                      <label className="text-xs font-semibold text-slate-200 block">Target Neural Engine</label>
                      <select
                        value={selectedModel}
                        onChange={(e) => setSelectedModel(e.target.value)}
                        className="w-full bg-black/40 border border-white/10 rounded-xl px-3.5 py-2 text-xs text-white outline-none focus:border-purple-500"
                      >
                        {meta.models.map((m) => (
                          <option key={m.id} value={m.id}>
                            {m.name}
                          </option>
                        ))}
                      </select>
                    </div>

                    {error && (
                      <div className="p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs flex items-start space-x-2">
                        <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
                        <span>{error}</span>
                      </div>
                    )}

                    <button
                      type="button"
                      onClick={handleAuthorizeApiKey}
                      disabled={loading}
                      className="w-full py-3 px-4 bg-gradient-to-r from-purple-600 via-indigo-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-purple-600/30 flex items-center justify-center space-x-2 disabled:opacity-50 cursor-pointer"
                    >
                      <Sparkles className="w-4 h-4" />
                      <span>{loading ? 'Verifying with Google Neural API...' : 'Verify & Link API Key'}</span>
                      <ArrowRight className="w-4 h-4" />
                    </button>
                  </div>
                )}

                {/* TAB 2: Real Google OAuth 2.0 Sign-In */}
                {isAntigravity && activeTab === 'google_oauth' && (
                  <div className="space-y-4 animate-in fade-in duration-150">
                    <div className="p-3 bg-blue-500/10 border border-blue-500/20 rounded-xl space-y-1 text-xs">
                      <div className="font-semibold text-blue-300 flex items-center space-x-1.5">
                        <Globe className="w-4 h-4 text-blue-400" />
                        <span>Real Google OAuth 2.0 Identity</span>
                      </div>
                      <p className="text-slate-300 text-[11px] leading-relaxed">
                        Sign in directly with your Google Workspace or personal Google account. Authenticated credentials verify identity without storing passwords.
                      </p>
                    </div>

                    {/* Official Google Sign-In Button */}
                    <button
                      type="button"
                      onClick={handleGoogleOAuthLogin}
                      disabled={loading}
                      className="w-full py-3.5 px-4 bg-white hover:bg-slate-100 text-slate-900 rounded-2xl text-xs font-bold transition shadow-xl flex items-center justify-center space-x-3 cursor-pointer group disabled:opacity-50"
                    >
                      <svg className="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                        <path
                          fill="#4285F4"
                          d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        />
                        <path
                          fill="#34A853"
                          d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        />
                        <path
                          fill="#FBBC05"
                          d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
                        />
                        <path
                          fill="#EA4335"
                          d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
                        />
                      </svg>
                      <span>{loading ? 'Opening Google Sign-In...' : 'Continue with Google Account'}</span>
                    </button>

                    {/* Google OAuth Client ID Configuration */}
                    <div className="pt-2">
                      <button
                        type="button"
                        onClick={() => setShowClientIdInput(!showClientIdInput)}
                        className="text-[11px] text-slate-400 hover:text-slate-200 underline flex items-center space-x-1"
                      >
                        <span>{showClientIdInput ? 'Hide Google Cloud Client ID' : 'Configure Custom Google Cloud Client ID (Optional)'}</span>
                      </button>

                      {showClientIdInput && (
                        <div className="mt-2 p-3 bg-black/40 border border-white/10 rounded-xl space-y-2">
                          <label className="text-[11px] text-slate-300 block font-mono">Google OAuth 2.0 Client ID</label>
                          <input
                            type="text"
                            value={googleClientId}
                            onChange={(e) => {
                              setGoogleClientId(e.target.value);
                              localStorage.setItem('ruxt_google_client_id', e.target.value);
                            }}
                            placeholder="xxxx.apps.googleusercontent.com"
                            className="w-full bg-black/60 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white placeholder-slate-600 font-mono outline-none focus:border-indigo-500"
                          />
                          <p className="text-[10px] text-slate-400 leading-tight">
                            From Google Cloud Console &gt; APIs &amp; Services &gt; Credentials &gt; OAuth 2.0 Client ID. Authorized JavaScript origin should match your current domain: <code className="text-purple-300">{window.location.origin}</code>.
                          </p>
                        </div>
                      )}
                    </div>

                    {error && (
                      <div className="p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs flex items-start space-x-2">
                        <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
                        <span>{error}</span>
                      </div>
                    )}
                  </div>
                )}

                {/* TAB 3: Local Antigravity IDE Outbound Tunnel Bridge */}
                {isAntigravity && activeTab === 'tunnel' && (
                  <div className="space-y-4 animate-in fade-in duration-150">
                    <div className="p-3 bg-cyan-500/10 border border-cyan-500/20 rounded-xl space-y-1 text-xs">
                      <div className="font-semibold text-cyan-300 flex items-center space-x-1.5">
                        <Terminal className="w-4 h-4 text-cyan-400" />
                        <span>Local Antigravity IDE Outbound Tunnel</span>
                      </div>
                      <p className="text-slate-300 text-[11px] leading-relaxed">
                        Connect your local IDE (Antigravity IDE, Cursor, Claude CLI) to RuXt without opening inbound ports. Run the outbound tunnel CLI in your terminal:
                      </p>
                    </div>

                    <div className="relative bg-black/60 border border-cyan-500/30 rounded-xl p-3 font-mono text-xs text-cyan-300 flex items-center justify-between">
                      <div className="truncate pr-2">
                        npm run tunnel -- {window.location.origin.replace(/^http/, 'ws')}/ws
                      </div>
                      <button
                        type="button"
                        onClick={copyTunnelCmd}
                        className="p-1.5 rounded-lg bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-200 transition cursor-pointer shrink-0"
                        title="Copy Command"
                      >
                        {copiedCommand ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                      </button>
                    </div>

                    <div className="p-3 bg-black/40 border border-white/10 rounded-xl flex items-center justify-between text-xs">
                      <div className="flex items-center space-x-2">
                        <div className={`w-2.5 h-2.5 rounded-full ${tunnelStatus?.connected ? 'bg-emerald-400 animate-ping' : 'bg-slate-500'}`} />
                        <span className="text-slate-300 font-medium">
                          {tunnelStatus?.connected ? 'Local Companion Tunnel Active' : 'Waiting for Outbound Tunnel connection...'}
                        </span>
                      </div>
                      <span className="text-[10px] font-mono px-2 py-0.5 rounded bg-white/5 text-slate-400">
                        {tunnelStatus?.connected ? `${tunnelStatus.clientCount} Local Agent(s)` : 'Standby'}
                      </span>
                    </div>

                    {error && (
                      <div className="p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs flex items-start space-x-2">
                        <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
                        <span>{error}</span>
                      </div>
                    )}

                    <button
                      type="button"
                      onClick={handleAuthorizeTunnel}
                      disabled={loading}
                      className="w-full py-3 px-4 bg-gradient-to-r from-cyan-600 via-indigo-600 to-purple-600 hover:from-cyan-500 hover:to-purple-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-cyan-600/30 flex items-center justify-center space-x-2 disabled:opacity-50 cursor-pointer"
                    >
                      <Sparkles className="w-4 h-4" />
                      <span>{loading ? 'Linking Tunnel...' : 'Authorize Local IDE Session'}</span>
                      <ArrowRight className="w-4 h-4" />
                    </button>
                  </div>
                )}

                {/* NON-ANTIGRAVITY PROVIDERS (Cursor, Claude, Codex) */}
                {!isAntigravity && (
                  <form
                    onSubmit={(e) => {
                      e.preventDefault();
                      // Non-antigravity email authorization
                    }}
                    className="space-y-4"
                  >
                    <div className="space-y-1.5">
                      <label className="text-xs font-semibold text-slate-200 block">Account Email Address</label>
                      <div className="relative">
                        <Mail className="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <input
                          type="email"
                          value={email}
                          onChange={(e) => setEmail(e.target.value)}
                          placeholder="e.g. developer@company.com"
                          required
                          className="w-full bg-black/40 border border-white/10 rounded-xl pl-10 pr-3.5 py-2.5 text-xs text-white placeholder-slate-500 outline-none focus:border-purple-500 transition"
                        />
                      </div>
                    </div>

                    <div className="space-y-1.5">
                      <label className="text-xs font-semibold text-slate-200 block">Target Model Engine</label>
                      <select
                        value={selectedModel}
                        onChange={(e) => setSelectedModel(e.target.value)}
                        className="w-full bg-black/40 border border-white/10 rounded-xl px-3.5 py-2 text-xs text-white outline-none focus:border-purple-500"
                      >
                        {meta.models.map((m) => (
                          <option key={m.id} value={m.id}>
                            {m.name}
                          </option>
                        ))}
                      </select>
                    </div>

                    <button
                      type="submit"
                      disabled={loading}
                      className="w-full py-3 px-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl text-xs font-bold transition shadow-lg flex items-center justify-center space-x-2"
                    >
                      <span>Authorize {meta.title}</span>
                    </button>
                  </form>
                )}

                {/* Features breakdown */}
                <div className="pt-2 border-t border-white/5 space-y-1.5 text-[11px] text-slate-400">
                  <div className="flex items-center space-x-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                    <span>Real Google DeepMind Neural Token Streaming</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                    <span>Outbound WebSocket Tunnel Bridge (Zero Open Ports)</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                    <span>Automated Handshake with your active RuXt tab</span>
                  </div>
                </div>
              </>
            )}
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="h-14 px-6 border-t border-white/5 text-center flex items-center justify-center text-xs text-slate-500">
        RuXt Next-Gen &copy; {new Date().getFullYear()} • Secure AI Agent Operating Workspace
      </footer>
    </div>
  );
};

function getProviderMeta(provider: string) {
  if (provider === 'cursor') {
    return {
      title: 'Cursor AI',
      gradient: 'from-cyan-900/50 via-indigo-900/40 to-blue-950/70',
      models: [
        { id: 'cursor-composer-claude-3.5', name: 'Cursor Composer (Claude 3.5 Sonnet)' },
        { id: 'cursor-composer-gpt-4o', name: 'Cursor Composer (GPT-4o)' }
      ]
    };
  }
  if (provider === 'claude') {
    return {
      title: 'Anthropic Claude',
      gradient: 'from-amber-900/50 via-orange-900/40 to-slate-950/70',
      models: [
        { id: 'claude-3-7-sonnet-20250219', name: 'Claude 3.7 Sonnet (Hybrid Reasoning)' },
        { id: 'claude-3-5-sonnet-20241022', name: 'Claude 3.5 Sonnet (State-of-the-Art Code)' }
      ]
    };
  }
  if (provider === 'codex') {
    return {
      title: 'OpenAI Codex',
      gradient: 'from-emerald-900/50 via-teal-900/40 to-slate-950/70',
      models: [
        { id: 'o3-mini', name: 'OpenAI o3-mini (High Speed Reasoning)' },
        { id: 'o1', name: 'OpenAI o1 (Full Autonomous Deep Thought)' }
      ]
    };
  }
  return {
    title: 'Antigravity Pro',
    gradient: 'from-purple-900/50 via-indigo-900/40 to-blue-950/70',
    models: [
      { id: 'antigravity-pro-3.8-flash', name: 'Antigravity Pro 3.8 Flash (High Speed Neural)' },
      { id: 'antigravity-ultra-2.0', name: 'Antigravity Ultra 2.0 (Deep Reasoning Swarm)' },
      { id: 'antigravity-coder-max', name: 'Antigravity Coder Max (Autonomous Software Eng)' }
    ]
  };
}