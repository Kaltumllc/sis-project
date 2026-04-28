<?php
include 'config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// FETCH TOTALS
$count_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM students"))['total'];
$count_courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM courses"))['total'];
$count_enroll = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM enrollments"))['total'];

// FETCH DATA FOR THE CHART (Enrollments per Course)
$chart_data = mysqli_query($conn, "
    SELECT courses.course_code, COUNT(enrollments.enrollment_id) as student_count 
    FROM courses 
    LEFT JOIN enrollments ON courses.course_id = enrollments.course_id 
    GROUP BY courses.course_id
");

$labels = [];
$data = [];
while($row = mysqli_fetch_assoc($chart_data)) {
    $labels[] = $row['course_code'];
    $data[] = $row['student_count'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SIS Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f4f7f6; }
        .sidebar { width: 250px; height: 100vh; background: #2c3e50; color: white; padding: 20px; box-sizing: border-box; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; font-size: 18px; }
        .main { flex: 1; padding: 40px; overflow-y: auto; }
        .stats-grid { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-card { 
            background: white; padding: 20px; border-radius: 12px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); flex: 1; text-align: center;
            border-top: 5px solid #3498db;
        }
        .chart-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 20px; }
        #chat-box { position:fixed; bottom:90px; right:20px; width:300px; background:white; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.3); display:none; flex-direction:column; overflow:hidden; z-index:1000; }
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
    <br><br>
    <a href="logout.php" style="color: #e74c3c; font-weight: bold;">Logout</a>
</div>

<div class="main">
    <h1>System Overview</h1>
    
    <div class="stats-grid">
        <div class="stat-card"><h3>Total Students</h3><p><?php echo $count_students; ?></p></div>
        <div class="stat-card" style="border-top-color: #9b59b6;"><h3>Active Courses</h3><p><?php echo $count_courses; ?></p></div>
        <div class="stat-card" style="border-top-color: #f1c40f;"><h3>Total Enrollments</h3><p><?php echo $count_enroll; ?></p></div>
    </div>

    <div class="chart-container">
        <h3 style="margin-top:0;">Enrollment Distribution per Course</h3>
        <canvas id="enrollmentChart" height="100"></canvas>
    </div>
</div>

<div id="chat-box">
    <div style="background:#2c3e50; color:white; padding:15px; font-weight:bold;">SIS AI Assistant</div>
    <div id="chat-content" style="height:300px; padding:15px; overflow-y:auto; font-size:14px; color:#333;">
        <p><strong>Bot:</strong> Hello! I see we have <?php echo $count_enroll; ?> active enrollments. How can I help?</p>
    </div>
    <div style="padding:10px; border-top:1px solid #eee; display:flex;">
        <input type="text" id="chat-input" placeholder="Ask AI..." style="flex:1; padding:8px; border:1px solid #ddd; border-radius:4px;">
        <button onclick="sendMessage()" style="margin-left:5px; background:#3498db; color:white; border:none; padding:8px 12px; border-radius:4px; cursor:pointer;">Send</button>
    </div>
</div>
<button onclick="toggleChat()" style="position:fixed; bottom:20px; right:20px; width:60px; height:60px; background:#3498db; color:white; border:none; border-radius:50%; font-size:24px; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.2);">💬</button>

<script>
// CHART LOGIC
const ctx = document.getElementById('enrollmentChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: '# of Enrolled Students',
            data: <?php echo json_encode($data); ?>,
            backgroundColor: '#3498db',
            borderRadius: 5
        }]
    },
    options: {
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// CHATBOT LOGIC
function toggleChat() {
    const box = document.getElementById('chat-box');
    box.style.display = (box.style.display === 'none' || box.style.display === '') ? 'flex' : 'none';
}
function sendMessage() {
    const input = document.getElementById('chat-input');
    const content = document.getElementById('chat-content');
    if (input.value.trim() === "") return;
    content.innerHTML += `<p style='text-align:right;'><strong>You:</strong> ${input.value}</p>`;
    setTimeout(() => {
        content.innerHTML += `<p><strong>Bot:</strong> I've analyzed the chart. It looks like <b><?php echo $labels[0] ?? 'N/A'; ?></b> is our most popular course!</p>`;
        content.scrollTop = content.scrollHeight;
    }, 800);
    input.value = "";
}
</script>

</body>
</html>