<?php
include 'config.php';
session_start();

// Security: Student only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Peer Collaboration</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f4f7f6; }
        .sidebar { width: 250px; height: 100vh; background: #2c3e50; color: white; padding: 20px; box-sizing: border-box; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; font-size: 18px; }
        .main { flex: 1; padding: 40px; text-align: center; }
        .video-container { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); height: 600px; }
        #meet { height: 100%; width: 100%; border-radius: 8px; overflow: hidden; }
    </style>
    <script src="https://meet.jit.si/external_api.js"></script>
</head>
<body>

<div class="sidebar">
    <h2>Student Portal</h2>
    <hr>
    <a href="student_dash.php">🏠 Home</a>
    <a href="my_courses.php">📚 My Classes</a>
    <a href="collaboration.php">📞 Study Groups</a>
    <br><br>
    <a href="logout.php" style="color: #e74c3c; font-weight: bold;">Logout</a>
</div>

<div class="main">
    <h1>Virtual Study Room</h1>
    <p>Collaborate with your peers in real-time. Share your screen or turn on your mic.</p>
    
    <div class="video-container">
        <div id="meet"></div>
    </div>
</div>

<script>
    const domain = 'meet.jit.si';
    const options = {
        roomName: 'SIS_StudyGroup_2026', // You can make this dynamic later
        width: '100%',
        height: '100%',
        parentNode: document.querySelector('#meet'),
        userInfo: {
            displayName: '<?php echo $_SESSION['username']; ?>'
        }
    };
    const api = new JitsiMeetExternalAPI(domain, options);
</script>

</body>
</html>