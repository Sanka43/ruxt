import React from 'react';
import { Task } from '../../types';
import { CheckCircle2, Loader2, Clock, Activity, Zap } from 'lucide-react';

interface TaskPipelineProps {
  task: Task | null;
}

export const TaskPipeline: React.FC<TaskPipelineProps> = ({ task }) => {
  if (!task) return null;

  return (
    <div
      className="border-t px-4 py-2.5 flex flex-col md:flex-row items-start md:items-center justify-between gap-3 text-xs select-none transition-colors shrink-0"
      style={{
        backgroundColor: 'var(--surface)',
        borderColor: 'var(--line)',
        color: 'var(--ink)'
      }}
    >
      <div className="flex items-center space-x-2.5 min-w-0">
        <div
          className="w-5 h-5 rounded-full flex items-center justify-center shrink-0"
          style={{ backgroundColor: 'var(--accent-soft)', color: 'var(--accent)' }}
        >
          <Activity className="w-3 h-3 animate-pulse" />
        </div>
        <span className="font-bold text-xs" style={{ color: 'var(--ink)' }}>Pipeline:</span>
        <span className="truncate max-w-xs sm:max-w-md font-mono text-[11px]" style={{ color: 'var(--muted)' }}>
          {task.prompt}
        </span>
      </div>

      <div className="flex items-center space-x-1.5 overflow-x-auto w-full md:w-auto pb-0.5 md:pb-0">
        {task.steps.map((step, idx) => {
          const isDone = step.status === 'completed';
          const isRunning = step.status === 'running';

          return (
            <div key={step.id} className="flex items-center space-x-1.5 shrink-0">
              <div
                className={`flex items-center space-x-1.5 px-2.5 py-1 rounded-full border text-[11px] font-medium transition ${
                  isDone
                    ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-700 dark:text-emerald-300'
                    : isRunning
                    ? 'border-indigo-500/40 text-[#1967ff] font-semibold ring-1 ring-indigo-500/20'
                    : 'opacity-60'
                }`}
                style={
                  isRunning
                    ? { backgroundColor: 'var(--accent-soft)', borderColor: 'var(--accent)' }
                    : !isDone
                    ? { borderColor: 'var(--line)', backgroundColor: 'var(--bg)', color: 'var(--muted)' }
                    : {}
                }
              >
                {isDone ? (
                  <CheckCircle2 className="w-3 h-3 text-emerald-600" />
                ) : isRunning ? (
                  <Loader2 className="w-3 h-3 animate-spin text-[#1967ff]" />
                ) : (
                  <Clock className="w-3 h-3 text-slate-400" />
                )}
                <span>{step.label}</span>
              </div>
              {idx < task.steps.length - 1 && (
                <span className="text-[10px] text-slate-400">→</span>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
};
