-- Create and select the database
CREATE DATABASE IF NOT EXISTS `sms_db`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `sms_db`;

-- ============================================================
-- 1. USERS — Central authentication table (all roles)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `full_name`  VARCHAR(100) NOT NULL,
    `email`      VARCHAR(100) NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `role`       ENUM('admin','lecturer','student') NOT NULL DEFAULT 'student',
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. STUDENTS — Extends users with student-specific data
-- ============================================================
CREATE TABLE IF NOT EXISTS `students` (
    `student_id`      INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`         INT(11)      NOT NULL,
    `registration_no` VARCHAR(50)  NOT NULL,
    `department`      VARCHAR(100) NOT NULL,
    `intake_year`     YEAR(4)      NOT NULL,
    `status`          ENUM('active','suspended','graduated') DEFAULT 'active',
    PRIMARY KEY (`student_id`),
    UNIQUE KEY `uk_students_reg` (`registration_no`),
    KEY `fk_students_user` (`user_id`),
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. LECTURERS — Extends users with lecturer-specific data
-- ============================================================
CREATE TABLE IF NOT EXISTS `lecturers` (
    `lecturer_id` INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`     INT(11)      NOT NULL,
    `department`  VARCHAR(100) NOT NULL,
    PRIMARY KEY (`lecturer_id`),
    KEY `fk_lecturers_user` (`user_id`),
    CONSTRAINT `fk_lecturers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. COURSES
-- ============================================================
CREATE TABLE IF NOT EXISTS `courses` (
    `course_id`   INT(11)      NOT NULL AUTO_INCREMENT,
    `course_code` VARCHAR(20)  NOT NULL,
    `course_name` VARCHAR(150) NOT NULL,
    `credits`     INT(11)      NOT NULL DEFAULT 3,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`course_id`),
    UNIQUE KEY `uk_courses_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. COURSE_ASSIGNMENTS — Links lecturers to courses they teach
-- ============================================================
CREATE TABLE IF NOT EXISTS `course_assignments` (
    `assignment_id` INT(11) NOT NULL AUTO_INCREMENT,
    `course_id`     INT(11) NOT NULL,
    `lecturer_id`   INT(11) NOT NULL,
    `assigned_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`assignment_id`),
    UNIQUE KEY `uk_course_lecturer` (`course_id`, `lecturer_id`),
    CONSTRAINT `fk_ca_course`   FOREIGN KEY (`course_id`)   REFERENCES `courses`   (`course_id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_ca_lecturer` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers` (`lecturer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. ENROLLMENTS — Students enrolled in courses
-- ============================================================
CREATE TABLE IF NOT EXISTS `enrollments` (
    `enrollment_id` INT(11)   NOT NULL AUTO_INCREMENT,
    `student_id`    INT(11)   NOT NULL,
    `course_id`     INT(11)   NOT NULL,
    `enrolled_at`   DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`enrollment_id`),
    UNIQUE KEY `uk_enrollment` (`student_id`, `course_id`),
    CONSTRAINT `fk_enroll_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enroll_course`  FOREIGN KEY (`course_id`)  REFERENCES `courses`  (`course_id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 7. MARKS — Individual assessment components per student/course
-- ============================================================
CREATE TABLE IF NOT EXISTS `marks` (
    `mark_id`    INT(11)       NOT NULL AUTO_INCREMENT,
    `student_id` INT(11)       NOT NULL,
    `course_id`  INT(11)       NOT NULL,
    `component`  VARCHAR(100)  NOT NULL,
    `score`      DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    `weight`     DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`mark_id`),
    CONSTRAINT `fk_marks_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_marks_course`  FOREIGN KEY (`course_id`)  REFERENCES `courses`  (`course_id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 8. FINAL_RESULTS — Computed totals & grades per student/course
-- ============================================================
CREATE TABLE IF NOT EXISTS `final_results` (
    `result_id`   INT(11)      NOT NULL AUTO_INCREMENT,
    `student_id`  INT(11)      NOT NULL,
    `course_id`   INT(11)      NOT NULL,
    `total_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `grade`       CHAR(2)      NOT NULL DEFAULT 'F',
    `gpa_points`  DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`result_id`),
    UNIQUE KEY `uk_final_student_course` (`student_id`, `course_id`),
    CONSTRAINT `fk_fr_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fr_course`  FOREIGN KEY (`course_id`)  REFERENCES `courses`  (`course_id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 9. ATTENDANCE_SESSIONS — Created by lecturers
-- ============================================================
CREATE TABLE IF NOT EXISTS `attendance_sessions` (
    `session_id`   INT(11)  NOT NULL AUTO_INCREMENT,
    `course_id`    INT(11)  NOT NULL,
    `lecturer_id`  INT(11)  NOT NULL,
    `session_date` DATE     NOT NULL,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`session_id`),
    UNIQUE KEY `uk_session` (`course_id`, `lecturer_id`, `session_date`),
    CONSTRAINT `fk_sess_course`   FOREIGN KEY (`course_id`)   REFERENCES `courses`   (`course_id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_sess_lecturer` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers` (`lecturer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 10. ATTENDANCE_RECORDS — Per-student attendance per session
-- ============================================================
CREATE TABLE IF NOT EXISTS `attendance_records` (
    `record_id`  INT(11)                              NOT NULL AUTO_INCREMENT,
    `session_id` INT(11)                              NOT NULL,
    `student_id` INT(11)                              NOT NULL,
    `status`     ENUM('present','absent','late','excused') NOT NULL DEFAULT 'absent',
    PRIMARY KEY (`record_id`),
    UNIQUE KEY `uk_att_record` (`session_id`, `student_id`),
    CONSTRAINT `fk_att_session` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`session_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_student` FOREIGN KEY (`student_id`) REFERENCES `students`             (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 11. TIMETABLE — Weekly class schedule
-- ============================================================
CREATE TABLE IF NOT EXISTS `timetable` (
    `slot_id`      INT(11)      NOT NULL AUTO_INCREMENT,
    `course_id`    INT(11)      NOT NULL,
    `lecturer_id`  INT(11)      NOT NULL,
    `day_of_week`  ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
    `start_time`   TIME         NOT NULL,
    `end_time`     TIME         NOT NULL,
    `room`         VARCHAR(50)  DEFAULT NULL,
    PRIMARY KEY (`slot_id`),
    CONSTRAINT `fk_tt_course`   FOREIGN KEY (`course_id`)   REFERENCES `courses`   (`course_id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_tt_lecturer` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers` (`lecturer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 12. ANNOUNCEMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `announcements` (
    `announcement_id` INT(11)      NOT NULL AUTO_INCREMENT,
    `title`           VARCHAR(200) NOT NULL,
    `message`         TEXT         NOT NULL,
    `author_id`       INT(11)      NOT NULL,
    `author_role`     ENUM('admin','lecturer') NOT NULL,
    `target_role`     ENUM('all','student','lecturer') NOT NULL DEFAULT 'all',
    `is_pinned`       TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`announcement_id`),
    CONSTRAINT `fk_ann_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 13. ACADEMIC_PERIODS — semesters / terms
-- ============================================================
CREATE TABLE IF NOT EXISTS `academic_periods` (
    `period_id`  INT(11)      NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `start_date` DATE         NOT NULL,
    `end_date`   DATE         NOT NULL,
    `is_current` TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (`period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 14. GRADE_CONFIG — Admin-configurable grade boundaries
-- ============================================================
CREATE TABLE IF NOT EXISTS `grade_config` (
    `grade`      CHAR(2)      NOT NULL,
    `min_score`  DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `gpa_points` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    `label`      VARCHAR(50)  DEFAULT '',
    PRIMARY KEY (`grade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 15. ACTIVITY_LOG — Audit trail
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_log` (
    `log_id`     INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11)      NOT NULL,
    `action`     VARCHAR(100) NOT NULL,
    `details`    TEXT         DEFAULT NULL,
    `ip_address` VARCHAR(45)  DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    KEY `fk_log_user` (`user_id`),
    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 16. PASSWORD_RESETS — Token-based password recovery
-- ============================================================
CREATE TABLE IF NOT EXISTS `password_resets` (
    `reset_id`   INT(11)      NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(100) NOT NULL,
    `token`      VARCHAR(64)  NOT NULL,
    `expires_at` DATETIME     NOT NULL,
    `used`       TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`reset_id`),
    KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ############################################################
--  SEED DATA
-- ############################################################

-- ============================================================
-- USERS (passwords hashed with PHP password_hash)
-- admin123, lecturer123, student123
-- ============================================================
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`) VALUES
(1,  'System Admin',      'admin@sms.com',      '$2y$10$ANwwaK2hbV1Awy2dun5D6.SUuG4w8ftjqXh0V/NS0ongkxZGkaqNe', 'admin'),
(2,  'Dr. Kamal Perera',  'lecturer@sms.com',   '$2y$10$0jEgglbj524k2Kq1t.MYcuoJun5gIgtdoue4EPDzOkE1OajjOdFDi', 'lecturer'),
(3,  'Dr. Nimali Silva',  'nimali@sms.com',     '$2y$10$0jEgglbj524k2Kq1t.MYcuoJun5gIgtdoue4EPDzOkE1OajjOdFDi', 'lecturer'),
(4,  'Sahan Fernando',    'student@sms.com',    '$2y$10$ONfh/fjsV3YSSpkFpHHkLeSDKVv2ouT6i0BOWI/LdHHZvilnsx6ou', 'student'),
(5,  'Kumari Jayawardena','kumari@sms.com',     '$2y$10$ONfh/fjsV3YSSpkFpHHkLeSDKVv2ouT6i0BOWI/LdHHZvilnsx6ou', 'student'),
(6,  'Tharindu Bandara',  'tharindu@sms.com',   '$2y$10$ONfh/fjsV3YSSpkFpHHkLeSDKVv2ouT6i0BOWI/LdHHZvilnsx6ou', 'student'),
(7,  'Nethmi Wickrama',   'nethmi@sms.com',     '$2y$10$ONfh/fjsV3YSSpkFpHHkLeSDKVv2ouT6i0BOWI/LdHHZvilnsx6ou', 'student'),
(8,  'Dinesh Rajapaksha', 'dinesh@sms.com',     '$2y$10$ONfh/fjsV3YSSpkFpHHkLeSDKVv2ouT6i0BOWI/LdHHZvilnsx6ou', 'student');

-- ============================================================
-- LECTURERS
-- ============================================================
INSERT INTO `lecturers` (`lecturer_id`, `user_id`, `department`) VALUES
(1, 2, 'Computer Science'),
(2, 3, 'Information Technology');

-- ============================================================
-- STUDENTS
-- ============================================================
INSERT INTO `students` (`student_id`, `user_id`, `registration_no`, `department`, `intake_year`, `status`) VALUES
(1, 4, 'REG-2025-001', 'Computer Science',         2025, 'active'),
(2, 5, 'REG-2025-002', 'Information Technology',    2025, 'active'),
(3, 6, 'REG-2025-003', 'Computer Science',         2025, 'active'),
(4, 7, 'REG-2025-004', 'Software Engineering',     2025, 'active'),
(5, 8, 'REG-2024-010', 'Computer Science',         2024, 'active');

-- ============================================================
-- COURSES
-- ============================================================
INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `credits`) VALUES
(1, 'CS1010', 'Introduction to Programming',   4),
(2, 'CS2020', 'Data Structures & Algorithms',  3),
(3, 'IT1010', 'Database Management Systems',   3),
(4, 'SE2010', 'Software Engineering',           3),
(5, 'CS3030', 'Web Application Development',   4),
(6, 'IT2020', 'Networking Fundamentals',        3);

-- ============================================================
-- COURSE ASSIGNMENTS (lecturers → courses)
-- ============================================================
INSERT INTO `course_assignments` (`course_id`, `lecturer_id`) VALUES
(1, 1),  -- Dr. Kamal  → Intro Programming
(2, 1),  -- Dr. Kamal  → Data Structures
(5, 1),  -- Dr. Kamal  → Web Dev
(3, 2),  -- Dr. Nimali → DBMS
(4, 2),  -- Dr. Nimali → Software Eng
(6, 2);  -- Dr. Nimali → Networking

-- ============================================================
-- ENROLLMENTS
-- ============================================================
INSERT INTO `enrollments` (`student_id`, `course_id`, `enrolled_at`) VALUES
-- Sahan Fernando (enrolled Jan 2026)
(1, 1, '2026-01-10 09:00:00'),
(1, 2, '2026-01-10 09:15:00'),
(1, 3, '2026-01-12 10:00:00'),
-- Kumari Jayawardena (enrolled Jan-Feb 2026)
(2, 1, '2026-01-15 08:30:00'),
(2, 3, '2026-02-01 09:00:00'),
(2, 4, '2026-02-01 09:30:00'),
-- Tharindu Bandara (enrolled Jan 2026)
(3, 2, '2026-01-20 11:00:00'),
(3, 5, '2026-01-20 11:30:00'),
-- Nethmi Wickrama (enrolled Feb 2026)
(4, 4, '2026-02-03 08:00:00'),
(4, 5, '2026-02-03 08:30:00'),
(4, 6, '2026-02-05 14:00:00'),
-- Dinesh Rajapaksha (enrolled Feb 2026)
(5, 1, '2026-02-10 09:00:00'),
(5, 2, '2026-02-10 09:15:00'),
(5, 6, '2026-02-12 10:00:00');

-- ============================================================
-- GRADE_CONFIG
-- ============================================================
INSERT INTO `grade_config` (`grade`, `min_score`, `gpa_points`, `label`) VALUES
('A', 75.00, 4.00, 'Excellent'),
('B', 65.00, 3.00, 'Good'),
('C', 55.00, 2.00, 'Average'),
('D', 45.00, 1.00, 'Below Average'),
('F',  0.00, 0.00, 'Fail');

-- ============================================================
-- MARKS (sample marks for a few student-course combos)
-- ============================================================
-- Sahan Fernando → Intro Programming (CS1010)
INSERT INTO `marks` (`student_id`, `course_id`, `component`, `score`, `weight`) VALUES
(1, 1, 'Quiz',       85.00, 10.00),
(1, 1, 'Assignment', 78.00, 20.00),
(1, 1, 'Midterm',    72.00, 30.00),
(1, 1, 'Final',      80.00, 40.00);

-- Kumari Jayawardena → Intro Programming (CS1010)
INSERT INTO `marks` (`student_id`, `course_id`, `component`, `score`, `weight`) VALUES
(2, 1, 'Quiz',       92.00, 10.00),
(2, 1, 'Assignment', 88.00, 20.00),
(2, 1, 'Midterm',    85.00, 30.00),
(2, 1, 'Final',      90.00, 40.00);

-- Dinesh Rajapaksha → Intro Programming (CS1010)
INSERT INTO `marks` (`student_id`, `course_id`, `component`, `score`, `weight`) VALUES
(5, 1, 'Quiz',       60.00, 10.00),
(5, 1, 'Assignment', 55.00, 20.00),
(5, 1, 'Midterm',    48.00, 30.00),
(5, 1, 'Final',      52.00, 40.00);

-- Tharindu Bandara → Data Structures (CS2020)
INSERT INTO `marks` (`student_id`, `course_id`, `component`, `score`, `weight`) VALUES
(3, 2, 'Quiz',       70.00, 10.00),
(3, 2, 'Assignment', 65.00, 20.00),
(3, 2, 'Midterm',    75.00, 30.00),
(3, 2, 'Final',      68.00, 40.00);

-- ============================================================
-- FINAL_RESULTS (pre-calculated from marks above)
-- Sahan:   8.5 + 15.6 + 21.6 + 32.0 = 77.70 → A
-- Kumari:  9.2 + 17.6 + 25.5 + 36.0 = 88.30 → A
-- Dinesh:  6.0 + 11.0 + 14.4 + 20.8 = 52.20 → D
-- Tharindu: 7.0 + 13.0 + 22.5 + 27.2 = 69.70 → B
-- ============================================================
INSERT INTO `final_results` (`student_id`, `course_id`, `total_score`, `grade`, `gpa_points`) VALUES
(1, 1, 77.70, 'A', 4.00),
(2, 1, 88.30, 'A', 4.00),
(5, 1, 52.20, 'D', 1.00),
(3, 2, 69.70, 'B', 3.00);

-- ============================================================
-- ATTENDANCE_SESSIONS (sample sessions)
-- ============================================================
INSERT INTO `attendance_sessions` (`session_id`, `course_id`, `lecturer_id`, `session_date`) VALUES
(1, 1, 1, '2026-01-13'),
(2, 1, 1, '2026-01-20'),
(3, 2, 1, '2026-01-14'),
(4, 3, 2, '2026-01-15'),
(5, 4, 2, '2026-01-16');

-- ============================================================
-- ATTENDANCE_RECORDS
-- ============================================================
INSERT INTO `attendance_records` (`session_id`, `student_id`, `status`) VALUES
-- Session 1: CS1010 – Mar 3
(1, 1, 'present'),
(1, 2, 'present'),
(1, 5, 'absent'),
-- Session 2: CS1010 – Mar 10
(2, 1, 'present'),
(2, 2, 'late'),
(2, 5, 'present'),
-- Session 3: CS2020 – Mar 4
(3, 1, 'present'),
(3, 3, 'present'),
(3, 5, 'present'),
-- Session 4: DBMS – Mar 5
(4, 1, 'present'),
(4, 2, 'present'),
-- Session 5: SE2010 – Mar 6
(5, 2, 'present'),
(5, 4, 'present');

-- ============================================================
-- TIMETABLE (weekly schedule)
-- ============================================================
INSERT INTO `timetable` (`course_id`, `lecturer_id`, `day_of_week`, `start_time`, `end_time`, `room`) VALUES
(1, 1, 'Monday',    '08:00', '10:00', 'LR-101'),
(2, 1, 'Monday',    '10:30', '12:30', 'LR-102'),
(5, 1, 'Wednesday', '08:00', '10:00', 'Lab-A'),
(3, 2, 'Tuesday',   '08:00', '10:00', 'LR-201'),
(4, 2, 'Tuesday',   '10:30', '12:30', 'LR-201'),
(6, 2, 'Thursday',  '14:00', '16:00', 'LR-301'),
(1, 1, 'Thursday',  '08:00', '10:00', 'LR-101'),
(3, 2, 'Friday',    '08:00', '10:00', 'Lab-B');

-- ============================================================
-- ACADEMIC_PERIODS
-- ============================================================
INSERT INTO `academic_periods` (`name`, `start_date`, `end_date`, `is_current`) VALUES
('2026 Semester 1', '2026-01-05', '2026-05-31', 1),
('2026 Semester 2', '2026-07-01', '2026-11-30', 0);

-- ============================================================
-- ANNOUNCEMENTS (sample)
-- ============================================================
INSERT INTO `announcements` (`title`, `message`, `author_id`, `author_role`, `target_role`, `is_pinned`) VALUES
('Welcome to Semester 1!',
 'Welcome back everyone! Classes begin on March 3rd. Please check the timetable for your schedule.',
 1, 'admin', 'all', 1),
('Assignment 1 Released',
 'The first assignment for Introduction to Programming (CS1010) has been released. Please submit by March 21st.',
 2, 'lecturer', 'student', 0),
('Library Hours Extended',
 'The university library will remain open until 10 PM during the exam period.',
 1, 'admin', 'all', 0);


