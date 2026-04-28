<?php
include 'config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['username'];
$sql_student = "SELECT student_id FROM students WHERE email = '$email'";
$res_student = mysqli_query($conn, $sql_student);
$student = mysqli_fetch_assoc($res_student);
$sid = $student['student_id'];

// SQL JOIN: Gets course details for this specific student
$sql_courses = "SELECT courses.course_name, courses.course_code 
                FROM enrollments 
                JOIN courses ON enrollments.course_id = courses.course_id 
                WHERE enrollments.student_id = '$sid'";
$result = mysqli_query($conn, $sql_courses);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Courses</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f4f7f6; }
        .sidebar { width: 250px; height: 100vh; background: #2c3e50; color: white; padding: 20px; box-sizing: border-box; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; font-size: 18px; }
        .main { flex: 1; padding: 40px; }
        .course-list { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .course-item { padding: 15px; border-bottom: 1px solid #eee; }
        .course-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Student Portal</h2>
        <hr>
        <a href="student_dash.php">🏠 Home</a>
        <a href="my_courses.php">📚 My Classes</a>
        <a href="logout.php" style="color: #e74c3c;">Logout</a>
    </div>
    <div class="main">
        <h1>My Enrolled Courses</h1>
        <div class="course-list">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <div class="course-item">
                        <strong><?php echo $row['course_code']; ?></strong>: <?php echo $row['course_name']; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>You are not enrolled in any courses yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>