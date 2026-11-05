# Next-Gen Agent Platform Architectural Invariants

1. **Zero Legacy Contamination**:
   - Never copy, import, or adapt code or static assets from `ruxt-handoff-v21`. The project in `ruxt-next` is built 100% from scratch.

2. **Unified Single-Port Delivery**:
   - The Fastify backend builds and serves the React 19 SPA directly alongside the REST and WebSocket endpoints on one unified port (`localhost:1234`). Avoid splitting into multiple developer ports.

3. **Hybrid Outbound WebSocket Tunnel**:
   - All Cloud-to-Local IDE execution (Antigravity, Claude Code, Cursor, Codex) must use the Outbound Secure WebSocket Tunnel (`npx ruxt-tunnel`). Never require inbound port forwarding or public firewall holes on the user's local PC.

4. **Data-Driven Agent Architecture**:
   - Agent creation via Admin Studio is 100% data-driven. All agent configuration (`systemPrompt`, `domain`, `strictGuardrails`, `curriculum`, `specializedKnowledge`, `canvasType`, `dashboardConfig`) is stored as columns/JSON in the SQLite database.
   - **Never generate source code files** (`.ts`, `.tsx`, component files) when creating a new agent. New agents are database rows, not new code.

5. **Agent Dashboard / Canvas Pipeline**:
   - Every agent's dashboard is determined by its `canvasType` field in the database.
   - The rendering flow is: **SQLite `canvas_type` → Fastify API (`GET /api/agents`) → App.tsx conditional render → Canvas component**.
   - Canvas components live in `apps/web/src/components/canvas/` and are registered via conditional checks in `App.tsx`.
   - Canvas ↔ Chat interaction uses callback props (e.g., `onMoveMade`) that dispatch messages through the 4-Phase Cognition Pipeline.
   - If `canvasType === 'none'`, no canvas panel renders — chat takes full width.
   - Adding a new canvas type requires: (a) extending the `CanvasType` union in both `domain/types.ts` and `web/types/index.ts`, (b) creating the component in `canvas/`, and (c) adding the conditional render in `App.tsx`.

6. **Admin Agent Lifecycle**:
   - Agents are created through the Admin Studio with industry presets, domain guardrails, and dashboard configuration.
   - Live sandbox verification must be available before publishing to the Marketplace.
   - Agent cognition follows the standardized 4-Phase Pipeline: Understand → Plan & Guardrail → Execute & Stream → Verify.
