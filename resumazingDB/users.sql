-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2026 at 09:21 PM
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
-- Database: `resumazing`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `auth_type` enum('manual','google') DEFAULT 'manual',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0,
  `remember_token` varchar(64) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `google_id`, `auth_type`, `created_at`, `is_admin`, `remember_token`, `token_expires`) VALUES
(19, 'Joebert Regencia', 'regenciajoebert21@gmail.com', '$2y$10$NiYrvepMEIqLsAHRLDwdJeymbYpv9gWA.Z9C.xIxtE1wFtHsYcCAK', '117702351088402242960', 'google', '2026-05-17 20:04:20', 1, NULL, NULL),
(20, 'test test', 'test@gmail.com', '$2y$10$P6MoL1s5ZUpaeAJUOSNLpeA7wa3.UaYcSFRh2eQqgsf3a034oHM1u', NULL, 'manual', '2026-05-17 21:29:53', 0, 'ba4a0db4fa623715c0fa8bbefdc8b136d47b84e1bacd4a5adff97cd46eb736ac', '2026-05-18 19:11:17'),
(21, 'Kdkaksn Dhsh', 'resumazingcv@gmail.com', '$2y$10$Ug8jbbvvmIz/A47H9N9GcOfxYPjstWJYTkqUgZ3OQqe8LzFqCT0a6', NULL, 'manual', '2026-05-17 21:46:08', 0, NULL, NULL),
(22, 'Joebert Regencia', 'regenciajoebert211@gmail.com', '$2y$10$0jHADsJlOAhZp6BQ0tct3uSo6cIJHZBNcveOw4Klx2YWR3LGg.N6K', NULL, 'manual', '2026-05-18 15:57:30', 0, NULL, NULL);

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
