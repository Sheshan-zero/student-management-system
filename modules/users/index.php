<?php
/**
 * User Management — Admin
 * View all users, filter by role
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

// Filter
$roleFilter = $_GET['role'] ?? '';
$search     = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
if ($roleFilter !== '' && in_array($roleFilter, ['admin','lecturer','student'])) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}
if ($search !== '') {
    $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle   = 'User Management';
$currentPage = 'users';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Users']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Users & Roles</h1><p>View and manage all user accounts.</p></div>
</div>

<?php displayFlashMessage(); ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" class="form-control" name="search" placeholder="Search by name or email..." value="<?php echo e($search); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="role">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="lecturer" <?php echo $roleFilter === 'lecturer' ? 'selected' : ''; ?>>Lecturer</option>
                    <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Student</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-filter me-1"></i> Filter</button>
                <?php if ($search || $roleFilter): ?><a href="index.php" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead><tr><th>#</th><th>Full Name</th><th>Email</th><th>Role</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $u):
                        $roleBadge = match($u['role']) {
                            'admin' => 'bg-danger',
                            'lecturer' => 'bg-primary',
                            default => 'bg-success'
                        };
                    ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td class="fw-600"><?php echo e($u['full_name']); ?></td>
                        <td><?php echo e($u['email']); ?></td>
                        <td><span class="badge <?php echo $roleBadge; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td class="text-end">
                            <a href="edit.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-5">
                        <i class="fas fa-users fa-3x mb-3 d-block" style="opacity:0.2;"></i>
                        <p class="text-muted">No users found.</p>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($users) > 0): ?>
    <div class="card-footer text-muted"><small>Showing <?php echo count($users); ?> user(s)</small></div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
