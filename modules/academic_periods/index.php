<?php
/**
 * Academic Periods — Admin
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $name  = trim($_POST['name'] ?? '');
        $start = $_POST['start_date'] ?? '';
        $end   = $_POST['end_date'] ?? '';
        $isCurrent = isset($_POST['is_current']) ? 1 : 0;
        if (!empty($name) && !empty($start) && !empty($end)) {
            if ($isCurrent) $pdo->exec("UPDATE academic_periods SET is_current = 0");
            $pdo->prepare("INSERT INTO academic_periods (name, start_date, end_date, is_current) VALUES (?, ?, ?, ?)")
                ->execute([$name, $start, $end, $isCurrent]);
            redirectWithMessage('index.php', 'success', 'Period created.');
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'set_current') {
        $id = (int)$_POST['period_id'];
        $pdo->exec("UPDATE academic_periods SET is_current = 0");
        $pdo->prepare("UPDATE academic_periods SET is_current = 1 WHERE period_id = ?")->execute([$id]);
        redirectWithMessage('index.php', 'success', 'Current period updated.');
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $pdo->prepare("DELETE FROM academic_periods WHERE period_id = ?")->execute([(int)$_POST['period_id']]);
        redirectWithMessage('index.php', 'success', 'Period deleted.');
    }
}

$periods = $pdo->query("SELECT * FROM academic_periods ORDER BY start_date DESC")->fetchAll();

$pageTitle   = 'Academic Periods';
$currentPage = 'academic_periods';
$breadcrumbs = [['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'], ['label' => 'Academic Periods']];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Academic Periods</h1><p>Manage semesters and academic years.</p></div>
</div>

<?php displayFlashMessage(); ?>

<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add Period</h6></div>
    <div class="card-body">
        <form method="POST" class="row g-3 align-items-end">
            <input type="hidden" name="action" value="create">
            <div class="col-md-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="name" placeholder="e.g. 2026 Semester 1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" class="form-control" name="start_date" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" class="form-control" name="end_date" required>
            </div>
            <div class="col-md-1">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_current" id="curCheck">
                    <label class="form-check-label" for="curCheck">Current</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i> Add</button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead><tr><th>Period</th><th>Start</th><th>End</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($periods as $p): ?>
                <tr>
                    <td class="fw-600"><?php echo e($p['name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($p['start_date'])); ?></td>
                    <td><?php echo date('M d, Y', strtotime($p['end_date'])); ?></td>
                    <td>
                        <?php if ($p['is_current']): ?>
                            <span class="badge bg-success">Current</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end d-flex gap-1 justify-content-end">
                        <?php if (!$p['is_current']): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="set_current">
                            <input type="hidden" name="period_id" value="<?php echo $p['period_id']; ?>">
                            <button class="btn btn-sm btn-outline-success" title="Set as Current"><i class="fas fa-check"></i></button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this period?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="period_id" value="<?php echo $p['period_id']; ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($periods)): ?>
                <tr><td colspan="5" class="text-center py-5"><p class="text-muted">No academic periods yet.</p></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
