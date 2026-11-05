import { db } from '../db/index.js';
import { agentsTable } from '../db/schema.js';
import { eq } from 'drizzle-orm';
import { Agent, CurriculumLevel } from '../domain/types.js';

const elenaCurriculum: CurriculumLevel[] = [
  {
    id: 'lvl_novice',
    level: 'Tier 1: Novice & Fundamentals',
    eloRange: '800 - 1200 Elo',
    focus: 'Center Control, Piece Development, and Blunder Prevention',
    milestones: [
      'Control central squares (e4, d4, e5, d5)',
      'Knights before Bishops development principle',
      'Castle early for King safety',
      'Never leave pieces hanging unprotected'
    ]
  },
  {
    id: 'lvl_club',
    level: 'Tier 2: Club Tactician',
    eloRange: '1200 - 1600 Elo',
    focus: 'Tactical Weaponry, Forks, Pins, Skewers & Opening Repertoire',
    milestones: [
      'Master tactical motifs: Absolute Pins, Royal Forks, Skewers',
      'Discovered attacks and Double Checks',
      'Solidify opening choices (Sicilian, Ruy Lopez, Queen’s Gambit)',
      'Basic King and Pawn endgame mechanics'
    ]
  },
  {
    id: 'lvl_master',
    level: 'Tier 3: Strategic Master',
    eloRange: '1600 - 2000 Elo',
    focus: 'Pawn Structures, Weak Squares, Prophylaxis & Positional Play',
    milestones: [
      'Assess pawn islands, doubled pawns, and backward pawns',
      'Outpost occupation and bishop pair advantage',
      'Prophylactic thinking (preventing opponent counterplay)',
      'Rook endgames: Lucena and Philidor positions'
    ]
  },
  {
    id: 'lvl_legend',
    level: 'Tier 4: Chess Legend',
    eloRange: '2000 - 2800+ Elo (Grandmaster)',
    focus: 'Deep Calculation Trees, Intuitive Sacrifices, Psychological Warfare & Endgame Flawlessness',
    milestones: [
      'Dynamic sacrifices in the style of Mikhail Tal and Garry Kasparov',
      'Flawless endgame conversion in the style of Capablanca and Magnus Carlsen',
      'Deep opening novelties and computer preparation',
      'Time management and psychological resilience under championship pressure'
    ]
  }
];

export class AgentService {
  async ensureSeedAgents(): Promise<void> {
    const existing = await db.select().from(agentsTable);

    const initialAgents: Agent[] = [
      {
        id: 'agent_elena_voss',
        name: 'Elena Voss',
        slug: 'elena-voss',
        role: 'Master Chess Coach',
        description: 'Interactive grandmaster coach with strict domain focus, real-time board analysis, tactical drills, and a path to Chess Legend status.',
        avatar: '♟️',
        systemPrompt: 'You are Elena Voss, an authoritative and insightful Grandmaster Chess Coach. You speak EXCLUSIVELY about chess, strategy, openings, tactics, and the road to grandmaster mastery. Firmly and politely decline any non-chess questions and pivot back to the 64 squares.',
        canvasType: 'chess',
        skills: ['Opening Book Engine', 'Tactical Blunder Detector', 'Interactive Board Sync', 'Legend Curriculum Path', 'Endgame Mastery'],
        domain: 'chess',
        strictGuardrails: true,
        targetLegendLevel: 'Chess Legend (2800+ Grandmaster)',
        curriculum: elenaCurriculum,
        specializedKnowledge: [
          'Sicilian Defense (Najdorf, Dragon, Scheveningen)',
          'Ruy Lopez & Italian Game',
          'Queen’s Gambit & King’s Indian Defense',
          'Lucena & Philidor Rook Endgames',
          'Mikhail Tal Style Dynamic Sacrifices',
          'Kasparov & Carlsen Championship Technique'
        ],
        guardrailRedirectMessage: 'My sacred dedication is exclusively to your chess mastery and the path to Grandmaster. I do not engage in non-chess subjects. Let us return to the 64 squares — what opening, move, or tactical pattern shall we examine?',
        isBuiltin: true,
        isInstalled: true,
        createdAt: Date.now()
      },
      {
        id: 'agent_noah_reed',
        name: 'Noah Reed',
        slug: 'noah-reed',
        role: 'Tax & Compliance Adviser',
        description: 'Concise, evidence-minded adviser for statutory filing, compliance schedules, and audit trails.',
        avatar: '⚖️',
        systemPrompt: 'You are Noah Reed, a strict and careful compliance adviser. Review risk factors objectively.',
        canvasType: 'audit',
        skills: ['Statutory Filing Audit', 'Expense Deductibility Review', 'Risk Score Analysis'],
        domain: 'compliance',
        strictGuardrails: true,
        targetLegendLevel: 'Senior Partner / Statutory Auditor',
        curriculum: [],
        specializedKnowledge: ['IFRS & GAAP Compliance', 'Cross-Border VAT & Corporate Tax', 'Internal Audit Controls'],
        isBuiltin: true,
        isInstalled: true,
        createdAt: Date.now()
      },
      {
        id: 'agent_numbers',
        name: 'Numbers Agent',
        slug: 'numbers-agent',
        role: 'Spreadsheet & Financial Architect',
        description: 'Automates spreadsheet creation, budgets, timetables, and multi-sheet data structures.',
        avatar: '📊',
        systemPrompt: 'You are Numbers Agent. Build clean, validated workbooks with formulas and clear layouts.',
        canvasType: 'datagrid',
        skills: ['Formula Generator', 'Multi-tab Financials', 'CSV/Excel Export'],
        domain: 'finance',
        strictGuardrails: false,
        targetLegendLevel: 'Chief Financial Architect',
        curriculum: [],
        specializedKnowledge: ['DCF & Valuation Modeling', 'Multi-currency Consolidation', 'Dynamic Excel/Sheets Formulas'],
        isBuiltin: true,
        isInstalled: true,
        createdAt: Date.now()
      }
    ];

    if (existing.length === 0) {
      for (const agent of initialAgents) {
        await db.insert(agentsTable).values(agent as any);
      }
    } else {
      // Upgrade existing Elena Voss agent to ensure latest curriculum & guardrails
      const elena = existing.find((a) => a.id === 'agent_elena_voss');
      if (elena) {
        await db.update(agentsTable).set({
          domain: 'chess',
          strictGuardrails: true,
          targetLegendLevel: 'Chess Legend (2800+ Grandmaster)',
          curriculum: elenaCurriculum,
          specializedKnowledge: [
            'Sicilian Defense (Najdorf, Dragon, Scheveningen)',
            'Ruy Lopez & Italian Game',
            'Queen’s Gambit & King’s Indian Defense',
            'Lucena & Philidor Rook Endgames',
            'Mikhail Tal Style Dynamic Sacrifices',
            'Kasparov & Carlsen Championship Technique'
          ],
          guardrailRedirectMessage: 'My sacred dedication is exclusively to your chess mastery and the path to Grandmaster. I do not engage in non-chess subjects. Let us return to the 64 squares — what opening, move, or tactical pattern shall we examine?'
        }).where(eq(agentsTable.id, 'agent_elena_voss'));
      }
    }
  }

