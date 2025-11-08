-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 08, 2025 at 05:55 AM
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
-- Database: `ticketingsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `posted_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `send_email` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `priority`, `posted_by`, `created_at`, `expires_at`, `send_email`) VALUES
(1, 'I have a cancer', 'Today I figure out I have cancer', 'high', NULL, '2025-11-01 11:54:33', NULL, 1),
(8, 'ASD', 'ASDASDASDAS', 'medium', NULL, '2025-11-01 13:56:20', NULL, 1),
(9, 'asdasdasdasdasd', 'asdasdasda', 'medium', NULL, '2025-11-07 05:10:01', '2025-11-14 13:09:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `announcement_comments`
--

CREATE TABLE `announcement_comments` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_comments`
--

INSERT INTO `announcement_comments` (`id`, `announcement_id`, `employee_id`, `comment`, `created_at`) VALUES
(1, 1, 'employee03', 'take care', '2025-11-01 12:06:11'),
(2, 1, 'employee003', 'god bless', '2025-11-01 12:07:10'),
(3, 1, '422002152', 'Thank you\r\n', '2025-11-01 12:11:06'),
(5, 8, 'employee03', 'adad', '2025-11-01 14:07:51'),
(6, 8, 'employee03', '..', '2025-11-01 14:17:47'),
(7, 1, 'employee003', 'magaling na ako guys hehehe uminom ako ng alak', '2025-11-06 08:40:59');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_responses`
--

CREATE TABLE `chatbot_responses` (
  `id` int(11) NOT NULL,
  `trigger_keyword` varchar(100) NOT NULL,
  `response_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_responses`
--

INSERT INTO `chatbot_responses` (`id`, `trigger_keyword`, `response_text`, `created_at`) VALUES
(1, 'leave', 'To file a leave request, please go to the Leave Request page or contact HR directly.', '2025-10-14 09:27:19'),
(2, 'payroll', 'Payroll is processed every 15th and 30th of the month. For details, contact HR.', '2025-10-14 09:27:19'),
(3, 'benefits', 'You can view your benefits in the HR Portal under \"Employee Benefits\".', '2025-10-14 09:27:19'),
(4, 'equipment', 'If you have issues with your equipment, please create a technical support ticket.', '2025-10-14 09:27:19'),
(5, 'help', 'I can help you with leave, payroll, benefits, and ticket issues.', '2025-10-14 09:27:19'),
(6, 'ticket', 'To create a ticket, click \"New Ticket\" on your dashboard.', '2025-10-14 09:27:19');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_bot` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `employee_id`, `message`, `is_bot`, `created_at`) VALUES
(1, 'employee003', 'leave', 0, '2025-10-14 11:10:09'),
(2, 'employee003', 'To file a leave request, please go to the Leave Request page or contact HR directly.', 1, '2025-10-14 11:10:09'),
(3, 'employee003', 'sada', 0, '2025-10-14 11:10:11'),
(4, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-14 11:10:11'),
(5, 'employee003', 'leave', 0, '2025-10-14 11:14:30'),
(6, 'employee003', 'To file a leave request, please go to the Leave Request page or contact HR directly.', 1, '2025-10-14 11:14:30'),
(7, 'employee003', 'leave', 0, '2025-10-14 15:06:05'),
(8, 'employee003', 'To file a leave request, please go to the Leave Request page or contact HR directly.', 1, '2025-10-14 15:06:05'),
(9, 'employee003', 'payroll', 0, '2025-10-16 08:20:57'),
(10, 'employee003', 'Payroll is processed every 15th and 30th of the month. For details, contact HR.', 1, '2025-10-16 08:20:57'),
(11, 'employee003', 'tanginamo', 0, '2025-10-17 07:02:59'),
(12, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-17 07:02:59'),
(13, 'employee003', 'payroll', 0, '2025-10-17 07:03:16'),
(14, 'employee003', 'Payroll is processed every 15th and 30th of the month. For details, contact HR.', 1, '2025-10-17 07:03:16'),
(15, 'employee003', 'sup', 0, '2025-10-17 07:04:18'),
(16, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-17 07:04:18'),
(17, 'employee003', 'leave', 0, '2025-10-19 10:04:28'),
(18, 'employee003', 'To file a leave request, please go to the Leave Request page or contact HR directly.', 1, '2025-10-19 10:04:28'),
(19, 'employee003', 'payroll', 0, '2025-10-19 10:06:01'),
(20, 'employee003', 'Payroll is processed every 15th and 30th of the month. For details, contact HR.', 1, '2025-10-19 10:06:01'),
(21, 'employee003', 'payroll', 0, '2025-10-19 10:06:15'),
(22, 'employee003', 'Payroll is processed every 15th and 30th of the month. For details, contact HR.', 1, '2025-10-19 10:06:15'),
(23, 'employee003', 'asdasd', 0, '2025-10-22 14:14:27'),
(24, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:14:27'),
(25, 'employee003', 'adasd', 0, '2025-10-22 14:14:41'),
(26, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:14:41'),
(27, 'employee003', 'asdasd', 0, '2025-10-22 14:24:56'),
(28, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:24:56'),
(29, 'employee003', 'asdas', 0, '2025-10-22 14:28:37'),
(30, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:28:37'),
(31, 'employee003', 'asdas', 0, '2025-10-22 14:28:47'),
(32, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:28:47'),
(33, 'employee003', 'undefined', 0, '2025-10-22 14:28:50'),
(34, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:28:50'),
(35, 'employee003', 'undefined', 0, '2025-10-22 14:28:52'),
(36, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:28:52'),
(37, 'employee003', 'dadad', 0, '2025-10-22 14:28:56'),
(38, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:28:56'),
(39, 'employee003', 'undefined', 0, '2025-10-22 14:29:43'),
(40, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:29:43'),
(41, 'employee003', 'adad', 0, '2025-10-22 14:29:47'),
(42, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:29:47'),
(43, 'employee003', 'undefined', 0, '2025-10-22 14:29:53'),
(44, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:29:53'),
(45, 'employee003', 'undefined', 0, '2025-10-22 14:29:55'),
(46, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:29:55'),
(47, 'employee003', 'asdasd', 0, '2025-10-22 14:32:23'),
(48, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:32:23'),
(49, 'employee003', 'dsd', 0, '2025-10-22 14:32:58'),
(50, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:32:58'),
(51, 'employee003', 'dada', 0, '2025-10-22 14:33:43'),
(52, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:33:43'),
(53, 'employee003', 'undefined', 0, '2025-10-22 14:33:45'),
(54, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:33:45'),
(55, 'employee003', 'adasd', 0, '2025-10-22 14:35:39'),
(56, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:35:39'),
(57, 'employee003', 'asdasd', 0, '2025-10-22 14:38:36'),
(58, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:38:36'),
(59, 'employee003', 'undefined', 0, '2025-10-22 14:38:40'),
(60, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:38:40'),
(61, 'employee003', 'undefined', 0, '2025-10-22 14:38:42'),
(62, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:38:42'),
(63, 'employee003', 'undefined', 0, '2025-10-22 14:39:45'),
(64, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:39:45'),
(65, 'employee003', 'asdas', 0, '2025-10-22 14:47:32'),
(66, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:47:32'),
(67, 'employee003', 'undefined', 0, '2025-10-22 14:48:26'),
(68, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-22 14:48:26'),
(69, 'employee003', 'leave request', 0, '2025-10-22 14:52:17'),
(70, 'employee003', 'To file a leave request, please go to the Leave Request page or contact HR directly.', 1, '2025-10-22 14:52:17'),
(71, 'employee003', 'leave request', 0, '2025-10-23 00:51:46'),
(72, 'employee003', 'To file a leave request, please go to the Leave Request page or contact HR directly.', 1, '2025-10-23 00:51:46'),
(73, 'employee003', 'hghgg', 0, '2025-10-23 00:51:50'),
(74, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-23 00:51:50'),
(75, 'employee003', 'adasd', 0, '2025-10-23 13:58:11'),
(76, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-23 13:58:11'),
(77, 'employee003', 'leave request', 0, '2025-10-24 06:49:52'),
(78, 'employee003', 'To file a leave request, please go to the Leave Request page or contact HR directly.', 1, '2025-10-24 06:49:52'),
(79, 'employee003', 'leave request', 0, '2025-10-27 05:05:29'),
(80, 'employee003', 'To file a leave request, please go to the Leave Request page or contact HR directly.', 1, '2025-10-27 05:05:29'),
(81, 'employee003', 'adadasd', 0, '2025-10-28 09:49:31'),
(82, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-28 09:49:31'),
(83, 'employee003', 'adada', 0, '2025-10-28 14:44:56'),
(84, 'employee003', 'I couldn\'t find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.', 1, '2025-10-28 14:44:56'),
(85, 'employee003', 's', 0, '2025-10-30 11:09:59'),
(86, 'employee003', 'I\'m here to help! ', 1, '2025-10-30 11:09:59'),
(87, 'employee003', 'What are the company holidays?', 0, '2025-10-30 11:14:37'),
(88, 'employee003', 'I\'m here to help! ', 1, '2025-10-30 11:14:37'),
(89, 'employee003', 'ID Request', 0, '2025-10-30 12:52:33'),
(90, 'employee003', 'I\'m here to help! ', 1, '2025-10-30 12:52:33'),
(91, 'employee003', 'ID Lost or Access Card', 0, '2025-10-30 12:52:35'),
(92, 'employee003', 'I\'m here to help! ', 1, '2025-10-30 12:52:35'),
(93, 'employee003', 'Notary Request', 0, '2025-10-30 12:52:36'),
(94, 'employee003', 'I\'m here to help! ', 1, '2025-10-30 12:52:36'),
(95, 'employee003', 'What are the company holidays?', 0, '2025-10-30 12:52:39'),
(96, 'employee003', 'I\'m here to help! ', 1, '2025-10-30 12:52:39'),
(97, 'employee003', 'How do I update my personal data?', 0, '2025-10-30 12:52:40'),
(98, 'employee003', 'I\'m here to help! ', 1, '2025-10-30 12:52:40'),
(99, 'employee003', 'What is the sick leave policy?', 0, '2025-10-30 12:52:41'),
(100, 'employee003', 'To file a leave request, please go to the Leave Request page or contact HR directly.', 1, '2025-10-30 12:52:41'),
(101, 'employee03', 'What are the company holidays?', 0, '2025-11-01 15:20:50'),
(102, 'employee03', 'I\'m here to help! ', 1, '2025-11-01 15:20:50'),
(103, 'employee03', 'Notary Request', 0, '2025-11-01 15:21:10'),
(104, 'employee03', 'I\'m here to help! ', 1, '2025-11-01 15:21:10'),
(105, 'employee03', 'What are the company holidays?', 0, '2025-11-01 15:25:04'),
(106, 'employee03', 'Our company observes both national and special holidays as declared by the Philippine government.\n\nIn addition, employees are entitled to company-declared holidays such as:\n• Company Foundation Day\n• Christmas Break (December 24–25)\n• New Year\'s Eve (December 31)\n\nA full list of holidays is posted on the HR Dashboard or may be requested from the HR Department.', 1, '2025-11-01 15:25:04'),
(107, 'employee03', 'What are the company holidays?', 0, '2025-11-01 15:27:53'),
(108, 'employee03', 'Our company observes both national and special holidays as declared by the Philippine government.\n\nIn addition, employees are entitled to company-declared holidays such as:\n• Company Foundation Day\n• Christmas Break (December 24–25)\n• New Year\'s Eve (December 31)\n\nA full list of holidays is posted on the HR Dashboard or may be requested from the HR Department.', 1, '2025-11-01 15:27:53'),
(109, 'employee03', 'How do I update my personal data?', 0, '2025-11-01 15:28:08'),
(110, 'employee03', 'I\'m here to help! Could you please rephrase your question or select one of the common questions from the sidebar? I can assist with company holidays, personal data updates, sick leave policies, ID requests, lost cards, notary services, benefits, payroll, tickets, and more.', 1, '2025-11-01 15:28:08'),
(111, 'employee03', 'ID Request', 0, '2025-11-01 15:28:18'),
(112, 'employee03', 'To request an ID Card:\n\n1. Go to Tickets and create a new ticket with category \'ID Request\'\n2. Provide your details and reason for the request\n3. Submit for HR processing\n4. HR will contact you for photo verification if needed\n5. Your ID will be ready for pickup in 3-5 business days\n\nFor urgent requests, please contact the HR Department directly.', 1, '2025-11-01 15:28:18'),
(113, 'employee003', 'What is the sick leave policy?', 0, '2025-11-01 16:05:10'),
(114, 'employee003', 'Employees are entitled to up to 15 days of paid sick leave per year (subject to company policy).\n\nTo file a sick leave:\n1. Submit your Sick Leave Request through the system before or immediately after your absence\n2. Attach a medical certificate if the absence is more than two consecutive days\n3. Wait for HR approval and confirmation through the portal or email\n\nUnused sick leaves may be converted to cash or carried over depending on company rules.', 1, '2025-11-01 16:05:10'),
(115, 'employee003', 'What is the sick leave policy?', 0, '2025-11-02 02:36:07'),
(116, 'employee003', 'Employees are entitled to up to 15 days of paid sick leave per year (subject to company policy).\n\nTo file a sick leave:\n1. Submit your Sick Leave Request through the system before or immediately after your absence\n2. Attach a medical certificate if the absence is more than two consecutive days\n3. Wait for HR approval and confirmation through the portal or email\n\nUnused sick leaves may be converted to cash or carried over depending on company rules.', 1, '2025-11-02 02:36:07'),
(117, 'employee003', 'ID Request', 0, '2025-11-03 07:38:21'),
(118, 'employee003', 'To request an ID Card:\n\n1. Go to Tickets and create a new ticket with category \'ID Request\'\n2. Provide your details and reason for the request\n3. Submit for HR processing\n4. HR will contact you for photo verification if needed\n5. Your ID will be ready for pickup in 3-5 business days\n\nFor urgent requests, please contact the HR Department directly.', 1, '2025-11-03 07:38:21'),
(119, 'employee003', 'hi', 0, '2025-11-03 07:38:30'),
(120, 'employee003', 'I\'m here to help! Could you please rephrase your question or select one of the common questions from the sidebar? I can assist with company holidays, personal data updates, sick leave policies, ID requests, lost cards, notary services, benefits, payroll, tickets, and more.', 1, '2025-11-03 07:38:30'),
(121, 'employee003', 'hi', 0, '2025-11-06 08:37:29'),
(122, 'employee003', 'I\'m here to help! Could you please rephrase your question or select one of the common questions from the sidebar? I can assist with company holidays, personal data updates, sick leave policies, ID requests, lost cards, notary services, benefits, payroll, tickets, and more.', 1, '2025-11-06 08:37:29'),
(123, 'employee003', 'how to make a tickets?', 0, '2025-11-06 08:38:04'),
(124, 'employee003', 'To create or manage support tickets:\n\n1. Go to Tickets section in the Employee Portal\n2. Click \'Create New Ticket\'\n3. Select the appropriate category (ID Request, Concern, etc.)\n4. Provide detailed description\n5. Track your ticket status in real-time\n\nYou can view all your tickets and their status anytime in the Tickets section.', 1, '2025-11-06 08:38:04'),
(125, 'employee003', 'thankyou', 0, '2025-11-06 08:38:21'),
(126, 'employee003', 'I\'m here to help! Could you please rephrase your question or select one of the common questions from the sidebar? I can assist with company holidays, personal data updates, sick leave policies, ID requests, lost cards, notary services, benefits, payroll, tickets, and more.', 1, '2025-11-06 08:38:21'),
(127, 'employee003', 'what about ID lost?', 0, '2025-11-06 08:38:41'),
(128, 'employee003', 'If your ID or Access Card is lost:\n\n1. Report it immediately to the HR Department\n2. Create a ticket with category \'ID Lost or Access Card\'\n3. Provide details of when and where it was lost\n4. Pay the replacement fee (if applicable per company policy)\n5. A new card will be issued within 3-5 business days\n\nIn the meantime, notify your manager and the security team.', 1, '2025-11-06 08:38:41'),
(129, 'employee003', 'ID request?', 0, '2025-11-06 08:39:08'),
(130, 'employee003', 'To request an ID Card:\n\n1. Go to Tickets and create a new ticket with category \'ID Request\'\n2. Provide your details and reason for the request\n3. Submit for HR processing\n4. HR will contact you for photo verification if needed\n5. Your ID will be ready for pickup in 3-5 business days\n\nFor urgent requests, please contact the HR Department directly.', 1, '2025-11-06 08:39:08'),
(131, 'employee003', 'What are the company holidays?', 0, '2025-11-06 08:39:37'),
(132, 'employee003', 'Our company observes both national and special holidays as declared by the Philippine government.\n\nIn addition, employees are entitled to company-declared holidays such as:\n• Company Foundation Day\n• Christmas Break (December 24–25)\n• New Year\'s Eve (December 31)\n\nA full list of holidays is posted on the HR Dashboard or may be requested from the HR Department.', 1, '2025-11-06 08:39:37'),
(133, 'employee03', 'What are the company holidays?', 0, '2025-11-07 05:08:32'),
(134, 'employee03', 'Our company observes both national and special holidays as declared by the Philippine government.\n\nIn addition, employees are entitled to company-declared holidays such as:\n• Company Foundation Day\n• Christmas Break (December 24–25)\n• New Year\'s Eve (December 31)\n\nA full list of holidays is posted on the HR Dashboard or may be requested from the HR Department.', 1, '2025-11-07 05:08:32'),
(135, 'employee03', 'What are the company holidays?', 0, '2025-11-07 08:57:18'),
(136, 'employee03', 'Our company observes both national and special holidays as declared by the Philippine government.\n\nIn addition, employees are entitled to company-declared holidays such as:\n• Company Foundation Day\n• Christmas Break (December 24–25)\n• New Year\'s Eve (December 31)\n\nA full list of holidays is posted on the HR Dashboard or may be requested from the HR Department.', 1, '2025-11-07 08:57:18');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'employee',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone_number` varchar(20) DEFAULT NULL,
  `street_address` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Philippines',
  `location` varchar(255) DEFAULT NULL,
  `language` varchar(100) DEFAULT 'English',
  `timezone` varchar(50) DEFAULT 'GMT+8',
  `nationality` varchar(100) DEFAULT 'Filipino',
  `two_step_enabled` tinyint(1) DEFAULT 0,
  `two_step_code` varchar(6) DEFAULT NULL,
  `two_step_code_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `email`, `department`, `position`, `position_id`, `salary`, `hire_date`, `password`, `photo`, `role`, `created_at`, `phone_number`, `street_address`, `barangay`, `city`, `province`, `postal_code`, `country`, `location`, `language`, `timezone`, `nationality`, `two_step_enabled`, `two_step_code`, `two_step_code_expires`) VALUES
(1, 'HR001', 'HR Administrator', 'hr@company.com', 'Human Resources', NULL, NULL, NULL, NULL, 'admin123', '', 'hr_admin', '2025-10-05 15:44:29', NULL, NULL, NULL, NULL, NULL, NULL, 'Philippines', NULL, 'English', 'GMT+8', 'Filipino', 0, NULL, NULL),
(2, '422002152', '', '', '422002152', NULL, NULL, NULL, NULL, '$2y$10$Cn.9ufz6yMu7bXd7fEGnw./zZETPwe1jr.TwM9eaddGB/ZOQAdWIS', 'assets/images/photo_69097fdd24684_1762230237_download (2).jpg', 'hr_admin', '2025-10-05 15:46:33', '', '', '', '', '', '', 'Philippines', '', 'English', 'GMT+8', 'Filipino', 0, NULL, NULL),
(3, 'employee003', 'meow', 'ihatespiders0405@gmail.com', 'Data Engineering', 'Database Administrator', 81, NULL, NULL, '$2y$10$.XcbuHGMcBrhjr0sqDHIvu83zYmyut2XBT/lH.hNzZ6.gmt11eS32', 'assets/images/photo_690d7290cb320_1762488976_tumblr_mkso9kBlrv1s4xdz1o1_400_0_gif (400×294).gif', 'employee', '2025-10-06 02:35:31', '+639554918057', '1035 Samar St.', '564', 'Sampaloc, Manila', 'Metro Manila', '1005', 'Philippines', 'Manila, Philippines', 'English', 'GMT+8', 'Filipino', 1, NULL, NULL),
(4, 'employee03', 'Tralalero Tralalá,', 'ihatechemicals@gmail.com', 'Employee', NULL, NULL, NULL, NULL, '$2y$10$4Ynb7Yhkk4ERJBmGEtZttOLc2rYrdPzbD.wfifXhHTpbSqN06BUU6', 'assets/images/hr_6901f73dce21f_8323422c-c799-4cfc-bd5a-7b30b0964fce.jpg', 'employee', '2025-10-06 04:57:01', '', 'Tatalon, Quezon City', 'tatalon', 'Quezon City', 'masbate', '1113', 'Philippines', '', 'English', 'GMT+8', 'Filipino', 0, NULL, NULL),
(5, 'troyjames', 'basangan', 'troyjamesrobrigado@gmail.com', 'Human Resources', NULL, NULL, NULL, NULL, '$2y$10$CHxxaofuD.pRHKy2LkJiou1bWY34TGsq6QfFJFGxBKkrWoAAUy0u6', '', 'employee', '2025-10-07 08:01:22', NULL, NULL, NULL, NULL, NULL, NULL, 'Philippines', NULL, 'English', 'GMT+8', 'Filipino', 0, NULL, NULL),
(6, '123456789', 'tungtungtung sahur', 'alekalmene099@gmail.com', 'Quality Assurance', 'Junior QA Engineer', 157, 80000.00, '2025-11-07', '$2y$10$C9am1Z6HKjxaiuNt22Brk.A6D0fyLIjrZaJtDoWmXmJgs20TByLpC', NULL, 'employee', '2025-11-07 04:07:50', '+639606801306', 'Tatalon, Quezon City', 'Tatalon ', 'Quezon City', 'Masbate', '1113', 'Philippines', 'Manila, Philippines', 'English', 'GMT+8', 'Filipino', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `hr_notifications`
--

CREATE TABLE `hr_notifications` (
  `id` int(11) NOT NULL,
  `hr_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_notifications`
--

INSERT INTO `hr_notifications` (`id`, `hr_id`, `ticket_id`, `notification_type`, `message`, `is_read`, `created_at`) VALUES
(1, 0, 17, 'new_ticket', 'Test Employee submitted a new ticket: asadsa', 0, '2025-11-04 05:03:44'),
(2, 0, 19, 'new_ticket', 'Test Employee submitted a new ticket: asdsa', 0, '2025-11-04 05:25:29'),
(3, 422002152, 19, 'new_ticket', 'Test Employee submitted a new ticket: asdsa', 1, '2025-11-04 05:25:29');

-- --------------------------------------------------------

--
-- Table structure for table `job_applicants`
--

CREATE TABLE `job_applicants` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position_applied` varchar(255) DEFAULT NULL,
  `status` enum('pending','reviewing','interviewed','hired','rejected') DEFAULT 'pending',
  `resume_path` varchar(500) DEFAULT NULL,
  `applied_date` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_applicants`
--

INSERT INTO `job_applicants` (`id`, `full_name`, `email`, `phone`, `position_applied`, `status`, `resume_path`, `applied_date`, `created_at`) VALUES
(1, 'John Doe', 'john@example.com', '09171234567', 'Software Developer', 'reviewing', NULL, '2025-10-30 13:41:35', '2025-10-30 05:41:35'),
(2, 'Jane Smith', 'jane@example.com', '09181234567', 'UI Designer', 'pending', NULL, '2025-10-30 13:41:35', '2025-10-30 05:41:35'),
(3, 'Mike Johnson', 'mike@example.com', '09191234567', 'Project Manager', 'interviewed', NULL, '2025-10-30 13:41:35', '2025-10-30 05:41:35');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `ticket_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notification_type` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `sent_via` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `employee_id`, `ticket_id`, `notification_type`, `message`, `sent_via`, `is_read`, `created_at`) VALUES
(5, 'employee003', 10, 'ticket_response', 'HR responded to your ticket: TKT-20251030-495D00', 'email', 1, '2025-10-30 12:02:18'),
(6, 'employee003', 11, 'ticket_response', 'HR responded to your ticket: TKT-20251030-3F023D', 'email', 1, '2025-10-30 12:54:31'),
(7, 'employee03', 12, 'ticket_response', 'HR responded to your ticket: TKT-20251030-7E00EC', 'email', 1, '2025-11-01 15:20:24'),
(8, 'employee003', NULL, 'general', 'wuuuzzzaaaaaaaaaaaaaaahhhhhhhhhh!!', 'email', 1, '2025-11-06 08:03:50'),
(9, 'employee003', 19, 'ticket_response', 'HR responded to your ticket: TKT-20251104-F196D3', 'email', 1, '2025-11-06 08:14:00'),
(10, 'employee03', 20, 'ticket_response', 'HR responded to your ticket: TKT-20251107-9E2F16', 'email', 0, '2025-11-07 05:12:42'),
(11, 'employee03', NULL, 'urgent', 'punta ka dito suntukin kita', 'email', 0, '2025-11-07 05:14:33'),
(12, 'employee03', 21, 'ticket_response', 'HR responded to your ticket: TKT-20251107-2DB0EB', 'email', 0, '2025-11-07 08:59:44');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `employee_id`, `email`, `token`, `created_at`, `expires_at`, `used`) VALUES
(3, 'employee003', 'ihatespiders0405@gmail.com', '846249', '2025-10-29 06:22:43', '2025-10-29 07:37:43', 1),
(4, 'employee003', 'ihatespiders0405@gmail.com', '765083', '2025-10-30 01:48:57', '2025-10-30 03:03:57', 1),
(5, 'employee003', 'ihatespiders0405@gmail.com', '837825', '2025-10-30 12:44:34', '2025-10-30 13:59:34', 1),
(6, 'employee003', 'ihatespiders0405@gmail.com', '723103', '2025-10-30 12:50:27', '2025-10-30 14:05:27', 1),
(7, 'employee003', 'ihatespiders0405@gmail.com', '655148', '2025-11-06 08:15:15', '2025-11-06 09:30:15', 1),
(8, 'employee003', 'ihatespiders0405@gmail.com', '860257', '2025-11-06 08:16:40', '2025-11-06 09:31:40', 0),
(9, 'employee03', 'ihatechemicals@gmail.com', '697542', '2025-11-07 05:17:00', '2025-11-07 06:32:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL,
  `pay_period` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`id`, `employee_id`, `basic_salary`, `allowances`, `deductions`, `net_salary`, `pay_period`, `payment_date`, `created_at`) VALUES
(1, 'HR001', 50000.00, 5000.00, 3000.00, 52000.00, 'October 2025', '2025-10-30', '2025-10-30 05:41:35'),
(2, '422002152', 45000.00, 4000.00, 2500.00, 46500.00, 'October 2025', '2025-10-30', '2025-10-30 05:41:35');

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `position_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `position_level` varchar(50) NOT NULL,
  `min_salary` decimal(12,2) DEFAULT NULL,
  `max_salary` decimal(12,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `position_name`, `department`, `position_level`, `min_salary`, `max_salary`, `description`, `requirements`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Vice President - Engineering', 'Engineering', 'Senior Management', 3500000.00, 6000000.00, 'Leads engineering teams and technical architecture', 'BS Computer Science, 12+ years software engineering', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(2, 'Vice President - Data Science', 'Data Science', 'Senior Management', 3500000.00, 6000000.00, 'Oversees AI/ML development and data strategy', 'PhD/MS in Data Science, 12+ years experience', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(3, 'Vice President - Product', 'Product Management', 'Senior Management', 3200000.00, 5500000.00, 'Manages product strategy for AI fintech solutions', 'MBA, 10+ years product management', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(4, 'Vice President - Sales & Partnerships', 'Sales', 'Senior Management', 3000000.00, 5500000.00, 'Leads enterprise sales and strategic partnerships', 'MBA, 10+ years B2B fintech sales', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(5, 'Vice President - Operations', 'Operations', 'Senior Management', 3000000.00, 5200000.00, 'Manages company operations and scaling', 'MBA, 10+ years operations', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(6, 'Vice President - People & Culture', 'Human Resources', 'Senior Management', 2800000.00, 5000000.00, 'Leads HR strategy, talent, and organizational culture', 'MBA, 12+ years HR leadership in tech', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(7, 'Engineering Manager', 'Engineering', 'Management', 1800000.00, 3000000.00, 'Manages engineering teams and delivery', 'BS Computer Science, 7+ years development', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(8, 'Data Science Manager', 'Data Science', 'Management', 1800000.00, 3200000.00, 'Leads AI/ML projects and data science teams', 'MS Data Science, 7+ years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(9, 'Product Manager', 'Product Management', 'Management', 1500000.00, 2800000.00, 'Manages AI product development lifecycle', 'BS degree, 5+ years product management', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(10, 'Sales Manager', 'Sales', 'Management', 1400000.00, 2600000.00, 'Manages enterprise sales team', 'Bachelors degree, 5+ years B2B sales', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(11, 'Customer Success Manager', 'Customer Success', 'Management', 1300000.00, 2400000.00, 'Ensures client satisfaction and retention', 'Bachelors degree, 5+ years CS', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(12, 'Marketing Manager', 'Marketing', 'Management', 1200000.00, 2200000.00, 'Develops B2B marketing strategies', 'Bachelors Marketing, 5+ years B2B', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(13, 'HR Director / Head of HR', 'Human Resources', 'Senior Management', 2500000.00, 4500000.00, 'Complete ownership of HR functions and strategy', '8+ years HR, MBA preferred, tech/startup experience', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(14, 'Senior HR Manager', 'Human Resources', 'Management', 1600000.00, 2800000.00, 'Manages HR operations and team', 'Bachelors HR, SHRM-CP, 10+ years HR', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(15, 'HR Manager - Talent Acquisition', 'Human Resources', 'Management', 1400000.00, 2500000.00, 'Leads recruitment strategy and TA team', '8+ years IT recruitment, tech industry', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(16, 'HR Manager - Operations', 'Human Resources', 'Management', 1300000.00, 2400000.00, 'Manages HR operations, policies, employee relations', '7+ years HR operations, HRIS experience', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(17, 'HR Business Partner', 'Human Resources', 'Professional', 900000.00, 1600000.00, 'Strategic partner to business units on HR', 'Bachelors HR, 5+ years HRBP in tech', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(18, 'Senior Talent Acquisition Specialist', 'Human Resources', 'Senior Professional', 800000.00, 1400000.00, 'Leads complex technical recruitment', '7+ years IT recruitment, full-cycle hiring', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(19, 'Talent Acquisition Specialist - Tech', 'Human Resources', 'Professional', 600000.00, 1100000.00, 'Recruits software engineers, data scientists, IT roles', '3+ years technical recruitment', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(20, 'Talent Acquisition Specialist - Business', 'Human Resources', 'Professional', 550000.00, 1000000.00, 'Recruits sales, marketing, operations roles', '3+ years business recruitment', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(21, 'Senior HR Generalist', 'Human Resources', 'Senior Professional', 750000.00, 1300000.00, 'Manages employee relations, engagement, policies', '5+ years HR generalist', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(22, 'HR Generalist', 'Human Resources', 'Professional', 550000.00, 1000000.00, 'Handles onboarding, employee relations, HR admin', '2-5 years HR experience', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(23, 'HR Operations Associate', 'Human Resources', 'Professional', 500000.00, 900000.00, 'Supports HR operations and administration', '2-5 years HR operations', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(24, 'Recruiter - IT/Tech', 'Human Resources', 'Professional', 550000.00, 1000000.00, 'Sources and recruits technical talent', '2-5 years recruitment', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(25, 'Talent Sourcer', 'Human Resources', 'Associate', 420000.00, 700000.00, 'Sources candidates through various channels', '0-2 years sourcing/recruitment', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(26, 'Learning & Development Specialist', 'Human Resources', 'Professional', 600000.00, 1100000.00, 'Develops training programs and upskilling', '3+ years L&D in tech', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(27, 'Compensation & Benefits Specialist', 'Human Resources', 'Professional', 650000.00, 1200000.00, 'Manages compensation structure and benefits', '3+ years C&B, analytics skills', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(28, 'Employee Engagement Specialist', 'Human Resources', 'Professional', 550000.00, 1000000.00, 'Drives engagement initiatives and culture', '2-5 years employee engagement', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(29, 'Payroll & Benefits Specialist', 'Human Resources', 'Professional', 500000.00, 900000.00, 'Processes payroll and benefits administration', '2-5 years payroll', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(30, 'HR Coordinator', 'Human Resources', 'Associate', 380000.00, 650000.00, 'Provides HR administrative support', '0-2 years HR coordination', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(31, 'Lead AI Engineer', 'Data Science', 'Senior Professional', 1500000.00, 2500000.00, 'Leads AI/ML model development', 'MS AI/ML, 7+ years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(32, 'Senior Software Engineer', 'Engineering', 'Senior Professional', 1200000.00, 2200000.00, 'Develops complex software systems', 'BS Computer Science, 5+ years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(33, 'Software Developer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops software applications', 'BS Computer Science, 2-5 years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(34, 'Senior QA Engineer', 'Quality Assurance', 'Senior Professional', 900000.00, 1700000.00, 'Leads testing and quality', 'BS Computer Science, 5+ years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(35, 'QA/Test Engineer', 'Quality Assurance', 'Professional', 600000.00, 1100000.00, 'Performs testing and QA', 'BS Computer Science, 2-5 years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(36, 'IT Helpdesk Specialist', 'Information Technology', 'Professional', 450000.00, 800000.00, 'Provides IT support to users', 'BS IT, 2-5 years helpdesk', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(37, 'IT Operations Engineer', 'Information Technology', 'Professional', 650000.00, 1200000.00, 'Manages IT infrastructure and operations', 'BS IT/Computer Science, 2-5 years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(38, 'Application Support Engineer', 'Engineering', 'Professional', 600000.00, 1100000.00, 'Provides application support and troubleshooting', 'BS Computer Science, 2-5 years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(39, 'Business Analyst', 'Product Management', 'Professional', 700000.00, 1300000.00, 'Analyzes business requirements', 'BS degree, 2-5 years BA', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(40, 'Senior Business Analyst', 'Product Management', 'Senior Professional', 950000.00, 1700000.00, 'Leads business analysis projects', 'BS degree, 5+ years BA', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(41, 'Project Manager', 'Project Management Office', 'Professional', 900000.00, 1600000.00, 'Manages IT and product projects', 'PMP, 3+ years project management', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(42, 'Senior Project Manager', 'Project Management Office', 'Senior Professional', 1200000.00, 2100000.00, 'Manages complex enterprise projects', 'PMP, 7+ years project management', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(43, 'Product Manager - AI/ML', 'Product Management', 'Professional', 1000000.00, 1800000.00, 'Manages AI product features', 'BS degree, 3+ years product', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(44, 'Technical Product Manager', 'Product Management', 'Professional', 1100000.00, 1900000.00, 'Manages technical products and APIs', 'BS Computer Science, 3+ years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(45, 'Account Executive', 'Sales', 'Professional', 700000.00, 1400000.00, 'Manages enterprise client accounts', 'Bachelors degree, 2-5 years B2B sales', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(46, 'Senior Account Executive', 'Sales', 'Senior Professional', 1000000.00, 1900000.00, 'Manages key enterprise accounts', 'Bachelors degree, 5+ years sales', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(47, 'Sales Engineer', 'Sales', 'Professional', 800000.00, 1500000.00, 'Provides technical sales support', 'BS Computer Science, 2-5 years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(48, 'Business Development Manager', 'Sales', 'Management', 1100000.00, 2000000.00, 'Identifies new business opportunities', 'Bachelors degree, 5+ years BD', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(49, 'Partnership Manager', 'Partnerships', 'Professional', 900000.00, 1600000.00, 'Manages strategic partnerships', 'Bachelors degree, 3+ years partnerships', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(50, 'Finance Manager', 'Finance & Accounting', 'Management', 1300000.00, 2300000.00, 'Manages financial planning', 'CPA, MBA, 7+ years finance', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(51, 'Accountant', 'Finance & Accounting', 'Professional', 550000.00, 1000000.00, 'Manages financial records', 'CPA/BS Accountancy, 2-5 years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(52, 'Financial Analyst', 'Finance & Accounting', 'Professional', 600000.00, 1100000.00, 'Performs financial analysis', 'BS Finance/Accounting, 2-5 years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(53, 'Compliance Manager', 'Legal & Compliance', 'Management', 1200000.00, 2200000.00, 'Ensures fintech compliance', 'Law degree/CPA, 5+ years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(54, 'IT Manager', 'Information Technology', 'Management', 1100000.00, 2100000.00, 'Manages IT infrastructure', 'BS IT/CS, 7+ years IT', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(55, 'Office Manager', 'Administration', 'Support', 500000.00, 900000.00, 'Manages office operations', 'Bachelors degree, 3+ years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(56, 'Executive Assistant', 'Administration', 'Support', 450000.00, 750000.00, 'Supports senior management', 'Bachelors degree, 2+ years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(57, 'Administrative Assistant', 'Administration', 'Entry-Level', 320000.00, 480000.00, 'Provides admin support', 'College graduate, 0-2 years', 1, '2025-11-06 14:08:02', '2025-11-06 14:08:02'),
(58, 'AI Engineer', 'Data Science', 'Professional', 800000.00, 1500000.00, 'Develops AI solutions and algorithms', 'BS Computer Science/Math/Stats, 2-5 years AI', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(59, 'Machine Learning Engineer', 'Data Science', 'Professional', 850000.00, 1600000.00, 'Implements ML models and pipelines', 'BS Computer Science/Data Science, 2-5 years ML', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(60, 'Data Scientist', 'Data Science', 'Professional', 800000.00, 1500000.00, 'Analyzes data and builds predictive models', 'BS Statistics/Math/CS, 2-5 years data science', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(61, 'NLP Engineer', 'Data Science', 'Professional', 850000.00, 1600000.00, 'Develops natural language processing solutions', 'BS Computer Science, 2-5 years NLP', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(62, 'Computer Vision Engineer', 'Data Science', 'Professional', 850000.00, 1600000.00, 'Develops computer vision and image processing', 'BS Computer Science, 2-5 years CV', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(63, 'Data Analyst', 'Business Intelligence', 'Professional', 650000.00, 1200000.00, 'Analyzes business data and creates insights', 'BS Statistics/Business/CS, 2-5 years', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(64, 'Software Engineer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops software applications and features', 'BS Computer Science, 2-5 years development', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(65, 'Software Developer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops and maintains software systems', 'BS Computer Science, 2-5 years', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(66, 'Backend Developer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops RESTful APIs and microservices', 'BS Computer Science, 2-5 years backend', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(67, 'Frontend Developer', 'Engineering', 'Professional', 650000.00, 1200000.00, 'Develops user interfaces using React/Vue/Angular', 'BS Computer Science, 2-5 years frontend', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(68, 'Full Stack Developer', 'Engineering', 'Professional', 750000.00, 1400000.00, 'Develops both frontend and backend features', 'BS Computer Science, 2-5 years full stack', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(69, 'Mobile Developer (iOS/Android)', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops iOS/Android native applications', 'BS Computer Science, 2-5 years mobile', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(70, 'React Developer', 'Engineering', 'Professional', 650000.00, 1200000.00, 'Develops web applications using React', 'BS Computer Science, 2-5 years React', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(71, 'Angular Developer', 'Engineering', 'Professional', 650000.00, 1200000.00, 'Develops web applications using Angular', 'BS Computer Science, 2-5 years Angular', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(72, 'Vue.js Developer', 'Engineering', 'Professional', 650000.00, 1200000.00, 'Develops web applications using Vue.js', 'BS Computer Science, 2-5 years Vue', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(73, 'Node.js Developer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops backend services using Node.js', 'BS Computer Science, 2-5 years Node', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(74, 'Python Developer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops applications using Python', 'BS Computer Science, 2-5 years Python', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(75, 'Java Developer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops applications using Java', 'BS Computer Science, 2-5 years Java', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(76, 'Data Engineer', 'Data Engineering', 'Professional', 750000.00, 1400000.00, 'Builds ETL pipelines and data infrastructure', 'BS Computer Science, 2-5 years data engineering', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(77, 'DevOps Engineer', 'DevOps', 'Professional', 700000.00, 1300000.00, 'Manages CI/CD pipelines and cloud infrastructure', 'BS Computer Science/IT, 2-5 years DevOps', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(78, 'Cloud Engineer', 'DevOps', 'Professional', 750000.00, 1400000.00, 'Manages AWS/GCP/Azure cloud infrastructure', 'BS Computer Science, 2-5 years cloud', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(79, 'Site Reliability Engineer (SRE)', 'DevOps', 'Professional', 800000.00, 1500000.00, 'Ensures system reliability and uptime', 'BS Computer Science, 2-5 years SRE', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(80, 'Security Engineer', 'Security & Infrastructure', 'Professional', 800000.00, 1500000.00, 'Implements security controls and monitoring', 'BS Computer Science, 2-5 years security', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(81, 'Database Administrator', 'Data Engineering', 'Professional', 700000.00, 1200000.00, 'Manages databases and data security', 'BS Computer Science, 3-5 years DBA', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(82, 'ETL Developer', 'Data Engineering', 'Professional', 700000.00, 1300000.00, 'Develops ETL processes and data pipelines', 'BS Computer Science, 2-5 years ETL', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(83, 'QA Engineer', 'Quality Assurance', 'Professional', 600000.00, 1100000.00, 'Performs testing and quality assurance', 'BS Computer Science, 2-5 years QA', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(84, 'Test Automation Engineer', 'Quality Assurance', 'Professional', 700000.00, 1300000.00, 'Develops automated testing frameworks', 'BS Computer Science, 2-5 years automation', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(85, 'QA/Test Engineer', 'Quality Assurance', 'Professional', 600000.00, 1100000.00, 'Manual and automated testing', 'BS Computer Science, 2-5 years testing', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(86, 'QA Automation Engineer', 'Quality Assurance', 'Professional', 700000.00, 1300000.00, 'Automates testing processes', 'BS Computer Science, 2-5 years QA automation', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(87, 'IT Operations Engineer', 'Information Technology', 'Professional', 650000.00, 1200000.00, 'Manages IT infrastructure and operations', 'BS IT/Computer Science, 2-5 years IT ops', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(88, 'Application Support Engineer', 'Engineering', 'Professional', 600000.00, 1100000.00, 'Provides application support and troubleshooting', 'BS Computer Science, 2-5 years support', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(89, 'IT Helpdesk Specialist', 'Information Technology', 'Professional', 450000.00, 800000.00, 'Provides technical support to users', 'BS IT/Computer Science, 2-5 years helpdesk', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(90, 'Systems Administrator', 'Information Technology', 'Professional', 600000.00, 1100000.00, 'Manages IT systems and infrastructure', 'BS IT/Computer Science, 2-5 years sysadmin', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(91, 'Network Engineer', 'Information Technology', 'Professional', 650000.00, 1200000.00, 'Manages network infrastructure', 'BS IT/Computer Science, 2-5 years networking', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(92, 'Product Manager', 'Product Management', 'Professional', 1000000.00, 1800000.00, 'Manages product features and roadmap', 'BS degree, 3-5 years product management', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(93, 'Technical Product Manager', 'Product Management', 'Professional', 1100000.00, 1900000.00, 'Manages technical products and APIs', 'BS Computer Science, 3-5 years product/tech', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(94, 'Project Manager', 'Project Management Office', 'Professional', 900000.00, 1600000.00, 'Manages IT and product projects', 'PMP/BS degree, 3-5 years project management', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(95, 'Scrum Master', 'Project Management Office', 'Professional', 850000.00, 1500000.00, 'Facilitates agile development processes', 'CSM/BS degree, 3-5 years Scrum', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(96, 'Business Analyst', 'Product Management', 'Professional', 700000.00, 1300000.00, 'Analyzes business requirements and processes', 'BS degree, 2-5 years business analysis', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(97, 'Product Owner', 'Product Management', 'Professional', 900000.00, 1600000.00, 'Manages product backlog and priorities', 'BS degree, 3-5 years product', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(98, 'Junior Data Scientist', 'Data Science', 'Associate', 450000.00, 750000.00, 'Assists with data analysis and modeling', 'BS Statistics/Math/CS, 0-2 years', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(99, 'Junior Software Engineer', 'Engineering', 'Associate', 400000.00, 700000.00, 'Assists with software development', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(100, 'Junior Data Engineer', 'Data Engineering', 'Associate', 420000.00, 720000.00, 'Assists with data pipeline development', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(101, 'Junior QA Engineer', 'Quality Assurance', 'Associate', 380000.00, 650000.00, 'Performs testing and bug tracking', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(102, 'Junior Business Analyst', 'Product Management', 'Associate', 400000.00, 700000.00, 'Assists with requirements gathering', 'BS degree, 0-2 years', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(103, 'Junior DevOps Engineer', 'DevOps', 'Associate', 400000.00, 700000.00, 'Assists with DevOps tasks and automation', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(104, 'Junior Frontend Developer', 'Engineering', 'Associate', 380000.00, 650000.00, 'Assists with frontend development', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(105, 'Junior Backend Developer', 'Engineering', 'Associate', 400000.00, 700000.00, 'Assists with backend development', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(106, 'Junior Mobile Developer', 'Engineering', 'Associate', 400000.00, 700000.00, 'Assists with mobile app development', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(107, 'Associate Data Analyst', 'Business Intelligence', 'Associate', 380000.00, 650000.00, 'Assists with data analysis and reporting', 'BS Statistics/Business, 0-2 years', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(108, 'Software Engineer Intern', 'Engineering', 'Intern', 20000.00, 30000.00, 'Internship program for software development', 'BS Computer Science student, 3rd-4th year', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(109, 'Data Science Intern', 'Data Science', 'Intern', 20000.00, 30000.00, 'Internship program for data science', 'BS Data Science/Statistics student', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(110, 'QA Intern', 'Quality Assurance', 'Intern', 18000.00, 25000.00, 'Internship program for quality assurance', 'BS Computer Science student', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(111, 'DevOps Intern', 'DevOps', 'Intern', 20000.00, 30000.00, 'Internship program for DevOps', 'BS Computer Science/IT student', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(112, 'Product Management Intern', 'Product Management', 'Intern', 20000.00, 30000.00, 'Internship program for product management', 'BS any degree, 3rd-4th year', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(113, 'IT Support Intern', 'Information Technology', 'Intern', 18000.00, 25000.00, 'Internship program for IT support', 'BS IT/Computer Science student', 1, '2025-11-06 14:16:27', '2025-11-06 14:16:27'),
(114, 'AI Engineer', 'Data Science', 'Professional', 800000.00, 1500000.00, 'Develops AI solutions and algorithms', 'BS Computer Science/Math/Stats, 2-5 years AI', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(115, 'Machine Learning Engineer', 'Data Science', 'Professional', 850000.00, 1600000.00, 'Implements ML models and pipelines', 'BS Computer Science/Data Science, 2-5 years ML', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(116, 'Data Scientist', 'Data Science', 'Professional', 800000.00, 1500000.00, 'Analyzes data and builds predictive models', 'BS Statistics/Math/CS, 2-5 years data science', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(117, 'NLP Engineer', 'Data Science', 'Professional', 850000.00, 1600000.00, 'Develops natural language processing solutions', 'BS Computer Science, 2-5 years NLP', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(118, 'Computer Vision Engineer', 'Data Science', 'Professional', 850000.00, 1600000.00, 'Develops computer vision and image processing', 'BS Computer Science, 2-5 years CV', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(119, 'Data Analyst', 'Business Intelligence', 'Professional', 650000.00, 1200000.00, 'Analyzes business data and creates insights', 'BS Statistics/Business/CS, 2-5 years', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(120, 'Software Engineer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops software applications and features', 'BS Computer Science, 2-5 years development', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(121, 'Software Developer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops and maintains software systems', 'BS Computer Science, 2-5 years', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(122, 'Backend Developer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops RESTful APIs and microservices', 'BS Computer Science, 2-5 years backend', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(123, 'Frontend Developer', 'Engineering', 'Professional', 650000.00, 1200000.00, 'Develops user interfaces using React/Vue/Angular', 'BS Computer Science, 2-5 years frontend', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(124, 'Full Stack Developer', 'Engineering', 'Professional', 750000.00, 1400000.00, 'Develops both frontend and backend features', 'BS Computer Science, 2-5 years full stack', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(125, 'Mobile Developer (iOS/Android)', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops iOS/Android native applications', 'BS Computer Science, 2-5 years mobile', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(126, 'React Developer', 'Engineering', 'Professional', 650000.00, 1200000.00, 'Develops web applications using React', 'BS Computer Science, 2-5 years React', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(127, 'Angular Developer', 'Engineering', 'Professional', 650000.00, 1200000.00, 'Develops web applications using Angular', 'BS Computer Science, 2-5 years Angular', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(128, 'Vue.js Developer', 'Engineering', 'Professional', 650000.00, 1200000.00, 'Develops web applications using Vue.js', 'BS Computer Science, 2-5 years Vue', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(129, 'Node.js Developer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops backend services using Node.js', 'BS Computer Science, 2-5 years Node', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(130, 'Python Developer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops applications using Python', 'BS Computer Science, 2-5 years Python', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(131, 'Java Developer', 'Engineering', 'Professional', 700000.00, 1300000.00, 'Develops applications using Java', 'BS Computer Science, 2-5 years Java', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(132, 'Data Engineer', 'Data Engineering', 'Professional', 750000.00, 1400000.00, 'Builds ETL pipelines and data infrastructure', 'BS Computer Science, 2-5 years data engineering', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(133, 'DevOps Engineer', 'DevOps', 'Professional', 700000.00, 1300000.00, 'Manages CI/CD pipelines and cloud infrastructure', 'BS Computer Science/IT, 2-5 years DevOps', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(134, 'Cloud Engineer', 'DevOps', 'Professional', 750000.00, 1400000.00, 'Manages AWS/GCP/Azure cloud infrastructure', 'BS Computer Science, 2-5 years cloud', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(135, 'Site Reliability Engineer (SRE)', 'DevOps', 'Professional', 800000.00, 1500000.00, 'Ensures system reliability and uptime', 'BS Computer Science, 2-5 years SRE', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(136, 'Security Engineer', 'Security & Infrastructure', 'Professional', 800000.00, 1500000.00, 'Implements security controls and monitoring', 'BS Computer Science, 2-5 years security', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(137, 'Database Administrator', 'Data Engineering', 'Professional', 700000.00, 1200000.00, 'Manages databases and data security', 'BS Computer Science, 3-5 years DBA', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(138, 'ETL Developer', 'Data Engineering', 'Professional', 700000.00, 1300000.00, 'Develops ETL processes and data pipelines', 'BS Computer Science, 2-5 years ETL', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(139, 'QA Engineer', 'Quality Assurance', 'Professional', 600000.00, 1100000.00, 'Performs testing and quality assurance', 'BS Computer Science, 2-5 years QA', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(140, 'Test Automation Engineer', 'Quality Assurance', 'Professional', 700000.00, 1300000.00, 'Develops automated testing frameworks', 'BS Computer Science, 2-5 years automation', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(141, 'QA/Test Engineer', 'Quality Assurance', 'Professional', 600000.00, 1100000.00, 'Manual and automated testing', 'BS Computer Science, 2-5 years testing', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(142, 'QA Automation Engineer', 'Quality Assurance', 'Professional', 700000.00, 1300000.00, 'Automates testing processes', 'BS Computer Science, 2-5 years QA automation', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(143, 'IT Operations Engineer', 'Information Technology', 'Professional', 650000.00, 1200000.00, 'Manages IT infrastructure and operations', 'BS IT/Computer Science, 2-5 years IT ops', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(144, 'Application Support Engineer', 'Engineering', 'Professional', 600000.00, 1100000.00, 'Provides application support and troubleshooting', 'BS Computer Science, 2-5 years support', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(145, 'IT Helpdesk Specialist', 'Information Technology', 'Professional', 450000.00, 800000.00, 'Provides technical support to users', 'BS IT/Computer Science, 2-5 years helpdesk', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(146, 'Systems Administrator', 'Information Technology', 'Professional', 600000.00, 1100000.00, 'Manages IT systems and infrastructure', 'BS IT/Computer Science, 2-5 years sysadmin', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(147, 'Network Engineer', 'Information Technology', 'Professional', 650000.00, 1200000.00, 'Manages network infrastructure', 'BS IT/Computer Science, 2-5 years networking', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(148, 'Product Manager', 'Product Management', 'Professional', 1000000.00, 1800000.00, 'Manages product features and roadmap', 'BS degree, 3-5 years product management', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(149, 'Technical Product Manager', 'Product Management', 'Professional', 1100000.00, 1900000.00, 'Manages technical products and APIs', 'BS Computer Science, 3-5 years product/tech', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(150, 'Project Manager', 'Project Management Office', 'Professional', 900000.00, 1600000.00, 'Manages IT and product projects', 'PMP/BS degree, 3-5 years project management', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(151, 'Scrum Master', 'Project Management Office', 'Professional', 850000.00, 1500000.00, 'Facilitates agile development processes', 'CSM/BS degree, 3-5 years Scrum', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(152, 'Business Analyst', 'Product Management', 'Professional', 700000.00, 1300000.00, 'Analyzes business requirements and processes', 'BS degree, 2-5 years business analysis', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(153, 'Product Owner', 'Product Management', 'Professional', 900000.00, 1600000.00, 'Manages product backlog and priorities', 'BS degree, 3-5 years product', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(154, 'Junior Data Scientist', 'Data Science', 'Associate', 450000.00, 750000.00, 'Assists with data analysis and modeling', 'BS Statistics/Math/CS, 0-2 years', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(155, 'Junior Software Engineer', 'Engineering', 'Associate', 400000.00, 700000.00, 'Assists with software development', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(156, 'Junior Data Engineer', 'Data Engineering', 'Associate', 420000.00, 720000.00, 'Assists with data pipeline development', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(157, 'Junior QA Engineer', 'Quality Assurance', 'Associate', 380000.00, 650000.00, 'Performs testing and bug tracking', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(158, 'Junior Business Analyst', 'Product Management', 'Associate', 400000.00, 700000.00, 'Assists with requirements gathering', 'BS degree, 0-2 years', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(159, 'Junior DevOps Engineer', 'DevOps', 'Associate', 400000.00, 700000.00, 'Assists with DevOps tasks and automation', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(160, 'Junior Frontend Developer', 'Engineering', 'Associate', 380000.00, 650000.00, 'Assists with frontend development', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(161, 'Junior Backend Developer', 'Engineering', 'Associate', 400000.00, 700000.00, 'Assists with backend development', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(162, 'Junior Mobile Developer', 'Engineering', 'Associate', 400000.00, 700000.00, 'Assists with mobile app development', 'BS Computer Science, 0-2 years', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(163, 'Associate Data Analyst', 'Business Intelligence', 'Associate', 380000.00, 650000.00, 'Assists with data analysis and reporting', 'BS Statistics/Business, 0-2 years', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(164, 'Software Engineer Intern', 'Engineering', 'Intern', 20000.00, 30000.00, 'Internship program for software development', 'BS Computer Science student, 3rd-4th year', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(165, 'Data Science Intern', 'Data Science', 'Intern', 20000.00, 30000.00, 'Internship program for data science', 'BS Data Science/Statistics student', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(166, 'QA Intern', 'Quality Assurance', 'Intern', 18000.00, 25000.00, 'Internship program for quality assurance', 'BS Computer Science student', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(167, 'DevOps Intern', 'DevOps', 'Intern', 20000.00, 30000.00, 'Internship program for DevOps', 'BS Computer Science/IT student', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(168, 'Product Management Intern', 'Product Management', 'Intern', 20000.00, 30000.00, 'Internship program for product management', 'BS any degree, 3rd-4th year', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09'),
(169, 'IT Support Intern', 'Information Technology', 'Intern', 18000.00, 25000.00, 'Internship program for IT support', 'BS IT/Computer Science student', 1, '2025-11-06 14:18:09', '2025-11-06 14:18:09');

-- --------------------------------------------------------

--
-- Table structure for table `status_history`
--

CREATE TABLE `status_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ticket_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status_history`
--

INSERT INTO `status_history` (`id`, `ticket_id`, `old_status`, `new_status`, `updated_by`, `notes`, `created_at`) VALUES
(7, 9, NULL, 'pending', 'employee003', NULL, '2025-10-30 08:39:08'),
(8, 10, NULL, 'pending', 'employee003', NULL, '2025-10-30 10:58:55'),
(9, 11, NULL, 'pending', 'employee003', NULL, '2025-10-30 12:52:23'),
(10, 12, NULL, 'pending', 'employee03', NULL, '2025-10-30 15:36:07'),
(12, 14, NULL, 'pending', 'employee003', NULL, '2025-11-03 09:55:48'),
(13, 15, NULL, 'pending', 'employee003', NULL, '2025-11-04 04:54:23'),
(14, 16, NULL, 'pending', 'employee003', NULL, '2025-11-04 04:58:40'),
(15, 17, NULL, 'pending', 'employee003', NULL, '2025-11-04 05:01:49'),
(16, 18, NULL, 'pending', 'employee003', NULL, '2025-11-04 05:10:40'),
(17, 19, NULL, 'pending', 'employee003', NULL, '2025-11-04 05:22:32'),
(18, 20, NULL, 'pending', 'employee03', NULL, '2025-11-07 05:08:23'),
(19, 21, NULL, 'pending', 'employee03', NULL, '2025-11-07 08:53:24');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'medium',
  `status` varchar(50) DEFAULT 'pending',
  `assigned_to` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `progress` int(11) DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_number`, `employee_id`, `category_id`, `title`, `description`, `attachment`, `priority`, `status`, `assigned_to`, `created_at`, `updated_at`, `progress`, `is_archived`, `archived_at`) VALUES
(9, 'TKT-20251030-25CDEA', 'employee003', 6, 'asdasdsa', 'dasdadad', NULL, 'low', 'pending', NULL, '2025-10-30 08:39:08', '2025-10-30 08:39:08', 0, 0, NULL),
(10, 'TKT-20251030-495D00', 'employee003', 7, 'sdsd', 'sdsds', NULL, 'medium', 'pending', NULL, '2025-10-30 10:58:55', '2025-10-30 10:58:55', 0, 0, NULL),
(11, 'TKT-20251030-3F023D', 'employee003', 7, 'asdada', 'dasdadas', NULL, 'high', 'in progress', NULL, '2025-10-30 12:52:23', '2025-10-30 12:55:17', 0, 0, NULL),
(12, 'TKT-20251030-7E00EC', 'employee03', 1, '123456', 'ediwowowoww', NULL, 'high', 'closed', NULL, '2025-10-30 15:36:07', '2025-11-02 03:43:28', 0, 0, NULL),
(14, 'TKT-20251103-633FAB', 'employee003', 2, 'asdasda', 'dasdasdas', NULL, 'medium', 'pending', NULL, '2025-11-03 09:55:48', '2025-11-03 09:55:48', 0, 0, NULL),
(15, 'TKT-20251104-642D69', 'employee003', 3, 'sdada', 'dadsadas', NULL, 'medium', 'pending', NULL, '2025-11-04 04:54:23', '2025-11-04 04:54:23', 0, 0, NULL),
(16, 'TKT-20251104-301759', 'employee003', 7, 'asdas', 'asdasd', NULL, 'medium', 'pending', NULL, '2025-11-04 04:58:40', '2025-11-04 04:58:40', 0, 0, NULL),
(17, 'TKT-20251104-74C463', 'employee003', 3, 'asadsa', 'asdasda', NULL, 'medium', 'pending', NULL, '2025-11-04 05:01:49', '2025-11-04 05:01:49', 0, 0, NULL),
(18, 'TKT-20251104-2BB146', 'employee003', 4, 'as', 'as', NULL, 'low', 'pending', NULL, '2025-11-04 05:10:40', '2025-11-04 05:10:40', 0, 0, NULL),
(19, 'TKT-20251104-F196D3', 'employee003', 4, 'asdsa', 'asdadsas', NULL, 'medium', 'closed', NULL, '2025-11-04 05:22:32', '2025-11-07 04:14:05', 0, 0, NULL),
(20, 'TKT-20251107-9E2F16', 'employee03', 1, 'Cancer sa tenga', 'hindi ako makahinga', NULL, 'high', 'closed', NULL, '2025-11-07 05:08:23', '2025-11-07 05:15:58', 0, 1, '2025-11-07 05:16:14'),
(21, 'TKT-20251107-2DB0EB', 'employee03', 1, 'agasfasda', 'asdfgghf', NULL, 'high', 'closed', NULL, '2025-11-07 08:53:24', '2025-11-07 09:03:00', 0, 1, '2025-11-07 09:03:16');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_categories`
--

CREATE TABLE `ticket_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_categories`
--

INSERT INTO `ticket_categories` (`id`, `category_name`, `description`, `created_at`) VALUES
(1, 'Leave Request', 'Vacation, sick leave, personal leave requests', '2025-10-05 15:44:29'),
(2, 'Payroll Inquiry', 'Salary, benefits, deductions related questions', '2025-10-05 15:44:29'),
(3, 'IT Support', 'Computer, software, hardware, network issues', '2025-10-05 15:44:29'),
(4, 'Training Request', 'Professional development and training requests', '2025-10-05 15:44:29'),
(5, 'Workplace Concern', 'Workplace safety, harassment, conflicts', '2025-10-05 15:44:29'),
(6, 'Benefits Inquiry', 'Health insurance, retirement, other benefits', '2025-10-05 15:44:29'),
(7, 'Equipment Request', 'Office supplies, equipment needs', '2025-10-05 15:44:29'),
(8, 'Policy Question', 'Company policies and procedures', '2025-10-05 15:44:29'),
(9, 'Other', 'General concerns and requests', '2025-10-05 15:44:29');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_responses`
--

CREATE TABLE `ticket_responses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ticket_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `response_text` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_responses`
--

INSERT INTO `ticket_responses` (`id`, `ticket_id`, `employee_id`, `response_text`, `is_internal`, `created_at`) VALUES
(11, 10, '422002152', 'ADIK', 0, '2025-10-30 12:02:14'),
(12, 11, '422002152', 'ADIK', 0, '2025-10-30 12:54:25'),
(13, 12, '422002152', 'okay', 0, '2025-11-01 15:20:20'),
(14, 19, '422002152', 'dsdsfdssddfdfdfdf', 0, '2025-11-06 08:13:55'),
(15, 20, '422002152', 'kupal', 0, '2025-11-07 05:12:38'),
(16, 21, '422002152', 'hi', 0, '2025-11-07 08:59:40');

-- --------------------------------------------------------

--
-- Table structure for table `work_schedules`
--

CREATE TABLE `work_schedules` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `schedule_date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `status` enum('present','absent','leave','wfh') DEFAULT 'present',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_schedules`
--

INSERT INTO `work_schedules` (`id`, `employee_id`, `schedule_date`, `check_in`, `check_out`, `hours_worked`, `status`, `created_at`) VALUES
(1, 'HR001', '2025-10-30', '08:00:00', '17:00:00', 8.00, 'present', '2025-10-30 05:41:35'),
(2, '422002152', '2025-10-30', '08:30:00', '17:30:00', 8.00, 'present', '2025-10-30 05:41:35'),
(3, 'employee003', '2025-10-30', NULL, NULL, 0.00, 'leave', '2025-10-30 05:41:35'),
(4, 'employee003', '2025-11-07', '12:25:07', '12:27:40', 0.00, 'present', '2025-11-07 04:25:07'),
(5, 'employee03', '2025-11-07', '12:28:02', '12:45:25', 0.00, 'present', '2025-11-07 04:28:02'),
(6, 'employee03', '2025-11-07', '12:45:28', '12:57:31', 0.00, 'present', '2025-11-07 04:45:28'),
(7, 'employee03', '2025-11-07', '12:57:35', '12:57:49', 0.00, 'present', '2025-11-07 04:57:35'),
(8, 'employee03', '2025-11-07', '13:07:02', '13:07:10', 0.00, 'present', '2025-11-07 05:07:02'),
(9, 'employee03', '2025-11-07', '16:52:14', '16:52:26', 0.00, 'present', '2025-11-07 08:52:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `announcement_comments`
--
ALTER TABLE `announcement_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcement` (`announcement_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `chatbot_responses`
--
ALTER TABLE `chatbot_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trigger_keyword` (`trigger_keyword`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `idx_position_id` (`position_id`);

--
-- Indexes for table `hr_notifications`
--
ALTER TABLE `hr_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hr_id` (`hr_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `job_applicants`
--
ALTER TABLE `job_applicants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_employee` (`employee_id`),
  ADD KEY `fk_notifications_ticket` (`ticket_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_employee_id` (`employee_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `status_history`
--
ALTER TABLE `status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_history_ticket` (`ticket_id`),
  ADD KEY `fk_status_history_employee` (`updated_by`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `idx_tickets_employee` (`employee_id`),
  ADD KEY `idx_tickets_status` (`status`),
  ADD KEY `idx_tickets_category` (`category_id`),
  ADD KEY `fk_tickets_assigned` (`assigned_to`);

--
-- Indexes for table `ticket_categories`
--
ALTER TABLE `ticket_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ticket_responses`
--
ALTER TABLE `ticket_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_responses_ticket` (`ticket_id`),
  ADD KEY `fk_ticket_responses_employee` (`employee_id`);

--
-- Indexes for table `work_schedules`
--
ALTER TABLE `work_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `announcement_comments`
--
ALTER TABLE `announcement_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `chatbot_responses`
--
ALTER TABLE `chatbot_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `hr_notifications`
--
ALTER TABLE `hr_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `job_applicants`
--
ALTER TABLE `job_applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `status_history`
--
ALTER TABLE `status_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `ticket_categories`
--
ALTER TABLE `ticket_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ticket_responses`
--
ALTER TABLE `ticket_responses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `work_schedules`
--
ALTER TABLE `work_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `announcement_comments`
--
ALTER TABLE `announcement_comments`
  ADD CONSTRAINT `announcement_comments_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notifications_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `status_history`
--
ALTER TABLE `status_history`
  ADD CONSTRAINT `fk_status_history_employee` FOREIGN KEY (`updated_by`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_status_history_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_category` FOREIGN KEY (`category_id`) REFERENCES `ticket_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tickets_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ticket_responses`
--
ALTER TABLE `ticket_responses`
  ADD CONSTRAINT `fk_ticket_responses_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ticket_responses_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `work_schedules`
--
ALTER TABLE `work_schedules`
  ADD CONSTRAINT `work_schedules_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
