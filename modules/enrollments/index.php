<?php
/**
 * Enrollment List Page
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

$filterStudent = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$filterCourse  = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$page          = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage       = 10;

$sql = "SELECT e.enrollment_id, e.enrolled_at,
               s.student_id, s.registration_no, u.full_name AS student_name,
               c.course_id, c.course_code, c.course_name, c.credits
        FROM enrollments e
        INNER JOIN students s ON e.student_id = s.student_id
        INNER JOIN users u ON s.user_id = u.id
        INNER JOIN courses c ON e.course_id = c.course_id
        WHERE 1=1";
$params = [];
if ($filterStudent > 0) { $sql .= " AND s.student_id = ?"; $params[] = $filterStudent; }
if ($filterCourse > 0)  { $sql .= " AND c.course_id = ?";  $params[] = $filterCourse; }
if (!empty($search))    { $sql .= " AND (s.registration_no LIKE ? OR c.course_code LIKE ?)"; $p = "%{$search}%"; $params[] = $p; $params[] = $p; }
$sql .= " ORDER BY e.enrolled_at DESC";

$paginated = paginate($pdo, $sql, $params, $page, $perPage);
$enrollments = $paginated['items'];
$totalRecords = $paginated['total'];

$students = $pdo->query("SELECT s.student_id, s.registration_no, u.full_name FROM students s INNER JOIN users u ON s.user_id = u.id ORDER BY s.registration_no ASC")->fetchAll();
$courses  = $pdo->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code ASC")->fetchAll();

$pageTitle   = 'Enrollments';
$currentPage = 'enrollments';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Enrollments']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Enrollments</h1>
        <p><?php echo $totalRecords; ?> total enrollments</p>
    </div>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Enroll Student</a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Student</label>
                <select class="form-select form-select-sm" name="student_id">
                    <option value="">All Students</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s['student_id']; ?>" <?php echo $filterStudent == $s['student_id'] ? 'selected' : ''; ?>>
                            <?php echo e($s['registration_no'] . ' - ' . $s['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Course</label>
                <select class="form-select form-select-sm" name="course_id">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['course_id']; ?>" <?php echo $filterCourse == $c['course_id'] ? 'selected' : ''; ?>>
                            <?php echo e($c['course_code'] . ' - ' . $c['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Search</label>
                <input type="text" class="form-control form-control-sm" name="search" value="<?php echo e($search); ?>" placeholder="Reg. No / Course Code">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i> Apply</button>
                <a href="index.php" class="btn btn-light btn-sm"><i class="fas fa-times me-1"></i> Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Enrollments Table -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr><th>Student</th><th>Course</th><th>Credits</th><th>Enrolled On</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (count($enrollments) > 0): ?>
                    <?php foreach ($enrollments as $enr): ?>
                    <tr>
                        <td>
                            <div class="fw-600"><?php echo e($enr['student_name']); ?></div>
                            <small class="text-muted"><?php echo e($enr['registration_no']); ?></small>
                        </td>
                        <td>
                            <div class="fw-600"><?php echo e($enr['course_name']); ?></div>
                            <small class="text-muted"><code><?php echo e($enr['course_code']); ?></code></small>
                        </td>
                        <td><span class="badge" style="background:var(--primary-light);color:var(--primary);"><?php echo $enr['credits']; ?></span></td>
                        <td><small><?php echo date('M d, Y', strtotime($enr['enrolled_at'])); ?></small></td>
                        <td>
                            <form method="POST" action="delete.php" class="delete-form d-inline">
                                <input type="hidden" name="enrollment_id" value="<?php echo $enr['enrollment_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-light text-danger" title="Unenroll"><i class="fas fa-user-minus"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-clipboard-list fa-3x mb-3 d-block" style="opacity:0.2;"></i>
                            <?php if ($filterStudent > 0 || $filterCourse > 0 || !empty($search)): ?>
                                <p>No enrollments match your filters.</p>
                                <a href="index.php" class="btn btn-primary btn-sm mt-2">View All</a>
                            <?php else: ?>
                                <p>No enrollments yet. Click <strong>Enroll Student</strong> to begin.</p>
                            <?php endif; ?>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalRecords > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing <?php echo count($enrollments); ?> of <?php echo $totalRecords; ?> record(s)</small>
        <?php 
            $baseUrl = '?search=' . urlencode($search) . '&student_id=' . $filterStudent . '&course_id=' . $filterCourse;
            echo renderPagination($paginated, $baseUrl); 
        ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
