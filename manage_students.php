<?php
include 'config.php';
session_start();

// Admin Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Handle adding a new student AND creating their login
if (isset($_POST['add_student'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Hash the password for security
    $default_password = 'password123';
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

    // 1. Insert into students table
    $sql_student = "INSERT INTO students (full_name, email) VALUES ('$name', '$email')";
    
    if (mysqli_query($conn, $sql_student)) {
        // 2. Automatically insert into users table for login access
        $sql_user = "INSERT INTO users (username, password, role) VALUES ('$email', '$hashed_password', 'student')";
        mysqli_query($conn, $sql_user);
        
        echo "<script>alert('Success: Student profile and login account created!'); window.location='manage_students.php';</script>";
    } else {
        echo "<script>alert('Error: Email might already exist.');</script>";
    }
}

// --- SEARCH & DISPLAY LOGIC ---
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search']);
    $sql = "SELECT * FROM students WHERE full_name LIKE '%$search_query%' OR email LIKE '%$search_query%' ORDER BY enrollment_date DESC";
} else {
    $sql = "SELECT * FROM students ORDER BY enrollment_date DESC";
}
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Students</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f4f7f6; }
        .sidebar { width: 250px; height: 100vh; background: #2c3e50; color: white; padding: 20px; box-sizing: border-box; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; font-size: 18px; }
        .sidebar a:hover { color: #3498db; }
        .main { flex: 1; padding: 40px; }
        .form-box, .search-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
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
        <h1>Student Management</h1>

        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" placeholder="Search by name..." value="<?php echo $search_query; ?>">
                <button type="submit" class="btn-search">🔍 Search</button>
                <?php if($search_query): ?>
                    <a href="manage_students.php" style="margin-left:10px; text-decoration:none; color:#e74c3c;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="form-box">
            <h3>Add New Student</h3>
            <form method="POST">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email Address" required>
                <button type="submit" name="add_student" class="btn-add">Add Student</button>
            </form>
        </div>

        <table>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Enrollment Date</th>
            </tr>
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['student_id']; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['enrollment_date']; ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">No students found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>