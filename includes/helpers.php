<?php
/**
 * Helper Functions - helpers.php
 * 
 * This file contains reusable utility functions for:
 * - Flash messages (success/error notifications)
 * - Redirects
 * - Input validation
 * - Sanitization
 * 
 * USAGE: Include this file in pages that need these utilities
 * require_once 'includes/helpers.php';
 */

// ==================== FLASH MESSAGES ====================

/**
 * Set a flash message to display on the next page load
 * Flash messages are stored in session and automatically cleared after display
 * 
 * @param string $type Type of message ('success', 'error', 'warning', 'info')
 * @param string $message The message to display
 * 
 * USAGE:
 * setFlashMessage('success', 'Student created successfully!');
 * setFlashMessage('error', 'Email already exists');
 */
function setFlashMessage($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear the flash message
 * Returns the message once, then removes it from session
 * 
 * @return array|null Array with 'type' and 'message' or null if no message
 * 
 * USAGE:
 * $flash = getFlashMessage();
 * if ($flash) {
 *     echo '<div class="' . $flash['type'] . '">' . $flash['message'] . '</div>';
 * }
 */
function getFlashMessage() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    
    return null;
}

/**
 * Display flash message as HTML
 * Automatically shows the message and clears it
 * 
 * USAGE:
 * displayFlashMessage();  // Call at top of page
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $message = htmlspecialchars($flash['message']);
        
        $colors = [
            'success' => 'background: #d4edda; color: #155724; border-left: 4px solid #28a745;',
            'error' => 'background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545;',
            'warning' => 'background: #fff3cd; color: #856404; border-left: 4px solid #ffc107;',
            'info' => 'background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8;'
        ];
        
        $style = $colors[$type] ?? $colors['info'];
        
        echo '<div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; ' . $style . '">';
        echo $message;
        echo '</div>';
    }
}

// ==================== REDIRECTS ====================

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code (default 302)
 * 
 * USAGE:
 * redirect('modules/students/index.php');
 * redirect('login.php', 301);  // Permanent redirect
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * Redirect with a flash message
 * 
 * @param string $url URL to redirect to
 * @param string $type Message type
 * @param string $message Message text
 * 
 * USAGE:
 * redirectWithMessage('index.php', 'success', 'Student created!');
 */
function redirectWithMessage($url, $type, $message) {
    setFlashMessage($type, $message);
    redirect($url);
}

// ==================== INPUT VALIDATION ====================

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 * 
 * USAGE:
 * if (!isValidEmail($email)) {
 *     $errors[] = 'Invalid email format';
 * }
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if string is empty or only whitespace
 * 
 * @param string $value Value to check
 * @return bool True if empty, false otherwise
 * 
 * USAGE:
 * if (isEmpty($name)) {
 *     $errors[] = 'Name is required';
 * }
 */
function isEmpty($value) {
    return empty(trim($value));
}

/**
 * Validate required fields
 * 
 * @param array $fields Associative array of field names and values
 * @return array Array of error messages
 * 
 * USAGE:
 * $errors = validateRequired([
 *     'name' => $name,
 *     'email' => $email
 * ]);
 */
