<?php
/**
 * Student List Page - index.php
 * Displays all students in a modern table with search
 * Only accessible to administrators
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination config
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$params = [];

if (!empty($search)) {
    $sql = "SELECT s.student_id, s.registration_no, s.department, s.intake_year, s.status,
                   u.id as user_id, u.full_name, u.email
            FROM students s JOIN users u ON s.user_id = u.id
            WHERE u.full_name LIKE ? OR s.registration_no LIKE ?
            ORDER BY s.student_id DESC";
    $params = ["%$search%", "%$search%"];
} else {
    $sql = "SELECT s.student_id, s.registration_no, s.department, s.intake_year, s.status,
                   u.id as user_id, u.full_name, u.email
            FROM students s JOIN users u ON s.user_id = u.id
            ORDER BY s.student_id DESC";
}

$paginated = paginate($pdo, $sql, $params, $page, $perPage);
$students = $paginated['items'];
$totalRecords = $paginated['total'];


// Layout
$pageTitle   = 'Students';
$currentPage = 'students';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Students']
];
require_once '../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1>Students</h1>
        <p><?php echo $totalRecords; ?> total students<?php echo !empty($search) ? ' matching "' . htmlspecialchars($search) . '"' : ''; ?></p>
    </div>
    <div>
        <a href="import.php" class="btn btn-success me-2">
            <i class="fas fa-file-csv me-1"></i> Bulk Import
        </a>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add Student
        </a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="" class="filter-bar mb-0">
            <div class="search-box" style="max-width:400px;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" class="form-control"
                       placeholder="Search by name or registration number..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search me-1"></i> Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="index.php" class="btn btn-light">
                    <i class="fas fa-times me-1"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>Reg. No</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Intake Year</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><code class="fw-600"><?php echo e($student['registration_no']); ?></code></td>
                            <td class="fw-600"><?php echo e($student['full_name']); ?></td>
                            <td><?php echo e($student['email']); ?></td>
                            <td><?php echo e($student['department']); ?></td>
                            <td><?php echo e($student['intake_year']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo e($student['status']); ?>">
                                    <?php echo ucfirst(e($student['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="view.php?student_id=<?php echo $student['student_id']; ?>"
                                       class="btn btn-sm btn-light" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?student_id=<?php echo $student['student_id']; ?>"
                                       class="btn btn-sm btn-light" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form method="POST" action="delete.php" class="delete-form d-inline">
                                        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-user-graduate fa-3x mb-3 d-block" style="opacity:0.2;"></i>
                                <?php if (!empty($search)): ?>
                                    <p>No students found matching "<strong><?php echo e($search); ?></strong>"</p>
                                    <a href="index.php" class="btn btn-primary btn-sm mt-2">View All Students</a>
                                <?php else: ?>
                                    <p>No students yet. Click <strong>Add Student</strong> to get started.</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalRecords > 0): ?>
    <div class="pagination-wrapper">
        <span>Showing <?php echo count($students); ?> of <?php echo $totalRecords; ?> record(s)</span>
        <?php 
            $baseUrl = '?search=' . urlencode($search);
            echo renderPagination($paginated, $baseUrl); 
        ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
