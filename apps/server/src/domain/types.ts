export type CanvasType = 'chess' | 'datagrid' | 'audit' | 'custom' | 'none';
export type AgentDomain = 'chess' | 'finance' | 'compliance' | 'code' | 'general';

export interface CurriculumLevel {
  id: string;
  level: string;
  eloRange: string;
  focus: string;
  milestones: string[];
}

export interface Agent {
  id: string;
  name: string;
  slug: string;
  role: string;
  description: string;
  avatar: string;
  systemPrompt: string;
  canvasType: CanvasType;
  skills: string[];
  domain?: AgentDomain;
  strictGuardrails?: boolean;
  targetLegendLevel?: string;
  curriculum?: CurriculumLevel[];
  specializedKnowledge?: string[];
  guardrailRedirectMessage?: string;
  isBuiltin: boolean;
  isInstalled: boolean;
  createdAt: number;
}

export type TaskStatus = 'queued' | 'running' | 'completed' | 'failed';
export type StepStatus = 'pending' | 'running' | 'completed' | 'failed';

export interface TaskStep {
  id: string;
  label: string;
  status: StepStatus;
  detail?: string;
}

export interface Task {
  id: string;
  agentId: string;
  prompt: string;
  status: TaskStatus;
  currentStep: string;
  steps: TaskStep[];
  result?: string | null;
  outputFiles?: string[];
  createdAt: number;
  finishedAt?: number | null;
}

export interface ChatMessage {
  id: string;
  userId?: string | null;
  agentId: string;
  role: 'user' | 'assistant' | 'system';
  content: string;
  taskId?: string | null;
  metadata?: Record<string, any>;
  createdAt: number;
}

export interface User {
  id: string;
  name: string;
  email: string;
  avatar: string;
  role: 'admin' | 'user';
  createdAt: number;
  updatedAt?: number;
}

export interface AuthResponse {
  ok: boolean;
  user?: User;
  token?: string;
  error?: string;
}

export interface ProviderStatus {
  id: string;
  name: string;
  type: 'simulator' | 'ollama' | 'gemini' | 'claude' | 'openai' | 'local_ide';
  connected: boolean;
  label: string;
  detail: string;
}
