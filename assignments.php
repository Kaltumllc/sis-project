<?php
include 'config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$s = mysqli_fetch_assoc(mysqli_query($conn, "SELECT student_id, full_name FROM students WHERE email = '" . mysqli_real_escape_string($conn, $username) . "'"));
$sid = $s['student_id'];

// Handle file submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {
    $assignment_id = (int)$_POST['assignment_id'];

    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === 0) {
        $upload_dir = 'uploads/submissions/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $ext      = pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION);
        $filename = 'sub_' . $sid . '_' . $assignment_id . '_' . time() . '.' . $ext;
        $dest     = $upload_dir . $filename;

        $allowed = ['pdf','doc','docx','txt','zip','png','jpg','jpeg'];
        if (!in_array(strtolower($ext), $allowed)) {
            $error = "File type not allowed. Please upload PDF, DOC, DOCX, TXT, ZIP, or an image.";
        } elseif (move_uploaded_file($_FILES['submission_file']['tmp_name'], $dest)) {
            // Upsert: replace old submission if re-submitting
            $check = mysqli_query($conn, "SELECT submission_id FROM submissions 
                WHERE assignment_id = $assignment_id AND student_id = $sid");
            if (mysqli_num_rows($check) > 0) {
                $row = mysqli_fetch_assoc($check);
                mysqli_query($conn, "UPDATE submissions SET file_path='$dest', submission_date=NOW(), status='submitted' 
                    WHERE submission_id=" . $row['submission_id']);
            } else {
                mysqli_query($conn, "INSERT INTO submissions (assignment_id, student_id, file_path) 
                    VALUES ($assignment_id, $sid, '$dest')");
            }
            $success = "Assignment submitted successfully!";
        } else {
            $error = "Upload failed. Please try again.";
        }
    } else {
        $error = "Please choose a file to upload.";
    }
}

// Fetch assignments for enrolled courses
$assignments = mysqli_query($conn, "
    SELECT a.*, c.course_name, c.course_code,
           s.status AS submission_status, s.submission_date AS submitted_on
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id AND e.student_id = $sid
    LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = $sid
    ORDER BY a.due_date ASC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Assignments – SIS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; background: #f4f7f6; }

        .sidebar { width: 250px; min-height: 100vh; background: #2c3e50; color: white; padding: 20px; flex-shrink: 0; }
        .sidebar h2 { font-size: 18px; margin-bottom: 10px; }
        .sidebar hr { border-color: rgba(255,255,255,0.15); margin: 10px 0 20px; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; font-size: 16px; }
        .sidebar a:hover { color: #3498db; }

        .main { flex: 1; padding: 40px; max-width: 900px; }
        h1 { font-size: 26px; color: #2c3e50; margin-bottom: 6px; }
        .subtitle { color: #888; font-size: 14px; margin-bottom: 28px; }

        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert.success { background: #dcfce7; color: #166534; border-left: 4px solid #16a34a; }
        .alert.error   { background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; }

        .assignment-card {
            background: white; border-radius: 12px; margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07); overflow: hidden;
        }
        .card-top {
            padding: 20px 24px; display: flex; justify-content: space-between;
            align-items: flex-start; gap: 16px;
        }
        .card-top .left h3 { font-size: 17px; color: #1e293b; margin-bottom: 4px; }
        .card-top .left .meta { font-size: 13px; color: #888; }
        .card-top .left .meta span { margin-right: 16px; }

        .status-badge {
            padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;
            white-space: nowrap; flex-shrink: 0;
        }
        .status-submitted { background: #dcfce7; color: #16a34a; }
        .status-graded    { background: #dbeafe; color: #2563eb; }
        .status-pending   { background: #fef3c7; color: #d97706; }
        .status-overdue   { background: #fee2e2; color: #dc2626; }

        .due-bar {
            padding: 10px 24px; border-top: 1px solid #f0f0f0;
            font-size: 13px; display: flex; justify-content: space-between; align-items: center;
        }
        .due-bar .due { color: #666; }
        .due-bar .due.overdue { color: #dc2626; font-weight: 600; }

        /* Upload form (collapsible) */
        .upload-section {
            padding: 18px 24px; border-top: 1px solid #f0f0f0; background: #f8fafc;
            display: none;
        }
        .upload-section.open { display: block; }
        .upload-section form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .upload-section input[type="file"] {
            flex: 1; padding: 8px; border: 1px dashed #cbd5e1; border-radius: 6px;
            background: white; font-size: 13px; min-width: 200px;
        }
        .upload-section .btn-submit {
            padding: 9px 20px; background: #2563eb; color: white;
            border: none; border-radius: 6px; cursor: pointer; font-weight: 600;
            font-size: 13px;
        }
        .upload-section .btn-submit:hover { background: #1d4ed8; }
        .upload-section .submitted-info { font-size: 13px; color: #666; }

        .toggle-btn {
            padding: 7px 16px; border: 1px solid #e2e8f0; background: white;
            border-radius: 6px; cursor: pointer; font-size: 13px; color: #2563eb;
            font-weight: 500;
        }
        .toggle-btn:hover { background: #eff6ff; }

        .empty-state { text-align: center; padding: 60px; color: #aaa; background: white; border-radius: 12px; }
        .empty-state .icon { font-size: 48px; margin-bottom: 14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Student Portal</h2>
    <hr>
    <a href="student_dash.php">🏠 Home</a>
    <a href="my_courses.php">📚 My Classes</a>
    <a href="view_grades.php">🎓 My Grades</a>
    <a href="assignments.php" style="color:#3498db; font-weight:600;">📝 Assignments</a>
    <a href="announcements.php">📢 Announcements</a>
    <a href="collaboration.php">📞 Study Groups</a>
    <br><br>
    <a href="logout.php" style="color:#e74c3c; font-weight:bold;">Logout</a>
</div>

<div class="main">
    <h1>📝 My Assignments</h1>
    <p class="subtitle">Assignments from all your enrolled courses</p>

    <?php if (!empty($success)): ?>
        <div class="alert success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($assignments) > 0): ?>
        <?php while ($a = mysqli_fetch_assoc($assignments)):
            $due = new DateTime($a['due_date']);
            $now = new DateTime();
            $overdue = ($now > $due) && empty($a['submission_status']);
            $status = $a['submission_status'] ?? ($overdue ? 'overdue' : 'pending');
            $status_labels = [
                'submitted' => '✅ Submitted',
                'graded'    => '🏅 Graded',
                'pending'   => '⏳ Pending',
                'overdue'   => '🔴 Overdue',
            ];
        ?>
        <div class="assignment-card">
            <div class="card-top">
                <div class="left">
                    <h3><?php echo htmlspecialchars($a['title']); ?></h3>
                    <div class="meta">
                        <span>📚 <?php echo htmlspecialchars($a['course_code']); ?> – <?php echo htmlspecialchars($a['course_name']); ?></span>
                        <?php if ($a['description']): ?>
                            <span style="display:block; margin-top:4px; color:#555;">
                                <?php echo nl2br(htmlspecialchars($a['description'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                    <span class="status-badge status-<?php echo $status; ?>">
                        <?php echo $status_labels[$status]; ?>
                    </span>
                    <?php if ($status !== 'graded'): ?>
                        <button class="toggle-btn" onclick="toggleUpload(<?php echo $a['assignment_id']; ?>)">
                            <?php echo $a['submission_status'] ? '🔄 Re-submit' : '📤 Submit'; ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="due-bar">
                <span class="due <?php echo $overdue ? 'overdue' : ''; ?>">
                    📅 Due: <?php echo $due->format('D, M j Y – g:i A'); ?>
                    <?php if ($overdue): ?> <strong>(OVERDUE)</strong><?php endif; ?>
                </span>
                <?php if ($a['submitted_on']): ?>
                    <span style="color:#16a34a; font-size:12px;">
                        Submitted: <?php echo date('M j, Y g:i A', strtotime($a['submitted_on'])); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Upload Form -->
            <div class="upload-section" id="upload-<?php echo $a['assignment_id']; ?>">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="assignment_id" value="<?php echo $a['assignment_id']; ?>">
                    <input type="file" name="submission_file" accept=".pdf,.doc,.docx,.txt,.zip,.png,.jpg,.jpeg" required>
                    <button type="submit" class="btn-submit">Upload & Submit</button>
                </form>
                <p style="margin-top:8px; font-size:12px; color:#aaa;">
                    Accepted: PDF, DOC, DOCX, TXT, ZIP, PNG, JPG
                </p>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>No assignments posted yet. Check back soon!</p>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleUpload(id) {
    const el = document.getElementById('upload-' + id);
    el.classList.toggle('open');
}
</script>

</body>
</html>