<?php
/**
 * Sidebar Component — Role-Based Navigation
 * 
 * REQUIRED: Set $currentPage before including this file:
 *   $currentPage = 'dashboard';
 * 
 * This file reads $_SESSION['role'] to show the correct menu items.
 * The $currentPage variable highlights the active menu item.
 */

$role = $_SESSION['role'] ?? 'student';
$userName = $_SESSION['full_name'] ?? 'User';

// Get user initials for avatar
$nameParts = explode(' ', $userName);
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $initials .= strtoupper(substr(end($nameParts), 0, 1));
}

// Helper: check if menu item is active
function isActive($page, $current) {
    return $page === $current ? 'active' : '';
}

// Compute base paths depending on where the including file is
// Dashboard pages are in /dashboards/, module pages are in /modules/xxx/
$inDashboard = (strpos($_SERVER['SCRIPT_NAME'], '/dashboards/') !== false);
$inModules   = (strpos($_SERVER['SCRIPT_NAME'], '/modules/') !== false);

if ($inDashboard) {
    $dashBase = '';
    $modBase  = '../modules/';
    $rootBase = '../';
} elseif ($inModules) {
    $dashBase = '../../dashboards/';
    $modBase  = '../';  // relative within modules
    $rootBase = '../../';
} else {
    $dashBase = 'dashboards/';
    $modBase  = 'modules/';
    $rootBase = '';
}
?>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="brand-text">
            SMS Campus
            <small>Management System</small>
        </div>
    </div>
    
    <nav class="sidebar-menu">

        <?php if ($role === 'admin'): ?>
        <!-- ====== ADMIN MENU ====== -->
        <div class="menu-label">Main</div>
        <a href="<?php echo $dashBase; ?>admin_dashboard.php" class="menu-item <?php echo isActive('dashboard', $currentPage); ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>

        <div class="menu-label">Academic</div>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/students/') !== false ? 'index.php' : ($inModules ? '../students/index.php' : $modBase . 'students/index.php'); ?>" 
           class="menu-item <?php echo isActive('students', $currentPage); ?>">
            <i class="fas fa-user-graduate"></i> Students
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/lecturers/') !== false ? 'index.php' : ($inModules ? '../lecturers/index.php' : $modBase . 'lecturers/index.php'); ?>"
           class="menu-item <?php echo isActive('lecturers', $currentPage); ?>">
            <i class="fas fa-chalkboard-teacher"></i> Lecturers
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/courses/') !== false ? 'index.php' : ($inModules ? '../courses/index.php' : $modBase . 'courses/index.php'); ?>"
           class="menu-item <?php echo isActive('courses', $currentPage); ?>">
            <i class="fas fa-book-open"></i> Courses
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/enrollments/') !== false ? 'index.php' : ($inModules ? '../enrollments/index.php' : $modBase . 'enrollments/index.php'); ?>"
           class="menu-item <?php echo isActive('enrollments', $currentPage); ?>">
            <i class="fas fa-clipboard-list"></i> Enrollments
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/assignments/') !== false ? 'index.php' : ($inModules ? '../assignments/index.php' : $modBase . 'assignments/index.php'); ?>"
           class="menu-item <?php echo isActive('assignments', $currentPage); ?>">
            <i class="fas fa-link"></i> Course Assignments
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/timetable/') !== false ? 'index.php' : ($inModules ? '../timetable/index.php' : $modBase . 'timetable/index.php'); ?>"
           class="menu-item <?php echo isActive('timetable', $currentPage); ?>">
            <i class="fas fa-calendar-alt"></i> Timetable
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/announcements/') !== false ? 'index.php' : ($inModules ? '../announcements/index.php' : $modBase . 'announcements/index.php'); ?>"
           class="menu-item <?php echo isActive('announcements', $currentPage); ?>">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>

        <div class="menu-label">Reports</div>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/reports/') !== false ? 'attendance_report.php' : ($inModules ? '../reports/attendance_report.php' : $modBase . 'reports/attendance_report.php'); ?>"
           class="menu-item <?php echo isActive('att_reports', $currentPage); ?>">
            <i class="fas fa-chart-bar"></i> Attendance Reports
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/reports/') !== false ? 'marks_report.php' : ($inModules ? '../reports/marks_report.php' : $modBase . 'reports/marks_report.php'); ?>"
           class="menu-item <?php echo isActive('mark_reports', $currentPage); ?>">
            <i class="fas fa-chart-pie"></i> Marks Reports
        </a>

        <div class="menu-label">System</div>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/users/') !== false ? 'index.php' : ($inModules ? '../users/index.php' : $modBase . 'users/index.php'); ?>"
           class="menu-item <?php echo isActive('users', $currentPage); ?>">
            <i class="fas fa-users-cog"></i> Users & Roles
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/settings/') !== false ? 'grade_config.php' : ($inModules ? '../settings/grade_config.php' : $modBase . 'settings/grade_config.php'); ?>"
           class="menu-item <?php echo isActive('grade_config', $currentPage); ?>">
            <i class="fas fa-sliders-h"></i> Grade Config
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/academic_periods/') !== false ? 'index.php' : ($inModules ? '../academic_periods/index.php' : $modBase . 'academic_periods/index.php'); ?>"
           class="menu-item <?php echo isActive('academic_periods', $currentPage); ?>">
            <i class="fas fa-calendar-week"></i> Academic Periods
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/activity_log/') !== false ? 'index.php' : ($inModules ? '../activity_log/index.php' : $modBase . 'activity_log/index.php'); ?>"
           class="menu-item <?php echo isActive('activity_log', $currentPage); ?>">
            <i class="fas fa-history"></i> Activity Log
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/profile/') !== false ? 'index.php' : ($inModules ? '../profile/index.php' : $modBase . 'profile/index.php'); ?>"
           class="menu-item <?php echo isActive('profile', $currentPage); ?>">
            <i class="fas fa-user-circle"></i> My Profile
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/settings/') !== false ? 'index.php' : ($inModules ? '../settings/index.php' : $modBase . 'settings/index.php'); ?>"
           class="menu-item <?php echo isActive('settings', $currentPage); ?>">
            <i class="fas fa-cog"></i> Settings
        </a>

        <?php elseif ($role === 'lecturer'): ?>
        <!-- ====== LECTURER MENU ====== -->
        <div class="menu-label">Main</div>
        <a href="<?php echo $dashBase; ?>lecturer_dashboard.php" class="menu-item <?php echo isActive('dashboard', $currentPage); ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>

        <div class="menu-label">Teaching</div>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/attendance/') !== false ? 'index.php' : ($inModules ? '../attendance/index.php' : $modBase . 'attendance/index.php'); ?>"
           class="menu-item <?php echo isActive('attendance', $currentPage); ?>">
            <i class="fas fa-clipboard-check"></i> Attendance
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/marks/') !== false ? 'index.php' : ($inModules ? '../marks/index.php' : $modBase . 'marks/index.php'); ?>"
           class="menu-item <?php echo isActive('marks', $currentPage); ?>">
            <i class="fas fa-pen-alt"></i> Marks Entry
        </a>

        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/announcements/') !== false ? 'index.php' : ($inModules ? '../announcements/index.php' : $modBase . 'announcements/index.php'); ?>"
           class="menu-item <?php echo isActive('announcements', $currentPage); ?>">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/timetable/') !== false ? 'index.php' : ($inModules ? '../timetable/index.php' : $modBase . 'timetable/index.php'); ?>"
           class="menu-item <?php echo isActive('timetable', $currentPage); ?>">
            <i class="fas fa-calendar-alt"></i> Timetable
        </a>

        <div class="menu-label">Account</div>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/profile/') !== false ? 'index.php' : ($inModules ? '../profile/index.php' : $modBase . 'profile/index.php'); ?>"
           class="menu-item <?php echo isActive('profile', $currentPage); ?>">
            <i class="fas fa-user-circle"></i> My Profile
        </a>

        <?php else: ?>
        <!-- ====== STUDENT MENU ====== -->
        <div class="menu-label">Main</div>
        <a href="<?php echo $dashBase; ?>student_dashboard.php" class="menu-item <?php echo isActive('dashboard', $currentPage); ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>

        <div class="menu-label">Academics</div>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/marks/') !== false ? 'student_view.php' : ($inModules ? '../marks/student_view.php' : $modBase . 'marks/student_view.php'); ?>"
           class="menu-item <?php echo isActive('my_courses', $currentPage); ?>">
            <i class="fas fa-book-open"></i> My Courses
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/attendance/') !== false ? 'student_view.php' : ($inModules ? '../attendance/student_view.php' : $modBase . 'attendance/student_view.php'); ?>"
           class="menu-item <?php echo isActive('my_attendance', $currentPage); ?>">
            <i class="fas fa-calendar-check"></i> My Attendance
        </a>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/marks/') !== false ? 'student_view.php' : ($inModules ? '../marks/student_view.php' : $modBase . 'marks/student_view.php'); ?>"
           class="menu-item <?php echo isActive('my_results', $currentPage); ?>">
            <i class="fas fa-poll"></i> My Results
        </a>

        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/timetable/') !== false ? 'index.php' : ($inModules ? '../timetable/index.php' : $modBase . 'timetable/index.php'); ?>"
           class="menu-item <?php echo isActive('timetable', $currentPage); ?>">
            <i class="fas fa-calendar-alt"></i> Timetable
        </a>

        <div class="menu-label">Account</div>
        <a href="<?php echo $inModules && strpos($_SERVER['SCRIPT_NAME'], '/profile/') !== false ? 'index.php' : ($inModules ? '../profile/index.php' : $modBase . 'profile/index.php'); ?>"
           class="menu-item <?php echo isActive('profile', $currentPage); ?>">
            <i class="fas fa-user-circle"></i> My Profile
        </a>
        <?php endif; ?>

    </nav>

    <!-- Sidebar Footer (user info) -->
    <div class="sidebar-footer">
        <div class="avatar"><?php echo $initials; ?></div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
            <div class="user-role"><?php echo ucfirst($role); ?></div>
        </div>
        <a href="<?php echo $rootBase; ?>logout.php" title="Logout" style="color:rgba(255,255,255,0.5);">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</aside>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
