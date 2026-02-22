<?php
/**
 * Announcements — Admin/Lecturer
 * Create and manage announcements
 */
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';
require_once '../../config/db.php';
if (!in_array($_SESSION['role'], ['admin', 'lecturer'])) { header('Location: ../../access_denied.php'); exit; }

$role = $_SESSION['role'];

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $title   = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $target  = $_POST['target_role'] ?? 'all';
        $pinned  = isset($_POST['is_pinned']) ? 1 : 0;

        if (!empty($title) && !empty($message)) {
            $pdo->prepare("INSERT INTO announcements (title, message, author_id, author_role, target_role, is_pinned) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$title, $message, $_SESSION['user_id'], $role, $target, $pinned]);
            
            // Send email notification
            $emailSql = "SELECT email FROM users";
            if ($target === 'student') $emailSql .= " WHERE role = 'student'";
            elseif ($target === 'lecturer') $emailSql .= " WHERE role = 'lecturer'";
            
            $stmt = $pdo->query($emailSql);
            $emails = [];
            while ($row = $stmt->fetch()) {
                if (!empty($row['email'])) $emails[] = $row['email'];
            }
            
            if (!empty($emails)) {
                $bcc = implode(', ', $emails);
                $to = "noreply@smscampus.local";
                $subject = "New Announcement: " . $title;
                $headers = "From: noreply@smscampus.local\r\n";
                $headers .= "Bcc: $bcc\r\n";
                @mail($to, $subject, $message, $headers);
            }
            
            redirectWithMessage('index.php', 'success', 'Announcement posted!');
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int)$_POST['announcement_id'];
        // Admin can delete any, lecturer only their own
        if ($role === 'admin') {
            $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?")->execute([$id]);
        } else {
            $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ? AND author_id = ?")->execute([$id, $_SESSION['user_id']]);
        }
        redirectWithMessage('index.php', 'success', 'Announcement deleted.');
    }
}

// Fetch announcements
$sql = "SELECT a.*, u.full_name as author_name FROM announcements a JOIN users u ON a.author_id = u.id";
if ($role === 'lecturer') {
    $sql .= " WHERE a.author_id = " . (int)$_SESSION['user_id'];
}
$sql .= " ORDER BY a.is_pinned DESC, a.created_at DESC";
$announcements = $pdo->query($sql)->fetchAll();

$pageTitle   = 'Announcements';
$currentPage = 'announcements';
$dashUrl = $role === 'admin' ? '../../dashboards/admin_dashboard.php' : '../../dashboards/lecturer_dashboard.php';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => $dashUrl],
    ['label' => 'Announcements']
];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div><h1>Announcements</h1><p>Post notices for students and staff.</p></div>
</div>

<?php displayFlashMessage(); ?>

<!-- Create Form -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Post New Announcement</h6></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Title <span class="required">*</span></label>
                    <input type="text" class="form-control" name="title" required maxlength="200">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Visible To</label>
                    <select class="form-select" name="target_role">
                        <option value="all">Everyone</option>
                        <option value="student">Students Only</option>
                        <option value="lecturer">Lecturers Only</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Message <span class="required">*</span></label>
                    <textarea class="form-control" name="message" rows="3" required></textarea>
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_pinned" id="pinCheck">
                        <label class="form-check-label" for="pinCheck"><i class="fas fa-thumbtack me-1"></i> Pin to top</label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Post</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Announcements List -->
<?php if (count($announcements) > 0): ?>
    <?php foreach ($announcements as $a): ?>
    <div class="card mb-3 <?php echo $a['is_pinned'] ? 'border-warning' : ''; ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-1">
                        <?php if ($a['is_pinned']): ?><i class="fas fa-thumbtack text-warning me-1"></i><?php endif; ?>
                        <?php echo e($a['title']); ?>
                    </h6>
                    <small class="text-muted">
                        By <strong><?php echo e($a['author_name']); ?></strong> (<?php echo ucfirst($a['author_role']); ?>)
                        · <?php echo date('M d, Y g:i A', strtotime($a['created_at'])); ?>
                        · <span class="badge bg-secondary"><?php echo $a['target_role'] === 'all' ? 'Everyone' : ucfirst($a['target_role']) . 's'; ?></span>
                    </small>
                </div>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="announcement_id" value="<?php echo $a['announcement_id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
            </div>
            <p class="mt-2 mb-0"><?php echo nl2br(e($a['message'])); ?></p>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="card"><div class="card-body text-center py-5">
        <i class="fas fa-bullhorn fa-3x mb-3 d-block" style="opacity:0.2;"></i>
        <p class="text-muted">No announcements yet.</p>
    </div></div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
