-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 29, 2026 at 08:32 PM
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
-- Database: `ilmify`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` enum('info','warning','quest') DEFAULT 'info',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `type`, `created_by`, `created_at`) VALUES
(3, 'Welcome', 'Feel free to explore this dungeon.', 'info', 5, '2026-01-14 08:07:33');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('present','absent','excused') DEFAULT 'present',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `session_id`, `student_id`, `status`, `joined_at`) VALUES
(2, 2, 13, 'present', '2026-01-29 07:52:18'),
(3, 3, 13, 'present', '2026-01-29 15:05:21'),
(4, 6, 13, 'present', '2026-01-29 19:05:47'),
(5, 11, 13, 'present', '2026-01-29 19:22:08');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `is_live` tinyint(1) DEFAULT 0,
  `current_session_id` int(11) DEFAULT NULL,
  `current_week_id` int(11) DEFAULT NULL,
  `current_session_content_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `class_name`, `subject_id`, `teacher_id`, `is_live`, `current_session_id`, `current_week_id`, `current_session_content_id`) VALUES
(3, 'kontol 3 segi', 3, 29, 1, 6, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `class_material_overrides`
--

CREATE TABLE `class_material_overrides` (
  `override_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `item_type` enum('material','video','quiz') NOT NULL,
  `item_id` int(11) NOT NULL,
  `is_hidden` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_material_overrides`
--

INSERT INTO `class_material_overrides` (`override_id`, `class_id`, `item_type`, `item_id`, `is_hidden`) VALUES
(1, 0, '', 10, 1);

-- --------------------------------------------------------

--
-- Table structure for table `class_schedule`
--

CREATE TABLE `class_schedule` (
  `schedule_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `schedule_start_date` date DEFAULT NULL,
  `schedule_end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_schedule`
--

INSERT INTO `class_schedule` (`schedule_id`, `class_id`, `day_of_week`, `start_time`, `end_time`, `schedule_start_date`, `schedule_end_date`) VALUES
(3, 3, 'Thursday', '15:52:00', '17:52:00', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `class_id`, `enrolled_at`) VALUES
(5, 13, 3, '2026-01-29 07:51:20');

-- --------------------------------------------------------

--
-- Table structure for table `live_sessions`
--

CREATE TABLE `live_sessions` (
  `session_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `meeting_link` text NOT NULL,
  `status` enum('active','ended') DEFAULT 'active',
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `live_sessions`
--

INSERT INTO `live_sessions` (`session_id`, `class_id`, `schedule_id`, `teacher_id`, `meeting_link`, `status`, `started_at`, `ended_at`) VALUES
(2, 3, 3, 29, '', 'active', '2026-01-29 15:52:01', '2026-01-29 22:52:12'),
(3, 3, 3, 29, '', 'active', '2026-01-29 23:05:17', '2026-01-30 01:46:25'),
(4, 3, 3, 29, '', 'active', '2026-01-30 01:46:25', '2026-01-30 01:53:02'),
(5, 3, 3, 29, '', 'active', '2026-01-30 02:04:30', '2026-01-30 02:04:39'),
(6, 3, 3, 29, '', 'active', '2026-01-30 02:24:22', '2026-01-30 03:08:37'),
(7, 3, 3, 29, '', 'active', '2026-01-30 03:11:39', '2026-01-30 03:14:16'),
(8, 3, 3, 29, '', 'active', '2026-01-30 03:14:36', '2026-01-30 03:14:48'),
(9, 3, 3, 29, '', 'active', '2026-01-30 03:14:48', '2026-01-30 03:14:57'),
(10, 3, 3, 29, '', 'active', '2026-01-30 03:14:53', '2026-01-30 03:15:03'),
(11, 3, 3, 29, '', 'active', '2026-01-30 03:21:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `material_id` int(11) NOT NULL,
  `week_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('note','reading_session') DEFAULT 'note',
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `thumbnail` varchar(255) DEFAULT 'default_note.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`material_id`, `week_id`, `class_id`, `title`, `description`, `type`, `file_path`, `uploaded_by`, `created_at`, `thumbnail`) VALUES
(5, NULL, NULL, 'Learning Colors', 'Colors are all around us!\r\nRed, blue, yellow, green, and more make our world bright and fun.\r\nWe can learn colors by looking at toys, fruits, clothes, and pictures.\r\nLet’s name the colors, point to them, and have fun learning together! ????', 'note', '1768414152_note.pdf', 5, '2026-01-14 18:09:12', 'default_note.png'),
(6, NULL, NULL, 'Learning Shapes ⭐', 'Shapes help us understand things around us.\r\nA circle is round like a ball, a square has four equal sides, and a triangle has three corners.\r\nWe can find shapes in toys, houses, and pictures.\r\nLet’s explore shapes and have fun learning together! ????', 'note', '1768414816_note.pdf', 5, '2026-01-14 18:20:16', 'default_note.png'),
(8, NULL, NULL, 'Learning Emotions', 'Emotions tell us how we feel.\r\nWe can feel happy, sad, angry, scared, or excited.\r\nOur faces and actions show our emotions.\r\nLearning emotions helps us understand ourselves and others.\r\nIt’s okay to feel different emotions every day! ????', 'note', '1768418761_note.pdf', 5, '2026-01-14 19:26:01', 'default_note.png'),
(9, NULL, NULL, 'Learning Fruits', 'Fruits are healthy and yummy.\r\nThey come in many colors, shapes, and tastes.\r\nApples, bananas, oranges, and grapes help our bodies grow strong.\r\nLet’s learn the names of fruits and enjoy eating them every day!', 'note', '1768418954_note.pdf', 5, '2026-01-14 19:29:14', 'default_note.png'),
(10, 1, NULL, 'cdsfcds', 'sfsafd', 'note', '697b90c675f72_master.pdf', 5, '2026-01-29 16:54:30', 'default_note.png');

-- --------------------------------------------------------

--
-- Table structure for table `parent_alerts`
--

CREATE TABLE `parent_alerts` (
  `alert_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `alert_type` varchar(50) DEFAULT 'general',
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_students`
--

CREATE TABLE `parent_students` (
  `parent_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT 'manual',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pushed_content_log`
--

CREATE TABLE `pushed_content_log` (
  `log_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `content_type` enum('material','video','quiz') NOT NULL,
  `content_id` int(11) NOT NULL,
  `pushed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pushed_content_log`
--

INSERT INTO `pushed_content_log` (`log_id`, `session_id`, `content_type`, `content_id`, `pushed_at`) VALUES
(1, 2, 'material', 5, '2026-01-29 07:52:50'),
(2, 2, 'material', 6, '2026-01-29 08:02:29'),
(3, 2, 'material', 5, '2026-01-29 14:48:44'),
(4, 2, 'material', 6, '2026-01-29 14:50:11'),
(5, 3, 'material', 6, '2026-01-29 15:05:26'),
(6, 3, 'material', 9, '2026-01-29 15:05:54'),
(7, 3, 'video', 2, '2026-01-29 15:06:41'),
(8, 3, 'quiz', 14, '2026-01-29 15:07:07'),
(9, 3, 'material', 5, '2026-01-29 15:07:36'),
(10, 3, 'video', 2, '2026-01-29 15:14:23'),
(11, 3, 'video', 2, '2026-01-29 15:14:37'),
(12, 3, 'material', 5, '2026-01-29 15:15:10'),
(13, 3, 'quiz', 14, '2026-01-29 15:15:18'),
(14, 3, 'quiz', 13, '2026-01-29 15:15:28'),
(15, 3, 'material', 5, '2026-01-29 15:16:01'),
(16, 3, 'video', 2, '2026-01-29 15:16:13'),
(17, 3, 'material', 5, '2026-01-29 15:16:33'),
(18, 3, 'quiz', 14, '2026-01-29 15:16:58'),
(19, 3, 'material', 5, '2026-01-29 15:19:17'),
(20, 3, 'video', 2, '2026-01-29 15:19:25'),
(21, 3, 'material', 5, '2026-01-29 15:19:39'),
(22, 6, 'material', 5, '2026-01-29 19:05:51'),
(23, 11, 'material', 10, '2026-01-29 19:21:18'),
(24, 11, 'video', 12, '2026-01-29 19:22:15'),
(25, 11, 'quiz', 15, '2026-01-29 19:22:46'),
(26, 11, 'material', 10, '2026-01-29 19:22:54');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('text','image','audio','input') NOT NULL DEFAULT 'text',
  `media_file` varchar(255) DEFAULT NULL,
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `correct_option` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `quiz_id`, `question_text`, `question_type`, `media_file`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`) VALUES
(20, 13, 'Which fruit is yellow and long?', 'image', NULL, '6967f2c26068f_opt.jpg', '6967f2c2608f6_opt.png', '6967f2c260b31_opt.png', '6967f2c260d7d_opt.jpg', 'c'),
(21, 13, ' An apple is ______ in color . ', 'input', NULL, '', '', '', '', 'red'),
(22, 13, 'What fruit is this?', 'audio', '6967f4ca634cc_media.ogg', 'Strawberry', 'Banana', 'Apple', 'Mango', 'c'),
(23, 13, 'Which fruit is small and purple?', 'text', '6967f5c074b45_media.jpg', 'Grape', 'Pear', 'Orange', 'Banana', 'a'),
(24, 13, 'Listen to the audio carefully', 'audio', '6967f70f8c9be_media.ogg', 'Watermelon', 'Durian', 'Guava', 'Blueberry', 'b');

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `quiz_id` int(11) NOT NULL,
  `week_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `thumbnail` varchar(255) DEFAULT 'default_quiz.png',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `class_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`quiz_id`, `week_id`, `title`, `description`, `file_path`, `thumbnail`, `created_by`, `created_at`, `class_id`) VALUES
(13, NULL, 'Yummy Fruits Adventure', 'Let’s play and learn about fruits!\r\nLook at the pictures, name the fruits, and choose the right answer.\r\nThis fun quiz helps little ones learn fruit names, colors, and shapes.\r\nLearning fruits is yummy and fun! ????????', 'take_quiz.php', 'default_quiz.png', 5, '2026-01-29 18:16:41', NULL),
(14, NULL, 'animal', '', 'take_quiz.php', 'default_quiz.png', 5, '2026-01-29 18:16:41', NULL),
(15, 1, 'sfdsa', 'safsaf', 'take_quiz.php', 'default_quiz.png', 5, '2026-01-29 18:16:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_results`
--

CREATE TABLE `quiz_results` (
  `result_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `total_score` int(11) NOT NULL,
  `attempt_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `xp_earned` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_results`
--

INSERT INTO `quiz_results` (`result_id`, `student_id`, `quiz_id`, `score`, `total_score`, `attempt_date`, `xp_earned`) VALUES
(16, 19, 13, 100, 0, '2026-01-14 20:06:25', 0),
(17, 19, 13, 80, 0, '2026-01-14 20:23:38', 0),
(18, 23, 13, 100, 0, '2026-01-15 04:56:10', 0),
(19, 19, 13, 100, 0, '2026-01-15 07:04:33', 0),
(20, 13, 13, 100, 0, '2026-01-19 08:45:38', 0);

-- --------------------------------------------------------

--
-- Table structure for table `student_progress`
--

CREATE TABLE `student_progress` (
  `progress_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `current_level` int(11) DEFAULT 1,
  `current_xp` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_progress`
--

INSERT INTO `student_progress` (`progress_id`, `student_id`, `current_level`, `current_xp`) VALUES
(7, 13, 4, 290),
(10, 19, 4, 60),
(18, 21, 2, 10),
(19, 23, 2, 30),
(20, 25, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `student_reads`
--

CREATE TABLE `student_reads` (
  `read_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_reads`
--

INSERT INTO `student_reads` (`read_id`, `student_id`, `material_id`, `read_at`) VALUES
(3, 21, 3, '2026-01-14 06:17:35'),
(4, 19, 3, '2026-01-14 12:29:21'),
(5, 19, 4, '2026-01-14 12:30:58'),
(11, 13, 4, '2026-01-14 16:21:12'),
(12, 19, 9, '2026-01-14 20:24:16'),
(13, 19, 8, '2026-01-14 20:24:34'),
(14, 19, 6, '2026-01-14 20:25:08'),
(15, 23, 9, '2026-01-15 04:56:41'),
(16, 23, 8, '2026-01-15 05:24:52'),
(17, 23, 6, '2026-01-15 05:25:09'),
(18, 23, 5, '2026-01-15 05:25:25'),
(21, 13, 9, '2026-01-19 08:43:59'),
(22, 13, 5, '2026-01-29 05:27:08'),
(36, 13, 10, '2026-01-29 19:22:09');

-- --------------------------------------------------------

--
-- Table structure for table `student_task_claims`
--

CREATE TABLE `student_task_claims` (
  `claim_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `claimed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_task_claims`
--

INSERT INTO `student_task_claims` (`claim_id`, `student_id`, `task_id`, `claimed_at`) VALUES
(5, 13, 1, '2026-01-13 21:03:50'),
(6, 13, 2, '2026-01-13 21:03:52'),
(7, 13, 5, '2026-01-13 21:03:54'),
(8, 13, 6, '2026-01-13 21:03:55'),
(10, 21, 5, '2026-01-14 06:18:22'),
(11, 21, 1, '2026-01-14 06:19:08'),
(12, 19, 5, '2026-01-14 12:31:57'),
(13, 19, 1, '2026-01-14 12:31:59'),
(14, 19, 2, '2026-01-14 18:15:48'),
(15, 23, 5, '2026-01-15 04:59:48'),
(16, 23, 1, '2026-01-15 04:59:51');

-- --------------------------------------------------------

--
-- Table structure for table `student_video_views`
--

CREATE TABLE `student_video_views` (
  `view_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `watched_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `progress_percent` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT 'default_subject.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `name`, `description`, `thumbnail`, `created_at`) VALUES
(3, 'hehe', 'gfyfu', 'default_subject.png', '2026-01-29 07:50:42'),
(4, 'math', 'fsdfds', '697b9257a16a5_subject.png', '2026-01-29 17:01:11'),
(5, 'cs', 'ccasfsa', 'default_subject.png', '2026-01-29 17:02:05');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `task_id` int(11) NOT NULL,
  `task_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `xp_reward` int(11) DEFAULT 10,
  `criteria_type` enum('quiz','note_read') NOT NULL DEFAULT 'quiz',
  `criteria_count` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`task_id`, `task_name`, `description`, `xp_reward`, `criteria_type`, `criteria_count`) VALUES
(1, 'Novice Challenger', 'Complete your first Quiz.', 20, 'quiz', 1),
(2, 'Apprentice Scholar', 'Complete 3 Quizzes.', 50, 'quiz', 3),
(3, 'Quiz Master', 'Complete 10 Quizzes.', 150, 'quiz', 10),
(4, 'Legendary Sage', 'Complete 20 Quizzes to prove your dominance.', 500, 'quiz', 20),
(5, 'Bookworm I', 'Read 1 Study Note.', 10, 'note_read', 1),
(6, 'Bookworm II', 'Read 5 Study Notes.', 40, 'note_read', 5),
(7, 'Library Guardian', 'Read 15 Study Notes.', 100, 'note_read', 15);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_remarks`
--

CREATE TABLE `teacher_remarks` (
  `remark_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `remark_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `role` enum('admin','parent','student','teacher') NOT NULL,
  `subscription_expiry` date DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT 'default_avatar.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login_at` datetime DEFAULT NULL,
  `reset_token_hash` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `phone_number`, `role`, `subscription_expiry`, `profile_picture`, `created_at`, `last_login_at`, `reset_token_hash`, `reset_token_expires_at`) VALUES
(5, 'admin', '$2y$10$3g3cN5rRMPlKXJ8pJVFpf.Z7xypzfiB//ZXQzhtzwbad8Q5bq6mxu', 'System Administrator', NULL, NULL, 'admin', NULL, 'default_avatar.png', '2026-01-10 19:24:11', NULL, NULL, NULL),
(13, 'ayong', '$2y$10$k3RnQ25Syh2C./4qN1Ac1OpZue0kqIyPCMrUgZ9Q6EYvoFyzG9BHG', 'NOR SURIZA BINTI ZABIDIN', 'dzulamri070@gmail.com', NULL, 'student', NULL, 'default_avatar.png', '2026-01-13 20:18:01', NULL, NULL, NULL),
(14, 'ayong_parent', '$2y$10$xuuCO6tHXZ.Y8c3AlVmZxeCZ5bdqcQddS8VIDckjDP/JK2qtDDx8i', 'Parent of NOR SURIZA BINTI ZABIDIN', 'parent_dzulamri070@gmail.com', NULL, 'parent', NULL, 'default_avatar.png', '2026-01-13 20:18:01', NULL, NULL, NULL),
(19, 'dzulss', '$2y$10$sgMw80fomHjvEIiNGkkfC.LVxIFM1ykTO4.oOPk.CC05PIOtG8yrK', 'DZUL AMRI BIN ZABIDIN', 'dzulamri070@gmail.com', NULL, 'student', NULL, '1768495411_19.jpg', '2026-01-13 22:05:22', NULL, NULL, NULL),
(20, 'dzulss_parent', '$2y$10$I6.cHKrwJ4FiTK77vE/wm.MJwTXpl4z3i3bXXvPga6SDlWAGfAZ9u', 'Parent of DZUL AMRI BIN ZABIDIN', 'parent_dzulamri070@gmail.com', NULL, 'parent', NULL, 'default_avatar.png', '2026-01-13 22:05:22', NULL, NULL, NULL),
(21, 'farish', '$2y$10$Noqb5roVeHGkHnvpX1E8BuZmHmdlXEroynaF1pFlMmoh3jd4YTTH.', 'Muhammad Farish Ilmi Bin Muhd Faizal', 'niksyimi@hotmail.com', NULL, 'student', NULL, '1768371598_21.jpeg', '2026-01-14 06:17:01', NULL, NULL, NULL),
(22, 'farish_parent', '$2y$10$ikIWiFj.G1adaYSv1M5zuOMTm9ML6Yx7pN/rgMU59Ymji5IyB3gSq', 'Parent of Muhammad Farish Ilmi Bin Muhd Faizal', 'parent_niksyimi@hotmail.com', NULL, 'parent', NULL, 'default_avatar.png', '2026-01-14 06:17:02', NULL, NULL, NULL),
(23, 'ieka', '$2y$10$kM5ZCWlc.nj9VbtUrB2kou48p3dIhqfv.5E6a.B4nl0RHInCKK4SK', 'asyif', 'syahirahatiqah801@gmail.com', NULL, 'student', NULL, '1768455117_23.jpg', '2026-01-15 04:50:32', NULL, NULL, NULL),
(24, 'ieka_parent', '$2y$10$bKHnhIUN4UUphSAzGbaHCerirpfFeq9Uq8/pK2LyRW6SvjFRfdph.', 'Parent of ika', 'parent_syahirahatiqah801@gmail.com', NULL, 'parent', NULL, 'default_avatar.png', '2026-01-15 04:50:32', NULL, NULL, NULL),
(25, 'hayyan', '$2y$10$4odpCBQfsdAg5iE6HZm83O1M7nyfk.lQTu6ehPKZNioIkeg6WEO0q', 'hayyan', 'hayyan78@gamil.com', NULL, 'student', NULL, 'default_avatar.png', '2026-01-19 08:48:16', NULL, NULL, NULL),
(26, 'hayyan_parent', '$2y$10$0hLxhqphVbympWk7K5R5QOFRBja5/.X3gaTQL76Q8hTRhd/dLHDhG', 'Parent of hayyan', 'parent_hayyan78@gamil.com', NULL, 'parent', NULL, 'default_avatar.png', '2026-01-19 08:48:16', NULL, NULL, NULL),
(28, 'kontol', '$2y$10$dYSLoZXNP6.MbEe85h9ljunY/kbIC13nLgNM/1wYAXOpDJo27cnfW', 'nik ijtihadi', 'asyifmika@gmail.com', NULL, 'admin', NULL, 'default_avatar.jpg', '2026-01-21 18:02:40', NULL, NULL, NULL),
(29, 'dzulzamri', '$2y$10$VAyOPU4oM/HRFaU4unZ9z.HDB.AI.BzF4NQDLxrgcHRrpfhZGAQc2', 'dzulzamri', 'longmerpunj2@gmail.com', NULL, 'teacher', '2026-02-28', 'default_avatar.png', '2026-01-29 07:20:25', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_pets`
--

CREATE TABLE `user_pets` (
  `pet_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `pet_name` varchar(50) DEFAULT 'Buddy',
  `happiness_level` int(11) DEFAULT 100,
  `last_fed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_pets`
--

INSERT INTO `user_pets` (`pet_id`, `student_id`, `pet_name`, `happiness_level`, `last_fed_at`) VALUES
(5, 13, 'Buddy', 100, '2026-01-13 20:18:01'),
(8, 19, 'Buddy', 100, '2026-01-13 22:05:22'),
(9, 21, 'Buddy', 100, '2026-01-14 06:17:02'),
(10, 23, 'Buddy', 100, '2026-01-15 04:50:32'),
(11, 25, 'Buddy', 100, '2026-01-19 08:48:16');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `video_id` int(11) NOT NULL,
  `week_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `youtube_link` varchar(255) NOT NULL,
  `thumbnail` varchar(255) DEFAULT 'default_video.png',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `class_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`video_id`, `week_id`, `title`, `description`, `youtube_link`, `thumbnail`, `created_by`, `created_at`, `class_id`) VALUES
(2, NULL, 'Lets Learn Numbers', 'Are you in the mood to learn more about numbers? Today, we\'ll learn how to write them.  Always keeping it easy and fun!', 'https://youtu.be/wuxu6Qsaq5I?si=A_jX0FwXpG1RyMzs', 'default_video.png', 5, '2026-01-14 17:37:36', NULL),
(3, NULL, 'Lets Learn Geometric shapes', 'Kids educational video to learn geometric shapes, like the circle, triangle, square, rectangle, diamond and heart shapes, the cross, the star and the moon through countless super-fun examples.', 'https://youtu.be/NDMPwZL47JY?si=nvIZwdDB8mwfGW7R', 'default_video.png', 5, '2026-01-14 17:40:41', NULL),
(4, NULL, 'Lets Learn The Human Body for children', 'This is an educational video where children can learn more about the human body. In this video you will learn about every part of the body, from the head to the lower limbs.', 'https://youtu.be/SqI-NMDeLa8?si=scsO05e9TmTIoOrk', 'default_video.png', 5, '2026-01-14 17:42:44', NULL),
(5, NULL, 'Lets Learn About Fruits', 'This time, We will learn about fruits. Always with an easy example for kids to identify each word with the correct fruit.', 'https://youtu.be/KRqg3RJFWPo?si=vbq8gye2Kzl-F_OQ', 'default_video.png', 5, '2026-01-14 17:45:41', NULL),
(6, NULL, 'Lets Learn About Farm Animal', 'Educational video for kids to learn new vocabulary about farm animals like the cow, the horse, the sheep, the hen, the rabbit, the donkey, the goat, the dog, the cat and many other.', 'https://youtu.be/hewioIU4a64?si=IZEnC0koaB-N30P8', 'default_video.png', 5, '2026-01-14 17:50:16', NULL),
(7, NULL, 'Lets Learn Emotions for kids', 'Educational video for children to learn the basic emotions in a fun way. We feel happiness when good things happen to us, for example, when we play. We feel sadness when something bad happens around us.', 'https://youtu.be/jetoWelJJJk?si=UHH0ftnSVULAN3UM', 'default_video.png', 5, '2026-01-14 17:52:35', NULL),
(8, NULL, 'Lets Learn Good Manners For Kids', 'Educational video for children that talks about good manners, specifically how to say please, thank you and ask for permission. Good manners help us to communicate with others in a polite way.', 'https://youtu.be/TPhabSkn3sM?si=RM8tITX2cFFQ-5ZI', 'default_video.png', 5, '2026-01-14 17:55:35', NULL),
(9, NULL, 'Lets Learn Mathematics For Kids', 'Educational video for kids to practice Mathematics and learn how to subtract with Dino, the dinosaur. They will find out how the minus sign is used in subtractions.', 'https://youtu.be/rqiu_xcvSk4?si=Pdbqdu9U_93CIvy2', 'default_video.png', 5, '2026-01-14 17:58:22', NULL),
(10, NULL, 'Lets Learn Solar System', 'he Solar System in 3D animation for kids  is an educational video in which the little ones will take a trip to explore the planets. It is perfect to reinforce the subject of Science in Primary Education.', 'https://youtu.be/sVPK_u_Dvig?si=Gz5YjfJx9DRdO4qw', 'default_video.png', 5, '2026-01-14 18:01:17', NULL),
(11, NULL, 'Lets Learn Colors', 'Our most colorful videos are arriving! Want to learn to mix colors with Smile and Learn? In this video, kids will meet primary and secondary colors in a fun way.', 'https://youtu.be/2aKep6PUFfI?si=UGOmRKdcxv4Vr3vj', 'default_video.png', 5, '2026-01-14 18:03:48', NULL),
(12, 1, 'peeidh 6sense', 'dsegfwe', 'https://www.youtube.com/watch?v=nf-emeU6XFY&list=RDMMnf-emeU6XFY&start_radio=1', 'default_video.png', 5, '2026-01-29 17:34:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `weeks`
--

CREATE TABLE `weeks` (
  `week_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `weeks`
--

INSERT INTO `weeks` (`week_id`, `subject_id`, `title`, `sort_order`, `is_visible`) VALUES
(1, 3, 'koentol', 1, 1),
(2, 5, 'week 1: intro', 1, 0),
(3, 5, 'week 2: basic', 2, 0),
(7, 3, 'week 2: moj', 2, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_attendance` (`session_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `class_material_overrides`
--
ALTER TABLE `class_material_overrides`
  ADD PRIMARY KEY (`override_id`),
  ADD UNIQUE KEY `unique_override` (`class_id`,`item_type`,`item_id`);

--
-- Indexes for table `class_schedule`
--
ALTER TABLE `class_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`class_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `live_sessions`
--
ALTER TABLE `live_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `fk_session_schedule` (`schedule_id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`material_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `week_id` (`week_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `parent_alerts`
--
ALTER TABLE `parent_alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `parent_students`
--
ALTER TABLE `parent_students`
  ADD PRIMARY KEY (`parent_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `pushed_content_log`
--
ALTER TABLE `pushed_content_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`quiz_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `week_id` (`week_id`);

--
-- Indexes for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `student_progress`
--
ALTER TABLE `student_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_reads`
--
ALTER TABLE `student_reads`
  ADD PRIMARY KEY (`read_id`),
  ADD UNIQUE KEY `unique_read` (`student_id`,`material_id`);

--
-- Indexes for table `student_task_claims`
--
ALTER TABLE `student_task_claims`
  ADD PRIMARY KEY (`claim_id`),
  ADD UNIQUE KEY `unique_claim` (`student_id`,`task_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `student_video_views`
--
ALTER TABLE `student_video_views`
  ADD PRIMARY KEY (`view_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `video_id` (`video_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`task_id`);

--
-- Indexes for table `teacher_remarks`
--
ALTER TABLE `teacher_remarks`
  ADD PRIMARY KEY (`remark_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`teacher_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_pets`
--
ALTER TABLE `user_pets`
  ADD PRIMARY KEY (`pet_id`),
  ADD UNIQUE KEY `unique_pet` (`student_id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`video_id`),
  ADD KEY `week_id` (`week_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `weeks`
--
ALTER TABLE `weeks`
  ADD PRIMARY KEY (`week_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `class_material_overrides`
--
ALTER TABLE `class_material_overrides`
  MODIFY `override_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `class_schedule`
--
ALTER TABLE `class_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `live_sessions`
--
ALTER TABLE `live_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `parent_alerts`
--
ALTER TABLE `parent_alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pushed_content_log`
--
ALTER TABLE `pushed_content_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `quiz_results`
--
ALTER TABLE `quiz_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `student_progress`
--
ALTER TABLE `student_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `student_reads`
--
ALTER TABLE `student_reads`
  MODIFY `read_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `student_task_claims`
--
ALTER TABLE `student_task_claims`
  MODIFY `claim_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `student_video_views`
--
ALTER TABLE `student_video_views`
  MODIFY `view_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `teacher_remarks`
--
ALTER TABLE `teacher_remarks`
  MODIFY `remark_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `user_pets`
--
ALTER TABLE `user_pets`
  MODIFY `pet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `video_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `weeks`
--
ALTER TABLE `weeks`
  MODIFY `week_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `live_sessions` (`session_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `class_schedule`
--
ALTER TABLE `class_schedule`
  ADD CONSTRAINT `class_schedule_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE;

--
-- Constraints for table `live_sessions`
--
ALTER TABLE `live_sessions`
  ADD CONSTRAINT `fk_session_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedule` (`schedule_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `live_sessions_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `materials_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `materials_week` FOREIGN KEY (`week_id`) REFERENCES `weeks` (`week_id`) ON DELETE SET NULL;

--
-- Constraints for table `parent_alerts`
--
ALTER TABLE `parent_alerts`
  ADD CONSTRAINT `parent_alerts_parent` FOREIGN KEY (`parent_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_alerts_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `parent_students`
--
ALTER TABLE `parent_students`
  ADD CONSTRAINT `parent_students_parent` FOREIGN KEY (`parent_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_students_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `pushed_content_log`
--
ALTER TABLE `pushed_content_log`
  ADD CONSTRAINT `pushed_content_log_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `live_sessions` (`session_id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `quizzes_week` FOREIGN KEY (`week_id`) REFERENCES `weeks` (`week_id`) ON DELETE SET NULL;

--
-- Constraints for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD CONSTRAINT `quiz_results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `quiz_results_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`);

--
-- Constraints for table `student_progress`
--
ALTER TABLE `student_progress`
  ADD CONSTRAINT `student_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_task_claims`
--
ALTER TABLE `student_task_claims`
  ADD CONSTRAINT `student_task_claims_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `student_task_claims_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`);

--
-- Constraints for table `student_video_views`
--
ALTER TABLE `student_video_views`
  ADD CONSTRAINT `student_video_views_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_video_views_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`video_id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_remarks`
--
ALTER TABLE `teacher_remarks`
  ADD CONSTRAINT `teacher_remarks_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `teacher_remarks_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_remarks_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `teacher_remarks_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_pets`
--
ALTER TABLE `user_pets`
  ADD CONSTRAINT `user_pets_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `videos_week` FOREIGN KEY (`week_id`) REFERENCES `weeks` (`week_id`) ON DELETE SET NULL;

--
-- Constraints for table `weeks`
--
ALTER TABLE `weeks`
  ADD CONSTRAINT `weeks_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
