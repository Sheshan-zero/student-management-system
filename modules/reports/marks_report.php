<?php
/**
 * Marks Reports — Admin
 * Shows marks/grades overview across all courses
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

// Overall stats
$totalGraded = $pdo->query("SELECT COUNT(*) FROM final_results")->fetchColumn();
$avgScore    = $pdo->query("SELECT ROUND(AVG(total_score), 2) FROM final_results")->fetchColumn() ?: 0;
$passCount   = $pdo->query("SELECT COUNT(*) FROM final_results WHERE grade != 'F'")->fetchColumn();
$failCount   = $pdo->query("SELECT COUNT(*) FROM final_results WHERE grade = 'F'")->fetchColumn();
$passRate    = $totalGraded > 0 ? round(($passCount / $totalGraded) * 100, 1) : 0;

// Grade distribution
$gradeDist = $pdo->query("SELECT grade, COUNT(*) as cnt FROM final_results GROUP BY grade ORDER BY FIELD(grade, 'A','B','C','D','F')")->fetchAll();

// Per-course breakdown
$courseStats = $pdo->query("
    SELECT c.course_code, c.course_name, c.credits,
           COUNT(fr.result_id) as graded,
           ROUND(AVG(fr.total_score), 2) as avg_score,
           ROUND(AVG(fr.gpa_points), 2) as avg_gpa,
           SUM(CASE WHEN fr.grade = 'A' THEN 1 ELSE 0 END) as grade_a,
           SUM(CASE WHEN fr.grade = 'B' THEN 1 ELSE 0 END) as grade_b,
           SUM(CASE WHEN fr.grade = 'C' THEN 1 ELSE 0 END) as grade_c,
           SUM(CASE WHEN fr.grade = 'D' THEN 1 ELSE 0 END) as grade_d,
           SUM(CASE WHEN fr.grade = 'F' THEN 1 ELSE 0 END) as grade_f
    FROM courses c
    INNER JOIN final_results fr ON c.course_id = fr.course_id
    GROUP BY c.course_id, c.course_code, c.course_name, c.credits
    ORDER BY c.course_code
")->fetchAll();

$pageTitle   = 'Marks Reports';
$currentPage = 'mark_reports';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Marks Reports']
];
require_once '../../includes/header.php';
?>

<style>
.grade-A { background: #d4edda; color: #155724; }
.grade-B { background: #cce5ff; color: #004085; }
.grade-C { background: #fff3cd; color: #856404; }
.grade-D { background: #ffe0cc; color: #cc5500; }
.grade-F { background: #f8d7da; color: #721c24; }
</style>

<div class="page-header">
    <div><h1>Marks Reports</h1><p>Overview of grades and performance across all courses.</p></div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card bg-primary text-white">
            <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
            <div class="stat-number"><?php echo $totalGraded; ?></div>
            <div class="stat-label">Students Graded</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card bg-info text-white">
            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
            <div class="stat-number"><?php echo $avgScore; ?>%</div>
            <div class="stat-label">Average Score</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card bg-success text-white">
            <div class="stat-icon"><i class="fas fa-check-double"></i></div>
            <div class="stat-number"><?php echo $passRate; ?>%</div>
            <div class="stat-label">Pass Rate</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card bg-danger text-white">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-number"><?php echo $failCount; ?></div>
            <div class="stat-label">Failures</div>
        </div>
    </div>
</div>

<!-- Grade Distribution -->
<?php if (count($gradeDist) > 0): ?>
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Grade Distribution</h6></div>
    <div class="card-body">
        <div class="row g-2 text-center">
            <?php foreach ($gradeDist as $g): 
                $pct = $totalGraded > 0 ? round(($g['cnt'] / $totalGraded) * 100, 1) : 0;
            ?>
            <div class="col">
                <div class="p-3 rounded border">
                    <span class="badge grade-<?php echo $g['grade']; ?>" style="font-size:1.2rem;padding:8px 16px;"><?php echo $g['grade']; ?></span>
                    <div class="fs-4 fw-bold mt-2"><?php echo $g['cnt']; ?></div>
                    <small class="text-muted"><?php echo $pct; ?>%</small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Per-course Table -->
<div class="card table-card">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Performance by Course</h6></div>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead><tr><th>Course</th><th>Graded</th><th>Avg Score</th><th>Avg GPA</th><th>A</th><th>B</th><th>C</th><th>D</th><th>F</th></tr></thead>
            <tbody>
                <?php if (count($courseStats) > 0): ?>
                    <?php foreach ($courseStats as $cs): ?>
                    <tr>
                        <td><div class="fw-600"><?php echo e($cs['course_name']); ?></div><small class="text-muted"><code><?php echo e($cs['course_code']); ?></code></small></td>
                        <td><?php echo $cs['graded']; ?></td>
                        <td class="fw-600"><?php echo $cs['avg_score']; ?>%</td>
                        <td class="fw-600"><?php echo $cs['avg_gpa']; ?></td>
                        <td><span class="badge grade-A"><?php echo $cs['grade_a']; ?></span></td>
                        <td><span class="badge grade-B"><?php echo $cs['grade_b']; ?></span></td>
                        <td><span class="badge grade-C"><?php echo $cs['grade_c']; ?></span></td>
                        <td><span class="badge grade-D"><?php echo $cs['grade_d']; ?></span></td>
                        <td><span class="badge grade-F"><?php echo $cs['grade_f']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center py-5"><i class="fas fa-chart-pie fa-3x mb-3 d-block" style="opacity:0.2;"></i><p class="text-muted">No graded results yet. Lecturers need to enter marks first.</p></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
