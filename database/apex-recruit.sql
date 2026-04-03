-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 03, 2026 at 06:59 PM
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
-- Database: `apex-recruit`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `candidate_id` int(11) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` enum('applied','reviewed','shortlisted','rejected') DEFAULT 'applied',
  `is_deleted` tinyint(1) DEFAULT 0,
  `added_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `resume` varchar(255) DEFAULT NULL,
  `highest_qualification` varchar(512) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `nationality` varchar(276) DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `current_job_title` varchar(150) DEFAULT NULL,
  `current_company` varchar(150) DEFAULT NULL,
  `total_experience` decimal(4,1) DEFAULT NULL,
  `current_salary` varchar(50) DEFAULT NULL,
  `expected_salary` varchar(50) DEFAULT NULL,
  `preferred_location` varchar(150) DEFAULT NULL,
  `job_type` varchar(50) DEFAULT NULL,
  `notice_period` varchar(50) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `added_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`id`, `user_id`, `resume`, `highest_qualification`, `skills`, `nationality`, `experience`, `education`, `full_name`, `email`, `phone`, `city`, `state`, `country`, `current_job_title`, `current_company`, `total_experience`, `current_salary`, `expected_salary`, `preferred_location`, `job_type`, `notice_period`, `linkedin_url`, `portfolio_url`, `profile_photo`, `date_of_birth`, `gender`, `is_deleted`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-29 19:35:54', '2026-03-29 19:35:54'),
(2, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-29 19:36:19', '2026-03-29 19:36:19'),
(3, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-31 09:55:33', '2026-03-31 09:55:33'),
(4, 19, 'assets/uploads/resumes/1774988457_obc_ncl_260330_114409_125455.pdf', 'BE/Btech', 'nv, nvjh, hjhg', 'indian', '[{\"company\":\"mmtech\",\"job_title\":\"BA\",\"start_date\":\"2026-01-01\",\"end_date\":\"2026-04-02\",\"is_current\":0,\"employment_type\":\"Full-time\"}]', '', 'Harry Warner', 'mithawala8@gmail.com', '123564897', 'Austin', 'Texas', 'India', '', '', 5.0, '12000', '50000', 'texas', 'Part-time', '0', '', '', '', '2026-04-01', 'Male', 0, 1, '2026-03-31 20:20:57', '2026-04-02 20:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `candidate_experience`
--

CREATE TABLE `candidate_experience` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `job_title` varchar(150) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `employment_type` varchar(50) DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidate_experience`
--

