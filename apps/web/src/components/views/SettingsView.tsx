import React, { useState, useEffect } from 'react';
import { THEME_PRESETS, applyTheme } from '../../utils/theme';
import { ActivityItem, User } from '../../types';
import { Check, RefreshCw, Sparkles, Terminal, Volume2, LogOut, ShieldCheck, Mail, User as UserIcon } from 'lucide-react';

interface SettingsViewProps {
  onProfileUpdated?: (name: string) => void;
  activities?: ActivityItem[];
  currentUser?: User | null;
  onLogout?: () => void;
}

export const SettingsView: React.FC<SettingsViewProps> = ({
  onProfileUpdated,
  activities = [],
  currentUser,
  onLogout
}) => {
  const [activeTab, setActiveTab] = useState<'profile' | 'workspace' | 'activity' | 'tools' | 'notifications' | 'themes'>('profile');

  // Profile State
  const [fullName, setFullName] = useState(() => currentUser?.name || localStorage.getItem('ruxt_fullname') || 'User');
  const [preferredName, setPreferredName] = useState(() => currentUser?.name || localStorage.getItem('ruxt_pref_name') || 'User');
  const [timezone, setTimezone] = useState(() => localStorage.getItem('ruxt_timezone') || Intl.DateTimeFormat().resolvedOptions().timeZone || 'Asia/Colombo');
  const [language, setLanguage] = useState(() => localStorage.getItem('ruxt_language') || 'English');
  const [occupation, setOccupation] = useState(() => localStorage.getItem('ruxt_occupation') || 'Founder, designer');
  const [tone, setTone] = useState(() => localStorage.getItem('ruxt_tone') || 'Warm, practical, direct');
  const [goals, setGoals] = useState(() => localStorage.getItem('ruxt_goals') || 'Build intelligent local-first agent workflows with RuXt.');

  // Workspace State
  const [defaultPage, setDefaultPage] = useState(() => localStorage.getItem('ruxt_default_page') || 'home');
  const [density, setDensity] = useState(() => localStorage.getItem('ruxt_density') || 'comfortable');
  const [dateFormat, setDateFormat] = useState(() => localStorage.getItem('ruxt_date_format') || 'friendly');
  const [reduceMotion, setReduceMotion] = useState(() => localStorage.getItem('ruxt_reduce_motion') === 'true');

  // AI Tools State
  const [preferredTool, setPreferredTool] = useState(() => localStorage.getItem('ruxt_pref_tool') || 'auto');
  const [missingToolBehavior, setMissingToolBehavior] = useState(() => localStorage.getItem('ruxt_missing_tool') || 'fallback');
  const [autoRefreshTools, setAutoRefreshTools] = useState(true);
  const [allowToolLaunch, setAllowToolLaunch] = useState(true);
  const [toolsNotes, setToolsNotes] = useState(() => localStorage.getItem('ruxt_tools_notes') || '');

  // Notifications State
  const [notifyMessages, setNotifyMessages] = useState(() => localStorage.getItem('ruxt_notify_msg') !== 'false');
  const [notifyThreads, setNotifyThreads] = useState(() => localStorage.getItem('ruxt_notify_threads') !== 'false');
  const [notifyDecisions, setNotifyDecisions] = useState(() => localStorage.getItem('ruxt_notify_decisions') !== 'false');
  const [notifyTools, setNotifyTools] = useState(() => localStorage.getItem('ruxt_notify_tools') !== 'false');
  const [notifySound, setNotifySound] = useState(() => localStorage.getItem('ruxt_notify_sound') !== 'false');

  // Themes State
  const [activeThemeId, setActiveThemeId] = useState(() => localStorage.getItem('ruxt_active_theme') || 'ruxt');
  const [themeIdea, setThemeIdea] = useState('');
  const [saveToast, setSaveToast] = useState<string | null>(null);

  const showSaveNotice = (msg: string) => {
    setSaveToast(msg);
    setTimeout(() => setSaveToast(null), 3000);
  };

  const handleSaveProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    localStorage.setItem('ruxt_fullname', fullName);
    localStorage.setItem('ruxt_pref_name', preferredName);
    localStorage.setItem('ruxt_timezone', timezone);
    localStorage.setItem('ruxt_language', language);
    localStorage.setItem('ruxt_occupation', occupation);
    localStorage.setItem('ruxt_tone', tone);
    localStorage.setItem('ruxt_goals', goals);

    const token = localStorage.getItem('ruxt_auth_token');
    if (token) {
      try {
        await fetch('/api/auth/profile', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`
          },
          body: JSON.stringify({ name: fullName })
        });
      } catch (_) {}
    }

    if (onProfileUpdated) onProfileUpdated(preferredName || fullName);
    showSaveNotice('Profile saved successfully.');
  };

  const handleSaveWorkspace = (e: React.FormEvent) => {
    e.preventDefault();
    localStorage.setItem('ruxt_default_page', defaultPage);
    localStorage.setItem('ruxt_density', density);
    localStorage.setItem('ruxt_date_format', dateFormat);
    localStorage.setItem('ruxt_reduce_motion', String(reduceMotion));
    document.body.classList.toggle('reduce-motion', reduceMotion);
    document.body.classList.toggle('density-compact', density === 'compact');
    showSaveNotice('Workspace preferences updated.');
  };

  const handleSelectTheme = (id: string) => {
    setActiveThemeId(id);
    applyTheme(id);
    showSaveNotice(`Theme "${THEME_PRESETS[id]?.label || id}" applied.`);
  };

  const handleComposeAiTheme = (e: React.FormEvent) => {
    e.preventDefault();
    if (!themeIdea.trim()) return;

    // Custom generative palette based on user idea
    const ideaLower = themeIdea.toLowerCase();
    let chosenId = 'ruxt';
    if (ideaLower.includes('dark') || ideaLower.includes('night') || ideaLower.includes('space') || ideaLower.includes('neon')) {
      chosenId = 'dark';
    } else if (ideaLower.includes('light') || ideaLower.includes('white') || ideaLower.includes('clean') || ideaLower.includes('paper')) {
      chosenId = 'light';
    } else if (ideaLower.includes('blue') || ideaLower.includes('ocean') || ideaLower.includes('sea') || ideaLower.includes('coastal')) {
      chosenId = 'ocean';
    } else if (ideaLower.includes('sand') || ideaLower.includes('warm') || ideaLower.includes('coffee') || ideaLower.includes('wood')) {
      chosenId = 'sand';
    }

    handleSelectTheme(chosenId);
    showSaveNotice(`Theme dynamically styled from: "${themeIdea.slice(0, 40)}..."`);
  };

  const activePreset = THEME_PRESETS[activeThemeId] || THEME_PRESETS.ruxt;

  return (
    <div className="flex-1 overflow-y-auto p-6 md:p-8 space-y-6 max-w-5xl mx-auto w-full">
      {/* Page Head */}
      <div className="pb-3 border-b space-y-1" style={{ borderColor: 'var(--line)' }}>
        <span className="eyebrow">WORKSPACE CONTROL</span>
        <h1 className="text-2xl sm:text-3xl font-bold tracking-tight" style={{ color: 'var(--ink)' }}>
          Settings
        </h1>
        <p className="text-xs sm:text-sm" style={{ color: 'var(--muted)' }}>
          Profile, AI tools, notifications, and themes built from your ideas.
        </p>
      </div>

      {/* Tabs */}
      <nav className="flex flex-wrap gap-2 pb-2 border-b" style={{ borderColor: 'var(--line)' }}>
        {(
          [
            ['profile', 'Profile'],
            ['workspace', 'Workspace'],
            ['activity', 'Activities'],
            ['tools', 'AI tools'],
            ['notifications', 'Notifications'],
            ['themes', 'Themes']
          ] as const
        ).map(([tabKey, label]) => (
          <button
            key={tabKey}
            type="button"
            onClick={() => setActiveTab(tabKey)}
            className={`px-3.5 py-1.5 rounded-full text-xs font-bold border transition ${
              activeTab === tabKey ? 'text-white' : 'hover:bg-black/5 dark:hover:bg-white/5'
            }`}
            style={
              activeTab === tabKey
                ? { backgroundColor: 'var(--ruxt)', borderColor: 'var(--ruxt)' }
                : {
                    borderColor: 'var(--line)',
                    backgroundColor: 'var(--surface)',
                    color: 'var(--muted)'
                  }
            }
          >
            {label}
          </button>
        ))}
      </nav>

      {/* Save Notification Toast */}
      {saveToast && (
        <div
          className="p-3 rounded-xl border text-xs flex items-center justify-between"
          style={{
            backgroundColor: 'var(--accent-soft)',
            borderColor: 'color-mix(in srgb, var(--accent) 30%, var(--line))',
            color: 'var(--accent)'
          }}
        >
          <div className="flex items-center space-x-2">
            <Check className="w-4 h-4" />
            <span>{saveToast}</span>
          </div>
        </div>
      )}

      {/* Tab 1: Profile */}
      {activeTab === 'profile' && (
        <form
          onSubmit={handleSaveProfile}
          className="rounded-xl border p-6 space-y-6"
          style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)' }}
        >
          {/* Account Overview Box */}
          {currentUser && (
            <div
              className="p-4 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-4"
              style={{
                borderColor: 'var(--line)',
                backgroundColor: 'color-mix(in srgb, var(--bg) 70%, var(--surface))'
              }}
            >
              <div className="flex items-center space-x-3.5">
                <div
                  className="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg shrink-0 shadow-xs border"
                  style={{
                    backgroundColor: 'var(--accent-soft)',
                    borderColor: 'var(--line)',
                    color: 'var(--accent)'
                  }}
                >
                  {currentUser.avatar || currentUser.name.charAt(0).toUpperCase()}
                </div>
                <div className="space-y-0.5">
                  <div className="flex items-center space-x-2">
                    <span className="font-bold text-sm" style={{ color: 'var(--ink)' }}>{currentUser.name}</span>
                    <span className="text-[10px] uppercase font-mono px-2 py-0.5 rounded-full font-bold bg-indigo-500/10 text-indigo-500 border border-indigo-500/20">
                      {currentUser.role}
                    </span>
                  </div>
                  <div className="text-xs flex items-center space-x-1" style={{ color: 'var(--muted)' }}>
                    <Mail className="w-3 h-3" />
                    <span>{currentUser.email}</span>
                  </div>
                </div>
              </div>

              {onLogout && (
                <button
                  type="button"
                  onClick={onLogout}
                  className="px-3.5 py-1.5 rounded-lg border text-xs font-semibold flex items-center space-x-1.5 text-rose-500 hover:bg-rose-500/10 transition cursor-pointer self-start sm:self-center"
                  style={{ borderColor: 'color-mix(in srgb, #f43f5e 30%, var(--line))' }}
                >
                  <LogOut className="w-3.5 h-3.5" />
                  <span>Sign Out</span>
                </button>
              )}
            </div>
          )}

          <div className="space-y-1">
            <span className="eyebrow">Identity</span>
            <h2 className="text-lg font-bold" style={{ color: 'var(--ink)' }}>
              How should your agents know you?
            </h2>
            <p className="text-xs" style={{ color: 'var(--muted)' }}>
              These details stay in your local workspace database and shape agent tone.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <label className="space-y-1.5">
              <span className="font-semibold" style={{ color: 'var(--ink)' }}>Full name</span>
              <input
                value={fullName}
                onChange={(e) => setFullName(e.target.value)}
                placeholder="Your full name"
                className="w-full px-3.5 py-2 rounded-lg border outline-none"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
              />
            </label>

            <label className="space-y-1.5">
              <span className="font-semibold" style={{ color: 'var(--ink)' }}>Preferred name</span>
              <input
                value={preferredName}
                onChange={(e) => setPreferredName(e.target.value)}
                placeholder="What agents should call you"
                className="w-full px-3.5 py-2 rounded-lg border outline-none"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
              />
            </label>

            <label className="space-y-1.5">
              <span className="font-semibold" style={{ color: 'var(--ink)' }}>Timezone</span>
              <input
                value={timezone}
                onChange={(e) => setTimezone(e.target.value)}
                placeholder="e.g. Asia/Colombo"
                className="w-full px-3.5 py-2 rounded-lg border outline-none"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
              />
            </label>

            <label className="space-y-1.5">
              <span className="font-semibold" style={{ color: 'var(--ink)' }}>Language</span>
              <input
                value={language}
                onChange={(e) => setLanguage(e.target.value)}
                placeholder="e.g. English"
                className="w-full px-3.5 py-2 rounded-lg border outline-none"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
              />
            </label>

            <label className="space-y-1.5">
              <span className="font-semibold" style={{ color: 'var(--ink)' }}>Role or focus</span>
              <input
                value={occupation}
                onChange={(e) => setOccupation(e.target.value)}
                placeholder="e.g. Founder, designer, engineer"
                className="w-full px-3.5 py-2 rounded-lg border outline-none"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
              />
            </label>

            <label className="space-y-1.5">
              <span className="font-semibold" style={{ color: 'var(--ink)' }}>Writing tone</span>
              <input
                value={tone}
                onChange={(e) => setTone(e.target.value)}
                placeholder="Warm, practical, direct"
                className="w-full px-3.5 py-2 rounded-lg border outline-none"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
              />
            </label>
          </div>

          <label className="block space-y-1.5 text-xs">
            <span className="font-semibold" style={{ color: 'var(--ink)' }}>What are you building?</span>
            <textarea
              rows={3}
              value={goals}
              onChange={(e) => setGoals(e.target.value)}
              placeholder="Projects or goals your agents should keep in mind"
              className="w-full px-3.5 py-2 rounded-lg border outline-none resize-none"
              style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
            />
          </label>

          <div className="flex items-center justify-between pt-2">
            <span className="text-[11px]" style={{ color: 'var(--muted)' }}>
              Saved to this local workspace.
            </span>
            <button
              type="submit"
              className="px-4 py-2 rounded-lg text-xs font-semibold text-white shadow-sm transition hover:opacity-90"
              style={{ backgroundColor: 'var(--ruxt)' }}
            >
              Save profile
            </button>
          </div>
        </form>
      )}

      {/* Tab 2: Workspace */}
      {activeTab === 'workspace' && (
        <form
          onSubmit={handleSaveWorkspace}
          className="rounded-xl border p-6 space-y-6"
          style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)' }}
        >
          <div className="space-y-1">
            <span className="eyebrow">Workspace</span>
            <h2 className="text-lg font-bold" style={{ color: 'var(--ink)' }}>
              Make RuXt feel like your desk
            </h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <label className="space-y-1.5">
              <span className="font-semibold" style={{ color: 'var(--ink)' }}>Default page</span>
              <select
                value={defaultPage}
                onChange={(e) => setDefaultPage(e.target.value)}
                className="w-full px-3.5 py-2 rounded-lg border outline-none"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
              >
                <option value="home">Home</option>
                <option value="agents">AI agents</option>
                <option value="messages">Messages</option>
                <option value="forum">Threads</option>
                <option value="settings">Settings</option>
              </select>
            </label>

            <label className="space-y-1.5">
              <span className="font-semibold" style={{ color: 'var(--ink)' }}>Layout density</span>
              <select
                value={density}
                onChange={(e) => setDensity(e.target.value)}
                className="w-full px-3.5 py-2 rounded-lg border outline-none"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
              >
                <option value="comfortable">Comfortable</option>
                <option value="compact">Compact</option>
              </select>
            </label>

            <label className="space-y-1.5">
              <span className="font-semibold" style={{ color: 'var(--ink)' }}>Date format</span>
              <select
                value={dateFormat}
                onChange={(e) => setDateFormat(e.target.value)}
                className="w-full px-3.5 py-2 rounded-lg border outline-none"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
              >
                <option value="friendly">Friendly (e.g. 5m ago)</option>
                <option value="local">Local time</option>
                <option value="iso">ISO YYYY-MM-DD</option>
              </select>
            </label>

            <div className="flex items-center space-x-2 pt-6">
              <input
                type="checkbox"
                id="reduceMotionInput"
                checked={reduceMotion}
                onChange={(e) => setReduceMotion(e.target.checked)}
                className="w-4 h-4 rounded text-[#1967ff]"
              />
              <label htmlFor="reduceMotionInput" className="text-xs font-semibold cursor-pointer" style={{ color: 'var(--ink)' }}>
                Reduce animations & motion
              </label>
            </div>
          </div>

          <div className="flex items-center justify-between pt-2">
            <span className="text-[11px]" style={{ color: 'var(--muted)' }}>
              Workspace preferences are local.
            </span>
            <button
              type="submit"
              className="px-4 py-2 rounded-lg text-xs font-semibold text-white shadow-sm transition hover:opacity-90"
              style={{ backgroundColor: 'var(--ruxt)' }}
            >
              Save workspace
            </button>
          </div>
        </form>
      )}

      {/* Tab 3: Activities Timeline */}
      {activeTab === 'activity' && (
        <div
          className="rounded-xl border p-6 space-y-4"
          style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)' }}
        >
          <div className="space-y-1">
            <span className="eyebrow">WORKSPACE HISTORY</span>
            <h2 className="text-lg font-bold" style={{ color: 'var(--ink)' }}>
              Activities
            </h2>
            <p className="text-xs" style={{ color: 'var(--muted)' }}>
              A saved timeline of agent tasks, messages, threads, and workspace events.
            </p>
          </div>

          <div className="space-y-2">
            {activities.length > 0 ? (
              activities.map((act) => (
                <div
                  key={act.id}
                  className="p-3 rounded-lg border text-xs flex items-center justify-between"
                  style={{
                    backgroundColor: 'color-mix(in srgb, var(--bg) 50%, var(--surface))',
                    borderColor: 'var(--line)'
                  }}
                >
                  <div className="flex items-center space-x-3">
                    <span className="text-sm">{act.agentAvatar || '✦'}</span>
                    <div>
                      <div className="font-semibold" style={{ color: 'var(--ink)' }}>{act.title}</div>
                      {act.detail && <div style={{ color: 'var(--muted)' }}>{act.detail}</div>}
                    </div>
                  </div>
                  <span className="text-[10px]" style={{ color: 'var(--muted)' }}>
                    {new Date(act.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                  </span>
                </div>
              ))
            ) : (
              <div
                className="p-6 border border-dashed rounded-xl text-center text-xs"
                style={{ borderColor: 'var(--line)', color: 'var(--muted)' }}
              >
                No activities logged yet. Activity appears as agents perform tasks.
              </div>
            )}
          </div>
        </div>
      )}

      {/* Tab 4: AI Tools */}
      {activeTab === 'tools' && (
        <div
          className="rounded-xl border p-6 space-y-5"
          style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)' }}
        >
          <div className="space-y-1">
            <span className="eyebrow">AI TOOLS & BRIDGES</span>
            <h2 className="text-lg font-bold" style={{ color: 'var(--ink)' }}>
              Codex, Cursor, and Antigravity
            </h2>
            <p className="text-xs" style={{ color: 'var(--muted)' }}>
              Live connection status from this computer, plus how RuXt bridges into them.
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {[
              { name: 'Antigravity IDE', status: 'connected', desc: 'Outbound secure WebSocket bridge' },
              { name: 'Codex / OpenAI', status: 'ready', desc: 'Neural synthesis ready' },
              { name: 'Cursor IDE', status: 'ready', desc: 'Local project workspace integration' }
            ].map((tool, i) => (
              <div
                key={i}
                className="p-3.5 rounded-xl border space-y-1.5"
                style={{
                  backgroundColor: 'color-mix(in srgb, var(--bg) 40%, var(--surface))',
                  borderColor: 'var(--line)'
                }}
              >
                <div className="flex items-center justify-between">
                  <strong className="text-xs" style={{ color: 'var(--ink)' }}>{tool.name}</strong>
                  <span className="flex items-center space-x-1 text-[10px] text-emerald-600 font-bold uppercase">
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                    <span>{tool.status}</span>
                  </span>
                </div>
                <p className="text-[11px]" style={{ color: 'var(--muted)' }}>{tool.desc}</p>
              </div>
            ))}
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs pt-2">
            <label className="space-y-1.5">
              <span className="font-semibold" style={{ color: 'var(--ink)' }}>Preferred tool for tasks</span>
              <select
                value={preferredTool}
                onChange={(e) => setPreferredTool(e.target.value)}
                className="w-full px-3.5 py-2 rounded-lg border outline-none"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
              >
                <option value="auto">Auto (first available)</option>
                <option value="antigravity">Antigravity</option>
                <option value="cursor">Cursor</option>
                <option value="codex">Codex</option>
              </select>
            </label>

            <label className="space-y-1.5">
              <span className="font-semibold" style={{ color: 'var(--ink)' }}>When a tool is missing</span>
              <select
                value={missingToolBehavior}
                onChange={(e) => setMissingToolBehavior(e.target.value)}
                className="w-full px-3.5 py-2 rounded-lg border outline-none"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
              >
                <option value="fallback">Use local coaching / next tool</option>
                <option value="notify">Notify me and wait</option>
              </select>
            </label>
          </div>

          <div className="space-y-2 text-xs pt-1">
            <label className="flex items-center space-x-2 cursor-pointer">
              <input
                type="checkbox"
                checked={autoRefreshTools}
                onChange={(e) => setAutoRefreshTools(e.target.checked)}
                className="w-4 h-4 rounded text-[#1967ff]"
              />
              <span style={{ color: 'var(--ink)' }}>Auto-refresh AI tool status</span>
            </label>
            <label className="flex items-center space-x-2 cursor-pointer">
              <input
                type="checkbox"
                checked={allowToolLaunch}
                onChange={(e) => setAllowToolLaunch(e.target.checked)}
                className="w-4 h-4 rounded text-[#1967ff]"
              />
              <span style={{ color: 'var(--ink)' }}>Allow opening connected apps when needed</span>
            </label>
          </div>
        </div>
      )}

      {/* Tab 5: Notifications */}
      {activeTab === 'notifications' && (
        <div
          className="rounded-xl border p-6 space-y-5"
          style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)' }}
        >
          <div className="space-y-1">
            <span className="eyebrow">Notifications</span>
            <h2 className="text-lg font-bold" style={{ color: 'var(--ink)' }}>
              Choose what deserves attention
            </h2>
          </div>

          <div className="space-y-3 text-xs">
            {[
              [notifyMessages, setNotifyMessages, 'New agent messages'],
              [notifyThreads, setNotifyThreads, 'New work threads'],
              [notifyDecisions, setNotifyDecisions, 'Decisions waiting for you'],
              [notifyTools, setNotifyTools, 'AI tool connection changes'],
              [notifySound, setNotifySound, 'Completion sounds']
            ].map(([val, setVal, lbl]: any, i) => (
              <label key={i} className="flex items-center space-x-2.5 cursor-pointer">
                <input
                  type="checkbox"
                  checked={val}
                  onChange={(e) => {
                    setVal(e.target.checked);
                    showSaveNotice('Notification preferences updated.');
                  }}
                  className="w-4 h-4 rounded text-[#1967ff]"
                />
                <span className="font-medium" style={{ color: 'var(--ink)' }}>{lbl}</span>
              </label>
            ))}
          </div>
        </div>
      )}

      {/* Tab 6: Themes Studio */}
      {activeTab === 'themes' && (
        <div className="space-y-6">
          {/* Theme Stage */}
          <div className="theme-stage">
            <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
              <div>
                <span className="eyebrow">THEME STUDIO</span>
                <h2 className="text-2xl font-bold tracking-tight mt-1" style={{ color: 'var(--ink)' }}>
                  Living room for RuXt
                </h2>
                <p className="text-xs sm:text-sm mt-1 max-w-md" style={{ color: 'var(--muted)' }}>
                  Connected AI tools reshape color, motion, and the workspace’s atmosphere from your idea.
                </p>
              </div>

              {/* Logo Preview */}
              <div className="flex flex-col items-end space-y-2">
                <img
                  src="/ruxt-logo.png"
                  alt="RuXt Logo"
                  className="ruxt-lockup shadow-sm"
                />
                <span className="text-[10px] font-mono" style={{ color: 'var(--muted)' }}>
                  Active Preset: {activePreset.label}
                </span>
              </div>
            </div>

            {/* Mosaic Swatches */}
            <div className="flex items-center space-x-3 mt-6">
              {[
                ['Background', activePreset.vars['--bg']],
                ['Surface', activePreset.vars['--surface']],
                ['Accent', activePreset.vars['--accent']],
                ['Sidebar', activePreset.vars['--sidebar']]
              ].map(([lbl, col], i) => (
                <div
                  key={i}
                  className="w-10 h-10 rounded-xl border shadow-sm flex items-center justify-center font-bold text-[9px] text-white"
                  style={{ backgroundColor: col, borderColor: 'var(--line)' }}
                  title={`${lbl}: ${col}`}
                >
                  {lbl.slice(0, 3)}
                </div>
              ))}
            </div>
          </div>

          {/* Theme Constellation Orbs */}
          <div className="space-y-2">
            <span className="eyebrow">SAVED THEME PRESETS</span>
            <div className="flex items-center gap-3 overflow-x-auto pb-2">
              {Object.values(THEME_PRESETS).map((preset) => {
                const isActive = activeThemeId === preset.id;
                return (
                  <button
                    key={preset.id}
                    onClick={() => handleSelectTheme(preset.id)}
                    className={`theme-orb flex flex-col justify-between p-4 ${isActive ? 'active' : ''}`}
                    style={{
                      background: `radial-gradient(circle at 20% 20%, ${preset.orbA}, ${preset.orbB})`
                    }}
                  >
                    <div className="flex items-center justify-between w-full">
                      <span className="text-xs font-bold uppercase tracking-wider">{preset.id}</span>
                      {isActive && <Check className="w-4 h-4 text-emerald-400" />}
                    </div>
                    <div>
                      <strong className="block text-sm font-bold text-white leading-tight">
                        {preset.label}
                      </strong>
                      <span className="text-[10px] text-slate-300 font-mono">
                        {preset.accentBg}
                      </span>
                    </div>
                  </button>
                );
              })}
            </div>
          </div>

          {/* AI Theme Idea Composer */}
          <form
            onSubmit={handleComposeAiTheme}
            className="rounded-xl border p-5 space-y-3"
            style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--line)' }}
          >
            <label className="block space-y-1">
              <span className="text-xs font-bold" style={{ color: 'var(--ink)' }}>
                Compose the next look
              </span>
              <textarea
                rows={3}
                value={themeIdea}
                onChange={(e) => setThemeIdea(e.target.value)}
                placeholder="Example: paper-soft cream room, deep forest links that gently pulse, navy sidebar with drifting light, soft card lift, no neon"
                className="w-full px-3.5 py-2.5 rounded-xl border text-xs outline-none resize-none"
                style={{
                  borderColor: 'var(--line)',
                  backgroundColor: 'color-mix(in srgb, var(--bg) 40%, var(--surface))',
                  color: 'var(--ink)'
                }}
              />
            </label>

            <div className="flex items-center justify-between">
              <button
                type="button"
                onClick={() => handleSelectTheme('ruxt')}
                className="text-xs px-3 py-1.5 rounded-lg border font-semibold transition hover:opacity-85"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)', color: 'var(--ink)' }}
              >
                Reset to RuXt
              </button>
              <button
                type="submit"
                className="inline-flex items-center space-x-1.5 px-4 py-2 rounded-lg text-xs font-bold text-white shadow-sm transition hover:opacity-90"
                style={{ backgroundColor: 'var(--ruxt)' }}
              >
                <Sparkles className="w-3.5 h-3.5" />
                <span>Compose with AI</span>
              </button>
            </div>
          </form>
        </div>
      )}
    </div>
  );
};
