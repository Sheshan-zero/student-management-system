<?php
/**
 * Timetable — Admin manages, Students/Lecturers view
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
$role = $_SESSION['role'];

// Handle add/delete (admin only)
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $courseId    = (int)$_POST['course_id'];
        $lecturerId = (int)$_POST['lecturer_id'];
        $day        = $_POST['day_of_week'];
        $start      = $_POST['start_time'];
        $end        = $_POST['end_time'];
        $room       = trim($_POST['room'] ?? '');
        $pdo->prepare("INSERT INTO timetable (course_id, lecturer_id, day_of_week, start_time, end_time, room) VALUES (?,?,?,?,?,?)")
            ->execute([$courseId, $lecturerId, $day, $start, $end, $room]);
        redirectWithMessage('index.php', 'success', 'Timetable slot added.');
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $pdo->prepare("DELETE FROM timetable WHERE slot_id = ?")->execute([(int)$_POST['slot_id']]);
        redirectWithMessage('index.php', 'success', 'Slot removed.');
    }
}

// Fetch timetable based on role
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
if ($role === 'student') {
    $stmt = $pdo->prepare("
        SELECT t.*, c.course_code, c.course_name, u.full_name as lecturer_name
        FROM timetable t
        JOIN courses c ON t.course_id = c.course_id
        JOIN lecturers l ON t.lecturer_id = l.lecturer_id
        JOIN users u ON l.user_id = u.id
        JOIN enrollments e ON e.course_id = c.course_id
        JOIN students s ON s.student_id = e.student_id AND s.user_id = ?
        ORDER BY FIELD(t.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time
    ");
    $stmt->execute([$_SESSION['user_id']]);
} elseif ($role === 'lecturer') {
    $stmt = $pdo->prepare("
        SELECT t.*, c.course_code, c.course_name, u.full_name as lecturer_name
        FROM timetable t
        JOIN courses c ON t.course_id = c.course_id
        JOIN lecturers l ON t.lecturer_id = l.lecturer_id
        JOIN users u ON l.user_id = u.id
        WHERE l.user_id = ?
        ORDER BY FIELD(t.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time
    ");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->query("
        SELECT t.*, c.course_code, c.course_name, u.full_name as lecturer_name
        FROM timetable t
        JOIN courses c ON t.course_id = c.course_id
        JOIN lecturers l ON t.lecturer_id = l.lecturer_id
        JOIN users u ON l.user_id = u.id
        ORDER BY FIELD(t.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time
    ");
}
$slots = $stmt->fetchAll();

// Group by day
$byDay = [];
foreach ($days as $d) $byDay[$d] = [];
foreach ($slots as $s) { $byDay[$s['day_of_week']][] = $s; }

// For admin form
$courses = $lecturers = [];
if ($role === 'admin') {
    $courses   = $pdo->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code")->fetchAll();
    $lecturers = $pdo->query("SELECT l.lecturer_id, u.full_name FROM lecturers l JOIN users u ON l.user_id = u.id ORDER BY u.full_name")->fetchAll();
}

$dashUrl = $role === 'admin' ? '../../dashboards/admin_dashboard.php' : ($role === 'lecturer' ? '../../dashboards/lecturer_dashboard.php' : '../../dashboards/student_dashboard.php');
$pageTitle   = 'Timetable';
$currentPage = 'timetable';
$breadcrumbs = [['label' => 'Dashboard', 'url' => $dashUrl], ['label' => 'Timetable']];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Weekly Timetable</h1><p>View class schedule for the week.</p></div>
</div>

<?php displayFlashMessage(); ?>

<?php if ($role === 'admin'): ?>
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add Slot</h6></div>
    <div class="card-body">
        <form method="POST" class="row g-3 align-items-end">
            <input type="hidden" name="action" value="add">
            <div class="col-md-3">
                <label class="form-label">Course</label>
                <select class="form-select" name="course_id" required>
                    <option value="">Select...</option>
                    <?php foreach ($courses as $c): ?><option value="<?php echo $c['course_id']; ?>"><?php echo e($c['course_code']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Lecturer</label>
                <select class="form-select" name="lecturer_id" required>
                    <option value="">Select...</option>
                    <?php foreach ($lecturers as $l): ?><option value="<?php echo $l['lecturer_id']; ?>"><?php echo e($l['full_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Day</label>
                <select class="form-select" name="day_of_week" required>
                    <?php foreach ($days as $d): ?><option><?php echo $d; ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">Start</label>
                <input type="time" class="form-control" name="start_time" required>
            </div>
            <div class="col-md-1">
                <label class="form-label">End</label>
                <input type="time" class="form-control" name="end_time" required>
            </div>
            <div class="col-md-1">
                <label class="form-label">Room</label>
                <input type="text" class="form-control" name="room" placeholder="A101">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i> Add</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Timetable Grid -->
<?php foreach ($days as $day): ?>
<?php if (!empty($byDay[$day])): ?>
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-calendar-day me-2"></i><?php echo $day; ?></h6></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Time</th><th>Course</th><th>Lecturer</th><th>Room</th><?php if ($role === 'admin'): ?><th class="text-end">Action</th><?php endif; ?></tr></thead>
            <tbody>
                <?php foreach ($byDay[$day] as $s): ?>
                <tr>
                    <td><code><?php echo date('g:i A', strtotime($s['start_time'])); ?> – <?php echo date('g:i A', strtotime($s['end_time'])); ?></code></td>
                    <td class="fw-600"><?php echo e($s['course_code'] . ' — ' . $s['course_name']); ?></td>
                    <td><?php echo e($s['lecturer_name']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo e($s['room'] ?: '—'); ?></span></td>
                    <?php if ($role === 'admin'): ?>
                    <td class="text-end">
                        <form method="POST" class="d-inline" onsubmit="return confirm('Remove?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="slot_id" value="<?php echo $s['slot_id']; ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<?php if (empty($slots)): ?>
<div class="card"><div class="card-body text-center py-5">
    <i class="fas fa-calendar-alt fa-3x mb-3 d-block" style="opacity:0.2;"></i>
    <p class="text-muted">No timetable slots found.</p>
</div></div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
