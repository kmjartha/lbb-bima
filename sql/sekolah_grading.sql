-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 20, 2026 at 11:09 AM
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
(1, '2025/2026', 0, '2026-04-28 22:38:05'),
(2, '2026/2027', 0, '2026-06-20 03:55:55'),
(9, '2031/2032', 1, '2026-06-20 05:19:22'),
(10, '2027/2028', 0, '2026-06-20 06:25:16'),
(11, '2028/2029', 0, '2026-06-20 06:36:31'),
(12, '2029/2030', 0, '2026-06-20 06:40:35');

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
(1, 1, 1, '2026-04-29', 'H', NULL, 1, '2026-04-29 01:22:46'),
(2, 1, 2, '2026-04-29', 'I', NULL, 1, '2026-04-29 01:22:46'),
(3, 1, 1, '2026-04-30', 'H', NULL, 1, '2026-04-29 01:23:18'),
(4, 1, 2, '2026-04-30', 'H', NULL, 1, '2026-04-29 01:23:18'),
(5, 1, 1, '2026-05-28', 'I', NULL, 1, '2026-04-29 01:23:30'),
(6, 1, 2, '2026-05-28', 'A', NULL, 1, '2026-04-29 01:23:30'),
(7, 1, 1, '2026-05-27', 'H', NULL, 1, '2026-04-29 01:23:42'),
(8, 1, 2, '2026-05-27', 'H', NULL, 1, '2026-04-29 01:23:42'),
(9, 1, 1, '2026-04-28', 'H', NULL, 1, '2026-04-29 01:24:06'),
(10, 1, 2, '2026-04-28', 'H', NULL, 1, '2026-04-29 01:24:06'),
(11, 1, 1, '2026-04-27', 'A', NULL, 1, '2026-04-29 01:24:26'),
(12, 1, 2, '2026-04-27', 'S', NULL, 1, '2026-04-29 01:24:26'),
(13, 3, 1, '2026-04-29', 'H', NULL, 1, '2026-04-29 01:38:30'),
(14, 3, 2, '2026-04-29', 'I', NULL, 1, '2026-04-29 01:38:30'),
(15, 3, 1, '2026-04-28', 'A', NULL, 1, '2026-04-29 01:38:38'),
(16, 3, 2, '2026-04-28', 'H', NULL, 1, '2026-04-29 01:38:38'),
(17, 3, 1, '2026-04-27', 'H', NULL, 1, '2026-04-29 01:38:43'),
(18, 3, 2, '2026-04-27', 'H', NULL, 1, '2026-04-29 01:38:43'),
(21, 2, 13, '2026-04-29', 'I', NULL, 1, '2026-04-29 08:37:07'),
(22, 2, 9, '2026-04-29', 'H', NULL, 1, '2026-04-29 08:37:07'),
(23, 2, 11, '2026-04-29', 'I', NULL, 1, '2026-04-29 08:37:07'),
(24, 2, 13, '2026-04-28', 'S', NULL, 1, '2026-04-29 08:37:20'),
(25, 2, 9, '2026-04-28', 'H', NULL, 1, '2026-04-29 08:37:20'),
(26, 2, 11, '2026-04-28', 'I', NULL, 1, '2026-04-29 08:37:20'),
(27, 2, 13, '2026-04-27', 'H', NULL, 1, '2026-04-29 08:37:27'),
(28, 2, 9, '2026-04-27', 'H', NULL, 1, '2026-04-29 08:37:27'),
(29, 2, 11, '2026-04-27', 'H', NULL, 1, '2026-04-29 08:37:27'),
(30, 2, 13, '2026-04-26', 'H', NULL, 1, '2026-04-29 08:37:32'),
(31, 2, 9, '2026-04-26', 'H', NULL, 1, '2026-04-29 08:37:32'),
(32, 2, 11, '2026-04-26', 'H', NULL, 1, '2026-04-29 08:37:32'),
(33, 2, 13, '2026-04-25', 'H', NULL, 1, '2026-04-29 08:37:38'),
(34, 2, 9, '2026-04-25', 'H', NULL, 1, '2026-04-29 08:37:38'),
(35, 2, 11, '2026-04-25', 'H', NULL, 1, '2026-04-29 08:37:38'),
(36, 3, 1, '2026-04-30', 'H', NULL, 1, '2026-04-30 01:26:46'),
(37, 3, 2, '2026-04-30', 'I', NULL, 1, '2026-04-30 01:26:46'),
(38, 2, 9, '2026-04-30', 'H', NULL, 1, '2026-04-30 01:26:51'),
(39, 2, 11, '2026-04-30', 'A', NULL, 1, '2026-04-30 01:26:51');

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
(1, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-04-28 22:41:15'),
(2, 1, 'Administrator', 'update', 'school_profile', NULL, '::1', '2026-04-29 00:47:12'),
(3, 1, 'Administrator', 'save', 'teacher:3', NULL, '::1', '2026-04-29 00:49:05'),
(4, 1, 'Administrator', 'save', 'user:9', NULL, '::1', '2026-04-29 00:56:40'),
(5, 1, 'Administrator', 'save', 'teacher:5', NULL, '::1', '2026-04-29 00:58:10'),
(6, 1, 'Administrator', 'save', 'rombel:2', NULL, '::1', '2026-04-29 00:58:34'),
(7, 1, 'Administrator', 'assign_teacher', 'rombel:2/subject:7', '{\"t\":5,\"sem\":null}', '::1', '2026-04-29 01:21:37'),
(8, 1, 'Administrator', 'save', 'topic:1', NULL, '::1', '2026-04-29 01:22:19'),
(9, 1, 'Administrator', 'save_attendance', 'rombel:1', '{\"date\":\"2026-04-29\",\"n\":2}', '::1', '2026-04-29 01:22:46'),
(10, 1, 'Administrator', 'save_attendance', 'rombel:1', '{\"date\":\"2026-04-30\",\"n\":2}', '::1', '2026-04-29 01:23:18'),
(11, 1, 'Administrator', 'save_attendance', 'rombel:1', '{\"date\":\"2026-05-28\",\"n\":2}', '::1', '2026-04-29 01:23:30'),
(12, 1, 'Administrator', 'save_attendance', 'rombel:1', '{\"date\":\"2026-05-27\",\"n\":2}', '::1', '2026-04-29 01:23:42'),
(13, 1, 'Administrator', 'save_attendance', 'rombel:1', '{\"date\":\"2026-04-28\",\"n\":2}', '::1', '2026-04-29 01:24:06'),
(14, 1, 'Administrator', 'save_attendance', 'rombel:1', '{\"date\":\"2026-04-27\",\"n\":2}', '::1', '2026-04-29 01:24:26'),
(15, 1, 'Administrator', 'save', 'kkm:3', NULL, '::1', '2026-04-29 01:27:13'),
(16, 1, 'Administrator', 'save', 'kkm:11', NULL, '::1', '2026-04-29 01:27:24'),
(17, 1, 'Administrator', 'save', 'kkm:19', NULL, '::1', '2026-04-29 01:27:34'),
(18, 1, 'Administrator', 'save', 'subject:10', NULL, '::1', '2026-04-29 01:28:09'),
(19, 1, 'Administrator', 'save', 'teacher:6', NULL, '::1', '2026-04-29 01:29:20'),
(20, 1, 'Administrator', 'delete', 'rombel:1', NULL, '::1', '2026-04-29 01:29:57'),
(21, 1, 'Administrator', 'save', 'rombel:3', NULL, '::1', '2026-04-29 01:30:06'),
(22, 1, 'Administrator', 'add_members', 'rombel:3', '{\"n\":2}', '::1', '2026-04-29 01:30:20'),
(23, 1, 'Administrator', 'assign_teacher', 'rombel:3/subject:7', '{\"t\":5,\"sem\":null}', '::1', '2026-04-29 01:30:39'),
(24, 1, 'Administrator', 'save', 'topic:2', NULL, '::1', '2026-04-29 01:32:04'),
(25, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-04-29 01:35:58'),
(26, 1, 'Administrator', 'save_attendance', 'rombel:3', '{\"date\":\"2026-04-29\",\"n\":2}', '::1', '2026-04-29 01:38:30'),
(27, 1, 'Administrator', 'save_attendance', 'rombel:3', '{\"date\":\"2026-04-28\",\"n\":2}', '::1', '2026-04-29 01:38:38'),
(28, 1, 'Administrator', 'save_attendance', 'rombel:3', '{\"date\":\"2026-04-27\",\"n\":2}', '::1', '2026-04-29 01:38:43'),
(29, 1, 'Administrator', 'save_grades_daily', 'rombel:3/subj:3/topic:2', '{\"date\":\"2026-04-29\",\"bucket\":\"tengah_ganjil\",\"n\":1}', '::1', '2026-04-29 01:39:40'),
(30, 1, 'Administrator', 'save_final_grades', 'rombel:3/subj:3', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":1}', '::1', '2026-04-29 01:40:45'),
(31, 1, 'Administrator', 'save_character_eval', 'rombel:3', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":9}', '::1', '2026-04-29 01:42:29'),
(32, 1, 'Administrator', 'save_wali_notes', 'rombel:3', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":2}', '::1', '2026-04-29 01:42:39'),
(33, 1, 'Administrator', 'save_character_eval', 'rombel:3', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":18}', '::1', '2026-04-29 01:42:58'),
(34, 1, 'Administrator', 'save_grades_daily', 'rombel:3/subj:3/topic:2', '{\"date\":\"2026-04-29\",\"bucket\":\"tengah_ganjil\",\"n\":2}', '::1', '2026-04-29 01:43:26'),
(35, 1, 'Administrator', 'save_grades_daily', 'rombel:3/subj:3/topic:2', '{\"date\":\"2026-04-27\",\"bucket\":\"tengah_ganjil\",\"n\":2}', '::1', '2026-04-29 01:43:53'),
(36, 1, 'Administrator', 'save_final_grades', 'rombel:3/subj:3', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":2}', '::1', '2026-04-29 01:44:16'),
(37, 1, 'Administrator', 'save_attendance', 'rombel:3', '{\"date\":\"2026-04-29\",\"n\":2}', '::1', '2026-04-29 01:44:51'),
(38, 1, 'Administrator', 'save_report_template', 'jenjang:SD', NULL, '::1', '2026-04-29 01:46:13'),
(39, 1, 'Administrator', 'submit_final_grades', 'rombel:3/subj:3', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":2}', '::1', '2026-04-29 01:47:21'),
(40, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-04-29 01:47:41'),
(41, 6, 'Bu Sari (Wali 1A)', 'login', 'user:6', NULL, '::1', '2026-04-29 01:47:54'),
(42, 3, 'Kepsek SD', 'login', 'user:3', NULL, '::1', '2026-04-29 01:53:17'),
(43, 3, 'Kepsek SD', 'logout', 'user:3', NULL, '::1', '2026-04-29 01:54:54'),
(44, 7, 'Pak Budi (Guru MTK)', 'login', 'user:7', NULL, '::1', '2026-04-29 01:55:06'),
(45, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-04-29 01:56:30'),
(46, 1, 'Administrator', 'save', 'student:9', NULL, '::1', '2026-04-29 02:30:11'),
(47, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-04-29 03:19:18'),
(48, 1, 'Administrator', 'update', 'school_profile', NULL, '::1', '2026-04-29 03:29:43'),
(49, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 03:30:00'),
(50, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 03:30:01'),
(51, 1, 'Administrator', 'save', 'topic:3', NULL, '::1', '2026-04-29 04:43:16'),
(52, 1, 'Administrator', 'save', 'topic:4', NULL, '::1', '2026-04-29 04:43:32'),
(53, 1, 'Administrator', 'add_members', 'rombel:2', '{\"n\":1}', '::1', '2026-04-29 04:47:31'),
(54, 1, 'Administrator', 'save', 'topic:5', NULL, '::1', '2026-04-29 04:50:42'),
(55, 1, 'Administrator', 'save', 'topic:6', NULL, '::1', '2026-04-29 05:07:57'),
(56, 1, 'Administrator', 'save', 'topic:7', NULL, '::1', '2026-04-29 05:11:23'),
(57, 1, 'Administrator', 'save', 'topic:7', NULL, '::1', '2026-04-29 05:11:34'),
(58, 1, 'Administrator', 'save', 'topic:8', NULL, '::1', '2026-04-29 05:42:57'),
(59, 1, 'Administrator', 'save', 'topic:9', NULL, '::1', '2026-04-29 05:53:06'),
(60, 1, 'Administrator', 'save_grades_daily', 'rombel:3/subj:3/topic:2', '{\"date\":\"2026-04-29\",\"bucket\":\"tengah_ganjil\",\"n\":2,\"ranahList\":\"sikap,pengetahuan,keterampilan\"}', '::1', '2026-04-29 05:53:23'),
(61, 1, 'Administrator', 'save_grades_daily', 'rombel:3/subj:3/topic:2', '{\"date\":\"2026-04-29\",\"bucket\":\"tengah_ganjil\",\"n\":2,\"ranahList\":\"sikap,pengetahuan,keterampilan\"}', '::1', '2026-04-29 05:54:11'),
(62, 1, 'Administrator', 'save_grades_daily', 'rombel:3/subj:3/topic:2', '{\"date\":\"2026-04-29\",\"bucket\":\"tengah_ganjil\",\"n\":2,\"ranahList\":\"sikap,pengetahuan,keterampilan\"}', '::1', '2026-04-29 05:54:24'),
(63, 1, 'Administrator', 'save_grades_daily', 'rombel:3/subj:3/topic:2', '{\"date\":\"2026-04-29\",\"bucket\":\"tengah_ganjil\",\"n\":2,\"ranahList\":\"sikap,pengetahuan,keterampilan\"}', '::1', '2026-04-29 05:55:22'),
(64, 1, 'Administrator', 'save_grades_daily', 'rombel:2/subj:7/topic:7', '{\"date\":\"2026-04-29\",\"bucket\":\"tengah_ganjil\",\"n\":1,\"ranahList\":\"sikap,pengetahuan,keterampilan\"}', '::1', '2026-04-29 06:08:32'),
(65, 1, 'Administrator', 'autofill_final_grades', 'rombel:3/subj:3', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":2}', '::1', '2026-04-29 06:09:31'),
(66, 1, 'Administrator', 'autofill_final_grades', 'rombel:3/subj:7', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":0}', '::1', '2026-04-29 06:09:46'),
(67, 1, 'Administrator', 'autofill_final_grades', 'rombel:2/subj:3', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":0}', '::1', '2026-04-29 06:09:58'),
(68, 1, 'Administrator', 'autofill_final_grades', 'rombel:2/subj:7', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":1}', '::1', '2026-04-29 06:10:02'),
(69, 1, 'Administrator', 'save', 'subject:11', NULL, '::1', '2026-04-29 06:27:02'),
(70, 1, 'Administrator', 'save', 'subject:12', NULL, '::1', '2026-04-29 06:27:15'),
(71, 1, 'Administrator', 'save', 'teacher:7', NULL, '::1', '2026-04-29 06:27:51'),
(72, 1, 'Administrator', 'save', 'student:11', NULL, '::1', '2026-04-29 06:29:00'),
(73, 1, 'Administrator', 'save', 'student:11', NULL, '::1', '2026-04-29 06:29:09'),
(74, 1, 'Administrator', 'save', 'rombel:4', NULL, '::1', '2026-04-29 06:29:36'),
(75, 1, 'Administrator', 'assign_teacher', 'rombel:4/subject:6', '{\"t\":7,\"sem\":null}', '::1', '2026-04-29 06:30:12'),
(76, 1, 'Administrator', 'assign_teacher', 'rombel:4/subject:7', '{\"t\":1,\"sem\":null}', '::1', '2026-04-29 06:30:26'),
(77, 1, 'Administrator', 'assign_teacher', 'rombel:4/subject:10', '{\"t\":5,\"sem\":null}', '::1', '2026-04-29 06:30:43'),
(78, 1, 'Administrator', 'save', 'topic:10', NULL, '::1', '2026-04-29 06:31:11'),
(79, 1, 'Administrator', 'save', 'student:11', NULL, '::1', '2026-04-29 06:33:29'),
(80, 1, 'Administrator', 'save', 'student:12', NULL, '::1', '2026-04-29 06:35:03'),
(81, 1, 'Administrator', 'save', 'student:13', NULL, '::1', '2026-04-29 06:50:02'),
(82, 1, 'Administrator', 'add_members', 'rombel:2', '{\"n\":2}', '::1', '2026-04-29 06:57:36'),
(83, 1, 'Administrator', 'save', 'student:13', '{\"rombel_id\":2}', '::1', '2026-04-29 06:57:51'),
(84, 1, 'Administrator', 'save_grades_daily', 'rombel:2/subj:7/topic:5', '{\"date\":\"2026-04-29\",\"bucket\":\"tengah_genap\",\"n\":3,\"ranahList\":\"sikap,pengetahuan,keterampilan\"}', '::1', '2026-04-29 08:31:53'),
(85, 1, 'Administrator', 'submit_final_grades', 'rombel:2/subj:7', '{\"sem\":\"genap\",\"period\":\"PTS\",\"n\":3}', '::1', '2026-04-29 08:33:34'),
(86, 1, 'Administrator', 'review_approve_final_grades', NULL, '{\"n\":2,\"sem\":\"genap\",\"period\":\"PTS\"}', '::1', '2026-04-29 08:33:57'),
(87, 1, 'Administrator', 'save_character_eval', 'rombel:2', '{\"sem\":\"genap\",\"period\":\"PTS\",\"n\":9}', '::1', '2026-04-29 08:35:26'),
(88, 1, 'Administrator', 'save_general_eval', 'rombel:2', '{\"sem\":\"genap\",\"period\":\"PTS\",\"n\":3}', '::1', '2026-04-29 08:36:02'),
(89, 1, 'Administrator', 'save_wali_notes', 'rombel:2', '{\"sem\":\"genap\",\"period\":\"PTS\",\"n\":3}', '::1', '2026-04-29 08:36:20'),
(90, 1, 'Administrator', 'save_attendance', 'rombel:2', '{\"date\":\"2026-04-29\",\"n\":3}', '::1', '2026-04-29 08:37:07'),
(91, 1, 'Administrator', 'save_attendance', 'rombel:2', '{\"date\":\"2026-04-28\",\"n\":3}', '::1', '2026-04-29 08:37:20'),
(92, 1, 'Administrator', 'save_attendance', 'rombel:2', '{\"date\":\"2026-04-27\",\"n\":3}', '::1', '2026-04-29 08:37:27'),
(93, 1, 'Administrator', 'save_attendance', 'rombel:2', '{\"date\":\"2026-04-26\",\"n\":3}', '::1', '2026-04-29 08:37:32'),
(94, 1, 'Administrator', 'save_attendance', 'rombel:2', '{\"date\":\"2026-04-25\",\"n\":3}', '::1', '2026-04-29 08:37:38'),
(95, 1, 'Administrator', 'save_signature', 'jenjang:SD/slot:wali', NULL, '::1', '2026-04-29 08:40:35'),
(96, 1, 'Administrator', 'save_signature', 'jenjang:SD/slot:kepsek', NULL, '::1', '2026-04-29 08:40:47'),
(97, 1, 'Administrator', 'save_signature', 'jenjang:SD/slot:direktur', NULL, '::1', '2026-04-29 08:40:58'),
(98, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 08:44:08'),
(99, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pas_locked', NULL, '::1', '2026-04-29 08:44:10'),
(100, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pts_locked', NULL, '::1', '2026-04-29 08:44:34'),
(101, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pas_locked', NULL, '::1', '2026-04-29 08:44:36'),
(102, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pas_locked', NULL, '::1', '2026-04-29 08:44:44'),
(103, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pts_locked', NULL, '::1', '2026-04-29 08:44:45'),
(104, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pts_locked', NULL, '::1', '2026-04-29 08:44:47'),
(105, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pts_locked', NULL, '::1', '2026-04-29 08:44:48'),
(106, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pas_locked', NULL, '::1', '2026-04-29 08:44:50'),
(107, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pas_locked', NULL, '::1', '2026-04-29 08:44:52'),
(108, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 08:44:56'),
(109, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pas_locked', NULL, '::1', '2026-04-29 08:44:59'),
(110, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 08:45:01'),
(111, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pas_locked', NULL, '::1', '2026-04-29 08:45:02'),
(112, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pts_locked', NULL, '::1', '2026-04-29 08:45:05'),
(113, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pts_locked', NULL, '::1', '2026-04-29 08:45:07'),
(114, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pts_locked', NULL, '::1', '2026-04-29 08:45:08'),
(115, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-04-29 08:46:28'),
(116, 6, 'Bu Sari (Wali 1A)', 'login', 'user:6', NULL, '::1', '2026-04-29 08:47:17'),
(117, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', '{\"val\":0}', '::1', '2026-04-29 13:01:41'),
(118, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 13:21:26'),
(119, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pas_locked', NULL, '::1', '2026-04-29 13:21:28'),
(120, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pts_locked', NULL, '::1', '2026-04-29 13:21:29'),
(121, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pas_locked', NULL, '::1', '2026-04-29 13:21:30'),
(122, 1, 'Administrator', 'toggle_lock', 'year:1/genap/pts_locked', NULL, '::1', '2026-04-29 13:21:32'),
(123, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pas_locked', NULL, '::1', '2026-04-29 13:21:33'),
(124, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 13:21:34'),
(125, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 13:21:35'),
(126, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 13:21:36'),
(127, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 13:21:37'),
(128, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 13:21:38'),
(129, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 13:21:39'),
(130, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 13:21:39'),
(131, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pas_locked', NULL, '::1', '2026-04-29 13:21:40'),
(132, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pas_locked', NULL, '::1', '2026-04-29 13:21:41'),
(133, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pas_locked', NULL, '::1', '2026-04-29 13:21:42'),
(134, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pas_locked', NULL, '::1', '2026-04-29 13:21:43'),
(135, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pas_locked', NULL, '::1', '2026-04-29 13:21:43'),
(136, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pas_locked', NULL, '::1', '2026-04-29 13:21:47'),
(137, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 13:21:48'),
(138, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-04-29 13:22:05'),
(139, 3, 'Kepsek SD', 'login', 'user:3', NULL, '::1', '2026-04-29 13:22:21'),
(140, 3, 'Kepsek SD', 'logout', 'user:3', NULL, '::1', '2026-04-29 13:23:33'),
(141, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-04-29 13:23:50'),
(142, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pts_locked', NULL, '::1', '2026-04-29 13:24:00'),
(143, 1, 'Administrator', 'toggle_lock', 'year:1/ganjil/pas_locked', NULL, '::1', '2026-04-29 13:24:02'),
(144, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-04-29 13:28:38'),
(145, 3, 'Kepsek SD', 'login', 'user:3', NULL, '::1', '2026-04-29 13:28:47'),
(146, 3, 'Kepsek SD', 'logout', 'user:3', NULL, '::1', '2026-04-29 13:37:09'),
(147, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-04-29 13:37:15'),
(148, 1, 'Administrator', 'toggle_semester_lock', 'year:1/ganjil', NULL, '::1', '2026-04-29 13:37:30'),
(149, 1, 'Administrator', 'toggle_semester_lock', 'year:1/genap', NULL, '::1', '2026-04-29 13:37:32'),
(150, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-04-29 13:38:16'),
(151, 3, 'Kepsek SD', 'login', 'user:3', NULL, '::1', '2026-04-29 13:38:25'),
(152, 3, 'Kepsek SD', 'logout', 'user:3', NULL, '::1', '2026-04-29 13:43:25'),
(153, 2, 'Operator Admin', 'login', 'user:2', NULL, '::1', '2026-04-29 13:43:29'),
(154, 2, 'Operator Admin', 'logout', 'user:2', NULL, '::1', '2026-04-29 13:46:52'),
(155, 3, 'Kepsek SD', 'login', 'user:3', NULL, '::1', '2026-04-29 13:46:58'),
(156, 3, 'Kepsek SD', 'logout', 'user:3', NULL, '::1', '2026-04-29 13:50:08'),
(157, 6, 'Bu Sari (Wali 1A)', 'login', 'user:6', NULL, '::1', '2026-04-29 13:50:43'),
(158, 6, 'Bu Sari (Wali 1A)', 'logout', 'user:6', NULL, '::1', '2026-04-29 13:53:21'),
(159, 7, 'Pak Budi (Guru MTK)', 'login', 'user:7', NULL, '::1', '2026-04-29 13:53:32'),
(160, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-04-29 22:32:14'),
(161, 1, 'Administrator', 'toggle_semester_lock', 'year:1/ganjil', NULL, '::1', '2026-04-29 22:56:15'),
(162, 1, 'Administrator', 'toggle_semester_lock', 'year:1/ganjil', NULL, '::1', '2026-04-29 22:56:29'),
(163, 1, 'Administrator', 'toggle_semester_lock', 'year:1/ganjil', NULL, '::1', '2026-04-29 22:59:59'),
(164, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-04-30 01:09:34'),
(165, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-04-30 01:10:38'),
(166, 1, 'Administrator', 'toggle_semester_lock', 'year:1/ganjil', NULL, '::1', '2026-04-30 01:12:07'),
(167, 1, 'Administrator', 'toggle_semester_lock', 'year:1/ganjil', NULL, '::1', '2026-04-30 01:12:09'),
(168, 1, 'Administrator', 'save', 'kkm:2', NULL, '::1', '2026-04-30 01:12:48'),
(169, 1, 'Administrator', 'save', 'subject:13', NULL, '::1', '2026-04-30 01:18:30'),
(170, 1, 'Administrator', 'save', 'rombel:5', NULL, '::1', '2026-04-30 01:23:37'),
(171, 1, 'Administrator', 'remove_member', 'rombel:2', '{\"s\":13}', '::1', '2026-04-30 01:24:14'),
(172, 1, 'Administrator', 'save', 'topic:11', NULL, '::1', '2026-04-30 01:26:06'),
(173, 1, 'Administrator', 'save_attendance', 'rombel:3', '{\"date\":\"2026-04-30\",\"n\":2}', '::1', '2026-04-30 01:26:46'),
(174, 1, 'Administrator', 'save_attendance', 'rombel:2', '{\"date\":\"2026-04-30\",\"n\":2}', '::1', '2026-04-30 01:26:51'),
(175, 1, 'Administrator', 'save_grades_daily', 'rombel:2/subj:7/topic:8', '{\"date\":\"2026-04-30\",\"bucket\":\"tengah_ganjil\",\"n\":1,\"ranahList\":\"sikap,pengetahuan,keterampilan\"}', '::1', '2026-04-30 01:28:52'),
(176, 1, 'Administrator', 'submit_final_grades', 'rombel:2/subj:7', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":1}', '::1', '2026-04-30 01:29:38'),
(177, 1, 'Administrator', 'save_final_grades', 'rombel:2/subj:7', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":1}', '::1', '2026-04-30 01:30:03'),
(178, 1, 'Administrator', 'submit_final_grades', 'rombel:2/subj:7', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":1}', '::1', '2026-04-30 01:30:05'),
(179, 1, 'Administrator', 'submit_final_grades', 'rombel:2/subj:7', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":1}', '::1', '2026-04-30 01:32:13'),
(180, 1, 'Administrator', 'submit_final_grades', 'rombel:2/subj:7', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":1}', '::1', '2026-04-30 01:32:18'),
(181, 1, 'Administrator', 'review_approve_final_grades', NULL, '{\"n\":1,\"sem\":\"ganjil\",\"period\":\"PTS\"}', '::1', '2026-04-30 01:32:44'),
(182, 1, 'Administrator', 'save_character_eval', 'rombel:3', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":18}', '::1', '2026-04-30 01:34:14'),
(183, 1, 'Administrator', 'save_character_eval', 'rombel:2', '{\"sem\":\"ganjil\",\"period\":\"PTS\",\"n\":18}', '::1', '2026-04-30 01:34:57'),
(184, 1, 'Administrator', 'save_report_template', 'jenjang:SD', NULL, '::1', '2026-04-30 01:37:35'),
(185, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-04-30 21:31:11'),
(186, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-04-30 21:33:32'),
(187, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-04-30 21:33:56'),
(188, NULL, 'Aga Sayoga', 'parent_login', 'student:9', NULL, '::1', '2026-04-30 21:34:21'),
(189, NULL, 'Aga Sayoga', 'parent_view_home', 'student:9', NULL, '::1', '2026-04-30 21:34:21'),
(190, NULL, 'Aga Sayoga', 'parent_change_pw', 'parent_auth:5', NULL, '::1', '2026-04-30 21:34:58'),
(191, NULL, 'Aga Sayoga', 'parent_view_home', 'student:9', NULL, '::1', '2026-04-30 21:34:58'),
(192, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-04-30 21:35:36'),
(193, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-05-03 01:48:10'),
(194, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-05-03 01:48:31'),
(195, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-05-03 01:48:50'),
(196, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-05-03 01:50:01'),
(197, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-05-03 01:50:14'),
(198, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-05-03 01:50:37'),
(199, NULL, 'Ahmad Fauzi', 'parent_login', 'student:1', NULL, '::1', '2026-05-03 01:50:55'),
(200, NULL, 'Ahmad Fauzi', 'parent_view_home', 'student:1', NULL, '::1', '2026-05-03 01:50:55'),
(201, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-05-04 00:01:09'),
(202, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-05-05 01:35:02'),
(203, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-05-05 02:20:36'),
(204, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-05-05 02:20:56'),
(205, 1, 'Administrator', 'toggle_semester_lock', 'year:1/ganjil', NULL, '::1', '2026-05-05 02:21:21'),
(206, 1, 'Administrator', 'toggle_semester_lock', 'year:1/ganjil', NULL, '::1', '2026-05-05 02:21:23'),
(207, 1, 'Administrator', 'save', 'subject:14', NULL, '::1', '2026-05-05 02:23:35'),
(208, 1, 'Administrator', 'save', 'teacher:8', NULL, '::1', '2026-05-05 02:24:14'),
(209, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-05-05 02:27:53'),
(210, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-05-05 07:50:40'),
(211, 1, 'Administrator', 'save', 'student:1', NULL, '::1', '2026-05-05 08:06:38'),
(212, 1, 'Administrator', 'save', 'student:1', NULL, '::1', '2026-05-05 08:07:01'),
(213, 1, 'Administrator', 'save', 'student:1', NULL, '::1', '2026-05-05 08:18:18'),
(214, 1, 'Administrator', 'save_report_template', 'jenjang:SD', NULL, '::1', '2026-05-05 08:21:09'),
(215, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-05-25 22:12:58'),
(216, 10, 'Artha', 'login', 'user:10', NULL, '::1', '2026-05-25 23:58:53'),
(217, 10, 'Artha', 'logout', 'user:10', NULL, '::1', '2026-05-27 01:13:20'),
(218, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-05-27 01:13:24'),
(219, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-05-27 04:17:13'),
(220, 1, 'Administrator', 'save_report_template', 'jenjang:SD', NULL, '::1', '2026-05-27 04:23:31'),
(221, 1, 'Administrator', 'save_report_template', 'jenjang:SD', NULL, '::1', '2026-05-27 04:25:22'),
(222, 1, 'Administrator', 'save_report_template', 'jenjang:SD', NULL, '::1', '2026-05-27 04:26:06'),
(223, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-05-28 22:25:34'),
(224, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-01 06:42:21'),
(225, 1, 'Administrator', 'save', 'elective:1', NULL, '::1', '2026-06-02 05:42:53'),
(226, 1, 'Administrator', 'save', 'elective:2', NULL, '::1', '2026-06-02 05:43:36'),
(227, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-02 05:44:22'),
(228, 10, 'Artha', 'login', 'user:10', NULL, '::1', '2026-06-02 05:44:26'),
(229, 10, 'Artha', 'save', 'elective_assign:e1:s9:ganjil', NULL, '::1', '2026-06-02 05:44:50'),
(230, 10, 'Artha', 'save', 'elective_assign:e1:s11:ganjil', NULL, '::1', '2026-06-02 05:44:50'),
(231, 10, 'Artha', 'save', 'elective_assign:e1:s11:ganjil', NULL, '::1', '2026-06-02 05:45:05'),
(232, 10, 'Artha', 'save', 'elective_assign:e1:s9:ganjil', NULL, '::1', '2026-06-02 05:45:06'),
(233, 10, 'Artha', 'logout', 'user:10', NULL, '::1', '2026-06-02 08:20:51'),
(234, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-02 08:21:01'),
(235, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-02 08:24:39'),
(236, 6, 'Bu Sari (Wali 1A)', 'login', 'user:6', NULL, '::1', '2026-06-02 08:24:43'),
(237, 6, 'Bu Sari (Wali 1A)', 'save', 'elective_assign:e1:s1:ganjil', NULL, '::1', '2026-06-02 08:25:59'),
(238, 6, 'Bu Sari (Wali 1A)', 'save', 'elective_assign:e1:s2:ganjil', NULL, '::1', '2026-06-02 08:26:00'),
(239, 6, 'Bu Sari (Wali 1A)', 'save', 'elective_assign:e1:s2:ganjil', NULL, '::1', '2026-06-02 08:26:05'),
(240, 6, 'Bu Sari (Wali 1A)', 'save', 'elective_assign:e1:s2:ganjil', NULL, '::1', '2026-06-02 08:26:07'),
(241, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-08 02:34:21'),
(242, 1, 'Administrator', 'save_report_template', 'jenjang:SMP', NULL, '::1', '2026-06-08 02:50:43'),
(243, 1, 'Administrator', 'save_report_template', 'jenjang:SD', NULL, '::1', '2026-06-08 02:51:20'),
(244, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-20 03:55:42'),
(245, 1, 'Administrator', 'toggle_semester_lock', 'year:1/ganjil', NULL, '::1', '2026-06-20 03:55:48'),
(246, 1, 'Administrator', 'toggle_semester_lock', 'year:1/genap', NULL, '::1', '2026-06-20 03:55:49'),
(247, 1, 'Administrator', 'create', 'academic_year:2026/2027', '{\"copy_from\":null}', '::1', '2026-06-20 03:55:55'),
(248, 1, 'Administrator', 'toggle_semester_lock', 'year:2/genap', NULL, '::1', '2026-06-20 03:55:57'),
(249, 1, 'Administrator', 'create', 'academic_year:2027/2028', '{\"copy_from\":null}', '::1', '2026-06-20 03:56:38'),
(250, 1, 'Administrator', 'toggle_semester_lock', 'year:2/ganjil', NULL, '::1', '2026-06-20 03:56:40'),
(251, 1, 'Administrator', 'create', 'academic_year:2028/2029', '{\"copy_from\":null}', '::1', '2026-06-20 04:18:18'),
(252, 1, 'Administrator', 'create', 'academic_year:2029/2030', '{\"copy_from\":null}', '::1', '2026-06-20 04:23:06'),
(253, 1, 'Administrator', 'create', 'academic_year:2030/2031', '{\"copy_from\":null}', '::1', '2026-06-20 04:30:03'),
(254, 1, 'Administrator', 'logout', 'user:1', NULL, '::1', '2026-06-20 04:30:16'),
(255, 1, 'Administrator', 'login', 'user:1', NULL, '::1', '2026-06-20 04:31:05'),
(256, 1, 'Administrator', 'create', 'academic_year:2060/2061', '{\"copy_from\":null}', '::1', '2026-06-20 04:40:50'),
(257, 1, 'Administrator', 'create', 'academic_year:2013/2014', '{\"copy_from\":null}', '::1', '2026-06-20 04:52:08'),
(258, 1, 'Administrator', 'set_active', 'academic_year:8', NULL, '::1', '2026-06-20 04:52:14'),
(259, 1, 'Administrator', 'delete', 'student:1', NULL, '::1', '2026-06-20 04:52:31'),
(260, 1, 'Administrator', 'save', 'student:12', NULL, '::1', '2026-06-20 04:52:51'),
(261, 1, 'Administrator', 'create', 'academic_year:2031/2032', '{\"copy_from\":null}', '::1', '2026-06-20 05:19:22'),
(262, 1, 'Administrator', 'delete', 'academic_year:6', NULL, '::1', '2026-06-20 06:24:45'),
(263, 1, 'Administrator', 'delete', 'academic_year:7', NULL, '::1', '2026-06-20 06:24:48'),
(264, 1, 'Administrator', 'delete', 'academic_year:5', NULL, '::1', '2026-06-20 06:24:49'),
(265, 1, 'Administrator', 'delete', 'academic_year:4', NULL, '::1', '2026-06-20 06:24:52'),
(266, 1, 'Administrator', 'delete', 'academic_year:3', NULL, '::1', '2026-06-20 06:24:54'),
(267, 1, 'Administrator', 'delete', 'academic_year:8', NULL, '::1', '2026-06-20 06:24:59'),
(268, 1, 'Administrator', 'create', 'academic_year:2027/2028', '{\"copy_from\":null}', '::1', '2026-06-20 06:25:16'),
(269, 1, 'Administrator', 'create', 'academic_year:2028/2029', '{\"copy_from\":null}', '::1', '2026-06-20 06:36:31'),
(270, 1, 'Administrator', 'create', 'academic_year:2029/2030', '{\"copy_from\":null}', '::1', '2026-06-20 06:40:35'),
(271, 1, 'Administrator', 'set_active', 'academic_year:9', NULL, '::1', '2026-06-20 06:40:39'),
(272, 1, 'Administrator', 'toggle_semester_lock', 'year:2/ganjil', NULL, '::1', '2026-06-20 06:48:38'),
(273, 1, 'Administrator', 'toggle_semester_lock', 'year:2/genap', NULL, '::1', '2026-06-20 06:48:39'),
(274, 1, 'Administrator', 'save', 'subject_category:4', NULL, '::1', '2026-06-20 06:48:55'),
(275, 1, 'Administrator', 'save', 'subject_category:5', NULL, '::1', '2026-06-20 06:49:13'),
(276, 1, 'Administrator', 'toggle_semester_lock', 'year:1/ganjil', NULL, '::1', '2026-06-20 06:49:35'),
(277, 1, 'Administrator', 'save', 'subject_category:6', NULL, '::1', '2026-06-20 06:49:41'),
(278, 1, 'Administrator', 'save', 'subject_category:8', NULL, '::1', '2026-06-20 06:50:15'),
(279, 1, 'Administrator', 'save', 'subject_category:11', NULL, '::1', '2026-06-20 07:30:07'),
(280, 1, 'Administrator', 'save', 'subject_category:12', NULL, '::1', '2026-06-20 07:30:11'),
(281, 1, 'Administrator', 'save', 'subject_category:14', NULL, '::1', '2026-06-20 07:30:24'),
(282, 1, 'Administrator', 'save', 'subject:16', NULL, '::1', '2026-06-20 07:41:44'),
(283, 1, 'Administrator', 'save', 'subject_category:15', NULL, '::1', '2026-06-20 07:52:38'),
(284, 1, 'Administrator', 'save', 'subject:25', NULL, '::1', '2026-06-20 07:52:50'),
(285, 1, 'Administrator', 'save', 'teacher:9', NULL, '::1', '2026-06-20 07:58:58'),
(286, 1, 'Administrator', 'save', 'teacher:10', NULL, '::1', '2026-06-20 08:05:44'),
(287, 1, 'Administrator', 'save', 'teacher:11', NULL, '::1', '2026-06-20 08:12:02'),
(288, 1, 'Administrator', 'save', 'teacher:12', NULL, '::1', '2026-06-20 08:20:40'),
(289, 1, 'Administrator', 'save', 'student:14', NULL, '::1', '2026-06-20 08:22:05'),
(290, 1, 'Administrator', 'save', 'student:15', NULL, '::1', '2026-06-20 09:06:05'),
(291, 1, 'Administrator', 'save', 'student:16', NULL, '::1', '2026-06-20 09:06:40'),
(292, 1, 'Administrator', 'save', 'teacher:13', NULL, '::1', '2026-06-20 09:07:23'),
(293, 1, 'Administrator', 'save', 'subject_category:16', NULL, '::1', '2026-06-20 09:08:21'),
(294, 1, 'Administrator', 'save', 'subject:26', NULL, '::1', '2026-06-20 09:08:40');

-- --------------------------------------------------------

--
-- Table structure for table `character_aspects`
--

CREATE TABLE `character_aspects` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `jenjang` enum('SD','SMP','SMA') NOT NULL DEFAULT 'SD',
  `nama` varchar(120) NOT NULL,
  `kategori` enum('Spiritual and morality','Discipline','Manner','Obedience','Focus and Confidence','spiritual','sosial') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `character_aspects`
--

INSERT INTO `character_aspects` (`id`, `academic_year_id`, `jenjang`, `nama`, `kategori`) VALUES
(1, 9, 'SD', 'Ketaatan beribadah', 'spiritual'),
(2, 9, 'SD', 'Berdoa sebelum/sesudah kegiatan', 'spiritual'),
(3, 9, 'SD', 'Toleransi beragama', 'spiritual'),
(4, 9, 'SD', 'Jujur', 'sosial'),
(5, 9, 'SD', 'Disiplin', 'sosial'),
(6, 9, 'SD', 'Tanggung jawab', 'sosial'),
(7, 9, 'SD', 'Santun', 'sosial'),
(8, 9, 'SD', 'Peduli', 'sosial'),
(9, 9, 'SD', 'Percaya diri', 'sosial');

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
(1, 3, 1, 2, 'ganjil', 'PTS', 'NI', NULL, '2026-04-29 01:42:29'),
(2, 3, 1, 1, 'ganjil', 'PTS', 'NI', NULL, '2026-04-29 01:42:29'),
(3, 3, 1, 3, 'ganjil', 'PTS', 'NI', NULL, '2026-04-29 01:42:29'),
(4, 3, 1, 5, 'ganjil', 'PTS', 'SI', NULL, '2026-04-29 01:42:29'),
(5, 3, 1, 4, 'ganjil', 'PTS', 'SI', NULL, '2026-04-29 01:42:29'),
(6, 3, 1, 8, 'ganjil', 'PTS', 'SI', NULL, '2026-04-29 01:42:29'),
(7, 3, 1, 9, 'ganjil', 'PTS', 'SI', NULL, '2026-04-29 01:42:29'),
(8, 3, 1, 7, 'ganjil', 'PTS', 'PR', NULL, '2026-04-29 01:42:29'),
(9, 3, 1, 6, 'ganjil', 'PTS', 'WI', NULL, '2026-04-29 01:42:29'),
(10, 3, 2, 2, 'ganjil', 'PTS', 'SI', NULL, '2026-04-29 01:42:58'),
(11, 3, 2, 1, 'ganjil', 'PTS', 'SI', NULL, '2026-04-29 01:42:58'),
(12, 3, 2, 3, 'ganjil', 'PTS', 'WI', NULL, '2026-04-29 01:42:58'),
(13, 3, 2, 5, 'ganjil', 'PTS', 'WI', NULL, '2026-04-29 01:42:58'),
(14, 3, 2, 4, 'ganjil', 'PTS', 'WI', NULL, '2026-04-29 01:42:58'),
(15, 3, 2, 8, 'ganjil', 'PTS', 'PR', NULL, '2026-04-29 01:42:58'),
(16, 3, 2, 9, 'ganjil', 'PTS', 'WI', NULL, '2026-04-29 01:42:58'),
(17, 3, 2, 7, 'ganjil', 'PTS', 'PR', NULL, '2026-04-29 01:42:58'),
(18, 3, 2, 6, 'ganjil', 'PTS', 'WI', NULL, '2026-04-29 01:42:58'),
(19, 2, 9, 2, 'genap', 'PTS', 'NI', NULL, '2026-04-29 08:35:26'),
(20, 2, 9, 1, 'genap', 'PTS', 'WI', NULL, '2026-04-29 08:35:26'),
(21, 2, 9, 3, 'genap', 'PTS', 'PR', NULL, '2026-04-29 08:35:26'),
(22, 2, 9, 5, 'genap', 'PTS', 'SI', NULL, '2026-04-29 08:35:26'),
(23, 2, 9, 4, 'genap', 'PTS', 'WI', NULL, '2026-04-29 08:35:26'),
(24, 2, 9, 8, 'genap', 'PTS', 'WI', NULL, '2026-04-29 08:35:26'),
(25, 2, 9, 9, 'genap', 'PTS', 'PR', NULL, '2026-04-29 08:35:26'),
(26, 2, 9, 7, 'genap', 'PTS', 'WI', NULL, '2026-04-29 08:35:26'),
(27, 2, 9, 6, 'genap', 'PTS', 'PR', NULL, '2026-04-29 08:35:26'),
(28, 2, 9, 2, 'ganjil', 'PTS', 'SI', NULL, '2026-04-30 01:34:57'),
(29, 2, 9, 1, 'ganjil', 'PTS', 'SI', NULL, '2026-04-30 01:34:57'),
(30, 2, 9, 3, 'ganjil', 'PTS', 'SI', NULL, '2026-04-30 01:34:57'),
(31, 2, 9, 5, 'ganjil', 'PTS', 'SI', NULL, '2026-04-30 01:34:57'),
(32, 2, 9, 4, 'ganjil', 'PTS', 'WI', NULL, '2026-04-30 01:34:57'),
(33, 2, 9, 8, 'ganjil', 'PTS', 'SI', NULL, '2026-04-30 01:34:57'),
(34, 2, 9, 9, 'ganjil', 'PTS', 'SI', NULL, '2026-04-30 01:34:57'),
(35, 2, 9, 7, 'ganjil', 'PTS', 'WI', NULL, '2026-04-30 01:34:57'),
(36, 2, 9, 6, 'ganjil', 'PTS', 'PR', NULL, '2026-04-30 01:34:57'),
(37, 2, 11, 2, 'ganjil', 'PTS', 'PR', NULL, '2026-04-30 01:34:57'),
(38, 2, 11, 1, 'ganjil', 'PTS', 'SI', NULL, '2026-04-30 01:34:57'),
(39, 2, 11, 3, 'ganjil', 'PTS', 'SI', NULL, '2026-04-30 01:34:57'),
(40, 2, 11, 5, 'ganjil', 'PTS', 'NI', NULL, '2026-04-30 01:34:57'),
(41, 2, 11, 4, 'ganjil', 'PTS', 'WI', NULL, '2026-04-30 01:34:57'),
(42, 2, 11, 8, 'ganjil', 'PTS', 'WI', NULL, '2026-04-30 01:34:57'),
(43, 2, 11, 9, 'ganjil', 'PTS', 'WI', NULL, '2026-04-30 01:34:57'),
(44, 2, 11, 7, 'ganjil', 'PTS', 'WI', NULL, '2026-04-30 01:34:57'),
(45, 2, 11, 6, 'ganjil', 'PTS', 'WI', NULL, '2026-04-30 01:34:57');

-- --------------------------------------------------------

--
-- Table structure for table `electives`
--

CREATE TABLE `electives` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `jenjang` enum('SD','SMP','SMA') NOT NULL,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(120) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `electives`
--

INSERT INTO `electives` (`id`, `academic_year_id`, `jenjang`, `kode`, `nama`, `deskripsi`, `created_at`, `deleted_at`) VALUES
(1, 1, 'SD', 'CGV', 'CGV', NULL, '2026-06-02 05:42:53', NULL),
(2, 1, 'SD', 'SWR', 'SWR', NULL, '2026-06-02 05:43:36', NULL);

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
(1, 1, 2, 9, 'ganjil', 10, '2026-06-02 05:44:50', '2026-06-02 05:44:50'),
(2, 1, 1, 11, 'ganjil', 10, '2026-06-02 05:45:05', '2026-06-02 05:45:05'),
(4, 1, 1, 1, 'ganjil', 6, '2026-06-02 08:25:59', '2026-06-02 08:25:59'),
(5, 1, 1, 2, 'ganjil', 6, '2026-06-02 08:26:07', '2026-06-02 08:26:07');

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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elective_classes`
--

INSERT INTO `elective_classes` (`id`, `elective_id`, `nama`, `teacher_id`, `kapasitas`, `created_at`, `deleted_at`) VALUES
(1, 1, 'Coding', NULL, 25, '2026-06-02 05:42:53', NULL),
(2, 1, 'Grafis', NULL, 25, '2026-06-02 05:42:53', NULL),
(3, 1, 'Videografi', NULL, 20, '2026-06-02 05:42:53', NULL),
(4, 2, 'Speaking', NULL, 25, '2026-06-02 05:43:36', NULL),
(5, 2, 'Writing', NULL, 25, '2026-06-02 05:43:36', NULL),
(6, 2, 'Reading', NULL, 17, '2026-06-02 05:43:36', NULL);

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
(1, 2),
(1, 3),
(2, 4),
(2, 5);

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

--
-- Dumping data for table `extracurriculars`
--

INSERT INTO `extracurriculars` (`id`, `academic_year_id`, `nama`, `pembina`, `jadwal`, `deskripsi`, `is_active`, `created_at`) VALUES
(1, 9, 'Pramuka', 'Kak Tono', 'Sabtu 08:00', NULL, 1, '2026-04-28 22:38:05'),
(2, 9, 'Tahfidz', 'Ust. Yusuf', 'Senin 14:00', NULL, 1, '2026-04-28 22:38:05'),
(3, 9, 'Futsal', 'Coach Iwan', 'Rabu 15:00', NULL, 1, '2026-04-28 22:38:05');

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
  `submitted_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_grades`
--

INSERT INTO `final_grades` (`id`, `rombel_id`, `subject_id`, `student_id`, `semester`, `period_kind`, `nilai_sikap`, `nilai_pengetahuan`, `nilai_keterampilan`, `catatan_guru`, `status`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 3, 3, 1, 'ganjil', 'PTS', 85.00, 90.00, 90.00, NULL, 'draft', NULL, NULL, '2026-04-29 01:40:45', '2026-04-29 06:09:31'),
(2, 3, 3, 2, 'ganjil', 'PTS', 90.00, 87.00, 87.00, NULL, 'draft', NULL, NULL, '2026-04-29 01:44:16', '2026-04-29 06:09:31'),
(3, 2, 7, 9, 'ganjil', 'PTS', 90.00, 89.00, 94.50, NULL, 'approved', 1, '2026-04-30 09:32:44', '2026-04-29 06:10:02', '2026-04-30 01:32:44'),
(4, 2, 7, 13, 'genap', 'PTS', 90.00, 88.00, 86.00, NULL, 'draft', NULL, NULL, '2026-04-29 08:32:39', '2026-04-29 08:32:39'),
(5, 2, 7, 9, 'genap', 'PTS', 92.00, 89.00, 88.00, NULL, 'approved', 1, '2026-04-29 16:33:57', '2026-04-29 08:32:39', '2026-04-29 08:33:57'),
(6, 2, 7, 11, 'genap', 'PTS', 85.00, 88.00, 86.00, NULL, 'approved', 1, '2026-04-29 16:33:57', '2026-04-29 08:32:39', '2026-04-29 08:33:57');

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
(1, 2, 13, 'genap', 'PTS', NULL),
(2, 2, 9, 'genap', 'PTS', 'Lorem ipsum dolor sit amet is placeholder text used in design and publishing to demonstrate visual layouts, derived from Cicero\'s 45 B.C. Latin text on ethics. It is used to prevent the focus from being on the text content itself and has been industry standard since the 1500s.'),
(3, 2, 11, 'genap', 'PTS', NULL);

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
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades_daily`
--

INSERT INTO `grades_daily` (`id`, `rombel_id`, `subject_id`, `topic_id`, `student_id`, `semester`, `period_bucket`, `tanggal`, `nilai_sikap`, `nilai_pengetahuan`, `nilai_keterampilan`, `recorded_by`, `created_at`, `updated_at`) VALUES
(4, 3, 3, 2, 1, 'ganjil', 'tengah_ganjil', '2026-04-27', 90.00, NULL, NULL, 1, '2026-04-29 01:43:53', '2026-04-29 01:43:53'),
(5, 3, 3, 2, 2, 'ganjil', 'tengah_ganjil', '2026-04-27', 90.00, NULL, NULL, 1, '2026-04-29 01:43:53', '2026-04-29 01:43:53'),
(12, 3, 3, 2, 1, 'ganjil', 'tengah_ganjil', '2026-04-29', 80.00, 90.00, 90.00, 1, '2026-04-29 05:55:22', '2026-04-29 05:55:22'),
(13, 3, 3, 2, 2, 'ganjil', 'tengah_ganjil', '2026-04-29', 90.00, 87.00, 87.00, 1, '2026-04-29 05:55:22', '2026-04-29 05:55:22'),
(14, 2, 7, 7, 9, 'ganjil', 'tengah_ganjil', '2026-04-29', 90.00, 90.00, 100.00, 1, '2026-04-29 06:08:32', '2026-04-29 06:08:32'),
(15, 2, 7, 5, 13, 'genap', 'tengah_genap', '2026-04-29', 90.00, 88.00, 86.00, 1, '2026-04-29 08:31:53', '2026-04-29 08:31:53'),
(16, 2, 7, 5, 9, 'genap', 'tengah_genap', '2026-04-29', 92.00, 89.00, 88.00, 1, '2026-04-29 08:31:53', '2026-04-29 08:31:53'),
(17, 2, 7, 5, 11, 'genap', 'tengah_genap', '2026-04-29', 85.00, 88.00, 86.00, 1, '2026-04-29 08:31:53', '2026-04-29 08:31:53'),
(18, 2, 7, 8, 9, 'ganjil', 'tengah_ganjil', '2026-04-30', 90.00, 88.00, 89.00, 1, '2026-04-30 01:28:52', '2026-04-30 01:28:52');

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
  `jenjang` enum('SD','SMP','SMA') NOT NULL,
  `grade` varchar(5) NOT NULL,
  `min_val` decimal(5,2) NOT NULL,
  `max_val` decimal(5,2) NOT NULL,
  `predikat` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kkm_settings`
--

INSERT INTO `kkm_settings` (`id`, `academic_year_id`, `jenjang`, `grade`, `min_val`, `max_val`, `predikat`) VALUES
(1, 9, 'SD', 'A+', 100.00, 100.00, 'Perfect'),
(2, 9, 'SD', 'A', 95.98, 99.99, 'nice'),
(3, 9, 'SD', 'A-', 91.00, 95.99, 'Amazing'),
(4, 9, 'SD', 'B+', 86.00, 90.99, 'Terrific'),
(5, 9, 'SD', 'B', 81.00, 85.99, 'Good'),
(6, 9, 'SD', 'B-', 76.00, 80.99, 'Good'),
(7, 9, 'SD', 'C', 70.00, 75.99, 'Average'),
(8, 9, 'SD', 'D', 0.00, 69.99, 'Below Average'),
(9, 9, 'SMP', 'A+', 100.00, 100.00, 'Perfect'),
(10, 9, 'SMP', 'A', 96.00, 99.99, 'Excellent'),
(11, 9, 'SMP', 'A-', 91.00, 95.99, 'Good Job'),
(12, 9, 'SMP', 'B+', 86.00, 90.99, 'Terrific'),
(13, 9, 'SMP', 'B', 81.00, 85.99, 'Good'),
(14, 9, 'SMP', 'B-', 76.00, 80.99, 'Good'),
(15, 9, 'SMP', 'C', 70.00, 75.99, 'Average'),
(16, 9, 'SMP', 'D', 0.00, 69.99, 'Below Average'),
(17, 9, 'SMA', 'A+', 100.00, 100.00, 'Perfect'),
(18, 9, 'SMA', 'A', 96.00, 99.99, 'Excellent'),
(19, 9, 'SMA', 'A-', 91.00, 95.99, 'Uwau'),
(20, 9, 'SMA', 'B+', 86.00, 90.99, 'Terrific'),
(21, 9, 'SMA', 'B', 81.00, 85.99, 'Good'),
(22, 9, 'SMA', 'B-', 76.00, 80.99, 'Good'),
(23, 9, 'SMA', 'C', 70.00, 75.99, 'Average'),
(24, 9, 'SMA', 'D', 0.00, 69.99, 'Below Average');

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
(1, 1, '$2y$10$vJ9iUxVWGPgQkaPcpjfIb.vDPKiF3xcTCpFHnjIn7oTUugUcWsTY.', 1, 1, '2026-05-03 09:50:55', '2026-04-28 22:38:05'),
(2, 2, '$2y$10$RpY/BV.JArsGOycWKWoZPORyrtSmPpT9dS.Y2eN5xdK5V/NlsFkj.', 1, 1, NULL, '2026-04-28 22:38:05'),
(3, 3, '$2y$10$QuvHeMS30BsbtlKULUlKm.uRkZg6OtFHmJj1oDdbaPPEuVOdnOnMy', 1, 1, NULL, '2026-04-28 22:38:05'),
(4, 4, '$2y$10$Y/xCBnENUuKGYQUYwW66Ze09yzVakhvMTIwiiVs.8XQuFBkJ.FbKa', 1, 1, NULL, '2026-04-28 22:38:05'),
(5, 9, '$2y$10$kkTPWnt2cZoC7Rr4O/PwX.6W6P/A4q5.NL49K75N10g1jIg8wO4Ga', 0, 1, '2026-05-01 05:34:21', '2026-04-29 02:30:11'),
(6, 11, '$2y$10$mIaWyIlU4pisZdawxp9SYuu9OsjynAeRPU1UcOkdcQ7ESeLYEXj0O', 1, 1, NULL, '2026-04-29 06:29:00'),
(7, 12, '$2y$10$BckGEnP0p6JEjB8MT6Bqwu59toRbC.0Dx10OiDVUmATFmHW8njODe', 1, 1, NULL, '2026-04-29 06:35:03'),
(8, 13, '$2y$10$eTcI.wezftzQ1KTMGddyNexnMzqpeQ6RtdV5hioQsWPM/Xwr/qHIW', 1, 1, NULL, '2026-04-29 06:50:02'),
(9, 14, '$2y$10$EAGENQ3qVeV6fGPsHu7w4.W1oWHjbUT6o.xh3NltHrebmgMJqD1Jq', 1, 1, NULL, '2026-06-20 08:22:05'),
(10, 15, '$2y$10$XlFAO3LVYR4Ei/7KIJLG7.SJun14qeVxkCaY8rgAz2U9UQuduohmm', 1, 1, NULL, '2026-06-20 09:06:05'),
(11, 16, '$2y$10$vabehlj3OVKKlsrn5c6K5uCuW9aZLF2vimhCZ74DO/zx4HRwJcOMa', 1, 1, NULL, '2026-06-20 09:06:40');

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
  `jenjang` enum('SD','SMP','SMA') NOT NULL,
  `slot` enum('wali','kepsek','direktur','parent') NOT NULL,
  `nama` varchar(120) DEFAULT NULL,
  `jabatan` varchar(120) DEFAULT NULL,
  `ttd_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_signatures`
--

INSERT INTO `report_signatures` (`id`, `academic_year_id`, `jenjang`, `slot`, `nama`, `jabatan`, `ttd_path`) VALUES
(1, 9, 'SD', 'wali', 'Episman Gea', 'Wali Kelas', NULL),
(2, 9, 'SD', 'kepsek', 'Dewi', 'Kepala Sekolah', NULL),
(3, 9, 'SD', 'direktur', 'Wayan Suarnawan', 'Direktur Operational', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `report_templates`
--

CREATE TABLE `report_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `jenjang` enum('SD','SMP','SMA') NOT NULL,
  `layout_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_json`)),
  `layout_hidden_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_hidden_json`)),
  `header_img` varchar(255) DEFAULT NULL,
  `footer_img` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_templates`
--

INSERT INTO `report_templates` (`id`, `academic_year_id`, `jenjang`, `layout_json`, `layout_hidden_json`, `header_img`, `footer_img`) VALUES
(1, 9, 'SD', '[\"identitas\",\"character\",\"wali_note\",\"attendance\",\"academic\",\"signatures\",\"extracurricular\",\"general_eval\"]', '[\"character\",\"wali_note\",\"extracurricular\",\"general_eval\"]', 'reports/hdr_SD_2c093f48970e.jpg', NULL),
(2, 9, 'SMP', '[\"identitas\",\"character\",\"academic\",\"extracurricular\",\"attendance\",\"wali_note\",\"general_eval\",\"signatures\"]', '[\"character\"]', NULL, NULL),
(3, 9, 'SMA', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rombel`
--

CREATE TABLE `rombel` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `jenjang` enum('SD','SMP','SMA') NOT NULL,
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
(1, 1, 'SD', 1, '1A', 6, 28, '2026-04-28 22:38:05', '2026-04-29 01:29:57', '2026-04-29 09:29:57'),
(2, 1, 'SD', 3, 'Mawar', 10, 25, '2026-04-29 00:58:34', '2026-04-29 00:58:34', NULL),
(3, 1, 'SD', 1, 'Kamboja', 6, 28, '2026-04-29 01:30:06', '2026-04-29 01:30:06', NULL),
(4, 1, 'SD', 6, 'Sakura', 12, 25, '2026-04-29 06:29:36', '2026-04-29 06:29:36', NULL),
(5, 1, 'SD', 5, '5 Leli', 10, 28, '2026-04-30 01:23:37', '2026-04-30 01:23:37', NULL);

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
(1, 1, '2026-04-28 22:38:05'),
(1, 2, '2026-04-28 22:38:05'),
(2, 9, '2026-04-29 04:47:31'),
(2, 11, '2026-04-29 06:57:36'),
(3, 1, '2026-04-29 01:30:20'),
(3, 2, '2026-04-29 01:30:20');

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
(1, 1, 4, 2, 'ganjil', '2026-04-28 22:38:05'),
(2, 2, 7, 5, NULL, '2026-04-29 01:21:37'),
(3, 3, 7, 5, NULL, '2026-04-29 01:30:39'),
(4, 4, 6, 7, NULL, '2026-04-29 06:30:12'),
(5, 4, 7, 1, NULL, '2026-04-29 06:30:26'),
(6, 4, 10, 5, NULL, '2026-04-29 06:30:43');

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
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `semester_locked` tinyint(1) NOT NULL DEFAULT 0,
  `pts_locked` tinyint(1) NOT NULL DEFAULT 0,
  `pas_locked` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters_state`
--

INSERT INTO `semesters_state` (`id`, `academic_year_id`, `semester`, `semester_locked`, `pts_locked`, `pas_locked`) VALUES
(1, 1, 'ganjil', 0, 0, 0),
(2, 1, 'genap', 1, 1, 1),
(14, 2, 'ganjil', 0, 0, 0),
(15, 2, 'genap', 0, 0, 0),
(30, 9, 'ganjil', 0, 0, 0),
(31, 9, 'genap', 0, 0, 0),
(32, 10, 'ganjil', 0, 0, 0),
(33, 10, 'genap', 0, 0, 0),
(34, 11, 'ganjil', 0, 0, 0),
(35, 11, 'genap', 0, 0, 0),
(36, 12, 'ganjil', 0, 0, 0),
(37, 12, 'genap', 0, 0, 0);

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
  `jenjang` enum('SD','SMP','SMA') NOT NULL,
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
(1, 9, '0123456701', '0010001', 'Ahmad Fauzi', 'SD', 1, 'L', 'Jakarta', '2018-02-01', 'Jl. Anggrek 1', 'Bapak Fauzi', 'Ibu Fauzi', NULL, NULL, NULL, 'uploads/students/stu_1cddad62a68eeff9.png', 1, '2026-04-28 22:38:05', '2026-06-20 08:44:17', '2026-06-20 12:52:31'),
(2, 9, '0123456702', '0010002', 'Siti Aisyah', 'SD', 1, 'P', 'Bandung', '2018-04-12', 'Jl. Mawar 2', 'Bapak Hendra', 'Ibu Diana', NULL, NULL, NULL, NULL, 1, '2026-04-28 22:38:05', '2026-06-20 08:44:17', NULL),
(3, 9, '0123456703', '0070001', 'Bagas Pratama', 'SMP', 7, 'L', 'Bogor', '2012-09-20', 'Jl. Melati 3', 'Bapak Eko', 'Ibu Sri', NULL, NULL, NULL, NULL, 1, '2026-04-28 22:38:05', '2026-06-20 08:44:17', NULL),
(4, 9, '0123456704', '0100001', 'Citra Lestari', 'SMA', 10, 'P', 'Depok', '2009-11-30', 'Jl. Kenanga 4', 'Bapak Hadi', 'Ibu Wati', NULL, NULL, NULL, NULL, 1, '2026-04-28 22:38:05', '2026-06-20 08:44:17', NULL),
(9, 9, '0123456798', '0010098', 'Aga Sayoga', 'SD', 3, 'L', 'Badung', '2017-06-05', 'jh', 'agag', 'gsgs', 'bud', 'bad', '89898', NULL, 1, '2026-04-29 02:30:11', '2026-06-20 08:44:17', NULL),
(11, 9, '0123456711', '0010289', 'Arjuna', 'SD', 3, 'L', 'Denpasar', '2017-02-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-29 06:29:00', '2026-06-20 08:44:17', NULL),
(12, 9, '0123456799', '3456799', 'Putri Indah Swari 12', 'SD', 1, 'P', 'Denpasar', '2013-01-30', 'sad', 'sad', 'asd', 'asd', 'asd', 'ads', NULL, 1, '2026-04-29 06:35:03', '2026-06-20 08:44:17', NULL),
(13, 9, '0123456999', '3456788', 'Abigail', 'SD', 3, 'P', 'Bandung', '2015-02-01', 'jalan', 'asd', 'asd', 'asd', 'ads', '09123123', NULL, 1, '2026-04-29 06:50:02', '2026-06-20 08:44:17', NULL),
(14, 9, '1234567890', '1234567', 'Erling', 'SD', 3, 'L', 'Denpasar', '2019-02-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-06-20 08:22:05', '2026-06-20 08:44:17', NULL),
(15, 12, '1234567899', '1234566', 'Ahmad Fauzi', 'SD', 2, 'L', 'Denpasar', '2019-01-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-06-20 09:06:05', '2026-06-20 09:06:05', NULL),
(16, 12, '1234567888', '1234569', 'Hallland fauzi ui', 'SD', 2, 'L', 'Jakarta', '2019-04-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-06-20 09:06:40', '2026-06-20 09:06:40', NULL);

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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `academic_year_id`, `kode`, `nama`, `category_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'PAI', 'Pendidikan Agama Islam', 1, '2026-04-28 22:38:05', '2026-06-20 07:40:16', NULL),
(2, 1, 'PKN', 'PPKn', 1, '2026-04-28 22:38:05', '2026-06-20 07:40:16', NULL),
(3, 1, 'BIN', 'Bahasa Indonesia', 1, '2026-04-28 22:38:05', '2026-06-20 07:40:16', NULL),
(4, 1, 'MTK', 'Matematika', 1, '2026-04-28 22:38:05', '2026-06-20 07:40:16', NULL),
(5, 1, 'IPA', 'Ilmu Pengetahuan Alam', 1, '2026-04-28 22:38:05', '2026-06-20 07:40:16', NULL),
(6, 1, 'IPS', 'Ilmu Pengetahuan Sosial', 1, '2026-04-28 22:38:05', '2026-06-20 07:40:16', NULL),
(7, 1, 'BIG', 'Bahasa Inggris', 1, '2026-04-28 22:38:05', '2026-06-20 07:40:16', NULL),
(8, 1, 'SBK', 'Seni Budaya & Prakarya', 2, '2026-04-28 22:38:05', '2026-06-20 07:40:16', NULL),
(9, 1, 'PJK', 'PJOK', 2, '2026-04-28 22:38:05', '2026-06-20 07:40:16', NULL),
(10, 1, 'T1', 'CGV - Grafis', 2, '2026-04-29 01:28:09', '2026-06-20 07:40:16', NULL),
(11, 1, 'EKM', 'Ekonomi', 2, '2026-04-29 06:27:02', '2026-06-20 07:40:16', NULL),
(12, 1, 'AKT', 'Akutansi', 2, '2026-04-29 06:27:15', '2026-06-20 07:40:16', NULL),
(13, 1, 'VDG', 'Videografi', 2, '2026-04-30 01:18:30', '2026-06-20 07:40:16', NULL),
(14, 1, 'CDN', 'Coding', 2, '2026-05-05 02:23:35', '2026-06-20 07:40:16', NULL),
(16, 9, 'BIG', 'Bahasa Inggris', 1, '2026-06-20 07:41:44', '2026-06-20 07:41:44', NULL),
(25, 12, 'BIG', 'Bahasa Inggris', 15, '2026-06-20 07:52:50', '2026-06-20 07:52:50', NULL),
(26, 9, 'RQ', 'Jailudin', 16, '2026-06-20 09:08:40', '2026-06-20 09:08:40', NULL);

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
(6, 9, 'CGV'),
(1, 9, 'Kelompok A (Wajib Umum)'),
(2, 9, 'Kelompok B (Wajib)'),
(14, 9, 'kuy'),
(3, 9, 'Muatan Lokal'),
(16, 9, 'OIOI'),
(5, 9, 'sdsd'),
(8, 9, 'Tech'),
(4, 9, 'Teknologi'),
(11, 11, 'CGV'),
(12, 11, 'Tech'),
(15, 12, 'Bahasa');

-- --------------------------------------------------------

--
-- Table structure for table `subject_jenjang_map`
--

CREATE TABLE `subject_jenjang_map` (
  `subject_id` int(10) UNSIGNED NOT NULL,
  `jenjang` enum('SD','SMP','SMA') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_jenjang_map`
--

INSERT INTO `subject_jenjang_map` (`subject_id`, `jenjang`) VALUES
(1, 'SD'),
(1, 'SMP'),
(1, 'SMA'),
(2, 'SD'),
(2, 'SMP'),
(2, 'SMA'),
(3, 'SD'),
(3, 'SMP'),
(3, 'SMA'),
(4, 'SD'),
(4, 'SMP'),
(4, 'SMA'),
(5, 'SD'),
(5, 'SMP'),
(5, 'SMA'),
(6, 'SD'),
(6, 'SMP'),
(6, 'SMA'),
(7, 'SD'),
(7, 'SMP'),
(7, 'SMA'),
(8, 'SD'),
(8, 'SMP'),
(8, 'SMA'),
(9, 'SD'),
(9, 'SMP'),
(9, 'SMA'),
(10, 'SD'),
(10, 'SMP'),
(10, 'SMA'),
(11, 'SMA'),
(12, 'SMA'),
(13, 'SD'),
(13, 'SMP'),
(13, 'SMA'),
(14, 'SD'),
(14, 'SMP'),
(16, 'SD'),
(25, 'SD'),
(26, 'SD'),
(26, 'SMP'),
(26, 'SMA');

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
(1, 1, 7, 'ganjil', 'T1', 'Bab 1 - Grammar Basic', 'pengetahuan', 'tugas', 100.00, NULL, 1, '2026-04-29 01:22:19', '2026-04-29 04:43:07', NULL, '[\"pengetahuan\"]'),
(2, 3, 3, 'ganjil', 'B1', 'Bab 1 - Grammar Basic', 'sikap', 'praktek', 4.00, NULL, 1, '2026-04-29 01:32:04', '2026-04-29 04:43:07', NULL, '[\"sikap\"]'),
(3, 2, 7, 'genap', 'T5', 'MATH ENG', 'keterampilan', 'tugas', 1.00, NULL, 1, '2026-04-29 04:43:16', '2026-04-29 04:43:16', NULL, '[\"keterampilan\"]'),
(4, 2, 7, 'genap', 'T7', 'BAHASA GERMAN', 'keterampilan', 'tugas', 1.00, NULL, 1, '2026-04-29 04:43:32', '2026-04-29 04:43:32', NULL, '[\"keterampilan\"]'),
(5, 2, 7, 'genap', 'T2', 'DAMN', 'keterampilan', 'tugas', 1.00, NULL, 1, '2026-04-29 04:50:42', '2026-04-29 04:50:42', NULL, '[\"keterampilan\"]'),
(6, 3, 3, 'ganjil', 'T5', 'ASEK', 'keterampilan', 'tugas', 1.00, NULL, 1, '2026-04-29 05:07:57', '2026-04-29 05:07:57', NULL, '[\"keterampilan\"]'),
(7, 2, 7, 'ganjil', 'kj', 'kjkmk', 'keterampilan', 'tugas', 1.00, NULL, 1, '2026-04-29 05:11:23', '2026-04-29 05:11:23', NULL, '[\"keterampilan\"]'),
(8, 2, 7, 'ganjil', 'rq', 'ASEK', 'keterampilan', 'tugas', 1.00, 'ker', 1, '2026-04-29 05:42:57', '2026-04-29 05:42:57', NULL, '[\"keterampilan\"]'),
(9, 3, 7, 'ganjil', 'km', 'bab', 'sikap', 'tugas', 1.00, NULL, 1, '2026-04-29 05:53:06', '2026-04-29 05:53:06', NULL, '[\"sikap\",\"pengetahuan\",\"keterampilan\"]'),
(10, 4, 10, 'ganjil', 'EK', 'BAB1', 'sikap', 'tugas', 1.00, NULL, 1, '2026-04-29 06:31:11', '2026-04-29 06:31:11', NULL, '[\"sikap\",\"pengetahuan\",\"keterampilan\"]'),
(11, 3, 7, 'ganjil', 'T5', 'Nirwana', 'sikap', 'tugas', 1.00, NULL, 1, '2026-04-30 01:26:06', '2026-04-30 01:26:06', NULL, '[\"sikap\",\"pengetahuan\",\"keterampilan\"]');

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
(1, 6, '198001012010012001', NULL, '2026-04-28 22:38:05'),
(2, 7, '198501012010012002', NULL, '2026-04-28 22:38:05'),
(3, 8, '8384341', '087776666182', '2026-04-29 00:49:05'),
(4, 9, NULL, NULL, '2026-04-29 00:56:40'),
(5, 10, '198001012010012033', '08777333231', '2026-04-29 00:58:10'),
(6, 11, '198001012010012044', '01293', '2026-04-29 01:29:20'),
(7, 12, '1231241241', '087773423', '2026-04-29 06:27:51'),
(8, 13, NULL, NULL, '2026-05-05 02:24:14'),
(9, 14, '1234567890', NULL, '2026-06-20 07:58:58'),
(10, 15, '1122334455', NULL, '2026-06-20 08:05:44'),
(11, 16, '1234512345', NULL, '2026-06-20 08:12:02'),
(12, 17, '1231231231', NULL, '2026-06-20 08:20:40'),
(13, 18, '1234567999', NULL, '2026-06-20 09:07:23');

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
(3, 2),
(5, 4),
(5, 5),
(5, 6),
(6, 5),
(6, 6),
(7, 6),
(7, 11),
(7, 12),
(8, 7),
(8, 12);

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
(12, 9),
(13, 9);

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
  `jenjang` enum('SD','SMP','SMA') DEFAULT NULL,
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

INSERT INTO `users` (`id`, `niy`, `nama`, `email`, `password_hash`, `role`, `jenjang`, `is_wali`, `is_active`, `must_change_pw`, `last_login_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '1990010001', 'Administrator', 'admin@sekolah.id', '$2y$10$.W0EMeg.g8xrBM88WdasSOuZIdUJuG6kXsOq1nJxM4f4MEPttKYEu', 'administrator', NULL, 0, 1, 1, '2026-06-20 12:31:05', '2026-04-28 22:38:05', '2026-06-20 04:31:05', NULL),
(2, '1990020002', 'Operator Admin', 'operator@sekolah.id', '$2y$10$Sb2RdVhIyZ8XhCiGt8G0yelu3FGZgJcxAznxg/Oh9iUu11pURhYyi', 'admin', NULL, 0, 1, 1, '2026-04-29 21:43:29', '2026-04-28 22:38:05', '2026-04-29 13:43:29', NULL),
(3, '1990030003', 'Kepsek SD', 'kepsek.sd@sekolah.id', '$2y$10$gu7UgkZS23mqajpPGJSeLuoGIVe/fEsY5kdnyBcEQrZ5TNHqXzaci', 'kepsek', 'SD', 0, 1, 1, '2026-04-29 21:46:58', '2026-04-28 22:38:05', '2026-04-29 13:46:58', NULL),
(4, '1990040004', 'Kepsek SMP', 'kepsek.smp@sekolah.id', '$2y$10$x/tPze8vu1HLLrq09F.4vuOUm/xylaHR4qE8FuGimIM8YCfQznG36', 'kepsek', 'SMP', 0, 1, 1, NULL, '2026-04-28 22:38:05', '2026-04-28 22:40:26', NULL),
(5, '1990050005', 'Kepsek SMA', 'kepsek.sma@sekolah.id', '$2y$10$bhgo2W1soR6KnKQmzBrD2O4gpSTlyDZxrxfuDGH1T9J5TFKBva.Ty', 'kepsek', 'SMA', 0, 1, 1, NULL, '2026-04-28 22:38:05', '2026-04-28 22:40:26', NULL),
(6, '1990060006', 'Bu Sari (Wali 1A)', 'sari@sekolah.id', '$2y$10$JqACgdix1wpUWrLZMLrW3.7zEz8fR2YbdA6QXML0Geos94ckMAMba', 'guru', NULL, 1, 1, 1, '2026-06-02 16:24:43', '2026-04-28 22:38:05', '2026-06-02 08:24:43', NULL),
(7, '1990070007', 'Pak Budi (Guru MTK)', 'budi@sekolah.id', '$2y$10$0iNItqmLBEaX0hN9Z91TEubEDN0rdHlTXzM8dX9KqCxpK04n2KgCK', 'guru', NULL, 0, 1, 1, '2026-04-29 21:53:32', '2026-04-28 22:38:05', '2026-04-29 13:53:32', NULL),
(8, '1990010089', 'Chandra', 'kmjartha@gmail.com', '$2y$10$anxIk24Jz26wMBjUGTZfGOkFDdU6Gvp.v/sPwrMwHw0FOZoQZi/Bi', 'guru', NULL, 0, 1, 1, NULL, '2026-04-29 00:49:05', '2026-04-29 00:49:05', NULL),
(9, '1990010099', 'Made Geledik', 'madegeledik@gmail.com', '$2y$10$8.lxWQnI2fsixHJ/GO/2yOP7rxodEnXMT5BLvPcQbIlk2vgl7FhH.', 'guru', NULL, 0, 1, 1, NULL, '2026-04-29 00:56:40', '2026-04-29 00:56:40', NULL),
(10, '1990010081', 'Artha', 'artha@gmail.cm', '$2y$10$t1AQrnFG9yAEMN2xlD472e5wuX8JDcpjfaLDR1L7pBNWRk23Ui8uq', 'guru', NULL, 1, 1, 1, '2026-06-02 13:44:26', '2026-04-29 00:58:10', '2026-06-02 05:44:26', NULL),
(11, '1990010078', 'William', 'wiliam@ilmain.com', '$2y$10$2dj6E4zbx8LbdKTOpiysXuE5uiGY.DiJzzSpY.D54wSe9VzHIs7cC', 'guru', NULL, 0, 1, 1, NULL, '2026-04-29 01:29:20', '2026-04-29 01:29:20', NULL),
(12, '1990010100', 'Episman Gea', 'epismangea@gmail.com', '$2y$10$KaasCt6QBFGaVo5GLbGK3udeZ3hTuNeQ9ZtzLL46P2PPc/2KShT5a', 'guru', NULL, 1, 1, 1, NULL, '2026-04-29 06:27:51', '2026-04-29 06:27:51', NULL),
(13, '1990010233', 'Abigail Endra', NULL, '$2y$10$63lnC8IA1mNKmPJS.18BlO5nEHUAmbunwPiWcUnEOVT9EouYwg5mG', 'guru', NULL, 0, 1, 1, NULL, '2026-05-05 02:24:14', '2026-05-05 02:24:14', NULL),
(14, '1234567890', 'Halland', NULL, '$2y$10$7OcrfvLmhMlvxsGPCRjtaOQQNYWGf.XT77tPPVHwsXXc0glWemp0e', 'guru', NULL, 0, 1, 1, NULL, '2026-06-20 07:58:58', '2026-06-20 07:58:58', NULL),
(15, '1122334455', 'Braut Halland', NULL, '$2y$10$Wny7ZHHoTFfwjVo730wQAeyzMQ8dvn0H2WQNr/hGT0Unv0Hl4O9p2', 'guru', NULL, 0, 1, 1, NULL, '2026-06-20 08:05:44', '2026-06-20 08:05:44', NULL),
(16, '1234512345', 'Halland Fauzi', NULL, '$2y$10$ZHMn8WPwO3XWDXTVRWSZPuFdTJKA.YbOfWNWIwxCBqc86WMNFk/Uu', 'guru', NULL, 0, 1, 1, NULL, '2026-06-20 08:12:02', '2026-06-20 08:12:02', NULL),
(17, '1231231231', 'Lando Norris', NULL, '$2y$10$Wv8FpHdKVjnAtxLxu.2hvO5Crteo.Gcm19DVq6O7UNAQ5xGfPmW82', 'guru', NULL, 0, 1, 1, NULL, '2026-06-20 08:20:40', '2026-06-20 08:20:40', NULL),
(18, '1234567999', 'Arjuna', NULL, '$2y$10$ovxievpdOh4vNNSdkR.Raea9tA1Gk3wx2wqWuLY1oh8clXFydHTjS', 'guru', NULL, 0, 1, 1, NULL, '2026-06-20 09:07:23', '2026-06-20 09:07:23', NULL);

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
-- Dumping data for table `wali_notes`
--

INSERT INTO `wali_notes` (`id`, `rombel_id`, `student_id`, `semester`, `period_kind`, `catatan`) VALUES
(1, 3, 1, 'ganjil', 'PTS', 'mantap jiwa'),
(2, 3, 2, 'ganjil', 'PTS', 'jeg mantep'),
(3, 2, 13, 'genap', 'PTS', NULL),
(4, 2, 9, 'genap', 'PTS', 'Lorem ipsum dolor sit amet is placeholder text used in design and publishing to demonstrate visual layouts, derived from Cicero\'s 45 B.C. Latin text on ethics. It is used to prevent the focus from being on the text content itself and has been industry standard since the 1500s.'),
(5, 2, 11, 'genap', 'PTS', NULL);

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
  ADD UNIQUE KEY `uq_aspect_year_jenjang_nama` (`academic_year_id`,`jenjang`,`nama`),
  ADD KEY `ix_aspect_year_jenjang` (`academic_year_id`,`jenjang`);

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
  ADD KEY `ix_elective_year` (`academic_year_id`);

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
  ADD KEY `fk_ec_t` (`teacher_id`);

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
  ADD KEY `fk_fg_u` (`reviewed_by`);

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
  ADD KEY `fk_subj_cat` (`category_id`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=295;

--
-- AUTO_INCREMENT for table `character_aspects`
--
ALTER TABLE `character_aspects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `character_evaluations`
--
ALTER TABLE `character_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `electives`
--
ALTER TABLE `electives`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `elective_assignments`
--
ALTER TABLE `elective_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `elective_classes`
--
ALTER TABLE `elective_classes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `extracurriculars`
--
ALTER TABLE `extracurriculars`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `extracurricular_grades`
--
ALTER TABLE `extracurricular_grades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `final_grades`
--
ALTER TABLE `final_grades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `general_evaluations`
--
ALTER TABLE `general_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `grades_daily`
--
ALTER TABLE `grades_daily`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `grade_descriptions`
--
ALTER TABLE `grade_descriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kkm_settings`
--
ALTER TABLE `kkm_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `parents_auth`
--
ALTER TABLE `parents_auth`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `parent_remember_tokens`
--
ALTER TABLE `parent_remember_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_signatures`
--
ALTER TABLE `report_signatures`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `report_templates`
--
ALTER TABLE `report_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rombel`
--
ALTER TABLE `rombel`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rombel_subject_teachers`
--
ALTER TABLE `rombel_subject_teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `semesters_state`
--
ALTER TABLE `semesters_state`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `subject_categories`
--
ALTER TABLE `subject_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `subject_topics`
--
ALTER TABLE `subject_topics`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_remember_tokens`
--
ALTER TABLE `user_remember_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wali_notes`
--
ALTER TABLE `wali_notes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  ADD CONSTRAINT `fk_el_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_ec_t` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `fk_subj_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

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
