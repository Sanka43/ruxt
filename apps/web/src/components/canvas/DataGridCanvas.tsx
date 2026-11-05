import React, { useState } from 'react';
import { Download, Plus, FileSpreadsheet, Calculator } from 'lucide-react';

interface RowData {
  id: number;
  category: string;
  jan: number;
  feb: number;
  mar: number;
}

export const DataGridCanvas: React.FC = () => {
  const [rows, setRows] = useState<RowData[]>([
    { id: 1, category: 'Engineering & AI Compute', jan: 4500, feb: 5200, mar: 5800 },
    { id: 2, category: 'Cloud Infrastructure & DB', jan: 1200, feb: 1250, mar: 1300 },
    { id: 3, category: 'Marketing & Operations', jan: 2100, feb: 2400, mar: 2700 },
    { id: 4, category: 'Legal & Compliance Audit', jan: 800, feb: 800, mar: 850 },
  ]);

  const addRow = () => {
    const newId = rows.length + 1;
    setRows([...rows, { id: newId, category: `New Service Item ${newId}`, jan: 1000, feb: 1000, mar: 1000 }]);
  };

  const updateCell = (id: number, field: keyof RowData, value: string) => {
    setRows(
      rows.map((row) => {
        if (row.id === id) {
          if (field === 'category') return { ...row, category: value };
          return { ...row, [field]: Number(value) || 0 };
        }
        return row;
      })
    );
  };

  const totalJan = rows.reduce((acc, r) => acc + r.jan, 0);
  const totalFeb = rows.reduce((acc, r) => acc + r.feb, 0);
  const totalMar = rows.reduce((acc, r) => acc + r.mar, 0);

  const exportCSV = () => {
    let csv = 'Category,January,February,March,Total\n';
    rows.forEach((r) => {
      const rowTot = r.jan + r.feb + r.mar;
      csv += `"${r.category}",${r.jan},${r.feb},${r.mar},${rowTot}\n`;
    });
    csv += `"Total",${totalJan},${totalFeb},${totalMar},${totalJan + totalFeb + totalMar}\n`;

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'budget_q1_forecast.csv';
    a.click();
    URL.revokeObjectURL(url);
  };

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
            <FileSpreadsheet className="w-4 h-4" />
          </div>
          <div>
            <h3 className="text-sm font-bold tracking-tight" style={{ color: 'var(--ink)' }}>
              Numbers Agent — Live Financial Matrix
            </h3>
            <p className="text-xs" style={{ color: 'var(--muted)' }}>
              Dynamic calculations, recurring margin trees & multi-tab models
            </p>
          </div>
        </div>

        <div className="flex items-center space-x-2">
          <button
            onClick={addRow}
            className="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition hover:opacity-85"
            style={{
              borderColor: 'var(--line)',
              backgroundColor: 'color-mix(in srgb, var(--bg) 60%, var(--surface))',
              color: 'var(--ink)'
            }}
          >
            <Plus className="w-3.5 h-3.5" />
            <span>Add Row</span>
          </button>
          <button
            onClick={exportCSV}
            className="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white shadow-xs transition hover:opacity-90"
            style={{ backgroundColor: 'var(--ruxt)' }}
          >
            <Download className="w-3.5 h-3.5" />
            <span>Export CSV</span>
          </button>
        </div>
      </div>

      {/* Grid Sheet Container */}
      <div className="flex-1 overflow-x-auto rounded-xl border" style={{ borderColor: 'var(--line)' }}>
        <table className="w-full text-left border-collapse text-xs">
          <thead>
            <tr
              className="border-b font-mono text-[11px] font-semibold uppercase tracking-wider"
              style={{
                backgroundColor: 'color-mix(in srgb, var(--bg) 75%, var(--surface))',
                borderColor: 'var(--line)',
                color: 'var(--muted)'
              }}
            >
              <th className="p-3 w-12 text-center border-r" style={{ borderColor: 'var(--line)' }}>#</th>
              <th className="p-3 border-r" style={{ borderColor: 'var(--line)' }}>Expense & Compute Line Item</th>
              <th className="p-3 w-28 text-right border-r" style={{ borderColor: 'var(--line)' }}>Jan ($)</th>
              <th className="p-3 w-28 text-right border-r" style={{ borderColor: 'var(--line)' }}>Feb ($)</th>
              <th className="p-3 w-28 text-right border-r" style={{ borderColor: 'var(--line)' }}>Mar ($)</th>
              <th className="p-3 w-32 text-right">Row Total</th>
            </tr>
          </thead>
          <tbody className="divide-y" style={{ borderColor: 'var(--line)' }}>
            {rows.map((row, idx) => {
              const rowTotal = row.jan + row.feb + row.mar;
              return (
                <tr
                  key={row.id}
                  className="transition hover:bg-black/2 dark:hover:bg-white/2"
                  style={{ borderColor: 'var(--line)' }}
                >
                  <td
                    className="p-3 text-center font-mono font-bold border-r"
                    style={{
                      backgroundColor: 'color-mix(in srgb, var(--bg) 40%, var(--surface))',
                      borderColor: 'var(--line)',
                      color: 'var(--muted)'
                    }}
                  >
                    {idx + 1}
                  </td>
                  <td className="p-2 border-r" style={{ borderColor: 'var(--line)' }}>
                    <input
                      type="text"
                      value={row.category}
                      onChange={(e) => updateCell(row.id, 'category', e.target.value)}
                      className="w-full bg-transparent p-1 font-medium outline-none focus:ring-1 focus:ring-indigo-400 rounded transition"
                      style={{ color: 'var(--ink)' }}
                    />
                  </td>
                  <td className="p-2 border-r" style={{ borderColor: 'var(--line)' }}>
                    <input
                      type="number"
                      value={row.jan}
                      onChange={(e) => updateCell(row.id, 'jan', e.target.value)}
                      className="w-full bg-transparent p-1 text-right font-mono outline-none focus:ring-1 focus:ring-indigo-400 rounded transition"
                      style={{ color: 'var(--ink)' }}
                    />
                  </td>
                  <td className="p-2 border-r" style={{ borderColor: 'var(--line)' }}>
                    <input
                      type="number"
                      value={row.feb}
                      onChange={(e) => updateCell(row.id, 'feb', e.target.value)}
                      className="w-full bg-transparent p-1 text-right font-mono outline-none focus:ring-1 focus:ring-indigo-400 rounded transition"
                      style={{ color: 'var(--ink)' }}
                    />
                  </td>
                  <td className="p-2 border-r" style={{ borderColor: 'var(--line)' }}>
                    <input
                      type="number"
                      value={row.mar}
                      onChange={(e) => updateCell(row.id, 'mar', e.target.value)}
                      className="w-full bg-transparent p-1 text-right font-mono outline-none focus:ring-1 focus:ring-indigo-400 rounded transition"
                      style={{ color: 'var(--ink)' }}
                    />
                  </td>
                  <td className="p-3 text-right font-mono font-bold" style={{ color: 'var(--ruxt)' }}>
                    ${rowTotal.toLocaleString()}
                  </td>
                </tr>
              );
            })}
          </tbody>
          <tfoot>
            <tr
              className="border-t font-semibold"
              style={{
                backgroundColor: 'color-mix(in srgb, var(--bg) 60%, var(--surface))',
                borderColor: 'var(--line)'
              }}
            >
              <td className="p-3 text-center border-r font-mono text-xs" style={{ borderColor: 'var(--line)', color: 'var(--muted)' }}>
                ∑
              </td>
              <td className="p-3 border-r font-bold text-xs" style={{ borderColor: 'var(--line)', color: 'var(--ink)' }}>
                Consolidated Operational Total
              </td>
              <td className="p-3 text-right font-mono font-bold text-xs border-r" style={{ borderColor: 'var(--line)', color: 'var(--ink)' }}>
                ${totalJan.toLocaleString()}
              </td>
              <td className="p-3 text-right font-mono font-bold text-xs border-r" style={{ borderColor: 'var(--line)', color: 'var(--ink)' }}>
                ${totalFeb.toLocaleString()}
              </td>
              <td className="p-3 text-right font-mono font-bold text-xs border-r" style={{ borderColor: 'var(--line)', color: 'var(--ink)' }}>
                ${totalMar.toLocaleString()}
              </td>
              <td className="p-3 text-right font-mono font-bold text-xs" style={{ color: 'var(--ruxt)' }}>
                ${(totalJan + totalFeb + totalMar).toLocaleString()}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      {/* Summary Footer */}
      <div
        className="p-3.5 rounded-xl border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs"
        style={{
          backgroundColor: 'color-mix(in srgb, var(--bg) 50%, var(--surface))',
          borderColor: 'var(--line)'
        }}
      >
        <div className="flex items-center space-x-2">
          <Calculator className="w-4 h-4 text-emerald-600" />
          <span style={{ color: 'var(--muted)' }}>
            Formulas automatically synchronized via SQLite WAL engine.
          </span>
        </div>
        <div className="text-[11px] font-mono font-semibold" style={{ color: 'var(--ruxt)' }}>
          Quarterly Burn Rate: ${Math.round((totalJan + totalFeb + totalMar) / 3).toLocaleString()}/mo
        </div>
      </div>
    </div>
  );
};
