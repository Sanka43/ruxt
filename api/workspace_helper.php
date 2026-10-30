<?php
// api/workspace_helper.php - Workspace Provisioning Helper Function
require_once __DIR__ . '/../config.php';

/**
 * Helper function to clone a template into the user's workspace
 */
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
