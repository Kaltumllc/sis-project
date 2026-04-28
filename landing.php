<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIS — Student Information System</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --navy: #0a0f1e; --blue: #3b82f6; --blue2: #60a5fa;
            --accent: #06b6d4; --white: #f8fafc; --muted: #94a3b8;
            --border: rgba(255,255,255,0.08);
        }
        html { scroll-behavior: smooth; }
        body { font-family: 'DM Sans', sans-serif; background: var(--navy); color: var(--white); overflow-x: hidden; }

        /* BG */
        .bg-mesh {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background:
                radial-gradient(ellipse 80% 60% at 10% 10%, rgba(59,130,246,0.15) 0%, transparent 55%),
                radial-gradient(ellipse 60% 50% at 90% 80%, rgba(6,182,212,0.10) 0%, transparent 55%),
                var(--navy);
        }
        .grid-bg {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* NAV */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            padding: 20px 60px; display: flex; align-items: center; justify-content: space-between;
            background: rgba(10,15,30,0.8); backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; }
        .nav-logo { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--blue), var(--accent)); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 16px; }
        .nav-name { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; }
        .nav-links { display: flex; align-items: center; gap: 32px; }
        .nav-links a { color: var(--muted); text-decoration: none; font-size: 14px; transition: color 0.2s; }
        .nav-links a:hover { color: var(--white); }
        .nav-cta { background: linear-gradient(135deg, var(--blue), #2563eb); color: white; padding: 10px 24px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 16px rgba(59,130,246,0.3); }
        .nav-cta:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(59,130,246,0.4); }

        /* HERO */
        .hero {
            position: relative; z-index: 1;
            min-height: 100vh; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center; padding: 120px 60px 80px;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.25);
            border-radius: 20px; padding: 8px 18px; font-size: 13px; font-weight: 500;
            color: var(--blue2); letter-spacing: 0.5px; text-transform: uppercase;
            margin-bottom: 32px; animation: fadeUp 0.6s ease both;
        }
        .hero-badge::before { content: "●"; font-size: 8px; animation: pulse 2s ease infinite; }
        @keyframes pulse { 0%,100%{opacity:1}50%{opacity:0.3} }

        .hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(48px, 7vw, 88px);
            font-weight: 800; line-height: 1.0; letter-spacing: -3px;
            margin-bottom: 28px;
            animation: fadeUp 0.6s ease 0.1s both;
        }
        .hero h1 .gradient { background: linear-gradient(135deg, var(--blue2), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .hero p {
            font-size: 20px; color: var(--muted); max-width: 600px;
            line-height: 1.7; margin-bottom: 48px;
            animation: fadeUp 0.6s ease 0.2s both;
        }

        .hero-actions {
            display: flex; gap: 16px; align-items: center;
            animation: fadeUp 0.6s ease 0.3s both;
        }
        .btn-primary { background: linear-gradient(135deg, var(--blue), #2563eb); color: white; padding: 16px 36px; border-radius: 12px; text-decoration: none; font-size: 16px; font-weight: 600; transition: all 0.2s; box-shadow: 0 4px 24px rgba(59,130,246,0.35); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(59,130,246,0.5); }
        .btn-secondary { color: var(--muted); padding: 16px 32px; border-radius: 12px; text-decoration: none; font-size: 16px; font-weight: 500; border: 1px solid var(--border); transition: all 0.2s; }
        .btn-secondary:hover { color: var(--white); border-color: rgba(255,255,255,0.2); }

        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

        /* STATS BAR */
        .stats-bar {
            position: relative; z-index: 1;
            display: flex; justify-content: center; gap: 80px;
            padding: 48px 60px; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
            background: rgba(255,255,255,0.02);
        }
        .stat-item { text-align: center; }
        .stat-num { font-family: 'Syne', sans-serif; font-size: 40px; font-weight: 800; background: linear-gradient(135deg, var(--blue2), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { font-size: 14px; color: var(--muted); margin-top: 4px; }

        /* FEATURES */
        .features { position: relative; z-index: 1; padding: 100px 60px; max-width: 1200px; margin: 0 auto; }
        .section-tag { font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--blue2); font-weight: 600; margin-bottom: 16px; }
        .section-title { font-family: 'Syne', sans-serif; font-size: clamp(32px, 4vw, 48px); font-weight: 800; letter-spacing: -1.5px; margin-bottom: 16px; }
        .section-sub { font-size: 18px; color: var(--muted); max-width: 500px; line-height: 1.6; margin-bottom: 60px; }

        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .feature-card {
            background: rgba(255,255,255,0.03); border: 1px solid var(--border);
            border-radius: 20px; padding: 32px;
            transition: all 0.3s;
        }
        .feature-card:hover { background: rgba(59,130,246,0.05); border-color: rgba(59,130,246,0.2); transform: translateY(-4px); }
        .feature-icon { font-size: 36px; margin-bottom: 20px; }
        .feature-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 10px; }
        .feature-desc { font-size: 14px; color: var(--muted); line-height: 1.7; }

        /* ROLES */
        .roles { position: relative; z-index: 1; padding: 80px 60px; background: rgba(255,255,255,0.02); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
        .roles-inner { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .role-card { background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 20px; padding: 40px; }
        .role-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 20px; }
        .role-badge.admin { background: rgba(139,92,246,0.15); color: #a78bfa; border: 1px solid rgba(139,92,246,0.2); }
        .role-badge.student { background: rgba(59,130,246,0.15); color: var(--blue2); border: 1px solid rgba(59,130,246,0.2); }
        .role-title { font-family: 'Syne', sans-serif; font-size: 24px; font-weight: 700; margin-bottom: 16px; }
        .role-list { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .role-list li { font-size: 14px; color: var(--muted); display: flex; align-items: center; gap: 10px; }
        .role-list li::before { content: "✓"; color: var(--accent); font-weight: 700; font-size: 12px; }

        /* CTA */
        .cta-section {
            position: relative; z-index: 1;
            padding: 100px 60px; text-align: center;
        }
        .cta-card {
            max-width: 700px; margin: 0 auto;
            background: linear-gradient(135deg, rgba(59,130,246,0.1), rgba(6,182,212,0.05));
            border: 1px solid rgba(59,130,246,0.2);
            border-radius: 28px; padding: 60px 48px;
        }
        .cta-title { font-family: 'Syne', sans-serif; font-size: 40px; font-weight: 800; letter-spacing: -1.5px; margin-bottom: 16px; }
        .cta-sub { font-size: 18px; color: var(--muted); margin-bottom: 36px; }

        /* FOOTER */
        footer {
            position: relative; z-index: 1;
            padding: 32px 60px; border-top: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .footer-brand { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700; }
        .footer-copy { font-size: 13px; color: var(--muted); }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            nav { padding: 16px 24px; }
            .nav-links { display: none; }
            .hero { padding: 100px 24px 60px; }
            .hero h1 { letter-spacing: -1px; }
            .hero-actions { flex-direction: column; }
            .stats-bar { gap: 40px; padding: 40px 24px; flex-wrap: wrap; }
            .features { padding: 60px 24px; }
            .features-grid { grid-template-columns: 1fr; }
            .roles { padding: 60px 24px; }
            .roles-inner { grid-template-columns: 1fr; }
            .cta-section { padding: 60px 24px; }
            .cta-card { padding: 40px 28px; }
            footer { padding: 24px; flex-direction: column; gap: 12px; text-align: center; }
        }
    </style>
</head>
<body>

<div class="bg-mesh"></div>
<div class="grid-bg"></div>

<!-- NAV -->
<nav>
    <div class="nav-brand">
        <div class="nav-logo">S</div>
        <div class="nav-name">SIS</div>
    </div>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#roles">Who It's For</a>
        <a href="index.php">Login</a>
    </div>
    <a href="index.php" class="nav-cta">Get Started →</a>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-badge">Student Information System</div>
    <h1>Academic Management<br><span class="gradient">Reimagined.</span></h1>
    <p>A unified platform for UMass Lowell students and administrators. Manage courses, track grades, submit assignments, and collaborate — all in one place.</p>
    <div class="hero-actions">
        <a href="index.php" class="btn-primary">Access Portal →</a>
        <a href="#features" class="btn-secondary">Learn More</a>
    </div>
</section>

<!-- STATS -->
<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-num">8+</div>
        <div class="stat-label">Core Features</div>
    </div>
    <div class="stat-item">
        <div class="stat-num">4.0</div>
        <div class="stat-label">GPA Scale</div>
    </div>
    <div class="stat-item">
        <div class="stat-num">AI</div>
        <div class="stat-label">Powered Assistant</div>
    </div>
    <div class="stat-item">
        <div class="stat-num">Live</div>
        <div class="stat-label">Real-time Data</div>
    </div>
</div>

<!-- FEATURES -->
<section class="features" id="features">
    <div class="section-tag">Platform Features</div>
    <h2 class="section-title">Everything you need,<br>nothing you don't.</h2>
    <p class="section-sub">Built for modern academic environments with powerful tools for both students and administrators.</p>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">🎓</div>
            <div class="feature-title">GPA Tracking</div>
            <div class="feature-desc">Real-time 4.0 scale GPA calculation with academic standing labels — Dean's List, Good Standing, and more.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📝</div>
            <div class="feature-title">Assignment System</div>
            <div class="feature-desc">Admins create assignments, students submit files. Full submission tracking with status updates and deadlines.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🤖</div>
            <div class="feature-title">AI Assistant</div>
            <div class="feature-desc">Claude-powered AI chat that knows your courses, GPA, and assignments — giving personalized academic guidance.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📢</div>
            <div class="feature-title">Announcements</div>
            <div class="feature-desc">Admins post institutional notices. Students see real-time updates with "New" badges for recent announcements.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📹</div>
            <div class="feature-title">Study Rooms</div>
            <div class="feature-desc">Built-in Jitsi video collaboration rooms. Students join peer study sessions directly from their dashboard.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <div class="feature-title">Admin Analytics</div>
            <div class="feature-desc">Enrollment distribution charts, student management, course listings, and comprehensive system overview.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🖨️</div>
            <div class="feature-title">Transcripts</div>
            <div class="feature-desc">Students can print official-style academic transcripts with all grades, GPA, and enrollment information.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🌗</div>
            <div class="feature-title">Dark/Light Mode</div>
            <div class="feature-desc">Full theme toggle across the entire platform. User preference is saved automatically for the next session.</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📱</div>
            <div class="feature-title">Mobile Ready</div>
            <div class="feature-desc">Fully responsive design. Access your portal from any device — desktop, tablet, or smartphone.</div>
        </div>
    </div>
</section>

<!-- ROLES -->
<section class="roles" id="roles">
    <div class="roles-inner">
        <div>
            <div class="section-tag">Built For Everyone</div>
            <h2 class="section-title" style="margin-bottom:16px;">Two roles.<br>One platform.</h2>
            <p style="font-size:16px; color:var(--muted); line-height:1.7;">Whether you're managing an institution or navigating your academic journey, SIS has the right tools for you.</p>
        </div>
        <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="role-card">
                <div class="role-badge admin">Administrator</div>
                <div class="role-title">Full System Control</div>
                <ul class="role-list">
                    <li>Add and manage students & courses</li>
                    <li>Create assignments and grade submissions</li>
                    <li>Post announcements to all students</li>
                    <li>View enrollment analytics & reports</li>
                    <li>Enter and manage grade records</li>
                </ul>
            </div>
            <div class="role-card">
                <div class="role-badge student">Student</div>
                <div class="role-title">Your Academic Hub</div>
                <ul class="role-list">
                    <li>View enrolled courses and schedule</li>
                    <li>Track GPA and academic standing</li>
                    <li>Submit assignments and track deadlines</li>
                    <li>Read announcements and notices</li>
                    <li>Join video study rooms with peers</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="cta-card">
        <div class="cta-title">Ready to get started?</div>
        <p class="cta-sub">Access your student or admin portal now. Built for UMass Lowell INFO 4800.</p>
        <a href="index.php" class="btn-primary" style="display:inline-block;">Sign In to Portal →</a>
        <div style="margin-top:24px; font-size:13px; color:var(--muted);">
            Admin: <code style="color:var(--blue2);">admin / password123</code> &nbsp;·&nbsp;
            Student: <code style="color:var(--blue2);">imustap007@gmail.com / password123</code>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="footer-brand">SIS — Student Information System</div>
    <div class="footer-copy">Built by Mustapha Ibrahim · UMass Lowell INFO 4800 · Spring 2026</div>
</footer>

</body>
</html>
