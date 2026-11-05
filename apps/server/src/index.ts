import Fastify from 'fastify';
import cors from '@fastify/cors';
import websocket from '@fastify/websocket';
import fastifyStatic from '@fastify/static';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import { agentService } from './services/agent.service.js';
import { taskService } from './services/task.service.js';
import { authService } from './services/auth.service.js';
import { providerHub, ProviderId } from './providers/provider.hub.js';
import { geminiProvider } from './providers/gemini.provider.js';
import { ollamaProvider } from './providers/ollama.provider.js';
import { openAIProvider } from './providers/openai.provider.js';
import type { WebSocket } from 'ws';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const fastify = Fastify({
  logger: false
});

await fastify.register(cors, {
  origin: true
});

await fastify.register(websocket);

// Connected WebSocket clients
const clients = new Set<WebSocket>();
const tunnelClients = new Set<WebSocket>();
const pendingTunnelResolvers = new Map<string, (result: any) => void>();

function broadcast(event: string, payload: any) {
  const message = JSON.stringify({ event, payload });
  for (const client of clients) {
    if (client.readyState === 1) { // OPEN
      client.send(message);
    }
  }
}

// WebSocket handler
fastify.get('/ws', { websocket: true }, (connection: any) => {
  const ws: WebSocket = connection.socket || connection;
  clients.add(ws);

  ws.send(JSON.stringify({
    event: 'connected',
    payload: { status: 'connected', time: Date.now() }
  }));

  ws.on('message', async (raw: any) => {
    try {
      const data = JSON.parse(raw.toString());

      if (data.type === 'ping') {
        ws.send(JSON.stringify({ event: 'pong', payload: { time: Date.now() } }));
        return;
      }

      // Outbound Tunnel Companion Registration
      if (data.type === 'tunnel:register') {
        tunnelClients.add(ws);
        providerHub.setTunnelStatus(true, tunnelClients.size);
        broadcast('tunnel:status', {
          connected: true,
          clientCount: tunnelClients.size,
          platform: data.payload?.platform,
          tools: data.payload?.tools
        });
        console.log(`[RuXt Host] Tunnel client connected (Total: ${tunnelClients.size})`);
        return;
      }

      // Outbound Tunnel Command Execution Result
      if (data.type === 'tunnel:exec_result') {
        const { id } = data.payload || {};
        if (id && pendingTunnelResolvers.has(id)) {
          const resolver = pendingTunnelResolvers.get(id);
          pendingTunnelResolvers.delete(id);
          resolver?.(data.payload);
        }
        broadcast('tunnel:exec_result', data.payload);
        return;
      }
    } catch (_) {}
  });

  ws.on('close', () => {
    clients.delete(ws);
    if (tunnelClients.has(ws)) {
      tunnelClients.delete(ws);
      providerHub.setTunnelStatus(tunnelClients.size > 0, tunnelClients.size);
      broadcast('tunnel:status', {
        connected: tunnelClients.size > 0,
        clientCount: tunnelClients.size
      });
      console.log(`[RuXt Host] Tunnel client disconnected (Remaining: ${tunnelClients.size})`);
    }
  });
});

// Helper to parse Bearer token from request
function getBearerToken(request: any): string | null {
  const authHeader = request.headers.authorization;
  if (!authHeader || !authHeader.startsWith('Bearer ')) return null;
  return authHeader.substring(7).trim();
}

// Authentication Endpoints
fastify.post('/api/auth/register', async (request, reply) => {
  try {
    const { name, email, password } = request.body as any;
    const result = await authService.register(name, email, password);
    return { ok: true, ...result };
  } catch (err: any) {
    return reply.status(400).send({ ok: false, error: err.message || 'Registration failed' });
  }
});

fastify.post('/api/auth/login', async (request, reply) => {
  try {
    const { email, password } = request.body as any;
    const result = await authService.login(email, password);
    return { ok: true, ...result };
  } catch (err: any) {
    return reply.status(401).send({ ok: false, error: err.message || 'Invalid credentials' });
  }
});

