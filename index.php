<?php
include 'config.php';
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = trim(mysqli_real_escape_string($conn, $_POST['password']));
    $query = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password']) || $password == $user['password']) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] == 'admin') {
                header("Location: admin_dash.php");
                exit();
            } else {
                header("Location: student_dash.php");
                exit();
            }
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    } else {
        $error = "No account found with that username.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIS — Student Information System</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --navy: #0a0f1e; --blue: #3b82f6; --blue2: #60a5fa;
            --accent: #06b6d4; --white: #f8fafc; --muted: #94a3b8;
            --border: rgba(255,255,255,0.08); --card: rgba(255,255,255,0.04);
        }
        html, body { height: 100%; font-family: 'DM Sans', sans-serif; background: var(--navy); color: var(--white); overflow: hidden; }

        .bg { position: fixed; inset: 0; z-index: 0;
            background: radial-gradient(ellipse 80% 60% at 20% 10%, rgba(59,130,246,0.18) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 80%, rgba(6,182,212,0.12) 0%, transparent 55%), var(--navy); }
        .grid { position: fixed; inset: 0; z-index: 0;
            background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%); }
        .orb { position: fixed; border-radius: 50%; filter: blur(80px); animation: drift 12s ease-in-out infinite alternate; z-index: 0; pointer-events: none; }
        .orb1 { width: 400px; height: 400px; top: -100px; left: -100px; background: rgba(59,130,246,0.12); }
        .orb2 { width: 300px; height: 300px; bottom: -80px; right: -80px; background: rgba(6,182,212,0.10); animation-delay: -4s; }
        @keyframes drift { from { transform: translate(0,0) scale(1); } to { transform: translate(30px,20px) scale(1.05); } }

        .page { position: relative; z-index: 1; min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr; }

        .left { display: flex; flex-direction: column; justify-content: center; padding: 60px; border-right: 1px solid var(--border); animation: fadeLeft 0.8s ease both; }
        @keyframes fadeLeft { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }

        .logo { display: flex; align-items: center; gap: 12px; margin-bottom: 64px; }
        .logo-icon { width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, var(--blue), var(--accent)); display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; color: white; font-family: 'Syne', sans-serif; box-shadow: 0 0 24px rgba(59,130,246,0.4); }
        .logo-text { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; }
        .logo-text span { color: var(--muted); font-weight: 400; }

        .hero-label { display: inline-flex; align-items: center; gap: 8px; background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.25); border-radius: 20px; padding: 6px 14px; font-size: 12px; font-weight: 500; color: var(--blue2); letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 24px; width: fit-content; }
        .hero-label::before { content: "●"; font-size: 8px; animation: pulse 2s ease infinite; }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.3; } }

        .hero-title { font-family: 'Syne', sans-serif; font-size: clamp(36px, 4vw, 52px); font-weight: 800; line-height: 1.1; letter-spacing: -1.5px; margin-bottom: 20px; }
        .gradient { background: linear-gradient(135deg, var(--blue2), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-desc { font-size: 16px; color: var(--muted); line-height: 1.7; max-width: 400px; margin-bottom: 48px; }

        .features { display: flex; flex-direction: column; gap: 16px; }
        .feature { display: flex; align-items: center; gap: 14px; }
        .feature-dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, var(--blue), var(--accent)); flex-shrink: 0; box-shadow: 0 0 8px rgba(59,130,246,0.5); }
        .feature-text { font-size: 14px; color: var(--muted); }
        .feature-text strong { color: var(--white); font-weight: 500; }

        .right { display: flex; align-items: center; justify-content: center; padding: 60px 80px; animation: fadeRight 0.8s ease 0.2s both; }
        @keyframes fadeRight { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }

        .card { width: 100%; max-width: 400px; background: var(--card); border: 1px solid var(--border); border-radius: 24px; padding: 48px 40px; backdrop-filter: blur(20px); box-shadow: 0 0 0 1px rgba(255,255,255,0.04) inset, 0 32px 64px rgba(0,0,0,0.4); }
        .card-title { font-family: 'Syne', sans-serif; font-size: 26px; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 8px; }
        .card-sub { font-size: 14px; color: var(--muted); margin-bottom: 32px; }

        .error-box { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); border-radius: 10px; padding: 12px 16px; font-size: 13px; color: #f87171; margin-bottom: 20px; }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 12px; font-weight: 500; color: var(--muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; }
        .input-wrap { position: relative; }
        .input-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 15px; pointer-events: none; opacity: 0.4; }
        input[type="text"], input[type="password"] { width: 100%; padding: 13px 14px 13px 42px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 12px; color: var(--white); font-size: 14px; font-family: 'DM Sans', sans-serif; outline: none; transition: all 0.2s; }
        input::placeholder { color: rgba(255,255,255,0.2); }
        input:focus { border-color: rgba(59,130,246,0.5); background: rgba(59,130,246,0.06); box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }

        .btn { width: 100%; padding: 14px; background: linear-gradient(135deg, var(--blue), #2563eb); color: white; border: none; border-radius: 12px; font-size: 15px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; margin-top: 8px; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 20px rgba(59,130,246,0.35); }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 8px 28px rgba(59,130,246,0.45); }

        .divider { display: flex; align-items: center; gap: 12px; margin: 24px 0; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
        .divider span { font-size: 12px; color: var(--muted); }

        .demo-creds { background: rgba(6,182,212,0.06); border: 1px solid rgba(6,182,212,0.15); border-radius: 12px; padding: 16px; }
        .demo-title { font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--accent); font-weight: 600; margin-bottom: 10px; }
        .demo-row { display: flex; justify-content: space-between; font-size: 12px; color: var(--muted); padding: 3px 0; }
        .demo-row span:last-child { color: var(--white); font-family: monospace; }

        .footer-note { text-align: center; margin-top: 24px; font-size: 12px; color: rgba(148,163,184,0.4); }
    </style>
</head>
<body>
<div class="bg"></div>
<div class="grid"></div>
<div class="orb orb1"></div>
<div class="orb orb2"></div>

<div class="page">
    <div class="left">
        <div class="logo">
            <div class="logo-icon">S</div>
            <div class="logo-text">SIS <span>/ Student Information System</span></div>
        </div>
        <div class="hero-label">Live Platform</div>
        <h1 class="hero-title">Your Academic<br>Life, <span class="gradient">Simplified.</span></h1>
        <p class="hero-desc">A unified platform for students and administrators to manage courses, track grades, submit assignments, and collaborate — powered by AI.</p>
        <div class="features">
            <div class="feature"><div class="feature-dot"></div><div class="feature-text"><strong>GPA Tracking</strong> — Real-time 4.0 scale calculation</div></div>
            <div class="feature"><div class="feature-dot"></div><div class="feature-text"><strong>Assignment Submission</strong> — Upload and track deadlines</div></div>
            <div class="feature"><div class="feature-dot"></div><div class="feature-text"><strong>AI Assistant</strong> — Personalized academic guidance</div></div>
            <div class="feature"><div class="feature-dot"></div><div class="feature-text"><strong>Video Study Rooms</strong> — Peer collaboration built-in</div></div>
        </div>
    </div>

    <div class="right">
        <div class="card">
            <div class="card-title">Welcome back 👋</div>
            <div class="card-sub">Sign in to access your portal</div>

            <?php if (!empty($error)): ?>
                <div class="error-box">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username / Email</label>
                    <div class="input-wrap">
                        <span class="input-icon">👤</span>
                        <input type="text" name="username" placeholder="Enter your username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                <button type="submit" class="btn">Sign In →</button>
            </form>

            <div class="divider"><span>demo access</span></div>
            <div class="demo-creds">
                <div class="demo-title">🎓 Test Credentials</div>
                <div class="demo-row"><span>Admin</span><span>admin / password123</span></div>
                <div class="demo-row"><span>Student</span><span>imustap007@gmail.com / password123</span></div>
            </div>
            <div class="footer-note">Built by Mustapha Ibrahim · UMass Lowell INFO 4800</div>
        </div>
    </div>
</div>
</body>
</html>