INSERT INTO `candidate_experience` (`id`, `candidate_id`, `company_name`, `job_title`, `start_date`, `end_date`, `is_current`, `employment_type`, `added_by`, `created_at`, `updated_at`) VALUES
(14, 4, 'mmtech', 'BA', '2026-01-01', '2026-04-02', 0, 'Full-time', 1, '2026-04-02 20:12:24', '2026-04-02 20:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `user_id`, `company_name`, `logo`, `description`, `website`, `is_deleted`, `city`, `state`, `country`, `address_line1`, `address_line2`, `phone`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 2, 'Apex', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-29 19:34:24', '2026-03-29 20:05:31'),
(2, 5, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-30 19:59:42', '2026-04-02 20:11:42'),
(3, 6, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-30 20:07:17', '2026-03-30 20:26:11'),
(4, 9, 'Roth and Stout Traders', '', 'sfsdsddfg', 'https://www.wizu.org.au', 0, 'Torrento', 'Texas', 'usa', '456 Oak Drive', 'floor-1', '123456789', 1, '2026-03-30 21:01:20', '2026-03-30 21:42:20'),
(5, 10, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-31 09:53:59', '2026-03-31 09:53:59');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `nice_to_have` text DEFAULT NULL,
  `perks` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `work_mode` varchar(512) NOT NULL DEFAULT 'on-site',
  `employment_type` enum('full-time','part-time','contract','internship','freelance','temporary') NOT NULL DEFAULT 'full-time',
  `salary` varchar(100) DEFAULT NULL,
  `salary_min` int(10) UNSIGNED DEFAULT NULL,
  `salary_max` int(10) UNSIGNED DEFAULT NULL,
  `salary_visible` tinyint(1) NOT NULL DEFAULT 1,
  `experience_required` varchar(50) DEFAULT NULL,
  `education` varchar(100) DEFAULT NULL,
  `openings` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `deadline` date DEFAULT NULL,
  `duration_days` smallint(5) UNSIGNED NOT NULL DEFAULT 30,
  `apply_email` varchar(150) DEFAULT NULL,
  `apply_url` varchar(500) DEFAULT NULL,
  `status` enum('active','closed','pending') DEFAULT 'pending',
  `is_deleted` tinyint(1) DEFAULT 0,
  `added_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `company_id`, `department_id`, `category_id`, `title`, `description`, `responsibilities`, `requirements`, `nice_to_have`, `perks`, `location`, `city`, `state`, `country`, `work_mode`, `employment_type`, `salary`, `salary_min`, `salary_max`, `salary_visible`, `experience_required`, `education`, `openings`, `deadline`, `duration_days`, `apply_email`, `apply_url`, `status`, `is_deleted`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 4, 'Java Developer', 'bnbmbnm mmbmmbmb', 'bmbnmbm', 'mnbmbnm', 'bnmbnmbn', 'bnmbm', 'Ahmedabad', 'Ahmedabad', 'Gujarat', 'India', 'hybrid', 'contract', '1000-2000', 30000, 120000, 0, '5', '', 1, '2026-04-11', 30, 'test.user@example.com', '', 'active', 0, 1, '2026-04-02 17:39:56', '2026-04-02 20:07:44'),
(2, 1, 1, 4, 'Software Developer', 'Webdeveloper and software developer in fullstack', 'bmbnmbm', 'mnbmbnm', 'bnmbnmbn', 'bnmbm', 'Ahmedabad', 'Ahmedabad', 'Gujarat', 'India', 'remote', 'freelance', '1000-2000', 30000, 120000, 0, '5', 'High School', 1, '2026-04-11', 30, 'test.user@example.com', '', 'active', 0, 1, '2026-04-02 17:40:59', '2026-04-02 20:08:36');

-- --------------------------------------------------------

--
-- Table structure for table `job_categories`
--

CREATE TABLE `job_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_categories`
--

INSERT INTO `job_categories` (`id`, `name`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Mohammed Mithawala', 1, 1, '2026-04-01 12:54:29', '2026-04-01 12:54:29'),
(4, 'Training And Developmnent', 1, 1, '2026-04-01 13:28:23', '2026-04-01 13:51:50');

-- --------------------------------------------------------

--
-- Table structure for table `job_departments`
--

CREATE TABLE `job_departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_departments`
--

INSERT INTO `job_departments` (`id`, `name`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Marketing and Finance', 1, 1, '2026-04-01 13:54:01', '2026-04-01 13:58:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(512) DEFAULT NULL,
  `last_name` varchar(512) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','company','candidate') DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `added_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `role`, `is_deleted`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 'Apex Admin', NULL, 'admin@admin.com', '$2y$10$I0TBa5T7Ry34deYHme7GvuVodqZCwSpzISEDtgWmWkka2vh0tePsS', 'admin', 0, NULL, '2026-03-29 18:30:26', '2026-03-29 20:26:50'),
(2, 'Ira Freeman', NULL, 'qeqoqofoq@mailinator.com', '$2y$10$u/lx8pFxa8CXENQYgzvPvO6ZjI6oGPDWGiL29U.vLnDGF5DEMcY8y', 'company', 0, 0, '2026-03-29 19:34:24', '2026-03-29 19:34:24'),
(3, 'Hilda Serrano', NULL, 'bobisix@mailinator.com', '$2y$10$npfgfZxjOx.Y5Gydl5DW5.EPvT/n.fwRuBjlWgjVVAmqVZLp4leKm', 'candidate', 0, 0, '2026-03-29 19:35:54', '2026-03-29 19:35:54'),
(4, 'Ifeoma Floyd', NULL, 'gega@mailinator.com', '$2y$10$huCXgB5fHpRWgEYnfIt2J.Akx8wP7WUXnUplIi8GHn.LvpH.5MfcK', 'candidate', 0, 0, '2026-03-29 19:36:19', '2026-03-29 19:36:19'),
(5, 'Lane Mcleod', NULL, 'fecapawu@mailinator.com', '$2y$10$dLiHmw20VVAyXjmjk32VVeTQVGyk128gqYcSHlMhEHqYjSyPJPImS', 'company', 1, 0, '2026-03-30 19:59:42', '2026-04-02 20:11:42'),
(6, 'mm', NULL, 'mm@admin.com', '$2y$10$IOktYX2Qxr/X0tJvNzKQlOMdpP4l70tlYFWvuchCPQEuqsIc0kgAm', 'company', 1, 0, '2026-03-30 20:07:17', '2026-03-30 20:26:11'),
(9, 'Mohammed', NULL, 'mohammed.fullstackdeveloper@gmail.com', '$2y$10$4QrbEoodkfhVaZ8zjidSaOqCdUM5HICFiLDN3vUEcs4DRkaK/g9Tq', 'company', 0, 1, '2026-03-30 21:01:20', '2026-03-30 21:42:20'),
(10, 'Harry', 'Smith', 'harry@gmail.com', '$2y$10$msxPMro3pIzj6O23v5t3k.Cm6nBlrxPbzZ6qbGnGGnNnXDc27ohSm', 'company', 0, 0, '2026-03-31 09:53:59', '2026-03-31 09:53:59'),
(11, 'Liam', 'clark', 'clark@gmail.com', '$2y$10$pMZMiDTnXjITpkQos/weHu7COoGFx9RMH72xWW/qI/vp8RZxBFVAS', 'candidate', 0, 0, '2026-03-31 09:55:33', '2026-03-31 09:55:33'),
(19, 'Harry', 'Warner', 'mithawala8@gmail.com', '$2y$10$LP9szJfZ7P3NuzJHVicK1ewr8gmuY2O6Jaga/A3gdJA/VqZZ.6xqS', 'candidate', 0, 1, '2026-03-31 20:20:57', '2026-04-02 20:12:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `candidate_experience`
--
ALTER TABLE `candidate_experience`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `job_categories`
--
ALTER TABLE `job_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_departments`
--
ALTER TABLE `job_departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `candidate_experience`
--
ALTER TABLE `candidate_experience`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `job_categories`
--
ALTER TABLE `job_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `job_departments`
--
ALTER TABLE `job_departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`),
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`);

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `candidate_experience`
--
ALTER TABLE `candidate_experience`
  ADD CONSTRAINT `candidate_experience_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `companies`
--
ALTER TABLE `companies`
  ADD CONSTRAINT `companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `job_departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `jobs_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
