
-- phpMyAdmin SQL Dump
-- Project: project_allocation_system
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `project_allocation_system`;
USE `project_allocation_system`;

-- Table: admin
CREATE TABLE `admin` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`)
);

-- Table: announcements
CREATE TABLE `announcements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` INT(11) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`)
);

-- Table: announcement_reads
CREATE TABLE `announcement_reads` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` INT(11) NOT NULL,
  `student_id` INT(11) NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `announcement_id` (`announcement_id`),
  KEY `student_id` (`student_id`)
);

-- Table: chat_reads
CREATE TABLE `chat_reads` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `chat_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `role` ENUM('student', 'teacher') NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `chat_id` (`chat_id`),
  KEY `user_id` (`user_id`),
  KEY `role` (`role`)
);

-- Table: groups
CREATE TABLE `groups` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `group_name` VARCHAR(100) DEFAULT NULL,
  `guide_id` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guide_id` (`guide_id`)
);

-- Table: group_chats
CREATE TABLE `group_chats` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `group_id` INT(11) DEFAULT NULL,
  `sender_id` INT(11) DEFAULT NULL,
  `sender_role` ENUM('student', 'teacher') DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`)
);

-- Table: group_members
CREATE TABLE `group_members` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `group_id` INT(11) NOT NULL,
  `student_id` INT(11) NOT NULL,
  `joined_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `student_id` (`student_id`)
);

-- Table: project_ideas
CREATE TABLE `project_ideas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `group_id` INT(11) DEFAULT NULL,
  `idea_title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `feedback` TEXT DEFAULT NULL,
  `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `is_editable` TINYINT(1) DEFAULT 0,
  `student_id` INT(11) NOT NULL,
  `final_chosen` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`)
);

-- Table: teachers
CREATE TABLE `teachers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
);

-- Table: users
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `group_id` INT(11) DEFAULT NULL,
  `ie_marks` INT(11) DEFAULT NULL,
  `phase1` INT(11) DEFAULT NULL,
  `phase2` INT(11) DEFAULT NULL,
  `phase3` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
);
