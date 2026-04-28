<?php
include 'config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// --- SEARCH LOGIC ---
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search']);
    // Search both the code and the name
    $sql = "SELECT * FROM courses WHERE course_code LIKE '%$search_query%' OR course_name LIKE '%$search_query%' ORDER BY course_code ASC";
} else {
    $sql = "SELECT * FROM courses ORDER BY course_code ASC";
}
$result = mysqli_query($conn, $sql);

// Handle deleting a course
if (isset($_GET['delete_course'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_course']);
    $sql_del = "DELETE FROM courses WHERE course_id = '$id'";
    if (mysqli_query($conn, $sql_del)) {
        echo "<script>alert('Course deleted!'); window.location='manage_courses.php';</script>";
    } else {
        echo "<script>alert('Error: Cannot delete course. Students might be enrolled in it.');</script>";
    }
}

// Handle adding a new course
if (isset($_POST['add_course'])) {
    $code = mysqli_real_escape_string($conn, $_POST['course_code']);
    $name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $sql_add = "INSERT INTO courses (course_code, course_name) VALUES ('$code', '$name')";
    if (mysqli_query($conn, $sql_add)) {
        echo "<script>alert('Course added successfully!'); window.location='manage_courses.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Courses</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f4f7f6; }
        .sidebar { width: 250px; height: 100vh; background: #2c3e50; color: white; padding: 20px; box-sizing: border-box; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; font-size: 18px; }
        .main { flex: 1; padding: 40px; }
        .search-box, .form-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        input { padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 250px; }
        button { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-add { background: #27ae60; color: white; }
        .btn-search { background: #3498db; color: white; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #2c3e50; color: white; }
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
        <h1>Course Management</h1>

        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" placeholder="Search by code or name..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="btn-search">🔍 Search</button>
                <?php if($search_query): ?>
                    <a href="manage_courses.php" style="margin-left:10px; color:#e74c3c; text-decoration:none;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="form-box">
            <h3>Add New Course</h3>
            <form method="POST">
                <input type="text" name="course_code" placeholder="e.g. CS101" required>
                <input type="text" name="course_name" placeholder="Course Name" required style="width: 350px;">
                <button type="submit" name="add_course" class="btn-add">Add Course</button>
            </form>
        </div>

        <table>
            <tr>
                <th>Code</th>
                <th>Course Name</th>
                <th>Action</th>
            </tr>
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['course_code']; ?></td>
                    <td><?php echo $row['course_name']; ?></td>
                    <td>
                        <a href="manage_courses.php?delete_course=<?php echo $row['course_id']; ?>" 
                           onclick="return confirm('Delete this course?')" 
                           style="color:#e74c3c; text-decoration:none;">🗑️ Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align:center;">No courses found matching your search.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>