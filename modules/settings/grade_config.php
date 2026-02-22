<?php
/**
 * Grade Configuration — Admin
 * Customize grade boundaries and GPA points
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

$errors  = [];
$success = false;

// Check if grade_config table exists
$tableExists = false;
try {
    $pdo->query("SELECT 1 FROM grade_config LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {
    $tableExists = false;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tableExists) {
    $grades    = $_POST['grade'] ?? [];
    $minScores = $_POST['min_score'] ?? [];
    $gpaPoints = $_POST['gpa_points'] ?? [];
    $labels    = $_POST['label'] ?? [];
    
    // Validate
    $prevScore = 101;
    for ($i = 0; $i < count($grades); $i++) {
        $score = floatval($minScores[$i]);
        $gpa   = floatval($gpaPoints[$i]);
        if ($score < 0 || $score > 100) $errors[] = "Grade {$grades[$i]}: min score must be 0-100.";
        if ($gpa < 0 || $gpa > 4.0) $errors[] = "Grade {$grades[$i]}: GPA must be 0-4.";
        if ($i > 0 && $score >= $prevScore) $errors[] = "Grade boundaries must be in descending order (A highest, F lowest).";
        $prevScore = $score;
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE grade_config SET min_score = ?, gpa_points = ?, label = ? WHERE grade = ?");
            for ($i = 0; $i < count($grades); $i++) {
                $stmt->execute([floatval($minScores[$i]), floatval($gpaPoints[$i]), trim($labels[$i] ?? ''), $grades[$i]]);
            }

            // Recalculate ALL final_results with new boundaries
            $allResults = $pdo->query("SELECT DISTINCT student_id, course_id FROM final_results")->fetchAll();
            $updateStmt = $pdo->prepare("UPDATE final_results SET total_score = ?, grade = ?, gpa_points = ? WHERE student_id = ? AND course_id = ?");
            
            foreach ($allResults as $r) {
                $totalScore = calculateTotalScore($pdo, $r['student_id'], $r['course_id']);
                $grade      = calculateGradeFromDB($pdo, $totalScore);
                $gpa        = getGPAFromDB($pdo, $grade);
                $updateStmt->execute([$totalScore, $grade, $gpa, $r['student_id'], $r['course_id']]);
            }

            $pdo->commit();
            $success = true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch current config
$gradeConfig = [];
if ($tableExists) {
    $gradeConfig = $pdo->query("SELECT * FROM grade_config ORDER BY min_score DESC")->fetchAll();
}

// Helper functions for DB-backed grades
function calculateGradeFromDB($pdo, $score) {
    try {
        $config = $pdo->query("SELECT grade, min_score FROM grade_config ORDER BY min_score DESC")->fetchAll();
        foreach ($config as $g) {
            if ($score >= $g['min_score']) return $g['grade'];
        }
        return 'F';
    } catch (PDOException $e) {
        return calculateGrade($score); // fallback
    }
}

function getGPAFromDB($pdo, $grade) {
    try {
        $stmt = $pdo->prepare("SELECT gpa_points FROM grade_config WHERE grade = ?");
        $stmt->execute([$grade]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (float)$result : 0.0;
    } catch (PDOException $e) {
        return calculateGPAPoints($grade); // fallback
    }
}

$pageTitle   = 'Grade Configuration';
$currentPage = 'grade_config';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Settings', 'url' => 'index.php'],
    ['label' => 'Grade Config']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Grade Configuration</h1><p>Customize grade boundaries, GPA values, and labels.</p></div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Settings</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> Grade configuration updated and all results recalculated!</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>
        <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php if (!$tableExists): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Table not found!</strong> Run <code>setup_grade_config.sql</code> in phpMyAdmin first.
    </div>
<?php else: ?>

<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Grade Boundaries</h6></div>
    <div class="card-body">
        <form method="POST">
            <div class="table-responsive">
                <table class="table table-bordered mb-3">
                    <thead class="table-light">
                        <tr><th>Grade</th><th>Min Score (≥)</th><th>GPA Points</th><th>Label</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gradeConfig as $gc): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary fs-6"><?php echo e($gc['grade']); ?></span>
                                <input type="hidden" name="grade[]" value="<?php echo e($gc['grade']); ?>">
                            </td>
                            <td>
                                <input type="number" class="form-control" name="min_score[]" 
                                       value="<?php echo $gc['min_score']; ?>" 
                                       min="0" max="100" step="0.01" required>
                            </td>
                            <td>
                                <input type="number" class="form-control" name="gpa_points[]" 
                                       value="<?php echo $gc['gpa_points']; ?>" 
                                       min="0" max="4" step="0.01" required>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="label[]" 
                                       value="<?php echo e($gc['label']); ?>" 
                                       placeholder="e.g. Excellent">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> Saving will automatically recalculate all existing student grades and GPAs.
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save & Recalculate</button>
        </form>
    </div>
</div>

<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
