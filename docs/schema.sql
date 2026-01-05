-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 04, 2026 at 10:03 PM
-- Server version: 8.0.44-35
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `izvyufte_RockbandSchedule`
--
CREATE DATABASE IF NOT EXISTS `izvyufte_RockbandSchedule` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `izvyufte_RockbandSchedule`;

-- --------------------------------------------------------

--
-- Table structure for table `entries`
--

CREATE TABLE `entries` (
  `entry_id` bigint UNSIGNED NOT NULL,
  `event_id` binary(16) NOT NULL,
  `song_id` bigint UNSIGNED DEFAULT NULL,
  `position` tinyint UNSIGNED NOT NULL,
  `performer_name` varchar(150) NOT NULL DEFAULT '',
  `finished` tinyint(1) NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` binary(16) NOT NULL DEFAULT (uuid_to_bin(uuid())),
  `name` varchar(150) NOT NULL,
  `theme_id` bigint UNSIGNED DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `num_entries` tinyint UNSIGNED NOT NULL,
  `qr_image` blob,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `songs`
--

CREATE TABLE `songs` (
  `song_id` bigint UNSIGNED NOT NULL,
  `artist` varchar(150) NOT NULL,
  `album` varchar(150) NOT NULL,
  `title` varchar(150) NOT NULL,
  `year` int NOT NULL,
  `duration` int NOT NULL DEFAULT '0',
  `deezer_id` bigint UNSIGNED DEFAULT NULL,
  `album_art` blob,
  `selection_count` int UNSIGNED NOT NULL DEFAULT '0',
  `last_selected` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `themes`
--

CREATE TABLE `themes` (
  `theme_id` bigint UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `primary_color` varchar(7) NOT NULL,
  `bg_gradient_start` varchar(7) NOT NULL,
  `bg_gradient_end` varchar(7) NOT NULL,
  `text_color` varchar(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `themes`
--

INSERT INTO `themes` (`theme_id`, `name`, `primary_color`, `bg_gradient_start`, `bg_gradient_end`, `text_color`) VALUES
(1, 'Purple Night (dark)', '#6f42c1', '#1a1a2e', '#16213e', '#ffffff'),
(2, 'Ocean Blue (dark)', '#0d6efd', '#0a1628', '#1a2744', '#ffffff'),
(3, 'Emerald (dark)', '#198754', '#0d1f17', '#162d22', '#ffffff'),
(4, 'Sunset Orange (dark)', '#fd7e14', '#1f1510', '#2d1f15', '#ffffff'),
(5, 'Cherry Red (dark)', '#dc3545', '#1f0d0f', '#2d1518', '#ffffff'),
(6, 'Midnight Gold (dark)', '#ffc107', '#1a1810', '#2d2815', '#ffffff'),
(7, 'Electric Pink (dark)', '#d63384', '#1f0d18', '#2d1522', '#ffffff'),
(8, 'Teal Wave (dark)', '#20c997', '#0d1f1c', '#152d28', '#ffffff'),
(9, 'Cherry Blossom (light)', '#c41e3a', '#fff5f5', '#ffe4e6', '#1f2937'),
(10, 'Electric Pink (light)', '#c026a3', '#fdf4ff', '#fae8ff', '#1f2937'),
(11, 'Emerald (light)', '#047857', '#ecfdf5', '#d1fae5', '#1f2937'),
(12, 'Midnight Gold (light)', '#b45309', '#fffbeb', '#fef3c7', '#1f2937'),
(13, 'Ocean Blue (light)', '#0369a1', '#f0f9ff', '#e0f2fe', '#1f2937'),
(14, 'Purple Night (light)', '#7c3aed', '#faf5ff', '#ede9fe', '#1f2937'),
(15, 'Sunset Orange (light)', '#c2410c', '#fff7ed', '#ffedd5', '#1f2937'),
(16, 'Teal Wave (light)', '#0f766e', '#f0fdfa', '#ccfbf1', '#1f2937');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `entries`
--
ALTER TABLE `entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD UNIQUE KEY `enty_id` (`entry_id`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `fk_songs` (`song_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `fk_events_theme` (`theme_id`);

--
-- Indexes for table `songs`
--
ALTER TABLE `songs`
  ADD PRIMARY KEY (`song_id`),
  ADD UNIQUE KEY `song_id` (`song_id`),
  ADD KEY `idxArtist` (`artist`),
  ADD KEY `idxAlbum` (`album`),
  ADD KEY `idxTitle` (`title`);

--
-- Indexes for table `themes`
--
ALTER TABLE `themes`
  ADD PRIMARY KEY (`theme_id`),
  ADD UNIQUE KEY `theme_id` (`theme_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `entries`
--
ALTER TABLE `entries`
  MODIFY `entry_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `songs`
--
ALTER TABLE `songs`
  MODIFY `song_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `themes`
--
ALTER TABLE `themes`
  MODIFY `theme_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `entries`
--
ALTER TABLE `entries`
  ADD CONSTRAINT `fk_events` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_songs` FOREIGN KEY (`song_id`) REFERENCES `songs` (`song_id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_theme` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`theme_id`) ON DELETE RESTRICT;
COMMIT;
