<?php
/**
 * Lecturer Attendance Sessions List
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('lecturer');

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
$stmt->execute([$userId]);
$lecturer = $stmt->fetch();
if (!$lecturer) { die("Lecturer record not found. Please contact administrator."); }
$lecturerId = $lecturer['lecturer_id'];

$stmt = $pdo->prepare("
    SELECT s.session_id, s.session_date, s.created_at,
           c.course_id, c.course_code, c.course_name,
           COUNT(ar.record_id) as marked_count
    FROM attendance_sessions s
    INNER JOIN courses c ON s.course_id = c.course_id
    LEFT JOIN attendance_records ar ON s.session_id = ar.session_id
    WHERE s.lecturer_id = ?
    GROUP BY s.session_id, s.session_date, s.created_at, c.course_id, c.course_code, c.course_name
    ORDER BY s.session_date DESC, s.created_at DESC
");
$stmt->execute([$lecturerId]);
$sessions = $stmt->fetchAll();

$pageTitle   = 'Attendance Sessions';
$currentPage = 'attendance';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/lecturer_dashboard.php'],
    ['label' => 'Attendance Sessions']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Attendance Sessions</h1>
        <p><?php echo count($sessions); ?> sessions</p>
    </div>
    <a href="create_session.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Session</a>
</div>

<?php displayFlashMessage(); ?>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr><th>Course</th><th>Session Date</th><th>Students Marked</th><th>Created</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (count($sessions) > 0): ?>
                    <?php foreach ($sessions as $s): ?>
                    <tr>
                        <td>
                            <div class="fw-600"><?php echo e($s['course_name']); ?></div>
                            <small class="text-muted"><code><?php echo e($s['course_code']); ?></code></small>
                        </td>
                        <td><i class="fas fa-calendar-day text-muted me-1"></i> <?php echo date('M d, Y', strtotime($s['session_date'])); ?></td>
                        <td><span class="badge bg-primary"><?php echo $s['marked_count']; ?></span></td>
                        <td><small class="text-muted"><?php echo date('M d, Y H:i', strtotime($s['created_at'])); ?></small></td>
                        <td>
                            <a href="mark.php?session_id=<?php echo $s['session_id']; ?>" class="btn btn-sm btn-primary" title="Mark/Edit">
                                <i class="fas fa-clipboard-check"></i> Mark
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-calendar-alt fa-3x mb-3 d-block" style="opacity:0.2;"></i>
                            <p>No attendance sessions yet. Click <strong>New Session</strong> to begin.</p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
