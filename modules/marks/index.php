<?php
/**
 * Lecturer Marks Index Page
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
    SELECT c.course_id, c.course_code, c.course_name, c.credits 
    FROM courses c
    JOIN course_assignments ca ON c.course_id = ca.course_id
    WHERE ca.lecturer_id = ?
    ORDER BY c.course_code ASC
");
$coursesStmt->execute([$lecturerId]);
$courses = $coursesStmt->fetchAll();

$selectedCourseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$enrolledStudents = [];
$selectedCourse = null;

if ($selectedCourseId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
    $stmt->execute([$selectedCourseId]);
    $selectedCourse = $stmt->fetch();

    if ($selectedCourse) {
        $stmt = $pdo->prepare("
            SELECT s.student_id, s.registration_no, u.full_name,
                   fr.total_score, fr.grade, fr.gpa_points
            FROM enrollments e
            INNER JOIN students s ON e.student_id = s.student_id
            INNER JOIN users u ON s.user_id = u.id
            LEFT JOIN final_results fr ON s.student_id = fr.student_id AND fr.course_id = ?
            WHERE e.course_id = ?
            ORDER BY s.registration_no ASC
        ");
        $stmt->execute([$selectedCourseId, $selectedCourseId]);
        $enrolledStudents = $stmt->fetchAll();
    }
}

$pageTitle   = 'Manage Marks';
$currentPage = 'marks';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/lecturer_dashboard.php'],
    ['label' => 'Manage Marks']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Manage Marks</h1><p>Select a course and enter marks for enrolled students.</p></div>
</div>

<?php displayFlashMessage(); ?>

<!-- Course Selector -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label small mb-1">Select Course</label>
                <select class="form-select" name="course_id" required>
                    <option value="">-- Choose a course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['course_id']; ?>" <?php echo ($selectedCourseId == $c['course_id']) ? 'selected' : ''; ?>>
                            <?php echo e($c['course_code']); ?> — <?php echo e($c['course_name']); ?> (<?php echo $c['credits']; ?> credits)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-eye me-1"></i> View Students</button>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<?php if ($selectedCourse): ?>
    <div class="alert alert-info mb-3">
        <i class="fas fa-info-circle me-2"></i>
        <strong><?php echo e($selectedCourse['course_code']); ?></strong> — <?php echo e($selectedCourse['course_name']); ?> |
        Credits: <?php echo $selectedCourse['credits']; ?> |
        Enrolled: <?php echo count($enrolledStudents); ?> student(s)
    </div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead><tr><th>#</th><th>Reg. No</th><th>Student Name</th><th>Total Score</th><th>Grade</th><th>GPA</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (count($enrolledStudents) > 0): ?>
                        <?php $counter = 1; foreach ($enrolledStudents as $st):
                            $gradeClass = $st['grade'] ? 'grade-' . $st['grade'] : 'grade-none';
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><code class="fw-600"><?php echo e($st['registration_no']); ?></code></td>
                            <td class="fw-600"><?php echo e($st['full_name']); ?></td>
                            <td><?php echo $st['total_score'] !== null ? number_format($st['total_score'], 2) . '%' : '<span class="text-muted">—</span>'; ?></td>
                            <td>
                                <?php if ($st['grade']): ?>
                                    <span class="badge <?php echo $gradeClass; ?>"><?php echo e($st['grade']); ?></span>
                                <?php else: ?>
                                    <span class="badge grade-none">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $st['gpa_points'] !== null ? number_format($st['gpa_points'], 2) : '<span class="text-muted">—</span>'; ?></td>
                            <td>
                                <a href="edit.php?student_id=<?php echo $st['student_id']; ?>&course_id=<?php echo $selectedCourseId; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-pen me-1"></i> Marks
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-5">
                            <i class="fas fa-user-graduate fa-3x mb-3 d-block" style="opacity:0.2;"></i>
                            <p class="text-muted">No students enrolled in this course.</p>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($selectedCourseId > 0): ?>
    <div class="card"><div class="card-body text-center py-5">
        <i class="fas fa-exclamation-circle fa-3x mb-3 text-danger" style="opacity:0.4;"></i>
        <p class="text-muted">Course not found.</p>
    </div></div>
<?php else: ?>
    <div class="card"><div class="card-body text-center py-5">
        <i class="fas fa-arrow-up fa-3x mb-3" style="opacity:0.2;"></i>
        <p class="text-muted">Select a course above to view enrolled students and manage marks.</p>
    </div></div>
<?php endif; ?>

<style>
.grade-A { background: #d4edda; color: #155724; }
.grade-B { background: #cce5ff; color: #004085; }
.grade-C { background: #fff3cd; color: #856404; }
.grade-D { background: #ffe0cc; color: #cc5500; }
.grade-F { background: #f8d7da; color: #721c24; }
.grade-none { background: #e9ecef; color: #6c757d; }
</style>

<?php require_once '../../includes/footer.php'; ?>
