<?php
include 'config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$count_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM students"))['total'];
$count_courses  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM courses"))['total'];
$count_enroll   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM enrollments"))['total'];
$count_assign   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assignments"))['total'];

$chart_data = mysqli_query($conn, "
    SELECT courses.course_code, COUNT(enrollments.enrollment_id) as student_count 
    FROM courses 
    LEFT JOIN enrollments ON courses.course_id = enrollments.course_id 
    GROUP BY courses.course_id
");
$labels = []; $data = [];
while($row = mysqli_fetch_assoc($chart_data)) {
    $labels[] = $row['course_code'];
    $data[]   = $row['student_count'];
}

$recent_students = mysqli_query($conn, "SELECT full_name, email, enrollment_date FROM students ORDER BY enrollment_date DESC LIMIT 5");
$recent_announce = mysqli_query($conn, "SELECT title, created_at FROM announcements ORDER BY created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard — SIS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #f0f4f8; --white: #ffffff; --navy: #0f172a;
            --blue: #3b82f6; --blue-light: #eff6ff; --blue-border: #bfdbfe;
            --green: #10b981; --purple: #8b5cf6; --amber: #f59e0b; --red: #ef4444;
            --text: #1e293b; --muted: #64748b; --border: #e2e8f0;
            --sidebar-w: 260px;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w); background: var(--navy); color: white;
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; height: 100vh;
            z-index: 100; overflow-y: auto;
        }
        .sidebar-brand {
            padding: 28px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .brand-logo {
            display: flex; align-items: center; gap: 10px; margin-bottom: 4px;
        }
        .brand-icon {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, var(--blue), #06b6d4);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif; font-weight: 800; font-size: 16px;
        }
        .brand-name { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700; }
        .brand-role {
            font-size: 11px; color: rgba(255,255,255,0.4);
            letter-spacing: 0.8px; text-transform: uppercase; margin-top: 2px;
        }

        .sidebar-section { padding: 20px 16px 8px; }
        .sidebar-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
            color: rgba(255,255,255,0.3); padding: 0 8px; margin-bottom: 6px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            color: rgba(255,255,255,0.6); text-decoration: none;
            font-size: 14px; font-weight: 500; margin-bottom: 2px;
            transition: all 0.2s;
        }
        .nav-item:hover { background: rgba(255,255,255,0.08); color: white; }
        .nav-item.active { background: rgba(59,130,246,0.2); color: var(--blue); }
        .nav-item .icon { font-size: 16px; width: 20px; text-align: center; }

        .sidebar-bottom {
            margin-top: auto; padding: 16px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .logout-btn {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            color: #f87171; text-decoration: none;
            font-size: 14px; font-weight: 500;
            transition: background 0.2s;
        }
        .logout-btn:hover { background: rgba(239,68,68,0.1); }

        /* ── MAIN ── */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; }

        .topbar {
            background: white; border-bottom: 1px solid var(--border);
            padding: 16px 32px; display: flex; align-items: center;
            justify-content: space-between; position: sticky; top: 0; z-index: 50;
        }
        .topbar-title { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .admin-badge {
            background: var(--blue-light); color: var(--blue);
            border: 1px solid var(--blue-border);
            padding: 6px 14px; border-radius: 20px;
            font-size: 13px; font-weight: 600;
        }
        .avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, var(--blue), #06b6d4);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px; color: white;
        }

        .content { padding: 32px; flex: 1; }

        /* ── STATS ── */
        .stats-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 16px; margin-bottom: 28px;
        }
        .stat-card {
            background: white; border-radius: 16px; padding: 20px 24px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            display: flex; align-items: center; gap: 16px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
        }
        .stat-icon.blue   { background: #eff6ff; }
        .stat-icon.green  { background: #ecfdf5; }
        .stat-icon.purple { background: #f5f3ff; }
        .stat-icon.amber  { background: #fffbeb; }
        .stat-num { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 800; line-height: 1; }
        .stat-label { font-size: 13px; color: var(--muted); margin-top: 3px; }
        .stat-change { font-size: 11px; color: var(--green); margin-top: 4px; font-weight: 500; }

        /* ── GRID LAYOUT ── */
        .dashboard-grid {
            display: grid; grid-template-columns: 1fr 340px;
            gap: 20px;
        }

        .card {
            background: white; border-radius: 16px; border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;
        }
        .card-header {
            padding: 20px 24px 0;
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-title { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700; }
        .card-body { padding: 20px 24px; }
        .card-link { font-size: 13px; color: var(--blue); text-decoration: none; font-weight: 500; }

        /* Quick Actions */
        .quick-actions {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;
        }
        .action-btn {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border);
            text-decoration: none; color: var(--text); font-size: 13px; font-weight: 500;
            background: var(--bg); transition: all 0.2s;
        }
        .action-btn:hover { background: var(--blue-light); border-color: var(--blue-border); color: var(--blue); }
        .action-btn .icon { font-size: 18px; }

        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.6px; color: var(--muted); padding: 10px 16px; text-align: left; border-bottom: 1px solid var(--border); font-weight: 600; }
        td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #f8fafc; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        .student-name { font-weight: 600; color: var(--text); }
        .student-email { color: var(--muted); font-size: 12px; }
        .date-badge { font-size: 11px; color: var(--muted); white-space: nowrap; }

        /* Announcements */
        .announce-item {
            padding: 14px 0; border-bottom: 1px solid var(--border);
            display: flex; gap: 12px; align-items: flex-start;
        }
        .announce-item:last-child { border-bottom: none; }
        .announce-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--blue); flex-shrink: 0; margin-top: 5px; }
        .announce-title { font-size: 13px; font-weight: 600; margin-bottom: 2px; }
        .announce-date { font-size: 11px; color: var(--muted); }

        .empty-state { text-align: center; padding: 32px; color: var(--muted); font-size: 14px; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <div class="brand-icon">S</div>
            <div class="brand-name">SIS Portal</div>
        </div>
        <div class="brand-role">Administrator</div>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-label">Main</div>
        <a href="admin_dash.php" class="nav-item active"><span class="icon">🏠</span> Dashboard</a>
        <a href="manage_students.php" class="nav-item"><span class="icon">👥</span> Students</a>
        <a href="manage_courses.php" class="nav-item"><span class="icon">📚</span> Courses</a>
        <a href="enroll_student.php" class="nav-item"><span class="icon">📝</span> Enrollment</a>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-label">Academic</div>
        <a href="manage_assignments.php" class="nav-item"><span class="icon">📋</span> Assignments</a>
        <a href="manage_announcements.php" class="nav-item"><span class="icon">📢</span> Announcements</a>
    </div>

    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn"><span>🚪</span> Sign Out</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <div class="topbar-title">System Overview</div>
        <div class="topbar-right">
            <span class="admin-badge">👤 Admin</span>
            <div class="avatar">A</div>
        </div>
    </div>

    <div class="content">

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">👥</div>
                <div>
                    <div class="stat-num"><?php echo $count_students; ?></div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-change">↑ Active</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">📚</div>
                <div>
                    <div class="stat-num"><?php echo $count_courses; ?></div>
                    <div class="stat-label">Active Courses</div>
                    <div class="stat-change">↑ Running</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">📝</div>
                <div>
                    <div class="stat-num"><?php echo $count_enroll; ?></div>
                    <div class="stat-label">Enrollments</div>
                    <div class="stat-change">↑ Total</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amber">📋</div>
                <div>
                    <div class="stat-num"><?php echo $count_assign; ?></div>
                    <div class="stat-label">Assignments</div>
                    <div class="stat-change">↑ Posted</div>
                </div>
            </div>
        </div>

        <!-- DASHBOARD GRID -->
        <div class="dashboard-grid">

            <!-- LEFT COLUMN -->
            <div style="display:flex; flex-direction:column; gap:20px;">

                <!-- Chart -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Enrollment Distribution</div>
                        <a href="manage_courses.php" class="card-link">View All →</a>
                    </div>
                    <div class="card-body">
                        <canvas id="enrollmentChart" height="80"></canvas>
                    </div>
                </div>

                <!-- Recent Students -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Recent Students</div>
                        <a href="manage_students.php" class="card-link">View All →</a>
                    </div>
                    <table>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Joined</th>
                        </tr>
                        <?php if(mysqli_num_rows($recent_students) > 0): ?>
                            <?php while($s = mysqli_fetch_assoc($recent_students)): ?>
                            <tr>
                                <td><div class="student-name"><?php echo htmlspecialchars($s['full_name']); ?></div></td>
                                <td><div class="student-email"><?php echo htmlspecialchars($s['email']); ?></div></td>
                                <td><div class="date-badge"><?php echo date('M j, Y', strtotime($s['enrollment_date'])); ?></div></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3"><div class="empty-state">No students yet</div></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div style="display:flex; flex-direction:column; gap:20px;">

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header"><div class="card-title">Quick Actions</div></div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="manage_students.php" class="action-btn"><span class="icon">➕</span> Add Student</a>
                            <a href="manage_courses.php" class="action-btn"><span class="icon">📚</span> Add Course</a>
                            <a href="enroll_student.php" class="action-btn"><span class="icon">📝</span> Enroll</a>
                            <a href="manage_assignments.php" class="action-btn"><span class="icon">📋</span> Assignment</a>
                            <a href="manage_announcements.php" class="action-btn"><span class="icon">📢</span> Announce</a>
                            <a href="manage_students.php" class="action-btn"><span class="icon">📊</span> Reports</a>
                        </div>
                    </div>
                </div>

                <!-- Recent Announcements -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Announcements</div>
                        <a href="manage_announcements.php" class="card-link">Post New →</a>
                    </div>
                    <div class="card-body" style="padding-top:8px;">
                        <?php if(mysqli_num_rows($recent_announce) > 0): ?>
                            <?php while($a = mysqli_fetch_assoc($recent_announce)): ?>
                            <div class="announce-item">
                                <div class="announce-dot"></div>
                                <div>
                                    <div class="announce-title"><?php echo htmlspecialchars($a['title']); ?></div>
                                    <div class="announce-date"><?php echo date('M j, Y', strtotime($a['created_at'])); ?></div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">No announcements yet</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- System Info -->
                <div class="card">
                    <div class="card-header"><div class="card-title">System Info</div></div>
                    <div class="card-body">
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <div style="display:flex; justify-content:space-between; font-size:13px;">
                                <span style="color:var(--muted);">Platform</span>
                                <span style="font-weight:600;">SIS v2.0</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:13px;">
                                <span style="color:var(--muted);">Institution</span>
                                <span style="font-weight:600;">UMass Lowell</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:13px;">
                                <span style="color:var(--muted);">Semester</span>
                                <span style="font-weight:600;">Spring 2026</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:13px;">
                                <span style="color:var(--muted);">Status</span>
                                <span style="color:var(--green); font-weight:600;">🟢 Live</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('enrollmentChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Enrolled Students',
            data: <?php echo json_encode($data); ?>,
            backgroundColor: 'rgba(59,130,246,0.15)',
            borderColor: '#3b82f6',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, color: '#94a3b8', font: { size: 12 } }, grid: { color: '#f1f5f9' } },
            x: { ticks: { color: '#94a3b8', font: { size: 12 } }, grid: { display: false } }
        }
    }
});
</script>
</body>
</html>
