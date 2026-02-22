<?php
/**
 * Student Results View Page
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('student');

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$userId]);
$student = $stmt->fetch();
if (!$student) { die("Student record not found."); }
$studentId = $student['student_id'];

$stmt = $pdo->prepare("
    SELECT c.course_id, c.course_code, c.course_name, c.credits,
           fr.total_score, fr.grade, fr.gpa_points
    FROM enrollments e
    INNER JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN final_results fr ON e.student_id = fr.student_id AND e.course_id = fr.course_id
    WHERE e.student_id = ?
    ORDER BY c.course_code ASC
");
$stmt->execute([$studentId]);
$enrolledCourses = $stmt->fetchAll();

$totalGPAPoints = $totalCredits = $gradedCourseCount = 0;
foreach ($enrolledCourses as $c) {
    if ($c['grade'] !== null) {
        $totalGPAPoints += $c['gpa_points'] * $c['credits'];
        $totalCredits += $c['credits'];
        $gradedCourseCount++;
    }
}
$overallGPA = ($totalCredits > 0) ? round($totalGPAPoints / $totalCredits, 2) : 0.00;

$stmt = $pdo->prepare("SELECT course_id, component, score, weight FROM marks WHERE student_id = ? ORDER BY course_id, mark_id ASC");
$stmt->execute([$studentId]);
$allMarks = $stmt->fetchAll();
$marksByCourse = [];
foreach ($allMarks as $mark) { $marksByCourse[$mark['course_id']][] = $mark; }

$pageTitle   = 'My Results';
$currentPage = 'results';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/student_dashboard.php'],
    ['label' => 'My Results']
];
require_once '../../includes/header.php';
?>

<style>
.grade-A { background: #d4edda; color: #155724; }
.grade-B { background: #cce5ff; color: #004085; }
.grade-C { background: #fff3cd; color: #856404; }
.grade-D { background: #ffe0cc; color: #cc5500; }
.grade-F { background: #f8d7da; color: #721c24; }
.grade-none { background: #e9ecef; color: #6c757d; }
</style>

<?php if (count($enrolledCourses) > 0): ?>

<div class="d-flex justify-content-end mb-3">
    <a href="../reports/transcript_pdf.php?student_id=<?php echo $studentId; ?>" target="_blank" class="btn btn-primary">
        <i class="fas fa-file-pdf me-2"></i> Download Transcript (PDF)
    </a>
</div>

<!-- GPA Summary -->
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card h-100" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;">
            <div class="card-body text-center py-4">
                <h6 class="opacity-75 mb-1">Overall GPA</h6>
                <div class="display-4 fw-bold"><?php echo number_format($overallGPA, 2); ?></div>
                <small class="opacity-75">on a 4.0 scale</small>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="row g-3 h-100">
            <div class="col-4">
                <div class="card h-100"><div class="card-body text-center py-3">
                    <small class="text-muted d-block">Enrolled</small><div class="fs-3 fw-bold"><?php echo count($enrolledCourses); ?></div>
                </div></div>
            </div>
            <div class="col-4">
                <div class="card h-100"><div class="card-body text-center py-3">
                    <small class="text-muted d-block">Graded</small><div class="fs-3 fw-bold"><?php echo $gradedCourseCount; ?></div>
                </div></div>
            </div>
            <div class="col-4">
                <div class="card h-100"><div class="card-body text-center py-3">
                    <small class="text-muted d-block">Credits</small><div class="fs-3 fw-bold"><?php echo $totalCredits; ?></div>
                </div></div>
            </div>
        </div>
    </div>
</div>

<!-- Course Results -->
<?php foreach ($enrolledCourses as $course):
    $courseMarks = $marksByCourse[$course['course_id']] ?? [];
    $gradeClass = $course['grade'] ? 'grade-' . $course['grade'] : 'grade-none';
    $gradeText  = $course['grade'] ?? 'N/A';
?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <div>
            <h6 class="mb-0 fw-600"><?php echo e($course['course_name']); ?></h6>
            <small class="text-muted"><?php echo e($course['course_code']); ?> | <?php echo $course['credits']; ?> credits</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($course['total_score'] !== null): ?>
                <span class="text-muted fw-600"><?php echo number_format($course['total_score'], 2); ?>%</span>
            <?php endif; ?>
            <span class="badge <?php echo $gradeClass; ?>" style="font-size:1rem;padding:6px 14px;"><?php echo $gradeText; ?></span>
        </div>
    </div>
    <?php if (!empty($courseMarks)): ?>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Component</th><th>Score</th><th>Weight</th><th>Weighted</th></tr></thead>
            <tbody>
                <?php $tw = $twt = 0; foreach ($courseMarks as $m):
                    $weighted = $m['score'] * $m['weight'] / 100;
                    $tw += $m['weight']; $twt += $weighted;
                ?>
                <tr>
                    <td><?php echo e($m['component']); ?></td>
                    <td><?php echo number_format($m['score'], 2); ?></td>
                    <td><?php echo number_format($m['weight'], 2); ?>%</td>
                    <td><?php echo number_format($weighted, 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="table-light fw-bold">
                    <td>Total</td><td></td>
                    <td><?php echo number_format($tw, 2); ?>%</td>
                    <td><?php echo number_format($twt, 2); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="card-body text-center py-3"><small class="text-muted">Marks not entered yet.</small></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php else: ?>
    <div class="card"><div class="card-body text-center py-5">
        <i class="fas fa-graduation-cap fa-3x mb-3" style="opacity:0.2;"></i>
        <p class="text-muted">Not enrolled in any courses. Contact your administrator.</p>
    </div></div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
