<?php
/**
 * Edit Student Page - edit.php
 * Updates records in both users and students tables
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($studentId <= 0) {
    redirectWithMessage('index.php', 'error', 'Invalid student ID');
}

// Fetch student data
try {
    $stmt = $pdo->prepare("
        SELECT s.student_id, s.registration_no, s.department, s.intake_year, s.status, s.user_id,
               u.full_name, u.email
        FROM students s JOIN users u ON s.user_id = u.id WHERE s.student_id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    if (!$student) { redirectWithMessage('index.php', 'error', 'Student not found'); }
} catch (PDOException $e) {
    error_log("Fetch student error: " . $e->getMessage());
    redirectWithMessage('index.php', 'error', 'Error loading student data');
}

$formData = [
    'full_name'       => $student['full_name'],
    'email'           => $student['email'],
    'registration_no' => $student['registration_no'],
    'department'      => $student['department'],
    'intake_year'     => $student['intake_year'],
    'status'          => $student['status']
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'full_name'       => sanitizeInput($_POST['full_name'] ?? ''),
        'email'           => sanitizeEmail($_POST['email'] ?? ''),
        'registration_no' => sanitizeInput($_POST['registration_no'] ?? ''),
        'department'      => sanitizeInput($_POST['department'] ?? ''),
        'intake_year'     => sanitizeInput($_POST['intake_year'] ?? ''),
        'status'          => $_POST['status'] ?? 'active'
    ];

    $requiredErrors = validateRequired([
        'full_name' => $formData['full_name'], 'email' => $formData['email'],
        'registration_no' => $formData['registration_no'], 'department' => $formData['department'],
        'intake_year' => $formData['intake_year']
    ]);
    $errors = array_merge($errors, $requiredErrors);

    if (!isEmpty($formData['email']) && !isValidEmail($formData['email'])) $errors[] = 'Invalid email format';
    if (!isEmpty($formData['email']) && emailExists($pdo, $formData['email'], $student['user_id'])) $errors[] = 'Email already exists in the system';
    if (!isEmpty($formData['registration_no']) && registrationExists($pdo, $formData['registration_no'], $studentId)) $errors[] = 'Registration number already exists';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$formData['full_name'], $formData['email'], $student['user_id']]);
            $stmt = $pdo->prepare("UPDATE students SET registration_no = ?, department = ?, intake_year = ?, status = ? WHERE student_id = ?");
            $stmt->execute([$formData['registration_no'], $formData['department'], $formData['intake_year'], $formData['status'], $studentId]);
            $pdo->commit();
            logActivity($pdo, $_SESSION['user_id'], 'Update Student', "Registration No: {$formData['registration_no']}");
            redirectWithMessage('index.php', 'success', 'Student updated successfully!');
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Update student error: " . $e->getMessage());
            $errors[] = 'Database error occurred. Please try again.';
        }
    }
}

$pageTitle   = 'Edit Student';
$currentPage = 'students';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Students', 'url' => 'index.php'],
    ['label' => 'Edit Student']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Edit Student</h1>
        <p>Update information for <?php echo htmlspecialchars($student['full_name']); ?></p>
    </div>
    <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Password cannot be changed from this form.
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <div><strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-1"><?php foreach ($errors as $error): ?><li><?php echo e($error); ?></li><?php endforeach; ?></ul>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="">
            <h6 class="fw-700 mb-3"><i class="fas fa-user me-2 text-primary"></i>Personal Information</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <label class="form-label">Full Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="full_name" value="<?php echo e($formData['full_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email" class="form-control" name="email" value="<?php echo e($formData['email']); ?>" required>
                </div>
            </div>

            <hr class="my-4">
            <h6 class="fw-700 mb-3"><i class="fas fa-graduation-cap me-2 text-primary"></i>Academic Information</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Registration Number <span class="required">*</span></label>
                    <input type="text" class="form-control" name="registration_no" value="<?php echo e($formData['registration_no']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Department <span class="required">*</span></label>
                    <input type="text" class="form-control" name="department" value="<?php echo e($formData['department']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Intake Year <span class="required">*</span></label>
                    <input type="number" class="form-control" name="intake_year" value="<?php echo e($formData['intake_year']); ?>" min="2000" max="2100" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="required">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo $formData['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="graduated" <?php echo $formData['status'] === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2 pt-3 border-top">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Student</button>
                <a href="index.php" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
