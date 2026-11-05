import { Agent, Task, TaskStep } from '../domain/types.js';

interface StreamCallbacks {
  onToken?: (token: string) => void;
  onStep?: (step: TaskStep) => void;
  onComplete?: (result: string, metadata?: any) => void;
}

export class SimulatorEngine {
  async runTask(agent: Agent, prompt: string, callbacks: StreamCallbacks): Promise<string> {
    const isGuardrailTriggered = this.checkGuardrailViolation(agent, prompt);

    const steps: TaskStep[] = [
      { id: 'understand', label: 'Analyzing request & persona context', status: 'running' },
      { id: 'plan', label: isGuardrailTriggered ? 'Verifying domain boundaries & guardrails' : 'Formulating step-by-step strategy', status: 'pending' },
      { id: 'execute', label: isGuardrailTriggered ? 'Enforcing domain redirect' : 'Executing agent actions & tools', status: 'pending' },
      { id: 'verify', label: 'Verifying outcome & quality checks', status: 'pending' }
    ];

    // Step 1: Understand
    callbacks.onStep?.(steps[0]);
    await new Promise((r) => setTimeout(r, 380));
    steps[0].status = 'completed';
    callbacks.onStep?.(steps[0]);

    // Step 2: Plan
    steps[1].status = 'running';
    callbacks.onStep?.(steps[1]);
    await new Promise((r) => setTimeout(r, 420));
    steps[1].status = 'completed';
    callbacks.onStep?.(steps[1]);

    // Step 3: Execute
    steps[2].status = 'running';
    callbacks.onStep?.(steps[2]);
    await new Promise((r) => setTimeout(r, 480));
    steps[2].status = 'completed';
    callbacks.onStep?.(steps[2]);

    // Step 4: Verify
    steps[3].status = 'running';
    callbacks.onStep?.(steps[3]);
    await new Promise((r) => setTimeout(r, 320));
    steps[3].status = 'completed';
    callbacks.onStep?.(steps[3]);

    // Generate Contextual Response based on Agent Role and Guardrails
    const responseText = this.generateResponse(agent, prompt, isGuardrailTriggered);

    // Stream tokens smoothly
    const tokens = responseText.split(' ');
    for (const token of tokens) {
      callbacks.onToken?.(token + ' ');
      await new Promise((r) => setTimeout(r, 20));
    }

    callbacks.onComplete?.(responseText);
    return responseText;
  }

  private checkGuardrailViolation(agent: Agent, prompt: string): boolean {
    if (!agent.strictGuardrails) return false;

    const lower = prompt.toLowerCase().trim();

    // If Domain is CHESS
    if (agent.domain === 'chess' || agent.slug === 'elena-voss') {
      const chessKeywords = [
        'chess', 'move', 'board', 'pawn', 'knight', 'bishop', 'rook', 'queen', 'king',
        'checkmate', 'mate', 'stalemate', 'opening', 'defense', 'gambit', 'endgame',
        'middle game', 'middlegame', 'tactics', 'blunder', 'elo', 'rating', 'grandmaster',
        'master', 'legend', 'kasparov', 'fischer', 'carlsen', 'tal', 'capablanca', 'karpov',
        'morphy', 'fen', 'pgn', 'sicilian', 'ruy lopez', 'italian', 'french', 'caro-kann',
        'indian', 'london', 'puzzle', 'fork', 'pin', 'skewer', 'zugzwang', 'zwischenzug',
        'castling', 'promotion', 'capture', 'attack', 'sacrifice', 'scholar',
        'e4', 'd4', 'c4', 'nf3', 'nc6', 'e5', 'd5', 'c5', 'b6', 'g6', 'f4', 'play',
        'coach', 'curriculum', 'tier', 'drill', 'eval', 'evaluation', 'position', 'pieces'
      ];

      const offTopicKeywords = [
        'recipe', 'cook', 'cake', 'baking', 'food', 'restaurant', 'movie', 'cinema',
        'weather', 'python', 'javascript', 'typescript', 'write code', 'program', 'css',
        'html', 'react', 'flutter', 'java', 'c++', 'song', 'lyrics', 'love letter',
        'crypto', 'stock market', 'bitcoin', 'funny story', 'bedtime story', 'translate',
        'car repair', 'gym workout', 'diet plan', 'football', 'cricket'
      ];

      const hasOffTopic = offTopicKeywords.some((w) => lower.includes(w));
      const hasChessTopic = chessKeywords.some((w) => lower.includes(w));

      if (hasOffTopic && !hasChessTopic) {
        return true;
      }

      // Greetings are fine
      if (/^(hi|hello|hey|good morning|greetings|help|who are you|what can you do)/i.test(lower)) {
        return false;
      }

      // If prompt has clearly no chess terms and is long, it violates strict domain guardrail
      if (lower.length > 25 && !hasChessTopic) {
        return true;
      }
    }

    return false;
  }