fastify.get('/api/auth/me', async (request, reply) => {
  const token = getBearerToken(request);
  if (!token) {
    return reply.status(401).send({ ok: false, error: 'Unauthorized' });
  }
  const user = await authService.getUserByToken(token);
  if (!user) {
    return reply.status(401).send({ ok: false, error: 'Invalid or expired session' });
  }
  return { ok: true, user };
});

fastify.post('/api/auth/logout', async (request) => {
  const token = getBearerToken(request);
  if (token) {
    await authService.logout(token);
  }
  return { ok: true };
});

fastify.put('/api/auth/profile', async (request, reply) => {
  const token = getBearerToken(request);
  if (!token) return reply.status(401).send({ ok: false, error: 'Unauthorized' });
  const user = await authService.getUserByToken(token);
  if (!user) return reply.status(401).send({ ok: false, error: 'Unauthorized' });

  try {
    const body = request.body as any;
    const updated = await authService.updateProfile(user.id, body);
    return { ok: true, user: updated };
  } catch (err: any) {
    return reply.status(400).send({ ok: false, error: err.message });
  }
});

// Admin Authentication Guard Helper
async function requireAdmin(request: any, reply: any): Promise<any> {
  const token = getBearerToken(request);
  if (!token) {
    reply.status(401).send({ ok: false, error: 'Unauthorized: Authentication required.' });
    return null;
  }
  const user = await authService.getUserByToken(token);
  if (!user || user.role !== 'admin') {
    reply.status(403).send({ ok: false, error: 'Forbidden: Administrator privileges required.' });
    return null;
  }
  return user;
}

// --- Admin User Management Endpoints ---

fastify.get('/api/admin/users', async (request, reply) => {
  const admin = await requireAdmin(request, reply);
  if (!admin) return;
  const users = await authService.getAllUsers();
  return { ok: true, users };
});

fastify.post('/api/admin/users', async (request, reply) => {
  const admin = await requireAdmin(request, reply);
  if (!admin) return;
  try {
    const user = await authService.createUserByAdmin(request.body as any);
    return { ok: true, user };
  } catch (err: any) {
    return reply.status(400).send({ ok: false, error: err.message });
  }
});

fastify.put('/api/admin/users/:id', async (request, reply) => {
  const admin = await requireAdmin(request, reply);
  if (!admin) return;
  try {
    const { id } = request.params as { id: string };
    const user = await authService.updateUserByAdmin(id, request.body as any);
    return { ok: true, user };
  } catch (err: any) {
    return reply.status(400).send({ ok: false, error: err.message });
  }
});

fastify.put('/api/admin/users/:id/password', async (request, reply) => {
  const admin = await requireAdmin(request, reply);
  if (!admin) return;
  try {
    const { id } = request.params as { id: string };
    const { password } = request.body as any;
    await authService.resetUserPasswordByAdmin(id, password);
    return { ok: true, message: 'Password reset successfully.' };
  } catch (err: any) {
    return reply.status(400).send({ ok: false, error: err.message });
  }
});

fastify.delete('/api/admin/users/:id', async (request, reply) => {
  const admin = await requireAdmin(request, reply);
  if (!admin) return;
  try {
    const { id } = request.params as { id: string };
    await authService.deleteUserByAdmin(id);
    return { ok: true, message: 'User deleted successfully.' };
  } catch (err: any) {
    return reply.status(400).send({ ok: false, error: err.message });
  }
});

// REST API Endpoints
fastify.get('/api/health', async () => {
  return {
    status: 'ok',
    platform: 'ruxt-next',
    version: '1.0.0',
    activeProvider: providerHub.getActiveProvider(),
    tunnelConnected: tunnelClients.size > 0,
    timestamp: Date.now()
  };
});

fastify.get('/api/agents', async () => {
  const agents = await agentService.getAllAgents();
  return { agents };
});

