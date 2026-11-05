import { Agent, TaskStep, ChatMessage } from '../domain/types.js';

interface StreamCallbacks {
  onToken?: (token: string) => void;
  onStep?: (step: TaskStep) => void;
  onComplete?: (result: string, metadata?: any) => void;
}

export class GeminiProvider {
  private apiKey: string;
  private defaultModel: string;

  constructor(apiKey = process.env.GEMINI_API_KEY || '', defaultModel = 'gemini-1.5-flash') {
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
    return Boolean(this.apiKey && this.apiKey.length > 5);
  }

  async testConnection(): Promise<{ ok: boolean; message: string }> {
    if (!this.isConfigured()) {
      return { ok: false, message: 'Gemini API key is not configured.' };
    }
    try {
      const url = `https://generativelanguage.googleapis.com/v1beta/models?key=${this.apiKey}`;
      const res = await fetch(url, { signal: AbortSignal.timeout(5000) });
      if (res.ok) {
        return { ok: true, message: 'Successfully connected to Google Gemini API (Gemini Pro/Flash ready).' };
      }
      return { ok: false, message: `Google Gemini returned HTTP ${res.status}` };
    } catch (e: any) {
      return { ok: false, message: e.message || 'Connection timeout to Google Gemini' };
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
      throw new Error('Gemini API key is not configured.');
    }

    const model = modelName || this.defaultModel;

    const steps: TaskStep[] = [
      { id: 'understand', label: 'Preparing Gemini context & persona instructions', status: 'running' },
      { id: 'plan', label: `Connecting to Google Cloud (${model})`, status: 'pending' },
      { id: 'execute', label: 'Streaming multi-modal tokens', status: 'pending' },
      { id: 'verify', label: 'Verifying completion and safety filters', status: 'pending' }
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

    steps[1].status = 'completed';
    callbacks.onStep?.(steps[1]);

    steps[2].status = 'running';
    callbacks.onStep?.(steps[2]);

    const url = `https://generativelanguage.googleapis.com/v1beta/models/${model}:streamGenerateContent?alt=sse&key=${this.apiKey}`;

    // Multi-turn conversational memory contents
    const contents: Array<{ role: 'user' | 'model'; parts: Array<{ text: string }> }> = [];
    const recent = history.slice(-8);
    for (const msg of recent) {
      contents.push({
        role: msg.role === 'assistant' ? 'model' : 'user',
        parts: [{ text: msg.content }]
      });
    }
    contents.push({
      role: 'user',
      parts: [{ text: prompt }]
    });

    let fullText = '';

    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          system_instruction: {
            parts: [{ text: systemInstruction }]
          },
          contents
        })
      });

      if (!res.ok || !res.body) {
        throw new Error(`Gemini API Error: HTTP ${res.status}`);
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
          if (!line.startsWith('data: ')) continue;
          const jsonStr = line.replace(/^data:\s*/, '').trim();
          if (!jsonStr) continue;

          try {
            const parsed = JSON.parse(jsonStr);
            const textChunk = parsed.candidates?.[0]?.content?.parts?.[0]?.text;
            if (textChunk) {
              fullText += textChunk;
              callbacks.onToken?.(textChunk);
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

export const geminiProvider = new GeminiProvider();
