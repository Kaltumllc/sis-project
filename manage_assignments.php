<?php
include 'config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Create new assignment
if (isset($_POST['create_assignment'])) {
    $course_id   = (int)$_POST['course_id'];
    $title       = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $due_date    = mysqli_real_escape_string($conn, $_POST['due_date']);
    mysqli_query($conn, "INSERT INTO assignments (course_id, title, description, due_date) 
                         VALUES ($course_id, '$title', '$description', '$due_date')");
    echo "<script>alert('Assignment created!'); window.location='manage_assignments.php';</script>";
}

// Delete assignment
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM assignments WHERE assignment_id = $id");
    echo "<script>window.location='manage_assignments.php';</script>";
}

$courses     = mysqli_query($conn, "SELECT * FROM courses ORDER BY course_code");
$assignments = mysqli_query($conn, "
    SELECT a.*, c.course_name, c.course_code,
           COUNT(s.submission_id) AS submission_count
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id
    GROUP BY a.assignment_id
    ORDER BY a.due_date DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Assignments – SIS Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; background: #f4f7f6; }

        .sidebar { width: 250px; min-height: 100vh; background: #2c3e50; color: white; padding: 20px; flex-shrink: 0; }
        .sidebar h2 { font-size: 18px; margin-bottom: 10px; }
        .sidebar hr { border-color: rgba(255,255,255,0.15); margin: 10px 0 20px; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; font-size: 16px; }
        .sidebar a:hover { color: #3498db; }

        .main { flex: 1; padding: 40px; }
        h1 { font-size: 26px; color: #2c3e50; margin-bottom: 28px; }

        .form-card { background: white; padding: 28px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); margin-bottom: 28px; }
        .form-card h3 { margin-bottom: 18px; color: #2c3e50; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-grid .full { grid-column: 1 / -1; }
        label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        input, select, textarea {
            width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0;
            border-radius: 6px; font-size: 14px; font-family: inherit;
        }
        textarea { resize: vertical; min-height: 80px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #3498db; }
        .btn-create { background: #2563eb; color: white; border: none; padding: 11px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; margin-top: 6px; }
        .btn-create:hover { background: #1d4ed8; }

        table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
        th { background: #2c3e50; color: white; padding: 13px 16px; text-align: left; font-size: 13px; }
        td { padding: 13px 16px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .sub-count { background: #dbeafe; color: #2563eb; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .btn-delete { color: #dc2626; text-decoration: none; font-size: 13px; }
        .btn-view   { color: #2563eb; text-decoration: none; font-size: 13px; margin-right: 10px; }
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
    <a href="manage_assignments.php" style="color:#3498db; font-weight:600;">📋 Assignments</a>
    <a href="manage_announcements.php">📢 Announcements</a>
    <br><br>
    <a href="logout.php" style="color:#e74c3c; font-weight:bold;">Logout</a>
</div>

<div class="main">
    <h1>📋 Manage Assignments</h1>

    <div class="form-card">
        <h3>➕ Create New Assignment</h3>
        <form method="POST">
            <div class="form-grid">
                <div>
                    <label>Course</label>
                    <select name="course_id" required>
                        <option value="">-- Select Course --</option>
                        <?php while ($c = mysqli_fetch_assoc($courses)): ?>
                            <option value="<?php echo $c['course_id']; ?>">
                                <?php echo htmlspecialchars($c['course_code'] . ' – ' . $c['course_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label>Due Date & Time</label>
                    <input type="datetime-local" name="due_date" required>
                </div>
                <div class="full">
                    <label>Assignment Title</label>
                    <input type="text" name="title" placeholder="e.g. Lab Report 1" required>
                </div>
                <div class="full">
                    <label>Description (Optional)</label>
                    <textarea name="description" placeholder="Instructions, requirements, notes..."></textarea>
                </div>
            </div>
            <button type="submit" name="create_assignment" class="btn-create">Create Assignment</button>
        </form>
    </div>

    <table>
        <tr>
            <th>Title</th>
            <th>Course</th>
            <th>Due Date</th>
            <th>Submissions</th>
            <th>Actions</th>
        </tr>
        <?php if (mysqli_num_rows($assignments) > 0): ?>
            <?php while ($a = mysqli_fetch_assoc($assignments)): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($a['title']); ?></strong></td>
                <td><?php echo htmlspecialchars($a['course_code']); ?></td>
                <td><?php echo date('M j, Y g:i A', strtotime($a['due_date'])); ?></td>
                <td><span class="sub-count"><?php echo $a['submission_count']; ?> submitted</span></td>
                <td>
                    <a href="view_submissions.php?assignment_id=<?php echo $a['assignment_id']; ?>" class="btn-view">📂 View Submissions</a>
                    <a href="manage_assignments.php?delete=<?php echo $a['assignment_id']; ?>"
                       onclick="return confirm('Delete this assignment?')" class="btn-delete">🗑️ Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center; color:#aaa; padding:30px;">No assignments yet. Create one above!</td></tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>