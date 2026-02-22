<?php
/**
 * Course-Lecturer Assignment — Admin
 * Assign courses to lecturers
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

// Handle assign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'assign') {
        $courseId    = (int)$_POST['course_id'];
        $lecturerId = (int)$_POST['lecturer_id'];
        try {
            $pdo->prepare("INSERT IGNORE INTO course_assignments (course_id, lecturer_id) VALUES (?, ?)")
                ->execute([$courseId, $lecturerId]);
            redirectWithMessage('index.php', 'success', 'Course assigned successfully.');
        } catch (PDOException $e) {
            redirectWithMessage('index.php', 'error', 'Assignment failed. It may already exist.');
        }
    } elseif ($_POST['action'] === 'unassign') {
        $assignId = (int)$_POST['assignment_id'];
        $pdo->prepare("DELETE FROM course_assignments WHERE assignment_id = ?")->execute([$assignId]);
        redirectWithMessage('index.php', 'success', 'Assignment removed.');
    }
}

// Fetch current assignments
$assignments = $pdo->query("
    SELECT ca.assignment_id, c.course_code, c.course_name, u.full_name as lecturer_name, l.department
    FROM course_assignments ca
    JOIN courses c ON ca.course_id = c.course_id
    JOIN lecturers l ON ca.lecturer_id = l.lecturer_id
    JOIN users u ON l.user_id = u.id
    ORDER BY c.course_code, u.full_name
")->fetchAll();

// Fetch all courses and lecturers for the assign form
$courses   = $pdo->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code")->fetchAll();
$lecturers = $pdo->query("SELECT l.lecturer_id, u.full_name, l.department FROM lecturers l JOIN users u ON l.user_id = u.id ORDER BY u.full_name")->fetchAll();

$pageTitle   = 'Course Assignments';
$currentPage = 'assignments';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Course Assignments']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Course-Lecturer Assignments</h1><p>Assign courses to lecturers to control access.</p></div>
</div>

<?php displayFlashMessage(); ?>

<!-- Assign Form -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>New Assignment</h6></div>
    <div class="card-body">
        <form method="POST" class="row g-3 align-items-end">
            <input type="hidden" name="action" value="assign">
            <div class="col-md-4">
                <label class="form-label">Course</label>
                <select class="form-select" name="course_id" required>
                    <option value="">Select course...</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['course_id']; ?>"><?php echo e($c['course_code'] . ' — ' . $c['course_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Lecturer</label>
                <select class="form-select" name="lecturer_id" required>
                    <option value="">Select lecturer...</option>
                    <?php foreach ($lecturers as $l): ?>
                        <option value="<?php echo $l['lecturer_id']; ?>"><?php echo e($l['full_name'] . ' (' . ($l['department'] ?? 'No dept') . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-link me-1"></i> Assign</button>
            </div>
        </form>
    </div>
</div>

<!-- Current Assignments -->
<div class="card table-card">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-link me-2"></i>Current Assignments</h6></div>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead><tr><th>Course</th><th>Lecturer</th><th>Department</th><th class="text-end">Action</th></tr></thead>
            <tbody>
                <?php if (count($assignments) > 0): ?>
                    <?php foreach ($assignments as $a): ?>
                    <tr>
                        <td><span class="fw-600"><?php echo e($a['course_name']); ?></span><br><small class="text-muted"><code><?php echo e($a['course_code']); ?></code></small></td>
                        <td class="fw-600"><?php echo e($a['lecturer_name']); ?></td>
                        <td><span class="badge bg-info"><?php echo e($a['department'] ?? '—'); ?></span></td>
                        <td class="text-end">
                            <form method="POST" class="d-inline" onsubmit="return confirm('Remove this assignment?');">
                                <input type="hidden" name="action" value="unassign">
                                <input type="hidden" name="assignment_id" value="<?php echo $a['assignment_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-unlink"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center py-5">
                        <i class="fas fa-link fa-3x mb-3 d-block" style="opacity:0.2;"></i>
                        <p class="text-muted">No assignments yet. Use the form above to assign courses to lecturers.</p>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
