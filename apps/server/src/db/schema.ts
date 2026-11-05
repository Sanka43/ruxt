import { sqliteTable, text, integer } from 'drizzle-orm/sqlite-core';
import type { CurriculumLevel } from '../domain/types.js';

export const usersTable = sqliteTable('users', {
  id: text('id').primaryKey(),
  name: text('name').notNull(),
  email: text('email').notNull().unique(),
  passwordHash: text('password_hash').notNull(),
  avatar: text('avatar').notNull().default('👤'),
  role: text('role').notNull().default('user'),
  createdAt: integer('created_at').notNull(),
  updatedAt: integer('updated_at').notNull()
});

export const sessionsTable = sqliteTable('sessions', {
  token: text('token').primaryKey(),
  userId: text('user_id').notNull(),
  expiresAt: integer('expires_at').notNull(),
  createdAt: integer('created_at').notNull()
});

export const agentsTable = sqliteTable('agents', {
  id: text('id').primaryKey(),
  name: text('name').notNull(),
  slug: text('slug').notNull().unique(),
  role: text('role').notNull(),
  description: text('description').notNull(),
  avatar: text('avatar').notNull(),
  systemPrompt: text('system_prompt').notNull(),
  canvasType: text('canvas_type').notNull().default('none'),
  skills: text('skills', { mode: 'json' }).$type<string[]>().default([]),
  domain: text('domain').default('general'),
  strictGuardrails: integer('strict_guardrails', { mode: 'boolean' }).default(true),
  targetLegendLevel: text('target_legend_level').default('Master / Legend'),
  curriculum: text('curriculum', { mode: 'json' }).$type<CurriculumLevel[]>().default([]),
  specializedKnowledge: text('specialized_knowledge', { mode: 'json' }).$type<string[]>().default([]),
  guardrailRedirectMessage: text('guardrail_redirect_message'),
  isBuiltin: integer('is_builtin', { mode: 'boolean' }).notNull().default(false),
  isInstalled: integer('is_installed', { mode: 'boolean' }).notNull().default(true),
  createdAt: integer('created_at').notNull()
});

export const tasksTable = sqliteTable('tasks', {
  id: text('id').primaryKey(),
  userId: text('user_id'),
  agentId: text('agent_id').notNull(),
  prompt: text('prompt').notNull(),
  status: text('status').notNull().default('queued'),
  currentStep: text('current_step').default('understand'),
  steps: text('steps', { mode: 'json' }).$type<Array<{ id: string; label: string; status: string; detail?: string }>>().default([]),
  result: text('result'),
  outputFiles: text('output_files', { mode: 'json' }).$type<string[]>().default([]),
  createdAt: integer('created_at').notNull(),
  finishedAt: integer('finished_at')
});

export const messagesTable = sqliteTable('messages', {
  id: text('id').primaryKey(),
  userId: text('user_id'),
  agentId: text('agent_id').notNull(),
  role: text('role').notNull(),
  content: text('content').notNull(),
  taskId: text('task_id'),
  metadata: text('metadata', { mode: 'json' }).$type<Record<string, any>>(),
  createdAt: integer('created_at').notNull()
});
