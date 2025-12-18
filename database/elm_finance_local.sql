-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 18, 2025 at 11:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elm_finance_local`
--

-- --------------------------------------------------------

--
-- Table structure for table `elm_budgets`
--

CREATE TABLE `elm_budgets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` enum('food','transport','essentials','entertainment','other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `month_year` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elm_budgets`
--

INSERT INTO `elm_budgets` (`id`, `user_id`, `category`, `amount`, `month_year`, `created_at`) VALUES
(1, 1, 'food', 0.01, '0000-00-00', '2025-12-17 10:41:44'),
(2, 1, 'entertainment', 500.00, '0000-00-00', '2025-12-16 23:38:35'),
(3, 1, 'transport', 0.02, '0000-00-00', '2025-12-17 10:49:14'),
(4, 1, 'food', 21000.00, '2025-12-01', '2025-12-17 22:43:35'),
(5, 1, 'transport', 500.00, '2025-12-01', '2025-12-18 00:03:01');

-- --------------------------------------------------------

--
-- Table structure for table `elm_expenses`
--

CREATE TABLE `elm_expenses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` enum('food','transport','essentials','entertainment','other') NOT NULL,
  `description` varchar(255) NOT NULL,
  `expense_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elm_expenses`
--

INSERT INTO `elm_expenses` (`id`, `user_id`, `amount`, `category`, `description`, `expense_date`, `created_at`) VALUES
(1, 1, 500.00, 'food', 'Lunch at hallmark ', '2025-12-16', '2025-12-16 22:15:05'),
(2, 1, 20.00, 'transport', 'Uber to Kitaase', '2025-11-16', '2025-12-16 22:17:05'),
(3, 1, 56.00, 'essentials', '12 plantain chips ', '2025-12-17', '2025-12-16 23:42:09'),
(4, 1, 189.00, 'food', 'jollof', '2025-12-18', '2025-12-18 00:02:12'),
(5, 1, 200.00, 'transport', 'uber', '2025-12-18', '2025-12-18 00:57:28');

-- --------------------------------------------------------

--
-- Table structure for table `elm_goals`
--

CREATE TABLE `elm_goals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `goal_name` varchar(100) NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `current_amount` decimal(10,2) DEFAULT 0.00,
  `deadline` date NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','completed','failed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elm_goals`
--

INSERT INTO `elm_goals` (`id`, `user_id`, `goal_name`, `target_amount`, `current_amount`, `deadline`, `category`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Start a business ', 5000.00, 200.00, '2025-12-25', 'other', '', 'active', '2025-12-17 00:06:06', '2025-12-17 00:06:22'),
(2, 1, 'laptop', 200.00, 0.00, '2025-12-22', 'electronics', '', 'active', '2025-12-17 23:26:54', '2025-12-17 23:26:54');

-- --------------------------------------------------------

--
-- Table structure for table `elm_reports`
--

CREATE TABLE `elm_reports` (
  `id` int(11) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`report_data`)),
  `generated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elm_reports`
--

INSERT INTO `elm_reports` (`id`, `report_type`, `report_data`, `generated_by`, `created_at`) VALUES
(1, 'Financial Summary', '{\"summary\":{\"total_amount\":\"965.00\",\"total_count\":5,\"generated_at\":\"2025-12-18 09:03:28\"},\"categories\":[{\"category\":\"food\",\"total\":\"689.00\"},{\"category\":\"transport\",\"total\":\"220.00\"},{\"category\":\"essentials\",\"total\":\"56.00\"}],\"high_value_items\":[]}', 6, '2025-12-18 08:03:28'),
(2, 'Financial Summary', '{\"summary\":{\"total_amount\":\"965.00\",\"total_count\":5,\"generated_at\":\"2025-12-18 09:05:22\"},\"categories\":[{\"category\":\"food\",\"total\":\"689.00\"},{\"category\":\"transport\",\"total\":\"220.00\"},{\"category\":\"essentials\",\"total\":\"56.00\"}],\"high_value_items\":[]}', 6, '2025-12-18 08:05:22'),
(3, 'Student Detail', '{\"summary\":{\"total_amount\":\"0.00\",\"total_count\":0,\"target_username\":\"ohene\",\"target_user_id\":\"6\",\"generated_at\":\"2025-12-18 09:13:18\"},\"categories\":[],\"high_value_items\":[]}', 6, '2025-12-18 08:13:18'),
(4, 'Student Detail', '{\"summary\":{\"total_amount\":\"0.00\",\"total_count\":0,\"target_username\":\"Princess\",\"target_user_id\":\"2\",\"generated_at\":\"2025-12-18 09:34:06\"},\"categories\":[],\"high_value_items\":[]}', 6, '2025-12-18 08:34:06'),
(5, 'Student Detail', '{\"summary\":{\"total_amount\":\"965.00\",\"total_count\":5,\"target_username\":\"Tracy2456\",\"target_user_id\":\"1\",\"generated_at\":\"2025-12-18 09:34:28\"},\"categories\":[{\"category\":\"food\",\"total\":\"689.00\"},{\"category\":\"transport\",\"total\":\"220.00\"},{\"category\":\"essentials\",\"total\":\"56.00\"}],\"high_value_items\":[{\"description\":\"jollof\",\"amount\":\"189.00\",\"expense_date\":\"2025-12-18\"},{\"description\":\"uber\",\"amount\":\"200.00\",\"expense_date\":\"2025-12-18\"},{\"description\":\"12 plantain chips \",\"amount\":\"56.00\",\"expense_date\":\"2025-12-17\"},{\"description\":\"Lunch at hallmark \",\"amount\":\"500.00\",\"expense_date\":\"2025-12-16\"},{\"description\":\"Uber to Kitaase\",\"amount\":\"20.00\",\"expense_date\":\"2025-11-16\"}]}', 6, '2025-12-18 08:34:28'),
(6, 'Personal Summary', '{\"summary\":{\"total_amount\":\"965.00\",\"total_count\":5,\"target_username\":\"Tracy2456\",\"target_user_id\":1,\"generated_at\":\"2025-12-18 09:39:08\"},\"categories\":[{\"category\":\"food\",\"total\":\"689.00\"},{\"category\":\"transport\",\"total\":\"220.00\"},{\"category\":\"essentials\",\"total\":\"56.00\"}],\"high_value_items\":[{\"description\":\"jollof\",\"amount\":\"189.00\",\"expense_date\":\"2025-12-18\"},{\"description\":\"uber\",\"amount\":\"200.00\",\"expense_date\":\"2025-12-18\"},{\"description\":\"12 plantain chips \",\"amount\":\"56.00\",\"expense_date\":\"2025-12-17\"},{\"description\":\"Lunch at hallmark \",\"amount\":\"500.00\",\"expense_date\":\"2025-12-16\"},{\"description\":\"Uber to Kitaase\",\"amount\":\"20.00\",\"expense_date\":\"2025-11-16\"}]}', 1, '2025-12-18 08:39:08');