  private generateResponse(agent: Agent, prompt: string, isGuardrailTriggered: boolean): string {
    const lower = prompt.toLowerCase();

    // 1. Guardrail Interception
    if (isGuardrailTriggered) {
      if (agent.domain === 'chess' || agent.slug === 'elena-voss') {
        return `🛡️ **[Strict Domain Guardrail Enforced]**

${agent.guardrailRedirectMessage || `As your dedicated ${agent.role}, my sacred mission is exclusively focused on your ascent to Chess Legend status and mastering the 64 squares. I do not engage in off-topic subjects such as outside trivia, cooking, or general programming.

To reach Grandmaster level, every moment of concentration at the board is vital. Let us return our discipline to chess:
- **Opening Mastery**: Would you like to study the Sicilian Defense, Ruy Lopez, or Queen's Gambit?
- **Tactical Sharpness**: Shall we explore Mikhail Tal's dynamic sacrifices or master the Greek Gift motif?
- **Interactive Board**: Make a move on the chessboard to your left, or ask me to evaluate your tactical weaknesses!`}`;
      }

      return `🛡️ **[Domain Boundary Notification]**

${agent.guardrailRedirectMessage || `I am configured exclusively as a specialist in ${agent.role}. Your inquiry lies outside my assigned operational boundaries. Please direct instructions relevant to my core domain.`}`;
    }

    // 2. High-Intelligence Chess Coach Engine (Elena Voss / Chess Domain)
    if (agent.domain === 'chess' || agent.slug === 'elena-voss') {
      return this.generateChessIntelligenceResponse(agent, prompt);
    }

    // 3. Compliance Domain (Noah Reed)
    if (agent.domain === 'compliance' || agent.slug === 'noah-reed') {
      return `From a strict regulatory and compliance perspective on "${prompt}":
1. **Statutory Filing Deadlines**: Quarter-end filings must be validated against official jurisdiction schedules with immutable audit logging.
2. **Deductible Boundaries & Substantiation**: Separate operational expenditure from capital depreciables. Invoices lacking verifiable tax IDs must be segregated into a risk-review queue.
3. **Audit Trail Retention**: Maintain cryptographic transaction records for 7 years. Any cross-border intra-group transfer pricing requires contemporaneous OECD documentation.`;
    }

    // 4. Finance Domain (Numbers Agent)
    if (agent.domain === 'finance' || agent.slug === 'numbers-agent') {
      return `I have structured your financial workbook model for "${prompt}":
- **Dynamic Cash Flow Projections**: Revenue lines calibrated with seasonal weighting and automated compound growth formulas (\`=SUMPRODUCT\`, \`=XLOOKUP\`).
- **Variance Analysis**: Automated budget-vs-actual variance tracking with conditional formatting indicators in the canvas grid.
- **Data Integrity**: All totals are cross-checked across multi-tab structures. You can review the interactive grid on your left or export as CSV/Excel.`;
    }

    // 5. Custom Agent Default
    return `I am ${agent.name}, operating as your dedicated ${agent.role}.
Persona System Directives: "${agent.systemPrompt}"
Task Processed: "${prompt}".
Domain boundary maintained. Let me know which parameter or action you would like to execute next.`;
  }

