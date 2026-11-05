import Database from 'better-sqlite3';
import { drizzle } from 'drizzle-orm/better-sqlite3';
import * as schema from './schema.js';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const dataDir = path.resolve(__dirname, '../../../data');
if (!fs.existsSync(dataDir)) {
  fs.mkdirSync(dataDir, { recursive: true });
}

const sqlitePath = path.join(dataDir, 'ruxt-next.sqlite');
const sqlite = new Database(sqlitePath);

// Enable Write-Ahead Logging (WAL) for maximum concurrent read/write performance
sqlite.pragma('journal_mode = WAL');

// Auto-create tables if they don't exist
sqlite.exec(`
  CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    avatar TEXT NOT NULL DEFAULT '👤',
    role TEXT NOT NULL DEFAULT 'user',
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL
  );

  CREATE TABLE IF NOT EXISTS sessions (
    token TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    expires_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
  );

  CREATE TABLE IF NOT EXISTS agents (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    role TEXT NOT NULL,
    description TEXT NOT NULL,
    avatar TEXT NOT NULL,
    system_prompt TEXT NOT NULL,
    canvas_type TEXT NOT NULL DEFAULT 'none',
    skills TEXT DEFAULT '[]',
    is_builtin INTEGER NOT NULL DEFAULT 0,
    is_installed INTEGER NOT NULL DEFAULT 1,
    created_at INTEGER NOT NULL
  );

  CREATE TABLE IF NOT EXISTS tasks (
    id TEXT PRIMARY KEY,
    user_id TEXT,
    agent_id TEXT NOT NULL,
    prompt TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'queued',
    current_step TEXT DEFAULT 'understand',
    steps TEXT DEFAULT '[]',
    result TEXT,
    output_files TEXT DEFAULT '[]',
    created_at INTEGER NOT NULL,
    finished_at INTEGER
  );

  CREATE TABLE IF NOT EXISTS messages (
    id TEXT PRIMARY KEY,
    user_id TEXT,
    agent_id TEXT NOT NULL,
    role TEXT NOT NULL,
    content TEXT NOT NULL,
    task_id TEXT,
    metadata TEXT,
    created_at INTEGER NOT NULL
  );
`);

// Safely migrate columns if table already exists
try {
  const agentsCols = (sqlite.pragma('table_info(agents)') as Array<{ name: string }>).map((c) => c.name);
  const tasksCols = (sqlite.pragma('table_info(tasks)') as Array<{ name: string }>).map((c) => c.name);
  const msgsCols = (sqlite.pragma('table_info(messages)') as Array<{ name: string }>).map((c) => c.name);

  if (!agentsCols.includes('domain')) {
    sqlite.exec(`ALTER TABLE agents ADD COLUMN domain TEXT DEFAULT 'general';`);
  }
  if (!agentsCols.includes('strict_guardrails')) {
    sqlite.exec(`ALTER TABLE agents ADD COLUMN strict_guardrails INTEGER DEFAULT 1;`);
  }
  if (!agentsCols.includes('target_legend_level')) {
    sqlite.exec(`ALTER TABLE agents ADD COLUMN target_legend_level TEXT DEFAULT 'Master / Legend';`);
  }
  if (!agentsCols.includes('curriculum')) {
    sqlite.exec(`ALTER TABLE agents ADD COLUMN curriculum TEXT DEFAULT '[]';`);
  }
  if (!agentsCols.includes('specialized_knowledge')) {
    sqlite.exec(`ALTER TABLE agents ADD COLUMN specialized_knowledge TEXT DEFAULT '[]';`);
  }
  if (!agentsCols.includes('guardrail_redirect_message')) {
    sqlite.exec(`ALTER TABLE agents ADD COLUMN guardrail_redirect_message TEXT;`);
  }

  if (!tasksCols.includes('user_id')) {
    sqlite.exec(`ALTER TABLE tasks ADD COLUMN user_id TEXT;`);
  }
  if (!msgsCols.includes('user_id')) {
    sqlite.exec(`ALTER TABLE messages ADD COLUMN user_id TEXT;`);
  }
} catch (e) {
  console.warn('DB Migration note:', e);
}

export const db = drizzle(sqlite, { schema });
export { sqlite };
