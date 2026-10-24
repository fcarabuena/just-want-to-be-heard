CREATE DATABASE IF NOT EXISTS `just_want_to_be_heard`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `just_want_to_be_heard`;

CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'admin',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_admins_username` (`username`),
  UNIQUE KEY `uq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` LONGTEXT NULL,
  `setting_group` VARCHAR(100) NOT NULL DEFAULT 'general',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `videos` (
  `video_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `duration_seconds` INT UNSIGNED NULL DEFAULT NULL,
  `resolution` VARCHAR(50) NULL DEFAULT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `uploaded_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`video_id`),
  KEY `idx_videos_uploaded_by` (`uploaded_by`),
  KEY `idx_videos_published` (`is_published`),
  CONSTRAINT `fk_videos_admins`
    FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `characters` (
  `character_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `role` VARCHAR(255) NULL DEFAULT NULL,
  `description` TEXT NULL,
  `emotion_profile` TEXT NULL,
  `visual_notes` TEXT NULL,
  `image_path` VARCHAR(500) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`character_id`),
  KEY `idx_characters_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `objects` (
  `object_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `type` VARCHAR(100) NOT NULL DEFAULT 'prop',
  `description` TEXT NULL,
  `image_path` VARCHAR(500) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`object_id`),
  KEY `idx_objects_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `scenes` (
  `scene_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scene_number` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `video_id` INT UNSIGNED NULL,
  `next_scene_id` INT UNSIGNED NULL,
  `is_interactive` TINYINT(1) NOT NULL DEFAULT 1,
  `ending_type` VARCHAR(20) NOT NULL DEFAULT 'none',
  `choice_prompt` VARCHAR(255) NULL DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`scene_id`),
  UNIQUE KEY `uq_scenes_number` (`scene_number`),
  UNIQUE KEY `uq_scenes_slug` (`slug`),
  KEY `idx_scenes_video` (`video_id`),
  KEY `idx_scenes_order` (`display_order`),
  CONSTRAINT `fk_scenes_video`
    FOREIGN KEY (`video_id`) REFERENCES `videos` (`video_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_scenes_next_scene`
    FOREIGN KEY (`next_scene_id`) REFERENCES `scenes` (`scene_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `scene_characters` (
  `scene_character_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scene_id` INT UNSIGNED NOT NULL,
  `character_id` INT UNSIGNED NOT NULL,
  `relationship_note` TEXT NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`scene_character_id`),
  UNIQUE KEY `uq_scene_character` (`scene_id`, `character_id`),
  KEY `idx_scene_characters_scene` (`scene_id`),
  KEY `idx_scene_characters_character` (`character_id`),
  CONSTRAINT `fk_scene_characters_scene`
    FOREIGN KEY (`scene_id`) REFERENCES `scenes` (`scene_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_scene_characters_character`
    FOREIGN KEY (`character_id`) REFERENCES `characters` (`character_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `scene_objects` (
  `scene_object_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scene_id` INT UNSIGNED NOT NULL,
  `object_id` INT UNSIGNED NOT NULL,
  `placement_note` TEXT NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`scene_object_id`),
  UNIQUE KEY `uq_scene_object` (`scene_id`, `object_id`),
  KEY `idx_scene_objects_scene` (`scene_id`),
  KEY `idx_scene_objects_object` (`object_id`),
  CONSTRAINT `fk_scene_objects_scene`
    FOREIGN KEY (`scene_id`) REFERENCES `scenes` (`scene_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_scene_objects_object`
    FOREIGN KEY (`object_id`) REFERENCES `objects` (`object_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `interactive_elements` (
  `interactive_element_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scene_id` INT UNSIGNED NOT NULL,
  `character_id` INT UNSIGNED NULL,
  `object_id` INT UNSIGNED NULL,
  `element_name` VARCHAR(255) NOT NULL,
  `element_type` VARCHAR(100) NOT NULL DEFAULT 'hotspot',
  `label` VARCHAR(255) NULL DEFAULT NULL,
  `insight_title` VARCHAR(255) NULL DEFAULT NULL,
  `insight_text` TEXT NULL,
  `position_x` INT NULL DEFAULT NULL,
  `position_y` INT NULL DEFAULT NULL,
  `width` INT NULL DEFAULT NULL,
  `height` INT NULL DEFAULT NULL,
  `appear_at_seconds` DECIMAL(8,2) NULL DEFAULT NULL,
  `pauses_video` TINYINT(1) NOT NULL DEFAULT 1,
  `interaction_behavior` VARCHAR(100) NOT NULL DEFAULT 'info_popup',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`interactive_element_id`),
  KEY `idx_interactive_scene` (`scene_id`),
  KEY `idx_interactive_character` (`character_id`),
  KEY `idx_interactive_object` (`object_id`),
  KEY `idx_interactive_order` (`display_order`),
  CONSTRAINT `fk_interactive_scene`
    FOREIGN KEY (`scene_id`) REFERENCES `scenes` (`scene_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_interactive_character`
    FOREIGN KEY (`character_id`) REFERENCES `characters` (`character_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_interactive_object`
    FOREIGN KEY (`object_id`) REFERENCES `objects` (`object_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dialogues` (
  `dialogue_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scene_id` INT UNSIGNED NOT NULL,
  `interactive_element_id` INT UNSIGNED NULL,
  `speaker_name` VARCHAR(255) NOT NULL,
  `speaker_role` VARCHAR(100) NULL DEFAULT NULL,
  `dialogue_text` TEXT NOT NULL,
  `display_type` VARCHAR(50) NOT NULL DEFAULT 'panel',
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`dialogue_id`),
  KEY `idx_dialogues_scene` (`scene_id`),
  KEY `idx_dialogues_interactive` (`interactive_element_id`),
  KEY `idx_dialogues_active` (`is_active`),
  CONSTRAINT `fk_dialogues_scene`
    FOREIGN KEY (`scene_id`) REFERENCES `scenes` (`scene_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dialogues_interactive_element`
    FOREIGN KEY (`interactive_element_id`) REFERENCES `interactive_elements` (`interactive_element_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `scene_choices` (
  `choice_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scene_id` INT UNSIGNED NOT NULL,
  `choice_text` VARCHAR(255) NOT NULL,
  `target_scene_id` INT UNSIGNED NULL,
  `feedback_text` VARCHAR(500) NULL DEFAULT NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`choice_id`),
  KEY `idx_choices_scene` (`scene_id`),
  KEY `idx_choices_target` (`target_scene_id`),
  CONSTRAINT `fk_choices_scene`
    FOREIGN KEY (`scene_id`) REFERENCES `scenes` (`scene_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_choices_target`
    FOREIGN KEY (`target_scene_id`) REFERENCES `scenes` (`scene_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reflection_questions` (
  `question_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scene_id` INT UNSIGNED NOT NULL,
  `question_text` VARCHAR(500) NOT NULL,
  `question_type` VARCHAR(30) NOT NULL DEFAULT 'multiple_choice',
  `options_text` TEXT NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`question_id`),
  KEY `idx_reflections_scene` (`scene_id`),
  CONSTRAINT `fk_reflections_scene`
    FOREIGN KEY (`scene_id`) REFERENCES `scenes` (`scene_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES ('schema_version', '2', 'system')
  ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
