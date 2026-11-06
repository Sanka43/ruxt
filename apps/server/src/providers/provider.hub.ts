import { Agent, TaskStep, ProviderStatus, ChatMessage } from '../domain/types.js';
import { simulator } from './simulator.js';
import { ollamaProvider } from './ollama.provider.js';
import { geminiProvider } from './gemini.provider.js';
import { openAIProvider } from './openai.provider.js';
import { antigravityProvider } from './antigravity.provider.js';
import { claudeProvider } from './claude.provider.js';
import { cursorProvider } from './cursor.provider.js';
import { codexProvider } from './codex.provider.js';

interface StreamCallbacks {
  onToken?: (token: string) => void;
  onStep?: (step: TaskStep) => void;
  onComplete?: (result: string, metadata?: any) => void;
}

export type ProviderId =
  | 'simulator'
  | 'antigravity_pro'
  | 'cursor'
  | 'claude'
  | 'gemini'
  | 'openai'
  | 'codex'
  | 'ollama'
  | 'local_ide';

export class ProviderHub {
  private activeProviderId: ProviderId = 'simulator';
  private tunnelConnected = false;
  private tunnelClientCount = 0;

  setActiveProvider(id: ProviderId) {
    this.activeProviderId = id;
  }

  getActiveProvider(): ProviderId {
    return this.activeProviderId;
  }

  setTunnelStatus(connected: boolean, clientCount = 0) {
    this.tunnelConnected = connected;
    this.tunnelClientCount = clientCount;
    cursorProvider.setBridgeStatus(connected);
  }

  async getProvidersStatus(): Promise<ProviderStatus[]> {
    const ollamaStatus = await ollamaProvider.isAvailable();
    const isGeminiReady = geminiProvider.isConfigured();
    const isOpenAIReady = openAIProvider.isConfigured();
    const agInfo = antigravityProvider.getAccountInfo();
    const claudeInfo = claudeProvider.getAccountInfo();
    const cursorInfo = cursorProvider.getAccountInfo();
    const codexInfo = codexProvider.getAccountInfo();

    return [
      {
        id: 'antigravity_pro',
        name: 'Antigravity Pro Neural Brain',
        type: 'antigravity_pro',
        connected: agInfo.connected,
        label: this.activeProviderId === 'antigravity_pro' ? 'Active Engine' : agInfo.connected ? 'Pro Ready' : 'Connect Account',
        detail: agInfo.connected
          ? `Linked to ${agInfo.email} (${agInfo.tier})`
          : 'Link your registered Antigravity Pro email to unlock high-intelligence reasoning'
      },
      {
        id: 'cursor',
        name: 'Cursor AI & Composer',
        type: 'cursor',
        connected: cursorInfo.connected,
        label: this.activeProviderId === 'cursor' ? 'Active Engine' : cursorInfo.connected ? 'Ready' : 'Connect Cursor',
        detail: cursorInfo.connected
          ? `Active (${cursorInfo.model}${cursorInfo.email ? ` • ${cursorInfo.email}` : ''})`
          : 'Connect Cursor Pro account or local IDE companion tunnel'
      },
      {
        id: 'claude',
        name: 'Anthropic Claude 3.5 / 3.7',
        type: 'claude',
        connected: claudeInfo.connected,
        label: this.activeProviderId === 'claude' ? 'Active Engine' : claudeInfo.connected ? 'Ready' : 'Requires API Key / Link',
        detail: claudeInfo.connected
          ? `Connected (${claudeInfo.model})`
          : 'Configure Anthropic API key (sk-ant-...) or connect Claude Pro'
      },
      {
        id: 'gemini',
        name: 'Google Gemini Pro / Flash API',
        type: 'gemini',
        connected: isGeminiReady,
        label: this.activeProviderId === 'gemini' ? 'Active Engine' : isGeminiReady ? 'Ready' : 'Requires API Key',
        detail: isGeminiReady ? `Connected (${geminiProvider.getDefaultModel()})` : 'Configure Google Gemini API key to enable'
      },
      {
        id: 'codex',
        name: 'OpenAI Codex Reasoning',
        type: 'codex',
        connected: codexInfo.connected,
        label: this.activeProviderId === 'codex' ? 'Active Engine' : codexInfo.connected ? 'Ready' : 'Connect Codex',
        detail: codexInfo.connected
          ? `Autonomous SWE (${codexInfo.model})`
          : 'Connect OpenAI Codex / ChatGPT Team or Pro key'
      },
      {
        id: 'openai',
        name: 'OpenAI GPT-4o / GPT-4o-mini',
        type: 'openai',
        connected: isOpenAIReady,
        label: this.activeProviderId === 'openai' ? 'Active Engine' : isOpenAIReady ? 'Ready' : 'Requires API Key',
        detail: isOpenAIReady ? `Connected (${openAIProvider.getDefaultModel()})` : 'Configure OpenAI API key (sk-...) to enable'
      },
      {
        id: 'ollama',
        name: 'Local Ollama Neural Server',
        type: 'ollama',
        connected: ollamaStatus.available,
        label: this.activeProviderId === 'ollama' ? 'Active Engine' : ollamaStatus.available ? 'Ready (Local Models)' : 'Offline',
        detail: ollamaStatus.available
          ? `Models detected: ${ollamaStatus.models.slice(0, 3).join(', ') || 'Default'}`
          : 'Detects http://localhost:11434 when Ollama is running'
      },
      {
        id: 'simulator',
        name: 'Built-in Simulation Engine',
        type: 'simulator',
        connected: true,
        label: this.activeProviderId === 'simulator' ? 'Active Engine' : 'Ready (Zero Setup)',
        detail: 'Instant token streaming, guardrails & chess/finance/audit intelligence'
      },
      {
        id: 'local_ide',
        name: 'Outbound Tunnel Companion',
        type: 'local_ide',
        connected: this.tunnelConnected,
        label: this.tunnelConnected ? `Connected (${this.tunnelClientCount} client)` : 'Standby',
        detail: this.tunnelConnected
          ? 'Outbound secure WebSocket bridge active for local commands & IDEs'
          : 'Run "npm run tunnel" or "npx ruxt-tunnel" to bridge IDE tools'
      }
    ];
  }

