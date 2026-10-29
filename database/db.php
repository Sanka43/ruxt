<?php
// database/db.php - Universal Database Connector (MySQL / MariaDB & SQLite)
require_once __DIR__ . '/config.php';

$driver = defined('DB_DRIVER') ? strtolower(DB_DRIVER) : 'mysql';

try {
    if ($driver === 'mysql') {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_TIMEOUT => 15,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (PDOException $e) {
            // If the database does not exist yet (error 1049), auto-create it
            if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                $serverPdo = new PDO(
                    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_TIMEOUT => 15,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]
                );
                $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_TIMEOUT => 15,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                    ]
                );
            } else {
                throw $e;
            }
        }
    } else {
        // SQLite
        $sqlitePath = defined('DB_SQLITE_PATH') ? DB_SQLITE_PATH : (__DIR__ . '/assistant.db');
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_TIMEOUT => 15,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        $pdo->exec("PRAGMA journal_mode = WAL;");
        $pdo->exec("PRAGMA busy_timeout = 10000;");
    }

    // Helper to get column names in a table
    function getDbTableColumns($pdo, $table) {
        try {
            $currentDriver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($currentDriver === 'sqlite') {
                $cols = $pdo->query("PRAGMA table_info(`{$table}`)")->fetchAll(PDO::FETCH_ASSOC);
                return array_column($cols, 'name');
            } else {
                $cols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
                return array_column($cols, 'Field');
            }
        } catch (\Throwable $e) {
            return [];
        }
    }

    // Helper to check if a table exists
    function dbTableExists($pdo, $table) {
        try {
            $currentDriver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($currentDriver === 'sqlite') {
                $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = :t");
                $stmt->execute([':t' => $table]);
                return (bool)$stmt->fetchColumn();
            } else {
                $stmt = $pdo->prepare("SHOW TABLES LIKE :t");
                $stmt->execute([':t' => $table]);
                return (bool)$stmt->fetchColumn();
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    // Initialize Schema for MySQL vs SQLite
    if ($driver === 'mysql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `settings` (
                `key` VARCHAR(191) PRIMARY KEY,
                `value` LONGTEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `sessions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `bot_id` INT DEFAULT 1,
                `executive_id` INT DEFAULT 1,
                `venture_id` INT DEFAULT NULL,
                `project_id` INT DEFAULT NULL,
                `project_name` VARCHAR(255) DEFAULT NULL,
                `user_id` INT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `messages` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `session_id` INT NOT NULL,
                `role` VARCHAR(50) NOT NULL,
                `content` LONGTEXT NOT NULL,
                `discussion_id` INT DEFAULT NULL,
                `workspace_id` INT DEFAULT NULL,
                `workspace_data` LONGTEXT DEFAULT NULL,
                `work_preview_id` INT DEFAULT NULL,
                `actions_data` LONGTEXT DEFAULT NULL,
                `attachments` LONGTEXT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_messages_session_id` (`session_id`),
                CONSTRAINT `fk_messages_session` FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `notes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `content` LONGTEXT DEFAULT NULL,
                `is_done` TINYINT DEFAULT 0,
                `due_date` VARCHAR(100) DEFAULT NULL,
                `assigned_to` VARCHAR(255) DEFAULT NULL,
                `assigned_avatar` TEXT DEFAULT NULL,
                `priority` VARCHAR(50) DEFAULT 'Medium',
                `status` VARCHAR(50) DEFAULT 'todo',
                `discussion_id` INT DEFAULT NULL,
                `venture_id` INT DEFAULT NULL,
                `project_id` INT DEFAULT NULL,
                `project_name` VARCHAR(255) DEFAULT NULL,
                `user_id` INT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `memories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `category` VARCHAR(100) DEFAULT 'general',
                `content` LONGTEXT NOT NULL,
                `user_id` INT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `bots` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `role` VARCHAR(255) NOT NULL,
                `icon` VARCHAR(100) DEFAULT 'bot',
                `color` VARCHAR(50) DEFAULT 'indigo',
                `system_prompt` LONGTEXT NOT NULL,
                `is_system` TINYINT DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `executives` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `department` VARCHAR(255) NOT NULL,
                `avatar_url` TEXT NOT NULL,
                `bio` TEXT DEFAULT NULL,
                `personality` TEXT DEFAULT NULL,
                `system_prompt` LONGTEXT NOT NULL,
                `is_system` TINYINT DEFAULT 0,
                `is_chief_of_staff` TINYINT DEFAULT 0,
                `venture_id` INT DEFAULT 1,
                `voice_id` VARCHAR(100) DEFAULT 'nova',
                `voice_gender` VARCHAR(50) DEFAULT 'female',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `board_meetings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `venture_id` INT DEFAULT NULL,
                `user_id` INT DEFAULT NULL,
                `title` VARCHAR(255) NOT NULL,
                `agenda` LONGTEXT NOT NULL,
                `status` VARCHAR(50) DEFAULT 'completed',
                `executive_summary` LONGTEXT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `meeting_dialogues` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `meeting_id` INT NOT NULL,
                `executive_id` INT DEFAULT NULL,
                `speaker_name` VARCHAR(255) NOT NULL,
                `speaker_title` VARCHAR(255) NOT NULL,
                `avatar_url` TEXT NOT NULL,
                `content` LONGTEXT NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_meeting_id` (`meeting_id`),
                CONSTRAINT `fk_meeting_dialogues_meeting` FOREIGN KEY (`meeting_id`) REFERENCES `board_meetings`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `ventures` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `domain` VARCHAR(255) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `icon` VARCHAR(100) DEFAULT 'briefcase',
                `color` VARCHAR(50) DEFAULT 'amber',
                `user_id` INT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `team_discussions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `venture_id` INT DEFAULT NULL,
                `session_id` INT DEFAULT NULL,
                `user_id` INT DEFAULT NULL,
                `project_id` INT DEFAULT NULL,
                `project_name` VARCHAR(255) DEFAULT NULL,
                `title` VARCHAR(255) NOT NULL,
                `user_prompt` LONGTEXT NOT NULL,
                `summary` LONGTEXT DEFAULT NULL,
                `status` VARCHAR(50) DEFAULT 'completed',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `team_discussion_dialogues` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `discussion_id` INT NOT NULL,
                `executive_id` INT DEFAULT NULL,
                `speaker_name` VARCHAR(255) NOT NULL,
                `speaker_title` VARCHAR(255) NOT NULL,
                `avatar_url` TEXT NOT NULL,
                `content` LONGTEXT NOT NULL,
                `turn_type` VARCHAR(50) DEFAULT 'speech',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_discussion_id` (`discussion_id`),
                CONSTRAINT `fk_team_dialogues_disc` FOREIGN KEY (`discussion_id`) REFERENCES `team_discussions`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `projects` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `venture_id` INT DEFAULT NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `status` VARCHAR(50) DEFAULT 'active',
                `color` VARCHAR(50) DEFAULT 'indigo',
                `icon` VARCHAR(100) DEFAULT 'folder-kanban',
                `user_id` INT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `member_work_previews` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `session_id` INT DEFAULT NULL,
                `message_id` INT DEFAULT NULL,
                `note_id` INT DEFAULT NULL,
                `executive_id` INT NOT NULL,
                `executive_name` VARCHAR(255) NOT NULL,
                `task_title` VARCHAR(255) NOT NULL,
                `target_system` VARCHAR(255) NOT NULL,
                `status` VARCHAR(50) DEFAULT 'in_progress',
                `progress_percent` INT DEFAULT 65,
                `checklist_json` LONGTEXT DEFAULT NULL,
                `terminal_logs` LONGTEXT DEFAULT NULL,
                `visual_data_json` LONGTEXT DEFAULT NULL,
                `findings_markdown` LONGTEXT DEFAULT NULL,
                `preview_url` TEXT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `token_usage` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `model` VARCHAR(100) NOT NULL,
                `feature` VARCHAR(100) DEFAULT 'chat',
                `prompt_tokens` INT DEFAULT 0,
                `completion_tokens` INT DEFAULT 0,
                `thoughts_tokens` INT DEFAULT 0,
                `total_tokens` INT DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_token_usage_created_at` (`created_at`),
                INDEX `idx_token_usage_model` (`model`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(191) UNIQUE NOT NULL,
                `email` VARCHAR(191) UNIQUE NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `avatar_url` TEXT DEFAULT NULL,
                `role` VARCHAR(50) DEFAULT 'user',
                `remember_token` VARCHAR(191) DEFAULT NULL,
                `job_field` VARCHAR(255) DEFAULT NULL,
                `specialized_role` VARCHAR(255) DEFAULT NULL,
                `onboarding_completed` TINYINT DEFAULT 0,
                `onboarding_data` LONGTEXT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `last_login` DATETIME DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } else {
        // SQLite
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT
            );

            CREATE TABLE IF NOT EXISTS sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                bot_id INTEGER DEFAULT 1,
                executive_id INTEGER DEFAULT 1,
                venture_id INTEGER,
                project_id INTEGER,
                project_name TEXT,
                user_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NOT NULL,
                role TEXT NOT NULL,
                content TEXT NOT NULL,
                discussion_id INTEGER,
                workspace_id INTEGER,
                workspace_data TEXT,
                work_preview_id INTEGER,
                actions_data TEXT,
                attachments TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                content TEXT,
                is_done INTEGER DEFAULT 0,
                due_date TEXT,
                assigned_to TEXT,
                assigned_avatar TEXT,
                priority TEXT DEFAULT 'Medium',
                status TEXT DEFAULT 'todo',
                discussion_id INTEGER,
                venture_id INTEGER,
                project_id INTEGER,
                project_name TEXT,
                user_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS memories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category TEXT DEFAULT 'general',
                content TEXT NOT NULL,
                user_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS bots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                role TEXT NOT NULL,
                icon TEXT DEFAULT 'bot',
                color TEXT DEFAULT 'indigo',
                system_prompt TEXT NOT NULL,
                is_system INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS executives (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                title TEXT NOT NULL,
                department TEXT NOT NULL,
                avatar_url TEXT NOT NULL,
                bio TEXT,
                personality TEXT,
                system_prompt TEXT NOT NULL,
                is_system INTEGER DEFAULT 0,
                is_chief_of_staff INTEGER DEFAULT 0,
                venture_id INTEGER,
                voice_id TEXT DEFAULT 'nova',
                voice_gender TEXT DEFAULT 'female',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS board_meetings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                venture_id INTEGER,
                user_id INTEGER,
                title TEXT NOT NULL,
                agenda TEXT NOT NULL,
                status TEXT DEFAULT 'completed',
                executive_summary TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS meeting_dialogues (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                meeting_id INTEGER NOT NULL,
                executive_id INTEGER,
                speaker_name TEXT NOT NULL,
                speaker_title TEXT NOT NULL,
                avatar_url TEXT NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (meeting_id) REFERENCES board_meetings(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS ventures (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                domain TEXT NOT NULL,
                description TEXT,
                icon TEXT DEFAULT 'briefcase',
                color TEXT DEFAULT 'amber',
                user_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS team_discussions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                venture_id INTEGER,
                session_id INTEGER,
                user_id INTEGER,
                project_id INTEGER,
                project_name TEXT,
                title TEXT NOT NULL,
                user_prompt TEXT NOT NULL,
                summary TEXT,
                status TEXT DEFAULT 'completed',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS team_discussion_dialogues (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                discussion_id INTEGER NOT NULL,
                executive_id INTEGER,
                speaker_name TEXT NOT NULL,
                speaker_title TEXT NOT NULL,
                avatar_url TEXT NOT NULL,
                content TEXT NOT NULL,
                turn_type TEXT DEFAULT 'speech',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (discussion_id) REFERENCES team_discussions(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                venture_id INTEGER,
                name TEXT NOT NULL,
                description TEXT,
                status TEXT DEFAULT 'active',
                color TEXT DEFAULT 'indigo',
                icon TEXT DEFAULT 'folder-kanban',
                user_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS member_work_previews (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER,
                message_id INTEGER,
                note_id INTEGER,
                executive_id INTEGER NOT NULL,
                executive_name TEXT NOT NULL,
                task_title TEXT NOT NULL,
                target_system TEXT NOT NULL,
                status TEXT DEFAULT 'in_progress',
                progress_percent INTEGER DEFAULT 65,
                checklist_json TEXT,
                terminal_logs TEXT,
                visual_data_json TEXT,
                findings_markdown TEXT,
                preview_url TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS token_usage (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                model TEXT NOT NULL,
                feature TEXT DEFAULT 'chat',
                prompt_tokens INTEGER DEFAULT 0,
                completion_tokens INTEGER DEFAULT 0,
                thoughts_tokens INTEGER DEFAULT 0,
                total_tokens INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX IF NOT EXISTS idx_token_usage_created_at ON token_usage(created_at);
            CREATE INDEX IF NOT EXISTS idx_token_usage_model ON token_usage(model);

            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                avatar_url TEXT DEFAULT '',
                role TEXT DEFAULT 'user',
                remember_token TEXT DEFAULT NULL,
                job_field TEXT DEFAULT NULL,
                specialized_role TEXT DEFAULT NULL,
                onboarding_completed INTEGER DEFAULT 0,
                onboarding_data TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME
            );
        ");
    }

    // Auto-migration: Ensure attachments column exists on messages table
    $messagesColumns = getDbTableColumns($pdo, 'messages');
    if (!empty($messagesColumns) && !in_array('attachments', $messagesColumns)) {
        try {
            $currentDriver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($currentDriver === 'sqlite') {
                $pdo->exec("ALTER TABLE messages ADD COLUMN attachments TEXT DEFAULT NULL");
            } else {
                $pdo->exec("ALTER TABLE `messages` ADD COLUMN `attachments` LONGTEXT DEFAULT NULL");
            }
        } catch (\Throwable $e) {
            error_log("Failed to add attachments column to messages: " . $e->getMessage());
        }
    }

    // Seed default projects if empty
    $projCount = $pdo->query("SELECT COUNT(*) FROM `projects`")->fetchColumn();
    if ($projCount == 0) {
        $pdo->exec("
            INSERT INTO `projects` (`id`, `venture_id`, `name`, `description`, `color`, `icon`, `status`) VALUES 
            (1, 1, 'General Operations', 'Primary workspace roadmap and execution deliverables', 'indigo', 'folder-kanban', 'active');
        ");
    }

    // Seed default bots if empty
    $botCount = $pdo->query("SELECT COUNT(*) FROM `bots`")->fetchColumn();
    if ($botCount == 0) {
        $defaultBots = [
            [
                'id' => 1,
                'name' => 'RuXt (Master AI)',
                'role' => 'Personal Chief of Staff & Orchestrator',
                'icon' => 'sparkles',
                'color' => 'indigo',
                'system_prompt' => "You are RuXt, the user's primary personal AI assistant and Chief of Staff. You know the user deeply, keep their schedule organized, answer questions in English or any language the user speaks, and coordinate other role bots.",
                'is_system' => 1
            ],
            [
                'id' => 2,
                'name' => 'DevPro AI',
                'role' => 'Senior Full-Stack Architect & Tech Lead',
                'icon' => 'code',
                'color' => 'emerald',
                'system_prompt' => "You are DevPro AI, a Senior Full-Stack Software Architect and Tech Lead. You write rock-solid, production-ready code, diagnose tricky bugs, design scalable database schemas, explain modern software design patterns, and provide practical developer guidance tailored to the user's tech stack.",
                'is_system' => 1
            ],
            [
                'id' => 3,
                'name' => 'ClientBridge AI',
                'role' => 'Client Communications & Proposal Specialist',
                'icon' => 'briefcase',
                'color' => 'amber',
                'system_prompt' => "You are ClientBridge AI, a specialist in freelance operations, client communication, and deal-closing. You write winning freelance proposals, handle challenging client requests gracefully, draft clear professional emails, and help price and scope projects.",
                'is_system' => 1
            ],
            [
                'id' => 4,
                'name' => 'GrowthCraft AI',
                'role' => 'Digital Marketing & Content Strategist',
                'icon' => 'trending-up',
                'color' => 'purple',
                'system_prompt' => "You are GrowthCraft AI, an elite digital marketing and copy strategist. You produce high-converting landing page copy, viral social media posts, SEO-optimized outlines, and launch plans tailored to the user's products and services.",
                'is_system' => 1
            ]
        ];

        $insBot = $pdo->prepare("INSERT INTO `bots` (`id`, `name`, `role`, `icon`, `color`, `system_prompt`, `is_system`) VALUES (:id, :name, :role, :icon, :color, :system_prompt, :is_system)");
        foreach ($defaultBots as $b) {
            $insBot->execute($b);
        }
    }

    // Seed default settings if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM `settings`");
    if ($stmt->fetchColumn() == 0) {
        $defaultSettings = [
            'gemini_api_key' => '',
            'model' => 'gemini-3.1-flash-lite',
            'company_name' => 'Enterprises',
            'user_name' => 'CEO',
            'assistant_name' => 'RuXt',
            'system_instruction' => "You are RuXt, Chief of Staff and lead Private Assistant. You advise your CEO with executive precision."
        ];

        foreach ($defaultSettings as $k => $v) {
            setSetting($k, $v);
        }
    }

    // Ensure voice system settings exist
    $voiceDefaults = [
        'tts_provider' => 'browser',
        'elevenlabs_api_key' => '',
        'openai_api_key' => '',
        'voice_input_lang' => 'en-US',
        'voice_auto_speak' => '0'
    ];
    $checkSettingStmt = $pdo->prepare("SELECT COUNT(*) FROM `settings` WHERE `key` = :key");
    foreach ($voiceDefaults as $vk => $vv) {
        $checkSettingStmt->execute([':key' => $vk]);
        if ($checkSettingStmt->fetchColumn() == 0) {
            setSetting($vk, $vv);
        }
    }

    // Ensure RuXt exists as Chief of Staff & Lead Private Assistant
    $hasChiefOfStaff = $pdo->query("SELECT COUNT(*) FROM `executives` WHERE is_chief_of_staff = 1")->fetchColumn();
    if ($hasChiefOfStaff == 0) {
        $ruxtData = [
            'name' => 'RuXt',
            'title' => 'Chief of Staff & Lead Private Assistant',
            'department' => 'Office of the CEO',
            'avatar_url' => 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80',
            'bio' => 'Your trusted Chief of Staff and primary Private Assistant. Oversees the Office of the CEO, translates your business vision into structured Work Breakdown Packages (WBS), delegates mandates across company ventures, and manages corporate execution.',
            'personality' => 'Strategic, proactive, fiercely loyal, highly organized, master of work delegation, fully multilingual.',
            'is_system' => 1,
            'is_chief_of_staff' => 1,
            'venture_id' => 1,
            'voice_id' => 'nova',
            'voice_gender' => 'female',
            'system_prompt' => "You are RuXt, Chief of Staff and the primary Executive Private Assistant to the CEO/Founder (the user). You are the CEO's right-hand operator and commander of the executive enterprise across all company ventures."
        ];

        $insChief = $pdo->prepare("
            INSERT INTO `executives` 
            (`name`, `title`, `department`, `avatar_url`, `bio`, `personality`, `system_prompt`, `is_system`, `is_chief_of_staff`, `venture_id`, `voice_id`, `voice_gender`) 
            VALUES (:name, :title, :department, :avatar_url, :bio, :personality, :system_prompt, :is_system, :is_chief_of_staff, :venture_id, :voice_id, :voice_gender)
        ");
        $insChief->execute($ruxtData);
    }

    // Seed default venture if empty
    $ventureCount = $pdo->query("SELECT COUNT(*) FROM `ventures`")->fetchColumn();
    if ($ventureCount == 0) {
        $defaultVentures = [
            [
                'id' => 1,
                'name' => 'Primary Workspace',
                'domain' => 'General Operations',
                'description' => 'Main workspace for private AI assistance, strategic planning, and operational execution.',
                'icon' => 'briefcase',
                'color' => 'indigo'
            ]
        ];

        $insVenture = $pdo->prepare("INSERT INTO `ventures` (`id`, `name`, `domain`, `description`, `icon`, `color`) VALUES (:id, :name, :domain, :description, :icon, :color)");
        foreach ($defaultVentures as $v) {
            $insVenture->execute($v);
        }
    }

} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Database connection failed: ' . $e->getMessage(),
        'hint' => 'Make sure MySQL is started in your XAMPP Control Panel.'
    ]);
    exit;
}

function getSetting($key, $default = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT `value` FROM `settings` WHERE `key` = :key LIMIT 1");
        $stmt->execute([':key' => $key]);
        $res = $stmt->fetch();
        return $res ? $res['value'] : $default;
    } catch (\Throwable $e) {
        return $default;
    }
}

function setSetting($key, $value) {
    global $pdo;
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare("INSERT INTO `settings` (`key`, `value`) VALUES (:key, :value) ON CONFLICT(`key`) DO UPDATE SET `value` = :value");
        } else {
            $stmt = $pdo->prepare("INSERT INTO `settings` (`key`, `value`) VALUES (:key, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        }
        return $stmt->execute([':key' => $key, ':value' => $value]);
    } catch (\Throwable $e) {
        error_log("setSetting error: " . $e->getMessage());
        return false;
    }
}

/**
 * Record token usage for an LLM API execution
 */
function recordTokenUsage($model, $promptTokens, $completionTokens, $totalTokens, $feature = 'chat', $thoughtsTokens = 0) {
    global $pdo;
    try {
        if ($totalTokens <= 0) {
            $totalTokens = (int)$promptTokens + (int)$completionTokens + (int)$thoughtsTokens;
        }
        $stmt = $pdo->prepare("
            INSERT INTO `token_usage` (`model`, `feature`, `prompt_tokens`, `completion_tokens`, `thoughts_tokens`, `total_tokens`, `created_at`)
            VALUES (:model, :feature, :prompt_tokens, :completion_tokens, :thoughts_tokens, :total_tokens, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([
            ':model' => $model,
            ':feature' => $feature,
            ':prompt_tokens' => (int)$promptTokens,
            ':completion_tokens' => (int)$completionTokens,
            ':thoughts_tokens' => (int)$thoughtsTokens,
            ':total_tokens' => (int)$totalTokens
        ]);
    } catch (\Throwable $e) {
        error_log("Failed to record token usage: " . $e->getMessage());
        return false;
    }
}

/**
 * Get aggregated token usage analytics for dashboard and settings
 */
function getTokenUsageStats() {
    global $pdo;
    try {
        $totalsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_requests,
                COALESCE(SUM(total_tokens), 0) as total_tokens,
                COALESCE(SUM(prompt_tokens), 0) as prompt_tokens,
                COALESCE(SUM(completion_tokens), 0) as completion_tokens,
                COALESCE(SUM(thoughts_tokens), 0) as thoughts_tokens
            FROM `token_usage`
        ");
        $totals = $totalsStmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_requests' => 0,
            'total_tokens' => 0,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'thoughts_tokens' => 0
        ];

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $dateCondition = ($driver === 'sqlite')
            ? "date(created_at, 'localtime') = date('now', 'localtime')"
            : "DATE(created_at) = CURDATE()";

        $todayStmt = $pdo->query("
            SELECT 
                COUNT(*) as today_requests,
                COALESCE(SUM(total_tokens), 0) as today_tokens
            FROM `token_usage`
            WHERE {$dateCondition}
        ");
        $today = $todayStmt->fetch(PDO::FETCH_ASSOC) ?: [
            'today_requests' => 0,
            'today_tokens' => 0
        ];

        $modelStmt = $pdo->query("
            SELECT 
                model,
                COUNT(*) as requests,
                COALESCE(SUM(total_tokens), 0) as total_tokens,
                COALESCE(SUM(prompt_tokens), 0) as prompt_tokens,
                COALESCE(SUM(completion_tokens), 0) as completion_tokens
            FROM `token_usage`
            GROUP BY model
            ORDER BY total_tokens DESC
        ");
        $models = $modelStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalTokens = (int)$totals['total_tokens'];
        foreach ($models as &$m) {
            $m['percentage'] = ($totalTokens > 0) ? round(((int)$m['total_tokens'] / $totalTokens) * 100, 1) : 0;
            $m['requests'] = (int)$m['requests'];
            $m['total_tokens'] = (int)$m['total_tokens'];
            $m['prompt_tokens'] = (int)$m['prompt_tokens'];
            $m['completion_tokens'] = (int)$m['completion_tokens'];
        }

        $featureStmt = $pdo->query("
            SELECT 
                feature,
                COUNT(*) as requests,
                COALESCE(SUM(total_tokens), 0) as total_tokens
            FROM `token_usage`
            GROUP BY feature
            ORDER BY total_tokens DESC
        ");
        $features = $featureStmt->fetchAll(PDO::FETCH_ASSOC);

        $lastStmt = $pdo->query("
            SELECT model, feature, prompt_tokens, completion_tokens, thoughts_tokens, total_tokens, created_at
            FROM `token_usage`
            ORDER BY id DESC
            LIMIT 1
        ");
        $lastRequest = $lastStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return [
            'total_requests' => (int)$totals['total_requests'],
            'total_tokens' => (int)$totals['total_tokens'],
            'prompt_tokens' => (int)$totals['prompt_tokens'],
            'completion_tokens' => (int)$totals['completion_tokens'],
            'thoughts_tokens' => (int)$totals['thoughts_tokens'],
            'today_requests' => (int)$today['today_requests'],
            'today_tokens' => (int)$today['today_tokens'],
            'models' => $models,
            'features' => $features,
            'last_request' => $lastRequest
        ];
    } catch (\Throwable $e) {
        error_log("Failed to fetch token usage: " . $e->getMessage());
        return [
            'total_requests' => 0,
            'total_tokens' => 0,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'thoughts_tokens' => 0,
            'today_requests' => 0,
            'today_tokens' => 0,
            'models' => [],
            'features' => [],
            'last_request' => null
        ];
    }
}

/**
 * Reset token usage counter
 */
function resetTokenUsage() {
    global $pdo;
    try {
        $pdo->exec("DELETE FROM `token_usage`");
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            try { $pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'token_usage'"); } catch (\Throwable $e) {}
        } else {
            try { $pdo->exec("ALTER TABLE `token_usage` AUTO_INCREMENT = 1"); } catch (\Throwable $e) {}
        }
        return true;
    } catch (\Throwable $e) {
        error_log("Failed to reset token usage: " . $e->getMessage());
        return false;
    }
}

/**
 * User & Authentication Helper Functions
 */
function getUserById($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, name, avatar_url, role, remember_token, job_field, specialized_role, onboarding_completed, onboarding_data, created_at, last_login FROM `users` WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (\Throwable $e) {
        error_log("getUserById error: " . $e->getMessage());
        return null;
    }
}

function getUserByUsernameOrEmail($identifier) {
    global $pdo;
    try {
        $clean = trim((string)$identifier);
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE LOWER(username) = LOWER(:id1) OR LOWER(email) = LOWER(:id2) LIMIT 1");
        $stmt->execute([':id1' => $clean, ':id2' => $clean]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (\Throwable $e) {
        error_log("getUserByUsernameOrEmail error: " . $e->getMessage());
        return null;
    }
}

function getUserByRememberToken($token) {
    global $pdo;
    try {
        if (empty($token)) return null;
        $stmt = $pdo->prepare("SELECT id, username, email, name, avatar_url, role, remember_token, job_field, specialized_role, onboarding_completed, onboarding_data, created_at, last_login FROM `users` WHERE remember_token = :t LIMIT 1");
        $stmt->execute([':t' => (string)$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (\Throwable $e) {
        return null;
    }
}

function countUsers() {
    global $pdo;
    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    } catch (\Throwable $e) {
        return 0;
    }
}

function updateUserLastLogin($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE `users` SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute([':id' => (int)$userId]);
    } catch (\Throwable $e) {
        return false;
    }
}

function updateUserRememberToken($userId, $token) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE `users` SET remember_token = :t WHERE id = :id");
        return $stmt->execute([':t' => $token, ':id' => (int)$userId]);
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Update user onboarding state and specialized role
 */
function updateUserOnboarding($userId, $field, $specializedRole, $onboardingData = []) {
    global $pdo;
    try {
        $dataJson = !empty($onboardingData) ? json_encode($onboardingData, JSON_UNESCAPED_UNICODE) : null;
        $stmt = $pdo->prepare("
            UPDATE `users` 
            SET job_field = :field, 
                specialized_role = :role, 
                onboarding_completed = 1,
                onboarding_data = :data
            WHERE id = :id
        ");
        return $stmt->execute([
            ':field' => trim($field),
            ':role' => trim($specializedRole),
            ':data' => $dataJson,
            ':id' => (int)$userId
        ]);
    } catch (\Throwable $e) {
        error_log("updateUserOnboarding error: " . $e->getMessage());
        return false;
    }
}

/**
 * Reset user onboarding so they can reconfigure RuXt's role
 */
function resetUserOnboarding($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE `users` SET onboarding_completed = 0 WHERE id = :id");
        return $stmt->execute([':id' => (int)$userId]);
    } catch (\Throwable $e) {
        error_log("resetUserOnboarding error: " . $e->getMessage());
        return false;
    }
}
