<?php
include 'config.php';
include 'gpa_helper.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') { header("Location: index.php"); exit(); }

$email = $_SESSION['username'];
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE email='" . mysqli_real_escape_string($conn, $email) . "'"));
$sid = $student['student_id'];
$gpa = calculateGPA($conn, $sid);
$gpa_color = getGPAColor((float)$gpa);
$gpa_label = getGPALabel((float)$gpa);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    mysqli_query($conn, "UPDATE students SET full_name='$full_name' WHERE student_id=$sid");
    $msg = "Profile updated successfully!";
    $student['full_name'] = $full_name;
}

$courses = mysqli_query($conn, "SELECT c.course_code, c.course_name, g.grade FROM enrollments e JOIN courses c ON e.course_id=c.course_id LEFT JOIN grades g ON g.student_id=e.student_id AND g.course_id=c.course_id WHERE e.student_id=$sid");
$course_count = mysqli_num_rows($courses);
mysqli_data_seek($courses, 0);

$first = explode(' ', $student['full_name'])[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile — SIS</title>
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

        .main { margin-left:var(--sidebar-w); flex:1; }
        .topbar { background:white; border-bottom:1px solid var(--border); padding:16px 32px; }
        .topbar-title { font-family:'Syne',sans-serif; font-size:20px; font-weight:700; }

        .content { padding:32px; max-width:900px; }
        .alert { padding:14px 20px; border-radius:12px; margin-bottom:24px; font-size:14px; font-weight:500; background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }

        /* Profile Hero */
        .profile-hero {
            background:white; border-radius:20px; border:1px solid var(--border);
            padding:40px; display:flex; align-items:center; gap:32px; margin-bottom:24px;
        }
        .avatar-big {
            width:100px; height:100px; border-radius:50%;
            background:linear-gradient(135deg, var(--blue), #06b6d4);
            display:flex; align-items:center; justify-content:center;
            font-family:'Syne',sans-serif; font-size:40px; font-weight:800; color:white;
            flex-shrink:0; box-shadow:0 8px 32px rgba(59,130,246,0.3);
        }
        .profile-info h1 { font-family:'Syne',sans-serif; font-size:28px; font-weight:800; margin-bottom:4px; }
        .profile-info .email { color:var(--muted); font-size:15px; margin-bottom:16px; }
        .profile-badges { display:flex; gap:10px; flex-wrap:wrap; }
        .badge { padding:6px 16px; border-radius:20px; font-size:13px; font-weight:600; }
        .badge-blue { background:var(--blue-light); color:var(--blue); }
        .badge-gpa { background:<?php echo $gpa_color; ?>22; color:<?php echo $gpa_color; ?>; }

        .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .card { background:white; border-radius:16px; border:1px solid var(--border); overflow:hidden; }
        .card-header { padding:20px 24px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
        .card-title { font-family:'Syne',sans-serif; font-size:16px; font-weight:700; }
        .card-body { padding:24px; }

        .form-group { margin-bottom:18px; }
        label { display:block; font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:0.6px; margin-bottom:7px; }
        input[type="text"], input[type="email"] { width:100%; padding:11px 14px; border:1px solid var(--border); border-radius:10px; font-size:14px; font-family:'DM Sans',sans-serif; outline:none; transition:border 0.2s; }
        input:focus { border-color:var(--blue); box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
        input[readonly] { background:#f8fafc; color:var(--muted); }
        .btn-save { width:100%; padding:13px; background:linear-gradient(135deg,var(--blue),#2563eb); color:white; border:none; border-radius:12px; font-size:15px; font-weight:600; cursor:pointer; transition:all 0.2s; }
        .btn-save:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(59,130,246,0.35); }

        .stat-row { display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid var(--border); font-size:14px; }
        .stat-row:last-child { border-bottom:none; }
        .stat-row span:first-child { color:var(--muted); }
        .stat-row span:last-child { font-weight:600; }

        .course-item { padding:12px 0; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
        .course-item:last-child { border-bottom:none; }
        .grade-badge { padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; }
        .grade-A { background:#dcfce7; color:#166534; }
        .grade-B { background:#dbeafe; color:#1e40af; }
        .grade-C { background:#fef3c7; color:#92400e; }
        .grade-D, .grade-F { background:#fee2e2; color:#991b1b; }

        .transcript-btn { display:block; text-align:center; margin-top:16px; padding:11px; background:#f8fafc; border:1px solid var(--border); border-radius:12px; font-size:14px; font-weight:600; color:var(--text); text-decoration:none; transition:all 0.2s; }
        .transcript-btn:hover { background:var(--blue-light); border-color:var(--blue); color:var(--blue); }

        @media(max-width:768px){ .sidebar{display:none;} .main{margin-left:0;} .content{padding:16px;} .grid2{grid-template-columns:1fr;} .profile-hero{flex-direction:column; text-align:center;} }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo"><div class="brand-icon">S</div><div class="brand-name">SIS Portal</div></div>
        <div class="brand-role">Student</div>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-label">Navigation</div>
        <a href="student_dash.php" class="nav-item"><span class="icon">🏠</span> Home</a>
        <a href="my_courses.php" class="nav-item"><span class="icon">📚</span> My Courses</a>
        <a href="view_grades.php" class="nav-item"><span class="icon">🎓</span> Grades & GPA</a>
        <a href="assignments.php" class="nav-item"><span class="icon">📝</span> Assignments</a>
        <a href="announcements.php" class="nav-item"><span class="icon">📢</span> Announcements</a>
        <a href="collaboration.php" class="nav-item"><span class="icon">📹</span> Study Rooms</a>
        <a href="student_profile.php" class="nav-item active"><span class="icon">👤</span> My Profile</a>
    </div>
    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn"><span>🚪</span> Sign Out</a>
    </div>
</div>

<div class="main">
    <div class="topbar"><div class="topbar-title">My Profile</div></div>
    <div class="content">

        <?php if (!empty($msg)): ?>
            <div class="alert">✅ <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- Profile Hero -->
        <div class="profile-hero">
            <div class="avatar-big"><?php echo strtoupper($first[0]); ?></div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($student['full_name']); ?></h1>
                <div class="email">📧 <?php echo htmlspecialchars($student['email']); ?></div>
                <div class="profile-badges">
                    <span class="badge badge-blue">Student #<?php echo $sid; ?></span>
                    <span class="badge badge-gpa">GPA <?php echo $gpa; ?> · <?php echo $gpa_label; ?></span>
                    <span class="badge" style="background:#f0fdf4; color:#166534;">Spring 2026</span>
                </div>
            </div>
        </div>

        <div class="grid2">
            <!-- Edit Profile -->
            <div class="card">
                <div class="card-header"><div class="card-title">Edit Profile</div></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email (Username)</label>
                            <input type="email" value="<?php echo htmlspecialchars($student['email']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="text" value="#<?php echo $sid; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Member Since</label>
                            <input type="text" value="<?php echo date('F j, Y', strtotime($student['enrollment_date'])); ?>" readonly>
                        </div>
                        <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Academic Summary -->
            <div style="display:flex; flex-direction:column; gap:20px;">
                <div class="card">
                    <div class="card-header"><div class="card-title">Academic Summary</div></div>
                    <div class="card-body">
                        <div class="stat-row"><span>Cumulative GPA</span><span style="color:<?php echo $gpa_color; ?>; font-size:18px;"><?php echo $gpa; ?></span></div>
                        <div class="stat-row"><span>Academic Standing</span><span><?php echo $gpa_label; ?></span></div>
                        <div class="stat-row"><span>Courses Enrolled</span><span><?php echo $course_count; ?></span></div>
                        <div class="stat-row"><span>Semester</span><span>Spring 2026</span></div>

                        <a href="print_transcript.php" class="transcript-btn">🖨️ Print Transcript</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><div class="card-title">My Courses</div></div>
                    <div class="card-body" style="padding-top:8px;">
                        <?php if ($course_count > 0):
                            while ($c = mysqli_fetch_assoc($courses)):
                                $g = $c['grade'];
                                $letter = $g ? strtoupper($g[0]) : '';
                        ?>
                        <div class="course-item">
                            <div>
                                <div style="font-weight:600; font-size:13px;"><?php echo htmlspecialchars($c['course_code']); ?></div>
                                <div style="font-size:12px; color:var(--muted);"><?php echo htmlspecialchars($c['course_name']); ?></div>
                            </div>
                            <?php if ($g): ?>
                                <span class="grade-badge grade-<?php echo $letter; ?>"><?php echo $g; ?></span>
                            <?php else: ?>
                                <span style="font-size:12px; color:var(--muted);">Pending</span>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; else: ?>
                            <div style="text-align:center; color:var(--muted); padding:16px; font-size:13px;">No courses enrolled</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