  async runTask(
    agent: Agent,
    prompt: string,
    history: ChatMessage[] = [],
    callbacks: StreamCallbacks
  ): Promise<string> {
    // 0. ANTIGRAVITY PRO
    if (this.activeProviderId === 'antigravity_pro') {
      try {
        if (antigravityProvider.isConfigured()) {
          return await antigravityProvider.runTask(agent, prompt, history, callbacks);
        }
      } catch (err: any) {
        console.warn('Antigravity Pro execution failed, falling back to simulator:', err.message);
        callbacks.onStep?.({
          id: 'fallback',
          label: 'Antigravity Pro unavailable - Falling back to Simulator',
          status: 'completed',
          detail: err.message
        });
      }
    }

    // 1. CURSOR AI & COMPOSER
    if (this.activeProviderId === 'cursor') {
      try {
        if (cursorProvider.isConfigured()) {
          return await cursorProvider.runTask(agent, prompt, history, callbacks);
        }
      } catch (err: any) {
        console.warn('Cursor execution failed, falling back to simulator:', err.message);
        callbacks.onStep?.({
          id: 'fallback',
          label: 'Cursor unavailable - Falling back to Simulator',
          status: 'completed',
          detail: err.message
        });
      }
    }

    // 2. ANTHROPIC CLAUDE
    if (this.activeProviderId === 'claude') {
      try {
        if (claudeProvider.isConfigured()) {
          return await claudeProvider.runTask(agent, prompt, history, callbacks);
        }
      } catch (err: any) {
        console.warn('Claude execution failed, falling back to simulator:', err.message);
        callbacks.onStep?.({
          id: 'fallback',
          label: 'Claude API failed - Falling back to Simulator',
          status: 'completed',
          detail: err.message
        });
      }
    }

    // 3. OPENAI CODEX
    if (this.activeProviderId === 'codex') {
      try {
        if (codexProvider.isConfigured()) {
          return await codexProvider.runTask(agent, prompt, history, callbacks);
        }
      } catch (err: any) {
        console.warn('Codex execution failed, falling back to simulator:', err.message);
        callbacks.onStep?.({
          id: 'fallback',
          label: 'Codex unavailable - Falling back to Simulator',
          status: 'completed',
          detail: err.message
        });
      }
    }

    // 4. OPENAI GPT-4o
    if (this.activeProviderId === 'openai') {
      try {
        if (openAIProvider.isConfigured()) {
          return await openAIProvider.runTask(agent, prompt, history, callbacks);
        }
      } catch (err: any) {
        console.warn('OpenAI execution failed, falling back to simulator:', err.message);
        callbacks.onStep?.({
          id: 'fallback',
          label: 'OpenAI API failed - Falling back to Built-in Simulator',
          status: 'completed',
          detail: err.message
        });
      }
    }

    // 2. GEMINI
    if (this.activeProviderId === 'gemini') {
      try {
        if (geminiProvider.isConfigured()) {
          return await geminiProvider.runTask(agent, prompt, history, callbacks);
        }
      } catch (err: any) {
        console.warn('Gemini execution failed, falling back to simulator:', err.message);
        callbacks.onStep?.({
          id: 'fallback',
          label: 'Gemini API failed - Falling back to Built-in Simulator',
          status: 'completed',
          detail: err.message
        });
      }
    }

    // 3. OLLAMA
    if (this.activeProviderId === 'ollama') {
      try {
        const ollamaCheck = await ollamaProvider.isAvailable();
        if (ollamaCheck.available) {
          return await ollamaProvider.runTask(agent, prompt, callbacks);
        }
      } catch (err: any) {
        console.warn('Ollama execution failed, falling back to simulator:', err.message);
        callbacks.onStep?.({
          id: 'fallback',
          label: 'Local Ollama unavailable - Falling back to Built-in Simulator',
          status: 'completed',
          detail: err.message
        });
      }
    }

    // 4. DEFAULT: High-Intelligence Simulation Engine
    return await simulator.runTask(agent, prompt, callbacks);
  }
}

export const providerHub = new ProviderHub();
