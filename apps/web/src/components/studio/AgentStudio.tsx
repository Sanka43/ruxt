import React, { useState } from 'react';
import { Sparkles, Bot, Save, Play, Shield, ShieldCheck, ShieldAlert, Award, BookOpen, Layers, CheckCircle2 } from 'lucide-react';
import { Agent, AgentDomain, CanvasType, CurriculumLevel } from '../../types';

interface AgentStudioProps {
  onAgentCreated: (agent: Agent) => void;
  onClose: () => void;
}

const defaultChessCurriculum: CurriculumLevel[] = [
  {
    id: 'lvl_novice',
    level: 'Tier 1: Novice & Fundamentals',
    eloRange: '800 - 1200 Elo',
    focus: 'Center Control, Piece Development, and Blunder Shield',
    milestones: [
      'Control central squares (e4, d4, e5, d5)',
      'Knights before Bishops development principle',
      'Castle early for King safety',
      'Never leave pieces hanging unprotected'
    ]
  },
  {
    id: 'lvl_club',
    level: 'Tier 2: Club Tactician',
    eloRange: '1200 - 1600 Elo',
    focus: 'Tactical Weaponry, Forks, Pins, Skewers & Opening Repertoire',
    milestones: [
      'Master tactical motifs: Absolute Pins, Royal Forks, Skewers',
      'Discovered attacks and Double Checks',
      'Solidify opening choices (Sicilian, Ruy Lopez, Queen’s Gambit)',
      'Basic King and Pawn endgame mechanics'
    ]
  },
  {
    id: 'lvl_master',
    level: 'Tier 3: Strategic Master',
    eloRange: '1600 - 2000 Elo',
    focus: 'Pawn Structures, Weak Squares, Prophylaxis & Positional Play',
    milestones: [
      'Assess pawn islands, doubled pawns, and backward pawns',
      'Outpost occupation and bishop pair advantage',
      'Prophylactic thinking (preventing opponent counterplay)',
      'Rook endgames: Lucena and Philidor positions'
    ]
  },
  {
    id: 'lvl_legend',
    level: 'Tier 4: Chess Legend',
    eloRange: '2000 - 2800+ Elo (Grandmaster)',
    focus: 'Deep Calculation Trees, Intuitive Sacrifices, Psychological Warfare & Endgame Flawlessness',
    milestones: [
      'Dynamic sacrifices in the style of Mikhail Tal and Garry Kasparov',
      'Flawless endgame conversion in the style of Capablanca and Magnus Carlsen',
      'Deep opening novelties and computer preparation',
      'Time management and psychological resilience under championship pressure'
    ]
  }
];

