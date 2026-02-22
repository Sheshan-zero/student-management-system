<?php
/**
 * Activity / Audit Log — Admin
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

$page = (int)($_GET['page'] ?? 1);

$sql = "SELECT al.*, u.full_name, u.role FROM activity_log al JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC";
$pagination = paginate($pdo, $sql, [], $page, 20);
$logs = $pagination['items'];

$pageTitle   = 'Activity Log';
$currentPage = 'activity_log';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Activity Log']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Activity Log</h1><p>Track all user actions across the system.</p></div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead><tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
                <?php if (count($logs) > 0): ?>
                    <?php foreach ($logs as $l):
                        $roleBadge = match($l['role']) { 'admin' => 'bg-danger', 'lecturer' => 'bg-primary', default => 'bg-success' };
                    ?>
                    <tr>
                        <td><small><?php echo date('M d, Y g:i A', strtotime($l['created_at'])); ?></small></td>
                        <td class="fw-600"><?php echo e($l['full_name']); ?></td>
                        <td><span class="badge <?php echo $roleBadge; ?>"><?php echo ucfirst($l['role']); ?></span></td>
                        <td><?php echo e($l['action']); ?></td>
                        <td><small class="text-muted"><?php echo e($l['details'] ?? ''); ?></small></td>
                        <td><code><?php echo e($l['ip_address']); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-5">
                        <i class="fas fa-history fa-3x mb-3 d-block" style="opacity:0.2;"></i>
                        <p class="text-muted">No activity recorded yet.</p>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pagination['pages'] > 1): ?>
    <div class="card-footer"><?php echo renderPagination($pagination, 'index.php'); ?></div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
