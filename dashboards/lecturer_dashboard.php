<?php
/**
 * Lecturer Dashboard — My Courses, Attendance, Marks stats
 */
require_once '../includes/auth.php';
require_once '../includes/helpers.php';
require_once '../config/db.php';
requireRole('lecturer');

// Stats (counts across all courses since no lecturer_id on courses)
try {
    $totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $attSessions = $pdo->query("SELECT COUNT(*) FROM attendance_sessions")->fetchColumn();
} catch (PDOException $e) {
    $totalCourses = $totalStudents = $attSessions = 0;
}

// Recent attendance sessions
try {
    $stmt = $pdo->query("
        SELECT s.session_id, c.course_code, c.course_name, s.session_date, s.session_type
        FROM attendance_sessions s
        INNER JOIN courses c ON s.course_id = c.course_id
        ORDER BY s.session_date DESC LIMIT 5
    ");
    $recentSessions = $stmt->fetchAll();
} catch (PDOException $e) {
    $recentSessions = [];
}

// Fetch target announcements
$announcements = [];
try {
    $stmt = $pdo->query("
        SELECT a.title, a.message, a.created_at, a.is_pinned, u.full_name as author_name 
        FROM announcements a
        LEFT JOIN users u ON a.author_id = u.id
        WHERE a.target_role IN ('all', 'lecturer')
        ORDER BY a.is_pinned DESC, a.created_at DESC
        LIMIT 5
    ");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {}

// Fetch Attendance Trend Data for Lecturer
$trendLabels = [];
$presentData = [];
$absentData = [];

try {
    $lectIdStmt = $pdo->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
    $lectIdStmt->execute([$_SESSION['user_id']]);
    $lect = $lectIdStmt->fetch();
    $lectId = $lect ? $lect['lecturer_id'] : 0;

    $trendStmt = $pdo->prepare("
        SELECT 
            s.session_date, 
            c.course_code,
            SUM(CASE WHEN r.status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN r.status = 'absent' THEN 1 ELSE 0 END) as absent_count
        FROM attendance_sessions s
        JOIN courses c ON s.course_id = c.course_id
        JOIN course_assignments ca ON c.course_id = ca.course_id
        LEFT JOIN attendance_records r ON s.session_id = r.session_id
        WHERE ca.lecturer_id = ?
        GROUP BY s.session_id
        ORDER BY s.session_date DESC
        LIMIT 6
    ");
    $trendStmt->execute([$lectId]);
    $sessionsData = array_reverse($trendStmt->fetchAll());
    
    foreach ($sessionsData as $sItem) {
        $trendLabels[] = '"' . date('M d', strtotime($sItem['session_date'])) . ' ' . $sItem['course_code'] . '"';
        $presentData[] = (int)$sItem['present_count'];
        $absentData[] = (int)$sItem['absent_count'];
    }
} catch (PDOException $e) {}

if (empty($trendLabels)) {
    $trendLabelsStr = '"No Data"';
    $presentDataStr = '0';
    $absentDataStr = '0';
} else {
    $trendLabelsStr = implode(', ', $trendLabels);
    $presentDataStr = implode(', ', $presentData);
    $absentDataStr = implode(', ', $absentData);
}

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
        WHERE l.user_id = ? AND t.day_of_week = ?
        ORDER BY t.start_time
    ");
    $stmt->execute([$_SESSION['user_id'], $todayName]);
    $todaysClasses = $stmt->fetchAll();
} catch (PDOException $e) {}


$pageTitle   = 'Lecturer Dashboard';
$currentPage = 'dashboard';
$breadcrumbs = [['label' => 'Dashboard']];
require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars(getUserName()); ?>! Manage attendance and marks.</p>
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

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-light"><i class="fas fa-book-open"></i></div>
            <div class="stat-details">
                <div class="stat-label">Courses</div>
                <div class="stat-value"><?php echo $totalCourses; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-light"><i class="fas fa-user-graduate"></i></div>
            <div class="stat-details">
                <div class="stat-label">Students</div>
                <div class="stat-value"><?php echo $totalStudents; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-light"><i class="fas fa-clipboard-check"></i></div>
            <div class="stat-details">
                <div class="stat-label">Att. Sessions</div>
                <div class="stat-value"><?php echo $attSessions; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-secondary-light"><i class="fas fa-pen-alt"></i></div>
            <div class="stat-details">
                <div class="stat-label">Marks Entry</div>
                <div class="stat-value">—</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts + Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-chart-bar me-2"></i>Attendance Overview</h6>
            </div>
            <div class="chart-container" style="height:280px;">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <!-- Today's Schedule -->
        <div class="card mb-4 border-warning" style="border-width:2px;">
            <div class="card-header" style="background:#fff5e6;">
                <h6 class="mb-0 text-warning" style="color:#cc7a00!important;"><i class="fas fa-calendar-day me-2"></i>Today's Classes</h6>
            </div>
            <div class="list-group list-group-flush">
                <?php if (empty($todaysClasses)): ?>
                    <div class="list-group-item text-center text-muted py-3">No classes today.</div>
                <?php else: ?>
                    <?php foreach ($todaysClasses as $tc): ?>
                    <div class="list-group-item py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong><?php echo e($tc['course_code']); ?></strong>
                            <code><?php echo date('g:i A', strtotime($tc['start_time'])); ?> - <?php echo date('g:i A', strtotime($tc['end_time'])); ?></code>
                        </div>
                        <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?php echo e($tc['room'] ?: 'TBA'); ?></small>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="card-footer bg-white border-top-0 pt-0">
                    <a href="../modules/timetable/index.php" class="btn btn-sm btn-outline-secondary w-100">Full Timetable</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6><i class="fas fa-bolt me-2"></i>Quick Actions</h6></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="../modules/attendance/index.php" class="btn btn-outline-primary text-start">
                        <i class="fas fa-clipboard-check me-2"></i> Manage Attendance
                    </a>
                    <a href="../modules/marks/index.php" class="btn btn-outline-primary text-start">
                        <i class="fas fa-pen-alt me-2"></i> Enter Marks
                    </a>
                    <a href="../modules/attendance/create_session.php" class="btn btn-outline-primary text-start">
                        <i class="fas fa-plus me-2"></i> New Attendance Session
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Sessions Table -->
<div class="card table-card">
    <div class="card-header">
        <h6><i class="fas fa-history me-2"></i>Recent Attendance Sessions</h6>
        <a href="../modules/attendance/index.php" class="btn btn-sm btn-light">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Course</th><th>Date</th><th>Type</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (empty($recentSessions)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No sessions yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentSessions as $sess): ?>
                    <tr>
                        <td><span class="fw-600"><?php echo htmlspecialchars($sess['course_code']); ?></span> — <?php echo htmlspecialchars($sess['course_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($sess['session_date'])); ?></td>
                        <td><span class="badge badge-active"><?php echo htmlspecialchars($sess['session_type']); ?></span></td>
                        <td>
                            <a href="../modules/attendance/mark.php?session_id=<?php echo $sess['session_id']; ?>" class="btn btn-sm btn-light" title="Mark"><i class="fas fa-check-double"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extraScripts = '<script>
SMS_CHARTS.barChart("attendanceChart", {
    labels: [' . $trendLabelsStr . '],
    datasets: [
        { label: "Present", data: [' . $presentDataStr . '], color: "success" },
        { label: "Absent",  data: [' . $absentDataStr . '], color: "danger" }
    ]
});
</script>';
require_once '../includes/footer.php';
?>
