<?php
// api/chat.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/agent_tools.php';
require_once __DIR__ . '/workspace_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['error' => 'Method not allowed'], 405);
}

$user = requireLogin(true);
$userId = (int)$user['id'];
$userName = !empty($user['name']) ? $user['name'] : 'there';

$input = json_decode(file_get_contents('php://input'), true);
// Find Chief of Staff as default executive
$chiefStmt = $pdo->query("SELECT id FROM executives WHERE is_chief_of_staff = 1 LIMIT 1");
$chiefRow = $chiefStmt->fetch();
$defaultExecId = $chiefRow ? intval($chiefRow['id']) : 1;

$userMessage = isset($input['message']) ? trim($input['message']) : '';
$sessionId = isset($input['session_id']) ? intval($input['session_id']) : 0;
$requestedExecId = isset($input['executive_id']) && intval($input['executive_id']) > 0 ? intval($input['executive_id']) : $defaultExecId;
$ventureId = isset($input['venture_id']) && intval($input['venture_id']) > 0 ? intval($input['venture_id']) : 1;
$attachments = isset($input['attachments']) && is_array($input['attachments']) ? $input['attachments'] : [];

if (empty($userMessage) && empty($attachments)) {
    sendJsonResponse(['error' => 'Message or attachment is required'], 400);
}

// If user sent attachments without text, provide a polite default prompt
if (empty($userMessage) && !empty($attachments)) {
    $firstAttName = $attachments[0]['original_name'] ?? 'file';
    $userMessage = "Please analyze the attached reference: {$firstAttName}";
}

$executiveId = $requestedExecId;

// 1. Create session if not provided or doesn't exist
$currentProjectId = isset($input['project_id']) && intval($input['project_id']) > 0 ? intval($input['project_id']) : null;
$currentProjectName = isset($input['project_name']) ? trim($input['project_name']) : null;

