import React, { useState } from 'react';
import { ShieldCheck, AlertTriangle, CheckCircle2, FileText, Check } from 'lucide-react';

interface AuditItem {
  id: string;
  category: string;
  title: string;
  checked: boolean;
  severity: 'high' | 'medium' | 'low';
}

export const AuditCanvas: React.FC = () => {
  const [items, setItems] = useState<AuditItem[]>([
    { id: '1', category: 'Statutory Filing', title: 'Q1 Corporate Tax Return lodgement completed', checked: true, severity: 'high' },
    { id: '2', category: 'Record Keeping', title: 'Invoices > $500 backed by verifiable digital receipts', checked: true, severity: 'high' },
    { id: '3', category: 'Expense Review', title: 'R&D tax credits verified under national guidelines', checked: false, severity: 'medium' },
    { id: '4', category: 'Contractor Compliance', title: 'W-8 / W-9 equivalent tax forms collected for contractors', checked: true, severity: 'medium' },
    { id: '5', category: 'Cross-Border', title: 'VAT/GST threshold monitoring for foreign digital services', checked: false, severity: 'low' },
  ]);

  const toggleCheck = (id: string) => {
    setItems(items.map((it) => (it.id === id ? { ...it, checked: !it.checked } : it)));
  };

  const completedCount = items.filter((it) => it.checked).length;
  const score = Math.round((completedCount / items.length) * 100);

  return (
    <div
      className="flex flex-col h-full rounded-2xl border overflow-hidden p-4 sm:p-5 space-y-4 shadow-xs transition-colors"
      style={{
        backgroundColor: 'var(--surface)',
        borderColor: 'var(--line)',
        color: 'var(--ink)'
      }}
    >
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b"
        style={{ borderColor: 'var(--line)' }}
      >
        <div className="flex items-center space-x-2.5">
          <div
            className="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-sm shrink-0"
            style={{ backgroundColor: 'var(--accent-soft)', color: 'var(--accent)' }}
          >
            <ShieldCheck className="w-4 h-4" />
          </div>
          <div>
            <h3 className="text-sm font-bold tracking-tight" style={{ color: 'var(--ink)' }}>
              Noah Reed — Tax & Compliance Audit
            </h3>
            <p className="text-xs" style={{ color: 'var(--muted)' }}>
              Statutory risk score & regulatory obligations tracker
            </p>
          </div>
        </div>

        <div className="flex items-center space-x-2">
          <span className="text-xs font-medium" style={{ color: 'var(--muted)' }}>Health Score:</span>
          <span
            className={`text-xs px-2.5 py-1 rounded-full font-bold border ${
              score >= 80
                ? 'bg-emerald-500/10 text-emerald-700 border-emerald-500/30'
                : 'bg-amber-500/10 text-amber-700 border-amber-500/30'
            }`}
          >
            {score}% Compliant
          </span>
        </div>
      </div>

      {/* Risk Meter Card */}
      <div
        className="rounded-xl p-4 border flex flex-col sm:flex-row sm:items-center justify-between gap-4"
        style={{
          backgroundColor: 'color-mix(in srgb, var(--bg) 60%, var(--surface))',
          borderColor: 'var(--line)'
        }}
      >
        <div className="space-y-1">
          <span className="text-xs font-bold" style={{ color: 'var(--ink)' }}>
            Regulatory Review Summary
          </span>
          <p className="text-xs" style={{ color: 'var(--muted)' }}>
            {completedCount} of {items.length} compliance checkpoints cleared. No critical filing penalties detected.
          </p>
        </div>
        <div className="w-full sm:w-36 bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden shrink-0">
          <div
            className="h-2 rounded-full transition-all duration-500"
            style={{
              width: `${score}%`,
              backgroundColor: score >= 80 ? 'var(--ruxt)' : '#d97706'
            }}
          />
        </div>
      </div>

      {/* Checklist Table */}
      <div className="flex-1 overflow-y-auto space-y-2">
        {items.map((item) => (
          <div
            key={item.id}
            onClick={() => toggleCheck(item.id)}
            className="flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition hover:shadow-xs"
            style={{
              backgroundColor: item.checked
                ? 'color-mix(in srgb, var(--accent-soft) 40%, var(--surface))'
                : 'var(--surface)',
              borderColor: item.checked
                ? 'color-mix(in srgb, var(--accent) 30%, var(--line))'
                : 'var(--line)'
            }}
          >
            <div className="flex items-center space-x-3 min-w-0">
              <div
                className={`w-5 h-5 rounded-md flex items-center justify-center border transition ${
                  item.checked
                    ? 'border-emerald-600 bg-emerald-600 text-white'
                    : 'border-slate-300 dark:border-slate-600 bg-transparent'
                }`}
              >
                {item.checked && <Check className="w-3.5 h-3.5 stroke-[3]" />}
              </div>
              <div className="min-w-0">
                <div className="flex items-center space-x-2">
                  <span className="eyebrow truncate">
                    {item.category}
                  </span>
                  <span
                    className={`text-[9px] px-1.5 py-0.2 rounded font-mono font-bold uppercase ${
                      item.severity === 'high'
                        ? 'bg-rose-100 text-rose-800'
                        : item.severity === 'medium'
                        ? 'bg-amber-100 text-amber-800'
                        : 'bg-slate-100 text-slate-800'
                    }`}
                  >
                    {item.severity}
                  </span>
                </div>
                <p className={`text-xs mt-0.5 ${item.checked ? 'line-through opacity-70' : ''}`} style={{ color: 'var(--ink)' }}>
                  {item.title}
                </p>
              </div>
            </div>

            <span className="text-[11px] font-semibold text-[#1967ff] shrink-0 pl-2">
              {item.checked ? 'Cleared' : 'Verify →'}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
};
