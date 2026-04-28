<?php
include 'config.php';
include 'gpa_helper.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') { header("Location: index.php"); exit(); }

$email = $_SESSION['username'];
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE email='" . mysqli_real_escape_string($conn, $email) . "'"));
$sid = $student['student_id'];
$gpa = calculateGPA($conn, $sid);
$gpa_label = getGPALabel((float)$gpa);

$grades = mysqli_query($conn, "
    SELECT c.course_code, c.course_name, g.grade, g.semester
    FROM grades g JOIN courses c ON g.course_id = c.course_id
    WHERE g.student_id = $sid ORDER BY g.semester, c.course_code
");

$grade_points = ['A'=>4.0,'A-'=>3.7,'B+'=>3.3,'B'=>3.0,'B-'=>2.7,'C+'=>2.3,'C'=>2.0,'C-'=>1.7,'D+'=>1.3,'D'=>1.0,'F'=>0.0];
$today = date('F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Transcript — <?php echo htmlspecialchars($student['full_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f0f4f8; color: #1e293b; }

        .no-print { background: #1e293b; padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; }
        .no-print a { color: #94a3b8; text-decoration: none; font-size: 14px; }
        .no-print a:hover { color: white; }
        .btn-print { padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }

        .transcript {
            max-width: 760px; margin: 32px auto; background: white;
            border-radius: 8px; box-shadow: 0 4px 24px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* Header */
        .t-header { background: #0f172a; color: white; padding: 40px 48px; text-align: center; }
        .t-institution { font-size: 13px; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,0.5); margin-bottom: 8px; }
        .t-title { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 4px; }
        .t-subtitle { font-size: 14px; color: rgba(255,255,255,0.5); }
        .t-line { width: 60px; height: 3px; background: linear-gradient(90deg, #3b82f6, #06b6d4); margin: 20px auto; border-radius: 2px; }

        /* Student Info */
        .t-info { padding: 32px 48px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; border-bottom: 2px solid #e2e8f0; background: #f8fafc; }
        .info-group label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; display: block; margin-bottom: 4px; }
        .info-group span { font-size: 15px; font-weight: 600; color: #0f172a; }

        /* Grades Table */
        .t-body { padding: 32px 48px; }
        .section-title { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.6px; color: #94a3b8; padding: 10px 0; text-align: left; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
        td { padding: 14px 0; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
        tr:last-child td { border-bottom: none; }
        .grade-cell { font-weight: 700; font-size: 15px; }
        .grade-A { color: #166534; }
        .grade-B { color: #1e40af; }
        .grade-C { color: #92400e; }
        .grade-D, .grade-F { color: #991b1b; }

        /* GPA Summary */
        .t-summary { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px 32px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .gpa-display { text-align: center; }
        .gpa-big { font-family: 'Syne', sans-serif; font-size: 48px; font-weight: 800; color: #3b82f6; line-height: 1; }
        .gpa-label { font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px; margin-top: 4px; }
        .standing { text-align: center; }
        .standing-val { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: #0f172a; }
        .standing-label { font-size: 12px; color: #94a3b8; margin-top: 4px; }
        .courses-stat { text-align: center; }

        /* Footer */
        .t-footer { padding: 24px 48px; border-top: 2px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-end; }
        .footer-left { font-size: 12px; color: #94a3b8; line-height: 1.6; }
        .footer-seal { text-align: right; }
        .seal { width: 60px; height: 60px; border-radius: 50%; background: #0f172a; display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 20px; color: white; margin-left: auto; margin-bottom: 6px; }
        .footer-note { font-size: 11px; color: #94a3b8; }

        .empty-msg { text-align: center; color: #94a3b8; padding: 24px; font-size: 14px; }

        @media print {
            body { background: white; }
            .no-print { display: none; }
            .transcript { margin: 0; box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="student_profile.php">← Back to Profile</a>
    <button class="btn-print" onclick="window.print()">🖨️ Print Transcript</button>
</div>

<div class="transcript">

    <!-- Header -->
    <div class="t-header">
        <div class="t-institution">University of Massachusetts Lowell</div>
        <div class="t-title">Official Academic Transcript</div>
        <div class="t-subtitle">Student Information System · Spring 2026</div>
        <div class="t-line"></div>
    </div>

    <!-- Student Info -->
    <div class="t-info">
        <div class="info-group">
            <label>Student Name</label>
            <span><?php echo htmlspecialchars($student['full_name']); ?></span>
        </div>
        <div class="info-group">
            <label>Student ID</label>
            <span>#<?php echo $sid; ?></span>
        </div>
        <div class="info-group">
            <label>Email Address</label>
            <span><?php echo htmlspecialchars($student['email']); ?></span>
        </div>
        <div class="info-group">
            <label>Date of Issue</label>
            <span><?php echo $today; ?></span>
        </div>
        <div class="info-group">
            <label>Program</label>
            <span>Information Technology</span>
        </div>
        <div class="info-group">
            <label>Academic Year</label>
            <span>Spring 2026</span>
        </div>
    </div>

    <!-- Grades -->
    <div class="t-body">
        <div class="section-title">Academic Record</div>

        <?php $count = mysqli_num_rows($grades); mysqli_data_seek($grades, 0); ?>
        <?php if ($count > 0): ?>
        <table>
            <tr>
                <th>Course Code</th>
                <th>Course Name</th>
                <th>Semester</th>
                <th>Grade</th>
                <th>Grade Points</th>
            </tr>
            <?php while ($g = mysqli_fetch_assoc($grades)):
                $grade = strtoupper(trim($g['grade']));
                $pts = isset($grade_points[$grade]) ? $grade_points[$grade] : '—';
                $letter = $grade[0];
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($g['course_code']); ?></strong></td>
                <td><?php echo htmlspecialchars($g['course_name']); ?></td>
                <td><?php echo htmlspecialchars($g['semester']); ?></td>
                <td class="grade-cell grade-<?php echo $letter; ?>"><?php echo $grade; ?></td>
                <td><?php echo $pts; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
            <div class="empty-msg">No grades on record for this student.</div>
        <?php endif; ?>

        <!-- GPA Summary -->
        <div class="t-summary">
            <div class="gpa-display">
                <div class="gpa-big"><?php echo $gpa; ?></div>
                <div class="gpa-label">Cumulative GPA / 4.0</div>
            </div>
            <div style="width:1px; height:60px; background:#e2e8f0;"></div>
            <div class="standing">
                <div class="standing-val"><?php echo $gpa_label; ?></div>
                <div class="standing-label">Academic Standing</div>
            </div>
            <div style="width:1px; height:60px; background:#e2e8f0;"></div>
            <div class="courses-stat">
                <div class="standing-val"><?php echo $count; ?></div>
                <div class="standing-label">Courses Completed</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="t-footer">
        <div class="footer-left">
            This is an official transcript generated by the UMass Lowell<br>
            Student Information System. Generated on <?php echo $today; ?>.<br>
            For verification, contact the Office of the Registrar.
        </div>
        <div class="footer-seal">
            <div class="seal">SIS</div>
            <div class="footer-note">Official Document</div>
        </div>
    </div>

</div>
</body>
</html>
