<?php
/**
 * Student Bulk Import (CSV) — Admin
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
requireRole('admin');

$errors   = [];
$results  = [];
$imported = 0;
$skipped  = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error.';
    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $errors[] = 'Only CSV files are allowed.';
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) { $errors[] = 'Cannot read the file.'; }
        else {
            // Read header row
            $header = fgetcsv($handle);
            if (!$header) { $errors[] = 'CSV file is empty.'; }
            else {
                $header = array_map('strtolower', array_map('trim', $header));
                $required = ['full_name', 'email', 'registration_no'];
                $missing = array_diff($required, $header);
                if (!empty($missing)) {
                    $errors[] = 'Missing required columns: ' . implode(', ', $missing);
                } else {
                    $nameIdx   = array_search('full_name', $header);
                    $emailIdx  = array_search('email', $header);
                    $regIdx    = array_search('registration_no', $header);
                    $deptIdx   = array_search('department', $header);
                    $yearIdx   = array_search('intake_year', $header);
                    $passIdx   = array_search('password', $header);

                    $row = 1;
                    try {
                        $pdo->beginTransaction();
                        while (($data = fgetcsv($handle)) !== false) {
                            $row++;
                            $name  = trim($data[$nameIdx] ?? '');
                            $email = trim($data[$emailIdx] ?? '');
                            $reg   = trim($data[$regIdx] ?? '');
                            $dept  = $deptIdx !== false ? trim($data[$deptIdx] ?? '') : '';
                            $year  = $yearIdx !== false ? (int)($data[$yearIdx] ?? date('Y')) : (int)date('Y');
                            $pass  = $passIdx !== false && !empty(trim($data[$passIdx] ?? '')) ? trim($data[$passIdx]) : 'student123';

                            if (empty($name) || empty($email) || empty($reg)) {
                                $results[] = ['row' => $row, 'status' => 'skipped', 'reason' => 'Missing required fields'];
                                $skipped++;
                                continue;
                            }
                            if (emailExists($pdo, $email)) {
                                $results[] = ['row' => $row, 'status' => 'skipped', 'reason' => "Email '$email' already exists"];
                                $skipped++;
                                continue;
                            }
                            if (registrationExists($pdo, $reg)) {
                                $results[] = ['row' => $row, 'status' => 'skipped', 'reason' => "Reg. No '$reg' already exists"];
                                $skipped++;
                                continue;
                            }

                            $hashed = password_hash($pass, PASSWORD_DEFAULT);
                            $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'student')")
                                ->execute([$name, $email, $hashed]);
                            $userId = $pdo->lastInsertId();
                            $pdo->prepare("INSERT INTO students (user_id, registration_no, department, intake_year, status) VALUES (?, ?, ?, ?, 'active')")
                                ->execute([$userId, $reg, $dept, $year]);
                            $results[] = ['row' => $row, 'status' => 'imported', 'reason' => "$name ($reg)"];
                            $imported++;
                        }
                        $pdo->commit();
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $errors[] = 'Database error on row ' . $row . ': ' . $e->getMessage();
                    }
                }
            }
            fclose($handle);
        }
    }
}

$pageTitle   = 'Bulk Import Students';
$currentPage = 'students';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../dashboards/admin_dashboard.php'],
    ['label' => 'Students', 'url' => '../students/index.php'],
    ['label' => 'Bulk Import']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>CSV Import</h1><p>Upload a CSV file to import multiple students at once.</p></div>
    <a href="../students/index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($imported > 0 || $skipped > 0): ?>
    <div class="alert alert-<?php echo $imported > 0 ? 'success' : 'warning'; ?>">
        <i class="fas fa-<?php echo $imported > 0 ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <strong><?php echo $imported; ?></strong> imported, <strong><?php echo $skipped; ?></strong> skipped.
    </div>
<?php endif; ?>

<!-- Upload Form -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-file-csv me-2"></i>Upload CSV File</h6></div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">CSV File <span class="required">*</span></label>
                <input type="file" class="form-control" name="csv_file" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i> Import</button>
        </form>
    </div>
</div>

<!-- CSV Format Guide -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>CSV Format</h6></div>
    <div class="card-body">
        <p>Your CSV file must have a <strong>header row</strong> with these columns:</p>
        <table class="table table-bordered table-sm">
            <thead class="table-light"><tr><th>Column</th><th>Required</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>full_name</code></td><td><span class="badge bg-danger">Required</span></td><td>Student's full name</td></tr>
                <tr><td><code>email</code></td><td><span class="badge bg-danger">Required</span></td><td>Login email (must be unique)</td></tr>
                <tr><td><code>registration_no</code></td><td><span class="badge bg-danger">Required</span></td><td>Registration number (must be unique)</td></tr>
                <tr><td><code>department</code></td><td><span class="badge bg-secondary">Optional</span></td><td>Department name</td></tr>
                <tr><td><code>intake_year</code></td><td><span class="badge bg-secondary">Optional</span></td><td>Year of intake (defaults to current)</td></tr>
                <tr><td><code>password</code></td><td><span class="badge bg-secondary">Optional</span></td><td>Password (defaults to <code>student123</code>)</td></tr>
            </tbody>
        </table>
        <div class="alert alert-info mb-0"><strong>Example:</strong><br>
            <code>full_name,email,registration_no,department,intake_year<br>
            John Doe,john@sms.com,REG2026001,Computer Science,2026<br>
            Jane Smith,jane@sms.com,REG2026002,Business,2026</code>
        </div>
    </div>
</div>

<!-- Import Results -->
<?php if (!empty($results)): ?>
<div class="card table-card">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-list me-2"></i>Import Results</h6></div>
    <div class="table-responsive">
        <table class="table table-striped table-sm mb-0">
            <thead><tr><th>Row</th><th>Status</th><th>Details</th></tr></thead>
            <tbody>
                <?php foreach ($results as $r): ?>
                <tr>
                    <td><?php echo $r['row']; ?></td>
                    <td><span class="badge <?php echo $r['status'] === 'imported' ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                    <td><?php echo e($r['reason']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
