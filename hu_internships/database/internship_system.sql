-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2026 at 08:17 PM
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
-- Database: `internship_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `company_id` varchar(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`company_id`, `name`, `address`, `phone`, `email`) VALUES
('CORP001', 'บริษัท ABC Digital', '123 อาคารซอฟต์แวร์พาร์ค ชั้น 10 ถ.แจ้งวัฒนะ นนทบุรี', '02-123-4567', 'contact@abcdigital.co.th'),
('CORP002', 'บริษัท Tech Solutions', '45/1 อาคารทีที คอร์ปอเรชัน ถ.สุขุมวิท กรุงเทพฯ 10110', '02-987-6543', 'info@techsolutions.com'),
('CORP003', 'บริษัท Creative Studio', '888 ยูนิต 4C แขวงคลองตันเหนือ เขตวัฒนา กรุงเทพฯ', '02-555-0123', 'hello@creativestudio.design'),
('CORP004', 'บริษัท Finance Plus', '99 อาคารสาทรทาวเวอร์ ชั้น 25 ถ.สาทรใต้ กรุงเทพฯ', '02-444-8888', 'support@financeplus.co.th'),
('CORP005', 'บริษัท Smart Logistics', '212 หมู่ 5 นิคมอุตสาหกรรมบางปู จ.สมุทรปราการ 10280', '02-333-7777', 'ops@smartlogistics.net'),
('CORP006', 'บริษัท Media Hub', '50/3 ซอยอารีย์ 5 ถ.พหลโยธิน เขตพญาไท กรุงเทพฯ', '02-222-9999', 'marketing@mediahub.co.th'),
('CORP007', 'บริษัท HealthCare จำกัด', '777 ถ.พระราม 9 แขวงบางกะปิ เขตห้วยขวาง กรุงเทพฯ', '02-111-0000', 'service@healthcare.co.th'),
('CORP008', 'บริษัท E-Commerce Pro', '10/10 อาคารดิจิทัลเกตเวย์ ถ.พระราม 1 กรุงเทพฯ 10330', '02-666-5555', 'sales@ecommercepro.com'),
('CORP009', 'บริษัท Data Insight', '152 อาคารวิทยุ ชั้น 12 ถ.วิทยุ เขตปทุมวัน กรุงเทพฯ', '02-777-4444', 'data@datainsight.ai'),
('CORP010', 'บริษัท Green Energy', '33 หมู่ 2 ถ.ราชพฤกษ์ ต.บางรักน้อย จ.นนทบุรี 11000', '02-888-3333', 'eco@greenenergy.org');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` varchar(10) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `credits` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`, `credits`) VALUES
('IS101', 'การพัฒนาทรัพยากรสารสนเทศ', '3'),
('IS111', 'การรู้สารสนเทศและรู้เท่าทันสื่อ', '3'),
('IS112', 'การจัดการบริการสารสนเทศ', '3'),
('IS113', 'บริการสนเทศเฉพาะกลุ่ม', '3'),
('IS114', 'จิตวิทยาการบริการสารสนเทศ', '3'),
('IS121', 'การพัฒนาโปรแกรมคอมพิวเตอร์', '3'),
('IS201', 'การทำรายการและการจัดหมวดหมู่', '3'),
('IS202', 'การทำรายการและการจัดหมวดหมู่ขั้นสูง', '3'),
('IS222', 'ระบบจัดการฐานข้อมูล', '3'),
('IS223', 'ระบบจัดการฐานข้อมูล', '3'),
('IS224', 'ระบบจัดการฐานข้อมูล', '3'),
('IS311', 'ระบบห้องสมุดดิจิทัล', '3'),
('IS312', 'การพัฒนานวัตกรรมทางวิชาชีพสารสนเทศ', '3'),
('IS321', 'การจัดเก็บและค้นคืนสารสนเทศ', '3'),
('IS322', 'การวิเคราะห์ข้อมูลและการเล่าเรื่องข้อมูล', '3'),
('IS323', 'การขับเคลื่อนองค์กรสารสนเทศด้วยข้อมูล', '3'),
('IS431', 'การวิจัยพื้นฐานสำหรับวิชาชีพสารสนเทศ', '2'),
('IS432', 'สัมมนาสารสนเทศศึกษา', '2'),
('IS441', 'การฝึกประสบการณ์วิชาชีพสารสนเทศ', '6'),
('IS442', 'โครงงานอาชีพ', '6'),
('IS443', 'เตรียมความพร้อมสหกิจศึกษา', '1'),
('IS444', 'สหกิจศึกษา', '6');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `en_id` varchar(10) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `course_id` varchar(10) NOT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `year` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`en_id`, `student_id`, `course_id`, `semester`, `year`) VALUES
('1', '63123470', 'IS431', '1', '2568'),
('10', '63123472', 'IS432', '1', '2568'),
('11', '63123472', 'IS443', '1', '2568'),
('12', '63123472', 'IS441', '2', '2568'),
('13', '63123473', 'IS431', '1', '2568'),
('14', '63123473', 'IS432', '1', '2568'),
('15', '63123473', 'IS443', '1', '2568'),
('16', '63123473', 'IS444', '2', '2568'),
('17', '63123474', 'IS431', '1', '2568'),
('18', '63123474', 'IS432', '1', '2568'),
('19', '63123474', 'IS443', '1', '2568'),
('2', '63123470', 'IS432', '1', '2568'),
('20', '63123474', 'IS441', '2', '2568'),
('21', '63123475', 'IS431', '1', '2568'),
('22', '63123475', 'IS432', '1', '2568'),
('23', '63123475', 'IS443', '1', '2568'),
('24', '63123475', 'IS442', '2', '2568'),
('25', '63123476', 'IS431', '1', '2568'),
('26', '63123476', 'IS432', '1', '2568'),
('27', '63123476', 'IS443', '1', '2568'),
('28', '63123476', 'IS441', '2', '2568'),
('29', '63123477', 'IS431', '1', '2568'),
('3', '63123470', 'IS443', '1', '2568'),
('30', '63123477', 'IS432', '1', '2568'),
('31', '63123477', 'IS443', '1', '2568'),
('32', '63123477', 'IS444', '2', '2568'),
('33', '63123478', 'IS431', '1', '2568'),
('34', '63123478', 'IS432', '1', '2568'),
('35', '63123478', 'IS443', '1', '2568'),
('36', '63123478', 'IS442', '2', '2568'),
('37', '63123479', 'IS431', '1', '2568'),
('38', '63123479', 'IS432', '1', '2568'),
('39', '63123479', 'IS443', '1', '2568'),
('4', '63123470', 'IS441', '2', '2568'),
('40', '63123479', 'IS444', '2', '2568'),
('5', '63123471', 'IS431', '1', '2568'),
('6', '63123471', 'IS432', '1', '2568'),
('7', '63123471', 'IS443', '1', '2568'),
('8', '63123471', 'IS441', '2', '2568'),
('9', '63123472', 'IS431', '1', '2568');

-- --------------------------------------------------------

--
-- Table structure for table `internship_requests`
--

CREATE TABLE `internship_requests` (
  `request_id` varchar(10) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `company_id` varchar(20) NOT NULL,
  `status` enum('รับคำขอ','กำลังดำเนินการ','อนุมัติ') NOT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staffs`
