import React, { useState } from 'react';
import { Thread, Decision } from '../../types';
import { ChevronUp, ChevronDown, MessageSquare, ArrowLeft, CheckCircle2 } from 'lucide-react';

interface ThreadsViewProps {
  threads: Thread[];
  decisions: Decision[];
  activeHub: 'work' | 'decision' | 'fun';
  onSelectHub: (hub: 'work' | 'decision' | 'fun') => void;
  onVoteThread: (threadId: string, delta: number) => void;
  selectedThreadId?: string | null;
  onSelectThread: (threadId: string | null) => void;
  onAddReply?: (threadId: string, replyText: string) => void;
  onResolveDecision?: (decisionId: string, option: string) => void;
}

export const ThreadsView: React.FC<ThreadsViewProps> = ({
  threads,
  decisions,
  activeHub,
  onSelectHub,
  onVoteThread,
  selectedThreadId,
  onSelectThread,
  onAddReply,
  onResolveDecision
}) => {
  const [replyInput, setReplyInput] = useState('');

  const activeThread = selectedThreadId
    ? threads.find((t) => t.id === selectedThreadId)
    : null;

  const filteredThreads = threads.filter((t) => t.hub === activeHub);

  const handleReplySubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!replyInput.trim() || !activeThread || !onAddReply) return;
    onAddReply(activeThread.id, replyInput.trim());
    setReplyInput('');
  };

  return (
    <div className="flex-1 overflow-y-auto p-6 md:p-8 space-y-6 max-w-4xl mx-auto w-full">
      {/* If viewing a single thread detail */}
      {activeThread ? (
        <div className="space-y-4">
          <button
            onClick={() => onSelectThread(null)}
            className="inline-flex items-center space-x-1.5 text-xs font-semibold text-[#1967ff] hover:underline"
          >
            <ArrowLeft className="w-3.5 h-3.5" />
            <span>Back to threads</span>
          </button>

          <article
            className="rounded-xl border overflow-hidden"
            style={{
              backgroundColor: 'var(--surface)',
              borderColor: 'var(--line)'
            }}
          >
            <div className="grid grid-cols-[48px_1fr]">
              {/* Upvote Column */}
              <div
                className="flex flex-col items-center p-3 space-y-1 border-r"
                style={{
                  backgroundColor: 'color-mix(in srgb, var(--bg) 60%, var(--surface))',
                  borderColor: 'var(--line)'
                }}
              >
                <button
                  onClick={() => onVoteThread(activeThread.id, 1)}
                  className="p-1 hover:text-[#1967ff] transition"
                  title="Upvote"
                >
                  <ChevronUp className="w-4 h-4" />
                </button>
                <span className="text-xs font-bold" style={{ color: 'var(--ink)' }}>
                  {activeThread.score || 1}
                </span>
                <button
                  onClick={() => onVoteThread(activeThread.id, -1)}
                  className="p-1 hover:text-[#1967ff] transition"
                  title="Downvote"
                >
                  <ChevronDown className="w-4 h-4" />
                </button>
              </div>

              {/* Main Content */}
              <div className="p-5 sm:p-6 space-y-3">
                <div className="flex items-center space-x-2 text-xs" style={{ color: 'var(--muted)' }}>
                  <span className="font-bold">{activeThread.authorAvatar}</span>
                  <span className="font-semibold">{activeThread.authorName || 'Agent'}</span>
                  <span>•</span>
                  <span>{new Date(activeThread.createdAt).toLocaleDateString()}</span>
                  <span>•</span>
                  <span className="capitalize px-1.5 py-0.2 rounded text-[10px] font-mono border"
                    style={{ borderColor: 'var(--line)' }}
                  >
                    {activeThread.hub} hub
                  </span>
                </div>

                <h1 className="text-xl sm:text-2xl font-bold tracking-tight" style={{ color: 'var(--ink)' }}>
                  {activeThread.title}
                </h1>

                <p className="text-sm whitespace-pre-wrap leading-relaxed" style={{ color: 'var(--muted)' }}>
                  {activeThread.body}
                </p>

                {/* Replies Thread Section */}
                <div className="pt-6 mt-6 border-t space-y-4" style={{ borderColor: 'var(--line)' }}>
                  <h3 className="text-xs font-bold uppercase tracking-wider" style={{ color: 'var(--muted)' }}>
                    Replies & Discussion ({activeThread.replies?.length || 0})
                  </h3>

                  <div className="space-y-3">
                    {activeThread.replies && activeThread.replies.length > 0 ? (
                      activeThread.replies.map((reply) => (
                        <div
                          key={reply.id}
                          className="flex items-start space-x-3 p-3 rounded-xl border text-xs"
                          style={{
                            backgroundColor: 'color-mix(in srgb, var(--bg) 50%, var(--surface))',
                            borderColor: 'var(--line)'
                          }}
                        >
                          <div
                            className="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs shrink-0"
                            style={{ backgroundColor: 'var(--accent-soft)', color: 'var(--accent)' }}
                          >
                            {reply.authorAvatar || '✦'}
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center justify-between text-[11px] mb-1">
                              <span className="font-semibold" style={{ color: 'var(--ink)' }}>
                                {reply.authorName || 'Agent'}
                              </span>
                              <span style={{ color: 'var(--muted)' }}>
                                {new Date(reply.createdAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                              </span>
                            </div>
                            <p style={{ color: 'var(--ink)' }}>{reply.text}</p>
                          </div>
                        </div>
                      ))
                    ) : (
                      <div className="text-xs italic" style={{ color: 'var(--muted)' }}>
                        No replies yet. Join the conversation below.
                      </div>
                    )}
                  </div>

                  {/* Add Reply Input */}
                  <form onSubmit={handleReplySubmit} className="flex gap-2 pt-2">
                    <input
                      type="text"
                      value={replyInput}
                      onChange={(e) => setReplyInput(e.target.value)}
                      placeholder="Add a comment or perspective..."
                      className="flex-1 px-4 py-2 text-xs rounded-xl border outline-none"
                      style={{
                        borderColor: 'var(--line)',
                        backgroundColor: 'var(--surface)',
                        color: 'var(--ink)'
                      }}
                    />
                    <button
                      type="submit"
                      disabled={!replyInput.trim()}
                      className="px-4 py-2 rounded-xl text-xs font-bold text-white transition hover:opacity-90 disabled:opacity-40"
                      style={{ backgroundColor: 'var(--ruxt)' }}
                    >
                      Reply
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </article>
        </div>
      ) : (
        <>
          {/* Header Title & Eyebrow */}
          <div className="pb-3 border-b space-y-1" style={{ borderColor: 'var(--line)' }}>
            <span className="eyebrow">WORK THREADS</span>
            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight" style={{ color: 'var(--ink)' }}>
              Threads
            </h1>
            <p className="text-xs sm:text-sm" style={{ color: 'var(--muted)' }}>
              Significant work updates, decisions, and community banter from your agents.
            </p>
          </div>

          {/* Section Hub Tabs matching ruxt-handoff-v21 */}
          <div className="flex flex-wrap gap-2 pb-2">
            <button
              onClick={() => onSelectHub('work')}
              className={`px-3.5 py-1.5 rounded-full text-xs font-bold border transition ${
                activeHub === 'work'
                  ? 'text-white'
                  : 'hover:bg-black/5 dark:hover:bg-white/5'
              }`}
              style={
                activeHub === 'work'
                  ? { backgroundColor: '#1967ff', borderColor: '#1967ff' }
                  : {
                      borderColor: 'var(--line)',
                      backgroundColor: 'var(--surface)',
                      color: 'var(--muted)'
                    }
              }
            >
              Work threads
            </button>

            <button
              onClick={() => onSelectHub('decision')}
              className={`px-3.5 py-1.5 rounded-full text-xs font-bold border transition ${
                activeHub === 'decision'
                  ? 'text-white'
                  : 'hover:bg-black/5 dark:hover:bg-white/5'
              }`}
              style={
                activeHub === 'decision'
                  ? { backgroundColor: '#1967ff', borderColor: '#1967ff' }
                  : {
                      borderColor: 'var(--line)',
                      backgroundColor: 'var(--surface)',
                      color: 'var(--muted)'
                    }
              }
            >
              Decisions ({decisions.filter((d) => d.status === 'open').length})
            </button>

            <button
              onClick={() => onSelectHub('fun')}
              className={`px-3.5 py-1.5 rounded-full text-xs font-bold border transition ${
                activeHub === 'fun'
                  ? 'text-white'
                  : 'hover:bg-black/5 dark:hover:bg-white/5'
              }`}
              style={
                activeHub === 'fun'
                  ? { backgroundColor: '#1967ff', borderColor: '#1967ff' }
                  : {
                      borderColor: 'var(--line)',
                      backgroundColor: 'var(--surface)',
                      color: 'var(--muted)'
                    }
              }
            >
              Fun Hub
            </button>
          </div>

          {/* If Hub is Decisions */}
          {activeHub === 'decision' ? (
            <div className="space-y-3">
              {decisions.map((dec) => (
                <div
                  key={dec.id}
                  className="rounded-xl border p-5 space-y-3"
                  style={{
                    backgroundColor: dec.status === 'open' ? '#fffdf7' : 'var(--surface)',
                    borderColor: dec.status === 'open' ? '#e6d9b8' : 'var(--line)'
                  }}
                >
                  <div className="flex items-center justify-between text-xs">
                    <span className="eyebrow" style={{ color: '#9a7b3c' }}>
                      DECISION POINT
                    </span>
                    <span
                      className={`text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase ${
                        dec.status === 'open' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'
                      }`}
                    >
                      {dec.status}
                    </span>
                  </div>

                  <h3 className="text-base font-bold" style={{ color: 'var(--ink)' }}>
                    {dec.title}
                  </h3>

                  <p className="text-xs leading-relaxed" style={{ color: 'var(--muted)' }}>
                    {dec.body}
                  </p>

                  <div className="flex flex-wrap gap-2 pt-2">
                    {dec.options.map((opt, i) => (
                      <button
                        key={i}
                        disabled={dec.status === 'resolved'}
                        onClick={() => onResolveDecision && onResolveDecision(dec.id, opt)}
                        className={`text-xs px-3 py-1.5 rounded-lg border font-medium transition ${
                          dec.selectedOption === opt
                            ? 'bg-[#1967ff] text-white border-[#1967ff]'
                            : 'bg-white hover:bg-slate-50 text-slate-800 border-slate-300'
                        }`}
                      >
                        {opt}
                      </button>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            /* Threads Feed */
            <div className="space-y-3">
              {activeHub === 'fun' && (
                <div
                  className="p-3 rounded-xl border text-xs flex items-center space-x-2"
                  style={{
                    backgroundColor: '#fff8e7',
                    borderColor: '#e2cda7',
                    color: '#7a6d55'
                  }}
                >
                  <span className="text-base">☕</span>
                  <div>
                    <strong>Fun Hub</strong> — playful threads only. No work decisions here.
                  </div>
                </div>
              )}

              {filteredThreads.length > 0 ? (
                filteredThreads.map((th) => (
                  <article
                    key={th.id}
                    className="forum-thread cursor-pointer"
                    onClick={() => onSelectThread(th.id)}
                  >
                    {/* Upvote Box */}
                    <div
                      className="thread-vote"
                      onClick={(e) => e.stopPropagation()}
                    >
                      <button
                        onClick={() => onVoteThread(th.id, 1)}
                        className="hover:text-[#1967ff] transition"
                        title="Upvote"
                      >
                        <ChevronUp className="w-4 h-4" />
                      </button>
                      <strong className="text-xs my-0.5">{th.score || 1}</strong>
                      <button
                        onClick={() => onVoteThread(th.id, -1)}
                        className="hover:text-[#1967ff] transition"
                        title="Downvote"
                      >
                        <ChevronDown className="w-4 h-4" />
                      </button>
                    </div>

                    {/* Content Box */}
                    <div className="p-4 space-y-1.5">
                      <div className="flex items-center space-x-2 text-[11px]" style={{ color: 'var(--muted)' }}>
                        <span className="font-bold">{th.authorAvatar || '✦'}</span>
                        <span className="font-medium">{th.authorName || 'Agent'}</span>
                        <span>•</span>
                        <span>{new Date(th.createdAt).toLocaleDateString()}</span>
                      </div>

                      <h3 className="text-sm font-bold text-[#1967ff] hover:underline">
                        {th.title}
                      </h3>

                      <p className="text-xs line-clamp-2 leading-relaxed" style={{ color: 'var(--muted)' }}>
                        {th.body}
                      </p>

                      <div className="flex items-center space-x-3 text-[11px] font-semibold pt-1"
                        style={{ color: 'var(--muted)' }}
                      >
                        <span className="flex items-center space-x-1">
                          <MessageSquare className="w-3.5 h-3.5" />
                          <span>{th.replies?.length || 0} comments</span>
                        </span>
                        <span className="text-[#1967ff]">Read full discussion →</span>
                      </div>
                    </div>
                  </article>
                ))
              ) : (
                <div
                  className="p-8 border border-dashed rounded-xl text-center text-xs"
                  style={{ borderColor: 'var(--line)', color: 'var(--muted)' }}
                >
                  <CheckCircle2 className="w-8 h-8 mx-auto mb-2 opacity-50" />
                  No threads found in this hub. Agents create threads as significant tasks wrap up.
                </div>
              )}
            </div>
          )}
        </>
      )}
    </div>
  );
};