fastify.post('/api/agents', async (request, reply) => {
  const admin = await requireAdmin(request, reply);
  if (!admin) return;

  const body = request.body as any;
  if (!body?.name || !body?.role) {
    return reply.status(400).send({ error: 'Name and role are required' });
  }
  const slug = String(body.slug || body.name).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  const agent = await agentService.createAgent({
    name: body.name,
    slug,
    role: body.role,
    description: body.description || `${body.name} - ${body.role}`,
    avatar: body.avatar || '🤖',
    systemPrompt: body.systemPrompt || `You are ${body.name}, an expert ${body.role}.`,
    canvasType: body.canvasType || 'none',
    skills: body.skills || [],
    domain: body.domain || 'general',
    strictGuardrails: body.strictGuardrails !== undefined ? Boolean(body.strictGuardrails) : true,
    targetLegendLevel: body.targetLegendLevel || 'Master / Legend',
    curriculum: body.curriculum || [],
    specializedKnowledge: body.specializedKnowledge || [],
    guardrailRedirectMessage: body.guardrailRedirectMessage || undefined,
    isBuiltin: false,
    isInstalled: true
  });
  broadcast('agent:created', agent);
  return { ok: true, agent };
});

fastify.post('/api/agents/:id/toggle', async (request, reply) => {
  const { id } = request.params as { id: string };
  const updated = await agentService.toggleInstall(id);
  if (!updated) return reply.status(404).send({ error: 'Agent not found' });
  broadcast('agent:updated', updated);
  return { ok: true, agent: updated };
});

fastify.get('/api/chat/:agentId', async (request) => {
  const { agentId } = request.params as { agentId: string };
  const messages = await taskService.getChatHistory(agentId);
  return { messages };
});

fastify.delete('/api/chat/:agentId', async (request) => {
  const { agentId } = request.params as { agentId: string };
  await taskService.clearChatHistory(agentId);
  return { ok: true, agentId };
});

fastify.delete('/api/chat', async () => {
  await taskService.clearChatHistory();
  return { ok: true };
});

fastify.get('/api/system/stats', async () => {
  const stats = await taskService.getStats();
  const allAgents = await agentService.getAllAgents();
  return {
    ok: true,
    messageCount: stats.messageCount,
    taskCount: stats.taskCount,
    agentCount: allAgents.length,
    installedCount: allAgents.filter((a) => a.isInstalled).length,
    uptimeSeconds: Math.floor(process.uptime()),
    memoryUsageMB: Math.round(process.memoryUsage().rss / (1024 * 1024)),
    nodeVersion: process.version,
    platform: process.platform,
    tunnelClientsCount: tunnelClients.size
  };
});

fastify.get('/api/tasks', async (request) => {
  const { agentId } = request.query as { agentId?: string };
  const tasks = await taskService.getTasks(agentId);
  return { tasks };
});

fastify.post('/api/tasks', async (request, reply) => {
  const body = request.body as any;
  if (!body?.agentId || !body?.prompt) {
    return reply.status(400).send({ error: 'agentId and prompt are required' });
  }
  const task = await taskService.executeTask(body.agentId, String(body.prompt).trim(), broadcast);
  return { ok: true, task };
});

// Provider Hub Management Endpoints
fastify.get('/api/providers', async () => {
  const providers = await providerHub.getProvidersStatus();
  return {
    activeProvider: providerHub.getActiveProvider(),
    providers
  };
});

fastify.post('/api/providers/active', async (request, reply) => {
  const body = request.body as { provider: ProviderId };
  if (!body?.provider) {
    return reply.status(400).send({ error: 'provider is required' });
  }
  providerHub.setActiveProvider(body.provider);
  broadcast('provider:active', { activeProvider: body.provider });
  return { ok: true, activeProvider: body.provider };
});