function validateRequired($fields) {
    $errors = [];
    
    foreach ($fields as $field => $value) {
        if (isEmpty($value)) {
            $errors[] = ucfirst($field) . ' is required';
        }
    }
    
    return $errors;
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @param int $minLength Minimum length (default 6)
 * @return array Array of error messages
 * 
 * USAGE:
 * $errors = validatePassword($password);
 */
function validatePassword($password, $minLength = 6) {
    $errors = [];
    
    if (strlen($password) < $minLength) {
        $errors[] = "Password must be at least {$minLength} characters long";
    }
    
    return $errors;
}

// ==================== SANITIZATION ====================

/**
 * Sanitize string input
 * Removes HTML tags and trims whitespace
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized string
 * 
 * USAGE:
 * $name = sanitizeInput($_POST['name']);
 */
function sanitizeInput($input) {
    return trim(strip_tags($input));
}

/**
 * Sanitize email
 * 
 * @param string $email Email to sanitize
 * @return string Sanitized email
 */
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

// ==================== DATABASE HELPERS ====================

/**
 * Check if email exists in users table
 * 
 * @param PDO $pdo Database connection
 * @param string $email Email to check
 * @param int|null $excludeUserId User ID to exclude from check (for updates)
 * @return bool True if exists, false otherwise
 * 
 * USAGE:
 * if (emailExists($pdo, $email)) {
 *     $errors[] = 'Email already exists';
 * }
 */
function emailExists($pdo, $email, $excludeUserId = null) {
    if ($excludeUserId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $excludeUserId]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
    }
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Check if registration number exists in students table
 * 
 * @param PDO $pdo Database connection
 * @param string $regNo Registration number to check
 * @param int|null $excludeStudentId Student ID to exclude from check (for updates)
 * @return bool True if exists, false otherwise
 * 
 * USAGE:
 * if (registrationExists($pdo, $regNo)) {
 *     $errors[] = 'Registration number already exists';
 * }
 */
function registrationExists($pdo, $regNo, $excludeStudentId = null) {
    if ($excludeStudentId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE registration_no = ? AND student_id != ?");
        $stmt->execute([$regNo, $excludeStudentId]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE registration_no = ?");
        $stmt->execute([$regNo]);
    }
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Check if course code exists in courses table
 * 
 * @param PDO $pdo Database connection
 * @param string $courseCode Course code to check
 * @param int|null $excludeCourseId Course ID to exclude from check (for updates)
 * @return bool True if exists, false otherwise
 * 
 * USAGE:
 * if (courseCodeExists($pdo, $courseCode)) {
 *     $errors[] = 'Course code already exists';
 * }
 */
function courseCodeExists($pdo, $courseCode, $excludeCourseId = null) {
    if ($excludeCourseId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE course_code = ? AND course_id != ?");
        $stmt->execute([$courseCode, $excludeCourseId]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE course_code = ?");
        $stmt->execute([$courseCode]);
    }
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Validate credits value
 * 
 * @param mixed $credits Credits value to validate
 * @param int $min Minimum credits (default 1)
 * @param int $max Maximum credits (default 30)
 * @return array Array of error messages
 * 
 * USAGE:
 * $errors = validateCredits($credits);
 */
function validateCredits($credits, $min = 1, $max = 30) {
    $errors = [];
    
    if (!is_numeric($credits)) {
        $errors[] = 'Credits must be a number';
    } elseif ($credits < $min || $credits > $max) {
        $errors[] = "Credits must be between {$min} and {$max}";
    } elseif (intval($credits) != $credits) {
        $errors[] = 'Credits must be a whole number';
    }
    
    return $errors;
}

/**
 * Check if course is used in enrollments
 * 
 * @param PDO $pdo Database connection
 * @param int $courseId Course ID to check
 * @return bool True if course has enrollments, false otherwise
 * 
 * USAGE:
 * if (isCourseInUse($pdo, $courseId)) {
 *     $errors[] = 'Cannot delete course with active enrollments';
 * }
 */
function isCourseInUse($pdo, $courseId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
    $stmt->execute([$courseId]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Check if enrollment already exists
 * 
 * @param PDO $pdo Database connection
 * @param int $studentId Student ID
 * @param int $courseId Course ID
 * @return bool True if enrollment exists, false otherwise
 * 
 * USAGE:
 * if (enrollmentExists($pdo, $studentId, $courseId)) {
 *     $errors[] = 'Student is already enrolled in this course';
 * }
 */
function enrollmentExists($pdo, $studentId, $courseId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$studentId, $courseId]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Check if student is eligible for enrollment
 * Students with status 'Suspended' or 'Graduated' are not eligible
 * 
 * @param PDO $pdo Database connection
 * @param int $studentId Student ID
 * @return array ['eligible' => bool, 'status' => string, 'message' => string]
 * 
 * USAGE:
 * $eligibility = isStudentEligible($pdo, $studentId);
 * if (!$eligibility['eligible']) {
 *     $errors[] = $eligibility['message'];
 * }
 */
function isStudentEligible($pdo, $studentId) {
    $stmt = $pdo->prepare("SELECT status FROM students WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $status = $stmt->fetchColumn();
    
    if (!$status) {
        return [
            'eligible' => false,
            'status' => 'Unknown',
            'message' => 'Student not found'
        ];
    }
    
    $ineligibleStatuses = ['Suspended', 'Graduated'];
    $eligible = !in_array($status, $ineligibleStatuses);
    
    $message = $eligible 
        ? 'Student is eligible' 
        : "Cannot enroll student with status: {$status}";
    
    return [
        'eligible' => $eligible,
        'status' => $status,
        'message' => $message
    ];
}

/**
 * Check if attendance session exists for specific course + lecturer + date
 * 
 * @param PDO $pdo Database connection
 * @param int $courseId Course ID
 * @param int $lecturerId Lecturer ID
 * @param string $sessionDate Session date (Y-m-d format)
 * @return bool True if session exists, false otherwise
 * 
 * USAGE:
 * if (sessionExists($pdo, $courseId, $lecturerId, $sessionDate)) {
 *     $errors[] = 'Session already exists for this date';
 * }
 */
function sessionExists($pdo, $courseId, $lecturerId, $sessionDate) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM attendance_sessions 
        WHERE course_id = ? AND lecturer_id = ? AND session_date = ?
    ");
    $stmt->execute([$courseId, $lecturerId, $sessionDate]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Verify that an attendance session belongs to a specific lecturer
 * 
 * @param PDO $pdo Database connection
 * @param int $sessionId Session ID
 * @param int $lecturerId Lecturer ID
 * @return bool True if session belongs to lecturer, false otherwise
 * 
 * USAGE:
 * if (!verifySessionOwnership($pdo, $sessionId, $lecturerId)) {
 *     redirectWithMessage('index.php', 'error', 'Unauthorized');
 * }
 */
function verifySessionOwnership($pdo, $sessionId, $lecturerId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM attendance_sessions 
        WHERE session_id = ? AND lecturer_id = ?
    ");
    $stmt->execute([$sessionId, $lecturerId]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Get students enrolled in a course (for attendance marking)
 * 
 * @param PDO $pdo Database connection
 * @param int $sessionId Session ID
 * @return array Array of student records with student_id, registration_no, full_name
 * 
 * USAGE:
 * $students = getEnrolledStudents($pdo, $sessionId);
 * foreach ($students as $student) {
 *     echo $student['registration_no'];
 * }
 */
function getEnrolledStudents($pdo, $sessionId) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            st.student_id, 
            st.registration_no,
            u.full_name
        FROM enrollments e
        INNER JOIN students st ON e.student_id = st.student_id
        INNER JOIN users u ON st.user_id = u.id
        INNER JOIN attendance_sessions sess ON e.course_id = sess.course_id
        WHERE sess.session_id = ?
        ORDER BY st.registration_no ASC
    ");
    $stmt->execute([$sessionId]);
    
    return $stmt->fetchAll();
}




// ==================== DISPLAY HELPERS ====================

/**
 * Escape output for HTML display (prevent XSS)
 * 
 * @param string $value Value to escape
 * @return string Escaped value
 * 
 * USAGE:
 * echo e($user['name']);  // Safe output
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 * 
 * @param string $date Date to format
 * @param string $format Format string (default: 'Y-m-d')
 * @return string Formatted date
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * ==================== USAGE EXAMPLES ====================
 * 
 * 1. FLASH MESSAGES:
 * ---------------------------------------------------
 * // Set message before redirect
 * setFlashMessage('success', 'Student created!');
 * redirect('index.php');
 * 
 * // Display message on next page
 * displayFlashMessage();
 * 
 * 
 * 2. VALIDATION:
 * ---------------------------------------------------
 * $errors = validateRequired([
 *     'name' => $_POST['name'],
 *     'email' => $_POST['email']
 * ]);
 * 
 * if (!isValidEmail($_POST['email'])) {
 *     $errors[] = 'Invalid email';
 * }
 * 
 * 
 * 3. DATABASE CHECKS:
 * ---------------------------------------------------
 * if (emailExists($pdo, $email)) {
 *     $errors[] = 'Email already in use';
 * }
 * 
 * 
 * 4. SAFE OUTPUT:
 * ---------------------------------------------------
 * echo e($student['name']);  // Prevents XSS
 */

// ==================== MARKS/RESULTS HELPERS ====================

/**
 * Calculate letter grade from a numeric score
 * 
 * Grade boundaries:
 *   A = 75-100 (Excellent)
 *   B = 65-74  (Good)
 *   C = 55-64  (Average)
 *   D = 45-54  (Below Average)
 *   F = 0-44   (Fail)
 * 
 * @param float $score Numeric score (0-100)
 * @return string Letter grade (A, B, C, D, or F)
 * 
 * USAGE:
 * $grade = calculateGrade(82.5);  // Returns 'A'
 * $grade = calculateGrade(43.2);  // Returns 'F'
 */
function calculateGrade($score) {
    // Try reading from grade_config table (admin-configurable)
    global $pdo;
    if (isset($pdo)) {
        try {
            $config = $pdo->query("SELECT grade, min_score FROM grade_config ORDER BY min_score DESC")->fetchAll();
            if (!empty($config)) {
                foreach ($config as $g) {
                    if ($score >= $g['min_score']) return $g['grade'];
                }
                return 'F';
            }
        } catch (PDOException $e) { /* table doesn't exist, fall through */ }
    }
    // Fallback to hardcoded defaults
    if ($score >= 75) return 'A';
    if ($score >= 65) return 'B';
    if ($score >= 55) return 'C';
    if ($score >= 45) return 'D';
    return 'F';
}

/**
 * Convert letter grade to GPA points (4.0 scale)
 * 
 * @param string $grade Letter grade (A, B, C, D, or F)
 * @return float GPA points
 * 
 * USAGE:
 * $gpa = calculateGPAPoints('A');  // Returns 4.0
 * $gpa = calculateGPAPoints('F');  // Returns 0.0
 */
function calculateGPAPoints($grade) {
    // Try reading from grade_config table (admin-configurable)
    global $pdo;
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT gpa_points FROM grade_config WHERE grade = ?");
            $stmt->execute([$grade]);
            $result = $stmt->fetchColumn();
            if ($result !== false) return (float)$result;
        } catch (PDOException $e) { /* table doesn't exist, fall through */ }
    }
    // Fallback to hardcoded defaults
    $scale = [
        'A' => 4.0, 'B' => 3.0, 'C' => 2.0, 'D' => 1.0, 'F' => 0.0
    ];
    return $scale[$grade] ?? 0.0;
}

/**
 * Calculate the weighted total score for a student in a course
 * 
 * Formula: SUM(score * weight / 100) for all components
 * Example: Quiz=85×10% + Midterm=78×30% = 8.5 + 23.4 = 31.9
 * 
 * @param PDO $pdo Database connection
 * @param int $studentId Student ID
 * @param int $courseId Course ID
 * @return float Total weighted score
 * 
 * USAGE:
 * $total = calculateTotalScore($pdo, 1, 3);  // Returns e.g. 82.70
 */
function calculateTotalScore($pdo, $studentId, $courseId) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(score * weight / 100), 0) as total_score 
        FROM marks 
        WHERE student_id = ? AND course_id = ?
    ");
    $stmt->execute([$studentId, $courseId]);
    return round($stmt->fetchColumn(), 2);
}

/**
 * Get the total weight percentage used for a student in a course
 * 
 * Ideally this should be close to 100%.
 * If it's less, the student's marks are incomplete.
 * If it's more, the weights were entered incorrectly.
 * 
 * @param PDO $pdo Database connection
 * @param int $studentId Student ID
 * @param int $courseId Course ID
 * @return float Total weight percentage
 * 
 * USAGE:
 * $totalWeight = getTotalWeight($pdo, 1, 3);  // Returns e.g. 100.00
 */
function getTotalWeight($pdo, $studentId, $courseId) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(weight), 0) as total_weight 
        FROM marks 
        WHERE student_id = ? AND course_id = ?
    ");
    $stmt->execute([$studentId, $courseId]);
    return round($stmt->fetchColumn(), 2);
}

// ==================== PAGINATION HELPER ====================

/**
 * Paginate query results
 * 
 * @param PDO $pdo Database connection
 * @param string $sql Base SELECT query (without LIMIT)
 * @param array $params Query parameters
 * @param int $page Current page number (1-indexed)
 * @param int $perPage Items per page
 * @return array ['items' => array, 'total' => int, 'pages' => int, 'page' => int, 'perPage' => int]
 */
function paginate($pdo, $sql, $params = [], $page = 1, $perPage = 15) {
    $page = max(1, (int)$page);
    
    // Count total
    $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as count_tbl";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    
    $pages = max(1, ceil($total / $perPage));
    $page  = min($page, $pages);
    $offset = ($page - 1) * $perPage;
    
    // Fetch page
    $stmt = $pdo->prepare($sql . " LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
    return [
        'items'   => $items,
        'total'   => $total,
        'pages'   => $pages,
        'page'    => $page,
        'perPage' => $perPage
    ];
}

/**
 * Render pagination HTML (Bootstrap 5)
 */
function renderPagination($pagination, $baseUrl = '?') {
    if ($pagination['pages'] <= 1) return '';
    $sep = strpos($baseUrl, '?') !== false ? '&' : '?';
    $html = '<nav><ul class="pagination justify-content-center mb-0">';
    // Prev
    $html .= '<li class="page-item ' . ($pagination['page'] <= 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . $sep . 'page=' . ($pagination['page'] - 1) . '">‹</a></li>';
    // Pages
    for ($i = 1; $i <= $pagination['pages']; $i++) {
        $html .= '<li class="page-item ' . ($i === $pagination['page'] ? 'active' : '') . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . $sep . 'page=' . $i . '">' . $i . '</a></li>';
    }
    // Next
    $html .= '<li class="page-item ' . ($pagination['page'] >= $pagination['pages'] ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . $sep . 'page=' . ($pagination['page'] + 1) . '">›</a></li>';
    $html .= '</ul></nav>';
    return $html;
}

// ==================== AUDIT LOG HELPER ====================

/**
 * Log an activity to the audit trail
 */
function logActivity($pdo, $userId, $action, $details = '') {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
            ->execute([$userId, $action, $details, $ip]);
    } catch (PDOException $e) {
        // Silently fail — don't break the app if log table doesn't exist
    }
}

?>
