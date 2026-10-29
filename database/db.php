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

            CREATE TABLE IF NOT EXISTS `workspace_templates` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `slug` VARCHAR(100) UNIQUE NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `domain` VARCHAR(255) NOT NULL,
                `description` LONGTEXT NOT NULL,
                `icon` VARCHAR(100) DEFAULT 'briefcase',
                `color` VARCHAR(50) DEFAULT 'indigo',
                `tier` VARCHAR(50) DEFAULT 'free',
                `price` DECIMAL(10, 2) DEFAULT 0.00,
                `currency` VARCHAR(10) DEFAULT 'USD',
                `badge` VARCHAR(50) DEFAULT NULL,
                `features_json` LONGTEXT NOT NULL,
                `executives_json` LONGTEXT NOT NULL,
                `projects_json` LONGTEXT NOT NULL,
                `tasks_json` LONGTEXT NOT NULL,
                `prompts_json` LONGTEXT NOT NULL,
                `is_active` TINYINT DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `workspace_purchases` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `template_id` INT NOT NULL,
                `template_slug` VARCHAR(100) NOT NULL,
                `amount_paid` DECIMAL(10, 2) DEFAULT 0.00,
                `currency` VARCHAR(10) DEFAULT 'USD',
                `payment_method` VARCHAR(50) NOT NULL,
                `transaction_id` VARCHAR(191) DEFAULT NULL,
                `status` VARCHAR(50) DEFAULT 'completed',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_user_template` (`user_id`, `template_id`)
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

            CREATE TABLE IF NOT EXISTS workspace_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                title TEXT NOT NULL,
                domain TEXT NOT NULL,
                description TEXT NOT NULL,
                icon TEXT DEFAULT 'briefcase',
                color TEXT DEFAULT 'indigo',
                tier TEXT DEFAULT 'free',
                price REAL DEFAULT 0.00,
                currency TEXT DEFAULT 'USD',
                badge TEXT DEFAULT NULL,
                features_json TEXT NOT NULL,
                executives_json TEXT NOT NULL,
                projects_json TEXT NOT NULL,
                tasks_json TEXT NOT NULL,
                prompts_json TEXT NOT NULL,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS workspace_purchases (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                template_id INTEGER NOT NULL,
                template_slug TEXT NOT NULL,
                amount_paid REAL DEFAULT 0.00,
                currency TEXT DEFAULT 'USD',
                payment_method TEXT NOT NULL,
                transaction_id TEXT,
                status TEXT DEFAULT 'completed',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX IF NOT EXISTS idx_workspace_purchases_user ON workspace_purchases(user_id, template_id);
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

    // Auto-migration: Ensure google_id column exists on users table
    $usersColumns = getDbTableColumns($pdo, 'users');
    if (!empty($usersColumns) && !in_array('google_id', $usersColumns)) {
        try {
            $currentDriver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($currentDriver === 'sqlite') {
                $pdo->exec("ALTER TABLE users ADD COLUMN google_id TEXT DEFAULT NULL");
                $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_google_id ON users(google_id)");
            } else {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `google_id` VARCHAR(191) NULL DEFAULT NULL AFTER `email`");
                $pdo->exec("ALTER TABLE `users` ADD UNIQUE KEY `google_id` (`google_id`)");
            }
        } catch (\Throwable $e) {
            error_log("Failed to add google_id column to users: " . $e->getMessage());
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

    // Seed default Predefined Workspace Templates if empty
    $templateCount = $pdo->query("SELECT COUNT(*) FROM `workspace_templates`")->fetchColumn();
    if ($templateCount == 0) {
        $starterTemplates = [
            [
                'slug' => 'youtube-creator',
                'title' => 'YouTube & Content Creator Media Hub',
                'domain' => 'Content Creation & Digital Media',
                'description' => 'End-to-end production engine for YouTubers, vloggers, and digital creators. Scale your content pipeline, optimize thumbnails & CTR, and close 5-figure sponsorship deals.',
                'icon' => 'video',
                'color' => 'rose',
                'tier' => 'free',
                'price' => 0.00,
                'currency' => 'USD',
                'badge' => 'FREE STARTER',
                'features_json' => json_encode([
                    'Instant YouTube Script & Retention Hook Formulation',
                    'High-CTR Thumbnail & Title Packaging Psychology',
                    'Sponsorship Pitch & Brand Deal Rate-Card Playbook',
                    'Multi-Platform Short-Form Repurposing (Shorts, Reels, TikTok)'
                ], JSON_UNESCAPED_UNICODE),
                'executives_json' => json_encode([
                    [
                        'name' => 'Leo Thorne',
                        'title' => 'Executive Producer & Channel Strategist',
                        'department' => 'Studio Management',
                        'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'YouTube algorithm analyst and programming director. Specializes in long-term channel positioning, viewer retention curves, and upload cadences.',
                        'personality' => 'Visionary, hyper-analytical, trend-forecasting, high energy.',
                        'voice_id' => 'nova',
                        'voice_gender' => 'female',
                        'system_prompt' => 'You are Leo Thorne, Executive Producer and Channel Strategist. You optimize YouTube video concepts, packaging, and content calendars for maximum organic reach and high subscriber conversion.'
                    ],
                    [
                        'name' => 'Mia Vance',
                        'title' => 'Lead Scriptwriter & Story Architect',
                        'department' => 'Content Production',
                        'avatar_url' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Award-winning narrative designer who structures high-retention video intros, tension-building story arcs, and unforgettable call-to-actions.',
                        'personality' => 'Master storyteller, concise, punchy pacing expert.',
                        'voice_id' => 'shimmer',
                        'voice_gender' => 'female',
                        'system_prompt' => 'You are Mia Vance, Lead Scriptwriter and Story Architect. You write irresistible hooks within the first 15 seconds of videos, write captivating outlines, and eliminate all viewer drop-off points.'
                    ],
                    [
                        'name' => 'Zack Sterling',
                        'title' => 'Thumbnail & Packaging Specialist',
                        'department' => 'Visual Arts & CTR',
                        'avatar_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Click-through rate psychologist obsessed with 3-element thumbnail composition, contrasting color theory, and curiosity gap titles.',
                        'personality' => 'Creative, data-driven, A/B testing fanatic.',
                        'voice_id' => 'echo',
                        'voice_gender' => 'male',
                        'system_prompt' => 'You are Zack Sterling, Thumbnail and Packaging Specialist. You design 3-element visual thumbnail concepts, formulate high-CTR titles, and ensure visual hierarchy wins on mobile feeds.'
                    ],
                    [
                        'name' => 'Aria Stone',
                        'title' => 'Brand Partnership & Sponsorship Lead',
                        'department' => 'Business Development',
                        'avatar_url' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Commercial agent negotiating integrated brand deals, calculating CPM floor rates, and crafting sponsor-friendly integration pitches.',
                        'personality' => 'Confident, diplomatic negotiator, revenue-focused.',
                        'voice_id' => 'alloy',
                        'voice_gender' => 'female',
                        'system_prompt' => 'You are Aria Stone, Brand Partnership and Sponsorship Lead. You negotiate high-paying sponsorships, draft media kits, and guide brand deal delivery without alienating core audiences.'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'projects_json' => json_encode([
                    [
                        'name' => 'Viral YouTube Pipeline & Content Calendar',
                        'description' => 'Flagship weekly production pipeline: Ideation, Scripting, Packaging, and Post-Production.',
                        'color' => 'rose',
                        'icon' => 'video',
                        'status' => 'active'
                    ],
                    [
                        'name' => 'Sponsor Outreach & Media Deck Overhaul',
                        'description' => 'Brand partnership media kit, rate cards, and cold outreach sequence to top tech sponsors.',
                        'color' => 'amber',
                        'icon' => 'briefcase',
                        'status' => 'active'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'tasks_json' => json_encode([
                    [
                        'title' => 'Draft 5 High-Retention Video Hooks for Next Release',
                        'content' => 'Write 5 alternative 15-second opening hooks focusing on curiosity gap and visual intrigue.',
                        'assigned_to' => 'Mia Vance',
                        'priority' => 'High',
                        'status' => 'in_progress',
                        'due_date' => 'In 2 days'
                    ],
                    [
                        'title' => 'Design 3 A/B Thumbnail Concepts with Emotion Focus',
                        'content' => 'Propose color palette, facial expression angle, and high-contrast background elements.',
                        'assigned_to' => 'Zack Sterling',
                        'priority' => 'High',
                        'status' => 'todo',
                        'due_date' => 'In 3 days'
                    ],
                    [
                        'title' => 'Compile Media Kit & Dedicated Sponsor Rate Card',
                        'content' => 'Summarize 30-day analytics, demographics, avg views, and 60-second integration price tiers.',
                        'assigned_to' => 'Aria Stone',
                        'priority' => 'Medium',
                        'status' => 'todo',
                        'due_date' => 'This week'
                    ],
                    [
                        'title' => 'Audit Audience Retention Graph on Last 3 Videos',
                        'content' => 'Identify common drop-off timestamps and create script pacing guidelines.',
                        'assigned_to' => 'Leo Thorne',
                        'priority' => 'Medium',
                        'status' => 'completed',
                        'due_date' => 'Done'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'prompts_json' => json_encode([
                    'Review my channel niche and propose 3 viral video titles with high search intent.',
                    'Draft a 60-second punchy integration script for a software brand sponsor.',
                    'Analyze why viewer retention drops at minute 2:30 and suggest fixes.'
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'slug' => 'digital-agency',
                'title' => 'Digital Marketing & Creative Agency',
                'domain' => 'Marketing & Client Growth',
                'description' => 'A powerhouse command center for digital agencies and creative consultancies. Accelerate client onboarding, optimize campaign ROAS, and produce high-converting ad copy.',
                'icon' => 'trending-up',
                'color' => 'purple',
                'tier' => 'free',
                'price' => 0.00,
                'currency' => 'USD',
                'badge' => 'FREE STARTER',
                'features_json' => json_encode([
                    'Full-Funnel Ad Campaign Architecture (Meta, Google, TikTok)',
                    'Conversion-Optimized Sales Copy & Landing Page Frameworks',
                    'SEO Content Strategy & Topic Clustering Engine',
                    'Client Monthly Reporting & ROAS Executive Dashboards'
                ], JSON_UNESCAPED_UNICODE),
                'executives_json' => json_encode([
                    [
                        'name' => 'Elena Rostova',
                        'title' => 'VP of Growth & Performance Marketing',
                        'department' => 'Growth Operations',
                        'avatar_url' => 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Ad spend strategist who has managed over $12M in paid media across Meta and Google with a relentless focus on Blended CAC and ROAS.',
                        'personality' => 'Data-obsessed, metrics-driven, aggressive ROAS optimizer.',
                        'voice_id' => 'nova',
                        'voice_gender' => 'female',
                        'system_prompt' => 'You are Elena Rostova, VP of Growth and Performance Marketing. You analyze customer acquisition funnels, structure budget allocation across ad platforms, and troubleshoot rising CPAs.'
                    ],
                    [
                        'name' => 'Julian Mercer',
                        'title' => 'Creative Director & Copy Chief',
                        'department' => 'Creative & Brand',
                        'avatar_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Direct-response copywriter behind 7-figure product launches and viral UGC creative frameworks.',
                        'personality' => 'Persuasive, emotional psychology copywriter, brand storyteller.',
                        'voice_id' => 'fable',
                        'voice_gender' => 'male',
                        'system_prompt' => 'You are Julian Mercer, Creative Director and Copy Chief. You formulate high-converting sales angles, direct-response ad scripts, landing page headlines, and email sequences.'
                    ],
                    [
                        'name' => 'Kavita Patel',
                        'title' => 'SEO & Organic Inbound Architect',
                        'department' => 'Search & Discovery',
                        'avatar_url' => 'https://images.unsplash.com/photo-1567532939604-b6b5b0db2604?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Technical search architect specializing in programmatic SEO, semantic keyword clustering, and authoritativeness engineering.',
                        'personality' => 'Analytical, meticulous, search intent mapping specialist.',
                        'voice_id' => 'shimmer',
                        'voice_gender' => 'female',
                        'system_prompt' => 'You are Kavita Patel, SEO and Organic Inbound Architect. You build scalable organic acquisition playbooks, optimize crawlability, and formulate high-intent content clusters.'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'projects_json' => json_encode([
                    [
                        'name' => 'Agency Inbound Client Acquisition Funnel',
                        'description' => 'Systematic client acquisition roadmap: Case studies, audit lead magnets, and discovery calls.',
                        'color' => 'purple',
                        'icon' => 'target',
                        'status' => 'active'
                    ],
                    [
                        'name' => 'Client Paid Traffic Scaling SOPs',
                        'description' => 'Standard operating procedures for onboarding, pixel setup, campaign testing, and monthly reporting.',
                        'color' => 'indigo',
                        'icon' => 'layers',
                        'status' => 'active'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'tasks_json' => json_encode([
                    [
                        'title' => 'Write 3-Part Retargeting Ad Angles for Flagship Client',
                        'content' => 'Create objections-handling ad copy focusing on social proof, risk reversal, and urgency.',
                        'assigned_to' => 'Julian Mercer',
                        'priority' => 'High',
                        'status' => 'in_progress',
                        'due_date' => 'Tomorrow'
                    ],
                    [
                        'title' => 'Perform Technical SEO Site Audit on Key Landing Pages',
                        'content' => 'Review Core Web Vitals, Schema markup, and internal link architecture.',
                        'assigned_to' => 'Kavita Patel',
                        'priority' => 'Medium',
                        'status' => 'todo',
                        'due_date' => 'In 3 days'
                    ],
                    [
                        'title' => 'Assemble Weekly ROAS & Blended CPA Performance Deck',
                        'content' => 'Aggregate ad spend across channels, calculate new customer acquisition cost and LTV.',
                        'assigned_to' => 'Elena Rostova',
                        'priority' => 'High',
                        'status' => 'completed',
                        'due_date' => 'Done'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'prompts_json' => json_encode([
                    'Formulate a 4-week ad testing roadmap for a high-ticket B2B service.',
                    'Audit this landing page copy and identify the top 3 friction points reducing conversions.',
                    'Outline a monthly client performance review agenda that highlights agency ROI.'
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'slug' => 'ecommerce-growth',
                'title' => 'E-Commerce & Dropshipping Growth Engine',
                'domain' => 'E-Commerce & DTC Retail',
                'description' => 'Turnkey executive suite for Shopify, Amazon, and DTC store owners. Built to scale winning products, streamline supplier logistics, boost average order value (AOV), and drive repeatable customer retention.',
                'icon' => 'shopping-bag',
                'color' => 'amber',
                'tier' => 'paid',
                'price' => 29.00,
                'currency' => 'USD',
                'badge' => 'HOT SELLER',
                'features_json' => json_encode([
                    'Winning Product Validation & Competitor Spy SOPs',
                    'Shopify High-Converting Product Page Blueprints',
                    'Supply Chain, Sourcing & Margin Protection Protocols',
                    'Post-Purchase Email/SMS Klaviyo Retention Automation',
                    'Meta & TikTok UGC Ad Briefing Frameworks'
                ], JSON_UNESCAPED_UNICODE),
                'executives_json' => json_encode([
                    [
                        'name' => 'Marcus Chen',
                        'title' => 'Head of DTC Growth & Conversion (CRO)',
                        'department' => 'Growth & Revenue',
                        'avatar_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'DTC growth engineer specializing in friction-free checkout flows, bundle offers, and AOV optimization for Shopify stores.',
                        'personality' => 'Analytical, funnel obsessive, user-behavior specialist.',
                        'voice_id' => 'echo',
                        'voice_gender' => 'male',
                        'system_prompt' => 'You are Marcus Chen, Head of DTC Growth and CRO. You diagnose eCommerce checkout drop-offs, design multi-tiered bundles, and optimize revenue per visitor.'
                    ],
                    [
                        'name' => 'Chloe Dubois',
                        'title' => 'Creative Ad Director & UGC Strategist',
                        'department' => 'Creative & Media',
                        'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Direct-to-consumer ad creative lead who writes scripts for viral TikTok organic and paid UGC hooks.',
                        'personality' => 'Trend forecaster, visual branding guru, social native.',
                        'voice_id' => 'shimmer',
                        'voice_gender' => 'female',
                        'system_prompt' => 'You are Chloe Dubois, Creative Ad Director and UGC Strategist. You draft high-converting UGC creator briefs, unboxing concepts, and TikTok Spark Ad hooks.'
                    ],
                    [
                        'name' => 'Henrik Lindqvist',
                        'title' => 'Supply Chain & Sourcing Director',
                        'department' => 'Logistics & Operations',
                        'avatar_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Global manufacturing and logistics veteran. Negotiates supplier pricing, customs clearance, and 3PL fulfillment speed.',
                        'personality' => 'Pragmatic, margin protector, vendor negotiation hawk.',
                        'voice_id' => 'onyx',
                        'voice_gender' => 'male',
                        'system_prompt' => 'You are Henrik Lindqvist, Supply Chain and Sourcing Director. You audit unit economics, protect gross margins, and troubleshoot supplier lead times.'
                    ],
                    [
                        'name' => 'Maya Lin',
                        'title' => 'Customer Experience & Retention Lead',
                        'department' => 'Retention & LTV',
                        'avatar_url' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'LTV engineer building automated Klaviyo email & SMS flows that turn 1-time buyers into loyal repeat brand advocates.',
                        'personality' => 'Warm, relationship builder, retention metric strategist.',
                        'voice_id' => 'nova',
                        'voice_gender' => 'female',
                        'system_prompt' => 'You are Maya Lin, Customer Experience and Retention Lead. You construct post-purchase flows, VIP loyalty tiers, and customer win-back automations.'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'projects_json' => json_encode([
                    [
                        'name' => 'Shopify Store Conversion & AOV Expansion',
                        'description' => 'Product page redesign, 1-click post-purchase upsells, and trust-badge integration.',
                        'color' => 'amber',
                        'icon' => 'shopping-cart',
                        'status' => 'active'
                    ],
                    [
                        'name' => 'Supplier Sourcing & Margin Fortification',
                        'description' => 'Supplier negotiation, batch MOQ agreements, and custom branded packaging.',
                        'color' => 'emerald',
                        'icon' => 'package',
                        'status' => 'active'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'tasks_json' => json_encode([
                    [
                        'title' => 'Set Up 5-Part Post-Purchase & Abandoned Checkout Sequence',
                        'content' => 'Configure triggers for SMS and email with dynamic coupon incentives and social proof.',
                        'assigned_to' => 'Maya Lin',
                        'priority' => 'High',
                        'status' => 'in_progress',
                        'due_date' => 'Tomorrow'
                    ],
                    [
                        'title' => 'Draft 3 High-Energy TikTok UGC Scripts for Winning Product',
                        'content' => 'Focus on problem-agitating hook, product demonstration, and limited-time offer.',
                        'assigned_to' => 'Chloe Dubois',
                        'priority' => 'High',
                        'status' => 'todo',
                        'due_date' => 'In 2 days'
                    ],
                    [
                        'title' => 'Negotiate 15% Bulk Discount Tier with Primary Manufacturer',
                        'content' => 'Leverage projected Q4 inventory volume to lock in lower unit costs.',
                        'assigned_to' => 'Henrik Lindqvist',
                        'priority' => 'Medium',
                        'status' => 'todo',
                        'due_date' => 'This week'
                    ],
                    [
                        'title' => 'Implement 1-Click Post-Purchase Cart Upsell Widget',
                        'content' => 'Add complementary accessory bundle at a 20% discount on order confirmation.',
                        'assigned_to' => 'Marcus Chen',
                        'priority' => 'High',
                        'status' => 'completed',
                        'due_date' => 'Done'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'prompts_json' => json_encode([
                    'Calculate the break-even ROAS for a $59 retail product with $16 COGS and $6 shipping.',
                    'Write a compelling product description utilizing sensory words and benefit-driven bullet points.',
                    'Design a 3-tier bundle strategy to increase our Average Order Value from $45 to $75.'
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'slug' => 'saas-studio',
                'title' => 'SaaS & Full-Stack Tech Studio',
                'domain' => 'Software Engineering & SaaS',
                'description' => 'Elite technical advisory and product launchpad for solo founders, developers, and micro-SaaS builders. Ship production-ready code, secure cloud infrastructure, and scale product-led growth.',
                'icon' => 'code',
                'color' => 'emerald',
                'tier' => 'paid',
                'price' => 39.00,
                'currency' => 'USD',
                'badge' => 'PRO SUITE',
                'features_json' => json_encode([
                    'Full-Stack System Architecture & DB Design Reviews',
                    'Cloud Infrastructure (Docker/AWS/Vercel) & CI/CD Pipelines',
                    'Product-Led Growth (PLG) Onboarding Flow Optimization',
                    'Security, Auth & API Rate-Limiting Hardening Protocols',
                    'Interactive Multi-Agent Code Review & Tech Stack Debates'
                ], JSON_UNESCAPED_UNICODE),
                'executives_json' => json_encode([
                    [
                        'name' => 'Dr. Aris Thorne',
                        'title' => 'Chief Technology Officer (CTO)',
                        'department' => 'Software Engineering',
                        'avatar_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Former Principal Systems Architect with 15+ years experience building fault-tolerant microservices, distributed systems, and scalable APIs.',
                        'personality' => 'Technical purist, uncompromising on reliability and clean code.',
                        'voice_id' => 'onyx',
                        'voice_gender' => 'male',
                        'system_prompt' => 'You are Dr. Aris Thorne, CTO. You architect backend systems, review complex database schemas, enforce coding best practices, and eliminate latency bottlenecks.'
                    ],
                    [
                        'name' => 'Sophia Mercer',
                        'title' => 'VP of Product & UX Design',
                        'department' => 'Product Experience',
                        'avatar_url' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Product designer obsessed with frictionless user activation, delightful micro-interactions, and reducing time-to-value.',
                        'personality' => 'User-centric, minimalist design advocate, telemetry driven.',
                        'voice_id' => 'nova',
                        'voice_gender' => 'female',
                        'system_prompt' => 'You are Sophia Mercer, VP of Product and UX Design. You turn complex features into intuitive user journeys, design onboarding checklists, and reduce churn.'
                    ],
                    [
                        'name' => 'Devon Vance',
                        'title' => 'Lead DevOps & Cloud Security Engineer',
                        'department' => 'Infrastructure & SecOps',
                        'avatar_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Cloud infrastructure specialist who secures containerized environments, provisions automated CI/CD pipelines, and manages backups.',
                        'personality' => 'Zero-trust mindset, automation evangelist, uptime guardian.',
                        'voice_id' => 'echo',
                        'voice_gender' => 'male',
                        'system_prompt' => 'You are Devon Vance, Lead DevOps and Security Engineer. You configure Docker/Kubernetes setups, enforce API security, setup logging, and optimize server bills.'
                    ],
                    [
                        'name' => 'Claire Zhang',
                        'title' => 'Growth Engineer & Monetization Lead',
                        'department' => 'Product-Led Growth',
                        'avatar_url' => 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Growth engineer specializing in freemium paywalls, usage-based subscription tiers, and viral referral loops.',
                        'personality' => 'Experimenter, quantitative, metric focused.',
                        'voice_id' => 'shimmer',
                        'voice_gender' => 'female',
                        'system_prompt' => 'You are Claire Zhang, Growth Engineer and Monetization Lead. You design pricing matrices, subscription billing gates, and telemetry funnels to maximize customer lifetime value.'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'projects_json' => json_encode([
                    [
                        'name' => 'SaaS V1 Production MVP Architecture',
                        'description' => 'Core database migrations, multi-tenant authentication, and REST/GraphQL API specifications.',
                        'color' => 'emerald',
                        'icon' => 'code-2',
                        'status' => 'active'
                    ],
                    [
                        'name' => 'Automated CI/CD & Cloud Infrastructure Deployment',
                        'description' => 'Automated test suite, staging environment, production Docker deployment, and SSL.',
                        'color' => 'indigo',
                        'icon' => 'server',
                        'status' => 'active'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'tasks_json' => json_encode([
                    [
                        'title' => 'Audit Database Schema for Multi-Tenant Isolation & Indexing',
                        'content' => 'Review foreign keys, composite indexes on user_id, and query performance under load.',
                        'assigned_to' => 'Dr. Aris Thorne',
                        'priority' => 'High',
                        'status' => 'in_progress',
                        'due_date' => 'Tomorrow'
                    ],
                    [
                        'title' => 'Configure GitHub Actions Automated CI/CD Workflow',
                        'content' => 'Build automated testing, linting, and zero-downtime deployment script.',
                        'assigned_to' => 'Devon Vance',
                        'priority' => 'High',
                        'status' => 'todo',
                        'due_date' => 'In 2 days'
                    ],
                    [
                        'title' => 'Design First-Time User Onboarding Flow Wireframe',
                        'content' => 'Create a 3-step setup modal that guides users to their initial "Aha!" moment.',
                        'assigned_to' => 'Sophia Mercer',
                        'priority' => 'Medium',
                        'status' => 'todo',
                        'due_date' => 'This week'
                    ],
                    [
                        'title' => 'Formulate Subscription Tiers & Feature Matrix',
                        'content' => 'Establish Free, Starter ($19/mo), and Pro ($49/mo) limits on API quotas.',
                        'assigned_to' => 'Claire Zhang',
                        'priority' => 'High',
                        'status' => 'completed',
                        'due_date' => 'Done'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'prompts_json' => json_encode([
                    'Review this database schema for a multi-tenant SaaS and identify indexing gaps.',
                    'How should I structure Stripe webhook handling to prevent double-charging or missing events?',
                    'Design an interactive onboarding checklist that increases Day-7 user retention.'
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'slug' => 'solo-freelancer',
                'title' => 'Solo Freelancer & Client Operating System',
                'domain' => 'Freelance & Independent Consulting',
                'description' => 'Your dedicated back-office executive team. Craft winning proposals on Upwork/Fiverr, command higher rates, manage scope creep, and automate invoicing so you can focus entirely on high-value client work.',
                'icon' => 'briefcase',
                'color' => 'cyan',
                'tier' => 'paid',
                'price' => 19.00,
                'currency' => 'USD',
                'badge' => 'RECOMMENDED',
                'features_json' => json_encode([
                    'High-Win Proposal & SOW Contract Blueprints',
                    'Scope-Creep Defense & Client Boundary Communication Scripts',
                    'Hourly to Value-Based Pricing Calculator SOPs',
                    'Milestone Deliverables & Invoicing Follow-up Automations'
                ], JSON_UNESCAPED_UNICODE),
                'executives_json' => json_encode([
                    [
                        'name' => 'Harrison Cole',
                        'title' => 'Client Partner & Deal Closer',
                        'department' => 'Client Relations',
                        'avatar_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Freelance business development expert who helps solo professionals close $5,000+ retainers and write winning proposals.',
                        'personality' => 'Charismatic, firm on boundaries, master negotiator.',
                        'voice_id' => 'echo',
                        'voice_gender' => 'male',
                        'system_prompt' => 'You are Harrison Cole, Client Partner and Deal Closer. You write winning Upwork/cold proposals, negotiate contracts, and price projects based on client business value.'
                    ],
                    [
                        'name' => 'Seraphina Blair',
                        'title' => 'Project Operations & Delivery Director',
                        'department' => 'Delivery & Operations',
                        'avatar_url' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Project manager dedicated to keeping client expectations crystal clear, preventing scope creep, and organizing milestones.',
                        'personality' => 'Organized, direct, deadline guardian.',
                        'voice_id' => 'nova',
                        'voice_gender' => 'female',
                        'system_prompt' => 'You are Seraphina Blair, Project Operations Director. You draft project scopes, set firm milestone timelines, and politely deflect out-of-scope client requests.'
                    ],
                    [
                        'name' => 'Damon Frost',
                        'title' => 'Financial Strategist & Tax Lead',
                        'department' => 'Finance & Billing',
                        'avatar_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Financial advisor managing freelance cash flows, quarterly tax reserves, payment milestone protection, and late-fee policies.',
                        'personality' => 'Prudent, precise, cashflow protector.',
                        'voice_id' => 'onyx',
                        'voice_gender' => 'male',
                        'system_prompt' => 'You are Damon Frost, Financial Strategist. You calculate billable hourly rates, draft invoice follow-ups, and calculate tax allocations for freelance income.'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'projects_json' => json_encode([
                    [
                        'name' => 'High-Ticket Client Pipeline & Proposal Vault',
                        'description' => 'Target prospect list, customized pitch templates, and retainer conversion frameworks.',
                        'color' => 'cyan',
                        'icon' => 'file-text',
                        'status' => 'active'
                    ],
                    [
                        'name' => 'Client Delivery & Milestone Management',
                        'description' => 'SOPs for client kick-off calls, weekly status updates, and milestone handoffs.',
                        'color' => 'indigo',
                        'icon' => 'check-square',
                        'status' => 'active'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'tasks_json' => json_encode([
                    [
                        'title' => 'Draft a $5,000 Value-Based Proposal for Inbound Client',
                        'content' => 'Structure proposal focusing on ROI, 3 milestone phases, and explicit terms of scope.',
                        'assigned_to' => 'Harrison Cole',
                        'priority' => 'High',
                        'status' => 'in_progress',
                        'due_date' => 'Tomorrow'
                    ],
                    [
                        'title' => 'Create Scope Change Addendum Script for Extra Feature Requests',
                        'content' => 'Formulate a diplomatic response quoting additional cost and delivery adjustments.',
                        'assigned_to' => 'Seraphina Blair',
                        'priority' => 'Medium',
                        'status' => 'todo',
                        'due_date' => 'In 2 days'
                    ],
                    [
                        'title' => 'Set Up Quarterly Tax Reserve & Expense Tracker',
                        'content' => 'Establish automatic 25% tax allocation rule and review deductible software subscriptions.',
                        'assigned_to' => 'Damon Frost',
                        'priority' => 'Medium',
                        'status' => 'completed',
                        'due_date' => 'Done'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'prompts_json' => json_encode([
                    'How should I respond to a client who demands a 40% discount on my standard proposal?',
                    'Write a polite but firm email to follow up on an unpaid invoice that is 14 days overdue.',
                    'Calculate what hourly rate I need to charge to net $100k annually working 25 billable hours/week.'
                ], JSON_UNESCAPED_UNICODE)
            ],
            [
                'slug' => 'real-estate-hub',
                'title' => 'Real Estate & Property Investment Hub',
                'domain' => 'Real Estate & Property Management',
                'description' => 'Comprehensive strategic suite for real estate investors, property managers, and syndicators. Analyze deal cashflows, evaluate CAP rates, optimize rental marketing, and manage tenant workflows with ease.',
                'icon' => 'home',
                'color' => 'indigo',
                'tier' => 'paid',
                'price' => 19.00,
                'currency' => 'USD',
                'badge' => 'PRO',
                'features_json' => json_encode([
                    'Cap Rate, Cash-on-Cash & ROI Underwriting Models',
                    'Tenant Screening SOPs & Rental Agreement Checklists',
                    'Luxury Property Listing Copy & Video Walkthrough Scripts',
                    'Preventative Maintenance & Vendor Management Schedules'
                ], JSON_UNESCAPED_UNICODE),
                'executives_json' => json_encode([
                    [
                        'name' => 'Victoria Sterling',
                        'title' => 'Acquisitions & Deal Underwriter',
                        'department' => 'Investment & Finance',
                        'avatar_url' => 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Real estate financial analyst who evaluates multi-family and commercial cashflows, debt service coverage ratios (DSCR), and renovation budgets.',
                        'personality' => 'Rigorous financial modeler, conservative risk manager.',
                        'voice_id' => 'nova',
                        'voice_gender' => 'female',
                        'system_prompt' => 'You are Victoria Sterling, Acquisitions and Deal Underwriter. You analyze property pro-formas, calculate IRR/Cap Rates, stress-test vacancy scenarios, and identify hidden liabilities.'
                    ],
                    [
                        'name' => 'Dominic Cruz',
                        'title' => 'Property Marketing & Listing Specialist',
                        'department' => 'Marketing & Sales',
                        'avatar_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Real estate marketing director who writes listing copy that attracts qualified buyers and high-credit tenants.',
                        'personality' => 'High-energy, luxury storytelling, showcase staging specialist.',
                        'voice_id' => 'echo',
                        'voice_gender' => 'male',
                        'system_prompt' => 'You are Dominic Cruz, Property Marketing Specialist. You write captivating MLS/Zillow listing descriptions, direct photo staging, and craft neighborhood spotlight copy.'
                    ],
                    [
                        'name' => 'Rachel Chen',
                        'title' => 'Asset & Tenant Operations Director',
                        'department' => 'Property Operations',
                        'avatar_url' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&auto=format&fit=crop&q=80',
                        'bio' => 'Property management veteran overseeing tenant screening, lease agreements, preventative repair logs, and emergency vendor dispatch.',
                        'personality' => 'Empathetic, firm on leases, maintenance coordination expert.',
                        'voice_id' => 'shimmer',
                        'voice_gender' => 'female',
                        'system_prompt' => 'You are Rachel Chen, Asset and Tenant Operations Director. You standardize tenant screening criteria, handle lease renewals, and manage property maintenance schedules.'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'projects_json' => json_encode([
                    [
                        'name' => 'Multi-Family Acquisition Due Diligence',
                        'description' => '10-year cashflow pro-forma, environmental inspections, rent roll audits, and title searches.',
                        'color' => 'indigo',
                        'icon' => 'building',
                        'status' => 'active'
                    ],
                    [
                        'name' => 'Property Marketing & Tenant Onboarding Protocols',
                        'description' => 'Listing photography checklist, digital application portal, and tenant welcome packages.',
                        'color' => 'emerald',
                        'icon' => 'key',
                        'status' => 'active'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'tasks_json' => json_encode([
                    [
                        'title' => 'Perform 10-Year Cashflow Analysis on 4-Unit Property',
                        'content' => 'Model net operating income (NOI), principal paydown, and return on equity at 6.8% interest.',
                        'assigned_to' => 'Victoria Sterling',
                        'priority' => 'High',
                        'status' => 'in_progress',
                        'due_date' => 'Tomorrow'
                    ],
                    [
                        'title' => 'Draft High-Converting Listing Description for Renovated Penthouse',
                        'content' => 'Emphasize architectural highlights, chef kitchen, natural lighting, and transit proximity.',
                        'assigned_to' => 'Dominic Cruz',
                        'priority' => 'High',
                        'status' => 'todo',
                        'due_date' => 'In 2 days'
                    ],
                    [
                        'title' => 'Compile Annual Preventative HVAC & Roof Maintenance Checklist',
                        'content' => 'Schedule vendor bids for spring servicing and establish emergency maintenance SLA.',
                        'assigned_to' => 'Rachel Chen',
                        'priority' => 'Medium',
                        'status' => 'completed',
                        'due_date' => 'Done'
                    ]
                ], JSON_UNESCAPED_UNICODE),
                'prompts_json' => json_encode([
                    'Calculate the Cash-on-Cash return for a $420,000 property with 25% down payment and $3,400 monthly rent.',
                    'Write a professional notice to tenants regarding upcoming scheduled water line maintenance.',
                    'Draft a 5-point tenant screening criteria policy compliant with fair housing laws.'
                ], JSON_UNESCAPED_UNICODE)
            ]
        ];

        $insTemplate = $pdo->prepare("
            INSERT INTO `workspace_templates` 
            (`slug`, `title`, `domain`, `description`, `icon`, `color`, `tier`, `price`, `currency`, `badge`, `features_json`, `executives_json`, `projects_json`, `tasks_json`, `prompts_json`, `is_active`)
            VALUES
            (:slug, :title, :domain, :description, :icon, :color, :tier, :price, :currency, :badge, :features_json, :executives_json, :projects_json, :tasks_json, :prompts_json, 1)
        ");

        foreach ($starterTemplates as $tpl) {
            $insTemplate->execute($tpl);
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

/**
 * Fetch a user by their Google OAuth unique ID
 */
function getUserByGoogleId($googleId) {
    global $pdo;
    try {
        if (empty($googleId)) return null;
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE google_id = :gid LIMIT 1");
        $stmt->execute([':gid' => (string)$googleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (\Throwable $e) {
        error_log("getUserByGoogleId error: " . $e->getMessage());
        return null;
    }
}

/**
 * Associate an existing user profile with their Google OAuth ID
 */
function linkUserGoogleId($userId, $googleId, $avatarUrl = null) {
    global $pdo;
    try {
        if (!empty($avatarUrl)) {
            $stmt = $pdo->prepare("UPDATE `users` SET google_id = :gid, avatar_url = CASE WHEN (avatar_url IS NULL OR avatar_url = '') THEN :avatar ELSE avatar_url END WHERE id = :id");
            return $stmt->execute([':gid' => (string)$googleId, ':avatar' => $avatarUrl, ':id' => (int)$userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE `users` SET google_id = :gid WHERE id = :id");
            return $stmt->execute([':gid' => (string)$googleId, ':id' => (int)$userId]);
        }
    } catch (\Throwable $e) {
        error_log("linkUserGoogleId error: " . $e->getMessage());
        return false;
    }
}