fastify.post('/api/providers/configure', async (request, reply) => {
  const body = request.body as any;
  if (body?.provider === 'gemini') {
    geminiProvider.setApiKey(body.apiKey, body.model);
    return { ok: true, provider: 'gemini', configured: geminiProvider.isConfigured() };
  }
  if (body?.provider === 'openai') {
    openAIProvider.setApiKey(body.apiKey, body.model);
    return { ok: true, provider: 'openai', configured: openAIProvider.isConfigured() };
  }
  if (body?.provider === 'ollama') {
    ollamaProvider.setEndpoint(body.endpoint || 'http://localhost:11434', body.model);
    return { ok: true, provider: 'ollama' };
  }
  return reply.status(400).send({ error: 'Unknown provider' });
});

fastify.post('/api/providers/test', async (request) => {
  const body = request.body as { provider: string };
  if (body?.provider === 'gemini') {
    const test = await geminiProvider.testConnection();
    return test;
  }
  if (body?.provider === 'openai') {
    const test = await openAIProvider.testConnection();
    return test;
  }
  if (body?.provider === 'ollama') {
    const test = await ollamaProvider.isAvailable();
    return { ok: test.available, message: test.available ? `Connected (${test.models.length} models)` : 'Ollama offline' };
  }
  return { ok: true, message: 'Simulator engine is always online' };
});

// Outbound Tunnel Command Execution via Local IDE Bridge
fastify.post('/api/tunnel/exec', async (request, reply) => {
  const body = request.body as { command: string };
  if (!body?.command) {
    return reply.status(400).send({ error: 'command is required' });
  }

  if (tunnelClients.size === 0) {
    return reply.status(503).send({
      ok: false,
      error: 'No outbound tunnel companion connected. Run "npm run tunnel" on your local machine to connect.'
    });
  }

  const execId = `cmd_${Date.now()}_${Math.random().toString(36).substring(2, 6)}`;
  const firstTunnelWs = Array.from(tunnelClients)[0];

  return new Promise((resolve) => {
    const timer = setTimeout(() => {
      pendingTunnelResolvers.delete(execId);
      resolve({ ok: false, error: 'Command timed out after 10s' });
    }, 10000);

    pendingTunnelResolvers.set(execId, (result) => {
      clearTimeout(timer);
      resolve({ ok: true, result });
    });

    firstTunnelWs.send(JSON.stringify({
      type: 'tunnel:exec_command',
      payload: {
        id: execId,
        command: body.command
      }
    }));
  });
});

// Serve frontend static build if available
const webDistDir = path.resolve(__dirname, '../../web/dist');
if (fs.existsSync(webDistDir)) {
  await fastify.register(fastifyStatic, {
    root: webDistDir,
    prefix: '/'
  });

  fastify.setNotFoundHandler((req, reply) => {
    if (req.raw.url && req.raw.url.startsWith('/api')) {
      reply.status(404).send({ error: 'API route not found' });
    } else {
      reply.sendFile('index.html');
    }
  });
}

// Start server
const rawPort = process.env.PORT;
const isSocket = rawPort && isNaN(Number(rawPort));

async function bootstrap() {
  try {
    await agentService.ensureSeedAgents();
    await authService.ensureAdminUser();
    
    if (isSocket && rawPort) {
      await fastify.listen({ path: rawPort });
      console.log(`\n======================================================`);
      console.log(`🚀 RuXt Next-Gen Server Running on socket: ${rawPort}`);
      console.log(`✨ Single-Port Unified Architecture (cPanel / Passenger Mode)`);
      console.log(`🎯 Active Provider: ${providerHub.getActiveProvider().toUpperCase()}`);
      console.log(`======================================================\n`);
    } else {
      const port = Number(rawPort) || 1234;
      await fastify.listen({ port, host: '0.0.0.0' });
      console.log(`\n======================================================`);
      console.log(`🚀 RuXt Next-Gen Server Running on http://localhost:${port}`);
      console.log(`✨ Single-Port Unified Architecture (API + WebSockets + UI)`);
      console.log(`🎯 Active Provider: ${providerHub.getActiveProvider().toUpperCase()}`);
      console.log(`======================================================\n`);
    }
  } catch (err) {
    console.error('Failed to start server:', err);
    process.exit(1);
  }
}

bootstrap();
