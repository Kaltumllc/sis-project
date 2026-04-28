<?php
include 'config.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grade'])) {
    $student_id = (int)$_POST['student_id'];
    $course_id  = (int)$_POST['course_id'];
    $grade      = mysqli_real_escape_string($conn, $_POST['grade']);
    $semester   = mysqli_real_escape_string($conn, $_POST['semester']);

    $check = mysqli_query($conn, "SELECT grade_id FROM grades WHERE student_id=$student_id AND course_id=$course_id AND semester='$semester'");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        mysqli_query($conn, "UPDATE grades SET grade='$grade' WHERE grade_id=" . $row['grade_id']);
        $msg = "Grade updated successfully!";
    } else {
        mysqli_query($conn, "INSERT INTO grades (student_id, course_id, grade, semester) VALUES ($student_id, $course_id, '$grade', '$semester')");
        $msg = "Grade saved successfully!";
    }
}

// Fetch all enrollments with student and course info
$enrollments = mysqli_query($conn, "
    SELECT e.enrollment_id, s.student_id, s.full_name, s.email,
           c.course_id, c.course_code, c.course_name,
           g.grade, g.semester
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN grades g ON g.student_id = s.student_id AND g.course_id = c.course_id AND g.semester = 'Spring 2026'
    ORDER BY s.full_name, c.course_code
");

$grade_options = ['A','A-','B+','B','B-','C+','C','C-','D+','D','F'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grade Entry — SIS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --bg:#f0f4f8; --white:#fff; --navy:#0f172a; --blue:#3b82f6; --blue-light:#eff6ff; --green:#10b981; --text:#1e293b; --muted:#64748b; --border:#e2e8f0; --sidebar-w:260px; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

        .sidebar { width:var(--sidebar-w); background:var(--navy); color:white; display:flex; flex-direction:column; position:fixed; top:0; left:0; height:100vh; z-index:100; }
        .sidebar-brand { padding:28px 24px 20px; border-bottom:1px solid rgba(255,255,255,0.08); }
        .brand-logo { display:flex; align-items:center; gap:10px; margin-bottom:4px; }
        .brand-icon { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,var(--blue),#06b6d4); display:flex; align-items:center; justify-content:center; font-family:'Syne',sans-serif; font-weight:800; font-size:16px; }
        .brand-name { font-family:'Syne',sans-serif; font-size:16px; font-weight:700; }
        .brand-role { font-size:11px; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.8px; margin-top:2px; }
        .sidebar-section { padding:20px 16px 8px; }
        .sidebar-label { font-size:10px; text-transform:uppercase; letter-spacing:1px; color:rgba(255,255,255,0.3); padding:0 8px; margin-bottom:6px; }
        .nav-item { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; color:rgba(255,255,255,0.6); text-decoration:none; font-size:14px; font-weight:500; margin-bottom:2px; transition:all 0.2s; }
        .nav-item:hover { background:rgba(255,255,255,0.08); color:white; }
        .nav-item.active { background:rgba(59,130,246,0.2); color:var(--blue); }
        .nav-item .icon { font-size:16px; width:20px; text-align:center; }
        .sidebar-bottom { margin-top:auto; padding:16px; border-top:1px solid rgba(255,255,255,0.08); }
        .logout-btn { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; color:#f87171; text-decoration:none; font-size:14px; transition:background 0.2s; }
        .logout-btn:hover { background:rgba(239,68,68,0.1); }

        .main { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; }
        .topbar { background:white; border-bottom:1px solid var(--border); padding:16px 32px; display:flex; align-items:center; justify-content:space-between; }
        .topbar-title { font-family:'Syne',sans-serif; font-size:20px; font-weight:700; }

        .content { padding:32px; }

        .alert { padding:14px 20px; border-radius:12px; margin-bottom:24px; font-size:14px; font-weight:500; background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }

        .card { background:white; border-radius:16px; border:1px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden; }
        .card-header { padding:20px 24px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
        .card-title { font-family:'Syne',sans-serif; font-size:16px; font-weight:700; }
        .card-sub { font-size:13px; color:var(--muted); margin-top:2px; }

        table { width:100%; border-collapse:collapse; }
        th { font-size:11px; text-transform:uppercase; letter-spacing:0.6px; color:var(--muted); padding:12px 20px; text-align:left; border-bottom:1px solid var(--border); font-weight:600; background:#f8fafc; }
        td { padding:14px 20px; font-size:14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#fafafa; }

        .student-name { font-weight:600; }
        .student-email { font-size:12px; color:var(--muted); margin-top:2px; }
        .course-code { display:inline-block; background:var(--blue-light); color:var(--blue); padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }

        .grade-form { display:flex; align-items:center; gap:8px; }
        select { padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px; font-family:'DM Sans',sans-serif; background:white; outline:none; cursor:pointer; }
        select:focus { border-color:var(--blue); }
        .btn-save { padding:8px 16px; background:var(--blue); color:white; border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; transition:background 0.2s; }
        .btn-save:hover { background:#2563eb; }

        .grade-badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:13px; font-weight:700; }
        .grade-A { background:#dcfce7; color:#166534; }
        .grade-B { background:#dbeafe; color:#1e40af; }
        .grade-C { background:#fef3c7; color:#92400e; }
        .grade-D, .grade-F { background:#fee2e2; color:#991b1b; }
        .grade-none { color:var(--muted); font-size:12px; font-style:italic; }

        @media(max-width:768px){
            .sidebar{display:none;}
            .main{margin-left:0;}
            .content{padding:16px;}
            table{font-size:12px;}
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo"><div class="brand-icon">S</div><div class="brand-name">SIS Portal</div></div>
        <div class="brand-role">Administrator</div>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-label">Main</div>
        <a href="admin_dash.php" class="nav-item"><span class="icon">🏠</span> Dashboard</a>
        <a href="manage_students.php" class="nav-item"><span class="icon">👥</span> Students</a>
        <a href="manage_courses.php" class="nav-item"><span class="icon">📚</span> Courses</a>
        <a href="enroll_student.php" class="nav-item"><span class="icon">📝</span> Enrollment</a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-label">Academic</div>
        <a href="grade_entry.php" class="nav-item active"><span class="icon">🎓</span> Grade Entry</a>
        <a href="manage_assignments.php" class="nav-item"><span class="icon">📋</span> Assignments</a>
        <a href="manage_announcements.php" class="nav-item"><span class="icon">📢</span> Announcements</a>
        <a href="admin_reports.php" class="nav-item"><span class="icon">📊</span> Reports</a>
    </div>
    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn"><span>🚪</span> Sign Out</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title">Grade Entry</div>
        </div>
    </div>
    <div class="content">
        <?php if (!empty($msg)): ?>
            <div class="alert">✅ <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Student Grade Management</div>
                    <div class="card-sub">Assign grades for Spring 2026. Changes save immediately.</div>
                </div>
            </div>
            <table>
                <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Current Grade</th>
                    <th>Update Grade</th>
                </tr>
                <?php if (mysqli_num_rows($enrollments) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($enrollments)):
                        $g = $row['grade'];
                        $letter = $g ? strtoupper($g[0]) : '';
                    ?>
                    <tr>
                        <td>
                            <div class="student-name"><?php echo htmlspecialchars($row['full_name']); ?></div>
                            <div class="student-email"><?php echo htmlspecialchars($row['email']); ?></div>
                        </td>
                        <td><span class="course-code"><?php echo htmlspecialchars($row['course_code']); ?></span><br><small style="color:var(--muted); font-size:12px;"><?php echo htmlspecialchars($row['course_name']); ?></small></td>
                        <td>
                            <?php if ($g): ?>
                                <span class="grade-badge grade-<?php echo $letter; ?>"><?php echo htmlspecialchars($g); ?></span>
                            <?php else: ?>
                                <span class="grade-none">Not graded</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" class="grade-form">
                                <input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>">
                                <input type="hidden" name="course_id" value="<?php echo $row['course_id']; ?>">
                                <input type="hidden" name="semester" value="Spring 2026">
                                <select name="grade">
                                    <option value="">-- Select --</option>
                                    <?php foreach ($grade_options as $opt): ?>
                                        <option value="<?php echo $opt; ?>" <?php echo ($g === $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="save_grade" class="btn-save">Save</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:40px;">No enrollments found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
