-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 26, 2026 at 08:07 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sekolah_grading`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(10) UNSIGNED NOT NULL,
  `label` varchar(9) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `label`, `is_active`, `created_at`) VALUES
(20, '2026/2027', 1, '2026-06-21 22:49:15');

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `semester` enum('ganjil','genap') NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `jenis` varchar(80) DEFAULT NULL,
  `judul` varchar(160) NOT NULL,
  `tingkat` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `rombel_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('H','I','S','A') NOT NULL,
  `catatan` varchar(160) DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `rombel_id`, `student_id`, `tanggal`, `status`, `catatan`, `recorded_by`, `created_at`) VALUES
(88, 17, 170, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(89, 17, 131, '2026-06-24', 'A', NULL, 1, '2026-06-23 23:09:31'),
(90, 17, 145, '2026-06-24', 'S', NULL, 1, '2026-06-23 23:09:31'),
(91, 17, 128, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(92, 17, 132, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(93, 17, 148, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(94, 17, 136, '2026-06-24', 'A', NULL, 1, '2026-06-23 23:09:31'),
(95, 17, 163, '2026-06-24', 'I', NULL, 1, '2026-06-23 23:09:31'),
(96, 17, 146, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(97, 17, 156, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(98, 17, 119, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(99, 17, 125, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(100, 17, 164, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(101, 17, 121, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(102, 17, 129, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(103, 17, 172, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(104, 17, 158, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(105, 17, 147, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(106, 17, 140, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(107, 17, 166, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(108, 17, 122, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(109, 17, 159, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(110, 17, 171, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(111, 17, 174, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(112, 17, 120, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(113, 17, 142, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(114, 17, 138, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(115, 17, 160, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(116, 17, 134, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(117, 17, 151, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(118, 17, 153, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(119, 17, 133, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(120, 17, 150, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(121, 17, 126, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(122, 17, 137, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(123, 17, 161, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(124, 17, 162, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(125, 17, 168, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(126, 17, 167, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(127, 17, 124, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(128, 17, 139, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(129, 17, 152, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(130, 17, 127, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(131, 17, 123, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(132, 17, 144, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(133, 17, 169, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(134, 17, 135, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(135, 17, 149, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(136, 17, 130, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(137, 17, 173, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(138, 17, 143, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(139, 17, 154, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(140, 17, 155, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(141, 17, 141, '2026-06-24', 'H', NULL, 1, '2026-06-23 23:09:31'),
(142, 17, 170, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(143, 17, 131, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(144, 17, 145, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(145, 17, 128, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(146, 17, 132, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(147, 17, 148, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(148, 17, 136, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(149, 17, 163, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(150, 17, 146, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(151, 17, 156, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(152, 17, 119, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(153, 17, 125, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(154, 17, 164, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(155, 17, 121, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(156, 17, 129, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(157, 17, 172, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(158, 17, 158, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(159, 17, 147, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(160, 17, 140, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(161, 17, 166, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(162, 17, 122, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(163, 17, 159, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(164, 17, 171, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(165, 17, 174, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(166, 17, 120, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(167, 17, 142, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(168, 17, 138, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(169, 17, 160, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(170, 17, 134, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(171, 17, 151, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(172, 17, 153, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(173, 17, 133, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(174, 17, 150, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(175, 17, 126, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(176, 17, 137, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(177, 17, 161, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(178, 17, 162, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(179, 17, 168, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(180, 17, 167, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(181, 17, 124, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(182, 17, 139, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(183, 17, 152, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(184, 17, 127, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(185, 17, 123, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(186, 17, 144, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(187, 17, 169, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(188, 17, 135, '2026-06-23', 'I', NULL, 1, '2026-06-23 23:09:46'),
(189, 17, 149, '2026-06-23', 'S', NULL, 1, '2026-06-23 23:09:46'),
(190, 17, 130, '2026-06-23', 'A', NULL, 1, '2026-06-23 23:09:46'),
(191, 17, 173, '2026-06-23', 'I', NULL, 1, '2026-06-23 23:09:46'),
(192, 17, 143, '2026-06-23', 'S', NULL, 1, '2026-06-23 23:09:46'),
(193, 17, 154, '2026-06-23', 'A', NULL, 1, '2026-06-23 23:09:46'),
(194, 17, 155, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(195, 17, 141, '2026-06-23', 'H', NULL, 1, '2026-06-23 23:09:46'),
(196, 17, 170, '2026-06-25', 'H', NULL, 45, '2026-06-25 04:42:50'),
(197, 17, 131, '2026-06-25', 'I', NULL, 45, '2026-06-25 04:42:50'),
(198, 17, 145, '2026-06-25', 'S', NULL, 45, '2026-06-25 04:42:50'),
(199, 17, 128, '2026-06-25', 'H', NULL, 45, '2026-06-25 04:42:50'),
(200, 17, 132, '2026-06-25', 'H', NULL, 45, '2026-06-25 04:42:50'),
(201, 17, 148, '2026-06-25', 'H', NULL, 45, '2026-06-25 04:42:50'),
(202, 17, 170, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(203, 17, 131, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(204, 17, 145, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(205, 17, 128, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(206, 17, 132, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(207, 17, 148, '2026-06-26', 'I', NULL, 1, '2026-06-26 03:44:57'),
(208, 17, 136, '2026-06-26', 'I', NULL, 1, '2026-06-26 03:44:57'),
(209, 17, 163, '2026-06-26', 'A', NULL, 1, '2026-06-26 03:44:57'),
(210, 17, 146, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(211, 17, 156, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(212, 17, 119, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(213, 17, 125, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(214, 17, 164, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(215, 17, 121, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(216, 17, 129, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(217, 17, 172, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(218, 17, 158, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(219, 17, 147, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(220, 17, 140, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(221, 17, 166, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(222, 17, 122, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(223, 17, 159, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(224, 17, 171, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(225, 17, 174, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(226, 17, 120, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(227, 17, 142, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(228, 17, 138, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(229, 17, 160, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(230, 17, 134, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(231, 17, 151, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(232, 17, 153, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(233, 17, 133, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(234, 17, 150, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(235, 17, 126, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(236, 17, 137, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(237, 17, 161, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(238, 17, 162, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(239, 17, 168, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(240, 17, 167, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(241, 17, 124, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(242, 17, 139, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(243, 17, 152, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(244, 17, 127, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(245, 17, 123, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(246, 17, 144, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(247, 17, 169, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(248, 17, 135, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(249, 17, 149, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(250, 17, 130, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(251, 17, 173, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(252, 17, 143, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(253, 17, 154, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(254, 17, 155, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57'),
(255, 17, 141, '2026-06-26', 'H', NULL, 1, '2026-06-26 03:44:57');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `user_label` varchar(120) DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `target` varchar(160) DEFAULT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `user_label`, `action`, `target`, `meta_json`, `ip`, `created_at`) VALUES
(479, 1, 'Administrator', 'login', 'user:1', NULL, '103.189.62.130', '2026-06-22 00:09:42'),
(480, 1, 'Administrator', 'save', 'subject_category:36', NULL, '2400:9800:8c4:e817:cc92:9651:be1a:b8d5', '2026-06-22 00:57:52'),
(481, 1, 'Administrator', 'save', 'subject_category:37', NULL, '2400:9800:8c4:e817:cc92:9651:be1a:b8d5', '2026-06-22 00:58:03'),
(482, 1, 'Administrator', 'save', 'subject_category:38', NULL, '2400:9800:8c4:e817:cc92:9651:be1a:b8d5', '2026-06-22 00:58:18'),
(483, 1, 'Administrator', 'save', 'subject_category:39', NULL, '2400:9800:8c4:e817:cc92:9651:be1a:b8d5', '2026-06-22 00:58:35'),
(484, 1, 'Administrator', 'save', 'subject_category:40', NULL, '2400:9800:8c4:e817:cc92:9651:be1a:b8d5', '2026-06-22 00:58:43'),
(485, 1, 'Administrator', 'save', 'subject_category:38', NULL, '140.213.127.230', '2026-06-22 00:59:16'),
(486, 1, 'Administrator', 'logout', 'user:1', NULL, '103.189.62.130', '2026-06-22 01:54:46'),
(487, 1, 'Administrator', 'save', 'user:95', NULL, '103.189.62.130', '2026-06-22 02:00:55'),
(488, 1, 'Administrator', 'login', 'user:1', NULL, '103.189.62.130', '2026-06-22 02:01:21'),
(489, 95, 'Admin', 'login', 'user:95', NULL, '103.189.62.130', '2026-06-22 02:05:42'),
(490, 95, 'Admin', 'save', 'subject:183', NULL, '103.189.62.130', '2026-06-22 02:08:36'),
(491, 1, 'Administrator', 'logout', 'user:1', NULL, '103.189.62.130', '2026-06-22 02:10:07'),
(492, 1, 'Administrator', 'logout', 'user:1', NULL, '103.189.62.130', '2026-06-22 02:11:38'),
(493, 1, 'Administrator', 'login', 'user:1', NULL, '103.189.62.130', '2026-06-22 02:11:45'),
(494, 95, 'Admin', 'login', 'user:95', NULL, '103.189.62.130', '2026-06-22 02:12:08'),
(495, 95, 'Admin', 'login', 'user:95', NULL, '103.189.62.130', '2026-06-22 02:13:04'),
(496, 1, 'Administrator', 'assign_teacher', 'rombel:17/subject:66', '{\"t\":34,\"sem\":null}', '103.189.62.130', '2026-06-22 02:19:21'),
(497, 95, 'Admin', 'save', 'elective:3', NULL, '103.189.62.130', '2026-06-22 02:22:22'),
(498, 1, 'Administrator', 'assign_teacher', 'rombel:17/subject:63', '{\"t\":32,\"sem\":null}', '103.189.62.130', '2026-06-22 02:25:21'),
(499, 1, 'Administrator', 'logout', 'user:1', NULL, '103.189.62.130', '2026-06-22 02:26:47'),
(500, 1, 'Administrator', 'login', 'user:1', NULL, '103.189.62.130', '2026-06-22 02:27:08'),
(501, 1, 'Administrator', 'logout', 'user:1', NULL, '103.189.62.130', '2026-06-22 02:27:52'),
(502, 95, 'Admin', 'remove_member', 'rombel:18', '{\"s\":207}', '103.189.62.130', '2026-06-22 02:37:44'),
(503, 95, 'Admin', 'remove_member', 'rombel:18', '{\"s\":223}', '103.189.62.130', '2026-06-22 02:37:56'),
(504, 95, 'Admin', 'add_members', 'rombel:18', '{\"n\":2}', '103.189.62.130', '2026-06-22 02:38:04'),
(505, 20, 'Wismar Sinaga', 'login', 'user:20', NULL, '103.189.62.130', '2026-06-22 02:48:00'),
(506, 95, 'Admin', 'logout', 'user:95', NULL, '103.189.62.130', '2026-06-22 02:48:04'),
(507, 95, 'Admin', 'login', 'user:95', NULL, '103.189.62.130', '2026-06-22 02:49:38'),
(508, 1, 'Administrator', 'login', 'user:1', NULL, '103.189.62.130', '2026-06-22 02:50:19'),
(509, 95, 'Admin', 'login', 'user:95', NULL, '103.189.62.130', '2026-06-22 02:51:18'),
(510, 95, 'Admin', 'save', 'elective:4', NULL, '103.189.62.130', '2026-06-22 02:55:24'),
(511, 95, 'Admin', 'remove_member', 'rombel:17', '{\"s\":170}', '103.189.62.130', '2026-06-22 03:02:07'),
(512, 95, 'Admin', 'remove_member', 'rombel:17', '{\"s\":165}', '103.189.62.130', '2026-06-22 03:02:10'),
(513, 95, 'Admin', 'add_members', 'rombel:17', '{\"n\":2}', '103.189.62.130', '2026-06-22 03:02:21'),
(514, 95, 'Admin', 'remove_member', 'rombel:17', '{\"s\":170}', '103.189.62.130', '2026-06-22 03:02:36'),
(515, 95, 'Admin', 'remove_member', 'rombel:17', '{\"s\":165}', '103.189.62.130', '2026-06-22 03:02:39'),
(516, 95, 'Admin', 'remove_member', 'rombel:17', '{\"s\":131}', '103.189.62.130', '2026-06-22 03:02:42'),
(517, 95, 'Admin', 'add_members', 'rombel:17', '{\"n\":3}', '103.189.62.130', '2026-06-22 03:03:05'),
(518, 95, 'Admin', 'remove_member', 'rombel:17', '{\"s\":170}', '103.189.62.130', '2026-06-22 03:03:15'),
(519, 95, 'Admin', 'remove_member', 'rombel:17', '{\"s\":165}', '103.189.62.130', '2026-06-22 03:03:18'),
(520, 95, 'Admin', 'remove_member', 'rombel:17', '{\"s\":131}', '103.189.62.130', '2026-06-22 03:03:27'),
(521, 95, 'Admin', 'add_members', 'rombel:17', '{\"n\":1}', '103.189.62.130', '2026-06-22 03:03:31'),
(522, 95, 'Admin', 'add_members', 'rombel:17', '{\"n\":1}', '103.189.62.130', '2026-06-22 03:03:34'),
(523, 95, 'Admin', 'remove_member', 'rombel:17', '{\"s\":165}', '103.189.62.130', '2026-06-22 03:03:50'),
(524, 95, 'Admin', 'remove_member', 'rombel:17', '{\"s\":170}', '103.189.62.130', '2026-06-22 03:03:53'),
(525, 95, 'Admin', 'add_members', 'rombel:17', '{\"n\":2}', '103.189.62.130', '2026-06-22 03:04:02'),
(526, 95, 'Admin', 'assign_teacher', 'rombel:17/subject:54', '{\"t\":28,\"sem\":null}', '103.189.62.130', '2026-06-22 03:04:44'),
(527, 95, 'Admin', 'assign_teacher', 'rombel:18/subject:88', '{\"t\":44,\"sem\":null}', '103.189.62.130', '2026-06-22 03:05:15'),
(528, 95, 'Admin', 'logout', 'user:95', NULL, '103.189.62.130', '2026-06-22 03:10:41'),
(529, 1, 'Administrator', 'login', 'user:1', NULL, '103.189.62.130', '2026-06-22 03:13:10'),
(530, 1, 'Administrator', 'login', 'user:1', NULL, '182.253.129.73', '2026-06-22 03:21:56'),
(531, 20, 'Wismar Sinaga', 'logout', 'user:20', NULL, '103.189.62.130', '2026-06-22 04:49:27'),
(532, 1, 'Administrator', 'login', 'user:1', NULL, '103.189.62.130', '2026-06-22 04:49:32'),
(533, 1, 'Administrator', 'save', 'teacher:34', NULL, '103.189.62.130', '2026-06-22 04:50:02'),
(534, 1, 'Administrator', 'save', 'teacher:43', NULL, '103.189.62.130', '2026-06-22 04:50:36'),
(535, 1, 'Administrator', 'save', 'teacher:36', NULL, '103.189.62.130', '2026-06-22 04:50:41'),
(536, 1, 'Administrator', 'save', 'rombel:17', NULL, '103.189.62.130', '2026-06-22 04:51:38'),
(537, 1, 'Administrator', 'save', 'rombel:18', NULL, '103.189.62.130', '2026-06-22 04:51:43'),
(538, 1, 'Administrator', 'save', 'rombel:19', NULL, '103.189.62.130', '2026-06-22 04:51:48'),
(539, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 04:52:05'),
(540, 48, 'Dani Chandra', 'logout', 'user:48', NULL, '103.189.62.130', '2026-06-22 04:52:40'),
(541, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 04:53:22'),
(542, 48, 'Dani Chandra', 'logout', 'user:48', NULL, '103.189.62.130', '2026-06-22 04:53:26'),
(543, 34, 'Rani Larassati, S.Pd', 'login', 'user:34', NULL, '103.189.62.130', '2026-06-22 05:00:01'),
(544, 41, 'Devita Wulandari', 'login', 'user:41', NULL, '103.189.62.130', '2026-06-22 05:00:03'),
(545, 42, 'Firdaus Eka Ngenca Sinuraya', 'login', 'user:42', NULL, '103.189.62.130', '2026-06-22 05:00:18'),
(546, 31, 'Herlin Suryanti Riang Keladok, S.Pd', 'login', 'user:31', NULL, '103.189.62.130', '2026-06-22 05:00:21'),
(547, 49, 'I Gst Ngr Nyoman Gde Suadnyana, S.Pd', 'login', 'user:49', NULL, '103.189.62.130', '2026-06-22 05:00:27'),
(548, 33, 'Dwi Prastiwi, S.Pd', 'login', 'user:33', NULL, '103.189.62.130', '2026-06-22 05:00:30'),
(549, 39, 'Amadea Agnes Verina', 'login', 'user:39', NULL, '103.189.62.130', '2026-06-22 05:00:31'),
(550, 22, 'I Putu Gede Putra Adnyana', 'login', 'user:22', NULL, '103.189.62.130', '2026-06-22 05:00:39'),
(551, 38, 'M. Arrie Kunilasari Elyna, S.Si', 'login', 'user:38', NULL, '202.56.162.109', '2026-06-22 05:00:45'),
(552, 25, 'LUH JUNITA PRAWITA', 'login', 'user:25', NULL, '103.189.62.130', '2026-06-22 05:00:45'),
(553, 39, 'Amadea Agnes Verina', 'login', 'user:39', NULL, '103.189.62.130', '2026-06-22 05:00:48'),
(554, 23, 'Ni Komang Cahyani', 'login', 'user:23', NULL, '103.189.62.130', '2026-06-22 05:00:48'),
(555, 52, 'Hani Elinta Br Simatupang', 'login', 'user:52', NULL, '103.189.62.130', '2026-06-22 05:01:28'),
(556, 20, 'Wismar Sinaga', 'login', 'user:20', NULL, '103.189.62.130', '2026-06-22 05:01:50'),
(557, 51, 'Ekin Dio Gokyansen Tarigan', 'login', 'user:51', NULL, '103.189.62.130', '2026-06-22 05:02:11'),
(558, 50, 'Esan Teopilus Ginting, S.Pd', 'login', 'user:50', NULL, '103.189.62.130', '2026-06-22 05:02:56'),
(559, 46, 'I Putu Aga Darma Winanda, S.Kom, M.M', 'login', 'user:46', NULL, '103.189.62.130', '2026-06-22 05:03:02'),
(560, 53, 'Episman Gea', 'login', 'user:53', NULL, '103.189.62.130', '2026-06-22 05:03:03'),
(561, 32, 'Ryshel A.G.Pontoh', 'login', 'user:32', NULL, '103.189.62.130', '2026-06-22 05:03:17'),
(562, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:03:32'),
(563, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:03:36'),
(564, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:03:41'),
(565, 48, 'Dani Chandra', 'login', 'user:48', NULL, '2404:c0:3e1e:6e9c:70bb:1a9d:5daa:85d2', '2026-06-22 05:03:43'),
(566, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:04:04'),
(567, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:04:07'),
(568, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:04:38'),
(569, 24, 'Luh Ade Tirta Wahyuning, S.S', 'login', 'user:24', NULL, '103.189.62.130', '2026-06-22 05:04:39'),
(570, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:04:39'),
(571, 21, 'I MADE GELGEL ASMARA PUTRA, S.Pd', 'login', 'user:21', NULL, '114.10.157.32', '2026-06-22 05:04:41'),
(572, 40, 'Erwin Kurniawan, S.Pd', 'login', 'user:40', NULL, '103.189.62.130', '2026-06-22 05:04:43'),
(573, 27, 'Komang Ayu Rosmala Dewi', 'login', 'user:27', NULL, '103.189.62.130', '2026-06-22 05:04:45'),
(574, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:04:46'),
(575, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:04:51'),
(576, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:04:52'),
(577, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:04:52'),
(578, 36, 'Merlyn Julita Erya Octavianus, S.Pd', 'login', 'user:36', NULL, '103.189.62.130', '2026-06-22 05:05:03'),
(579, 30, 'Ni Wayan Nita Jayanti, S.PdH', 'login', 'user:30', NULL, '114.10.156.225', '2026-06-22 05:05:11'),
(580, 26, 'R. Amalia Nurfitri, S.Pd', 'login', 'user:26', NULL, '103.189.62.130', '2026-06-22 05:05:24'),
(581, 37, 'Christo Victory, S.S', 'login', 'user:37', NULL, '103.189.62.130', '2026-06-22 05:05:26'),
(582, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:05:42'),
(583, 28, 'Ida Ayu Made Sarira Cahya Pertiwi, S.Pd', 'login', 'user:28', NULL, '202.56.162.109', '2026-06-22 05:05:45'),
(584, 48, 'Dani Chandra', 'login', 'user:48', NULL, '2400:9800:8c1:3ecb:f7d0:2183:5f23:d519', '2026-06-22 05:05:49'),
(585, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:06:10'),
(586, 48, 'Dani Chandra', 'logout', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:06:30'),
(587, 48, 'Dani Chandra', 'login', 'user:48', NULL, '168.235.203.236', '2026-06-22 05:06:47'),
(588, 48, 'Dani Chandra', 'login', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:06:52'),
(589, 35, 'Ellysabeth Wiji Witaningsih, S.Th', 'login', 'user:35', NULL, '103.189.62.130', '2026-06-22 05:06:53'),
(590, 33, 'Dwi Prastiwi, S.Pd', 'user_ttd_upload', 'user:33', NULL, '103.189.62.130', '2026-06-22 05:07:50'),
(591, 36, 'Merlyn Julita Erya Octavianus, S.Pd', 'login', 'user:36', NULL, '103.189.62.130', '2026-06-22 05:20:17'),
(592, 43, 'Elsia Linawati, S.Tp', 'login', 'user:43', NULL, '202.56.162.109', '2026-06-22 05:30:12'),
(593, 40, 'Erwin Kurniawan, S.Pd', 'logout', 'user:40', NULL, '103.189.62.130', '2026-06-22 05:54:41'),
(594, 48, 'Dani Chandra', 'logout', 'user:48', NULL, '103.189.62.130', '2026-06-22 05:54:54'),
(595, 40, 'Erwin Kurniawan, S.Pd', 'login', 'user:40', NULL, '103.189.62.130', '2026-06-22 05:55:09'),
(596, 1, 'Administrator', 'logout', 'user:1', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 11:54:35'),
(597, 1, 'Administrator', 'login', 'user:1', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 11:54:48'),
(598, 1, 'Administrator', 'logout', 'user:1', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 11:55:06'),
(599, 39, 'Amadea Agnes Verina', 'login', 'user:39', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 11:55:09'),
(600, 95, 'Admin', 'login', 'user:95', NULL, '103.154.151.137', '2026-06-22 11:59:20'),
(601, 39, 'Amadea Agnes Verina', 'logout', 'user:39', NULL, '180.254.225.28', '2026-06-22 12:03:49'),
(602, 1, 'Administrator', 'login', 'user:1', NULL, '180.254.225.28', '2026-06-22 12:04:08'),
(603, 1, 'Administrator', 'save', 'user:43', NULL, '180.254.225.28', '2026-06-22 12:11:06'),
(604, 1, 'Administrator', 'logout', 'user:1', NULL, '180.254.225.28', '2026-06-22 12:11:10'),
(605, 43, 'Elsia Linawati, S.Tp', 'login', 'user:43', NULL, '180.254.225.28', '2026-06-22 12:11:14'),
(606, 43, 'Elsia Linawati, S.Tp', 'logout', 'user:43', NULL, '180.254.225.28', '2026-06-22 12:11:39'),
(607, 1, 'Administrator', 'login', 'user:1', NULL, '180.254.225.28', '2026-06-22 12:11:44'),
(608, 1, 'Administrator', 'login', 'user:1', NULL, '38.86.221.100', '2026-06-22 12:12:26'),
(609, 1, 'Administrator', 'save', 'user:43', NULL, '180.254.225.28', '2026-06-22 12:13:50'),
(610, 1, 'Administrator', 'logout', 'user:1', NULL, '180.254.225.28', '2026-06-22 12:14:00'),
(611, 43, 'Elsia Linawati, S.Tp', 'login', 'user:43', NULL, '180.254.225.28', '2026-06-22 12:14:03'),
(612, 43, 'Elsia Linawati, S.Tp', 'logout', 'user:43', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 12:22:46'),
(613, 1, 'Administrator', 'login', 'user:1', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 12:22:57'),
(614, 1, 'Administrator', 'logout', 'user:1', NULL, '180.254.225.28', '2026-06-22 12:25:03'),
(615, 39, 'Amadea Agnes Verina', 'login', 'user:39', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 12:25:09'),
(616, 39, 'Amadea Agnes Verina', 'logout', 'user:39', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 12:26:02'),
(617, 1, 'Administrator', 'login', 'user:1', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 12:26:07'),
(618, 1, 'Administrator', 'logout', 'user:1', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 12:27:03'),
(619, 48, 'Dani Chandra', 'login', 'user:48', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 12:27:08'),
(620, 48, 'Dani Chandra', 'save', 'elective_assign:e3:s170:ganjil', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 12:27:44'),
(621, 48, 'Dani Chandra', 'save', 'elective_assign:e3:s131:ganjil', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 12:30:45'),
(622, 48, 'Dani Chandra', 'login', 'user:48', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 13:29:32'),
(623, 48, 'Dani Chandra', 'logout', 'user:48', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 13:33:05'),
(624, 1, 'Administrator', 'login', 'user:1', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 13:33:09'),
(625, 1, 'Administrator', 'save', 'elective:3', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 13:33:29'),
(626, 1, 'Administrator', 'save', 'elective:4', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 13:33:34'),
(627, 48, 'Dani Chandra', 'logout', 'user:48', NULL, '180.254.225.28', '2026-06-22 14:04:42'),
(628, 1, 'Administrator', 'login', 'user:1', NULL, '180.254.225.28', '2026-06-22 14:04:47'),
(629, 1, 'Administrator', 'save', 'elective:5', NULL, '2001:448a:5070:8e7f:7005:6054:5a37:9311', '2026-06-22 14:07:04'),
(630, 1, 'Administrator', 'save', 'elective:5', NULL, '::1', '2026-06-22 14:56:52'),
(631, 1, 'Administrator', 'assign_teacher', 'elective_class:7', '{\"t\":36}', '::1', '2026-06-22 15:00:50'),
(632, 1, 'Administrator', 'assign_teacher', 'elective_class:15', '{\"t\":46}', '::1', '2026-06-22 15:01:14'),
(633, 1, 'Administrator', 'save', 'topic:21', NULL, '::1', '2026-06-22 15:11:15'),
(634, 1, 'Administrator', 'save', 'topic:22', NULL, '::1', '2026-06-22 15:11:32'),
(635, 1, 'Administrator', 'save', 'topic:23', NULL, '::1', '2026-06-22 15:12:50'),
(636, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-23 00:35:48'),
(637, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-23 00:37:58'),
(638, 1, 'Administrator', 'save', 'student:259', NULL, '::1', '2026-06-23 00:38:29'),
(639, 1, 'Administrator', 'save', 'teacher:32', NULL, '::1', '2026-06-23 00:54:07'),
(640, 1, 'Administrator', 'save', 'rombel:20', NULL, '::1', '2026-06-23 00:54:19'),
(641, 1, 'Administrator', 'add_members', 'rombel:20', '{\"n\":1}', '::1', '2026-06-23 00:54:37'),
(642, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-23 03:26:08'),
(643, 1, 'Administrator', 'save', 'topic:24', NULL, '::1', '2026-06-23 23:07:21'),
(644, 1, 'Administrator', 'save', 'topic:25', NULL, '::1', '2026-06-23 23:07:38'),
(645, 1, 'Administrator', 'assign_teacher', 'rombel:17/subject:66', '{\"t\":34,\"sem\":null}', '::1', '2026-06-23 23:08:03'),
(646, 1, 'Administrator', 'save', 'rombel:17', NULL, '::1', '2026-06-23 23:09:10'),
(647, 1, 'Administrator', 'save_attendance', 'rombel:17', '{\"date\":\"2026-06-24\",\"n\":54}', '::1', '2026-06-23 23:09:31'),
(648, 1, 'Administrator', 'save_attendance', 'rombel:17', '{\"date\":\"2026-06-23\",\"n\":54}', '::1', '2026-06-23 23:09:46'),
(649, 1, 'Administrator', 'save_grades_daily', 'rombel:17/subj:66/topic:24', '{\"date\":\"2026-06-24\",\"bucket\":\"tengah_ganjil\",\"n\":6}', '::1', '2026-06-23 23:10:46'),
(650, 1, 'Administrator', 'save', 'subject:66', NULL, '::1', '2026-06-23 23:12:12'),
(651, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-25 04:41:40'),
(652, 1, 'Administrator', 'save', 'user:45', NULL, '::1', '2026-06-25 04:42:20'),
(653, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-25 04:42:27'),
(654, 45, 'Wiwik Rahayu, S.Pd.', 'login', 'user:45', NULL, '::1', '2026-06-25 04:42:30'),
(655, 45, 'Wiwik Rahayu, S.Pd.', 'save_attendance', 'rombel:17', '{\"date\":\"2026-06-25\",\"n\":6}', '::1', '2026-06-25 04:42:50'),
(656, 45, 'Wiwik Rahayu, S.Pd.', 'logout', 'user:45', NULL, '::1', '2026-06-25 04:44:14'),
(657, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-25 04:44:20'),
(658, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-25 04:44:41'),
(659, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-25 04:48:00'),
(660, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-25 04:48:27'),
(661, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-25 04:49:15'),
(662, NULL, 'Alicia Anneke Agustin', 'parent_login', 'student:170', NULL, '0.0.0.0', '2026-06-25 04:54:33'),
(663, NULL, 'Alicia Anneke Agustin', 'parent_login', 'student:170', NULL, '::1', '2026-06-25 04:56:07'),
(664, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 04:56:07'),
(665, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-25 05:06:23'),
(666, 1, 'Administrator', 'save_grades_daily', 'rombel:17/subj:66/topic:24', '{\"date\":\"2026-06-25\",\"bucket\":\"tengah_ganjil\",\"n\":8}', '::1', '2026-06-25 05:15:21'),
(667, 1, 'Administrator', 'submit_final_grades', 'rombel:17/subj:66', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":8}', '::1', '2026-06-25 05:15:51'),
(668, 1, 'Administrator', 'review_approve_final_grades', NULL, '{\"n\":8,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-25 05:16:04'),
(669, 1, 'Administrator', 'review_publish_final_grades', NULL, '{\"n\":8,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-25 05:16:11'),
(670, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-25 05:16:15'),
(671, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-25 05:16:37'),
(672, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-25 05:17:24'),
(673, NULL, 'Alicia Anneke Agustin', 'parent_login', 'student:170', NULL, '::1', '2026-06-25 05:17:41'),
(674, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:17:42'),
(675, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:20:54'),
(676, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:20:55'),
(677, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:20:55'),
(678, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:20:56'),
(679, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:20:57'),
(680, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:20:57'),
(681, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:20:58'),
(682, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:20:58'),
(683, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:20:58'),
(684, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:20:58'),
(685, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:20:58'),
(686, NULL, 'Alicia Anneke Agustin', 'parent_view_grades', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\"}', '::1', '2026-06-25 05:20:59'),
(687, NULL, 'Alicia Anneke Agustin', 'parent_view_rapor', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"ok\":1}', '::1', '2026-06-25 05:21:00'),
(688, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:21:03'),
(689, NULL, 'Test', 'parent_view_grades', 'student:259', '{\"sem\":\"ganjil\",\"pk\":\"PTS\"}', '0.0.0.0', '2026-06-25 05:24:06'),
(690, NULL, 'Test', 'parent_view_attendance', 'student:259', '{\"sem\":\"ganjil\"}', '0.0.0.0', '2026-06-25 05:26:36'),
(691, NULL, 'Test', 'parent_view_home', 'student:259', NULL, '0.0.0.0', '2026-06-25 05:26:36'),
(692, NULL, 'Test', 'parent_view_grades', 'student:259', '{\"sem\":\"ganjil\",\"pk\":\"PTS\"}', '0.0.0.0', '2026-06-25 05:26:48'),
(693, NULL, 'Test', 'parent_view_grades', 'student:259', '{\"sem\":\"ganjil\",\"pk\":\"PTS\"}', '0.0.0.0', '2026-06-25 05:27:10'),
(694, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:27:28'),
(695, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:27:29'),
(696, NULL, 'Alicia Anneke Agustin', 'parent_view_rapor', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"ok\":1}', '::1', '2026-06-25 05:27:29'),
(697, NULL, 'Alicia Anneke Agustin', 'parent_view_grades', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\"}', '::1', '2026-06-25 05:27:30'),
(698, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:27:31'),
(699, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:27:36'),
(700, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:27:36'),
(701, NULL, 'Alicia Anneke Agustin', 'parent_view_grades', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\"}', '::1', '2026-06-25 05:27:37'),
(702, NULL, 'Alicia Anneke Agustin', 'parent_view_rapor', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"ok\":1}', '::1', '2026-06-25 05:27:38'),
(703, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:27:39'),
(704, NULL, 'Alicia Anneke Agustin', 'parent_view_rapor', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"ok\":1}', '::1', '2026-06-25 05:27:40'),
(705, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:27:42'),
(706, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:27:43'),
(707, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:27:44'),
(708, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:27:44'),
(709, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:27:45'),
(710, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:27:46'),
(711, NULL, 'Alicia Anneke Agustin', 'parent_view_rapor', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"ok\":1}', '::1', '2026-06-25 05:27:48'),
(712, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:27:49'),
(713, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:27:50'),
(714, NULL, 'Test', 'parent_view_home', 'student:259', NULL, '0.0.0.0', '2026-06-25 05:30:03'),
(715, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:30:30'),
(716, NULL, 'Alicia Anneke Agustin', 'parent_view_grades', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\"}', '::1', '2026-06-25 05:30:32'),
(717, NULL, 'Alicia Anneke Agustin', 'parent_view_rapor', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"ok\":1}', '::1', '2026-06-25 05:30:35'),
(718, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:30:35'),
(719, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:30:36'),
(720, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:30:36'),
(721, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:30:36'),
(722, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:30:36'),
(723, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:30:38'),
(724, NULL, 'Alicia Anneke Agustin', 'parent_view_rapor', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"ok\":1}', '::1', '2026-06-25 05:30:39'),
(725, NULL, 'Alicia Anneke Agustin', 'parent_view_grades', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\"}', '::1', '2026-06-25 05:30:40'),
(726, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:30:40'),
(727, NULL, 'Ahmad Testing', 'parent_login', 'student:259', NULL, '127.0.0.1', '2026-06-25 05:31:54'),
(728, NULL, 'Ahmad Testing', 'parent_view_home', 'student:259', NULL, '127.0.0.1', '2026-06-25 05:32:03'),
(729, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:32:34'),
(730, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:32:35'),
(731, NULL, 'Alicia Anneke Agustin', 'parent_view_grades', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\"}', '::1', '2026-06-25 05:32:36'),
(732, NULL, 'Alicia Anneke Agustin', 'parent_view_rapor', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"ok\":1}', '::1', '2026-06-25 05:32:36'),
(733, NULL, 'Alicia Anneke Agustin', 'parent_view_grades', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\"}', '::1', '2026-06-25 05:32:40'),
(734, NULL, 'Alicia Anneke Agustin', 'parent_view_rapor', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"ok\":1}', '::1', '2026-06-25 05:32:41'),
(735, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:32:43'),
(736, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:32:44'),
(737, NULL, 'Ahmad Testing', 'parent_view_home', 'student:259', NULL, '127.0.0.1', '2026-06-25 05:40:13'),
(738, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:40:38'),
(739, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:40:39'),
(740, NULL, 'Alicia Anneke Agustin', 'parent_view_home', 'student:170', NULL, '::1', '2026-06-25 05:40:39'),
(741, NULL, 'Alicia Anneke Agustin', 'parent_view_grades', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\"}', '::1', '2026-06-25 05:40:40'),
(742, NULL, 'Alicia Anneke Agustin', 'parent_view_rapor', 'student:170', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"ok\":1}', '::1', '2026-06-25 05:40:41'),
(743, NULL, 'Alicia Anneke Agustin', 'parent_view_attendance', 'student:170', '{\"sem\":\"ganjil\"}', '::1', '2026-06-25 05:40:41'),
(744, 1, 'Administrator', 'save_character_eval', 'rombel:17', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":21}', '::1', '2026-06-25 05:44:50'),
(745, 1, 'Administrator', 'save_general_eval', 'rombel:17', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":54}', '::1', '2026-06-25 05:45:08'),
(746, 1, 'Administrator', 'submit_final_grades', 'rombel:17/subj:66', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":1}', '::1', '2026-06-25 05:46:26'),
(747, 1, 'Administrator', 'submit_final_grades', 'rombel:17/subj:66', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":1}', '::1', '2026-06-25 05:46:29'),
(748, 1, 'Administrator', 'save_grades_daily', 'rombel:17/subj:66/topic:24', '{\"date\":\"2026-06-25\",\"bucket\":\"tengah_ganjil\",\"n\":9}', '::1', '2026-06-25 05:46:48'),
(749, 1, 'Administrator', 'review_revise_final_grades', NULL, '{\"n\":1,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-25 05:47:10'),
(750, 1, 'Administrator', 'save_grades_daily', 'rombel:17/subj:66/topic:24', '{\"date\":\"2026-06-25\",\"bucket\":\"tengah_ganjil\",\"n\":9}', '::1', '2026-06-25 05:47:21'),
(751, 1, 'Administrator', 'save_grades_daily', 'rombel:17/subj:66/topic:24', '{\"date\":\"2026-06-24\",\"bucket\":\"tengah_ganjil\",\"n\":7}', '::1', '2026-06-25 05:47:32'),
(752, 1, 'Administrator', 'save_grades_daily', 'rombel:17/subj:66/topic:24', '{\"date\":\"2026-06-23\",\"bucket\":\"tengah_ganjil\",\"n\":1}', '::1', '2026-06-25 05:47:41'),
(753, 1, 'Administrator', 'save_character_eval', 'rombel:17', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":21}', '::1', '2026-06-25 05:48:23'),
(754, 1, 'Administrator', 'save_general_eval', 'rombel:17', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":54}', '::1', '2026-06-25 05:48:35'),
(755, 1, 'Administrator', 'submit_final_grades', 'rombel:17/subj:66', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":1}', '::1', '2026-06-26 01:49:08'),
(756, 1, 'Administrator', 'review_publish_final_grades', NULL, '{\"n\":0,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-26 01:49:20'),
(757, 1, 'Administrator', 'review_unpublish_final_grades', NULL, '{\"n\":1,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-26 01:49:30'),
(758, 1, 'Administrator', 'review_unpublish_final_grades', NULL, '{\"n\":1,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-26 01:49:39'),
(759, 1, 'Administrator', 'review_publish_final_grades', NULL, '{\"n\":1,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-26 01:49:47'),
(760, 1, 'Administrator', 'review_revise_final_grades', NULL, '{\"n\":1,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-26 01:49:55'),
(761, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-26 01:53:08'),
(762, 45, 'Wiwik Rahayu, S.Pd.', 'login', 'user:45', NULL, '::1', '2026-06-26 01:53:15'),
(763, 45, 'Wiwik Rahayu, S.Pd.', 'logout', 'user:45', NULL, '::1', '2026-06-26 01:53:22'),
(764, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-26 01:53:26'),
(765, 1, 'Administrator', 'batch_promote', 'students:2', '{\"new_jenjang\":\"SD\",\"new_tingkat\":2}', '::1', '2026-06-26 01:55:18'),
(766, 1, 'Administrator', 'batch_promote', 'students:1', '{\"new_jenjang\":\"SD\",\"new_tingkat\":1}', '::1', '2026-06-26 01:55:46'),
(767, 1, 'Administrator', 'save', 'student:46', NULL, '::1', '2026-06-26 01:56:33'),
(768, 1, 'Administrator', 'batch_promote', 'students:1', '{\"new_jenjang\":\"TK\",\"new_tingkat\":2}', '::1', '2026-06-26 01:56:47'),
(769, 1, 'Administrator', 'batch_promote', 'students:1', '{\"new_jenjang\":\"SD\",\"new_tingkat\":1}', '::1', '2026-06-26 01:56:59'),
(770, 1, 'Administrator', 'batch_promote', 'students:1', '{\"new_jenjang\":\"SMP\",\"new_tingkat\":7}', '::1', '2026-06-26 01:57:17'),
(771, 1, 'Administrator', 'batch_promote', 'students:1', '{\"new_jenjang\":\"SMA\",\"new_tingkat\":10}', '::1', '2026-06-26 01:57:51'),
(772, 1, 'Administrator', 'batch_promote', 'students:1', '{\"new_jenjang\":\"SD\",\"new_tingkat\":2}', '::1', '2026-06-26 02:09:43'),
(773, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-26 03:39:53'),
(774, 1, 'Administrator', 'save', 'elective:6', NULL, '::1', '2026-06-26 03:42:38'),
(775, 1, 'Administrator', 'assign_teacher', 'rombel:17/subject:192', '{\"t\":34,\"sem\":null}', '::1', '2026-06-26 03:43:49'),
(776, 1, 'Administrator', 'save', 'topic:26', NULL, '::1', '2026-06-26 03:44:30'),
(777, 1, 'Administrator', 'save', 'topic:27', NULL, '::1', '2026-06-26 03:44:41'),
(778, 1, 'Administrator', 'save_attendance', 'rombel:17', '{\"date\":\"2026-06-26\",\"n\":54}', '::1', '2026-06-26 03:44:57'),
(779, 1, 'Administrator', 'save_grades_daily', 'rombel:17/subj:192/topic:26', '{\"date\":\"2026-06-26\",\"bucket\":\"tengah_ganjil\",\"n\":5}', '::1', '2026-06-26 03:45:51'),
(780, 1, 'Administrator', 'save_grades_daily', 'rombel:17/subj:192/topic:26', '{\"date\":\"2026-06-25\",\"bucket\":\"tengah_ganjil\",\"n\":5}', '::1', '2026-06-26 03:46:11'),
(781, 1, 'Administrator', 'save', 'elective_assign:e6:s170:ganjil', NULL, '::1', '2026-06-26 03:47:32'),
(782, 1, 'Administrator', 'save', 'elective_assign:e6:s170:ganjil', NULL, '::1', '2026-06-26 03:47:36'),
(783, 1, 'Administrator', 'save', 'elective_assign:e6:s170:ganjil', NULL, '::1', '2026-06-26 03:47:44'),
(784, 1, 'Administrator', 'save', 'elective_assign:e3:ganjil', NULL, '::1', '2026-06-26 03:51:44'),
(785, 1, 'Administrator', 'save', 'elective_assign:e6:ganjil', NULL, '::1', '2026-06-26 03:53:46'),
(786, 1, 'Administrator', 'assign_teacher', 'rombel:17/subject:190', '{\"t\":43,\"sem\":null}', '::1', '2026-06-26 03:54:28'),
(787, 1, 'Administrator', 'assign_teacher', 'rombel:17/subject:191', '{\"t\":32,\"sem\":null}', '::1', '2026-06-26 03:54:43'),
(788, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-26 03:54:54'),
(789, 37, 'Christo Victory, S.S', 'login', 'user:37', NULL, '::1', '2026-06-26 03:54:58'),
(790, 37, 'Christo Victory, S.S', 'save', 'topic:28', NULL, '::1', '2026-06-26 03:55:45'),
(791, 37, 'Christo Victory, S.S', 'save_grades_daily', 'rombel:17/subj:191/topic:28', '{\"date\":\"2026-06-26\",\"bucket\":\"tengah_ganjil\",\"n\":5}', '::1', '2026-06-26 04:21:42'),
(792, 37, 'Christo Victory, S.S', 'logout', 'user:37', NULL, '::1', '2026-06-26 04:21:50'),
(793, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-26 04:21:55'),
(794, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-26 04:22:21'),
(795, 48, 'Dani Chandra', 'login', 'user:48', NULL, '::1', '2026-06-26 04:22:24'),
(796, 48, 'Dani Chandra', 'save', 'topic:29', NULL, '::1', '2026-06-26 04:22:53'),
(797, 48, 'Dani Chandra', 'logout', 'user:48', NULL, '::1', '2026-06-26 04:23:16'),
(798, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-26 04:23:21'),
(799, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-26 04:23:33'),
(800, 39, 'Amadea Agnes Verina', 'login', 'user:39', NULL, '::1', '2026-06-26 04:23:36'),
(801, 39, 'Amadea Agnes Verina', 'logout', 'user:39', NULL, '::1', '2026-06-26 04:24:17'),
(802, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-26 04:24:23'),
(803, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-26 04:53:59'),
(804, NULL, 'Arxazuan Hadinata', 'parent_login', 'student:131', NULL, '::1', '2026-06-26 04:54:08'),
(805, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:54:08'),
(806, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:54:23'),
(807, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:54:24'),
(808, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:54:25'),
(809, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:54:29'),
(810, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:54:34'),
(811, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:54:36'),
(812, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:54:36'),
(813, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:54:37'),
(814, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:54:38'),
(815, NULL, 'Arxazuan Hadinata', 'parent_view_attendance', 'student:131', '{\"sem\":\"ganjil\",\"year_id\":20}', '::1', '2026-06-26 04:54:39'),
(816, NULL, 'Arxazuan Hadinata', 'parent_view_rapor', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20,\"ok\":0}', '::1', '2026-06-26 04:54:39'),
(817, NULL, 'Arxazuan Hadinata', 'parent_view_grades', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20}', '::1', '2026-06-26 04:54:40'),
(818, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:54:40'),
(819, NULL, 'Arxazuan Hadinata', 'parent_change_pw', 'parent_auth:19', NULL, '::1', '2026-06-26 04:57:07'),
(820, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:57:07'),
(821, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:57:25'),
(822, NULL, 'Arxazuan Hadinata', 'parent_view_attendance', 'student:131', '{\"sem\":\"ganjil\",\"year_id\":20}', '::1', '2026-06-26 04:57:38'),
(823, NULL, 'Arxazuan Hadinata', 'parent_view_attendance', 'student:131', '{\"sem\":\"genap\",\"year_id\":20}', '::1', '2026-06-26 04:57:47'),
(824, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:57:53'),
(825, NULL, 'Arxazuan Hadinata', 'parent_view_grades', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20}', '::1', '2026-06-26 04:57:54'),
(826, NULL, 'Arxazuan Hadinata', 'parent_view_rapor', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20,\"ok\":0}', '::1', '2026-06-26 04:57:55'),
(827, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 04:58:24'),
(828, 1, 'Administrator', 'review_revise_final_grades', NULL, '{\"n\":1,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-26 05:02:42'),
(829, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:03:55'),
(830, NULL, 'Arxazuan Hadinata', 'parent_view_attendance', 'student:131', '{\"sem\":\"ganjil\",\"year_id\":20}', '::1', '2026-06-26 05:04:07'),
(831, NULL, 'Arxazuan Hadinata', 'parent_view_rapor', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20,\"ok\":0}', '::1', '2026-06-26 05:04:08'),
(832, NULL, 'Arxazuan Hadinata', 'parent_view_grades', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20}', '::1', '2026-06-26 05:04:09'),
(833, 1, 'Administrator', 'submit_final_grades', 'rombel:17/subj:66', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":1}', '::1', '2026-06-26 05:08:27'),
(834, 1, 'Administrator', 'review_publish_final_grades', NULL, '{\"n\":0,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-26 05:08:37'),
(835, 1, 'Administrator', 'save_grades_daily', 'rombel:17/subj:190/topic:29', '{\"date\":\"2026-06-26\",\"bucket\":\"tengah_ganjil\",\"n\":1}', '::1', '2026-06-26 05:10:13'),
(836, 1, 'Administrator', 'submit_final_grades', 'rombel:17/subj:190', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":1}', '::1', '2026-06-26 05:10:36'),
(837, 1, 'Administrator', 'review_approve_final_grades', NULL, '{\"n\":1,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-26 05:10:42'),
(838, 1, 'Administrator', 'review_approve_final_grades', NULL, '{\"n\":1,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-26 05:10:59'),
(839, 1, 'Administrator', 'review_publish_final_grades', NULL, '{\"n\":2,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-06-26 05:11:08'),
(840, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:13:10'),
(841, NULL, 'Arxazuan Hadinata', 'parent_view_attendance', 'student:131', '{\"sem\":\"ganjil\",\"year_id\":20}', '::1', '2026-06-26 05:13:19'),
(842, NULL, 'Arxazuan Hadinata', 'parent_view_rapor', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20,\"ok\":1}', '::1', '2026-06-26 05:13:33'),
(843, NULL, 'Arxazuan Hadinata', 'parent_rapor_pdf_export', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20}', '::1', '2026-06-26 05:13:39'),
(844, NULL, 'Arxazuan Hadinata', 'parent_view_rapor', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20,\"ok\":1}', '::1', '2026-06-26 05:15:18'),
(845, NULL, 'Arxazuan Hadinata', 'parent_rapor_pdf_export', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20}', '::1', '2026-06-26 05:15:27'),
(846, NULL, 'Arxazuan Hadinata', 'parent_rapor_pdf_export', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20}', '::1', '2026-06-26 05:19:53'),
(847, NULL, 'Arxazuan Hadinata', 'parent_view_grades', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20}', '::1', '2026-06-26 05:19:58'),
(848, NULL, 'Arxazuan Hadinata', 'parent_view_rapor', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20,\"ok\":1}', '::1', '2026-06-26 05:20:00'),
(849, NULL, 'Arxazuan Hadinata', 'parent_view_grades', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20}', '::1', '2026-06-26 05:20:01'),
(850, NULL, 'Arxazuan Hadinata', 'parent_view_attendance', 'student:131', '{\"sem\":\"ganjil\",\"year_id\":20}', '::1', '2026-06-26 05:20:08'),
(851, NULL, 'Arxazuan Hadinata', 'parent_view_attendance', 'student:131', '{\"sem\":\"ganjil\",\"year_id\":20}', '::1', '2026-06-26 05:20:10'),
(852, NULL, 'Arxazuan Hadinata', 'parent_view_attendance', 'student:131', '{\"sem\":\"ganjil\",\"year_id\":20}', '::1', '2026-06-26 05:20:11'),
(853, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:13'),
(854, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:15'),
(855, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:15'),
(856, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:16'),
(857, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:17'),
(858, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:17'),
(859, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:17'),
(860, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:18'),
(861, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:18'),
(862, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:18'),
(863, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:18'),
(864, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:18'),
(865, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:19'),
(866, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:19'),
(867, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:19'),
(868, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:19'),
(869, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:19'),
(870, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:20'),
(871, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:20'),
(872, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:20'),
(873, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:21'),
(874, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:21'),
(875, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:21'),
(876, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:23'),
(877, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:23'),
(878, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:23'),
(879, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:23'),
(880, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:25'),
(881, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:25'),
(882, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:26'),
(883, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:26'),
(884, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:28'),
(885, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:20:28'),
(886, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:21:56'),
(887, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:22:00'),
(888, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:22:00'),
(889, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:22:00'),
(890, NULL, 'Arxazuan Hadinata', 'parent_view_attendance', 'student:131', '{\"sem\":\"ganjil\",\"year_id\":20}', '::1', '2026-06-26 05:22:02'),
(891, NULL, 'Arxazuan Hadinata', 'parent_view_attendance', 'student:131', '{\"sem\":\"genap\",\"year_id\":20}', '::1', '2026-06-26 05:22:04'),
(892, NULL, 'Arxazuan Hadinata', 'parent_view_attendance', 'student:131', '{\"sem\":\"ganjil\",\"year_id\":20}', '::1', '2026-06-26 05:22:05'),
(893, 1, 'Administrator', 'edit_dates', 'academic_year:20', NULL, '::1', '2026-06-26 05:24:53'),
(894, 1, 'Administrator', 'edit_dates', 'academic_year:20', NULL, '::1', '2026-06-26 05:25:04'),
(895, 1, 'Administrator', 'edit_dates', 'academic_year:20', NULL, '::1', '2026-06-26 05:26:41'),
(896, NULL, 'Arxazuan Hadinata', 'parent_view_attendance', 'student:131', '{\"sem\":\"ganjil\",\"year_id\":20}', '::1', '2026-06-26 05:26:47'),
(897, NULL, 'Arxazuan Hadinata', 'parent_view_rapor', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20,\"ok\":1}', '::1', '2026-06-26 05:26:59'),
(898, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:27:01'),
(899, NULL, 'Arxazuan Hadinata', 'parent_view_grades', 'student:131', '{\"sem\":\"ganjil\",\"pk\":\"PTS\",\"year_id\":20}', '::1', '2026-06-26 05:28:20'),
(900, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:28:27'),
(901, 1, 'Administrator', 'create', 'academic_year:2027/2028', '{\"copy_from\":null,\"deep_copy\":false}', '::1', '2026-06-26 05:33:10'),
(902, 1, 'Administrator', 'delete', 'academic_year:26', NULL, '::1', '2026-06-26 05:33:26'),
(903, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:49:28'),
(904, NULL, 'Arxazuan Hadinata', 'parent_view_home', 'student:131', NULL, '::1', '2026-06-26 05:49:29');

-- --------------------------------------------------------

--
-- Table structure for table `character_aspects`
--

CREATE TABLE `character_aspects` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `jenjang` enum('TK','SD','SMP','SMA') NOT NULL DEFAULT 'SD',
  `nama` varchar(120) NOT NULL,
  `kategori` enum('Spiritual and morality','Discipline','Manner','Obedience','Focus and Confidence','spiritual','sosial') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `character_aspects`
--

INSERT INTO `character_aspects` (`id`, `academic_year_id`, `jenjang`, `nama`, `kategori`) VALUES
(20, 20, 'SD', 'Student prays in the morning and before meal', 'Spiritual and morality'),
(21, 20, 'SD', 'Student comes to school punctually', 'Discipline'),
(22, 20, 'SD', 'Shows kindness to others by helping them when they have problems', 'Manner'),
(24, 20, 'SD', 'Student shows creativity in solving a matter.', 'Focus and Confidence'),
(25, 20, 'SD', 'Student cares in the religion teaching by showing effort in doing good deeds and avoiding bad deeds', 'Spiritual and morality'),
(26, 20, 'SD', 'Student is likely to speak the truth/fact of story', 'Spiritual and morality'),
(27, 20, 'SD', 'Student respects and takes care of other people and their/belongings', 'Spiritual and morality'),
(28, 20, 'SD', 'Student is always eager to learn and improve self', 'Spiritual and morality'),
(29, 20, 'SD', 'Student show responsibility and seriousness in learning and doing school work', 'Discipline'),
(30, 20, 'SD', 'Stands in line and wait for turn in doing activity', 'Discipline'),
(31, 20, 'SD', 'Student keeps self and school environment clean and tidy', 'Discipline'),
(32, 20, 'SD', 'Finish the meal and water', 'Discipline'),
(33, 20, 'SD', 'Student is likely to say thank you, sorry, excuse me and please', 'Manner'),
(34, 20, 'SD', 'Student bows wholeheartedly when saying sorry or thank you', 'Manner'),
(35, 20, 'SD', 'Frequently greets teachers and friends in the morning and going home time.', 'Manner'),
(36, 20, 'SD', 'Talking with friend and especially teacher/elders with a good and polite tone.', 'Manner'),
(37, 20, 'SD', 'Pushes in the chair after use', 'Manner'),
(38, 20, 'SD', 'Student returns the borrowed things after use', 'Manner'),
(39, 20, 'SD', 'Student obeys rules and regulations', 'Obedience'),
(40, 20, 'SD', 'Student is willing to take an advice from teacher and learn from it', 'Obedience'),
(41, 20, 'SD', 'Student is confident to share an opinion to others in a good way', 'Focus and Confidence');

-- --------------------------------------------------------

--
-- Table structure for table `character_evaluations`
--

CREATE TABLE `character_evaluations` (
  `id` int(10) UNSIGNED NOT NULL,
  `rombel_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `aspect_id` int(10) UNSIGNED NOT NULL,
  `semester` enum('ganjil','genap') NOT NULL,
  `period_kind` enum('PTS','PAS') NOT NULL,
  `scale` enum('NI','SI','WI','PR') NOT NULL,
  `remark` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `character_evaluations`
--

INSERT INTO `character_evaluations` (`id`, `rombel_id`, `student_id`, `aspect_id`, `semester`, `period_kind`, `scale`, `remark`, `created_at`) VALUES
(150, 17, 170, 25, 'ganjil', 'PTS', 'NI', NULL, '2026-06-25 05:44:50'),
(151, 17, 170, 28, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:44:50'),
(152, 17, 170, 26, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:44:50'),
(153, 17, 170, 20, 'ganjil', 'PTS', 'WI', NULL, '2026-06-25 05:44:50'),
(154, 17, 170, 27, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:44:50'),
(155, 17, 170, 32, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:44:50'),
(156, 17, 170, 30, 'ganjil', 'PTS', 'WI', NULL, '2026-06-25 05:44:50'),
(157, 17, 170, 21, 'ganjil', 'PTS', 'WI', NULL, '2026-06-25 05:44:50'),
(158, 17, 170, 31, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:44:50'),
(159, 17, 170, 29, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:44:50'),
(160, 17, 170, 35, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:44:50'),
(161, 17, 170, 37, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:44:50'),
(162, 17, 170, 22, 'ganjil', 'PTS', 'WI', NULL, '2026-06-25 05:44:50'),
(163, 17, 170, 34, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:44:50'),
(164, 17, 170, 33, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:44:50'),
(165, 17, 170, 38, 'ganjil', 'PTS', 'WI', NULL, '2026-06-25 05:44:50'),
(166, 17, 170, 36, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:44:50'),
(167, 17, 170, 40, 'ganjil', 'PTS', 'WI', NULL, '2026-06-25 05:44:50'),
(168, 17, 170, 39, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:44:50'),
(169, 17, 170, 41, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:44:50'),
(170, 17, 170, 24, 'ganjil', 'PTS', 'NI', NULL, '2026-06-25 05:44:50'),
(171, 17, 131, 25, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:48:23'),
(172, 17, 131, 28, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:48:23'),
(173, 17, 131, 26, 'ganjil', 'PTS', 'WI', NULL, '2026-06-25 05:48:23'),
(174, 17, 131, 20, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:48:23'),
(175, 17, 131, 27, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:48:23'),
(176, 17, 131, 32, 'ganjil', 'PTS', 'NI', NULL, '2026-06-25 05:48:23'),
(177, 17, 131, 30, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:48:23'),
(178, 17, 131, 21, 'ganjil', 'PTS', 'WI', NULL, '2026-06-25 05:48:23'),
(179, 17, 131, 31, 'ganjil', 'PTS', 'WI', NULL, '2026-06-25 05:48:23'),
(180, 17, 131, 29, 'ganjil', 'PTS', 'NI', NULL, '2026-06-25 05:48:23'),
(181, 17, 131, 35, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:48:23'),
(182, 17, 131, 37, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:48:23'),
(183, 17, 131, 22, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:48:23'),
(184, 17, 131, 34, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:48:23'),
(185, 17, 131, 33, 'ganjil', 'PTS', 'WI', NULL, '2026-06-25 05:48:23'),
(186, 17, 131, 38, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:48:23'),
(187, 17, 131, 36, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:48:23'),
(188, 17, 131, 40, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:48:23'),
(189, 17, 131, 39, 'ganjil', 'PTS', 'PR', NULL, '2026-06-25 05:48:23'),
(190, 17, 131, 41, 'ganjil', 'PTS', 'WI', NULL, '2026-06-25 05:48:23'),
(191, 17, 131, 24, 'ganjil', 'PTS', 'SI', NULL, '2026-06-25 05:48:23');

-- --------------------------------------------------------

--
-- Table structure for table `electives`
--

CREATE TABLE `electives` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED DEFAULT NULL,
  `jenjang` enum('TK','SD','SMP','SMA') NOT NULL,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(120) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `electives`
--

INSERT INTO `electives` (`id`, `academic_year_id`, `subject_id`, `jenjang`, `kode`, `nama`, `deskripsi`, `category_id`, `created_at`, `deleted_at`) VALUES
(3, 20, 184, 'SD', 'TKB', 'CGV', NULL, 38, '2026-06-22 02:22:22', NULL),
(4, 20, 185, 'SD', 'TLN_ART3-4', 'TALENT INTEREST ART 3-4', NULL, 36, '2026-06-22 02:55:24', NULL),
(5, 20, 189, 'SD', 'SHS', 'SBTAA', NULL, 38, '2026-06-22 14:07:04', NULL),
(6, 20, NULL, 'SD', 'TST', 'Sekolah Beta', NULL, 38, '2026-06-26 03:42:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `elective_assignments`
--

CREATE TABLE `elective_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `elective_id` int(10) UNSIGNED NOT NULL,
  `elective_class_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `semester` enum('ganjil','genap') NOT NULL,
  `assigned_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elective_assignments`
--

INSERT INTO `elective_assignments` (`id`, `elective_id`, `elective_class_id`, `student_id`, `semester`, `assigned_by`, `created_at`, `updated_at`) VALUES
(6, 3, 7, 170, 'ganjil', 1, '2026-06-22 12:27:44', '2026-06-26 03:51:44'),
(7, 3, 7, 131, 'ganjil', 1, '2026-06-22 12:30:45', '2026-06-26 03:51:44'),
(8, 6, 17, 170, 'ganjil', 1, '2026-06-26 03:47:32', '2026-06-26 03:47:44'),
(13, 3, 8, 145, 'ganjil', 1, '2026-06-26 03:51:44', '2026-06-26 03:51:44'),
(15, 6, 16, 131, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(16, 6, 17, 145, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(17, 6, 18, 128, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(18, 6, 16, 132, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(19, 6, 16, 148, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(20, 6, 18, 136, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(21, 6, 18, 163, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(22, 6, 17, 146, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(23, 6, 17, 156, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(24, 6, 16, 119, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(25, 6, 16, 125, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(26, 6, 17, 164, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(27, 6, 18, 121, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(28, 6, 16, 129, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(29, 6, 17, 172, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(30, 6, 17, 158, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(31, 6, 17, 147, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(32, 6, 16, 140, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(33, 6, 16, 166, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(34, 6, 17, 122, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(35, 6, 16, 159, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(36, 6, 17, 171, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(37, 6, 17, 174, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(38, 6, 17, 120, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(39, 6, 18, 142, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(40, 6, 17, 138, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(41, 6, 18, 160, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(42, 6, 17, 134, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(43, 6, 17, 151, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(44, 6, 17, 153, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(45, 6, 18, 133, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(46, 6, 17, 150, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(47, 6, 16, 126, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(48, 6, 16, 137, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(49, 6, 18, 161, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(50, 6, 18, 162, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(51, 6, 18, 168, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(52, 6, 18, 167, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(53, 6, 18, 124, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(54, 6, 18, 139, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(55, 6, 16, 152, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(56, 6, 17, 127, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(57, 6, 16, 123, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(58, 6, 18, 144, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(59, 6, 17, 169, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(60, 6, 18, 135, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(61, 6, 18, 149, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(62, 6, 18, 130, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(63, 6, 16, 173, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(64, 6, 17, 143, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(65, 6, 17, 154, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(66, 6, 18, 155, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46'),
(67, 6, 17, 141, 'ganjil', 1, '2026-06-26 03:53:46', '2026-06-26 03:53:46');

-- --------------------------------------------------------

--
-- Table structure for table `elective_classes`
--

CREATE TABLE `elective_classes` (
  `id` int(10) UNSIGNED NOT NULL,
  `elective_id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(120) NOT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `kapasitas` smallint(5) UNSIGNED NOT NULL DEFAULT 40,
  `subject_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elective_classes`
--

INSERT INTO `elective_classes` (`id`, `elective_id`, `nama`, `teacher_id`, `kapasitas`, `subject_id`, `created_at`, `deleted_at`) VALUES
(7, 3, 'Coding', 36, 10, NULL, '2026-06-22 02:22:22', NULL),
(8, 3, 'Grafis', NULL, 10, NULL, '2026-06-22 02:22:22', NULL),
(9, 3, 'Video', NULL, 10, NULL, '2026-06-22 02:22:22', NULL),
(10, 4, 'MELUKIS', NULL, 50, NULL, '2026-06-22 02:55:24', NULL),
(11, 4, 'MODERN DANCE', NULL, 50, NULL, '2026-06-22 02:55:24', NULL),
(12, 4, 'MUSIC', NULL, 50, NULL, '2026-06-22 02:55:24', NULL),
(13, 5, 'Hustler', NULL, 10, NULL, '2026-06-22 14:07:04', NULL),
(14, 5, 'Hacker', NULL, 10, NULL, '2026-06-22 14:07:04', NULL),
(15, 5, 'Hipster', 46, 10, NULL, '2026-06-22 14:07:04', NULL),
(16, 6, 'Hipster', NULL, 10, 190, '2026-06-26 03:42:37', NULL),
(17, 6, 'Hacker', NULL, 10, 191, '2026-06-26 03:42:37', NULL),
(18, 6, 'Hustler', NULL, 10, 192, '2026-06-26 03:42:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `elective_rombels`
--

CREATE TABLE `elective_rombels` (
  `elective_id` int(10) UNSIGNED NOT NULL,
  `rombel_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elective_rombels`
--

INSERT INTO `elective_rombels` (`elective_id`, `rombel_id`) VALUES
(3, 17),
(4, 17),
(5, 17),
(6, 17);

-- --------------------------------------------------------

--
-- Table structure for table `extracurriculars`
--

CREATE TABLE `extracurriculars` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(120) NOT NULL,
  `pembina` varchar(120) DEFAULT NULL,
  `jadwal` varchar(120) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `extracurricular_grades`
--

CREATE TABLE `extracurricular_grades` (
  `id` int(10) UNSIGNED NOT NULL,
  `extracurricular_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `semester` enum('ganjil','genap') NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `predikat` varchar(40) DEFAULT NULL,
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `final_grades`
--

CREATE TABLE `final_grades` (
  `id` int(10) UNSIGNED NOT NULL,
  `rombel_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `semester` enum('ganjil','genap') NOT NULL,
  `period_kind` enum('PTS','PAS') NOT NULL,
  `nilai_sikap` decimal(5,2) DEFAULT NULL,
  `nilai_pengetahuan` decimal(5,2) DEFAULT NULL,
  `nilai_keterampilan` decimal(5,2) DEFAULT NULL,
  `catatan_guru` text DEFAULT NULL,
  `status` enum('draft','submitted','revised','approved','published') NOT NULL DEFAULT 'draft',
  `submitted_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_grades`
--

INSERT INTO `final_grades` (`id`, `rombel_id`, `subject_id`, `student_id`, `semester`, `period_kind`, `nilai_sikap`, `nilai_pengetahuan`, `nilai_keterampilan`, `catatan_guru`, `status`, `submitted_by`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`, `image_path`) VALUES
(35, 17, 66, 170, 'ganjil', 'PTS', 79.00, 79.00, 79.00, NULL, 'approved', NULL, 1, '2026-06-26 09:49:30', '2026-06-25 05:15:40', '2026-06-26 01:49:30', NULL),
(36, 17, 66, 128, 'ganjil', 'PTS', 85.00, 85.00, 85.00, NULL, 'revised', NULL, 1, '2026-06-26 09:49:55', '2026-06-25 05:15:40', '2026-06-26 01:49:55', NULL),
(37, 17, 66, 132, 'ganjil', 'PTS', 84.50, 84.50, 84.50, NULL, 'published', NULL, 1, '2026-06-25 13:16:11', '2026-06-25 05:15:40', '2026-06-25 05:16:11', NULL),
(38, 17, 66, 148, 'ganjil', 'PTS', 75.00, 75.00, 75.00, NULL, 'published', NULL, 1, '2026-06-25 13:16:11', '2026-06-25 05:15:40', '2026-06-25 05:16:11', NULL),
(39, 17, 66, 136, 'ganjil', 'PTS', 81.00, 81.00, 81.00, NULL, 'published', NULL, 1, '2026-06-25 13:16:11', '2026-06-25 05:15:40', '2026-06-25 05:16:11', NULL),
(40, 17, 66, 163, 'ganjil', 'PTS', 85.00, 85.00, 85.00, NULL, 'published', NULL, 1, '2026-06-25 13:16:11', '2026-06-25 05:15:40', '2026-06-25 05:16:11', NULL),
(41, 17, 66, 146, 'ganjil', 'PTS', 73.50, 73.50, 73.50, NULL, 'published', NULL, 1, '2026-06-25 13:16:11', '2026-06-25 05:15:40', '2026-06-25 05:16:11', NULL),
(42, 17, 66, 156, 'ganjil', 'PTS', 74.00, 74.00, 74.00, NULL, 'published', NULL, 1, '2026-06-25 13:16:11', '2026-06-25 05:15:40', '2026-06-25 05:16:11', NULL),
(43, 17, 66, 131, 'ganjil', 'PTS', 92.33, 92.33, 89.67, 'Komentar adalah ulasan, tanggapan, atau kritik yang diberikan terhadap suatu topik, berita, atau kejadian yang bertujuan untuk menerangkan, menjelaskan, atau memberikan penilaian.', 'published', NULL, 1, '2026-06-26 13:11:08', '2026-06-25 05:46:26', '2026-06-26 05:11:08', NULL),
(44, 17, 191, 170, 'ganjil', 'PTS', 80.00, 80.00, 80.00, NULL, 'draft', NULL, NULL, NULL, '2026-06-26 05:01:28', '2026-06-26 05:01:28', NULL),
(45, 17, 191, 145, 'ganjil', 'PTS', 90.00, 90.00, 90.00, NULL, 'draft', NULL, NULL, NULL, '2026-06-26 05:01:28', '2026-06-26 05:01:28', NULL),
(46, 17, 191, 146, 'ganjil', 'PTS', 90.00, 90.00, 90.00, NULL, 'draft', NULL, NULL, NULL, '2026-06-26 05:01:28', '2026-06-26 05:01:28', NULL),
(47, 17, 191, 156, 'ganjil', 'PTS', 90.00, 90.00, 90.00, NULL, 'draft', NULL, NULL, NULL, '2026-06-26 05:01:28', '2026-06-26 05:01:28', NULL),
(48, 17, 191, 164, 'ganjil', 'PTS', 90.00, 90.00, 90.00, NULL, 'draft', NULL, NULL, NULL, '2026-06-26 05:01:28', '2026-06-26 05:01:28', NULL),
(49, 17, 192, 128, 'ganjil', 'PTS', 85.00, 85.00, 85.00, NULL, 'draft', NULL, NULL, NULL, '2026-06-26 05:01:37', '2026-06-26 05:01:37', NULL),
(50, 17, 192, 136, 'ganjil', 'PTS', 78.00, 87.00, 78.00, NULL, 'draft', NULL, NULL, NULL, '2026-06-26 05:01:37', '2026-06-26 05:01:37', NULL),
(51, 17, 190, 131, 'ganjil', 'PTS', 90.00, 90.00, 90.00, 'Komentar adalah ulasan, tanggapan, atau kritik yang diberikan terhadap suatu topik, berita, atau kejadian yang bertujuan untuk menerangkan, menjelaskan.', 'published', NULL, 1, '2026-06-26 13:11:08', '2026-06-26 05:10:25', '2026-06-26 05:11:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `general_evaluations`
--

CREATE TABLE `general_evaluations` (
  `id` int(10) UNSIGNED NOT NULL,
  `rombel_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `semester` enum('ganjil','genap') NOT NULL,
  `period_kind` enum('PTS','PAS') NOT NULL,
  `narasi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_evaluations`
--

INSERT INTO `general_evaluations` (`id`, `rombel_id`, `student_id`, `semester`, `period_kind`, `narasi`) VALUES
(8, 17, 170, 'ganjil', 'PTS', 'Kleki is a free, browser-based digital painting application designed for drawing and painting directly in a web browser. It emphasizes immediate access with no installation required, while still providing features such as layers, brushes, filters, and image editing tools. The project has been maintained since 2010 as an open-source, community-funded creative tool.'),
(9, 17, 131, 'ganjil', 'PTS', 'Kleki is a free, browser-based digital painting application designed for drawing and painting directly in a web browser. It emphasizes immediate access with no installation required, while still providing features such as layers, brushes, filters, and image editing tools. The project has been maintained since 2010 as an open-source, community-funded creative tool.'),
(10, 17, 145, 'ganjil', 'PTS', NULL),
(11, 17, 128, 'ganjil', 'PTS', NULL),
(12, 17, 132, 'ganjil', 'PTS', NULL),
(13, 17, 148, 'ganjil', 'PTS', NULL),
(14, 17, 136, 'ganjil', 'PTS', NULL),
(15, 17, 163, 'ganjil', 'PTS', NULL),
(16, 17, 146, 'ganjil', 'PTS', NULL),
(17, 17, 156, 'ganjil', 'PTS', NULL),
(18, 17, 119, 'ganjil', 'PTS', NULL),
(19, 17, 125, 'ganjil', 'PTS', NULL),
(20, 17, 164, 'ganjil', 'PTS', NULL),
(21, 17, 121, 'ganjil', 'PTS', NULL),
(22, 17, 129, 'ganjil', 'PTS', NULL),
(23, 17, 172, 'ganjil', 'PTS', NULL),
(24, 17, 158, 'ganjil', 'PTS', NULL),
(25, 17, 147, 'ganjil', 'PTS', NULL),
(26, 17, 140, 'ganjil', 'PTS', NULL),
(27, 17, 166, 'ganjil', 'PTS', NULL),
(28, 17, 122, 'ganjil', 'PTS', NULL),
(29, 17, 159, 'ganjil', 'PTS', NULL),
(30, 17, 171, 'ganjil', 'PTS', NULL),
(31, 17, 174, 'ganjil', 'PTS', NULL),
(32, 17, 120, 'ganjil', 'PTS', NULL),
(33, 17, 142, 'ganjil', 'PTS', NULL),
(34, 17, 138, 'ganjil', 'PTS', NULL),
(35, 17, 160, 'ganjil', 'PTS', NULL),
(36, 17, 134, 'ganjil', 'PTS', NULL),
(37, 17, 151, 'ganjil', 'PTS', NULL),
(38, 17, 153, 'ganjil', 'PTS', NULL),
(39, 17, 133, 'ganjil', 'PTS', NULL),
(40, 17, 150, 'ganjil', 'PTS', NULL),
(41, 17, 126, 'ganjil', 'PTS', NULL),
(42, 17, 137, 'ganjil', 'PTS', NULL),
(43, 17, 161, 'ganjil', 'PTS', NULL),
(44, 17, 162, 'ganjil', 'PTS', NULL),
(45, 17, 168, 'ganjil', 'PTS', NULL),
(46, 17, 167, 'ganjil', 'PTS', NULL),
(47, 17, 124, 'ganjil', 'PTS', NULL),
(48, 17, 139, 'ganjil', 'PTS', NULL),
(49, 17, 152, 'ganjil', 'PTS', NULL),
(50, 17, 127, 'ganjil', 'PTS', NULL),
(51, 17, 123, 'ganjil', 'PTS', NULL),
(52, 17, 144, 'ganjil', 'PTS', NULL),
(53, 17, 169, 'ganjil', 'PTS', NULL),
(54, 17, 135, 'ganjil', 'PTS', NULL),
(55, 17, 149, 'ganjil', 'PTS', NULL),
(56, 17, 130, 'ganjil', 'PTS', NULL),
(57, 17, 173, 'ganjil', 'PTS', NULL),
(58, 17, 143, 'ganjil', 'PTS', NULL),
(59, 17, 154, 'ganjil', 'PTS', NULL),
(60, 17, 155, 'ganjil', 'PTS', NULL),
(61, 17, 141, 'ganjil', 'PTS', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `grades_daily`
--

CREATE TABLE `grades_daily` (
  `id` int(10) UNSIGNED NOT NULL,
  `rombel_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `topic_id` int(10) UNSIGNED DEFAULT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `semester` enum('ganjil','genap') NOT NULL,
  `period_bucket` enum('tengah_ganjil','ganjil','tengah_genap','genap') NOT NULL,
  `tanggal` date NOT NULL,
  `nilai_sikap` decimal(5,2) DEFAULT NULL,
  `nilai_pengetahuan` decimal(5,2) DEFAULT NULL,
  `nilai_keterampilan` decimal(5,2) DEFAULT NULL,
  `bintang` int(11) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades_daily`
--

INSERT INTO `grades_daily` (`id`, `rombel_id`, `subject_id`, `topic_id`, `student_id`, `semester`, `period_bucket`, `tanggal`, `nilai_sikap`, `nilai_pengetahuan`, `nilai_keterampilan`, `bintang`, `deskripsi`, `recorded_by`, `created_at`, `updated_at`) VALUES
(160, 17, 66, 24, 170, 'ganjil', 'tengah_ganjil', '2026-06-25', 88.00, 88.00, 88.00, NULL, NULL, 1, '2026-06-25 05:47:21', '2026-06-25 05:47:21'),
(161, 17, 66, 24, 131, 'ganjil', 'tengah_ganjil', '2026-06-25', 97.00, 97.00, 89.00, NULL, NULL, 1, '2026-06-25 05:47:21', '2026-06-25 05:47:21'),
(162, 17, 66, 24, 128, 'ganjil', 'tengah_ganjil', '2026-06-25', 90.00, 90.00, 90.00, NULL, NULL, 1, '2026-06-25 05:47:21', '2026-06-25 05:47:21'),
(163, 17, 66, 24, 132, 'ganjil', 'tengah_ganjil', '2026-06-25', 89.00, 89.00, 89.00, NULL, NULL, 1, '2026-06-25 05:47:21', '2026-06-25 05:47:21'),
(164, 17, 66, 24, 148, 'ganjil', 'tengah_ganjil', '2026-06-25', 90.00, 90.00, 90.00, NULL, NULL, 1, '2026-06-25 05:47:21', '2026-06-25 05:47:21'),
(165, 17, 66, 24, 136, 'ganjil', 'tengah_ganjil', '2026-06-25', 81.00, 81.00, 81.00, NULL, NULL, 1, '2026-06-25 05:47:21', '2026-06-25 05:47:21'),
(166, 17, 66, 24, 163, 'ganjil', 'tengah_ganjil', '2026-06-25', 85.00, 85.00, 85.00, NULL, NULL, 1, '2026-06-25 05:47:21', '2026-06-25 05:47:21'),
(167, 17, 66, 24, 146, 'ganjil', 'tengah_ganjil', '2026-06-25', 87.00, 87.00, 87.00, NULL, NULL, 1, '2026-06-25 05:47:21', '2026-06-25 05:47:21'),
(168, 17, 66, 24, 156, 'ganjil', 'tengah_ganjil', '2026-06-25', 88.00, 88.00, 88.00, NULL, NULL, 1, '2026-06-25 05:47:21', '2026-06-25 05:47:21'),
(169, 17, 66, 24, 170, 'ganjil', 'tengah_ganjil', '2026-06-24', 70.00, 70.00, 70.00, NULL, NULL, 1, '2026-06-25 05:47:32', '2026-06-25 05:47:32'),
(170, 17, 66, 24, 131, 'ganjil', 'tengah_ganjil', '2026-06-24', 90.00, 90.00, 90.00, NULL, NULL, 1, '2026-06-25 05:47:32', '2026-06-25 05:47:32'),
(171, 17, 66, 24, 128, 'ganjil', 'tengah_ganjil', '2026-06-24', 80.00, 80.00, 80.00, NULL, NULL, 1, '2026-06-25 05:47:32', '2026-06-25 05:47:32'),
(172, 17, 66, 24, 132, 'ganjil', 'tengah_ganjil', '2026-06-24', 80.00, 80.00, 80.00, NULL, NULL, 1, '2026-06-25 05:47:32', '2026-06-25 05:47:32'),
(173, 17, 66, 24, 148, 'ganjil', 'tengah_ganjil', '2026-06-24', 60.00, 60.00, 60.00, NULL, NULL, 1, '2026-06-25 05:47:32', '2026-06-25 05:47:32'),
(174, 17, 66, 24, 146, 'ganjil', 'tengah_ganjil', '2026-06-24', 60.00, 60.00, 60.00, NULL, NULL, 1, '2026-06-25 05:47:32', '2026-06-25 05:47:32'),
(175, 17, 66, 24, 156, 'ganjil', 'tengah_ganjil', '2026-06-24', 60.00, 60.00, 60.00, NULL, NULL, 1, '2026-06-25 05:47:32', '2026-06-25 05:47:32'),
(176, 17, 66, 24, 131, 'ganjil', 'tengah_ganjil', '2026-06-23', 90.00, 90.00, 90.00, NULL, NULL, 1, '2026-06-25 05:47:41', '2026-06-25 05:47:41'),
(177, 17, 192, 26, 170, 'ganjil', 'tengah_ganjil', '2026-06-26', 80.00, 80.00, 80.00, NULL, NULL, 1, '2026-06-26 03:45:51', '2026-06-26 03:45:51'),
(178, 17, 192, 26, 131, 'ganjil', 'tengah_ganjil', '2026-06-26', 80.00, 80.00, 80.00, NULL, NULL, 1, '2026-06-26 03:45:51', '2026-06-26 03:45:51'),
(179, 17, 192, 26, 145, 'ganjil', 'tengah_ganjil', '2026-06-26', 80.00, 80.00, 80.00, NULL, NULL, 1, '2026-06-26 03:45:51', '2026-06-26 03:45:51'),
(180, 17, 192, 26, 128, 'ganjil', 'tengah_ganjil', '2026-06-26', 80.00, 80.00, 80.00, NULL, NULL, 1, '2026-06-26 03:45:51', '2026-06-26 03:45:51'),
(181, 17, 192, 26, 132, 'ganjil', 'tengah_ganjil', '2026-06-26', 80.00, 80.00, 80.00, NULL, NULL, 1, '2026-06-26 03:45:51', '2026-06-26 03:45:51'),
(182, 17, 192, 26, 170, 'ganjil', 'tengah_ganjil', '2026-06-25', 89.00, 89.00, 89.00, NULL, NULL, 1, '2026-06-26 03:46:11', '2026-06-26 03:46:11'),
(183, 17, 192, 26, 128, 'ganjil', 'tengah_ganjil', '2026-06-25', 90.00, 90.00, 90.00, NULL, NULL, 1, '2026-06-26 03:46:11', '2026-06-26 03:46:11'),
(184, 17, 192, 26, 132, 'ganjil', 'tengah_ganjil', '2026-06-25', 87.00, 87.00, 87.00, NULL, NULL, 1, '2026-06-26 03:46:11', '2026-06-26 03:46:11'),
(185, 17, 192, 26, 148, 'ganjil', 'tengah_ganjil', '2026-06-25', 89.00, 89.00, 89.00, NULL, NULL, 1, '2026-06-26 03:46:11', '2026-06-26 03:46:11'),
(186, 17, 192, 26, 136, 'ganjil', 'tengah_ganjil', '2026-06-25', 78.00, 87.00, 78.00, NULL, NULL, 1, '2026-06-26 03:46:11', '2026-06-26 03:46:11'),
(187, 17, 191, 28, 170, 'ganjil', 'tengah_ganjil', '2026-06-26', 80.00, 80.00, 80.00, NULL, NULL, 37, '2026-06-26 04:21:42', '2026-06-26 04:21:42'),
(188, 17, 191, 28, 145, 'ganjil', 'tengah_ganjil', '2026-06-26', 90.00, 90.00, 90.00, NULL, NULL, 37, '2026-06-26 04:21:42', '2026-06-26 04:21:42'),
(189, 17, 191, 28, 146, 'ganjil', 'tengah_ganjil', '2026-06-26', 90.00, 90.00, 90.00, NULL, NULL, 37, '2026-06-26 04:21:42', '2026-06-26 04:21:42'),
(190, 17, 191, 28, 156, 'ganjil', 'tengah_ganjil', '2026-06-26', 90.00, 90.00, 90.00, NULL, NULL, 37, '2026-06-26 04:21:42', '2026-06-26 04:21:42'),
(191, 17, 191, 28, 164, 'ganjil', 'tengah_ganjil', '2026-06-26', 90.00, 90.00, 90.00, NULL, NULL, 37, '2026-06-26 04:21:42', '2026-06-26 04:21:42'),
(192, 17, 190, 29, 131, 'ganjil', 'tengah_ganjil', '2026-06-26', 90.00, 90.00, 90.00, NULL, NULL, 1, '2026-06-26 05:10:13', '2026-06-26 05:10:13');

-- --------------------------------------------------------

--
-- Table structure for table `grade_descriptions`
--

CREATE TABLE `grade_descriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `rombel_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `semester` enum('ganjil','genap') NOT NULL,
  `period_bucket` enum('tengah_ganjil','ganjil','tengah_genap','genap') NOT NULL,
  `ranah` enum('sikap','pengetahuan','keterampilan') NOT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kkm_settings`
--

CREATE TABLE `kkm_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `jenjang` enum('TK','SD','SMP','SMA') NOT NULL,
  `grade` varchar(5) NOT NULL,
  `min_val` decimal(5,2) NOT NULL,
  `max_val` decimal(5,2) NOT NULL,
  `predikat` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kkm_settings`
--

INSERT INTO `kkm_settings` (`id`, `academic_year_id`, `jenjang`, `grade`, `min_val`, `max_val`, `predikat`) VALUES
(74, 20, 'SD', 'A+', 100.00, 100.00, 'Perfect'),
(75, 20, 'SD', 'A', 95.98, 99.99, 'Nice'),
(76, 20, 'SD', 'A-', 91.00, 95.99, 'Amazing'),
(77, 20, 'SD', 'B+', 86.00, 90.99, 'Terrific'),
(78, 20, 'SD', 'B', 81.00, 85.99, 'Good'),
(79, 20, 'SD', 'B-', 76.00, 80.99, 'Good'),
(80, 20, 'SD', 'C', 70.00, 75.99, 'Average'),
(81, 20, 'SD', 'D', 0.00, 69.99, 'Below Average');

-- --------------------------------------------------------

--
-- Table structure for table `parents_auth`
--

CREATE TABLE `parents_auth` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `must_change_pw` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents_auth`
--

INSERT INTO `parents_auth` (`id`, `student_id`, `password_hash`, `must_change_pw`, `is_active`, `last_login_at`, `created_at`) VALUES
(17, 259, '$2y$10$M3aHxliJOXOVrVyNmM2W3.HjXDUrYZSR3eMkssV.SBuCdKHiHZVQS', 0, 1, '2026-06-25 13:31:54', '2026-06-23 00:38:29'),
(18, 170, '$2y$12$iinLZAe/1IIXHP3vWXt9t.DHJGXKonXRZvKI0SJRTaythq0Mpv7o.', 1, 1, '2026-06-25 13:17:41', '2026-06-25 04:54:33'),
(19, 131, '$2y$10$YwV9T1d7jc464NEk1MKrQOyORg.jaWcI9Kn.IOoM4WYKPKzXsj/gK', 0, 1, '2026-06-26 12:54:08', '2026-06-26 04:54:08');

-- --------------------------------------------------------

--
-- Table structure for table `parent_remember_tokens`
--

CREATE TABLE `parent_remember_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_auth_id` int(10) UNSIGNED NOT NULL,
  `selector` char(32) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_signatures`
--

CREATE TABLE `report_signatures` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `jenjang` enum('TK','SD','SMP','SMA') NOT NULL,
  `slot` enum('wali','kepsek','direktur','parent') NOT NULL,
  `nama` varchar(120) DEFAULT NULL,
  `jabatan` varchar(120) DEFAULT NULL,
  `ttd_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_templates`
--

CREATE TABLE `report_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `jenjang` enum('TK','SD','SMP','SMA') NOT NULL,
  `layout_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_json`)),
  `layout_hidden_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_hidden_json`)),
  `header_img` varchar(255) DEFAULT NULL,
  `footer_img` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_templates`
--

INSERT INTO `report_templates` (`id`, `academic_year_id`, `jenjang`, `layout_json`, `layout_hidden_json`, `header_img`, `footer_img`) VALUES
(11, 20, '', NULL, NULL, NULL, NULL),
(12, 20, 'SD', NULL, NULL, NULL, NULL),
(13, 20, 'SMA', NULL, NULL, NULL, NULL),
(14, 20, 'SMP', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rombel`
--

CREATE TABLE `rombel` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `jenjang` enum('TK','SD','SMP','SMA') NOT NULL,
  `tingkat` tinyint(3) UNSIGNED NOT NULL,
  `nama` varchar(40) NOT NULL,
  `wali_id` int(10) UNSIGNED DEFAULT NULL,
  `kapasitas` smallint(5) UNSIGNED NOT NULL DEFAULT 40,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rombel`
--

INSERT INTO `rombel` (`id`, `academic_year_id`, `jenjang`, `tingkat`, `nama`, `wali_id`, `kapasitas`, `created_at`, `updated_at`, `deleted_at`) VALUES
(14, 20, '', 0, 'PG', NULL, 40, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(15, 20, '', 1, 'TK A', NULL, 40, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(16, 20, '', 2, 'TK B', NULL, 40, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(17, 20, 'SD', 1, 'Kelas 1', 39, 40, '2026-06-21 22:54:13', '2026-06-23 23:09:10', NULL),
(18, 20, 'SMP', 7, 'Kelas 7', 39, 40, '2026-06-21 23:05:02', '2026-06-22 04:51:43', NULL),
(19, 20, 'SMA', 10, 'Kelas 10', 41, 40, '2026-06-21 23:10:21', '2026-06-22 04:51:48', NULL),
(20, 20, 'TK', 1, 'TK A', 37, 28, '2026-06-23 00:54:19', '2026-06-23 00:54:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rombel_members`
--

CREATE TABLE `rombel_members` (
  `rombel_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `joined_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rombel_members`
--

INSERT INTO `rombel_members` (`rombel_id`, `student_id`, `joined_at`) VALUES
(17, 119, '2026-06-21 22:54:13'),
(17, 120, '2026-06-21 22:54:13'),
(17, 121, '2026-06-21 22:54:13'),
(17, 122, '2026-06-21 22:54:13'),
(17, 123, '2026-06-21 22:54:13'),
(17, 124, '2026-06-21 22:54:13'),
(17, 125, '2026-06-21 22:54:13'),
(17, 126, '2026-06-21 22:54:13'),
(17, 127, '2026-06-21 22:54:13'),
(17, 128, '2026-06-21 22:54:13'),
(17, 129, '2026-06-21 22:54:13'),
(17, 130, '2026-06-21 22:54:13'),
(17, 131, '2026-06-22 03:04:02'),
(17, 132, '2026-06-21 22:54:13'),
(17, 133, '2026-06-21 22:54:13'),
(17, 134, '2026-06-21 22:54:13'),
(17, 135, '2026-06-21 22:54:13'),
(17, 136, '2026-06-21 22:54:13'),
(17, 137, '2026-06-21 22:54:13'),
(17, 138, '2026-06-21 22:54:13'),
(17, 139, '2026-06-21 22:54:13'),
(17, 140, '2026-06-21 22:54:13'),
(17, 141, '2026-06-21 22:54:13'),
(17, 142, '2026-06-21 22:54:13'),
(17, 143, '2026-06-21 22:54:13'),
(17, 144, '2026-06-21 22:54:13'),
(17, 145, '2026-06-21 22:54:13'),
(17, 146, '2026-06-21 22:54:13'),
(17, 147, '2026-06-21 22:54:13'),
(17, 148, '2026-06-21 22:54:13'),
(17, 149, '2026-06-21 22:54:13'),
(17, 150, '2026-06-21 22:54:13'),
(17, 151, '2026-06-21 22:54:13'),
(17, 152, '2026-06-21 22:54:13'),
(17, 153, '2026-06-21 22:54:13'),
(17, 154, '2026-06-21 22:54:13'),
(17, 155, '2026-06-21 22:54:13'),
(17, 156, '2026-06-21 22:54:13'),
(17, 158, '2026-06-21 22:54:13'),
(17, 159, '2026-06-21 22:54:13'),
(17, 160, '2026-06-21 22:54:13'),
(17, 161, '2026-06-21 22:54:13'),
(17, 162, '2026-06-21 22:54:13'),
(17, 163, '2026-06-21 22:54:13'),
(17, 164, '2026-06-21 22:54:13'),
(17, 166, '2026-06-21 22:54:13'),
(17, 167, '2026-06-21 22:54:13'),
(17, 168, '2026-06-21 22:54:13'),
(17, 169, '2026-06-21 22:54:13'),
(17, 170, '2026-06-22 03:04:02'),
(17, 171, '2026-06-21 22:54:13'),
(17, 172, '2026-06-21 22:54:13'),
(17, 173, '2026-06-21 22:54:13'),
(17, 174, '2026-06-21 22:54:13'),
(18, 175, '2026-06-21 23:05:02'),
(18, 176, '2026-06-21 23:05:02'),
(18, 177, '2026-06-21 23:05:02'),
(18, 178, '2026-06-21 23:05:02'),
(18, 179, '2026-06-21 23:05:02'),
(18, 180, '2026-06-21 23:05:02'),
(18, 181, '2026-06-21 23:05:02'),
(18, 182, '2026-06-21 23:05:02'),
(18, 183, '2026-06-21 23:05:02'),
(18, 184, '2026-06-21 23:05:02'),
(18, 185, '2026-06-21 23:05:02'),
(18, 186, '2026-06-21 23:05:02'),
(18, 187, '2026-06-21 23:05:02'),
(18, 188, '2026-06-21 23:05:02'),
(18, 189, '2026-06-21 23:05:02'),
(18, 190, '2026-06-21 23:05:02'),
(18, 191, '2026-06-21 23:05:02'),
(18, 192, '2026-06-21 23:05:02'),
(18, 193, '2026-06-21 23:05:02'),
(18, 194, '2026-06-21 23:05:02'),
(18, 195, '2026-06-21 23:05:02'),
(18, 196, '2026-06-21 23:05:02'),
(18, 197, '2026-06-21 23:05:02'),
(18, 198, '2026-06-21 23:05:02'),
(18, 199, '2026-06-21 23:05:02'),
(18, 200, '2026-06-21 23:05:02'),
(18, 201, '2026-06-21 23:05:02'),
(18, 202, '2026-06-21 23:05:02'),
(18, 203, '2026-06-21 23:05:02'),
(18, 204, '2026-06-21 23:05:02'),
(18, 205, '2026-06-21 23:05:02'),
(18, 206, '2026-06-21 23:05:02'),
(18, 207, '2026-06-22 02:38:04'),
(18, 208, '2026-06-21 23:05:02'),
(18, 209, '2026-06-21 23:05:02'),
(18, 210, '2026-06-21 23:05:02'),
(18, 211, '2026-06-21 23:05:02'),
(18, 212, '2026-06-21 23:05:02'),
(18, 213, '2026-06-21 23:05:02'),
(18, 214, '2026-06-21 23:05:02'),
(18, 215, '2026-06-21 23:05:02'),
(18, 216, '2026-06-21 23:05:02'),
(18, 217, '2026-06-21 23:05:02'),
(18, 218, '2026-06-21 23:05:02'),
(18, 219, '2026-06-21 23:05:02'),
(18, 220, '2026-06-21 23:05:02'),
(18, 221, '2026-06-21 23:05:02'),
(18, 222, '2026-06-21 23:05:02'),
(18, 223, '2026-06-22 02:38:04'),
(18, 224, '2026-06-21 23:05:02'),
(18, 225, '2026-06-21 23:05:02'),
(18, 226, '2026-06-21 23:05:02'),
(18, 227, '2026-06-21 23:05:02'),
(18, 228, '2026-06-21 23:05:02'),
(19, 229, '2026-06-21 23:10:21'),
(19, 230, '2026-06-21 23:10:21'),
(19, 231, '2026-06-21 23:10:21'),
(19, 232, '2026-06-21 23:10:21'),
(19, 233, '2026-06-21 23:10:21'),
(19, 234, '2026-06-21 23:10:21'),
(19, 235, '2026-06-21 23:10:21'),
(19, 236, '2026-06-21 23:10:21'),
(19, 237, '2026-06-21 23:10:21'),
(19, 238, '2026-06-21 23:10:21'),
(19, 239, '2026-06-21 23:10:21'),
(19, 240, '2026-06-21 23:10:21'),
(19, 241, '2026-06-21 23:10:21'),
(19, 242, '2026-06-21 23:10:21'),
(19, 243, '2026-06-21 23:10:21'),
(19, 244, '2026-06-21 23:10:21'),
(19, 245, '2026-06-21 23:10:21'),
(19, 246, '2026-06-21 23:10:21'),
(19, 247, '2026-06-21 23:10:21'),
(19, 248, '2026-06-21 23:10:21'),
(19, 249, '2026-06-21 23:10:21'),
(19, 250, '2026-06-21 23:10:21'),
(19, 251, '2026-06-21 23:10:21'),
(19, 252, '2026-06-21 23:10:21'),
(19, 253, '2026-06-21 23:10:21'),
(19, 254, '2026-06-21 23:10:21'),
(19, 255, '2026-06-21 23:10:21'),
(19, 256, '2026-06-21 23:10:21'),
(19, 257, '2026-06-21 23:10:21'),
(19, 258, '2026-06-21 23:10:21'),
(20, 259, '2026-06-23 00:54:37');

-- --------------------------------------------------------

--
-- Table structure for table `rombel_subject_teachers`
--

CREATE TABLE `rombel_subject_teachers` (
  `id` int(10) UNSIGNED NOT NULL,
  `rombel_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `semester` enum('ganjil','genap') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rombel_subject_teachers`
--

INSERT INTO `rombel_subject_teachers` (`id`, `rombel_id`, `subject_id`, `teacher_id`, `semester`, `created_at`) VALUES
(15, 17, 63, 32, NULL, '2026-06-22 02:25:21'),
(16, 17, 54, 28, NULL, '2026-06-22 03:04:44'),
(17, 18, 88, 44, NULL, '2026-06-22 03:05:15'),
(18, 17, 66, 34, NULL, '2026-06-23 23:08:03'),
(19, 17, 192, 34, NULL, '2026-06-26 03:43:49'),
(20, 17, 190, 43, NULL, '2026-06-26 03:54:28'),
(21, 17, 191, 32, NULL, '2026-06-26 03:54:43');

-- --------------------------------------------------------

--
-- Table structure for table `school_profile`
--

CREATE TABLE `school_profile` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `nama` varchar(160) NOT NULL DEFAULT 'Sekolah Saya',
  `npsn` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kota` varchar(80) DEFAULT NULL,
  `provinsi` varchar(80) DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `telp` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `website` varchar(160) DEFAULT NULL,
  `nama_direktur` varchar(120) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_profile`
--

INSERT INTO `school_profile` (`id`, `nama`, `npsn`, `alamat`, `kota`, `provinsi`, `kode_pos`, `telp`, `email`, `website`, `nama_direktur`, `logo_path`, `updated_at`) VALUES
(1, 'Bintang Mandiri School', '-', 'Jl. Raya Kampus Udayana Jl. Taman Ambengan 9 TM, Jimbaran, Nusa Dua, Badung Regency, Bali 80361', 'Jakarta', NULL, NULL, '(0361) 704646', 'info@bintangmandirischool.sch.id', 'https://bintangmandirischool.sch.id/', NULL, NULL, '2026-04-29 03:29:43');

-- --------------------------------------------------------

--
-- Table structure for table `semesters_state`
--

CREATE TABLE `semesters_state` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `semester` enum('ganjil','genap') NOT NULL,
  `semester_locked` tinyint(1) NOT NULL DEFAULT 0,
  `pts_locked` tinyint(1) NOT NULL DEFAULT 0,
  `pas_locked` tinyint(1) NOT NULL DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters_state`
--

INSERT INTO `semesters_state` (`id`, `academic_year_id`, `semester`, `semester_locked`, `pts_locked`, `pas_locked`, `start_date`, `end_date`) VALUES
(55, 20, 'ganjil', 0, 0, 0, '2026-06-01', '2026-12-31'),
(56, 20, 'genap', 0, 0, 0, '2027-01-01', '2027-06-30');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `nisn` varchar(10) NOT NULL,
  `nis` varchar(7) NOT NULL,
  `nama` varchar(120) NOT NULL,
  `jenjang` enum('TK','SD','SMP','SMA') NOT NULL,
  `tingkat` tinyint(3) UNSIGNED NOT NULL,
  `jk` enum('L','P') NOT NULL,
  `tempat_lahir` varchar(80) DEFAULT NULL,
  `tgl_lahir` date NOT NULL,
  `alamat` text DEFAULT NULL,
  `nama_ayah` varchar(120) DEFAULT NULL,
  `nama_ibu` varchar(120) DEFAULT NULL,
  `pekerjaan_ayah` varchar(80) DEFAULT NULL,
  `pekerjaan_ibu` varchar(80) DEFAULT NULL,
  `telp_ortu` varchar(30) DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `academic_year_id`, `nisn`, `nis`, `nama`, `jenjang`, `tingkat`, `jk`, `tempat_lahir`, `tgl_lahir`, `alamat`, `nama_ayah`, `nama_ibu`, `pekerjaan_ayah`, `pekerjaan_ibu`, `telp_ortu`, `foto_path`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(35, 20, 'PAUD000001', 'P000001', 'Nettadevi Zamdea Yawan', '', 0, 'P', 'Mangupura', '2023-04-10', 'Jl. Danau Batur V Perdana Graha Residence No 5 Lingk.Taman Griya Jimbaran', 'Ade Prima Mardiana', '081344961315', NULL, NULL, '081326404865', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(36, 20, 'PAUD000002', 'P000002', 'Ni Kadek Belda Danira Putri Widari', '', 0, 'P', 'Mangupura', '2022-12-21', 'Jl. Tegal Sari Gg Tegal Mas No 3 Jimbaran', 'Ni Komang Sri Martini', '081238109460', NULL, NULL, '081237240825', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(37, 20, 'PAUD000003', 'P000003', 'Made Araya Wimana Jinatriya', '', 0, 'L', 'Mangupura', '2023-01-16', 'Jl. Raya Mandiri - 53, Lingk. Taman Griya Jimbaran', 'Luh Tiwika Praba, S.Pd., M.Pd.', '081916232672', NULL, NULL, '081805439479', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(38, 20, 'PAUD000004', 'P000004', 'Putu Keina Roxanne Priantana', '', 0, 'P', 'Mangupura', '2022-12-22', 'Tempekan Giri Sari Pecatu Dusun.Tempekan Giri Sari Pecatu', 'Ni Made Yuniati', '085737001369', NULL, NULL, '081573938903', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(39, 20, 'PAUD000005', 'P000005', 'I Made Arkana Adiguna', '', 0, 'L', 'Mangupura', '2022-07-20', 'Jalan Taman Giri Gg Kamboja no 4, BR Mumbul Dusun. BR Mumbul Benoa', 'Kadek Andita Dwi Pratiwi', '081337802052', NULL, NULL, '081339888091', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(40, 20, 'PAUD000006', 'P000006', 'Made Prananda Wijaya Karang', '', 0, 'L', 'Buleleng', '2023-02-12', 'Jln.Raya Kampus Unud.Perumahan Graha Anyar .Blok.VI.No.11', 'I Gusti Ayu Ketut Wina Hariani', '085792584404', NULL, NULL, '082144172668', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(41, 20, 'PAUD000007', 'P000007', 'Made Sean Arya Sanjaya', '', 0, 'L', 'Denpasar', '2022-11-24', 'Br. Kangin, Desa Pecatu, Kec. Kuta Selatan,Kab. Badung', 'I Gst Agung A Densi Wulandari', '082147387243', NULL, NULL, '087835058616', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(42, 20, 'PAUD000008', 'P000008', 'Ni Kadek Zea Kanaya Pravita Bhuana', '', 0, 'P', 'Mangupura', '2022-10-10', 'Br. Tengah Pecatu Dusun. Br Tengah Pecatu', 'Anak Agung Istri Putri Ekaristyanti Dalem', '082236277567', NULL, NULL, '082132610014', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(43, 20, 'PAUD000009', 'P000009', 'I Kadek Nakula Radhiva Abiwara', '', 0, 'L', 'Denpasar', '2023-01-13', 'Jalan Karang Mas, Lingk. Pantai Sari Jimbaran', 'Luh Ayu Ari Santika Dewi, S.Sos.', '085739210090', NULL, NULL, '08980789808', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(44, 20, 'PAUD000010', 'P000010', 'I Komang Sadewa Radhiva Abiandra', '', 0, 'L', 'Denpasar', '2023-01-13', 'Jalan Karang Mas, Lingk. Pantai Sari Jimbaran', 'Luh Ayu Ari Santika Dewi, S.Sos.', '085739210090', NULL, NULL, '08980789808', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(45, 20, 'PAUD000011', 'P000011', 'Ni Made Keina Nattaya Jyoti', '', 0, 'P', 'Denpasar', '2023-02-15', 'Jl. Kor Jimbaran C, Blok A12 Perumahan Easterland Jimbaran', 'Ni Wayan Desi Aryaningsih', '081999906015', NULL, NULL, '081916050616', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(46, 20, '0000000012', '0000012', 'Gede Keenrakai Bameswara Sanjaya', 'SMA', 10, 'L', 'Badung', '2022-12-20', 'Jalan Bambang Kembar No. 1 Pecatu', 'Ni Luh Ria Dhyanti Dewi', '087883590866', NULL, NULL, '085737402710', NULL, 1, '2026-06-21 22:50:00', '2026-06-26 01:57:51', NULL),
(47, 20, 'PAUD000013', 'P000013', 'Josephine Elloise Victoria Tjoo', '', 0, 'P', 'Mangupura', '2023-03-18', 'Jl. Mandiri IV no. 8 Lingk. Taman Griya Jimbaran', 'Della Agnes Victoria', '-', NULL, NULL, '081217778620', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(48, 20, 'PAUD000014', 'P000014', 'I Gede Egnan Narantha Alvarendra', '', 0, 'L', 'Denpasar', '2022-08-28', 'Perum Bualu Indah, Jaln Flamboyan 4 Blok A84 Nusa Dua', 'Ni Putu Meidyani', '082247988587', NULL, NULL, '085777119854', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(49, 20, 'PAUD000015', 'P000015', 'Ni Putu Ryuka Kinandya', '', 0, 'P', 'Mangupura', '2022-11-18', 'Jl. Pratama No.93 Nusa Dua Dusun. Lingk Peken Benoa', 'Ni Nyoman Tri Rahayu Kusuma Dewi', '081915602302', NULL, NULL, '081936165164', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(50, 20, 'PAUD000016', 'P000016', 'Ni Putu Yuina Dewi Vantama', '', 0, 'P', 'Mangupura', '2023-07-30', 'Jln. Taman Ambengan, Gang Mangga, No. 4TM', 'Sandra Vasquien', '0881037171444', NULL, NULL, '0881037437484', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(51, 20, 'PAUD000017', 'P000017', 'I Wayan Abichandra Arsa Lingga', '', 0, 'L', 'Denpasar', '2022-10-22', 'Jalan Pengeracikan Gang Tambiak No.1', 'Ni Made Ardiani', '8.1320325965E10', NULL, NULL, '8.1282596453E10', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(52, 20, 'PAUD000018', 'P000018', 'Putu Ava Janitra', '', 0, 'P', 'Badung', '2022-09-06', 'Jl. Sanggar Alit No.5 Lingk. Perarudan Dusun. Lingk Perarudan Jimbaran.', 'Evy Clara Yoseph', '8.1353529162E10', NULL, NULL, '8.1338793473E10', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(53, 20, 'PAUD000019', 'A000019', 'I Putu Arsya Prabaswara Dwipayana', '', 1, 'L', 'Mangupura', '2022-03-08', 'Lingk. Puri Nusa Dua Gg Viii/63 Ys Benoa', 'I Putu Angga Dwipayana, S.Tr.Par', 'Ni Luh Gede Anggraeni Puspa Dewi, A.Md.Keb', NULL, NULL, '085738303526', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(54, 20, 'PAUD000020', 'A000020', 'Putu Desna Arsyanendra Rahjasa', '', 1, 'L', 'Mangupura', '2021-07-20', 'Lingkungan Balekembar, Benoa', 'PT.Surya Laksana Rahjasa, S.Tar.Par', 'Queency Esmeralda', NULL, NULL, '081339302364', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(55, 20, 'PAUD000021', 'A000021', 'Gede Bara Prabangkara Putra Bumi', '', 1, 'L', 'Klungkung', '2021-08-15', 'Dusun Getakan Desa Getakan, Kec. Banjarangkan Kab. Klungkung', 'Nyoman Bayu Bumi Ratnata', 'Desak Made Wiwid Jeniari', NULL, NULL, '087862282551', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(56, 20, 'PAUD000022', 'A000022', 'Emmaus Snow Bullen', '', 1, 'L', 'Kettering Inggris', '2021-08-31', 'Perdana Giri Cluster, Jl. Perdana 1 No.2, Benoa 80361', 'Matthew David Bullen', 'Devie Pangalila', NULL, NULL, '088211290011', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(57, 20, 'PAUD000023', 'A000023', 'Ni Komang Isyana Namirayasa Putri', '', 1, 'P', 'Denpasar', '2021-10-05', 'Jln. By Pass Ngurah Rai Perum Harvestland No. C9 Kuta', 'I Gede Wireyasa, Se', 'Ni Wayan Geminiyawati, Se', NULL, NULL, '\'-', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(58, 20, 'PAUD000024', 'A000024', 'Kadek Bian Sinatra Putra', '', 1, 'L', 'Denpasar', '2021-11-16', 'Jl. Sunset Road/LBC', 'I Komang Ivan Swarnadwipa,S.T', 'Dicka Puspita Ayu, S.E.', NULL, NULL, '087761649797', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(59, 20, 'PAUD000025', 'A000025', 'Ni Komang Areum Maudya Puwi', '', 1, 'P', 'Denpasar', '2021-09-29', 'Perum Beranda Garden Jl.Palm VIII No.7', 'Putu Wirtawan', 'Ni Kadek Ariasih', NULL, NULL, '081904294750', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(60, 20, 'PAUD000026', 'A000026', 'I Komang Aurelio Radhika Wiguna', '', 1, 'L', 'Denpasar', '2022-06-25', 'Jl. Lutut No.99 Siligita Nusa Dua', 'Agus Sastra Wiguna, S.Kom', 'Made Widya Dharma Santi, SE', NULL, NULL, '085333929814', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(61, 20, 'PAUD000027', 'A000027', 'Pande Kadek Dion Saktyawan', '', 1, 'L', 'Denpasar', '2022-06-07', 'Jl Giri Kencana No 6e Lingkungan Mekarsari Simpangan Jimbaran', 'Pande Made Sutawan', 'Ni Wayan Pani Astuti', NULL, NULL, '087861386266', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(62, 20, 'PAUD000028', 'A000028', 'Delson Dalbert Lin', '', 1, 'L', 'Mangupura', '2022-06-09', 'Jalan Teges Gede II No 6', 'Sanny', 'Sandri', NULL, NULL, '081238306868', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(63, 20, 'PAUD000029', 'A000029', 'I Wayan Mahesa Natha Gautama', '', 1, 'L', 'Denpasar', '2021-09-27', 'Jl. Karang Mas, LINK.PANTAI SARI JIMBARAN DUSUN. Lingk Pantai Sari Jimbaran', 'I Wayan Bagus Citra Wedana', 'Luh Ayu Ari Santika Dewi, S.Sos.', NULL, NULL, '-', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(64, 20, 'PAUD000030', 'A000030', 'Putu Ayuka Drisana Gantari Sulaksana', '', 1, 'P', 'Mangupura', '2021-09-17', 'Jalan Taman Giri Nusa Dua', 'I Wayan Gede Arya Sulaksana', 'Ni Made Ayu Aprilia', NULL, NULL, '085737605300', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(65, 20, 'PAUD000031', 'A000031', 'Rachel Maureen Jovita', '', 1, 'P', 'Pontianak', '2022-06-13', 'Jln. Uluwatu 2 Perumahan Jasmine Mensye no 9', 'Hendrawan Hasjim', 'Isti Cholisah', NULL, NULL, '082215566667', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(66, 20, 'PAUD000032', 'A000032', 'Putu Sinta Ganitri Irandani', '', 1, 'P', 'Mangupura', '2022-04-02', 'BR Dinas Suluban Pecatu Dusun. BR Dinas Suluban Pecatu', 'Kadek Ady Irawan', 'Desak Dwi Ardani', NULL, NULL, '085737054055', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(67, 20, 'PAUD000033', 'A000033', 'I Made Arya Ranaka Shankara', '', 1, 'L', 'Mangupura', '2021-08-28', 'Jl Uluwatu No.5. Lingk. Ubung Dusun. Lingk Ubung Jimbaran', 'I Made Arya Wira Maha Putra.S.H', 'Ni Nyoman Mega Silvia Wijana.S.M', NULL, NULL, '081339690868', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(68, 20, 'PAUD000034', 'A000034', 'Made Natta Praditya Putra', '', 1, 'L', 'Denpasar', '2022-04-21', 'Perum Bumi Jimbaran Asri Jl Kampus Udayana no 22', 'I Putu Martha Kresna Raditya', 'Made Ayu mas Prima Mandasari', NULL, NULL, '082144544448', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(69, 20, 'PAUD000035', 'A000035', 'I Gusti Putu Davindra Agung Prameswara', '', 1, 'L', 'Denpasar', '2021-11-08', 'Perumahan Puri Gading Jl. Elang B6 no 15 , Jimbaran , Kuta Selatan , Badung.Bali', 'I Gusti Putu Yuda Pariartha', 'Putu Lenny Swari Agustini', NULL, NULL, '081529424421', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(70, 20, 'PAUD000036', 'A000036', 'Ni Kadek Anindira Putri Prasani', '', 1, 'P', 'Denpasar', '2022-02-12', 'Jl. Nuansa Utama XXIII/18 A, Lingk. Taman Griya Dusun. Link Taman Griya Jimbaran', 'I Gede Abi Praboga', 'Luh Putu Sania Diandra Sari', NULL, NULL, '082145181264', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(71, 20, 'PAUD000037', 'A000037', 'I Gede Alexander Jordan Saputra', '', 1, 'L', 'Mangupura', '2020-11-05', 'Jl. Pura Kulat Pecatu', 'I Putu Edy Saputra', 'Yuliani Levelline Presisca', NULL, NULL, '081529342445', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(72, 20, 'PAUD000038', 'A000038', 'Vania Almira Zhang', '', 1, 'P', 'Mangupura', '2022-04-05', 'Perumahan Bhumi Jimbaran Asri. Jalan Kampus Udayana 2 No 13', 'Thian Bun', 'Vivi Wulandari', NULL, NULL, '081378655515', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(73, 20, 'PAUD000039', 'A000039', 'Ni Nyoman Luna Ashadanti Putra', '', 1, 'P', 'Denpasar', '2022-03-29', 'Jalan Taman Ambengan No 11 A', 'I Ketut Yossy Mandara Putra', 'Ni Nyoman Kristiana Dewi', NULL, NULL, '089621071120', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(74, 20, 'PAUD000040', 'A000040', 'Luh Gita Puspa Karang', '', 1, 'P', 'Buleleng', '2021-08-02', 'Jln.Raya Kampus Unud.Perumahan Graha Anyar .Blok.VI.No.11', 'Nyoman Sudiartawan', 'I Gusti Ayu Ketut Wina Hariani', NULL, NULL, '085792584404', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(75, 20, 'PAUD000041', 'A000041', 'Ni Komang Nadha Gistara Dananjaya', '', 1, 'P', 'Denpasar', '2021-09-12', 'Jl. Bukit Hijau No 18 Lingk. Mekar Sari Simpangan', 'I Wayan Dedik Saputra', 'Ni Komang Deni', NULL, NULL, '081237307777', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(76, 20, 'PAUD000042', 'A000042', 'Putu Darren Laksamana Putra Pratama', '', 1, 'L', 'Karangasem', '2021-06-24', 'Jalan Teras Bukit Il No 11A Goa Gong Jimbaran Kuta Selatan Badung Bali', 'I Gede Hary Pratama', 'Ni Kadek Dwi Purnami', NULL, NULL, '085792196681', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(77, 20, 'PAUD000043', 'A000043', 'I Wayan Nadendra Arya Wiguna', '', 1, 'L', 'Denpasar', '2021-12-14', 'Jln Kampus Udayana No. 11B Jimbaran', 'I Wayan Suka Bayu Adnyana', 'Ari Purwanti', NULL, NULL, '082247010787', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(78, 20, 'PAUD000044', 'A000044', 'I Kadek Varendra Battra Adiwiguna', '', 1, 'L', 'Mangupura', '2021-11-17', 'Jln. Pratama No. 93 Lingkungan Peken Benoa Nusa Dua', 'Kadek Battra Adiputra', 'Ni Kadek Artanti', NULL, NULL, '081236332156', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(79, 20, 'PAUD000045', 'A000045', 'Anak Agung Bagus Mahesa Artha Wicaksana', '', 1, 'L', 'Mangupura', '2021-09-17', 'Jalan Uluwatu II, Gang Tambak Sari No.10 Jimbaran', 'Anak Agung Ngurah Juliadi', 'Ni Made Lisna Cahya Lestari, A,Md', NULL, NULL, '085792040273', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(80, 20, 'PAUD000046', 'A000046', 'I Putu Agastya Chandra Radeva', '', 1, 'L', 'Mangupura', '2021-08-15', 'Jl. Uluwatu Gg. Bukit Hijau 99, Lingk. Jerokuta, Jimbaran.', 'I Putu Tedy, S.T.', 'Luh Gde Hari Martha Pratiwi, S.E.', NULL, NULL, '085792799227', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(81, 20, 'PAUD000047', 'A000047', 'Ni Nyoman Bulan Queensha Hasana Santika', '', 1, 'P', 'Denpasar', '2022-06-24', 'Jalan Labuhan Sait Banjar Dinas Labuhan Sait No39X Pecatu', 'I Made Santika,Se', 'Ni Made Yusmini,Se.,M.Si', NULL, NULL, '081299996478', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(82, 20, 'PAUD000048', 'A000048', 'I Gusti Putu Agung Prawara Wijaksa', '', 1, 'L', 'Denpasar', '2022-07-08', 'Jln Kubung Batu Raya No 18, TMN Griya Jimbaran', 'I Gusti Putu Agung Widyagoca', 'Ni Putu Putri Ayu Wijayanthi, S.Kom, MM', NULL, NULL, '087715670082', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(83, 20, 'PAUD000049', 'A000049', 'Luna Adreena Yousef', '', 1, 'P', 'Jakarta', '2021-11-09', 'Jl. Taman Ambengan IV Perumahan De Casa Blok A no. 4 Jimbaran', 'Yousef Nael Abdulraheem Mahmoud', 'Anggia Prasanti', NULL, NULL, '081283007345', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(84, 20, 'PAUD000050', 'A000050', 'Grey Benjamin', '', 1, 'L', 'Sleman', '2021-03-26', 'Jalan Nuansa Utama X No.9 Perumahan Kori Nuansa Taman Griya Jimbaran', 'Reggy Samantha', 'Prisilia Monalisa Rompies', NULL, NULL, '085228018133', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(85, 20, 'PAUD000051', 'A000051', 'Niken Arum Hayuningtias', '', 1, 'P', 'Mangupura', '2021-02-19', 'Jalan petanahan sari no 3', 'Marishal Dwi Aristiwan', 'Putu Candra Purnama Sari', NULL, NULL, '081918600101', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(86, 20, '3208534735', 'B000052', 'Mackenzie Charlotte Glover', '', 2, 'P', 'Batang', '2020-12-22', 'Jl Taman Giri Perum Samatha', 'Darren Lee Glover', 'Efa Lusiana', NULL, NULL, '-', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(87, 20, '3206767130', 'B000053', 'Jio Santoso', '', 2, 'L', 'Mangupura', '2020-12-01', 'Jl. Danau Tamblingan Xiv No.28 Lingk.Taman Griya', 'Ricky Santoso', 'Pebbi Lieyanti Salim', NULL, NULL, '-', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(88, 20, '3217722591', 'B000054', 'Anak Agung Raviendra Putra Yodha', '', 2, 'L', 'Mangupura', '2021-05-17', 'Jl. By Pass Ngr.Rai No.61 Lingk.Pengenderan Kedonganan Dusun. Lingk Pengenderan', 'A.A.Putu Purna Aditya Putra', 'Ni Kadek Desy Mahadewi', NULL, NULL, '087860675290', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(89, 20, '3200879246', 'B000055', 'Angel Sky Ubbama Romli', '', 2, 'P', 'Mangupura', '2020-09-02', 'Jl. Pura Dalem Gaing Mas Lingk Tegal', 'Moh. Romli', 'Yulia Mutiara Sukma, S.Pd', NULL, NULL, '082282989167', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(90, 20, '3215728328', 'B000056', 'Azzalea Roseli Ningrum', '', 2, 'P', 'Denpasar', '2021-04-20', 'Kuta Permai Iii No 19', 'Andrew', 'Amelia Diah Ningrum', NULL, NULL, '-', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(91, 20, '3214259946', 'B000057', 'Azzka Noah Ningrum', '', 2, 'L', 'Denpasar', '2021-04-20', 'Kuta Permai Iii No 19', 'Andrew', 'Amelia Diah Ningrum', NULL, NULL, '-', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(92, 20, '3212278387', 'B000058', 'I Gede Gandiwa Mashutama', '', 2, 'L', 'Denpasar', '2021-05-05', 'Jl. Pantai Sari No.13c Lingk. Menega Jimbaran Dusun. Lingk Menega Jimbaran', 'I Wayan Julyan Satria Megano', 'Ni Wayan Fitriani', NULL, NULL, '089696060794', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(93, 20, '3210036578', 'B000059', 'I Gede Riganzyo Sagaraswin Perdana', '', 2, 'L', 'Denpasar', '2021-01-11', 'Jalan Mrajapati No 10x Jimbaran', 'I Gede Agus Hendra Perdana Putra', 'Ni Wayan Nari Widiadih, A.Md', NULL, NULL, '081337306001', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(94, 20, '3201873237', 'B000060', 'I Made Luky Darmavian Putra', '', 2, 'L', 'Denpasar', '2020-09-06', 'Kampus Unud Tmn . Ambengan 11 A, Lingk Perarudan', 'I Ketut Yossy Mandara Putra', 'Ni Nyoman Kristiana Dewi', NULL, NULL, '089621071120', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(95, 20, '3210290172', 'B000061', 'I Putu Bryan Kennard Anggara', '', 2, 'L', 'Mangupura', '2021-03-05', 'Jl.Mrajapati No.10x Lingk.Pesalakan', 'I Komang Agus Hari Anggara', 'Ni Komang Riska Manika Damayanti', NULL, NULL, '082147938014', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(96, 20, '3208348005', 'B000062', 'I Wayan Bryan Danendra Putra Mahardika', '', 2, 'L', 'Mangupura', '2020-12-09', 'Lingk. Perarudan Jimbaran Dusun. Lingk Perarudan Jimbaran', 'I Nyoman Yoga Putra Mahardika', 'Ni Komang Sri Martini', NULL, NULL, '081238109460', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(97, 20, '3211722661', 'B000063', 'Ni Komang Olivia Ananda Putri Widiyana', '', 2, 'P', 'Denpasar', '2021-06-10', 'Banjar Dauh Pasar, Pergung-Mendoyo-Jembrana', 'I Nyoman Tri Widiyana', 'Wiwin Widayanti', NULL, NULL, '-', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(98, 20, '3207629391', 'B000064', 'Ni Made Damara Ayu Nawangwulan', '', 2, 'P', 'Mangupura', '2020-09-14', 'Jl Uluwatu No 21, Lingk .Pesalakan Dusun. Lingk Pesalakan Jimbaran', 'I Gede Eka Edwin Saputra, S.E', 'Putu Diah Indrawati Bendesa, Sh., Mh', NULL, NULL, '085737405229', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(99, 20, '3214776946', 'B000065', 'Nyoman Alisha Mas Ayu Gayatri', '', 2, 'P', 'Singaraja', '2021-06-14', 'Jl. Taman Giri Perum. Griya Nugraha Blok C.Ix No.20 Lingk. Mumbul Dusun. Link Mumbul Bedoa', 'I Putu Mas Darmawan', 'Ni Nyoman Suasih', NULL, NULL, '081339146257', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(100, 20, '3216850076', 'B000066', 'Putu Kania Lakshita Jinatriya', '', 2, 'P', 'Mangupura', '2021-04-02', 'Jl. Raya Mandiri- 53. Lingk Taman Griya Jimbaran Dusun. Lingk Taman Griya Jimbaran', 'Drg. I Putu Risca Pramana Yudha', 'Luh Tiwika Praba, S.Pd.,M.Pd', NULL, NULL, '081916232672', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(101, 20, '3214819586', 'B000067', 'Sky Fernando', '', 2, 'L', 'Mangupura', '2021-01-15', 'Jl.Nuansa Utama Xviii, Perum Griya Sari Permata Ii', 'Sandi', 'Marina Okta Viana', NULL, NULL, '082359587828', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(102, 20, '3216532636', 'B000068', 'Ni Putu Nadine Kalyani Putri', '', 2, 'P', 'Denpasar', '2021-01-23', 'Tempekan Selonding, Br.Kangin Pecatu Dusun. Br Kangin Pecatu', 'I Made Septia Suprayana', 'Ni Wayan Triariyani Giri, S.E.', NULL, NULL, '087860569543', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(103, 20, '3211457789', 'B000069', 'Kalea Chayra Ramadhani', '', 2, 'P', 'Tangerang Selatan', '2021-05-08', 'Jl. Kartika Iii No.101-G Kpad', 'Ikbal Adiguna Perdana Wibowo', 'Tesza Badina Korompis', NULL, NULL, '081228666872', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(104, 20, 'PAUD000070', 'B000070', 'Made Rezvan Adhitama', '', 2, 'L', 'Denpasar', '2021-04-17', 'Jln Pratama Gg Mertajati No 1 Nusa Dua', 'I Made Sudarsana, St.', 'Ni Luh Ayu Sundari', NULL, NULL, '081353022716', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(105, 20, 'PAUD000071', 'B000071', 'Putu Wiguna Jayantara', '', 2, 'L', 'Denpasar', '2020-04-14', 'BR. Kangin Dusun. BR Kangin Pecatu', 'I Wayan Jayantara', 'Luh Dewi Adnyani', NULL, NULL, '085739063830', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(106, 20, 'PAUD000072', 'B000072', 'Putu Arthur Radeva Putra', '', 2, 'L', 'Lombok Utara', '2020-11-25', 'Perumahan Mandala Griya, Jalan Maya Loka Block X No. 1 Benoa, Kuta Selatan', 'Putu Muliana', 'Nuning Wardani', NULL, NULL, '082147858227', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(107, 20, '3202588618', 'B000073', 'Giovano Tohjaya', '', 2, 'L', 'Denpasar', '2020-11-22', 'Jalan Pulau Buton no. 26 Dauhwaru Jembrana', 'Septhian Geraldy Tohjaya', 'Jesika Ginotodihardjo', NULL, NULL, '082247810777', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(108, 20, 'PAUD000074', 'B000074', 'Ni Putu Aileen Putri Prasani', '', 2, 'P', 'Denpasar', '2020-11-28', 'Jl. Nuansa Utama XXIII/18 A, Lingk Taman Rama', 'I Gede Abi Praboga', 'Luh Putu Sania Diandra Sari', NULL, NULL, '082145181264', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(109, 20, 'PAUD000075', 'B000075', 'Ni Putu Dahayu Gantari Yuki', '', 2, 'P', 'Denpasar', '2021-03-08', 'Jalan sempati no 21 tuban', 'I Made Rizky Ryan Kasada', 'Ni Putu Herma Yulianti', NULL, NULL, '087860880602', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(110, 20, '3211851406', 'B000076', 'I Gede Agus Birendra Adinata', '', 2, 'L', 'Mangupura', '2021-01-11', 'Jl Maya Loka, Perum Mandala Griya Blok 1 No 14, Lingkungan Terora, Benoa', 'I Komang Gede Sumaryana, Se', 'Kadek Yunita Dewi', NULL, NULL, '085253072494', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(111, 20, 'PAUD000077', 'B000077', 'Anslira Dwitama Weltris', '', 2, 'L', 'Denpasar', '2020-12-11', 'Jl. Celagi Basur gg Kamboja 1 no 120 jimbaran', 'Novan Eko Sudarsono', 'A.A Citra Dewi, A.Md', NULL, NULL, '085333730323', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(112, 20, 'PAUD000078', 'B000078', 'Made Alia Mita Maharani', '', 2, 'P', 'Denpasar', '2020-12-22', 'Jalan Pasraman Unud Blok E No. 38, Jimbaran, Bali', 'I Gede Anom Sastrawan, S.Par.,M.Par', 'Ni Putu Anik Prabawati, S.IP.,M.A.P.', NULL, NULL, '08113969898', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(113, 20, 'PAUD000079', 'B000079', 'Genevieve Shasya Permata Wijaya', '', 2, 'P', 'Denpasar', '2021-03-26', 'Jln Nuansa Utama XXI No 23 A Taman Griya Jimbaran', 'Yohanes Pandhu Wijaya', 'Vincensia Beatrick Permatasari', NULL, NULL, '087838475553', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(114, 20, 'PAUD000080', 'B000080', 'Lewin Havertz Yeo', '', 2, 'L', 'Tanjung Pinang', '2020-08-12', 'Jl. Blong Poh Gang Plamboyan, Jimbaran', 'Eko Kurniawan', 'Meta', NULL, NULL, '087893391636', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(115, 20, 'PAUD000081', 'B000081', 'I Komang Pradita Bayu Mahendra', '', 2, 'L', 'Tabanan', '2020-04-09', 'Jalan Taman Sari Gang Dahlia No 2 Lingkungan Kelan Abian, Tuban', 'I Putu Adhi Kerta Mahendra', 'Luh Gede Dwi Fernayanti', NULL, NULL, '08970207372', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(116, 20, 'PAUD000082', 'B000082', 'Kadek Derrel Hyuga Hardana', '', 2, 'L', 'Mangupura', '2020-11-11', 'Jl.Puri Nusa Dua Gg Viii No 63xx Lingk Bualu Benoa', 'Kadek Dwi Cahyadi Sukma', 'Ni Putu Dessy Meilanie', NULL, NULL, '085829492073', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(117, 20, 'PAUD000083', 'B000083', 'I Gede Made Agastya Rama Dharmaputra', '', 2, 'L', 'Mangupura', '2021-01-24', 'Jl.Nuansa Utama XVIII Perum Geria Sari Permata 2 KAV. D 6', 'I Gede Putu Agus Edy Saputra', 'Luh Putu Sriwidiasih', NULL, NULL, '081239228796', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(118, 20, 'PAUD000084', 'B000084', 'Kadek Briantya Devandra', '', 2, 'L', 'Mangupura', '2021-07-09', 'Br. Dinas Karang Boma Pecatu', 'I Made Suarsana', 'Ni Luh Putu Chandra Dewi, S.E.,M.M', NULL, NULL, '081933300660', NULL, 1, '2026-06-21 22:50:00', '2026-06-21 22:50:00', NULL),
(119, 20, '3201820694', 'SD10001', 'I Kadek Narendra Nesa Adi Putra', 'SD', 1, 'L', 'Denpasar', '2020-02-02', 'Jl. By Pass Ngurah Rai 48 Kedonganan, Kuta, Badung.', 'I Wayan Darsana Adi Putra,S.Par.', 'Luh Simarani, S.I.Kom.', 'Wiraswasta', 'Wirausaha', '0895394527942 / 081337230486', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(120, 20, '3190898503', 'SD10002', 'Keenandra Arshaka Muhammad', 'SD', 2, 'L', 'Mangupura', '2019-08-25', 'Perum Slbb Jimbaran Lingk.Kalanganyar', 'Mochamad Akbar', 'Nurul Hikmah Wijayanti', 'Karyawan Swasta', 'Karyawan Swasta', '081999665453 / 0817273746', NULL, 1, '2026-06-21 22:54:13', '2026-06-26 02:09:43', NULL),
(121, 20, '3204550004', 'SD10003', 'I Made Sakha Susastradi Astranegara', 'SD', 1, 'L', 'Denpasar', '2020-06-19', 'Perumahan Puri Mumbul Permai,Jalan Jepun 1 no b6 Jimbaran Kuta Selatan', 'I Putu Bagus Muliartha', 'Eka Candra Purnami', 'Karyawan Swasta', 'Karyawan Swasta', '083119604012 / 087805664307', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(122, 20, '3201692543', 'SD10004', 'Kadek Kae Binar Baskara', 'SD', 1, 'L', 'Denpasar', '2020-06-05', 'Nuansa Utama Xxiii/10,Lingk.Taman Geria', 'Putu Yudha Suartawan', 'Putu Eka Kusuma Anggareni', 'Karyawan Swasta', 'Karyawan Swasta', '085732703299 / 0811385628', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(123, 20, '3207229138', 'SD10005', 'Ni Putu Vanny Kalinda', 'SD', 1, 'P', 'Mangupura', '2020-07-14', 'Tempekan Bangket Kangin. Pecatu', 'I Putu Nova Suwastawan, S.E', 'Ni Luh Eva Karina, S.E.', 'Karyawan Swasta', 'Karyawan Swasta', '081805622156 / 081999243845', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(124, 20, '3201817060', 'SD10006', 'Ni Putu Gauri Kushmanda Shailaputri', 'SD', 1, 'P', 'Denpasar', '2020-04-04', 'Lingk Celuk Benoa Kuta Selatan', 'I Made Suwandyasa, S.E', 'Ni Gusti Agung Sri Setiawati', 'Karyawan Swasta', 'Karyawan Swasta', '081936049185 / 081999864783', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(125, 20, '3199984981', 'SD10007', 'I Komang Arya Daneswara', 'SD', 1, 'L', 'Mangupura', '2019-05-26', 'Perumahan Beranda Garden Palm Vi No 18 Mumbul, Benoa Kuta Selatan - Badung', 'I Ketut Arya Sentana Putra', 'Ni Luh Sri Diantini', 'Karyawan Swasta', 'Karyawan Swasta', '081348639354 / 08113804886', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(126, 20, '3199028295', 'SD10008', 'Ni Kadek Gita Mahagayatri Sanjaya', 'SD', 1, 'P', 'Denpasar', '2019-11-27', 'Perum Bukit Ungasan Permai Jalan Merak Gg Gunung Bromo B29', 'I Putu Arik Sanjaya, S.H', 'Ni Wayan Lisa Adiari, S.Gz', 'Karyawan Swasta', 'Karyawan Swasta', '082140078890 / 083119393309', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(127, 20, '3201347269', 'SD10009', 'Ni Putu Queensha Janitra Kandel', 'SD', 1, 'P', 'Denpasar', '2020-05-30', 'Jl. Danau Tempe I No.19, Br.Penopengan Dusun.Penopengan', 'I Wayan Tjahya Darmadi Kandel', 'Ni Putu Mayumi Sutama Putri', 'Karyawan Swasta', 'Karyawan Swasta', '081386625351 / 085804204021', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(128, 20, '3191815348', 'SD10010', 'Clarissa Gavriella Davelin', 'SD', 1, 'P', 'Mangupura', '2019-12-12', 'Jl. Danau Buyan Brt Dlm D.5-4 Lingk.Taman Griya', 'Haicing', 'Theresia Putri Piscesriwati', 'Karyawan Swasta', 'Karyawan Swasta', '081263835679 / 08179908882', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(129, 20, '3204911586', 'SD10011', 'I Made Vio Dwisa Pradipta', 'SD', 1, 'L', 'Denpasar', '2020-03-07', 'Br. Giri Sari Pecatu', 'I Kadek Rai Tangkas', 'Ni Wayan Luh Eka Sriyanti', 'Karyawan Swasta', 'Karyawan Swasta', '- / 081337241334', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(130, 20, '3191901553', 'SD10012', 'Putu Reina Adara Putri', 'SD', 1, 'P', 'Denpasar', '2019-09-24', 'Jln Pratama Gg Mertajati No 1 Nusa Dua', 'I Made Sudarsana, St.', 'Ni Luh Ayu Sundari', 'Karyawan Swasta', 'Karyawan Swasta', '081353022716 / 089656250676', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(131, 20, '3204341313', 'SD10013', 'Arxazuan Hadinata', 'SD', 1, 'L', 'Badung', '2020-04-12', 'Jl. Teluk Kendari No.5', 'Fikran Hadinata', 'Ade Purnama Hendrayani, S.T.', 'Wiraswasta', 'Wiraswasta', '081344705615 / 085749000458', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(132, 20, '3194023268', 'SD10014', 'Earnest Goh', 'SD', 1, 'L', 'Mangupura', '2019-06-03', 'Taman Baruna Blok Bougenville No 37', '-', 'Angelina Kristin', 'Wiraswasta', 'Ibu Rumah Tangga', '081211118559', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(133, 20, '3205069489', 'SD10015', 'Ng Emmanuella Gozali', 'SD', 1, 'P', 'Badung', '2020-02-02', 'Kebagusan City Tower Anggrek Unit 9a,7a', 'Stanislaus Albert Gozali', 'Josephina Pagano', 'Karyawan Swasta', 'Karyawan Swasta', '08129416264 / 081286501959', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(134, 20, '3190477066', 'SD10016', 'Maverick Ditson', 'SD', 1, 'L', 'Mangupura', '2019-09-11', 'Jl.Beranda Hijau Ii/10 Br.Kaja Jati', 'Aditya', 'Raissa', 'Karyawan Swasta', 'Ibu Rumah Tangga', '081912228898 / 081904923449', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(135, 20, '3200794202', 'SD10017', 'Putu Mikayla Gianna Putri', 'SD', 1, 'P', 'Mangupura', '2020-01-13', 'Jl.Melati Iv Blok: H-22, Lingk.Puri Mumbul Dusun.Lingk Taman Griya Jimbaran', 'I Kadek Juniandika', 'Ni Made Budiani', 'Wiraswasta', 'Karyawan Swasta', '087862291837 / 085237227199', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(136, 20, '3204400472', 'SD10018', 'Freya Alexandra Dominik', 'SD', 1, 'P', 'Denpasar', '2020-06-27', 'Casa De Bale Block A7 Jl. Taman Ambengan V, Jimbaran, Kec. Kuta Sel., Kabupaten Badung, Bali 80361', 'Aleksander Dominik Majewski Trefall', 'Rika Nurmayanti', 'Wirausaha', 'Wiraswasta', '+48606451351 / 081239760706', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(137, 20, '3208262057', 'SD10019', 'Ni Komang Aurora Losanna Grace Nakendra', 'SD', 1, 'P', 'Mangupura', '2020-06-15', 'Kampial Indah A 48 Lingk,Menesa Benoa', 'I Nengah Sumendra', 'Vilimaina Mata Balemailabasa Rokotuni', 'Karyawan Swasta', 'Karyawan Swasta', '081390914865 / 087751648687', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(138, 20, '3193721349', 'SD10020', 'Kwila Kahyang Kirania', 'SD', 1, 'P', 'Banyuwangi', '2019-12-20', 'Jalan, Kampus Unud Taman Ambengan, Gang Taman Mangga, Jimbaran', 'Ogut Ade Kurniawan', 'Rina Dewi Susanti', 'Wiraswasta', 'Wiraswasta', '081252462883 / 082145728131', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(139, 20, '3194193826', 'SD10021', 'Ni Putu Keisha Lavanya Jyoti', 'SD', 1, 'P', 'Denpasar', '2019-09-23', 'Br. Tengah Pecatu Dusun. Br Tengah', 'I Kadek Rama Dwi Purbaya', 'Ni Wayan Desi Aryaningsih', 'Wirausaha', 'Karyawan Swasta', '081999906015 / 081916050616', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(140, 20, '3195455339', 'SD10022', 'Jan Khaled Van Den Bos', 'SD', 1, 'L', 'Jakarta', '2019-08-25', 'Jl. Aneka Warga No. 8, kuta selatan (Depan villa alindra) KAB. BADUNG,JIMBARAN, Bali 80362', 'Gilbert Reynaldo Van Den Bos', 'Aisyah Rizki Savira', 'Wirausaha', 'Ibu Rumah Tangga', '088802200717 / 085156148269', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(141, 20, '3195355846', 'SD10023', 'Zenecka Candra Muhammad', 'SD', 1, 'L', 'Badung', '2019-08-24', 'Jl. Mayaloka Komplek Mandala Griya Blok B No. 5', 'Maskulin Candra Muhammad', 'Yeni Anggraini', 'TNI/POLRI', 'Ibu Rumah Tangga', '081246001660 / 081952617678', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(142, 20, '3204593041', 'SD10024', 'Kirana Adya Arinantha', 'SD', 1, 'P', 'Denpasar', '2020-08-26', 'Perum. Griya Nusa Damai, Jl. Kecubung No.6 Lingkungan Perarudan Jimbaran', 'Aditya Dwicipta Arinantha', 'Alyscha Anjelique Mary-lou Tulung', 'Karyawan Swasta', 'Karyawan Swasta', '081238797990 / 081236154121', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(143, 20, '3201404000', 'SD10025', 'Sei Kai Badudu', 'SD', 1, 'L', 'Kagawa', '2020-05-03', 'Jl. Palm IV No. 34 Perum. Beranda Garden, Benoa, Bali', 'Intan Gemala Badudu', 'Keiko Badudu', 'Karyawan Swasta', 'Ibu Rumah Tangga', '081321503640 / 082217236265', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(144, 20, '3193473074', 'SD10026', 'Ni Wayan Clarissa Chintya Aurelia Dewi', 'SD', 1, 'P', 'Mangupura', '2019-11-28', 'Banjar Karang Boma Pecatu', 'I Wayan Agus Arya Sumantara', 'Ni Wayan Ratna Miladewi', 'Karyawan Swasta', 'Wiraswasta', '081238561699 / 081337271221', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(145, 20, '3201703037', 'SD10027', 'Clara Richelle Saragih', 'SD', 1, 'P', 'Denpasar', '2020-01-20', 'Jl. Taman Giri Cluster No 5 Benoa', 'Parulian Saragih', 'Sintia Nugraheni', 'Karyawan Swasta', 'Karyawan Swasta', '082147053991 / 087865434373', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(146, 20, '3199284473', 'SD10028', 'I Kadek Arya Bramastra Kumara', 'SD', 1, 'L', 'Denpasar', '2019-03-20', 'Br. Kauh Pecatu Dusun. Br Kauh Pecatu, Kuta Selatan', 'I Wayan Agus Nelson Dwi Payana', 'Ni Komang Lina Triwidari', 'Wirausaha', 'Wirausaha', '085737063288 / 082341639010', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(147, 20, '3199091486', 'SD10029', 'I Putu Devansh Narendra Bramanstya', 'SD', 1, 'L', 'Mangupura', '2019-12-27', 'Br. Bakung Sari, Ungasan', 'I Wayan Adi Adnyana', 'Ni Komang Nurhandayani', 'Karyawan Swasta', 'Karyawan Swasta', '081529434052 / 081239590621', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(148, 20, '3192283647', 'SD10030', 'Fajar Daniel Thomas', 'SD', 1, 'L', 'Banyuwangi', '2019-12-07', 'Perum Green Lestari No. 17 Goa Gong Gg. Pura Pemecutan Br. Santhi Karya Dusun Br. Santhi Karya Ungasan', 'Rick Daniel Thomas', 'Kasiati', 'Wiraswasta', 'Wiraswasta', '61438667188 / 081337595915', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(149, 20, '3203533269', 'SD10031', 'Putu Misa Alana Kaneko', 'SD', 1, 'P', 'Mangupura', '2020-05-18', 'Jl. Pratama No. 69A Lingkungan Terora Benoa', 'Agus Masaki Kaneko', 'Luh Eka Novy Anggraeni', 'Wiraswasta', 'Wiraswasta', '081977770106 / 087761837666', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(150, 20, '3195950254', 'SD10032', 'Ni Kadek Dewi Aubree Pawasa Wimala', 'SD', 1, 'P', 'Denpasar', '2019-07-25', 'Jalan Pura Selonding Gang Kadi, pecatu', 'I Nyoman Pawasa Canis Swara', 'Ni Made Dwinda Sari Wiladika', 'Karyawan Swasta', 'Karyawan Swasta', '082144342797 / 081353777305', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(151, 20, '3192408540', 'SD10033', 'Maximilian Othniel Sanjaya Halim', 'SD', 1, 'L', 'Bali', '2019-12-16', 'Jl. Bang Bang Mali No. 19 Dusun. Bakung Sari, Ungasan, Kuta Selatan.', 'Nicky Saputra Halim', 'Maria Meryl Susan', 'Karyawan Swasta', 'Karyawan Swasta', '+62 859-5948-8805 / +62 877-77', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(152, 20, '3202573088', 'SD10034', 'Ni Putu Maureen Ayuniya Sarasvati', 'SD', 1, 'P', 'Mangupura', '2020-03-07', 'Lingkungan Kertha Pascima Tanjung Benoa Dusun Kertha Pascima', 'I Kadek Angga Brata', 'Ni Luh Devi Rosiana Putri', 'Karyawan Swasta', 'Karyawan Swasta', '081239749719 / 081353442292', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(153, 20, '3195047750', 'SD10035', 'Muhammad Attaya Keenandra Risky', 'SD', 1, 'L', 'Denpasar', '2019-04-12', 'Perumahan BKR III Blok E No. 10 Ungasan', 'Risky Widyantoro', 'Mardhiyah', 'Wiraswasta', 'Karyawan Swasta', '081805644288 / 082146144797', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(154, 20, '3198478145', 'SD10036', 'Tio Arjuna Siswando', 'SD', 1, 'L', 'Denpasar', '2019-12-07', 'Jl. Nuansa Udayana Utama No.22', 'Dimitri Swandanu', 'Shinta Defi Julian Fentilia', 'Karyawan Swasta', 'Ibu Rumah Tangga', '081237944558', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(155, 20, '3206394221', 'SD10039', 'Valencia Lin', 'SD', 1, 'P', 'Denpasar', '2019-09-05', 'Jl. Danau Batur Timur No. B2 Taman Griya Jimbaran', 'Danny Salim', 'Maria', 'Wiraswasta', 'Wiraswasta', '081353999850 / 082144914197', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(156, 20, '3192518465', 'SD10038', 'I Kadek Bagas Adinata Artawan', 'SD', 1, 'L', NULL, '1900-01-01', 'Jalan Aneka Warga No. 1 Lingkungan Taman Griya, Jimbaran. 80364', 'I Wayan Adiartawan', 'Ni Luh Putu Sari Kenti', 'Karyawan Swasta', 'Karyawan Swasta', '081384369447 / 081238566943', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(158, 20, '3197890790', 'SD10040', 'I Putu Davin Aryan Pavitra Bhuana', 'SD', 1, 'L', 'Mangupura', '2019-10-18', 'Br. Tengah Pecatu, Pecatu, Kuta Selatan', 'I Wayan Wahyu Surya Bhuana', 'Anak Agung Istri Putri Ekaristyanti Dalem', 'TNI/POLRI', 'TNI/POLRI', '082236277567 / 082132610014', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(159, 20, '3196074294', 'SD10041', 'Kai Maveric Sullivan', 'SD', 1, 'L', 'Bali', '2019-07-24', 'Jl. Parigata 708', NULL, 'Sofia Wati', NULL, 'Karyawan Swasta', '085738496109', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(160, 20, '3191380019', 'SD10042', 'Mario Binaglia', 'SD', 1, 'L', 'Mangupura', '2019-10-03', 'Jl. Permata Siligita Blok II No. 23, Lingkungan Bualu Dusun. Lingkungan Bualu Benoa', 'Lamberto Binaglia', 'Ni Wayan Sutrianingsih', 'Lainnya', 'Ibu Rumah Tangga', '08174714775 / 087862535823', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(161, 20, '3190440667', 'SD10043', 'Ni Luh Clarissa Ayudia Putri', 'SD', 1, 'P', 'Mangupura', '2019-11-19', 'Jl. Celagi Nunggul Gg. Melati No.4, Br. Sawangan, Nusa Dua Selatan.', 'I Wayan Suryantara', 'Ni Putu Ayu Sukma Dewi', 'Lainnya', 'Karyawan Swasta', '+62 819 99379152 / 08224717555', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(162, 20, '3204291166', 'SD10044', 'Ni Luh Putu Ilona Widya Putri Sarastya', 'SD', 1, 'P', 'Mangupura', '2019-06-28', 'Link.Kertha Pascima Tanjung Benoa, Dusun. Link Kertha Pascima Tanjung Benoa', 'I Ketut Aditya Putra', 'Putu Adiyanti Parmita Putri', 'Wiraswasta', 'Wiraswasta', '082144082113 / 08170017490/081', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(163, 20, '3209893014', 'SD10045', 'I Gede Gusta Shankara Treasure Saputra', 'SD', 1, 'L', 'Mangupura', '2020-05-10', 'Beranda Garden Palm III No. 7, Manesa, Benoa, Kuta Selatan.', 'Made Agus Saputra', 'Ni Ketut Suastari', 'Karyawan Swasta', 'Karyawan Swasta', '081529652982 / 081238996992', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(164, 20, '3201586017', 'SD10046', 'I Made Casey Mahawira Artana', 'SD', 1, 'L', 'Mangupura', '2020-03-08', 'Jl. Calonarang No.3, Lingkungan Peken Dusun Lingk. Peken Benoa', 'I Made Juliartana', 'Ni Wayan Dewi Sudyantari', 'Wiraswasta', 'Wiraswasta', '081236692844 / 082237343664', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(165, 20, '3205361340', 'SD10047', 'Amoreiza Berly Kusumo', 'SD', 2, 'P', 'Denpasar', '2020-07-12', 'Jl. Nuansa Bukit X No. 19 Kori Nuansa Benoa, Badung, Kuta Selatan.', 'RM.Sony Kusumo', 'D. Noviana P. Astuti', 'Wiraswasta', 'Ibu Rumah Tangga', '081215145858 / 081936094493', NULL, 1, '2026-06-21 22:54:13', '2026-06-26 01:55:18', NULL),
(166, 20, '3191410386', 'SD10048', 'Joanna Moyra Wimantha', 'SD', 1, 'P', 'Denpasar', '2019-09-15', 'Perum. Mandala Griya IX No. 2, Jl. Maya Loka, Kel. Benoa, Kec. Kuta Selatan, Badung.', 'Iwan', 'Louisa Adrianne Wimantha', 'Karyawan Swasta', 'Ibu Rumah Tangga', '081239308035 / 08113958883', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(167, 20, '3205207209', 'SD10049', 'Ni Putu Emily Natasha Putri', 'SD', 1, 'P', 'Badung', '2020-04-05', 'Perum. Taman Sakura Gg. Sakura III Blok C17 Jimbaran.', 'I Nengah Wahyu Astina Putra', 'Ni Luh Made Tresna Dwi Prabayanti', 'Karyawan Swasta', 'Karyawan Swasta', '087816141575 / 085953928528', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(168, 20, '3190934193', 'SD10050', 'Ni Luh Putu Kinandari Pramesti', 'SD', 1, 'P', 'Mangupura', '2019-12-29', 'Lingkungan Bhuana Gubug, Jimbaran', 'I Putu Eka Cahyadi Putra', 'Luh Ayu Wandira', 'Karyawan Swasta', 'Karyawan Swasta', '081558979367 / 082340924291', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(169, 20, '3206239848', 'SD10051', 'Putu Arshavina Nareswari Pramegiana', 'SD', 1, 'P', 'Badung', '2020-06-01', 'Jl. Perintis gang IV, Kampial', 'Komang Wisnu Gangga Suteja', 'Gusti Ayu Putu Padma Rayyani', 'Wiraswasta', 'Wiraswasta', '081337224268 / 081237217248', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(170, 20, '3191427004', 'SD10052', 'Alicia Anneke Agustin', 'SD', 2, 'P', 'Denpasar', '2019-02-27', 'Jl. Bovgenville IV Blok A/6, Lingk. Taman Griya Kelurahan Jimbaran, Kec. Kuta Selatan. Badung', 'Matthew Ellis Agustin', 'Dwik Astuti', 'Karyawan Swasta', 'Karyawan Swasta', '+62 878-6689-2903 / +62 819-99', NULL, 1, '2026-06-21 22:54:13', '2026-06-26 01:55:18', NULL),
(171, 20, '3203803237', 'SD10053', 'Kaylo Zulkarnain Effendi', 'SD', 1, 'L', 'Badung', '2020-02-13', 'Jl. Teras Bukit II no.14 Jimbaran, Kuta Selatan, Badung', 'Effendi', 'Ratna Sari Dewi', 'Karyawan Swasta', 'Ibu Rumah Tangga', '0817271173 / 08170771081', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(172, 20, '3190579158', 'SD10054', 'I Nyoman Jevan Bameswara Wikramawardhana Putra', 'SD', 1, 'L', 'Mangupura', '2019-10-02', 'Br. Labuan Sait Pecatu', 'I Kadek Darmadi Putra', 'Ni Wayan Wahyuni', 'Wiraswasta', 'Wiraswasta', '082146446167 / 082330038088', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(173, 20, '3191393481', 'SD10055', 'Reynand Zevanka Daftrian', 'SD', 1, 'L', 'Bandung', '2019-06-29', 'Jl. Giri Puspa Lestari Blok L No. 10, Lingkungan Mumbul, Dusun Mumbul, Benoa, Kuta Selatan', 'Divi Daftrian Reksha', 'Kristiane Kusumawardhani', 'Karyawan Swasta', 'Karyawan Swasta', '08112285113 / 08112384113', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(174, 20, '3191125492', 'SD10056', 'Kayyesa Arlyana Putri', 'SD', 1, 'P', 'Bali', '2019-09-23', 'Jl. Raya Taman Jimbaran Gg. Serongga A/1 No. 10. Jimbaran. Kec Kuta Selatan. Kab Badung. Bali 80361', 'Banuarly Ramadhan', 'Kristiana', 'Wirausaha', 'Ibu Rumah Tangga', '082236135758 / 081238143165', NULL, 1, '2026-06-21 22:54:13', '2026-06-21 22:54:13', NULL),
(175, 20, '0139425949', 'SMP7001', 'I Nyoman Angga Waisnawa Jaya', 'SMP', 7, 'L', 'Denpasar', '2013-08-06', 'Pantai Balangan No. 15 Ungasan', 'I Wayan Pon Rai Jaya', 'Eka Suryani', 'Karyawan Swasta', 'Karyawan Swasta', '081338778235 / 08113800332', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(176, 20, '0147762510', 'SMP7002', 'I Made Barathama Lesmana', 'SMP', 7, 'L', 'Denpasar', '2014-06-13', 'Jl.Taman Baruna Blok Melati 12 A', 'Putu Indra Lesmana, ST', 'Luh Raka Diana Fitriani , ST.,MT', 'Karyawan Swasta', 'Karyawan Swasta', '081999907146 / 081990999677', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(177, 20, '0147859377', 'SMP7003', 'Rebecca', 'SMP', 7, 'P', 'Badung', '2014-04-23', 'Taman Ambengan Jl. D\'Casa II Blok A No. 1', 'Doni', 'Mui Huang', 'Karyawan Swasta', 'Lainnya', '08127724594 / 081277273737', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(178, 20, '0149942417', 'SMP7004', 'I Made Sean Artha Winjaya', 'SMP', 7, 'L', 'Denpasar', '2014-05-13', 'MIPA 142, Perum Jimbaran Asri', 'Dwi Kelana Putra', 'Kadek Julia Ariawati', 'Wiraswasta', 'Wiraswasta', '081337222944 / 081337884644', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(179, 20, '0144063078', 'SMP7005', 'I Putu Putra Azka Pawasa Alderic', 'SMP', 7, 'L', 'Denpasar', '2014-02-05', 'Jl. Pura Selonding Gg. Kadi, Br Tengah Pecatu', 'I Nyoman Pawasa Canis Swara', 'Ni Made Dwinda Sari Wiladika', 'Karyawan Swasta', 'Karyawan Swasta', '081353777306 / 81353777305', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(180, 20, '0135825843', 'SMP7006', 'Ni Luh Putu Ayu Dewi Kirana Larasati', 'SMP', 7, 'P', 'Mangupura', '2013-10-27', 'Jl. Srikandi Gg. Will No.25 Lingk. Penyarikan Benoa', 'I Wayan Suprapta', 'Ni Luh Eka Suarningsih', 'Karyawan Swasta', 'Karyawan Swasta', '085737222610 / 081547229988', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(181, 20, '0147834501', 'SMP7007', 'Ni Made Shellyna Srilidia Dewi', 'SMP', 7, 'P', 'Mangupura', '2014-01-28', 'Br. Karang Boma Pecatu', 'I Wayan Eka Artana Putra', 'Ni Made Triamadia Dewi', 'Karyawan Swasta', 'Lainnya', '082236323073 / 081339468669', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(182, 20, '0143808188', 'SMP7008', 'I Gede Abhi Satya Wiguna', 'SMP', 7, 'L', 'Denpasar', '2014-04-15', 'Jl. Sulut No. 99 Siligita Nusa Dua', 'Agus Sastra Wiguna, S.Kom', 'Made Widya Dharma Santi, SE', 'Wiraswasta', 'Wiraswasta', '085333929814 / 081931005654', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(183, 20, '0138940739', 'SMP7009', 'Almira Tridyanti Winira', 'SMP', 7, 'P', 'Denpasar', '2013-10-11', 'Nuansa Barat I No. 25', 'Wendi Tri Wirawan', 'Yanti Herawati', 'Wiraswasta', 'Lainnya', '081338529622 / 081246267562', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(184, 20, '0136928465', 'SMP7010', 'Ni Komang Novita Wilyastini', 'SMP', 7, 'P', 'Denpasar', '2013-11-20', 'Jl. Raya Uluwatu Gg. Kunyit No. 18 Br Dinas Kangin Pecatu', 'I Nyoman Wisana', 'Ni Luh Anik Suwastini', 'Karyawan Swasta', 'Karyawan Swasta', '085792128840 / 085792128836', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(185, 20, '0131307574', 'SMP7011', 'I Gede Putu Agastya Krisna Dharmaputra', 'SMP', 7, 'L', 'Tabanan', '2013-09-20', 'Jalan Nuansa Utama XVIII, perumahan Griya Sari Permata 2 Kav. D6, Taman Griya', 'I Gede Putu Agus Edy Saputra', 'Luh Putu Sriwidiasih', 'Karyawan Swasta', 'Tidak bekerja', '081239228796 / 081238830732', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(186, 20, '0138475340', 'SMP7012', 'I Made Arya Narendra', 'SMP', 7, 'L', 'Denpasar', '2013-09-11', 'Perum. Beranda Garden Palm VI No. 18 Mumbul', 'I Ketut Arya Sentana Putra', 'Ni Luh Sri Diantini', 'Karyawan Swasta', 'Karyawan Swasta', '081338639354 / 081338018178', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(187, 20, '0142334452', 'SMP7013', 'I Putu Devdan Pradipa Satwika Pratama', 'SMP', 7, 'L', 'Denpasar', '2014-02-23', 'Jl. Hyang Desa No. 1 Pecatu', 'I Wayan Agus Edy Pratama Putra', 'Ni Putu Wika Yusita Lestari', 'Karyawan Swasta', 'Karyawan Swasta', '085738639391 / 081238160028', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(188, 20, '0149639164', 'SMP7014', 'Arayo Tanaya Bramarta', 'SMP', 7, 'L', 'Denpasar', '2014-02-14', 'Jl. Nuansa Timur XV No.6 Jimbaran', 'Tidy Bramarta', 'Marilyn', 'Wiraswasta', 'Karyawan Swasta', '087861423106 / 081805526984', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(189, 20, '0144219860', 'SMP7015', 'Regina Kwok', 'SMP', 7, 'P', 'Mangupura', '2014-03-11', 'Jl. Taman Ambengan Perum D\'Cassa Bale Blok C No. 6 Jimbaran', 'Agus Susanto', 'Luluk Lidiawati', 'Wiraswasta', 'Wiraswasta', '085238134567 / 082340055005', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(190, 20, '0147609003', 'SMP7016', 'Ni Made Erlyn Karisa Putri', 'SMP', 7, 'P', 'Mangupura', '2014-04-13', 'Jl. Poh Gading V No. 10 Jimbaran', 'I Wayan Agus Arya Suartana', 'Ni Wayan Rani', 'Karyawan Swasta', 'Karyawan Swasta', '081337828324 / 085792759708', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(191, 20, '3147297846', 'SMP7017', 'Rebecca Vilanova', 'SMP', 7, 'P', 'Bali', '2014-07-23', 'Jl. Nuansa Udayana I, Perumahan Nuansa Bali Residence No. 6 Kori Nuansa', 'Rama Susanto', 'Shiela Amelia Tjhin', 'Karyawan Swasta', 'Wiraswasta', '081339744897 / 081284117209', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(192, 20, '3145715321', 'SMP7018', 'Saviora Mesiana Bilangi', 'SMP', 7, 'P', 'Jimbaran', '2014-04-23', 'Perum Puri Gading Blok E2/17', '.-', 'Evangeline Bilangi', 'Tidak bekerja', 'Wiraswasta', '081337555633', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(193, 20, '0142464694', 'SMP7019', 'Jeanie Sarisha Pramana', 'SMP', 7, 'P', 'Denpasar', '2014-01-20', 'Perum. Bukit Pratama, Jl. Gong Suling I No.19 Goa Gong', 'I Gede Adhe Pramana', 'Fitri Yanti', 'Karyawan Swasta', 'Karyawan Swasta', '085739440700 / 082144926975', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(194, 20, '3136424005', 'SMP7020', 'Gede Evan Radhika Paramaditya', 'SMP', 7, 'L', 'Tabanan', '2013-10-24', 'Jl. Flamboyan II No. A29, Bualu Indah, Benoa', 'I Gede Widiputra', 'Ni Putu Elly Cristina', 'Apoteker', 'Apoteker', NULL, NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL);
INSERT INTO `students` (`id`, `academic_year_id`, `nisn`, `nis`, `nama`, `jenjang`, `tingkat`, `jk`, `tempat_lahir`, `tgl_lahir`, `alamat`, `nama_ayah`, `nama_ibu`, `pekerjaan_ayah`, `pekerjaan_ibu`, `telp_ortu`, `foto_path`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(195, 20, '3147142741', 'SMP7021', 'I Kadek Prama Aditya Putra Mudya', 'SMP', 7, 'L', 'Denpasar', '2014-05-26', 'Lingk. Sawangan Benoa', 'I Nyoman Mudya', 'Ni Kadek Witarini, S.S', 'Wiraswasta', 'Karyawan Swasta', NULL, NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(196, 20, '0144021271', 'SMP7022', 'Caroline Callysta Belvania', 'SMP', 7, 'P', 'Denpasar', '2014-05-15', 'Jl. Gong Suling 1/4. Bkt Pratama Lingk. As, Jimbaran', 'Antonius Heri Yuniarto', 'Lusna Widuri', 'Karyawan Swasta', 'Karyawan Swasta', NULL, NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(197, 20, '3145401436', 'SMP7023', 'Kadek Sunari Dewi Pertiwi', 'SMP', 7, 'P', 'Denpasar', '2014-03-12', 'Jl. Lumba-lumba No. 15, Lingk. Purwa Santhi, Tj Benoa', 'I Made Sirda Wiguna', 'Ni Made Parniati', 'Karyawan Swasta', 'Karyawan Swasta', NULL, NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(198, 20, '3140515173', 'SMP7024', 'Khaleesi Medina Vincent', 'SMP', 7, 'P', 'Jakarta', '2014-08-08', 'Jl. Danau Bratan IX, Taman Griya- Perdana Town House Blok C No.10', 'Vincent', 'Riska Methalina', 'Karyawan Swasta', 'Karyawan Swasta', '08111779310', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(199, 20, '0141354586', 'SMP7025', 'Vita Rachma Zidniansyah', 'SMP', 7, 'P', 'Bandung', '2014-01-29', 'Perumahan Taman Griya Jl. Nuansa Udayana Utara IV N029 Jimbaran, Kuta Selatan Kabupaten Badung', 'Heru Zidniansyah', 'Vidya Hapsari', 'Karyawan Swasta', 'Ibu Rumah Tangga', '081510044496 / 081572222714', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(200, 20, '0137335318', 'SMP7026', 'Raihan Al Bukhari Friesatama', 'SMP', 7, 'L', 'Denpasar', '2013-08-17', 'Perum Daisy Residence B2 Jl. Taman Putri Mumbul - Benoa', 'Okky Friesatama', 'Linda Sulistyowati', 'Pegawai BUMN', 'Ibu Rumah Tangga', NULL, NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(201, 20, '0149745152', 'SMP7027', 'I Putu Agasthya Narada Astawa', 'SMP', 7, 'L', 'Denpasar', '2014-06-19', 'Jl. Bhineka Jati-Jaya No.8 Kuta', 'I Kadek Puja Astawa', 'Ni Putu Santiari Dewi', 'Karyawan Swasta', 'Karyawan Swasta', '081338574567 / 08123997580', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(202, 20, '0148155894', 'SMP7028', 'Aurellio Alfariel Rayhan', 'SMP', 7, 'L', 'Pontianak', '2014-03-06', 'Jl. Uluwatu II Gg. Tambak Sari Perumahan Jasmine Mensye No.09', '-', 'Isti Cholisah', 'Tidak bekerja', 'Wiraswasta', '- / 081370508989', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(203, 20, '0146615727', 'SMP7029', 'Gabriella Abigail Nichols Lumintang', 'SMP', 7, 'P', 'Denpasar', '2014-07-29', 'Perdana Townhouse 1 Blok D4 Taman Griya Jimbaran', 'Maicel Hendra Lumintang', 'Elisa Soesilo', 'Karyawan Swasta', 'Karyawan Swasta', '081337770130 / 082144914198', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(204, 20, '0136366604', 'SMP7030', 'Keanu Arkana Muhammad', 'SMP', 7, 'L', 'Mangupura', '2013-08-24', 'Perumahan Beranda Garden, Jl. Palm X/14 Beni', 'Mochamad Akbar', 'Nurul Hikmah Wijayanti', 'Karyawan Swasta', 'Karyawan Swasta', '081999665453 / 0817273746', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(205, 20, '0139458387', 'SMP7031', 'Ni Kadek Pracandhana Daranindra Sudana', 'SMP', 7, 'P', 'Denpasar', '2013-11-01', 'Lingk. Bhuana Gubug Jimbaran Dusun, Lingk. Bhuana Gubug Jimbaran', 'I Made Intaran Sukamdana', 'Ni putu novi aryanti', 'Karyawan Swasta', 'Ibu Rumah Tangga', '08174766194 / +62 812-3611-634', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(206, 20, '0135727967', 'SMP7032', 'Ni Luh Keisya Kiana Anggraini Putri', 'SMP', 7, 'P', 'Denpasar', '2014-07-02', 'Perum Taman Graha 2 Blok D34, Jimbaran, Kuta Selatan, Badung', 'I Made Agus Widarmayuda', 'Gusti Ayu Made Erna Parwati', 'Karyawan Swasta', 'Karyawan Swasta', '087861784999 / 087861784999', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(207, 20, '0137178696', 'SMP7033', 'Adrian Pradita Wibawa', 'SMP', 7, 'L', 'Denpasar', '2013-09-28', 'Jl. Pengeracikan Gg.IV/5 B Link. Ketapang Kedonganan', 'Aris Satrya Wibawa.SE', 'Ni Made Ayu Sutariani', 'Wiraswasta', 'Wiraswasta', '085857575580 / 081999909455', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(208, 20, '0141008056', 'SMP7034', 'Ida Ayu Lady Gina Pradnyani', 'SMP', 7, 'P', 'Badung', '2014-10-27', 'Perumahan Bumi Jimbaran Asri, Jalan pertanian No. 74, Jimbaran', 'Ida Bagus Alit Pradnyana', 'Ni Gusti Ketut Ayu Sukertini', 'Karyawan Swasta', 'Karyawan Swasta', '081339160459 / 082144321409', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(209, 20, '0131854648', 'SMP7035', 'Tafana Maryam Aqilah', 'SMP', 7, 'P', 'Jakarta', '2013-08-29', NULL, 'Muchamad Taufik', 'Evelin Indyani', 'Karyawan Swasta', 'Ibu Rumah Tangga', '62 818-749-345 / 087780805744', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(210, 20, '0183202102', 'SMP7036', 'Ariadne Vidyadhari Elia', 'SMP', 7, 'P', 'Bandung', '2014-07-06', 'Jl. Beranda Hijau III No. 24, Br. Kaja Jati, Kutuh, Kuta Selatan, Badung, Bali', 'Yosua Septian Elia', 'Benedicta Novena Sheila Putri', 'Wiraswasta', 'Karyawan Swasta', '\'08113886836 / 081931777748', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(211, 20, 'TMP7000037', 'SMP7037', 'I Gede Gandhi Badrikha Bayu Nugaha Wisesa', 'SMP', 7, 'L', 'Denpasar', '2014-01-01', 'Girimas Residence Blok A No. 6, Kuta Selatan, Badung', 'Gede Dedy Marttyana', 'Ni Ketut Ayu Winda Arini', 'Karyawan Swasta', 'Karyawan Swasta', '081558310888 / 081558311888', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(212, 20, '3132976714', 'SMP7038', 'Naura Ramadhany', 'SMP', 7, 'P', 'Bandung', '2013-02-28', 'Jl. Taman Jimbaran Gg. Serongga A/1 No. 10. Jimbaran. Kec Kuta Selatan. Kab Badung. Bali 80361', 'Banuarly Ramadhan', 'Kristiana', 'Wirausaha', 'Ibu Rumah Tangga', '082236135758 / 081238143165', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(213, 20, '0138549521', 'SMP7039', 'I Kadek Abhiyana Sanjaya Nadiputra', 'SMP', 7, 'L', 'Mangupura', '2013-03-18', 'Link Kertha Pascima, Tanjung Benoa', 'I Gede Jaya Pawitra. Ssn', 'Putu Febi Krisnadewi', 'Wiraswasta', 'Wiraswasta', '08133642744 / 081943449972', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(214, 20, '0133425530', 'SMP7040', 'Pande Gede Lucky Hero Davidson', 'SMP', 7, 'L', 'Singaraja', '2013-07-11', 'Jalan Serma Karma Gang Harley', 'Putu Wiratnaya', 'Sri Vina Kersina', 'Karyawan Swasta', 'Karyawan Swasta', '0859-6152-6777 / 087742946600', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(215, 20, '0148824465', 'SMP7041', 'Detha Keisya Salsabilla Aras', 'SMP', 7, 'P', 'Denpasar', '2014-06-15', 'JL Kesatria No 45X Ling. Tuban Geriya Tuban', NULL, 'Ita Sylviana Dewi', NULL, NULL, '+62 821-4466-6667', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(216, 20, 'TMP7000042', 'SMP7042', 'Jesslyn Alvina', 'SMP', 7, 'P', 'Batam', '2014-04-01', 'Nuansa utama XXVIII No.20', 'Siang ti', 'Kartini', 'Wiraswasta', 'Ibu Rumah Tangga', '085263701711 / 085253356018', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(217, 20, '3142137848', 'SMP7043', 'Muhammad Mas Abdul Karim', 'SMP', 7, 'L', 'Gresik', '2014-05-15', 'JL Taman Ambengan , Jimbaran, KEC KUTA SELATAN, KAB BADUNG', 'Ahmad Anang Sani', 'Siti Cholidah', 'Wiraswasta', 'IBU RUMAH TANGGA', '0895323524218 / 081331544140', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(218, 20, '0139621470', 'SMP7044', 'Toding Tandibayang', 'SMP', 7, 'L', 'Denpasar', '2013-07-03', 'Jl.Mertasari Jimbaran', 'Elias Tawa Pasassung', 'Elisabeth Yulita', 'Wiraswasta', 'Ibu Rumah Tangga', '08123984070 / 08113892473', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(219, 20, '0142141455', 'SMP7045', 'Julius Dai', 'SMP', 7, 'L', 'Denpasar', '2014-07-19', 'Jl. Taman Ambengan, Perum Casa De Bale, Block D no 7, Jimbaran - Kuta Selatan - Badung - 80364', 'Herman', 'Harnum Priyanti', 'Wiraswasta', NULL, '081338134888', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(220, 20, '0136834539', 'SMP7046', 'Ni Luh Sri Mulyani Wulandari', 'SMP', 7, 'P', 'Mangupura', '2013-12-04', 'Jl.Parigata Gg. 8 No.8 Lingk Tegal Jimbaran', 'I Wayan Sandiyasa', 'Yan Evy Susanthi Dewi', 'Karyawan Swasta', 'Karyawan Swasta', '\'082339702778 / 08113891108', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(221, 20, '0145211293', 'SMP7047', 'Kenzo Sebastian Pandapotan Hutauruk', 'SMP', 7, 'L', 'Jakarta', '2014-08-18', 'Jl. Taman Ambengan V No.7', 'Donald Marangkup Tua Hutauruk', 'Sandy Florentina', 'Karyawan Swasta', NULL, '087882801113 / 0817400151', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(222, 20, '0146867992', 'SMP7048', 'Fidel Manual Manoppo', 'SMP', 7, 'L', 'Denpasar', '2014-01-19', 'Jl. Uluwatu I Gg. Mecutan 7 Blok D No. 77', 'Ricardo Manoppo', 'Linawati', 'Wiraswasta', 'Wiraswasta', '087862362303 / 087862362302', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(223, 20, '3149457140', 'SMP7049', 'Agnes Kirana Wibowo', 'SMP', 7, 'P', 'Denpasar', '2014-09-01', 'Jl. D’south Townhouse NO.19, Taman Griya, Jimbaran', 'Cahyadi Krisna Aji Wibowo', 'Meirta Putri Vicratosia', 'Wirausaha', 'Karyawan Swasta', '081330805968', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(224, 20, '0147143338', 'SMP7050', 'Taishi Sr Tsukamoto', 'SMP', 7, 'L', 'Denpasar', '2014-03-13', 'Perum Taman Griya, Griya Nuansa Pratama Jl. Bougenville Boullevard No. 48 Jimbaran', 'Kazuhito Tsukamoto', 'Sri Rahayu', 'Karyawan Swasta', 'Wiraswasta', '0811150872 / 081225248980', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(225, 20, '1022245022', 'SMP7051', 'I Gst Agung Kruse Von Warfle', 'SMP', 7, 'L', 'Jembrana', '2013-06-07', 'Jl. Maya Loka, Perumahan Mandala Griya, Blok 3 No.12', 'Kenneth Ray Warfle', 'I Gst. Agung Ayu Putu Sri Anggraeni', 'Wiraswasta', 'Ibu Rumah Tangga', '087862028660 / 081246526125', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(226, 20, '0132355944', 'SMP7052', 'Koming Gendis Michelia Merciana', 'SMP', 7, 'P', 'Mangupura', '2013-11-03', 'Jl. Ayodia 5, Nusadua - Bali', 'I Nyoman Sugiana', 'Ni Made Dwi Madesyawati', 'Karyawan Swasta', 'Karyawan Swasta', '085935102029 / 081936005329', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(227, 20, '0144813650', 'SMP7053', 'Jevan Gilbert', 'SMP', 7, 'L', 'Bekasi', '2014-05-08', 'JL. POLTEK NO.5', 'WENDI', 'Suni frerida', 'Wirausaha', 'Ibu Rumah Tangga', '085691166892 / 0895616717555', NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(228, 20, 'TMP7000054', 'SMP7054', 'Putu Charissa Naya Stanly', 'SMP', 7, 'P', NULL, '1900-01-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-06-21 23:05:02', '2026-06-21 23:05:02', NULL),
(229, 20, '0116523891', '0400310', 'Aurora Catherryn Galaxia Wibowo', 'SMA', 10, 'P', 'Denpasar', '2011-05-25', 'Perumahan Beranda Garden Jalan Palm IV No. 44 Link. Menesa Jimbaran', 'Mohamad Erry Wibowo', 'Sheila Desida Aisyah', 'Karyawan Swasta', 'Karyawan Swasta', '081805419099 / 08123867797', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(230, 20, '3117796831', '0400327', 'I Putu Prama Raditya Suputra', 'SMA', 10, 'L', 'Mangupura', '2011-05-10', 'Jl. Srikandi Gang Tangkas No. 5C lingk. Peminge Nusa Dua', 'I Made Suantana', 'Ayu Dian Puspayani S.Putri', 'Wiraswasta', 'Guru', '081337164799 / 081236162081', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(231, 20, '0108516073', '0400350', 'Ni Luh Putu Diva Maharani', 'SMA', 10, 'P', 'Mangupura', '2010-07-19', 'Jl. Taman Jati No. 3 A Mumbul', 'I Wayan Budiarta', 'Ni Wayan Sulasmiathi', 'Karyawan Swasta', 'Karyawan Swasta', '08123995715 / 081236367557', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(232, 20, '0111197495', '0400317', 'Dave Chen', 'SMA', 10, 'L', 'Denpasar', '2011-06-18', 'Taman Graha Blok A4, Lingk. Taman Griya Jimbaran', 'Gunawan Chandra', 'Fenny', NULL, 'Karyawan Swasta', NULL, NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(233, 20, '0115026531', '0400347', 'Maximilian Nugroho', 'SMA', 10, 'L', 'Medan', '2011-05-28', 'Jl. Taman Giri Asri, Perum. Samatha B-6 Lingk. Mumbul', 'Eric Nugroho', 'Rosita', 'SWASTA', 'Swasta', NULL, NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(234, 20, '3107891163', '0400349', 'Ni Luh Bertha Juvenis Nakendra', 'SMA', 10, 'P', 'Suva, Fiji', '2010-06-22', 'Kampial Indah A 48, Lingk. Menesa Benoa, Kuta Sealatan', 'I Nengah Sumendra', 'Vilimania Mata Balemailabasa Rokotuni', 'Karyawan Swasta', '-', '087751648687', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(235, 20, '0108385091', '0400318', 'Emmanuella Saka Bramudha', 'SMA', 10, 'P', 'Badung', '2010-11-25', 'Jl. Pura Masuka, Gg Jepun Perum Akasia, Park B. 15 Banjar Sari Karya, Ungasan', 'Antonius Bramudha, AMd. Per.', 'Rika Krisdiana', 'Karyawan Swasta', 'Karyawan Swasta', NULL, NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(236, 20, '0101906644', '0400351', 'Ni Putu Indira Ganhysita Sudana', 'SMA', 10, 'P', 'Denpasar', '2010-11-13', 'Lingk. Bhuana Gubug Jimbaran Dusun, Lingk. Bhuana Gubug Jimbaran', 'I Made Intaran Sukamdana', 'Ni Putu Novi Aryanti, A.Md, Kep.', 'Karyawan Swasta', 'Karyawan Swasta', NULL, NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(237, 20, '0103852327', '0400356', 'Reem Al Moatasam Al Saadi', 'SMA', 10, 'P', 'Cianjur', '2010-12-06', 'Samatha Mumbul Residence No. F5 Taman Giri, Jimbaran', 'AL Moatasam AL Saadi', 'Rosana Meldawati', '-', 'Karyawan Swasta', NULL, NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(238, 20, '0112082494', '0400314', 'Charicce Rosaline Suteja', 'SMA', 10, 'P', 'Jakarta', '2011-05-16', 'Jl. Perum. Biluk Residence No. 2C, Lingk. Taman Griya Jimbaran', 'Junaedy', 'Omega Soegiyanto', NULL, NULL, NULL, NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(239, 20, '0116554193', '0400329', 'Jessica Winoto', 'SMA', 10, 'P', 'Yogyakarta', '2011-01-05', 'Jl. Hukum 13-14 Bumi Jimbaran', 'Peter Eka Budhi Winoto', 'Ditta Ariastuti', 'Wiraswasta', 'IRT', '08122709591 / 085326797755', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(240, 20, '0114186802', '0400346', 'Maulitsa Shafrani', 'SMA', 10, 'P', 'Mangupura', '2011-06-25', 'Perum Puri gading Gg. Rambla No. I Jimbaran', 'Muji Hartono', 'Kembang Aulia', 'Wiraswasta', 'IRT', '081236311007 / 082147030148', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(241, 20, '0103869968', '0400330', 'Jevelyne', 'SMA', 10, 'P', 'Denpasar', '2010-12-08', 'Perum Taman Mulya, Jl. Dolphin No. 2 Jimbaran', 'Robi', 'Wenny Novarina', 'Karyawan Swasta', 'Tidak bekerja', '081337512005 / 08127721036', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(242, 20, '0101068683', '0400338', 'Leonel Juliant Alexander Ngguso', 'SMA', 10, 'L', 'Denpasar', '2010-07-06', 'Vila Bukit Tidar A-2 506, Merjosari, Lowokwaru, Malang, Jawa Timur', 'R. David Lestiawan Ngguso', 'Dessy Yurike Utami', 'Arsitek', 'IRT', NULL, NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(243, 20, '3109618594', '0400311', 'Bastian Jusuf Hadianto', 'SMA', 10, 'L', 'Denpasar', '2010-10-18', 'Perum. Nusa Dua Hill Blok G9. Lingk. Wisma Nusa Permai', 'Windianto', 'Desy Handayani', 'Karyawan Swasta', 'Karyawan Swasta', NULL, NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(244, 20, '0116236178', '0400352', 'Ni Putu Queen Delicia Ariasta', 'SMA', 10, 'P', 'Denpasar', '2011-04-30', 'Perum Kori Nuansa Timur Blok FF/24 Jimbaran', 'I Putu Eka Widana Ariasta', 'Hessy Santy', 'Karyawan Swasta', 'Karyawan Swasta', '08155786060 / 08113997288', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(245, 20, '0093950689', '0400341', 'Made Ariadne Sentani Mawiney', 'SMA', 10, 'P', 'Denpasar', '2009-10-03', 'Jl. Nangka, Gg Kertasari No. 10, Dps. BR/Lingk. Buana Sari, Dangin Puri', 'Ketut Gede Rama Kusuma', 'Elsye Prihatina Nyamayanti', 'Dokter', 'Apoteker', '083117995884', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(246, 20, '0119626347', '0400342', 'Made Kalea Wulan', 'SMA', 10, 'P', 'Jakarta', '2011-07-15', 'Beranda Mumbul, Jl. Kenari V No.1, Jimbaran', 'I Wayan Wirtama', 'Aa Ningsih', 'Lainnya', 'Wirausaha', '081239001010 / 081210693330', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(247, 20, '0108217197', '0400348', 'Nabila Loviana Putri', 'SMA', 10, 'P', 'Surabaya', '2010-12-07', 'Perum Sakura Regency III, C/15 Jimbaran', 'Chossy Mohar Lovian. A.Md. Par', 'Ariati Febriana Lukitasari, A.Md', 'Karyawan Swasta', 'Karyawan Swasta', '081703087929 / 081364024042', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(248, 20, '0118380254', '0400328', 'I Wayan Dhayana Krisna', 'SMA', 10, 'L', 'Mangupura', '2011-07-01', 'Jl. Pratama 87 Gang Welaka Lingk. Br. Terora Nusa Dua', 'I Wayan Sudika. Se', 'Ni Made Kerti', 'Karyawan Swasta', 'Wiraswasta', '08179783978 / 081934377911', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(249, 20, '0109212241', '0400339', 'Lionel Blessed Twelviano', 'SMA', 10, 'L', 'Denpasar', '2010-12-12', 'Jl. Danau Buyan Raya / 25, Lingk. Taman Griya Jimbaran', '-', 'Gebby Twelvia Sunny', NULL, NULL, NULL, NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(250, 20, '0119742643', '0400320', 'Gede Kenzie Kayana Arisatya', 'SMA', 10, 'L', 'Tinggarsari', '2011-02-05', 'Jalan Goa Gong - Gang Jepun No.3 Jimbaran', 'Made Sariada', 'Ni Kadek Susanti Dewi', 'Wiraswasta', 'Wiraswasta', '08123823624 / 082144124551', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(251, 20, '0097847406', '0400315', 'Charlotta Jasmine Adiba', 'SMA', 10, 'P', 'Lubuk Pakam', '2009-09-08', 'Jl. Komp. Mangsa Permai Y.3 No. 3, Gunung Sari, Rappocini, Makassar', 'Finky Suma', 'Deby Shintya Fira Nasution', NULL, 'Karyawan Swasta', NULL, NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(252, 20, '0119795525', 'SMA1024', 'Zavier Febriansyah Zanjabila Yahdi', 'SMA', 10, 'L', 'Mangupura', '2011-02-21', 'Perumahan Bukit Pratama Jl. Gong Suling 1 No. 22, Jimbaran', 'Adli Yahdi', 'Sulistari', 'Wiraswasta', 'Karyawan Swasta', '08123619301 / 081338401424', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(253, 20, '0114518204', 'SMA1025', 'Jedha Ananda dana', 'SMA', 10, 'L', 'Denpasar', '2011-01-13', 'Jl.Taman Ambengan Perum.Casa De Bale blok A6', 'Dana Endro Leksono', 'Fransisca utianik', 'Wiraswasta', 'Wiraswasta', '081236183313 / 08123619574', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(254, 20, '0109187667', 'SMA1026', 'Putu Alecia permata Dewi', 'SMA', 10, 'P', 'Denpasar', '2010-10-13', 'Jalan Dharmawangsa,perumahan Nusadua Hill Residen Blok E.9,Benoa-kuta selatan', 'Agus Mawan Kurniawan', 'Ni Made Sri Indrayanti', 'Seaman', 'Karyawan Swasta', '087871406252 / 081717300032', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(255, 20, '0111088389', 'SMA1027', 'Nur Aini', 'SMA', 10, 'P', 'Gresik , Jawa Timur', '2011-03-12', 'Jl Taman Ambengan ,Jimbaran  , kec Kuta Selatan Kab Badung', 'Ahmad Anang Sani', 'Siti Cholidah', 'Wiraswasta', 'IRT', '0895323524218 / 081331544140', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(256, 20, '0103375655', 'SMA1028', 'I Gede Paramahamsa Premananda Gunarsana', 'SMA', 10, 'L', 'Denpasar', '2010-11-23', 'EASTERLAND MANSION JIMBARAN BLOK B NO 8A.', 'I KETUT GUNARSANA PUTRA, ST', 'NIADE YUDIANTARI', 'Karyawan Swasta', 'Karyawan Swasta', '081337374988 / 081337374987', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(257, 20, 'TMP1000029', 'SMA1029', 'Yoel Karsten Xavier Ayden', 'SMA', 10, 'L', 'Denpasar', '2011-09-07', 'JL. GONG SULING IV/3 BKT PRATAMA LINGK. ANGGA Swara', 'YOHANES DIAN SETYO NUGROHO', 'ADININGGAR ASTAINDRA LISTYANINGTHIAS', 'Karyawan Swasta', 'Karyawan Swasta', '+62 858-4782-7452', NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(258, 20, 'TMP1000030', 'SMA1030', 'Ni Kadek Mishael Angelina Putri', 'SMA', 10, 'P', NULL, '1900-01-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-06-21 23:10:21', '2026-06-21 23:10:21', NULL),
(259, 20, '1234567000', '1234569', 'Ahmad Testing', 'SD', 1, 'L', 'Jakarta', '2020-07-02', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-06-23 00:38:29', '2026-06-26 01:55:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(120) NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `elective_class_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `academic_year_id`, `kode`, `nama`, `category_id`, `elective_class_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(41, 20, 'ART_TARI', 'ART (TARI)', 39, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(42, 20, 'TLN_KREASI_TARI_BALI', 'TALENT KREASI TARI BALI', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(43, 20, 'ART_MUSIK_MOD', 'ART (MUSIK) MODERN', 39, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(44, 20, 'TLN_BAND', 'TALENT BAND', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(45, 20, 'TLN_DRUM_BAND', 'TALENT DRUM BAND', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(46, 20, 'ART_MUSIK_TRAD', 'ART (MUSIK) TRADISIONAL', 39, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(47, 20, 'TLN_BALEGANJUR', 'TALENT BALEGANJUR', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(48, 20, 'ART_CRAFT', 'ART (CRAFT)', 39, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(49, 20, 'TLN_MELUKIS', 'TALENT MELUKIS', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(50, 20, 'MEMBACA', 'MEMBACA', 37, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(51, 20, 'MENULIS', 'MENULIS', 37, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(52, 20, 'BHS_BALI_WICARA', 'BHS. BALI (WICARA)', 37, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(53, 20, 'TLN_MEJEJAHITAN', 'TALENT MEJEJAHITAN', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(54, 20, 'BHSBALI_MENULIS', 'BHS.BALI (MENULIS)', 37, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(55, 20, 'TLN_MEKIDUNG', 'TALENT MEKIDUNG', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(56, 20, 'BHS_BALI_WICARA_2', 'BHS BALI WICARA', 37, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(57, 20, 'SPEAKING', 'SPEAKING', 37, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(58, 20, 'TLN_ENGLISH', 'TALENT ENGLISH', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(59, 20, 'READING', 'READING', 37, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(60, 20, 'TLN_MC', 'TALENT MC', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(61, 20, 'WRITING', 'WRITING', 37, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(62, 20, 'TLN', 'TALENT', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(63, 20, 'AGAMA_ISLAM', 'AGAMA ISLAM', 36, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(64, 20, 'TLN_MENGAJI', 'TALENT MENGAJI', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(65, 20, 'PKN', 'PKN', 36, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(66, 20, 'AGAMA_HINDU', 'AGAMA HINDU', 36, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(67, 20, 'AGAMA_KATOLIK', 'AGAMA KATOLIK', 36, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(68, 20, 'FSK', 'FISIKA', 38, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(69, 20, 'PRAMUKA', 'PRAMUKA', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(70, 20, 'BASKET', 'BASKET', 39, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(71, 20, 'TLN_BASKET', 'TALENT BASKET', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(72, 20, 'TLN_VOKAL', 'TALENT VOKAL', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(73, 20, 'TEATER', 'TEATER', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(74, 20, 'JURNALISTIK', 'JURNALISTIK', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(75, 20, 'TLN_COOKING', 'TALENT COOKING', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(76, 20, 'AGAMA_KRISTEN', 'AGAMA KRISTEN', 36, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(77, 20, 'TLN_PASKIB', 'TALENT PASKIB', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(78, 20, 'KOORD_PRAMUKA', 'KOORD PRAMUKA', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(79, 20, 'MATH_APL', 'MATHEMATICS (APPLIED)', 38, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(80, 20, 'MATH_BSC', 'MATHEMATICS (BASIC)', 38, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(81, 20, 'MTK', 'MATEMATIKA', 38, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(82, 20, 'TLN_CROCHET', 'TALENT CROCHET', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(83, 20, 'KMA', 'KIMIA', 38, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(84, 20, 'TLN_BIOKIMIA', 'TALENT BIOKIMIA', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(85, 20, 'LAB', 'LABORAN', 38, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(86, 20, 'SCI_APL', 'SCIENCE (APPLIED)', 38, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(87, 20, 'TLN_ELEKTRO', 'TALENT ELEKTRO', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(88, 20, 'BIO', 'BIOLOGI', 38, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(89, 20, 'SCI_BSC', 'SCIENCE (BASIC)', 38, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(90, 20, 'TLN_BUDI_DAYA', 'TALENT BUDI DAYA', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(91, 20, 'MATH_BSC_2', 'MATH BASIC', 38, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(92, 20, 'TLN_BIOTECH', 'TALENT BIOTECH', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(93, 20, 'MRK', 'MARKETING', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(94, 20, 'TLN_VIDEOGRAFI', 'TALENT VIDEOGRAFI', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(95, 20, 'TLN_GRAFIS', 'TALENT GRAFIS', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(96, 20, 'CODING', 'CODING', 38, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(97, 20, 'TLN_ROBOTIC', 'TALENT ROBOTIC', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(98, 20, 'GRAFIS', 'GRAFIS', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(99, 20, 'TLN_E_SPORT', 'TALENT E-SPORT', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(100, 20, 'TLN_HARDWARE', 'TALENT HARDWARE', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(101, 20, 'BULUTANGKIS', 'BULUTANGKIS', 39, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(102, 20, 'TLN_SILAT', 'TALENT SILAT', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(103, 20, 'RENANG', 'RENANG', 39, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(104, 20, 'TLN_FUTSAL', 'TALENT FUTSAL', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(105, 20, 'GURU_OLGA_TK', 'GURU OLGA TK', 39, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(106, 20, 'TLN_RENANG', 'TALENT RENANG', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(107, 20, 'AST_GURU_OLGA', 'ASISTEN GURU OLGA', 39, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(108, 20, 'AKT', 'AKUNTANSI', 38, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(109, 20, 'IPS', 'IPS', 36, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(110, 20, 'TLN_PHOTOGRAFI', 'TALENT PHOTOGRAFI', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(111, 20, 'TLN_MODELLING', 'TALENT MODELLING', 40, NULL, '2026-06-22 00:55:43', '2026-06-22 01:13:13', NULL),
(183, 20, 'CKC', 'Cricket', 39, NULL, '2026-06-22 02:08:36', '2026-06-22 02:08:36', NULL),
(184, 20, 'TKB', 'CGV', 38, NULL, '2026-06-22 14:49:41', '2026-06-22 14:49:41', NULL),
(185, 20, 'TLN_ART3-4', 'TALENT INTEREST ART 3-4', 36, NULL, '2026-06-22 14:49:41', '2026-06-22 14:49:41', NULL),
(186, 20, 'HHH', 'Sekolah Beta', 38, NULL, '2026-06-22 14:49:41', '2026-06-22 14:49:41', NULL),
(189, 20, 'SHS', 'SBTAA', 38, NULL, '2026-06-22 14:56:52', '2026-06-22 14:56:52', NULL),
(190, 20, 'HIPSTER', 'Hipster', 38, 16, '2026-06-26 03:42:37', '2026-06-26 03:42:37', NULL),
(191, 20, 'HACKER', 'Hacker', 38, 17, '2026-06-26 03:42:38', '2026-06-26 03:42:38', NULL),
(192, 20, 'HUSTLER', 'Hustler', 38, 18, '2026-06-26 03:42:38', '2026-06-26 03:42:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subject_categories`
--

CREATE TABLE `subject_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_categories`
--

INSERT INTO `subject_categories` (`id`, `academic_year_id`, `nama`) VALUES
(39, 20, 'Art and Sport'),
(37, 20, 'Language'),
(36, 20, 'Moral and Social'),
(38, 20, 'Science and Technology'),
(40, 20, 'Talent and Interest');

-- --------------------------------------------------------

--
-- Table structure for table `subject_jenjang_map`
--

CREATE TABLE `subject_jenjang_map` (
  `subject_id` int(10) UNSIGNED NOT NULL,
  `jenjang` enum('TK','SD','SMP','SMA') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_jenjang_map`
--

INSERT INTO `subject_jenjang_map` (`subject_id`, `jenjang`) VALUES
(41, ''),
(41, 'SD'),
(41, 'SMP'),
(41, 'SMA'),
(42, ''),
(42, 'SD'),
(42, 'SMP'),
(42, 'SMA'),
(43, ''),
(43, 'SD'),
(43, 'SMP'),
(43, 'SMA'),
(44, ''),
(44, 'SD'),
(44, 'SMP'),
(44, 'SMA'),
(45, ''),
(45, 'SD'),
(45, 'SMP'),
(45, 'SMA'),
(46, ''),
(46, 'SD'),
(46, 'SMP'),
(46, 'SMA'),
(47, ''),
(47, 'SD'),
(47, 'SMP'),
(47, 'SMA'),
(48, ''),
(48, 'SD'),
(48, 'SMP'),
(48, 'SMA'),
(49, ''),
(49, 'SD'),
(49, 'SMP'),
(49, 'SMA'),
(50, ''),
(50, 'SD'),
(50, 'SMP'),
(50, 'SMA'),
(51, ''),
(51, 'SD'),
(51, 'SMP'),
(51, 'SMA'),
(52, ''),
(52, 'SD'),
(52, 'SMP'),
(52, 'SMA'),
(53, ''),
(53, 'SD'),
(53, 'SMP'),
(53, 'SMA'),
(54, ''),
(54, 'SD'),
(54, 'SMP'),
(54, 'SMA'),
(55, ''),
(55, 'SD'),
(55, 'SMP'),
(55, 'SMA'),
(56, ''),
(56, 'SD'),
(56, 'SMP'),
(56, 'SMA'),
(57, ''),
(57, 'SD'),
(57, 'SMP'),
(57, 'SMA'),
(58, ''),
(58, 'SD'),
(58, 'SMP'),
(58, 'SMA'),
(59, ''),
(59, 'SD'),
(59, 'SMP'),
(59, 'SMA'),
(60, ''),
(60, 'SD'),
(60, 'SMP'),
(60, 'SMA'),
(61, ''),
(61, 'SD'),
(61, 'SMP'),
(61, 'SMA'),
(62, ''),
(62, 'SD'),
(62, 'SMP'),
(62, 'SMA'),
(63, ''),
(63, 'SD'),
(63, 'SMP'),
(63, 'SMA'),
(64, ''),
(64, 'SD'),
(64, 'SMP'),
(64, 'SMA'),
(65, ''),
(65, 'SD'),
(65, 'SMP'),
(65, 'SMA'),
(66, 'SD'),
(66, 'SMP'),
(66, 'SMA'),
(67, ''),
(67, 'SD'),
(67, 'SMP'),
(67, 'SMA'),
(68, ''),
(68, 'SD'),
(68, 'SMP'),
(68, 'SMA'),
(69, ''),
(69, 'SD'),
(69, 'SMP'),
(69, 'SMA'),
(70, ''),
(70, 'SD'),
(70, 'SMP'),
(70, 'SMA'),
(71, ''),
(71, 'SD'),
(71, 'SMP'),
(71, 'SMA'),
(72, ''),
(72, 'SD'),
(72, 'SMP'),
(72, 'SMA'),
(73, ''),
(73, 'SD'),
(73, 'SMP'),
(73, 'SMA'),
(74, ''),
(74, 'SD'),
(74, 'SMP'),
(74, 'SMA'),
(75, ''),
(75, 'SD'),
(75, 'SMP'),
(75, 'SMA'),
(76, ''),
(76, 'SD'),
(76, 'SMP'),
(76, 'SMA'),
(77, ''),
(77, 'SD'),
(77, 'SMP'),
(77, 'SMA'),
(78, ''),
(78, 'SD'),
(78, 'SMP'),
(78, 'SMA'),
(79, ''),
(79, 'SD'),
(79, 'SMP'),
(79, 'SMA'),
(80, ''),
(80, 'SD'),
(80, 'SMP'),
(80, 'SMA'),
(81, ''),
(81, 'SD'),
(81, 'SMP'),
(81, 'SMA'),
(82, ''),
(82, 'SD'),
(82, 'SMP'),
(82, 'SMA'),
(83, ''),
(83, 'SD'),
(83, 'SMP'),
(83, 'SMA'),
(84, ''),
(84, 'SD'),
(84, 'SMP'),
(84, 'SMA'),
(85, ''),
(85, 'SD'),
(85, 'SMP'),
(85, 'SMA'),
(86, ''),
(86, 'SD'),
(86, 'SMP'),
(86, 'SMA'),
(87, ''),
(87, 'SD'),
(87, 'SMP'),
(87, 'SMA'),
(88, ''),
(88, 'SD'),
(88, 'SMP'),
(88, 'SMA'),
(89, ''),
(89, 'SD'),
(89, 'SMP'),
(89, 'SMA'),
(90, ''),
(90, 'SD'),
(90, 'SMP'),
(90, 'SMA'),
(91, ''),
(91, 'SD'),
(91, 'SMP'),
(91, 'SMA'),
(92, ''),
(92, 'SD'),
(92, 'SMP'),
(92, 'SMA'),
(93, ''),
(93, 'SD'),
(93, 'SMP'),
(93, 'SMA'),
(94, ''),
(94, 'SD'),
(94, 'SMP'),
(94, 'SMA'),
(95, ''),
(95, 'SD'),
(95, 'SMP'),
(95, 'SMA'),
(96, ''),
(96, 'SD'),
(96, 'SMP'),
(96, 'SMA'),
(97, ''),
(97, 'SD'),
(97, 'SMP'),
(97, 'SMA'),
(98, ''),
(98, 'SD'),
(98, 'SMP'),
(98, 'SMA'),
(99, ''),
(99, 'SD'),
(99, 'SMP'),
(99, 'SMA'),
(100, ''),
(100, 'SD'),
(100, 'SMP'),
(100, 'SMA'),
(101, ''),
(101, 'SD'),
(101, 'SMP'),
(101, 'SMA'),
(102, ''),
(102, 'SD'),
(102, 'SMP'),
(102, 'SMA'),
(103, ''),
(103, 'SD'),
(103, 'SMP'),
(103, 'SMA'),
(104, ''),
(104, 'SD'),
(104, 'SMP'),
(104, 'SMA'),
(105, ''),
(105, 'SD'),
(105, 'SMP'),
(105, 'SMA'),
(106, ''),
(106, 'SD'),
(106, 'SMP'),
(106, 'SMA'),
(107, ''),
(107, 'SD'),
(107, 'SMP'),
(107, 'SMA'),
(108, ''),
(108, 'SD'),
(108, 'SMP'),
(108, 'SMA'),
(109, ''),
(109, 'SD'),
(109, 'SMP'),
(109, 'SMA'),
(110, ''),
(110, 'SD'),
(110, 'SMP'),
(110, 'SMA'),
(111, ''),
(111, 'SD'),
(111, 'SMP'),
(111, 'SMA'),
(183, 'SD'),
(183, 'SMP'),
(183, 'SMA'),
(184, 'SD'),
(185, 'SD'),
(186, 'SD'),
(189, 'SD'),
(190, 'SD'),
(191, 'SD'),
(192, 'SD');

-- --------------------------------------------------------

--
-- Table structure for table `subject_kkm`
--

CREATE TABLE `subject_kkm` (
  `subject_id` int(10) UNSIGNED NOT NULL,
  `tingkat` tinyint(3) UNSIGNED NOT NULL,
  `kkm` decimal(5,2) NOT NULL DEFAULT 70.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_kkm`
--

INSERT INTO `subject_kkm` (`subject_id`, `tingkat`, `kkm`) VALUES
(66, 1, 80.00),
(66, 2, 80.00),
(66, 3, 80.00),
(66, 4, 80.00),
(66, 5, 80.00),
(66, 6, 80.00),
(66, 7, 80.00),
(66, 8, 80.00),
(66, 9, 80.00),
(66, 10, 80.00),
(66, 11, 80.00),
(66, 12, 80.00),
(190, 1, 70.00),
(190, 2, 70.00),
(190, 3, 70.00),
(190, 4, 70.00),
(190, 5, 70.00),
(190, 6, 70.00),
(191, 1, 70.00),
(191, 2, 70.00),
(191, 3, 70.00),
(191, 4, 70.00),
(191, 5, 70.00),
(191, 6, 70.00),
(192, 1, 70.00),
(192, 2, 70.00),
(192, 3, 70.00),
(192, 4, 70.00),
(192, 5, 70.00),
(192, 6, 70.00);

-- --------------------------------------------------------

--
-- Table structure for table `subject_topics`
--

CREATE TABLE `subject_topics` (
  `id` int(10) UNSIGNED NOT NULL,
  `rombel_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `semester` enum('ganjil','genap') NOT NULL,
  `kode` varchar(20) DEFAULT NULL,
  `judul` varchar(160) NOT NULL,
  `ranah` enum('sikap','pengetahuan','keterampilan') NOT NULL DEFAULT 'pengetahuan',
  `kategori` enum('tugas','ulangan','proyek','praktek','portofolio','produk','lainnya') NOT NULL DEFAULT 'tugas',
  `bobot` decimal(5,2) NOT NULL DEFAULT 1.00,
  `deskripsi` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `ranah_list` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ranah_list`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_topics`
--

INSERT INTO `subject_topics` (`id`, `rombel_id`, `subject_id`, `semester`, `kode`, `judul`, `ranah`, `kategori`, `bobot`, `deskripsi`, `created_by`, `created_at`, `updated_at`, `deleted_at`, `ranah_list`) VALUES
(21, 17, 189, 'ganjil', 'SHS', 'Hacker Wow', 'sikap', 'tugas', 1.00, NULL, 1, '2026-06-22 15:11:15', '2026-06-22 15:11:15', NULL, '[\"sikap\",\"pengetahuan\",\"keterampilan\"]'),
(22, 17, 184, 'ganjil', 'TKB', 'Coding wew', 'sikap', 'tugas', 1.00, NULL, 1, '2026-06-22 15:11:32', '2026-06-22 15:11:32', NULL, '[\"sikap\",\"pengetahuan\",\"keterampilan\"]'),
(23, 17, 189, 'ganjil', 'SHA', 'Hipster top', 'sikap', 'tugas', 1.00, NULL, 1, '2026-06-22 15:12:50', '2026-06-22 15:12:50', NULL, '[\"sikap\",\"pengetahuan\",\"keterampilan\"]'),
(24, 17, 66, 'ganjil', 'T!', 'Bab 1 - Mengenal Yadnya', 'sikap', 'tugas', 1.00, NULL, 1, '2026-06-23 23:07:21', '2026-06-23 23:07:21', NULL, '[\"sikap\",\"pengetahuan\",\"keterampilan\"]'),
(25, 17, 66, 'ganjil', 'T2', 'Bab 2 - Mengenal Buana Agung & Alit', 'sikap', 'tugas', 1.00, NULL, 1, '2026-06-23 23:07:38', '2026-06-23 23:07:38', NULL, '[\"sikap\",\"pengetahuan\",\"keterampilan\"]'),
(26, 17, 192, 'ganjil', 'HST', 'Bab 1 - Pengenalan tentang Branding', 'sikap', 'tugas', 1.00, NULL, 1, '2026-06-26 03:44:30', '2026-06-26 03:44:30', NULL, '[\"sikap\",\"pengetahuan\",\"keterampilan\"]'),
(27, 17, 192, 'ganjil', 'HST', 'Bab 2 - Personal branding', 'sikap', 'tugas', 1.00, NULL, 1, '2026-06-26 03:44:41', '2026-06-26 03:44:41', NULL, '[\"sikap\",\"pengetahuan\",\"keterampilan\"]'),
(28, 17, 191, 'ganjil', 'HCK', 'Pen Test', 'sikap', 'tugas', 1.00, NULL, 37, '2026-06-26 03:55:45', '2026-06-26 03:55:45', NULL, '[\"sikap\",\"pengetahuan\",\"keterampilan\"]'),
(29, 17, 190, 'ganjil', 'HPS', 'Bab 1 - UI Fundamental', 'sikap', 'tugas', 1.00, NULL, 48, '2026-06-26 04:22:53', '2026-06-26 04:22:53', NULL, '[\"sikap\",\"pengetahuan\",\"keterampilan\"]');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `nip`, `phone`, `created_at`) VALUES
(14, 19, '02090082025', NULL, '2026-06-22 00:55:43'),
(15, 20, '01730052024', NULL, '2026-06-22 00:55:43'),
(16, 21, '02140042026', NULL, '2026-06-22 00:55:43'),
(17, 22, '01970062025', NULL, '2026-06-22 00:55:43'),
(18, 23, '01950062025', NULL, '2026-06-22 00:55:43'),
(19, 24, '00840092019', NULL, '2026-06-22 00:55:43'),
(20, 25, '02010062025', NULL, '2026-06-22 00:55:43'),
(21, 26, '00990062020', NULL, '2026-06-22 00:55:43'),
(22, 27, '01650092023', NULL, '2026-06-22 00:55:43'),
(23, 28, '01280062022', NULL, '2026-06-22 00:55:43'),
(24, 29, '01690052024', NULL, '2026-06-22 00:55:43'),
(25, 30, '01140072021', NULL, '2026-06-22 00:55:43'),
(26, 31, '01400022023', NULL, '2026-06-22 00:55:43'),
(27, 32, '01960062025', NULL, '2026-06-22 00:55:43'),
(28, 33, '01430042023', NULL, '2026-06-22 00:55:43'),
(29, 34, '01560052023', NULL, '2026-06-22 00:55:43'),
(30, 35, '00240062013', NULL, '2026-06-22 00:55:43'),
(31, 36, '01070052021', NULL, '2026-06-22 00:55:43'),
(32, 37, '01570062023', NULL, '2026-06-22 00:55:43'),
(33, 38, '00280072014', NULL, '2026-06-22 00:55:43'),
(34, 39, '01920052025', NULL, '2026-06-22 00:55:43'),
(35, 40, '01460052023', NULL, '2026-06-22 00:55:43'),
(36, 41, '01680052024', NULL, '2026-06-22 00:55:43'),
(37, 42, '02050062025', NULL, '2026-06-22 00:55:43'),
(38, 43, '00440072017', NULL, '2026-06-22 00:55:43'),
(39, 44, '00140072010', NULL, '2026-06-22 00:55:43'),
(40, 45, '00220102012', NULL, '2026-06-22 00:55:43'),
(41, 46, '01640092023', NULL, '2026-06-22 00:55:43'),
(42, 47, '01900052025', NULL, '2026-06-22 00:55:43'),
(43, 48, '01910052025', NULL, '2026-06-22 00:55:43'),
(44, 49, '01020012021', NULL, '2026-06-22 00:55:43'),
(45, 50, '01510052023', NULL, '2026-06-22 00:55:43'),
(46, 51, '01940062025', NULL, '2026-06-22 00:55:43'),
(47, 52, '02060072025', NULL, '2026-06-22 00:55:43'),
(48, 53, '02080072025', NULL, '2026-06-22 00:55:43'),
(49, 54, '00700042019', NULL, '2026-06-22 00:55:43'),
(50, 55, '02040062025', NULL, '2026-06-22 00:55:43'),
(51, 56, '02030062025', NULL, '2026-06-22 00:55:43');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_subjects`
--

INSERT INTO `teacher_subjects` (`teacher_id`, `subject_id`) VALUES
(14, 41),
(14, 42),
(15, 43),
(15, 44),
(15, 45),
(16, 46),
(16, 47),
(17, 48),
(17, 49),
(18, 50),
(18, 51),
(19, 52),
(19, 53),
(20, 54),
(20, 55),
(20, 56),
(21, 57),
(21, 58),
(22, 59),
(22, 60),
(23, 57),
(23, 61),
(23, 62),
(24, 63),
(24, 64),
(24, 65),
(25, 66),
(26, 67),
(26, 68),
(26, 69),
(27, 70),
(27, 71),
(27, 72),
(28, 51),
(28, 73),
(29, 51),
(29, 74),
(30, 69),
(30, 75),
(30, 76),
(31, 51),
(31, 65),
(31, 77),
(31, 78),
(32, 79),
(32, 80),
(33, 81),
(33, 82),
(34, 69),
(34, 83),
(34, 84),
(34, 85),
(35, 86),
(35, 87),
(36, 69),
(36, 88),
(36, 89),
(36, 90),
(37, 85),
(37, 89),
(37, 91),
(37, 92),
(38, 90),
(39, 75),
(40, 75),
(41, 93),
(41, 94),
(42, 93),
(42, 95),
(43, 96),
(43, 97),
(44, 96),
(44, 98),
(44, 99),
(44, 100),
(45, 101),
(45, 102),
(46, 103),
(46, 104),
(47, 105),
(47, 106),
(47, 107),
(48, 62),
(48, 69),
(48, 108),
(48, 109),
(49, 93),
(49, 110),
(50, 58),
(51, 111);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_years`
--

CREATE TABLE `teacher_years` (
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_years`
--

INSERT INTO `teacher_years` (`teacher_id`, `academic_year_id`) VALUES
(14, 20),
(15, 20),
(16, 20),
(17, 20),
(18, 20),
(19, 20),
(20, 20),
(21, 20),
(22, 20),
(23, 20),
(24, 20),
(25, 20),
(26, 20),
(27, 20),
(28, 20),
(29, 20),
(30, 20),
(31, 20),
(32, 20),
(33, 20),
(34, 20),
(35, 20),
(36, 20),
(37, 20),
(38, 20),
(39, 20),
(40, 20),
(41, 20),
(42, 20),
(43, 20),
(44, 20),
(45, 20),
(46, 20),
(47, 20),
(48, 20),
(49, 20),
(50, 20),
(51, 20);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `niy` varchar(20) NOT NULL,
  `nama` varchar(120) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `ttd_path` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('administrator','admin','kepsek','guru') NOT NULL,
  `jenjang` enum('TK','SD','SMP','SMA') DEFAULT NULL,
  `is_wali` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_pw` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `niy`, `nama`, `email`, `ttd_path`, `password_hash`, `role`, `jenjang`, `is_wali`, `is_active`, `must_change_pw`, `last_login_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '1990010001', 'Administrator', 'admin@sekolah.id', NULL, '$2y$10$.W0EMeg.g8xrBM88WdasSOuZIdUJuG6kXsOq1nJxM4f4MEPttKYEu', 'administrator', NULL, 0, 1, 1, '2026-06-26 12:24:23', '2026-04-28 22:38:05', '2026-06-26 04:24:23', NULL),
(19, '0209008202', 'Ni Kadek Ary Apriyani', NULL, NULL, '$2y$12$/KMxdLF5jPrI/YSSesLPO.uJv6uPqpGKXo6mB6nDAHh/9NrCMQ2HO', 'guru', NULL, 0, 1, 1, NULL, '2026-06-22 00:55:43', '2026-06-22 02:47:49', NULL),
(20, '01730052024', 'Wismar Sinaga', NULL, NULL, '$2y$12$QkRPm3D/i/n07aHJM8uwMuG9DAGDfwxlo6GHDDfaSh7dYC9KXsyOa', 'guru', NULL, 0, 1, 1, '2026-06-22 05:01:50', '2026-06-22 00:55:43', '2026-06-22 05:01:50', NULL),
(21, '02140042026', 'I MADE GELGEL ASMARA PUTRA, S.Pd', NULL, NULL, '$2y$12$rovSpxaiPmzjSfT9qkafluz/62TGUCPdVqWLQcBcgyUPbLQnC7qG2', 'guru', NULL, 0, 1, 1, '2026-06-22 05:04:41', '2026-06-22 00:55:43', '2026-06-22 05:04:41', NULL),
(22, '01970062025', 'I Putu Gede Putra Adnyana', NULL, NULL, '$2y$12$/G70mEiJbZLPEZbEQhMal.U0JwbzZRv5fPvxRByyje/nn7M8SGZu6', 'guru', NULL, 0, 1, 1, '2026-06-22 05:00:39', '2026-06-22 00:55:43', '2026-06-22 05:00:39', NULL),
(23, '01950062025', 'Ni Komang Cahyani', NULL, NULL, '$2y$12$KT56cR4iPPJyZjiPar7Oz.VG3Yg4/ihe4RBfq3Eo90q4.xyCJTs8C', 'guru', NULL, 0, 1, 1, '2026-06-22 05:00:48', '2026-06-22 00:55:43', '2026-06-22 05:00:48', NULL),
(24, '00840092019', 'Luh Ade Tirta Wahyuning, S.S', NULL, NULL, '$2y$12$JWFz8JQxDSJVCymij3WdHOdL1MexYJW0l2Gymq90ts5eVhg/kyVzK', 'guru', NULL, 0, 1, 1, '2026-06-22 05:04:39', '2026-06-22 00:55:43', '2026-06-22 05:04:39', NULL),
(25, '02010062025', 'LUH JUNITA PRAWITA', NULL, NULL, '$2y$12$ddunq4WkhVJe8Gq9bO.9s.EASF8utxFYCGu91WBX5sMsY4H6DSk42', 'guru', NULL, 0, 1, 1, '2026-06-22 05:00:45', '2026-06-22 00:55:43', '2026-06-22 05:00:45', NULL),
(26, '00990062020', 'R. Amalia Nurfitri, S.Pd', NULL, NULL, '$2y$12$jkb1AkzlM2b0pksJBs77NeDkfGXXQBf7zjwH/VbTPwPokt/NCxuJa', 'guru', NULL, 0, 1, 1, '2026-06-22 05:05:24', '2026-06-22 00:55:43', '2026-06-22 05:05:24', NULL),
(27, '01650092023', 'Komang Ayu Rosmala Dewi', NULL, NULL, '$2y$12$6E4Z8jPP2d0lkZvPO.zQAunx4kXAy5dTk0ZrdYdbJ8zditIoYUgVG', 'guru', NULL, 0, 1, 1, '2026-06-22 05:04:45', '2026-06-22 00:55:43', '2026-06-22 05:04:45', NULL),
(28, '01280062022', 'Ida Ayu Made Sarira Cahya Pertiwi, S.Pd', NULL, NULL, '$2y$12$UUL5VhTjgqwFNGb2Uxu1H.UxF6PBqA9fFgfJVSiysvMnXIy7/h4OK', 'guru', NULL, 0, 1, 1, '2026-06-22 05:05:45', '2026-06-22 00:55:43', '2026-06-22 05:05:45', NULL),
(29, '01690052024', 'Muhammad Syaiful Faiz', NULL, NULL, '$2y$12$zaX2WeosBCsDtbMbK8klUuajBbFZSO9hNU.vSpvZgOmQEOHmpfNdu', 'guru', NULL, 0, 1, 1, NULL, '2026-06-22 00:55:43', '2026-06-22 02:47:49', NULL),
(30, '01140072021', 'Ni Wayan Nita Jayanti, S.PdH', NULL, NULL, '$2y$12$KwVGJH99fEHl/pUeYQ82I.8vdg/kEDBToh6R0mCcXSPThubPRNnV.', 'guru', NULL, 0, 1, 1, '2026-06-22 05:05:11', '2026-06-22 00:55:43', '2026-06-22 05:05:11', NULL),
(31, '01400022023', 'Herlin Suryanti Riang Keladok, S.Pd', NULL, NULL, '$2y$12$AgnhyVJVZ9EvjPdExHmdqe9gQepfd5tHdJ5eikAZJQL8I4IZ4B8T6', 'guru', NULL, 0, 1, 1, '2026-06-22 05:00:21', '2026-06-22 00:55:43', '2026-06-22 05:00:21', NULL),
(32, '01960062025', 'Ryshel A.G.Pontoh', NULL, NULL, '$2y$12$AUNIFqtB/Ucpp/blW8KJsegz8Y614YoMcjFP/KJmfaNUOa1aQPOXO', 'guru', NULL, 0, 1, 1, '2026-06-22 05:03:17', '2026-06-22 00:55:43', '2026-06-22 05:03:17', NULL),
(33, '01430042023', 'Dwi Prastiwi, S.Pd', NULL, 'signatures/ttd_33_7e3c1ca22ece.png', '$2y$12$v7d2sT3/MT/AKWeHz7a4nO9hoXSi1jxWNd/54zLWtiqPCCdlHzfQ6', 'guru', NULL, 0, 1, 1, '2026-06-22 05:00:30', '2026-06-22 00:55:43', '2026-06-22 05:07:50', NULL),
(34, '01560052023', 'Rani Larassati, S.Pd', NULL, NULL, '$2y$12$aCjAvW7JOGeEytT0K1nrnuencp17LN6J6MchmcDGpeb57fhwZMNxG', 'guru', NULL, 0, 1, 1, '2026-06-22 05:00:01', '2026-06-22 00:55:43', '2026-06-22 05:00:01', NULL),
(35, '00240062013', 'Ellysabeth Wiji Witaningsih, S.Th', NULL, NULL, '$2y$12$rzFdAGnyMn.M9UY8tCbPnOqKPVBiEpAsay/oBLYA1s6h2bjc4txl6', 'guru', NULL, 0, 1, 1, '2026-06-22 05:06:53', '2026-06-22 00:55:43', '2026-06-22 05:06:53', NULL),
(36, '01070052021', 'Merlyn Julita Erya Octavianus, S.Pd', NULL, NULL, '$2y$12$851XSqPC3.QHKmhZJTdE.ureeD8NAnp2MsA2lwrwfMDf2fvnKAQP2', 'guru', NULL, 0, 1, 1, '2026-06-22 05:20:17', '2026-06-22 00:55:43', '2026-06-22 05:20:17', NULL),
(37, '01570062023', 'Christo Victory, S.S', NULL, NULL, '$2y$12$kJygg2x9ahl/0w5cqERwjOsMitfVlEWUPVC1CTa91R4/dZLzZXCnW', 'guru', NULL, 1, 1, 1, '2026-06-26 11:54:58', '2026-06-22 00:55:43', '2026-06-26 03:54:58', NULL),
(38, '00280072014', 'M. Arrie Kunilasari Elyna, S.Si', NULL, NULL, '$2y$12$MaItKB175Bvz08KdeUuXjex5ZMKAsxYKMx7n4HNsTXr/pv.iSw.wu', 'guru', NULL, 0, 1, 1, '2026-06-22 05:00:45', '2026-06-22 00:55:43', '2026-06-22 05:00:45', NULL),
(39, '01920052025', 'Amadea Agnes Verina', NULL, NULL, '$2y$12$orXMtM8ivFjxOthP71WgjeeT7/tlTDdOd7.vZWl.y4RK3jPgCf1H2', 'guru', NULL, 1, 1, 1, '2026-06-26 12:23:36', '2026-06-22 00:55:43', '2026-06-26 04:23:36', NULL),
(40, '01460052023', 'Erwin Kurniawan, S.Pd', NULL, NULL, '$2y$12$AY2arXpR74FGIv4x3yIwsux/wVavBn.oLRpSXiGAyFagwtIWNyslC', 'guru', NULL, 0, 1, 1, '2026-06-22 05:55:09', '2026-06-22 00:55:43', '2026-06-22 05:55:09', NULL),
(41, '01680052024', 'Devita Wulandari', NULL, NULL, '$2y$12$AOsSxk8ezq6bg1MQd6EV9.mHkOU0TDk4p7CGFcZVa0RGoGYVHC4Q6', 'guru', NULL, 1, 1, 1, '2026-06-22 05:00:03', '2026-06-22 00:55:43', '2026-06-22 05:00:03', NULL),
(42, '02050062025', 'Firdaus Eka Ngenca Sinuraya', NULL, NULL, '$2y$12$K/BJ.CFPV1QOE0pcFULVEexUqmFp43n3ZkPqJPw7RjR1n0U7eKmb.', 'guru', NULL, 0, 1, 1, '2026-06-22 05:00:18', '2026-06-22 00:55:43', '2026-06-22 05:00:18', NULL),
(43, '00440072017', 'Elsia Linawati, S.Tp', NULL, NULL, '$2y$12$BlNaz54KAQsE93/QfYsB4.VP5hc9gu5rhFvrU5Rlw2mMYn1fzVAUK', 'kepsek', 'SMP', 0, 1, 1, '2026-06-22 12:14:03', '2026-06-22 00:55:43', '2026-06-22 12:14:03', NULL),
(44, '00140072010', 'Yuniadi Dwi Utami, S.Si', NULL, NULL, '$2y$12$0qT/Ijl4xou3s.0AE4pR8OK4Jspu38vn/RLvNbyMStTYfdCkgDQdi', 'guru', NULL, 0, 1, 1, NULL, '2026-06-22 00:55:43', '2026-06-22 02:47:49', NULL),
(45, '00220102012', 'Wiwik Rahayu, S.Pd.', NULL, NULL, '$2y$12$knFihRJjgR1zcZj2F9afquSAGbb6G4moF90iqJ8fAkfi0wBDP3h5i', 'kepsek', 'SD', 0, 1, 1, '2026-06-26 09:53:15', '2026-06-22 00:55:43', '2026-06-26 01:53:15', NULL),
(46, '01640092023', 'I Putu Aga Darma Winanda, S.Kom, M.M', NULL, NULL, '$2y$12$47r9kk5pqnObaEEY1hFYmO3nq95yvZst8l2rT2eMhGWKGpS6w3VKW', 'guru', NULL, 0, 1, 1, '2026-06-22 05:03:02', '2026-06-22 00:55:43', '2026-06-22 05:03:02', NULL),
(47, '01900052025', 'I Komang Jupri Artha', NULL, NULL, '$2y$12$1RZrYonjyjj9teIkcGr.mOgQgjFURPPWy3sDS.JQeHs98WkcCazra', 'guru', NULL, 0, 1, 1, NULL, '2026-06-22 00:55:43', '2026-06-22 02:47:49', NULL),
(48, '01910052025', 'Dani Chandra', NULL, NULL, '$2y$12$xhgp5xjBXMHSnDeYteZgjuS5XvqAvWPZvEQ0AD1xP4vQA12yRKfhq', 'guru', NULL, 1, 1, 1, '2026-06-26 12:22:24', '2026-06-22 00:55:43', '2026-06-26 04:22:24', NULL),
(49, '01020012021', 'I Gst Ngr Nyoman Gde Suadnyana, S.Pd', NULL, NULL, '$2y$12$jmOMrA8lDkguHAbhbIwG4uDEj5XRlMeL6/mP/o.4jvJqvNZKk6UwO', 'guru', NULL, 0, 1, 1, '2026-06-22 05:00:27', '2026-06-22 00:55:43', '2026-06-22 05:00:27', NULL),
(50, '01510052023', 'Esan Teopilus Ginting, S.Pd', NULL, NULL, '$2y$12$dn5pIcxO8zCQ26l4dP3Jm..H/pHNkZz/3VPvl9gwLrxyD681qiNmW', 'guru', NULL, 0, 1, 1, '2026-06-22 05:02:56', '2026-06-22 00:55:43', '2026-06-22 05:02:56', NULL),
(51, '01940062025', 'Ekin Dio Gokyansen Tarigan', NULL, NULL, '$2y$12$a.greCWWtnRuMT7cVA3Fguen9eI1FcG4RC.TCcLDt.0fBvUyPzJ/y', 'guru', NULL, 0, 1, 1, '2026-06-22 05:02:11', '2026-06-22 00:55:43', '2026-06-22 05:02:11', NULL),
(52, '02060072025', 'Hani Elinta Br Simatupang', NULL, NULL, '$2y$12$Ww1bOYH3BbIorChgytx59eW30/nIpjp8Xu.vA.zxe9s9sbmkcMsvq', 'guru', NULL, 0, 1, 1, '2026-06-22 05:01:28', '2026-06-22 00:55:43', '2026-06-22 05:01:28', NULL),
(53, '02080072025', 'Episman Gea', NULL, NULL, '$2y$12$HT7pKOfAl5uQ2mQ1tKLjUeVBuEFPAcHJJ./Cb0VmCiBMV9TJj79iq', 'guru', NULL, 0, 1, 1, '2026-06-22 05:03:03', '2026-06-22 00:55:43', '2026-06-22 05:03:03', NULL),
(54, '00700042019', 'Ketut Febriana Rahayu, S.S', NULL, NULL, '$2y$12$ewBjb0efYvl9k5iDJXxUHOHsZSWdRddp5JeWpPchyr9ZLi/k6T7oW', 'guru', NULL, 0, 1, 1, NULL, '2026-06-22 00:55:43', '2026-06-22 02:47:49', NULL),
(55, '02040062025', 'Meri Rajagukguk', NULL, NULL, '$2y$12$0JGtvyO2EXwVSdw2iKTdOOddKlGMsF7mI5xgPe0TTXPUWUYfVSrI6', 'guru', NULL, 0, 1, 1, NULL, '2026-06-22 00:55:43', '2026-06-22 02:47:49', NULL),
(56, '02030062025', 'Nadia Nuran Dani', NULL, NULL, '$2y$12$ETZPqTfGogbo9s6l23q2EedItmCRAaeOvDlWvIy61EypjTj5bmoSy', 'guru', NULL, 0, 1, 1, NULL, '2026-06-22 00:55:43', '2026-06-22 02:47:49', NULL),
(95, '1990010100', 'Admin', 'admin@bimaschool.id', NULL, '$2y$12$HF9nhWBXM7IWGlzthoG3oOs6XXMJPkUHlAYqNLStmsC2LwnmTeZku', 'admin', NULL, 0, 1, 1, '2026-06-22 11:59:20', '2026-06-22 02:00:55', '2026-06-22 11:59:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_remember_tokens`
--

CREATE TABLE `user_remember_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `selector` char(32) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_remember_tokens`
--

INSERT INTO `user_remember_tokens` (`id`, `user_id`, `selector`, `validator_hash`, `expires_at`, `created_at`) VALUES
(1, 95, 'f57492f20dab9a6211bd78851d2a5b74', 'e2ef76f14e79bff53c87738dc6da453e22e118db75e2088232b6c73737901c1e', '2026-07-22 09:51:18', '2026-06-22 02:51:18'),
(2, 49, '9c06fb9d75842648c5808f321720deca', 'dc5ddb5aa77a0e08c7c406a0a4014c1c390db9ba3fb0f59e55000a4febb53864', '2026-07-22 12:00:27', '2026-06-22 05:00:27'),
(3, 33, '367cc912bdb8cad2f4f807447821d2f3', '7260a67f702b5ecffc8d3f4dc8f75c79094af1758e522036d7144617a18ce014', '2026-07-22 12:00:30', '2026-06-22 05:00:30'),
(4, 22, '8702777205a72efa06500cf23d015d4b', 'f47cc73796ed1d3990714296a55f9cd9cedd4f494f250d93a4adc97967158038', '2026-07-22 12:00:39', '2026-06-22 05:00:39'),
(5, 52, 'c8e0fcb6c9c2a3390950a856969f8b98', '811973fb8918d50ad209dcf2e9dcd52eef6f7e28f74f2f1fff43623d217094f9', '2026-07-22 12:01:28', '2026-06-22 05:01:28'),
(6, 51, 'f35e51514c6cd6bdf0cf5b1b15bb08d6', 'a2ff730581df52295c7bc4dafaa2e48adb82e2c95eabef0cc89784db9e76081c', '2026-07-22 12:02:11', '2026-06-22 05:02:11'),
(7, 48, '1752fc62302f67a13475ac714cdfa9c8', 'f291d81139bd910d01478c0974721456fb33a78329aa779aa8351fcd7b6ce9b4', '2026-07-22 12:03:43', '2026-06-22 05:03:43'),
(8, 48, '94c45255d8af68b4015eaf291c94dcb4', '90723af398c9ddb1f7bc8c8d26e2f679112650b6faea73bcbbf1885726049b26', '2026-07-22 12:04:07', '2026-06-22 05:04:07'),
(9, 24, 'a7dff92c91487224cf163db31ea24c6a', 'f6b6425d13b46509b2de4a8b5563c470dc57ee20d3081c08146e92d0ece1bbc8', '2026-07-22 12:04:39', '2026-06-22 05:04:39'),
(11, 48, '4d27e2b0a59cd38f44f16405ec37d868', '2fa77f89c579ad05d51405ab888d929a98c5d19b0afef8dc06d2ae190f133baf', '2026-07-22 12:04:52', '2026-06-22 05:04:52'),
(13, 26, '73d307dfaf92aef7f1008ae6d8dd936e', 'd37ec95ae950f22e5ec0f7200ea41fc49b36bd5301d6dde9a903cf7e99700dbb', '2026-07-22 12:05:24', '2026-06-22 05:05:24'),
(14, 37, '4415dbad46e47f1560b92deabb2f1f62', '6b83c1f01b7eebc0d15faffc01350c7ca5b327a87e1ea203dfc51eec26c05cf0', '2026-07-22 12:05:26', '2026-06-22 05:05:26'),
(15, 28, 'e648246b5d1489f85481c34fd8b688cc', 'e0ba7823c7b9d97f78bf5b38610457a0d81ed55745afe4e316ac4d7cc024606a', '2026-07-22 12:05:45', '2026-06-22 05:05:45'),
(16, 48, 'e3503d41d061f1fa76843a877adb10ba', '248574ca229c5d6a05a3ba6e6f1ea9d853264c68ab0887af8d40220c5d50d93b', '2026-07-22 12:05:49', '2026-06-22 05:05:49'),
(17, 48, '350a47c1eaee55bb7ffa3022fa2cbe02', '02d8930ad8c0ede9242e6b2b696749037ce4ec882df227dd8f4c998bbafc1f1f', '2026-07-22 12:06:47', '2026-06-22 05:06:47'),
(18, 48, '8aeaff347ef803bb759b7dfcbf86845f', '8d8208f41e706a9679ed54a894eefcbf5aa4f4496d6e538d43b3525dcbba5529', '2026-07-22 12:06:52', '2026-06-22 05:06:52'),
(19, 35, 'd312d1f272aa0a61453df0a2d1707c12', 'e6525a470e5fe11e3277ef94ae7839d90bed9ab8190ace0e3a61c94c11400e81', '2026-07-22 12:06:53', '2026-06-22 05:06:53'),
(20, 36, 'ccbef2636c1c74a56d671b16f868b8f7', '18eaaca8f0692a3afe5e793108f1e7a2e50217786e81571c331a76312a8a1a57', '2026-07-22 12:20:17', '2026-06-22 05:20:17'),
(21, 43, '24888388c459b9c674df065c043df2b3', '69ac04b616a20ec35b76b776d3a057e001b4fc6aaf04106b2713029719337f97', '2026-07-22 12:30:12', '2026-06-22 05:30:12'),
(22, 95, 'f2d908307f9630ba3130bf8d502f9d14', '8a2e586c305e3361e70b047e11c03e3b1ccabfb9f4b54e85447721deb5b0d73d', '2026-07-22 18:59:20', '2026-06-22 11:59:20');

-- --------------------------------------------------------

--
-- Table structure for table `wali_notes`
--

CREATE TABLE `wali_notes` (
  `id` int(10) UNSIGNED NOT NULL,
  `rombel_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `semester` enum('ganjil','genap') NOT NULL,
  `period_kind` enum('PTS','PAS') NOT NULL,
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_year` (`label`);

--
-- Indexes for table `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ach_s` (`student_id`),
  ADD KEY `fk_ach_y` (`academic_year_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_att` (`rombel_id`,`student_id`,`tanggal`),
  ADD KEY `fk_att_s` (`student_id`),
  ADD KEY `fk_att_u` (`recorded_by`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_audit_when` (`created_at`),
  ADD KEY `ix_audit_user` (`user_id`);

--
-- Indexes for table `character_aspects`
--
ALTER TABLE `character_aspects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_aspect_year_nama` (`academic_year_id`,`nama`),
  ADD KEY `ix_aspect_year` (`academic_year_id`);

--
-- Indexes for table `character_evaluations`
--
ALTER TABLE `character_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ce` (`rombel_id`,`student_id`,`aspect_id`,`semester`,`period_kind`),
  ADD KEY `fk_ce_s` (`student_id`),
  ADD KEY `fk_ce_a` (`aspect_id`);

--
-- Indexes for table `electives`
--
ALTER TABLE `electives`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_elective` (`academic_year_id`,`jenjang`,`kode`),
  ADD KEY `ix_elective_year` (`academic_year_id`),
  ADD KEY `ix_elective_category` (`category_id`),
  ADD KEY `ix_elective_subject` (`subject_id`);

--
-- Indexes for table `elective_assignments`
--
ALTER TABLE `elective_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ea_stud_sem` (`elective_id`,`student_id`,`semester`),
  ADD KEY `ix_ea_class_sem` (`elective_class_id`,`semester`),
  ADD KEY `fk_ea_s` (`student_id`);

--
-- Indexes for table `elective_classes`
--
ALTER TABLE `elective_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_ec_e` (`elective_id`),
  ADD KEY `fk_ec_t` (`teacher_id`),
  ADD KEY `fk_elective_classes_subject` (`subject_id`);

--
-- Indexes for table `elective_rombels`
--
ALTER TABLE `elective_rombels`
  ADD PRIMARY KEY (`elective_id`,`rombel_id`),
  ADD KEY `ix_er_r` (`rombel_id`);

--
-- Indexes for table `extracurriculars`
--
ALTER TABLE `extracurriculars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_ex_year` (`academic_year_id`);

--
-- Indexes for table `extracurricular_grades`
--
ALTER TABLE `extracurricular_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_eg` (`extracurricular_id`,`student_id`,`semester`,`academic_year_id`),
  ADD KEY `fk_eg_s` (`student_id`),
  ADD KEY `fk_eg_y` (`academic_year_id`);

--
-- Indexes for table `final_grades`
--
ALTER TABLE `final_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_final` (`rombel_id`,`subject_id`,`student_id`,`semester`,`period_kind`),
  ADD KEY `fk_fg_s` (`subject_id`),
  ADD KEY `fk_fg_st` (`student_id`),
  ADD KEY `fk_fg_u` (`reviewed_by`),
  ADD KEY `fk_fg_submitted_by` (`submitted_by`);

--
-- Indexes for table `general_evaluations`
--
ALTER TABLE `general_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ge` (`rombel_id`,`student_id`,`semester`,`period_kind`),
  ADD KEY `fk_ge_s` (`student_id`);

--
-- Indexes for table `grades_daily`
--
ALTER TABLE `grades_daily`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_gd_search` (`rombel_id`,`subject_id`,`semester`,`period_bucket`),
  ADD KEY `fk_gd_s` (`subject_id`),
  ADD KEY `fk_gd_st` (`student_id`),
  ADD KEY `fk_gd_t` (`topic_id`),
  ADD KEY `fk_gd_u` (`recorded_by`);

--
-- Indexes for table `grade_descriptions`
--
ALTER TABLE `grade_descriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_gdesc` (`rombel_id`,`subject_id`,`student_id`,`semester`,`period_bucket`,`ranah`),
  ADD KEY `fk_gdesc_s` (`subject_id`),
  ADD KEY `fk_gdesc_st` (`student_id`);

--
-- Indexes for table `kkm_settings`
--
ALTER TABLE `kkm_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kkm_year_grade` (`academic_year_id`,`jenjang`,`grade`),
  ADD KEY `ix_kkm_jenjang` (`jenjang`),
  ADD KEY `ix_kkm_year` (`academic_year_id`);

--
-- Indexes for table `parents_auth`
--
ALTER TABLE `parents_auth`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pa_student` (`student_id`);

--
-- Indexes for table `parent_remember_tokens`
--
ALTER TABLE `parent_remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_psel` (`selector`),
  ADD KEY `ix_prt_parent` (`parent_auth_id`);

--
-- Indexes for table `report_signatures`
--
ALTER TABLE `report_signatures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sig_year` (`academic_year_id`,`jenjang`,`slot`);

--
-- Indexes for table `report_templates`
--
ALTER TABLE `report_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tpl_year_jenjang` (`academic_year_id`,`jenjang`);

--
-- Indexes for table `rombel`
--
ALTER TABLE `rombel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rombel` (`academic_year_id`,`jenjang`,`tingkat`,`nama`,`deleted_at`),
  ADD KEY `ix_rombel_year` (`academic_year_id`),
  ADD KEY `ix_rombel_wali` (`wali_id`);

--
-- Indexes for table `rombel_members`
--
ALTER TABLE `rombel_members`
  ADD PRIMARY KEY (`rombel_id`,`student_id`),
  ADD KEY `ix_rm_student` (`student_id`);

--
-- Indexes for table `rombel_subject_teachers`
--
ALTER TABLE `rombel_subject_teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rst` (`rombel_id`,`subject_id`,`semester`),
  ADD KEY `ix_rst_teacher` (`teacher_id`),
  ADD KEY `fk_rst_s` (`subject_id`);

--
-- Indexes for table `school_profile`
--
ALTER TABLE `school_profile`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `semesters_state`
--
ALTER TABLE `semesters_state`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_state` (`academic_year_id`,`semester`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_students_year_nisn` (`academic_year_id`,`nisn`),
  ADD UNIQUE KEY `uq_students_year_nis` (`academic_year_id`,`nis`),
  ADD KEY `ix_students_jt` (`jenjang`,`tingkat`),
  ADD KEY `ix_students_year` (`academic_year_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_subject_year_kode` (`academic_year_id`,`kode`),
  ADD KEY `fk_subj_cat` (`category_id`),
  ADD KEY `fk_subjects_elective_class` (`elective_class_id`);

--
-- Indexes for table `subject_categories`
--
ALTER TABLE `subject_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cat_year_nama` (`academic_year_id`,`nama`);

--
-- Indexes for table `subject_jenjang_map`
--
ALTER TABLE `subject_jenjang_map`
  ADD PRIMARY KEY (`subject_id`,`jenjang`);

--
-- Indexes for table `subject_kkm`
--
ALTER TABLE `subject_kkm`
  ADD PRIMARY KEY (`subject_id`,`tingkat`);

--
-- Indexes for table `subject_topics`
--
ALTER TABLE `subject_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_st_rss` (`rombel_id`,`subject_id`,`semester`),
  ADD KEY `fk_st_s` (`subject_id`),
  ADD KEY `fk_st_u` (`created_by`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_teacher_user` (`user_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`teacher_id`,`subject_id`),
  ADD KEY `fk_ts_s` (`subject_id`);

--
-- Indexes for table `teacher_years`
--
ALTER TABLE `teacher_years`
  ADD PRIMARY KEY (`teacher_id`,`academic_year_id`),
  ADD KEY `fk_ty_y` (`academic_year_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_niy` (`niy`),
  ADD KEY `ix_users_role` (`role`),
  ADD KEY `ix_users_jenjang` (`jenjang`);

--
-- Indexes for table `user_remember_tokens`
--
ALTER TABLE `user_remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_selector` (`selector`),
  ADD KEY `ix_user` (`user_id`);

--
-- Indexes for table `wali_notes`
--
ALTER TABLE `wali_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wn` (`rombel_id`,`student_id`,`semester`,`period_kind`),
  ADD KEY `fk_wn_s` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=256;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=905;

--
-- AUTO_INCREMENT for table `character_aspects`
--
ALTER TABLE `character_aspects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `character_evaluations`
--
ALTER TABLE `character_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

--
-- AUTO_INCREMENT for table `electives`
--
ALTER TABLE `electives`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `elective_assignments`
--
ALTER TABLE `elective_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `elective_classes`
--
ALTER TABLE `elective_classes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `extracurriculars`
--
ALTER TABLE `extracurriculars`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `extracurricular_grades`
--
ALTER TABLE `extracurricular_grades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `final_grades`
--
ALTER TABLE `final_grades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `general_evaluations`
--
ALTER TABLE `general_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `grades_daily`
--
ALTER TABLE `grades_daily`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT for table `grade_descriptions`
--
ALTER TABLE `grade_descriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kkm_settings`
--
ALTER TABLE `kkm_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `parents_auth`
--
ALTER TABLE `parents_auth`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `parent_remember_tokens`
--
ALTER TABLE `parent_remember_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_signatures`
--
ALTER TABLE `report_signatures`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `report_templates`
--
ALTER TABLE `report_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `rombel`
--
ALTER TABLE `rombel`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `rombel_subject_teachers`
--
ALTER TABLE `rombel_subject_teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `semesters_state`
--
ALTER TABLE `semesters_state`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=260;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=801;

--
-- AUTO_INCREMENT for table `subject_categories`
--
ALTER TABLE `subject_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `subject_topics`
--
ALTER TABLE `subject_topics`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `user_remember_tokens`
--
ALTER TABLE `user_remember_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `wali_notes`
--
ALTER TABLE `wali_notes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `achievements`
--
ALTER TABLE `achievements`
  ADD CONSTRAINT `fk_ach_s` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ach_y` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_att_r` FOREIGN KEY (`rombel_id`) REFERENCES `rombel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_att_s` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_att_u` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `character_aspects`
--
ALTER TABLE `character_aspects`
  ADD CONSTRAINT `fk_aspect_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `character_evaluations`
--
ALTER TABLE `character_evaluations`
  ADD CONSTRAINT `fk_ce_a` FOREIGN KEY (`aspect_id`) REFERENCES `character_aspects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ce_r` FOREIGN KEY (`rombel_id`) REFERENCES `rombel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ce_s` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `electives`
--
ALTER TABLE `electives`
  ADD CONSTRAINT `fk_el_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_elective_category` FOREIGN KEY (`category_id`) REFERENCES `subject_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_elective_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `elective_assignments`
--
ALTER TABLE `elective_assignments`
  ADD CONSTRAINT `fk_ea_c` FOREIGN KEY (`elective_class_id`) REFERENCES `elective_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ea_e` FOREIGN KEY (`elective_id`) REFERENCES `electives` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ea_s` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `elective_classes`
--
ALTER TABLE `elective_classes`
  ADD CONSTRAINT `fk_ec_e` FOREIGN KEY (`elective_id`) REFERENCES `electives` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ec_t` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_elective_classes_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `elective_rombels`
--
ALTER TABLE `elective_rombels`
  ADD CONSTRAINT `fk_er_e` FOREIGN KEY (`elective_id`) REFERENCES `electives` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_er_r` FOREIGN KEY (`rombel_id`) REFERENCES `rombel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `extracurriculars`
--
ALTER TABLE `extracurriculars`
  ADD CONSTRAINT `fk_ex_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `extracurricular_grades`
--
ALTER TABLE `extracurricular_grades`
  ADD CONSTRAINT `fk_eg_e` FOREIGN KEY (`extracurricular_id`) REFERENCES `extracurriculars` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eg_s` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eg_y` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `final_grades`
--
ALTER TABLE `final_grades`
  ADD CONSTRAINT `fk_fg_r` FOREIGN KEY (`rombel_id`) REFERENCES `rombel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fg_s` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fg_st` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fg_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_fg_u` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `general_evaluations`
--
ALTER TABLE `general_evaluations`
  ADD CONSTRAINT `fk_ge_r` FOREIGN KEY (`rombel_id`) REFERENCES `rombel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ge_s` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grades_daily`
--
ALTER TABLE `grades_daily`
  ADD CONSTRAINT `fk_gd_r` FOREIGN KEY (`rombel_id`) REFERENCES `rombel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gd_s` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gd_st` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gd_t` FOREIGN KEY (`topic_id`) REFERENCES `subject_topics` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_gd_u` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `grade_descriptions`
--
ALTER TABLE `grade_descriptions`
  ADD CONSTRAINT `fk_gdesc_r` FOREIGN KEY (`rombel_id`) REFERENCES `rombel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gdesc_s` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gdesc_st` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kkm_settings`
--
ALTER TABLE `kkm_settings`
  ADD CONSTRAINT `fk_kkm_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parents_auth`
--
ALTER TABLE `parents_auth`
  ADD CONSTRAINT `fk_pa_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parent_remember_tokens`
--
ALTER TABLE `parent_remember_tokens`
  ADD CONSTRAINT `fk_prt_pa` FOREIGN KEY (`parent_auth_id`) REFERENCES `parents_auth` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_signatures`
--
ALTER TABLE `report_signatures`
  ADD CONSTRAINT `fk_sig_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_templates`
--
ALTER TABLE `report_templates`
  ADD CONSTRAINT `fk_tpl_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rombel`
--
ALTER TABLE `rombel`
  ADD CONSTRAINT `fk_rombel_wali` FOREIGN KEY (`wali_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rombel_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rombel_members`
--
ALTER TABLE `rombel_members`
  ADD CONSTRAINT `fk_rm_rombel` FOREIGN KEY (`rombel_id`) REFERENCES `rombel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rm_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rombel_subject_teachers`
--
ALTER TABLE `rombel_subject_teachers`
  ADD CONSTRAINT `fk_rst_r` FOREIGN KEY (`rombel_id`) REFERENCES `rombel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rst_s` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rst_t` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `semesters_state`
--
ALTER TABLE `semesters_state`
  ADD CONSTRAINT `fk_ss_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subj_cat` FOREIGN KEY (`category_id`) REFERENCES `subject_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_subj_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_subjects_elective_class` FOREIGN KEY (`elective_class_id`) REFERENCES `elective_classes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `subject_categories`
--
ALTER TABLE `subject_categories`
  ADD CONSTRAINT `fk_cat_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_jenjang_map`
--
ALTER TABLE `subject_jenjang_map`
  ADD CONSTRAINT `fk_sjm_subj` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_kkm`
--
ALTER TABLE `subject_kkm`
  ADD CONSTRAINT `fk_subject_kkm_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_topics`
--
ALTER TABLE `subject_topics`
  ADD CONSTRAINT `fk_st_r` FOREIGN KEY (`rombel_id`) REFERENCES `rombel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_st_s` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_st_u` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `fk_ts_s` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ts_t` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_years`
--
ALTER TABLE `teacher_years`
  ADD CONSTRAINT `fk_ty_t` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ty_y` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_remember_tokens`
--
ALTER TABLE `user_remember_tokens`
  ADD CONSTRAINT `fk_urt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wali_notes`
--
ALTER TABLE `wali_notes`
  ADD CONSTRAINT `fk_wn_r` FOREIGN KEY (`rombel_id`) REFERENCES `rombel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wn_s` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
