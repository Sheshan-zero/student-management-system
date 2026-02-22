<?php
/**
 * Database Connection File - db.php
 * 
 * This file establishes a secure PDO connection to the MySQL database.
 * PDO (PHP Data Objects) is used because it:
 * - Supports prepared statements (prevents SQL injection)
 * - Provides better error handling
 * - Works with multiple database types
 * 
 * USAGE: Include this file in any PHP script that needs database access
 * Example: require_once 'config/db.php';
 */

// ==================== DATABASE CREDENTIALS ====================
// SECURITY NOTE: In production, move these to environment variables (.env file)
// Never commit real credentials to version control (Git)

define('DB_HOST', 'localhost');        // Database server (localhost for XAMPP)
define('DB_NAME', 'sms_db');          // Database name (from your SQL)
define('DB_USER', 'root');            // Database username (XAMPP default)
define('DB_PASS', '');                // Database password (empty by default in XAMPP)
define('DB_CHARSET', 'utf8mb4');      // Character set (utf8mb4 supports emojis)

// ==================== PDO CONNECTION ====================
try {
    // Data Source Name (DSN) - contains database connection information
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    // PDO Options - configures how PDO behaves
    $options = [
        // ERRMODE_EXCEPTION: Throws exceptions on errors (easier to catch and handle)
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        
        // FETCH_ASSOC: Returns results as associative arrays (e.g., $row['name'])
        // This is more readable than numeric indexes
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        
        // Disable emulated prepared statements for better security
        // Forces MySQL to use real prepared statements
        PDO::ATTR_EMULATE_PREPARES   => false,
        
        // Set connection to use UTF-8 encoding
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];
    
    // Create PDO instance (the actual database connection)
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Optional: Uncomment for debugging (REMOVE in production for security)
    // echo "Database connection successful!";
    
} catch (PDOException $e) {
    // ==================== ERROR HANDLING ====================
    // If connection fails, catch the exception and display error
    
    // Log error to file (recommended for production)
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display user-friendly message (hide technical details in production)
    die("Database connection failed. Please contact the administrator.");
    
    // For development/debugging, you can show the actual error:
    // die("Connection failed: " . $e->getMessage());
}

/**
 * ==================== USAGE EXAMPLES ====================
 * 
 * 1. SIMPLE SELECT QUERY:
 * ---------------------------------------------------
 * require_once 'config/db.php';
 * 
 * $stmt = $pdo->query("SELECT * FROM users");
 * $users = $stmt->fetchAll();
 * 
 * foreach ($users as $user) {
 *     echo $user['full_name'];
 * }
 * 
 * 
 * 2. PREPARED STATEMENT (PREVENTS SQL INJECTION):
 * ---------------------------------------------------
 * require_once 'config/db.php';
 * 
 * $email = 'user@example.com';
 * 
 * // Prepare the query with placeholder (?)
 * $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
 * 
 * // Execute with actual value
 * $stmt->execute([$email]);
 * 
 * // Fetch single result
 * $user = $stmt->fetch();
 * 
 * 
 * 3. INSERT WITH PREPARED STATEMENT:
 * ---------------------------------------------------
 * require_once 'config/db.php';
 * 
 * $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
 * 
 * $stmt->execute([
 *     'John Doe',
 *     'john@example.com',
 *     password_hash('mypassword', PASSWORD_DEFAULT),  // ALWAYS hash passwords!
 *     'student'
 * ]);
 * 
 * // Get the ID of inserted record
 * $lastId = $pdo->lastInsertId();
 * 
 * 
 * 4. NAMED PLACEHOLDERS (MORE READABLE):
 * ---------------------------------------------------
 * $stmt = $pdo->prepare("UPDATE students SET status = :status WHERE student_id = :id");
 * 
 * $stmt->execute([
 *     ':status' => 'graduated',
 *     ':id' => 123
 * ]);
 * 
 * 
 * 5. TRANSACTION (FOR MULTIPLE RELATED QUERIES):
 * ---------------------------------------------------
 * try {
 *     $pdo->beginTransaction();
 *     
 *     // Multiple queries here
 *     $pdo->exec("DELETE FROM enrollments WHERE student_id = 5");
 *     $pdo->exec("DELETE FROM students WHERE student_id = 5");
 *     
 *     $pdo->commit();  // Save all changes
 * } catch (Exception $e) {
 *     $pdo->rollBack();  // Undo all changes if error occurs
 *     echo "Transaction failed: " . $e->getMessage();
 * }
 */

// ==================== BEST PRACTICES ====================
/**
 * 1. ALWAYS use prepared statements for user input (prevents SQL injection)
 * 2. NEVER store passwords in plain text (use password_hash())
 * 3. Use transactions for operations that modify multiple tables
 * 4. Close connections when done (PDO closes automatically, but you can set $pdo = null)
 * 5. Use try-catch blocks to handle database errors gracefully
 * 6. In production:
 *    - Move credentials to .env file
 *    - Disable error display (set display_errors = Off in php.ini)
 *    - Log errors to files instead
 *    - Use HTTPS to encrypt data in transit
 */
?>
