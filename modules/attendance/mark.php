<?php
/**
 * Mark Attendance
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

$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if ($sessionId <= 0) { redirectWithMessage('index.php', 'error', 'Invalid session ID'); }
if (!verifySessionOwnership($pdo, $sessionId, $lecturerId)) { redirectWithMessage('index.php', 'error', 'Unauthorized access'); }

$stmt = $pdo->prepare("SELECT s.session_id, s.session_date, c.course_code, c.course_name FROM attendance_sessions s INNER JOIN courses c ON s.course_id = c.course_id WHERE s.session_id = ?");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();
if (!$session) { redirectWithMessage('index.php', 'error', 'Session not found'); }

$students = getEnrolledStudents($pdo, $sessionId);

$stmt = $pdo->prepare("SELECT student_id, status FROM attendance_records WHERE session_id = ?");
$stmt->execute([$sessionId]);
$existingRecords = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO attendance_records (session_id, student_id, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status)");
        $markedCount = 0;
        foreach ($students as $student) {
            $sid = $student['student_id'];
            $status = isset($_POST['attendance'][$sid]) ? $_POST['attendance'][$sid] : 'Absent';
            if (!in_array($status, ['Present', 'Absent'])) $status = 'Absent';
            $stmt->execute([$sessionId, $sid, $status]);
            $markedCount++;
        }
        $pdo->commit();
        redirectWithMessage('index.php', 'success', "Attendance saved for {$markedCount} student(s)!");
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Mark attendance error: " . $e->getMessage());
        $error = 'Error saving attendance. Please try again.';
    }
}

$pageTitle   = 'Mark Attendance';
$currentPage = 'attendance';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/lecturer_dashboard.php'],
    ['label' => 'Attendance', 'url' => 'index.php'],
    ['label' => 'Mark Attendance']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?php echo e($session['course_code'] . ' — ' . $session['course_name']); ?></h1>
        <p><i class="fas fa-calendar-day me-1"></i> <?php echo date('F d, Y', strtotime($session['session_date'])); ?> · <?php echo count($students); ?> students</p>
    </div>
    <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo e($error); ?></div>
<?php endif; ?>

<?php displayFlashMessage(); ?>

<div class="card">
    <div class="card-body p-4">
        <?php if (count($students) > 0): ?>
            <div class="d-flex gap-2 mb-3">
                <button type="button" class="btn btn-sm btn-success" onclick="markAllPresent()"><i class="fas fa-check-double me-1"></i> All Present</button>
                <button type="button" class="btn btn-sm btn-danger" onclick="markAllAbsent()"><i class="fas fa-times me-1"></i> All Absent</button>
            </div>
            <form method="POST" action="">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead><tr><th>Reg. No</th><th>Student Name</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($students as $student):
                                $sid = $student['student_id'];
                                $currentStatus = $existingRecords[$sid] ?? 'Present';
                            ?>
                            <tr>
                                <td><code class="fw-600"><?php echo e($student['registration_no']); ?></code></td>
                                <td class="fw-600"><?php echo e($student['full_name']); ?></td>
                                <td>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="present_<?php echo $sid; ?>" name="attendance[<?php echo $sid; ?>]" value="Present" <?php echo $currentStatus == 'Present' ? 'checked' : ''; ?>>
                                            <label class="form-check-label text-success fw-600" for="present_<?php echo $sid; ?>">Present</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="absent_<?php echo $sid; ?>" name="attendance[<?php echo $sid; ?>]" value="Absent" <?php echo $currentStatus == 'Absent' ? 'checked' : ''; ?>>
                                            <label class="form-check-label text-danger fw-600" for="absent_<?php echo $sid; ?>">Absent</label>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex gap-2 pt-3 mt-3 border-top">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Attendance</button>
                    <a href="index.php" class="btn btn-light">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-user-graduate fa-3x mb-3" style="opacity:0.2;"></i>
                <p class="text-muted">No students enrolled in this course yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function markAllPresent() { document.querySelectorAll('input[type="radio"][value="Present"]').forEach(r => r.checked = true); }
function markAllAbsent()  { document.querySelectorAll('input[type="radio"][value="Absent"]').forEach(r => r.checked = true); }
</script>

<?php require_once '../../includes/footer.php'; ?>
