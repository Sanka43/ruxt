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
  image?: string;
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

export interface TaskStep {
  id: string;
  label: string;
  status: 'pending' | 'running' | 'completed' | 'failed';
  detail?: string;
}

export interface Task {
  id: string;
  agentId: string;
  prompt: string;
  status: 'queued' | 'running' | 'completed' | 'failed';
  currentStep: string;
  steps: TaskStep[];
  result?: string;
  outputFiles?: string[];
  createdAt: number;
  finishedAt?: number;
}

export interface ChatMessage {
  id: string;
  userId?: string;
  agentId: string;
  role: 'user' | 'assistant' | 'system';
  content: string;
  taskId?: string;
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
  type: string;
  connected: boolean;
  label: string;
  detail: string;
}

export interface ThreadReply {
  id: string;
  authorId: string;
  authorName?: string;
  authorAvatar?: string;
  text: string;
  createdAt: number;
}

export interface Thread {
  id: string;
  hub: 'work' | 'decision' | 'fun';
  authorId: string;
  authorName?: string;
  authorAvatar?: string;
  authorRole?: string;
  title: string;
  body: string;
  score?: number;
  upvoted?: boolean;
  replies?: ThreadReply[];
  createdAt: number;
  updatedAt?: number;
}

export interface Decision {
  id: string;
  title: string;
  body: string;
  options: string[];
  selectedOption?: string;
  status: 'open' | 'resolved';
  authorId: string;
  authorName?: string;
  createdAt: number;
}

export interface ActivityItem {
  id: string;
  type: 'task' | 'message' | 'thread' | 'decision' | 'system';
  agentId?: string;
  agentName?: string;
  agentAvatar?: string;
  title: string;
  detail?: string;
  timestamp: number;
}

export interface UserProfile {
  fullName: string;
  preferredName: string;
  timezone: string;
  language: string;
  occupation: string;
  tone: string;
  goals: string;
}

export interface WorkspaceSettings {
  defaultPage: 'home' | 'agents' | 'messages' | 'forum' | 'workspace';
  density: 'comfortable' | 'compact';
  dateFormat: 'friendly' | 'local' | 'iso';
  reduceMotion: boolean;
}

export interface ToolsSettings {
  preferredTool: string;
  missingBehavior: string;
  autoRefresh: boolean;
  allowLaunch: boolean;
  notes: string;
}

export interface NotificationSettings {
  messages: boolean;
  threads: boolean;
  decisions: boolean;
  tools: boolean;
  sound: boolean;
}

export interface ThemePresetVars {
  '--bg': string;
  '--surface': string;
  '--ink': string;
  '--muted': string;
  '--line': string;
  '--accent': string;
  '--accent-soft': string;
  '--ruxt': string;
  '--ruxt-soft': string;
  '--sidebar': string;
  '--sidebar-line': string;
  '--sidebar-text': string;
  '--sidebar-muted': string;
  '--sidebar-panel'?: string;
  '--sidebar-hover'?: string;
  '--sidebar-active'?: string;
}

export interface ThemeSettings {
  activeId: string;
  preset: string;
  idea: string;
  logo: string;
  motion: string;
}
