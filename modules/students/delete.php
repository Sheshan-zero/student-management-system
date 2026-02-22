<?php
/**
 * Delete Student Handler - delete.php
 * 
 * This page handles student deletion (POST only for security)
 * Deletes records from students table (and potentially users table via CASCADE)
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

// Get student ID from POST
$studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;

if ($studentId <= 0) {
    redirectWithMessage('index.php', 'error', 'Invalid student ID');
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Get user_id before deletion (in case we need to delete user separately)
    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        $pdo->rollBack();
        redirectWithMessage('index.php', 'error', 'Student not found');
    }
    
    // Delete student record
    // If foreign key is set with ON DELETE CASCADE, this will also delete the user
    // If not, we need to delete the user separately
    $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->execute([$studentId]);
    
    // Optional: Delete user account as well (if CASCADE is not set)
    // Uncomment the following lines if your FK doesn't have CASCADE
    // $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    // $stmt->execute([$student['user_id']]);
    
    // Commit transaction
    $pdo->commit();
    logActivity($pdo, $_SESSION['user_id'], 'Delete Student', "Student ID: $studentId");
    
    // Redirect with success message
    redirectWithMessage(
        'index.php',
        'success',
        'Student deleted successfully!'
    );
    
} catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    
    error_log("Delete student error: " . $e->getMessage());
    redirectWithMessage(
        'index.php',
        'error',
        'Error deleting student. Please try again.'
    );
}

/**
 * ==================== HOW IT WORKS ====================
 * 
 * 1. SECURITY - POST ONLY:
 *    - Only accepts POST requests
 *    - Prevents accidental deletion via URL clicking
 *    - Form in index.php includes confirmation dialog
 * 
 * 2. TRANSACTION:
 *    - Uses transaction to ensure data consistency
 *    - If any error occurs, all changes are rolled back
 * 
 * 3. CASCADE DELETION:
 *    - If your FK has ON DELETE CASCADE:
 *      - Deleting from students will automatically delete from users
 *    - If not:
 *      - Uncomment the user deletion code above
 * 
 * 4. ERROR HANDLING:
 *    - All errors are caught and logged
 *    - User sees friendly error message
 *    - Redirects back to list with flash message
 * 
 * 
 * ==================== USAGE FROM HTML ====================
 * 
 * <form method="POST" action="delete.php" onsubmit="return confirm('Are you sure?');">
 *     <input type="hidden" name="student_id" value="123">
 *     <button type="submit">Delete</button>
 * </form>
 */
?>
