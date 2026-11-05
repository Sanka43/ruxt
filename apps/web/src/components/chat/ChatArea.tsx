import React, { useState, useRef, useEffect } from 'react';
import { Agent, ChatMessage } from '../../types';
import {
  Send,
  Sparkles,
  Shield,
  Award,
  BookOpen,
  Check,
  Copy,
  Clock,
  ArrowRight,
  Download,
  Trash2,
  Cpu,
  Layers
} from 'lucide-react';
import { MarkdownContent } from './MarkdownContent';

interface ChatAreaProps {
  agent: Agent;
  messages: ChatMessage[];
  streamingToken: string | null;
  streamingTaskId: string | null;
  onSendMessage: (prompt: string) => void;
  isLoading: boolean;
  chatWidthClass?: string;
  onClearHistory?: () => void;
}

export const ChatArea: React.FC<ChatAreaProps> = ({
  agent,
  messages,
  streamingToken,
  streamingTaskId,
  onSendMessage,
  isLoading,
  chatWidthClass = 'max-w-3xl',
  onClearHistory
}) => {
  const [input, setInput] = useState('');
  const [copiedId, setCopiedId] = useState<string | null>(null);
  const endRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    endRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, streamingToken]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!input.trim() || isLoading) return;
    onSendMessage(input.trim());
    setInput('');
  };

  const handleCopy = (id: string, text: string) => {
    navigator.clipboard.writeText(text);
    setCopiedId(id);
    setTimeout(() => setCopiedId(null), 2000);
  };

  const handleExportChat = () => {
    if (messages.length === 0) return;
    let markdown = `# Chat Transcript with ${agent.name} (${agent.role})\n`;
    markdown += `Generated: ${new Date().toLocaleString()}\n`;
    markdown += `Agent Domain: ${agent.domain || 'general'}\n\n---\n\n`;

    messages.forEach((m) => {
      const sender = m.role === 'user' ? 'You' : agent.name;
      const time = new Date(m.createdAt).toLocaleTimeString();
      markdown += `### ${sender} (${time})\n\n${m.content}\n\n---\n\n`;
    });

    const blob = new Blob([markdown], { type: 'text/markdown;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `chat-${agent.slug}-${Date.now()}.md`;
    a.click();
    URL.revokeObjectURL(url);
  };

  const handleClear = async () => {
    if (!window.confirm(`Clear entire conversation history with ${agent.name}?`)) return;
    try {
      await fetch(`/api/chat/${agent.id}`, { method: 'DELETE' });
      onClearHistory?.();
    } catch (err) {
      console.error('Failed to clear chat:', err);
    }
  };

  const samplePrompts = () => {
    if (agent.domain === 'chess' || agent.slug === 'elena-voss') {
      return [
        'How do I reach Chess Legend level from my current rating?',
        'Analyze my opening repertoire: Sicilian Defense vs 1. e4',
        'Teach me Mikhail Tal dynamic sacrifice intuition',
        'Explain the Lucena position and building the bridge in rook endgames'
      ];
    }
    if (agent.slug === 'noah-reed' || agent.domain === 'compliance') {
      return [
        'Review statutory filing deadlines and penalty schedules',
        'What invoices qualify as deductible R&D expenditure?',
        'Audit cross-border contractor taxes & transfer pricing'
      ];
    }
    if (agent.slug === 'numbers-agent' || agent.domain === 'finance') {
      return [
        'Create a 12-month SaaS recurring revenue model',
        'Generate a multi-tab financial cash flow workbook',
        'Build dynamic formulas for budget variance tracking'
      ];
    }
    if (agent.slug === 'alex-rivera' || agent.domain === 'code') {
      return [
        'Design a scalable event-driven TypeScript architecture',
        'Build a resilient Fastify WebSocket streaming server',
        'Audit SQL performance and drizzle-orm query indexing',
        'Write automated unit and integration tests with vitest'
      ];
    }
    return ['What tasks are within your assigned domain?', 'Run a specialized analysis on my project'];
  };

  return (
    <div
      className="flex-1 flex flex-col h-full overflow-hidden transition-colors"
      style={{
        backgroundColor: 'var(--bg)',
        color: 'var(--ink)'
      }}
    >
      {/* Top Chat Toolbar (Export, Clear, Agent Info) */}
      <div
        className="h-10 px-4 border-b flex items-center justify-between text-xs shrink-0 select-none"
        style={{
          borderColor: 'var(--line)',
          backgroundColor: 'color-mix(in srgb, var(--surface) 90%, var(--bg))'
        }}
      >
        <div className="flex items-center space-x-2 truncate">
          <span className="font-semibold">{agent.name}</span>
          <span className="text-[11px] opacity-60 truncate hidden sm:inline">• {agent.role}</span>
        </div>

        <div className="flex items-center space-x-1.5">
          {messages.length > 0 && (
            <>
              <button
                onClick={handleExportChat}
                className="flex items-center space-x-1 px-2 py-1 rounded-md border hover:opacity-85 transition cursor-pointer"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)' }}
                title="Export chat transcript as Markdown"
              >
                <Download className="w-3 h-3" />
                <span className="hidden md:inline">Export MD</span>
              </button>

              <button
                onClick={handleClear}
                className="flex items-center space-x-1 px-2 py-1 rounded-md border hover:opacity-85 transition cursor-pointer text-rose-500"
                style={{ borderColor: 'var(--line)', backgroundColor: 'var(--surface)' }}
                title="Clear chat history"
              >
                <Trash2 className="w-3 h-3" />
                <span className="hidden md:inline">Clear</span>
              </button>
            </>
          )}
        </div>
      </div>

      {/* Messages stream */}
      <div className="flex-1 overflow-y-auto p-3 sm:p-4 md:p-6 space-y-4">
        <div className={`${chatWidthClass} mx-auto w-full space-y-4`}>
          {messages.length === 0 && !streamingToken ? (
            <div className="min-h-[55vh] flex flex-col items-center justify-center text-center max-w-lg mx-auto space-y-5 select-none px-3">
              {/* Agent Avatar Badge */}
              <div
                className="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shadow-sm border"
                style={{
                  backgroundColor: 'var(--surface)',
                  borderColor: 'var(--line)',
                  color: 'var(--accent)'
                }}
              >
                {agent.avatar}
              </div>

              {/* Agent Title & Role */}
              <div className="space-y-1.5">
                <div className="flex items-center justify-center space-x-2">
                  <h3 className="text-lg font-bold tracking-tight" style={{ color: 'var(--ink)' }}>
                    {agent.name}
                  </h3>
                  {agent.strictGuardrails && (
                    <span
                      className="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium border"
                      style={{
                        backgroundColor: 'var(--accent-soft)',
                        borderColor: 'color-mix(in srgb, var(--accent) 30%, var(--line))',
                        color: 'var(--accent)'
                      }}
                    >
                      <Shield className="w-2.5 h-2.5" />
                      <span>Guardrails Active</span>
                    </span>
                  )}
                </div>
                <p className="text-xs font-semibold" style={{ color: 'var(--ruxt)' }}>
                  {agent.role}
                </p>
                <p className="text-xs max-w-md mx-auto leading-relaxed" style={{ color: 'var(--muted)' }}>
                  {agent.description}
                </p>
              </div>

              {/* Badges for Target Level & Domain */}
              <div className="flex flex-wrap items-center justify-center gap-2">
                {agent.targetLegendLevel && (
                  <div
                    className="flex items-center space-x-1.5 px-3 py-1 rounded-full text-[11px] font-medium border shadow-xs"
                    style={{
                      backgroundColor: 'var(--surface)',
                      borderColor: 'var(--line)',
                      color: 'var(--ink)'
                    }}
                  >
                    <Award className="w-3.5 h-3.5 text-amber-500" />
                    <span>Target: {agent.targetLegendLevel}</span>
                  </div>
                )}
                {agent.specializedKnowledge && agent.specializedKnowledge.length > 0 && (
                  <div
                    className="flex items-center space-x-1.5 px-3 py-1 rounded-full text-[11px] font-medium border shadow-xs"
                    style={{
                      backgroundColor: 'var(--surface)',
                      borderColor: 'var(--line)',
                      color: 'var(--ink)'
                    }}
                  >
                    <BookOpen className="w-3.5 h-3.5 text-indigo-500" />
                    <span>{agent.specializedKnowledge.length} Specialized Modules</span>
                  </div>
                )}
              </div>

              {/* Quick Prompts */}
              <div className="w-full space-y-2 pt-2">
                <span className="eyebrow text-center">
                  SUGGESTED COPILOT TASKS
                </span>
                <div className="grid grid-cols-1 gap-2">
                  {samplePrompts().map((p, idx) => (
                    <button
                      key={idx}
                      onClick={() => onSendMessage(p)}
                      className="group w-full flex items-center justify-between text-left text-xs px-3.5 py-2.5 rounded-xl border transition-all shadow-xs hover:shadow-sm cursor-pointer"
                      style={{
                        backgroundColor: 'var(--surface)',
                        borderColor: 'var(--line)',
                        color: 'var(--ink)'
                      }}
                    >
                      <span className="truncate pr-2">{p}</span>
                      <ArrowRight className="w-3.5 h-3.5 text-slate-400 group-hover:text-[#1967ff] transition shrink-0" />
                    </button>
                  ))}
                </div>
              </div>
            </div>
          ) : (
            <>
              {messages.map((msg) => {
                const isUser = msg.role === 'user';
                const isGuardrailNotice =
                  !isUser && msg.content.includes('Strict Domain Guardrail Enforced');

                return (
                  <div
                    key={msg.id}
                    className={`flex items-start space-x-2.5 sm:space-x-3 ${
                      isUser ? 'justify-end' : 'justify-start'
                    }`}
                  >
                    {!isUser && (
                      <div
                        className="w-8 h-8 rounded-xl flex items-center justify-center text-sm shrink-0 select-none shadow-xs border mt-1 font-bold"
                        style={{
                          backgroundColor: 'var(--accent-soft)',
                          borderColor: 'var(--line)',
                          color: 'var(--accent)'
                        }}
                      >
                        {agent.avatar}
                      </div>
                    )}

                    {/* Message Bubble */}
                    <div
                      className={`max-w-[88%] sm:max-w-xl rounded-2xl p-4 text-xs leading-relaxed transition-all shadow-sm ${
                        isUser
                          ? 'rounded-tr-xs text-white'
                          : isGuardrailNotice
                          ? 'rounded-tl-xs border'
                          : 'rounded-tl-xs border'
                      }`}
                      style={
                        isUser
                          ? {
                              backgroundColor: 'var(--ruxt)',
                              boxShadow: '0 4px 12px color-mix(in srgb, var(--ruxt) 25%, transparent)'
                            }
                          : isGuardrailNotice
                          ? {
                              backgroundColor: '#fffdf7',
                              borderColor: '#e6d9b8',
                              color: 'var(--ink)'
                            }
                          : {
                              backgroundColor: 'var(--surface)',
                              borderColor: 'var(--line)',
                              color: 'var(--ink)'
                            }
                      }
                    >
                      {/* Header bar for assistant replies */}
                      {!isUser && (
                        <div
                          className="flex items-center justify-between pb-2 mb-2.5 border-b text-[11px] select-none"
                          style={{ borderColor: 'var(--line)', color: 'var(--muted)' }}
                        >
                          <div className="flex items-center space-x-1.5">
                            <span className="font-bold tracking-wide" style={{ color: 'var(--ink)' }}>
                              {agent.name}
                            </span>
                            <span className="text-[10px] font-mono">• {agent.role}</span>
                          </div>
                          <button
                            onClick={() => handleCopy(msg.id, msg.content)}
                            className="flex items-center space-x-1 px-1.5 py-0.5 rounded transition hover:opacity-80 cursor-pointer"
                            style={{ backgroundColor: 'color-mix(in srgb, var(--bg) 70%, var(--surface))' }}
                            title="Copy reply text"
                          >
                            {copiedId === msg.id ? (
                              <>
                                <Check className="w-3 h-3 text-emerald-600" />
                                <span className="text-[10px] text-emerald-600 font-medium">Copied</span>
                              </>
                            ) : (
                              <>
                                <Copy className="w-3 h-3 text-slate-500" />
                                <span className="text-[10px] text-slate-500">Copy</span>
                              </>
                            )}
                          </button>
                        </div>
                      )}

                      {/* Rich Markdown Formatted Body */}
                      {isUser ? (
                        <div className="whitespace-pre-wrap leading-relaxed">{msg.content}</div>
                      ) : (
                        <MarkdownContent content={msg.content} />
                      )}
                    </div>
                  </div>
                );
              })}

              {/* Live Streaming Assistant Token Bubble */}
              {streamingToken !== null && (
                <div className="flex items-start space-x-2.5 sm:space-x-3 justify-start">
                  <div
                    className="w-8 h-8 rounded-xl flex items-center justify-center text-sm shrink-0 select-none shadow-sm border mt-1 animate-pulse font-bold"
                    style={{
                      backgroundColor: 'var(--accent-soft)',
                      borderColor: 'var(--line)',
                      color: 'var(--accent)'
                    }}
                  >
                    {agent.avatar}
                  </div>
                  <div
                    className="max-w-[88%] sm:max-w-xl rounded-2xl rounded-tl-xs p-4 text-xs leading-relaxed border shadow-md"
                    style={{
                      backgroundColor: 'var(--surface)',
                      borderColor: 'color-mix(in srgb, var(--accent) 50%, var(--line))',
                      color: 'var(--ink)'
                    }}
                  >
                    <div
                      className="flex items-center justify-between pb-2 mb-2.5 border-b text-[11px] select-none"
                      style={{ borderColor: 'var(--line)', color: 'var(--muted)' }}
                    >
                      <div className="flex items-center space-x-1.5">
                        <span className="font-bold tracking-wide" style={{ color: 'var(--ink)' }}>
                          {agent.name}
                        </span>
                        <span className="text-[10px] font-mono flex items-center space-x-1" style={{ color: 'var(--accent)' }}>
                          <span className="w-1.5 h-1.5 rounded-full animate-ping" style={{ backgroundColor: 'var(--accent)' }} />
                          <span>Streaming Tokens...</span>
                        </span>
                      </div>
                    </div>
                    <MarkdownContent content={streamingToken} isStreaming={true} />
                  </div>
                </div>
              )}
              <div ref={endRef} />
            </>
          )}
        </div>
      </div>

      {/* Input Composer Bar */}
      <div
        className="p-3 md:p-4 border-t select-none transition-colors"
        style={{
          backgroundColor: 'var(--surface)',
          borderColor: 'var(--line)'
        }}
      >
        <div className={`${chatWidthClass} mx-auto w-full`}>
          <form
            onSubmit={handleSubmit}
            className="flex items-center space-x-2 rounded-2xl px-3 py-1.5 border transition shadow-xs focus-within:ring-2"
            style={{
              backgroundColor: 'color-mix(in srgb, var(--bg) 60%, var(--surface))',
              borderColor: 'var(--line)'
            }}
          >
            <input
              type="text"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              disabled={isLoading}
              placeholder={
                agent.strictGuardrails
                  ? `Message ${agent.name} strictly regarding ${agent.domain || agent.role}...`
                  : `Instruct ${agent.name}...`
              }
              className="flex-1 bg-transparent text-xs outline-none px-2 py-1.5 transition"
              style={{ color: 'var(--ink)' }}
            />
            <button
              type="submit"
              disabled={!input.trim() || isLoading}
              className="w-8 h-8 rounded-xl flex items-center justify-center transition disabled:opacity-40 shadow-xs cursor-pointer"
              style={
                input.trim() && !isLoading
                  ? { backgroundColor: 'var(--ruxt)', color: '#fff' }
                  : { backgroundColor: 'color-mix(in srgb, var(--bg) 80%, var(--surface))', color: 'var(--muted)' }
              }
              title="Send message (Enter)"
            >
              <Send className="w-3.5 h-3.5" />
            </button>
          </form>
        </div>
      </div>
    </div>
  );
};
