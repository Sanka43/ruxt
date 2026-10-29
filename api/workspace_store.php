<?php
// api/workspace_store.php - Commercial AI Predefined Workspaces & Monetization API
require_once __DIR__ . '/../config.php';

$user = requireLogin(true);
$userId = (int)$user['id'];

$method = $_SERVER['REQUEST_METHOD'];

// Helper function to clone a template into the user's workspace
function provisionWorkspaceFromTemplate($pdo, $userId, $template, $paymentMethod, $amountPaid, $transactionId) {
    $pdo->beginTransaction();
    try {
        // 1. Insert new Venture (Workspace)
        $insVenture = $pdo->prepare("
            INSERT INTO `ventures` (`name`, `domain`, `description`, `icon`, `color`, `user_id`)
            VALUES (:name, :domain, :description, :icon, :color, :uid)
        ");
        $insVenture->execute([
            ':name' => $template['title'],
            ':domain' => $template['domain'],
            ':description' => $template['description'],
            ':icon' => $template['icon'] ?: 'briefcase',
            ':color' => $template['color'] ?: 'indigo',
            ':uid' => $userId
        ]);
        $newVentureId = (int)$pdo->lastInsertId();

        // 2. Clone Executives
        $executives = json_decode($template['executives_json'], true) ?: [];
        $executiveIdMap = []; // name => new_id
        $avatarMap = [];      // name => avatar_url

        if (!empty($executives)) {
            $insExec = $pdo->prepare("
                INSERT INTO `executives` 
                (`name`, `title`, `department`, `avatar_url`, `bio`, `personality`, `system_prompt`, `is_system`, `is_chief_of_staff`, `venture_id`, `voice_id`, `voice_gender`)
                VALUES (:name, :title, :department, :avatar_url, :bio, :personality, :system_prompt, 0, 0, :venture_id, :voice_id, :voice_gender)
            ");

            foreach ($executives as $ex) {
                $name = $ex['name'] ?? 'Executive';
                $title = $ex['title'] ?? 'Specialist';
                $dept = $ex['department'] ?? $template['domain'];
                $avatar = $ex['avatar_url'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80';
                $bio = $ex['bio'] ?? '';
                $personality = $ex['personality'] ?? 'Strategic and driven';
                $prompt = $ex['system_prompt'] ?? "You are {$name}, {$title}. Advise the user with precision.";
                $voiceId = $ex['voice_id'] ?? 'nova';
                $voiceGender = $ex['voice_gender'] ?? 'female';

                $insExec->execute([
                    ':name' => $name,
                    ':title' => $title,
                    ':department' => $dept,
                    ':avatar_url' => $avatar,
                    ':bio' => $bio,
                    ':personality' => $personality,
                    ':system_prompt' => $prompt,
                    ':venture_id' => $newVentureId,
                    ':voice_id' => $voiceId,
                    ':voice_gender' => $voiceGender
                ]);
                $newExId = (int)$pdo->lastInsertId();
                $executiveIdMap[$name] = $newExId;
                $avatarMap[$name] = $avatar;
            }
        }

        // 3. Clone Projects (Roadmaps)
        $projects = json_decode($template['projects_json'], true) ?: [];
        $projectIdMap = []; // name => new_id

        if (!empty($projects)) {
            $insProj = $pdo->prepare("
                INSERT INTO `projects` (`venture_id`, `name`, `description`, `status`, `color`, `icon`, `user_id`)
                VALUES (:venture_id, :name, :description, :status, :color, :icon, :uid)
            ");

            foreach ($projects as $pr) {
                $pName = $pr['name'] ?? 'Core Roadmap';
                $pDesc = $pr['description'] ?? '';
                $pStatus = $pr['status'] ?? 'active';
                $pColor = $pr['color'] ?? 'indigo';
                $pIcon = $pr['icon'] ?? 'folder-kanban';

                $insProj->execute([
                    ':venture_id' => $newVentureId,
                    ':name' => $pName,
                    ':description' => $pDesc,
                    ':status' => $pStatus,
                    ':color' => $pColor,
                    ':icon' => $pIcon,
                    ':uid' => $userId
                ]);
                $newPId = (int)$pdo->lastInsertId();
                $projectIdMap[$pName] = $newPId;
            }
        }

        // 4. Clone Tasks / SOPs into Notes
        $tasks = json_decode($template['tasks_json'], true) ?: [];
        if (!empty($tasks)) {
            $insTask = $pdo->prepare("
                INSERT INTO `notes` 
                (`title`, `content`, `is_done`, `due_date`, `assigned_to`, `assigned_avatar`, `priority`, `status`, `venture_id`, `project_id`, `project_name`, `user_id`)
                VALUES (:title, :content, :is_done, :due_date, :assigned_to, :assigned_avatar, :priority, :status, :venture_id, :project_id, :project_name, :uid)
            ");

            $defaultProjectId = !empty($projectIdMap) ? reset($projectIdMap) : null;
            $defaultProjectName = !empty($projectIdMap) ? key($projectIdMap) : null;

            foreach ($tasks as $tsk) {
                $tTitle = $tsk['title'] ?? 'Task';
                $tContent = $tsk['content'] ?? '';
                $tStatus = $tsk['status'] ?? 'todo';
                $isDone = ($tStatus === 'completed') ? 1 : 0;
                $tDue = $tsk['due_date'] ?? 'This week';
                $tAssign = $tsk['assigned_to'] ?? null;
                $tAvatar = ($tAssign && isset($avatarMap[$tAssign])) ? $avatarMap[$tAssign] : null;
                $tPriority = $tsk['priority'] ?? 'Medium';

                $insTask->execute([
                    ':title' => $tTitle,
                    ':content' => $tContent,
                    ':is_done' => $isDone,
                    ':due_date' => $tDue,
                    ':assigned_to' => $tAssign,
                    ':assigned_avatar' => $tAvatar,
                    ':priority' => $tPriority,
                    ':status' => $tStatus,
                    ':venture_id' => $newVentureId,
                    ':project_id' => $defaultProjectId,
                    ':project_name' => $defaultProjectName,
                    ':uid' => $userId
                ]);
            }
        }

        // 5. Record Purchase / Activation
        $insPurchase = $pdo->prepare("
            INSERT INTO `workspace_purchases` 
            (`user_id`, `template_id`, `template_slug`, `amount_paid`, `currency`, `payment_method`, `transaction_id`, `status`)
            VALUES (:uid, :tid, :slug, :amount, :currency, :method, :txnid, 'completed')
        ");
        $insPurchase->execute([
            ':uid' => $userId,
            ':tid' => (int)$template['id'],
            ':slug' => $template['slug'],
            ':amount' => (float)$amountPaid,
            ':currency' => $template['currency'] ?: 'USD',
            ':method' => $paymentMethod,
            ':txnid' => $transactionId
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'venture' => [
                'id' => $newVentureId,
                'name' => $template['title'],
                'domain' => $template['domain'],
                'icon' => $template['icon'],
                'color' => $template['color'],
                'executives_count' => count($executives),
                'projects_count' => count($projects),
                'tasks_count' => count($tasks)
            ]
        ];
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Failed to provision workspace template: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Database provisioning failed: ' . $e->getMessage()
        ];
    }
}

// -------------------------------------------------------------
// GET: List all templates or view single template with preview
// -------------------------------------------------------------
if ($method === 'GET') {
    $slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;

    // Fetch user purchases
    $purchasedTemplateSlugs = [];
    try {
        $pStmt = $pdo->prepare("SELECT DISTINCT template_slug FROM `workspace_purchases` WHERE user_id = :uid AND status = 'completed'");
        $pStmt->execute([':uid' => $userId]);
        $purchasedTemplateSlugs = $pStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (\Throwable $e) {
        $purchasedTemplateSlugs = [];
    }

    // Fetch user ventures to detect already deployed templates
    $userVentureNames = [];
    try {
        $vStmt = $pdo->prepare("SELECT name FROM `ventures` WHERE user_id = :uid");
        $vStmt->execute([':uid' => $userId]);
        $userVentureNames = $vStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (\Throwable $e) {
        $userVentureNames = [];
    }

    if ($slug) {
        $stmt = $pdo->prepare("SELECT * FROM `workspace_templates` WHERE slug = :slug AND is_active = 1 LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $tpl = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tpl) {
            sendJsonResponse(['error' => 'Template not found'], 404);
        }

        $tpl['features'] = json_decode($tpl['features_json'], true) ?: [];
        $tpl['executives'] = json_decode($tpl['executives_json'], true) ?: [];
        $tpl['projects'] = json_decode($tpl['projects_json'], true) ?: [];
        $tpl['tasks'] = json_decode($tpl['tasks_json'], true) ?: [];
        $tpl['prompts'] = json_decode($tpl['prompts_json'], true) ?: [];
        $tpl['is_unlocked'] = ($tpl['tier'] === 'free' || in_array($tpl['slug'], $purchasedTemplateSlugs));
        $tpl['is_deployed'] = in_array($tpl['title'], $userVentureNames);

        sendJsonResponse(['template' => $tpl]);
    }

    // List all active templates
    $stmt = $pdo->query("SELECT * FROM `workspace_templates` WHERE is_active = 1 ORDER BY tier ASC, price ASC, id ASC");
    $rawTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $templates = [];
    foreach ($rawTemplates as $tpl) {
        $tpl['features'] = json_decode($tpl['features_json'], true) ?: [];
        $tpl['executives'] = json_decode($tpl['executives_json'], true) ?: [];
        $tpl['projects'] = json_decode($tpl['projects_json'], true) ?: [];
        $tpl['tasks'] = json_decode($tpl['tasks_json'], true) ?: [];
        $tpl['prompts'] = json_decode($tpl['prompts_json'], true) ?: [];
        $tpl['is_unlocked'] = ($tpl['tier'] === 'free' || in_array($tpl['slug'], $purchasedTemplateSlugs));
        $tpl['is_deployed'] = in_array($tpl['title'], $userVentureNames);
        $templates[] = $tpl;
    }

    sendJsonResponse([
        'templates' => $templates,
        'user_purchases_count' => count($purchasedTemplateSlugs)
    ]);
}

// -------------------------------------------------------------
// POST: Deploy Free Template, Dummy Sandbox Checkout, or Promo Code
// -------------------------------------------------------------
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $input['action'] ?? '';
    $templateSlug = trim($input['slug'] ?? ($input['template_slug'] ?? ''));
    $templateId = (int)($input['template_id'] ?? 0);

    // Resolve template
    if (!empty($templateSlug)) {
        $stmt = $pdo->prepare("SELECT * FROM `workspace_templates` WHERE slug = :s AND is_active = 1 LIMIT 1");
        $stmt->execute([':s' => $templateSlug]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM `workspace_templates` WHERE id = :id AND is_active = 1 LIMIT 1");
        $stmt->execute([':id' => $templateId]);
    }
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        sendJsonResponse(['error' => 'Target workspace blueprint was not found.'], 404);
    }

    // Action 1: Deploy Free Template
    if ($action === 'deploy_free') {
        if ($template['tier'] !== 'free') {
            sendJsonResponse(['error' => 'This is a premium Pro workspace. Please complete checkout or redeem an access code to unlock it.'], 400);
        }

        $txId = 'FREE-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
        $res = provisionWorkspaceFromTemplate($pdo, $userId, $template, 'free_claim', 0.00, $txId);

        if (!$res['success']) {
            sendJsonResponse(['error' => $res['error'] ?? 'Failed to deploy workspace'], 500);
        }

        sendJsonResponse([
            'success' => true,
            'message' => "🎉 Workspace '{$template['title']}' deployed successfully with {$res['venture']['executives_count']} AI Specialists!",
            'venture' => $res['venture']
        ], 201);
    }

    // Action 2: Dummy Sandbox Payment Checkout
    if ($action === 'dummy_checkout') {
        $price = (float)$template['price'];
        $currency = $template['currency'] ?: 'USD';
        $dummyCardLast4 = preg_replace('/\D/', '', $input['card_last4'] ?? '4242');
        if (strlen($dummyCardLast4) < 4) $dummyCardLast4 = '4242';

        $txId = 'SIM-SANDBOX-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));

        $res = provisionWorkspaceFromTemplate($pdo, $userId, $template, "sandbox_card_{$dummyCardLast4}", $price, $txId);

        if (!$res['success']) {
            sendJsonResponse(['error' => $res['error'] ?? 'Failed to complete transaction'], 500);
        }

        sendJsonResponse([
            'success' => true,
            'message' => "💳 Test payment of \${$price} {$currency} simulated successfully! '{$template['title']}' is now live in your workspaces.",
            'transaction_id' => $txId,
            'amount_paid' => $price,
            'currency' => $currency,
            'venture' => $res['venture']
        ], 201);
    }

    // Action 3: Redeem Promo / License Code
    if ($action === 'redeem_promo') {
        $code = strtoupper(trim($input['promo_code'] ?? ''));

        // Whitelisted test / promotional codes
        $validCodes = ['VIPPRO2026', 'LAUNCHFREE', 'RUXTPRO', 'DISCOUNT100', 'FOUNDER2026', 'GEMINI38'];

        if (empty($code) || !in_array($code, $validCodes)) {
            sendJsonResponse(['error' => 'Invalid or expired promo code. Please enter a valid VIP/license code (e.g. VIPPRO2026 or LAUNCHFREE).'], 400);
        }

        $txId = 'PROMO-' . $code . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $res = provisionWorkspaceFromTemplate($pdo, $userId, $template, "promo_{$code}", 0.00, $txId);

        if (!$res['success']) {
            sendJsonResponse(['error' => $res['error'] ?? 'Failed to activate with promo code'], 500);
        }

        sendJsonResponse([
            'success' => true,
            'message' => "🎁 Code '{$code}' applied! '{$template['title']}' has been unlocked and added to your workspaces.",
            'transaction_id' => $txId,
            'venture' => $res['venture']
        ], 201);
    }

    sendJsonResponse(['error' => 'Invalid action specified'], 400);
}

sendJsonResponse(['error' => 'Method not allowed'], 405);
