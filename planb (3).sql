-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2025 at 04:03 PM
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
-- Database: `planb`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL,
  `year` varchar(20) NOT NULL,
  `submission_deadline` date DEFAULT NULL,
  `final_closure_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `year`, `submission_deadline`, `final_closure_date`, `is_current`) VALUES
(2, '2024/2025', '2025-04-30', '2025-08-15', 1),
(3, '2025/2026', NULL, '2026-12-12', 0);

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `contribution_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contributions`
--

CREATE TABLE `contributions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `academic_year_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `submission_date` datetime DEFAULT current_timestamp(),
  `status` enum('Pending','submitted','approved','rejected','selected') DEFAULT 'Pending',
  `last_updated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `abstract` text DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `word_file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculties`
--

CREATE TABLE `faculties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculties`
--

INSERT INTO `faculties` (`id`, `name`, `code`) VALUES
(1, 'Faculty of Arts', 'ARTS'),
(2, 'Faculty of Science', 'SCI'),
(3, 'Faculty of Engineering', 'ENG'),
(4, 'Faculty of Business', 'BUS');

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE `images` (
  `id` int(11) NOT NULL,
  `contribution_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `email` varchar(255) NOT NULL,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `last_failed_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`email`, `failed_attempts`, `last_failed_at`) VALUES
('admin1@university.edu', 5, '2025-04-17 13:10:25'),
('coordinator@university.edu', 6, '2025-04-26 21:48:47'),
('man@university.edu', 2, '2025-05-01 12:07:06'),
('martha@admin.com', 1, '2025-04-26 22:39:30'),
('mulenga@gmail.com', 3, '2025-05-01 12:06:46'),
('mulenga@university.edu', 1, '2025-05-01 12:05:20'),
('student@gmail.com', 5, '2025-04-15 13:39:30');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rejected_contributions`
--

CREATE TABLE `rejected_contributions` (
  `id` int(11) NOT NULL,
  `contribution_id` int(11) NOT NULL,
  `rejected_by` int(11) NOT NULL,
  `rejected_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `selected_contributions`
--

CREATE TABLE `selected_contributions` (
  `id` int(11) NOT NULL,
  `contribution_id` int(11) NOT NULL,
  `selected_by` int(11) NOT NULL,
  `selected_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `page_url` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `accessed_at` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `terms_conditions`
--

CREATE TABLE `terms_conditions` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `version` varchar(20) NOT NULL,
  `effective_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terms_conditions`
--

INSERT INTO `terms_conditions` (`id`, `content`, `version`, `effective_date`, `is_active`) VALUES
(1, 'Default Terms and Conditions go here...\',', '2', '2025-04-01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','marketing_manager','marketing_coordinator','student','guest') NOT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_locked` tinyint(1) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `approved` tinyint(4) DEFAULT 1,
  `status` varchar(20) DEFAULT 'pending',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `role`, `faculty_id`, `last_login`, `created_at`, `is_locked`, `locked_until`, `approved`, `status`, `is_active`) VALUES
