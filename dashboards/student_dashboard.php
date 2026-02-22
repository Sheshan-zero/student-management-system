<?php
/**
 * Student Dashboard — My Courses, Attendance %, GPA, Results
 */
require_once '../includes/auth.php';
require_once '../includes/helpers.php';
require_once '../config/db.php';
requireRole('student');

$userId = $_SESSION['user_id'];

// Get student record
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$userId]);
$student = $stmt->fetch();
$studentId = $student['student_id'] ?? 0;

// Enrolled courses count
$enrolledCount = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $enrolledCount = $stmt->fetchColumn();
} catch (PDOException $e) {}

// GPA
$overallGPA = 0.00;
$gradedCount = 0;
try {
    $stmt = $pdo->prepare("
        SELECT fr.gpa_points, c.credits
        FROM final_results fr
        INNER JOIN courses c ON fr.course_id = c.course_id
        WHERE fr.student_id = ?
    ");
    $stmt->execute([$studentId]);
    $results = $stmt->fetchAll();
    $totalGP = 0; $totalCr = 0;
    foreach ($results as $r) {
        $totalGP += $r['gpa_points'] * $r['credits'];
        $totalCr += $r['credits'];
        $gradedCount++;
    }
    if ($totalCr > 0) $overallGPA = round($totalGP / $totalCr, 2);
} catch (PDOException $e) {}

// Attendance percentage
$attPercent = 0;
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count
        FROM attendance_records WHERE student_id = ?
    ");
    $stmt->execute([$studentId]);
    $att = $stmt->fetch();
    if ($att && $att['total'] > 0) {
        $attPercent = round(($att['present_count'] / $att['total']) * 100);
    }
} catch (PDOException $e) {}

// Enrolled courses list with grades
$enrolledCourses = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.course_code, c.course_name, c.credits, fr.grade, fr.total_score
        FROM enrollments e
        INNER JOIN courses c ON e.course_id = c.course_id
        LEFT JOIN final_results fr ON e.student_id = fr.student_id AND e.course_id = fr.course_id
        WHERE e.student_id = ?
        ORDER BY c.course_code
    ");
    $stmt->execute([$studentId]);
    $enrolledCourses = $stmt->fetchAll();
} catch (PDOException $e) {}

