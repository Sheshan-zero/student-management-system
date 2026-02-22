<?php
/**
 * Authentication Middleware - auth.php
 * 
 * This file provides session management and authentication functions.
 * Include this file at the top of any page that requires authentication.
 * 
 * USAGE:
 * require_once 'includes/auth.php';
 * requireLogin();              // Ensures user is logged in
 * requireRole('admin');        // Ensures user has specific role
 */

// Start session if not already started
// Sessions are used to store user login state across page loads
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    // Check if the session variable 'user_id' exists
    // This variable is set during login in login.php
    return isset($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user's ID
 * 
 * @return int|null User ID if logged in, null otherwise
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the currently logged-in user's full name
 * 
 * @return string|null User's full name if logged in, null otherwise
 */
function getUserName() {
    return $_SESSION['full_name'] ?? null;
}

/**
 * Get the currently logged-in user's role
 * 
 * @return string|null User's role (admin/lecturer/student) if logged in, null otherwise
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Require user to be logged in
 * If not logged in, redirect to login page
 * 
 * USAGE: Call this at the top of protected pages
 * Example: requireLogin();
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // User is not logged in, redirect to login page
        // Store the current page URL so we can redirect back after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        header('Location: /Student_Managment_Sys/login.php');
        exit();
    }
}

/**
 * Require user to have a specific role
 * If user doesn't have the role, redirect to access denied page or their own dashboard
 * 
 * @param string $requiredRole The role required (admin/lecturer/student)
 * 
 * USAGE:
 * requireRole('admin');     - Only admins can access
 * requireRole('lecturer');  - Only lecturers can access
 * requireRole('student');   - Only students can access
 */
function requireRole($requiredRole) {
    // First, ensure user is logged in
    requireLogin();
    
    $userRole = getUserRole();
    
    // Check if user's role matches required role
    if ($userRole !== $requiredRole) {
        // User doesn't have permission
        // Redirect them to their own dashboard
        redirectToDashboard();
        exit();
    }
}

/**
 * Redirect user to their appropriate dashboard based on role
 * This is used after login or when accessing unauthorized pages
 */
function redirectToDashboard() {
    $role = getUserRole();
    
    switch ($role) {
        case 'admin':
            header('Location: /Student_Managment_Sys/dashboards/admin_dashboard.php');
            break;
        case 'lecturer':
            header('Location: /Student_Managment_Sys/dashboards/lecturer_dashboard.php');
            break;
        case 'student':
            header('Location: /Student_Managment_Sys/dashboards/student_dashboard.php');
            break;
        default:
            // If role is unknown, log them out
            logout();
    }
    exit();
}

/**
 * Log out the current user
 * Destroys the session and redirects to login page
 */
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: /Student_Managment_Sys/login.php');
    exit();
}

/**
 * Check if user has one of multiple roles
 * 
 * @param array $allowedRoles Array of allowed roles
 * @return bool True if user has one of the allowed roles
 * 
 * USAGE:
 * if (hasAnyRole(['admin', 'lecturer'])) {
 *     // Show content for both admins and lecturers
 * }
 */
function hasAnyRole($allowedRoles) {
    $userRole = getUserRole();
    return in_array($userRole, $allowedRoles);
}

/**
 * Display a user-friendly error message
 * 
 * @param string $message The error message to display
 */
function showError($message) {
    echo '<div class="error-message">' . htmlspecialchars($message) . '</div>';
}

/**
 * Display a success message
 * 
 * @param string $message The success message to display
 */
function showSuccess($message) {
    echo '<div class="success-message">' . htmlspecialchars($message) . '</div>';
}

/**
 * ==================== USAGE EXAMPLES ====================
 * 
 * 1. PROTECT A PAGE (REQUIRE LOGIN):
 * ---------------------------------------------------
 * <?php
 * require_once 'includes/auth.php';
 * requireLogin();  // User must be logged in
 * ?>
 * <h1>Welcome, <?php echo getUserName(); ?></h1>
 * 
 * 
 * 2. PROTECT A PAGE BY ROLE:
 * ---------------------------------------------------
 * <?php
 * require_once 'includes/auth.php';
 * requireRole('admin');  // Only admins can access
 * ?>
 * <h1>Admin Dashboard</h1>
 * 
 * 
 * 3. SHOW CONTENT BASED ON ROLE:
 * ---------------------------------------------------
 * <?php
 * require_once 'includes/auth.php';
 * requireLogin();
 * 
 * if (getUserRole() === 'admin') {
 *     echo '<a href="manage_users.php">Manage Users</a>';
 * }
 * ?>
 * 
 * 
 * 4. CHECK MULTIPLE ROLES:
 * ---------------------------------------------------
 * <?php
 * require_once 'includes/auth.php';
 * requireLogin();
 * 
 * if (hasAnyRole(['admin', 'lecturer'])) {
 *     echo '<a href="view_all_students.php">View All Students</a>';
 * }
 * ?>
 */
?>
