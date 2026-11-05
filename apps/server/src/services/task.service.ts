import { db } from '../db/index.js';
import { tasksTable, messagesTable } from '../db/schema.js';
import { eq, desc } from 'drizzle-orm';
import { Task, ChatMessage, TaskStep } from '../domain/types.js';
import { agentService } from './agent.service.js';
import { providerHub } from '../providers/provider.hub.js';

type BroadcastFn = (event: string, payload: any) => void;

export class TaskService {
  async getChatHistory(agentId: string): Promise<ChatMessage[]> {
    const rows = await db.select().from(messagesTable).where(eq(messagesTable.agentId, agentId)).orderBy(messagesTable.createdAt);
    return rows.map((r) => ({
      ...r,
      role: r.role as ChatMessage['role'],
      metadata: r.metadata || {}
    }));
  }

  async getTasks(agentId?: string): Promise<Task[]> {
    let query = db.select().from(tasksTable);
    const rows = agentId
      ? await query.where(eq(tasksTable.agentId, agentId)).orderBy(desc(tasksTable.createdAt))
      : await query.orderBy(desc(tasksTable.createdAt));

    return rows.map((r) => ({
      ...r,
      status: r.status as Task['status'],
      currentStep: r.currentStep || 'understand',
      steps: (r.steps || []) as TaskStep[],
      outputFiles: r.outputFiles || []
    }));
  }

  async clearChatHistory(agentId?: string): Promise<{ clearedMessages: number; clearedTasks: number }> {
    if (agentId) {
      await db.delete(messagesTable).where(eq(messagesTable.agentId, agentId));
      await db.delete(tasksTable).where(eq(tasksTable.agentId, agentId));
    } else {
      await db.delete(messagesTable);
      await db.delete(tasksTable);
    }
    return { clearedMessages: 1, clearedTasks: 1 };
  }

  async getStats(): Promise<{ messageCount: number; taskCount: number }> {
    const msgs = await db.select().from(messagesTable);
    const tasks = await db.select().from(tasksTable);
    return { messageCount: msgs.length, taskCount: tasks.length };
  }

  async executeTask(agentId: string, prompt: string, broadcast: BroadcastFn): Promise<Task> {
    const agent = await agentService.getAgentBySlugOrId(agentId);
    if (!agent) throw new Error(`Agent not found: ${agentId}`);

    const taskId = `task_${Date.now()}_${Math.random().toString(36).substring(2, 6)}`;
    const initialSteps: TaskStep[] = [
      { id: 'understand', label: 'Analyzing request & persona context', status: 'pending' },
      { id: 'plan', label: 'Formulating step-by-step strategy', status: 'pending' },
      { id: 'execute', label: 'Executing agent actions & tools', status: 'pending' },
      { id: 'verify', label: 'Verifying outcome & quality checks', status: 'pending' }
    ];

    const task: Task = {
      id: taskId,
      agentId: agent.id,
      prompt,
      status: 'running',
      currentStep: 'understand',
      steps: initialSteps,
      createdAt: Date.now()
    };

    // Save User message
    const userMsg: ChatMessage = {
      id: `msg_${Date.now()}_u`,
      agentId: agent.id,
      role: 'user',
      content: prompt,
      taskId,
      createdAt: Date.now()
    };
    await db.insert(messagesTable).values(userMsg);
    broadcast('chat:message', userMsg);

    // Insert task into DB
    await db.insert(tasksTable).values({
      ...task,
      steps: initialSteps
    });
    broadcast('task:started', task);

    // Run through Active Provider (Ollama / Gemini / Simulator)
    let accumulatedTokens = '';
    const assistantMsgId = `msg_${Date.now()}_a`;

    await providerHub.runTask(agent, prompt, {
      onStep: (step) => {
        const found = task.steps.find((s) => s.id === step.id);
        if (found) {
          found.status = step.status;
          task.currentStep = step.id;
        }
        broadcast('task:step', { taskId, currentStep: task.currentStep, step, steps: task.steps });
      },
      onToken: (token) => {
        accumulatedTokens += token;
        broadcast('chat:token', {
          messageId: assistantMsgId,
          agentId: agent.id,
          taskId,
          token,
          fullText: accumulatedTokens
        });
      },
      onComplete: async (result) => {
        task.status = 'completed';
        task.result = result;
        task.finishedAt = Date.now();

        await db.update(tasksTable).set({
          status: 'completed',
          result,
          steps: task.steps,
          finishedAt: task.finishedAt
        }).where(eq(tasksTable.id, taskId));

        // Save Assistant message
        const assistantMsg: ChatMessage = {
          id: assistantMsgId,
          agentId: agent.id,
          role: 'assistant',
          content: result,
          taskId,
          createdAt: Date.now()
        };
        await db.insert(messagesTable).values(assistantMsg);

        broadcast('task:completed', task);
        broadcast('chat:message', assistantMsg);
      }
    });

    return task;
  }
}

export const taskService = new TaskService();
