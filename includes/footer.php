<?php
/**
 * Layout Footer — Closes wrappers + loads scripts
 * Include this at the bottom of every page.
 *
 * OPTIONAL: Set $extraScripts before including to add page-specific JS:
 *   $extraScripts = '<script src="some.js"></script>';
 */
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
?>

<?php if (!$isLoginPage): ?>
    </main><!-- /.main-content -->
<?php endif; ?>

<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo $assetBase; ?>js/app.js"></script>

<?php if (isset($extraScripts)) echo $extraScripts; ?>

</body>
</html>
