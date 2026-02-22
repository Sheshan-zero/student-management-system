<?php
/**
 * Create Enrollment Page
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

$errors = [];
$formData = ['student_id' => '', 'course_id' => ''];

$students = $pdo->query("SELECT s.student_id, s.registration_no, u.full_name, s.status FROM students s INNER JOIN users u ON s.user_id = u.id WHERE s.status = 'Active' ORDER BY s.registration_no ASC")->fetchAll();
$courses  = $pdo->query("SELECT course_id, course_code, course_name, credits FROM courses ORDER BY course_code ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = ['student_id' => (int)($_POST['student_id'] ?? 0), 'course_id' => (int)($_POST['course_id'] ?? 0)];

    if ($formData['student_id'] <= 0) $errors[] = 'Please select a student';
    if ($formData['course_id'] <= 0) $errors[] = 'Please select a course';

    if (empty($errors)) {
        $eligibility = isStudentEligible($pdo, $formData['student_id']);
        if (!$eligibility['eligible']) $errors[] = $eligibility['message'];
        if (enrollmentExists($pdo, $formData['student_id'], $formData['course_id'])) $errors[] = 'This student is already enrolled in this course';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, enrolled_at) VALUES (?, ?, NOW())");
            $stmt->execute([$formData['student_id'], $formData['course_id']]);
            logActivity($pdo, $_SESSION['user_id'], 'Create Enrollment', "Student ID: {$formData['student_id']}, Course ID: {$formData['course_id']}");
            redirectWithMessage('index.php', 'success', 'Student enrolled successfully!');
        } catch (PDOException $e) {
            error_log("Create enrollment error: " . $e->getMessage());
            $errors[] = ($e->getCode() == 23000) ? 'This enrollment already exists (duplicate)' : 'Database error occurred. Please try again.';
        }
    }
}

$pageTitle   = 'Enroll Student';
$currentPage = 'enrollments';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Enrollments', 'url' => 'index.php'],
    ['label' => 'Enroll Student']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Enroll Student</h1><p>Assign a student to a course.</p></div>
    <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<?php if (count($students) == 0 || count($courses) == 0): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <?php if (count($students) == 0): ?>
                <i class="fas fa-user-graduate fa-3x mb-3" style="opacity:0.2;"></i>
                <p class="text-muted">No active students available. <a href="../students/create.php">Add a student</a> first.</p>
            <?php else: ?>
                <i class="fas fa-book fa-3x mb-3" style="opacity:0.2;"></i>
                <p class="text-muted">No courses available. <a href="../courses/create.php">Create a course</a> first.</p>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>

    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Only <strong>active</strong> students can be enrolled. A student cannot be enrolled in the same course twice.
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
                    <div class="col-md-6">
                        <label class="form-label">Select Student <span class="required">*</span></label>
                        <select class="form-select" name="student_id" required>
                            <option value="">-- Choose a Student --</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?php echo $s['student_id']; ?>" <?php echo $formData['student_id'] == $s['student_id'] ? 'selected' : ''; ?>>
                                    <?php echo e($s['registration_no'] . ' - ' . $s['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Select Course <span class="required">*</span></label>
                        <select class="form-select" name="course_id" required>
                            <option value="">-- Choose a Course --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['course_id']; ?>" <?php echo $formData['course_id'] == $c['course_id'] ? 'selected' : ''; ?>>
                                    <?php echo e($c['course_code'] . ' - ' . $c['course_name'] . ' (' . $c['credits'] . ' cr)'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-2 pt-3 mt-4 border-top">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i> Enroll Student</button>
                    <a href="index.php" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
