<?php
/**
 * Admin Dashboard — KPI Cards + Charts + Recent Activity
 */
require_once '../includes/auth.php';
require_once '../includes/helpers.php';
require_once '../config/db.php';
requireRole('admin');

// ===== STATS =====
try {
    $totalUsers     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalStudents  = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $totalLecturers = $pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn();
    $totalCourses   = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $totalEnrollments = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
} catch (PDOException $e) {
    $totalUsers = $totalStudents = $totalLecturers = $totalCourses = $totalEnrollments = 0;
}

// ===== ENROLLMENT TREND (Current Year) =====
$currentYear = date('Y');
$enrollmentData = array_fill(1, 12, 0); // 1 to 12
try {
    $stmt = $pdo->prepare("
        SELECT MONTH(enrolled_at) as month, COUNT(*) as count 
        FROM enrollments 
        WHERE YEAR(enrolled_at) = ? 
        GROUP BY MONTH(enrolled_at)
    ");
    $stmt->execute([$currentYear]);
    while ($row = $stmt->fetch()) {
        $enrollmentData[(int)$row['month']] = (int)$row['count'];
    }
} catch (PDOException $e) {
    // keep zeros
}
$enrollmentDataValues = implode(', ', array_values($enrollmentData));

// ===== RECENT STUDENTS =====
try {
    $stmt = $pdo->query("
        SELECT s.student_id, s.registration_no, u.full_name, u.email, s.created_at
        FROM students s INNER JOIN users u ON s.user_id = u.id
        ORDER BY s.student_id DESC LIMIT 5
    ");
    $recentStudents = $stmt->fetchAll();
} catch (PDOException $e) {
    $recentStudents = [];
}

// Layout
$pageTitle   = 'Admin Dashboard';
$currentPage = 'dashboard';
$breadcrumbs = [['label' => 'Dashboard']];
require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars(getUserName()); ?>! Here's your overview.</p>
    </div>
    <a href="../modules/students/create.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Add Student
    </a>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-light"><i class="fas fa-user-graduate"></i></div>
            <div class="stat-details">
                <div class="stat-label">Total Students</div>
                <div class="stat-value"><?php echo $totalStudents; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-light"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="stat-details">
                <div class="stat-label">Total Lecturers</div>
                <div class="stat-value"><?php echo $totalLecturers; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-light"><i class="fas fa-book-open"></i></div>
            <div class="stat-details">
                <div class="stat-label">Total Courses</div>
                <div class="stat-value"><?php echo $totalCourses; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-info-light"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-details">
                <div class="stat-label">Enrollments</div>
                <div class="stat-value"><?php echo $totalEnrollments; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-chart-area me-2"></i>Student Enrollment Trend</h6>
            </div>
            <div class="chart-container" style="height:300px;">
                <canvas id="enrollmentChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-chart-pie me-2"></i>Users by Role</h6>
            </div>
            <div class="chart-container" style="height:300px;">
                <canvas id="rolesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Students -->
<div class="card table-card">
    <div class="card-header">
        <h6><i class="fas fa-clock me-2"></i>Recent Students</h6>
        <a href="../modules/students/index.php" class="btn btn-sm btn-light">View All <i class="fas fa-arrow-right ms-1"></i></a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Reg. No</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentStudents)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No students found.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentStudents as $s): ?>
                    <tr>
                        <td class="fw-600"><?php echo htmlspecialchars($s['full_name']); ?></td>
                        <td><code><?php echo htmlspecialchars($s['registration_no']); ?></code></td>
                        <td><?php echo htmlspecialchars($s['email']); ?></td>
                        <td><?php echo isset($s['created_at']) ? date('M d, Y', strtotime($s['created_at'])) : '—'; ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="../modules/students/view.php?id=<?php echo $s['student_id']; ?>" class="btn btn-sm btn-light" title="View"><i class="fas fa-eye"></i></a>
                                <a href="../modules/students/edit.php?id=<?php echo $s['student_id']; ?>" class="btn btn-sm btn-light" title="Edit"><i class="fas fa-pen"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
// Enrollment Trend (sample data — replace with PHP-generated data later)
SMS_CHARTS.lineChart('enrollmentChart', {
    labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    datasets: [{
        label: 'Enrollments',
        data: [12, 19, 14, 25, 22, 30, 28, 35, 40, 38, 42, 50],
        color: 'primary'
    }]
});

// Roles Doughnut
SMS_CHARTS.doughnutChart('rolesChart', {
    labels: ['Students', 'Lecturers', 'Admins'],
    data: [<?php echo $totalStudents; ?>, <?php echo $totalLecturers; ?>, 1],
    colors: ['primary', 'success', 'warning']
});
</script>
JS;

// Fix: The heredoc above won't interpolate PHP. Use a regular string instead.
$extraScripts = '<script>
SMS_CHARTS.lineChart("enrollmentChart", {
    labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
    datasets: [{
        label: "Enrollments (' . $currentYear . ')",
        data: [' . $enrollmentDataValues . '],
        color: "primary"
    }]
});

SMS_CHARTS.doughnutChart("rolesChart", {
    labels: ["Students", "Lecturers", "Admins"],
    data: [' . (int)$totalStudents . ', ' . (int)$totalLecturers . ', 1],
    colors: ["primary", "success", "warning"]
});
</script>';

require_once '../includes/footer.php';
?>
