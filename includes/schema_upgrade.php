<?php

/*
 * Adds the tables and columns used by the interactive player (choices,
 * reflections, endings, hotspot insights) to databases created from the
 * original schema. Runs once; afterwards a settings flag skips it.
 */

const INTERACTIVE_SCHEMA_VERSION = '2';

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c');
    $stmt->execute([':t' => $table, ':c' => $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function ensure_interactive_schema(PDO $pdo): void
{
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'schema_version' LIMIT 1");
        $stmt->execute();
        if ((string) $stmt->fetchColumn() === INTERACTIVE_SCHEMA_VERSION) {
            return;
        }
    } catch (PDOException $exception) {
        return; // settings table missing: the base schema has not been imported yet
    }

    $columns = [
        ['scenes', 'ending_type', "VARCHAR(20) NOT NULL DEFAULT 'none' AFTER `is_interactive`"],
        ['scenes', 'choice_prompt', 'VARCHAR(255) NULL DEFAULT NULL AFTER `ending_type`'],
        ['interactive_elements', 'insight_title', 'VARCHAR(255) NULL DEFAULT NULL AFTER `label`'],
        ['interactive_elements', 'insight_text', 'TEXT NULL AFTER `insight_title`'],
        ['interactive_elements', 'appear_at_seconds', 'DECIMAL(8,2) NULL DEFAULT NULL AFTER `height`'],
        ['interactive_elements', 'pauses_video', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER `appear_at_seconds`'],
    ];

    foreach ($columns as [$table, $column, $definition]) {
        if (!column_exists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }

    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $stmt = $pdo->prepare(
        "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES ('schema_version', :v, 'system')
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $stmt->execute([':v' => INTERACTIVE_SCHEMA_VERSION]);
}
