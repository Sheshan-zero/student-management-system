<?php
/**
 * Lecturers List — Admin
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    try {
        $pdo->beginTransaction();
        // Get user_id for this lecturer
        $stmt = $pdo->prepare("SELECT user_id FROM lecturers WHERE lecturer_id = ?");
        $stmt->execute([$deleteId]);
        $lect = $stmt->fetch();
        if ($lect) {
            $pdo->prepare("DELETE FROM lecturers WHERE lecturer_id = ?")->execute([$deleteId]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$lect['user_id']]);
        }
        $pdo->commit();
        redirectWithMessage('index.php', 'success', 'Lecturer deleted successfully.');
    } catch (PDOException $e) {
        $pdo->rollBack();
        redirectWithMessage('index.php', 'error', 'Error deleting lecturer.');
    }
}

// Search
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;

$sql = "SELECT l.lecturer_id, l.department, u.id as user_id, u.full_name, u.email, u.created_at
        FROM lecturers l
        JOIN users u ON l.user_id = u.id";
$params = [];
if ($search !== '') {
    $sql .= " WHERE (u.full_name LIKE ? OR u.email LIKE ? OR l.department LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$sql .= " ORDER BY u.full_name";

$paginated = paginate($pdo, $sql, $params, $page, $perPage);
$lecturers = $paginated['items'];
$totalRecords = $paginated['total'];

$pageTitle   = 'Manage Lecturers';
$currentPage = 'lecturers';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Lecturers']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Lecturers</h1><p>Manage lecturer accounts and departments.</p></div>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Lecturer</a>
</div>

<?php displayFlashMessage(); ?>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-8">
                <input type="text" class="form-control" name="search" placeholder="Search by name, email, or department..." value="<?php echo e($search); ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-search me-1"></i> Search</button>
                <?php if ($search): ?><a href="index.php" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Joined</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (count($lecturers) > 0): ?>
                    <?php foreach ($lecturers as $l): ?>
                    <tr>
                        <td class="fw-600"><?php echo e($l['full_name']); ?></td>
                        <td><?php echo e($l['email']); ?></td>
                        <td><span class="badge bg-info"><?php echo e($l['department'] ?? '—'); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($l['created_at'])); ?></td>
                        <td class="text-end">
                            <a href="edit.php?id=<?php echo $l['lecturer_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this lecturer? This cannot be undone.');">
                                <input type="hidden" name="delete_id" value="<?php echo $l['lecturer_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-5">
                        <i class="fas fa-chalkboard-teacher fa-3x mb-3 d-block" style="opacity:0.2;"></i>
                        <p class="text-muted"><?php echo $search ? 'No lecturers match your search.' : 'No lecturers found. Add one to get started.'; ?></p>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalRecords > 0): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing <?php echo count($lecturers); ?> of <?php echo $totalRecords; ?> record(s)</small>
        <?php 
            $baseUrl = '?search=' . urlencode($search);
            echo renderPagination($paginated, $baseUrl); 
        ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
