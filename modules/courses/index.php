<?php
/**
 * Course List Page
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$params = [];

if (!empty($search)) {
    $sql = "SELECT course_id, course_code, course_name, credits
            FROM courses WHERE course_code LIKE ? OR course_name LIKE ?
            ORDER BY course_code ASC";
    $params = ["%$search%", "%$search%"];
} else {
    $sql = "SELECT course_id, course_code, course_name, credits FROM courses ORDER BY course_code ASC";
}

$paginated = paginate($pdo, $sql, $params, $page, $perPage);
$courses = $paginated['items'];
$totalRecords = $paginated['total'];

$pageTitle   = 'Courses';
$currentPage = 'courses';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Courses']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Courses</h1>
        <p><?php echo $totalRecords; ?> total courses</p>
    </div>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Course</a>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="" class="filter-bar mb-0">
            <div class="search-box" style="max-width:400px;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" class="form-control" placeholder="Search by code or name..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Search</button>
            <?php if (!empty($search)): ?>
                <a href="index.php" class="btn btn-light"><i class="fas fa-times me-1"></i> Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr><th>Course Code</th><th>Course Name</th><th>Credits</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (count($courses) > 0): ?>
                    <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><code class="fw-600"><?php echo e($course['course_code']); ?></code></td>
                        <td class="fw-600"><?php echo e($course['course_name']); ?></td>
                        <td><span class="badge" style="background:var(--primary-light);color:var(--primary);"><?php echo $course['credits']; ?></span></td>
                        <td>
                            <div class="action-btns">
                                <a href="edit.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-light" title="Edit"><i class="fas fa-pen"></i></a>
                                <form method="POST" action="delete.php" class="delete-form d-inline">
                                    <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-book fa-3x mb-3 d-block" style="opacity:0.2;"></i>
                            <?php if (!empty($search)): ?>
                                <p>No courses found matching "<strong><?php echo e($search); ?></strong>"</p>
                            <?php else: ?>
                                <p>No courses yet. Click <strong>Add Course</strong> to get started.</p>
                            <?php endif; ?>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalRecords > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing <?php echo count($courses); ?> of <?php echo $totalRecords; ?> record(s)</small>
        <?php 
            $baseUrl = '?search=' . urlencode($search);
            echo renderPagination($paginated, $baseUrl); 
        ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