-- --------------------------------------------------------

--
-- Table structure for table `elm_users`
--

CREATE TABLE `elm_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `university` varchar(100) DEFAULT 'Ashesi University',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verification_token` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `role` enum('student','admin') NOT NULL DEFAULT 'student',
  `merchant_type` varchar(100) DEFAULT NULL,
  `campus_location` varchar(100) DEFAULT NULL,
  `shop_name` varchar(200) DEFAULT NULL,
  `is_superadmin` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_count` int(11) DEFAULT 0,
  `account_status` enum('active','suspended','deleted') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elm_users`
--

INSERT INTO `elm_users` (`id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `university`, `created_at`, `verification_token`, `is_verified`, `is_active`, `role`, `merchant_type`, `campus_location`, `shop_name`, `is_superadmin`, `last_login`, `login_count`, `account_status`) VALUES
(1, 'Tracy2456', 'princess.agyemfra@ashesi.edu.gh', '$2y$10$87OJGQAMfJrsLXYEkQU6SO2yu8uR1ox5jGzheqJNMsx2G50EKsdWW', 'Princess', 'Agyemfra', 'Ashesi University', '2025-11-24 10:51:09', NULL, 1, 1, 'student', NULL, NULL, NULL, 0, '2025-12-18 08:37:19', 0, 'active'),
(2, 'Princess', 'tutu@ashesi.edu.gh', '$2y$10$AFwQ5RHXt49E0IAZixqaveeUDV2pnbxVgkSCt5kg6.uYkR6CsktDm', 'Princess', 'Agyemfra', 'Ashesi University', '2025-12-09 22:46:36', NULL, 0, 1, 'student', NULL, NULL, NULL, 0, NULL, 0, 'active'),
(3, 'Kojo', 'kojo@ashesi.edu.gh', '$2y$10$t/f6gUpfHfs1.pRl6f79jeE1zfP6s06OxLXtDArnjRukMsabnsQE.', 'Kojo', 'kooko', 'Ashesi University', '2025-12-09 23:01:43', NULL, 0, 1, 'student', NULL, NULL, NULL, 0, NULL, 0, 'active'),
(4, 'testuser', 'test@ashesi.edu.gh', '$2y$10$3OkNlWt8r/gjSFCIwio8KOC2NpPckd2tLMLoj.vf3xpxutXWpYaFa', 'Test', 'User', 'Ashesi University', '2025-12-09 23:40:33', NULL, 1, 1, 'student', NULL, NULL, NULL, 0, NULL, 0, 'active'),
(6, 'ohene', 'ohene@ashesi.edu.gh', '$2y$10$/qN78CcgkzOruYR.cZAjC.bAOKNv1oFHQHOvobhqufR2C3Sd3q0WK', 'Ohene', 'Agyemfra', 'Ashesi University', '2025-12-18 05:01:32', NULL, 0, 1, 'admin', NULL, NULL, NULL, 0, '2025-12-18 06:37:25', 0, 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `elm_budgets`
--
ALTER TABLE `elm_budgets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `elm_expenses`
--
ALTER TABLE `elm_expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `elm_goals`
--
ALTER TABLE `elm_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`);

--
-- Indexes for table `elm_reports`
--
ALTER TABLE `elm_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `idx_report_type` (`report_type`,`created_at`);

--
-- Indexes for table `elm_users`
--
ALTER TABLE `elm_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `elm_budgets`
--
ALTER TABLE `elm_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `elm_expenses`
--
ALTER TABLE `elm_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `elm_goals`
--
ALTER TABLE `elm_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `elm_reports`
--
ALTER TABLE `elm_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `elm_users`
--
ALTER TABLE `elm_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `elm_goals`
--
ALTER TABLE `elm_goals`
  ADD CONSTRAINT `elm_goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `elm_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `elm_reports`
--
ALTER TABLE `elm_reports`
  ADD CONSTRAINT `elm_reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `elm_users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
