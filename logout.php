<?php
/**
 * Logout Handler - logout.php
 * 
 * This file handles user logout by:
 * 1. Destroying the session
 * 2. Clearing session cookies
 * 3. Redirecting to login page with success message
 * 
 * USAGE: Link to this file from any page
 * Example: <a href="logout.php">Logout</a>
 */

// Start the session
session_start();

// Unset all session variables
// This clears all data stored in the session
$_SESSION = array();

// Delete the session cookie if it exists
// This ensures the session is completely removed from the browser
if (isset($_COOKIE[session_name()])) {
    setcookie(
        session_name(),     // Cookie name
        '',                 // Empty value
        time() - 3600,      // Expiration time (in the past)
        '/'                 // Path (root)
    );
}

// Destroy the session completely
// This removes the session file from the server
session_destroy();

// Redirect to login page with success message
header('Location: login.php?logout=success');
exit();

/**
 * ==================== HOW IT WORKS ====================
 * 
 * 1. SESSION CLEANUP:
 *    - $_SESSION = array() clears all session data
 *    - This removes user_id, full_name, role, etc.
 * 
 * 2. COOKIE REMOVAL:
 *    - setcookie() removes the session cookie from browser
 *    - Setting expiration to past time deletes the cookie
 * 
 * 3. SESSION DESTRUCTION:
 *    - session_destroy() deletes the session file from server
 *    - This completely removes all traces of the session
 * 
 * 4. REDIRECT:
 *    - User is sent back to login page
 *    - URL parameter ?logout=success shows success message
 * 
 * 
 * ==================== SECURITY NOTES ====================
 * 
 * - Always clear session data before destroying session
 * - Remove session cookies to prevent session fixation
 * - Use exit() after header() to stop script execution
 * - Don't store sensitive data in sessions that shouldn't persist
 */
?>
