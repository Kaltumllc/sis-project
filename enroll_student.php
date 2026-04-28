<?php
include 'config.php';
session_start();

// Admin Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Handle the Enrollment Submission
if (isset($_POST['enroll'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $course_id = mysqli_real_escape_string($conn, $_POST['course_id']);

    // Check if student is already enrolled in this course
    $check = "SELECT * FROM enrollments WHERE student_id = '$student_id' AND course_id = '$course_id'";
    $check_res = mysqli_query($conn, $check);

    if (mysqli_num_rows($check_res) > 0) {
        echo "<script>alert('Error: Student is already enrolled in this course!');</script>";
    } else {
        $sql = "INSERT INTO enrollments (student_id, course_id) VALUES ('$student_id', '$course_id')";
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Success: Student enrolled in course!'); window.location='enroll_student.php';</script>";
        } else {
            echo "<script>alert('Error: Enrollment failed.');</script>";
        }
    }
}

// Fetch all students and courses for the dropdowns
$students = mysqli_query($conn, "SELECT student_id, full_name FROM students");
$courses = mysqli_query($conn, "SELECT course_id, course_name FROM courses");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Course Enrollment</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f4f7f6; }
        .sidebar { width: 250px; height: 100vh; background: #2c3e50; color: white; padding: 20px; box-sizing: border-box; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; font-size: 18px; }
        .main { flex: 1; padding: 40px; }
        .form-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 400px; }
        select, button { width: 100%; padding: 12px; margin: 10px 0; border-radius: 4px; border: 1px solid #ddd; }
        button { background: #3498db; color: white; border: none; font-weight: bold; cursor: pointer; }
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
        <a href="logout.php" style="color: #e74c3c;">Logout</a>
    </div>

    <div class="main">
        <h1>Course Enrollment</h1>
        <div class="form-box">
            <form method="POST">
                <label>Select Student:</label>
                <select name="student_id" required>
                    <option value="">-- Choose Student --</option>
                    <?php while($s = mysqli_fetch_assoc($students)): ?>
                        <option value="<?php echo $s['student_id']; ?>"><?php echo $s['full_name']; ?></option>
                    <?php endwhile; ?>
                </select>

                <label>Select Course:</label>
                <select name="course_id" required>
                    <option value="">-- Choose Course --</option>
                    <?php while($c = mysqli_fetch_assoc($courses)): ?>
                        <option value="<?php echo $c['course_id']; ?>"><?php echo $c['course_name']; ?></option>
                    <?php endwhile; ?>
                </select>

                <button type="submit" name="enroll">Confirm Enrollment</button>
            </form>
        </div>
    </div>
</body>
</html>