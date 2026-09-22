<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
http_response_code(403);

// Redirect target: dashboard for logged-in users, otherwise login/home
$homeUrl = isset($_SESSION['user_id']) ? 'dashboard.php' : 'login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f6f8fb;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --accent-start: #7e22ce;
            --accent-end: #db2777;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            margin: 0;
        }

        .error-card {
            background: var(--card-bg);
            border-radius: 1.5rem;
            padding: 2.75rem 2.25rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
            border: 1px solid #eef2f6;
            text-align: center;
        }

        .error-badge {
            display: inline-block;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            background: #fdf2f8;
            color: #db2777;
            border: 1px solid #fce7f3;
            margin-bottom: 1.25rem;
        }

        .error-title {
            font-size: 1.85rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-start), var(--accent-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
        }

        .error-desc {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--accent-start), var(--accent-end));
            color: #ffffff;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            border: none;
            width: 100%;
            display: block;
            text-decoration: none;
            transition: opacity 0.2s ease, transform 0.15s ease;
        }

        .btn-gradient:hover {
            opacity: 0.92;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: var(--text-dark);
        }
    </style>
</head>
<body>

<div class="error-card">
    <div class="error-badge">Error 403</div>
    <h1 class="error-title">Access Denied</h1>
    <p class="error-desc">
        You do not have the required permissions to view this resource. This page is restricted to administrators or higher roles.
    </p>

    <a href="<?= htmlspecialchars($homeUrl) ?>" class="btn-gradient">Return to Dashboard</a>

    <div>
        <!-- <a href="<?= htmlspecialchars($homeUrl) ?>" class="back-link">&larr; Back to Home</a> -->
    </div>
</div>

</body>
</html>