(4, 'test_student', '$2y$10$EXAMPLEHASH', 'student@gmail.com', 'Test', 'Student', 'student', 1, NULL, '2025-04-11 21:18:49', 0, NULL, 1, 'pending', 1),
(5, 'test_coordinator', '$2y$10$EXAMPLEHASH', 'coordinator@gmail.com', 'Test', 'Coordinator', 'marketing_coordinator', 1, NULL, '2025-04-11 21:18:49', 0, NULL, 1, 'pending', 1),
(6, 'test_manager', '$2y$10$EXAMPLEHASH', 'manager@gmail.com', 'Test', 'Manager', 'marketing_manager', NULL, NULL, '2025-04-11 21:18:49', 0, NULL, 1, 'pending', 1),
(7, 'student1', '$2y$10$4H27XOfqDFxGoVfEw3bzIOqKKnht0/0wnlqObg1rNL7mGkl5tsDX6', 'student1@gmail.com', 'Alice', 'Mwansa', 'student', 1, NULL, '2025-04-11 21:51:45', 0, NULL, 1, 'pending', 1),
(8, 'student2', '$2y$10$4H27XOfqDFxGoVfEw3bzIOqKKnht0/0wnlqObg1rNL7mGkl5tsDX6', 'student2@gmail.com', 'Brian', 'Phiri', 'student', 2, NULL, '2025-04-11 21:51:45', 0, NULL, 1, 'pending', 0),
(9, 'coordinator1', '$2y$10$4H27XOfqDFxGoVfEw3bzIOqKKnht0/0wnlqObg1rNL7mGkl5tsDX6', 'coord1@gmail.com', 'Cathy', 'Lungu', 'marketing_coordinator', 1, NULL, '2025-04-11 21:51:45', 0, NULL, 1, 'pending', 1),
(10, 'manager1', '$2y$10$4H27XOfqDFxGoVfEw3bzIOqKKnht0/0wnlqObg1rNL7mGkl5tsDX6', 'manager1@gmail.com', 'Dan', 'Zimba', 'marketing_manager', NULL, NULL, '2025-04-11 21:51:45', 0, NULL, 1, 'pending', 1),
(11, 'admin1', '$2y$10$4H27XOfqDFxGoVfEw3bzIOqKKnht0/0wnlqObg1rNL7mGkl5tsDX6', 'admin1@university.edu', 'Admin', 'System', 'admin', NULL, NULL, '2025-04-11 21:51:45', 0, NULL, 1, 'pending', 1),
(12, 'guest1', '$2y$10$4H27XOfqDFxGoVfEw3bzIOqKKnht0/0wnlqObg1rNL7mGkl5tsDX6', 'guest1@gmail.com', 'Guest', 'User', 'guest', NULL, NULL, '2025-04-11 21:51:45', 0, NULL, 1, 'pending', 1),
(18, 'Crispin', '$2y$10$x8/WXgaVnMHLwHGTG0lOvuhSaf9vvUwEdc4YOgzZsW2.ipWPvtq5O', 'crispinngulube1@gmail.com', 'Crispin', 'Ngulube', 'student', NULL, '2025-04-26 01:33:17', '2025-04-16 15:35:49', 0, NULL, 1, 'pending', 1),
(19, 'Admin', '$2y$10$QOBfsnZ4SZyGBoz15KDbh.bPRtckmIY06gouXLnq.M.2z/BQ7Q9nC', 'admin@admin.com', '', '', 'admin', NULL, '2025-05-01 12:09:33', '2025-04-17 14:30:38', 0, NULL, 1, 'pending', 1),
(20, 'Sarah', '$2y$10$EvEgBXPCcPT77x6POcHb.OVF/RGkcmemFXVDvOs06xuZXusT8mWwS', 'sarah@university.edu', 'Sarah', 'Zulu', 'student', 4, '2025-05-01 12:07:11', '2025-04-24 00:49:31', 0, NULL, 0, 'pending', 1),
(21, 'MichealBine', '$2y$10$jWDM6bg0MGPK96UAJ3wf5.TCf2lBjqgrgw5X15Owk8cSMdxWWH98W', 'michealbine@gmail.com', 'Micheal', 'Bine', 'student', 3, '2025-04-29 01:38:58', '2025-04-24 09:12:27', 0, NULL, 0, 'pending', 1),
(22, 'Mulenga', '$2y$10$uj8oernCwzYlhfO0V9Y5XObekKgKv/M9k83nD7YPUp19aSmAXTvMi', 'mulenga@gmail.com', 'Mulenga', 'Bwalya', 'student', 3, '2025-04-24 09:23:52', '2025-04-24 09:23:26', 0, NULL, 0, 'pending', 1),
(23, 'freshchisenga5@gmail.com', '$2y$10$10Rsut654VPZhIDbj/HyY.7U.ybRRUTdJa/5LKmBmMffZ.ILTUgY.', 'freshchisenga5@gmail.com', 'Fresh', 'Chisenga', 'marketing_coordinator', 1, '2025-04-27 03:38:49', '2025-04-25 18:44:41', 0, NULL, 0, 'pending', 1),
(24, 'Fresh', '$2y$10$KIRtVPe7F3IR1nHA9J2/xO7HkD3m.UPu.eXAHuVQjK14kP.9d.E6q', 'freshchisenga@gmail.com', 'Fresh', 'Chisenga', 'marketing_coordinator', 3, '2025-05-01 11:43:59', '2025-04-25 20:55:19', 0, NULL, 0, 'pending', 1),
(25, 'Martha', '$2y$10$DKQMjjaqPGQxtzJB1ZnWbuwEUcDb4woxwV8jYCVhfwRxBZSwuYZNW', 'martha@university.edu', 'Matha', 'Mulenga', 'marketing_manager', 4, '2025-05-01 13:01:02', '2025-04-25 23:53:31', 0, NULL, 1, 'approved', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_terms`
--

CREATE TABLE `user_terms` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `terms_id` int(11) NOT NULL,
  `accepted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_terms`
--

INSERT INTO `user_terms` (`id`, `user_id`, `terms_id`, `accepted_at`) VALUES
(1, 18, 1, '2025-04-16 15:35:49'),
(2, 20, 1, '2025-04-24 00:49:31'),
(3, 21, 1, '2025-04-24 09:12:27'),
(4, 22, 1, '2025-04-24 09:23:26'),
(5, 23, 1, '2025-04-25 18:44:41'),
(6, 24, 1, '2025-04-25 20:55:19'),
(7, 25, 1, '2025-04-25 23:53:31');

-- --------------------------------------------------------

--
-- Table structure for table `word_documents`
--

CREATE TABLE `word_documents` (
  `id` int(11) NOT NULL,
  `contribution_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year` (`year`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contribution_id` (`contribution_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `contributions`
--
ALTER TABLE `contributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `faculties`
--
ALTER TABLE `faculties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contribution_id` (`contribution_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `rejected_contributions`
--
ALTER TABLE `rejected_contributions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `selected_contributions`
--
ALTER TABLE `selected_contributions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contribution_id` (`contribution_id`),
  ADD KEY `selected_by` (`selected_by`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `terms_conditions`
--
ALTER TABLE `terms_conditions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `user_terms`
--
ALTER TABLE `user_terms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `terms_id` (`terms_id`);

--
-- Indexes for table `word_documents`
--
ALTER TABLE `word_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contribution_id` (`contribution_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contributions`
--
ALTER TABLE `contributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `faculties`
--
ALTER TABLE `faculties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `images`
--
ALTER TABLE `images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rejected_contributions`
--
ALTER TABLE `rejected_contributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `selected_contributions`
--
ALTER TABLE `selected_contributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `terms_conditions`
--
ALTER TABLE `terms_conditions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `user_terms`
--
ALTER TABLE `user_terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `word_documents`
--
ALTER TABLE `word_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`contribution_id`) REFERENCES `contributions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contributions`
--
ALTER TABLE `contributions`
  ADD CONSTRAINT `contributions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contributions_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contributions_ibfk_3` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`contribution_id`) REFERENCES `contributions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `selected_contributions`
--
ALTER TABLE `selected_contributions`
  ADD CONSTRAINT `selected_contributions_ibfk_1` FOREIGN KEY (`contribution_id`) REFERENCES `contributions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `selected_contributions_ibfk_2` FOREIGN KEY (`selected_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_terms`
--
ALTER TABLE `user_terms`
  ADD CONSTRAINT `user_terms_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_terms_ibfk_2` FOREIGN KEY (`terms_id`) REFERENCES `terms_conditions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `word_documents`
--
ALTER TABLE `word_documents`
  ADD CONSTRAINT `word_documents_ibfk_1` FOREIGN KEY (`contribution_id`) REFERENCES `contributions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
