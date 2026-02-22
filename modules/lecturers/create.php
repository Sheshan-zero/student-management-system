<?php
/**
 * Create Lecturer — Admin
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

$errors = [];
$full_name = $email = $department = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $department = trim($_POST['department'] ?? '');

    if (empty($full_name)) $errors[] = 'Full name is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    elseif (emailExists($pdo, $email)) $errors[] = 'Email already exists.';
    if (empty($password)) $errors[] = 'Password is required.';
    elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'lecturer')");
            $stmt->execute([$full_name, $email, $hashedPassword]);
            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO lecturers (user_id, department) VALUES (?, ?)");
            $stmt->execute([$userId, $department]);
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Create Lecturer', "Email: $email");
            redirectWithMessage('index.php', 'success', 'Lecturer created successfully.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Database error. Please try again.';
        }
    }
}

$pageTitle   = 'Add Lecturer';
$currentPage = 'lecturers';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Lecturers', 'url' => 'index.php'],
    ['label' => 'Add New']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Add Lecturer</h1><p>Create a new lecturer account.</p></div>
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
                    <label class="form-label">Password <span class="required">*</span></label>
                    <input type="password" class="form-control" name="password" required minlength="6">
                    <div class="form-text">Minimum 6 characters.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <input type="text" class="form-control" name="department" value="<?php echo e($department); ?>" placeholder="e.g. Computer Science">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Lecturer</button>
                <a href="index.php" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
