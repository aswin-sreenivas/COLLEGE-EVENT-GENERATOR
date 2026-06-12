-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2026 at 11:55 AM
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
-- Database: `campus_connect`
--

-- --------------------------------------------------------

--
-- Table structure for table `bulletins`
--

CREATE TABLE `bulletins` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(50) DEFAULT 'general',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bulletins`
--

INSERT INTO `bulletins` (`id`, `title`, `description`, `category`, `created_by`, `created_at`) VALUES
(1, 'Welcome to CampusConnect!', 'Welcome to the new CampusConnect platform. Stay updated with all campus events, bulletins, and connect with fellow students. Let\'s make campus life exciting!', 'announcement', 1, '2026-03-22 14:50:04'),
(2, 'Registration Now Open', 'Registration for Tech Hackathon 2026 and Cultural Fest is now open! Limited seats available. Register now to secure your spot.', 'urgent', 1, '2026-03-22 14:55:04'),
(3, 'Exam Schedule Updated', 'The final examination schedule has been updated. Please check your student portal for the revised dates and venues.', 'academic', 1, '2026-03-22 15:00:04');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `rank` varchar(50) DEFAULT NULL,
  `certificate_code` varchar(50) DEFAULT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` enum('tech','cultural','sports','workshop','seminar') DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `venue` varchar(150) DEFAULT NULL,
  `capacity` int(11) DEFAULT 100,
  `organizer_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `event_time` time DEFAULT NULL,
  `date` date DEFAULT NULL,
  `is_past` tinyint(1) DEFAULT 0,
  `images` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `category`, `event_date`, `venue`, `capacity`, `organizer_id`, `status`, `created_at`, `event_time`, `date`, `is_past`, `images`) VALUES
(2, 'mega show', 'mindblowing', 'cultural', '2026-05-01', 'auditorium', 10, NULL, 'approved', '2026-04-26 17:18:34', '15:22:00', NULL, 0, NULL),
(3, 'dance blast', 'fun and engaging', 'cultural', '2026-04-30', 'stage', 100, NULL, 'approved', '2026-04-26 17:57:28', '15:00:00', NULL, 1, '[\"uploads\\/events\\/event_3_1778312078_0.jpg\",\"uploads\\/events\\/event_3_1778312078_1.jpg\",\"uploads\\/events\\/event_3_1778312078_2.jpg\"]'),
(15, 'trip', 'relax', 'cultural', '2026-04-24', 'goa', 100, NULL, 'approved', '2026-04-27 10:34:47', '00:00:00', NULL, 1, NULL),
(16, 'tech fest', 'tech war debate', 'tech', '2026-05-30', 'ccf lab', 100, NULL, 'approved', '2026-04-27 11:10:51', '20:10:00', NULL, 1, NULL),
(17, 'book collection', 'collect old books for library', 'cultural', '2026-05-20', 'seminar hall', 1000, 12, 'approved', '2026-05-09 08:26:41', '17:00:00', NULL, 0, NULL),
(20, 'python lecture', ' future tech', 'tech', '2026-05-30', 'ccf lab', 20, 12, 'approved', '2026-05-09 08:33:34', '15:05:00', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event_images`
--

CREATE TABLE `event_images` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `user_id`, `event_id`, `registered_at`) VALUES
(1, 17, 15, '2026-05-01 11:07:12'),
(2, 18, 15, '2026-05-09 06:44:21'),
(3, 18, 16, '2026-05-09 06:44:25'),
(4, 17, 17, '2026-05-09 08:35:05');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `feedback_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `event_id`, `user_id`, `rating`, `review_text`, `created_at`, `feedback_text`) VALUES
(1, 2, 18, 3, NULL, '2026-05-09 07:12:35', NULL),
(2, 3, 18, 4, NULL, '2026-05-09 07:16:01', NULL),
(3, 3, 11, 3, NULL, '2026-05-09 07:17:51', NULL),
(4, 15, 17, 5, NULL, '2026-05-09 09:08:14', 'had fun');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('student','organizer','admin') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department` varchar(100) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `department`, `profile_photo`, `phone`) VALUES
(11, 'Admin', 'admin@campus.com', '$2y$10$DbbdColuPGQRNtFbXtPizOBkmYDlWaOqrqvA8YXQx4s./mW3e.ZBa', 'admin', '2026-04-30 16:00:39', NULL, NULL, NULL),
(12, 'Organizer 1', 'org1@campus.com', '$2y$10$8uD8v1IiVYkoAyisHlYf9eV7R/1xZ5464UeJkrFxxERs7vyFLLUkW', 'organizer', '2026-04-30 16:00:39', NULL, NULL, NULL),
(13, 'Organizer 2', 'org2@campus.com', '$2y$10$EAYY/9VLeZ80xeaLNe.V2.82qTBo.ibZ8gOdnLwPSuukp3Z.qo0Xa\r\n', 'organizer', '2026-04-30 16:00:39', NULL, NULL, NULL),
(14, 'Organizer 3', 'org3@campus.com', '$2y$10$1FCoOkGgvA5bHj4h/ay4sO8i77TuEPAflR6tPBcniZ1dVz40m4/eC\r\n', 'organizer', '2026-04-30 16:00:39', NULL, NULL, NULL),
(15, 'Organizer 4', 'org4@campus.com', '$2y$10$wZDnrxfeHg.EKSSE.d.YNuUjLX8ZgXIQJ5K6hrAjCXT8EBPTOqXrS', 'organizer', '2026-04-30 16:00:39', NULL, NULL, NULL),
(16, 'Amal', 'amal90@gmail.com', '$2y$10$paUuKxNWHLoHRxaea83mg.23baJMC9eM/TE7OqCbsWGROqxJ9uHqi', 'student', '2026-04-30 17:57:16', 'civil', NULL, NULL),
(17, 'Arun', 'arun@kkt.com', '$2y$10$lBBFxEX9remiO6RMvhOgsOMUuFY9hO6PfmkEQVanfsHMbuagzmlDq', 'student', '2026-05-01 10:16:34', 'Mech', 'uploads/profiles/user_17_1778318264.jpg', NULL),
(18, 'Karthik tom', 'kart@gmail.com', '$2y$10$epsHINWSjjDqRKbojcfRu.4PjJsQnEpKtcWFYDjIViCEsX2f2tITO', 'student', '2026-05-09 06:43:36', 'CS', NULL, NULL);

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `one_admin_insert` BEFORE INSERT ON `users` FOR EACH ROW BEGIN
    IF NEW.role = 'admin' THEN
        IF (SELECT COUNT(*) FROM users WHERE role = 'admin') > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Only one admin allowed';
        END IF;
    END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bulletins`
--
ALTER TABLE `bulletins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_bulletins_category` (`category`),
  ADD KEY `idx_bulletins_created_at` (`created_at`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_certificate` (`user_id`,`event_id`),
  ADD UNIQUE KEY `certificate_code` (`certificate_code`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_is_read` (`is_read`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organizer_id` (`organizer_id`);

--
-- Indexes for table `event_images`
--
ALTER TABLE `event_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_notifications_is_read` (`is_read`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_resets_email` (`email`),
  ADD KEY `idx_password_resets_token` (`token`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `idx_registrations_date` (`registered_at`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `idx_reviews_rating` (`rating`);

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
-- AUTO_INCREMENT for table `bulletins`
--
ALTER TABLE `bulletins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `event_images`
--
ALTER TABLE `event_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_images`
--
ALTER TABLE `event_images`
  ADD CONSTRAINT `event_images_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
