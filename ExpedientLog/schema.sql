-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2025 at 07:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `exp_log`
--

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_knowledge` tinyint(4) DEFAULT 0,
  `category` varchar(100) DEFAULT 'General',
  `project` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `task`, `created_at`, `updated_at`, `is_knowledge`, `category`, `project`, `image_path`) VALUES
(1, 2, 'I just created this Awesome Workspace!', '2025-11-10 14:51:07', NULL, 0, 'General', NULL, NULL),
(2, 1, 'I SHOWED EDDIE HOW IT WORKS', '2025-11-10 14:57:07', NULL, 0, 'General', NULL, NULL),
(3, 2, 'I will show you what I can do!', '2025-11-10 15:17:06', NULL, 0, 'General', NULL, NULL),
(4, 2, 'I tried to assist a client with choosing the best service.', '2025-11-11 14:53:47', NULL, 0, 'General', NULL, NULL),
(5, 2, 'I showed the project to Rabson.', '2025-11-13 09:00:40', NULL, 0, 'General', NULL, NULL),
(6, 2, 'sfnnwrnw', '2025-11-16 20:21:28', NULL, 0, 'General', NULL, NULL),
(7, 2, 'ymyee,etueeuuirutityfuduf', '2025-11-16 20:51:29', NULL, 0, 'General', NULL, NULL),
(23, 3, 'Tried out the enhanced layout', '2025-11-26 13:26:37', NULL, 0, 'General', 'General / Other', NULL),
(24, 3, 'Tried out the enhanced layout with project category added', '2025-11-26 13:27:19', NULL, 0, 'General', 'IT Systems Upgrade', NULL),
(25, 3, 'Tried out the enhanced layout with project category added, and pushed to knowledge base', '2025-11-26 13:27:54', NULL, 1, 'IT / Tech', 'Lusaka Branch Operations', NULL),
(26, 3, 'So, what is it looking like from the Supervisor\'s side?', '2025-11-27 09:53:35', NULL, 0, 'General', 'IT Systems Upgrade', NULL),
(27, 1, 'Hmmm, so, it works... Lemme push this as new knowledge.', '2025-11-27 09:56:19', NULL, 1, 'Lessons Learned', 'HR & Recruitment', NULL),
(28, 2, 'What is in the knowledge base today?', '2025-11-27 09:58:32', NULL, 0, 'General', 'Field Work', NULL),
(29, 1, 'dlv anvkj', '2025-11-27 20:00:11', NULL, 0, 'General', 'General / Other', NULL),
(30, 1, 'cscscjfv,nkzn,jvm jdsvksjvhsvjk', '2025-11-27 20:01:16', NULL, 0, 'General', 'IT Systems Upgrade', NULL),
(35, 2, 'Tried out the enhanced layout', '2025-11-27 20:25:09', NULL, 0, 'General', 'Q4 Financial Audit', NULL),
(36, 2, 'Tried out the enhanced layout with project category added, and did not pushto knowledge base', '2025-11-27 20:26:56', NULL, 0, 'General', 'HR & Recruitment', NULL),
(37, 5, 'I am a weirdo!!!!!! A big one Again!', '2025-11-27 20:31:09', NULL, 0, 'General', 'HR & Recruitment', NULL),
(38, 5, 'I am a weirdo!!!!!! A big one Again! Actually, I\'ll post it in the knowledge base for all to know.', '2025-11-27 20:31:55', NULL, 1, 'Lessons Learned', 'HR & Recruitment', NULL),
(39, 2, 'I was showing this system to my Family.', '2025-12-01 19:14:33', NULL, 0, 'General', 'IT Systems Upgrade', NULL),
(40, 3, 'I was showing this system to my Family, but I also saved this Information as Knowledge.', '2025-12-01 19:17:01', NULL, 1, 'Client Notes', 'HR & Recruitment', NULL),
(41, 2, 'Tried to log an activity with a photo upload. Hey the edit modal fix works.\r\nLemme add it as knowledge', '2025-12-02 15:00:49', '2025-12-02 15:18:24', 1, 'Lessons Learned', 'IT Systems Upgrade', 'uploads/1764687649_usb-3-0-sata-iii-hard-drive-adapter.jpg'),
(42, 3, 'Tried also to log an activity with a photo upload, and save it as knowledge.', '2025-12-02 15:06:21', NULL, 1, 'General', 'Field Work', 'uploads/1764687981_c901c7918af34451bbb08d0fdef15d7b.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('employee','supervisor','admin') NOT NULL DEFAULT 'employee',
  `department` varchar(50) NOT NULL DEFAULT 'General',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `department`, `created_at`) VALUES
(1, 'Administrator', '$2y$10$E9MiXONOCb7tFqyyMBT.buBUdCicSZBnOCyd0BwNPYwhQnRfYHqMe', 'supervisor', 'General', '2025-11-10 14:48:27'),
(2, 'Expedia1', '$2y$10$Wt4gZ.gVSEwKMlO.49qvkulm46V7/70IP2n55IiCMYLvLwh6RMYfq', 'employee', 'Technical', '2025-11-10 14:50:26'),
(3, 'Expedia2', '$2y$10$HUWB08B9HOqdkh3Ufzal8u/ZQxUl4PuDDLJBS65YsPJpZozuefBaq', 'employee', 'Technical', '2025-11-26 12:06:51'),
(5, 'nchimunya', '$2y$10$zoNPjtzOQoELVfTMpFNDauSXn82CmnAZfii7e0SXE0ez00kO4LmNu', 'employee', 'HR', '2025-11-27 20:30:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tickets_user_id` (`user_id`),
  ADD KEY `idx_tickets_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
