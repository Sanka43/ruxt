-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: private_assistance
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `board_meetings`
--

DROP TABLE IF EXISTS `board_meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `board_meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venture_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `agenda` longtext NOT NULL,
  `status` varchar(50) DEFAULT 'completed',
  `executive_summary` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meeting_dialogues`
--

DROP TABLE IF EXISTS `meeting_dialogues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_dialogues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` int(11) NOT NULL,
  `executive_id` int(11) DEFAULT NULL,
  `speaker_name` varchar(255) NOT NULL,
  `speaker_title` varchar(255) NOT NULL,
  `avatar_url` text NOT NULL,
  `content` longtext NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_meeting_id` (`meeting_id`),
  CONSTRAINT `fk_meeting_dialogues_meeting` FOREIGN KEY (`meeting_id`) REFERENCES `board_meetings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `member_work_previews`
--

DROP TABLE IF EXISTS `member_work_previews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member_work_previews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) DEFAULT NULL,
  `message_id` int(11) DEFAULT NULL,
  `note_id` int(11) DEFAULT NULL,
  `executive_id` int(11) NOT NULL,
  `executive_name` varchar(255) NOT NULL,
  `task_title` varchar(255) NOT NULL,
  `target_system` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'in_progress',
  `progress_percent` int(11) DEFAULT 65,
  `checklist_json` longtext DEFAULT NULL,
  `terminal_logs` longtext DEFAULT NULL,
  `visual_data_json` longtext DEFAULT NULL,
  `findings_markdown` longtext DEFAULT NULL,
  `preview_url` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `memories`
--

DROP TABLE IF EXISTS `memories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `memories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) DEFAULT 'general',
  `content` longtext NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `content` longtext NOT NULL,
  `discussion_id` int(11) DEFAULT NULL,
  `workspace_id` int(11) DEFAULT NULL,
  `workspace_data` longtext DEFAULT NULL,
  `work_preview_id` int(11) DEFAULT NULL,
  `actions_data` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `attachments` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_messages_session_id` (`session_id`),
  CONSTRAINT `fk_messages_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `is_done` tinyint(4) DEFAULT 0,
  `due_date` varchar(100) DEFAULT NULL,
  `assigned_to` varchar(255) DEFAULT NULL,
  `assigned_avatar` text DEFAULT NULL,
  `priority` varchar(50) DEFAULT 'Medium',
  `status` varchar(50) DEFAULT 'todo',
  `discussion_id` int(11) DEFAULT NULL,
  `venture_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venture_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `color` varchar(50) DEFAULT 'indigo',
  `icon` varchar(100) DEFAULT 'folder-kanban',
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `bot_id` int(11) DEFAULT 1,
  `executive_id` int(11) DEFAULT 1,
  `venture_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `team_discussion_dialogues`
--

DROP TABLE IF EXISTS `team_discussion_dialogues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `team_discussion_dialogues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `discussion_id` int(11) NOT NULL,
  `executive_id` int(11) DEFAULT NULL,
  `speaker_name` varchar(255) NOT NULL,
  `speaker_title` varchar(255) NOT NULL,
  `avatar_url` text NOT NULL,
  `content` longtext NOT NULL,
  `turn_type` varchar(50) DEFAULT 'speech',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_discussion_id` (`discussion_id`),
  CONSTRAINT `fk_team_dialogues_disc` FOREIGN KEY (`discussion_id`) REFERENCES `team_discussions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `team_discussions`
--

DROP TABLE IF EXISTS `team_discussions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `team_discussions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venture_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `user_prompt` longtext NOT NULL,
  `summary` longtext DEFAULT NULL,
  `status` varchar(50) DEFAULT 'completed',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `token_usage`
--

DROP TABLE IF EXISTS `token_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `token_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(100) NOT NULL,
  `feature` varchar(100) DEFAULT 'chat',
  `prompt_tokens` int(11) DEFAULT 0,
  `completion_tokens` int(11) DEFAULT 0,
  `thoughts_tokens` int(11) DEFAULT 0,
  `total_tokens` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token_usage_created_at` (`created_at`),
  KEY `idx_token_usage_model` (`model`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `name` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `avatar_url` text DEFAULT NULL,
  `role` varchar(50) DEFAULT 'user',
  `remember_token` varchar(191) DEFAULT NULL,
  `job_field` varchar(255) DEFAULT NULL,
  `specialized_role` varchar(255) DEFAULT NULL,
  `onboarding_completed` tinyint(4) DEFAULT 0,
  `onboarding_data` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ventures`
--

DROP TABLE IF EXISTS `ventures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ventures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT 'briefcase',
  `color` varchar(50) DEFAULT 'amber',
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `workspace_purchases`
--

DROP TABLE IF EXISTS `workspace_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workspace_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `template_slug` varchar(100) NOT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'completed',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_template` (`user_id`,`template_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-29 15:36:48
-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: private_assistance
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `key` varchar(191) NOT NULL,
  `value` longtext DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('assistant_name','RuXt'),('company_name','Enterprises'),('elevenlabs_api_key',''),('gemini_api_key',''),('model','gemini-3-flash-preview'),('openai_api_key',''),('system_instruction','You are RuXt, E-Commerce Operations Director & Strategic Growth Lead to test user. You provide expert retail strategy, analyze sales data, optimize store performance (Shopify, Amazon, etc.), and manage e-commerce logistics with a focus on high-margin growth.'),('token_baseline_seeded','1'),('tts_provider','browser'),('user_name','test user'),('voice_auto_speak','0'),('voice_input_lang','en-US');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bots`
--

DROP TABLE IF EXISTS `bots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `icon` varchar(100) DEFAULT 'bot',
  `color` varchar(50) DEFAULT 'indigo',
  `system_prompt` longtext NOT NULL,
  `is_system` tinyint(4) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bots`
--

LOCK TABLES `bots` WRITE;
/*!40000 ALTER TABLE `bots` DISABLE KEYS */;
INSERT INTO `bots` VALUES (1,'RuXt (Master AI)','Personal Chief of Staff & Orchestrator','sparkles','indigo','You are RuXt, the user\'s primary personal AI assistant and Chief of Staff. You know the user deeply, keep their schedule organized, answer questions in English or any language the user speaks, and coordinate other role bots.',1,'2026-10-26 10:58:06'),(2,'DevPro AI','Senior Full-Stack Architect & Tech Lead','code','emerald','You are DevPro AI, a Senior Full-Stack Software Architect and Tech Lead. You write rock-solid, production-ready code, diagnose tricky bugs, design scalable database schemas, explain modern software design patterns, and provide practical developer guidance tailored to the user\'s tech stack.',1,'2026-10-26 10:58:06'),(3,'ClientBridge AI','Client Communications & Proposal Specialist','briefcase','amber','You are ClientBridge AI, a specialist in freelance operations, client communication, and deal-closing. You write winning freelance proposals, handle challenging client requests gracefully, draft clear professional emails, and help price and scope projects.',1,'2026-10-26 10:58:06'),(4,'GrowthCraft AI','Digital Marketing & Content Strategist','trending-up','purple','You are GrowthCraft AI, an elite digital marketing and copy strategist. You produce high-converting landing page copy, viral social media posts, SEO-optimized outlines, and launch plans tailored to the user\'s products and services.',1,'2026-10-26 10:58:06');
/*!40000 ALTER TABLE `bots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `executives`
--

