<?php
// api/meetings.php
require_once __DIR__ . '/../config.php';
requireLogin(true);

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $meetingId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($meetingId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM board_meetings WHERE id = :id AND (user_id = :uid OR user_id IS NULL)");
        $stmt->execute([':id' => $meetingId, ':uid' => $userId]);
        $meeting = $stmt->fetch();

        if (!$meeting) {
            sendJsonResponse(['error' => 'Meeting not found'], 404);
        }

        $diagStmt = $pdo->prepare("SELECT * FROM meeting_dialogues WHERE meeting_id = :mid ORDER BY id ASC");
        $diagStmt->execute([':mid' => $meetingId]);
        $dialogues = $diagStmt->fetchAll();

        sendJsonResponse([
            'meeting' => $meeting,
            'dialogues' => $dialogues
        ]);
    } else {
        $ventureId = isset($_GET['venture_id']) ? intval($_GET['venture_id']) : 0;
        if ($ventureId > 0) {
            $stmt = $pdo->prepare("
                SELECT m.*, 
                       (SELECT COUNT(*) FROM meeting_dialogues WHERE meeting_id = m.id) as dialogue_count
                FROM board_meetings m 
                WHERE (m.user_id = :uid OR m.user_id IS NULL)
                  AND (m.venture_id = :vid OR m.venture_id IS NULL)
                ORDER BY m.id DESC
            ");
            $stmt->execute([':vid' => $ventureId, ':uid' => $userId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT m.*, 
                       (SELECT COUNT(*) FROM meeting_dialogues WHERE meeting_id = m.id) as dialogue_count
                FROM board_meetings m 
                WHERE (m.user_id = :uid OR m.user_id IS NULL)
                ORDER BY m.id DESC
            ");
            $stmt->execute([':uid' => $userId]);
        }
        $meetings = $stmt->fetchAll();
        sendJsonResponse(['meetings' => $meetings]);
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $agenda = isset($input['agenda']) ? trim($input['agenda']) : '';
    $ventureId = isset($input['venture_id']) && intval($input['venture_id']) > 0 ? intval($input['venture_id']) : 1;
    $attendeeIds = isset($input['attendee_ids']) && is_array($input['attendee_ids']) ? $input['attendee_ids'] : [];

    if (empty($agenda)) {
        sendJsonResponse(['error' => 'Meeting agenda is required'], 400);
    }

    // 1. Fetch Attendees
    if (!empty($attendeeIds)) {
        $inClause = implode(',', array_map('intval', $attendeeIds));
        $execStmt = $pdo->query("SELECT id, name, title, department, avatar_url, personality, bio FROM executives WHERE id IN ($inClause)");
    } else {
        $execStmt = $pdo->prepare("SELECT id, name, title, department, avatar_url, personality, bio FROM executives WHERE (venture_id = :vid OR is_chief_of_staff = 1) ORDER BY is_chief_of_staff DESC, id ASC LIMIT 6");
        $execStmt->execute([':vid' => $ventureId]);
    }
    $executives = $execStmt->fetchAll();

    if (empty($executives)) {
        sendJsonResponse(['error' => 'No executives available for the board meeting'], 400);
    }

    $currentUser = getCurrentUser();
    $userName = !empty($currentUser['name']) ? $currentUser['name'] : getSetting('user_name', 'CEO');
    $companyName = getSetting('company_name', 'Enterprises');

    // Fetch active venture details if provided
    $vStmt = $pdo->prepare("SELECT name, domain, description FROM ventures WHERE id = :vid");
    $vStmt->execute([':vid' => $ventureId]);
    $activeVenture = $vStmt->fetch();
    $ventureName = $activeVenture ? $activeVenture['name'] : $companyName;
    $ventureDomain = $activeVenture ? $activeVenture['domain'] : 'General Business';

    // Fetch memory vault for this user
    $memStmt = $pdo->prepare("SELECT category, content FROM memories WHERE (user_id = :uid OR user_id IS NULL) ORDER BY id ASC");
    $memStmt->execute([':uid' => $userId]);
    $memories = $memStmt->fetchAll();
    $companyContext = "Company: {$companyName}\nActive Venture: {$ventureName} ({$ventureDomain})\nCEO/Founder: {$userName}\n";
    if (!empty($memories)) {
        $companyContext .= "Known Facts & Company Context:\n";
        foreach ($memories as $m) {
            $companyContext .= "- [{$m['category']}] {$m['content']}\n";
        }
    }

    // Build Executives profile for prompt
    $execProfiles = "";
    foreach ($executives as $ex) {
        $execProfiles .= "Specialist/Executive ID #{$ex['id']}: {$ex['name']} | {$ex['title']} ({$ex['department']})\n";
        $execProfiles .= "Personality & Domain: {$ex['personality']}\n\n";
    }

    // 2. Multi-Agent Boardroom Simulation Prompt
    $meetingPrompt = "You are orchestrating a high-stakes, realistic Strategy & Operational Meeting for {$companyName} - Venture: \"{$ventureName}\" ({$ventureDomain}).
The CEO/Founder ({$userName}) has convened this urgent Meeting with the following agenda:
\"{$agenda}\"

{$companyContext}

The Specialist Team & Executives seated at the table are:
{$execProfiles}

SIMULATION RULES:
1. Simulate a realistic, highly authentic discussion among the specialists/executives and the CEO. Each specialist must speak strictly from their domain expertise matching their title and role in {$ventureName}.
2. The team members should acknowledge and challenge each other's points realistically, debating tradeoffs directly and addressing {$userName} (the CEO).
3. The team communicates in English by default, but dynamically mirrors and speaks in whatever language or dialect the CEO uses in the agenda or discussion.
4. FORMAT: You MUST format each spoken turn with this exact delimiter line on its own line:
===EXECUTIVE: <executive_id> | <speaker_name> | <speaker_title>===
Followed immediately by what the executive says.

After all members have spoken and debated, finish the meeting with this final turn:
===EXECUTIVE: 0 | Executive Summary | Action Plan & Key Decisions for the CEO===
Followed by structured bullet points of the final agreed decisions, designated executive owner per action item, and immediate next steps for the CEO.";

    $aiMessages = [
        ['role' => 'user', 'content' => $meetingPrompt]
    ];

    $res = callGeminiApi($aiMessages, "You are a master corporate boardroom meeting orchestrator.", 'board_meeting');

    if (isset($res['error'])) {
        sendJsonResponse(['error' => $res['error']], 500);
    }

    $rawReply = trim($res['reply']);

    // Parse delimited dialogue turns
    $dialoguesData = [];
    if (preg_match_all('/===EXECUTIVE:\s*(\d+)\s*\|\s*([^\|]+)\s*\|\s*([^=]+)===\s*(.*?)(?=(?:===EXECUTIVE:|$))/s', $rawReply, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $dialoguesData[] = [
                'executive_id' => intval($m[1]),
                'speaker_name' => trim($m[2]),
                'speaker_title' => trim($m[3]),
                'speech' => trim($m[4])
            ];
        }
    }

    // Fallback if no delimiters matched
    if (empty($dialoguesData)) {
        $dialoguesData = [
            [
                'executive_id' => $executives[0]['id'],
                'speaker_name' => $executives[0]['name'],
                'speaker_title' => $executives[0]['title'],
                'speech' => $rawReply
            ]
        ];
    }

    // 3. Save Board Meeting into Database
    $meetingTitle = mb_substr($agenda, 0, 45);
    if (mb_strlen($agenda) > 45) $meetingTitle .= '...';

    $stmt = $pdo->prepare("INSERT INTO board_meetings (title, agenda, status, venture_id, user_id) VALUES (:title, :agenda, 'completed', :venture_id, :uid)");
    $stmt->execute([
        ':title' => $meetingTitle,
        ':agenda' => $agenda,
        ':venture_id' => $ventureId,
        ':uid' => $userId
    ]);
    $meetingId = $pdo->lastInsertId();

    // Map avatar URLs by executive ID
    $avatarMap = [];
    foreach ($executives as $ex) {
        $avatarMap[$ex['id']] = $ex['avatar_url'];
    }
    $summaryAvatar = 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=400&auto=format&fit=crop&q=80';

    $savedDialogues = [];
    $insDiag = $pdo->prepare("INSERT INTO meeting_dialogues (meeting_id, executive_id, speaker_name, speaker_title, avatar_url, content) VALUES (:mid, :eid, :name, :title, :avatar, :content)");

    foreach ($dialoguesData as $d) {
        $eid = $d['executive_id'];
        $name = $d['speaker_name'];
        $title = $d['speaker_title'];
        $content = $d['speech'];
        $avatar = ($eid > 0 && isset($avatarMap[$eid])) ? $avatarMap[$eid] : $summaryAvatar;

        if (!empty($content)) {
            $insDiag->execute([
                ':mid' => $meetingId,
                ':eid' => $eid,
                ':name' => $name,
                ':title' => $title,
                ':avatar' => $avatar,
                ':content' => $content
            ]);

            $savedDialogues[] = [
                'id' => $pdo->lastInsertId(),
                'meeting_id' => $meetingId,
                'executive_id' => $eid,
                'speaker_name' => $name,
                'speaker_title' => $title,
                'avatar_url' => $avatar,
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    sendJsonResponse([
        'success' => true,
        'meeting' => [
            'id' => $meetingId,
            'title' => $meetingTitle,
            'agenda' => $agenda,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s')
        ],
        'dialogues' => $savedDialogues
    ], 201);
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        sendJsonResponse(['error' => 'Invalid meeting ID'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM board_meetings WHERE id = :id AND (user_id = :uid OR user_id IS NULL)");
    $stmt->execute([':id' => $id, ':uid' => $userId]);

    sendJsonResponse(['success' => true]);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
