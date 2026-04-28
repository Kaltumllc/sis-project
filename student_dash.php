<?php
include 'config.php';
include 'gpa_helper.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['username'];
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'"));
$display_name = $student ? $student['full_name'] : "Student";
$sid = $student ? $student['student_id'] : 0;
$first_name = explode(' ', $display_name)[0];

$course_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM enrollments WHERE student_id = '$sid'"))['t'];
$gpa = calculateGPA($conn, $sid);
$gpa_color = getGPAColor((float)$gpa);

$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as t FROM assignments a
    JOIN enrollments e ON a.course_id = e.course_id AND e.student_id = '$sid'
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = '$sid'
    WHERE s.submission_id IS NULL AND a.due_date > NOW()
"))['t'];

$announcement_count = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as t FROM announcements WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
"))['t'];

$courses_res = mysqli_query($conn, "
    SELECT courses.course_name, courses.course_code FROM enrollments
    JOIN courses ON enrollments.course_id = courses.course_id
    WHERE enrollments.student_id = '$sid'");
$enrolled_courses = [];
while ($c = mysqli_fetch_assoc($courses_res)) {
    $enrolled_courses[] = $c['course_code'] . ' - ' . $c['course_name'];
}
$courses_list = implode(', ', $enrolled_courses) ?: 'None yet';

$upcoming = mysqli_query($conn, "
    SELECT a.title, a.due_date, c.course_code,
           s.status AS submission_status
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id AND e.student_id = '$sid'
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = '$sid'
    WHERE a.due_date > NOW()
    ORDER BY a.due_date ASC LIMIT 4
");

$recent_ann = mysqli_query($conn, "SELECT title, created_at FROM announcements ORDER BY created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal — SIS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #f0f4f8; --white: #ffffff; --navy: #0f172a;
            --blue: #3b82f6; --blue-light: #eff6ff; --blue-border: #bfdbfe;
            --green: #10b981; --purple: #8b5cf6; --amber: #f59e0b;
            --text: #1e293b; --muted: #64748b; --border: #e2e8f0;
            --sidebar-w: 260px;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        /* ── SIDEBAR ── */
        .sidebar { width: var(--sidebar-w); background: var(--navy); color: white; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; overflow-y: auto; }
        .sidebar-brand { padding: 28px 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .brand-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
        .brand-icon { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--blue), #06b6d4); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 16px; }
        .brand-name { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700; }
        .brand-role { font-size: 11px; color: rgba(255,255,255,0.4); letter-spacing: 0.8px; text-transform: uppercase; margin-top: 2px; }

        .sidebar-section { padding: 20px 16px 8px; }
        .sidebar-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.3); padding: 0 8px; margin-bottom: 6px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; color: rgba(255,255,255,0.6); text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 2px; transition: all 0.2s; }
        .nav-item:hover { background: rgba(255,255,255,0.08); color: white; }
        .nav-item.active { background: rgba(59,130,246,0.2); color: var(--blue); }
        .nav-item .icon { font-size: 16px; width: 20px; text-align: center; }
        .nav-badge { margin-left: auto; background: var(--blue); color: white; font-size: 10px; font-weight: 700; min-width: 18px; height: 18px; border-radius: 9px; display: flex; align-items: center; justify-content: center; padding: 0 5px; }

        .sidebar-profile { padding: 16px; border-top: 1px solid rgba(255,255,255,0.08); }
        .profile-card { background: rgba(255,255,255,0.06); border-radius: 12px; padding: 14px; display: flex; align-items: center; gap: 10px; }
        .profile-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--blue), var(--purple)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0; }
        .profile-name { font-size: 13px; font-weight: 600; line-height: 1.2; }
        .profile-id { font-size: 11px; color: rgba(255,255,255,0.4); }

        .sidebar-bottom { padding: 8px 16px 16px; }
        .logout-btn { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; color: #f87171; text-decoration: none; font-size: 14px; font-weight: 500; transition: background 0.2s; }
        .logout-btn:hover { background: rgba(239,68,68,0.1); }

        /* ── MAIN ── */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; }
        .topbar { background: white; border-bottom: 1px solid var(--border); padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
        .topbar-left h1 { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; }
        .topbar-left p { font-size: 13px; color: var(--muted); margin-top: 2px; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .gpa-badge { background: var(--blue-light); border: 1px solid var(--blue-border); color: var(--blue); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700; }

        .content { padding: 32px; flex: 1; }

        /* ── STATS ── */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: all 0.2s; cursor: default; }
        .stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .stat-icon.blue { background: #eff6ff; }
        .stat-icon.green { background: #ecfdf5; }
        .stat-icon.amber { background: #fffbeb; }
        .stat-icon.purple { background: #f5f3ff; }
        .stat-num { font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 800; line-height: 1; }
        .stat-label { font-size: 13px; color: var(--muted); margin-top: 4px; }

        /* ── DASHBOARD GRID ── */
        .dashboard-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }

        .card { background: white; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; }
        .card-header { padding: 20px 24px 0; display: flex; align-items: center; justify-content: space-between; }
        .card-title { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700; }
        .card-body { padding: 20px 24px; }
        .card-link { font-size: 13px; color: var(--blue); text-decoration: none; font-weight: 500; }

        /* App Cards Grid */
        .app-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .app-card { background: var(--bg); border: 1px solid var(--border); border-radius: 14px; padding: 20px 16px; text-align: center; text-decoration: none; color: var(--text); transition: all 0.2s; display: block; }
        .app-card:hover { background: var(--blue-light); border-color: var(--blue-border); transform: translateY(-2px); box-shadow: 0 4px 16px rgba(59,130,246,0.12); }
        .app-card .app-icon { font-size: 28px; margin-bottom: 8px; }
        .app-card .app-name { font-size: 13px; font-weight: 600; }
        .app-card .app-sub { font-size: 11px; color: var(--muted); margin-top: 3px; }

        /* Upcoming Assignments */
        .assign-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .assign-item:last-child { border-bottom: none; }
        .assign-status { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .assign-status.pending { background: var(--amber); }
        .assign-status.submitted { background: var(--green); }
        .assign-title { font-size: 13px; font-weight: 600; }
        .assign-meta { font-size: 11px; color: var(--muted); margin-top: 2px; }
        .assign-due { font-size: 11px; color: var(--amber); font-weight: 600; margin-left: auto; white-space: nowrap; }

        /* Announcements */
        .ann-item { padding: 12px 0; border-bottom: 1px solid var(--border); }
        .ann-item:last-child { border-bottom: none; }
        .ann-title { font-size: 13px; font-weight: 600; margin-bottom: 3px; }
        .ann-date { font-size: 11px; color: var(--muted); }

        .empty-state { text-align: center; padding: 24px; color: var(--muted); font-size: 13px; }

        /* Chat */
        #chat-bubble { position: fixed; bottom: 24px; right: 24px; width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, var(--blue), #2563eb); color: white; border: none; font-size: 22px; cursor: pointer; box-shadow: 0 4px 20px rgba(59,130,246,0.4); display: flex; align-items: center; justify-content: center; transition: transform 0.2s; z-index: 1000; }
        #chat-bubble:hover { transform: scale(1.1); }
        #chat-window { position: fixed; bottom: 88px; right: 24px; width: 360px; background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); display: none; flex-direction: column; overflow: hidden; z-index: 1000; border: 1px solid var(--border); }
        #chat-window.open { display: flex; }
        .chat-header { background: var(--navy); color: white; padding: 16px 20px; display: flex; align-items: center; gap: 10px; }
        .chat-av { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--blue), #06b6d4); display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .chat-inf strong { display: block; font-size: 14px; }
        .chat-inf span { font-size: 11px; color: rgba(255,255,255,0.5); }
        .chat-header button { background: none; border: none; color: rgba(255,255,255,0.5); font-size: 18px; cursor: pointer; margin-left: auto; }
        #chat-messages { padding: 16px; overflow-y: auto; min-height: 260px; max-height: 340px; display: flex; flex-direction: column; gap: 10px; background: #f8fafc; }
        .msg { max-width: 82%; padding: 10px 14px; border-radius: 14px; font-size: 13.5px; line-height: 1.5; }
        .msg.bot { background: white; color: var(--text); border-bottom-left-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); align-self: flex-start; }
        .msg.user { background: var(--blue); color: white; border-bottom-right-radius: 4px; align-self: flex-end; }
        .chat-footer { padding: 12px; border-top: 1px solid var(--border); display: flex; gap: 8px; background: white; }
        .chat-footer input { flex: 1; padding: 10px 14px; border: 1px solid var(--border); border-radius: 20px; font-size: 13px; outline: none; font-family: 'DM Sans', sans-serif; }
        .chat-footer input:focus { border-color: var(--blue); }
        .chat-footer button { width: 38px; height: 38px; border-radius: 50%; background: var(--blue); color: white; border: none; cursor: pointer; font-size: 16px; }
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
        <div class="brand-role">Student</div>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-label">Navigation</div>
        <a href="student_dash.php" class="nav-item active"><span class="icon">🏠</span> Home</a>
        <a href="my_courses.php" class="nav-item"><span class="icon">📚</span> My Courses</a>
        <a href="view_grades.php" class="nav-item"><span class="icon">🎓</span> Grades & GPA</a>
        <a href="assignments.php" class="nav-item">
            <span class="icon">📝</span> Assignments
            <?php if($pending_count > 0): ?><span class="nav-badge"><?php echo $pending_count; ?></span><?php endif; ?>
        </a>
        <a href="announcements.php" class="nav-item">
            <span class="icon">📢</span> Announcements
            <?php if($announcement_count > 0): ?><span class="nav-badge"><?php echo $announcement_count; ?></span><?php endif; ?>
        </a>
        <a href="collaboration.php" class="nav-item"><span class="icon">📹</span> Study Rooms</a>
    </div>

    <div class="sidebar-profile">
        <div class="profile-card">
            <div class="profile-avatar"><?php echo strtoupper($first_name[0]); ?></div>
            <div>
                <div class="profile-name"><?php echo htmlspecialchars($display_name); ?></div>
                <div class="profile-id">ID #<?php echo $sid; ?></div>
            </div>
        </div>
    </div>

    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn"><span>🚪</span> Sign Out</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <h1>Welcome back, <?php echo htmlspecialchars($first_name); ?>! 👋</h1>
            <p>Spring 2026 · <?php echo $course_count; ?> courses enrolled</p>
        </div>
        <div class="topbar-right">
            <span class="gpa-badge">GPA <?php echo $gpa; ?></span>
        </div>
    </div>

    <div class="content">

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card" onclick="location.href='my_courses.php'" style="cursor:pointer;">
                <div class="stat-top">
                    <div class="stat-icon blue">📚</div>
                </div>
                <div class="stat-num"><?php echo $course_count; ?></div>
                <div class="stat-label">Enrolled Courses</div>
            </div>
            <div class="stat-card" onclick="location.href='view_grades.php'" style="cursor:pointer;">
                <div class="stat-top">
                    <div class="stat-icon green">🎓</div>
                </div>
                <div class="stat-num" style="color:<?php echo $gpa_color; ?>"><?php echo $gpa; ?></div>
                <div class="stat-label">Current GPA</div>
            </div>
            <div class="stat-card" onclick="location.href='assignments.php'" style="cursor:pointer;">
                <div class="stat-top">
                    <div class="stat-icon amber">📝</div>
                </div>
                <div class="stat-num"><?php echo $pending_count; ?></div>
                <div class="stat-label">Pending Tasks</div>
            </div>
            <div class="stat-card" onclick="location.href='announcements.php'" style="cursor:pointer;">
                <div class="stat-top">
                    <div class="stat-icon purple">📢</div>
                </div>
                <div class="stat-num"><?php echo $announcement_count; ?></div>
                <div class="stat-label">New Notices</div>
            </div>
        </div>

        <!-- DASHBOARD GRID -->
        <div class="dashboard-grid">

            <!-- LEFT -->
            <div style="display:flex; flex-direction:column; gap:20px;">

                <!-- Quick Access -->
                <div class="card">
                    <div class="card-header"><div class="card-title">Quick Access</div></div>
                    <div class="card-body">
                        <div class="app-grid">
                            <a href="my_courses.php" class="app-card">
                                <div class="app-icon">📅</div>
                                <div class="app-name">Schedule</div>
                                <div class="app-sub"><?php echo $course_count; ?> courses</div>
                            </a>
                            <a href="view_grades.php" class="app-card">
                                <div class="app-icon">🎓</div>
                                <div class="app-name">Grades</div>
                                <div class="app-sub">GPA <?php echo $gpa; ?></div>
                            </a>
                            <a href="assignments.php" class="app-card">
                                <div class="app-icon">📝</div>
                                <div class="app-name">Assignments</div>
                                <div class="app-sub"><?php echo $pending_count; ?> pending</div>
                            </a>
                            <a href="announcements.php" class="app-card">
                                <div class="app-icon">📢</div>
                                <div class="app-name">Notices</div>
                                <div class="app-sub"><?php echo $announcement_count; ?> new</div>
                            </a>
                            <a href="collaboration.php" class="app-card">
                                <div class="app-icon">📹</div>
                                <div class="app-name">Study Room</div>
                                <div class="app-sub">Join call</div>
                            </a>
                            <div class="app-card" style="cursor:default;">
                                <div class="app-icon">💰</div>
                                <div class="app-name">Finances</div>
                                <div class="app-sub">$0.00 balance</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Assignments -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Upcoming Assignments</div>
                        <a href="assignments.php" class="card-link">View All →</a>
                    </div>
                    <div class="card-body" style="padding-top:8px;">
                        <?php if(mysqli_num_rows($upcoming) > 0): ?>
                            <?php while($a = mysqli_fetch_assoc($upcoming)):
                                $due = new DateTime($a['due_date']);
                                $diff = (new DateTime())->diff($due);
                                $days_left = $diff->days;
                            ?>
                            <div class="assign-item">
                                <div class="assign-status <?php echo $a['submission_status'] ? 'submitted' : 'pending'; ?>"></div>
                                <div>
                                    <div class="assign-title"><?php echo htmlspecialchars($a['title']); ?></div>
                                    <div class="assign-meta"><?php echo htmlspecialchars($a['course_code']); ?></div>
                                </div>
                                <div class="assign-due">
                                    <?php echo $days_left === 0 ? 'Today!' : "In $days_left days"; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">🎉 No upcoming assignments!</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT -->
            <div style="display:flex; flex-direction:column; gap:20px;">

                <!-- Recent Announcements -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Announcements</div>
                        <a href="announcements.php" class="card-link">All →</a>
                    </div>
                    <div class="card-body" style="padding-top:8px;">
                        <?php if(mysqli_num_rows($recent_ann) > 0): ?>
                            <?php while($ann = mysqli_fetch_assoc($recent_ann)): ?>
                            <div class="ann-item">
                                <div class="ann-title"><?php echo htmlspecialchars($ann['title']); ?></div>
                                <div class="ann-date"><?php echo date('M j, Y', strtotime($ann['created_at'])); ?></div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">No announcements yet</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- My Courses -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">My Courses</div>
                        <a href="my_courses.php" class="card-link">All →</a>
                    </div>
                    <div class="card-body" style="padding-top:8px;">
                        <?php
                        $my_courses = mysqli_query($conn, "
                            SELECT c.course_code, c.course_name FROM enrollments e
                            JOIN courses c ON e.course_id = c.course_id
                            WHERE e.student_id = '$sid'");
                        if(mysqli_num_rows($my_courses) > 0):
                            while($c = mysqli_fetch_assoc($my_courses)): ?>
                            <div style="padding:10px 0; border-bottom:1px solid var(--border);">
                                <div style="font-size:13px; font-weight:600;"><?php echo htmlspecialchars($c['course_code']); ?></div>
                                <div style="font-size:12px; color:var(--muted);"><?php echo htmlspecialchars($c['course_name']); ?></div>
                            </div>
                        <?php endwhile; else: ?>
                            <div class="empty-state">No courses enrolled</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Academic Standing -->
                <div class="card">
                    <div class="card-header"><div class="card-title">Academic Standing</div></div>
                    <div class="card-body">
                        <div style="text-align:center; padding:8px 0;">
                            <div style="font-family:'Syne',sans-serif; font-size:48px; font-weight:800; color:<?php echo $gpa_color; ?>; line-height:1;"><?php echo $gpa; ?></div>
                            <div style="font-size:13px; color:var(--muted); margin-top:4px;">Cumulative GPA / 4.0</div>
                            <div style="display:inline-block; margin-top:12px; padding:6px 16px; border-radius:20px; font-size:12px; font-weight:600; background:<?php echo $gpa_color; ?>22; color:<?php echo $gpa_color; ?>;">
                                <?php echo getGPALabel((float)$gpa); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AI CHAT -->
<button id="chat-bubble" onclick="toggleChat()">💬</button>
<div id="chat-window">
    <div class="chat-header">
        <div class="chat-av">🤖</div>
        <div class="chat-inf"><strong>SIS AI Assistant</strong><span>Here to help</span></div>
        <button onclick="toggleChat()">✕</button>
    </div>
    <div id="chat-messages">
        <div class="msg bot">Hi <?php echo htmlspecialchars($first_name); ?>! 👋 You have <b><?php echo $pending_count; ?> pending assignment<?php echo $pending_count!=1?'s':''; ?></b> and a GPA of <b><?php echo $gpa; ?></b>. How can I help?</div>
    </div>
    <div class="chat-footer">
        <input type="text" id="chat-input" placeholder="Ask me anything..." onkeydown="if(event.key==='Enter') sendMessage()">
        <button id="send-btn" onclick="sendMessage()">➤</button>
    </div>
</div>

<script>
const STUDENT_NAME = <?php echo json_encode($display_name); ?>;
const ENROLLED = <?php echo json_encode($courses_list); ?>;
const COURSE_COUNT = <?php echo json_encode($course_count); ?>;
const GPA = <?php echo json_encode($gpa); ?>;
const PENDING = <?php echo json_encode($pending_count); ?>;
let chatHistory = [];

function toggleChat() {
    const w = document.getElementById('chat-window');
    w.classList.toggle('open');
    if(w.classList.contains('open')) document.getElementById('chat-input').focus();
}
function addMsg(text, role) {
    const box = document.getElementById('chat-messages');
    const div = document.createElement('div');
    div.className = 'msg ' + role;
    div.innerHTML = text;
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
    return div;
}
async function sendMessage() {
    const input = document.getElementById('chat-input');
    const btn = document.getElementById('send-btn');
    const text = input.value.trim();
    if(!text) return;
    input.value = ''; btn.disabled = true;
    addMsg(text, 'user');
    chatHistory.push({role:'user', content:text});
    const typing = addMsg('Thinking…', 'bot');
    try {
        const res = await fetch('https://api.anthropic.com/v1/messages', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                model:'claude-sonnet-4-20250514', max_tokens:1000,
                system:`You are a friendly AI assistant in a Student Information System. Student: ${STUDENT_NAME}, GPA: ${GPA}/4.0, Courses: ${ENROLLED}, Pending assignments: ${PENDING}. Be concise, encouraging, 2-3 sentences. Use simple HTML for emphasis.`,
                messages: chatHistory
            })
        });
        const data = await res.json();
        const reply = data.content?.[0]?.text || "Sorry, try again!";
        chatHistory.push({role:'assistant', content:reply});
        typing.className = 'msg bot';
        typing.innerHTML = reply;
        document.getElementById('chat-messages').scrollTop = 9999;
    } catch(e) { typing.innerHTML = '⚠️ Connection error.'; }
    btn.disabled = false; input.focus();
}
</script>

</body>
</html>
