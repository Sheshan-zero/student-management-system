<?php
/**
 * 404 Not Found Page
 * 
 * Shown when a user navigates to a page that doesn't exist.
 * Can be set as the custom ErrorDocument in Apache .htaccess.
 */
session_start();

// Determine where to send the user
$homeLink = 'login.php';
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':    $homeLink = 'dashboards/admin_dashboard.php'; break;
        case 'lecturer': $homeLink = 'dashboards/lecturer_dashboard.php'; break;
        case 'student':  $homeLink = 'dashboards/student_dashboard.php'; break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - SMS</title>
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
        .error-code { font-size: 48px; font-weight: 800; color: #fd7e14; margin-bottom: 8px; }
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
        <div class="error-icon">🔍</div>
        <div class="error-code">404</div>
        <div class="error-title">Page Not Found</div>
        <p class="error-text">
            The page you're looking for doesn't exist or has been moved. 
            Please check the URL and try again.
        </p>
        <a href="<?php echo $homeLink; ?>" class="back-btn">
            <?php echo isset($_SESSION['role']) ? '← Back to Dashboard' : '← Go to Login'; ?>
        </a>
    </div>
</body>
</html>
