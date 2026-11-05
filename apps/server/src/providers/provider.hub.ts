import { Agent, TaskStep, ProviderStatus, ChatMessage } from '../domain/types.js';
import { simulator } from './simulator.js';
import { ollamaProvider } from './ollama.provider.js';
import { geminiProvider } from './gemini.provider.js';
import { openAIProvider } from './openai.provider.js';

interface StreamCallbacks {
  onToken?: (token: string) => void;
  onStep?: (step: TaskStep) => void;
  onComplete?: (result: string, metadata?: any) => void;
}

export type ProviderId = 'simulator' | 'ollama' | 'gemini' | 'openai' | 'local_ide';

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
  }

  async getProvidersStatus(): Promise<ProviderStatus[]> {
    const ollamaStatus = await ollamaProvider.isAvailable();
    const isGeminiReady = geminiProvider.isConfigured();
    const isOpenAIReady = openAIProvider.isConfigured();

    return [
      {
        id: 'simulator',
        name: 'Built-in Simulation Engine',
        type: 'simulator',
        connected: true,
        label: this.activeProviderId === 'simulator' ? 'Active Engine' : 'Ready (Zero Setup)',
        detail: 'Instant token streaming, guardrails & chess/finance/audit intelligence'
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
    // 1. OPENAI
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
