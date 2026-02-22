<?php
/**
 * Delete Course Handler - delete.php
 * 
 * This page handles course deletion (POST only for security)
 * Checks if course is used in enrollments before deletion
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

// Get course ID from POST
$courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

if ($courseId <= 0) {
    redirectWithMessage('index.php', 'error', 'Invalid course ID');
}

try {
    // Check if course exists
    $stmt = $pdo->prepare("SELECT course_code, course_name FROM courses WHERE course_id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();
    
    if (!$course) {
        redirectWithMessage('index.php', 'error', 'Course not found');
    }
    
    // IMPORTANT: Check if course is used in enrollments
    // This prevents deletion of courses that students are enrolled in
    if (isCourseInUse($pdo, $courseId)) {
        redirectWithMessage(
            'index.php',
            'error',
            'Cannot delete course "' . $course['course_code'] . '" because it has active enrollments. Please remove all enrollments first.'
        );
    }
    
    // If not in use, proceed with deletion
    $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->execute([$courseId]);
    logActivity($pdo, $_SESSION['user_id'], 'Delete Course', "Course Code: {$course['course_code']}");
    
    // Redirect with success message
    redirectWithMessage(
        'index.php',
        'success',
        'Course "' . $course['course_code'] . '" deleted successfully!'
    );
    
} catch (PDOException $e) {
    error_log("Delete course error: " . $e->getMessage());
    redirectWithMessage(
        'index.php',
        'error',
        'Error deleting course. Please try again.'
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
 * 2. ENROLLMENT CHECK:
 *    - Before deletion, checks if course has enrollments
 *    - Uses isCourseInUse() helper function
 *    - If in use, shows error and prevents deletion
 *    - This maintains data integrity
 * 
 * 3. ERROR HANDLING:
 *    - All errors are caught and logged
 *    - User sees friendly error message
 *    - Redirects back to list with flash message
 * 
 * 
 * ==================== USAGE FROM HTML ====================
 * 
 * <form method="POST" action="delete.php" onsubmit="return confirm('Are you sure?');">
 *     <input type="hidden" name="course_id" value="123">
 *     <button type="submit">Delete</button>
 * </form>
 * 
 * 
 * ==================== COMMON MISTAKES TO AVOID ====================
 * 
 * 1. DON'T delete via GET:
 *    - ❌ <a href="delete.php?course_id=123">Delete</a>
 *    - ✅ Use POST form shown above
 * 
 * 2. DON'T skip enrollment check:
 *    - Always verify course isn't in use
 *    - Prevents broken foreign key references
 * 
 * 3. DON'T expose sensitive error details:
 *    - Log full errors with error_log()
 *    - Show friendly messages to users
 */
?>
