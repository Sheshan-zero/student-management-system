<?php
/**
 * Reset Password — Public
 * Token-based password reset
 */
require_once 'config/db.php';
require_once 'includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors  = [];
$success = false;
$token   = $_GET['token'] ?? '';
$valid   = false;

if (empty($token)) {
    $errors[] = 'Invalid or missing reset token.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    if ($reset) {
        $valid = true;
    } else {
        $errors[] = 'This reset link has expired or already been used.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (empty($password)) $errors[] = 'Password is required.';
    elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hashed, $reset['email']]);
            $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")->execute([$reset['id']]);
            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Could not reset password. Try again.';
        }
    }
}

$pageTitle   = 'Reset Password';
$isLoginPage = true;
require_once 'includes/header.php';
?>

<div class="login-card">
    <div class="logo">
        <div class="icon-box"><i class="fas fa-lock-open"></i></div>
        <h2>Reset Password</h2>
        <p>Choose a new password for your account.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> Password reset successfully!</div>
        <a href="login.php" class="btn btn-primary w-100">Go to Login</a>
    <?php elseif (!empty($errors) && !$valid): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
        <a href="forgot_password.php" class="btn btn-primary w-100">Request New Link</a>
    <?php elseif ($valid): ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" name="password" required minlength="6">
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="confirm" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
