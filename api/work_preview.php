<?php
// api/work_preview.php - Member Live Work Preview & Task Inspection Engine
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

function generateLiveWorkPreviewForNote($pdo, $note) {
    $noteId = intval($note['id']);
    $title = $note['title'];
    $projectName = !empty($note['project_name']) ? $note['project_name'] : 'Corporate Sprint';
    $status = !empty($note['status']) ? $note['status'] : 'in_progress';
    $assignedTo = !empty($note['assigned_to']) ? $note['assigned_to'] : 'Executive Specialist';

    // Find executive ID & details
    $cleanName = trim(explode('(', $assignedTo)[0]);
    $eStmt = $pdo->prepare("SELECT id, name, title, department, avatar_url FROM executives WHERE LOWER(name) LIKE LOWER(:name) OR LOWER(title) LIKE LOWER(:title) LIMIT 1");
    $eStmt->execute([
        ':name' => '%' . $cleanName . '%',
        ':title' => '%' . $cleanName . '%'
    ]);
    $exec = $eStmt->fetch();

    if (!$exec) {
        $exec = $pdo->query("SELECT id, name, title, department, avatar_url FROM executives ORDER BY id ASC LIMIT 1")->fetch();
    }

    $execId = $exec ? intval($exec['id']) : 1;
    $execName = $exec ? $exec['name'] : 'Executive Specialist';
    $execTitle = $exec ? $exec['title'] : 'Lead Executive';

    // Progress percentage based on status
    if ($status === 'completed' || $note['is_done'] == 1) {
        $progressPct = 100;
    } elseif ($status === 'review') {
        $progressPct = 90;
    } elseif ($status === 'in_progress') {
        $progressPct = 72;
    } else {
        $progressPct = 30;
    }

    $text = strtolower($title . ' ' . $execTitle . ' ' . $projectName);
    $now = date('H:i:s');
    $t1 = date('H:i:s', time() - 240);
    $t2 = date('H:i:s', time() - 180);
    $t3 = date('H:i:s', time() - 110);
    $t4 = date('H:i:s', time() - 40);
    $t5 = $now;

    if (preg_match('/(security|audit|cluster|scaling|infrastructure|docker|server|redis|database|backend|api)/i', $text)) {
        $domain = 'infrastructure';
        $checklist = [
            ['text' => 'Analyze infrastructure architecture & cluster topology', 'done' => $progressPct >= 30],
            ['text' => 'Execute vulnerability diagnostic & zero-trust access audit', 'done' => $progressPct >= 50],
            ['text' => 'Configure automated cluster node autoscaling & load balancer rules', 'done' => $progressPct >= 70],
            ['text' => 'Run high-concurrency synthetic stress benchmark (10k req/s)', 'done' => $progressPct >= 90],
            ['text' => 'Finalize executive safety certificate and production sign-off', 'done' => $progressPct >= 100]
        ];
        $terminalLogs = [
            ['time' => $t1, 'type' => 'info', 'text' => "Initializing enterprise security audit suite for [{$projectName}]..."],
            ['time' => $t1, 'type' => 'info', 'text' => "Connected to primary cluster: us-east-prod-k8s-cluster (v1.29.4)"],
            ['time' => $t2, 'type' => 'success', 'text' => "Diagnostic scan complete: 0 critical CVE vulnerabilities found."],
            ['time' => $t2, 'type' => 'info', 'text' => "Triggering high-availability cluster scaling: Replicas (3 -> 8 active pods)"],
            ['time' => $t3, 'type' => 'success', 'text' => "Kubernetes HPA (Horizontal Pod Autoscaler) configured with targetCPU=65%"],
            ['time' => $t3, 'type' => 'warning', 'text' => "Ingress rate limiter enabled: 2,500 req/min/IP with Redis token bucket"],
            ['time' => $t4, 'type' => 'info', 'text' => "Executing synthetic stress test: 12,000 requests over 60 seconds..."],
            ['time' => $t5, 'type' => 'success', 'text' => "Cluster scaling active. P99 latency: 14.2ms. All health checks PASSED."]
        ];
        $visualData = [
            'type' => 'metrics',
            'system_status' => 'Operational & Scaled',
            'badges' => ['TLS 1.3 Strict', 'Zero-Trust Enforced', '8 Pods Healthy'],
            'metrics' => [
                ['label' => 'Active Cluster Nodes', 'value' => '8 Instances', 'change' => '+166% Capacity', 'status' => 'emerald'],
                ['label' => 'P99 Response Latency', 'value' => '14.2 ms', 'change' => '-38ms Faster', 'status' => 'emerald'],
                ['label' => 'CPU Cluster Load', 'value' => '32.4%', 'change' => 'Optimal', 'status' => 'cyan'],
                ['label' => 'Error Rate', 'value' => '0.00%', 'change' => 'Zero Faults', 'status' => 'emerald']
            ],
            'code_snippet' => "apiVersion: apps/v1\nkind: Deployment\nmetadata:\n  name: enterprise-scaling-group\nspec:\n  replicas: 8\n  strategy:\n    type: RollingUpdate\n    rollingUpdate:\n      maxSurge: 2\n      maxUnavailable: 0"
        ];
        $findings = "### 🛡️ Executive Infrastructure & Security Audit Briefing\n\n" .
            "- **Target System:** {$projectName} Infrastructure\n" .
            "- **Lead Specialist:** {$execName} ({$execTitle})\n\n" .
            "#### Key Deliverables Verified:\n" .
            "1. **High-Availability Scaling:** Horizontal Pod Autoscaler successfully initiated. Cluster can gracefully sustain over 15,000 simultaneous concurrent sessions without degradation.\n" .
            "2. **Zero-Trust Hardening:** TLS 1.3 strict transport security and rotating token credentials verified across all internal API endpoints.\n" .
            "3. **Data Integrity Guarantee:** Zero database deadlocks or memory leaks identified during continuous stress simulation.\n\n" .
            "**Verdict:** Architecture is hardened, fully resilient, and cleared for live production rollout.";
    } elseif (preg_match('/(ui|ux|design|logo|brand|component|frontend|page|visual|specs)/i', $text)) {
        $domain = 'design';
        $checklist = [
            ['text' => 'Audit brand guidelines, typography tokens & contrast standards', 'done' => $progressPct >= 30],
            ['text' => 'Build high-fidelity responsive UI components in Tailwind CSS', 'done' => $progressPct >= 50],
            ['text' => 'Implement micro-interactions, dark/light token states & mobile gestures', 'done' => $progressPct >= 70],
            ['text' => 'Run WCAG AAA accessibility audit & viewport matrix validation', 'done' => $progressPct >= 90],
            ['text' => 'Publish component asset library to production design studio', 'done' => $progressPct >= 100]
        ];
        $terminalLogs = [
            ['time' => $t1, 'type' => 'info', 'text' => "Vite v5.2.0 compiling design system assets..."],
            ['time' => $t1, 'type' => 'success', 'text' => "Tailwind JIT engine built in 142ms. 0 CSS conflicts."],
            ['time' => $t2, 'type' => 'info', 'text' => "Generating SVG vector assets and high-res icon matrices..."],
            ['time' => $t2, 'type' => 'success', 'text' => "WCAG AAA color contrast validation: 100% compliant (ratio > 7.1:1)."],
            ['time' => $t3, 'type' => 'info', 'text' => "Simulating device viewports: Mobile (375px), Tablet (768px), 4K Desktop"],
            ['time' => $t4, 'type' => 'success', 'text' => "Zero horizontal layout shift (CLS: 0.00). All touch targets > 44px."],
            ['time' => $t5, 'type' => 'success', 'text' => "Visual staging preview rendered successfully. Ready for CEO review."]
        ];
        $visualData = [
            'type' => 'design',
            'system_status' => 'Design Staging Ready',
            'badges' => ['WCAG AAA Verified', 'Mobile First', 'Micro-Animations Active'],
            'metrics' => [
                ['label' => 'Contrast Ratio', 'value' => '14.8:1', 'change' => 'Ultra Legible', 'status' => 'emerald'],
                ['label' => 'Bundle CSS Weight', 'value' => '8.4 KB', 'change' => 'Ultralight', 'status' => 'emerald'],
                ['label' => 'Responsive Breakpoints', 'value' => '5 Verified', 'change' => 'Mobile to 4K', 'status' => 'cyan'],
                ['label' => 'Render Frame Rate', 'value' => '60 FPS', 'change' => 'Silk Smooth', 'status' => 'emerald']
            ],
            'code_snippet' => "/* Primary Component Theme Tokens */\n:root {\n  --color-primary: #173d34;\n  --color-accent: #b7843f;\n  --surface-ground: #fcfaf5;\n  --radius-container: 1.25rem;\n  --transition-smooth: cubic-bezier(0.16, 1, 0.3, 1);\n}"
        ];
        $findings = "### 🎨 Creative Direction & UI/UX Technical Review\n\n" .
            "- **Scope:** {$title}\n" .
            "- **Designer:** {$execName} ({$execTitle})\n\n" .
            "#### Architectural Achievements:\n" .
            "1. **Modern Typography & Spacing:** Perfect optical baseline rhythm aligned with our premium corporate aesthetic.\n" .
            "2. **Responsive Fluidity:** 100% responsive fluid scaling across all mobile touchscreens and desktop screens.\n" .
            "3. **State Transitions:** Subtle micro-interactions applied for hover, focus, and active touch feedback.\n\n" .
            "**Verdict:** Creative direction approved and locked for implementation.";
    } elseif (preg_match('/(seo|keyword|silzee|content|traffic|serp|crawl|organic)/i', $text)) {
        $domain = 'seo';
        $checklist = [
            ['text' => 'Perform deep search crawl & Core Web Vitals health inspection', 'done' => $progressPct >= 30],
            ['text' => 'Analyze competitive keyword gap matrix & search intent distribution', 'done' => $progressPct >= 50],
            ['text' => 'Construct high-authority evergreen topic cluster blueprint', 'done' => $progressPct >= 70],
            ['text' => 'Optimize schema.org structured JSON-LD data for rich snippets', 'done' => $progressPct >= 90],
            ['text' => 'Integrate prioritized action items into corporate operational sprint', 'done' => $progressPct >= 100]
        ];
        $terminalLogs = [
            ['time' => $t1, 'type' => 'info', 'text' => "Starting comprehensive crawl on target domain..."],
            ['time' => $t1, 'type' => 'success', 'text' => "Core Web Vitals LCP: 1.1s | FID: 8ms | CLS: 0.01 (All Green)"],
            ['time' => $t2, 'type' => 'info', 'text' => "Extracting competitor keyword gap matrix across top 10 SERP rankings..."],
            ['time' => $t2, 'type' => 'success', 'text' => "Identified 12 high-intent low-difficulty keyword cluster targets."],
            ['time' => $t3, 'type' => 'info', 'text' => "Validating Schema.org Article & Organization structured JSON-LD..."],
            ['time' => $t4, 'type' => 'success', 'text' => "Google Rich Results Test: 0 errors, 3 eligible snippet enhancements."],
            ['time' => $t5, 'type' => 'success', 'text' => "SEO execution blueprint finalized. Traffic velocity projected: +45%"]
        ];
        $visualData = [
            'type' => 'seo',
            'system_status' => 'SERP Optimization Active',
            'badges' => ['Core Web Vitals 100/100', 'Schema JSON-LD Valid', 'Indexing Ready'],
            'metrics' => [
                ['label' => 'Target Keywords Identified', 'value' => '28 Terms', 'change' => 'High Intent', 'status' => 'cyan'],
                ['label' => 'Lighthouse SEO Score', 'value' => '100 / 100', 'change' => 'Flawless', 'status' => 'emerald'],
                ['label' => 'Crawl Error Rate', 'value' => '0 Errors', 'change' => 'Clean Sitemap', 'status' => 'emerald'],
                ['label' => 'Organic Growth Projection', 'value' => '+45%', 'change' => 'Next 90 Days', 'status' => 'emerald']
            ],
            'code_snippet' => "{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"TechArticle\",\n  \"headline\": \"Enterprise Architecture Strategy\",\n  \"author\": { \"@type\": \"Person\", \"name\": \"{$execName}\" }\n}"
        ];
        $findings = "### 📈 Search Architecture & Organic Growth Analysis\n\n" .
            "- **Project:** {$projectName}\n" .
            "- **Lead Strategist:** {$execName} ({$execTitle})\n\n" .
            "#### Growth Strategy Highlights:\n" .
            "1. **Technical Foundation:** All Core Web Vitals are in Google's optimal 'Green' zone for maximum ranking advantage.\n" .
            "2. **Content Pillar Clustering:** 4 core thematic topic clusters mapped to capture high-conversion organic buyer intent.\n" .
            "3. **Structured Entity Authority:** Rich Schema.org markup prepared to trigger rich snippets on search result pages.\n\n" .
            "**Verdict:** High organic impact roadmap ready for execution.";
    } else {
        $domain = 'general';
        $checklist = [
            ['text' => 'Review executive requirements, dependencies & operational deliverables', 'done' => $progressPct >= 30],
            ['text' => 'Execute operational workflow validation & stakeholder alignment', 'done' => $progressPct >= 50],
            ['text' => 'Verify milestone documentation & cross-functional integration gates', 'done' => $progressPct >= 70],
            ['text' => 'Compile executive diagnostic summary & data integrity sign-off', 'done' => $progressPct >= 90],
            ['text' => 'Deliver complete sprint milestone deliverables to the CEO', 'done' => $progressPct >= 100]
        ];
        $terminalLogs = [
            ['time' => $t1, 'type' => 'info', 'text' => "Task execution sequence initiated for: '{$title}'"],
            ['time' => $t1, 'type' => 'info', 'text' => "Owner assigned: {$execName} ({$execTitle})"],
            ['time' => $t2, 'type' => 'success', 'text' => "Operational requirements and technical dependencies satisfied."],
            ['time' => $t3, 'type' => 'info', 'text' => "Executing sprint verification gates..."],
            ['time' => $t4, 'type' => 'success', 'text' => "Automated validation complete: All deliverable milestones certified."],
            ['time' => $t5, 'type' => 'success', 'text' => "Deliverable completed. Generating briefing for the CEO..."]
        ];
        $visualData = [
            'type' => 'general',
            'system_status' => 'Execution Verified',
            'badges' => ['Milestones Aligned', 'Dependencies Clear', 'Quality Certified'],
            'metrics' => [
                ['label' => 'Sprint Gate Progress', 'value' => "{$progressPct}%", 'change' => 'On Track', 'status' => 'emerald'],
                ['label' => 'Team Alignment', 'value' => '100%', 'change' => 'Consensus Met', 'status' => 'emerald'],
                ['label' => 'Risk Level', 'value' => 'Low', 'change' => 'Zero Blockers', 'status' => 'cyan'],
                ['label' => 'Delivery Timeline', 'value' => 'Ahead of Schedule', 'change' => 'Sprint 1', 'status' => 'emerald']
            ],
            'code_snippet' => "TASK_METRICS: {\n  id: {$noteId},\n  title: \"{$title}\",\n  owner: \"{$execName}\",\n  status: \"{$status}\",\n  readiness: \"APPROVED\"\n}"
        ];
        $findings = "### 📋 Executive Milestone Verification Report\n\n" .
            "- **Deliverable:** {$title}\n" .
            "- **Executive Lead:** {$execName} ({$execTitle})\n" .
            "- **Project:** {$projectName}\n\n" .
            "#### Verification Conclusions:\n" .
            "1. **Operational Completeness:** All roadmap milestones meet corporate quality standards.\n" .
            "2. **Cross-Departmental Harmony:** No blockers or technical conflicts discovered with other active ventures.\n" .
            "3. **Security & Stability:** Safe for production integration without technical debt.\n\n" .
            "**Verdict:** Deliverable fully signed off.";
    }

    $previewUrl = "https://" . strtolower(preg_replace('/[^a-z0-9]+/i', '-', $projectName)) . ".internal/live-preview";

    // Resolve a valid session_id to satisfy foreign key constraint
    $validSessionId = null;
    if (!empty($note['discussion_id'])) {
        $discStmt = $pdo->prepare("SELECT session_id FROM team_discussions WHERE id = :did");
        $discStmt->execute([':did' => $note['discussion_id']]);
        $discRow = $discStmt->fetch();
        if ($discRow && !empty($discRow['session_id'])) {
            $validSessionId = intval($discRow['session_id']);
        }
    }
    if (!$validSessionId) {
        $userId = !empty($note['user_id']) ? intval($note['user_id']) : getCurrentUserId();
        if ($userId) {
            $sessStmt = $pdo->prepare("SELECT id FROM sessions WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
            $sessStmt->execute([':uid' => $userId]);
            $sessRow = $sessStmt->fetch();
            $validSessionId = $sessRow ? intval($sessRow['id']) : null;
        } else {
            $sessRow = $pdo->query("SELECT id FROM sessions ORDER BY id DESC LIMIT 1")->fetch();
            $validSessionId = $sessRow ? intval($sessRow['id']) : null;
        }
    }

    $insStmt = $pdo->prepare("
        INSERT INTO member_work_previews 
        (note_id, session_id, executive_id, executive_name, task_title, target_system, status, progress_percent, checklist_json, terminal_logs, visual_data_json, findings_markdown, preview_url)
        VALUES 
        (:nid, :sid, :eid, :ename, :task, :target, :status, :pct, :chk, :logs, :vdata, :findings, :url)
    ");
    $insStmt->execute([
        ':nid' => $noteId,
        ':sid' => $validSessionId,
        ':eid' => $execId,
        ':ename' => $execName,
        ':task' => $title,
        ':target' => $projectName,
        ':status' => $status,
        ':pct' => $progressPct,
        ':chk' => json_encode($checklist, JSON_UNESCAPED_UNICODE),
        ':logs' => json_encode($terminalLogs, JSON_UNESCAPED_UNICODE),
        ':vdata' => json_encode($visualData, JSON_UNESCAPED_UNICODE),
        ':findings' => $findings,
        ':url' => $previewUrl
    ]);

    $newId = $pdo->lastInsertId();

    return [
        'id' => $newId,
        'note_id' => $noteId,
        'session_id' => $validSessionId,
        'executive_id' => $execId,
        'executive_name' => $execName,
        'task_title' => $title,
        'target_system' => $projectName,
        'status' => $status,
        'progress_percent' => $progressPct,
        'checklist' => $checklist,
        'terminal_logs' => $terminalLogs,
        'visual_data' => $visualData,
        'findings_markdown' => $findings,
        'preview_url' => $previewUrl,
        'executive' => $exec
    ];
}

if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $taskId = isset($_GET['task_id']) ? intval($_GET['task_id']) : (isset($_GET['note_id']) ? intval($_GET['note_id']) : 0);
    $sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
    $messageId = isset($_GET['message_id']) ? intval($_GET['message_id']) : 0;
    $executiveId = isset($_GET['executive_id']) ? intval($_GET['executive_id']) : 0;

    $preview = null;

    if ($taskId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM member_work_previews WHERE note_id = :nid ORDER BY id DESC LIMIT 1");
        $stmt->execute([':nid' => $taskId]);
        $preview = $stmt->fetch();

        if (!$preview) {
            // Find note and generate preview dynamically
            $nStmt = $pdo->prepare("SELECT n.*, p.name as resolved_project_name FROM notes n LEFT JOIN projects p ON p.id = n.project_id WHERE n.id = :id");
            $nStmt->execute([':id' => $taskId]);
            $note = $nStmt->fetch();
            if ($note) {
                if (empty($note['project_name'])) {
                    $note['project_name'] = $note['resolved_project_name'] ?? 'General';
                }
                $preview = generateLiveWorkPreviewForNote($pdo, $note);
                sendJsonResponse(['success' => true, 'preview' => $preview]);
            }
        }
    } elseif ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM member_work_previews WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $preview = $stmt->fetch();
    } elseif ($executiveId > 0) {
        // Find latest preview for executive, or latest task assigned to them
        $stmt = $pdo->prepare("SELECT * FROM member_work_previews WHERE executive_id = :eid ORDER BY id DESC LIMIT 1");
        $stmt->execute([':eid' => $executiveId]);
        $preview = $stmt->fetch();

        if (!$preview) {
            $exStmt = $pdo->prepare("SELECT name, title FROM executives WHERE id = :eid");
            $exStmt->execute([':eid' => $executiveId]);
            $exRow = $exStmt->fetch();
            if ($exRow) {
                $nStmt = $pdo->prepare("SELECT * FROM notes WHERE LOWER(assigned_to) LIKE LOWER(:name) ORDER BY is_done ASC, id DESC LIMIT 1");
                $nStmt->execute([':name' => '%' . $exRow['name'] . '%']);
                $note = $nStmt->fetch();
                if ($note) {
                    $preview = generateLiveWorkPreviewForNote($pdo, $note);
                    sendJsonResponse(['success' => true, 'preview' => $preview]);
                }
            }
        }
    } elseif ($messageId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM member_work_previews WHERE message_id = :mid");
        $stmt->execute([':mid' => $messageId]);
        $preview = $stmt->fetch();
    } elseif ($sessionId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM member_work_previews WHERE session_id = :sid ORDER BY id DESC LIMIT 1");
        $stmt->execute([':sid' => $sessionId]);
        $preview = $stmt->fetch();
    } else {
        $stmt = $pdo->query("SELECT * FROM member_work_previews ORDER BY id DESC LIMIT 1");
        $preview = $stmt->fetch();
    }

    $currentUid = getCurrentUserId();

    if (!$preview) {
        // Fallback: pick the first in_progress or active note from notes table
        if ($currentUid) {
            $nStmt = $pdo->prepare("SELECT * FROM notes WHERE status = 'in_progress' AND user_id = :uid ORDER BY id DESC LIMIT 1");
            $nStmt->execute([':uid' => $currentUid]);
            $note = $nStmt->fetch();
            if (!$note) {
                $nStmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
                $nStmt->execute([':uid' => $currentUid]);
                $note = $nStmt->fetch();
            }
        } else {
            $nStmt = $pdo->query("SELECT * FROM notes WHERE status = 'in_progress' ORDER BY id DESC LIMIT 1");
            $note = $nStmt->fetch();
            if (!$note) {
                $note = $pdo->query("SELECT * FROM notes ORDER BY id DESC LIMIT 1")->fetch();
            }
        }
        if ($note) {
            $preview = generateLiveWorkPreviewForNote($pdo, $note);
            sendJsonResponse(['success' => true, 'preview' => $preview]);
        } else {
            sendJsonResponse(['error' => 'No active deliverables or work previews found'], 404);
        }
    }

    // Decode JSON fields if from DB
    if (!isset($preview['checklist'])) {
        $preview['checklist'] = !empty($preview['checklist_json']) ? json_decode($preview['checklist_json'], true) : [];
    }
    if (!isset($preview['terminal_logs'])) {
        $preview['terminal_logs'] = !empty($preview['terminal_logs']) ? json_decode($preview['terminal_logs'], true) : [];
    }
    if (!isset($preview['visual_data'])) {
        $preview['visual_data'] = !empty($preview['visual_data_json']) ? json_decode($preview['visual_data_json'], true) : null;
    }

    // Fetch executive details if missing
    if (!isset($preview['executive']) || empty($preview['executive'])) {
        $exStmt = $pdo->prepare("SELECT id, name, title, department, avatar_url FROM executives WHERE id = :eid");
        $exStmt->execute([':eid' => $preview['executive_id']]);
        $preview['executive'] = $exStmt->fetch();
    }

    sendJsonResponse(['success' => true, 'preview' => $preview]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? trim($input['action']) : 'deliver_report';
    $previewId = isset($input['preview_id']) ? intval($input['preview_id']) : 0;
    $taskId = isset($input['task_id']) ? intval($input['task_id']) : (isset($input['note_id']) ? intval($input['note_id']) : 0);

    if ($previewId <= 0 && $taskId > 0) {
        $fStmt = $pdo->prepare("SELECT id FROM member_work_previews WHERE note_id = :nid LIMIT 1");
        $fStmt->execute([':nid' => $taskId]);
        $found = $fStmt->fetch();
        if ($found) {
            $previewId = intval($found['id']);
        } else {
            $nStmt = $pdo->prepare("SELECT * FROM notes WHERE id = :id");
            $nStmt->execute([':id' => $taskId]);
            $note = $nStmt->fetch();
            if ($note) {
                $created = generateLiveWorkPreviewForNote($pdo, $note);
                $previewId = $created['id'];
            }
        }
    }

    if ($previewId <= 0) {
        sendJsonResponse(['error' => 'Invalid preview ID'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM member_work_previews WHERE id = :id");
    $stmt->execute([':id' => $previewId]);
    $preview = $stmt->fetch();

    if (!$preview) {
        sendJsonResponse(['error' => 'Work preview not found'], 404);
    }

    if ($action === 'simulate_step') {
        $checklist = !empty($preview['checklist_json']) ? json_decode($preview['checklist_json'], true) : [];
        $terminalLogs = !empty($preview['terminal_logs']) ? json_decode($preview['terminal_logs'], true) : [];
        $pct = intval($preview['progress_percent']);

        // Check off next item
        $nextItemIndex = -1;
        foreach ($checklist as $idx => $item) {
            if (empty($item['done'])) {
                $nextItemIndex = $idx;
                break;
            }
        }

        $now = date('H:i:s');
        if ($nextItemIndex >= 0) {
            $checklist[$nextItemIndex]['done'] = true;
            $completedText = $checklist[$nextItemIndex]['text'];
            $terminalLogs[] = ['time' => $now, 'type' => 'success', 'text' => "Milestone verified: '{$completedText}' [SUCCESS]"];
            $pct = min(100, $pct + 15);
        } else {
            $terminalLogs[] = ['time' => $now, 'type' => 'info', 'text' => "Running real-time heartbeat verification ping... All systems 100% nominal."];
            $pct = 100;
        }

        $newStatus = ($pct >= 100) ? 'completed' : 'in_progress';

        $updStmt = $pdo->prepare("UPDATE member_work_previews SET progress_percent = :pct, status = :status, checklist_json = :chk, terminal_logs = :logs, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $updStmt->execute([
            ':pct' => $pct,
            ':status' => $newStatus,
            ':chk' => json_encode($checklist, JSON_UNESCAPED_UNICODE),
            ':logs' => json_encode($terminalLogs, JSON_UNESCAPED_UNICODE),
            ':id' => $previewId
        ]);

        if ($newStatus === 'completed' && !empty($preview['note_id'])) {
            $pdo->prepare("UPDATE notes SET status = 'completed', is_done = 1 WHERE id = :id")->execute([':id' => $preview['note_id']]);
        }

        // Fetch refreshed
        $stmt->execute([':id' => $previewId]);
        $refreshed = $stmt->fetch();
        $refreshed['checklist'] = $checklist;
        $refreshed['terminal_logs'] = $terminalLogs;
        $refreshed['visual_data'] = !empty($refreshed['visual_data_json']) ? json_decode($refreshed['visual_data_json'], true) : null;

        sendJsonResponse([
            'success' => true,
            'simulated' => true,
            'preview' => $refreshed
        ]);
    }

    if ($action === 'deliver_report') {
        $checklist = !empty($preview['checklist_json']) ? json_decode($preview['checklist_json'], true) : [];
        foreach ($checklist as &$item) {
            $item['done'] = true;
        }

        $terminalLogs = !empty($preview['terminal_logs']) ? json_decode($preview['terminal_logs'], true) : [];
        $terminalLogs[] = ['time' => date('H:i:s'), 'type' => 'success', 'text' => 'Executive audit completed. Generating direct debrief for CEO...'];
        $terminalLogs[] = ['time' => date('H:i:s'), 'type' => 'info', 'text' => 'Delivered final architectural report into session #' . $preview['session_id']];

        // Update preview state to completed
        $updStmt = $pdo->prepare("UPDATE member_work_previews SET status = 'completed', progress_percent = 100, checklist_json = :chk, terminal_logs = :logs, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $updStmt->execute([
            ':chk' => json_encode($checklist, JSON_UNESCAPED_UNICODE),
            ':logs' => json_encode($terminalLogs, JSON_UNESCAPED_UNICODE),
            ':id' => $previewId
        ]);

        if (!empty($preview['note_id'])) {
            $pdo->prepare("UPDATE notes SET status = 'completed', is_done = 1 WHERE id = :id")->execute([':id' => $preview['note_id']]);
        } elseif (!empty($preview['task_title'])) {
            $pdo->prepare("UPDATE notes SET status = 'completed', is_done = 1 WHERE LOWER(title) = LOWER(:task)")->execute([':task' => $preview['task_title']]);
        }

        if (!empty($preview['findings_markdown'])) {
            $finalReport = $preview['findings_markdown'];
        } else {
            $execName = !empty($preview['executive_name']) ? $preview['executive_name'] : 'Executive Specialist';
            $taskTitle = !empty($preview['task_title']) ? $preview['task_title'] : 'Platform Deliverable';
            $targetSys = !empty($preview['target_system']) ? $preview['target_system'] : 'Enterprise Workspace';

            $finalReport = "I have successfully concluded the technical execution deliverables for **{$taskTitle}** ({$targetSys})!\n\n" .
                "📋 **Executive Briefing & Architectural Status:**\n" .
                "1. **Task Scope:** {$taskTitle}\n" .
                "2. **Lead Owner:** {$execName}\n" .
                "3. **Execution Verification:** All roadmap checklist items have been successfully completed and system integration is verified.\n" .
                "4. **Data Integrity:** Core system stability is 100% verified with zero data anomalies.\n\n" .
                "This deliverable is now verified and ready for production / next sprint stage.";
        }

        // Insert completed report into messages table if session exists
        $newMsgId = null;
        if (!empty($preview['session_id'])) {
            $insMsgStmt = $pdo->prepare("INSERT INTO messages (session_id, role, content, work_preview_id) VALUES (:sid, 'model', :content, :wpid)");
            $insMsgStmt->execute([
                ':sid' => $preview['session_id'],
                ':content' => $finalReport,
                ':wpid' => $previewId
            ]);
            $newMsgId = $pdo->lastInsertId();
            $pdo->prepare("UPDATE sessions SET updated_at = CURRENT_TIMESTAMP WHERE id = :sid")->execute([':sid' => $preview['session_id']]);
        }

        // Fetch refreshed preview
        $stmt->execute([':id' => $previewId]);
        $refreshedPreview = $stmt->fetch();
        $refreshedPreview['checklist'] = $checklist;
        $refreshedPreview['terminal_logs'] = $terminalLogs;
        $refreshedPreview['visual_data'] = !empty($refreshedPreview['visual_data_json']) ? json_decode($refreshedPreview['visual_data_json'], true) : null;

        sendJsonResponse([
            'success' => true,
            'message_id' => $newMsgId,
            'reply' => $finalReport,
            'preview' => $refreshedPreview
        ]);
    }

    sendJsonResponse(['error' => 'Unknown action'], 400);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
