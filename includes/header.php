<?php
/**
 * Layout Header — Opens HTML + Sidebar + Topbar
 *
 * REQUIRED variables to set before including:
 *   $pageTitle   = 'Dashboard';       // page title
 *   $currentPage = 'dashboard';       // active sidebar item key
 *
 * OPTIONAL:
 *   $breadcrumbs = [                  // breadcrumb trail
 *       ['label' => 'Home', 'url' => 'dashboard.php'],
 *       ['label' => 'Students']       // last item = current page (no url)
 *   ];
 *   $hideSearch = true;               // hide topbar search (default: shown)
 *   $isLoginPage = true;              // standalone login page (no sidebar/topbar)
 */

// Defaults
if (!isset($pageTitle))   $pageTitle   = 'SMS Campus';
if (!isset($currentPage)) $currentPage = '';
if (!isset($breadcrumbs))  $breadcrumbs = [];
if (!isset($hideSearch))   $hideSearch  = false;
if (!isset($isLoginPage)) $isLoginPage = false;

// Asset base path
$_scriptPath = $_SERVER['SCRIPT_NAME'];
if (strpos($_scriptPath, '/dashboards/') !== false) {
    $assetBase = '../assets/';
} elseif (strpos($_scriptPath, '/modules/') !== false) {
    $assetBase = '../../assets/';
} else {
    $assetBase = 'assets/';
}

// Include base path for sidebar.php
if (strpos($_scriptPath, '/dashboards/') !== false) {
    $includeBase = '../includes/';
} elseif (strpos($_scriptPath, '/modules/') !== false) {
    $includeBase = '../../includes/';
} else {
    $includeBase = 'includes/';
}

$userName   = $_SESSION['full_name'] ?? 'User';
$userRole   = $_SESSION['role'] ?? 'guest';
$nameParts  = explode(' ', $userName);
$_initials  = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $_initials .= strtoupper(substr(end($nameParts), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> — SMS Campus</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <link href="<?php echo $assetBase; ?>css/style.css" rel="stylesheet">
</head>
<body class="<?php echo $isLoginPage ? 'login-body' : ''; ?>">
<script>if(localStorage.getItem('darkMode')==='true')document.body.classList.add('dark-mode');</script>

<?php if (!$isLoginPage): ?>
    <!-- Sidebar -->
    <?php require_once $includeBase . 'sidebar.php'; ?>

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <?php if (!empty($breadcrumbs)): ?>
            <div class="breadcrumb-nav">
                <?php foreach ($breadcrumbs as $i => $crumb): ?>
                    <?php if ($i > 0): ?><span class="separator"><i class="fas fa-chevron-right"></i></span><?php endif; ?>
                    <?php if (isset($crumb['url'])): ?>
                        <a href="<?php echo $crumb['url']; ?>"><?php echo htmlspecialchars($crumb['label']); ?></a>
                    <?php else: ?>
                        <span class="current"><?php echo htmlspecialchars($crumb['label']); ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="topbar-right">
            <div class="d-none d-md-flex align-items-center me-3" style="font-size: 13px;">
                <?php
                $currentPeriodName = 'No Active Period';
                if (isset($pdo)) {
                    try {
                        $cpStmt = $pdo->query("SELECT name FROM academic_periods WHERE is_current = 1 LIMIT 1");
                        if ($cp = $cpStmt->fetch()) {
                            $currentPeriodName = $cp['name'];
                        }
                    } catch (PDOException $e) {}
                }
                ?>
                <span class="badge" style="background:var(--primary-light); color:var(--primary); padding: 5px 10px; border: 1px solid rgba(0,0,0,0.05);"><i class="fas fa-calendar-alt me-1"></i> <?php echo htmlspecialchars($currentPeriodName); ?></span>
            </div>
            
            <?php if (!$hideSearch): ?>
            <div class="search-box d-none d-md-block" style="width:220px;">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control form-control-sm" placeholder="Search...">
            </div>
            <?php endif; ?>
            
            <button class="topbar-icon-btn" id="darkModeToggle" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i>
            </button>

            <button class="topbar-icon-btn" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="notification-dot"></span>
            </button>

            <div class="dropdown">
                <div class="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar-sm"><?php echo $_initials; ?></div>
                    <div class="user-meta d-none d-sm-block">
                        <div class="name"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="role"><?php echo ucfirst($userRole); ?></div>
                    </div>
                    <i class="fas fa-chevron-down" style="font-size:10px; color:var(--text-muted);"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-user-circle"></i> My Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?php echo (strpos($_scriptPath, '/modules/') !== false) ? '../../logout.php' : ((strpos($_scriptPath, '/dashboards/') !== false) ? '../logout.php' : 'logout.php'); ?>">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content Wrapper -->
    <main class="main-content">

    <?php
    // Flash message display
    if (function_exists('getFlashMessage')) {
        $flash = getFlashMessage();
        if ($flash) {
            $alertType = ($flash['type'] === 'error') ? 'danger' : $flash['type'];
            echo '<div class="alert alert-' . $alertType . ' alert-dismissible fade show" role="alert">';
            echo '<i class="fas fa-' . ($alertType === 'success' ? 'check-circle' : ($alertType === 'danger' ? 'exclamation-triangle' : 'info-circle')) . '"></i>';
            echo htmlspecialchars($flash['message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
        }
    }
    ?>

<?php endif; ?>
