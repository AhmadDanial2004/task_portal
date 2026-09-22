<?php
session_start();
require_once '../src/config/database.php';
require_once '../src/helpers/validation.php';

// If already logged in, go straight to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
// Basic login processing using PDO prepared statements
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'Active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password and regenerate session ID for security
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            session_regenerate_id(true);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Invalid email or password, or account is inactive.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management Portal Login</title>
    <!-- Satisfies the mobile-responsive requirement using Bootstrap.-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.cdnfonts.com/css/telegraf" rel="stylesheet"> 
    <style>
        body {
            /* The CSS gradient background is kept as the base layer */
            background: linear-gradient(135deg, #86469C 0%, #E96479 100%);
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
            color: #ffffff;
            height: 100vh;
            overflow: hidden;
            margin: 0;
            display: flex;
            align-items: center;
        }

        /* Canvas Background Animation Layer */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0; /* Keeps the animation behind the UI */
        }
        
        canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        /*Smooth View Transition System*/
        .view-container {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.65s cubic-bezier(0.22, 1, 0.36, 1), 
                        transform 0.65s cubic-bezier(0.22, 1, 0.36, 1);
            will-change: opacity, transform;
        }

        .view-active {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
            z-index: 2; /* Brings active UI to the front */
        }

        .view-exit-up {
            opacity: 0;
            transform: translateY(-40px) scale(0.96);
            pointer-events: none;
            z-index: 1;
        }

        .view-exit-down {
            opacity: 0;
            transform: translateY(40px) scale(0.96);
            pointer-events: none;
            z-index: 1;
        }

        /*Page 1: Intro Styling*/
        .intro-content {
            padding: 40px;
            max-width: 900px; 
            width: 100%;
            text-align: center;
        }

        .animated-title {
            font-family: 'Telegraf', 'Inter', sans-serif;
            font-size: 5rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 0.5rem;
            text-shadow: 0 0 10px #FF90BC, 0 0 20px #FF90BC, 0 0 40px #FF90BC;
        }

        .animated-title-content {
            display: flex;
            align-items: flex-start;
            overflow: hidden;
            height: 6rem;
            position: relative;
            padding: 0 40px;
        }

        .animated-title-content::before,
        .animated-title-content::after {
            position: absolute;
            top: 0;
            color: #ffd1e3;
            line-height: 6rem;
            animation: bracketOpacity 2s infinite;
        }

        .animated-title-content::before { content: '['; left: 0; }
        .animated-title-content::after { content: ']'; right: 0; }

        .static-text {
            margin: 0;
            line-height: 6rem;
            display: inline-block;
            margin-right: 20px;
        }

        .dynamic-list {
            margin: 0;
            padding: 0;
            list-style: none;
            text-align: left;
            animation: textChange 10s infinite;
        }

        .dynamic-item {
            line-height: 6rem;
            margin: 0;
            height: 6rem;
        }

        @keyframes bracketOpacity {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }

        @keyframes textChange {
            0%, 15%, 100% { transform: translate3d(0, 0, 0); }
            25%, 40%, 75%, 90% { transform: translate3d(0, -33.33%, 0); }
            50%, 65% { transform: translate3d(0, -66.66%, 0); }
        }

        .brand-subtitle {
            font-size: 2rem;
            font-weight: 300;
            line-height: 1.3;
            margin-bottom: 1rem;
            display: inline-block;
            vertical-align: middle;
            text-align: left;
        }

        .company-name {
            font-size: 1.5rem;
            font-weight: 600;
            opacity: 0.9;
            display: inline-block;
            vertical-align: middle;
            margin-left: 25px;
            padding-left: 25px;
            border-left: 2px solid rgba(255, 255, 255, 0.5);
        }

        .text-group {
            margin-bottom: 4rem;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-glow {
            background: transparent;
            color: #ffffff;
            border: 2px solid #ffffff;
            border-radius: 30px; 
            padding: 12px 50px;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
            cursor: pointer;
        }

        .btn-glow:hover {
            background: #ffffff;
            color: #86469C;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.9); 
            transform: translateY(-3px) scale(1.03);
        }

        /*Page 2: Login Form Styling*/
        .login-box {
            background-color: #ffffff;
            border-radius: 24px; 
            padding: 50px 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            color: #333333;
            transition: transform 0.3s ease;
        }

        .login-box h3 {
            font-weight: 700;
            color: #86469C;
            margin-bottom: 30px;
        }

        .form-control {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 20px;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #86469C;
            box-shadow: 0 0 0 4px rgba(134, 70, 156, 0.15);
        }

        .btn-submit {
            background: linear-gradient(135deg, #86469C 0%, #E96479 100%);
            border: none;
            color: #fff;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: transform 0.2s, opacity 0.2s;
        }

        .btn-submit:hover {
            opacity: 0.92;
            color: #fff;
            transform: translateY(-1px);
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #6c757d;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
            transition: color 0.2s;
        }

        .back-link:hover { color: #86469C; }

        @media (max-width: 768px) {
            .brand-subtitle { text-align: center; }
            .company-name { 
                margin-left: 0; padding-left: 0; border-left: none; display: block; margin-top: 15px;
            }
        }
    </style>
</head>
<body>

    <!-- Interactive Canvas Background Layer -->
    <div class="bg-animation">
        <canvas id="bg-canvas"></canvas>
    </div>

    <!-- Page 1: Introduction Entry -->
    <div id="intro-view" class="view-container <?= empty($error) ? 'view-active' : 'view-exit-up' ?>">
        <div class="intro-content">
            <div class="animated-title">
                <div class="animated-title-content">
                    <p class="static-text">Hello</p>
                    <ul class="dynamic-list">
                        <li class="dynamic-item">Interns!</li>
                        <li class="dynamic-item">Team!</li>
                        <li class="dynamic-item">Users!</li>
                    </ul>
                </div>
            </div>
            <div class="text-group text-center">
                <div class="brand-subtitle" style="text-align: center;">Internship<br>Task Management<br>Portal</div>
            </div>
            
            <button type="button" class="btn-glow" onclick="showLogin()">Log in</button>
        </div>
    </div>

    <!-- Page 2: Login Form Portal -->
    <div id="login-view" class="view-container <?= empty($error) ? 'view-exit-down' : 'view-active' ?>">
        <div class="login-box text-center">
            <h3>Portal Login</h3>
            
            <?php if ($error): ?>
                <div class="alert alert-danger rounded-3 fs-6 p-2 mb-4"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="text-start">
                    <label class="form-label text-muted fw-semibold ms-1 mb-1">Email</label>
                    <input type="email" name="email" class="form-control" required autocomplete="email">
                </div>
                <div class="text-start">
                    <label class="form-label text-muted fw-semibold ms-1 mb-1">Password</label>
                    <input type="password" name="password" class="form-control" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-submit mt-3">Login</button>
            </form>
            
            <a class="back-link" onclick="showIntro()">← Back to Home</a>
            
            <div class="mt-4 text-muted" style="font-size: 0.8rem;">
                Demonstration Access:<br>
                admin@example.test | InternDemo!2026<br>
                member1@example.test | InternDemo!2026
            </div>
        </div>
    </div>

    <!-- UI View Transition Logic -->
    <script>
        const introView = document.getElementById('intro-view');
        const loginView = document.getElementById('login-view');

        function showLogin() {
            introView.classList.remove('view-active', 'view-exit-down');
            introView.classList.add('view-exit-up');

            loginView.classList.remove('view-exit-down', 'view-exit-up');
            loginView.classList.add('view-active');
        }

        function showIntro() {
            loginView.classList.remove('view-active');
            loginView.classList.add('view-exit-down');

            introView.classList.remove('view-exit-up');
            introView.classList.add('view-active');
        }
    </script>

    <!-- Canvas Animation Logic -->
    <script>
        // Customizations for light soft pink dots
        var gap = 40;
        var radiusVmin = 30;
        var speedIn = 0.5;
        var speedOut = 0.6;
        var restScale = 0.09;
        var minHoverScale = 1;
        var maxHoverScale = 3;
        var waveSpeed = 1200;
        var waveWidth = 180;

        // Palette restricted to soft light pinks
        var PALETTE = [
            { type: 'solid', value: '#ffd1e3' },
            { type: 'solid', value: '#ffb6c1' },
            { type: 'solid', value: '#ffc0cb' },
            { type: 'solid', value: '#ffa6c9' }
        ];

        var SHAPE_TYPES = ['circle'];

        var canvas = document.getElementById('bg-canvas');
        var ctx = canvas.getContext('2d');

        var grid = null;
        var rafId = null;
        var pointer = null;
        var activity = 0;
        var waves = [];
        var maskRects = [];
        var frameCount = 0;
        var maskOverride = false;

        function rnd(min, max) { return Math.random() * (max - min) + min; }
        function pick(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

        function smoothstep(t) {
            var c = Math.max(0, Math.min(1, t));
            return c * c * (3 - 2 * c);
        }

        function durationToFactor(seconds) {
            if (seconds <= 0) return 1;
            return 1 - Math.pow(0.05, 1 / (60 * seconds));
        }

        function drawCircle(ctx, size) {
            ctx.beginPath();
            ctx.arc(0, 0, size, 0, Math.PI * 2);
            ctx.fill();
        }

        function buildGrid() {
            var W = window.innerWidth;
            var H = window.innerHeight;
            var cols = Math.floor(W / gap);
            var rows = Math.floor(H / gap);
            var offsetX = (W - (cols - 1) * gap) / 2;
            var offsetY = (H - (rows - 1) * gap) / 2;
            var shapes = [];

            for (var row = 0; row < rows; row++) {
                for (var col = 0; col < cols; col++) {
                    var shape = {
                        x: offsetX + col * gap,
                        y: offsetY + row * gap,
                        color: pick(PALETTE),
                        angle: rnd(0, Math.PI * 2),
                        size: gap * 0.38,
                        scale: restScale,
                        maxScale: rnd(minHoverScale, maxHoverScale),
                        hovered: false,
                    };
                    shapes.push(shape);
                }
            }
            return { shapes: shapes, width: W, height: H };
        }

        function init() {
            var W = window.innerWidth;
            var H = window.innerHeight;
            var dpr = window.devicePixelRatio || 1;

            canvas.width = W * dpr;
            canvas.height = H * dpr;
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.scale(dpr, dpr);

            grid = buildGrid();
        }

        function tick() {
            if (!grid) { rafId = requestAnimationFrame(tick); return; }

            var shapes = grid.shapes;
            var width = grid.width;
            var height = grid.height;
            var radius = Math.min(width, height) * (radiusVmin / 100);
            var now = performance.now();

            // Clear the canvas, but DO NOT fill it with black.
            // This allows the CSS linear-gradient to show through cleanly!
            ctx.clearRect(0, 0, width, height);

            activity *= 0.93;
            frameCount++;

            var maxDist = Math.sqrt(width * width + height * height);
            waves = waves.filter(function(w) {
                return (now - w.startTime) / 1000 * waveSpeed < maxDist + waveWidth;
            });

            for (var i = 0; i < shapes.length; i++) {
                var shape = shapes[i];

                var pointerInfluence = 0;
                if (pointer && activity > 0.001) {
                    var dx = shape.x - pointer.x;
                    var dy = shape.y - pointer.y;
                    var dist = Math.sqrt(dx * dx + dy * dy);
                    pointerInfluence = smoothstep(1 - dist / radius) * activity;

                    if (pointerInfluence > 0.05 && !shape.hovered) {
                        shape.hovered = true;
                        shape.maxScale = rnd(minHoverScale, maxHoverScale);
                    } else if (pointerInfluence <= 0.05) {
                        shape.hovered = false;
                    }
                } else {
                    shape.hovered = false;
                }

                var waveInfluence = 0;
                for (var j = 0; j < waves.length; j++) {
                    var wave = waves[j];
                    var waveRadius = (now - wave.startTime) / 1000 * waveSpeed;
                    var wdx = shape.x - wave.x;
                    var wdy = shape.y - wave.y;
                    var wdist = Math.sqrt(wdx * wdx + wdy * wdy);
                    var t = 1 - Math.abs(wdist - waveRadius) / waveWidth;
                    if (t > 0) waveInfluence = Math.max(waveInfluence, Math.sin(Math.PI * t));
                }

                var pointerTarget = restScale + pointerInfluence * (shape.maxScale - restScale);
                var waveTarget = restScale + waveInfluence * (shape.maxScale - restScale);
                var target = Math.max(pointerTarget, waveTarget);

                var factor = target > shape.scale ? durationToFactor(speedIn) : durationToFactor(speedOut);
                shape.scale += (target - shape.scale) * factor;

                if (shape.scale < restScale * 0.15) continue;

                ctx.save();
                ctx.translate(shape.x, shape.y);
                ctx.rotate(shape.angle);
                ctx.scale(shape.scale, shape.scale);
                ctx.fillStyle = shape.color.value;
                drawCircle(ctx, shape.size / 1.5);
                ctx.restore();
            }

            rafId = requestAnimationFrame(tick);
        }

        function onMove(e) {
            pointer = { x: e.clientX, y: e.clientY };
            activity = 1;
        }

        function onClick(e) {
            triggerWave(e.clientX, e.clientY);
        }

        function triggerWave(x, y) {
            x = x !== undefined ? x : window.innerWidth / 2;
            y = y !== undefined ? y : window.innerHeight / 2;
            waves.push({ x: x, y: y, startTime: performance.now() });
        }

        init();
        rafId = requestAnimationFrame(tick);

        window.addEventListener('resize', init);
        window.addEventListener('pointermove', onMove);
        window.addEventListener('click', onClick);

        // Initial wave burst on load
        triggerWave();
    </script>
</body>
</html>