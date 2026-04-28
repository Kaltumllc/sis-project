<?php
include 'config.php';
include 'gpa_helper.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['username'];
$sql = "SELECT * FROM students WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'";
$student = mysqli_fetch_assoc(mysqli_query($conn, $sql));

$display_name = $student ? $student['full_name'] : "Student";
$sid = $student ? $student['student_id'] : "N/A";

// Stats
$course_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM enrollments WHERE student_id = '$sid'"))['t'];
$gpa = calculateGPA($conn, $sid);
$gpa_color = getGPAColor((float)$gpa);

// Pending assignments (enrolled courses, not yet submitted)
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as t FROM assignments a
    JOIN enrollments e ON a.course_id = e.course_id AND e.student_id = '$sid'
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = '$sid'
    WHERE s.submission_id IS NULL AND a.due_date > NOW()
"))['t'];

// Unread announcements (last 7 days)
$announcement_count = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as t FROM announcements WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
"))['t'];

// Enrolled course names for AI context
$courses_res = mysqli_query($conn, "
    SELECT courses.course_name, courses.course_code FROM enrollments
    JOIN courses ON enrollments.course_id = courses.course_id
    WHERE enrollments.student_id = '$sid'");
$enrolled_courses = [];
while ($c = mysqli_fetch_assoc($courses_res)) {
    $enrolled_courses[] = $c['course_code'] . ' - ' . $c['course_name'];
}
$courses_list = implode(', ', $enrolled_courses) ?: 'None yet';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Center – SIS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; background: #f4f7f6; }

        .sidebar { width: 250px; min-height: 100vh; background: #2c3e50; color: white; padding: 20px; flex-shrink: 0; }
        .sidebar h2 { font-size: 18px; margin-bottom: 10px; }
        .sidebar hr { border-color: rgba(255,255,255,0.15); margin: 10px 0 20px; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; font-size: 16px; transition: color 0.2s; }
        .sidebar a:hover { color: #3498db; }

        .main { flex: 1; padding: 40px; }
        .welcome h1 { font-size: 28px; color: #2c3e50; }
        .welcome p  { color: #888; margin-top: 4px; font-size: 14px; }

        /* Stats row */
        .stats-row { display: flex; gap: 16px; margin: 28px 0; flex-wrap: wrap; }
        .stat-pill {
            background: white; border-radius: 10px; padding: 14px 22px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 14px;
            flex: 1; min-width: 160px;
        }
        .stat-pill .icon { font-size: 28px; }
        .stat-pill .info .num { font-size: 22px; font-weight: 700; color: #2c3e50; }
        .stat-pill .info .lbl { font-size: 12px; color: #aaa; text-transform: uppercase; letter-spacing: 0.4px; }

        /* App cards */
        .section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #aaa; margin-bottom: 14px; }
        .app-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 16px; }
        .app-card {
            background: white; padding: 26px 20px; border-radius: 12px; text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); cursor: pointer;
            border-bottom: 4px solid #3498db; transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .app-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .app-card .card-icon { font-size: 32px; margin-bottom: 10px; }
        .app-card h3 { font-size: 15px; color: #2c3e50; margin-bottom: 5px; }
        .app-card p  { font-size: 13px; color: #888; }
        .badge {
            position: absolute; top: 12px; right: 12px;
            background: #ef4444; color: white; font-size: 11px; font-weight: 700;
            min-width: 20px; height: 20px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; padding: 0 5px;
        }

        /* Chat */
        #chat-bubble {
            position: fixed; bottom: 24px; right: 24px; width: 56px; height: 56px;
            border-radius: 50%; background: #3498db; color: white; border: none;
            font-size: 24px; cursor: pointer; box-shadow: 0 4px 14px rgba(52,152,219,0.5);
            display: flex; align-items: center; justify-content: center;
            transition: transform 0.2s; z-index: 1000;
        }
        #chat-bubble:hover { transform: scale(1.1); }

        #chat-window {
            position: fixed; bottom: 92px; right: 24px; width: 360px;
            background: white; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.18);
            display: none; flex-direction: column; overflow: hidden; z-index: 1000;
            animation: slideUp 0.25s ease;
        }
        #chat-window.open { display: flex; }
        @keyframes slideUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }

        .chat-header { background: #2c3e50; color: white; padding: 14px 18px; display: flex; align-items: center; gap: 10px; }
        .chat-header .av { width:36px; height:36px; border-radius:50%; background:#3498db; display:flex; align-items:center; justify-content:center; font-size:18px; }
        .chat-header .inf strong { display:block; font-size:14px; }
        .chat-header .inf span { font-size:11px; color:#95a5a6; }
        .chat-header button { background:none; border:none; color:#aaa; font-size:20px; cursor:pointer; margin-left:auto; }

        #chat-messages { padding: 16px; overflow-y: auto; min-height: 280px; max-height: 380px; display: flex; flex-direction: column; gap: 10px; background: #f9f9fb; }
        .msg { max-width: 82%; padding: 10px 14px; border-radius: 14px; font-size: 13.5px; line-height: 1.5; }
        .msg.bot  { background: white; color: #333; border-bottom-left-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.07); align-self: flex-start; }
        .msg.user { background: #3498db; color: white; border-bottom-right-radius: 4px; align-self: flex-end; }

        .chat-footer { padding: 12px; border-top: 1px solid #eee; display: flex; gap: 8px; background: white; }
        .chat-footer input { flex:1; padding:10px 14px; border:1px solid #e0e0e0; border-radius:20px; font-size:13px; outline:none; }
        .chat-footer input:focus { border-color: #3498db; }
        .chat-footer button { width:38px; height:38px; border-radius:50%; background:#3498db; color:white; border:none; cursor:pointer; font-size:16px; }
        .chat-footer button:disabled { background:#bdc3c7; cursor:not-allowed; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Student Portal</h2>
    <hr>
    <a href="student_dash.php">🏠 Home</a>
    <a href="my_courses.php">📚 My Classes</a>
    <a href="view_grades.php">🎓 My Grades</a>
    <a href="assignments.php">📝 Assignments</a>
    <a href="announcements.php">📢 Announcements</a>
    <a href="collaboration.php">📞 Study Groups</a>
    <br><br>
    <a href="logout.php" style="color:#e74c3c; font-weight:bold;">Logout</a>
</div>

<div class="main">
    <div class="welcome">
        <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $display_name)[0]); ?>! 👋</h1>
        <p>Student ID: <strong>#<?php echo $sid; ?></strong> &nbsp;·&nbsp; Spring 2026</p>
    </div>

    <!-- Quick Stats -->
    <div class="stats-row">
        <div class="stat-pill">
            <span class="icon">📚</span>
            <div class="info">
                <div class="num"><?php echo $course_count; ?></div>
                <div class="lbl">Courses</div>
            </div>
        </div>
        <div class="stat-pill">
            <span class="icon">🎓</span>
            <div class="info">
                <div class="num" style="color:<?php echo $gpa_color; ?>"><?php echo $gpa; ?></div>
                <div class="lbl">Current GPA</div>
            </div>
        </div>
        <div class="stat-pill">
            <span class="icon">📝</span>
            <div class="info">
                <div class="num"><?php echo $pending_count; ?></div>
                <div class="lbl">Pending Tasks</div>
            </div>
        </div>
        <div class="stat-pill">
            <span class="icon">📢</span>
            <div class="info">
                <div class="num"><?php echo $announcement_count; ?></div>
                <div class="lbl">New Notices</div>
            </div>
        </div>
    </div>

    <!-- App Cards -->
    <p class="section-title">Quick Access</p>
    <div class="app-grid">
        <div class="app-card" onclick="location.href='my_courses.php'">
            <div class="card-icon">📅</div>
            <h3>My Schedule</h3>
            <p><?php echo $course_count; ?> Course<?php echo $course_count != 1 ? 's' : ''; ?></p>
        </div>
        <div class="app-card" onclick="location.href='view_grades.php'">
            <div class="card-icon">🎓</div>
            <h3>Grades & GPA</h3>
            <p style="color:<?php echo $gpa_color; ?>; font-weight:600;"><?php echo $gpa; ?> / 4.0</p>
        </div>
        <div class="app-card" onclick="location.href='assignments.php'" style="border-bottom-color:#f59e0b;">
            <?php if ($pending_count > 0): ?><span class="badge"><?php echo $pending_count; ?></span><?php endif; ?>
            <div class="card-icon">📝</div>
            <h3>Assignments</h3>
            <p><?php echo $pending_count; ?> pending</p>
        </div>
        <div class="app-card" onclick="location.href='announcements.php'" style="border-bottom-color:#8b5cf6;">
            <?php if ($announcement_count > 0): ?><span class="badge"><?php echo $announcement_count; ?></span><?php endif; ?>
            <div class="card-icon">📢</div>
            <h3>Announcements</h3>
            <p><?php echo $announcement_count; ?> this week</p>
        </div>
        <div class="app-card" style="border-bottom-color:#10b981;">
            <div class="card-icon">💰</div>
            <h3>Finances</h3>
            <p>Balance: $0.00</p>
        </div>
        <div class="app-card" onclick="location.href='collaboration.php'" style="border-bottom-color:#ec4899;">
            <div class="card-icon">📹</div>
            <h3>Study Rooms</h3>
            <p>Join Video Call</p>
        </div>
    </div>
</div>

<!-- AI Chat Bubble -->
<button id="chat-bubble" onclick="toggleChat()" title="Ask SIS AI">💬</button>

<div id="chat-window">
    <div class="chat-header">
        <div class="av">🤖</div>
        <div class="inf">
            <strong>SIS AI Assistant</strong>
            <span>Here to help with your studies</span>
        </div>
        <button onclick="toggleChat()">✕</button>
    </div>
    <div id="chat-messages">
        <div class="msg bot">
            Hi <?php echo htmlspecialchars(explode(' ', $display_name)[0]); ?>! 👋
            You have <b><?php echo $pending_count; ?> pending assignment<?php echo $pending_count != 1 ? 's' : ''; ?></b>
            and a GPA of <b style="color:<?php echo $gpa_color; ?>"><?php echo $gpa; ?></b>.
            How can I help you today?
        </div>
    </div>
    <div class="chat-footer">
        <input type="text" id="chat-input" placeholder="Ask me anything..." onkeydown="if(event.key==='Enter') sendMessage()">
        <button id="send-btn" onclick="sendMessage()">➤</button>
    </div>
</div>

<script>
const STUDENT_NAME  = <?php echo json_encode($display_name); ?>;
const STUDENT_ID    = <?php echo json_encode($sid); ?>;
const ENROLLED      = <?php echo json_encode($courses_list); ?>;
const COURSE_COUNT  = <?php echo json_encode($course_count); ?>;
const GPA           = <?php echo json_encode($gpa); ?>;
const PENDING       = <?php echo json_encode($pending_count); ?>;

let chatHistory = [];

function toggleChat() {
    const w = document.getElementById('chat-window');
    w.classList.toggle('open');
    if (w.classList.contains('open')) document.getElementById('chat-input').focus();
}

function addMessage(text, role) {
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
    const btn   = document.getElementById('send-btn');
    const text  = input.value.trim();
    if (!text) return;
    input.value = '';
    btn.disabled = true;
    addMessage(text, 'user');
    chatHistory.push({ role: 'user', content: text });
    const typing = addMessage('Thinking…', 'bot');

    try {
        const res = await fetch('https://api.anthropic.com/v1/messages', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                model: 'claude-sonnet-4-20250514',
                max_tokens: 1000,
                system: `You are a friendly AI assistant inside a Student Information System (SIS).

Student profile:
- Name: ${STUDENT_NAME} (Student ID: ${STUDENT_ID})
- Enrolled courses (${COURSE_COUNT}): ${ENROLLED}
- Current GPA: ${GPA} / 4.0
- Pending assignments: ${PENDING}

Help with: course questions, study tips, GPA improvement, navigating the SIS portal.
Pages available: My Classes, Grades & GPA, Assignments, Announcements, Study Rooms, Finances.
Be encouraging, concise (2-4 sentences), and use simple HTML like <b> for emphasis.`,
                messages: chatHistory
            })
        });
        const data = await res.json();
        const reply = data.content?.[0]?.text || "Sorry, I couldn't respond. Please try again.";
        chatHistory.push({ role: 'assistant', content: reply });
        typing.className = 'msg bot';
        typing.innerHTML = reply;
        document.getElementById('chat-messages').scrollTop = 9999;
    } catch(e) {
        typing.innerHTML = '⚠️ Connection error. Please try again.';
    }
    btn.disabled = false;
    input.focus();
}
</script>

</body>
</html>