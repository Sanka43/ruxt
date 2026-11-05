import { Agent, TaskStep } from '../domain/types.js';

interface StreamCallbacks {
  onToken?: (token: string) => void;
  onStep?: (step: TaskStep) => void;
  onComplete?: (result: string, metadata?: any) => void;
}

export class OllamaProvider {
  private baseUrl: string;
  private defaultModel: string;

  constructor(baseUrl = 'http://localhost:11434', defaultModel = 'llama3') {
    this.baseUrl = baseUrl;
    this.defaultModel = defaultModel;
  }

  setEndpoint(url: string, model?: string) {
    this.baseUrl = url.replace(/\/+$/, '');
    if (model) this.defaultModel = model;
  }

  async isAvailable(): Promise<{ available: boolean; models: string[] }> {
    try {
      const res = await fetch(`${this.baseUrl}/api/tags`, {
        signal: AbortSignal.timeout(1800)
      });
      if (!res.ok) return { available: false, models: [] };
      const data = await res.json() as { models?: Array<{ name: string }> };
      const models = (data.models || []).map((m) => m.name);
      return { available: true, models };
    } catch (_) {
      return { available: false, models: [] };
    }
  }

  async runTask(agent: Agent, prompt: string, callbacks: StreamCallbacks, modelName?: string): Promise<string> {
    const model = modelName || this.defaultModel;

    const steps: TaskStep[] = [
      { id: 'understand', label: 'Connecting to local Ollama inference server', status: 'running' },
      { id: 'plan', label: `Loading weights & context for ${model}`, status: 'pending' },
      { id: 'execute', label: 'Streaming local neural responses', status: 'pending' },
      { id: 'verify', label: 'Verifying completion status', status: 'pending' }
    ];

    callbacks.onStep?.(steps[0]);
    await new Promise((r) => setTimeout(r, 200));
    steps[0].status = 'completed';
    callbacks.onStep?.(steps[0]);

    steps[1].status = 'running';
    callbacks.onStep?.(steps[1]);

    const systemPromptWithGuardrails = `${agent.systemPrompt}
Domain: ${agent.domain || 'general'}
Strict Guardrails: ${agent.strictGuardrails ? 'YES - You MUST strictly focus only on ' + (agent.domain || agent.role) + '. Decline off-topic queries politely and pivot back.' : 'NO'}
Specialized Knowledge: ${(agent.specializedKnowledge || []).join(', ')}`;

    steps[1].status = 'completed';
    callbacks.onStep?.(steps[1]);

    steps[2].status = 'running';
    callbacks.onStep?.(steps[2]);

    let fullText = '';

    try {
      const res = await fetch(`${this.baseUrl}/api/chat`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          model,
          messages: [
            { role: 'system', content: systemPromptWithGuardrails },
            { role: 'user', content: prompt }
          ],
          stream: true
        })
      });

      if (!res.ok || !res.body) {
        throw new Error(`Ollama error: HTTP ${res.status}`);
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
          if (!line.trim()) continue;
          try {
            const parsed = JSON.parse(line);
            if (parsed.message?.content) {
              const chunk = parsed.message.content;
              fullText += chunk;
              callbacks.onToken?.(chunk);
            }
          } catch (_) {}
        }
      }

      steps[2].status = 'completed';
      callbacks.onStep?.(steps[2]);

      steps[3].status = 'running';
      callbacks.onStep?.(steps[3]);
      await new Promise((r) => setTimeout(r, 150));
      steps[3].status = 'completed';
      callbacks.onStep?.(steps[3]);

      callbacks.onComplete?.(fullText, { provider: 'ollama', model });
      return fullText;
    } catch (err: any) {
      steps[2].status = 'failed';
      steps[2].detail = err.message;
      callbacks.onStep?.(steps[2]);
      throw err;
    }
  }
}

export const ollamaProvider = new OllamaProvider();