  async getAllAgents(includeUninstalled = true): Promise<Agent[]> {
    const rows = await db.select().from(agentsTable);
    return rows.map((r) => ({
      ...r,
      canvasType: r.canvasType as Agent['canvasType'],
      domain: (r.domain || 'general') as Agent['domain'],
      strictGuardrails: Boolean(r.strictGuardrails),
      skills: r.skills || [],
      curriculum: (r.curriculum || []) as CurriculumLevel[],
      specializedKnowledge: r.specializedKnowledge || [],
      targetLegendLevel: r.targetLegendLevel || 'Master / Legend',
      guardrailRedirectMessage: r.guardrailRedirectMessage || undefined
    }));
  }

  async getAgentBySlugOrId(identifier: string): Promise<Agent | null> {
    const rows = await db.select().from(agentsTable).where(eq(agentsTable.id, identifier));
    if (rows.length > 0) {
      const r = rows[0];
      return {
        ...r,
        canvasType: r.canvasType as Agent['canvasType'],
        domain: (r.domain || 'general') as Agent['domain'],
        strictGuardrails: Boolean(r.strictGuardrails),
        skills: r.skills || [],
        curriculum: (r.curriculum || []) as CurriculumLevel[],
        specializedKnowledge: r.specializedKnowledge || [],
        targetLegendLevel: r.targetLegendLevel || 'Master / Legend',
        guardrailRedirectMessage: r.guardrailRedirectMessage || undefined
      };
    }

    const bySlug = await db.select().from(agentsTable).where(eq(agentsTable.slug, identifier));
    if (bySlug.length > 0) {
      const r = bySlug[0];
      return {
        ...r,
        canvasType: r.canvasType as Agent['canvasType'],
        domain: (r.domain || 'general') as Agent['domain'],
        strictGuardrails: Boolean(r.strictGuardrails),
        skills: r.skills || [],
        curriculum: (r.curriculum || []) as CurriculumLevel[],
        specializedKnowledge: r.specializedKnowledge || [],
        targetLegendLevel: r.targetLegendLevel || 'Master / Legend',
        guardrailRedirectMessage: r.guardrailRedirectMessage || undefined
      };
    }

    return null;
  }

  async createAgent(data: Omit<Agent, 'id' | 'createdAt'>): Promise<Agent> {
    const newAgent: Agent = {
      ...data,
      id: `agent_${Date.now()}_${Math.random().toString(36).substring(2, 7)}`,
      createdAt: Date.now()
    };
    await db.insert(agentsTable).values(newAgent as any);
    return newAgent;
  }

  async toggleInstall(id: string): Promise<Agent | null> {
    const agent = await this.getAgentBySlugOrId(id);
    if (!agent) return null;
    const updatedStatus = !agent.isInstalled;
    await db.update(agentsTable).set({ isInstalled: updatedStatus }).where(eq(agentsTable.id, agent.id));
    return { ...agent, isInstalled: updatedStatus };
  }
}

export const agentService = new AgentService();