// Fetch target announcements
$announcements = [];
try {
    $stmt = $pdo->query("
        SELECT a.title, a.message, a.created_at, a.is_pinned, u.full_name as author_name 
        FROM announcements a
        LEFT JOIN users u ON a.author_id = u.id
        WHERE a.target_role IN ('all', 'student')
        ORDER BY a.is_pinned DESC, a.created_at DESC
        LIMIT 5
    ");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {}

// Fetch Today's Timetable
$todaysClasses = [];
$todayName = date('l');
try {
    $stmt = $pdo->prepare("
        SELECT t.*, c.course_code, c.course_name, u.full_name as lecturer_name
        FROM timetable t
        JOIN courses c ON t.course_id = c.course_id
        JOIN lecturers l ON t.lecturer_id = l.lecturer_id
        JOIN users u ON l.user_id = u.id
        JOIN enrollments e ON e.course_id = c.course_id
        WHERE e.student_id = ? AND t.day_of_week = ?
        ORDER BY t.start_time
    ");
    $stmt->execute([$studentId, $todayName]);
    $todaysClasses = $stmt->fetchAll();
} catch (PDOException $e) {}


$pageTitle   = 'Student Dashboard';
$currentPage = 'dashboard';
$breadcrumbs = [['label' => 'Dashboard']];
require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>My Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars(getUserName()); ?>! Here's your academic summary.</p>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-light"><i class="fas fa-book-open"></i></div>
            <div class="stat-details">
                <div class="stat-label">Enrolled Courses</div>
                <div class="stat-value"><?php echo $enrolledCount; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-light"><i class="fas fa-chart-line"></i></div>
            <div class="stat-details">
                <div class="stat-label">Overall GPA</div>
                <div class="stat-value"><?php echo number_format($overallGPA, 2); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-light"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-details">
                <div class="stat-label">Attendance</div>
                <div class="stat-value"><?php echo $attPercent; ?>%</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-info-light"><i class="fas fa-award"></i></div>
            <div class="stat-details">
                <div class="stat-label">Graded Courses</div>
                <div class="stat-value"><?php echo $gradedCount; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts + Attendance Progress -->
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h6><i class="fas fa-chart-pie me-2"></i>Grade Distribution</h6></div>
            <div class="chart-container" style="height:280px;">
                <canvas id="gradeChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h6><i class="fas fa-clipboard-check me-2"></i>Attendance</h6></div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div style="font-size:48px;font-weight:800;color:var(--<?php echo $attPercent >= 75 ? 'success' : ($attPercent >= 50 ? 'warning' : 'danger'); ?>);">
                        <?php echo $attPercent; ?>%
                    </div>
                    <div class="text-muted">Overall Attendance Rate</div>
                </div>
                <div class="progress" style="height:12px;">
                    <div class="progress-bar bg-<?php echo $attPercent >= 75 ? 'success' : ($attPercent >= 50 ? 'warning' : 'danger'); ?>"
                         style="width:<?php echo $attPercent; ?>%"></div>
                </div>
                <div class="d-grid mt-3">
                    <a href="../modules/attendance/student_view.php" class="btn btn-outline-primary btn-sm">
                        View Full Attendance <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Announcements -->
<?php if (!empty($announcements)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h6><i class="fas fa-bullhorn me-2"></i>Recent Announcements</h6>
    </div>
    <div class="list-group list-group-flush">
        <?php foreach ($announcements as $ann): ?>
        <div class="list-group-item py-3">
            <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                <h6 class="mb-0">
                    <?php if ($ann['is_pinned']): ?><i class="fas fa-thumbtack text-danger me-1"></i><?php endif; ?>
                    <?php echo e($ann['title']); ?>
                </h6>
                <small class="text-muted"><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></small>
            </div>
            <p class="mb-1 text-muted" style="white-space: pre-wrap;"><?php echo e($ann['message']); ?></p>
            <small class="text-primary"><i class="fas fa-user-circle me-1"></i> <?php echo e($ann['author_name']); ?></small>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Today's Schedule -->
<div class="card mb-4 table-card border-primary" style="border-width:2px;">
    <div class="card-header bg-primary-light">
        <h6 class="mb-0 text-primary"><i class="fas fa-calendar-alt me-2"></i>Today's Schedule (<?php echo $todayName; ?>)</h6>
        <a href="../modules/timetable/index.php" class="btn btn-sm btn-primary">Full Timetable</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <tbody>
                <?php if (empty($todaysClasses)): ?>
                    <tr><td class="text-center text-muted py-4">No classes scheduled for today.</td></tr>
                <?php else: ?>
                    <?php foreach ($todaysClasses as $tc): ?>
                    <tr>
                        <td style="width:180px;"><code><?php echo date('g:i A', strtotime($tc['start_time'])); ?> - <?php echo date('g:i A', strtotime($tc['end_time'])); ?></code></td>
                        <td class="fw-600"><?php echo e($tc['course_code'] . ' - ' . $tc['course_name']); ?></td>
                        <td><i class="fas fa-user-tie text-muted me-1"></i> <?php echo e($tc['lecturer_name']); ?></td>
                        <td><span class="badge bg-secondary"><i class="fas fa-map-marker-alt me-1"></i> <?php echo e($tc['room'] ?: 'TBA'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- My Courses Table -->
<div class="card table-card">
    <div class="card-header">
        <h6><i class="fas fa-book me-2"></i>My Courses & Grades</h6>
        <a href="../modules/marks/student_view.php" class="btn btn-sm btn-light">View Full Results</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Code</th><th>Course Name</th><th>Credits</th><th>Score</th><th>Grade</th></tr>
            </thead>
            <tbody>
                <?php if (empty($enrolledCourses)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Not enrolled in any courses yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($enrolledCourses as $c): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($c['course_code']); ?></code></td>
                        <td class="fw-600"><?php echo htmlspecialchars($c['course_name']); ?></td>
                        <td><?php echo $c['credits']; ?></td>
                        <td><?php echo $c['total_score'] !== null ? number_format($c['total_score'], 1) . '%' : '—'; ?></td>
                        <td>
                            <?php if ($c['grade']): ?>
                                <span class="badge badge-grade-<?php echo strtolower($c['grade']); ?>"><?php echo $c['grade']; ?></span>
                            <?php else: ?>
                                <span class="badge" style="background:#edf2f7;color:#6c757d;">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Grade distribution chart data
$gradeData = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
foreach ($enrolledCourses as $c) {
    if ($c['grade'] && isset($gradeData[$c['grade']])) {
        $gradeData[$c['grade']]++;
    }
}

$extraScripts = '<script>
SMS_CHARTS.doughnutChart("gradeChart", {
    labels: ["A (Excellent)", "B (Good)", "C (Average)", "D (Below Avg)", "F (Fail)"],
    data: [' . implode(',', array_values($gradeData)) . '],
    colors: ["success", "info", "warning", "#fd7e14", "danger"]
});
</script>';
require_once '../includes/footer.php';
?>
