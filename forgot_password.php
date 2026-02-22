<?php
/**
 * Forgot Password — Public
 * Send a password reset token
 */
require_once 'config/db.php';
require_once 'includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors  = [];
$success = false;
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            // Generate token
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            try {
                $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)")
                    ->execute([$email, $token, $expires]);
                $success = true;
                
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . "/reset_password.php?token=$token";
                
                $subject = "Password Reset Request - SMS Campus";
                $message = "You requested a password reset. Click the link below to reset your password:\n\n$resetLink\n\nIf you did not request this, please ignore this email.";
                $headers = "From: noreply@smscampus.local\r\n";
                @mail($email, $subject, $message, $headers);
                
            } catch (PDOException $e) {
                $errors[] = 'Could not process request.';
            }
        } else {
            // Don't reveal if email exists — show success anyway
            $success = true;
        }
    }
}

$pageTitle  = 'Forgot Password';
$isLoginPage = true;
require_once 'includes/header.php';
?>

<div class="login-card">
    <div class="logo">
        <div class="icon-box"><i class="fas fa-key"></i></div>
        <h2>Forgot Password</h2>
        <p>Enter your email to receive a reset link.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> If an account exists, a reset link has been generated.</div>
        <?php if (isset($resetLink)): ?>
            <div class="alert alert-info">
                <small><strong>Dev mode:</strong> <a href="<?php echo $resetLink; ?>"><?php echo $resetLink; ?></a></small>
            </div>
        <?php endif; ?>
        <a href="login.php" class="btn btn-primary w-100">Back to Login</a>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">Send Reset Link</button>
            <div class="text-center"><a href="login.php">Back to Login</a></div>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