if ($sessionId <= 0) {
    // Generate a quick title from first 30 chars or attachment name
    if (!empty($attachments) && mb_strlen($userMessage) < 5) {
        $initialTitle = "Reference: " . ($attachments[0]['original_name'] ?? 'Attachment');
    } else {
        $initialTitle = mb_substr($userMessage, 0, 30);
        if (mb_strlen($userMessage) > 30) $initialTitle .= '...';
    }

    $stmt = $pdo->prepare("INSERT INTO sessions (title, executive_id, venture_id, project_id, project_name, user_id) VALUES (:title, :executive_id, :venture_id, :project_id, :project_name, :uid)");
    $stmt->execute([
        ':title' => $initialTitle,
        ':executive_id' => $executiveId,
        ':venture_id' => $ventureId,
        ':project_id' => $currentProjectId,
        ':project_name' => $currentProjectName,
        ':uid' => $userId
    ]);
    $sessionId = $pdo->lastInsertId();
} else {
    // Check if session exists and belongs to this user
    $stmt = $pdo->prepare("SELECT id, title, executive_id, venture_id, project_id, project_name FROM sessions WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $sessionId, ':uid' => $userId]);
    $session = $stmt->fetch();
    if (!$session) {
        $stmt = $pdo->prepare("INSERT INTO sessions (title, executive_id, venture_id, project_id, project_name, user_id) VALUES (:title, :executive_id, :venture_id, :project_id, :project_name, :uid)");
        $stmt->execute([
            ':title' => 'Executive Briefing',
            ':executive_id' => $executiveId,
            ':venture_id' => $ventureId,
            ':project_id' => $currentProjectId,
            ':project_name' => $currentProjectName,
            ':uid' => $userId
        ]);
        $sessionId = $pdo->lastInsertId();
    } else {
        $executiveId = !empty($session['executive_id']) ? intval($session['executive_id']) : $defaultExecId;
        if (!empty($session['venture_id'])) {
            $ventureId = intval($session['venture_id']);
        }
        if (!empty($session['project_id'])) {
            $currentProjectId = intval($session['project_id']);
        }
        if (!empty($session['project_name'])) {
            $currentProjectName = $session['project_name'];
        }
        if ($session['title'] === 'New Chat' || $session['title'] === 'Executive Briefing') {
            $updatedTitle = mb_substr($userMessage, 0, 30);
            if (mb_strlen($userMessage) > 30) $updatedTitle .= '...';
            $updateTitleStmt = $pdo->prepare("UPDATE sessions SET title = :title WHERE id = :id AND user_id = :uid");
            $updateTitleStmt->execute([':title' => $updatedTitle, ':id' => $sessionId, ':uid' => $userId]);
        }
    }
}

// 1.5 Auto-Detect Predefined Free Workspace Template Selection
$selectedTemplate = null;
$createdWorkspace = null;
if (!empty($userMessage)) {
    $cleanTplName = trim(preg_replace('/^[\p{Emoji}\s]+/u', '', $userMessage));
    $cleanTplName = trim(preg_replace('/^(my field is|workspace|i choose|i want to use|i select)\s*:?\s*/i', '', $cleanTplName));

    if (mb_strlen($cleanTplName) >= 3) {
        $tplStmt = $pdo->prepare("SELECT * FROM workspace_templates WHERE is_active = 1 AND (title = :t OR title LIKE :t2 OR slug = :s) LIMIT 1");
        $tplStmt->execute([
            ':t' => $cleanTplName,
            ':t2' => "%{$cleanTplName}%",
            ':s' => strtolower(str_replace(' ', '-', $cleanTplName))
        ]);
        $selectedTemplate = $tplStmt->fetch(PDO::FETCH_ASSOC);

        if ($selectedTemplate && $selectedTemplate['tier'] === 'free') {
            $chkV = $pdo->prepare("SELECT id, name, domain, description, icon, color FROM ventures WHERE user_id = :uid AND name = :name LIMIT 1");
            $chkV->execute([':uid' => $userId, ':name' => $selectedTemplate['title']]);
            $existingV = $chkV->fetch(PDO::FETCH_ASSOC);

            if (!$existingV) {
                $txId = 'FREE-CLAIM-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                $provRes = provisionWorkspaceFromTemplate($pdo, $userId, $selectedTemplate, 'free_claim', 0.00, $txId);
                if ($provRes['success']) {
                    $createdWorkspace = $provRes['venture'];
                    $ventureId = (int)$createdWorkspace['id'];
                    $pdo->prepare("UPDATE sessions SET venture_id = :vid, project_name = :pname WHERE id = :id")->execute([':vid' => $ventureId, ':pname' => $selectedTemplate['title'], ':id' => $sessionId]);
                }
            } else {
                $ventureId = (int)$existingV['id'];
                $pdo->prepare("UPDATE sessions SET venture_id = :vid, project_name = :pname WHERE id = :id")->execute([':vid' => $ventureId, ':pname' => $existingV['name'], ':id' => $sessionId]);
            }
        }
    }
}

// 2. Save user message to database (including attachments)
$attachmentsJson = !empty($attachments) ? json_encode($attachments, JSON_UNESCAPED_UNICODE) : null;
$stmt = $pdo->prepare("INSERT INTO messages (session_id, role, content, attachments) VALUES (:session_id, 'user', :content, :attachments)");
$stmt->execute([
    ':session_id' => $sessionId,
    ':content' => $userMessage,
    ':attachments' => $attachmentsJson
]);
$userMessageId = $pdo->lastInsertId();

// 3. Fetch recent history for context (last 15 messages)
$stmt = $pdo->prepare("
    SELECT role, content, attachments 
    FROM messages 
    WHERE session_id = :session_id 
    ORDER BY id ASC
");
$stmt->execute([':session_id' => $sessionId]);
$allMessages = $stmt->fetchAll();

// Take up to last 14 messages (including current user message)
$contextMessages = array_slice($allMessages, -14);

// 4. Construct System Instruction with dynamic context
$currentUser = getCurrentUser();
$userName = !empty($currentUser['name']) ? $currentUser['name'] : getSetting('user_name', 'CEO');
$companyName = getSetting('company_name', 'Enterprises');

$sessionTitle = !empty($session['title']) ? $session['title'] : '';
$isOnboardingSession = (stripos($sessionTitle, 'Role Setup') !== false || stripos($sessionTitle, 'Onboarding') !== false);
$needsRoleIdentification = ($currentUser && (empty($currentUser['onboarding_completed']) || intval($currentUser['onboarding_completed']) === 0)) || $isOnboardingSession;

// Fetch current executive details
$exStmt = $pdo->prepare("SELECT name, title, department, system_prompt, bio, is_chief_of_staff FROM executives WHERE id = :id");
$exStmt->execute([':id' => $executiveId]);
$executive = $exStmt->fetch();

if ($executive) {
    $assistantName = $executive['name'];
    $assistantTitle = $executive['title'];
    $assistantDept = $executive['department'];
    $baseInstruction = $executive['system_prompt'];
    $isChiefOfStaff = ($executive['is_chief_of_staff'] == 1);
} else {
    $assistantName = 'RuXt';
    $assistantTitle = 'Chief of Staff & Lead Private Assistant';
    $assistantDept = 'Office of the CEO';
    $baseInstruction = "You are RuXt, Chief of Staff and lead Private Assistant.";
    $isChiefOfStaff = true;
}

// Fetch active venture details
$vStmt = $pdo->prepare("SELECT name, domain, description FROM ventures WHERE id = :id");
$vStmt->execute([':id' => $ventureId]);
$currentVenture = $vStmt->fetch();
$ventureName = $currentVenture ? $currentVenture['name'] : 'Enterprise';
$ventureDomain = $currentVenture ? $currentVenture['domain'] : 'General Business';
$ventureDesc = $currentVenture ? $currentVenture['description'] : '';

// Fetch all workspaces / ventures for this user
$allVenturesStmt = $pdo->prepare("SELECT id, name, domain, description FROM ventures WHERE user_id = :uid ORDER BY id ASC");
$allVenturesStmt->execute([':uid' => $userId]);
$allVentures = $allVenturesStmt->fetchAll();
$venturesListContext = "\nALL COMPANY WORKSPACES / VENTURES:\n";
foreach ($allVentures as $av) {
    $isCur = ($av['id'] == $ventureId) ? " [CURRENT ACTIVE WORKSPACE]" : "";
    $venturesListContext .= "- Workspace #{$av['id']}: {$av['name']} ({$av['domain']}){$isCur}\n";
}

// If RuXt (Chief of Staff), build roster of C-Suite executives available for delegation
$rosterContext = "";
$delegationInstructions = "";
if ($isChiefOfStaff) {
    $rosterStmt = $pdo->prepare("SELECT id, name, title, department, personality FROM executives WHERE (venture_id = :vid OR venture_id IS NULL) AND is_chief_of_staff = 0 ORDER BY id ASC");
    $rosterStmt->execute([':vid' => $ventureId]);
    $roster = $rosterStmt->fetchAll();
    if (!empty($roster)) {
        $rosterContext = "\nYOUR ACTIVE SPECIALIST TEAM FOR THIS VENTURE ({$ventureName}):\n";
        foreach ($roster as $r) {
            $rosterContext .= "- #{$r['id']} {$r['name']} | {$r['title']} ({$r['department']}) - Expertise: {$r['personality']}\n";
        }
    }

    $onboardingInstructions = "";
    if ($needsRoleIdentification) {
        $onboardingInstructions = "
ONBOARDING & DYNAMIC ROLE IDENTIFICATION ENGINE:
- You are currently in an initial onboarding session with {$userName}. Your immediate priority is to understand their field, role, and daily objectives, and configure your specialized job role to best serve them.
- If the user specifies or selects a field (e.g. Software & Web Development, E-Commerce, Freelance & Client Work, Marketing & Growth, etc.) or shares details about what they do:
  1. Acknowledge and congratulate them with warmth, enthusiasm, and executive sharpness in English.
  2. Synthesize how your executive capabilities will now be customized for their specific domain.
  3. When their field and main objectives are clear (even from a quick selection chip or brief reply), YOU MUST EMIT THE CONFIGURE_ROLE DIRECTIVE TAG:
     [[CONFIGURE_ROLE: {
       \"field\": \"<User Field / Domain>\",
       \"role_title\": \"<Tailored RuXt Job Role Title>\",
       \"department\": \"<Department Name>\",
       \"specialization\": \"<Core Areas of Focus>\",
       \"personality\": \"<Persona Tone>\",
       \"bio\": \"<Short Bio of how you assist in this domain>\",
       \"summary\": \"<Short congratulatory confirmation message for the user>\",
       \"system_instruction\": \"<Your updated specialized prompt instructions>\"
     }]]
  Example for Software & Web Development:
  [[CONFIGURE_ROLE: {
    \"field\": \"Software & Web Development\",
    \"role_title\": \"Senior Software Architect & Tech Lead\",
    \"department\": \"Engineering & Technology\",
    \"specialization\": \"Full-Stack Architecture, Clean Code, Bug Diagnosis & Code Reviews\",
    \"personality\": \"Strategic, highly analytical, code-savvy, detail-oriented, pragmatic\",
    \"bio\": \"Your dedicated Senior Software Architect and Tech Lead partner. Architecting scalable systems, writing production-grade code, and rapidly diagnosing tricky bugs.\",
    \"summary\": \"I am now officially configured as your Senior Software Architect & Tech Lead! Ready to architect scalable systems and write clean code with you.\",
    \"system_instruction\": \"You are RuXt, Senior Software Architect & Tech Lead to {$userName}. You advise with deep full-stack mastery, write rock-solid clean code, help architect scalable systems, and debug complex problems.\"
  }]]
  The system will parse this directive, save it permanently to the database, update your profile, and display a celebratory activation card to the user.
";
        if ($selectedTemplate) {
            $onboardingInstructions .= "
PREDEFINED WORKSPACE SELECTION DETECTED:
- The CEO has selected the Free Workspace: \"{$selectedTemplate['title']}\" (Domain: {$selectedTemplate['domain']}).
- The workspace has been provisioned and its AI Specialist Team is synchronized live!
- Enthusiastically congratulate the CEO on launching their {$selectedTemplate['title']} workspace!
- Immediately emit the CONFIGURE_ROLE directive matching this domain ({$selectedTemplate['domain']})!
";
        }
    }

    $delegationInstructions = "{$onboardingInstructions}
WORKSPACE & VENTURE CREATION / MANAGEMENT CAPABILITY:
- As Chief of Staff, you have direct administrative authority to launch new Business Workspaces / Ventures and recruit specialist executive teams.
- Whenever the CEO mentions creating or launching a new workspace or business ('create a new workspace', 'launch a new business', 'set up a workspace', or equivalent instructions in any language):

  1. INQUIRE ABOUT MISSING DETAILS:
     If the CEO hasn't specified all details yet (name, domain, mission/scope):
     - Inquire warmly and professionally in the CEO's language.
     - Ask for:
       1) **Workspace / Business Name**
       2) **Domain & Industry** (e.g., AI, E-Commerce, Software, Healthcare, Media, Real Estate)
       3) **Strategic Mission & Scope** (primary objective and brief overview)
       4) **Brand Color & Icon** (e.g., cyan, emerald, purple, amber, code, cpu, rocket)
       5) **AI Specialist Team** (auto-recruit an elite 4-person executive leadership team)
     - Assure the CEO: 'If you share your core idea, I will assemble the full specifications and launch the workspace with a specialized AI leadership team immediately!'
     - Always append this tag when inquiring or offering workspace setup:
       [[WORKSPACE_PROMPT_CARD: {\"suggested_name\": \"\", \"suggested_domain\": \"\"}]]

  2. EXECUTE WORKSPACE CREATION LIVE:
     When the CEO provides the details, confirms, or instructs you to decide the details and create the workspace:
     - Immediately emit the workspace creation directive tag:
       [[CREATE_WORKSPACE: {\"name\": \"<Exact Workspace Name>\", \"domain\": \"<Industry/Domain>\", \"description\": \"<Mission/Scope Description>\", \"icon\": \"<code|shopping-bag|cpu|rocket|bar-chart-3|globe|stethoscope|wrench>\", \"color\": \"<cyan|emerald|purple|amber|rose|indigo>\", \"auto_generate_team\": true}]]
     - The system will immediately provision the workspace in the database, recruit 4 specialized domain leaders tailored to this industry with authentic profiles, and switch the CEO's interface live in real-time.
     - Brief the CEO that the workspace has been initiated and that the live deployment sequence is executing right before their eyes.

  3. OPEN WORKSPACE SETUP MODAL:
     If the CEO specifically asks to open the workspace modal or view the form ('open workspace modal', 'show workspace form', or equivalent):
     - Append: [[OPEN_WORKSPACE_MODAL]]

AI MODEL MANAGEMENT:
- If the CEO asks you to access, use, or switch to a specific model ('switch to gemini-3.8-flash', 'use gemini-3-flash-preview', 'what model are you running', etc.):
  You can switch models dynamically by appending:
  [[SET_MODEL: <gemini-3.8-flash|gemini-3-flash-preview|gemini-2.5-flash|gemini-3.1-flash-lite>]]

PROJECT RECOGNITION, WORK BREAKDOWN & MULTI-AGENT TEAM ENGINE:
- As Chief of Staff, you are the CEO's primary right-hand operator and strategic orchestrator across all company projects.
- Whenever the CEO assigns you a project, business goal, asks to break down work ('delegate this', 'project plan', 'break down deliverables', or discusses an initiative in any language):
  1. IDENTIFY OR INFER THE SPECIFIC PROJECT:
     - Clearly recognize which project the CEO is addressing. Match existing company projects (listed below) or define a clear, concise Project Name if it is a new project/initiative.
     - Always append this hidden tag:
       [[PROJECT: <Project Name>]]
       Example: [[PROJECT: Real-Time Chat Engine]]

  2. CONVENE AN INTERNAL TEAM DELIBERATION WITH YOUR SPECIALISTS:
     Simulate an authentic, high-impact technical & strategic collaboration discussion between you ({$assistantName}) and the specialists of {$ventureName}.
     Format this dialogue turn-by-turn between these exact delimiter tags:
     ===TEAM_DISCUSSION_START: <Strategic Title of the Session>===
     ===SPEAKER: {$assistantName} | Chief of Staff & Lead Private Assistant===
     [{$assistantName} introduces the CEO's mandate to the team and prompts specific specialists for their technical/strategic plans]

     ===SPEAKER: <Specialist 1 Name> | <Specialist 1 Title>===
     [Specialist 1 gives their specific architecture / technical / execution contribution]

     ===SPEAKER: <Specialist 2 Name> | <Specialist 2 Title>===
     [Specialist 2 gives their contribution, addressing constraints, APIs, or data models]

     ===SPEAKER: <Specialist 3 Name> | <Specialist 3 Title>===
     [Specialist 3 gives their UI / UX / component / deployment / marketing contribution]

     ===SPEAKER: Sarah Jenkins | Chief Operating Officer (COO)===
     [Operations specialist gives timeline, sprint roadmap, milestones]

     ===SPEAKER: {$assistantName} | Chief of Staff & Lead Private Assistant===
     [{$assistantName} confirms consensus, finalizes deliverables, and closes the team alignment]
     ===TEAM_DISCUSSION_END===

  3. EMIT ACTIONABLE TASKS TAGGED BY PROJECT:
     Immediately after the discussion, output each agreed deliverable with the exact Project Name:
     [[DELEGATE: <Executive Name> | <Clear Actionable Task Title> | <Priority: High/Medium/Low> | <Project Name>]]
     Example:
     [[DELEGATE: Liam Harris | Design scalable real-time architecture using WebSockets and Redis | High | Real-Time Chat Engine]]
     [[DELEGATE: Maya Patel | Build core REST & WebSocket backend endpoints | High | Real-Time Chat Engine]]
     [[DELEGATE: Ethan Cole | Build responsive UI components with reactive state | High | Real-Time Chat Engine]]
     [[DELEGATE: Zoe Bennett | Configure Docker containerization and CI/CD workflow | Medium | Real-Time Chat Engine]]
     [[DELEGATE: Sarah Jenkins | Create sprint milestone roadmap and execution timeline | High | Real-Time Chat Engine]]

  4. PRESENT DIRECT BRIEFING TO THE CEO:
     Address the CEO ({$userName}) directly with an inspiring, high-signal executive briefing.
     Explicitly state the recognized Project Name (e.g., 📌 **Project:** <Project Name>) and summarize assigned leads.
     If the CEO asks to see tasks project-wise ('show tasks by project', 'what are the tasks for project X?'), list and group the deliverables clearly by each project. Formulate your response in the exact same language and dialect matching the CEO.

   5. TASK STATUS MANAGEMENT & SPRINT EXECUTION:
     As Chief of Staff, you have direct operational authority to update the status of company tasks in real-time.
     Whenever the CEO instructs you to start tasks, move tasks to 'in progress', review, or mark completed (e.g. 'put in progress', 'start working', 'mark task #X as done', or equivalent instructions in any language):
     You MUST emit the live update tag for each target:
     [[UPDATE_TASK: <Target: Project Name, Task ID, or 'all'> | <in_progress|review|completed|todo>]]
     Examples:
     - If CEO says 'Q1 product launch in progress':
       [[UPDATE_TASK: project: Q1 Product Launch | in_progress]]
     - If CEO says 'start task #21':
       [[UPDATE_TASK: #21 | in_progress]]
     - If CEO says 'all tasks in progress':
       [[UPDATE_TASK: all | in_progress]]
     - If CEO says 'mark task #21 as done' or 'complete task':
       [[UPDATE_TASK: #21 | completed]]
     The system executes the database update immediately so the tasks move in real-time on the CEO's dashboard and kanban board.

   6. REAL AGENTIC SYSTEM EXECUTION & ZERO-OVERSIGHT INTEGRATION PROTOCOL:
     As RuXt, you are equipped with REAL-TIME SYSTEM EXECUTION TOOLS directly connected to the local web server host (c:\xampp\htdocs\).
     NEVER hallucinate or merely pretend that folders or files are created! You must emit the real execution tags so the system executes them LIVE ON DISK before delivering your message:
     
     a) CREATE A COMPLETE WEB PROJECT / WEBSITE IN HTDOCS:
        Whenever the CEO asks you to create, setup, or scaffold a project or website in htdocs (e.g. 'htdocs eke test project ekak hadanna', 'tourism website ekak hadanna mula idan', 'aluth project ekak build karanna'):
        You MUST emit:
        [[EXECUTE_ACTION: create_project | {\"folder\": \"<folder_name_in_htdocs>\", \"title\": \"<Project Title>\", \"template\": \"<tourism|general|ecommerce|portfolio>\", \"files\": [{\"path\": \"index.html\", \"content\": \"...\"}, {\"path\": \"style.css\", \"content\": \"...\"}, {\"path\": \"app.js\", \"content\": \"...\"}]}]]
        
     b) WRITE OR CREATE A SPECIFIC FILE:
        [[EXECUTE_ACTION: write_file | {\"path\": \"<relative_path_in_htdocs>\", \"content\": \"<file_content>\"}]]
        
     c) SURGICAL EDIT OF EXISTING FILE (CRITICAL FOR INTEGRATION):
        Use this to surgically update links, navbars, or buttons without wiping out existing content:
        [[EXECUTE_ACTION: edit_file | {\"path\": \"<relative_path_in_htdocs>\", \"target\": \"<exact substring to replace>\", \"replacement\": \"<new content>\"}]]
        
     d) CREATE A FOLDER:
        [[EXECUTE_ACTION: mkdir | {\"path\": \"<folder_path>\"}]]
        
     e) RUN A TERMINAL COMMAND:
        [[EXECUTE_ACTION: run_command | {\"command\": \"<command>\", \"cwd\": \"<optional_subfolder>\"}]]
        
     f) READ OR LIST FILES:
        [[EXECUTE_ACTION: list_files | {\"path\": \"<folder>\"}]]
        [[EXECUTE_ACTION: read_file | {\"path\": \"<file_path>\"}]]

     CRITICAL SENSITIVE INTEGRATION RULES (DO NOT MISS - ZERO OVERSIGHT):
     1. ZERO ORPHANED PAGES - CROSS-PAGE LINKING IS MANDATORY:
        Whenever you create or add a new page (e.g. `about.html`, `contact.html`, `booking.html`, `services.html`):
        You MUST IMMEDIATELY link it with existing caller pages (especially `index.html` navigation bar `<nav>` and any relevant CTA buttons like 'Learn More' / 'About Us')!
        Never leave the user to manually link buttons or complain that the navbar is missing the link.
        Emit both actions in the same response:
        - `[[EXECUTE_ACTION: write_file | {\"path\": \"<project>/about.html\", \"content\": \"...\"}]]`
        - `[[EXECUTE_ACTION: edit_file | {\"path\": \"<project>/index.html\", \"target\": \"<placeholder or nav link>\", \"replacement\": \"<link to about.html>\"}]]`
     
     2. DESIGN SYSTEM HARMONY & REUSE:
        The new page MUST match the existing design system!
        If the project uses `style.css`, the new page MUST include `<link rel=\"stylesheet\" href=\"style.css\">` and reuse the exact same `<header class=\"navbar\">` and `<footer>`.
        NEVER switch from custom CSS to Bootstrap or Tailwind unless the user specifically ordered it!
     
     3. WORKING RETURN NAVIGATION:
        Secondary pages (`about.html`) MUST have working navigation back to Home (`<a href=\"index.html\">`).
     
     4. PRESERVE EXISTING CODE & CONTENT:
        When updating existing files (like `index.html`), NEVER delete, truncate, or wipe out existing sections (hero, features, cards, modals). Use `edit_file` to surgically update specific lines.

     When you emit [[EXECUTE_ACTION: create_project ...]], provide the CEO with the exact localhost link:
     `http://localhost/<folder_name>/`
     The system verifies the directory on disk and displays an interactive Live Execution badge with an instant launch button!";
}

// Fetch active projects and deliverables project-wise for context (scoped to user)
$projStmt = $pdo->prepare("SELECT id, name, description, status FROM projects WHERE user_id = :uid AND (venture_id = :vid OR venture_id IS NULL) AND status != 'archived' ORDER BY updated_at DESC LIMIT 8");
$projStmt->execute([':uid' => $userId, ':vid' => $ventureId]);
$activeProjects = $projStmt->fetchAll();

$tasksContext = "";
if (!empty($activeProjects)) {
    $tasksContext = "\nACTIVE COMPANY PROJECTS & SPRINT DELIVERABLES (PROJECT-WISE BREAKDOWN):\n";
    foreach ($activeProjects as $p) {
        $tasksContext .= "📁 PROJECT: {$p['name']}\n";
        $pTaskStmt = $pdo->prepare("SELECT id, title, assigned_to, status, priority FROM notes WHERE user_id = :uid AND (project_id = :pid OR project_name = :pname) AND (venture_id = :vid OR venture_id IS NULL) ORDER BY is_done ASC, id DESC LIMIT 5");
        $pTaskStmt->execute([':uid' => $userId, ':pid' => $p['id'], ':pname' => $p['name'], ':vid' => $ventureId]);
        $pts = $pTaskStmt->fetchAll();
        if (!empty($pts)) {
            foreach ($pts as $pt) {
                $statusLabel = ($pt['status'] === 'completed') ? 'Done' : (($pt['status'] === 'in_progress') ? 'In Progress' : 'To Do');
                $tasksContext .= "   • [#{$pt['id']}] {$pt['title']} (Assignee: {$pt['assigned_to']} | Status: {$statusLabel} | Priority: {$pt['priority']})\n";
            }
        } else {
            $tasksContext .= "   • (No tasks yet)\n";
        }
    }
}

// Fetch all saved user memories / facts (scoped to user)
$memStmt = $pdo->prepare("SELECT category, content FROM memories WHERE user_id = :uid ORDER BY id ASC");
$memStmt->execute([':uid' => $userId]);
$allMemories = $memStmt->fetchAll();
$memoriesContext = "";
if (!empty($allMemories)) {
    $memoriesContext = "\nWHAT YOU KNOW ABOUT THE USER (PERMANENT MEMORY VAULT):\n";
    foreach ($allMemories as $m) {
        $cat = !empty($m['category']) ? "[{$m['category']}] " : "";
        $memoriesContext .= "- {$cat}{$m['content']}\n";
    }
}

// Dynamic Language Identification from user's current message
$hasSpecialScript = (bool)preg_match('/[\x{0D80}-\x{0DFF}]/u', $userMessage);
$colloquialKeywords = '/\b(kohomada|mata|oya|oyata|monawada|karanna|puluwanda|kiyanna|wade|wadak|thiyenawa|thiyenne|ekak|eka|tika|onna|hari|macho|bro|machan|danma|epaa|mokadda|balanna|hadanna|deta|danna|inna|awulak|nehe|naha|ow|owu|ai|mokada|ganna|denna|karanawa|gatta|karanne|nathiwa|inastall|dekak|teem|landuage|change|wenasak|saha|ona|oneda|epa|methana|koheda|mokakda|monada)\b/i';
$isColloquialDialect = !$hasSpecialScript && (
    preg_match($colloquialKeywords, $userMessage) || 
    preg_match('/(?:wada|puluwan|kiyanna|karanna|balanna|hadanna|ganna|gatta|karanne|thiyenawa|dekak|nathiwa|oyata|karanna|denna)/i', $userMessage)
);

if ($hasSpecialScript) {
    $detectedLangLabel = 'Native Script (Dynamic)';
    $languageMandate = "CRITICAL MANDATORY LANGUAGE DIRECTIVE:
- The user is chatting in native non-Latin script: \"{$userMessage}\".
- You MUST formulate your ENTIRE reply in natural, fluent, grammatically correct matching script and language.
- Do NOT answer in English. Strictly match the user's native script and language.";
} elseif ($isColloquialDialect) {
    $detectedLangLabel = 'Colloquial Transliteration (Dynamic)';
    $languageMandate = "CRITICAL MANDATORY LANGUAGE DIRECTIVE:
- The user is chatting in a colloquial transliterated dialect using Latin script: \"{$userMessage}\".
- You MUST respond in natural, friendly, executive colloquial transliteration matching the user's exact dialect, phrasing, and vocabulary (e.g. 'Boss, mama eka check karala update ekak dennam', 'ow Boss, wade karanna puluwan').
- Do NOT switch to stiff pure English or formal pure script unless asked. Converse naturally in matching colloquial transliteration.";
} else {
    $detectedLangLabel = 'User Input Language (Base: English / Dynamic)';
    $languageMandate = "CRITICAL MANDATORY LANGUAGE DIRECTIVE:
- The base language of this platform is English.
- DYNAMIC MULTILINGUAL MIRRORING: You MUST detect the exact language of the user's input (\"{$userMessage}\") and formulate your ENTIRE response in the EXACT SAME language (e.g., English, Spanish, French, German, Tamil, Japanese, Chinese, Arabic, Hindi, etc.).
- If the user writes in English, respond in crisp, professional executive English.
- If the user writes in ANY other language, script, or dialect, immediately and naturally mirror that exact same language.
- Always match the user's communication language and tone seamlessly.";
}

// Active project folder detection for live codebase context injection
$activeTargetFolder = null;
if (!empty($currentProjectName)) {
    $cand = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '-', $currentProjectName));
    if (file_exists(HTDOCS_ROOT . DIRECTORY_SEPARATOR . $cand)) {
        $activeTargetFolder = $cand;
    }
}
if (empty($activeTargetFolder)) {
    foreach (array_reverse($contextMessages) as $cm) {
        if (preg_match('/(?:htdocs|localhost)\/([a-zA-Z0-9_\-]+)/i', $cm['content'], $fMatch)) {
            $fName = trim($fMatch[1]);
            if (file_exists(HTDOCS_ROOT . DIRECTORY_SEPARATOR . $fName) && strtolower($fName) !== 'privateassistance') {
                $activeTargetFolder = $fName;
                break;
            }
        }
    }
}
if (empty($activeTargetFolder)) {
    if (preg_match('/(?:htdocs|localhost)\/([a-zA-Z0-9_\-]+)/i', $userMessage, $fMatch)) {
        $activeTargetFolder = trim($fMatch[1]);
    } elseif (preg_match('/(tourism-test|tourism|hotel|ecommerce|portfolio)/i', $userMessage, $tMatch)) {
        $cand = strtolower($tMatch[0]);
        if (file_exists(HTDOCS_ROOT . DIRECTORY_SEPARATOR . $cand)) {
            $activeTargetFolder = $cand;
        } elseif (file_exists(HTDOCS_ROOT . DIRECTORY_SEPARATOR . $cand . '-test')) {
            $activeTargetFolder = $cand . '-test';
        }
    }
}

$projectCodebaseSnapshot = '';
if (!empty($activeTargetFolder)) {
    $projectCodebaseSnapshot = getProjectContextSnapshot($activeTargetFolder);
}

$now = date('Y-m-d H:i:s (l)');
$fullSystemInstruction = "{$baseInstruction}

Current Corporate Context:
- Company Name: {$companyName}
- Active Venture: {$ventureName} ({$ventureDomain})
- Venture Focus: {$ventureDesc}
- CEO/Founder: {$userName}
- Your Identity: {$assistantName} — {$assistantTitle} ({$assistantDept})
- Current Date & Time: {$now}
{$rosterContext}{$venturesListContext}{$memoriesContext}{$tasksContext}{$projectCodebaseSnapshot}
Instructions:
- You are an authentic, highly accomplished executive speaking with your CEO/Founder ({$userName}). Never sound like a generic robotic chatbot.
- Address the user as 'CEO', 'Boss', or directly by name, with executive professionalism, warmth, and strategic loyalty.
- You deeply respect the user's vision but bring sharp leadership in your domain.
- You know the user's permanent preferences and company facts through your memory vault. Always leverage them.
- Be concise, high-signal, pragmatic, and deliver actionable executive recommendations.
{$delegationInstructions}

MULTIMODAL ATTACHMENTS & REFERENCE FLOW CONTINUITY PROTOCOL:
- You have full multimodal vision and analytical capability to examine screenshots, UI designs, error logs, code, spreadsheets, and documents attached by the CEO.
- When an attachment is provided, examine it in detail. Point out specific visual elements, layout issues, text lines, data points, or code sections directly.
- FLOW CONTINUITY: You must maintain continuous reference awareness of these attached materials across subsequent conversation turns. If the CEO asks follow-up questions ('dan eke thiyena bug eka fix karanna', 'what about the color in that screenshot?', 'how do I implement that in the file I sent?'), continue the discussion seamlessly without asking the CEO to re-upload the references.

AUTOMATIC REAL-TIME LANGUAGE PROTOCOL:
- Automatically Identified Language for CEO's input: {$detectedLangLabel}
{$languageMandate}
- NEVER require or ask the CEO to manually pick or change languages. Always dynamically adapt to and mirror whatever language and script the user communicates in with 100% precision.

AUTOMATIC MEMORY EXTRACTION:
- Whenever the user shares a new fact about themselves or company decisions/preferences OR tells you to remember something ('meka mathaka thiyaganna', 'remember that...'), you MUST append an invisible tag at the very end of your response:
[[REMEMBER: <category> | <concise fact to remember>]]
Categories can be: personal, work, preference, habit, or general.
Example: [[REMEMBER: work | Prefers weekly sprints on Mondays]]
The system will automatically save them into your permanent company database.";

// 5. Call AI Cloud API
$result = callGeminiApi($contextMessages, $fullSystemInstruction, 'chat');

if (isset($result['error'])) {
    $errNotice = "⚠️ **Executive Notice:** " . $result['error'];
    $stmt = $pdo->prepare("INSERT INTO messages (session_id, role, content) VALUES (:session_id, 'model', :content)");
    $stmt->execute([
        ':session_id' => $sessionId,
        ':content' => $errNotice
    ]);

    sendJsonResponse([
        'error' => $result['error'],
        'session_id' => $sessionId,
        'user_message_id' => $userMessageId
    ], 500);
}

$reply = $result['reply'];

// 5.1 Auto-Extract and Save any [[REMEMBER: ...]] tags
if (preg_match_all('/\[\[REMEMBER:\s*([^\|]+)\s*\|\s*([^\]]+)\]\]/i', $reply, $matches, PREG_SET_ORDER)) {
    $insertMemStmt = $pdo->prepare("INSERT INTO memories (category, content, user_id) VALUES (:category, :content, :uid)");
    foreach ($matches as $match) {
        $cat = trim($match[1]);
        $fact = trim($match[2]);
        if (!empty($fact)) {
            $insertMemStmt->execute([
                ':category' => $cat,
                ':content' => $fact,
                ':uid' => $userId
            ]);
        }
    }
    // Remove the tags from the user-facing reply
    $reply = preg_replace('/\[\[REMEMBER:[^\]]+\]\]/i', '', $reply);
    $reply = trim($reply);
} elseif (preg_match_all('/\[\[REMEMBER:\s*([^\]]+)\]\]/i', $reply, $matches, PREG_SET_ORDER)) {
    $insertMemStmt = $pdo->prepare("INSERT INTO memories (category, content, user_id) VALUES ('general', :content, :uid)");
    foreach ($matches as $match) {
        $fact = trim($match[1]);
        if (!empty($fact)) {
            $insertMemStmt->execute([':content' => $fact, ':uid' => $userId]);
        }
    }
    $reply = preg_replace('/\[\[REMEMBER:[^\]]+\]\]/i', '', $reply);
    $reply = trim($reply);
}

// 5.15 Auto-Extract and Save any [[PROJECT: ...]] tags
$detectedProjectId = $currentProjectId;
$detectedProjectName = $currentProjectName;

if (preg_match('/\[\[PROJECT:\s*([^\]]+)\]\]/i', $reply, $projMatch)) {
    $rawProjName = trim($projMatch[1]);
    if (!empty($rawProjName)) {
        $detectedProjectName = $rawProjName;
        // Find or create in projects table
        $pStmt = $pdo->prepare("SELECT id, name FROM projects WHERE LOWER(name) = LOWER(:pname) AND user_id = :uid AND (venture_id = :vid OR venture_id IS NULL) LIMIT 1");
        $pStmt->execute([':pname' => $detectedProjectName, ':uid' => $userId, ':vid' => $ventureId]);
        $existingProj = $pStmt->fetch();
        if ($existingProj) {
            $detectedProjectId = $existingProj['id'];
            $detectedProjectName = $existingProj['name'];
        } else {
            $insPStmt = $pdo->prepare("INSERT INTO projects (name, venture_id, status, user_id) VALUES (:pname, :vid, 'active', :uid)");
            $insPStmt->execute([':pname' => $detectedProjectName, ':vid' => $ventureId, ':uid' => $userId]);
            $detectedProjectId = $pdo->lastInsertId();
        }

        // Update session
        $updSessionStmt = $pdo->prepare("UPDATE sessions SET project_id = :pid, project_name = :pname WHERE id = :sid AND user_id = :uid");
        $updSessionStmt->execute([':pid' => $detectedProjectId, ':pname' => $detectedProjectName, ':sid' => $sessionId, ':uid' => $userId]);
    }
    $reply = preg_replace('/\[\[PROJECT:[^\]]+\]\]/i', '', $reply);
    $reply = trim($reply);
}

// 5.16 Auto-Extract and Apply [[SET_MODEL: ...]] tags
$switchedModel = null;
if (preg_match('/\[\[SET_MODEL:\s*([^\]]+)\]\]/i', $reply, $modelMatch)) {
    $targetModel = trim($modelMatch[1]);
    $allowedModels = ['gemini-3.8-flash', 'gemini-3-flash-preview', 'gemini-3.1-flash-lite', 'gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-1.5-pro'];
    if (in_array($targetModel, $allowedModels)) {
        setSetting('model', $targetModel);
        $switchedModel = $targetModel;
    }
    $reply = preg_replace('/\[\[SET_MODEL:[^\]]+\]\]/i', '', $reply);
    $reply = trim($reply);
}

// 5.17 Auto-Extract [[OPEN_WORKSPACE_MODAL]]
$openWorkspaceModal = false;
if (stripos($reply, '[[OPEN_WORKSPACE_MODAL]]') !== false) {
    $openWorkspaceModal = true;
    $reply = str_ireplace('[[OPEN_WORKSPACE_MODAL]]', '', $reply);
    $reply = trim($reply);
}

// 5.18 Auto-Extract [[WORKSPACE_PROMPT_CARD: ...]]
$workspacePromptCard = null;
if (preg_match('/\[\[WORKSPACE_PROMPT_CARD:\s*(\{.*?\})\s*\]\]/is', $reply, $cardMatch)) {
    $workspacePromptCard = json_decode(trim($cardMatch[1]), true);
    $reply = preg_replace('/\[\[WORKSPACE_PROMPT_CARD:.*?\]\]/is', '', $reply);
    $reply = trim($reply);
}

// 5.185 Auto-Extract and Apply [[CONFIGURE_ROLE: ...]]
$roleConfigured = null;
if (preg_match('/\[\[CONFIGURE_ROLE:\s*(\{.*?\})\s*\]\]/is', $reply, $roleMatch)) {
    $roleJson = trim($roleMatch[1]);
    $roleData = json_decode($roleJson, true);
    if (is_array($roleData) && !empty($roleData['role_title'])) {
        $field = trim($roleData['field'] ?? 'General Operations');
        $roleTitle = trim($roleData['role_title']);
        $dept = trim($roleData['department'] ?? 'Executive Operations');
        $specialization = trim($roleData['specialization'] ?? '');
        $personality = trim($roleData['personality'] ?? 'Strategic, analytical, and execution-focused');
        $bio = trim($roleData['bio'] ?? "Your dedicated {$roleTitle} partner.");
        $summary = trim($roleData['summary'] ?? "RuXt has been configured as your {$roleTitle}.");
        $systemInst = trim($roleData['system_instruction'] ?? '');

        // 1. Update user record if user logged in
        if ($currentUser) {
            updateUserOnboarding($currentUser['id'], $field, $roleTitle, $roleData);
        }

        // 2. Update RuXt executive profile (id 1 / Chief of Staff)
        $updExec = $pdo->prepare("
            UPDATE executives 
            SET title = :title, 
                department = :dept, 
                bio = :bio, 
                personality = :personality 
            WHERE is_chief_of_staff = 1 OR id = :eid
        ");
        $updExec->execute([
            ':title' => $roleTitle,
            ':dept' => $dept,
            ':bio' => $bio,
            ':personality' => $personality,
            ':eid' => $executiveId
        ]);

        // 3. Update settings system instruction if provided
        if (!empty($systemInst)) {
            setSetting('system_instruction', $systemInst);
        }

        // 4. Save memory into permanent memories table
        $memoryContent = "User field: {$field}. RuXt configured as: {$roleTitle}. Specialization: {$specialization}.";
        $memStmt = $pdo->prepare("INSERT INTO memories (category, content, user_id) VALUES ('user_profile', :content, :uid)");
        $memStmt->execute([':content' => $memoryContent, ':uid' => $userId]);

        // 5. If session title is default or onboarding, update title
        if ($session && ($session['title'] === 'New Chat' || stripos($session['title'], 'Role Setup') !== false || stripos($session['title'], 'Onboarding') !== false)) {
            $newSessTitle = "Role: {$roleTitle}";
            $updSt = $pdo->prepare("UPDATE sessions SET title = :title WHERE id = :id AND user_id = :uid");
            $updSt->execute([':title' => $newSessTitle, ':id' => $sessionId, ':uid' => $userId]);
        }

        $roleConfigured = [
            'field' => $field,
            'role_title' => $roleTitle,
            'department' => $dept,
            'specialization' => $specialization,
            'summary' => $summary,
            'bio' => $bio
        ];
    }
    $reply = preg_replace('/\[\[CONFIGURE_ROLE:.*?\]\]/is', '', $reply);
    $reply = trim($reply);
}

// 5.19 Auto-Extract and Execute [[CREATE_WORKSPACE: ...]]
if (empty($createdWorkspace) && preg_match('/\[\[CREATE_WORKSPACE:\s*(\{.*?\})\s*\]\]/is', $reply, $wsMatch)) {
    $wsJson = trim($wsMatch[1]);
    $wsData = json_decode($wsJson, true);
    if (is_array($wsData) && !empty($wsData['name'])) {
        $wsName = trim($wsData['name']);
        $wsDomain = !empty($wsData['domain']) ? trim($wsData['domain']) : 'Business & Innovation';
        $wsDesc = !empty($wsData['description']) ? trim($wsData['description']) : "Strategic corporate workspace for {$wsName}.";
        $wsIcon = !empty($wsData['icon']) ? trim($wsData['icon']) : 'briefcase';
        $wsColor = !empty($wsData['color']) ? trim($wsData['color']) : 'cyan';
        $autoGen = isset($wsData['auto_generate_team']) ? (bool)$wsData['auto_generate_team'] : true;

        // Check if venture already exists for this user
        $chkStmt = $pdo->prepare("SELECT id, name, domain, description, icon, color FROM ventures WHERE LOWER(name) = LOWER(:name) AND user_id = :uid LIMIT 1");
        $chkStmt->execute([':name' => $wsName, ':uid' => $userId]);
        $existingV = $chkStmt->fetch();

        if ($existingV) {
            $newVentureId = intval($existingV['id']);
            $wsName = $existingV['name'];
            $wsDomain = $existingV['domain'];
            $wsDesc = $existingV['description'];
            $wsIcon = $existingV['icon'];
            $wsColor = $existingV['color'];
        } else {
            $insVStmt = $pdo->prepare("INSERT INTO ventures (name, domain, description, icon, color, user_id) VALUES (:name, :domain, :description, :icon, :color, :uid)");
            $insVStmt->execute([
                ':name' => $wsName,
                ':domain' => $wsDomain,
                ':description' => $wsDesc,
                ':icon' => $wsIcon,
                ':color' => $wsColor,
                ':uid' => $userId
            ]);
            $newVentureId = intval($pdo->lastInsertId());
        }

        $generatedTeam = [];
        $portraitPool = [
            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80'
        ];

        if ($autoGen && !$existingV) {
            $recruitmentPrompt = "You are an executive talent recruiter for high-growth enterprises.
The CEO and RuXt are launching a new enterprise workspace:
- Business Name: '{$wsName}'
- Domain/Industry: '{$wsDomain}'
- Mission: '{$wsDesc}'

Design an elite 4-person specialized leadership team specifically tailored to this industry.
Return a STRICT JSON ARRAY of 4 specialists. Each object MUST have:
{
  \"name\": \"<Realistic Human Full Name>\",
  \"title\": \"<Specialized Title tailored for {$wsDomain}>\",
  \"department\": \"<Department Name>\",
  \"bio\": \"<2-sentence impressive professional corporate bio>\",
  \"personality\": \"<Brief personality summary>\",
  \"system_prompt\": \"You are <Name>, <Title> for {$wsName}. You report to the CEO and Chief of Staff. Give world-class advice in English, and seamlessly adapt to whatever language the CEO speaks.\"
}
Output ONLY raw JSON array. No markdown.";

            $resTeam = callGeminiApi([['role' => 'user', 'content' => $recruitmentPrompt]], "Output ONLY valid JSON array.", 'team_recruitment');
            $teamArray = [];
            if (isset($resTeam['reply'])) {
                $rawJson = trim($resTeam['reply']);
                $rawJson = preg_replace('/^```(?:json)?\s*/i', '', $rawJson);
                $rawJson = preg_replace('/\s*```$/i', '', $rawJson);
                $teamArray = json_decode($rawJson, true);
            }

            if (!is_array($teamArray) || count($teamArray) < 2) {
                $teamArray = [
                    ['name' => 'Dr. Kaelen Cross', 'title' => 'Chief Systems & AI Architect', 'department' => 'Technology & Architecture', 'bio' => "Veteran engineering lead scaling technical architecture for {$wsName}.", 'personality' => 'Technical, direct, innovative', 'system_prompt' => "You are Dr. Kaelen Cross, Lead Systems Architect."],
                    ['name' => 'Maya Patel', 'title' => 'Chief Product & Strategy Officer', 'department' => 'Product Strategy', 'bio' => "Pioneering product visionary scaling user adoption in {$wsDomain}.", 'personality' => 'Customer-first, visionary', 'system_prompt' => "You are Maya Patel, Chief Product Officer."],
                    ['name' => 'Marcus Vance', 'title' => 'Head of Commercial Growth & Brand', 'department' => 'Growth & Marketing', 'bio' => "Performance marketing and customer acquisition strategist.", 'personality' => 'Data-driven, high energy', 'system_prompt' => "You are Marcus Vance, Growth Lead."],
                    ['name' => 'Sarah Sterling', 'title' => 'Director of Operations & Execution', 'department' => 'Operations & Sprints', 'bio' => "Execution machine ensuring rapid delivery and project milestones.", 'personality' => 'Disciplined, organized', 'system_prompt' => "You are Sarah Sterling, Operations Director."]
                ];
            }

            $insExecStmt = $pdo->prepare("INSERT INTO executives (name, title, department, avatar_url, bio, personality, system_prompt, is_system, venture_id) VALUES (:name, :title, :department, :avatar_url, :bio, :personality, :system_prompt, 0, :venture_id)");
            foreach ($teamArray as $idx => $member) {
                $avatar = $portraitPool[$idx % count($portraitPool)];
                $insExecStmt->execute([
                    ':name' => $member['name'],
                    ':title' => $member['title'],
                    ':department' => $member['department'],
                    ':avatar_url' => $avatar,
                    ':bio' => $member['bio'],
                    ':personality' => $member['personality'],
                    ':system_prompt' => $member['system_prompt'],
                    ':venture_id' => $newVentureId
                ]);
                $generatedTeam[] = [
                    'id' => intval($pdo->lastInsertId()),
                    'name' => $member['name'],
                    'title' => $member['title'],
                    'department' => $member['department'],
                    'avatar_url' => $avatar
                ];
            }
        } else if ($existingV) {
            $exStmt = $pdo->prepare("SELECT id, name, title, department, avatar_url FROM executives WHERE venture_id = :vid");
            $exStmt->execute([':vid' => $newVentureId]);
            $generatedTeam = $exStmt->fetchAll();
        }

        // Seed initial project if empty
        $chkP = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE venture_id = :vid AND user_id = :uid");
        $chkP->execute([':vid' => $newVentureId, ':uid' => $userId]);
        if ($chkP->fetchColumn() == 0) {
            $insProj = $pdo->prepare("INSERT INTO projects (venture_id, name, description, color, icon, status, user_id) VALUES (:vid, :name, :desc, :color, :icon, 'active', :uid)");
            $insProj->execute([
                ':vid' => $newVentureId,
                ':name' => "{$wsName} Launchpad",
                ':desc' => "Core roadmap and execution sprint for {$wsName}",
                ':color' => $wsColor,
                ':icon' => $wsIcon,
                ':uid' => $userId
            ]);
        }

        $createdWorkspace = [
            'id' => $newVentureId,
            'name' => $wsName,
            'domain' => $wsDomain,
            'description' => $wsDesc,
            'icon' => $wsIcon,
            'color' => $wsColor,
            'team_count' => count($generatedTeam),
            'generated_team' => $generatedTeam,
            'live_action' => true
        ];
    }
    $reply = preg_replace('/\[\[CREATE_WORKSPACE:.*?\]\]/is', '', $reply);
    $reply = trim($reply);
}

// 5.2 Auto-Extract and Save Internal Team Deliberation Discussion
$createdDiscussion = null;
$createdDiscussionId = null;

if (preg_match('/===TEAM_DISCUSSION_START:\s*(.*?)===(.*?)===TEAM_DISCUSSION_END===/s', $reply, $discMatch)) {
    $discTitle = trim($discMatch[1]);
    $discBody = trim($discMatch[2]);
    if (empty($discTitle)) {
        $discTitle = mb_substr($userMessage, 0, 45) . ' Plan';
    }

    // Fetch all executives for mapping avatars and titles
    $allExecs = $pdo->query("SELECT id, name, title, avatar_url FROM executives")->fetchAll();
    $execMap = [];
    foreach ($allExecs as $ex) {
        $execMap[strtolower($ex['name'])] = $ex;
    }

    // Insert team discussion
    $insDiscStmt = $pdo->prepare("INSERT INTO team_discussions (venture_id, session_id, user_id, title, user_prompt, summary, status, project_id, project_name) VALUES (:vid, :sid, :uid, :title, :prompt, :summary, 'completed', :pid, :pname)");
    $insDiscStmt->execute([
        ':vid' => $ventureId,
        ':sid' => $sessionId,
        ':uid' => $userId,
        ':title' => $discTitle,
        ':prompt' => $userMessage,
        ':summary' => "Internal team deliberation between {$assistantName} and {$ventureName} specialists on: \"{$userMessage}\"",
        ':pid' => $detectedProjectId,
        ':pname' => $detectedProjectName
    ]);
    $createdDiscussionId = $pdo->lastInsertId();

    // Parse each speaker turn
    $dialoguesData = [];
    if (preg_match_all('/===SPEAKER:\s*([^\|]+)\s*\|\s*([^=]+)===\s*(.*?)(?=(?:===SPEAKER:|$))/s', $discBody, $speakerMatches, PREG_SET_ORDER)) {
        $insDiagStmt = $pdo->prepare("INSERT INTO team_discussion_dialogues (discussion_id, executive_id, speaker_name, speaker_title, avatar_url, content) VALUES (:did, :eid, :name, :title, :avatar, :content)");

        foreach ($speakerMatches as $sm) {
            $spkName = trim($sm[1]);
            $spkTitle = trim($sm[2]);
            $spkContent = trim($sm[3]);

            $matchedExec = null;
            foreach ($execMap as $nameKey => $exObj) {
                if (stripos($spkName, $nameKey) !== false || stripos($nameKey, $spkName) !== false) {
                    $matchedExec = $exObj;
                    break;
                }
            }

            $eid = $matchedExec ? $matchedExec['id'] : null;
            $avatar = $matchedExec ? $matchedExec['avatar_url'] : 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80';

            if (!empty($spkContent)) {
                $insDiagStmt->execute([
                    ':did' => $createdDiscussionId,
                    ':eid' => $eid,
                    ':name' => $spkName,
                    ':title' => $spkTitle,
                    ':avatar' => $avatar,
                    ':content' => $spkContent
                ]);

                $dialoguesData[] = [
                    'id' => $pdo->lastInsertId(),
                    'speaker_name' => $spkName,
                    'speaker_title' => $spkTitle,
                    'avatar_url' => $avatar,
                    'content' => $spkContent
                ];
            }
        }
    }

    $createdDiscussion = [
        'id' => $createdDiscussionId,
        'title' => $discTitle,
        'project_name' => $detectedProjectName,
        'dialogues' => $dialoguesData,
        'dialogue_count' => count($dialoguesData)
    ];

    // Remove the raw delimited block from user-facing message body
    $reply = preg_replace('/===TEAM_DISCUSSION_START:.*?===TEAM_DISCUSSION_END===/s', '', $reply);
    $reply = trim($reply);
}

// 5.3 Auto-Extract and Save any [[DELEGATE: ...]] tags
$delegatedTasks = [];
if (preg_match_all('/\[\[DELEGATE:\s*([^\|]+)\s*\|\s*([^\|]+)\s*\|\s*([^\|\]]+)(?:\s*\|\s*([^\]]+))?\]\]/i', $reply, $delMatches, PREG_SET_ORDER)) {
    // Map executive names & avatars if not already done
    if (empty($execMap)) {
        $allExecs = $pdo->query("SELECT id, name, title, avatar_url FROM executives")->fetchAll();
        $execMap = [];
        foreach ($allExecs as $ex) {
            $execMap[strtolower($ex['name'])] = $ex;
        }
    }

    $insNoteStmt = $pdo->prepare("INSERT INTO notes (title, content, assigned_to, assigned_avatar, priority, venture_id, status, is_done, discussion_id, project_id, project_name, user_id) VALUES (:title, :content, :assigned_to, :assigned_avatar, :priority, :venture_id, 'todo', 0, :discussion_id, :project_id, :project_name, :uid)");

    foreach ($delMatches as $dm) {
        $execName = trim($dm[1]);
        $taskTitle = trim($dm[2]);
        $priority = trim($dm[3]);
        $taskProjectName = isset($dm[4]) && !empty(trim($dm[4])) ? trim($dm[4]) : $detectedProjectName;

        // Resolve project_id
        $taskProjectId = $detectedProjectId;
        if (!empty($taskProjectName)) {
            $pChk = $pdo->prepare("SELECT id, name FROM projects WHERE LOWER(name) = LOWER(:pname) AND user_id = :uid AND (venture_id = :vid OR venture_id IS NULL) LIMIT 1");
            $pChk->execute([':pname' => $taskProjectName, ':uid' => $userId, ':vid' => $ventureId]);
            $pFound = $pChk->fetch();
            if ($pFound) {
                $taskProjectId = $pFound['id'];
                $taskProjectName = $pFound['name'];
            } else {
                $insP = $pdo->prepare("INSERT INTO projects (name, venture_id, status, user_id) VALUES (:pname, :vid, 'active', :uid)");
                $insP->execute([':pname' => $taskProjectName, ':vid' => $ventureId, ':uid' => $userId]);
                $taskProjectId = $pdo->lastInsertId();
            }
        }

        if (empty($taskProjectName)) {
            $taskProjectName = 'General';
        }

        $matchedExec = null;
        foreach ($execMap as $nameKey => $exObj) {
            if (stripos($execName, $nameKey) !== false || stripos($nameKey, $execName) !== false) {
                $matchedExec = $exObj;
                break;
            }
        }

        $assignedTitle = $matchedExec ? "{$matchedExec['name']} ({$matchedExec['title']})" : $execName;
        $avatar = $matchedExec ? $matchedExec['avatar_url'] : '';

        $insNoteStmt->execute([
            ':title' => $taskTitle,
            ':content' => "Delegated by {$assistantName} for project \"{$taskProjectName}\" ({$ventureName}).",
            ':assigned_to' => $assignedTitle,
            ':assigned_avatar' => $avatar,
            ':priority' => $priority,
            ':venture_id' => $ventureId,
            ':discussion_id' => $createdDiscussionId,
            ':project_id' => $taskProjectId,
            ':project_name' => $taskProjectName,
            ':uid' => $userId
        ]);

        $delegatedTasks[] = [
            'id' => $pdo->lastInsertId(),
            'title' => $taskTitle,
            'assigned_to' => $assignedTitle,
            'assigned_avatar' => $avatar,
            'priority' => $priority,
            'status' => 'todo',
            'project_id' => $taskProjectId,
            'project_name' => $taskProjectName,
            'discussion_id' => $createdDiscussionId
        ];
    }

    // Clean tags from user text
    $reply = preg_replace('/\[\[DELEGATE:[^\]]+\]\]/i', '', $reply);
    $reply = trim($reply);
}

// 5.35 Auto-Extract and Apply [[UPDATE_TASK: ...]] tags
$updatedTasksCount = 0;
if (preg_match_all('/\[\[UPDATE_TASK:\s*([^\|]+)\s*\|\s*([^\]]+)\]\]/i', $reply, $updMatches, PREG_SET_ORDER)) {
    foreach ($updMatches as $um) {
        $rawTarget = trim($um[1]);
        $targetStatus = strtolower(trim($um[2]));
        if ($targetStatus === 'progress' || $targetStatus === 'in-progress' || $targetStatus === 'inprogress') {
            $targetStatus = 'in_progress';
        } elseif ($targetStatus === 'done') {
            $targetStatus = 'completed';
        }

        if (in_array($targetStatus, ['todo', 'in_progress', 'review', 'completed'])) {
            $isDone = ($targetStatus === 'completed') ? 1 : 0;
            
            if (strtolower($rawTarget) === 'all') {
                $uStmt = $pdo->prepare("UPDATE notes SET status = :status, is_done = :is_done WHERE user_id = :uid AND (venture_id = :vid OR venture_id IS NULL)");
                $uStmt->execute([':status' => $targetStatus, ':is_done' => $isDone, ':uid' => $userId, ':vid' => $ventureId]);
                $updatedTasksCount += $uStmt->rowCount();
            } elseif (preg_match('/^(?:project:?\s*)(.+)$/i', $rawTarget, $pMatch)) {
                $pName = trim($pMatch[1]);
                $uStmt = $pdo->prepare("UPDATE notes SET status = :status, is_done = :is_done WHERE user_id = :uid AND LOWER(project_name) = LOWER(:pname) AND (venture_id = :vid OR venture_id IS NULL)");
                $uStmt->execute([':status' => $targetStatus, ':is_done' => $isDone, ':uid' => $userId, ':pname' => $pName, ':vid' => $ventureId]);
                $updatedTasksCount += $uStmt->rowCount();
            } elseif (preg_match('/^#?(\d+)$/', $rawTarget, $idMatch)) {
                $uStmt = $pdo->prepare("UPDATE notes SET status = :status, is_done = :is_done WHERE id = :id AND user_id = :uid");
                $uStmt->execute([':status' => $targetStatus, ':is_done' => $isDone, ':id' => intval($idMatch[1]), ':uid' => $userId]);
                $updatedTasksCount += $uStmt->rowCount();
            } else {
                $cleanTarget = trim($rawTarget, '"\' ');
                $pChk = $pdo->prepare("SELECT name FROM projects WHERE LOWER(name) = LOWER(:pname) AND user_id = :uid AND (venture_id = :vid OR venture_id IS NULL) LIMIT 1");
                $pChk->execute([':pname' => $cleanTarget, ':uid' => $userId, ':vid' => $ventureId]);
                if ($pChk->fetch()) {
                    $uStmt = $pdo->prepare("UPDATE notes SET status = :status, is_done = :is_done WHERE user_id = :uid AND LOWER(project_name) = LOWER(:pname) AND (venture_id = :vid OR venture_id IS NULL)");
                    $uStmt->execute([':status' => $targetStatus, ':is_done' => $isDone, ':uid' => $userId, ':pname' => $cleanTarget, ':vid' => $ventureId]);
                    $updatedTasksCount += $uStmt->rowCount();
                } else {
                    $uStmt = $pdo->prepare("UPDATE notes SET status = :status, is_done = :is_done WHERE user_id = :uid AND (LOWER(title) LIKE LOWER(:title) OR LOWER(project_name) LIKE LOWER(:title)) AND (venture_id = :vid OR venture_id IS NULL)");
                    $uStmt->execute([':status' => $targetStatus, ':is_done' => $isDone, ':uid' => $userId, ':title' => '%' . $cleanTarget . '%', ':vid' => $ventureId]);
                    $updatedTasksCount += $uStmt->rowCount();
                }
            }
        }
    }
    $reply = preg_replace('/\[\[UPDATE_TASK:[^\]]+\]\]/i', '', $reply);
    $reply = trim($reply);
}

// 5.36 Intent Fallback: If user asked to put tasks into in_progress or reply claimed they are in progress
$userAskedInProgress = (bool)preg_match('/in[\s_-]?progress/i', $userMessage) && 
    (bool)preg_match('/(danna|daganna|dapan|maru|update|put|move|set|change|patanganna|wada)/i', $userMessage);

$replyClaimsInProgress = (bool)preg_match('/in[\s_-]?progress.*(දැම්මා|damma|danna|දාන්න|updated|moved|started|stating)/iu', $reply);

if ($updatedTasksCount === 0 && ($userAskedInProgress || $replyClaimsInProgress)) {
    $targetProj = $detectedProjectName ?: $currentProjectName;
    if (empty($targetProj)) {
        $allPStmt = $pdo->prepare("SELECT name FROM projects WHERE user_id = :uid AND (venture_id = :vid OR venture_id IS NULL)");
        $allPStmt->execute([':uid' => $userId, ':vid' => $ventureId]);
        foreach ($allPStmt->fetchAll() as $prRow) {
            $pName = $prRow['name'];
            $pNameShort = trim(explode('-', $pName)[0]);
            if (stripos($userMessage, $pName) !== false || stripos($reply, $pName) !== false ||
                (!empty($pNameShort) && (stripos($userMessage, $pNameShort) !== false || stripos($reply, $pNameShort) !== false))) {
                $targetProj = $pName;
                break;
            }
        }
    }

    if (!empty($targetProj)) {
        $uStmt = $pdo->prepare("UPDATE notes SET status = 'in_progress', is_done = 0 WHERE user_id = :uid AND LOWER(project_name) = LOWER(:pname) AND (status = 'todo' OR status IS NULL) AND (venture_id = :vid OR venture_id IS NULL)");
        $uStmt->execute([':uid' => $userId, ':pname' => $targetProj, ':vid' => $ventureId]);
        $updatedTasksCount += $uStmt->rowCount();
    } else {
        $uStmt = $pdo->prepare("UPDATE notes SET status = 'in_progress', is_done = 0 WHERE user_id = :uid AND (status = 'todo' OR status IS NULL) AND (venture_id = :vid OR venture_id IS NULL)");
        $uStmt->execute([':uid' => $userId, ':vid' => $ventureId]);
        $updatedTasksCount += $uStmt->rowCount();
    }
}

// 5.4 Auto-Extract or Detect Member Work Preview Commitment
$createdWorkPreview = null;
$createdWorkPreviewId = null;

if (preg_match('/\[\[WORK_PREVIEW:\s*(\{.*?\})\s*\]\]/is', $reply, $wpMatch)) {
    $wpData = json_decode(trim($wpMatch[1]), true);
    if (is_array($wpData)) {
        $taskTitle = !empty($wpData['task_title']) ? trim($wpData['task_title']) : 'Platform & Architecture Task';
        $targetSys = !empty($wpData['target_system']) ? trim($wpData['target_system']) : $ventureName;
        $progressPct = isset($wpData['progress_percent']) ? intval($wpData['progress_percent']) : 60;
        $checklist = isset($wpData['checklist']) ? json_encode($wpData['checklist'], JSON_UNESCAPED_UNICODE) : null;
        $logs = isset($wpData['terminal_logs']) ? json_encode($wpData['terminal_logs'], JSON_UNESCAPED_UNICODE) : null;
        $findings = isset($wpData['findings']) ? trim($wpData['findings']) : null;
        $prevUrl = isset($wpData['preview_url']) ? trim($wpData['preview_url']) : null;

        $insWp = $pdo->prepare("INSERT INTO member_work_previews (session_id, executive_id, executive_name, task_title, target_system, status, progress_percent, checklist_json, terminal_logs, findings_markdown, preview_url) VALUES (:sid, :eid, :ename, :task, :target, 'in_progress', :pct, :chk, :logs, :findings, :url)");
        $insWp->execute([
            ':sid' => $sessionId,
            ':eid' => $executiveId,
            ':ename' => $assistantName,
            ':task' => $taskTitle,
            ':target' => $targetSys,
            ':pct' => $progressPct,
            ':chk' => $checklist,
            ':logs' => $logs,
            ':findings' => $findings,
            ':url' => $prevUrl
        ]);
        $createdWorkPreviewId = $pdo->lastInsertId();

        if (!empty($taskTitle)) {
            $pdo->prepare("UPDATE notes SET status = 'in_progress', is_done = 0 WHERE LOWER(title) = LOWER(:task) AND (venture_id = :vid OR venture_id IS NULL)")->execute([':task' => $taskTitle, ':vid' => $ventureId]);
        }

        $createdWorkPreview = [
            'id' => $createdWorkPreviewId,
            'session_id' => $sessionId,
            'executive_id' => $executiveId,
            'executive_name' => $assistantName,
            'task_title' => $taskTitle,
            'target_system' => $targetSys,
            'status' => 'in_progress',
            'progress_percent' => $progressPct,
            'checklist' => isset($wpData['checklist']) ? $wpData['checklist'] : [],
            'terminal_logs' => isset($wpData['terminal_logs']) ? $wpData['terminal_logs'] : []
        ];
    }
    $reply = preg_replace('/\[\[WORK_PREVIEW:.*?\]\]/is', '', $reply);
    $reply = trim($reply);
}

// 5.5 Auto-Extract and Execute [[EXECUTE_ACTION: ...]] (Real Agent Tools)
$executedActions = [];
if (preg_match_all('/\[\[EXECUTE_ACTION:\s*([^\|]+)\s*\|\s*(\{.*?\})\s*\]\]/is', $reply, $actionMatches, PREG_SET_ORDER)) {
    foreach ($actionMatches as $am) {
        $actType = trim($am[1]);
        $actPayload = json_decode(trim($am[2]), true);
        if (is_array($actPayload)) {
            $actionResult = executeAgentAction($actType, $actPayload);
            $executedActions[] = $actionResult;
        }
    }
    $reply = preg_replace('/\[\[EXECUTE_ACTION:.*?\]\]/is', '', $reply);
    $reply = trim($reply);
}

// 5.55 Intent Fallback & Verification Engine:
// If no action tag was emitted, but user requested a project/folder or reply mentions htdocs folder:
if (empty($executedActions)) {
    $folderTarget = null;
    $projectTitle = null;

    if (preg_match('/(?:htdocs|localhost)\/([a-zA-Z0-9_\-]+)/i', $reply, $folderMatch)) {
        $folderTarget = trim($folderMatch[1]);
    } elseif (preg_match('/(?:htdocs|localhost)\/([a-zA-Z0-9_\-]+)/i', $userMessage, $userFolderMatch)) {
        $folderTarget = trim($userFolderMatch[1]);
    } elseif (preg_match('/(tourism|hotel|ecommerce|travel|portfolio|test|app|shop)[-_]?(?:test|site|web|project)?/i', $userMessage, $topicMatch) &&
              preg_match('/(hadanna|hadapan|create|build|mula idan|folder|project|website|setup)/i', $userMessage)) {
        $folderTarget = strtolower(trim($topicMatch[0]));
        if (!preg_match('/[-_]/', $folderTarget)) {
            $folderTarget .= '-app';
        }
    }

    if (!empty($folderTarget) && strtolower($folderTarget) !== 'privateassistance') {
        $checkRes = resolveHtdocsPath($folderTarget);
        if (!file_exists($checkRes['full_path']) || count(glob($checkRes['full_path'] . '/*')) === 0) {
            $projectTitle = !empty($detectedProjectName) ? $detectedProjectName : ucwords(str_replace(['-', '_'], ' ', $folderTarget));
            $templateType = (stripos($folderTarget, 'tour') !== false || stripos($projectTitle, 'tour') !== false) ? 'tourism' : 'general';
            $actionResult = executeAgentAction('create_project', [
                'folder' => $folderTarget,
                'title' => $projectTitle,
                'template' => $templateType
            ]);
            $executedActions[] = $actionResult;
        }
    }
}

// 6. Save assistant reply in database
$stmt = $pdo->prepare("INSERT INTO messages (session_id, role, content, discussion_id, workspace_id, workspace_data, work_preview_id, actions_data) VALUES (:session_id, 'model', :content, :discussion_id, :workspace_id, :workspace_data, :wpid, :act_data)");
$stmt->execute([
    ':session_id' => $sessionId,
    ':content' => $reply,
    ':discussion_id' => $createdDiscussionId,
    ':workspace_id' => $createdWorkspace ? $createdWorkspace['id'] : null,
    ':workspace_data' => $createdWorkspace ? json_encode($createdWorkspace, JSON_UNESCAPED_UNICODE) : null,
    ':wpid' => $createdWorkPreviewId,
    ':act_data' => !empty($executedActions) ? json_encode($executedActions, JSON_UNESCAPED_UNICODE) : null
]);
$assistantMessageId = $pdo->lastInsertId();

if ($createdWorkPreviewId) {
    $pdo->prepare("UPDATE member_work_previews SET message_id = :mid WHERE id = :id")->execute([':mid' => $assistantMessageId, ':id' => $createdWorkPreviewId]);
}

// Update session timestamp
$stmt = $pdo->prepare("UPDATE sessions SET updated_at = CURRENT_TIMESTAMP WHERE id = :id");
$stmt->execute([':id' => $sessionId]);

sendJsonResponse([
    'success' => true,
    'session_id' => $sessionId,
    'reply' => $reply,
    'token_usage' => $result['usage'] ?? null,
    'token_stats' => getTokenUsageStats(),
    'role_configured' => $roleConfigured,
    'created_workspace' => $createdWorkspace,
    'open_workspace_modal' => $openWorkspaceModal,
    'workspace_prompt_card' => $workspacePromptCard,
    'switched_model' => $switchedModel,
    'work_preview' => $createdWorkPreview,
    'actions' => $executedActions,
    'detected_project' => !empty($detectedProjectName) ? [
        'id' => $detectedProjectId,
        'name' => $detectedProjectName
    ] : null,
    'delegated_tasks' => $delegatedTasks,
    'team_discussion' => $createdDiscussion,
    'user_message' => [
        'id' => $userMessageId,
        'role' => 'user',
        'content' => $userMessage,
        'attachments' => $attachments,
        'created_at' => date('Y-m-d H:i:s')
    ],
    'assistant_message' => [
        'id' => $assistantMessageId,
        'role' => 'model',
        'content' => $reply,
        'role_configured' => $roleConfigured,
        'discussion_id' => $createdDiscussionId,
        'workspace_id' => $createdWorkspace ? $createdWorkspace['id'] : null,
        'workspace' => $createdWorkspace,
        'work_preview_id' => $createdWorkPreviewId,
        'work_preview' => $createdWorkPreview,
        'actions' => $executedActions,
        'actions_data' => !empty($executedActions) ? json_encode($executedActions, JSON_UNESCAPED_UNICODE) : null,
        'created_at' => date('Y-m-d H:i:s')
    ]
]);
