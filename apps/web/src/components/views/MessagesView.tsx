import React, { useState, useEffect, useRef } from 'react';
import { Agent, ChatMessage } from '../../types';
import { Send, CheckCircle2 } from 'lucide-react';

interface MessagesViewProps {
  agents: Agent[];
  activeAgentId: string;
  onSelectAgent: (id: string) => void;
  messages: ChatMessage[];
  onSendMessage: (prompt: string) => void;
  isLoading?: boolean;
  userName?: string;
}

export const MessagesView: React.FC<MessagesViewProps> = ({
  agents,
  activeAgentId,
  onSelectAgent,
  messages,
  onSendMessage,
  isLoading = false,
  userName = 'You'
}) => {
  const [inputText, setInputText] = useState('');
  const scrollRef = useRef<HTMLDivElement>(null);

  const selectedAgent = agents.find((a) => a.id === activeAgentId) || agents[0];

  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
    }
  }, [messages, activeAgentId]);

  const handleSend = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    if (!inputText.trim() || isLoading) return;
    const prompt = inputText.trim();
    setInputText('');
    onSendMessage(prompt);
  };

  const getAgentPhoto = (agent: Agent) => {
    if (agent.slug === 'numbers-agent' || agent.id.includes('numbers')) {
      return '/numbers-agent.png';
    }
    if (agent.slug === 'maya-brooks' || agent.id.includes('maya')) {
      return '/agent-avatars/maya-brooks.png';
    }
    if (agent.slug === 'noah-reed' || agent.id.includes('noah')) {
      return '/agent-avatars/noah-reed.png';
    }
    return null;
  };

  return (
    <div
      className="flex-1 flex overflow-hidden w-full h-[calc(100vh-56px)] border-t"
      style={{
        backgroundColor: 'var(--surface)',
        borderColor: 'var(--line)'
      }}
    >
      {/* Left Rail: Agent Selector */}
      <aside
        className="w-64 sm:w-72 border-r flex flex-col shrink-0 overflow-y-auto"
        style={{
          backgroundColor: 'color-mix(in srgb, var(--bg) 75%, var(--surface))',
          borderColor: 'var(--line)'
        }}
      >
        <div className="p-4 border-b" style={{ borderColor: 'var(--line)' }}>
          <span className="eyebrow">ALL AGENTS</span>
          <h2 className="text-sm font-bold tracking-tight mt-0.5" style={{ color: 'var(--ink)' }}>
            Messages
          </h2>
        </div>

        <div className="p-2 space-y-1">
          {agents.map((ag) => {
            const isSelected = selectedAgent?.id === ag.id;
            const photoUrl = getAgentPhoto(ag);

            return (
              <button
                key={ag.id}
                onClick={() => onSelectAgent(ag.id)}
                className={`w-full flex items-center space-x-3 p-2.5 rounded-xl text-left transition ${
                  isSelected
                    ? 'shadow-sm font-semibold'
                    : 'hover:bg-black/5 dark:hover:bg-white/5 opacity-80 hover:opacity-100'
                }`}
                style={
                  isSelected
                    ? {
                        backgroundColor: 'var(--accent-soft)',
                        color: 'var(--accent)',
                        borderColor: 'color-mix(in srgb, var(--accent) 30%, var(--line))'
                      }
                    : { color: 'var(--ink)' }
                }
              >
                <div
                  className="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg shrink-0 overflow-hidden shadow-sm"
                  style={{
                    backgroundColor: 'var(--accent-soft)',
                    color: 'var(--accent)'
                  }}
                >
                  {photoUrl ? (
                    <img src={photoUrl} alt={ag.name} className="w-full h-full object-cover" />
                  ) : (
                    <span>{ag.avatar}</span>
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="text-xs font-bold truncate">{ag.name}</div>
                  <div className="text-[10px] truncate" style={{ color: 'var(--muted)' }}>
                    {ag.role}
                  </div>
                </div>
              </button>
            );
          })}
        </div>
      </aside>

      {/* Right Panel: Conversation Thread */}
      <section className="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
        {/* Conversation Header */}
        {selectedAgent && (
          <header
            className="p-4 border-b flex items-center space-x-3.5 shrink-0"
            style={{
              backgroundColor: 'color-mix(in srgb, var(--bg) 50%, var(--surface))',
              borderColor: 'var(--line)'
            }}
          >
            <div
              className="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-xl overflow-hidden shadow-sm shrink-0"
              style={{
                backgroundColor: 'var(--accent-soft)',
                color: 'var(--accent)'
              }}
            >
              {getAgentPhoto(selectedAgent) ? (
                <img src={getAgentPhoto(selectedAgent)!} alt={selectedAgent.name} className="w-full h-full object-cover" />
              ) : (
                <span>{selectedAgent.avatar}</span>
              )}
            </div>
            <div>
              <div className="flex items-center space-x-2">
                <strong className="text-sm font-bold" style={{ color: 'var(--ink)' }}>
                  {selectedAgent.name}
                </strong>
                <span
                  className="text-[10px] px-2 py-0.5 rounded-full font-mono font-semibold"
                  style={{ backgroundColor: 'var(--accent-soft)', color: 'var(--accent)' }}
                >
                  ● READY
                </span>
              </div>
              <small className="text-xs" style={{ color: 'var(--muted)' }}>
                {selectedAgent.role} — {selectedAgent.description}
              </small>
            </div>
          </header>
        )}

        {/* Message Log */}
        <div
          ref={scrollRef}
          className="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4"
          style={{ backgroundColor: 'color-mix(in srgb, var(--bg) 85%, var(--surface))' }}
        >
          {messages.length > 0 ? (
            messages.map((msg) => {
              const isUser = msg.role === 'user';
              return (
                <div
                  key={msg.id}
                  className={`flex items-end gap-2.5 ${isUser ? 'justify-end' : 'justify-start'}`}
                >
                  {!isUser && selectedAgent && (
                    <div
                      className="w-7 h-7 rounded-lg flex items-center justify-center text-xs shrink-0 font-bold"
                      style={{ backgroundColor: 'var(--accent-soft)', color: 'var(--accent)' }}
                    >
                      {selectedAgent.avatar}
                    </div>
                  )}

                  <div
                    className="max-w-[78%] rounded-2xl p-3.5 text-xs shadow-sm"
                    style={
                      isUser
                        ? {
                            backgroundColor: 'var(--ruxt)',
                            color: '#fff',
                            borderBottomRightRadius: '4px'
                          }
                        : {
                            backgroundColor: 'var(--surface)',
                            color: 'var(--ink)',
                            border: '1px solid var(--line)',
                            borderBottomLeftRadius: '4px'
                          }
                    }
                  >
                    <div className="flex items-center justify-between text-[10px] mb-1 opacity-75">
                      <span className="font-semibold">{isUser ? userName : selectedAgent?.name}</span>
                      <span>
                        {new Date(msg.createdAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                      </span>
                    </div>
                    <p className="whitespace-pre-wrap leading-relaxed">{msg.content}</p>
                  </div>
                </div>
              );
            })
          ) : (
            <div
              className="p-8 border border-dashed rounded-xl text-center text-xs max-w-md mx-auto my-12"
              style={{ borderColor: 'var(--line)', color: 'var(--muted)' }}
            >
              <CheckCircle2 className="w-8 h-8 mx-auto mb-2 opacity-50" />
              Pick an agent on the left to read and send messages.
            </div>
          )}
        </div>

        {/* Message Composer */}
        <form
          onSubmit={handleSend}
          className="p-3 border-t flex items-center space-x-2 shrink-0"
          style={{
            backgroundColor: 'var(--surface)',
            borderColor: 'var(--line)'
          }}
        >
          <input
            type="text"
            value={inputText}
            onChange={(e) => setInputText(e.target.value)}
            placeholder={`Message ${selectedAgent?.name || 'copilot'}...`}
            className="flex-1 px-4 py-2 text-xs rounded-xl border outline-none transition"
            style={{
              borderColor: 'var(--line)',
              backgroundColor: 'color-mix(in srgb, var(--bg) 50%, var(--surface))',
              color: 'var(--ink)'
            }}
          />
          <button
            type="submit"
            disabled={!inputText.trim() || isLoading}
            className="p-2.5 rounded-xl text-white font-bold transition hover:opacity-90 disabled:opacity-40"
            style={{ backgroundColor: 'var(--ruxt)' }}
            title="Send Message"
          >
            <Send className="w-4 h-4" />
          </button>
        </form>
      </section>
    </div>
  );
};
