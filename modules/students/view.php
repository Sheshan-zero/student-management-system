<?php
/**
 * View Student Page - view.php
 * Detailed student profile + enrolled courses
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($studentId <= 0) { redirectWithMessage('index.php', 'error', 'Invalid student ID'); }

try {
    $stmt = $pdo->prepare("
        SELECT s.student_id, s.registration_no, s.department, s.intake_year, s.status, s.user_id, s.created_at,
               u.full_name, u.email
        FROM students s JOIN users u ON s.user_id = u.id WHERE s.student_id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    if (!$student) { redirectWithMessage('index.php', 'error', 'Student not found'); }

    // Enrolled courses
    $stmt = $pdo->prepare("
        SELECT c.course_code, c.course_name, c.credits
        FROM enrollments e JOIN courses c ON e.course_id = c.course_id
        WHERE e.student_id = ? ORDER BY c.course_code
    ");
    $stmt->execute([$studentId]);
    $enrolledCourses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch student error: " . $e->getMessage());
    redirectWithMessage('index.php', 'error', 'Error loading student data');
}

$pageTitle   = 'Student Profile';
$currentPage = 'students';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Students', 'url' => 'index.php'],
    ['label' => htmlspecialchars($student['full_name'])]
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?php echo htmlspecialchars($student['full_name']); ?></h1>
        <p>Student Profile &amp; Enrolled Courses</p>
    </div>
    <div class="d-flex gap-2">
        <a href="../reports/transcript_pdf.php?student_id=<?php echo $student['student_id']; ?>" target="_blank" class="btn btn-outline-primary">
            <i class="fas fa-file-pdf me-1"></i> Transcript
        </a>
        <a href="edit.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-primary">
            <i class="fas fa-pen me-1"></i> Edit
        </a>
        <a href="index.php" class="btn btn-light">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Personal Info Card -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6><i class="fas fa-user me-2"></i>Personal Information</h6></div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted fw-600" style="width:40%">Full Name</td><td><?php echo e($student['full_name']); ?></td></tr>
                    <tr><td class="text-muted fw-600">Email</td><td><?php echo e($student['email']); ?></td></tr>
                    <tr><td class="text-muted fw-600">Joined</td><td><?php echo isset($student['created_at']) ? date('M d, Y', strtotime($student['created_at'])) : '—'; ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <!-- Academic Info Card -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6><i class="fas fa-graduation-cap me-2"></i>Academic Information</h6></div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted fw-600" style="width:40%">Reg. No</td><td><code><?php echo e($student['registration_no']); ?></code></td></tr>
                    <tr><td class="text-muted fw-600">Department</td><td><?php echo e($student['department']); ?></td></tr>
                    <tr><td class="text-muted fw-600">Intake Year</td><td><?php echo e($student['intake_year']); ?></td></tr>
                    <tr><td class="text-muted fw-600">Status</td><td><span class="badge badge-<?php echo e($student['status']); ?>"><?php echo ucfirst(e($student['status'])); ?></span></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Enrolled Courses -->
<div class="card mt-4 table-card">
    <div class="card-header">
        <h6><i class="fas fa-book-open me-2"></i>Enrolled Courses (<?php echo count($enrolledCourses); ?>)</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Code</th><th>Course Name</th><th>Credits</th></tr></thead>
            <tbody>
                <?php if (count($enrolledCourses) > 0): ?>
                    <?php foreach ($enrolledCourses as $c): ?>
                    <tr>
                        <td><code><?php echo e($c['course_code']); ?></code></td>
                        <td class="fw-600"><?php echo e($c['course_name']); ?></td>
                        <td><span class="badge" style="background:var(--primary-light);color:var(--primary);"><?php echo $c['credits']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">Not enrolled in any courses.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