--

CREATE TABLE `staffs` (
  `staff_id` varchar(10) NOT NULL,
  `u_id` varchar(30) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staffs`
--

INSERT INTO `staffs` (`staff_id`, `u_id`, `first_name`, `department`, `last_name`, `phone`, `email`) VALUES
('69110001', 'ADM69110001', 'สมชาย', 'งานทะเบียน', 'สายวิชาการ', '081-234-5678', 'somchai.s@g.swu.ac.th');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` varchar(20) NOT NULL,
  `u_id` varchar(10) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `major` varchar(100) NOT NULL,
  `year_level` varchar(10) DEFAULT NULL,
  `gpa` varchar(10) DEFAULT NULL,
  `advisor` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `u_id`, `full_name`, `email`, `phone`, `major`, `year_level`, `gpa`, `advisor`) VALUES
('63123470', 'is63123470', 'กิตติพงษ์ ศรีสวัสดิ์', 'kittipong.s@g.swu.ac.th', '0842203176', 'สารสนเทศศึกษา', '1', '3.82', 'ดิษฐู'),
('63123471', 'is63123471', 'พิมพ์ชนก วัฒนกุล', 'pimchanok.w@g.swu.ac.th', '0644677539', 'สารสนเทศศึกษา', '1', '3.45', 'ดิษฐู'),
('63123472', 'is63123472', 'ธนกร จิตต์ประเสริฐ', 'thanakorn.j@g.swu.ac.th', '0899311232', 'สารสนเทศศึกษา', '2', '2.96', 'ดิษฐู'),
('63123473', 'is63123473', 'ชลธิชา ภู่วิไล', 'cholthicha.p@g.swu.ac.th', '0956428950', 'สารสนเทศศึกษา', '2', '3.21', 'ดิษฐู'),
('63123474', 'is63123474', 'ณัฐวุฒิ แสงทอง', 'nattawut.s@g.swu.ac.th', '0655527996', 'สารสนเทศศึกษา', '2', '3.58', 'ดิษฐู'),
('63123475', 'is63123475', 'ศิริลักษณ์ ทองดี', 'sirilak.t@g.swu.ac.th', '0988675478', 'สารสนเทศศึกษา', '3', '3.74', 'ดิษฐู'),
('63123476', 'is63123476', 'ภูมิพัฒน์ อินทรชัย', 'phumipat.i@g.swu.ac.th', '0885631799', 'สารสนเทศศึกษา', '3', '2.88', 'ดิษฐู'),
('63123477', 'is63123477', 'อริสรา แก้วมณี', 'arisara.k@g.swu.ac.th', '0674245765', 'สารสนเทศศึกษา', '4', '3.10', 'ดิษฐู'),
('63123478', 'is63123478', 'ปกรณ์ ตั้งสุวรรณ', 'pakorn.t@g.swu.ac.th', '0976886432', 'สารสนเทศศึกษา', '4', '4.00', 'ดิษฐู'),
('63123479', 'is63123479', 'สุภัสสรา มีสุข', 'supatsara.m@g.swu.ac.th', '0874827976', 'สารสนเทศศึกษา', '4', '3.96', 'ดิษฐู');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` varchar(20) NOT NULL,
  `u_id` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `u_id`, `first_name`, `last_name`, `email`, `phone`) VALUES
('94258546742', 'tl94258546742', 'ดิษฐ์', 'สุทธิวงศ์', 'dit@g.swu.ac.th', '874884653');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `u_id` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`u_id`, `username`, `password`, `role`) VALUES
('ADM69110001', '69110001', '1234', 'admin'),
('is63123470', '63123470', '1234', 'student'),
('is63123471', '63123471', '1234', 'student'),
('is63123472', '63123472', '1234', 'student'),
('is63123473', '63123473', '1234', 'student'),
('is63123474', '63123474', '1234', 'student'),
('is63123475', '63123475', '1234', 'student'),
('is63123476', '63123476', '1234', 'student'),
('is63123477', '63123477', '1234', 'student'),
('is63123478', '63123478', '1234', 'student'),
('is63123479', '63123479', '1234', 'student'),
('tl94258546742', '94258546742', '1234', 'teacher');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`company_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`en_id`),
  ADD KEY `fk_en_student` (`student_id`),
  ADD KEY `fk_en_course` (`course_id`);

--
-- Indexes for table `internship_requests`
--
ALTER TABLE `internship_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `fk_req_student` (`student_id`),
  ADD KEY `fk_req_company` (`company_id`);

--
-- Indexes for table `staffs`
--
ALTER TABLE `staffs`
  ADD PRIMARY KEY (`staff_id`),
  ADD KEY `fk_staff_user` (`u_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `fk_student_user` (`u_id`),
  ADD KEY `fk_student_advisor` (`advisor`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD KEY `fk_teacher_user` (`u_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`u_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `fk_en_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_en_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `internship_requests`
--
ALTER TABLE `internship_requests`
  ADD CONSTRAINT `fk_req_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  ADD CONSTRAINT `fk_req_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
