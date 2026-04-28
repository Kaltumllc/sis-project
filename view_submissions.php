<?php
include 'config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$assignment = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT a.*, c.course_name, c.course_code 
    FROM assignments a JOIN courses c ON a.course_id = c.course_id 
    WHERE a.assignment_id = $assignment_id
"));

if (!$assignment) {
    echo "<script>alert('Assignment not found.'); window.location='manage_assignments.php';</script>";
    exit();
}

$submissions = mysqli_query($conn, "
    SELECT s.*, st.full_name, st.email
    FROM submissions s
    JOIN students st ON s.student_id = st.student_id
    WHERE s.assignment_id = $assignment_id
    ORDER BY s.submission_date DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Submissions – SIS Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; background: #f4f7f6; }

        .sidebar { width: 250px; min-height: 100vh; background: #2c3e50; color: white; padding: 20px; flex-shrink: 0; }
        .sidebar h2 { font-size: 18px; margin-bottom: 10px; }
        .sidebar hr { border-color: rgba(255,255,255,0.15); margin: 10px 0 20px; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; font-size: 16px; }
        .sidebar a:hover { color: #3498db; }

        .main { flex: 1; padding: 40px; }

        .info-banner {
            background: white; border-radius: 12px; padding: 20px 26px;
            margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border-left: 5px solid #2563eb;
        }
        .info-banner h1 { font-size: 22px; color: #1e293b; }
        .info-banner p { font-size: 13px; color: #888; margin-top: 4px; }

        table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
        th { background: #2c3e50; color: white; padding: 13px 16px; text-align: left; font-size: 13px; }
        td { padding: 13px 16px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .status-submitted { background: #fef3c7; color: #d97706; }
        .status-graded    { background: #dcfce7; color: #16a34a; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        .back-link { display: inline-block; margin-bottom: 20px; color: #2563eb; text-decoration: none; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>SIS Admin</h2>
    <hr>
    <a href="admin_dash.php">🏠 Dashboard</a>
    <a href="manage_students.php">👥 Manage Students</a>
    <a href="manage_courses.php">📚 Course Listings</a>
    <a href="enroll_student.php">📝 Enrollment</a>
    <a href="manage_assignments.php" style="color:#3498db; font-weight:600;">📋 Assignments</a>
    <a href="manage_announcements.php">📢 Announcements</a>
    <br><br>
    <a href="logout.php" style="color:#e74c3c; font-weight:bold;">Logout</a>
</div>

<div class="main">
    <a href="manage_assignments.php" class="back-link">← Back to Assignments</a>

    <div class="info-banner">
        <h1>📂 <?php echo htmlspecialchars($assignment['title']); ?></h1>
        <p>
            <?php echo htmlspecialchars($assignment['course_code'] . ' – ' . $assignment['course_name']); ?>
            &nbsp;·&nbsp; Due: <?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?>
            &nbsp;·&nbsp; <?php echo mysqli_num_rows($submissions); ?> submission(s)
        </p>
    </div>

    <table>
        <tr>
            <th>Student Name</th>
            <th>Email</th>
            <th>Submitted At</th>
            <th>Status</th>
            <th>File</th>
        </tr>
        <?php if (mysqli_num_rows($submissions) > 0):
            mysqli_data_seek($submissions, 0);
            while ($sub = mysqli_fetch_assoc($submissions)): ?>
        <tr>
            <td><strong><?php echo htmlspecialchars($sub['full_name']); ?></strong></td>
            <td><?php echo htmlspecialchars($sub['email']); ?></td>
            <td><?php echo date('M j, Y g:i A', strtotime($sub['submission_date'])); ?></td>
            <td>
                <span class="status-badge status-<?php echo $sub['status']; ?>">
                    <?php echo ucfirst($sub['status']); ?>
                </span>
            </td>
            <td>
                <?php if ($sub['file_path']): ?>
                    <a href="<?php echo htmlspecialchars($sub['file_path']); ?>" target="_blank" 
                       style="color:#2563eb; text-decoration:none; font-size:13px;">
                        📥 Download
                    </a>
                <?php else: ?>
                    <span style="color:#aaa;">No file</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; else: ?>
        <tr>
            <td colspan="5" style="text-align:center; color:#aaa; padding:30px;">
                No submissions yet for this assignment.
            </td>
        </tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>