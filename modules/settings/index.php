<?php
/**
 * Settings Page (Admin Only)
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

// Gather system stats
$stats = [];
$stats['users']       = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['students']    = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$stats['courses']     = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$stats['enrollments'] = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();

// Check if table exists before querying
try {
    $stats['att_sessions'] = $pdo->query("SELECT COUNT(*) FROM attendance_sessions")->fetchColumn();
} catch (PDOException $e) { $stats['att_sessions'] = 'N/A'; }
try {
    $stats['marks_entries'] = $pdo->query("SELECT COUNT(*) FROM marks")->fetchColumn();
} catch (PDOException $e) { $stats['marks_entries'] = 'N/A'; }

$pageTitle   = 'Settings';
$currentPage = 'settings';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Settings']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>System Settings</h1><p>View system information and database statistics.</p></div>
</div>

<div class="row g-4">
    <!-- System Info -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-server me-2"></i>System Information</h6></div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted" style="width:40%;">PHP Version</td><td class="fw-600"><?php echo phpversion(); ?></td></tr>
                    <tr><td class="text-muted">Server Software</td><td class="fw-600"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td></tr>
                    <tr><td class="text-muted">Database Driver</td><td class="fw-600">MySQL (PDO)</td></tr>
                    <tr><td class="text-muted">MySQL Version</td><td class="fw-600"><?php echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></td></tr>
                    <tr><td class="text-muted">Document Root</td><td class="fw-600" style="word-break:break-all;"><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Database Stats -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-database me-2"></i>Database Statistics</h6></div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted" style="width:40%;">Total Users</td><td class="fw-600"><?php echo $stats['users']; ?></td></tr>
                    <tr><td class="text-muted">Students</td><td class="fw-600"><?php echo $stats['students']; ?></td></tr>
                    <tr><td class="text-muted">Courses</td><td class="fw-600"><?php echo $stats['courses']; ?></td></tr>
                    <tr><td class="text-muted">Enrollments</td><td class="fw-600"><?php echo $stats['enrollments']; ?></td></tr>
                    <tr><td class="text-muted">Attendance Sessions</td><td class="fw-600"><?php echo $stats['att_sessions']; ?></td></tr>
                    <tr><td class="text-muted">Mark Entries</td><td class="fw-600"><?php echo $stats['marks_entries']; ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h6></div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="../../modules/students/index.php" class="btn btn-outline-primary"><i class="fas fa-user-graduate me-1"></i> Manage Students</a>
            <a href="../../modules/courses/index.php" class="btn btn-outline-primary"><i class="fas fa-book-open me-1"></i> Manage Courses</a>
            <a href="../../modules/enrollments/index.php" class="btn btn-outline-primary"><i class="fas fa-clipboard-list me-1"></i> Manage Enrollments</a>
            <a href="../profile/index.php" class="btn btn-outline-secondary"><i class="fas fa-user-circle me-1"></i> My Profile</a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
