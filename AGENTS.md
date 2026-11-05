# RuXt Next-Gen: AI Agent Operating Workspace

Welcome to **RuXt Next-Gen**, a local-first, cloud-ready AI Agent Operating Workspace.

## Project Invariants
1. **Zero Legacy Contamination**: Built 100% from scratch. Do not copy or import old code from `ruxt-handoff-v21`.
2. **Unified Single-Port Delivery**: Fastify builds & serves the React 19 Frontend SPA, REST API, and WebSocket channels on a single port (`http://localhost:1234`).
3. **Outbound Secure WebSocket Tunnel**: Cloud-to-Local IDE bridges (Antigravity, Claude Code, Cursor, Codex) connect via an outbound persistent WebSocket. Never require public inbound ports on user devices.
4. **Admin Agent Studio & Marketplace**: Admins build, refine, sandbox-test, and publish agents with strict domain guardrails and progression curriculums.
5. **Multi-Model Provider Hub**: Dual-mode engine: Zero-Setup Simulation Engine (instant offline token streaming) seamlessly switchable to real neural models (Local Ollama, Google Gemini Pro/Flash, OpenAI).

## Agent Cognition & Response Pipeline
Every agent created and run in RuXt Next-Gen operates under a standardized 4-Phase Thinking Pipeline:
1. **Phase 1: Understand (Context & Persona Analysis)**:
   - Parses user input, active agent persona directives (`systemPrompt`), and conversation history.
2. **Phase 2: Plan & Guardrail Verification**:
   - Evaluates `strictGuardrails`. If an off-domain query is detected (e.g. asking a chess coach for code or recipes), it activates an in-character domain boundary interceptor.
   - Selects appropriate domain engines (e.g. Chess Openings/Tactics Engine, Financial Modeling, Compliance Checklist).
3. **Phase 3: Execution & Token Streaming**:
   - Generates contextual reasoning and streams tokens via WebSockets (`chat:token`) to the UI with sub-second latency.
   - Dispatches real-time micro-frontend canvas events (e.g. Chessboard move reactions, Grid formula recalculations).
4. **Phase 4: Verification & Completion**:
   - Validates response quality against domain boundaries and records the task outcome in SQLite WAL.

## Quick Start
```bash
npm install
npm run dev
```
Open [http://localhost:1234](http://localhost:1234) in your browser.
