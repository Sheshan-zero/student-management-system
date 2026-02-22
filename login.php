<?php
/**
 * Login Page — Modern Card UI
 * Standalone page (no sidebar/topbar layout)
 */
session_start();
require_once 'config/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'admin':    header('Location: dashboards/admin_dashboard.php'); break;
        case 'lecturer': header('Location: dashboards/lecturer_dashboard.php'); break;
        case 'student':  header('Location: dashboards/student_dashboard.php'); break;
    }
    exit();
}

$error = '';
$success = '';

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                switch ($user['role']) {
                    case 'admin':    header('Location: dashboards/admin_dashboard.php'); break;
                    case 'lecturer': header('Location: dashboards/lecturer_dashboard.php'); break;
                    case 'student':  header('Location: dashboards/student_dashboard.php'); break;
                    default: $error = 'Invalid user role';
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = 'You have been logged out successfully.';
}

// Standalone login page layout
$isLoginPage = true;
$pageTitle = 'Login';
require_once 'includes/header.php';
?>

<div class="login-card">
    <div class="logo">
        <div class="icon-box"><i class="fas fa-graduation-cap"></i></div>
        <h2>SMS Campus</h2>
        <p>Sign in to your account</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label" for="email">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email"
                       placeholder="you@example.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label d-flex justify-content-between align-items-center" for="password">
                <span>Password</span>
                <a href="forgot_password.php" tabindex="-1" style="font-size:12px; font-weight:500; text-decoration:none;">Forgot Password?</a>
            </label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="Enter your password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:15px;">
            <i class="fas fa-sign-in-alt me-2"></i>Sign In
        </button>
    </form>

    <div class="mt-4 p-3 rounded" style="background:var(--body-bg); font-size:12px;">
        <div class="fw-600 mb-2" style="font-size:13px;"><i class="fas fa-flask me-1"></i> Test Credentials</div>
        <div class="d-flex justify-content-between mb-1">
            <span>Admin</span>
            <code>admin@sms.com / admin123</code>
        </div>
        <div class="d-flex justify-content-between mb-1">
            <span>Lecturer</span>
            <code>lecturer@sms.com / lecturer123</code>
        </div>
        <div class="d-flex justify-content-between">
            <span>Student</span>
            <code>student@sms.com / student123</code>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