export const AgentStudio: React.FC<AgentStudioProps> = ({ onAgentCreated, onClose }) => {
  const [name, setName] = useState('');
  const [role, setRole] = useState('');
  const [description, setDescription] = useState('');
  const [avatar, setAvatar] = useState('✨');
  const [domain, setDomain] = useState<AgentDomain>('general');
  const [canvasType, setCanvasType] = useState<CanvasType>('none');
  const [strictGuardrails, setStrictGuardrails] = useState<boolean>(true);
  const [targetLegendLevel, setTargetLegendLevel] = useState<string>('Master / Legend');
  const [systemPrompt, setSystemPrompt] = useState('');
  const [guardrailRedirectMessage, setGuardrailRedirectMessage] = useState('');
  const [curriculum, setCurriculum] = useState<CurriculumLevel[]>([]);
  const [specializedKnowledge, setSpecializedKnowledge] = useState<string[]>([]);
  const [newTag, setNewTag] = useState('');

  // Sandbox state
  const [sandboxPrompt, setSandboxPrompt] = useState('How do I become a chess legend?');
  const [sandboxResponse, setSandboxResponse] = useState('');
  const [sandboxGuardrailStatus, setSandboxGuardrailStatus] = useState<'pass' | 'blocked' | null>(null);
  const [isTesting, setIsTesting] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Apply Presets
  const applyPreset = (presetType: 'chess' | 'finance' | 'compliance' | 'custom') => {
    if (presetType === 'chess') {
      setName('Elena Voss (Grandmaster)');
      setAvatar('♟️');
      setRole('Master Chess Coach & Grandmaster Mentor');
      setDescription('Elite interactive chess coach strictly dedicated to chess strategy, real-time board analysis, tactical drills, and elevating players to Chess Legend level.');
      setDomain('chess');
      setCanvasType('chess');
      setStrictGuardrails(true);
      setTargetLegendLevel('Chess Legend (2800+ Elo)');
      setSystemPrompt('You are Elena Voss, an authoritative and insightful Grandmaster Chess Coach. You speak EXCLUSIVELY about chess, strategy, openings, tactics, and the road to grandmaster mastery. Firmly and politely decline any non-chess questions and pivot back to the 64 squares.');
      setGuardrailRedirectMessage('My sacred dedication is exclusively to your chess mastery and the path to Grandmaster. I do not engage in non-chess subjects. Let us return to the 64 squares — what opening, move, or tactical pattern shall we examine?');
      setCurriculum(defaultChessCurriculum);
      setSpecializedKnowledge([
        'Sicilian Defense (Najdorf, Dragon)',
        'Ruy Lopez & Italian Game',
        'Queen’s Gambit & Slav Defense',
        'Lucena & Philidor Endgames',
        'Mikhail Tal Style Sacrifices',
        'Kasparov & Carlsen Dynamic Technique'
      ]);
      setSandboxPrompt('How do I become a chess legend?');
    } else if (presetType === 'finance') {
      setName('Numbers Agent Pro');
      setAvatar('📊');
      setRole('Chief Financial Modeler & Spreadsheet Architect');
      setDescription('Designs complex financial workbooks, budgets, dynamic formulas, DCF models, and multi-sheet summaries.');
      setDomain('finance');
      setCanvasType('datagrid');
      setStrictGuardrails(false);
      setTargetLegendLevel('Chief Financial Architect');
      setSystemPrompt('You are Numbers Agent. Build clean, validated workbooks with formulas and clear layouts.');
      setGuardrailRedirectMessage('I specialize exclusively in financial modeling and spreadsheets.');
      setCurriculum([]);
      setSpecializedKnowledge(['DCF Valuation Models', 'Multi-tab Consolidation', 'Dynamic Excel/Sheets Formulas']);
      setSandboxPrompt('Structure a 12-month SaaS revenue forecast.');
    } else if (presetType === 'compliance') {
      setName('Noah Reed (Audit Lead)');
      setAvatar('⚖️');
      setRole('Senior Statutory Auditor & Tax Counsel');
      setDescription('Strict, evidence-based compliance partner for statutory deadlines, VAT/tax filing, and internal control auditing.');
      setDomain('compliance');
      setCanvasType('audit');
      setStrictGuardrails(true);
      setTargetLegendLevel('Senior Statutory Auditor');
      setSystemPrompt('You are Noah Reed, a strict and careful compliance adviser. Review risk factors objectively and ensure statutory adherence.');
      setGuardrailRedirectMessage('I only advise on statutory compliance, audit, and tax matters.');
      setCurriculum([]);
      setSpecializedKnowledge(['IFRS / GAAP Compliance', 'Cross-Border VAT Protocols', 'Risk Score Analysis']);
      setSandboxPrompt('What are the risks of omitting transfer pricing documentation?');
    } else {
      setName('');
      setAvatar('🤖');
      setRole('');
      setDescription('');
      setDomain('general');
      setCanvasType('none');
      setStrictGuardrails(false);
      setTargetLegendLevel('Master / Legend');
      setSystemPrompt('');
      setGuardrailRedirectMessage('');
      setCurriculum([]);
      setSpecializedKnowledge([]);
      setSandboxPrompt('');
    }
  };

  const handleAddTag = () => {
    if (newTag.trim() && !specializedKnowledge.includes(newTag.trim())) {
      setSpecializedKnowledge([...specializedKnowledge, newTag.trim()]);
      setNewTag('');
    }
  };

  const handleRemoveTag = (tag: string) => {
    setSpecializedKnowledge(specializedKnowledge.filter((t) => t !== tag));
  };

  // Test sandbox in real-time
  const testSandbox = () => {
    setIsTesting(true);
    setSandboxResponse('');
    setSandboxGuardrailStatus(null);

    const lower = sandboxPrompt.toLowerCase().trim();

    // Guardrail evaluation simulation
    const offTopicKeywords = ['recipe', 'cook', 'cake', 'food', 'weather', 'python', 'javascript', 'write code', 'movie', 'crypto'];
    const hasOffTopic = offTopicKeywords.some((w) => lower.includes(w));
    const chessTerms = ['chess', 'move', 'board', 'pawn', 'knight', 'bishop', 'rook', 'queen', 'king', 'sicilian', 'legend', 'opening', 'elo', 'tactic'];
    const hasChess = chessTerms.some((w) => lower.includes(w));

    const isBlocked = strictGuardrails && (domain === 'chess') && (hasOffTopic || (!hasChess && lower.length > 20));

    setTimeout(() => {
      if (isBlocked) {
        setSandboxGuardrailStatus('blocked');
        setSandboxResponse(
          guardrailRedirectMessage ||
            `🛡️ [Strict Domain Guardrail Enforced]: As your dedicated ${role || 'Chess Coach'}, my sacred focus is exclusively on your ascent to ${targetLegendLevel || 'Chess Legend'} status. I do not engage in off-topic subjects such as outside trivia, cooking, or general programming. Let us return our discipline to the 64 squares — what position or opening shall we master?`
        );
      } else {
        setSandboxGuardrailStatus('pass');
        if (domain === 'chess' || role.toLowerCase().includes('chess')) {
          if (lower.includes('legend') || lower.includes('curriculum')) {
            setSandboxResponse(
              `👑 [Grandmaster Curriculum Engine]: To elevate you to ${targetLegendLevel || 'Chess Legend'}, we progress through 4 disciplined tiers:\n1. Tier 1: Novice (800-1200) - Center control & blunder prevention.\n2. Tier 2: Club (1200-1600) - Tactical armory (pins, forks, skewers).\n3. Tier 3: Master (1600-2000) - Pawn structure, prophylaxis & Lucena endgames.\n4. Tier 4: Legend (2000-2800+) - Mikhail Tal dynamic sacrifices & Capablanca endgame conversion.\nWhich tier matches your current rating?`
            );
          } else {
            setSandboxResponse(
              `♟️ [Master Coach Analysis]: I have evaluated your chess inquiry: "${sandboxPrompt}". Focus on contesting central squares (e4, d4), maintaining piece harmony, and looking for tactical refutations before committing pawn structure.`
            );
          }
        } else {
          setSandboxResponse(
            `[Sandbox Output]: Hello! I am ${name || 'your custom agent'}, acting as ${role || 'AI specialist'}. My persona prompt instructs: "${systemPrompt || 'General assistant'}". Assigned domain: ${domain.toUpperCase()}. Ready for deployment!`
          );
        }
      }
      setIsTesting(false);
    }, 600);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim() || !role.trim()) return;

    setIsSubmitting(true);
    try {
      const res = await fetch('/api/agents', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name,
          role,
          description,
          avatar,
          domain,
          canvasType,
          strictGuardrails,
          targetLegendLevel,
          systemPrompt,
          guardrailRedirectMessage,
          curriculum,
          specializedKnowledge,
          skills: specializedKnowledge.length > 0 ? specializedKnowledge : ['Custom Prompt Engine', 'Local Task Runner']
        })
      });
      const data = await res.json();
      if (data.ok && data.agent) {
        onAgentCreated(data.agent);
        onClose();
      }
    } catch (err) {
      console.error('Failed to create agent:', err);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="flex-1 flex flex-col h-full bg-[#0B0F19] overflow-y-auto p-6 max-w-6xl mx-auto w-full space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between border-b border-white/5 pb-4">
        <div className="flex items-center space-x-3">
          <div className="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
            <Sparkles className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-white">Admin Agent Studio</h2>
            <p className="text-xs text-slate-400">
              Build specialized task agents with strict domain guardrails & legend progression curriculums
            </p>
          </div>
        </div>
        <button
          onClick={onClose}
          className="px-3 py-1.5 rounded-lg bg-surface hover:bg-white/10 text-xs text-slate-300 transition"
        >
          Back to Workspace
        </button>
      </div>

      {/* Preset Quick-Loader Bar */}
      <div className="bg-surface/40 border border-white/5 rounded-2xl p-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div className="flex items-center space-x-2">
          <Layers className="w-4 h-4 text-indigo-400" />
          <span className="text-xs font-semibold text-slate-300 uppercase tracking-wider">
            Specialization Preset Blueprints:
          </span>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            onClick={() => applyPreset('chess')}
            className="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/30 hover:bg-amber-500/20 text-amber-300 text-xs font-medium transition"
          >
            <span>♟️</span>
            <span>Grandmaster Chess Coach</span>
          </button>
          <button
            type="button"
            onClick={() => applyPreset('finance')}
            className="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 hover:bg-emerald-500/20 text-emerald-300 text-xs font-medium transition"
          >
            <span>📊</span>
            <span>Financial Architect</span>
          </button>
          <button
            type="button"
            onClick={() => applyPreset('compliance')}
            className="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-blue-500/10 border border-blue-500/30 hover:bg-blue-500/20 text-blue-300 text-xs font-medium transition"
          >
            <span>⚖️</span>
            <span>Compliance Auditor</span>
          </button>
          <button
            type="button"
            onClick={() => applyPreset('custom')}
            className="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-surface hover:bg-white/10 border border-white/10 text-slate-300 text-xs font-medium transition"
          >
            <span>⚡</span>
            <span>Blank Custom</span>
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Builder Form */}
        <form onSubmit={handleSubmit} className="space-y-5 bg-surface/60 rounded-2xl p-5 border border-white/5">
          <div className="flex items-center justify-between border-b border-white/5 pb-2">
            <h3 className="text-xs font-semibold text-slate-300 uppercase tracking-wider">
              1. Agent Profile & Domain Scope
            </h3>
            <span className="text-[10px] text-indigo-400 font-mono">Domain Assignment</span>
          </div>

          <div className="grid grid-cols-4 gap-3">
            <div className="col-span-3">
              <label className="block text-xs font-medium text-slate-400 mb-1">Agent Name</label>
              <input
                type="text"
                placeholder="e.g. Elena Voss"
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
                className="w-full px-3 py-2 rounded-xl bg-black/40 border border-white/10 text-xs text-white placeholder-slate-600 focus:border-indigo-500 outline-none"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-slate-400 mb-1">Avatar</label>
              <input
                type="text"
                placeholder="Emoji"
                value={avatar}
                onChange={(e) => setAvatar(e.target.value)}
                className="w-full text-center px-3 py-2 rounded-xl bg-black/40 border border-white/10 text-xs text-white focus:border-indigo-500 outline-none"
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-medium text-slate-400 mb-1">Assigned Domain</label>
              <select
                value={domain}
                onChange={(e) => setDomain(e.target.value as AgentDomain)}
                className="w-full px-3 py-2 rounded-xl bg-black/40 border border-white/10 text-xs text-white focus:border-indigo-500 outline-none"
              >
                <option value="chess">Chess Strategy & Coaching</option>
                <option value="finance">Financial Modeling & Spreadsheets</option>
                <option value="compliance">Statutory Compliance & Audit</option>
                <option value="code">Software Architecture & Code</option>
                <option value="general">General / Multi-domain</option>
              </select>
            </div>
            <div>
              <label className="block text-xs font-medium text-slate-400 mb-1">Interactive Canvas</label>
              <select
                value={canvasType}
                onChange={(e) => setCanvasType(e.target.value as CanvasType)}
                className="w-full px-3 py-2 rounded-xl bg-black/40 border border-white/10 text-xs text-white focus:border-indigo-500 outline-none"
              >
                <option value="none">None (Chat Interface Only)</option>
                <option value="chess">Interactive Chessboard (Coaching)</option>
                <option value="datagrid">Interactive Data Grid (Spreadsheets)</option>
                <option value="audit">Compliance & Risk Checklist</option>
              </select>
            </div>
          </div>

          <div>
            <label className="block text-xs font-medium text-slate-400 mb-1">Primary Role & Title</label>
            <input
              type="text"
              placeholder="e.g. Master Chess Coach & Grandmaster Mentor"
              value={role}
              onChange={(e) => setRole(e.target.value)}
              required
              className="w-full px-3 py-2 rounded-xl bg-black/40 border border-white/10 text-xs text-white placeholder-slate-600 focus:border-indigo-500 outline-none"
            />
          </div>

          <div>
            <label className="block text-xs font-medium text-slate-400 mb-1">Short Description</label>
            <input
              type="text"
              placeholder="e.g. Teaches chess from novice up to legend level with real-time board analysis."
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              className="w-full px-3 py-2 rounded-xl bg-black/40 border border-white/10 text-xs text-white placeholder-slate-600 focus:border-indigo-500 outline-none"
            />
          </div>

          {/* Strict Domain Guardrail Box */}
          <div className="bg-indigo-950/20 border border-indigo-500/20 rounded-xl p-3 space-y-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-2">
                <Shield className="w-4 h-4 text-indigo-400" />
                <span className="text-xs font-semibold text-white">Strict Domain Guardrail</span>
              </div>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={strictGuardrails}
                  onChange={(e) => setStrictGuardrails(e.target.checked)}
                  className="sr-only peer"
                />
                <div className="w-9 h-5 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
              </label>
            </div>
            <p className="text-[11px] text-slate-400">
              When enabled, the agent talks <strong className="text-white">strictly about its assigned task/domain</strong> and firmly redirects any off-topic inquiries (e.g. recipes, code, general chit-chat) back to domain mastery.
            </p>
            {strictGuardrails && (
              <div>
                <label className="block text-[11px] font-medium text-slate-400 mb-1">
                  Custom Guardrail Redirection Prompt (Optional)
                </label>
                <textarea
                  rows={2}
                  placeholder="Custom response when user asks an off-topic question..."
                  value={guardrailRedirectMessage}
                  onChange={(e) => setGuardrailRedirectMessage(e.target.value)}
                  className="w-full px-3 py-1.5 rounded-lg bg-black/40 border border-white/10 text-xs text-slate-300 placeholder-slate-600 focus:border-indigo-500 outline-none"
                />
              </div>
            )}
          </div>

          {/* Mastery Level & Curriculum */}
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <label className="text-xs font-medium text-slate-400 flex items-center space-x-1.5">
                <Award className="w-3.5 h-3.5 text-amber-400" />
                <span>Target Mastery / Legend Level</span>
              </label>
              <span className="text-[10px] text-slate-500 font-mono">Curriculum Target</span>
            </div>
            <input
              type="text"
              placeholder="e.g. Chess Legend (2800+ Elo)"
              value={targetLegendLevel}
              onChange={(e) => setTargetLegendLevel(e.target.value)}
              className="w-full px-3 py-2 rounded-xl bg-black/40 border border-white/10 text-xs text-white placeholder-slate-600 focus:border-indigo-500 outline-none"
            />

            {/* Visual Curriculum Tiers */}
            {curriculum.length > 0 && (
              <div className="space-y-2 pt-1">
                <span className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">
                  Configured Progression Curriculum ({curriculum.length} Tiers)
                </span>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  {curriculum.map((lvl) => (
                    <div
                      key={lvl.id}
                      className="bg-black/30 border border-white/5 rounded-lg p-2.5 text-left space-y-1"
                    >
                      <div className="flex items-center justify-between text-xs">
                        <span className="font-semibold text-indigo-300">{lvl.level}</span>
                        <span className="text-[10px] text-amber-400 font-mono">{lvl.eloRange}</span>
                      </div>
                      <p className="text-[10px] text-slate-400 line-clamp-2">{lvl.focus}</p>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Specialized Knowledge Chips */}
          <div className="space-y-2">
            <label className="block text-xs font-medium text-slate-400">Specialized Knowledge Tags & Books</label>
            <div className="flex flex-wrap gap-1.5 mb-2">
              {specializedKnowledge.map((tag) => (
                <span
                  key={tag}
                  className="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-[11px]"
                >
                  <span>{tag}</span>
                  <button
                    type="button"
                    onClick={() => handleRemoveTag(tag)}
                    className="hover:text-red-400 text-slate-400 ml-1 font-bold"
                  >
                    ×
                  </button>
                </span>
              ))}
            </div>
            <div className="flex space-x-2">
              <input
                type="text"
                placeholder="Add topic (e.g. Sicilian Defense, Lucena Endgame)..."
                value={newTag}
                onChange={(e) => setNewTag(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    e.preventDefault();
                    handleAddTag();
                  }
                }}
                className="flex-1 px-3 py-1.5 rounded-lg bg-black/40 border border-white/10 text-xs text-white placeholder-slate-600 outline-none"
              />
              <button
                type="button"
                onClick={handleAddTag}
                className="px-3 py-1.5 rounded-lg bg-surface hover:bg-white/10 text-xs text-slate-300"
              >
                Add
              </button>
            </div>
          </div>

          {/* System Prompt */}
          <div>
            <label className="block text-xs font-medium text-slate-400 mb-1">System Prompt Directives</label>
            <textarea
              rows={4}
              placeholder="Define rules, strict boundaries, tone, and curriculum execution..."
              value={systemPrompt}
              onChange={(e) => setSystemPrompt(e.target.value)}
              className="w-full px-3 py-2 rounded-xl bg-black/40 border border-white/10 text-xs text-white placeholder-slate-600 focus:border-indigo-500 outline-none"
            />
          </div>

          <button
            type="submit"
            disabled={isSubmitting}
            className="w-full flex items-center justify-center space-x-2 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-semibold text-white shadow-lg transition"
          >
            <Save className="w-4 h-4" />
            <span>{isSubmitting ? 'Publishing Agent...' : 'Publish Specialized Agent to Workspace'}</span>
          </button>
        </form>

        {/* Live Test Sandbox */}
        <div className="space-y-4 bg-surface/60 rounded-2xl p-5 border border-white/5 flex flex-col">
          <div className="flex items-center justify-between border-b border-white/5 pb-2">
            <h3 className="text-xs font-semibold text-slate-300 uppercase tracking-wider flex items-center space-x-1.5">
              <Bot className="w-4 h-4 text-emerald-400" />
              <span>Live Guardrail & Sandbox Test</span>
            </h3>
            <span className="text-[10px] text-slate-500 font-mono">Simulated Execution</span>
          </div>

          {/* Preset Test Case Buttons */}
          <div className="space-y-1.5">
            <span className="text-[11px] text-slate-400 block font-medium">Quick-Test Verification Prompts:</span>
            <div className="flex flex-wrap gap-2">
              <button
                type="button"
                onClick={() => setSandboxPrompt('How do I become a chess legend?')}
                className="text-[11px] px-2.5 py-1 rounded-lg bg-indigo-950/40 border border-indigo-500/30 text-indigo-300 hover:bg-indigo-900/50 transition"
              >
                🎯 In-Domain: Road to Legend
              </button>
              <button
                type="button"
                onClick={() => setSandboxPrompt('Explain the Sicilian Defense counterplay')}
                className="text-[11px] px-2.5 py-1 rounded-lg bg-indigo-950/40 border border-indigo-500/30 text-indigo-300 hover:bg-indigo-900/50 transition"
              >
                ♟️ In-Domain: Sicilian Defense
              </button>
              <button
                type="button"
                onClick={() => setSandboxPrompt('Can you give me a recipe for chocolate cake?')}
                className="text-[11px] px-2.5 py-1 rounded-lg bg-red-950/30 border border-red-500/30 text-red-300 hover:bg-red-900/40 transition"
              >
                🛡️ Test Guardrail: Cake Recipe
              </button>
              <button
                type="button"
                onClick={() => setSandboxPrompt('Write a python script to sort numbers')}
                className="text-[11px] px-2.5 py-1 rounded-lg bg-amber-950/30 border border-amber-500/30 text-amber-300 hover:bg-amber-900/40 transition"
              >
                🛡️ Test Guardrail: Write Code
              </button>
            </div>
          </div>

          {/* Sandbox Terminal View */}
          <div className="flex-1 bg-black/40 rounded-xl p-3.5 border border-white/5 flex flex-col justify-between min-h-[340px]">
            <div className="space-y-3">
              <div className="bg-indigo-950/30 border border-indigo-500/20 rounded-lg p-2.5 text-xs text-indigo-300">
                <span className="font-semibold block mb-0.5 text-indigo-400">Sandbox Test Input:</span>
                {sandboxPrompt}
              </div>

              {/* Guardrail badge */}
              {sandboxGuardrailStatus === 'blocked' && (
                <div className="flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-amber-950/40 border border-amber-500/30 text-amber-300 text-xs">
                  <ShieldAlert className="w-4 h-4 text-amber-400 shrink-0" />
                  <span className="font-semibold">Guardrail Intercepted: Off-Domain Inquiry Blocked</span>
                </div>
              )}

              {sandboxGuardrailStatus === 'pass' && (
                <div className="flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 text-xs">
                  <ShieldCheck className="w-4 h-4 text-emerald-400 shrink-0" />
                  <span className="font-semibold">Domain Compliant: Passed Guardrail Inspection</span>
                </div>
              )}

              {isTesting && (
                <div className="text-xs text-slate-400 italic flex items-center space-x-2 py-4">
                  <div className="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-ping" />
                  <span>Evaluating persona directives & domain boundaries...</span>
                </div>
              )}

              {sandboxResponse && !isTesting && (
                <div className="bg-slate-900/80 border border-white/10 rounded-xl p-3 text-xs text-slate-200 whitespace-pre-line leading-relaxed max-h-72 overflow-y-auto">
                  {sandboxResponse}
                </div>
              )}
            </div>

            <div className="mt-4 flex space-x-2">
              <input
                type="text"
                value={sandboxPrompt}
                onChange={(e) => setSandboxPrompt(e.target.value)}
                placeholder="Ask test question..."
                className="flex-1 px-3 py-2 rounded-xl bg-black/60 border border-white/10 text-xs text-white placeholder-slate-600 outline-none focus:border-indigo-500"
              />
              <button
                type="button"
                onClick={testSandbox}
                disabled={isTesting || !sandboxPrompt.trim()}
                className="flex items-center space-x-1 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-semibold text-white transition shadow-lg"
              >
                <Play className="w-3.5 h-3.5" />
                <span>Test Run</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