  private generateChessIntelligenceResponse(agent: Agent, prompt: string): string {
    const lower = prompt.toLowerCase();

    // Road to Legend / Curriculum / Elo Progression
    if (
      lower.includes('legend') ||
      lower.includes('grandmaster') ||
      lower.includes('curriculum') ||
      lower.includes('how to become') ||
      lower.includes('roadmap') ||
      lower.includes('improve') ||
      lower.includes('tier') ||
      lower.includes('elo') ||
      lower.includes('level')
    ) {
      return `👑 **The Road to Chess Legend: 4-Tier Master Curriculum**

To take you from your current level to a true **Chess Legend (2800+ Grandmaster)**, we follow a rigorous, disciplined progression system:

### 🏛️ Tier 1: Novice & Fundamentals (800 – 1200 Elo)
- **Central Dominance**: Control squares **e4, d4, e5, d5** with pawns before advancing flanks.
- **Piece Harmony**: Develop Knights before Bishops; never move the same piece twice in the opening without tactical justification.
- **King Sanctuary**: Castle within the first 7–10 moves to eliminate central mating threats.
- **Blunder Shield**: Perform a 2-second blunder check before committing: *"Is any of my pieces undefended?"*

### ⚔️ Tier 2: Club Tactician (1200 – 1600 Elo)
- **Tactical Armory**: Master Royal Forks, Absolute Pins, Skewers, Discovered Attacks, and Zwischenzug (in-between moves).
- **Core Openings**: Develop a solid White system (e.g., Italian Game / Queen's Gambit) and Black replies to 1. e4 (Sicilian / French) and 1. d4 (King's Indian / Slav).
- **Endgame Essentials**: King + Pawn opposition, square rule for passed pawns.

### 🧠 Tier 3: Positional Master (1600 – 2000 Elo)
- **Pawn Architecture**: Identify backward pawns, pawn islands, doubled pawns, and minority attacks.
- **Outpost Exploitation**: Anchor Knights on unassailable squares (e.g., d5 or e5) supported by pawns.
- **Prophylaxis**: Think in the style of Anatoly Karpov — anticipate and extinguish your opponent's active ideas before they materialize.
- **Rook Endgames**: Perfect mastery of the **Lucena Position** (building a bridge to promote) and the **Philidor Defense**.

### 🌟 Tier 4: Chess Legend (2000 – 2800+ Elo)
- **Dynamic Sacrifices**: Calculating sacrifices for initiative and king exposure in the tradition of **Mikhail Tal** and **Garry Kasparov**.
- **Crystal Conversion**: Flawlessly grinding minor advantages with technical precision like **José Raúl Capablanca** and **Magnus Carlsen**.
- **Deep Calculation Trees**: Visualizing 5–8 plies deep with candidate move elimination.

Tell me your current rating, or make a move on the interactive board on your left to begin Tier 1 diagnostics!`;
    }

    // Sicilian Defense
    if (lower.includes('sicilian') || lower.includes('c5')) {
      return `♟️ **Grandmaster Analysis: The Sicilian Defense (1. e4 c5)**

The Sicilian is Black's most combative and highest-scoring response to White's 1. e4. Rather than mirroring symmetry (1... e5), Black immediately fights for central control asymmetrically from the flank.

1. **Strategic Imbalance**: Black exchanges a flank pawn (\`c5\`) for White's central \`d4\` pawn after \`2. Nf3\` and \`3. d4 cxd4 4. Nxd4\`. This yields Black a central 2-to-1 pawn majority and an open c-file.
2. **Legendary Variations**:
   - **The Najdorf (5... a6)**: Championed by Bobby Fischer and Garry Kasparov. Controls \`b5\`, prepares \`...e5\` or \`...b5\`, and provides ultimate tactical flexibility.
   - **The Dragon (5... g6)**: Black fianchettoes the dark-squared bishop to dominate the long diagonal (\`a1-h8\`), leading to sharp double-edged Yugoslav Attack battles.
3. **Coach Key Principle**: Do not rush kingside castling if White launches the \`f4/g4/h4\` pawn storm. Strike back immediately in the center with \`...d5\`!`;
    }

    // Ruy Lopez / Spanish Opening
    if (lower.includes('ruy lopez') || lower.includes('spanish') || lower.includes('bb5')) {
      return `♟️ **Grandmaster Analysis: The Ruy Lopez (1. e4 e5 2. Nf3 Nc6 3. Bb5)**

The Ruy Lopez is the pinnacle of classical chess strategy, tested for over five centuries by world champions:

1. **The Tactical Idea**: White pins and puts immediate structural pressure on the c6 knight that defends Black's central e5 pawn.
2. **Positional Depth**: Unlike the Italian Game (\`3. Bc4\`), the Ruy Lopez generates sustained, long-term tension rather than an immediate clash. White frequently maneuvers the bishop via \`Ba4 \` to \`Bb3\`, supports the center with \`c3\` and \`d4\`, and brings the b1 knight via \`Nd2-f1-g3\`.
3. **Black's Legendary Defenses**:
   - **Morphy Defense (3... a6)**: Tests White's bishop immediately.
   - **Berlin Defense (3... Nf6)**: The "Berlin Wall" famously deployed by Vladimir Kramnik to defeat Garry Kasparov in 2000. Leads to an endgame where White's extra pawn is neutralized by Black's bishop pair.`;
    }

    // Queen's Gambit
    if (lower.includes('queen\'s gambit') || lower.includes('queens gambit') || lower.includes('d4 d5 c4')) {
      return `♟️ **Grandmaster Analysis: The Queen's Gambit (1. d4 d5 2. c4)**

The Queen's Gambit is not a true gambit because Black cannot safely retain the c4 pawn without severely compromising piece development:

1. **The Core Plan**: White attacks Black's strongpoint on \`d5\`. If Black plays \`2... dxc4\` (Accepted), White regains the pawn with \`e3\` or \`e4\` followed by \`Bxc4\`, securing complete central dominance.
2. **Queen's Gambit Declined (2... e6)**: Black firmly anchors \`d5\`, though the light-squared bishop on c8 becomes temporarily restricted.
3. **The Slav Defense (2... c6)**: A rock-solid favorite of world champions that protects \`d5\` without hemming in the c8 bishop.
4. **Grandmaster Takeaway**: Master the Carlsbad pawn structure after White plays \`cxd5 exd5\`. Look for the minority attack with \`b4-b5\` to create weaknesses in Black's queenside!`;
    }

    // Tactics, Sacrifices & Puzzles
    if (
      lower.includes('tactic') ||
      lower.includes('puzzle') ||
      lower.includes('sacrifice') ||
      lower.includes('tal') ||
      lower.includes('greek gift') ||
      lower.includes('fork') ||
      lower.includes('pin')
    ) {
      return `⚔️ **Grandmaster Tactical Drill: The Art of the Decisive Blow**

Legendary players do not just calculate tactics; they cultivate intuition for when a position is ripe for a strike.

1. **The Greek Gift Sacrifice (\`Bxh7+!\`)**:
   - **Trigger Conditions**: Black's king has castled kingside; Black has no knight defending on \`f6\`; White has a knight ready to jump to \`g5\`, a queen capable of reaching \`h5\`, and the light-squared bishop on \`d3\`.
   - **The Destruction**: \`1. Bxh7+ Kxh7 2. Ng5+ Kg8 (or Kg6) 3. Qh5!\` leads to unstoppable mating nets or massive material gain.
2. **Mikhail Tal's Sacrificial Philosophy**:
   *"You must take your opponent into a deep, dark forest where 2+2=5, and the path leading out is only wide enough for one."*
   When your opponent's king is stranded in the center or piece coordination is broken, do not count pawns — open lines with dynamic sacrifices!
3. **Practice Challenge**: Look at your interactive board. Click on any of the tactical scenario presets to test your tactical refutation right now!`;
    }

    // Move Analysis & Interactive Board feedback
    if (lower.includes('move') || lower.includes('played') || lower.includes('e4') || lower.includes('d4') || lower.includes('think')) {
      return `♟️ **Move Analysis & Tactical Evaluation**

I tracked your move on the interactive board:
1. **Center Influence**: You contested central real estate. In classical opening doctrine, claiming central space dictates who dictates the tempo of the middle game.
2. **Piece Harmony**: Notice how your pawn advances affect your diagonals. Every pawn move permanently forfeits control of squares behind it. Ensure your minor pieces (knights and bishops) are actively coordinated behind your pawn skeleton.
3. **Next Recommended Step**:
   - Solidify your king's flank with rapid castling.
   - If White challenges your outpost, prepare the central counter-strike.

Make your next move on the interactive board, or select one of the legendary drill scenarios to test your tactical precision!`;
    }

    // General Greeting & Master Introduction
    return `♟️ **Greetings, future Chess Legend.**

I am **Elena Voss**, your Grandmaster Chess Coach. My entire intellect is dedicated to elevating your tactical vision, opening repertoire, and positional understanding to the highest echelon of competitive chess.

Here is how we will work together:
1. **Interactive Board Synchronization**: On your left is your live chessboard. As you execute moves or select tactical drill scenarios, I provide real-time Grandmaster evaluations.
2. **Strict Concentration Guardrail**: Our sessions remain 100% focused on chess mastery. No distractions, no outside noise.
3. **The 4-Tier Progression**: Whether you are starting at 1000 Elo or pushing past 2200 Elo, we will systematically build your game to legend level.

To begin: Make your first move on the board, tell me your favorite opening, or ask me to diagnose your tactical game!`;
  }
}

export const simulator = new SimulatorEngine();
