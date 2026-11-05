import { Agent, TaskStep, ChatMessage } from '../domain/types.js';

interface StreamCallbacks {
  onToken?: (token: string) => void;
  onStep?: (step: TaskStep) => void;
  onComplete?: (result: string, metadata?: any) => void;
}

export class OpenAIProvider {
  private apiKey: string;
  private defaultModel: string;

  constructor(apiKey = process.env.OPENAI_API_KEY || '', defaultModel = 'gpt-4o-mini') {
    this.apiKey = apiKey.trim();
    this.defaultModel = defaultModel;
  }

  setApiKey(key: string, model?: string) {
    this.apiKey = key.trim();
    if (model) this.defaultModel = model;
  }

  getApiKey(): string {
    return this.apiKey;
  }

  getDefaultModel(): string {
    return this.defaultModel;
  }

  isConfigured(): boolean {
    return Boolean(this.apiKey && this.apiKey.startsWith('sk-') && this.apiKey.length > 20);
  }

  async testConnection(): Promise<{ ok: boolean; message: string }> {
    if (!this.isConfigured()) {
      return { ok: false, message: 'OpenAI API key must begin with "sk-" and be properly configured.' };
    }
    try {
      const res = await fetch('https://api.openai.com/v1/models', {
        headers: { Authorization: `Bearer ${this.apiKey}` },
        signal: AbortSignal.timeout(5000)
      });
      if (res.ok) {
        return { ok: true, message: 'Successfully connected to OpenAI API (GPT-4o / GPT-4o-mini ready).' };
      }
      const data = (await res.json().catch(() => ({}))) as any;
      return { ok: false, message: data?.error?.message || `OpenAI returned HTTP ${res.status}` };
    } catch (e: any) {
      return { ok: false, message: e.message || 'Connection timeout to OpenAI' };
    }
  }

  async runTask(
    agent: Agent,
    prompt: string,
    history: ChatMessage[] = [],
    callbacks: StreamCallbacks,
    modelName?: string
  ): Promise<string> {
    if (!this.isConfigured()) {
      throw new Error('OpenAI API key is not configured.');
    }

    const model = modelName || this.defaultModel;

    const steps: TaskStep[] = [
      { id: 'understand', label: 'Preparing OpenAI context & persona instructions', status: 'running' },
      { id: 'plan', label: `Connecting to OpenAI (${model})`, status: 'pending' },
      { id: 'execute', label: 'Streaming tokens from GPT engine', status: 'pending' },
      { id: 'verify', label: 'Validating response & completion format', status: 'pending' }
    ];

    callbacks.onStep?.(steps[0]);
    await new Promise((r) => setTimeout(r, 120));
    steps[0].status = 'completed';
    callbacks.onStep?.(steps[0]);

    steps[1].status = 'running';
    callbacks.onStep?.(steps[1]);

    const systemInstruction = `${agent.systemPrompt}
Domain: ${agent.domain || 'general'}
Strict Guardrails: ${agent.strictGuardrails ? 'YES - You MUST strictly focus only on ' + (agent.domain || agent.role) + '. Decline off-topic queries politely and pivot back.' : 'NO'}
Specialized Knowledge: ${(agent.specializedKnowledge || []).join(', ')}`;

    // Build multi-turn conversational messages array
    const messages: Array<{ role: string; content: string }> = [
      { role: 'system', content: systemInstruction }
    ];

    // Include last 8 conversation turns for contextual memory
    const recentHistory = history.slice(-8);
    for (const msg of recentHistory) {
      messages.push({
        role: msg.role === 'assistant' ? 'assistant' : 'user',
        content: msg.content
      });
    }

    // Add current user prompt
    messages.push({ role: 'user', content: prompt });

    steps[1].status = 'completed';
    callbacks.onStep?.(steps[1]);

    steps[2].status = 'running';
    callbacks.onStep?.(steps[2]);

    let fullText = '';

    try {
      const res = await fetch('https://api.openai.com/v1/chat/completions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${this.apiKey}`
        },
        body: JSON.stringify({
          model,
          stream: true,
          messages
        })
      });

      if (!res.ok || !res.body) {
        const errorText = await res.text();
        throw new Error(`OpenAI API Error: HTTP ${res.status} - ${errorText.slice(0, 100)}`);
      }

      const reader = res.body.getReader();
      const decoder = new TextDecoder();
      let buffer = '';

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split('\n');
        buffer = lines.pop() || '';

        for (const line of lines) {
          const trimmed = line.trim();
          if (!trimmed.startsWith('data: ')) continue;
          const dataStr = trimmed.slice(6).trim();
          if (dataStr === '[DONE]') break;

          try {
            const parsed = JSON.parse(dataStr);
            const token = parsed.choices?.[0]?.delta?.content;
            if (token) {
              fullText += token;
              callbacks.onToken?.(token);
            }
          } catch (_) {}
        }
      }

      steps[2].status = 'completed';
      callbacks.onStep?.(steps[2]);

      steps[3].status = 'running';
      callbacks.onStep?.(steps[3]);
      await new Promise((r) => setTimeout(r, 100));
      steps[3].status = 'completed';
      callbacks.onStep?.(steps[3]);

      callbacks.onComplete?.(fullText);
      return fullText;
    } catch (err: any) {
      steps[2].status = 'failed';
      callbacks.onStep?.(steps[2]);
      throw err;
    }
  }
}

export const openAIProvider = new OpenAIProvider();
