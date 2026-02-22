<?php
/**
 * Export Transcript to PDF
 */
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$studentId = (int)($_GET['student_id'] ?? 0);
if ($studentId <= 0) die("Invalid Student ID.");

// Security check
$role = $_SESSION['role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

if ($role === 'student') {
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->execute([$userId]);
    $myStudent = $stmt->fetch();
    if (!$myStudent || $myStudent['student_id'] != $studentId) {
        die("Unauthorized access to this transcript.");
    }
} elseif (!in_array($role, ['admin', 'lecturer'])) {
    die("Unauthorized.");
}

// Fetch student info
$stmt = $pdo->prepare("SELECT s.*, u.full_name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.student_id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();
if (!$student) die("Student not found.");

// Fetch grades
$stmt = $pdo->prepare("
    SELECT c.course_code, c.course_name, c.credits, fr.total_score, fr.grade, fr.gpa_points
    FROM enrollments e
    INNER JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN final_results fr ON e.student_id = fr.student_id AND e.course_id = fr.course_id
    WHERE e.student_id = ?
    ORDER BY c.course_code ASC
");
$stmt->execute([$studentId]);
$courses = $stmt->fetchAll();

$totalGPAPoints = $totalCredits = 0;
foreach ($courses as $c) {
    if ($c['grade'] !== null) {
        $totalGPAPoints += $c['gpa_points'] * $c['credits'];
        $totalCredits += $c['credits'];
    }
}
$overallGPA = ($totalCredits > 0) ? round($totalGPAPoints / $totalCredits, 2) : 0.00;

// Build HTML
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: "Helvetica", "Arial", sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #4361ee; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; color: #1a1d29; margin: 0; }
        .subtitle { font-size: 14px; color: #6c757d; margin: 5px 0 0 0; }
        
        .student-info { margin-bottom: 30px; }
        .student-info table { width: 100%; border-collapse: collapse; }
        .student-info td { padding: 4px; }
        .student-info .label { font-weight: bold; width: 120px; color: #6c757d; }
        
        .results-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .results-table th { background-color: #f0f2f5; padding: 10px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .results-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        
        .summary { margin-top: 30px; float: right; width: 300px; background: #eef1fd; padding: 15px; border-radius: 6px; }
        .summary table { width: 100%; }
        .summary td { padding: 5px; }
        .summary .total { font-size: 18px; font-weight: bold; color: #4361ee; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #a0aec0; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">SMS Campus</h1>
        <p class="subtitle">Official Academic Transcript</p>
    </div>
    
    <div class="student-info">
        <table>
            <tr><td class="label">Name:</td><td>' . htmlspecialchars($student['full_name']) . '</td><td class="label">Reg. Number:</td><td>' . htmlspecialchars($student['registration_no']) . '</td></tr>
            <tr><td class="label">Email:</td><td>' . htmlspecialchars($student['email']) . '</td><td class="label">Date of Print:</td><td>' . date('F j, Y') . '</td></tr>
        </table>
    </div>
    
    <table class="results-table">
        <thead>
            <tr><th>Course Code</th><th>Course Name</th><th>Credits</th><th>Score</th><th>Grade</th></tr>
        </thead>
        <tbody>';

if (empty($courses)) {
    $html .= '<tr><td colspan="5" style="text-align:center;">No enrolled courses found.</td></tr>';
} else {
    foreach ($courses as $c) {
        $score = $c['total_score'] !== null ? number_format($c['total_score'], 1) . '%' : 'N/A';
        $grade = $c['grade'] ?? 'Pending';
        $html .= '<tr>
            <td>' . htmlspecialchars($c['course_code']) . '</td>
            <td>' . htmlspecialchars($c['course_name']) . '</td>
            <td>' . $c['credits'] . '</td>
            <td>' . $score . '</td>
            <td><strong>' . $grade . '</strong></td>
        </tr>';
    }
}

$html .= '
        </tbody>
    </table>
    
    <div class="summary">
        <table>
            <tr><td style="font-weight:bold;">Total Credits Earned:</td><td style="text-align:right;">' . $totalCredits . '</td></tr>
            <tr><td style="font-weight:bold;">Cumulative GPA:</td><td class="total" style="text-align:right;">' . number_format($overallGPA, 2) . '</td></tr>
        </table>
    </div>
    
    <div style="clear:both;"></div>
    
    <div class="footer">
        Generated by Student Management System on ' . date('Y-m-d H:i:s') . '
    </div>
</body>
</html>';

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream('Transcript_' . $student['registration_no'] . '.pdf', ["Attachment" => true]);
