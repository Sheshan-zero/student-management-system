<?php
/**
 * Edit User — Admin
 * Edit name, email, and optionally reset password
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) { redirectWithMessage('index.php', 'error', 'Invalid user ID.'); }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) { redirectWithMessage('index.php', 'error', 'User not found.'); }

$errors = [];
$full_name = $user['full_name'];
$email     = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($full_name)) $errors[] = 'Full name is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    elseif (emailExists($pdo, $email, $userId)) $errors[] = 'Email already taken by another user.';
    if (!empty($password) && strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        try {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?")->execute([$full_name, $email, $hashed, $userId]);
            } else {
                $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?")->execute([$full_name, $email, $userId]);
            }
            redirectWithMessage('index.php', 'success', 'User updated successfully.');
        } catch (PDOException $e) {
            $errors[] = 'Database error. Please try again.';
        }
    }
}

$pageTitle   = 'Edit User';
$currentPage = 'users';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Users', 'url' => 'index.php'],
    ['label' => 'Edit']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Edit User</h1><p>Update user account details.</p></div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>
        <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-user-edit me-2"></i>User Details</h6></div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="full_name" value="<?php echo e($full_name); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" class="form-control" name="email" value="<?php echo e($email); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                    <div class="form-text">Role cannot be changed here to prevent data integrity issues.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reset Password</label>
                    <input type="password" class="form-control" name="password" minlength="6" placeholder="Enter new password">
                    <div class="form-text">Leave blank to keep current password.</div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update User</button>
                <a href="index.php" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
