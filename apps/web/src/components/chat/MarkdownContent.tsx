import React, { useState } from 'react';
import { Copy, Check, Terminal, ShieldAlert } from 'lucide-react';

interface MarkdownContentProps {
  content: string;
  isStreaming?: boolean;
}

// Parses inline text: bold, italic, inline code, links
function renderInline(text: string): React.ReactNode[] {
  const tokenRegex = /(\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`|\[[^\]]+\]\([^)]+\))/g;
  const parts = text.split(tokenRegex);

  return parts.map((part, index) => {
    if (!part) return null;

    // Bold: **text**
    if (part.startsWith('**') && part.endsWith('**') && part.length >= 4) {
      const boldText = part.slice(2, -2);
      return (
        <strong key={index} className="font-semibold tracking-wide" style={{ color: 'var(--ink)' }}>
          {boldText}
        </strong>
      );
    }

    // Italic: *text*
    if (part.startsWith('*') && part.endsWith('*') && part.length >= 2) {
      const italicText = part.slice(1, -1);
      return (
        <em key={index} className="italic" style={{ color: 'var(--muted)' }}>
          {italicText}
        </em>
      );
    }

    // Inline code: `code`
    if (part.startsWith('`') && part.endsWith('`') && part.length >= 2) {
      const codeText = part.slice(1, -1);
      return (
        <code
          key={index}
          className="px-1.5 py-0.5 mx-0.5 rounded-md font-mono text-[11px] border select-all"
          style={{
            backgroundColor: 'color-mix(in srgb, var(--accent) 10%, var(--surface))',
            borderColor: 'var(--line)',
            color: 'var(--accent)'
          }}
        >
          {codeText}
        </code>
      );
    }

    // Link: [text](url)
    const linkMatch = part.match(/^\[([^\]]+)\]\(([^)]+)\)$/);
    if (linkMatch) {
      return (
        <a
          key={index}
          href={linkMatch[2]}
          target="_blank"
          rel="noopener noreferrer"
          className="text-[#1967ff] hover:underline underline-offset-2 transition font-medium"
        >
          {linkMatch[1]}
        </a>
      );
    }

    return <React.Fragment key={index}>{part}</React.Fragment>;
  });
}

function CodeBlock({ code, lang }: { code: string; lang: string }) {
  const [copied, setCopied] = useState(false);

  const handleCopy = () => {
    navigator.clipboard.writeText(code);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="my-3 rounded-xl overflow-hidden border border-slate-700/50 bg-[#0e131d] shadow-sm">
      <div className="flex items-center justify-between px-3 py-1.5 bg-[#141b27] border-b border-slate-700/40 text-[11px] text-slate-400 font-mono select-none">
        <div className="flex items-center space-x-1.5">
          <Terminal className="w-3.5 h-3.5 text-slate-400" />
          <span>{lang || 'code'}</span>
        </div>
        <button
          onClick={handleCopy}
          className="flex items-center space-x-1 hover:text-white transition px-1.5 py-0.5 rounded hover:bg-white/5"
          title="Copy code snippet"
        >
          {copied ? (
            <>
              <Check className="w-3 h-3 text-emerald-400" />
              <span className="text-[10px] text-emerald-400">Copied</span>
            </>
          ) : (
            <>
              <Copy className="w-3 h-3 text-slate-400" />
              <span className="text-[10px]">Copy</span>
            </>
          )}
        </button>
      </div>
      <pre className="p-3.5 overflow-x-auto font-mono text-[11px] text-slate-200 leading-relaxed selection:bg-indigo-500/30">
        <code>{code}</code>
      </pre>
    </div>
  );
}

export const MarkdownContent: React.FC<MarkdownContentProps> = ({ content, isStreaming = false }) => {
  const isGuardrail =
    content.includes('Strict Domain Guardrail Enforced') ||
    content.includes('Domain Boundary Notification');

  const lines = content.split('\n');
  const elements: React.ReactNode[] = [];
  let currentList: { type: 'ul' | 'ol'; items: string[] } | null = null;
  let inCodeBlock = false;
  let codeBlockLang = '';
  let codeBlockLines: string[] = [];

  const flushList = () => {
    if (!currentList) return;
    const { type, items } = currentList;
    const listKey = `list-${elements.length}`;

    if (type === 'ul') {
      elements.push(
        <ul key={listKey} className="my-2 space-y-1.5 pl-1">
          {items.map((item, idx) => (
            <li key={idx} className="flex items-start space-x-2 text-xs leading-relaxed" style={{ color: 'var(--ink)' }}>
              <span
                className="w-1.5 h-1.5 rounded-full mt-1.5 shrink-0"
                style={{ backgroundColor: 'var(--accent)' }}
              />
              <div className="flex-1 min-w-0">{renderInline(item)}</div>
            </li>
          ))}
        </ul>
      );
    } else {
      elements.push(
        <ol key={listKey} className="my-2 space-y-1.5 pl-1">
          {items.map((item, idx) => (
            <li key={idx} className="flex items-start space-x-2 text-xs leading-relaxed" style={{ color: 'var(--ink)' }}>
              <span
                className="flex items-center justify-center w-4 h-4 rounded-full text-[10px] font-mono font-bold mt-0.5 shrink-0"
                style={{ backgroundColor: 'var(--accent-soft)', color: 'var(--accent)' }}
              >
                {idx + 1}
              </span>
              <div className="flex-1 min-w-0">{renderInline(item)}</div>
            </li>
          ))}
        </ol>
      );
    }
    currentList = null;
  };

  const flushCodeBlock = () => {
    if (!inCodeBlock) return;
    const code = codeBlockLines.join('\n');
    const blockKey = `code-${elements.length}`;
    elements.push(<CodeBlock key={blockKey} code={code} lang={codeBlockLang} />);
    inCodeBlock = false;
    codeBlockLang = '';
    codeBlockLines = [];
  };

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];

    // Code block toggle (```)
    if (line.trim().startsWith('```')) {
      if (inCodeBlock) {
        flushCodeBlock();
      } else {
        flushList();
        inCodeBlock = true;
        codeBlockLang = line.trim().slice(3).trim();
        codeBlockLines = [];
      }
      continue;
    }

    if (inCodeBlock) {
      codeBlockLines.push(line);
      continue;
    }

    // Unordered list item: - item or * item
    const ulMatch = line.match(/^(\s*)[-*]\s+(.+)$/);
    if (ulMatch) {
      if (!currentList || currentList.type !== 'ul') {
        flushList();
        currentList = { type: 'ul', items: [] };
      }
      currentList.items.push(ulMatch[2]);
      continue;
    }

    // Ordered list item: 1. item
    const olMatch = line.match(/^(\s*)\d+\.\s+(.+)$/);
    if (olMatch) {
      if (!currentList || currentList.type !== 'ol') {
        flushList();
        currentList = { type: 'ol', items: [] };
      }
      currentList.items.push(olMatch[2]);
      continue;
    }

    // If not list item, flush pending list
    flushList();

    // Empty lines
    if (!line.trim()) {
      elements.push(<div key={`empty-${i}`} className="h-2" />);
      continue;
    }

    // Headings
    if (line.startsWith('# ')) {
      elements.push(
        <h1 key={`h1-${i}`} className="text-base font-bold my-2 pb-1 border-b" style={{ borderColor: 'var(--line)', color: 'var(--ink)' }}>
          {renderInline(line.slice(2))}
        </h1>
      );
      continue;
    }
    if (line.startsWith('## ')) {
      elements.push(
        <h2 key={`h2-${i}`} className="text-sm font-bold my-1.5" style={{ color: 'var(--ink)' }}>
          {renderInline(line.slice(3))}
        </h2>
      );
      continue;
    }
    if (line.startsWith('### ')) {
      elements.push(
        <h3 key={`h3-${i}`} className="text-xs font-bold my-1" style={{ color: 'var(--ink)' }}>
          {renderInline(line.slice(4))}
        </h3>
      );
      continue;
    }

    // Normal paragraph
    elements.push(
      <p key={`p-${i}`} className="text-xs leading-relaxed my-1" style={{ color: 'var(--ink)' }}>
        {renderInline(line)}
      </p>
    );
  }

  // Flush remaining blocks
  flushList();
  flushCodeBlock();

  return (
    <div className={`space-y-0.5 ${isGuardrail ? 'p-2 rounded-xl border border-amber-400/40 bg-amber-500/10' : ''}`}>
      {isGuardrail && (
        <div className="flex items-center space-x-1.5 mb-2 text-amber-600 font-bold text-xs">
          <ShieldAlert className="w-4 h-4 shrink-0" />
          <span>Domain Boundary Notification</span>
        </div>
      )}
      {elements}
    </div>
  );
};
