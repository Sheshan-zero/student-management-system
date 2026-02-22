<?php
/**
 * Delete Enrollment Handler - delete.php
 * 
 * This page handles enrollment deletion / unenrollment (POST only for security)
 */

// Include required files
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';

// Require admin role
requireRole('admin');

// Only accept POST requests (prevent accidental GET deletion)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('index.php', 'error', 'Invalid request method');
}

// Get enrollment ID from POST
$enrollmentId = isset($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : 0;

if ($enrollmentId <= 0) {
    redirectWithMessage('index.php', 'error', 'Invalid enrollment ID');
}

try {
    // Fetch enrollment details before deletion (for confirmation message)
    $stmt = $pdo->prepare("
        SELECT 
            s.registration_no,
            u.full_name AS student_name,
            c.course_code,
            c.course_name
        FROM enrollments e
        INNER JOIN students s ON e.student_id = s.student_id
        INNER JOIN users u ON s.user_id = u.id
        INNER JOIN courses c ON e.course_id = c.course_id
        WHERE e.enrollment_id = ?
    ");
    $stmt->execute([$enrollmentId]);
    $enrollment = $stmt->fetch();
    
    if (!$enrollment) {
        redirectWithMessage('index.php', 'error', 'Enrollment not found');
    }
    
    // Delete the enrollment
    $stmt = $pdo->prepare("DELETE FROM enrollments WHERE enrollment_id = ?");
    $stmt->execute([$enrollmentId]);
    logActivity($pdo, $_SESSION['user_id'], 'Delete Enrollment', "Unenrolled {$enrollment['registration_no']} from {$enrollment['course_code']}");
    
    // Redirect with success message
    redirectWithMessage(
        'index.php',
        'success',
        'Unenrolled ' . $enrollment['student_name'] . ' (' . $enrollment['registration_no'] . ') from ' . $enrollment['course_code']
    );
    
} catch (PDOException $e) {
    error_log("Delete enrollment error: " . $e->getMessage());
    redirectWithMessage(
        'index.php',
        'error',
        'Error removing enrollment. Please try again.'
    );
}

/**
 * ==================== HOW IT WORKS ====================
 * 
 * 1. SECURITY - POST ONLY:
 *    - Only accepts POST requests
 *    - Prevents accidental deletion via URL clicking
 *    - Form in index.php includes JavaScript confirmation
 * 
 * 2. FETCH BEFORE DELETE:
 *    - Gets enrollment details before deletion
 *    - Used for generating a detailed confirmation message
 *    - Shows which student was unenrolled from which course
 * 
 * 3. SIMPLE DELETION:
 *    - No complex checks needed (unlike courses)
 *    - Enrollment is just a link between student and course
 *    - Safe to delete anytime
 * 
 * 
 * ==================== USAGE FROM HTML ====================
 * 
 * <form method="POST" action="delete.php" onsubmit="return confirm('Are you sure?');">
 *     <input type="hidden" name="enrollment_id" value="123">
 *     <button type="submit">Unenroll</button>
 * </form>
 * 
 * 
 * ==================== COMMON MISTAKES TO AVOID ====================
 * 
 * 1. DON'T delete via GET:
 *    - ❌ <a href="delete.php?enrollment_id=123">Delete</a>
 *    - ✅ Use POST form shown above
 * 
 * 2. DON'T skip validation:
 *    - Always verify enrollment_id is valid
 *    - Check if enrollment exists before deleting
 * 
 * 3. DON'T expose sensitive error details:
 *    - Log full errors with error_log()
 *    - Show friendly messages to users
 */
?>
