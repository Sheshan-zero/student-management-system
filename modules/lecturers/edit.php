<?php
/**
 * Edit Lecturer — Admin
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

$lecturerId = (int)($_GET['id'] ?? 0);
if ($lecturerId <= 0) { redirectWithMessage('index.php', 'error', 'Invalid lecturer ID.'); }

// Fetch lecturer
$stmt = $pdo->prepare("SELECT l.*, u.full_name, u.email FROM lecturers l JOIN users u ON l.user_id = u.id WHERE l.lecturer_id = ?");
$stmt->execute([$lecturerId]);
$lecturer = $stmt->fetch();
if (!$lecturer) { redirectWithMessage('index.php', 'error', 'Lecturer not found.'); }

$errors = [];
$full_name  = $lecturer['full_name'];
$email      = $lecturer['email'];
$department = $lecturer['department'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $department = trim($_POST['department'] ?? '');

    if (empty($full_name)) $errors[] = 'Full name is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    elseif (emailExists($pdo, $email, $lecturer['user_id'])) $errors[] = 'Email already exists.';
    if (!empty($password) && strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?")->execute([$full_name, $email, $hashed, $lecturer['user_id']]);
            } else {
                $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?")->execute([$full_name, $email, $lecturer['user_id']]);
            }
            $pdo->prepare("UPDATE lecturers SET department = ? WHERE lecturer_id = ?")->execute([$department, $lecturerId]);
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Update Lecturer', "Email: $email");
            redirectWithMessage('index.php', 'success', 'Lecturer updated successfully.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Database error. Please try again.';
        }
    }
}

$pageTitle   = 'Edit Lecturer';
$currentPage = 'lecturers';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Lecturers', 'url' => 'index.php'],
    ['label' => 'Edit']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Edit Lecturer</h1><p>Update lecturer information.</p></div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>
        <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Lecturer Details</h6></div>
    <div class="card-body">
        <form method="POST" action="">
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
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="password" minlength="6">
                    <div class="form-text">Leave blank to keep current password.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <input type="text" class="form-control" name="department" value="<?php echo e($department); ?>">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Lecturer</button>
                <a href="index.php" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
