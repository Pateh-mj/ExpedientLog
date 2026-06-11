-- ExpedientLog — Production Schema
-- Run this against your MySQL/MariaDB instance.
-- Safe to run on an existing exp_log database (uses ALTER to migrate).

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+02:00";

-- ── Create database if needed ─────────────────────────────
CREATE DATABASE IF NOT EXISTS `exp_log`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `exp_log`;

-- ── users ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('employee','supervisor','admin') NOT NULL DEFAULT 'employee',
  `department` VARCHAR(50)  NOT NULL DEFAULT 'General',
  `full_name`  VARCHAR(100) NOT NULL DEFAULT '',
  `email`      VARCHAR(150) NOT NULL DEFAULT '',
  `phone`      VARCHAR(30)  NOT NULL DEFAULT '',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration: add new columns if upgrading from v1
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `department`,
  ADD COLUMN IF NOT EXISTS `email`     VARCHAR(150) NOT NULL DEFAULT '' AFTER `full_name`,
  ADD COLUMN IF NOT EXISTS `phone`     VARCHAR(30)  NOT NULL DEFAULT '' AFTER `email`;

-- ── tickets ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tickets` (
  `id`           INT          NOT NULL AUTO_INCREMENT,
  `user_id`      INT          NOT NULL,
  `task`         TEXT         NOT NULL,
  `project`      VARCHAR(100) NOT NULL DEFAULT 'General / Other',
  `is_knowledge` TINYINT(1)   NOT NULL DEFAULT 0,
  `category`     VARCHAR(100) NULL DEFAULT NULL,
  `image_path`   VARCHAR(255) NULL DEFAULT NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_date`   (`user_id`, `created_at`),
  KEY `idx_knowledge`   (`is_knowledge`),
  CONSTRAINT `fk_ticket_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── announcements ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `announcements` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(200) NOT NULL,
  `body`       TEXT         NOT NULL,
  `created_by` INT          NOT NULL,
  `is_pinned`  TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pinned_date` (`is_pinned`, `created_at`),
  CONSTRAINT `fk_ann_user`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Backfill full_name from username for existing rows ────
UPDATE `users` SET `full_name` = `username` WHERE `full_name` = '';
