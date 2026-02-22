<?php
/**
 * Student Attendance View
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('student');

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt->execute([$userId]);
$student = $stmt->fetch();
if (!$student) { die("Student record not found."); }
$studentId = $student['student_id'];

$stmt = $pdo->prepare("
    SELECT c.course_code, c.course_name,
           COUNT(DISTINCT s.session_id) as total_sessions,
           SUM(CASE WHEN ar.status = 'Present' THEN 1 ELSE 0 END) as present_count,
           SUM(CASE WHEN ar.status = 'Absent' THEN 1 ELSE 0 END) as absent_count
    FROM enrollments e
    INNER JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN attendance_sessions s ON c.course_id = s.course_id
    LEFT JOIN attendance_records ar ON s.session_id = ar.session_id AND ar.student_id = e.student_id
    WHERE e.student_id = ?
    GROUP BY c.course_id, c.course_code, c.course_name
    HAVING total_sessions > 0
    ORDER BY c.course_code ASC
");
$stmt->execute([$studentId]);
$attendanceData = $stmt->fetchAll();

$totalSessions = $totalPresent = $totalAbsent = 0;
foreach ($attendanceData as $row) {
    $totalSessions += $row['total_sessions'];
    $totalPresent  += $row['present_count'];
    $totalAbsent   += $row['absent_count'];
}
$overallPercentage = $totalSessions > 0 ? round(($totalPresent / $totalSessions) * 100, 2) : 0;

$pageTitle   = 'My Attendance';
$currentPage = 'attendance';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/student_dashboard.php'],
    ['label' => 'My Attendance']
];
require_once '../../includes/header.php';
?>

<?php if (count($attendanceData) > 0): ?>

<!-- Overall Summary -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card bg-primary text-white">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-number"><?php echo $totalSessions; ?></div>
            <div class="stat-label">Total Sessions</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card bg-success text-white">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number"><?php echo $totalPresent; ?></div>
            <div class="stat-label">Present</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card bg-danger text-white">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-number"><?php echo $totalAbsent; ?></div>
            <div class="stat-label">Absent</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card bg-info text-white">
            <div class="stat-icon"><i class="fas fa-chart-pie"></i></div>
            <div class="stat-number"><?php echo $overallPercentage; ?>%</div>
            <div class="stat-label">Attendance Rate</div>
        </div>
    </div>
</div>

<!-- Per-course attendance -->
<div class="row g-3">
    <?php foreach ($attendanceData as $data):
        $percentage = $data['total_sessions'] > 0 ? round(($data['present_count'] / $data['total_sessions']) * 100, 2) : 0;
        if ($percentage >= 90) { $badgeClass = 'bg-success'; $badgeText = 'Excellent'; }
        elseif ($percentage >= 75) { $badgeClass = 'bg-info'; $badgeText = 'Good'; }
        elseif ($percentage >= 60) { $badgeClass = 'bg-warning text-dark'; $badgeText = 'Fair'; }
        else { $badgeClass = 'bg-danger'; $badgeText = 'Poor'; }
    ?>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="mb-0 fw-600"><?php echo e($data['course_code']); ?></h6>
                        <small class="text-muted"><?php echo e($data['course_name']); ?></small>
                    </div>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                </div>
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-success"><i class="fas fa-check me-1"></i>Present: <?php echo $data['present_count']; ?></span>
                    <span class="text-danger"><i class="fas fa-times me-1"></i>Absent: <?php echo $data['absent_count']; ?></span>
                    <span class="text-muted">Sessions: <?php echo $data['total_sessions']; ?></span>
                </div>
                <div class="progress" style="height:10px;">
                    <div class="progress-bar bg-primary" style="width:<?php echo $percentage; ?>%;"></div>
                </div>
                <div class="text-center mt-1"><small class="fw-600"><?php echo $percentage; ?>%</small></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
    <div class="card"><div class="card-body text-center py-5">
        <i class="fas fa-clipboard-list fa-3x mb-3" style="opacity:0.2;"></i>
        <p class="text-muted">No attendance records yet. Your attendance will appear here once lecturers start marking.</p>
    </div></div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
