<?php
/**
 * Create Attendance Session
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('lecturer');

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
$stmt->execute([$userId]);
$lecturer = $stmt->fetch();
if (!$lecturer) { die("Lecturer record not found."); }
$lecturerId = $lecturer['lecturer_id'];

$coursesStmt = $pdo->prepare("
    SELECT c.course_id, c.course_code, c.course_name 
    FROM courses c
    JOIN course_assignments ca ON c.course_id = ca.course_id
    WHERE ca.lecturer_id = ?
    ORDER BY c.course_code ASC
");
$coursesStmt->execute([$lecturerId]);
$courses = $coursesStmt->fetchAll();

$errors = [];
$formData = ['course_id' => '', 'session_date' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = ['course_id' => (int)($_POST['course_id'] ?? 0), 'session_date' => trim($_POST['session_date'] ?? '')];

    if ($formData['course_id'] <= 0) $errors[] = 'Please select a course';
    if (empty($formData['session_date'])) $errors[] = 'Please select a session date';
    elseif (!strtotime($formData['session_date'])) $errors[] = 'Invalid date format';

    if (empty($errors)) {
        if (sessionExists($pdo, $formData['course_id'], $lecturerId, $formData['session_date'])) {
            $errors[] = 'Attendance session already exists for this course on this date';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO attendance_sessions (course_id, lecturer_id, session_date) VALUES (?, ?, ?)");
            $stmt->execute([$formData['course_id'], $lecturerId, $formData['session_date']]);
            $sessionId = $pdo->lastInsertId();
            redirectWithMessage("mark.php?session_id={$sessionId}", 'success', 'Session created! Now mark attendance.');
        } catch (PDOException $e) {
            error_log("Create session error: " . $e->getMessage());
            $errors[] = ($e->getCode() == 23000) ? 'This session already exists (duplicate)' : 'Database error occurred.';
        }
    }
}

$pageTitle   = 'New Session';
$currentPage = 'attendance';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/lecturer_dashboard.php'],
    ['label' => 'Attendance', 'url' => 'index.php'],
    ['label' => 'New Session']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Create Attendance Session</h1><p>Start a new session and mark attendance.</p></div>
    <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<?php if (count($courses) == 0): ?>
    <div class="card"><div class="card-body text-center py-5">
        <i class="fas fa-book fa-3x mb-3" style="opacity:0.2;"></i>
        <p class="text-muted">No courses available. Please contact an administrator.</p>
    </div></div>
<?php else: ?>

    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i> One session per course per date. After creating, you'll mark attendance.</div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>
            <div><strong>Please fix:</strong><ul class="mb-0 mt-1"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
        </div>
    <?php endif; ?>

    <div class="card"><div class="card-body p-4">
        <form method="POST" action="">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Course <span class="required">*</span></label>
                    <select class="form-select" name="course_id" required>
                        <option value="">-- Choose a Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['course_id']; ?>" <?php echo $formData['course_id'] == $c['course_id'] ? 'selected' : ''; ?>>
                                <?php echo e($c['course_code'] . ' - ' . $c['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Session Date <span class="required">*</span></label>
                    <input type="date" class="form-control" name="session_date" value="<?php echo e($formData['session_date']); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="d-flex gap-2 pt-3 mt-4 border-top">
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Create &amp; Mark Attendance</button>
                <a href="index.php" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div></div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
