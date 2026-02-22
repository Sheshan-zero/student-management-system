<?php
/**
 * Edit Course Page
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if ($courseId <= 0) { redirectWithMessage('index.php', 'error', 'Invalid course ID'); }

try {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();
    if (!$course) { redirectWithMessage('index.php', 'error', 'Course not found'); }
} catch (PDOException $e) {
    error_log("Fetch course error: " . $e->getMessage());
    redirectWithMessage('index.php', 'error', 'Error loading course data');
}

$formData = ['course_code' => $course['course_code'], 'course_name' => $course['course_name'], 'credits' => $course['credits']];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'course_code' => strtoupper(sanitizeInput($_POST['course_code'] ?? '')),
        'course_name' => sanitizeInput($_POST['course_name'] ?? ''),
        'credits'     => intval($_POST['credits'] ?? 0)
    ];

    $requiredErrors = validateRequired(['course_code' => $formData['course_code'], 'course_name' => $formData['course_name'], 'credits' => $formData['credits']]);
    $errors = array_merge($errors, $requiredErrors);

    if (!isEmpty($formData['credits'])) { $errors = array_merge($errors, validateCredits($formData['credits'])); }
    if (!isEmpty($formData['course_code']) && courseCodeExists($pdo, $formData['course_code'], $courseId)) { $errors[] = 'Course code already exists'; }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE courses SET course_code = ?, course_name = ?, credits = ? WHERE course_id = ?");
            $stmt->execute([$formData['course_code'], $formData['course_name'], $formData['credits'], $courseId]);
            logActivity($pdo, $_SESSION['user_id'], 'Update Course', "Course Code: {$formData['course_code']}");
            redirectWithMessage('index.php', 'success', 'Course updated successfully!');
        } catch (PDOException $e) {
            error_log("Update course error: " . $e->getMessage());
            $errors[] = 'Database error occurred. Please try again.';
        }
    }
}

$pageTitle   = 'Edit Course';
$currentPage = 'courses';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Courses', 'url' => 'index.php'],
    ['label' => 'Edit Course']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Edit Course</h1><p>Update <?php echo htmlspecialchars($course['course_code']); ?> — <?php echo htmlspecialchars($course['course_name']); ?></p></div>
    <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>
        <div><strong>Please fix:</strong><ul class="mb-0 mt-1"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Course Code <span class="required">*</span></label>
                    <input type="text" class="form-control" name="course_code" value="<?php echo e($formData['course_code']); ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Course Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="course_name" value="<?php echo e($formData['course_name']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Credits <span class="required">*</span></label>
                    <input type="number" class="form-control" name="credits" value="<?php echo e($formData['credits']); ?>" min="1" max="30" required>
                </div>
            </div>
            <div class="d-flex gap-2 pt-3 mt-4 border-top">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Course</button>
                <a href="index.php" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
