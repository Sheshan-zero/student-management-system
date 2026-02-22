<?php
/**
 * Enter/Edit Marks Page
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('lecturer');

$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$courseId  = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
if ($studentId <= 0 || $courseId <= 0) { redirectWithMessage('index.php', 'error', 'Invalid student or course ID.'); }

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
$stmt->execute([$userId]);
$lecturer = $stmt->fetch();
if (!$lecturer) { redirectWithMessage('index.php', 'error', 'Lecturer record not found.'); }
$lecturerId = $lecturer['lecturer_id'];

// Check assignment
$stmt = $pdo->prepare("SELECT COUNT(*) FROM course_assignments WHERE course_id = ? AND lecturer_id = ?");
$stmt->execute([$courseId, $lecturerId]);
if ($stmt->fetchColumn() == 0) { redirectWithMessage('index.php', 'error', 'You are not assigned to this course.'); }


// Verify enrollment
$stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND course_id = ?");
$stmt->execute([$studentId, $courseId]);
if ($stmt->fetchColumn() == 0) { redirectWithMessage('index.php?course_id=' . $courseId, 'error', 'Student not enrolled.'); }

// Student info
$stmt = $pdo->prepare("SELECT s.student_id, s.registration_no, u.full_name FROM students s INNER JOIN users u ON s.user_id = u.id WHERE s.student_id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();
if (!$student) { redirectWithMessage('index.php', 'error', 'Student not found.'); }

// Course info
$stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
$stmt->execute([$courseId]);
$course = $stmt->fetch();
if (!$course) { redirectWithMessage('index.php', 'error', 'Course not found.'); }

// Existing marks
$stmt = $pdo->prepare("SELECT component, score, weight FROM marks WHERE student_id = ? AND course_id = ? ORDER BY mark_id ASC");
$stmt->execute([$studentId, $courseId]);
$existingMarks = $stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $components = $_POST['components'] ?? [];
    $scores     = $_POST['scores'] ?? [];
    $weights    = $_POST['weights'] ?? [];

    if (empty($components) || count($components) == 0) {
        $errors[] = 'At least one mark component is required.';
    } else {
        $totalWeight = 0;
        for ($i = 0; $i < count($components); $i++) {
            $comp = trim($components[$i] ?? '');
            $score = $scores[$i] ?? '';
            $weight = $weights[$i] ?? '';
            if (empty($comp)) $errors[] = 'Component name #' . ($i + 1) . ' is required.';
            if (!is_numeric($score) || $score < 0 || $score > 100) $errors[] = 'Score for "' . e($comp) . '" must be 0-100.';
            if (!is_numeric($weight) || $weight < 0 || $weight > 100) $errors[] = 'Weight for "' . e($comp) . '" must be 0-100.';
            else $totalWeight += floatval($weight);
        }
        if ($totalWeight > 100) $errors[] = 'Total weight (' . number_format($totalWeight, 1) . '%) exceeds 100%.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM marks WHERE student_id = ? AND course_id = ?")->execute([$studentId, $courseId]);
            $ins = $pdo->prepare("INSERT INTO marks (student_id, course_id, component, score, weight) VALUES (?, ?, ?, ?, ?)");
            for ($i = 0; $i < count($components); $i++) {
                $comp = trim($components[$i]);
                if (!empty($comp)) $ins->execute([$studentId, $courseId, $comp, floatval($scores[$i]), floatval($weights[$i])]);
            }
            $totalScore = calculateTotalScore($pdo, $studentId, $courseId);
            $grade = calculateGrade($totalScore);
            $gpaPoints = calculateGPAPoints($grade);
            $pdo->prepare("INSERT INTO final_results (student_id, course_id, total_score, grade, gpa_points) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE total_score = VALUES(total_score), grade = VALUES(grade), gpa_points = VALUES(gpa_points)")
                ->execute([$studentId, $courseId, $totalScore, $grade, $gpaPoints]);
            $pdo->commit();
            redirectWithMessage('index.php?course_id=' . $courseId, 'success', 'Marks saved for ' . $student['full_name'] . '! Total: ' . number_format($totalScore, 2) . '% (Grade: ' . $grade . ')');
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Save marks error: " . $e->getMessage());
            $errors[] = 'Database error. Please try again.';
        }
    }

    if (!empty($errors)) {
        $existingMarks = [];
        for ($i = 0; $i < count($components); $i++) {
            $existingMarks[] = ['component' => $components[$i] ?? '', 'score' => $scores[$i] ?? '', 'weight' => $weights[$i] ?? ''];
        }
    }
}

$pageTitle   = 'Enter Marks';
$currentPage = 'marks';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/lecturer_dashboard.php'],
    ['label' => 'Marks', 'url' => 'index.php?course_id=' . $courseId],
    ['label' => 'Enter Marks']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Enter / Edit Marks</h1><p><?php echo e($student['full_name']); ?> — <?php echo e($course['course_code']); ?></p></div>
    <a href="index.php?course_id=<?php echo $courseId; ?>" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<!-- Student Info -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body py-2 px-3"><small class="text-muted d-block">Student</small><strong><?php echo e($student['full_name']); ?></strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-2 px-3"><small class="text-muted d-block">Reg. No</small><strong><?php echo e($student['registration_no']); ?></strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-2 px-3"><small class="text-muted d-block">Course</small><strong><?php echo e($course['course_code']); ?></strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-2 px-3"><small class="text-muted d-block">Credits</small><strong><?php echo $course['credits']; ?></strong></div></div></div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>
        <div><strong>Please fix:</strong><ul class="mb-0 mt-1"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Mark Components</h5>
            <button type="button" class="btn btn-sm btn-info text-white" onclick="addComponent()"><i class="fas fa-plus me-1"></i> Add Component</button>
        </div>

        <form method="POST" action="" id="marksForm">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="marksTable">
                    <thead class="table-light">
                        <tr><th style="width:35%;">Component</th><th style="width:18%;">Score (0-100)</th><th style="width:18%;">Weight (%)</th><th style="width:15%;">Weighted</th><th style="width:14%;">Action</th></tr>
                    </thead>
                    <tbody id="componentsBody">
                        <?php if (!empty($existingMarks)): ?>
                            <?php foreach ($existingMarks as $mark): ?>
                            <tr class="component-row">
                                <td><input type="text" class="form-control form-control-sm" name="components[]" value="<?php echo e($mark['component']); ?>" placeholder="e.g. Quiz" required></td>
                                <td><input type="number" class="form-control form-control-sm" name="scores[]" value="<?php echo e($mark['score']); ?>" min="0" max="100" step="0.01" oninput="updateCalc()" required></td>
                                <td><input type="number" class="form-control form-control-sm" name="weights[]" value="<?php echo e($mark['weight']); ?>" min="0" max="100" step="0.01" oninput="updateCalc()" required></td>
                                <td class="weighted-value fw-600 text-primary align-middle">—</td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="fas fa-trash-alt"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="component-row"><td><input type="text" class="form-control form-control-sm" name="components[]" value="Quiz" required></td><td><input type="number" class="form-control form-control-sm" name="scores[]" min="0" max="100" step="0.01" oninput="updateCalc()" required></td><td><input type="number" class="form-control form-control-sm" name="weights[]" value="10" min="0" max="100" step="0.01" oninput="updateCalc()" required></td><td class="weighted-value fw-600 text-primary align-middle">—</td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="fas fa-trash-alt"></i></button></td></tr>
                            <tr class="component-row"><td><input type="text" class="form-control form-control-sm" name="components[]" value="Assignment" required></td><td><input type="number" class="form-control form-control-sm" name="scores[]" min="0" max="100" step="0.01" oninput="updateCalc()" required></td><td><input type="number" class="form-control form-control-sm" name="weights[]" value="20" min="0" max="100" step="0.01" oninput="updateCalc()" required></td><td class="weighted-value fw-600 text-primary align-middle">—</td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="fas fa-trash-alt"></i></button></td></tr>
                            <tr class="component-row"><td><input type="text" class="form-control form-control-sm" name="components[]" value="Midterm" required></td><td><input type="number" class="form-control form-control-sm" name="scores[]" min="0" max="100" step="0.01" oninput="updateCalc()" required></td><td><input type="number" class="form-control form-control-sm" name="weights[]" value="30" min="0" max="100" step="0.01" oninput="updateCalc()" required></td><td class="weighted-value fw-600 text-primary align-middle">—</td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="fas fa-trash-alt"></i></button></td></tr>
                            <tr class="component-row"><td><input type="text" class="form-control form-control-sm" name="components[]" value="Final" required></td><td><input type="number" class="form-control form-control-sm" name="scores[]" min="0" max="100" step="0.01" oninput="updateCalc()" required></td><td><input type="number" class="form-control form-control-sm" name="weights[]" value="40" min="0" max="100" step="0.01" oninput="updateCalc()" required></td><td class="weighted-value fw-600 text-primary align-middle">—</td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="fas fa-trash-alt"></i></button></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Calculation Preview -->
            <div class="bg-light rounded p-3 mt-3">
                <h6 class="mb-2"><i class="fas fa-calculator me-1"></i> Calculation Preview</h6>
                <div class="row g-3 text-center">
                    <div class="col-md-4"><div class="bg-white rounded p-2 border"><small class="text-muted d-block">Total Weight</small><strong id="totalWeight" class="fs-5">0%</strong></div></div>
                    <div class="col-md-4"><div class="bg-white rounded p-2 border"><small class="text-muted d-block">Weighted Total</small><strong id="totalScore" class="fs-5">0.00</strong></div></div>
                    <div class="col-md-4"><div class="bg-white rounded p-2 border"><small class="text-muted d-block">Predicted Grade</small><strong id="predictedGrade" class="fs-5">—</strong></div></div>
                </div>
            </div>

            <div class="d-flex gap-2 pt-3 mt-4 border-top">
                <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Marks</button>
                <a href="index.php?course_id=<?php echo $courseId; ?>" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function addComponent() {
    var tbody = document.getElementById('componentsBody');
    var row = document.createElement('tr');
    row.className = 'component-row';
    row.innerHTML = `
        <td><input type="text" class="form-control form-control-sm" name="components[]" placeholder="e.g. Lab Work" required></td>
        <td><input type="number" class="form-control form-control-sm" name="scores[]" min="0" max="100" step="0.01" placeholder="0-100" oninput="updateCalc()" required></td>
        <td><input type="number" class="form-control form-control-sm" name="weights[]" min="0" max="100" step="0.01" placeholder="0-100" oninput="updateCalc()" required></td>
        <td class="weighted-value fw-600 text-primary align-middle">—</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="fas fa-trash-alt"></i></button></td>
    `;
    tbody.appendChild(row);
}

function removeRow(btn) {
    if (document.querySelectorAll('.component-row').length <= 1) { alert('At least one component is required.'); return; }
    btn.closest('tr').remove();
    updateCalc();
}

function updateCalc() {
    var rows = document.querySelectorAll('.component-row');
    var tw = 0, ts = 0;
    rows.forEach(function(r) {
        var s = parseFloat(r.querySelector('input[name="scores[]"]').value) || 0;
        var w = parseFloat(r.querySelector('input[name="weights[]"]').value) || 0;
        var weighted = s * w / 100;
        r.querySelector('.weighted-value').textContent = weighted.toFixed(2);
        tw += w; ts += weighted;
    });
    var weightEl = document.getElementById('totalWeight');
    weightEl.textContent = tw.toFixed(1) + '%';
    weightEl.style.color = tw > 100 ? '#dc3545' : '';
    document.getElementById('totalScore').textContent = ts.toFixed(2);
    var g = '—', gc = '';
    if (tw > 0) {
        if (ts >= 75) { g='A'; gc='color:#28a745'; }
        else if (ts >= 65) { g='B'; gc='color:#007bff'; }
        else if (ts >= 55) { g='C'; gc='color:#ffc107'; }
        else if (ts >= 45) { g='D'; gc='color:#fd7e14'; }
        else { g='F'; gc='color:#dc3545'; }
    }
    var ge = document.getElementById('predictedGrade');
    ge.textContent = g; ge.style.cssText = gc;
}

document.addEventListener('DOMContentLoaded', function() { updateCalc(); });
</script>

<?php require_once '../../includes/footer.php'; ?>
