<?php
/**
 * My Profile Page
 * Accessible by all roles: admin, lecturer, student
 * Shows user info and allows password change
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';

$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) { die("User not found."); }

// Fetch role-specific data
$extraInfo = [];
if ($role === 'student') {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$userId]);
    $extraInfo = $stmt->fetch() ?: [];
} elseif ($role === 'lecturer') {
    $stmt = $pdo->prepare("SELECT * FROM lecturers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $extraInfo = $stmt->fetch() ?: [];
}

// Handle password change
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword)) $errors[] = 'Current password is required.';
    if (empty($newPassword)) $errors[] = 'New password is required.';
    elseif (strlen($newPassword) < 6) $errors[] = 'New password must be at least 6 characters.';
    if ($newPassword !== $confirmPassword) $errors[] = 'New passwords do not match.';

    if (empty($errors)) {
        if (!password_verify($currentPassword, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            $success = true;
        }
    }
}

// Determine dashboard URL
$dashUrl = '../../dashboards/';
if ($role === 'admin') $dashUrl .= 'admin_dashboard.php';
elseif ($role === 'lecturer') $dashUrl .= 'lecturer_dashboard.php';
else $dashUrl .= 'student_dashboard.php';

$pageTitle   = 'My Profile';
$currentPage = 'profile';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $dashUrl],
    ['label' => 'My Profile']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>My Profile</h1><p>View your account information and change your password.</p></div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> Password changed successfully!</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>
        <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Account Info -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-user me-2"></i>Account Information</h6></div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted" style="width:40%;">Full Name</td><td class="fw-600"><?php echo e($user['full_name']); ?></td></tr>
                    <tr><td class="text-muted">Email</td><td class="fw-600"><?php echo e($user['email']); ?></td></tr>
                    <tr><td class="text-muted">Role</td><td><span class="badge bg-primary"><?php echo ucfirst($role); ?></span></td></tr>
                    <tr><td class="text-muted">Account Created</td><td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Role-specific Info -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-id-card me-2"></i>
                <?php echo $role === 'student' ? 'Student Details' : ($role === 'lecturer' ? 'Lecturer Details' : 'Admin Details'); ?>
            </h6></div>
            <div class="card-body">
                <?php if ($role === 'student' && !empty($extraInfo)): ?>
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted" style="width:40%;">Registration No</td><td class="fw-600"><?php echo e($extraInfo['registration_no'] ?? '—'); ?></td></tr>
                        <tr><td class="text-muted">Department</td><td class="fw-600"><?php echo e($extraInfo['department'] ?? '—'); ?></td></tr>
                        <tr><td class="text-muted">Intake Year</td><td class="fw-600"><?php echo e($extraInfo['intake_year'] ?? '—'); ?></td></tr>
                        <tr><td class="text-muted">Status</td><td>
                            <span class="badge <?php echo ($extraInfo['status'] ?? '') === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo e($extraInfo['status'] ?? '—'); ?>
                            </span>
                        </td></tr>
                    </table>
                <?php elseif ($role === 'lecturer' && !empty($extraInfo)): ?>
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted" style="width:40%;">Lecturer ID</td><td class="fw-600"><?php echo $extraInfo['lecturer_id'] ?? '—'; ?></td></tr>
                        <tr><td class="text-muted">Department</td><td class="fw-600"><?php echo e($extraInfo['department'] ?? '—'); ?></td></tr>
                    </table>
                <?php else: ?>
                    <p class="text-muted mb-0">Administrator account — full system access.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Change Password -->
<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h6></div>
    <div class="card-body">
        <form method="POST" action="" style="max-width:500px;">
            <div class="mb-3">
                <label class="form-label">Current Password <span class="required">*</span></label>
                <input type="password" class="form-control" name="current_password" required>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password <span class="required">*</span></label>
                <input type="password" class="form-control" name="new_password" required minlength="6">
                <div class="form-text">Minimum 6 characters.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm New Password <span class="required">*</span></label>
                <input type="password" class="form-control" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Password</button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
