-- ============================================
-- DATABASE SCHEMA: CONGRESS MANAGEMENT SYSTEM
-- MySQL 8.0+
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `congress_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `congress_db`;

-- ============================================
-- TABLE: users
-- ============================================
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `date_of_birth` DATE NOT NULL,
  `document_number` VARCHAR(50) NOT NULL COMMENT 'CPF or Passport',
  `country` VARCHAR(100) NOT NULL,
  `institution` VARCHAR(255) NOT NULL,
  `category` ENUM('professional', 'student') NOT NULL,
  `role` ENUM('user', 'moderator', 'admin') NOT NULL DEFAULT 'user',
  `payment_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `payment_proof_path` VARCHAR(500) DEFAULT NULL,
  `google_id` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_google_id` (`google_id`),
  KEY `idx_role` (`role`),
  KEY `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: areas
-- ============================================
CREATE TABLE `areas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: area_moderators
-- ============================================
CREATE TABLE `area_moderators` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `area_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_area_user` (`area_id`, `user_id`),
  CONSTRAINT `fk_am_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_am_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: abstracts
-- ============================================
CREATE TABLE `abstracts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `area_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `status` ENUM('pending', 'accepted', 'rejected', 'accepted_with_corrections', 'pending_revision') NOT NULL DEFAULT 'pending',
  `rejection_reason` TEXT DEFAULT NULL,
  `correction_notes` TEXT DEFAULT NULL,
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_area_id` (`area_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_abs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_abs_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: abstract_history
-- ============================================
CREATE TABLE `abstract_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `abstract_id` INT UNSIGNED NOT NULL,
  `previous_status` ENUM('pending', 'accepted', 'rejected', 'accepted_with_corrections', 'pending_revision') DEFAULT NULL,
  `new_status` ENUM('pending', 'accepted', 'rejected', 'accepted_with_corrections', 'pending_revision') NOT NULL,
  `justification` TEXT DEFAULT NULL,
  `changed_by_user_id` INT UNSIGNED NOT NULL,
  `changed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_abstract_id` (`abstract_id`),
  KEY `idx_changed_by` (`changed_by_user_id`),
  CONSTRAINT `fk_ah_abstract` FOREIGN KEY (`abstract_id`) REFERENCES `abstracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ah_user` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: system_settings
-- ============================================
CREATE TABLE `system_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: jwt_blacklist (for token revocation)
-- ============================================
CREATE TABLE `jwt_blacklist` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(1000) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`(768)),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: user_refresh_tokens
-- ============================================
CREATE TABLE `user_refresh_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL COMMENT 'SHA256 hash of the token',
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_urt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INITIAL DATA
-- ============================================

-- Default admin user (password: admin123 - must be changed!)
INSERT INTO `users` (`email`, `password_hash`, `full_name`, `date_of_birth`, `document_number`, `country`, `institution`, `category`, `role`, `payment_status`) 
VALUES ('admin@congress.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '1990-01-01', '00000000000', 'Brazil', 'Congress Organization', 'professional', 'admin', 'approved');

-- System settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) 
VALUES 
('submission_deadline', '2025-12-31 23:59:59', 'Deadline for abstract submissions'),
('max_abstracts_per_user', '2', 'Maximum number of abstracts per user'),
('site_name', 'Congress Management System', 'Name of the congress system');

COMMIT;
