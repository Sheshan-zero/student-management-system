<?php
/**
 * Attendance Reports — Admin
 * Shows attendance overview across all courses
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

// Overall stats
$totalSessions = $pdo->query("SELECT COUNT(*) FROM attendance_sessions")->fetchColumn();
$totalRecords  = $pdo->query("SELECT COUNT(*) FROM attendance_records")->fetchColumn();
$totalPresent  = $pdo->query("SELECT COUNT(*) FROM attendance_records WHERE status='Present'")->fetchColumn();
$totalAbsent   = $pdo->query("SELECT COUNT(*) FROM attendance_records WHERE status='Absent'")->fetchColumn();
$overallRate   = $totalRecords > 0 ? round(($totalPresent / $totalRecords) * 100, 1) : 0;

// Per-course breakdown
$courseStats = $pdo->query("
    SELECT c.course_code, c.course_name,
           COUNT(DISTINCT s.session_id) as sessions,
           SUM(CASE WHEN ar.status = 'Present' THEN 1 ELSE 0 END) as present_count,
           SUM(CASE WHEN ar.status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
           COUNT(ar.record_id) as total_records
    FROM courses c
    LEFT JOIN attendance_sessions s ON c.course_id = s.course_id
    LEFT JOIN attendance_records ar ON s.session_id = ar.session_id
    GROUP BY c.course_id, c.course_code, c.course_name
    HAVING sessions > 0
    ORDER BY c.course_code
")->fetchAll();

$pageTitle   = 'Attendance Reports';
$currentPage = 'att_reports';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Attendance Reports']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Attendance Reports</h1><p>Overview of attendance across all courses.</p></div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card bg-primary text-white">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-number"><?php echo $totalSessions; ?></div>
            <div class="stat-label">Total Sessions</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card bg-success text-white">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number"><?php echo $totalPresent; ?></div>
            <div class="stat-label">Present Records</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card bg-danger text-white">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-number"><?php echo $totalAbsent; ?></div>
            <div class="stat-label">Absent Records</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card bg-info text-white">
            <div class="stat-icon"><i class="fas fa-chart-pie"></i></div>
            <div class="stat-number"><?php echo $overallRate; ?>%</div>
            <div class="stat-label">Overall Attendance</div>
        </div>
    </div>
</div>

<!-- Per-course Table -->
<div class="card table-card">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Attendance by Course</h6></div>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead><tr><th>Course</th><th>Sessions</th><th>Present</th><th>Absent</th><th>Rate</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (count($courseStats) > 0): ?>
                    <?php foreach ($courseStats as $cs):
                        $rate = $cs['total_records'] > 0 ? round(($cs['present_count'] / $cs['total_records']) * 100, 1) : 0;
                        if ($rate >= 90) { $badge = 'bg-success'; $status = 'Excellent'; }
                        elseif ($rate >= 75) { $badge = 'bg-info'; $status = 'Good'; }
                        elseif ($rate >= 60) { $badge = 'bg-warning text-dark'; $status = 'Fair'; }
                        else { $badge = 'bg-danger'; $status = 'Poor'; }
                    ?>
                    <tr>
                        <td><div class="fw-600"><?php echo e($cs['course_name']); ?></div><small class="text-muted"><code><?php echo e($cs['course_code']); ?></code></small></td>
                        <td><?php echo $cs['sessions']; ?></td>
                        <td class="text-success fw-600"><?php echo $cs['present_count']; ?></td>
                        <td class="text-danger fw-600"><?php echo $cs['absent_count']; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px;"><div class="progress-bar bg-primary" style="width:<?php echo $rate; ?>%;"></div></div>
                                <small class="fw-600"><?php echo $rate; ?>%</small>
                            </div>
                        </td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo $status; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-5"><i class="fas fa-calendar-alt fa-3x mb-3 d-block" style="opacity:0.2;"></i><p class="text-muted">No attendance data recorded yet.</p></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
