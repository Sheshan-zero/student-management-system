<?php
/**
 * Access Denied Page
 * 
 * Shown when a user tries to access a page they don't have permission for.
 * The auth.php requireRole() function can redirect here.
 */
session_start();

// Determine where to send the user back to
$dashboardLink = 'login.php';
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':    $dashboardLink = 'dashboards/admin_dashboard.php'; break;
        case 'lecturer': $dashboardLink = 'dashboards/lecturer_dashboard.php'; break;
        case 'student':  $dashboardLink = 'dashboards/student_dashboard.php'; break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - SMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .error-box {
            background: white;
            padding: 50px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 450px;
            width: 100%;
        }
        .error-icon { font-size: 64px; margin-bottom: 15px; }
        .error-code { font-size: 48px; font-weight: 800; color: #dc3545; margin-bottom: 8px; }
        .error-title { font-size: 22px; color: #333; margin-bottom: 12px; }
        .error-text { color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 25px; }
        .back-btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .back-btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-icon">🔒</div>
        <div class="error-code">403</div>
        <div class="error-title">Access Denied</div>
        <p class="error-text">
            You don't have permission to access this page. 
            This area is restricted to authorized users only.
        </p>
        <a href="<?php echo $dashboardLink; ?>" class="back-btn">
            <?php echo isset($_SESSION['role']) ? '← Back to Dashboard' : '← Go to Login'; ?>
        </a>
    </div>
</body>
</html>
