<?php
include 'config.php';
include 'gpa_helper.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }

// Get all students with GPA
$students_raw = mysqli_query($conn, "SELECT student_id, full_name, email, enrollment_date FROM students ORDER BY full_name");
$students_data = [];
while ($s = mysqli_fetch_assoc($students_raw)) {
    $s['gpa'] = calculateGPA($conn, $s['student_id']);
    $students_data[] = $s;
}
usort($students_data, fn($a,$b) => $b['gpa'] <=> $a['gpa']);

// Submission stats
$total_assignments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM assignments"))['t'];
$total_submissions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM submissions"))['t'];
$graded = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM submissions WHERE status='graded'"))['t'];

// Enrollment per course
$course_stats = mysqli_query($conn, "
    SELECT c.course_code, c.course_name, COUNT(e.enrollment_id) as count
    FROM courses c LEFT JOIN enrollments e ON c.course_id = e.course_id
    GROUP BY c.course_id ORDER BY count DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports — SIS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .topbar { background:white; border-bottom:1px solid var(--border); padding:16px 32px; display:flex; align-items:center; justify-content:space-between; }
        .topbar-title { font-family:'Syne',sans-serif; font-size:20px; font-weight:700; }
        .btn-print { padding:10px 20px; background:var(--blue); color:white; border:none; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; }

        .content { padding:32px; }

        .stats-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:28px; }
        .stat-card { background:white; border-radius:16px; padding:24px; border:1px solid var(--border); }
        .stat-num { font-family:'Syne',sans-serif; font-size:36px; font-weight:800; color:var(--blue); }
        .stat-label { font-size:13px; color:var(--muted); margin-top:4px; }

        .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .card { background:white; border-radius:16px; border:1px solid var(--border); overflow:hidden; }
        .card-header { padding:20px 24px; border-bottom:1px solid var(--border); }
        .card-title { font-family:'Syne',sans-serif; font-size:16px; font-weight:700; }
        .card-body { padding:20px 24px; }

        table { width:100%; border-collapse:collapse; }
        th { font-size:11px; text-transform:uppercase; letter-spacing:0.6px; color:var(--muted); padding:10px 16px; text-align:left; border-bottom:1px solid var(--border); font-weight:600; }
        td { padding:12px 16px; font-size:14px; border-bottom:1px solid #f1f5f9; }
        tr:last-child td { border-bottom:none; }

        .rank { font-weight:700; color:var(--muted); font-size:13px; }
        .rank.gold { color:#f59e0b; }
        .rank.silver { color:#94a3b8; }
        .rank.bronze { color:#b45309; }

        .gpa-bar-wrap { display:flex; align-items:center; gap:10px; }
        .gpa-bar { height:6px; border-radius:3px; background:var(--border); flex:1; overflow:hidden; }
        .gpa-fill { height:100%; border-radius:3px; background:linear-gradient(90deg,var(--blue),#06b6d4); }
        .gpa-val { font-size:13px; font-weight:700; color:var(--blue); min-width:36px; }

        .progress-ring { display:inline-flex; align-items:center; justify-content:center; }

        @media print {
            .sidebar, .topbar { display:none; }
            .main { margin-left:0; }
            .content { padding:20px; }
        }
        @media(max-width:768px){
            .sidebar{display:none;} .main{margin-left:0;}
            .stats-grid{grid-template-columns:1fr 1fr;}
            .grid2{grid-template-columns:1fr;}
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
        <a href="grade_entry.php" class="nav-item"><span class="icon">🎓</span> Grade Entry</a>
        <a href="manage_assignments.php" class="nav-item"><span class="icon">📋</span> Assignments</a>
        <a href="manage_announcements.php" class="nav-item"><span class="icon">📢</span> Announcements</a>
        <a href="admin_reports.php" class="nav-item active"><span class="icon">📊</span> Reports</a>
    </div>
    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn"><span>🚪</span> Sign Out</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-title">Academic Reports</div>
        <button class="btn-print" onclick="window.print()">🖨️ Print Report</button>
    </div>
    <div class="content">

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-num"><?php echo $total_assignments; ?></div>
                <div class="stat-label">Total Assignments Posted</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?php echo $total_submissions; ?></div>
                <div class="stat-label">Total Submissions Received</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?php echo $graded; ?></div>
                <div class="stat-label">Assignments Graded</div>
            </div>
        </div>

        <div class="grid2">
            <!-- GPA Leaderboard -->
            <div class="card">
                <div class="card-header"><div class="card-title">🏆 GPA Leaderboard</div></div>
                <table>
                    <tr><th>#</th><th>Student</th><th>GPA</th></tr>
                    <?php foreach ($students_data as $i => $s):
                        $rankClass = $i===0?'gold':($i===1?'silver':($i===2?'bronze':''));
                        $fill = round(($s['gpa']/4.0)*100);
                    ?>
                    <tr>
                        <td><span class="rank <?php echo $rankClass; ?>"><?php echo $i+1; ?></span></td>
                        <td><div style="font-weight:600;font-size:13px;"><?php echo htmlspecialchars($s['full_name']); ?></div></td>
                        <td>
                            <div class="gpa-bar-wrap">
                                <div class="gpa-bar"><div class="gpa-fill" style="width:<?php echo $fill; ?>%"></div></div>
                                <div class="gpa-val"><?php echo $s['gpa']; ?></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($students_data)): ?>
                        <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:24px;">No students yet</td></tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Course Enrollment Stats -->
            <div class="card">
                <div class="card-header"><div class="card-title">📚 Course Enrollment</div></div>
                <div class="card-body">
                    <canvas id="courseChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Submission Rate -->
        <div class="card">
            <div class="card-header"><div class="card-title">📝 Submission Rate Overview</div></div>
            <div class="card-body">
                <?php if ($total_assignments > 0):
                    $rate = round(($total_submissions / max($total_assignments,1)) * 100);
                ?>
                <div style="display:flex; align-items:center; gap:24px; flex-wrap:wrap;">
                    <div style="text-align:center;">
                        <div style="font-family:'Syne',sans-serif; font-size:48px; font-weight:800; color:var(--blue);"><?php echo $rate; ?>%</div>
                        <div style="font-size:13px; color:var(--muted);">Submission Rate</div>
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <div style="height:12px; background:var(--border); border-radius:6px; overflow:hidden; margin-bottom:12px;">
                            <div style="height:100%; width:<?php echo $rate; ?>%; background:linear-gradient(90deg,var(--blue),#06b6d4); border-radius:6px; transition:width 1s;"></div>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:13px; color:var(--muted);">
                            <span><?php echo $total_submissions; ?> submitted</span>
                            <span><?php echo $graded; ?> graded</span>
                            <span><?php echo $total_assignments; ?> total</span>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <div style="text-align:center; color:var(--muted); padding:24px;">No assignments posted yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
<?php
$c_labels = []; $c_data = [];
while ($r = mysqli_fetch_assoc($course_stats)) { $c_labels[] = $r['course_code']; $c_data[] = $r['count']; }
?>
new Chart(document.getElementById('courseChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($c_labels); ?>,
        datasets: [{ data: <?php echo json_encode($c_data); ?>, backgroundColor: ['#3b82f6','#06b6d4','#8b5cf6','#f59e0b','#10b981'], borderWidth: 0 }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } } }, cutout: '65%' }
});
</script>
</body>
</html>