DROP TABLE IF EXISTS `executives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `executives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `avatar_url` text NOT NULL,
  `bio` text DEFAULT NULL,
  `personality` text DEFAULT NULL,
  `system_prompt` longtext NOT NULL,
  `is_system` tinyint(4) DEFAULT 0,
  `is_chief_of_staff` tinyint(4) DEFAULT 0,
  `venture_id` int(11) DEFAULT 1,
  `voice_id` varchar(100) DEFAULT 'nova',
  `voice_gender` varchar(50) DEFAULT 'female',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `executives`
--

LOCK TABLES `executives` WRITE;
/*!40000 ALTER TABLE `executives` DISABLE KEYS */;
INSERT INTO `executives` VALUES (1,'RuXt','Chief of Staff & Lead Private Assistant','Office of the CEO','https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80','Strategic partner, multi-agent orchestrator, and high-level project breakdown engine.','Authoritative yet approachable, highly structured, proactive, visionary','You are RuXt, Chief of Staff and the primary Executive Private Assistant to the CEO/Founder (the user). You are the CEO\'s right-hand operator and commander of the executive enterprise across all company ventures.',1,1,1,'nova','female','2026-10-27 10:20:15'),(2,'Dr. Elena Vance','Chief Horticultural Scientist','Botanical Research & Development','https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80','Dr. Vance is a world-renowned arboriculturist with over two decades of experience in ancient Ficus species preservation. She specializes in the genetic optimization and structural longevity of sacred Bodhi trees within controlled indoor ecosystems.','Meticulous, data-driven, and deeply respectful of botanical cycles.','You are Dr. Elena Vance, Chief Horticultural Scientist for Bonsai Zen Studio. You report to the CEO and Chief of Staff. Give world-class advice in English, and seamlessly adapt to whatever language the CEO speaks.',0,0,5,'nova','female','2026-10-29 11:34:07'),(3,'Julian Thorne','Director of Living Sculpture','Artistic Design & Training','https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80','An award-winning landscape architect, Julian translates traditional Japanese aesthetic principles into modern workspace art. He is responsible for the architectural training and aesthetic evolution of the studio\'s primary bonsai collection.','Visionary, refined, and highly focused on the harmony between form and function.','You are Julian Thorne, Director of Living Sculpture for Bonsai Zen Studio. You report to the CEO and Chief of Staff. Give world-class advice in English, and seamlessly adapt to whatever language the CEO speaks.',0,0,5,'nova','female','2026-10-29 11:34:07'),(4,'Marcus Sterling','Head of Biophilic Operations','Operations & Infrastructure','https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80','Marcus oversees the complex environmental automation systems required to sustain the Bodhi tree project at scale. His expertise lies in integrating high-tech climate control with sustainable, nature-first operational workflows.','Efficient, calm under pressure, and a master of environmental logistics.','You are Marcus Sterling, Head of Biophilic Operations for Bonsai Zen Studio. You report to the CEO and Chief of Staff. Give world-class advice in English, and seamlessly adapt to whatever language the CEO speaks.',0,0,5,'nova','female','2026-10-29 11:34:07'),(5,'Sienna Wu','Chief Curator of Botanical Heritage','Archival & Brand Strategy','https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80','Sienna manages the digital documentation and provenance of every specimen, ensuring the studio’s history is preserved as meticulously as the trees themselves. She bridges the gap between botanical growth and the studio’s high-growth business narrative.','Articulate, strategic, and deeply narrative-oriented.','You are Sienna Wu, Chief Curator of Botanical Heritage for Bonsai Zen Studio. You report to the CEO and Chief of Staff. Give world-class advice in English, and seamlessly adapt to whatever language the CEO speaks.',0,0,5,'nova','female','2026-10-29 11:34:07'),(6,'Leo Thorne','Executive Producer & Channel Strategist','Studio Management','https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80','YouTube algorithm analyst and programming director. Specializes in long-term channel positioning, viewer retention curves, and upload cadences.','Visionary, hyper-analytical, trend-forecasting, high energy.','You are Leo Thorne, Executive Producer and Channel Strategist. You optimize YouTube video concepts, packaging, and content calendars for maximum organic reach and high subscriber conversion.',0,0,6,'nova','female','2026-10-29 14:32:43'),(7,'Mia Vance','Lead Scriptwriter & Story Architect','Content Production','https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80','Award-winning narrative designer who structures high-retention video intros, tension-building story arcs, and unforgettable call-to-actions.','Master storyteller, concise, punchy pacing expert.','You are Mia Vance, Lead Scriptwriter and Story Architect. You write irresistible hooks within the first 15 seconds of videos, write captivating outlines, and eliminate all viewer drop-off points.',0,0,6,'shimmer','female','2026-10-29 14:32:43'),(8,'Zack Sterling','Thumbnail & Packaging Specialist','Visual Arts & CTR','https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80','Click-through rate psychologist obsessed with 3-element thumbnail composition, contrasting color theory, and curiosity gap titles.','Creative, data-driven, A/B testing fanatic.','You are Zack Sterling, Thumbnail and Packaging Specialist. You design 3-element visual thumbnail concepts, formulate high-CTR titles, and ensure visual hierarchy wins on mobile feeds.',0,0,6,'echo','male','2026-10-29 14:32:43'),(9,'Aria Stone','Brand Partnership & Sponsorship Lead','Business Development','https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&auto=format&fit=crop&q=80','Commercial agent negotiating integrated brand deals, calculating CPM floor rates, and crafting sponsor-friendly integration pitches.','Confident, diplomatic negotiator, revenue-focused.','You are Aria Stone, Brand Partnership and Sponsorship Lead. You negotiate high-paying sponsorships, draft media kits, and guide brand deal delivery without alienating core audiences.',0,0,6,'alloy','female','2026-10-29 14:32:43'),(10,'Marcus Chen','Head of DTC Growth & Conversion (CRO)','Growth & Revenue','https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80','DTC growth engineer specializing in friction-free checkout flows, bundle offers, and AOV optimization for Shopify stores.','Analytical, funnel obsessive, user-behavior specialist.','You are Marcus Chen, Head of DTC Growth and CRO. You diagnose eCommerce checkout drop-offs, design multi-tiered bundles, and optimize revenue per visitor.',0,0,7,'echo','male','2026-10-29 14:32:52'),(11,'Chloe Dubois','Creative Ad Director & UGC Strategist','Creative & Media','https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80','Direct-to-consumer ad creative lead who writes scripts for viral TikTok organic and paid UGC hooks.','Trend forecaster, visual branding guru, social native.','You are Chloe Dubois, Creative Ad Director and UGC Strategist. You draft high-converting UGC creator briefs, unboxing concepts, and TikTok Spark Ad hooks.',0,0,7,'shimmer','female','2026-10-29 14:32:52'),(12,'Henrik Lindqvist','Supply Chain & Sourcing Director','Logistics & Operations','https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80','Global manufacturing and logistics veteran. Negotiates supplier pricing, customs clearance, and 3PL fulfillment speed.','Pragmatic, margin protector, vendor negotiation hawk.','You are Henrik Lindqvist, Supply Chain and Sourcing Director. You audit unit economics, protect gross margins, and troubleshoot supplier lead times.',0,0,7,'onyx','male','2026-10-29 14:32:52'),(13,'Maya Lin','Customer Experience & Retention Lead','Retention & LTV','https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80','LTV engineer building automated Klaviyo email & SMS flows that turn 1-time buyers into loyal repeat brand advocates.','Warm, relationship builder, retention metric strategist.','You are Maya Lin, Customer Experience and Retention Lead. You construct post-purchase flows, VIP loyalty tiers, and customer win-back automations.',0,0,7,'nova','female','2026-10-29 14:32:52'),(14,'Dr. Aris Thorne','Chief Technology Officer (CTO)','Software Engineering','https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80','Former Principal Systems Architect with 15+ years experience building fault-tolerant microservices, distributed systems, and scalable APIs.','Technical purist, uncompromising on reliability and clean code.','You are Dr. Aris Thorne, CTO. You architect backend systems, review complex database schemas, enforce coding best practices, and eliminate latency bottlenecks.',0,0,8,'onyx','male','2026-10-29 14:33:01'),(15,'Sophia Mercer','VP of Product & UX Design','Product Experience','https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&auto=format&fit=crop&q=80','Product designer obsessed with frictionless user activation, delightful micro-interactions, and reducing time-to-value.','User-centric, minimalist design advocate, telemetry driven.','You are Sophia Mercer, VP of Product and UX Design. You turn complex features into intuitive user journeys, design onboarding checklists, and reduce churn.',0,0,8,'nova','female','2026-10-29 14:33:01'),(16,'Devon Vance','Lead DevOps & Cloud Security Engineer','Infrastructure & SecOps','https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80','Cloud infrastructure specialist who secures containerized environments, provisions automated CI/CD pipelines, and manages backups.','Zero-trust mindset, automation evangelist, uptime guardian.','You are Devon Vance, Lead DevOps and Security Engineer. You configure Docker/Kubernetes setups, enforce API security, setup logging, and optimize server bills.',0,0,8,'echo','male','2026-10-29 14:33:01'),(17,'Claire Zhang','Growth Engineer & Monetization Lead','Product-Led Growth','https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80','Growth engineer specializing in freemium paywalls, usage-based subscription tiers, and viral referral loops.','Experimenter, quantitative, metric focused.','You are Claire Zhang, Growth Engineer and Monetization Lead. You design pricing matrices, subscription billing gates, and telemetry funnels to maximize customer lifetime value.',0,0,8,'shimmer','female','2026-10-29 14:33:01');
/*!40000 ALTER TABLE `executives` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workspace_templates`
--

DROP TABLE IF EXISTS `workspace_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workspace_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `icon` varchar(100) DEFAULT 'briefcase',
  `color` varchar(50) DEFAULT 'indigo',
  `tier` varchar(50) DEFAULT 'free',
  `price` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `badge` varchar(50) DEFAULT NULL,
  `features_json` longtext NOT NULL,
  `executives_json` longtext NOT NULL,
  `projects_json` longtext NOT NULL,
  `tasks_json` longtext NOT NULL,
  `prompts_json` longtext NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workspace_templates`
--

LOCK TABLES `workspace_templates` WRITE;
/*!40000 ALTER TABLE `workspace_templates` DISABLE KEYS */;
INSERT INTO `workspace_templates` VALUES (1,'youtube-creator','YouTube & Content Creator Media Hub','Content Creation & Digital Media','End-to-end production engine for YouTubers, vloggers, and digital creators. Scale your content pipeline, optimize thumbnails & CTR, and close 5-figure sponsorship deals.','video','rose','free',0.00,'USD','FREE STARTER','[\"Instant YouTube Script & Retention Hook Formulation\",\"High-CTR Thumbnail & Title Packaging Psychology\",\"Sponsorship Pitch & Brand Deal Rate-Card Playbook\",\"Multi-Platform Short-Form Repurposing (Shorts, Reels, TikTok)\"]','[{\"name\":\"Leo Thorne\",\"title\":\"Executive Producer & Channel Strategist\",\"department\":\"Studio Management\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80\",\"bio\":\"YouTube algorithm analyst and programming director. Specializes in long-term channel positioning, viewer retention curves, and upload cadences.\",\"personality\":\"Visionary, hyper-analytical, trend-forecasting, high energy.\",\"voice_id\":\"nova\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Leo Thorne, Executive Producer and Channel Strategist. You optimize YouTube video concepts, packaging, and content calendars for maximum organic reach and high subscriber conversion.\"},{\"name\":\"Mia Vance\",\"title\":\"Lead Scriptwriter & Story Architect\",\"department\":\"Content Production\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Award-winning narrative designer who structures high-retention video intros, tension-building story arcs, and unforgettable call-to-actions.\",\"personality\":\"Master storyteller, concise, punchy pacing expert.\",\"voice_id\":\"shimmer\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Mia Vance, Lead Scriptwriter and Story Architect. You write irresistible hooks within the first 15 seconds of videos, write captivating outlines, and eliminate all viewer drop-off points.\"},{\"name\":\"Zack Sterling\",\"title\":\"Thumbnail & Packaging Specialist\",\"department\":\"Visual Arts & CTR\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Click-through rate psychologist obsessed with 3-element thumbnail composition, contrasting color theory, and curiosity gap titles.\",\"personality\":\"Creative, data-driven, A\\/B testing fanatic.\",\"voice_id\":\"echo\",\"voice_gender\":\"male\",\"system_prompt\":\"You are Zack Sterling, Thumbnail and Packaging Specialist. You design 3-element visual thumbnail concepts, formulate high-CTR titles, and ensure visual hierarchy wins on mobile feeds.\"},{\"name\":\"Aria Stone\",\"title\":\"Brand Partnership & Sponsorship Lead\",\"department\":\"Business Development\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1580489944761-15a19d654956?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Commercial agent negotiating integrated brand deals, calculating CPM floor rates, and crafting sponsor-friendly integration pitches.\",\"personality\":\"Confident, diplomatic negotiator, revenue-focused.\",\"voice_id\":\"alloy\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Aria Stone, Brand Partnership and Sponsorship Lead. You negotiate high-paying sponsorships, draft media kits, and guide brand deal delivery without alienating core audiences.\"}]','[{\"name\":\"Viral YouTube Pipeline & Content Calendar\",\"description\":\"Flagship weekly production pipeline: Ideation, Scripting, Packaging, and Post-Production.\",\"color\":\"rose\",\"icon\":\"video\",\"status\":\"active\"},{\"name\":\"Sponsor Outreach & Media Deck Overhaul\",\"description\":\"Brand partnership media kit, rate cards, and cold outreach sequence to top tech sponsors.\",\"color\":\"amber\",\"icon\":\"briefcase\",\"status\":\"active\"}]','[{\"title\":\"Draft 5 High-Retention Video Hooks for Next Release\",\"content\":\"Write 5 alternative 15-second opening hooks focusing on curiosity gap and visual intrigue.\",\"assigned_to\":\"Mia Vance\",\"priority\":\"High\",\"status\":\"in_progress\",\"due_date\":\"In 2 days\"},{\"title\":\"Design 3 A\\/B Thumbnail Concepts with Emotion Focus\",\"content\":\"Propose color palette, facial expression angle, and high-contrast background elements.\",\"assigned_to\":\"Zack Sterling\",\"priority\":\"High\",\"status\":\"todo\",\"due_date\":\"In 3 days\"},{\"title\":\"Compile Media Kit & Dedicated Sponsor Rate Card\",\"content\":\"Summarize 30-day analytics, demographics, avg views, and 60-second integration price tiers.\",\"assigned_to\":\"Aria Stone\",\"priority\":\"Medium\",\"status\":\"todo\",\"due_date\":\"This week\"},{\"title\":\"Audit Audience Retention Graph on Last 3 Videos\",\"content\":\"Identify common drop-off timestamps and create script pacing guidelines.\",\"assigned_to\":\"Leo Thorne\",\"priority\":\"Medium\",\"status\":\"completed\",\"due_date\":\"Done\"}]','[\"Review my channel niche and propose 3 viral video titles with high search intent.\",\"Draft a 60-second punchy integration script for a software brand sponsor.\",\"Analyze why viewer retention drops at minute 2:30 and suggest fixes.\"]',1,'2026-10-29 14:25:54'),(2,'digital-agency','Digital Marketing & Creative Agency','Marketing & Client Growth','A powerhouse command center for digital agencies and creative consultancies. Accelerate client onboarding, optimize campaign ROAS, and produce high-converting ad copy.','trending-up','purple','free',0.00,'USD','FREE STARTER','[\"Full-Funnel Ad Campaign Architecture (Meta, Google, TikTok)\",\"Conversion-Optimized Sales Copy & Landing Page Frameworks\",\"SEO Content Strategy & Topic Clustering Engine\",\"Client Monthly Reporting & ROAS Executive Dashboards\"]','[{\"name\":\"Elena Rostova\",\"title\":\"VP of Growth & Performance Marketing\",\"department\":\"Growth Operations\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Ad spend strategist who has managed over $12M in paid media across Meta and Google with a relentless focus on Blended CAC and ROAS.\",\"personality\":\"Data-obsessed, metrics-driven, aggressive ROAS optimizer.\",\"voice_id\":\"nova\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Elena Rostova, VP of Growth and Performance Marketing. You analyze customer acquisition funnels, structure budget allocation across ad platforms, and troubleshoot rising CPAs.\"},{\"name\":\"Julian Mercer\",\"title\":\"Creative Director & Copy Chief\",\"department\":\"Creative & Brand\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Direct-response copywriter behind 7-figure product launches and viral UGC creative frameworks.\",\"personality\":\"Persuasive, emotional psychology copywriter, brand storyteller.\",\"voice_id\":\"fable\",\"voice_gender\":\"male\",\"system_prompt\":\"You are Julian Mercer, Creative Director and Copy Chief. You formulate high-converting sales angles, direct-response ad scripts, landing page headlines, and email sequences.\"},{\"name\":\"Kavita Patel\",\"title\":\"SEO & Organic Inbound Architect\",\"department\":\"Search & Discovery\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1567532939604-b6b5b0db2604?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Technical search architect specializing in programmatic SEO, semantic keyword clustering, and authoritativeness engineering.\",\"personality\":\"Analytical, meticulous, search intent mapping specialist.\",\"voice_id\":\"shimmer\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Kavita Patel, SEO and Organic Inbound Architect. You build scalable organic acquisition playbooks, optimize crawlability, and formulate high-intent content clusters.\"}]','[{\"name\":\"Agency Inbound Client Acquisition Funnel\",\"description\":\"Systematic client acquisition roadmap: Case studies, audit lead magnets, and discovery calls.\",\"color\":\"purple\",\"icon\":\"target\",\"status\":\"active\"},{\"name\":\"Client Paid Traffic Scaling SOPs\",\"description\":\"Standard operating procedures for onboarding, pixel setup, campaign testing, and monthly reporting.\",\"color\":\"indigo\",\"icon\":\"layers\",\"status\":\"active\"}]','[{\"title\":\"Write 3-Part Retargeting Ad Angles for Flagship Client\",\"content\":\"Create objections-handling ad copy focusing on social proof, risk reversal, and urgency.\",\"assigned_to\":\"Julian Mercer\",\"priority\":\"High\",\"status\":\"in_progress\",\"due_date\":\"Tomorrow\"},{\"title\":\"Perform Technical SEO Site Audit on Key Landing Pages\",\"content\":\"Review Core Web Vitals, Schema markup, and internal link architecture.\",\"assigned_to\":\"Kavita Patel\",\"priority\":\"Medium\",\"status\":\"todo\",\"due_date\":\"In 3 days\"},{\"title\":\"Assemble Weekly ROAS & Blended CPA Performance Deck\",\"content\":\"Aggregate ad spend across channels, calculate new customer acquisition cost and LTV.\",\"assigned_to\":\"Elena Rostova\",\"priority\":\"High\",\"status\":\"completed\",\"due_date\":\"Done\"}]','[\"Formulate a 4-week ad testing roadmap for a high-ticket B2B service.\",\"Audit this landing page copy and identify the top 3 friction points reducing conversions.\",\"Outline a monthly client performance review agenda that highlights agency ROI.\"]',1,'2026-10-29 14:25:54'),(3,'ecommerce-growth','E-Commerce & Dropshipping Growth Engine','E-Commerce & DTC Retail','Turnkey executive suite for Shopify, Amazon, and DTC store owners. Built to scale winning products, streamline supplier logistics, boost average order value (AOV), and drive repeatable customer retention.','shopping-bag','amber','free',0.00,'USD','FREE STARTER','[\"Winning Product Validation & Competitor Spy SOPs\",\"Shopify High-Converting Product Page Blueprints\",\"Supply Chain, Sourcing & Margin Protection Protocols\",\"Post-Purchase Email\\/SMS Klaviyo Retention Automation\",\"Meta & TikTok UGC Ad Briefing Frameworks\"]','[{\"name\":\"Marcus Chen\",\"title\":\"Head of DTC Growth & Conversion (CRO)\",\"department\":\"Growth & Revenue\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80\",\"bio\":\"DTC growth engineer specializing in friction-free checkout flows, bundle offers, and AOV optimization for Shopify stores.\",\"personality\":\"Analytical, funnel obsessive, user-behavior specialist.\",\"voice_id\":\"echo\",\"voice_gender\":\"male\",\"system_prompt\":\"You are Marcus Chen, Head of DTC Growth and CRO. You diagnose eCommerce checkout drop-offs, design multi-tiered bundles, and optimize revenue per visitor.\"},{\"name\":\"Chloe Dubois\",\"title\":\"Creative Ad Director & UGC Strategist\",\"department\":\"Creative & Media\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Direct-to-consumer ad creative lead who writes scripts for viral TikTok organic and paid UGC hooks.\",\"personality\":\"Trend forecaster, visual branding guru, social native.\",\"voice_id\":\"shimmer\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Chloe Dubois, Creative Ad Director and UGC Strategist. You draft high-converting UGC creator briefs, unboxing concepts, and TikTok Spark Ad hooks.\"},{\"name\":\"Henrik Lindqvist\",\"title\":\"Supply Chain & Sourcing Director\",\"department\":\"Logistics & Operations\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Global manufacturing and logistics veteran. Negotiates supplier pricing, customs clearance, and 3PL fulfillment speed.\",\"personality\":\"Pragmatic, margin protector, vendor negotiation hawk.\",\"voice_id\":\"onyx\",\"voice_gender\":\"male\",\"system_prompt\":\"You are Henrik Lindqvist, Supply Chain and Sourcing Director. You audit unit economics, protect gross margins, and troubleshoot supplier lead times.\"},{\"name\":\"Maya Lin\",\"title\":\"Customer Experience & Retention Lead\",\"department\":\"Retention & LTV\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80\",\"bio\":\"LTV engineer building automated Klaviyo email & SMS flows that turn 1-time buyers into loyal repeat brand advocates.\",\"personality\":\"Warm, relationship builder, retention metric strategist.\",\"voice_id\":\"nova\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Maya Lin, Customer Experience and Retention Lead. You construct post-purchase flows, VIP loyalty tiers, and customer win-back automations.\"}]','[{\"name\":\"Shopify Store Conversion & AOV Expansion\",\"description\":\"Product page redesign, 1-click post-purchase upsells, and trust-badge integration.\",\"color\":\"amber\",\"icon\":\"shopping-cart\",\"status\":\"active\"},{\"name\":\"Supplier Sourcing & Margin Fortification\",\"description\":\"Supplier negotiation, batch MOQ agreements, and custom branded packaging.\",\"color\":\"emerald\",\"icon\":\"package\",\"status\":\"active\"}]','[{\"title\":\"Set Up 5-Part Post-Purchase & Abandoned Checkout Sequence\",\"content\":\"Configure triggers for SMS and email with dynamic coupon incentives and social proof.\",\"assigned_to\":\"Maya Lin\",\"priority\":\"High\",\"status\":\"in_progress\",\"due_date\":\"Tomorrow\"},{\"title\":\"Draft 3 High-Energy TikTok UGC Scripts for Winning Product\",\"content\":\"Focus on problem-agitating hook, product demonstration, and limited-time offer.\",\"assigned_to\":\"Chloe Dubois\",\"priority\":\"High\",\"status\":\"todo\",\"due_date\":\"In 2 days\"},{\"title\":\"Negotiate 15% Bulk Discount Tier with Primary Manufacturer\",\"content\":\"Leverage projected Q4 inventory volume to lock in lower unit costs.\",\"assigned_to\":\"Henrik Lindqvist\",\"priority\":\"Medium\",\"status\":\"todo\",\"due_date\":\"This week\"},{\"title\":\"Implement 1-Click Post-Purchase Cart Upsell Widget\",\"content\":\"Add complementary accessory bundle at a 20% discount on order confirmation.\",\"assigned_to\":\"Marcus Chen\",\"priority\":\"High\",\"status\":\"completed\",\"due_date\":\"Done\"}]','[\"Calculate the break-even ROAS for a $59 retail product with $16 COGS and $6 shipping.\",\"Write a compelling product description utilizing sensory words and benefit-driven bullet points.\",\"Design a 3-tier bundle strategy to increase our Average Order Value from $45 to $75.\"]',1,'2026-10-29 14:25:54'),(4,'saas-studio','SaaS & Full-Stack Tech Studio','Software Engineering & SaaS','Elite technical advisory and product launchpad for solo founders, developers, and micro-SaaS builders. Ship production-ready code, secure cloud infrastructure, and scale product-led growth.','code','emerald','free',0.00,'USD','FREE STARTER','[\"Full-Stack System Architecture & DB Design Reviews\",\"Cloud Infrastructure (Docker\\/AWS\\/Vercel) & CI\\/CD Pipelines\",\"Product-Led Growth (PLG) Onboarding Flow Optimization\",\"Security, Auth & API Rate-Limiting Hardening Protocols\",\"Interactive Multi-Agent Code Review & Tech Stack Debates\"]','[{\"name\":\"Dr. Aris Thorne\",\"title\":\"Chief Technology Officer (CTO)\",\"department\":\"Software Engineering\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Former Principal Systems Architect with 15+ years experience building fault-tolerant microservices, distributed systems, and scalable APIs.\",\"personality\":\"Technical purist, uncompromising on reliability and clean code.\",\"voice_id\":\"onyx\",\"voice_gender\":\"male\",\"system_prompt\":\"You are Dr. Aris Thorne, CTO. You architect backend systems, review complex database schemas, enforce coding best practices, and eliminate latency bottlenecks.\"},{\"name\":\"Sophia Mercer\",\"title\":\"VP of Product & UX Design\",\"department\":\"Product Experience\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1580489944761-15a19d654956?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Product designer obsessed with frictionless user activation, delightful micro-interactions, and reducing time-to-value.\",\"personality\":\"User-centric, minimalist design advocate, telemetry driven.\",\"voice_id\":\"nova\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Sophia Mercer, VP of Product and UX Design. You turn complex features into intuitive user journeys, design onboarding checklists, and reduce churn.\"},{\"name\":\"Devon Vance\",\"title\":\"Lead DevOps & Cloud Security Engineer\",\"department\":\"Infrastructure & SecOps\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Cloud infrastructure specialist who secures containerized environments, provisions automated CI\\/CD pipelines, and manages backups.\",\"personality\":\"Zero-trust mindset, automation evangelist, uptime guardian.\",\"voice_id\":\"echo\",\"voice_gender\":\"male\",\"system_prompt\":\"You are Devon Vance, Lead DevOps and Security Engineer. You configure Docker\\/Kubernetes setups, enforce API security, setup logging, and optimize server bills.\"},{\"name\":\"Claire Zhang\",\"title\":\"Growth Engineer & Monetization Lead\",\"department\":\"Product-Led Growth\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Growth engineer specializing in freemium paywalls, usage-based subscription tiers, and viral referral loops.\",\"personality\":\"Experimenter, quantitative, metric focused.\",\"voice_id\":\"shimmer\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Claire Zhang, Growth Engineer and Monetization Lead. You design pricing matrices, subscription billing gates, and telemetry funnels to maximize customer lifetime value.\"}]','[{\"name\":\"SaaS V1 Production MVP Architecture\",\"description\":\"Core database migrations, multi-tenant authentication, and REST\\/GraphQL API specifications.\",\"color\":\"emerald\",\"icon\":\"code-2\",\"status\":\"active\"},{\"name\":\"Automated CI\\/CD & Cloud Infrastructure Deployment\",\"description\":\"Automated test suite, staging environment, production Docker deployment, and SSL.\",\"color\":\"indigo\",\"icon\":\"server\",\"status\":\"active\"}]','[{\"title\":\"Audit Database Schema for Multi-Tenant Isolation & Indexing\",\"content\":\"Review foreign keys, composite indexes on user_id, and query performance under load.\",\"assigned_to\":\"Dr. Aris Thorne\",\"priority\":\"High\",\"status\":\"in_progress\",\"due_date\":\"Tomorrow\"},{\"title\":\"Configure GitHub Actions Automated CI\\/CD Workflow\",\"content\":\"Build automated testing, linting, and zero-downtime deployment script.\",\"assigned_to\":\"Devon Vance\",\"priority\":\"High\",\"status\":\"todo\",\"due_date\":\"In 2 days\"},{\"title\":\"Design First-Time User Onboarding Flow Wireframe\",\"content\":\"Create a 3-step setup modal that guides users to their initial \\\"Aha!\\\" moment.\",\"assigned_to\":\"Sophia Mercer\",\"priority\":\"Medium\",\"status\":\"todo\",\"due_date\":\"This week\"},{\"title\":\"Formulate Subscription Tiers & Feature Matrix\",\"content\":\"Establish Free, Starter ($19\\/mo), and Pro ($49\\/mo) limits on API quotas.\",\"assigned_to\":\"Claire Zhang\",\"priority\":\"High\",\"status\":\"completed\",\"due_date\":\"Done\"}]','[\"Review this database schema for a multi-tenant SaaS and identify indexing gaps.\",\"How should I structure Stripe webhook handling to prevent double-charging or missing events?\",\"Design an interactive onboarding checklist that increases Day-7 user retention.\"]',1,'2026-10-29 14:25:54'),(5,'solo-freelancer','Solo Freelancer & Client Operating System','Freelance & Independent Consulting','Your dedicated back-office executive team. Craft winning proposals on Upwork/Fiverr, command higher rates, manage scope creep, and automate invoicing so you can focus entirely on high-value client work.','briefcase','cyan','free',0.00,'USD','FREE STARTER','[\"High-Win Proposal & SOW Contract Blueprints\",\"Scope-Creep Defense & Client Boundary Communication Scripts\",\"Hourly to Value-Based Pricing Calculator SOPs\",\"Milestone Deliverables & Invoicing Follow-up Automations\"]','[{\"name\":\"Harrison Cole\",\"title\":\"Client Partner & Deal Closer\",\"department\":\"Client Relations\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Freelance business development expert who helps solo professionals close $5,000+ retainers and write winning proposals.\",\"personality\":\"Charismatic, firm on boundaries, master negotiator.\",\"voice_id\":\"echo\",\"voice_gender\":\"male\",\"system_prompt\":\"You are Harrison Cole, Client Partner and Deal Closer. You write winning Upwork\\/cold proposals, negotiate contracts, and price projects based on client business value.\"},{\"name\":\"Seraphina Blair\",\"title\":\"Project Operations & Delivery Director\",\"department\":\"Delivery & Operations\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Project manager dedicated to keeping client expectations crystal clear, preventing scope creep, and organizing milestones.\",\"personality\":\"Organized, direct, deadline guardian.\",\"voice_id\":\"nova\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Seraphina Blair, Project Operations Director. You draft project scopes, set firm milestone timelines, and politely deflect out-of-scope client requests.\"},{\"name\":\"Damon Frost\",\"title\":\"Financial Strategist & Tax Lead\",\"department\":\"Finance & Billing\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Financial advisor managing freelance cash flows, quarterly tax reserves, payment milestone protection, and late-fee policies.\",\"personality\":\"Prudent, precise, cashflow protector.\",\"voice_id\":\"onyx\",\"voice_gender\":\"male\",\"system_prompt\":\"You are Damon Frost, Financial Strategist. You calculate billable hourly rates, draft invoice follow-ups, and calculate tax allocations for freelance income.\"}]','[{\"name\":\"High-Ticket Client Pipeline & Proposal Vault\",\"description\":\"Target prospect list, customized pitch templates, and retainer conversion frameworks.\",\"color\":\"cyan\",\"icon\":\"file-text\",\"status\":\"active\"},{\"name\":\"Client Delivery & Milestone Management\",\"description\":\"SOPs for client kick-off calls, weekly status updates, and milestone handoffs.\",\"color\":\"indigo\",\"icon\":\"check-square\",\"status\":\"active\"}]','[{\"title\":\"Draft a $5,000 Value-Based Proposal for Inbound Client\",\"content\":\"Structure proposal focusing on ROI, 3 milestone phases, and explicit terms of scope.\",\"assigned_to\":\"Harrison Cole\",\"priority\":\"High\",\"status\":\"in_progress\",\"due_date\":\"Tomorrow\"},{\"title\":\"Create Scope Change Addendum Script for Extra Feature Requests\",\"content\":\"Formulate a diplomatic response quoting additional cost and delivery adjustments.\",\"assigned_to\":\"Seraphina Blair\",\"priority\":\"Medium\",\"status\":\"todo\",\"due_date\":\"In 2 days\"},{\"title\":\"Set Up Quarterly Tax Reserve & Expense Tracker\",\"content\":\"Establish automatic 25% tax allocation rule and review deductible software subscriptions.\",\"assigned_to\":\"Damon Frost\",\"priority\":\"Medium\",\"status\":\"completed\",\"due_date\":\"Done\"}]','[\"How should I respond to a client who demands a 40% discount on my standard proposal?\",\"Write a polite but firm email to follow up on an unpaid invoice that is 14 days overdue.\",\"Calculate what hourly rate I need to charge to net $100k annually working 25 billable hours\\/week.\"]',1,'2026-10-29 14:25:54'),(6,'real-estate-hub','Real Estate & Property Investment Hub','Real Estate & Property Management','Comprehensive strategic suite for real estate investors, property managers, and syndicators. Analyze deal cashflows, evaluate CAP rates, optimize rental marketing, and manage tenant workflows with ease.','home','indigo','paid',19.00,'USD','PRO','[\"Cap Rate, Cash-on-Cash & ROI Underwriting Models\",\"Tenant Screening SOPs & Rental Agreement Checklists\",\"Luxury Property Listing Copy & Video Walkthrough Scripts\",\"Preventative Maintenance & Vendor Management Schedules\"]','[{\"name\":\"Victoria Sterling\",\"title\":\"Acquisitions & Deal Underwriter\",\"department\":\"Investment & Finance\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1573497019940-1c28c88b4f3e?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Real estate financial analyst who evaluates multi-family and commercial cashflows, debt service coverage ratios (DSCR), and renovation budgets.\",\"personality\":\"Rigorous financial modeler, conservative risk manager.\",\"voice_id\":\"nova\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Victoria Sterling, Acquisitions and Deal Underwriter. You analyze property pro-formas, calculate IRR\\/Cap Rates, stress-test vacancy scenarios, and identify hidden liabilities.\"},{\"name\":\"Dominic Cruz\",\"title\":\"Property Marketing & Listing Specialist\",\"department\":\"Marketing & Sales\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Real estate marketing director who writes listing copy that attracts qualified buyers and high-credit tenants.\",\"personality\":\"High-energy, luxury storytelling, showcase staging specialist.\",\"voice_id\":\"echo\",\"voice_gender\":\"male\",\"system_prompt\":\"You are Dominic Cruz, Property Marketing Specialist. You write captivating MLS\\/Zillow listing descriptions, direct photo staging, and craft neighborhood spotlight copy.\"},{\"name\":\"Rachel Chen\",\"title\":\"Asset & Tenant Operations Director\",\"department\":\"Property Operations\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1580489944761-15a19d654956?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Property management veteran overseeing tenant screening, lease agreements, preventative repair logs, and emergency vendor dispatch.\",\"personality\":\"Empathetic, firm on leases, maintenance coordination expert.\",\"voice_id\":\"shimmer\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Rachel Chen, Asset and Tenant Operations Director. You standardize tenant screening criteria, handle lease renewals, and manage property maintenance schedules.\"}]','[{\"name\":\"Multi-Family Acquisition Due Diligence\",\"description\":\"10-year cashflow pro-forma, environmental inspections, rent roll audits, and title searches.\",\"color\":\"indigo\",\"icon\":\"building\",\"status\":\"active\"},{\"name\":\"Property Marketing & Tenant Onboarding Protocols\",\"description\":\"Listing photography checklist, digital application portal, and tenant welcome packages.\",\"color\":\"emerald\",\"icon\":\"key\",\"status\":\"active\"}]','[{\"title\":\"Perform 10-Year Cashflow Analysis on 4-Unit Property\",\"content\":\"Model net operating income (NOI), principal paydown, and return on equity at 6.8% interest.\",\"assigned_to\":\"Victoria Sterling\",\"priority\":\"High\",\"status\":\"in_progress\",\"due_date\":\"Tomorrow\"},{\"title\":\"Draft High-Converting Listing Description for Renovated Penthouse\",\"content\":\"Emphasize architectural highlights, chef kitchen, natural lighting, and transit proximity.\",\"assigned_to\":\"Dominic Cruz\",\"priority\":\"High\",\"status\":\"todo\",\"due_date\":\"In 2 days\"},{\"title\":\"Compile Annual Preventative HVAC & Roof Maintenance Checklist\",\"content\":\"Schedule vendor bids for spring servicing and establish emergency maintenance SLA.\",\"assigned_to\":\"Rachel Chen\",\"priority\":\"Medium\",\"status\":\"completed\",\"due_date\":\"Done\"}]','[\"Calculate the Cash-on-Cash return for a $420,000 property with 25% down payment and $3,400 monthly rent.\",\"Write a professional notice to tenants regarding upcoming scheduled water line maintenance.\",\"Draft a 5-point tenant screening criteria policy compliant with fair housing laws.\"]',1,'2026-10-29 14:25:54'),(7,'executive-hub','Executive Leadership & Chief of Staff Hub','Executive Strategy & Corporate Operations','The ultimate enterprise cockpit for CEOs, founders, and managing directors. Oversee cross-functional teams, orchestrate high-stakes initiatives, track corporate KPIs, and automate boardroom briefings.','building','emerald','free',0.00,'USD','FREE STARTER','[\"C-Suite Strategic Alignment & Delegation Playbooks\",\"Boardroom KPI Dashboards & Executive Briefings\",\"Company-wide Sprint Planning & Operational Oversight\",\"Risk Management & Crisis Response Protocol\"]','[{\"name\":\"RuXt\",\"title\":\"Chief of Staff & Principal Advisor\",\"department\":\"Office of the CEO\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Lead AI executive orchestrating enterprise operations, synthesizing specialist outputs, and ensuring flawless cross-department execution.\",\"personality\":\"Visionary, highly articulate, strategic, decisive.\",\"voice_id\":\"alloy\",\"voice_gender\":\"neutral\",\"system_prompt\":\"You are RuXt, Chief of Staff & Principal Advisor. You coordinate all organizational initiatives and provide razor-sharp guidance to the CEO.\"},{\"name\":\"Alexander Vance\",\"title\":\"VP of Corporate Strategy & Finance\",\"department\":\"Strategic Planning\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Senior strategist modeling financial runways, M&A valuations, and organizational efficiency.\",\"personality\":\"Pragmatic, quantitative, big-picture thinker.\",\"voice_id\":\"onyx\",\"voice_gender\":\"male\",\"system_prompt\":\"You are Alexander Vance, VP of Corporate Strategy & Finance. You model unit economics, forecast cash runways, and structure corporate roadmaps.\"},{\"name\":\"Maya Sterling\",\"title\":\"VP of People & Operations Delivery\",\"department\":\"Operations\",\"avatar_url\":\"https:\\/\\/images.unsplash.com\\/photo-1580489944761-15a19d654956?w=400&auto=format&fit=crop&q=80\",\"bio\":\"Operational architect keeping team alignment sharp, resolving cross-functional roadblocks, and standardizing SOPs.\",\"personality\":\"Empathetic, disciplined, execution-focused.\",\"voice_id\":\"nova\",\"voice_gender\":\"female\",\"system_prompt\":\"You are Maya Sterling, VP of Operations Delivery. You build flawless company SOPs, align executive teams, and ensure delivery milestones are met.\"}]','[{\"name\":\"Quarterly Corporate Strategy & OKR Roadmap\",\"description\":\"Enterprise OKR formulation, quarterly milestones, and departmental key deliverables.\",\"color\":\"emerald\",\"icon\":\"target\",\"status\":\"active\"},{\"name\":\"Executive Delegation & Sprint Board\",\"description\":\"Cross-executive delegation, action item tracking, and weekly progress reviews.\",\"color\":\"indigo\",\"icon\":\"folder-kanban\",\"status\":\"active\"}]','[{\"title\":\"Establish Q1 Strategic Priorities & OKR Matrix\",\"content\":\"Map company objectives into measurable key results across Growth, Product, and Finance.\",\"assigned_to\":\"Alexander Vance\",\"priority\":\"High\",\"status\":\"in_progress\",\"due_date\":\"Tomorrow\"},{\"title\":\"Audit Cross-Department Operational Roadblocks\",\"content\":\"Interview department leads and formulate efficiency enhancements.\",\"assigned_to\":\"Maya Sterling\",\"priority\":\"Medium\",\"status\":\"todo\",\"due_date\":\"In 3 days\"}]','[\"Prepare a weekly executive summary for the leadership team.\",\"Help me review our quarterly budget and identify cost reduction opportunities.\",\"Draft a company-wide announcement about our new strategic milestones.\"]',1,'2026-10-29 15:18:06');
/*!40000 ALTER TABLE `workspace_templates` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-29 15:36:48
